<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'منصة الشحن') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f4f6f9; }
        .sidebar { min-height: 100vh; background: #16213e; }
        .sidebar .nav-link { color: #c9d1e3; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { color: #fff; background: #1f2b4d; }
        .navbar-brand { font-weight: 700; }
        .badge-status { font-size: .75rem; }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .stat-card h3 { font-weight: 700; }
    </style>
    @stack('styles')
</head>
<body>
    <div class="d-flex">
        @hasSection('sidebar')
            <nav class="sidebar p-3" style="width:240px;">
                <div class="text-white mb-4 fs-5"><i class="bi bi-truck"></i> منصة الشحن</div>
                @yield('sidebar')
            </nav>
        @endif
        <div class="flex-grow-1">
            <nav class="navbar navbar-light bg-white border-bottom px-3">
                <span class="navbar-text">@yield('title', 'الرئيسية')</span>
                <div class="d-flex align-items-center gap-3">
                    @auth
                        <span class="text-muted small">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary">تسجيل الخروج</button>
                        </form>
                    @endauth
                </div>
            </nav>
            <main class="p-4">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }
    </script>
    @stack('scripts')
</body>
</html>
