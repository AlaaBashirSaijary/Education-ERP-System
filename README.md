# Education ERP System (Laravel 11)

نظام إدارة مدرسة (API) يغطي: الحضور (QR/بصمة)، العلامات والتقارير، الأقساط والمدفوعات،
إشعارات الأهل (WhatsApp/SMS عبر Queue)، وجدول الحصص مع منع التعارض.

## التشغيل
```bash
composer install && cp .env.example .env && php artisan key:generate
php artisan migrate --seed          # admin@school.test / change-me-now
php artisan queue:work              # لإرسال الإشعارات
php artisan schedule:work           # إغلاق اليوم + تذكير الأقساط
php artisan test
```
اضبط `MESSAGING_CHANNEL` (`log` | `whatsapp` | `sms`) ومفاتيح المزوّد في `.env`.

## الأدوار
`admin`, `teacher`, `accountant`, `parent` — الأهل يرون أبناءهم فقط.

## أهم المسارات (`/api`, Bearer token من `POST /login`)
| الميزة | المسار |
|---|---|
| مسح QR/بصمة | `POST attendance/scan` |
| حضور يدوي لصف | `POST attendance/bulk` |
| QR بطاقة الطالب (SVG) | `GET students/{id}/qr` |
| امتحان + علامات | `POST exams`, `POST exams/{id}/marks` |
| تقرير الطالب / إرساله للأهل | `GET students/{id}/report-card`, `POST .../send` |
| خطة أقساط | `POST students/{id}/fee-plan` |
| دفعة / المتأخرات | `POST fees/{id}/payments`, `GET fees/overdue` |
| جدول الحصص | `POST timetable`, `GET classes/{id}/timetable` |

## المصادر المفتوحة
الأفكار مستلهمة من مشاريع MIT: Education_ERP (هيكل الأقساط والعلامات)، lav_sms (الأدوار والجداول)،
ونمط Queue Jobs للإشعارات من school_management_system. الكود هنا مكتوب من الصفر على Laravel 11 لأن تلك المشاريع على Laravel 8 بمخططات متعارضة.
