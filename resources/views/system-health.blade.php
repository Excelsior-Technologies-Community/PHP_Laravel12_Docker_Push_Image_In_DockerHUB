<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Docker & Laravel Health Dashboard</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>
        body {
            background: #f4f6f9;
        }

        .dashboard-header {
            background: linear-gradient(
                135deg,
                #212529,
                #343a40
            );
            color: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 25px;
        }

        .stat-card,
        .info-card,
        .health-card,
        .table-card,
        .resource-card {
            border: 0;
            border-radius: 15px;
            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.06);
        }

        .stat-card {
            transition: 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .health-card {
            height: 100%;
        }

        .health-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 22px;
        }

        .healthy {
            background: #d1e7dd;
            color: #0f5132;
        }

        .warning {
            background: #fff3cd;
            color: #664d03;
        }

        .failed {
            background: #f8d7da;
            color: #842029;
        }

        .docker-badge {
            font-size: 14px;
            padding: 8px 12px;
            border-radius: 30px;
        }

        .small-label {
            color: #6c757d;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .value-text {
            font-weight: 600;
            word-break: break-word;
        }

        .code-value {
            font-family: monospace;
            background: #f1f3f5;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 13px;
            word-break: break-word;
        }

        .score-circle {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            background: #f8f9fa;
            border: 10px solid #198754;
        }

        .score-number {
            font-size: 28px;
            font-weight: 700;
        }

        .resource-value {
            font-size: 20px;
            font-weight: 700;
        }

        .auto-refresh {
            font-size: 13px;
        }

        /* =========================================================
           NUMERIC-ONLY PAGINATION
           ========================================================= */

        .numeric-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .numeric-pagination .page-item {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .numeric-pagination .page-link {
            min-width: 40px;
            height: 40px;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 0 12px;

            border: 1px solid #dee2e6;
            border-radius: 8px;

            background: #ffffff;
            color: #212529;

            text-decoration: none;
            font-weight: 600;

            transition: all 0.2s ease;
        }

        .numeric-pagination .page-link:hover {
            background: #f1f3f5;
            color: #0d6efd;
            border-color: #0d6efd;
        }

        .numeric-pagination .page-item.active .page-link {
            background: #0d6efd;
            border-color: #0d6efd;
            color: #ffffff;
        }

        .numeric-pagination .page-item.disabled {
            display: none !important;
        }

        /* Hide Laravel/bootstrap generated arrows/text if any */
        .numeric-pagination .page-item:first-child,
        .numeric-pagination .page-item:last-child {
            display: list-item;
        }

        .pagination-info {
            text-align: center;
            color: #6c757d;
            font-size: 13px;
            margin-top: 10px;
        }
    </style>
</head>

<body>

<div class="container-fluid py-4">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="dashboard-header">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div>

                <h2 class="mb-2">

                    <i class="bi bi-boxes me-2"></i>

                    Docker & Laravel Health Dashboard

                </h2>

                <p class="mb-0 text-white-50">

                    Monitor Laravel, Docker, database, cache,
                    storage and server resources.

                </p>

            </div>

            <div class="d-flex gap-2 flex-wrap">

                <form
                    method="POST"
                    action="{{ route('system.health.check') }}"
                >

                    @csrf

                    <button class="btn btn-light">

                        <i class="bi bi-arrow-repeat me-1"></i>

                        Run Health Check

                    </button>

                </form>

                <a
                    href="{{ route('system.health.api') }}"
                    target="_blank"
                    class="btn btn-outline-light"
                >

                    <i class="bi bi-braces me-1"></i>

                    API

                </a>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- FLASH MESSAGE --}}
    {{-- ========================================================= --}}

    @if(session('success'))

        <div class="alert alert-success alert-dismissible fade show">

            <i class="bi bi-check-circle me-2"></i>

            {{ session('success') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- HEALTH SCORE --}}
    {{-- ========================================================= --}}

    <div class="card info-card mb-4">

        <div class="card-body">

            <div class="row align-items-center">

                <div class="col-md-3 text-center">

                    <div
                        class="score-circle mx-auto"
                        style="border-color:
                            {{ $healthScore >= 90
                                ? '#198754'
                                : ($healthScore >= 70
                                    ? '#ffc107'
                                    : '#dc3545') }}"
                    >

                        <div class="score-number">

                            {{ $healthScore }}%

                        </div>

                        <small>Health</small>

                    </div>

                </div>

                <div class="col-md-9">

                    <h4>
                        Overall System Health
                    </h4>

                    <p class="text-muted mb-2">

                        Current system status:

                        <span class="badge bg-{{ $healthStatusClass }}">

                            {{ $healthStatus }}

                        </span>

                    </p>

                    <div
                        class="progress"
                        style="height: 12px;"
                    >

                        <div
                            class="progress-bar bg-{{ $healthStatusClass }}"
                            role="progressbar"
                            style="width: {{ $healthScore }}%"
                        ></div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- STATISTICS --}}
    {{-- ========================================================= --}}

    <div class="row g-4 mb-4">

        <div class="col-md-3">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <div class="small-label">
                                Total Checks
                            </div>

                            <h2 class="mt-2 mb-0">
                                {{ $statistics['total_checks'] }}
                            </h2>

                        </div>

                        <div class="health-icon bg-primary text-white">

                            <i class="bi bi-activity"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <div class="small-label">
                                Healthy
                            </div>

                            <h2 class="mt-2 mb-0 text-success">

                                {{ $statistics['healthy_checks'] }}

                            </h2>

                        </div>

                        <div class="health-icon healthy">

                            <i class="bi bi-check-circle"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <div class="small-label">
                                Warnings
                            </div>

                            <h2 class="mt-2 mb-0 text-warning">

                                {{ $statistics['warning_checks'] }}

                            </h2>

                        </div>

                        <div class="health-icon warning">

                            <i class="bi bi-exclamation-triangle"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <div class="small-label">
                                Failed
                            </div>

                            <h2 class="mt-2 mb-0 text-danger">

                                {{ $statistics['failed_checks'] }}

                            </h2>

                        </div>

                        <div class="health-icon failed">

                            <i class="bi bi-x-circle"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- SERVER RESOURCES --}}
    {{-- ========================================================= --}}

    <div class="card resource-card mb-4">

        <div class="card-header bg-white py-3">

            <h5 class="mb-0">

                <i class="bi bi-pc-display me-2"></i>

                Server Resources

            </h5>

        </div>

        <div class="card-body">

            <div class="row g-4">

                <div class="col-md-3">

                    <div class="small-label">
                        Disk Total
                    </div>

                    <div class="resource-value mt-2">

                        {{ $resources['disk_total'] }}

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        Disk Used
                    </div>

                    <div class="resource-value mt-2">

                        {{ $resources['disk_used'] }}

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        Disk Free
                    </div>

                    <div class="resource-value mt-2 text-success">

                        {{ $resources['disk_free'] }}

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        Disk Usage
                    </div>

                    <div class="resource-value mt-2">

                        @if($resources['disk_usage_percent'] !== null)

                            {{ $resources['disk_usage_percent'] }}%

                        @else

                            N/A

                        @endif

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        PHP Memory Usage
                    </div>

                    <div class="resource-value mt-2">

                        {{ $resources['memory_usage'] }}

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        PHP Peak Memory
                    </div>

                    <div class="resource-value mt-2">

                        {{ $resources['memory_peak'] }}

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        PHP Memory Limit
                    </div>

                    <div class="resource-value mt-2">

                        {{ $resources['memory_limit'] }}

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        Server Software
                    </div>

                    <div class="mt-2 value-text">

                        {{ $resources['server_software'] }}

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- DOCKER INFORMATION --}}
    {{-- ========================================================= --}}

    <div class="card info-card mb-4">

        <div class="card-header bg-white py-3">

            <h5 class="mb-0">

                <i class="bi bi-box-seam me-2"></i>

                Docker Runtime Information

            </h5>

        </div>

        <div class="card-body">

            <div class="row g-4">

                <div class="col-md-3">

                    <div class="small-label">
                        Docker Status
                    </div>

                    <div class="mt-2">

                        @if($docker['running_in_docker'])

                            <span class="badge bg-success docker-badge">

                                <i class="bi bi-check-circle me-1"></i>

                                Running in Docker

                            </span>

                        @else

                            <span class="badge bg-danger docker-badge">

                                <i class="bi bi-x-circle me-1"></i>

                                Not Docker

                            </span>

                        @endif

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        Container ID
                    </div>

                    <div class="mt-2 code-value">

                        {{ $docker['container_id'] }}

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        Hostname
                    </div>

                    <div class="mt-2 value-text">

                        {{ $docker['hostname'] }}

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        PHP SAPI
                    </div>

                    <div class="mt-2 value-text">

                        {{ $docker['php_sapi'] }}

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        Image Name
                    </div>

                    <div class="mt-2 code-value">

                        {{ $docker['image_name'] }}

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        Image Tag
                    </div>

                    <div class="mt-2">

                        <span class="badge bg-dark">

                            {{ $docker['image_tag'] }}

                        </span>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        Docker Hub Repository
                    </div>

                    <div class="mt-2 code-value">

                        {{ $docker['docker_repository'] }}

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        Build Time
                    </div>

                    <div class="mt-2 value-text">

                        {{ $docker['build_time'] }}

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- APPLICATION INFORMATION --}}
    {{-- ========================================================= --}}

    <div class="card info-card mb-4">

        <div class="card-header bg-white py-3">

            <h5 class="mb-0">

                <i class="bi bi-code-slash me-2"></i>

                Laravel Application Information

            </h5>

        </div>

        <div class="card-body">

            <div class="row g-4">

                <div class="col-md-3">

                    <div class="small-label">
                        Application
                    </div>

                    <div class="mt-2 value-text">

                        {{ $application['application_name'] }}

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        Laravel
                    </div>

                    <div class="mt-2">

                        <span class="badge bg-danger">

                            Laravel
                            {{ $application['laravel_version'] }}

                        </span>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        PHP
                    </div>

                    <div class="mt-2">

                        <span class="badge bg-primary">

                            PHP
                            {{ $application['php_version'] }}

                        </span>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        Environment
                    </div>

                    <div class="mt-2">

                        <span class="badge bg-dark">

                            {{ $application['environment'] }}

                        </span>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        Debug
                    </div>

                    <div class="mt-2">

                        <span
                            class="badge
                            {{ $application['debug'] === 'Enabled'
                                ? 'bg-warning text-dark'
                                : 'bg-success' }}"
                        >

                            {{ $application['debug'] }}

                        </span>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        Timezone
                    </div>

                    <div class="mt-2 value-text">

                        {{ $application['timezone'] }}

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="small-label">
                        Base Path
                    </div>

                    <div class="mt-2 code-value">

                        {{ $application['base_path'] }}

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="small-label">
                        Storage Path
                    </div>

                    <div class="mt-2 code-value">

                        {{ $application['storage_path'] }}

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- HEALTH CHECK CARDS --}}
    {{-- ========================================================= --}}

    <div class="row g-4 mb-4">

        @foreach($health as $check)

            <div class="col-md-6 col-lg-4">

                <div class="card health-card">

                    <div class="card-body">

                        <div class="d-flex align-items-center mb-3">

                            <div
                                class="health-icon
                                {{ $check['status'] }} me-3"
                            >

                                @if($check['status'] === 'healthy')

                                    <i class="bi bi-check-lg"></i>

                                @elseif($check['status'] === 'warning')

                                    <i class="bi bi-exclamation-lg"></i>

                                @else

                                    <i class="bi bi-x-lg"></i>

                                @endif

                            </div>

                            <div>

                                <h6 class="mb-1">

                                    {{ $check['name'] }}

                                </h6>

                                <span
                                    class="badge
                                    @if($check['status'] === 'healthy')
                                        bg-success
                                    @elseif($check['status'] === 'warning')
                                        bg-warning text-dark
                                    @else
                                        bg-danger
                                    @endif"
                                >

                                    {{ strtoupper($check['status']) }}

                                </span>

                            </div>

                        </div>

                        <p class="text-muted mb-0">

                            {{ $check['message'] }}

                        </p>

                        @if(!empty($check['details']))

                            <hr>

                            @foreach($check['details'] as $key => $value)

                                <div class="small mb-1">

                                    <strong>
                                        {{ ucfirst($key) }}:
                                    </strong>

                                    @if(is_array($value))

                                        {{ json_encode($value) }}

                                    @else

                                        {{ $value }}

                                    @endif

                                </div>

                            @endforeach

                        @endif

                    </div>

                </div>

            </div>

        @endforeach

    </div>


    {{-- ========================================================= --}}
    {{-- HEALTH ACTIVITY LOG --}}
    {{-- ========================================================= --}}

    <div class="card table-card">

        <div class="card-header bg-white py-3">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                <h5 class="mb-0">

                    <i class="bi bi-clock-history me-2"></i>

                    Health Activity Log

                </h5>

                <div class="auto-refresh text-muted">

                    <i class="bi bi-arrow-repeat"></i>

                    Auto refresh:

                    <strong id="countdown">
                        30
                    </strong>

                    sec

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- SEARCH / FILTER --}}
        {{-- ========================================================= --}}

        <div class="card-body border-bottom">

            <form
                method="GET"
                action="{{ route('system.health') }}"
                class="row g-3"
            >

                <div class="col-md-5">

                    <label class="form-label">
                        Search Logs
                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Search check type or message..."
                        value="{{ $search }}"
                    >

                </div>


                <div class="col-md-3">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">
                            All Statuses
                        </option>

                        <option
                            value="healthy"
                            @selected($status == 'healthy')
                        >
                            Healthy
                        </option>

                        <option
                            value="warning"
                            @selected($status == 'warning')
                        >
                            Warning
                        </option>

                        <option
                            value="failed"
                            @selected($status == 'failed')
                        >
                            Failed
                        </option>

                    </select>

                </div>


                <div class="col-md-4 d-flex align-items-end gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-search me-1"></i>

                        Search

                    </button>


                    <a
                        href="{{ route('system.health') }}"
                        class="btn btn-outline-secondary"
                    >
                        Reset
                    </a>


                    <a
                        href="{{ route(
                            'system.health.logs.export',
                            request()->query()
                        ) }}"
                        class="btn btn-success"
                    >

                        <i class="bi bi-filetype-csv me-1"></i>

                        CSV

                    </a>

                </div>

            </form>

        </div>


        {{-- ========================================================= --}}
        {{-- CLEANUP --}}
        {{-- ========================================================= --}}

        <div class="card-body border-bottom">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                <div>

                    <strong>
                        Log Management
                    </strong>

                    <div class="text-muted small">

                        Remove old health activity records.

                    </div>

                </div>


                <form
                    method="POST"
                    action="{{ route('system.health.logs.cleanup') }}"
                    class="d-flex gap-2"
                >

                    @csrf

                    @method('DELETE')

                    <select
                        name="days"
                        class="form-select"
                    >

                        <option value="7">
                            Older than 7 days
                        </option>

                        <option value="15">
                            Older than 15 days
                        </option>

                        <option
                            value="30"
                            selected
                        >
                            Older than 30 days
                        </option>

                        <option value="60">
                            Older than 60 days
                        </option>

                        <option value="90">
                            Older than 90 days
                        </option>

                    </select>


                    <button
                        class="btn btn-outline-danger"
                        onclick="return confirm(
                            'Delete old health logs?'
                        )"
                    >

                        <i class="bi bi-trash me-1"></i>

                        Cleanup

                    </button>

                </form>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- TABLE --}}
        {{-- ========================================================= --}}

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>#</th>

                            <th>Check</th>

                            <th>Status</th>

                            <th>Message</th>

                            <th>Checked At</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($recentLogs as $log)

                            <tr>

                                <td>
                                    {{ $log->id }}
                                </td>

                                <td>

                                    <strong>

                                        {{ $log->check_type }}

                                    </strong>

                                </td>

                                <td>

                                    @if($log->status === 'healthy')

                                        <span class="badge bg-success">

                                            Healthy

                                        </span>

                                    @elseif($log->status === 'warning')

                                        <span class="badge bg-warning text-dark">

                                            Warning

                                        </span>

                                    @else

                                        <span class="badge bg-danger">

                                            Failed

                                        </span>

                                    @endif

                                </td>

                                <td>

                                    {{ $log->message }}

                                </td>

                                <td>

                                    {{ $log->checked_at?->format(
                                        'd M Y, h:i:s A'
                                    ) }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="5"
                                    class="text-center text-muted py-4"
                                >

                                    No health activity found.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- NUMERIC-ONLY PAGINATION --}}
        {{-- IMPORTANT: DO NOT USE $recentLogs->links() HERE --}}
        {{-- ========================================================= --}}

        @if($recentLogs->hasPages())

            <div class="card-footer bg-white">

                <nav aria-label="Health activity pagination">

                    <ul class="numeric-pagination">

                        @for(
                            $page = 1;
                            $page <= $recentLogs->lastPage();
                            $page++
                        )

                            <li
                                class="page-item
                                {{ $page == $recentLogs->currentPage()
                                    ? 'active'
                                    : '' }}"
                            >

                                <a
                                    class="page-link"
                                    href="{{ $recentLogs->url($page) }}"
                                >

                                    {{ $page }}

                                </a>

                            </li>

                        @endfor

                    </ul>

                </nav>


                <div class="pagination-info">

                    Showing

                    <strong>
                        {{ $recentLogs->firstItem() }}
                    </strong>

                    to

                    <strong>
                        {{ $recentLogs->lastItem() }}
                    </strong>

                    of

                    <strong>
                        {{ $recentLogs->total() }}
                    </strong>

                    results

                </div>

            </div>

        @endif

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<script>

    /* =========================================================
       AUTO REFRESH
       ========================================================= */

    let countdown = 30;

    const countdownElement =
        document.getElementById('countdown');

    setInterval(function () {

        countdown--;

        countdownElement.textContent =
            countdown;

        if (countdown <= 0) {

            window.location.reload();

        }

    }, 1000);

</script>

</body>

</html>

