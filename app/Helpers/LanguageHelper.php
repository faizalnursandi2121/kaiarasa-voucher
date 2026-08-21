<?php

namespace App\Helpers;

use App\Core\Hooks;

class LanguageHelper
{
    /** @var array|null Cached translations for current language */
    private static $translations = null;

    /** @var string|null Current language code */
    private static $currentLang = null;

    /**
     * Get the current language from cookie (set by JS) or default to 'en'.
     *
     * @return string
     */
    public static function getCurrentLang()
    {
        if (self::$currentLang !== null) {
            return self::$currentLang;
        }

        $lang = $_COOKIE['kaiarasa_lang'] ?? 'en';
        $langFile = ROOT.'/public/lang/'.$lang.'.json';

        // Fallback to English if the language file doesn't exist
        if (! file_exists($langFile)) {
            $lang = 'en';
        }

        self::$currentLang = $lang;

        return $lang;
    }

    /**
     * Load translations from the JSON file for the current language.
     *
     * @return array
     */
    private static function loadTranslations()
    {
        if (self::$translations !== null) {
            return self::$translations;
        }

        $lang = self::getCurrentLang();
        $langFile = ROOT.'/public/lang/'.$lang.'.json';

        if (file_exists($langFile)) {
            $content = file_get_contents($langFile);
            self::$translations = json_decode($content, true) ?: [];
        } else {
            self::$translations = [];
        }

        return self::$translations;
    }

    /**
     * Get a translated string by dot-separated key (e.g., 'sidebar.system').
     * Renders server-side to prevent FOUC (Flash of Untranslated Content).
     *
     * @param  string  $key    Dot-separated translation key
     * @param  string  $fallback  Fallback text if translation not found
     * @return string
     */
    public static function t($key, $fallback = '')
    {
        $translations = self::loadTranslations();

        $value = $translations;
        foreach (explode('.', $key) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $fallback !== '' ? $fallback : $key;
            }
            $value = $value[$segment];
        }

        return is_string($value) ? $value : ($fallback !== '' ? $fallback : $key);
    }

    /**
     * Process rendered HTML output and replace all data-i18n fallback texts
     * with their server-side translations. This globally prevents FOUC
     * (Flash of Untranslated Content) without hiding elements.
     *
     * @param  string  $html
     * @return string
     */
    public static function translateHtml($html)
    {
        // 1) Translate text-node fallbacks: data-i18n="key">Fallback<
        // Uses [^<]*? (allows newlines/whitespace) so multi-line rendered text
        // like dynamically-built menus is also translated (prevents FOUC there too).
        $html = preg_replace_callback(
            '/data-i18n="([^"]+)">([^<]*?)</',
            function ($m) {
                $key = $m[1];
                $fallback = trim($m[2]);

                // Skip empty content or already-rendered PHP output
                if ($fallback === '' || strpos($fallback, '<?') !== false) {
                    return $m[0];
                }

                $translation = self::t($key, $fallback);

                // Replace only if a real translation exists (differs from fallback)
                if ($translation !== $fallback) {
                    return 'data-i18n="'.$key.'">'.$translation.'<';
                }

                return $m[0];
            },
            (string) $html
        );

        // 2) Translate input placeholders: data-i18n-placeholder="key" + placeholder="..."
        $html = preg_replace_callback(
            '/<input[^>]*data-i18n-placeholder="([^"]+)"[^>]*>/i',
            function ($m) {
                $tag = $m[0];
                $key = $m[1];

                $translation = self::t($key, '');

                // Skip if no real translation found
                if ($translation === '' || $translation === $key) {
                    return $tag;
                }

                // Replace the real placeholder attribute (preceded by whitespace, so it won't
                // accidentally match the '-placeholder' substring inside 'data-i18n-placeholder'),
                // regardless of attribute order. HTML-escaped.
                return preg_replace(
                    '/\splaceholder="[^"]*"/i',
                    ' placeholder="'.htmlspecialchars($translation, ENT_QUOTES, 'UTF-8').'"',
                    $tag,
                    1
                );
            },
            (string) $html
        );

        return $html;
    }

    /**
     * Get list of available languages from public/lang directory
     *
     * @return array Array of languages with code and name
     */
    public static function getAvailableLanguages()
    {
        $langDir = ROOT.'/public/lang';
        $languages = [];

        if (! is_dir($langDir)) {
            return [];
        }

        $files = scandir($langDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $code = pathinfo($file, PATHINFO_FILENAME);

                // Read file to get language name if defined, otherwise use code
                $content = file_get_contents($langDir.'/'.$file);
                $data = json_decode($content, true);

                $name = $data['_meta']['name'] ?? strtoupper($code);
                $flag = $data['_meta']['flag'] ?? '🌐';

                $languages[] = [
                    'code' => $code,
                    'name' => $name,
                    'flag' => $flag,
                ];
            }
        }

        return Hooks::applyFilters('get_available_languages', $languages);
    }
}
