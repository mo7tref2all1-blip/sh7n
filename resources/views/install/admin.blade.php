<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تثبيت منصة إدارة الشحن — حساب المدير</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; background: #f4f6f9; } .wrap { max-width: 480px; margin: 6vh auto; }</style>
</head>
<body>
<div class="wrap">
    <div class="card p-4">
        <h6 class="mb-3">3) إنشاء حساب Super Admin</h6>
        <p class="text-muted small">هذا هو الحساب اللي هتدخل بيه على النظام كمالك/مدير كامل الصلاحيات.</p>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('install.admin.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <label class="form-label small">الاسم</label>
            <input type="text" name="name" class="form-control mb-3" value="{{ old('name') }}" required autofocus>

            <label class="form-label small">رقم الهاتف (للدخول)</label>
            <input type="text" name="phone" class="form-control mb-3" value="{{ old('phone') }}" required>

            <label class="form-label small">البريد الإلكتروني (اختياري)</label>
            <input type="email" name="email" class="form-control mb-3" value="{{ old('email') }}">

            <label class="form-label small">كلمة المرور</label>
            <input type="password" name="password" class="form-control mb-3" required>

            <label class="form-label small">تأكيد كلمة المرور</label>
            <input type="password" name="password_confirmation" class="form-control mb-3" required>

            <button type="submit" class="btn btn-success w-100">إنهاء التثبيت</button>
        </form>
    </div>
</div>
</body>
</html>
