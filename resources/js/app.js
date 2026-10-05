import './bootstrap';

/* ---- PWA: service worker + install prompt ---- */

if ('serviceWorker' in navigator) {
    // Only works on HTTPS or localhost; silently skipped elsewhere (e.g. plain http on a LAN address).
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}

// Chrome/Edge/Android fire this when the app is installable. Keep the event so a button can use it later.
window.__pwa = { deferred: null };
window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    window.__pwa.deferred = event;
    window.dispatchEvent(new Event('pwa:available'));
});
window.addEventListener('appinstalled', () => {
    window.__pwa.deferred = null;
    window.dispatchEvent(new Event('pwa:installed'));
});

document.addEventListener('alpine:init', () => {
    window.Alpine.data('pwaInstall', () => ({
        deferred: null,
        ios: false,
        standalone: false,
        help: false,

        init() {
            this.standalone = matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
            // iPhone/iPad Safari has no install event: the user adds the app from the Share sheet.
            const ua = navigator.userAgent;
            this.ios = /iphone|ipad|ipod/i.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
            this.deferred = window.__pwa.deferred;
            window.addEventListener('pwa:available', () => (this.deferred = window.__pwa.deferred));
            window.addEventListener('pwa:installed', () => (this.standalone = true));
        },

        get available() {
            return !this.standalone && (this.deferred !== null || this.ios);
        },

        async install() {
            if (this.deferred) {
                this.deferred.prompt();
                await this.deferred.userChoice;
                this.deferred = window.__pwa.deferred = null;
            } else if (this.ios) {
                this.help = true;
            }
        },
    }));
});
