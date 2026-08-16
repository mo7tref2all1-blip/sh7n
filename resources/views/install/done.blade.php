<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اكتمل التثبيت</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; background: #f4f6f9; } .wrap { max-width: 640px; margin: 6vh auto; }</style>
</head>
<body>
<div class="wrap">
    <div class="card p-4">
        <h5 class="text-success mb-3">🎉 تم التثبيت بنجاح</h5>
        <p>تم إنشاء حساب المدير، وتم قفل صفحة التثبيت نهائيًا (لن تعمل مرة أخرى حتى لو أعدت زيارتها).</p>

        <div class="alert alert-warning">
            <strong>خطوات يدوية متبقية من لوحة cPanel (بدون شل):</strong>
            <ol class="mb-0 mt-2">
                <li>تأكد أن Document Root للدومين يشير لمجلد <code>public/</code> الخاص بالتطبيق (من cPanel &gt; Domains).</li>
                <li>أضف من cPanel &gt; Cron Jobs السطرين التاليين (كل دقيقة):
                    <pre class="small bg-light p-2 mt-2 mb-1" style="white-space: pre-wrap;">* * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd {{ base_path() }} && timeout 55 php artisan queue:work --stop-when-empty --tries=3 --max-time=50 >> /dev/null 2>&1</pre>
                </li>
                <li>فعّل SSL مجاني من cPanel &gt; SSL/TLS Status (AutoSSL).</li>
            </ol>
        </div>

        <a href="{{ route('login') }}" class="btn btn-primary w-100">الذهاب لصفحة تسجيل الدخول</a>
    </div>
</div>
</body>
</html>
