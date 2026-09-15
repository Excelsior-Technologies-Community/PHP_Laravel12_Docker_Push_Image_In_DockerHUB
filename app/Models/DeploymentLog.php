<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeploymentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'commit_id',
        'commit_message',
        'branch',
        'environment',
        'status',
        'build_time_seconds',
        'deployed_by',
        'deployment_logs',
        'is_current_active',
        'rollback_from_id',
        'deployed_at',
    ];

    protected $casts = [
        'is_current_active' => 'boolean',
        'build_time_seconds' => 'integer',
        'deployed_at' => 'datetime',
    ];

    /**
     * Get the previous deployment this was rolled back from, if any.
     */
    public function rolledBackFrom()
    {
        return $this->belongsTo(DeploymentLog::class, 'rollback_from_id');
    }

    /**
     * Helper to get short commit ID.
     */
    public function getShortCommitIdAttribute(): string
    {
        return $this->commit_id ? substr($this->commit_id, 0, 7) : 'N/A';
    }

    /**
     * Format build time in mm:ss or seconds.
     */
    public function getFormattedBuildTimeAttribute(): string
    {
        if (!$this->build_time_seconds) {
            return '0s';
        }
        $mins = floor($this->build_time_seconds / 60);
        $secs = $this->build_time_seconds % 60;
        if ($mins > 0) {
            return "{$mins}m {$secs}s";
        }
        return "{$secs}s";
    }
}
