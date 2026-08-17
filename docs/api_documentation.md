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

### 1. تسجيل مستخدم جديد (Register)
- **المسار**: `POST /api/v1/auth/register`
- **الجسم (Body)**:
```json
{
    "name": "فارس البطل",
    "email": "faris@example.com",
    "password": "password123"
}
```
- **الاستجابة (201 Created)**:
```json
{
    "success": true,
    "message": "تم تسجيل الحساب بنجاح",
    "data": {
        "user": {
            "id": "9d8b3c2e-...",
            "name": "فارس البطل",
            "email": "faris@example.com",
            "role": "USER"
        },
        "token": "1|sanctum_plain_text_token...",
        "token_type": "Bearer"
    }
}
```

### 2. تسجيل الدخول (Login)
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

### 3. الملف الشخصي الحالي (Current User)
- **المسار**: `GET /api/v1/auth/me` (Auth: Sanctum)

### 4. تسجيل الخروج (Logout)
- **المسار**: `POST /api/v1/auth/logout` (Auth: Sanctum)

---

## 3. تطبيق المستخدم (Mobile App: `/api/v1/app`)

المسارات تتطلب توكن مستخدم بحساب `USER`:

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
            "total_sessions": 5,
            "total_games_completed": 3,
            "average_accuracy": 88.5
        },
        "continue_session": null
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

### 5. بدء جلسة لعب (Start Game Session)
- **المسار**: `POST /api/v1/app/games/{game_id}/sessions`
- **الجسم (Body)**:
```json
{
    "curriculum_day_id": "...",
    "metadata": { "device": "iPad Pro" }
}
```
- **الاستجابة (201 Created)**:
```json
{
    "success": true,
    "data": {
        "id": "...",
        "status": "STARTED",
        "started_at": "2026-08-17T..."
    }
}
```

### 6. إنهاء جلسة اللعب وإرسال القياسات (Complete Game Session)
- **المسار**: `POST /api/v1/app/sessions/{session_id}/complete`
- **الجسم (Body)**:
```json
{
    "attempts": 10,
    "correct_attempts": 9,
    "incorrect_attempts": 1,
    "duration_seconds": 45,
    "score": 90,
    "metadata": { "eye_tracking_events": 12 }
}
```
- **الاستجابة (200 OK)**:
```json
{
    "success": true,
    "message": "تم إنهاء الجلسة واحتساب النتيجة بنجاح",
    "data": {
        "id": "...",
        "attempts": 10,
        "correct_attempts": 9,
        "accuracy": 90.0,
        "score": 90,
        "status": "COMPLETED"
    }
}
```

### 7. تقارير التقدم (Progress Reports)
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
