<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Single source of truth for the business "NAP" data (name, address, phone,
 * e-mail, social profiles) stored in the site_settings table.
 *
 * Values are looked up by setting_key (never by id), cached forever and
 * invalidated by the SiteSetting model whenever a row is saved or deleted.
 * Phone numbers are stored in E.164 (+359...) and formatted for display here,
 * so schema.org, tel: links and visible text can never disagree again.
 */
final class Settings
{
    public const CACHE_KEY = 'site_settings.by_key.v1';

    public const DEFAULT_PHONE = '+359886190124';

    public const DEFAULT_EMAIL = 'taketwostudio1603@gmail.com';

    public const DEFAULT_ADDRESS = 'ж.к. Възраждане IV 1603, Варна';

    public const CITY = 'Варна';

    public const POSTAL_CODE = '9000';

    /** Order matters: it is also the order used for schema.org sameAs. */
    public const SOCIAL_KEYS = [
        'facebook' => 'site_facebook',
        'instagram' => 'site_instagram',
        'tiktok' => 'site_tiktok',
        'youtube' => 'site_youtube',
        'google_maps' => 'site_google_maps',
    ];

    public static function all(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): Collection {
            return SiteSetting::query()->pluck('setting_value', 'setting_key');
        });
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = self::all()->get($key);

        if ($value === null || trim((string) $value) === '') {
            return $default;
        }

        return trim((string) $value);
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function siteName(): string
    {
        return self::get('site_name', 'Take Two Studio 1603');
    }

    public static function tagline(): string
    {
        return self::get('site_tagline', 'Запечатваме вашите моменти завинаги');
    }

    /** Primary business phone in E.164. */
    public static function phone(): string
    {
        return self::toE164(self::get('site_phone')) ?? self::DEFAULT_PHONE;
    }

    /** Optional second line (e.g. the prom-season number) in E.164. */
    public static function phoneSecondary(): ?string
    {
        return self::toE164(self::get('site_phone_secondary'));
    }

    public static function phoneSecondaryLabel(): ?string
    {
        return self::get('site_phone_secondary_label');
    }

    public static function email(): string
    {
        return self::get('site_email', self::DEFAULT_EMAIL);
    }

    /** Full address as shown to visitors, e.g. "ж.к. Възраждане IV 1603, Варна". */
    public static function address(): string
    {
        return self::get('site_address', self::DEFAULT_ADDRESS);
    }

    /** Address without the city, for schema.org PostalAddress.streetAddress. */
    public static function streetAddress(): string
    {
        return trim((string) preg_replace('/,\s*'.preg_quote(self::CITY, '/').'(,\s*България)?\s*$/u', '', self::address()));
    }

    /** @return array<string,string> network => URL (only the ones that are filled in) */
    public static function socialLinks(): array
    {
        $links = [];

        foreach (self::SOCIAL_KEYS as $network => $key) {
            $url = self::get($key);

            if ($url !== null && preg_match('#^https?://#i', $url)) {
                $links[$network] = $url;
            }
        }

        return $links;
    }

    /**
     * Normalise any human-entered Bulgarian phone number to E.164.
     * "088 619 0124" / "0886190124" / "+359 88 619 0124" / "00359886190124" -> "+359886190124"
     */
    public static function toE164(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $digits = preg_replace('/[^0-9+]/', '', $raw) ?? '';

        if ($digits === '' || $digits === '+') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = '+'.substr($digits, 2);
        }

        if (str_starts_with($digits, '+')) {
            return $digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+359'.substr($digits, 1);
        }

        if (strlen($digits) === 9 && (str_starts_with($digits, '8') || str_starts_with($digits, '9'))) {
            return '+359'.$digits;
        }

        return '+'.$digits;
    }

    /** "+359886190124" -> "088 619 0124" (national format Bulgarians recognise). */
    public static function phoneDisplay(?string $phone): string
    {
        $e164 = self::toE164($phone);

        if ($e164 === null) {
            return '';
        }

        if (str_starts_with($e164, '+359') && strlen($e164) === 13) {
            $national = '0'.substr($e164, 4);

            return substr($national, 0, 3).' '.substr($national, 3, 3).' '.substr($national, 6);
        }

        return $e164;
    }

    /** Value for href="tel:..." */
    public static function phoneHref(?string $phone): string
    {
        return self::toE164($phone) ?? '';
    }
}
