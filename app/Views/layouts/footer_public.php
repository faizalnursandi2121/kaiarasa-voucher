    <?php
    use App\Core\Hooks;
    use App\Helpers\FlashHelper;
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize Lucide Icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
        
        <?php if (FlashHelper::has()) { ?>
            <?php $flash = FlashHelper::get(); ?>
            document.addEventListener('DOMContentLoaded', () => {
                // Map Flash Type to Lucide Icon & Color Class
                const typeMap = {
                    'success': { icon: 'check-circle-2', color: 'text-success' },
                    'error':   { icon: 'x-circle', color: 'text-error' },
                    'warning': { icon: 'alert-triangle', color: 'text-warning' },
                    'info':    { icon: 'info', color: 'text-info' },
                    'question':{ icon: 'help-circle', color: 'text-question' }
                };

                const type = '<?= $flash['type'] ?>';
                const config = typeMap[type] || typeMap['info'];
                
                let title = '<?= addslashes($flash['title']) ?>';
                let message = '<?= addslashes($flash['message'] ?? '') ?>';
                const params = <?= json_encode($flash['params'] ?? []) ?>;
                const isTranslated = <?= $flash['isTranslated'] ? 'true' : 'false' ?>;
                
                const showFlash = () => {
                    if (isTranslated && window.i18n) {
                        title = window.i18n.t(title, params);
                        message = window.i18n.t(message, params);
                    }

                    // Use Custom Toasts for most notifications (Success, Info, Error)
                    // Only use Modal (Swal) for specific heavy warnings or questions if needed
                    // Use Toasts for standard notifications
                    if (['success', 'info', 'error', 'warning'].includes(type)) {
                        if (window.Kaiarasa && window.Kaiarasa.toast) {
                             Kaiarasa.toast(type, title, message);
                        }
                    } else {
                        // For questions or other types, use Modal Alert
                        if (window.Kaiarasa && window.Kaiarasa.alert) {
                            Kaiarasa.alert(type || 'info', title, message);
                        } else if (typeof Swal !== 'undefined') {
                            Swal.fire(title, message, type);
                        }
                    }
                };

                if (window.i18n && window.i18n.ready) {
                    window.i18n.ready.then(showFlash);
                } else {
                    showFlash();
                }
            });
        <?php } ?>
    </script>
    <?php Hooks::doAction('mivo_footer'); ?>
</body>
</html>
