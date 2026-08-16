# 6. تصميم واجهة برمجة التطبيقات (API Design)

## 6.1 مبادئ التصميم العامة

- **REST API** قياسي، إصدار مضمّن في المسار: `/api/v1/...`
- تنسيق الاستجابة موحّد:
```json
{
  "success": true,
  "data": { },
  "message": "تمت العملية بنجاح",
  "meta": { "page": 1, "per_page": 20, "total": 134 }
}
```
وعند الخطأ:
```json
{
  "success": false,
  "message": "بيانات غير صالحة",
  "errors": { "consignee_phone": ["رقم الهاتف مطلوب"] }
}
```
- **المصادقة**: Laravel Sanctum — Token لكل تاجر/وكيل/مندوب (`api_keys` / Personal Access Tokens)، بالإضافة إلى جلسات Web عادية للوحات الإدارية.
- **Rate Limiting**: افتراضي 60 طلب/دقيقة للـ API العام، قابل للرفع لكل تاجر حسب الحاجة (`throttle:api` مخصص).
- **الترقيم (Pagination)**: Cursor أو Offset قياسي لكل قوائم البيانات، حد أقصى 100 عنصر/صفحة.
- **Idempotency**: عمليات إنشاء الشحنة عبر API تدعم Header اختياري `Idempotency-Key` لمنع تكرار الإنشاء عند إعادة المحاولة من جانب المتجر الإلكتروني.
- **التوثيق**: يُنشر تلقائيًا عبر OpenAPI/Swagger (`l5-swagger` package) على `/api/documentation`.

## 6.2 API التاجر (Merchant API) — الأهم للتكاملات الخارجية

| Method | Endpoint | الوصف |
|---|---|---|
| POST | `/api/v1/auth/login` | تسجيل دخول (يُرجع Token) |
| GET | `/api/v1/merchant/profile` | بيانات التاجر + سياسة POD + خطة التسعير |
| GET | `/api/v1/merchant/pricing` | جدول أسعار التاجر الحالي (محافظة/وزن/خدمة) |
| POST | `/api/v1/shipments` | إنشاء شحنة واحدة |
| POST | `/api/v1/shipments/bulk` | إنشاء شحنات متعددة (حتى 500 في الطلب الواحد؛ الأكبر عبر رفع ملف) |
| POST | `/api/v1/shipments/import` | رفع ملف Excel/CSV (معالجة Async، يُرجع `import_id` للمتابعة) |
| GET | `/api/v1/shipments/import/{import_id}` | حالة الاستيراد + تقرير الأخطاء |
| GET | `/api/v1/shipments` | قائمة شحنات التاجر (فلترة: status, date_from, date_to, financial_status) |
| GET | `/api/v1/shipments/{tracking_number}` | تفاصيل شحنة + Timeline كامل |
| PATCH | `/api/v1/shipments/{tracking_number}/cancel` | إلغاء شحنة (فقط قبل `picked_up`) |
| GET | `/api/v1/wallet` | رصيد المحفظة الحالي |
| GET | `/api/v1/wallet/transactions` | كشف حساب (Ledger) مع فلترة تاريخية |
| GET | `/api/v1/settlements` | قائمة التسويات |
| GET | `/api/v1/settlements/{id}` | تفاصيل تسوية + مرفقات إثبات التحويل |
| GET | `/api/v1/reports/summary` | ملخص KPI (شحنات/تسليم/مرتجعات/تحصيلات) لفترة محددة |
| POST | `/api/v1/webhooks/subscribe` | تسجيل Webhook URL لاستقبال تحديثات الحالة |

### مثال Payload — إنشاء شحنة
```json
POST /api/v1/shipments
{
  "reference_number": "ORDER-10293",
  "consignee_name": "أحمد محمد",
  "consignee_phone": "01012345678",
  "governorate": "الجيزة",
  "zone": "6 أكتوبر",
  "address_text": "شارع الحصري، عمارة 12",
  "package_description": "ملابس",
  "weight_kg": 1.5,
  "service_type": "normal",
  "payment_method": "cod",
  "amount_to_collect": 450.00
}
```
### مثال استجابة
```json
{
  "success": true,
  "data": {
    "tracking_number": "SHP-2026-00931245",
    "shipping_fee": 55.00,
    "qr_code_url": "https://domain.com/storage/qrcodes/SHP-2026-00931245.png",
    "barcode_value": "SHP2026009312 45",
    "shipment_status": "created"
  }
}
```

## 6.3 API التتبع العام (Public — بدون مصادقة)

| Method | Endpoint | الوصف |
|---|---|---|
| GET | `/api/v1/public/track/{tracking_number}` | حالة الشحنة + Timeline مختصر (بدون بيانات حساسة كالمبلغ الكامل إلا لصاحبها) |
| POST | `/api/v1/public/track/{tracking_number}/reschedule` | طلب العميل النهائي إعادة جدولة موعد التسليم |

## 6.4 API الوكيل (Agent Portal API)

