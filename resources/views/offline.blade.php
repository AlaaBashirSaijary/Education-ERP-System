<!DOCTYPE html>
{{-- Shown by the service worker when there is no connection. Self-contained on purpose: it is cached on first visit. --}}
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0B2327">
    <title>{{ config('school.name') }}</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #0B2327; color: #fff;
               font-family: system-ui, -apple-system, 'Segoe UI', Tahoma, sans-serif; text-align: center; }
        main { max-width: 26rem; }
        svg { width: 84px; height: 84px; margin-bottom: 20px; }
        h1 { margin: 0 0 8px; font-size: 1.6rem; }
        p { margin: 0 0 24px; color: #A9C2C6; line-height: 1.7; }
        button { font: inherit; font-weight: 600; color: #1c1300; background: #F5B301; border: 0; border-radius: 12px; padding: 12px 26px; cursor: pointer; }
        button:focus-visible { outline: 3px solid #fff; outline-offset: 3px; }
        .en { display: none; }
        [lang="en"] .ar { display: none; } [lang="en"] .en { display: inline; }
        .brand { margin-top: 28px; font-size: .85rem; color: #5E8087; }
    </style>
</head>
<body>
<main>
    <svg viewBox="0 0 48 48" fill="none" aria-hidden="true">
        <rect x="9" y="9" width="30" height="30" rx="3" fill="#2BA699"/>
        <rect x="9" y="9" width="30" height="30" rx="3" transform="rotate(45 24 24)" fill="#2BA699" opacity=".55"/>
        <circle cx="24" cy="24" r="6.5" fill="#F5B301"/>
    </svg>
    <h1><span class="ar">أنت غير متصل بالإنترنت</span><span class="en">You are offline</span></h1>
    <p><span class="ar">تعذّر الوصول إلى الخادم. تحقق من اتصالك وحاول مجدداً. لا تُحفَظ بيانات الطلاب على هذا الجهاز، لذلك لا تظهر صفحات النظام دون اتصال.</span>
       <span class="en">We could not reach the server. Check your connection and try again. Student data is never stored on this device, so pages are not available offline.</span></p>
    <button type="button" onclick="location.reload()"><span class="ar">إعادة المحاولة</span><span class="en">Try again</span></button>
    <div class="brand">{{ config('school.name') }}</div>
</main>
<script>
    // Pick the language from the browser; reload by itself as soon as the connection returns.
    var saved = (document.cookie.match(/(?:^|; )ui_lang=(ar|en)/) || [])[1];
    var arabic = saved ? saved === 'ar' : /^ar/i.test(navigator.language || '');
    if (!arabic) { document.documentElement.lang = 'en'; document.documentElement.dir = 'ltr'; }
    window.addEventListener('online', function () { location.reload(); });
</script>
</body>
</html>
