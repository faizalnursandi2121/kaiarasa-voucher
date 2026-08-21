<?php
$title = 'Install Kaiarasa - Setup';
include ROOT.'/app/Views/layouts/header_public.php';
?>
    <!-- Install Container -->
    <main class="flex-grow flex items-center justify-center flex-col w-full">
    <div class="w-full max-w-full sm:max-w-md z-10 p-4 sm:p-6 animate-fade-in-up">
        
        <div class="text-center mb-6 sm:mb-10">
            <!-- Brand / Logo Area -->
            <div class="flex justify-center mb-6 sm:mb-8">
                <div class="relative group">
                     <img src="/assets/img/logo-sage.webp" alt="Kaiarasa Logo" class="relative h-10 sm:h-12 w-auto block dark:hidden">
                     <img src="/assets/img/logo-white.webp" alt="Kaiarasa Logo" class="relative h-10 sm:h-12 w-auto hidden dark:block">
                </div>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight mb-2">Welcome to Kaiarasa</h1>
            <p class="text-accents-5 text-sm">System Installation & Setup</p>
        </div>

        <div class="card p-6 sm:p-8 space-y-6">
            <?php if (isset($permissions) && (! $permissions['db_writable'] || ! $permissions['root_writable'])) { ?>
                <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-4 mb-6">
                    <div class="flex items-center gap-3 text-red-500 mb-2">
                        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                        <h4 class="font-bold text-sm" data-i18n="install.permission_warning_title">Permission Warning</h4>
                    </div>
                    <ul class="text-xs text-red-400 dark:text-red-400 space-y-1 list-disc list-inside">
                        <?php if (! $permissions['db_writable']) { ?>
                            <li data-i18n="install.permission_db_desc">Folder <code>app/Database</code> must be writable (chmod 775/777).</li>
                        <?php } ?>
                        <?php if (! $permissions['root_writable']) { ?>
                            <li data-i18n="install.permission_root_desc">Root directory must be writable to create the <code>.env</code> file.</li>
                        <?php } ?>
                    </ul>
                    <p class="text-[10px] text-red-400/70 mt-3 pt-3 border-t border-red-500/10" data-i18n="install.permission_fix_desc">
                        Please fix the folder permissions on your server before continuing.
                    </p>
                </div>
            <?php } ?>

            <form action="/install" method="POST" class="space-y-6">
                
                <!-- Steps UI -->
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-foreground text-background text-xs font-bold mt-0.5">1</div>
                        <div>
                            <h3 class="font-medium text-sm text-foreground">Database Setup</h3>
                            <p class="text-xs text-accents-5 mt-0.5">Tables will be created automatically (SQLite).</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3">
                         <div class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-foreground text-background text-xs font-bold mt-0.5">2</div>
                        <div>
                            <h3 class="font-medium text-sm text-foreground">Encryption Key</h3>
                             <p class="text-xs text-accents-5 mt-0.5">Secure key generation for passwords.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                         <div class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-foreground text-background text-xs font-bold mt-0.5">3</div>
                        <div class="w-full">
                            <h3 class="font-medium text-sm text-foreground mb-3">Admin Account</h3>
                             
                             <div class="space-y-3">
                                <div class="space-y-1">
                                    <label for="install-username" class="form-label" data-i18n="login.username">Username</label>
                                    <input type="text" id="install-username" name="username" value="admin" class="form-input" required autocomplete="username">
                                </div>
                                <div class="space-y-1">
                                    <label for="install-password" class="form-label" data-i18n="login.password">Password</label>
                                    <input type="password" id="install-password" name="password" placeholder="Min. 8 characters" class="form-input" required autocomplete="new-password" minlength="8" data-i18n-placeholder="install.password_placeholder">
                                </div>
                             </div>
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full btn btn-primary h-10" data-i18n="install.install_btn">
                        Install Kaiarasa
                    </button>
                </div>
            </form>
        </div>
        
    </div>
    </main>

    <?php include ROOT.'/app/Views/layouts/footer_public.php'; ?>


