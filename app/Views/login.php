<?php
$title = 'Kaiarasa Login';
include ROOT.'/app/Views/layouts/header_public.php';
?>

<!-- Initial page loader (UI States #1 Loading): menutup progresive-load asset,
     hilang begitu DOM siap — bukan menunggu semua asset (pola captive portal) -->
<div id="page-loader" style="position:fixed;inset:0;z-index:300;display:flex;align-items:center;justify-content:center;background:#5f7f67;transition:opacity .35s ease,visibility .35s ease;">
    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" aria-hidden="true"
         style="animation:pl-spin .8s linear infinite"><circle cx="12" cy="12" r="10" stroke="rgba(255,255,255,.25)" stroke-width="3"/><path d="M22 12a10 10 0 0 0-10-10" stroke="#fff" stroke-width="3" stroke-linecap="round"/></svg>
    <style>@keyframes pl-spin{to{transform:rotate(360deg)}}</style>
</div>
<script>
(function () {
    var loader = document.getElementById('page-loader');
    var t0 = Date.now();
    var done = false;
    function hide() {
        if (done) return;
        done = true;
        // tampil minimal 300ms agar tidak berkedip di koneksi cepat
        var wait = Math.max(0, 300 - (Date.now() - t0));
        setTimeout(function () {
            loader.style.opacity = '0';
            loader.style.visibility = 'hidden';
            setTimeout(function () { loader.remove(); }, 400);
        }, wait);
    }
    // Form login tidak berguna sebelum CSS siap — tunggu window.load
    // (semua asset selesai), dengan batas aman 2.5 dtk.
    if (document.readyState === 'complete') {
        hide();
    } else {
        window.addEventListener('load', hide);
        setTimeout(hide, 2500);
    }
})();
</script>

<!-- Replika anatomi Metronic demo18 creative/sign-in:
     bg full-page (sage solid, tanpa foto) · brand kiri · kartu 600px kanan.
     Nilai spasi mengikuti skala Metronic (mb-11=2.75rem, p-20=5rem, dst). -->

<main class="flex-grow flex flex-col lg:flex-row w-full">

    <!-- ===== KIRI: brand (Metronic: d-flex flex-center w-lg-50 pt-15 px-10) ===== -->
    <section class="lg:w-1/2 flex items-center justify-center pt-[3.75rem] px-10 lg:pt-0 lg:pb-[3.75rem]">
        <div class="flex flex-col items-center lg:items-start text-center lg:text-left">
            <img src="/assets/img/logo-white.webp" alt="Kaiarasa" class="h-20 w-auto mb-7 drop-shadow-lg">
        </div>
    </section>

    <!-- ===== KANAN: kartu form (Metronic: p-12 p-lg-20 justify-end → card w-md-600px p-20 rounded-4) ===== -->
    <section class="flex justify-center lg:justify-end items-start lg:items-center p-6 sm:p-12 lg:p-20 w-full lg:w-auto">
        <div class="bg-white dark:bg-[#1a1c19] flex flex-col items-stretch justify-center rounded-2xl w-full md:w-[560px] p-8 md:p-12 shadow-[0_1px_2px_rgba(0,0,0,.06),0_16px_40px_-12px_rgba(0,0,0,.25)] animate-fade-in-up">

            <!-- Wrapper dalam (Metronic: px-lg-10 pb-15 pb-lg-20) -->
            <div class="lg:px-8 pb-8 lg:pb-14">

                <?php if (! empty($error)): ?>
                <!-- Fallback error inline (path aktif saat ini: FlashHelper → SweetAlert via footer) -->
                <div class="mb-8 flex items-start gap-2.5 rounded-[15px] bg-red-500/[.07] border border-red-500/20 px-4 py-3 text-[13px] text-red-600 dark:text-red-400">
                    <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
                <?php endif; ?>

                <form action="/login" method="POST" class="w-full">

                    <!-- Heading (rata kiri sesuai permintaan) -->
                    <div class="mb-6">
                        <h1 data-i18n="login.sign_in" class="text-[22px] font-bold tracking-tight text-black/90 dark:text-white/95">Sign In</h1>
                    </div>

                    <!-- Username (Metronic: fv-row mb-8, input form-control besar radius .95rem) -->
                    <div class="mb-5">
                        <div class="relative">
                        <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                        <input type="text" name="username" required autocomplete="username" autofocus
                            data-i18n-placeholder="login.username"
                            placeholder="Username"
                            class="w-full rounded-[15px] border border-black/15 dark:border-white/15 bg-transparent px-4 pl-11 h-[54px] text-[17px] font-medium text-black/80 dark:text-white/85 outline-none placeholder:text-black/35 dark:placeholder:text-white/30 focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="relative">
                        <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                        <input type="password" id="login-password" name="password" required autocomplete="current-password"
                            data-i18n-placeholder="login.password"
                            placeholder="Password"
                            class="w-full rounded-[15px] border border-black/15 dark:border-white/15 bg-transparent px-4 pl-11 pr-12 h-[54px] text-[17px] font-medium text-black/80 dark:text-white/85 outline-none placeholder:text-black/35 dark:placeholder:text-white/30 focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                        <button type="button" id="toggle-pass" aria-label="Toggle password visibility"
                            class="absolute inset-y-0 right-0 flex items-center px-4 text-black/40 dark:text-white/40 hover:text-black/70 dark:hover:text-white/70 transition-colors">
                            <span class="relative block w-[18px] h-[18px]">
                                <i data-lucide="eye" class="pass-icon-eye absolute inset-0 transition-[opacity,transform,filter] duration-200 ease-out"></i>
                                <i data-lucide="eye-off" class="pass-icon-off absolute inset-0 opacity-0 scale-[.25] blur-[4px] transition-[opacity,transform,filter] duration-200 ease-out"></i>
                            </span>
                        </button>
                    </div>

                    <!-- Submit (Metronic: d-grid; dengan loading state) -->
                    <div class="mt-7">
                        <button type="submit" id="login-submit"
                            class="w-full h-[54px] rounded-[15px] bg-[#5f7f67] hover:bg-[#6b8b73] active:scale-[0.98] text-white text-[17px] font-semibold transition-[background-color,transform] duration-150 ease-out disabled:opacity-70 disabled:cursor-wait">
                            <span id="login-btn-label" data-i18n="login.sign_in" class="inline-flex items-center justify-center gap-2">Sign In</span>
                            <span id="login-btn-spinner" class="hidden items-center justify-center gap-2">
                                <svg class="animate-spin" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span>Signing in…</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<style>
    /* Login memakai light mode + bahasa Inggris tetap.
       Switcher tema & bahasa disembunyikan di halaman ini. */
    .fixed.top-4.right-4 { display: none !important; }

    /* Background full-page di LEVEL BODY — pola Metronic creative (body.auth-bg).
       Mencegah bg terang aplikasi bocor di gutter scrollbar / area kosong. */
    body {
        background-color: #5f7f67 !important;
        background-image:
            radial-gradient(ellipse 80% 60% at 15% 10%, rgba(146, 170, 150, .40), transparent 60%),
            radial-gradient(ellipse 70% 55% at 85% 90%, rgba(24, 44, 32, .55), transparent 60%),
            linear-gradient(160deg, #6b8b73 0%, #5f7f67 45%, #47614d 100%);
    }
</style>
<script>
    /* Paksa light mode + bahasa Inggris di halaman login */
    document.documentElement.classList.remove('dark');
    localStorage.setItem('kaiarasa_lang', 'en');
</script>

<script>
(function () {
    // Toggle visibilitas password
    var btn = document.getElementById('toggle-pass');
    if (btn) {
        var eye = btn.querySelector('.pass-icon-eye');
        var eyeOff = btn.querySelector('.pass-icon-off');
        btn.addEventListener('click', function () {
            var input = document.getElementById('login-password');
            var isPass = input.type === 'password';
            input.type = isPass ? 'text' : 'password';
            // cross-fade ikon (opacity + scale + blur), tanpa toggle visibility
            eye.classList.toggle('opacity-0', !isPass);
            eye.classList.toggle('scale-[.25]', !isPass);
            eye.classList.toggle('blur-[4px]', !isPass);
            eyeOff.classList.toggle('opacity-0', isPass);
            eyeOff.classList.toggle('scale-[.25]', isPass);
            eyeOff.classList.toggle('blur-[4px]', isPass);
        });
    }

    // Loading state saat submit (UI States #1 + #16)
    var form = document.querySelector('form[action="/login"]');
    if (form) {
        form.addEventListener('submit', function () {
            var submitBtn = document.getElementById('login-submit');
            var label = document.getElementById('login-btn-label');
            var spinner = document.getElementById('login-btn-spinner');
            if (! submitBtn || submitBtn.disabled) return;
            submitBtn.disabled = true;
            label.classList.add('hidden');
            spinner.classList.remove('hidden');
            spinner.classList.add('flex');
        });
    }
})();
</script>

<?php require_once ROOT.'/app/Views/layouts/footer_public.php'; ?>
