<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تثبيت منصة إدارة الشحن</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f4f6f9; }
        .wrap { max-width: 640px; margin: 6vh auto; }
        .req-ok { color: #198754; }
        .req-fail { color: #dc3545; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="text-center mb-4">
        <i class="bi bi-truck fs-1"></i>
        <h4 class="fw-bold mt-2">تثبيت منصة إدارة الشحن</h4>
        <p class="text-muted small">معالج تثبيت للاستضافات بدون SSH/Terminal</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card p-4 mb-3">
        <h6 class="mb-3">1) فحص متطلبات السيرفر</h6>
        <ul class="list-unstyled mb-0">
            <li class="{{ $phpOk ? 'req-ok' : 'req-fail' }}">
                {{ $phpOk ? '✓' : '✗' }} إصدار PHP {{ PHP_VERSION }} (المطلوب 8.3 فأعلى)
            </li>
            @foreach ($extensions as $ext => $ok)
                <li class="{{ $ok ? 'req-ok' : 'req-fail' }}">{{ $ok ? '✓' : '✗' }} إضافة PHP: {{ $ext }}</li>
            @endforeach
            <li class="{{ $envWritable ? 'req-ok' : 'req-fail' }}">{{ $envWritable ? '✓' : '✗' }} ملف .env قابل للكتابة</li>
            <li class="{{ $storageWritable ? 'req-ok' : 'req-fail' }}">{{ $storageWritable ? '✓' : '✗' }} مجلدات storage وbootstrap/cache قابلة للكتابة</li>
            <li class="{{ $tokenFileExists ? 'req-ok' : 'req-fail' }}">{{ $tokenFileExists ? '✓' : '✗' }} ملف INSTALL-TOKEN.txt موجود في جذر المشروع</li>
        </ul>
    </div>

    @php
        $allOk = $phpOk && $extensions->every(fn ($v) => $v) && $envWritable && $storageWritable && $tokenFileExists;
    @endphp

    @if (! $allOk)
        <div class="alert alert-warning">يجب حل النقاط أعلاه المُعلَّمة بـ ✗ قبل المتابعة (راجع docs/08 § 8.2.5، أو فعّل الإضافة الناقصة من cPanel &gt; MultiPHP INI Editor).</div>
    @else
        <div class="card p-4">
            <h6 class="mb-3">2) رمز التثبيت وبيانات قاعدة البيانات</h6>
            <form method="POST" action="{{ route('install.database') }}">
                @csrf
                <label class="form-label small">رمز التثبيت (من ملف INSTALL-TOKEN.txt)</label>
                <input type="text" name="token" class="form-control mb-3" required autofocus>

                <label class="form-label small">رابط الموقع (APP_URL)</label>
                <input type="url" name="app_url" class="form-control mb-3" placeholder="https://ship.example.com" value="{{ old('app_url', 'https://') }}" required>

                <div class="row g-2 mb-3">
                    <div class="col-8">
                        <label class="form-label small">MySQL Host</label>
                        <input type="text" name="db_host" class="form-control" value="{{ old('db_host', 'localhost') }}" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label small">Port</label>
                        <input type="text" name="db_port" class="form-control" value="{{ old('db_port', '3306') }}" required>
                    </div>
                </div>
                <label class="form-label small">اسم قاعدة البيانات (من cPanel &gt; MySQL Databases)</label>
                <input type="text" name="db_database" class="form-control mb-3" value="{{ old('db_database') }}" required>

                <label class="form-label small">مستخدم قاعدة البيانات</label>
                <input type="text" name="db_username" class="form-control mb-3" value="{{ old('db_username') }}" required>

                <label class="form-label small">كلمة مرور قاعدة البيانات</label>
                <input type="password" name="db_password" class="form-control mb-3">

                <button type="submit" class="btn btn-primary w-100">التالي — إعداد قاعدة البيانات</button>
            </form>
        </div>
    @endif
</div>
</body>
</html>
