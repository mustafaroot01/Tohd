# منصة رحلة فارس - توثيق واجهة برمجة التطبيقات (API Documentation V1)

توثيق كامل وشامل لجميع مسارات الـ Backend في منصة **رحلة فارس (Rihla Faris)** المبنية بواسطة Laravel 13 API.

---

## 1. المعايير العامة (General Standards)

### الرابط الأساسي (Base URL)
```text
http://127.0.0.1:8000/api/v1
```

### الترويسات المطلوبة (Headers)
- `Accept: application/json`
- `Content-Type: application/json`
- `Authorization: Bearer <TOKEN>` (للمسارات المحمية)

### تنسيق الاستجابة الناجحة (Standard Success Response)
```json
{
    "success": true,
    "message": "تمت العملية بنجاح",
    "data": {},
    "meta": {}
}
```

### تنسيق استجابة الخطأ (Standard Error Response)
```json
{
    "success": false,
    "message": "نص رسالة الخطأ التوضيحية",
    "error_code": "ERROR_IDENTIFIER",
    "errors": null
}
```

---

## 2. المصادقة والتحقق (Authentication: `/api/v1/auth`)

> لا يوجد تسجيل ذاتي لحسابات لوحة التحكم؛ يُنشئها مدير النظام. تسجيل المشتركين (أهل الأطفال) في القسم 3 — البند 0.

### 1. تسجيل الدخول (Login)
- **المسار**: `POST /api/v1/auth/login`
- **الجسم (Body)**:
```json
{
    "email": "admin@demo.com",
    "password": "admin123456",
    "device_name": "dashboard_web"
}
```
- **الاستجابة (200 OK)**:
```json
{
    "success": true,
    "message": "تم تسجيل الدخول بنجاح",
    "data": {
        "user": {
            "id": "...",
            "name": "مدير النظام",
            "role": "ADMIN"
        },
        "token": "2|...",
        "token_type": "Bearer"
    }
}
```

### 2. الملف الشخصي الحالي (Current User)
- **المسار**: `GET /api/v1/auth/me` (Auth: Sanctum)

### 3. تسجيل الخروج (Logout)
- **المسار**: `POST /api/v1/auth/logout` (Auth: Sanctum)

---

## 3. تطبيق المستخدم (Mobile App: `/api/v1/app`)

المسارات تتطلب توكن مستخدم بحساب `USER`:

### 0. الحساب — التسجيل بواتساب OTP، الدخول، استعادة كلمة المرور (`/api/v1/app/auth`)

رمز واتساب يُرسَل في **حالتين فقط**: لإثبات الرقم مرة واحدة عند **التسجيل**، ولإثباته قبل **تغيير كلمة المرور**. **تسجيل الدخول لا يُرسل أي رمز** — رقم + كلمة مرور ← توكن.

> **الحساب لا يُنشأ إلا بعد نجاح الرمز.** التسجيل يحفظ البيانات مؤقتاً (١٠ دقائق) ويرسل الرمز؛ لحظة إدخال الرمز الصحيح **يُولَد الحساب مفعّلاً ويدخل التطبيق فوراً بتوكن**. لا توجد حالة «بانتظار التوثيق».

```
إنشاء حساب:   POST register ──رمز واتساب──▶ POST otp/verify ──▶ حساب + توكن (يدخل مباشرة)
كل دخول:      POST login (رقم + كلمة مرور) ──▶ توكن
نسيت كلمتي:   POST password/forgot ──رمز──▶ POST password/reset ──▶ سجّل الدخول بالجديدة
```

رقم الهاتف يُقبل بصيغ `07701234567` · `+9647701234567` · `9647701234567` ويُخزَّن ويُرجَع دائماً `+9647701234567`. الرمز **٦ أرقام**. في بيئة التطوير (`OTP_FAKE=true`) لا يُرسل شيء والرمز `123456`.

