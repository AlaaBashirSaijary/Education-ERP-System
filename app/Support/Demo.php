<?php

namespace App\Support;

class Demo
{
    public static function on(): bool
    {
        return (bool) config('school.demo_mode');
    }

    /** WhatsApp link if a number is configured, else mailto, else null. */
    public static function contactUrl(?string $message = null): ?string
    {
        if ($wa = config('school.contact_whatsapp')) {
            return 'https://wa.me/'.$wa.'?text='.rawurlencode($message ?? __('Hello, I tried the school system demo and I would like to know more.'));
        }
        if ($mail = config('school.contact_email')) {
            return 'mailto:'.$mail.'?subject='.rawurlencode(__('School system demo'));
        }

        return null;
    }
}
