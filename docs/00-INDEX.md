# منصة إدارة شركات الشحن المصرية — وثيقة التحليل والتصميم الشاملة

**اسم المشروع المقترح:** ShipEgy Platform (قابل للتغيير)
**النطاق:** نظام Web متكامل (Laravel 12) قابل للتشغيل مباشرة على استضافة cPanel مشتركة/VPS، مع مسار تطور لاحق نحو PWA ثم تطبيق Flutter للمندوبين.
**تاريخ الإصدار:** أغسطس 2026
**الإصدار:** 1.0

---

## فهرس الوثائق

هذه الوثيقة مقسّمة إلى ملفات منفصلة لسهولة المراجعة والتحديث المستقل لكل جزء:

| # | الملف | المحتوى |
|---|-------|---------|
| 1 | [01-BRD.md](./01-BRD.md) | وثيقة متطلبات الأعمال (Business Requirements Document) |
| 2 | [02-SRS.md](./02-SRS.md) | وثيقة متطلبات النظام التفصيلية (Software Requirements Specification) |
| 3 | [03-Database-Design-ERD.md](./03-Database-Design-ERD.md) | تصميم قاعدة البيانات، ERD، الجداول، العلاقات، الفهارس، الأرشفة، النسخ الاحتياطي |
| 4 | [04-Permissions-Matrix.md](./04-Permissions-Matrix.md) | نظام الأدوار والصلاحيات RBAC ومصفوفة الصلاحيات التفصيلية |
| 5 | [05-Workflows.md](./05-Workflows.md) | دورة حياة الشحنة، حالة العهدة، التسويات، الشكاوى — بمخططات Mermaid |
| 6 | [06-API-Design.md](./06-API-Design.md) | تصميم REST API الكامل (Web, Merchant, Agent, Driver App, Webhooks, التكاملات الخارجية) |
| 7 | [07-UI-UX-Wireframes.md](./07-UI-UX-Wireframes.md) | واجهات المستخدم لكل بورتال (Admin/Merchant/Agent/Branch/Driver) بشكل Wireframes نصية |
| 8 | [08-System-Architecture-Security-Deployment.md](./08-System-Architecture-Security-Deployment.md) | معمارية النظام، الأمان، النشر على cPanel، التوسع، المراقبة والـ Logging |
| 9 | [09-Roadmap-MVP-Team-Cost.md](./09-Roadmap-MVP-Team-Cost.md) | خارطة الطريق، نطاق MVP، النطاق المستقبلي، هيكل الفريق، الجدول الزمني، التكلفة التقديرية |

---

## ملخص تنفيذي سريع

- **الهدف:** بناء منصة SaaS داخلية لشركة شحن مصرية (على غرار Bosta / Mylerz / Sprint) تُدير التجار، الشحنات، الفروع، المندوبين، الوكلاء، التحصيلات، العُهد النقدية، التسويات، المرتجعات، والتقارير.
- **القرار التقني الحاسم:** بما أن الاستضافة المستهدفة هي **cPanel**، تم تصميم كل الوثائق التالية على أساس:
  - عدم الاعتماد الإجباري على Docker/Kubernetes.
  - استخدام **Laravel Queue عبر Cron (`schedule:run` + `queue:work` بواسطة Supervisor إن توفر، أو `queue:work --stop-when-empty` عبر Cron كل دقيقة كبديل عملي على cPanel المشترك)**.
  - Redis **اختياري** (يُستخدم فقط إذا كانت الاستضافة VPS/Cloud تدعمه؛ في حالة cPanel المشترك البسيط يتم الاعتماد على **Database Cache/Queue Driver** كبديل متوافق دون فقدان الوظائف).
  - MySQL 8 (متوفر بشكل قياسي في كل استضافات cPanel).
  - الواجهة الأمامية: **Blade + Bootstrap 5 + Alpine.js/Vue 3 (عبر CDN أو Vite build يُرفع كملفات static)** — لا حاجة لسيرفر Node.js منفصل وقت التشغيل.
  - الموبايل: **يبدأ المشروع بدون تطبيق موبايل**؛ مندوب التوصيل يستخدم **PWA** (تطبيق ويب تقدمي يعمل من المتصفح ويدعم العمل شبه Offline + كاميرا + GPS)، ثم يتم التحول لاحقًا إلى Flutter عند الحاجة الفعلية للنشر على المتاجر (Google Play / App Store).
- **نطاق MVP:** تفاصيل كاملة في [09-Roadmap-MVP-Team-Cost.md](./09-Roadmap-MVP-Team-Cost.md#نطاق-mvp).

> ملاحظة: جميع الوثائق مكتوبة بصيغة Markdown نظيفة قابلة للتحويل مباشرة إلى PDF/Word، وتحتوي على مخططات Mermaid تُعرض تلقائيًا على GitHub.
