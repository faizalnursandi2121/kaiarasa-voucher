<?php
$title = 'API CORS';
$no_main_container = true;
require_once ROOT.'/app/Views/layouts/header_main.php';

use App\Helpers\LanguageHelper;
?>

<!-- Sub-Navbar Navigation -->
<div id="app-dynamic" class="contents">
<?php include ROOT.'/app/Views/layouts/sidebar_settings.php'; ?>

<div id="settings-dynamic" class="contents">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow w-full flex flex-col">

<?php
$page_title_key = 'settings.api_cors_title';
$page_title = 'API CORS';
$page_desc_key = 'settings.api_cors_subtitle';
$page_desc = 'Manage Cross-Origin Resource Sharing for API access.';
$breadcrumbs = [
    ['label' => LanguageHelper::t('common.dashboard', 'Dashboard'), 'href' => "/"],
    ['label' => LanguageHelper::t('sidebar.settings', 'Settings'), 'href' => "/settings"],
    ['label' => LanguageHelper::t('settings.api_cors_title', 'API CORS'), 'href' => null],
];
require_once ROOT.'/app/Views/layouts/page_header.php';
?>

    <!-- Content Area -->
    <div class="mt-8 flex-1 min-w-0" id="settings-content-area">
        <?php include __DIR__.'/partials/apicors_content.php'; ?>
    </div>

</div>
</div>
<?php require_once ROOT.'/app/Views/layouts/footer_main.php'; ?>
