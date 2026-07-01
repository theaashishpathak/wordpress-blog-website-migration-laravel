<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

/**
 * Seed the 6 starter languages NewsPilot ships with.
 *
 * Idempotent — `code` is the unique key. Re-running the seeder updates
 * existing rows in place without duplicates. Authoritative spec:
 * docs/Multilanguage Schema.txt Section 2.
 */
class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'flag_emoji' => '🇺🇸',
                'direction' => Language::DIRECTION_LTR,
                'is_default' => true,
                'is_active' => true,
                'is_admin_locale' => true,
                'sort_order' => 1,
                'locale_php' => 'en_US',
            ],
            [
                'code' => 'bn',
                'name' => 'Bangla',
                'native_name' => 'বাংলা',
                'flag_emoji' => '🇧🇩',
                'direction' => Language::DIRECTION_LTR,
                'is_default' => false,
                'is_active' => true,
                'is_admin_locale' => true,
                'sort_order' => 2,
                'locale_php' => 'bn_BD',
            ],
            [
                'code' => 'ar',
                'name' => 'Arabic',
                'native_name' => 'العربية',
                'flag_emoji' => '🇸🇦',
                'direction' => Language::DIRECTION_RTL,
                'is_default' => false,
                'is_active' => true,
                'is_admin_locale' => false,
                'sort_order' => 3,
                'locale_php' => 'ar_SA',
            ],
            [
                'code' => 'es',
                'name' => 'Spanish',
                'native_name' => 'Español',
                'flag_emoji' => '🇪🇸',
                'direction' => Language::DIRECTION_LTR,
                'is_default' => false,
                'is_active' => true,
                'is_admin_locale' => false,
                'sort_order' => 4,
                'locale_php' => 'es_ES',
            ],
            [
                'code' => 'fr',
                'name' => 'French',
                'native_name' => 'Français',
                'flag_emoji' => '🇫🇷',
                'direction' => Language::DIRECTION_LTR,
                'is_default' => false,
                'is_active' => true,
                'is_admin_locale' => false,
                'sort_order' => 5,
                'locale_php' => 'fr_FR',
            ],
            [
                'code' => 'hi',
                'name' => 'Hindi',
                'native_name' => 'हिन्दी',
                'flag_emoji' => '🇮🇳',
                'direction' => Language::DIRECTION_LTR,
                'is_default' => false,
                'is_active' => true,
                'is_admin_locale' => false,
                'sort_order' => 6,
                'locale_php' => 'hi_IN',
            ],
        ];

        foreach ($languages as $attributes) {
            Language::query()->updateOrCreate(
                ['code' => $attributes['code']],
                $attributes,
            );
        }
    }
}
