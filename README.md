# Education ERP System (Laravel 11)

نظام إدارة مدرسة (API) يغطي: الحضور (QR/بصمة)، العلامات والتقارير، الأقساط والمدفوعات،
إشعارات الأهل (WhatsApp/SMS عبر Queue)، وجدول الحصص مع منع التعارض.

## التشغيل
```bash
composer install && cp .env.example .env && php artisan key:generate
npm ci && npm run build            # Tailwind + Livewire assets
php artisan migrate --seed          # admin@school.test / change-me-now
php artisan queue:work              # لإرسال الإشعارات
php artisan schedule:work           # إغلاق اليوم + تذكير الأقساط
php artisan db:seed --class=DemoSeeder   # اختياري: بيانات تجريبية (teacher@/accountant@/parent@school.test، كلمة المرور password)
php artisan test                          # يعمل على قاعدة في الذاكرة ولا يمس بياناتك
```
الواجهة على `/` — عربية (RTL) افتراضياً مع زر 🌐 للتبديل إلى الإنجليزية (LTR). الترجمة في `lang/ar.json`.

## صفحات الواجهة (Livewire)
لوحة التحكم (رسوم بيانية)، الطلاب (إضافة/تعديل/تعطيل/حذف + بطاقة QR)، الحضور اليدوي، ماسح البوابة (قارئ باركود/QR أو الكاميرا)، العلامات وشهادة الطالب، الأقساط والمدفوعات وإيصالات الدفع، جدول الحصص، تقرير الحضور (CSV)، التقرير المالي (CSV)، الإعلانات لأولياء الأمور، سجل الرسائل، إدارة المستخدمين، إعداد الصفوف والمواد، الملف الشخصي وتغيير كلمة المرور.
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

## تجربة الموقع على GitHub (Codespaces)
GitHub Pages لا يشغّل PHP، لذلك تُجرَّب النسخة الحية داخل Codespace:
1. في صفحة المستودع: **Code ← Codespaces ← Create codespace on branch** واختر الفرع.
2. انتظر دقائق: يثبّت الإعداد التلقائي (`.devcontainer/`) الحزم ويبني الواجهة ويحمّل بيانات تجريبية ثم يشغّل الخادم.
3. افتح تبويب **Ports** واضغط أيقونة الكرة الأرضية بجانب المنفذ 8000. أبقِ المنفذ **Private**.
4. الحسابات التجريبية تظهر في الطرفية (المدير `admin@school.test` / `change-me-now`).

الرسائل لأولياء الأمور تُسجَّل في «رسائل أولياء الأمور» ولا تُرسل فعلياً (`MESSAGING_CHANNEL=log`). الاستهلاك ضمن حصة Codespaces المجانية لحسابك، وأوقف الـ Codespace بعد التجربة.

### بديل مجاني: التشغيل على جهازك (macOS / Linux)
إن وصل حسابك إلى حد إنفاق Codespaces، شغّل النسخة التجريبية محلياً. يلزم PHP 8.3 وComposer وNode 22:
```bash
brew install php@8.3 composer node     # macOS؛ أو استخدم Laravel Herd الذي يثبّت PHP وComposer
git clone -b claude/zen-cray-qms4d9 https://github.com/AlaaBashirSaijary/Education-ERP-System.git
cd Education-ERP-System
bash .devcontainer/setup.sh            # يثبّت الحزم، يبني الواجهة، ويحمّل البيانات التجريبية (SQLite)
php artisan serve                      # ثم افتح http://localhost:8000
php artisan queue:work                 # في طرفية ثانية، لتظهر رسائل الأهل كمرسَلة
```
الحسابات: `admin@school.test` / `change-me-now`، و`teacher@` و`accountant@` و`parent@school.test` / `password`.

## تحديث نسخة موجودة
```bash
git pull
composer install
npm ci && npm run build          # الخطوط مضمّنة الآن في الحزمة
php artisan migrate              # يضيف الأعوام الدراسية ويضع بياناتك الحالية في العام الحالي
# للبيانات التجريبية الجديدة (يمسح قاعدة التجربة): php artisan migrate:fresh --seed --seeder=DemoSeeder
```

## العام الدراسي
صفحة «الأعوام الدراسية» (للمدير): إنشاء عام وفصليه، اعتماده كعام حالي، **ترحيل الطلاب** إلى الصفوف التالية أو تخريجهم. مبدّل العام في الشريط العلوي يغيّر ما تعرضه صفحات العلامات والأقساط والشهادة والتقارير، وما تنشئه أثناء عرض عام سابق يُنسب إليه. التفاصيل في `docs/DATABASE.md`.
