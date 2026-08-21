<?php
// Print Toolbar (No Print)
// Expected variables: $templates (array), $currentTemplate (id or 'default'), $session (string)
// Also preserves current query params (like ids=...)

$currentQuery = $_GET;
unset($currentQuery['template']); // Remove old template param
$queryString = http_build_query($currentQuery);
$baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
?>
<link rel="stylesheet" href="/assets/css/styles.css">
<div class="no-print" style="position: sticky; top:0; z-index: 50; display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; background: var(--background, #fff); color: var(--foreground, #333); border-bottom: 1px solid var(--accents-2, #ddd);">
    <div style="display: flex; align-items: center; gap: 10px;">
        <label for="template-select" style="font-size: 14px; font-weight: bold;">Template:</label>
        <select id="template-select" onchange="changeTemplate(this.value)" style="padding: 5px; border: 1px solid var(--accents-2, #ccc); border-radius: 4px; background: var(--background, #fff); color: var(--foreground, #333);">
            <option value="default" <?= $currentTemplate === 'default' ? 'selected' : '' ?>>Default Thermal</option>
            <?php if (! empty($templates)) { ?>
                <?php foreach ($templates as $t) { ?>
                    <option value="<?= $t['id'] ?>" <?= (string) $currentTemplate === (string) $t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['name']) ?>
                    </option>
                <?php } ?>
            <?php } ?>
        </select>
    </div>
    
    <div style="display: flex; gap: 8px;">
        <button onclick="window.print()" style="padding: 6px 16px; background: var(--foreground, #000); color: var(--background, #fff); border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Print</button>
        <button onclick="window.close()" style="padding: 6px 16px; background: var(--accents-1, #eee); border: 1px solid var(--accents-2, #ccc); border-radius: 4px; cursor: pointer; color: var(--foreground, #333);">Close</button>
    </div>
</div>

<script>
    function changeTemplate(val) {
        const currentUrl = new URL(window.location.href);
        if (val === 'default') {
            currentUrl.searchParams.delete('template');
        } else {
            currentUrl.searchParams.set('template', val);
        }
        window.location.href = currentUrl.toString();
    }
</script>
