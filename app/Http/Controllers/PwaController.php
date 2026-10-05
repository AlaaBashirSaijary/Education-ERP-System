<?php

namespace App\Http\Controllers;

class PwaController extends Controller
{
    /** Web app manifest: makes the site installable ("Add to Home Screen" / "Install app"). */
    public function manifest()
    {
        $name = config('school.name');
        $icon = fn (string $file, string $size, string $purpose = 'any') => [
            'src' => '/icons/'.$file, 'sizes' => $size, 'type' => 'image/png', 'purpose' => $purpose,
        ];

        return response()->json([
            'id' => '/',
            'name' => $name.' – '.__('School Management'),
            'short_name' => mb_substr($name, 0, 12),
            'description' => __('Attendance, grades, fees and messages to parents, in one calm place.'),
            'lang' => app()->getLocale(),
            'dir' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
            'start_url' => '/dashboard?source=pwa',
            'scope' => '/',
            'display' => 'standalone',
            'display_override' => ['standalone', 'minimal-ui'],
            'orientation' => 'any',
            'background_color' => '#0B2327',
            'theme_color' => '#0B2327',
            'categories' => ['education', 'productivity'],
            'icons' => [
                $icon('icon-192.png', '192x192'),
                $icon('icon-512.png', '512x512'),
                $icon('icon-maskable-512.png', '512x512', 'maskable'),
            ],
            'shortcuts' => [
                ['name' => __('Dashboard'), 'url' => '/dashboard?source=shortcut', 'icons' => [$icon('icon-192.png', '192x192')]],
                ['name' => __('Students'), 'url' => '/students?source=shortcut', 'icons' => [$icon('icon-192.png', '192x192')]],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'public, max-age=3600']);
    }
}