#### `POST /api/v1/app/auth/register` — يحفظ البيانات ويرسل الرمز (لا حساب بعد)
```json
{ "name": "فارس الصغير", "phone": "07701234567", "password": "secret123456", "password_confirmation": "secret123456", "address": "بغداد" }
```
`201`:
```json
{ "success": true, "message": "أرسلنا رمز التحقق إلى واتساب",
  "data": { "phone": "+9647701234567", "signup_token": "aZ8…", "resend_in": 60 } }
```
`422` رقم له حساب: `errors.phone[0] = "رقم الهاتف مسجّل بالفعل، سجّل الدخول أو استعد كلمة المرور"` · `422` حقول: `errors.{name|phone|password}` · `422` قبل مرور ٦٠ ثانية على آخر رمز: `error_code = OTP_COOLDOWN` و`errors.otp = ["COOLDOWN"]` (استخدم `resend_in` من الرد السابق كعدّاد بدل إعادة المحاولة) · `503` خدمة الرسائل متعطّلة: `error_code = OTP_SERVICE_UNAVAILABLE` (اعرض `message` كـ«حاول لاحقاً»).

> ابدأ عدّاداً تنازلياً بـ`resend_in` ثانية قبل إظهار زر «إعادة الإرسال».

#### `POST /api/v1/app/auth/otp/verify` — يُنشئ الحساب ويدخل
```json
{ "phone": "07701234567", "code": "123456", "signup_token": "aZ8…" }
```
> **`signup_token` مطلوب**: احفظه من رد `register` وأرسله هنا. هو ما يمنع شخصاً سجّل برقمك من إكمال الحساب بكلمة سرّه لمن تُدخل أنت الرمز الواصل لهاتفك. إن لم يطابق فالرد `422` «انتهت جلسة التسجيل» — أعد التسجيل.
`201`:
```json
{ "success": true, "message": "تم إنشاء حسابك بنجاح", "data": { "user": { "id": "…", "name": "فارس الصغير", "phone": "+9647701234567", "status": "ACTIVE" }, "token": "12|…", "token_type": "Bearer" } }
```
`422` رمز خاطئ: `error_code = OTP_INVALID_CODE`, `errors.otp = ["INVALID_CODE"]` · `422` منتهٍ: `errors.otp = ["EXPIRED_CODE"]` (فعّل «إعادة الإرسال») · `422` انتهت جلسة التسجيل (١٠ دقائق): `errors.phone[0] = "انتهت جلسة التسجيل، أعد إدخال بياناتك"` (أعِده لشاشة التسجيل) · `429` محاولات كثيرة.

#### `POST /api/v1/app/auth/otp/resend` — «لم يصلني الرمز» (لشاشتَي التسجيل والاستعادة)
`{ "phone": "07701234567" }` → `200 { "data": { "phone", "purpose": "register" | "reset", "resend_in": 60 } }` · `422 errors.otp = ["COOLDOWN"]` قبل مرور ٦٠ ثانية · `422 errors.phone` لرقم بلا حساب ولا تسجيل جارٍ.

#### `POST /api/v1/app/auth/login` — بلا رمز
`{ "phone": "07701234567", "password": "secret123456", "device_name": "iphone" }` → `200 { "data": { "user", "token", "token_type" } }` · `401 INVALID_CREDENTIALS` · `403 ACCOUNT_SUSPENDED`.

#### `POST /api/v1/app/auth/password/forgot` → `POST /api/v1/app/auth/password/reset`
`forgot`: `{ "phone" }` → `200 { "data": { "phone", "resend_in" } }` (`403 ACCOUNT_SUSPENDED` · `422 errors.phone` لرقم بلا حساب · `422 OTP_COOLDOWN` قبل مرور ٦٠ ثانية). حدود الاستعادة منفصلة عن حدود التسجيل، فلا يستطيع أحد استهلاك رصيدك.
`reset`: `{ "phone", "code", "password", "password_confirmation" }` → `200` — **كل الجلسات القديمة تنتهي**، سجّل الدخول بالكلمة الجديدة.

#### الملف الشخصي 🔒
`PUT /api/v1/app/profile` يقبل `name` و`address` و`password` (مع `current_password` و`password_confirmation`). **رقم الهاتف غير قابل للتعديل من التطبيق** — هو هوية الحساب وأُثبت مرة عند التسجيل؛ يغيّره المشرف من لوحة التحكم عند الحاجة.

**الحدود:** إرسال الرموز ٤ مرات لكل رقم كل ١٠ دقائق (`register`, `otp/resend`, `password/forgot`)، والتحقق ٨ محاولات لكل رقم كل ١٠ دقائق (`otp/verify`, `password/reset`) — يرجع `429 TOO_MANY_REQUESTS`. الحدود **على رقم الهاتف فقط** ولا تعتمد على عنوان الجهاز، لأن عوائل كثيرة في العراق تشترك بعنوان واحد. وحدود الاستعادة منفصلة عن حدود التسجيل.

