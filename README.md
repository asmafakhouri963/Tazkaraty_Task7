# منصة تذكرتي (Tazkaraty API) - نظام حجز وتذاكر فعاليات عالي الأداء

نظام معالجة وتذاكر فعاليات مبني باستخدام Laravel 11 وفق معمارية Clean Architecture و Event-Driven Architecture لتحمل ضغط الطلبات العالي (High Concurrency) في لحظات إتاحة بيع التذاكر، مع فصل كامل للمهام الثقيلة في الخلفية باستخدام Laravel Horizon و Redis.

## المعمارية الهندسية للنظام (Architecture Overview)

يعتمد النظام على مبدأ عدم تأخير المستخدم (Zero Blocking API Response). بمجرد إتمام الشراء أو الإلغاء، يتلقى المشتري الاستجابة فوراً، بينما تنتقل باقي العمليات (توليد ملف التذكرة، الإشعار البريدي، الرسائل النصية، تحديث التقارير، والإعلانات) إلى الطوابير في الخلفية عبر Events & Listeners.

### المكونات والخصائص المضافة:
- **تسجيل الأحداث والـ Logging**: استخدام `Loggable` Trait لتدوين كافة الأحداث المهمة والأخطاء في ملفات الـ Log آلياً وبصيغة معيارية.
- **مسارات API الموحدة**: تنظيم جميع العمليات تحت الإصدار الأول `api/v1/` لتسهيل التطوير والتكامل مع التطبيقات.

## الهيكل الأساسي لقاعدة البيانات (Database Schema)

يتكون النظام من ثلاثة جداول رئيسية مرتبطة بعلاقات معيارية:

1. **جدول الفعاليات (`events`)**:
   - `id`: المعرف الرئيسي.
   - `organizer_id`: معرف المنظم (مرتبط بجدول `users`).
   - `title`, `description`, `event_date`.
   - `status`: حالة الفعالية (Enum: `published`, `cancelled`, `completed`).

2. **جدول أنواع التذاكر (`ticket_types`)**:
   - `id`: المعرف الرئيسي.
   - `event_id`: معرف الفعالية.
   - `name`: اسم التذكرة (مثل VIP، Regular).
   - `price`: سعر التذكرة.
   - `total_quantity` & `available_quantity`: الكمية الكلية والكمية المتاحة.

3. **جدول الحجوزات (`bookings`)**:
   - `id`: المعرف الرئيسي.
   - `user_id`: معرف المشتري.
   - `event_id`: معرف الفعالية.
   - `ticket_type_id`: نوع التذكرة.
   - `quantity` & `total_price`: العدد والسعر الإجمالي.
   - `status`: حالة الحجز (Enum: `pending`, `confirmed`, `cancelled`, `expired`).

## مسارات النظام (API V1 Routes)

جميع المسارات محمية عبر حزمة `auth:sanctum` وتقع تحت الـ Prefix الرسمي `v1`:

```
POST /api/v1/bookings        # إنشاء حجز مؤقت وتحديد المقاعد
POST /api/v1/bookings/{id}/pay # تأكيد الدفع وإطلاق أحداث الشراء
POST /api/v1/events/{id}/cancel # إلغاء الفعالية وإطلاق أحداث التعويض والإشعارات
```

## جدول المهام الأولية وطوابير المعالجة (Horizon Queues & Retry Policy)

| اسم الـ Job | الـ Queue | عدد المحاولات (Tries) | وقت الانتظار (Backoff) | السبب والمنطق خلف وضعها في الخلفية | 
| ----- | ----- | ----- | ----- | ----- | 
| GenerateTicketPdfJob | high | 3 | 10 ثوانٍ | توليد الملف وتجهيز رمز التذكرة يتطلب وقتاً؛ وضعه بالخلفية يمنح المشتري استجابة فورية دون الانتظار. | 
| SendTicketEmailJob | high | 5 | 30 ثانية | مزود البريد قد يتعطل مؤقتاً، السياسة تضمن إعادة المحاولة الذكية وتجنب استثناء 500 للمشتري. | 
| SendTicketSmsJob | default | 3 | 20 ثانية | الرسائل النصية مهمة ولكنها تأتي بأولوية ثانوية بعد تأكيد البريد الإلكتروني المباشر. | 
| NotifyOrganizerJob | low | 3 | 60 ثانية | إبلاغ المنظم بعملية بيع جديدة عملية إدارية لا يجب أن تعطل مسار الشراء المباشر. | 
| UpdateSalesAnalyticsJob | low | 3 | 60 ثانية | تحديث مجاميع التقارير ولوحات التحكم يتم في الخلفية بعيداً عن المعاملات المالية الفورية. | 
| SendBulkAnnouncementJob | bulk | 5 | 120 ثانية | إرسال آلاف الرسائل للمشتركين يتم بتقسيم الذاكرة (Chunking) وفي طابور منفصل كي لا يعطل إيميلات الشراء الفورية. | 

## إجراءات رفع الكود الجديد (Deployment & Horizon Refresh)

عند رفع تحديثات برمجية جديدة إلى السيرفر، تظل الـ Workers القديمة في Laravel Horizon تشغل الكود المخزّن في الذاكرة (RAM). لضمان تطبيق الكود الجديد فورياً ودون فقدان أي مهمة معلقة:

```bash
# 1. سحب التعديلات البرمجية الجديدة
git pull origin main

# 2. تحديث الحزم والتهيئة
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache

# 3. إعطاء أمر إنهاء آمن لـ Horizon ليعيد تحميل الكود الجديد تلقائياً
php artisan horizon:terminate
```

## الحجز المؤقت (Temporary Booking TTL)

لا يتم استخدام أي Cron Job أو فحص دوري لقاعدة البيانات لتحرير التذاكر المنتهية.

* يتم إنشاء الحجز بحالة PENDING.
* يُسجل مفتاح مؤقت في Redis TTL مدته 10 دقائق.
* عند محاولة الدفع، يتم التحقق من وجود المفتاح في Redis. وفي حال انقضاء المدة، يُلغى الحجز وتُعاد المقاعد تلقائياً للفعالية دون إجهاد السيرفر باستعلامات دورية.

## الأحداث والمستجيبات (Event-Driven Cancellation)

عند إلغاء الفعالية (cancel)، لا يحتوي الـ Controller على أي منطق معقد (Thin Controller):

1. يتم إطلاق الحدث EventCancelledEvent.
2. يستجيب لهذا الحدث مجموعة من الـ Listeners المستقلة (RefundTicketHoldersListener, SendCancellationsNotificationsListener, إلخ).
3. يمكن إضافة أي قناة إشعارات جديدة (مثل WhatsApp) مستقبلاً ببساطة عبر إضافة Listener جديد دون تعديل الكود الحالي إطلاقاً.

## تشغيل المشروع محلياً

```bash
# 1. تثبيت الاعتمادات
composer install

# 2. إعداد ملف البيئة
cp .env.example .env
php artisan key:generate

# 3. تشغيل الـ Migrations والـ Seeders
php artisan migrate --seed

# 4. تشغيل Laravel Horizon لمراقبة الطوابير
php artisan horizon
```