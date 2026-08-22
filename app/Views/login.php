<?php
$title = 'Kaiarasa Login';
include ROOT.'/app/Views/layouts/header_public.php';
?>

<!-- Replika anatomi Metronic demo18 creative/sign-in:
     bg full-page (sage solid, tanpa foto) · brand kiri · kartu 600px kanan.
     Nilai spasi mengikuti skala Metronic (mb-11=2.75rem, p-20=5rem, dst). -->

<main class="flex-grow flex flex-col lg:flex-row w-full">

    <!-- ===== KIRI: brand (Metronic: d-flex flex-center w-lg-50 pt-15 px-10) ===== -->
    <section class="lg:w-1/2 flex items-center justify-center pt-15 px-10 lg:pt-0 lg:pb-15">
        <div class="flex flex-col items-center lg:items-start text-center lg:text-left">
            <img src="/assets/img/logo-white.webp" alt="Kaiarasa" class="h-11 w-auto mb-7">
            <h2 class="text-white font-normal text-2xl lg:text-[28px] leading-snug m-0">
                Hotspot Voucher Management
            </h2>
        </div>
    </section>

    <!-- ===== KANAN: kartu form (Metronic: p-12 p-lg-20 justify-end → card w-md-600px p-20 rounded-4) ===== -->
    <section class="lg:flex-column-fluid flex justify-center lg:justify-end items-start lg:items-center p-6 sm:p-12 lg:p-20 w-full lg:w-auto">
        <div class="bg-white dark:bg-[#1a1c19] flex flex-col items-stretch justify-center rounded-2xl w-full md:w-[600px] p-8 md:p-14 lg:p-20 shadow-[0_8px_24px_rgba(0,0,0,.08)]">

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

                    <!-- Heading (Metronic: text-center mb-11, h1 fw-bolder mb-3, sub fs-6 fw-semibold) -->
                    <div class="text-center mb-11">
                        <h1 class="text-[22px] font-bold tracking-tight text-black/90 dark:text-white/95 mb-3">Masuk ke MIVO</h1>
                        <div class="text-black/50 dark:text-white/50 font-semibold text-[15px]">MikroTik Hotspot Manager</div>
                    </div>

                    <!-- Username (Metronic: fv-row mb-8, input form-control besar radius .95rem) -->
                    <div class="mb-8">
                        <input type="text" name="username" required autocomplete="username"
                            data-i18n-placeholder="login.username"
                            placeholder="Username"
                            class="w-full rounded-[15px] border border-black/15 dark:border-white/15 bg-transparent px-4 py-[.775rem] text-[17px] font-medium text-black/80 dark:text-white/85 outline-none placeholder:text-black/35 dark:placeholder:text-white/30 focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                    </div>

                    <!-- Password (Metronic: fv-row mb-3) -->
                    <div class="relative mb-3">
                        <input type="password" id="login-password" name="password" required autocomplete="current-password"
                            data-i18n-placeholder="login.password"
                            placeholder="Password"
                            class="w-full rounded-[15px] border border-black/15 dark:border-white/15 bg-transparent px-4 pr-12 py-[.775rem] text-[17px] font-medium text-black/80 dark:text-white/85 outline-none placeholder:text-black/35 dark:placeholder:text-white/30 focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                        <button type="button" id="toggle-pass" aria-label="Toggle password visibility"
                            class="absolute inset-y-0 right-0 flex items-center px-4 text-black/40 dark:text-white/40 hover:text-black/70 dark:hover:text-white/70 transition-colors">
                            <i data-lucide="eye" class="w-[18px] h-[18px]"></i>
                            <i data-lucide="eye-off" class="w-[18px] h-[18px] hidden"></i>
                        </button>
                    </div>

                    <!-- Submit (Metronic: d-grid mb-10, tombol primary full-width) -->
                    <div class="mt-9 mb-10">
                        <button type="submit" data-i18n="login.sign_in"
                            class="w-full h-12 rounded-[15px] bg-[#5f7f67] hover:bg-[#6b8b73] text-white text-[17px] font-semibold transition-colors">
                            Sign In
                        </button>
                    </div>

                    <!-- Baris bawah ala "Not a Member yet?" (Metronic: center fs-6 fw-semibold gray) -->
                    <div class="text-center font-semibold text-[15px] text-black/45 dark:text-white/45">
                        Lupa password? Hubungi administrator.
                    </div>
                </form>
            </div>

            <!-- Footer kartu (Metronic: d-flex flex-stack px-lg-10) -->
            <div class="flex items-center justify-between lg:px-8 pt-2 text-[11px] text-black/35 dark:text-white/35">
                <span>&copy; <?= date('Y') ?> Kaiarasa</span>
                <span class="uppercase tracking-wider"><?= htmlspecialchars($_SESSION['kaiarasa_lang'] ?? 'en') ?></span>
            </div>
        </div>
    </section>
</main>

<script>
(function () {
    var btn = document.getElementById('toggle-pass');
    if (! btn) return;
    var eye = btn.querySelector('[data-lucide="eye"]');
    var eyeOff = btn.querySelector('[data-lucide="eye-off"]');
    btn.addEventListener('click', function () {
        var input = document.getElementById('login-password');
        var isPass = input.type === 'password';
        input.type = isPass ? 'text' : 'password';
        eye.classList.toggle('hidden', isPass);
        eyeOff.classList.toggle('hidden', ! isPass);
    });
})();
</script>

<?php require_once ROOT.'/app/Views/layouts/footer_public.php'; ?>
