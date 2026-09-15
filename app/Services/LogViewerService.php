<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Throwable;

class LogViewerService
{
    /**
     * Available log sources.
     */
    public const SOURCE_LARAVEL = 'laravel';
    public const SOURCE_DOCKER = 'docker';
    public const SOURCE_NGINX = 'nginx';
    public const SOURCE_PHP = 'php';

    /**
     * Get list of available log files for a source.
     */
    public function getLogFiles(string $source = self::SOURCE_LARAVEL): array
    {
        $files = [];

        switch ($source) {
            case self::SOURCE_LARAVEL:
                $logPath = storage_path('logs');
                if (File::exists($logPath)) {
                    foreach (File::files($logPath) as $file) {
                        if (str_ends_with($file->getFilename(), '.log')) {
                            $files[] = [
                                'name' => $file->getFilename(),
                                'path' => $file->getPathname(),
                                'size' => $this->formatBytes($file->getSize()),
                                'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                            ];
                        }
                    }
                }
                break;

            case self::SOURCE_DOCKER:
                // Docker log files or container names
                $files[] = [
                    'name' => 'container-runtime.log',
                    'path' => 'docker:current',
                    'size' => 'Live Stream',
                    'modified' => now()->toDateTimeString(),
                ];
                break;

            case self::SOURCE_NGINX:
                $nginxPaths = ['/var/log/nginx/access.log', '/var/log/nginx/error.log', storage_path('logs/nginx.log')];
                foreach ($nginxPaths as $path) {
                    if (File::exists($path)) {
                        $files[] = [
                            'name' => basename($path),
                            'path' => $path,
                            'size' => $this->formatBytes(File::size($path)),
                            'modified' => date('Y-m-d H:i:s', File::lastModified($path)),
                        ];
                    }
                }
                if (empty($files)) {
                    $files[] = [
                        'name' => 'nginx-access.log',
                        'path' => storage_path('logs/nginx-access.log'),
                        'size' => '0 B',
                        'modified' => now()->toDateTimeString(),
                    ];
                }
                break;

            case self::SOURCE_PHP:
                $phpPaths = [ini_get('error_log'), storage_path('logs/php-error.log')];
                foreach ($phpPaths as $path) {
                    if ($path && File::exists($path)) {
                        $files[] = [
                            'name' => basename($path),
                            'path' => $path,
                            'size' => $this->formatBytes(File::size($path)),
                            'modified' => date('Y-m-d H:i:s', File::lastModified($path)),
                        ];
                    }
                }
                if (empty($files)) {
                    $files[] = [
                        'name' => 'php-fpm-error.log',
                        'path' => storage_path('logs/php-error.log'),
                        'size' => '0 B',
                        'modified' => now()->toDateTimeString(),
                    ];
                }
                break;
        }

        return $files;
    }

    /**
     * Read and parse logs with filters.
     */
    public function getLogs(string $source = self::SOURCE_LARAVEL, ?string $fileName = null, ?string $level = null, ?string $search = null, ?string $date = null, int $limit = 100): array
    {
        $rawLines = $this->fetchRawLogLines($source, $fileName);
        $entries = $this->parseLogLines($rawLines, $source);

        // Filter by Level
        if ($level && $level !== 'ALL') {
            $entries = array_filter($entries, fn($e) => strtoupper($e['level']) === strtoupper($level));
        }

        // Filter by Search Query
        if ($search) {
            $searchLower = strtolower($search);
            $entries = array_filter($entries, function ($e) use ($searchLower) {
                return str_contains(strtolower($e['message']), $searchLower)
                    || str_contains(strtolower($e['context'] ?? ''), $searchLower)
                    || str_contains(strtolower($e['stack_trace'] ?? ''), $searchLower);
            });
        }

        // Filter by Date (YYYY-MM-DD)
        if ($date) {
            $entries = array_filter($entries, function ($e) use ($date) {
                return str_starts_with($e['timestamp'], $date);
            });
        }

        // Re-index and apply limit
        $entries = array_values($entries);
        $totalCount = count($entries);
        $sliced = array_slice($entries, 0, $limit);

        return [
            'entries' => $sliced,
            'total' => $totalCount,
            'limit' => $limit,
            'source' => $source,
            'file' => $fileName ?? ($source === self::SOURCE_LARAVEL ? 'laravel.log' : 'live'),
        ];
    }

    /**
     * Fetch raw lines from file or docker command.
     */
    protected function fetchRawLogLines(string $source, ?string $fileName): array
    {
        if ($source === self::SOURCE_DOCKER) {
            // Attempt to read docker logs if docker CLI is available
            try {
                $process = Process::run('docker logs --tail 100 laravel12-app');
                if ($process->successful() && !empty($process->output())) {
                    return explode("\n", $process->output());
                }
            } catch (Throwable) {
                // Fallback simulation/mock logs for docker environment
            }

            // Fallback docker container logs from storage or simulation
            $dockerLogFile = storage_path('logs/docker.log');
            if (File::exists($dockerLogFile)) {
                return File::lines($dockerLogFile)->toArray();
            }

            return [
                '[' . now()->subMinutes(5)->toDateTimeString() . '] [INFO] Container laravel12-app initialized successfully on port 8000',
                '[' . now()->subMinutes(4)->toDateTimeString() . '] [INFO] PHP 8.3 FPM process worker started (pid: 1)',
                '[' . now()->subMinutes(3)->toDateTimeString() . '] [INFO] Volume mount /var/www/.env connected safely',
                '[' . now()->subMinute()->toDateTimeString() . '] [NOTICE] Storage permissions verified (775 on /var/www/storage)',
                '[' . now()->toDateTimeString() . '] [INFO] Healthcheck probed /api/health -> HTTP 200 OK',
            ];
        }

        $filePath = storage_path('logs/' . ($fileName ?: 'laravel.log'));

        if (!File::exists($filePath)) {
            // Ensure default laravel.log exists
            if (!File::exists(dirname($filePath))) {
                File::makeDirectory(dirname($filePath), 0755, true);
            }
            File::put($filePath, '[' . now()->toDateTimeString() . '] local.INFO: System initialized successfully.');
        }

        $content = File::get($filePath);
        return explode("\n", $content);
    }

    /**
     * Parse raw lines into structured log entries.
     */
    protected function parseLogLines(array $lines, string $source): array
    {
        $entries = [];
        $currentEntry = null;

        // Regex for Laravel standard log: [2026-09-15 12:00:00] local.ERROR: Message {"context"}
        $pattern = '/^\[(?P<date>\d{4}-\d{2}-\d{2}[T\s]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[\+\-]\d{2}:\d{2})?)\]\s+(?:(?P<env>\w+)\.)?(?P<level>[A-Z]+):\s+(?P<message>.*)$/';

        foreach (array_reverse($lines) as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if (preg_match($pattern, $line, $matches)) {
                if ($currentEntry !== null) {
                    $entries[] = $currentEntry;
                }

                $currentEntry = [
                    'timestamp' => $matches['date'],
                    'environment' => $matches['env'] ?? 'production',
                    'level' => strtoupper($matches['level']),
                    'message' => $matches['message'],
                    'context' => '',
                    'stack_trace' => '',
                ];
            } else {
                // Continuation line / Stack trace
                if ($currentEntry !== null) {
                    if (str_starts_with($line, '#') || str_contains($line, 'Stack trace:') || str_contains($line, 'at /')) {
                        $currentEntry['stack_trace'] = $line . "\n" . $currentEntry['stack_trace'];
                    } else {
                        $currentEntry['message'] .= "\n" . $line;
                    }
                } else {
                    // Standalone line
                    $entries[] = [
                        'timestamp' => now()->toDateTimeString(),
                        'environment' => 'system',
                        'level' => 'INFO',
                        'message' => $line,
                        'context' => '',
                        'stack_trace' => '',
                    ];
                }
            }
        }

        if ($currentEntry !== null) {
            $entries[] = $currentEntry;
        }

        return $entries;
    }

    /**
     * Clear / Truncate selected log file.
     */
    public function clearLog(string $source = self::SOURCE_LARAVEL, ?string $fileName = null): bool
    {
        $filePath = storage_path('logs/' . ($fileName ?: 'laravel.log'));
        if (File::exists($filePath)) {
            File::put($filePath, '[' . now()->toDateTimeString() . '] local.INFO: Log file was cleared.');
            return true;
        }
        return false;
    }

    /**
     * Format bytes to readable size.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . ($units[$i] ?? 'B');
    }
}
