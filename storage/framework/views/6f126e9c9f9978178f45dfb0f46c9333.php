<?php $__env->startSection('title', 'Live Server Health & Telemetry - Docker Control'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6" x-data="{
    showCleanModal: false,
    showAlertModal: false,
    alertChannel: 'email',
    alertType: 'High Latency / Resource Warning'
}">

    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                <i data-lucide="activity" class="w-6 h-6 text-emerald-400"></i>
                Live Server Health & Infrastructure Telemetry
            </h1>
            <p class="text-sm text-slate-400 mt-1">
                Real-time CPU, RAM, Disk, Laravel Queue, and Database latency monitoring with automated alerting.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Run Manual Check -->
            <form method="POST" action="<?php echo e(route('system.health.check')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-medium text-xs shadow-lg shadow-emerald-500/20 flex items-center gap-1.5 transition-all">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                    <span>Run Health Check</span>
                </button>
            </form>

            <!-- Test Alert Trigger -->
            <button @click="showAlertModal = true" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium border border-slate-700 flex items-center gap-1.5 transition-colors">
                <i data-lucide="bell-ring" class="w-3.5 h-3.5 text-amber-400"></i>
                <span>Test Alert Dispatch</span>
            </button>
        </div>
    </div>

    <!-- Health Score & Core Telemetry Row -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        
        <!-- Overall Health Score -->
        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 shadow-md flex items-center justify-between">
            <div>
                <span class="text-xs font-medium uppercase tracking-wider text-slate-400">Health Score</span>
                <div class="text-3xl font-extrabold mt-1 font-mono <?php echo e($healthScore >= 90 ? 'text-emerald-400' : ($healthScore >= 70 ? 'text-amber-400' : 'text-rose-400')); ?>">
                    <?php echo e($healthScore); ?>%
                </div>
                <div class="text-xs font-semibold mt-1 <?php echo e($healthScore >= 90 ? 'text-emerald-400' : ($healthScore >= 70 ? 'text-amber-400' : 'text-rose-400')); ?>">
                    Status: <?php echo e($healthStatus); ?>

                </div>
            </div>
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center <?php echo e($healthScore >= 90 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20'); ?>">
                <i data-lucide="shield-check" class="w-7 h-7"></i>
            </div>
        </div>

        <!-- CPU Usage Gauge -->
        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 shadow-md">
            <div class="flex items-center justify-between text-slate-400 mb-1">
                <span class="text-xs font-medium uppercase tracking-wider">CPU Load</span>
                <i data-lucide="cpu" class="w-4 h-4 text-cyan-400"></i>
            </div>
            <div class="text-2xl font-bold text-white font-mono"><?php echo e($resources['cpu_usage_percent']); ?>%</div>
            <div class="w-full bg-slate-800 h-2 rounded-full mt-2 overflow-hidden">
                <div class="h-full bg-cyan-400 rounded-full" style="width: <?php echo e(min(100, $resources['cpu_usage_percent'])); ?>%"></div>
            </div>
            <div class="text-[11px] text-slate-400 mt-1">Multi-core processor load</div>
        </div>

        <!-- RAM / Memory Gauge -->
        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 shadow-md">
            <div class="flex items-center justify-between text-slate-400 mb-1">
                <span class="text-xs font-medium uppercase tracking-wider">Memory (RAM)</span>
                <i data-lucide="hard-drive" class="w-4 h-4 text-sky-400"></i>
            </div>
            <div class="text-2xl font-bold text-white font-mono"><?php echo e($resources['memory_usage']); ?></div>
            <div class="w-full bg-slate-800 h-2 rounded-full mt-2 overflow-hidden">
                <div class="h-full bg-sky-400 rounded-full" style="width: <?php echo e(min(100, $resources['memory_usage_percent'])); ?>%"></div>
            </div>
            <div class="text-[11px] text-slate-400 mt-1">Peak: <?php echo e($resources['memory_peak']); ?> (Limit: <?php echo e($resources['memory_limit']); ?>)</div>
        </div>

        <!-- Disk Storage -->
        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 shadow-md">
            <div class="flex items-center justify-between text-slate-400 mb-1">
                <span class="text-xs font-medium uppercase tracking-wider">Disk Storage</span>
                <i data-lucide="pie-chart" class="w-4 h-4 text-indigo-400"></i>
            </div>
            <div class="text-2xl font-bold text-white font-mono"><?php echo e($resources['disk_usage_percent'] ?? 'N/A'); ?>%</div>
            <div class="w-full bg-slate-800 h-2 rounded-full mt-2 overflow-hidden">
                <div class="h-full bg-indigo-400 rounded-full" style="width: <?php echo e(min(100, $resources['disk_usage_percent'] ?? 0)); ?>%"></div>
            </div>
            <div class="text-[11px] text-slate-400 mt-1">Free: <?php echo e($resources['disk_free']); ?> / Total: <?php echo e($resources['disk_total']); ?></div>
        </div>

    </div>

    <!-- Live Queue & Database Latency Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        
        <!-- Laravel Queue Status -->
        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 shadow-md space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                        <i data-lucide="list-ordered" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white">Laravel Queue & Worker Status</h3>
                        <p class="text-xs text-slate-400 font-mono">Driver: <?php echo e(strtoupper($queueMetrics['driver'])); ?></p>
                    </div>
                </div>

                <?php if($queueMetrics['failed_jobs'] > 0): ?>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20 flex items-center gap-1">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                        <?php echo e($queueMetrics['failed_jobs']); ?> Failed
                    </span>
                <?php else: ?>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        Queue Healthy
                    </span>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2">
                <div class="p-3 rounded-xl bg-slate-950/80 border border-slate-800">
                    <div class="text-[11px] text-slate-400 uppercase tracking-wider">Pending Jobs</div>
                    <div class="text-xl font-bold text-slate-100 font-mono mt-0.5"><?php echo e($queueMetrics['pending_jobs']); ?></div>
                </div>
                <div class="p-3 rounded-xl bg-slate-950/80 border border-slate-800">
                    <div class="text-[11px] text-slate-400 uppercase tracking-wider">Failed Jobs</div>
                    <div class="text-xl font-bold font-mono mt-0.5 <?php echo e($queueMetrics['failed_jobs'] > 0 ? 'text-rose-400' : 'text-slate-100'); ?>">
                        <?php echo e($queueMetrics['failed_jobs']); ?>

                    </div>
                </div>
            </div>

            <div class="text-xs text-slate-400">
                <?php echo e($queueMetrics['message']); ?>

            </div>
        </div>

        <!-- Database Connectivity & Latency -->
        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 shadow-md space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400">
                        <i data-lucide="database" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white">Database Roundtrip Latency</h3>
                        <p class="text-xs text-slate-400 font-mono">Connection: <?php echo e($dbLatency['connection']); ?> (<?php echo e($dbLatency['database']); ?>)</p>
                    </div>
                </div>

                <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?php echo e($dbLatency['status'] === 'healthy' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'); ?>">
                    <?php echo e($dbLatency['latency_ms']); ?> ms
                </span>
            </div>

            <div class="p-3 rounded-xl bg-slate-950/80 border border-slate-800 flex items-center justify-between">
                <div class="text-xs text-slate-300">
                    <span class="text-slate-500">Query:</span> <code class="text-cyan-300 font-mono">SELECT 1</code>
                </div>
                <div class="text-xs text-slate-400">
                    Status: <strong class="text-emerald-400"><?php echo e(ucfirst($dbLatency['status'])); ?></strong>
                </div>
            </div>

            <div class="text-xs text-slate-400">
                <?php echo e($dbLatency['message']); ?>

            </div>
        </div>

    </div>

    <!-- Health Checks Grid -->
    <div>
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-2">
            <i data-lucide="check-square" class="w-4 h-4 text-cyan-400"></i>
            Detailed Component Checks
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php $__currentLoopData = $health; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $check): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 shadow-md flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-300"><?php echo e(str_replace('_', ' ', $key)); ?></span>
                            <?php if($check['status'] === 'healthy'): ?>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">OK</span>
                            <?php elseif($check['status'] === 'warning'): ?>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">WARN</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">FAIL</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-slate-200 font-medium"><?php echo e($check['message']); ?></p>
                    </div>
                    <div class="text-[11px] text-slate-500 mt-3 pt-2 border-t border-slate-800/60 font-mono break-all">
                        <?php if(is_array($check['details'] ?? null)): ?>
                            <?php echo e(json_encode($check['details'])); ?>

                        <?php else: ?>
                            <?php echo e($check['details'] ?? 'Component verified'); ?>

                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    <!-- Historical Activity Logs Table -->
    <div class="rounded-2xl bg-slate-900/60 border border-slate-800 overflow-hidden shadow-lg">
        <div class="p-4 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-900/80">
            <div class="flex items-center gap-2">
                <i data-lucide="list" class="w-4 h-4 text-cyan-400"></i>
                <h3 class="text-sm font-bold text-white">Health Activity Audit Logs</h3>
            </div>

            <div class="flex items-center gap-2">
                <a href="<?php echo e(route('system.health.logs.export')); ?>" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-slate-200 border border-slate-700 flex items-center gap-1">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    Export CSV
                </a>

                <form method="POST" action="<?php echo e(route('system.health.logs.clear')); ?>" onsubmit="return confirm('Clear all health audit logs?')">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-rose-950/60 hover:text-rose-400 text-xs text-slate-300 border border-slate-700 flex items-center gap-1">
                        <i data-lucide="trash" class="w-3.5 h-3.5"></i>
                        Clear
                    </button>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-3">Timestamp</th>
                        <th class="px-5 py-3">Check Type</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Message</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <?php $__empty_1 = true; $__currentLoopData = $recentLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="px-5 py-3 text-slate-400 font-mono"><?php echo e($log->checked_at ? $log->checked_at->format('d M Y, h:i:s A') : $log->created_at->format('d M Y, h:i:s A')); ?></td>
                            <td class="px-5 py-3 font-semibold text-slate-200"><?php echo e(strtoupper($log->check_type)); ?></td>
                            <td class="px-5 py-3">
                                <?php if($log->status === 'healthy'): ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">HEALTHY</span>
                                <?php elseif($log->status === 'warning'): ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">WARNING</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">FAILED</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 text-slate-300"><?php echo e($log->message); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-slate-500">No health audit logs recorded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($recentLogs->hasPages()): ?>
            <div class="p-4 border-t border-slate-800 bg-slate-900/60">
                <?php echo e($recentLogs->links()); ?>

            </div>
        <?php endif; ?>
    </div>

    <!-- Modal: Test Alert Dispatch -->
    <div x-show="showAlertModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.away="showAlertModal = false" class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
            <form method="POST" action="<?php echo e(route('system.health.alert.dispatch')); ?>">
                <?php echo csrf_field(); ?>
                <div class="p-4 border-b border-slate-800 bg-slate-950/80 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="bell-ring" class="w-5 h-5 text-amber-400"></i>
                        <h3 class="text-sm font-bold text-white">Dispatch Server Alert</h3>
                    </div>
                    <button type="button" @click="showAlertModal = false" class="text-slate-400 hover:text-white">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-5 space-y-4 text-xs text-slate-300">
                    <div>
                        <label class="block font-medium text-slate-300 mb-1">Notification Channel *</label>
                        <select name="channel" x-model="alertChannel" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 focus:outline-none focus:border-cyan-500">
                            <option value="email">Admin Email (ALERT_ADMIN_EMAIL)</option>
                            <option value="slack">Slack Webhook (SLACK_WEBHOOK_URL)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-300 mb-1">Alert Trigger Category</label>
                        <input type="text" name="type" value="High Latency / Memory Warning" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 focus:outline-none focus:border-cyan-500">
                    </div>

                    <div>
                        <label class="block font-medium text-slate-300 mb-1">Custom Alert Message</label>
                        <textarea name="message" rows="3" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 focus:outline-none focus:border-cyan-500">Live test notification dispatched from Server Health Telemetry dashboard.</textarea>
                    </div>
                </div>

                <div class="p-4 border-t border-slate-800 flex justify-end gap-2 bg-slate-950/80">
                    <button type="button" @click="showAlertModal = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-medium text-slate-300">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white text-xs font-medium shadow-lg shadow-amber-500/20 flex items-center gap-1.5">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        <span>Send Test Alert</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\git_desktop\PHP_Laravel12_Docker_Push_Image_In_DockerHUB\resources\views/system-health.blade.php ENDPATH**/ ?>