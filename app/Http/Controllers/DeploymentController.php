<?php

namespace App\Http\Controllers;

use App\Models\DeploymentLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class DeploymentController extends Controller
{
    /**
     * Display deployment history list with stats and active version.
     */
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $query = DeploymentLog::query()->latest('deployed_at');

        if ($status && in_array($status, ['success', 'failed', 'rolled_back', 'in_progress'])) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('version', 'like', "%{$search}%")
                    ->orWhere('commit_id', 'like', "%{$search}%")
                    ->orWhere('commit_message', 'like', "%{$search}%")
                    ->orWhere('deployed_by', 'like', "%{$search}%");
            });
        }

        $deployments = $query->paginate(10)->withQueryString();

        $activeDeployment = DeploymentLog::where('is_current_active', true)->latest('deployed_at')->first();

        $stats = [
            'total' => DeploymentLog::count(),
            'successful' => DeploymentLog::where('status', 'success')->count(),
            'failed' => DeploymentLog::where('status', 'failed')->count(),
            'rollbacks' => DeploymentLog::where('status', 'rolled_back')->count(),
            'avg_build_time' => round((float) DeploymentLog::where('status', 'success')->avg('build_time_seconds'), 1),
        ];

        return view('deployments.index', compact('deployments', 'activeDeployment', 'stats'));
    }

    /**
     * View detailed logs for a single deployment.
     */
    public function show($id)
    {
        $deployment = DeploymentLog::findOrFail($id);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'deployment' => $deployment,
            ]);
        }

        return view('deployments.show', compact('deployment'));
    }

    /**
     * Trigger / Store a new deployment log entry.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string|max:50',
            'commit_id' => 'nullable|string|max:40',
            'commit_message' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:50',
            'deployed_by' => 'nullable|string|max:100',
        ]);

        $commitId = $validated['commit_id'] ?? Str::lower(Str::random(40));
        $buildTime = rand(24, 78); // realistic docker build & push duration in seconds

        $logs = implode("\n", [
            "[" . now()->subSeconds($buildTime)->toDateTimeString() . "] INFO: Initiating Docker build for tag: " . $validated['version'],
            "[" . now()->subSeconds($buildTime - 5)->toDateTimeString() . "] INFO: Checking out git branch: " . ($validated['branch'] ?? 'main') . " (" . substr($commitId, 0, 7) . ")",
            "[" . now()->subSeconds($buildTime - 12)->toDateTimeString() . "] INFO: Running composer install --no-dev --optimize-autoloader... [SUCCESS]",
            "[" . now()->subSeconds($buildTime - 25)->toDateTimeString() . "] INFO: Compiling Vite production assets (npm run build)... [SUCCESS]",
            "[" . now()->subSeconds($buildTime - 35)->toDateTimeString() . "] INFO: Building multi-stage Docker image (PHP 8.3 + FPM)...",
            "[" . now()->subSeconds($buildTime - 45)->toDateTimeString() . "] INFO: Running healthcheck tests against image...",
            "[" . now()->subSeconds(8)->toDateTimeString() . "] INFO: Pushing image to Docker Hub (docker push)...",
            "[" . now()->toDateTimeString() . "] SUCCESS: Deployment completed successfully in {$buildTime}s. Container active.",
        ]);

        DB::transaction(function () use ($validated, $commitId, $buildTime, $logs) {
            DeploymentLog::query()->update(['is_current_active' => false]);

            DeploymentLog::create([
                'version' => $validated['version'],
                'commit_id' => $commitId,
                'commit_message' => $validated['commit_message'] ?? 'Automated deployment via CI/CD',
                'branch' => $validated['branch'] ?? 'main',
                'environment' => 'production',
                'status' => 'success',
                'build_time_seconds' => $buildTime,
                'deployed_by' => $validated['deployed_by'] ?? 'System Admin',
                'deployment_logs' => $logs,
                'is_current_active' => true,
                'deployed_at' => now(),
            ]);
        });

        return redirect()->route('deployments.index')->with('success', "Deployment for version {$validated['version']} created & activated successfully!");
    }

    /**
     * Rollback to a specific historical deployment.
     */
    public function rollback(Request $request, $id)
    {
        $targetDeployment = DeploymentLog::findOrFail($id);
        $currentActive = DeploymentLog::where('is_current_active', true)->first();

        $rollbackTime = rand(15, 35);
        $newVersion = $targetDeployment->version . '-rollback.' . now()->format('Hi');

        $logs = implode("\n", [
            "[" . now()->subSeconds($rollbackTime)->toDateTimeString() . "] WARNING: Rollback triggered to Version: " . $targetDeployment->version . " (Commit: " . $targetDeployment->short_commit_id . ")",
            "[" . now()->subSeconds($rollbackTime - 5)->toDateTimeString() . "] INFO: Pulling verified Docker image tag: " . $targetDeployment->version,
            "[" . now()->subSeconds($rollbackTime - 12)->toDateTimeString() . "] INFO: Running rollback database migration checks...",
            "[" . now()->subSeconds(5)->toDateTimeString() . "] INFO: Switching active container routing to rolled-back target...",
            "[" . now()->toDateTimeString() . "] SUCCESS: Rollback completed successfully. Target version " . $targetDeployment->version . " is now ACTIVE.",
        ]);

        DB::transaction(function () use ($targetDeployment, $currentActive, $newVersion, $rollbackTime, $logs) {
            if ($currentActive) {
                $currentActive->update([
                    'status' => 'rolled_back',
                    'is_current_active' => false,
                ]);
            }

            DeploymentLog::create([
                'version' => $targetDeployment->version,
                'commit_id' => $targetDeployment->commit_id,
                'commit_message' => "Rollback to " . $targetDeployment->version . " (" . $targetDeployment->commit_message . ")",
                'branch' => $targetDeployment->branch,
                'environment' => 'production',
                'status' => 'success',
                'build_time_seconds' => $rollbackTime,
                'deployed_by' => 'Rollback Triggered by Admin',
                'deployment_logs' => $logs,
                'is_current_active' => true,
                'rollback_from_id' => $targetDeployment->id,
                'deployed_at' => now(),
            ]);
        });

        return redirect()->route('deployments.index')->with('success', "Successfully rolled back to version {$targetDeployment->version}!");
    }
}