### 0-ب. إكمال بيانات المشترك (خطوة ثانية اختيارية) 🔒

خطوة تُشغَّل وتُطفأ من لوحة التحكم (**إعدادات النظام ← إكمال بيانات المشترك**).

> **وهي مطفأة، الميزة غير موجودة من ناحية الـAPI**: لا يظهر لها أي مفتاح في أي رد، ومساراها يرجعان `404`. وهي مشتغلة تصير **إلزامية**: كل مسارات التدريب والاشتراك ترجع `403 PROFILE_INCOMPLETE` حتى تُكمَل.

`GET /app/home` و`GET /app/profile` يحملان — **فقط عند التشغيل**:
```json
"profile_completion": { "is_complete": false,
  "missing_fields": ["governorate_id","gender","age","family_order","delivery_type"] }
```
فيعرض التطبيق شاشة «أكمل بياناتك» أول ما يفتح، ويمنع المتابعة قبلها.

#### `GET /api/v1/app/governorates` 🔒
المحافظات الظاهرة مرتّبة: `[{ "id": "…", "name": "بغداد" }, …]` (18 محافظة افتراضياً، يديرها المشرف).

#### `POST /api/v1/app/profile/details` 🔒
```json
{ "governorate_id": "…", "gender": "MALE", "age": 6, "family_order": 2, "delivery_type": "CESAREAN" }
```

| الحقل | القيد |
|---|---|
| `governorate_id` | مطلوب · من `GET /app/governorates` (المخفية تُرفض) |
| `gender` | مطلوب · `MALE` أو `FEMALE` |
| `age` | مطلوب · عدد صحيح من 1 إلى 18 |
| `family_order` | مطلوب · عدد صحيح من 1 إلى 20 |
| `delivery_type` | مطلوب · `NATURAL` أو `CESAREAN` |

`200` يرجع الملف الشخصي كاملاً ومعه `details` و`profile_completion.is_complete = true`، فيتابع المستخدم مباشرة. الإرسال مرة ثانية **يعدّل** البيانات ولا ينشئ صفاً جديداً. `422` توزَّع أخطاؤه على الحقول.

### 1. الصفحة الرئيسية (App Home)
- **المسار**: `GET /api/v1/app/home`
- **الاستجابة (200 OK)**:
```json
{
    "success": true,
    "data": {
        "user": { "id": "...", "name": "فارس" },
        "activation": { "id": "...", "code": "DEMO-2026-RIHL-0001" },
        "assignment": { "starts_at": "2026-08-17T...", "ends_at": "2026-09-16T...", "days_remaining": 30 },
        "today": {
            "day": { "number": 1, "name": "اليوم الأول", "is_completed": false, "completion_rate": 0 },
            "games": [
                {
                    "id": "...",
                    "code": "ATT-001",
                    "name": "صيد النجوم اللامعة",
                    "type": "TAP",
                    "is_completed": false
                }
            ]
        },
        "progress": {
            "total_attempts": 5,
            "games_played": 3,
            "games_passed": 2,
            "games_skipped": 0,
            "attention_seconds": 240,
            "attention_minutes": 4.0,
            "grades": { "graded_attempts": 5, "short_attempts": 1, "average_score": 7.2, "best_score": 10, "passed_attempts": 3, "pass_rate": 60.0, "games_passed": 2, "attention_seconds": 240 },
            "axes_progress": [], "skills_progress": []
        }
    }
}
```

### 2. تفعيل كود الاشتراك (Redeem Activation Code)
- **المسار**: `POST /api/v1/app/activations/redeem`
- **الجسم (Body)**:
```json
{
    "code": "DEMO-2026-RIHL-0001"
}
```
- **الاستجابة (200 OK)**:
```json
{
    "success": true,
    "message": "تم تفعيل كود الاشتراك والمنهج التدريبي بنجاح",
    "data": {
        "starts_at": "2026-08-17T...",
        "ends_at": "2026-09-16T...",
        "curriculum": { "id": "...", "code": "CURR-001", "name": "منهج الانطلاقة والتأسيس" }
    }
}
```

### 3. تدريب اليوم (Today Curriculum Plan)
- **المسار**: `GET /api/v1/app/curriculum/today`

