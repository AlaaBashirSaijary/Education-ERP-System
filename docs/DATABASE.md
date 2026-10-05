# قاعدة البيانات

23 جدولاً: 13 جدولاً للمدرسة، وجدول `users`، و9 جداول يضيفها Laravel (الجلسات، الطوابير، الكاش، التوكنات، سجل الـ migrations).
تُبنى كلها من ملفات `database/migrations/` بأمر واحد: `php artisan migrate`. اختُبر التطبيق على SQLite وPostgreSQL 16.

```mermaid
erDiagram
    users ||--o{ students : "parent_id"
    school_classes ||--o{ students : "class"
    students ||--o{ attendances : "daily"
    users ||--o{ attendances : "recorded_by"
    school_classes ||--o{ exams : "has"
    subjects ||--o{ exams : "of"
    exams ||--o{ marks : "has"
    students ||--o{ marks : "earns"
    school_classes ||--o{ timetable_entries : "has"
    subjects ||--o{ timetable_entries : "of"
    users ||--o{ timetable_entries : "teacher_id"
    students ||--o{ fees : "instalments"
    fees ||--o{ payments : "paid by"
    users ||--o{ payments : "received_by"
    students |o--o{ notification_logs : "about"
    academic_years ||--o{ terms : "has"
    academic_years ||--o{ enrollments : "in year"
    students ||--o{ enrollments : "class history"
    school_classes ||--o{ enrollments : "attended"
    academic_years |o--o{ exams : "of year"
    terms |o--o{ exams : "of term"
    academic_years |o--o{ fees : "of year"

    users {
        id id PK
        string role "admin teacher accountant parent"
        string email UK
        string phone
    }
    school_classes {
        id id PK
        string name
        string section "unique with name"
    }
    subjects {
        id id PK
        string code UK
        string name
    }
    students {
        id id PK
        string student_no UK
        string qr_token UK
        string fingerprint_id UK
        string parent_phone
        boolean active
        date graduated_at
    }
    attendances {
        id id PK
        date date "unique with student"
        string status "present late absent"
        string method "qr fingerprint manual"
    }
    exams {
        id id PK
        string name
        int max_mark
        date date
    }
    marks {
        id id PK
        decimal mark "unique with exam and student"
    }
    timetable_entries {
        id id PK
        int day_of_week
        int period
        time starts_at
        time ends_at
    }
    fees {
        id id PK
        string title
        decimal amount
        date due_date
    }
    payments {
        id id PK
        decimal amount
        string method
        timestamp paid_at
    }
    notification_logs {
        id id PK
        string phone
        string channel
        string type
        string status
    }
    academic_years {
        id id PK
        string name UK
        date starts_on
        date ends_on
        boolean is_current
    }
    terms {
        id id PK
        string name
        date starts_on
        date ends_on
    }
    enrollments {
        id id PK
        id student_id "unique with year"
        id academic_year_id
        id school_class_id
    }
```

## قرارات التصميم

| القرار | السبب |
|---|---|
| جدول `users` واحد مع عمود `role` | أربعة أدوار بصلاحيات بسيطة. ولي الأمر مستخدم عادي، والطالب يرتبط به بـ `students.parent_id`. |
| `students.parent_phone` منفصل عن `users.phone` | رسائل واتساب تحتاج رقماً حتى لو لم يكن لولي الأمر حساب. `Student::notifyPhone()` يفضّل رقم الطالب ثم رقم الحساب. |
| `qr_token` عشوائي (48 حرفاً) وليس رقم الطالب | البطاقة المطبوعة لا تكشف تسلسل الأرقام فلا يمكن تخمين بطاقة طالب آخر. والحقل مخفي من JSON. |
| `unique(student_id, date)` في `attendances` | سجل واحد لكل طالب كل يوم يفرضه قاعدة البيانات نفسها، فالمسح المكرر لا ينتج سجلين حتى مع طلبين متزامنين. |
| `unique(exam_id, student_id)` في `marks` | علامة واحدة لكل امتحان. إعادة الإدخال تصحّح القيمة ولا تكرّرها. |
| القسط صف في `fees` والدفعات صفوف في `payments` | يدعم الدفع الجزئي. المدفوع والمتبقي والحالة (مدفوع/جزئي/متأخر) تُحسب من الدفعات ولا تُخزَّن، فلا يمكن أن تتناقض. |
| المبالغ `decimal(10,2)` وتقسيم الخطة بالفلس | لا أخطاء فاصلة عائمة. القسط الأخير يمتص فرق التقريب (1000÷3 = 333.33 + 333.33 + 333.34). |
| فهرسان فريدان في `timetable_entries` | `(صف، يوم، حصة)` و`(معلم، يوم، حصة)`: لا يمكن حجز معلم في حصتين متزامنتين ولو تجاوز أحدهم الواجهة. |
| `notification_logs` مستقل عن جدول `jobs` | سجل دائم لكل رسالة (من، لمن، الحالة، سبب الفشل) يبقى بعد انتهاء المهمة. |

## قواعد الحذف (cascade)

- حذف طالب يحذف حضوره وعلاماته وأقساطه ودفعاته. لذلك الواجهة تطلب تأكيداً.
- حذف صف يحذف طلابه، فالواجهة ترفض حذف صف فيه طلاب.
- حذف حساب ولي أمر يفك ارتباط أبنائه (`null`) ولا يحذفهم.

## ما ينقص القاعدة حالياً

- **جدول الحصص** غير مرتبط بعام دراسي: يمثل الجدول الحالي فقط.
- الحضور مرتبط بالتاريخ لا بالعام (يُحسب العام من نطاق تواريخه).
- لا **soft delete** ولا سجل تدقيق للتعديلات والحذف.
- مدرسة واحدة فقط (لا عمود `school_id`).

## ترحيل الطلاب في نهاية العام
من صفحة «الأعوام الدراسية ← ترحيل الطلاب»: تحدد لكل صف إلى أين ينتقل (أو «يتخرجون»)، تراجع الملخص، ثم تطبّق.
- ينشئ سجل `enrollments` للعام الجديد ولا يغيّر سجلات العام السابق.
- الخريجون يصبحون غير نشطين مع `graduated_at`.
- التشغيل المتكرر آمن: من نُقل مسبقاً يُتجاوز.
- «اعتماد كعام حالي» ينقل كل طالب إلى صفه المسجّل في ذلك العام.
- ما يُنشأ أثناء عرض عام سابق (امتحان، قسط) يُنسب لذلك العام.
