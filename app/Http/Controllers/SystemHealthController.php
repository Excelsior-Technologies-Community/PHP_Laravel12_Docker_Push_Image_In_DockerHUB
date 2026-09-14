<?php

namespace App\Http\Controllers;

use App\Models\SystemHealthLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class SystemHealthController extends Controller
{
    /**
     * Main Docker & Laravel health dashboard.
     */
    public function index(Request $request)
    {
        $health = $this->runHealthChecks();

        $docker = $this->dockerInformation();

        $application = $this->applicationInformation();

        $resources = $this->resourceInformation();

        /*
        |--------------------------------------------------------------------------
        | Log Search
        |--------------------------------------------------------------------------
        */

        $search = $request->input('search');

        $status = $request->input('status');

        $logsQuery = SystemHealthLog::query()
            ->latest('checked_at');

        if ($search) {
            $logsQuery->where(function ($query) use ($search) {
                $query->where('check_type', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if (
            $status &&
            in_array($status, ['healthy', 'warning', 'failed'])
        ) {
            $logsQuery->where('status', $status);
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $recentLogs = $logsQuery
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Statistics
        |--------------------------------------------------------------------------
        */

        $statistics = [
            'total_checks' => SystemHealthLog::count(),

            'healthy_checks' => SystemHealthLog::where(
                'status',
                'healthy'
            )->count(),

            'warning_checks' => SystemHealthLog::where(
                'status',
                'warning'
            )->count(),

            'failed_checks' => SystemHealthLog::where(
                'status',
                'failed'
            )->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Health Score
        |--------------------------------------------------------------------------
        */

        $total = array_sum([
            $statistics['healthy_checks'],
            $statistics['warning_checks'],
            $statistics['failed_checks'],
        ]);

        $healthScore = $total > 0
            ? round(
                ($statistics['healthy_checks'] / $total) * 100
            )
            : 100;

        /*
        |--------------------------------------------------------------------------
        | Current Status
        |--------------------------------------------------------------------------
        */

        if ($healthScore >= 90) {
            $healthStatus = 'Excellent';
            $healthStatusClass = 'success';
        } elseif ($healthScore >= 70) {
            $healthStatus = 'Good';
            $healthStatusClass = 'warning';
        } else {
            $healthStatus = 'Critical';
            $healthStatusClass = 'danger';
        }

        return view('system-health', compact(
            'health',
            'docker',
            'application',
            'resources',
            'recentLogs',
            'statistics',
            'healthScore',
            'healthStatus',
            'healthStatusClass',
            'search',
            'status'
        ));
    }

    /**
     * Run all health checks manually.
     */
    public function check()
    {
        $this->runHealthChecks(true);

        return redirect()
            ->route('system.health')
            ->with(
                'success',
                'System health checks completed successfully.'
            );
    }

    /**
     * Clear all health logs.
     */
    public function clearLogs()
    {
        SystemHealthLog::truncate();

        return redirect()
            ->route('system.health')
            ->with(
                'success',
                'Health activity logs cleared successfully.'
            );
    }

    /**
     * Delete logs older than selected days.
     */
    public function cleanupLogs(Request $request)
    {
        $days = (int) $request->input('days', 30);

        if (!in_array($days, [7, 15, 30, 60, 90])) {
            $days = 30;
        }

        $cutoff = now()->subDays($days);

        $deleted = SystemHealthLog::where(
            'checked_at',
            '<',
            $cutoff
        )->delete();

        return redirect()
            ->route('system.health')
            ->with(
                'success',
                "{$deleted} log(s) older than {$days} days were deleted."
            );
    }

    /**
     * Export health logs to CSV.
     */
    public function exportLogs(Request $request)
    {
        $search = $request->input('search');

        $status = $request->input('status');

        $query = SystemHealthLog::query()
            ->latest('checked_at');

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where(
                    'check_type',
                    'like',
                    "%{$search}%"
                )->orWhere(
                    'message',
                    'like',
                    "%{$search}%"
                );
            });
        }

        if (
            $status &&
            in_array($status, ['healthy', 'warning', 'failed'])
        ) {
            $query->where('status', $status);
        }

        $logs = $query->get();

        $filename =
            'system-health-logs-' .
            now()->format('Y-m-d-H-i-s') .
            '.csv';

        return response()->streamDownload(function () use ($logs) {

            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Check Type',
                'Status',
                'Message',
                'Details',
                'Checked At',
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->check_type,
                    $log->status,
                    $log->message,
                    json_encode($log->details),
                    $log->checked_at?->format(
                        'Y-m-d H:i:s'
                    ),
                ]);
            }

            fclose($handle);

        }, $filename);
    }

    /**
     * Health API endpoint.
     */
    public function apiHealth()
    {
        return response()->json([
            'success' => true,

            'application' =>
                $this->applicationInformation(),

            'docker' =>
                $this->dockerInformation(),

            'resources' =>
                $this->resourceInformation(),

            'health' =>
                $this->runHealthChecks(false),

            'timestamp' =>
                now()->toDateTimeString(),
        ]);
    }

    /**
     * Perform all system health checks.
     */
    private function runHealthChecks(
        bool $saveLogs = true
    ): array {
        $checks = [];

        /*
        |--------------------------------------------------------------------------
        | 1. Database Connection
        |--------------------------------------------------------------------------
        */

        try {
            $start = microtime(true);

            DB::connection()->getPdo();

            $databaseName =
                DB::connection()->getDatabaseName();

            $latency = round(
                (microtime(true) - $start) * 1000,
                2
            );

            $checks['database'] = [
                'name' => 'Database Connection',
                'status' => 'healthy',
                'message' =>
                    'Database connection is working.',
                'details' => [
                    'driver' =>
                        DB::connection()->getDriverName(),

                    'database' =>
                        $databaseName,

                    'latency' =>
                        $latency . ' ms',
                ],
            ];
        } catch (Throwable $e) {
            $checks['database'] = [
                'name' => 'Database Connection',
                'status' => 'failed',
                'message' =>
                    'Database connection failed.',
                'details' => [
                    'error' =>
                        $e->getMessage(),
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Cache System
        |--------------------------------------------------------------------------
        */

        try {
            $cacheKey =
                'system_health_test';

            Cache::put(
                $cacheKey,
                'ok',
                60
            );

            $cacheValue =
                Cache::get($cacheKey);

            Cache::forget($cacheKey);

            if ($cacheValue === 'ok') {
                $checks['cache'] = [
                    'name' => 'Cache System',
                    'status' => 'healthy',
                    'message' =>
                        'Cache system is working.',
                    'details' => [
                        'driver' =>
                            config('cache.default'),
                    ],
                ];
            } else {
                $checks['cache'] = [
                    'name' => 'Cache System',
                    'status' => 'warning',
                    'message' =>
                        'Cache write/read test returned an unexpected result.',
                    'details' => [],
                ];
            }
        } catch (Throwable $e) {
            $checks['cache'] = [
                'name' => 'Cache System',
                'status' => 'failed',
                'message' =>
                    'Cache system is not working.',
                'details' => [
                    'error' =>
                        $e->getMessage(),
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Laravel Storage
        |--------------------------------------------------------------------------
        */

        try {
            $storagePath =
                storage_path();

            $isDirectory =
                File::isDirectory(
                    $storagePath
                );

            $isWritable =
                File::isWritable(
                    $storagePath
                );

            if (
                $isDirectory &&
                $isWritable
            ) {
                $checks['storage'] = [
                    'name' => 'Storage',
                    'status' => 'healthy',
                    'message' =>
                        'Laravel storage is available and writable.',
                    'details' => [
                        'path' =>
                            $storagePath,

                        'writable' =>
                            true,
                    ],
                ];
            } else {
                $checks['storage'] = [
                    'name' => 'Storage',
                    'status' => 'failed',
                    'message' =>
                        'Laravel storage is not writable.',
                    'details' => [
                        'path' =>
                            $storagePath,

                        'writable' =>
                            $isWritable,
                    ],
                ];
            }
        } catch (Throwable $e) {
            $checks['storage'] = [
                'name' => 'Storage',
                'status' => 'failed',
                'message' =>
                    'Storage check failed.',
                'details' => [
                    'error' =>
                        $e->getMessage(),
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Blade View Cache
        |--------------------------------------------------------------------------
        */

        try {
            $viewPath =
                storage_path(
                    'framework/views'
                );

            if (
                File::isDirectory(
                    $viewPath
                ) &&
                File::isWritable(
                    $viewPath
                )
            ) {
                $checks['view_cache'] = [
                    'name' =>
                        'Blade View Cache',

                    'status' =>
                        'healthy',

                    'message' =>
                        'Blade view cache directory is available.',

                    'details' => [
                        'path' =>
                            $viewPath,
                    ],
                ];
            } else {
                $checks['view_cache'] = [
                    'name' =>
                        'Blade View Cache',

                    'status' =>
                        'failed',

                    'message' =>
                        'Blade view cache directory is unavailable.',

                    'details' => [
                        'path' =>
                            $viewPath,
                    ],
                ];
            }
        } catch (Throwable $e) {
            $checks['view_cache'] = [
                'name' =>
                    'Blade View Cache',

                'status' =>
                    'failed',

                'message' =>
                    'Blade view cache check failed.',

                'details' => [
                    'error' =>
                        $e->getMessage(),
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Bootstrap Cache
        |--------------------------------------------------------------------------
        */

        try {
            $bootstrapPath =
                base_path(
                    'bootstrap/cache'
                );

            if (
                File::isDirectory(
                    $bootstrapPath
                ) &&
                File::isWritable(
                    $bootstrapPath
                )
            ) {
                $checks['bootstrap_cache'] = [
                    'name' =>
                        'Bootstrap Cache',

                    'status' =>
                        'healthy',

                    'message' =>
                        'Bootstrap cache directory is available.',

                    'details' => [
                        'path' =>
                            $bootstrapPath,
                    ],
                ];
            } else {
                $checks['bootstrap_cache'] = [
                    'name' =>
                        'Bootstrap Cache',

                    'status' =>
                        'failed',

                    'message' =>
                        'Bootstrap cache directory is unavailable.',

                    'details' => [
                        'path' =>
                            $bootstrapPath,
                    ],
                ];
            }
        } catch (Throwable $e) {
            $checks['bootstrap_cache'] = [
                'name' =>
                    'Bootstrap Cache',

                'status' =>
                    'failed',

                'message' =>
                    'Bootstrap cache check failed.',

                'details' => [
                    'error' =>
                        $e->getMessage(),
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Save Health Activity Logs
        |--------------------------------------------------------------------------
        */

        if ($saveLogs) {
            foreach ($checks as $check) {
                SystemHealthLog::create([
                    'check_type' =>
                        $check['name'],

                    'status' =>
                        $check['status'],

                    'message' =>
                        $check['message'],

                    'details' =>
                        $check['details'],

                    'checked_at' =>
                        now(),
                ]);
            }
        }

        return $checks;
    }

    /**
     * Laravel application information.
     */
    private function applicationInformation(): array
    {
        return [
            'application_name' =>
                config('app.name'),

            'environment' =>
                app()->environment(),

            'debug' =>
                config('app.debug')
                    ? 'Enabled'
                    : 'Disabled',

            'laravel_version' =>
                app()->version(),

            'php_version' =>
                PHP_VERSION,

            'timezone' =>
                config('app.timezone'),

            'locale' =>
                config('app.locale'),

            'base_path' =>
                base_path(),

            'storage_path' =>
                storage_path(),
        ];
    }

    /**
     * Docker/container information.
     */
    private function dockerInformation(): array
    {
        $containerId =
            getenv('HOSTNAME')
            ?: 'Not available';

        $isDocker = false;

        if (
            File::exists('/.dockerenv') ||
            File::exists('/run/.containerenv')
        ) {
            $isDocker = true;
        }

        $hostname =
            gethostname();

        return [
            'running_in_docker' =>
                $isDocker,

            'container_id' =>
                $containerId,

            'hostname' =>
                $hostname
                    ?: 'Not available',

            'image_name' =>
                env(
                    'DOCKER_IMAGE_NAME',
                    'my-laravel-app'
                ),

            'image_tag' =>
                env(
                    'DOCKER_IMAGE_TAG',
                    'latest'
                ),

            'docker_repository' =>
                env(
                    'DOCKER_HUB_REPOSITORY',
                    'Not configured'
                ),

            'build_time' =>
                env(
                    'DOCKER_BUILD_TIME',
                    'Not configured'
                ),

            'php_sapi' =>
                PHP_SAPI,

            'operating_system' =>
                PHP_OS_FAMILY,
        ];
    }

    /**
     * Server resource information.
     */
    private function resourceInformation(): array
    {
        $diskTotal = @disk_total_space(
            base_path()
        );

        $diskFree = @disk_free_space(
            base_path()
        );

        $diskUsed = null;

        if (
            $diskTotal &&
            $diskFree
        ) {
            $diskUsed =
                $diskTotal - $diskFree;
        }

        $memoryLimit =
            ini_get('memory_limit');

        $memoryUsage =
            memory_get_usage(true);

        $memoryPeak =
            memory_get_peak_usage(true);

        return [
            'disk_total' =>
                $diskTotal
                    ? $this->formatBytes($diskTotal)
                    : 'Not available',

            'disk_free' =>
                $diskFree
                    ? $this->formatBytes($diskFree)
                    : 'Not available',

            'disk_used' =>
                $diskUsed
                    ? $this->formatBytes($diskUsed)
                    : 'Not available',

            'disk_usage_percent' =>
                $diskTotal
                    ? round(
                        (($diskTotal - $diskFree)
                            / $diskTotal) * 100,
                        1
                    )
                    : null,

            'memory_limit' =>
                $memoryLimit ?: 'Not available',

            'memory_usage' =>
                $this->formatBytes(
                    $memoryUsage
                ),

            'memory_peak' =>
                $this->formatBytes(
                    $memoryPeak
                ),

            'server_time' =>
                now()->format(
                    'd M Y, h:i:s A'
                ),

            'server_software' =>
                $_SERVER['SERVER_SOFTWARE']
                    ?? 'Not available',
        ];
    }

    /**
     * Format bytes.
     */
    private function formatBytes(
        int $bytes
    ): string {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = [
            'B',
            'KB',
            'MB',
            'GB',
            'TB',
        ];

        $power =
            floor(
                log($bytes, 1024)
            );

        $power =
            min(
                $power,
                count($units) - 1
            );

        return round(
            $bytes / pow(1024, $power),
            2
        ) . ' ' . $units[$power];
    }
}