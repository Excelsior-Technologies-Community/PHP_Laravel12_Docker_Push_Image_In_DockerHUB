@extends('layouts.admin')

@section('title', 'Log Viewer - Multi-Source Logs')

@section('content')
<div class="space-y-6" x-data="{
    showDetailModal: false,
    selectedLog: null,
    viewDetail(entry) {
        this.selectedLog = entry;
        this.showDetailModal = true;
    }
}">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-2">
                <i data-lucide="file-text" class="w-6 h-6 text-amber-400"></i>
                Multi-Source Application & Container Log Viewer
            </h1>
            <p class="text-sm text-slate-400 mt-1">
                Inspect, search, and monitor real-time Laravel errors, Docker container output, and Nginx/PHP server logs.
            </p>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2">
            <!-- Clear Log -->
            <form method="POST" action="{{ route('logs.clear') }}" onsubmit="return confirm('Are you sure you want to clear this log file? This action cannot be undone.')">
                @csrf
                <input type="hidden" name="source" value="{{ $source }}">
                <input type="hidden" name="file" value="{{ $file }}">
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-rose-950/60 hover:text-rose-400 hover:border-rose-500/40 text-slate-300 text-xs font-medium border border-slate-700 transition-colors flex items-center gap-1.5">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Clear File</span>
                </button>
            </form>

            <!-- Download Log -->
            <a href="{{ route('logs.download', ['file' => $file ?: 'laravel.log']) }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium border border-slate-700 transition-colors flex items-center gap-1.5">
                <i data-lucide="download" class="w-3.5 h-3.5 text-cyan-400"></i>
                <span>Download</span>
            </a>
        </div>
    </div>

    <!-- Source Selector Tabs -->
    <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 pb-3">
        <a href="{{ route('logs.index', ['source' => 'laravel']) }}" 
           class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all {{ $source === 'laravel' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/40 shadow-sm shadow-cyan-500/10' : 'bg-slate-900/60 text-slate-400 hover:text-slate-200 border border-slate-800' }}">
            <i data-lucide="layers" class="w-4 h-4 text-red-400"></i>
            <span>Laravel Logs (Framework)</span>
        </a>

        <a href="{{ route('logs.index', ['source' => 'docker']) }}" 
           class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all {{ $source === 'docker' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/40 shadow-sm shadow-cyan-500/10' : 'bg-slate-900/60 text-slate-400 hover:text-slate-200 border border-slate-800' }}">
            <i data-lucide="container" class="w-4 h-4 text-blue-400"></i>
            <span>Docker Container Logs</span>
        </a>

        <a href="{{ route('logs.index', ['source' => 'nginx']) }}" 
           class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all {{ $source === 'nginx' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/40 shadow-sm shadow-cyan-500/10' : 'bg-slate-900/60 text-slate-400 hover:text-slate-200 border border-slate-800' }}">
            <i data-lucide="globe" class="w-4 h-4 text-emerald-400"></i>
            <span>Nginx Server Logs</span>
        </a>

        <a href="{{ route('logs.index', ['source' => 'php']) }}" 
           class="px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 transition-all {{ $source === 'php' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/40 shadow-sm shadow-cyan-500/10' : 'bg-slate-900/60 text-slate-400 hover:text-slate-200 border border-slate-800' }}">
            <i data-lucide="cpu" class="w-4 h-4 text-indigo-400"></i>
            <span>PHP / FPM Error Logs</span>
        </a>
    </div>

    <!-- Level Filter Badges -->
    <div class="flex flex-wrap items-center gap-2">
        @php
            $levels = [
                'ALL' => ['label' => 'All Levels', 'color' => 'bg-slate-800 text-slate-300 border-slate-700'],
                'ERROR' => ['label' => 'Errors', 'color' => 'bg-rose-500/10 text-rose-400 border-rose-500/30'],
                'CRITICAL' => ['label' => 'Critical', 'color' => 'bg-red-500/20 text-red-300 border-red-500/40'],
                'WARNING' => ['label' => 'Warnings', 'color' => 'bg-amber-500/10 text-amber-400 border-amber-500/30'],
                'INFO' => ['label' => 'Info', 'color' => 'bg-blue-500/10 text-blue-400 border-blue-500/30'],
                'DEBUG' => ['label' => 'Debug', 'color' => 'bg-slate-500/10 text-slate-400 border-slate-500/30'],
            ];
        @endphp

        @foreach($levels as $lvlKey => $lvlInfo)
            <a href="{{ route('logs.index', array_merge(request()->query(), ['level' => $lvlKey])) }}" 
               class="px-3 py-1.5 rounded-lg text-xs font-medium border flex items-center gap-2 transition-all {{ $level === $lvlKey ? 'ring-2 ring-cyan-500 ' . $lvlInfo['color'] : 'bg-slate-900/60 text-slate-400 border-slate-800 hover:text-slate-200' }}">
                <span>{{ $lvlInfo['label'] }}</span>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-black/40 text-slate-300">
                    {{ $levelCounts[$lvlKey] ?? 0 }}
                </span>
            </a>
        @endforeach
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800 shadow-md">
        <form method="GET" action="{{ route('logs.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <input type="hidden" name="source" value="{{ $source }}">
            <input type="hidden" name="level" value="{{ $level }}">

            <!-- File Selector -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Log File</label>
                <select name="file" onchange="this.form.submit()" class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-slate-200 focus:outline-none focus:border-cyan-500">
                    @foreach($availableFiles as $f)
                        <option value="{{ $f['name'] }}" {{ ($file === $f['name'] || (!$file && $loop->first)) ? 'selected' : '' }}>
                            {{ $f['name'] }} ({{ $f['size'] }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Date Filter -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Date Filter (YYYY-MM-DD)</label>
                <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" 
                       class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-slate-200 focus:outline-none focus:border-cyan-500">
            </div>

            <!-- Search Query -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Search Keyword</label>
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search messages, trace, error code..." 
                           class="w-full pl-8 pr-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-cyan-500">
                </div>
            </div>

            <!-- Line Limit & Submit -->
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">Limit</label>
                    <select name="limit" class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-slate-200 focus:outline-none focus:border-cyan-500">
                        <option value="50" {{ request('limit') == 50 ? 'selected' : '' }}>50 lines</option>
                        <option value="100" {{ request('limit', 100) == 100 ? 'selected' : '' }}>100 lines</option>
                        <option value="250" {{ request('limit') == 250 ? 'selected' : '' }}>250 lines</option>
                        <option value="500" {{ request('limit') == 500 ? 'selected' : '' }}>500 lines</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-medium transition-colors">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Logs Table Container -->
    <div class="rounded-2xl bg-slate-900/60 border border-slate-800 overflow-hidden shadow-lg">
        
        <div class="p-3 border-b border-slate-800 bg-slate-900/80 flex items-center justify-between text-xs text-slate-400">
            <span class="flex items-center gap-1.5">
                <i data-lucide="list" class="w-3.5 h-3.5 text-cyan-400"></i>
                Log Source: <strong class="text-slate-200 font-mono">{{ strtoupper($source) }}</strong> • File: <strong class="text-cyan-300 font-mono">{{ $logData['file'] }}</strong>
            </span>
            <span>Showing {{ count($logData['entries']) }} of {{ $logData['total'] }} matching entries</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-mono">
                <thead class="bg-slate-950 text-slate-400 uppercase tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3 w-40">Timestamp</th>
                        <th class="px-4 py-3 w-28">Level</th>
                        <th class="px-4 py-3">Message & Context</th>
                        <th class="px-4 py-3 text-right w-24">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($logData['entries'] as $entry)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            
                            <!-- Timestamp -->
                            <td class="px-4 py-3 text-slate-400 whitespace-nowrap align-top text-[11px]">
                                {{ $entry['timestamp'] }}
                            </td>

                            <!-- Level -->
                            <td class="px-4 py-3 align-top">
                                @php
                                    $lvl = strtoupper($entry['level']);
                                    $badgeColor = match($lvl) {
                                        'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => 'bg-rose-500/20 text-rose-400 border-rose-500/40',
                                        'WARNING' => 'bg-amber-500/20 text-amber-400 border-amber-500/40',
                                        'INFO', 'NOTICE' => 'bg-blue-500/20 text-blue-400 border-blue-500/40',
                                        default => 'bg-slate-700/40 text-slate-300 border-slate-600',
                                    };
                                @endphp
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold border {{ $badgeColor }}">
                                    {{ $lvl }}
                                </span>
                            </td>

                            <!-- Message -->
                            <td class="px-4 py-3 text-slate-200 break-words align-top">
                                <div class="line-clamp-2">{{ $entry['message'] }}</div>
                                @if(!empty($entry['stack_trace']))
                                    <div class="mt-1 text-[10px] text-rose-400/80 flex items-center gap-1">
                                        <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                        <span>Stack trace available</span>
                                    </div>
                                @endif
                            </td>

                            <!-- Action -->
                            <td class="px-4 py-3 text-right align-top">
                                <button @click="viewDetail({{ json_encode($entry) }})" 
                                        class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-cyan-400 text-[11px] border border-slate-700 transition-colors">
                                    Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i data-lucide="check-circle-2" class="w-8 h-8 text-emerald-500 mb-2"></i>
                                    <p class="text-sm font-medium text-slate-300">No logs found matching your criteria.</p>
                                    <p class="text-xs text-slate-500 mt-1">Try clearing filters or changing log level / date range.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Log Detail Inspector -->
    <div x-show="showDetailModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.away="showDetailModal = false" class="w-full max-w-4xl bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
            
            <!-- Modal Header -->
            <div class="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/80">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded text-xs font-bold font-mono" 
                          :class="{
                              'bg-rose-500/20 text-rose-400 border border-rose-500/40': selectedLog && ['ERROR', 'CRITICAL', 'EMERGENCY', 'ALERT'].includes(selectedLog.level),
                              'bg-amber-500/20 text-amber-400 border border-amber-500/40': selectedLog && selectedLog.level === 'WARNING',
                              'bg-blue-500/20 text-blue-400 border border-blue-500/40': selectedLog && ['INFO', 'NOTICE', 'DEBUG'].includes(selectedLog.level)
                          }"
                          x-text="selectedLog ? selectedLog.level : ''"></span>
                    <span class="text-xs text-slate-400 font-mono" x-text="selectedLog ? selectedLog.timestamp : ''"></span>
                </div>
                <button @click="showDetailModal = false" class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Content Area -->
            <div class="p-5 overflow-y-auto space-y-4 text-xs font-mono">
                <!-- Message -->
                <div>
                    <h4 class="text-slate-400 uppercase tracking-wider text-[11px] font-semibold mb-1">Message</h4>
                    <div class="p-3 rounded-xl bg-black/80 text-slate-200 whitespace-pre-wrap leading-relaxed border border-slate-800" x-text="selectedLog ? selectedLog.message : ''"></div>
                </div>

                <!-- Stack Trace -->
                <div x-show="selectedLog && selectedLog.stack_trace">
                    <h4 class="text-rose-400 uppercase tracking-wider text-[11px] font-semibold mb-1 flex items-center gap-1">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                        Stack Trace
                    </h4>
                    <div class="p-3 rounded-xl bg-black/90 text-rose-300 whitespace-pre-wrap leading-relaxed border border-rose-950 overflow-x-auto max-h-72" x-text="selectedLog ? selectedLog.stack_trace : ''"></div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-3 border-t border-slate-800 flex justify-end bg-slate-950/80">
                <button @click="showDetailModal = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-medium text-slate-200">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
