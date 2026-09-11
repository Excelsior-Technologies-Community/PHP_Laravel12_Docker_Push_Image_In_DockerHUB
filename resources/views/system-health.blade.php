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

        .stat-card {
            border: 0;

            border-radius: 15px;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.06);

            transition: 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .info-card {
            border: 0;

            border-radius: 15px;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.06);

            margin-bottom: 25px;
        }

        .health-card {
            border: 0;

            border-radius: 15px;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.06);

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

        .table-card {
            border: 0;

            border-radius: 15px;

            box-shadow:
                0 5px 20px rgba(0, 0, 0, 0.06);
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
        }

    </style>

</head>

<body>

<div class="container-fluid py-4">

    {{-- Header --}}

    <div class="dashboard-header">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div>

                <h2 class="mb-2">

                    <i class="bi bi-boxes me-2"></i>

                    Docker & Laravel Health Dashboard

                </h2>

                <p class="mb-0 text-white-50">

                    Monitor your Laravel application and Docker runtime.

                </p>

            </div>

            <div class="d-flex gap-2">

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


    {{-- Flash Message --}}

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


    {{-- Statistics --}}

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


    {{-- Docker Information --}}

    <div class="card info-card">

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


    {{-- Application Information --}}

    <div class="card info-card">

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

                            Laravel {{ $application['laravel_version'] }}

                        </span>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="small-label">
                        PHP
                    </div>

                    <div class="mt-2">

                        <span class="badge bg-primary">

                            PHP {{ $application['php_version'] }}

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

                        <span class="badge
                        {{ $application['debug'] === 'Enabled'
                            ? 'bg-warning text-dark'
                            : 'bg-success' }}">

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


    {{-- Health Checks --}}

    <div class="row g-4 mb-4">

        @foreach($health as $check)

            <div class="col-md-6 col-lg-4">

                <div class="card health-card">

                    <div class="card-body">

                        <div class="d-flex align-items-center mb-3">

                            <div class="health-icon
                                {{ $check['status'] }} me-3">

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

                                <span class="badge
                                    @if($check['status'] === 'healthy')
                                        bg-success
                                    @elseif($check['status'] === 'warning')
                                        bg-warning text-dark
                                    @else
                                        bg-danger
                                    @endif">

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


    {{-- Activity Logs --}}

    <div class="card table-card">

        <div class="card-header bg-white py-3">

            <div class="d-flex justify-content-between align-items-center">

                <h5 class="mb-0">

                    <i class="bi bi-clock-history me-2"></i>

                    Health Activity Log

                </h5>

                <form
                    method="POST"
                    action="{{ route('system.health.logs.clear') }}"
                    onsubmit="return confirm('Clear all health activity logs?')"
                >

                    @csrf

                    @method('DELETE')

                    <button class="btn btn-sm btn-outline-danger">

                        <i class="bi bi-trash me-1"></i>

                        Clear Logs

                    </button>

                </form>

            </div>

        </div>

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

                                    {{ $log->checked_at?->format('d M Y, h:i:s A') }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="5"
                                    class="text-center text-muted py-4"
                                >

                                    No health activity recorded yet.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>