| Method | Endpoint | الوصف |
|---|---|---|
| GET | `/api/v1/agent/shipments` | الشحنات الموزعة على الوكيل حاليًا |
| POST | `/api/v1/agent/shipments/{id}/assign-driver` | توزيع شحنة على مندوب داخلي تابع للوكيل |
| GET | `/api/v1/agent/drivers` | قائمة مندوبي الوكيل وأداء كل منهم |
| GET | `/api/v1/agent/cash-in-hand` | العهدة النقدية الحالية بحوزة الوكيل |
| POST | `/api/v1/agent/cash-handovers` | تسجيل توريد نقدية للشركة |
| GET | `/api/v1/agent/settlements` | تسويات/مستحقات الوكيل |

## 6.5 API تطبيق المندوب (Driver PWA API)

| Method | Endpoint | الوصف |
|---|---|---|
| GET | `/api/v1/driver/dashboard` | ملخص اليوم (عدد الشحنات، المسلمة، المتبقي، النقدية بحوزته) |
| GET | `/api/v1/driver/shipments` | شحنات اليوم المخصصة له |
| POST | `/api/v1/driver/shipments/{id}/scan-pickup` | استلام شحنة (Scan QR/Barcode) |
| POST | `/api/v1/driver/shipments/{id}/deliver` | تسليم شحنة (multipart: صورة/توقيع + GPS + ملاحظات) |
| POST | `/api/v1/driver/shipments/{id}/fail-attempt` | تسجيل محاولة فاشلة (سبب + ملاحظة) |
| POST | `/api/v1/driver/shipments/{id}/return` | إرجاع شحنة |
| POST | `/api/v1/driver/cash-handovers` | توريد نقدية لنهاية اليوم للفرع |
| POST | `/api/v1/driver/location` | تحديث موقع GPS الدوري (Live Tracking) |
| GET/POST | `/api/v1/driver/sync-offline` | مزامنة العمليات المُنفذة أثناء انقطاع الإنترنت (Batch) |

> تصميم `sync-offline` ضروري لأن PWA المندوب يجب أن تعمل بدون إنترنت مؤقتًا (مناطق تغطية ضعيفة)، فتُخزَّن العمليات محليًا (IndexedDB) وتُرسَل كدفعة واحدة عند عودة الاتصال، مع معالجة تعارضات محتملة (Conflict Resolution: آخر عملية موثوقة بالـ Timestamp الأصلي وقت التنفيذ الفعلي لا وقت الإرسال).

## 6.6 Webhooks الصادرة (النظام → التاجر/المتجر الإلكتروني)

عند أي تغيير حالة شحنة، إذا كان لدى التاجر Webhook مسجّل:
```json
POST {merchant_webhook_url}
Headers: X-Signature: HMAC-SHA256(payload, webhook_secret)
{
  "event": "shipment.status_changed",
  "tracking_number": "SHP-2026-00931245",
  "reference_number": "ORDER-10293",
  "old_status": "out_for_delivery",
  "new_status": "delivered",
  "amount_collected": 450.00,
  "timestamp": "2026-08-16T14:32:00+02:00"
}
```
- إعادة محاولة تلقائية (Retry with Backoff) حتى 5 مرات عند فشل استقبال Webhook (Job منفصل في الطابور، لا يُنفَّذ Sync داخل نفس الطلب).
- سجل كامل لكل محاولة إرسال Webhook (نجاح/فشل/كود الاستجابة) لأغراض التصحيح.

## 6.7 التكاملات الخارجية الجاهزة (E-commerce Connectors)

| المنصة | آلية الربط |
|---|---|
| **WooCommerce** | Plugin PHP مخصص يُثبَّت على ووردبريس، يستدعي `POST /api/v1/shipments` تلقائيًا عند تغيير حالة الطلب إلى "قيد التجهيز"، ويعرض حالة الشحن داخل صفحة الطلب عبر Webhook الوارد |
| **Shopify** | Shopify App (Private/Custom App) يستخدم Shopify Admin API لسحب الطلبات الجديدة + يستدعي API الشركة لإنشاء الشحنة + يُحدّث Fulfillment Status عبر Webhook |
| **OpenCart** | Extension/OCMOD يربط بنفس API العام |
| **Magento** | Module (Composer package) يربط بنفس API العام عبر Magento Webhooks (Order Placed → Create Shipment) |

> **مبدأ التصميم**: كل الموصلات (Connectors) الأربعة تستهلك نفس `API v1` العام دون أي منطق خاص من جهة السيرفر — الفرق فقط في كود العميل (Plugin/App) الخاص بكل منصة. هذا يضمن أن أي منصة تجارة إلكترونية جديدة تُضاف مستقبلًا (Salla, Zid, إلخ الشائعة في المنطقة العربية) تحتاج فقط لموصل عميل جديد دون تعديل الـ Backend.

## 6.8 أمان الـ API

- HTTPS إجباري على كل Endpoint (رفض أي طلب HTTP عادي).
- Token لكل تاجر/وكيل قابل للإلغاء الفوري من لوحة التحكم (Revoke) عند الاشتباه بتسريب.
- تسجيل كل استدعاء API حساس (إنشاء/حذف/تعديل مالي) في `audit_log`.
- التحقق من ملكية المورد (Authorization) على مستوى Policy في Laravel لكل Endpoint — تاجر لا يمكنه أبدًا الوصول لشحنة تاجر آخر حتى لو خمّن الـ tracking_number.
- CORS مضبوط بدقة (فقط النطاقات المسجّلة لكل تاجر عند استخدام API من متصفح مباشرة كـ Widget تتبع).
