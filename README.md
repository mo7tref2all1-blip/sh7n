# منصة إدارة الشحن — Egyptian Courier Platform

نسخة أولية (MVP) قابلة للتشغيل من نظام إدارة شركة شحن مصرية، مبنية بـ **Laravel 12** ومصممة للعمل مباشرة على استضافة **cPanel** (مشتركة أو VPS) بدون Docker أو Redis إجباريين.

الوثائق الكاملة (BRD / SRS / تصميم قاعدة البيانات / API / UI / خطة النشر) موجودة في **[`docs/00-INDEX.md`](./docs/00-INDEX.md)**. هذا الملف يغطي فقط تشغيل الكود.

## المميزات المُنفَّذة في هذه النسخة

- **مستخدمون وصلاحيات (RBAC)** عبر Spatie Permission — 11 دورًا مطابقين لـ [docs/04](./docs/04-Permissions-Matrix.md).
- **دورة حياة شحنة كاملة**: إنشاء → استلام → تسليم/فشل محاولة (لا يرد + سكرين شوت إلزامي / مؤجل / رفض استلام / رفض استلام وهروب مع تصعيد فوري / رفض سعر / عنوان خاطئ) → إرجاع.
- **فصل كامل بين الحالة التشغيلية والمالية**: التاجر يرى "محصَّل" فورًا، لكن المبلغ يبقى "مديونية على المندوب" داخليًا حتى يصل فعليًا للفرع ثم للشركة (`Shipment::FIN_*` + `CashService`).
- **عهدة نقدية (Cash In Hand)** لكل مندوب/وكيل/فرع + توريدات موثّقة بتأكيد ثنائي.
- **محفظة تاجر (Wallet Ledger)** Append-only: مستحقات التاجر = تحصيلات وصلت فعليًا − مصاريف شحن (تُخصم دائمًا حتى لو كان الشحن "مجاني" للعميل النهائي).
- **تسويات مالية** مع اعتماد + إرفاق إثبات دفع يظهر للتاجر.
- **تسعير مرن**: باقات عامة + تسعير مخصص لكل تاجر (Override) + سياسة مرتجع مستقلة لكل تاجر (مجاني / نسبة تحمّل).
- **وكلاء شحن** مستقلون عن المناديب المباشرين، بعمولات ومحفظة خاصة.
- **QR + Barcode** حقيقيين يُولَّدان تلقائيًا لكل شحنة (`endroid/qr-code` + `picqer/php-barcode-generator`).
- **رفع شحنات Excel بالجملة** عبر Queue مُجزّأ (Chunked) — لا يحمّل السيرفر دفعة واحدة.
- **بورتالات كاملة**: Admin / Merchant / Agent / Branch / Accountant + واجهة مندوب PWA-ready (Bootstrap 5 RTL، بدون Node.js على السيرفر وقت التشغيل).
- **التقاط GPS بدون أي تكلفة** (Geolocation API المجانية في المتصفح + رابط Google Maps نصي — لا مفتاح API مدفوع، تفصيل في [docs/02 § 2.2.8.1](./docs/02-SRS.md#2281-آلية-التقاط-الموقع-بدون-تكلفة)).
- **API عام (v1)** لتطبيق المندوب وتكامل المتاجر الإلكترونية — راجع [docs/06](./docs/06-API-Design.md).
- **Activity Log + Audit Log**: كل أكشن من المحاسب (تسوية، تأكيد توريد) يظهر تلقائيًا في تقرير نشاط الإدارة.

## غير مُنفَّذ بعد (Fast-Follow / Future Scope موثّق)

- تطبيق Flutter (يبدأ العمل بـ PWA أولاً كما هو مخطَّط).
- مزامنة Offline كاملة لتطبيق المندوب (Service Worker الحالي يخزّن الـ Shell فقط، وليس طابور العمليات).
- ربط فعلي مع WhatsApp Cloud API / SMS Gateway (المتغيرات جاهزة في `.env.example`، الإرسال الفعلي غير مُفعَّل).
- موديول التحديث عبر رفع ZIP — تعمّدنا تأجيله لآخر مرحلة لحساسيته الأمنية (تنفيذ كود عن بعد)، راجع النقاش في المحادثة.
- تفاصيل أكثر تُراجَع في [docs/09 § Future Scope](./docs/09-Roadmap-MVP-Team-Cost.md).

## التشغيل محليًا (Local Development)

```bash
composer install
cp .env.example .env
# للتطوير المحلي السريع بدون MySQL:
sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install && npm run build   # اختياري — الواجهات تعمل بالفعل عبر Bootstrap CDN بدون هذه الخطوة
php artisan serve
```

بعد التشغيل، افتح `http://127.0.0.1:8000/login`. حسابات تجريبية (كلمة المرور لجميعها `password`):

| الدور | الهاتف |
|---|---|
| Super Admin | 01000000001 |
| Company Owner | 01000000002 |
| Ops Manager | 01000000003 |
| Branch Manager | 01000000004 |
| Customer Service | 01000000005 |
| Accountant | 01000000006 |
| Merchant (صاحب متجر تجريبي) | 01000000011 |
| Agent (وكيل أسوان) | 01000000021 |
| Driver (أحمد علي) | 01000000030 |

الـ Seeder ينشئ أيضًا شحنة تجريبية كاملة الدورة (تم إنشاؤها → استلامها → تسليمها → تحصيل نقدي → توريد للفرع → توريد للشركة) لتشاهد فورًا كيف يعمل الفصل بين الحالة التشغيلية والمالية.

### تشغيل الطابور (Queue) محليًا

الاستيراد الجماعي والإشعارات تمر عبر Queue. أثناء التطوير شغّل في نافذة طرفية منفصلة:

```bash
php artisan queue:work
```

## النشر على cPanel (الإنتاج)

الخطوات التفصيلية الكاملة (بما فيها إعداد Cron بديل Supervisor، الأمان، النسخ الاحتياطي) موجودة في **[docs/08](./docs/08-System-Architecture-Security-Deployment.md)**. ملخص سريع:

1. أنشئ قاعدة بيانات MySQL من cPanel واضبط بياناتها في `.env` (القيم الافتراضية في `.env.example` تعكس هذا الإعداد).
2. `composer install --optimize-autoloader --no-dev`
3. `php artisan key:generate && php artisan migrate --force && php artisan db:seed --class=RolePermissionSeeder --force`
4. وجّه Document Root لمجلد `public/` فقط.
5. أضف Cron Job واحد لـ `schedule:run` وآخر لـ `queue:work --stop-when-empty` كل دقيقة (التفاصيل والأسطر الجاهزة في docs/08 § 8.2.3).
6. `php artisan storage:link`

## هيكل الكود

```
app/Models/         نماذج Eloquent (شحنات، عهدة، محفظة، تسويات...)
app/Services/        منطق الأعمال الأساسي (PricingService, ShipmentLifecycleService, CashService, WalletService, SettlementService)
app/Http/Controllers/ مقسّمة حسب البورتال: Admin, Merchant, Agent, Branch, Accountant, Driver, Api/V1, Public
app/Imports/          استيراد Excel الجماعي (Queued + Chunked)
database/migrations/  مخطط قاعدة البيانات الكامل
database/seeders/     محافظات مصر (27) + الأدوار والصلاحيات + بيانات تجريبية
resources/views/      Blade + Bootstrap 5 RTL لكل بورتال
routes/               web.php (البورتالات) + api_v1.php (تطبيق المندوب والتكاملات)
```
