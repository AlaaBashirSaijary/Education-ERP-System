import defaultTheme from 'tailwindcss/defaultTheme';

// Colour scales come from CSS variables (resources/css/app.css) so the whole UI re-themes for dark mode.
const scale = (name) => Object.fromEntries(
    [50, 100, 200, 300, 400, 500, 600, 700, 800, 900].map((k) => [k, `rgb(var(--${name}-${k}) / <alpha-value>)`]),
);
const single = (name) => `rgb(var(--${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    safelist: [{ pattern: /^tone-[0-7]$/ }],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                slate: scale('slate'),
                brand: scale('brand'),
                emerald: scale('emerald'),
                amber: scale('amber'),
                rose: scale('rose'),
                canvas: single('canvas'),
                surface: single('surface'),
                sidebar: single('sidebar'),
                'sidebar-2': single('sidebar-2'),
                saffron: single('saffron'),
            },
            fontFamily: {
                sans: ['"IBM Plex Sans Arabic"', ...defaultTheme.fontFamily.sans],
                display: ['"Reem Kufi"', '"IBM Plex Sans Arabic"', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                card: '0 1px 2px rgb(14 27 32 / .05), 0 4px 16px -6px rgb(14 27 32 / .08)',
                pop: '0 12px 40px -12px rgb(14 27 32 / .35)',
            },
            keyframes: {
                rise: { from: { opacity: 0, transform: 'translateY(8px)' }, to: { opacity: 1, transform: 'none' } },
                pulseRing: { '0%': { boxShadow: '0 0 0 0 rgb(var(--saffron) / .55)' }, '100%': { boxShadow: '0 0 0 14px rgb(var(--saffron) / 0)' } },
            },
            animation: { rise: 'rise .35s ease-out both', ring: 'pulseRing 1.4s ease-out infinite' },
        },
    },
    plugins: [],
};
