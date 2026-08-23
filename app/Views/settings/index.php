<?php
// Settings Hub — satu halaman, menu kiri + konten kanan (System / API CORS / Plugins)
// Router CRUD pindah ke Home; Voucher Templates & Logos pindah ke sidebar dashboard per-router.
$title = 'Settings';
$no_main_container = true;
require_once ROOT.'/app/Views/layouts/header_main.php';

use App\Helpers\LanguageHelper;

$menuItems = [
    'system'   => ['label' => LanguageHelper::t('settings.system', 'System'),        'icon' => 'sliders-horizontal'],
    'api-cors' => ['label' => LanguageHelper::t('settings.api_cors_title', 'API CORS'), 'icon' => 'shield'],
    'plugins'  => ['label' => LanguageHelper::t('settings.plugins', 'Plugins'),       'icon' => 'plug'],
];
$allowedTabs = array_keys($menuItems);
$activeTab = in_array($_GET['tab'] ?? '', $allowedTabs, true) ? $_GET['tab'] : 'system';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow w-full flex flex-col">

<?php
$page_title_key = 'sidebar.settings';
$page_title = 'Settings';
$page_desc = 'Application configuration, security, and extensions.';
$breadcrumbs = [
    ['label' => LanguageHelper::t('common.dashboard', 'Dashboard'), 'href' => "/"],
    ['label' => LanguageHelper::t('sidebar.settings', 'Settings'), 'href' => null],
];
require_once ROOT.'/app/Views/layouts/page_header.php';
?>

    <div class="mt-8 flex-1 min-w-0 flex flex-col lg:flex-row gap-8">

        <!-- ===== Menu kiri ===== -->
        <nav id="settings-menu" class="lg:w-56 shrink-0">
            <div class="flex lg:flex-col gap-1.5 overflow-x-auto no-scrollbar" role="tablist" aria-label="<?= LanguageHelper::t('sidebar.settings', 'Settings') ?>">
                <?php foreach ($menuItems as $key => $item) { ?>
                <button type="button" role="tab" data-tab="<?= $key ?>" aria-selected="<?= $key === $activeTab ? 'true' : 'false' ?>"
                    class="settings-tab flex items-center gap-3 whitespace-nowrap px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors border
                    <?= $key === $activeTab
                        ? 'bg-[#5f7f67]/10 text-[#47614d] dark:text-[#92aa96] border-[#5f7f67]/30'
                        : 'text-accents-5 hover:text-foreground hover:bg-black/[.03] dark:hover:bg-white/[.05] border-transparent' ?>">
                    <i data-lucide="<?= $item['icon'] ?>" class="w-4 h-4 shrink-0"></i>
                    <span><?= $item['label'] ?></span>
                </button>
                <?php } ?>
            </div>
        </nav>

        <!-- ===== Konten kanan ===== -->
        <div class="flex-1 min-w-0">

            <section id="panel-system" data-panel="system" class="<?= $activeTab !== 'system' ? 'hidden' : '' ?>" <?= $activeTab !== 'system' ? 'aria-hidden="true"' : '' ?>>
                <?php include __DIR__.'/partials/system_content.php'; ?>
            </section>

            <section id="panel-api-cors" data-panel="api-cors" class="<?= $activeTab !== 'api-cors' ? 'hidden' : '' ?>" <?= $activeTab !== 'api-cors' ? 'aria-hidden="true"' : '' ?>>
                <?php include __DIR__.'/partials/apicors_content.php'; ?>
            </section>

            <section id="panel-plugins" data-panel="plugins" class="<?= $activeTab !== 'plugins' ? 'hidden' : '' ?>" <?= $activeTab !== 'plugins' ? 'aria-hidden="true"' : '' ?>>
                <?php include __DIR__.'/partials/plugins_content.php'; ?>
            </section>

        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.settings-tab'));
    var panels = {
        'system': document.getElementById('panel-system'),
        'api-cors': document.getElementById('panel-api-cors'),
        'plugins': document.getElementById('panel-plugins')
    };

    var ACTIVE = ['bg-[#5f7f67]/10', 'text-[#47614d]', 'dark:text-[#92aa96]', 'border-[#5f7f67]/30'];
    var INACTIVE = ['text-accents-5', 'hover:text-foreground', 'hover:bg-black/[.03]', 'dark:hover:bg-white/[.05]', 'border-transparent'];

    function activate(name, pushHash) {
        if (!panels[name]) name = 'system';
        tabs.forEach(function (btn) {
            var on = btn.getAttribute('data-tab') === name;
            btn.setAttribute('aria-selected', on ? 'true' : 'false');
            ACTIVE.forEach(function (c) { btn.classList.toggle(c, on); });
            INACTIVE.forEach(function (c) { btn.classList.toggle(c, !on); });
        });
        Object.keys(panels).forEach(function (key) {
            var el = panels[key];
            if (!el) return;
            var on = key === name;
            el.classList.toggle('hidden', !on);
            if (on) el.removeAttribute('aria-hidden'); else el.setAttribute('aria-hidden', 'true');
        });
        if (pushHash && location.hash !== '#'+name) {
            history.replaceState(null, '', '#'+name);
        }
    }

    tabs.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activate(btn.getAttribute('data-tab'), true);
        });
    });

    window.addEventListener('hashchange', function () {
        var name = (location.hash || '').replace('#', '');
        if (panels[name]) activate(name, false);
    });

    // Buka tab dari hash URL (#system / #api-cors / #plugins)
    var initial = (location.hash || '').replace('#', '');
    if (initial && panels[initial]) activate(initial, false);

    if (window.lucide) lucide.createIcons();
})();
</script>
<?php require_once ROOT.'/app/Views/layouts/footer_main.php'; ?>
