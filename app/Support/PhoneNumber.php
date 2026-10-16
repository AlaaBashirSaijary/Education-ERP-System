<?php

namespace App\Support;

use RuntimeException;

/** Turns the phone numbers people actually type into what messaging providers accept. */
class PhoneNumber
{
    /**
     * Cleaned number for storage: digits only, "+" kept when a country code is known.
     * "0791234567" + SCHOOL_COUNTRY_CODE=962 → "+962791234567".
     */
    public static function normalize(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', self::latinDigits($raw));
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            return '+'.substr($digits, 2);
        }
        if (str_starts_with($raw, '+')) {
            return '+'.$digits;
        }
        $cc = preg_replace('/\D+/', '', (string) config('school.country_code'));
        if ($cc !== '' && str_starts_with($digits, '0')) {
            return '+'.$cc.substr($digits, 1);
        }

        return $digits;
    }

    public static function isValid(?string $phone): bool
    {
        $n = strlen(preg_replace('/\D+/', '', (string) $phone));

        return $n >= 8 && $n <= 15;
    }

    /** International format with "+" (Twilio). Fails loudly when the country code is unknown. */
    public static function e164(string $phone): string
    {
        $n = self::normalize($phone);
        if (! $n || ! str_starts_with($n, '+')) {
            throw new RuntimeException("Phone [{$phone}] has no country code. Write it as +962791234567 or set SCHOOL_COUNTRY_CODE.");
        }

        return $n;
    }

    /** Digits only, with country code (WhatsApp Cloud API). */
    public static function whatsapp(string $phone): string
    {
        return ltrim(self::e164($phone), '+');
    }

    /** Arabic-Indic and Persian digits → 0-9, so numbers typed on an Arabic keyboard work. */
    private static function latinDigits(string $s): string
    {
        return strtr($s, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']);
    }
}
