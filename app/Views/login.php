<?php
$title = 'Kaiarasa Login';
include ROOT.'/app/Views/layouts/header_public.php';
?>

<main class="flex-grow flex flex-col lg:flex-row w-full">

    <!-- Panel kiri: brand (menjadi header tipis di mobile) -->
    <section class="lg:w-1/2 bg-[#5f7f67] text-white flex items-center justify-center py-10 lg:py-0">
        <div class="text-center lg:text-left px-8 max-w-md">
            <img src="/assets/img/logo-white.webp" alt="Kaiarasa" class="h-10 w-auto mx-auto lg:mx-0 mb-6">
            <h1 class="font-serif italic text-3xl lg:text-4xl leading-tight">
                Hotspot Voucher<br>Management
            </h1>
            <p class="text-white/70 text-sm mt-3 tracking-wide">MikroTik Hotspot Manager</p>
        </div>
    </section>

    <!-- Panel kanan: form -->
    <section class="lg:w-1/2 flex items-center justify-center p-6 sm:p-12">
        <div class="w-full max-w-md rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-8 shadow-[0_8px_24px_rgba(0,0,0,.08)]">

            <h2 class="text-xl font-bold tracking-tight mb-1">Masuk ke MIVO</h2>
            <p class="text-[13px] opacity-50 mb-7">Kelola voucher dan router hotspot Anda.</p>

            <?php if (! empty($error)): ?>
            <!-- Catatan: mekanisme error aktual adalah flash message (FlashHelper) yang
                 dirender sebagai SweetAlert toast oleh footer_public.php.
                 Blok ini hanya fallback jika suatu saat controller mem-passing $error. -->
            <div class="mb-5 flex items-start gap-2.5 rounded-xl bg-red-500/[.07] border border-red-500/20 px-3.5 py-3 text-[12.5px] text-red-600 dark:text-red-400">
                <i data-lucide="alert-circle" class="w-4 h-4 mt-px shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <form action="/login" method="POST" class="space-y-4">
                <div>
                    <label for="login-username" class="block text-[11px] font-semibold uppercase tracking-wider opacity-60 mb-2">Username</label>
                    <input type="text" id="login-username" name="username" required autocomplete="username"
                        class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                </div>
                <div>
                    <label for="login-password" class="block text-[11px] font-semibold uppercase tracking-wider opacity-60 mb-2">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                        class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                </div>
                <button type="submit"
                    class="w-full h-11 rounded-xl bg-[#5f7f67] hover:bg-[#6b8b73] text-white text-[13px] font-semibold transition-colors">
                    Sign In
                </button>
            </form>

            <p class="text-center text-[11px] opacity-40 mt-7">&copy; <?= date('Y') ?> Kaiarasa</p>
        </div>
    </section>
</main>

<?php require_once ROOT.'/app/Views/layouts/footer_public.php'; ?>
