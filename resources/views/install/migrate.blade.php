<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تثبيت منصة إدارة الشحن — قاعدة البيانات</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; background: #f4f6f9; } .wrap { max-width: 640px; margin: 6vh auto; }</style>
</head>
<body>
<div class="wrap">
    <div class="card p-4">
        <h6 class="mb-3">✓ تم إنشاء الجداول والبيانات الأساسية بنجاح</h6>
        <p class="text-muted small">تم تشغيل الـ Migrations + بيانات المحافظات + الأدوار/الصلاحيات + التسعير الافتراضي.</p>
        <details class="mb-3">
            <summary class="small text-muted">عرض تفاصيل التشغيل</summary>
            <pre class="small bg-light p-2 mt-2" style="white-space: pre-wrap;">{{ $output }}</pre>
        </details>
        <a href="{{ route('install.admin.form', ['token' => $token]) }}" class="btn btn-primary w-100">التالي — إنشاء حساب المدير</a>
    </div>
</div>
</body>
</html>
