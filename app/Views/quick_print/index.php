<?php
// Quick Print Dashboard (Card View)
$title = 'Quick Print';
require_once ROOT.'/app/Views/layouts/header_main.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground" data-i18n="quick_print.title">Quick Print</h1>
            <p class="text-accents-5" data-i18n="quick_print.subtitle">Instant voucher generation and printing.</p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Template Selector -->
            <div class="flex items-center gap-2">
                <label class="text-sm text-accents-5 whitespace-nowrap" data-i18n="settings.template_name">Template</label>
                <select id="qp-template-select" class="form-input text-sm w-48" onchange="onTemplateChange(this.value)">
                    <option value="default" data-i18n="quick_print.default_thermal" <?= ($defaultTemplate ?? 'default') === 'default' ? 'selected' : '' ?>>Default Thermal</option>
                    <?php if (! empty($templates)) { ?>
                        <?php foreach ($templates as $t) { ?>
                            <option value="<?= htmlspecialchars($t['id']) ?>" <?= ($defaultTemplate ?? 'default') === (string) $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                        <?php } ?>
                    <?php } ?>
                </select>
            </div>
            <a href="/<?= htmlspecialchars($session) ?>/quick-print/manage" class="hidden sm:flex items-center gap-2 btn btn-secondary">
                <i data-lucide="settings" class="w-4 h-4"></i>
                <span data-i18n="quick_print.manage">Manage Packages</span>
            </a>
            <a href="/<?= htmlspecialchars($session) ?>/quick-print/manage" class="sm:hidden btn btn-secondary px-2">
                 <i data-lucide="settings" class="w-4 h-4"></i>
            </a>
        </div>
    </div>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php if (empty($packages)) { ?>
        <div class="col-span-full flex flex-col items-center justify-center p-12 border-2 border-dashed border-accents-2 rounded-lg text-accents-5">
            <i data-lucide="printer" class="w-12 h-12 mb-4 opacity-50"></i>
            <p class="text-lg font-medium" data-i18n="quick_print.no_packages">No Packages Found</p>
            <p class="text-sm mb-6" data-i18n="quick_print.create_first">Create a Quick Print package to get started.</p>
            <a href="/<?= htmlspecialchars($session) ?>/quick-print/manage" class="btn btn-primary" data-i18n="quick_print.create_package">
                Create Package
            </a>
        </div>
        <?php } else { ?>
            <?php foreach ($packages as $pkg) { ?>
            <!-- Card -->
            <div class="card relative group overflow-hidden hover:border-primary/50 hover:-translate-y-1 transition-all duration-300 p-0">
                <!-- Color Header -->
                <div class="h-2 <?= htmlspecialchars($pkg['color'] ?? 'bg-blue-500') ?>"></div>
                
                <div class="p-5 bg-background">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-bold text-lg text-foreground truncate" title="<?= htmlspecialchars($pkg['name']) ?>">
                                <?= htmlspecialchars($pkg['name']) ?>
                            </h3>
                            <div class="text-xs text-accents-5 font-mono mt-1">
                                <span data-i18n="quick_print.profile">Profile</span>: <?= htmlspecialchars($pkg['profile']) ?>
                            </div>
                        </div>
                        <div class="text-right">
                             <div class="font-bold text-foreground">
                                <?= htmlspecialchars($pkg['price'] > 0 ? number_format($pkg['price'], 0, ',', '.') : 'Free') ?>
                            </div>
                               <div class="text-xs text-accents-5" data-i18n="<?= $pkg['time_limit'] ?: 'common.unlimited' ?>">
                                <?= htmlspecialchars($pkg['time_limit'] ?: 'Unlimited') ?>
                            </div>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="space-y-2 text-sm text-accents-5 mb-6">
                         <div class="flex justify-between border-b border-accents-1 pb-1">
                            <span data-i18n="quick_print.prefix">Prefix</span>
                            <span class="font-mono text-xs"><?= htmlspecialchars($pkg['prefix']) ?: '-' ?></span>
                         </div>
                         <div class="flex justify-between border-b border-accents-1 pb-1">
                            <span data-i18n="quick_print.server">Server</span>
                            <span><?= htmlspecialchars($pkg['server']) ?></span>
                         </div>
                    </div>

                    <!-- Action -->
                    <button onclick="printPackage('<?= $pkg['id'] ?>', '<?= htmlspecialchars($pkg['name']) ?>')" class="w-full btn btn-primary flex items-center justify-center gap-2">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                        <span data-i18n="quick_print.print_voucher">Print Voucher</span>
                    </button>
                    
                    <?php if (! empty($pkg['comment'])) { ?>
                    <p class="text-xs text-accents-4 text-center mt-3 truncate"><?= htmlspecialchars($pkg['comment']) ?></p>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>
        <?php } ?>
    </div>
</div>

<!-- Print Script -->
<script>
    var selectedTemplate = '<?= htmlspecialchars($defaultTemplate ?? 'default') ?>';
    window.whenReady(() => {
        const sel = document.getElementById('qp-template-select');
        if (sel) selectedTemplate = sel.value;
    });

    function onTemplateChange(val) {
        selectedTemplate = val;
    }

    function printPackage(id, name) {
        // Open print window
        const width = 400;
        const height = 600;
        const left = (window.innerWidth - width) / 2;
        const top = (window.innerHeight - height) / 2;
        
        let url = `/<?= htmlspecialchars($session) ?>/quick-print/print/${id}`;
        if (selectedTemplate !== 'default') {
            url += `?template=${selectedTemplate}`;
        }
        
        window.open(url, `Print_${name}`, `width=${width},height=${height},top=${top},left=${left},scrollbars=yes`);
    }
</script>

<?php require_once ROOT.'/app/Views/layouts/footer_main.php'; ?>