### 4. تفاصيل لعبة قابلة للعب (Playable Game Details)
- **المسار**: `GET /api/v1/app/games/{game_id}`

### 5. بدء محاولة (Start Attempt)
- **المسار**: `POST /api/v1/app/games/{game_id}/attempts`
- **لا يُكتب شيء في القاعدة عند البدء.** يرجّع الخادم رمزًا مختومًا يحمل لحظة البدء بساعة الخادم، ويُقيَّم به عند الإكمال.
- **الجسم (Body)** — اختياري: `curriculum_day_id` اليوم الذي يعرضه التطبيق (يُقبل فقط إذا كان يوم الطفل الحالي ويحتوي اللعبة، وإلا تُسجَّل المحاولة كلعب حر).
- **الاستجابة (201 Created)**:
```json
{
    "success": true,
    "data": {
        "attempt_token": "eyJpdiI6...",
        "started_at": "2026-08-27T13:00:00.000000Z",
        "expires_at": "2026-08-27T13:11:00.000000Z",
        "expires_in_seconds": 660,
        "curriculum_day_id": "...",
        "game": { "id": "...", "code": "ATT-001", "name": "صيد النجوم اللامعة", "required_seconds": 60, "required_score": 8, "min_counted_seconds": 15 },
        "progress": { "status": "NOT_STARTED", "status_label": "لم تبدأ", "score": null, "required_score": 8, "attempts": 0, "failed_attempts": 0, "short_attempts": 0, "can_skip": false, "best_seconds": 0, "required_seconds": 60 }
    }
}
```

### 6. إكمال المحاولة (Complete Attempt)
- **المسار**: `POST /api/v1/app/games/{game_id}/attempts/complete`
- **الجسم (Body)**:
```json
{
    "attempt_token": "eyJpdiI6...",
    "duration_seconds": 49
}
```
- **كيف تُحسب الدرجة (على الخادم فقط)**: الثواني المعتمدة = الأصغر من (ما ادّعاه التطبيق، الوقت الفعلي منذ إصدار الرمز، الوقت منذ آخر نتيجة للطفل، مدة اللعبة). الدرجة = `floor(المعتمدة ÷ المطلوبة × 10)`، والنجاح = الدرجة ≥ `round(معيار اللعبة × 10)`. محاولة أقصر من `min_counted_seconds` تُسجَّل لكنها لا تُعدّ محاولة حقيقية (لا تُحسب فاشلة ولا تفتح التخطّي ولا يمكن أن تنجح).
- **الاستجابة (200 OK)**:
```json
{
    "success": true,
    "message": "أحسنت! اجتزت اللعبة",
    "data": {
        "attempt": {
            "game_id": "...", "curriculum_day_id": "...",
            "started_at": "...", "completed_at": "...",
            "claimed_seconds": 49, "elapsed_seconds": 49, "effective_seconds": 49, "required_seconds": 60,
            "score": 8, "required_score": 8, "is_passed": true, "is_counted": true, "is_replayed": false
        },
        "game": { "status": "PASSED", "status_label": "ناجحة", "score": 8, "required_score": 8, "attempts": 1, "failed_attempts": 0, "short_attempts": 0, "can_skip": false, "best_seconds": 49, "required_seconds": 60 }
    }
}
```
- **إعادة الإرسال**: إرسال نفس الرمز مرة ثانية (انقطاع الشبكة) يُرجع نفس النتيجة مع `is_replayed: true` دون احتساب جديد.
- **أخطاء**: `INVALID_ATTEMPT_TOKEN` (422) رمز تالف أو لطفل/لعبة أخرى · `ATTEMPT_EXPIRED` (410) تجاوز مدة اللعبة + المهلة · `ATTEMPT_ALREADY_USED` (409) رمز أقدم من آخر نتيجة مُحتسبة · `ATTEMPT_LIMIT_REACHED` (429) تجاوز الحد اليومي للعبة · `GAME_NOT_PUBLISHED` (403).

### 6-ب. تخطّي لعبة (Skip Game)
- **المسار**: `POST /api/v1/app/games/{game_id}/skip` — يُقبل بعد 3 محاولات حقيقية فاشلة في اليوم نفسه (`attempts_before_unlock`)، ويُسجَّل كقرار (`SKIPPED`) يفتح اللعبة التالية دون أن يُحسب إنجازًا. تكراره في اليوم نفسه يُرجع نفس التسجيل.
- **الاستجابة (200 OK)**: `{ "game_id", "skipped_at", "failed_attempts", "best_score", "game": { ...progress } }` · خطأ `GAME_SKIP_NOT_ALLOWED` (422).

