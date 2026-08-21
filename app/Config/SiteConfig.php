<?php

namespace App\Config;

class SiteConfig
{
    const APP_NAME = 'Kaiarasa';

    const APP_VERSION = 'v1.2.3';

    const APP_FULL_NAME = 'Kaiarasa - Voucher Management';

    const CREDIT_NAME = 'Kaiarasa';

    const CREDIT_URL = 'https://github.com/kaiarasa';

    const YEAR = '2026';

    const REPO_URL = 'https://github.com/kaiarasa/kaiarasa';

    // Security Keys
    // Fetched from .env or fallback to default
    public static function getSecretKey()
    {
        return getenv('APP_KEY') ?: 'kaiarasa_official_secret_key_32bytes';
    }

    const IS_DEV = false; // Set to true only for local development.

    /**
     * Get the formatted page title
     */
    public static function getTitle($page = '')
    {
        return empty($page) ? self::APP_FULL_NAME : $page.' | '.self::APP_FULL_NAME;
    }

    /**
     * Get footer text
     */
    public static function getFooter()
    {
        $currentYear = date('Y');
        $yearDisplay = ($currentYear == self::YEAR) ? self::YEAR : self::YEAR.' - '.$currentYear;

        return self::APP_FULL_NAME.' &copy; 2026 - '.$yearDisplay.' &bull; Created with Love <i data-lucide="heart" class="w-3 h-3 inline text-red-500 fill-red-500 mx-1"></i> Developed by <a href="'.self::CREDIT_URL.'" target="_blank" class="font-medium hover:text-foreground transition-colors">'.self::CREDIT_NAME.'</a>';
    }
}
