<?php require_once ROOT.'/app/Views/layouts/header_main.php'; ?>
<?php require_once ROOT.'/app/Views/layouts/sidebar_session.php'; ?>

<!-- ===== Generate Vouchers (pattern: Home / Add Router Modal) ===== -->
<div class="max-w-4xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
    <!-- Form Column -->
    <div class="lg:col-span-2 min-w-0">
    <div class="rounded-2xl border border-black/[.08] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] shadow-xl overflow-hidden">
        <!-- Panel Header (Sage) -->
        <div class="bg-[#5f7f67] px-6 py-5">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center shrink-0">
                    <i data-lucide="layers" class="w-4 h-4 text-white"></i>
                </div>
                <h3 class="text-base font-bold text-white tracking-tight" data-i18n="hotspot_generate.title">Generate Vouchers</h3>
            </div>
            <p class="text-xs text-white/70 mt-1.5" data-i18n="hotspot_generate.form.subtitle" data-i18n-params='{"name": "<?= htmlspecialchars($session) ?>"}'>Create multiple hotspot vouchers in batch for: <?= htmlspecialchars($session) ?></p>
        </div>

        <form action="/<?= htmlspecialchars($session) ?>/hotspot/generate/process" method="POST" class="space-y-4 p-6">
            <input type="hidden" name="session" value="<?= htmlspecialchars($session) ?>">

            <div class="flex items-center gap-3 pt-1">
                <span class="text-[13px] font-bold uppercase tracking-[0.14em] opacity-90" data-i18n="hotspot_generate.form.core_config">Core Config</span>
                <span class="h-px flex-1 bg-black/[.06] dark:bg-white/[.06]"></span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="gv-qty" class="block text-[11px] font-semibold uppercase tracking-wider opacity-50 mb-2" data-i18n="hotspot_generate.form.qty">Quantity</label>
                    <div class="relative">
                        <input type="number" name="qty" id="gv-qty" value="1" min="1" required placeholder="1"
                            class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-14 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[11px] font-bold uppercase opacity-40 pointer-events-none" data-i18n="hotspot_users.title">Users</span>
                    </div>
                </div>
                <div>
                    <label for="gv-server" class="block text-[11px] font-semibold uppercase tracking-wider opacity-50 mb-2" data-i18n="hotspot_generate.form.server">Server</label>
                    <div class="relative">
                        <i data-lucide="server" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                        <select name="server" id="gv-server"
                            class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-10 pr-9 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition appearance-none">
                            <option value="all">all</option>
                            <?php if (isset($servers) && is_array($servers)) { ?>
                                <?php foreach ($servers as $srv) { ?>
                                    <option value="<?= htmlspecialchars($srv['name']) ?>">
                                        <?= htmlspecialchars($srv['name']) ?>
                                    </option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                        <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="gv-usermode" class="block text-[11px] font-semibold uppercase tracking-wider opacity-50 mb-2" data-i18n="hotspot_generate.form.user_mode">User Mode</label>
                    <div class="relative">
                        <select name="userModel" id="gv-usermode"
                            class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-9 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition appearance-none">
                            <option value="up">Username &amp; Password</option>
                            <option value="vc">Username = Password</option>
                        </select>
                        <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                    </div>
                </div>
                <div>
                    <label for="gv-comment" class="block text-[11px] font-semibold uppercase tracking-wider opacity-50 mb-2" data-i18n="hotspot_generate.form.comment">Comment</label>
                    <div class="relative">
                        <i data-lucide="message-square" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                        <input type="text" name="comment" id="gv-comment" data-i18n-placeholder="hotspot_generate.form.comment_help" placeholder="Batch note..."
                            class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-10 pr-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <span class="text-[13px] font-bold uppercase tracking-[0.14em] opacity-90" data-i18n="hotspot_generate.form.user_format">User Format</span>
                <span class="h-px flex-1 bg-black/[.06] dark:bg-white/[.06]"></span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="gv-length" class="block text-[11px] font-semibold uppercase tracking-wider opacity-50 mb-2" data-i18n="hotspot_generate.form.user_length">Name Length</label>
                    <div class="relative">
                        <select name="userLength" id="gv-length"
                            class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-9 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition appearance-none">
                            <?php for ($i = 3; $i <= 8; $i++) { ?>
                            <option value="<?= $i ?>" <?= $i == 4 ? 'selected' : '' ?>><?= $i ?></option>
                            <?php } ?>
                        </select>
                        <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                    </div>
                </div>
                <div>
                    <label for="gv-prefix" class="block text-[11px] font-semibold uppercase tracking-wider opacity-50 mb-2" data-i18n="hotspot_generate.form.prefix">Prefix</label>
                    <div class="relative">
                        <i data-lucide="type" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                        <input type="text" name="prefix" id="gv-prefix" data-i18n-placeholder="hotspot_generate.form.prefix_placeholder" placeholder="e.g. VIP-"
                            class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-10 pr-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                    </div>
                </div>
            </div>

            <div>
                <label for="gv-char" class="block text-[11px] font-semibold uppercase tracking-wider opacity-50 mb-2" data-i18n="hotspot_generate.form.characters">Character Set</label>
                <div class="relative">
                    <select name="char" id="gv-char"
                        class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-9 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition appearance-none">
                        <option value="lower">abcd (Lower)</option>
                        <option value="upper">ABCD (Upper)</option>
                        <option value="uppernumber">ABCD2345 (Upper + Num)</option>
                        <option value="lowernumber">abcd2345 (Lower + Num)</option>
                        <option value="number">12345 (Numbers)</option>
                        <option value="mix">aBcD2345 (Mix)</option>
                    </select>
                    <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <span class="text-[13px] font-bold uppercase tracking-[0.14em] opacity-90" data-i18n="hotspot_generate.form.limits_profile">Limits &amp; Profile</span>
                <span class="h-px flex-1 bg-black/[.06] dark:bg-white/[.06]"></span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="gv-profile" class="block text-[11px] font-semibold uppercase tracking-wider opacity-50 mb-2" data-i18n="hotspot_generate.form.profile">Profile *</label>
                    <div class="relative">
                        <i data-lucide="layers" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                        <select name="profile" id="gv-profile" required
                            class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-10 pr-9 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition appearance-none">
                            <?php foreach ($profiles as $profile) { ?>
                                <option value="<?= htmlspecialchars($profile['name']) ?>">
                                    <?= htmlspecialchars($profile['name']) ?>
                                </option>
                            <?php } ?>
                        </select>
                        <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                    </div>
                </div>
                <div>
                    <label for="gv-datalimit" class="block text-[11px] font-semibold uppercase tracking-wider opacity-50 mb-2" data-i18n="hotspot_generate.form.data_limit">Data Limit</label>
                    <div class="flex gap-2">
                        <div class="relative flex-1 min-w-0">
                            <i data-lucide="database" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                            <input type="number" name="datalimit_val" id="gv-datalimit" min="0" placeholder="0"
                                class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-10 pr-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                        </div>
                        <div class="relative w-24 shrink-0">
                            <select name="datalimit_unit" aria-label="Data limit unit"
                                class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-9 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition appearance-none">
                                <option value="MB" selected>MB</option>
                                <option value="GB">GB</option>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <span class="block text-[11px] font-semibold uppercase tracking-wider opacity-50 mb-2" data-i18n="hotspot_generate.form.time_limit">Time Limit</span>
                <div class="grid grid-cols-3 gap-2">
                    <div class="relative">
                        <input type="number" name="timelimit_d" min="0" placeholder="0" aria-label="Time limit days"
                            class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-8 text-[14px] text-center outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] font-bold uppercase opacity-40 pointer-events-none">D</span>
                    </div>
                    <div class="relative">
                        <input type="number" name="timelimit_h" min="0" max="23" placeholder="0" aria-label="Time limit hours"
                            class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-8 text-[14px] text-center outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] font-bold uppercase opacity-40 pointer-events-none">H</span>
                    </div>
                    <div class="relative">
                        <input type="number" name="timelimit_m" min="0" max="59" placeholder="0" aria-label="Time limit minutes"
                            class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-8 text-[14px] text-center outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] font-bold uppercase opacity-40 pointer-events-none">M</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-5 border-t border-black/[.06] dark:border-white/[.06]">
                <a href="/<?= htmlspecialchars($session) ?>/hotspot/users"
                    class="h-10 px-4 inline-flex items-center rounded-xl border border-black/10 dark:border-white/10 text-[13px] font-semibold hover:bg-black/[.03] dark:hover:bg-white/[.05] transition-colors" data-i18n="common.cancel">Cancel</a>
                <button type="submit"
                    class="h-10 px-5 rounded-xl bg-[#5f7f67] hover:bg-[#6b8b73] text-white text-[13px] font-semibold transition-colors disabled:opacity-60 disabled:cursor-wait inline-flex items-center gap-2">
                    <span data-i18n="hotspot_generate.form.generate">Generate Vouchers</span>
                </button>
            </div>
        </form>
    </div>
    </div>

    <!-- Quick Tips Column -->
    <aside class="lg:sticky lg:top-24 self-start">
    <div class="rounded-2xl border border-dashed border-black/[.08] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
        <h3 class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wider opacity-50 mb-3">
            <i data-lucide="lightbulb" class="w-3.5 h-3.5 text-yellow-500"></i>
            <span data-i18n="hotspot_generate.form.quick_tips">Quick Tips</span>
        </h3>
        <ul class="space-y-1.5 text-xs opacity-70 leading-relaxed">
            <li class="flex gap-2">
                <span class="w-1 h-1 rounded-full bg-[#5f7f67] mt-1.5 flex-shrink-0"></span>
                <span data-i18n="hotspot_generate.form.tip_user_mode"><strong>User Mode</strong>: UP (separate), VC (same).</span>
            </li>
            <li class="flex gap-2">
                <span class="w-1 h-1 rounded-full bg-[#5f7f67] mt-1.5 flex-shrink-0"></span>
                <span data-i18n="hotspot_generate.form.tip_format_examples"><strong>Format Examples</strong>: abcd (lower), 1234 (num), Mix (upper/lower/num).</span>
            </li>
            <li class="flex gap-2">
                <span class="w-1 h-1 rounded-full bg-[#5f7f67] mt-1.5 flex-shrink-0"></span>
                <span data-i18n="hotspot_generate.form.tip_limits"><strong>Limits</strong>: Time (e.g. 1h, 30m), Data (e.g. 100MB). Leave empty to use Profile default.</span>
            </li>
        </ul>
    </div>
    </aside>
</div>

<?php require_once ROOT.'/app/Views/layouts/footer_main.php'; ?>