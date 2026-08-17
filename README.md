# منصة رحلة فارس التدريبية للأطفال - Backend API

> **Professional, Scalable, Production-Ready Laravel 13 API Backend**

نظام Backend متكامل ومخصص لمنصة التدريب التفاعلي للأطفال (**رحلة فارس**)، مصمم وفق مبادئ **Clean Architecture** و **Domain-Driven Design** و **Modular Pattern**.

---

## ✨ المميزات المعمارية للنظام

- **واجهة برمجية نقية (Pure RESTful API)**: لا يوجد أي واجهات Frontend أو Blade أو Livewire لضمان الفصل التام وتغذية كل من لوحة التحكم (Admin Dashboard) وتطبيق الجوال (Mobile App).
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
- **قاعدة البيانات**: `MySQL 8+` أو `SQLite`
- **Laravel Framework**: `^12.0` / `^13.0`

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

### 3. تهيئة قاعدة البيانات والتعبئة بالبيانات التجريبية
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

| نوع الحساب | البريد الإلكتروني | كلمة المرور | الصلاحية |
|---|---|---|---|
| **مدير النظام (Admin)** | `admin@demo.com` | `admin123456` | `ADMIN` |
| **مستخدم التطبيق (User)** | `user@demo.com` | `user123456` | `USER` |

### أكواد التفعيل التجريبية المتاحة:
- `DEMO-2026-RIHL-0001`
- `DEMO-2026-RIHL-0002`
- `DEMO-2026-RIHL-0003`

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

---

## 📖 توثيق الـ API

يمكنك مراجعة الدليل الكامل لكافة الـ Endpoints والمخططات في:
👉 [docs/api_documentation.md](file:///c:/xampp_new/htdocs/Rihla%20faris/docs/api_documentation.md)
