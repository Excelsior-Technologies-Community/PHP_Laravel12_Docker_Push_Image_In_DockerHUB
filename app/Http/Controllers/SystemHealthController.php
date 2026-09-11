<?php

namespace App\Http\Controllers;

use App\Models\SystemHealthLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class SystemHealthController extends Controller
{
    /**
     * Main Docker & Laravel health dashboard.
     */
    public function index()
    {
        $health = $this->runHealthChecks();

        $docker = $this->dockerInformation();

        $application = $this->applicationInformation();

        $recentLogs = SystemHealthLog::latest('checked_at')
            ->limit(10)
            ->get();

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

        return view('system-health', compact(
            'health',
            'docker',
            'application',
            'recentLogs',
            'statistics'
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
     * Clear health activity logs.
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
     * Health API endpoint.
     */
    public function apiHealth()
    {
        return response()->json([
            'success' => true,

            'application' => $this->applicationInformation(),

            'docker' => $this->dockerInformation(),

            'health' => $this->runHealthChecks(false),

            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Perform all system health checks.
     */
    private function runHealthChecks(bool $saveLogs = true): array
    {
        $checks = [];

        /*
        |--------------------------------------------------------------------------
        | 1. Database Connection
        |--------------------------------------------------------------------------
        */

        try {
            DB::connection()->getPdo();

            $databaseName = DB::connection()
                ->getDatabaseName();

            $checks['database'] = [
                'name' => 'Database Connection',
                'status' => 'healthy',
                'message' => 'Database connection is working.',

                'details' => [
                    'driver' => DB::connection()->getDriverName(),
                    'database' => $databaseName,
                ],
            ];
        } catch (Throwable $e) {
            $checks['database'] = [
                'name' => 'Database Connection',
                'status' => 'failed',
                'message' => 'Database connection failed.',

                'details' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Cache System
        |--------------------------------------------------------------------------
        */

        try {
            $cacheKey = 'system_health_test';

            Cache::put(
                $cacheKey,
                'ok',
                60
            );

            $cacheValue = Cache::get($cacheKey);

            Cache::forget($cacheKey);

            if ($cacheValue === 'ok') {
                $checks['cache'] = [
                    'name' => 'Cache System',
                    'status' => 'healthy',
                    'message' => 'Cache system is working.',

                    'details' => [
                        'driver' => config('cache.default'),
                    ],
                ];
            } else {
                $checks['cache'] = [
                    'name' => 'Cache System',
                    'status' => 'warning',
                    'message' => 'Cache write/read test returned an unexpected result.',

                    'details' => [],
                ];
            }
        } catch (Throwable $e) {
            $checks['cache'] = [
                'name' => 'Cache System',
                'status' => 'failed',
                'message' => 'Cache system is not working.',

                'details' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Laravel Storage
        |--------------------------------------------------------------------------
        */

        try {
            $storagePath = storage_path();

            $isDirectory = File::isDirectory(
                $storagePath
            );

            $isWritable = File::isWritable(
                $storagePath
            );

            if (
                $isDirectory &&
                $isWritable
            ) {
                $checks['storage'] = [
                    'name' => 'Storage',
                    'status' => 'healthy',
                    'message' => 'Laravel storage is available and writable.',

                    'details' => [
                        'path' => $storagePath,
                        'writable' => true,
                    ],
                ];
            } else {
                $checks['storage'] = [
                    'name' => 'Storage',
                    'status' => 'failed',
                    'message' => 'Laravel storage is not writable.',

                    'details' => [
                        'path' => $storagePath,
                        'writable' => $isWritable,
                    ],
                ];
            }
        } catch (Throwable $e) {
            $checks['storage'] = [
                'name' => 'Storage',
                'status' => 'failed',
                'message' => 'Storage check failed.',

                'details' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Blade View Cache
        |--------------------------------------------------------------------------
        */

        try {
            $viewPath = storage_path(
                'framework/views'
            );

            if (
                File::isDirectory($viewPath) &&
                File::isWritable($viewPath)
            ) {
                $checks['view_cache'] = [
                    'name' => 'Blade View Cache',
                    'status' => 'healthy',
                    'message' => 'Blade view cache directory is available.',

                    'details' => [
                        'path' => $viewPath,
                    ],
                ];
            } else {
                $checks['view_cache'] = [
                    'name' => 'Blade View Cache',
                    'status' => 'failed',
                    'message' => 'Blade view cache directory is unavailable.',

                    'details' => [
                        'path' => $viewPath,
                    ],
                ];
            }
        } catch (Throwable $e) {
            $checks['view_cache'] = [
                'name' => 'Blade View Cache',
                'status' => 'failed',
                'message' => 'Blade view cache check failed.',

                'details' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Bootstrap Cache
        |--------------------------------------------------------------------------
        */

        try {
            $bootstrapPath = base_path(
                'bootstrap/cache'
            );

            if (
                File::isDirectory($bootstrapPath) &&
                File::isWritable($bootstrapPath)
            ) {
                $checks['bootstrap_cache'] = [
                    'name' => 'Bootstrap Cache',
                    'status' => 'healthy',
                    'message' => 'Bootstrap cache directory is available.',

                    'details' => [
                        'path' => $bootstrapPath,
                    ],
                ];
            } else {
                $checks['bootstrap_cache'] = [
                    'name' => 'Bootstrap Cache',
                    'status' => 'failed',
                    'message' => 'Bootstrap cache directory is unavailable.',

                    'details' => [
                        'path' => $bootstrapPath,
                    ],
                ];
            }
        } catch (Throwable $e) {
            $checks['bootstrap_cache'] = [
                'name' => 'Bootstrap Cache',
                'status' => 'failed',
                'message' => 'Bootstrap cache check failed.',

                'details' => [
                    'error' => $e->getMessage(),
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
                    'check_type' => $check['name'],
                    'status' => $check['status'],
                    'message' => $check['message'],
                    'details' => $check['details'],
                    'checked_at' => now(),
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
            'application_name' => config('app.name'),

            'environment' => app()->environment(),

            'debug' => config('app.debug')
                ? 'Enabled'
                : 'Disabled',

            'laravel_version' => app()->version(),

            'php_version' => PHP_VERSION,

            'timezone' => config('app.timezone'),

            'locale' => config('app.locale'),

            'base_path' => base_path(),

            'storage_path' => storage_path(),
        ];
    }

    /**
     * Docker/container information.
     */
    private function dockerInformation(): array
    {
        $containerId = getenv('HOSTNAME')
            ?: 'Not available';

        $isDocker = false;

        if (
            File::exists('/.dockerenv') ||
            File::exists('/run/.containerenv')
        ) {
            $isDocker = true;
        }

        $hostname = gethostname();

        return [
            'running_in_docker' => $isDocker,

            'container_id' => $containerId,

            'hostname' => $hostname
                ?: 'Not available',

            'image_name' => env(
                'DOCKER_IMAGE_NAME',
                'my-laravel-app'
            ),

            'image_tag' => env(
                'DOCKER_IMAGE_TAG',
                'latest'
            ),

            'docker_repository' => env(
                'DOCKER_HUB_REPOSITORY',
                'Not configured'
            ),

            'build_time' => env(
                'DOCKER_BUILD_TIME',
                'Not configured'
            ),

            'php_sapi' => PHP_SAPI,

            'operating_system' => PHP_OS_FAMILY,
        ];
    }
}

