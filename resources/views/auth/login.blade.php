<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background: linear-gradient(135deg,#16213e,#0f3460); min-height: 100vh; }
        .login-card { max-width: 400px; margin: 8vh auto; }
    </style>
</head>
<body>
    <div class="login-card card shadow p-4">
        <div class="text-center mb-4">
            <i class="bi bi-truck fs-1"></i>
            <h4 class="fw-bold mt-2">منصة إدارة الشحن</h4>
            <p class="text-muted small">سجّل الدخول للمتابعة</p>
        </div>
        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">رقم الهاتف أو البريد الإلكتروني</label>
                <input type="text" name="login" class="form-control" value="{{ old('login') }}" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">كلمة المرور</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                <label class="form-check-label" for="remember">تذكرني</label>
            </div>
            <button type="submit" class="btn btn-primary w-100">دخول</button>
        </form>
    </div>
</body>
</html>
