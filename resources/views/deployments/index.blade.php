@extends('layouts.admin')

@section('title', 'Deployment History & Rollback - Docker Control')

@section('content')
<div class="space-y-6" x-data="{ 
    showDeployModal: false, 
    showLogsModal: false, 
    activeLog: '', 
    activeVersion: '',
    activeCommit: '',
    viewLog(version, commit, logs) {
        this.activeVersion = version;
        this.activeCommit = commit;
        this.activeLog = logs;
        this.showLogsModal = true;
    }
}">

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                <i data-lucide="rocket" class="w-6 h-6 text-cyan-400"></i>
                Deployment History & Release Pipeline
            </h1>
            <p class="text-sm text-slate-400 mt-1">
                Monitor build durations, container tags, commit hashes, and perform instant zero-downtime rollbacks.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button @click="showDeployModal = true" class="px-4 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-medium text-sm shadow-lg shadow-cyan-500/20 flex items-center gap-2 transition-all">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Trigger New Build</span>
            </button>
        </div>
    </div>

    <!-- Stats Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <!-- Total Deployments -->
        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 shadow-md">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-xs font-medium uppercase tracking-wider">Total Builds</span>
                <i data-lucide="layers" class="w-4 h-4 text-cyan-400"></i>
            </div>
            <div class="text-2xl font-bold text-white font-mono">{{ $stats['total'] }}</div>
            <div class="text-xs text-slate-400 mt-1">Recorded in database</div>
        </div>

        <!-- Successful -->
        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 shadow-md">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-xs font-medium uppercase tracking-wider">Successful</span>
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400"></i>
            </div>
            <div class="text-2xl font-bold text-emerald-400 font-mono">{{ $stats['successful'] }}</div>
            <div class="text-xs text-emerald-400/80 mt-1">
                {{ $stats['total'] > 0 ? round(($stats['successful'] / $stats['total']) * 100) : 100 }}% Success Rate
            </div>
        </div>

        <!-- Failed -->
        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 shadow-md">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-xs font-medium uppercase tracking-wider">Failed</span>
                <i data-lucide="x-circle" class="w-4 h-4 text-rose-400"></i>
            </div>
            <div class="text-2xl font-bold text-rose-400 font-mono">{{ $stats['failed'] }}</div>
            <div class="text-xs text-slate-400 mt-1">Build/runtime errors</div>
        </div>

        <!-- Rollbacks -->
        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 shadow-md">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-xs font-medium uppercase tracking-wider">Rollbacks</span>
                <i data-lucide="rotate-ccw" class="w-4 h-4 text-amber-400"></i>
            </div>
            <div class="text-2xl font-bold text-amber-400 font-mono">{{ $stats['rollbacks'] }}</div>
            <div class="text-xs text-slate-400 mt-1">Historical reversions</div>
        </div>

        <!-- Avg Build Time -->
        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 shadow-md col-span-2 md:col-span-1">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-xs font-medium uppercase tracking-wider">Avg Build Time</span>
                <i data-lucide="clock" class="w-4 h-4 text-sky-400"></i>
            </div>
            <div class="text-2xl font-bold text-white font-mono">{{ $stats['avg_build_time'] }}s</div>
            <div class="text-xs text-sky-400 mt-1">Docker image push</div>
        </div>
    </div>

    <!-- Active Production Image Card -->
    @if($activeDeployment)
        <div class="p-5 rounded-2xl bg-gradient-to-r from-cyan-950/40 via-slate-900/80 to-blue-950/40 border border-cyan-500/30 shadow-xl relative overflow-hidden">
            <div class="absolute -right-8 -bottom-8 w-48 h-48 bg-cyan-500/10 rounded-full blur-2xl pointer-events-none"></div>
            
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping mr-1.5"></span>
                            ACTIVE LIVE VERSION
                        </span>
                        <span class="text-xs text-slate-400 font-mono">Environment: {{ strtoupper($activeDeployment->environment) }}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-slate-200">
                        <span class="text-xl font-mono font-bold text-cyan-300">{{ $activeDeployment->version }}</span>
                        <span class="text-slate-500">•</span>
                        <span class="text-sm font-mono text-slate-300 flex items-center gap-1">
                            <i data-lucide="git-commit" class="w-4 h-4 text-cyan-400"></i>
                            {{ $activeDeployment->short_commit_id }}
                        </span>
                        <span class="text-slate-500">•</span>
                        <span class="text-sm text-slate-400 flex items-center gap-1">
                            <i data-lucide="git-branch" class="w-4 h-4 text-sky-400"></i>
                            {{ $activeDeployment->branch }}
                        </span>
                        <span class="text-slate-500">•</span>
                        <span class="text-sm text-slate-400">{{ $activeDeployment->commit_message }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="viewLog('{{ $activeDeployment->version }}', '{{ $activeDeployment->short_commit_id }}', `{{ addslashes($activeDeployment->deployment_logs) }}`)"
                            class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium flex items-center gap-1.5 border border-slate-700 transition-colors">
                        <i data-lucide="terminal" class="w-3.5 h-3.5 text-cyan-400"></i>
                        <span>View Active Build Logs</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Filters and Table Container -->
    <div class="rounded-2xl bg-slate-900/60 border border-slate-800 overflow-hidden shadow-lg">
        
        <!-- Filter Toolbar -->
        <div class="p-4 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-900/80">
            <div class="flex items-center gap-2">
                <form method="GET" action="{{ route('deployments.index') }}" class="flex items-center gap-2">
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search version, commit, deployer..." 
                               class="pl-9 pr-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-cyan-500 w-56 sm:w-72">
                    </div>

                    <select name="status" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-slate-300 focus:outline-none focus:border-cyan-500">
                        <option value="">All Statuses</option>
                        <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="rolled_back" {{ request('status') === 'rolled_back' ? 'selected' : '' }}>Rolled Back</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    </select>
                </form>
            </div>

            <div class="text-xs text-slate-400">
                Showing {{ $deployments->firstItem() ?? 0 }}-{{ $deployments->lastItem() ?? 0 }} of {{ $deployments->total() }} deployments
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                    <tr>
                        <th class="px-5 py-3">Version / Tag</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Commit & Branch</th>
                        <th class="px-5 py-3">Build Duration</th>
                        <th class="px-5 py-3">Deployed By</th>
                        <th class="px-5 py-3">Deployed At</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-normal">
                    @forelse($deployments as $item)
                        <tr class="hover:bg-slate-800/40 transition-colors {{ $item->is_current_active ? 'bg-cyan-950/10' : '' }}">
                            
                            <!-- Version -->
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-sm text-cyan-300">{{ $item->version }}</span>
                                    @if($item->is_current_active)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">ACTIVE</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-400 truncate max-w-xs mt-0.5">{{ $item->commit_message }}</div>
                            </td>

                            <!-- Status -->
                            <td class="px-5 py-4">
                                @if($item->status === 'success')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                        Success
                                    </span>
                                @elseif($item->status === 'failed')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                        Failed
                                    </span>
                                @elseif($item->status === 'rolled_back')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                        <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                        Rolled Back
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-sky-500/10 text-sky-400 border border-sky-500/20">
                                        <i data-lucide="loader" class="w-3.5 h-3.5 animate-spin"></i>
                                        Building
                                    </span>
                                @endif
                            </td>

                            <!-- Commit -->
                            <td class="px-5 py-4 font-mono">
                                <div class="text-slate-200 flex items-center gap-1">
                                    <i data-lucide="git-commit" class="w-3.5 h-3.5 text-cyan-400"></i>
                                    {{ $item->short_commit_id }}
                                </div>
                                <div class="text-[11px] text-slate-400 flex items-center gap-1 mt-0.5">
                                    <i data-lucide="git-branch" class="w-3 h-3 text-slate-500"></i>
                                    {{ $item->branch }}
                                </div>
                            </td>

                            <!-- Duration -->
                            <td class="px-5 py-4 font-mono text-slate-300">
                                <span class="flex items-center gap-1">
                                    <i data-lucide="timer" class="w-3.5 h-3.5 text-slate-400"></i>
                                    {{ $item->formatted_build_time }}
                                </span>
                            </td>

                            <!-- Deployer -->
                            <td class="px-5 py-4 text-slate-300">
                                {{ $item->deployed_by }}
                            </td>

                            <!-- Timestamp -->
                            <td class="px-5 py-4 text-slate-400">
                                <div>{{ $item->deployed_at ? $item->deployed_at->format('d M Y, h:i A') : $item->created_at->format('d M Y, h:i A') }}</div>
                                <div class="text-[10px] text-slate-500">{{ $item->deployed_at ? $item->deployed_at->diffForHumans() : '' }}</div>
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- View Logs Button -->
                                    <button @click="viewLog('{{ $item->version }}', '{{ $item->short_commit_id }}', `{{ addslashes($item->deployment_logs) }}`)" 
                                            class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-cyan-400 text-xs font-medium border border-slate-700 transition-colors flex items-center gap-1">
                                        <i data-lucide="terminal" class="w-3.5 h-3.5"></i>
                                        <span>Logs</span>
                                    </button>

                                    <!-- Rollback Button -->
                                    @if(!$item->is_current_active && $item->status !== 'failed')
                                        <form method="POST" action="{{ route('deployments.rollback', $item->id) }}" onsubmit="return confirm('Are you sure you want to rollback to version {{ $item->version }} (Commit {{ $item->short_commit_id }})? This will make this version live.')">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 hover:text-amber-300 text-xs font-medium border border-amber-500/30 transition-colors flex items-center gap-1">
                                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                                <span>Rollback</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i data-lucide="inbox" class="w-8 h-8 text-slate-600 mb-2"></i>
                                    <p class="text-sm font-medium">No deployment records found.</p>
                                    <p class="text-xs text-slate-500 mt-1">Click "Trigger New Build" above to record your first deployment.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($deployments->hasPages())
            <div class="p-4 border-t border-slate-800 bg-slate-900/60">
                {{ $deployments->links() }}
            </div>
        @endif
    </div>

    <!-- Modal: View Terminal Logs -->
    <div x-show="showLogsModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.away="showLogsModal = false" class="w-full max-w-4xl bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
            <!-- Modal Header -->
            <div class="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/80">
                <div class="flex items-center gap-2">
                    <i data-lucide="terminal" class="w-5 h-5 text-cyan-400"></i>
                    <h3 class="text-sm font-bold text-white">
                        Build & Deployment Logs: <span class="font-mono text-cyan-400" x-text="activeVersion"></span>
                    </h3>
                    <span class="text-xs text-slate-500 font-mono" x-text="'(' + activeCommit + ')'"></span>
                </div>
                <button @click="showLogsModal = false" class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Terminal Window -->
            <div class="p-4 bg-black/90 font-mono text-xs text-emerald-400 overflow-y-auto flex-1 whitespace-pre-wrap leading-relaxed">
                <div x-text="activeLog || 'No console log output recorded for this deployment.'"></div>
            </div>

            <!-- Modal Footer -->
            <div class="p-3 border-t border-slate-800 flex justify-end bg-slate-950/80">
                <button @click="showLogsModal = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-medium text-slate-200">
                    Close Logs
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: Trigger New Deployment -->
    <div x-show="showDeployModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.away="showDeployModal = false" class="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
            <form method="POST" action="{{ route('deployments.store') }}">
                @csrf
                <div class="p-5 border-b border-slate-800 bg-slate-950/80 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="rocket" class="w-5 h-5 text-cyan-400"></i>
                        <h3 class="text-base font-bold text-white">Trigger New Deployment Build</h3>
                    </div>
                    <button type="button" @click="showDeployModal = false" class="text-slate-400 hover:text-white">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="p-5 space-y-4 text-xs text-slate-300">
                    <div>
                        <label class="block font-medium text-slate-300 mb-1">Version / Docker Tag *</label>
                        <input type="text" name="version" required placeholder="e.g. v1.2.0 or 2026.09.15" 
                               class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 focus:outline-none focus:border-cyan-500 font-mono">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Git Branch</label>
                            <input type="text" name="branch" value="main" 
                                   class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 focus:outline-none focus:border-cyan-500 font-mono">
                        </div>
                        <div>
                            <label class="block font-medium text-slate-300 mb-1">Commit Hash (Optional)</label>
                            <input type="text" name="commit_id" placeholder="Auto-generated if empty" 
                                   class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 focus:outline-none focus:border-cyan-500 font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-300 mb-1">Commit / Release Summary</label>
                        <input type="text" name="commit_message" placeholder="e.g. feat: add live telemetry and queue monitoring" 
                               class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 focus:outline-none focus:border-cyan-500">
                    </div>

                    <div>
                        <label class="block font-medium text-slate-300 mb-1">Triggered By</label>
                        <input type="text" name="deployed_by" value="System Admin" 
                               class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 focus:outline-none focus:border-cyan-500">
                    </div>
                </div>

                <div class="p-4 border-t border-slate-800 flex justify-end gap-2 bg-slate-950/80">
                    <button type="button" @click="showDeployModal = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-medium text-slate-300">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white text-xs font-medium shadow-lg shadow-cyan-500/20 flex items-center gap-1.5">
                        <i data-lucide="play" class="w-3.5 h-3.5"></i>
                        <span>Start Build & Push</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
