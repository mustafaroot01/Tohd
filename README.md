# منصة رحلة فارس التدريبية للأطفال - Backend API

> **Laravel 12 API Backend + لوحة تحكم Vue 3 (Vuetify)**

نظام Backend متكامل ومخصص لمنصة التدريب التفاعلي للأطفال (**رحلة فارس**)، مصمم وفق مبادئ **Clean Architecture** و **Domain-Driven Design** و **Modular Pattern**.

---

## ✨ المميزات المعمارية للنظام

- **واجهة برمجية موحّدة (RESTful API)** تحت `/api/v1` تغذّي لوحة التحكم وتطبيق الجوال معاً، مع لوحة تحكم Vue 3 (Vuetify) تُقدَّم من نفس التطبيق عبر `routes/web.php`.
- **مصادقة آمنة عبر Laravel Sanctum**: إدارة جلسات التوكن عبر API Tokens وحماية المسارات حسب الصلاحيات (`ADMIN` / `USER`).
- **معالجة متقدمة لملفات Lottie JSON**: التحقق الأمني والتركيبي لملفات Lottie وحفظها في التخزين السحابي/المحلي مع الحفاظ على بصمة الـ Checksum والـ Metadata.
- **منشئ المناهج التدريبية (Curriculum Builder)**: هيكل علائقي مرن (Curriculum -> Months -> Weeks -> Days -> Games) مع التحقق الصارم قبل النشر (Publishing Lifecycle).
- **نظام تفعيل فوري ومحمي من الـ Race Conditions**: استخدام `DB Transactions` وقفل السجلات `SELECT ... FOR UPDATE` عند استهلاك أكواد التفعيل لضمان الأمان والموثوقية.
- **تتبع جلسات اللعب والقياسات (Game Telemetry & Sessions)**: استقبال محاولات الطفل، النتيجة، والدقة واحتساب مؤشرات الأداء اللحظية عبر Events & Listeners.
- **تقارير أداء متقدمة (Progress Reports)**: ملخصات إجمالية ويومية وأسبوعية وشهرية وتفصيلية حسب المحاور التدريبية والمهارات (غير موجهة للتشخيص الطبي).
- **استجابات موحدة (Consistent API Response Format)**: هيكل موحد لكافة العمليات الناجحة وأخطاء الـ Validation والـ Exceptions.

---

## 🛠️ المتطلبات التقنية

- **PHP**: `^8.2` أو `^8.3+`
- **Composer**: `^2.2+`
- **قاعدة البيانات**: `MySQL 8+` (أو `PostgreSQL`) — **في التطوير والإنتاج معاً**.
  - حماية الاستخدام المزدوج لأكواد التفعيل تعتمد على `lockForUpdate`، و**SQLite تُصرّفها إلى لا شيء** — أي أن السيريال الواحد يمكن أن يُفعَّل مرتين تحت التزامن.
  - لهذا السبب لم تعد SQLite خياراً مقبولاً حتى محلياً، والاختبارات نفسها تعمل على MySQL (`rihla_faris_testing`) حتى تطابق الإنتاج.
  - يحرس هذا القرارَ اختبارٌ صريح: `tests/Feature/ActivationConcurrencyTest.php` يفشل فوراً إذا أُعيد توجيه المشروع إلى محرّك بلا قفل صفوف.
- **Laravel Framework**: `^12.0` (المثبّت حالياً: 12.36.1)

---

## 🚀 التثبيت والتشغيل المحلي

### 1. تثبيت الحزم
```bash
composer install
```

### 2. إعداد ملف البيئة
```bash
cp .env.example .env
php artisan key:generate
```
لتفعيل إرسال رمز OTP الفعلي لأرقام هواتف المشتركين، أضف مفتاح [OTPIQ](https://docs.otpiq.com) الخاص بك في `.env`:
```
OTPIQ_API_KEY=sk_live_your_api_key_here
```

### 3. تهيئة قاعدة البيانات والتعبئة بالبيانات التجريبية
أنشئ قاعدتين في MySQL — واحدة للتطوير وأخرى للاختبارات:
```bash
mysql -uroot -e "CREATE DATABASE rihla_faris CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -uroot -e "CREATE DATABASE rihla_faris_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```
ثم اضبط `DB_*` في `.env` وشغّل:
```bash
php artisan migrate:fresh --seed
```

### 4. تشغيل الخادم
```bash
php artisan serve
```
سيكون الخادم متاحاً على: `http://127.0.0.1:8000`

---

## 🔑 الحسابات والبيانات التجريبية (Demo Data)

| نوع الحساب | بيانات الدخول | كلمة المرور |
|---|---|---|
| **مدير النظام (Admin)** | البريد: `admin@demo.com` | `admin123456` |
| **مشترك تجريبي (Subscriber)** | رقم الهاتف: `07701234567` (يُخزَّن كـ `+9647701234567`) | `user123456` |

مستخدمو النظام (المدراء) يسجّلون الدخول بالبريد الإلكتروني عبر `/api/v1/auth/login`، بينما المشتركون (مستخدمو التطبيق) يسجّلون فقط برقم الهاتف عبر `/api/v1/app/auth/login` — لا يوجد بريد إلكتروني في نظام المشتركين. المشترك التجريبي أعلاه مُنشأ بحالة "فعال" مسبقاً (هاتفه موثّق) لتفادي المرور بخطوة OTP الفعلية عند التجربة المحلية.

### أكواد التفعيل التجريبية المتاحة:
- `DEMO-2026-RIHL-0001` … `DEMO-2026-RIHL-0005` (خمسة أكواد)

---

## 🧪 الاختبارات الآلية (Automated Tests)

لتشغيل الاختبارات الشاملة (Unit Tests + Feature Tests + UserTrainingFlow Integration Test):
```bash
php artisan test
```

---

## ⚡ الأوامر المخصصة (Artisan Commands)

- **التحقق من صحة الألعاب المنشورة**:
  ```bash
  php artisan app:validate-games
  ```
- **إعادة احتساب وتحديث سجلات التقدم**:
  ```bash
  php artisan app:recalculate-progress
  ```
- **تحويل الاشتراكات المنتهية الصلاحية تلقائياً**:
  ```bash
  php artisan app:expire-subscriptions
  ```
- **حذف رموز OTP القديمة (منتهية أو مُستهلكة)**:
  ```bash
  php artisan app:cleanup-expired-otps
  ```

كلا الأمرين مجدولان تلقائياً يومياً عبر Laravel Scheduler (`bootstrap/app.php`) — يكفي فقط تشغيل مجدول واحد قياسي على الخادم:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📖 توثيق الـ API

يمكنك مراجعة الدليل الكامل لكافة الـ Endpoints والمخططات في:
👉 [docs/api_documentation.md](docs/api_documentation.md)
