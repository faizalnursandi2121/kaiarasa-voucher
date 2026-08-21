<?php
/**
 * Reusable Page Header Partial
 *
 * Renders: Breadcrumb → Title/Description
 *
 * Page actions and toolbar are rendered by the PAGE itself,
 * right above the table/content — not in the header.
 *
 * Variables (all optional):
 *   $breadcrumbs      : array  of ['label' => string, 'href' => string|null]
 *   $page_title       : string
 *   $page_title_key   : string  i18n key for title
 *   $page_desc        : string
 *   $page_desc_key    : string  i18n key for description
 *   $page_desc_params : array   i18n params for description
 */

$breadcrumbs      = $breadcrumbs      ?? [];
$page_title       = $page_title       ?? '';
$page_title_key   = $page_title_key   ?? null;
$page_desc        = $page_desc        ?? '';
$page_desc_key    = $page_desc_key    ?? null;
$page_desc_params = $page_desc_params ?? null;

use App\Helpers\LanguageHelper;

// Resolve title via i18n if key provided
if ($page_title_key && empty($page_title)) {
    $page_title = LanguageHelper::t($page_title_key, $page_title);
}

// Resolve description via i18n if key provided
if ($page_desc_key && empty($page_desc)) {
    $page_desc = LanguageHelper::t($page_desc_key, $page_desc, $page_desc_params);
}
?>
<div class="page-header-block">

    <?php if (! empty($breadcrumbs)) { ?>
    <nav class="page-breadcrumb" aria-label="Breadcrumb">
        <?php foreach ($breadcrumbs as $i => $crumb) {
            $isLast = ($i === count($breadcrumbs) - 1);
            $label = $crumb['label'] ?? '';
            $href  = $crumb['href']  ?? null;
            ?>
            <?php if (! empty($href) && ! $isLast) { ?>
                <a href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($label) ?></a>
            <?php } else { ?>
                <span class="<?= $isLast ? 'page-breadcrumb-current' : '' ?>"><?= htmlspecialchars($label) ?></span>
            <?php } ?>
            <?php if (! $isLast) { ?>
                <span class="page-breadcrumb-separator">/</span>
            <?php } ?>
        <?php } ?>
    </nav>
    <?php } ?>

    <header class="page-header">
        <div class="page-title-group">
            <h1 class="page-title" <?= $page_title_key ? 'data-i18n="' . htmlspecialchars($page_title_key) . '"' : '' ?>>
                <?= $page_title ?>
            </h1>
            <?php if (! empty($page_desc)) { ?>
                <p class="page-description" <?= $page_desc_key ? 'data-i18n="' . htmlspecialchars($page_desc_key) . '"' : '' ?>
                    <?php if (! empty($page_desc_params)) { ?>
                        data-i18n-params='<?= htmlspecialchars(json_encode($page_desc_params)) ?>'
                    <?php } ?>>
                    <?= $page_desc ?>
                </p>
            <?php } ?>
        </div>
    </header>

</div>
