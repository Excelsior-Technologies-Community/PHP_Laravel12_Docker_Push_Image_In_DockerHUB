<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', 'Laravel 12 Docker Control'); ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            500: '#0284c7',
                            600: '#0369a1',
                            900: '#0c4a6e',
                            950: '#082f49',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>
<body class="h-full flex flex-col antialiased bg-slate-950 text-slate-200">

    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-40 border-b border-slate-800 bg-slate-900/90 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand / Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-cyan-500 to-blue-600 flex items-center justify-center shadow-lg shadow-cyan-500/20 text-white font-bold text-lg">
                        🐳
                    </div>
                    <div>
                        <a href="<?php echo e(route('system.health')); ?>" class="text-lg font-bold bg-gradient-to-r from-cyan-400 via-sky-300 to-blue-500 bg-clip-text text-transparent">
                            Laravel Docker Control
                        </a>
                        <div class="text-xs text-slate-400 flex items-center gap-1.5 font-mono">
                            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Laravel 12.x • Docker Hub Ready
                        </div>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <nav class="flex items-center space-x-1 sm:space-x-2">
                    <a href="<?php echo e(route('deployments.index')); ?>" 
                       class="px-3.5 py-2 rounded-lg text-sm font-medium transition-all duration-150 flex items-center gap-2 <?php echo e(request()->routeIs('deployments.*') ? 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'); ?>">
                        <i data-lucide="history" class="w-4 h-4"></i>
                        <span>Deployments</span>
                    </a>

                    <a href="<?php echo e(route('system.health')); ?>" 
                       class="px-3.5 py-2 rounded-lg text-sm font-medium transition-all duration-150 flex items-center gap-2 <?php echo e(request()->routeIs('system.health*') ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'); ?>">
                        <i data-lucide="activity" class="w-4 h-4"></i>
                        <span>Live Health</span>
                    </a>

                    <a href="<?php echo e(route('logs.index')); ?>" 
                       class="px-3.5 py-2 rounded-lg text-sm font-medium transition-all duration-150 flex items-center gap-2 <?php echo e(request()->routeIs('logs.*') ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'); ?>">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                        <span>Log Viewer</span>
                    </a>
                </nav>

                <!-- Environment & Quick Actions -->
                <div class="hidden md:flex items-center space-x-3">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-800 text-cyan-300 border border-cyan-500/20">
                        <i data-lucide="server" class="w-3.5 h-3.5 mr-1 text-cyan-400"></i>
                        PHP <?php echo e(PHP_VERSION); ?>

                    </span>
                    <a href="/" target="_blank" class="text-xs text-slate-400 hover:text-slate-200 p-2 rounded-lg hover:bg-slate-800 flex items-center gap-1">
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        App Home
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Global Toast / Alerts -->
    <?php if(session('success')): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full" x-data="{ show: true }" x-show="show">
            <div class="p-4 rounded-xl bg-emerald-950/80 border border-emerald-500/40 text-emerald-300 flex items-center justify-between shadow-lg shadow-emerald-950/40">
                <div class="flex items-center gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-400 flex-shrink-0"></i>
                    <span class="text-sm font-medium"><?php echo e(session('success')); ?></span>
                </div>
                <button @click="show = false" class="text-emerald-400 hover:text-emerald-200"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full" x-data="{ show: true }" x-show="show">
            <div class="p-4 rounded-xl bg-rose-950/80 border border-rose-500/40 text-rose-300 flex items-center justify-between shadow-lg shadow-rose-950/40">
                <div class="flex items-center gap-3">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-400 flex-shrink-0"></i>
                    <span class="text-sm font-medium"><?php echo e(session('error')); ?></span>
                </div>
                <button @click="show = false" class="text-rose-400 hover:text-rose-200"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-900 bg-slate-950/60 py-4 text-center text-xs text-slate-500">
        <p>PHP Laravel 12 Dockerized Architecture • Docker Hub CI/CD Integrated</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</body>
</html>
<?php /**PATH D:\xampp\htdocs\git_desktop\PHP_Laravel12_Docker_Push_Image_In_DockerHUB\resources\views/layouts/admin.blade.php ENDPATH**/ ?>