### 7. تقارير التقدم (Progress Reports)
- `GET /api/v1/app/progress` — الملخّص الإجمالي من لوحة (طفل × لعبة).
- `GET /api/v1/app/progress/daily` و `.../monthly` — مجاميع الصفوف اليومية (طفل × يوم × لعبة) للفترة.
- `GET /api/v1/app/progress/weekly?week=YYYY-MM-DD` — **التقرير الأسبوعي** (السبت → الجمعة بتوقيت بغداد؛ `week` أي تاريخ داخل الأسبوع المطلوب، الافتراضي الأسبوع الحالي): `week`, `previous_week`, `summary`, `previous_summary`, `delta`, `days[7]`, `games[]` (لكل لعبة: أيام اللعب، المحاولات/الفاشلة/القصيرة، أفضل درجة، متوسط أفضل‑اليوم، الانتباه، أيام النجاح/التخطّي، `per_day[7]`، `previous`، `delta`)، و`axes[]`/`skills[]`.
- للمشرف: `GET /api/v1/admin/subscribers/{id}/weekly?week=YYYY-MM-DD` بنفس الشكل.
- `GET /api/v1/app/progress` (الإجمالي مع تفصيل المحاور والمهارات)
- `GET /api/v1/app/progress/daily` (تقرير اليوم)
- `GET /api/v1/app/progress/weekly` (تقرير الأسبوع)
- `GET /api/v1/app/progress/monthly` (تقرير الشهر)

---

## 4. لوحة تحكم الإدارة (Admin Dashboard: `/api/v1/admin`)

تتطلب توكن مستخدم بصلاحية `ADMIN`:

### 1. إحصائيات لوحة التحكم
- `GET /api/v1/admin/dashboard`

### 2. إدارة المحاور والمهارات
- `GET|POST /api/v1/admin/axes`
- `GET|PUT|DELETE /api/v1/admin/axes/{axis_id}`
- `GET|POST /api/v1/admin/skills`
- `GET|PUT|DELETE /api/v1/admin/skills/{skill_id}`

### 3. رفع وإدارة وسائط Lottie
- `POST /api/v1/admin/assets` (Multipart Form Data مع ملف `application/json`)
- `GET /api/v1/admin/assets`
- `DELETE /api/v1/admin/assets/{asset_id}`

### 4. إدارة الألعاب ودورة النشر (Game Lifecycle)
- `GET|POST /api/v1/admin/games`
- `GET|PUT|DELETE /api/v1/admin/games/{game_id}`
- `POST /api/v1/admin/games/{game_id}/validate`
- `POST /api/v1/admin/games/{game_id}/approve`
- `POST /api/v1/admin/games/{game_id}/publish`
- `POST /api/v1/admin/games/{game_id}/archive`

### 5. منشئ المناهج التدريبية (Curriculum Builder)
- `GET|POST /api/v1/admin/curriculums`
- `GET|PUT|DELETE /api/v1/admin/curriculums/{curriculum_id}`
- `POST /api/v1/admin/curriculums/{curriculum_id}/publish`
- `POST /api/v1/admin/curriculums/{curriculum_id}/months`
- `POST /api/v1/admin/curriculums/months/{month_id}/weeks`
- `POST /api/v1/admin/curriculums/weeks/{week_id}/days`
- `POST /api/v1/admin/curriculums/days/{day_id}/games`
- `DELETE /api/v1/admin/curriculums/days/{day_id}/games/{game_id}`

### 6. إدارة الباقات والمنتجات
- `GET|POST /api/v1/admin/products`
- `GET|PUT|DELETE /api/v1/admin/products/{product_id}`
- `POST /api/v1/admin/products/{product_id}/activate`
- `POST /api/v1/admin/products/{product_id}/deactivate`

### 7. توليد وإلغاء أكواد التفعيل
- `GET /api/v1/admin/activation-codes`
- `POST /api/v1/admin/activation-codes/generate` (مع تحديد `product_id` و `quantity`)
- `POST /api/v1/admin/activation-codes/{id}/revoke`

### 8. استعراض المستخدمين
- `GET /api/v1/admin/users`
- `GET /api/v1/admin/users/{user_id}`
