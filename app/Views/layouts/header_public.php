<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="theme-color" content="#fafaf8"> <!-- light-only -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/img/favicon.png" />
    <title><?php

use App\Config\SiteConfig;
use App\Core\Hooks;

?><?= $title ?? SiteConfig::getTitle() ?></title>
    <!-- Tailwind CSS (Local) -->
    <link rel="stylesheet" href="/assets/css/styles.css?v=<?= filemtime(ROOT.'/public/assets/css/styles.css') ?>">
    <script src="/assets/js/lucide.min.js" defer></script>
    <script src="/assets/js/sweetalert2.all.min.js" defer></script>
    <script src="/assets/js/kaiarasa.js" defer></script>
    <script src="/assets/js/modules/alert.js" defer></script>
    <script src="/assets/js/modules/i18n.js" defer></script>
    <style>
        /* Custom Keyframes */
        @keyframes fade-in-up {
            0% { opacity: 0; transform: translateY(10px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fade-in-up 0.4s ease-out forwards;
        }
    </style>
    <script>
        // Light-only lock — dark mode dinonaktifkan permanen
        document.documentElement.classList.remove('dark');
        try { localStorage.removeItem('theme'); } catch (e) {}
    </script>
    <?php Hooks::doAction('kaiarasa_head'); ?>
</head>
<body class="bg-background text-foreground antialiased min-h-screen relative overflow-x-hidden font-sans selection:bg-accents-2 selection:text-foreground flex flex-col">
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <!-- Background Elements (Common) -->
    <div class="absolute inset-0 z-0 pointer-events-none">
        <!-- Subtle Grid Pattern -->
        <div class="absolute inset-0 bg-gradient-to-b from-black/[.02] to-transparent dark:from-white/[.02]"></div>
    </div>

    <!-- Top Right Controls (Pill Theme Toggle & Lang Switcher) -->
    <div class="fixed top-4 right-4 z-50 flex items-center space-x-3">
         <!-- Language Switcher -->
         <div class="relative group">
            <button onclick="toggleMenu('lang-dropdown-public', this)" class="h-9 px-3 rounded-full bg-background/50 backdrop-blur-md border border-accents-2 hover:border-foreground/20 text-accents-5 hover:text-foreground transition-all flex items-center shadow-sm">
                <i data-lucide="globe" class="w-4 h-4 mr-2"></i>
                <span class="text-xs font-semibold uppercase tracking-wider" id="current-lang-label">EN</span>
                <i data-lucide="chevron-down" class="w-3 h-3 ml-2 opacity-50"></i>
            </button>
            <!-- Dropdown -->
            <div id="lang-dropdown-public" class="hidden absolute right-0 mt-2 w-32 bg-background/95 backdrop-blur-2xl border border-accents-2 dark:border-white/10 rounded-xl shadow-2xl py-1 z-50 transform origin-top-right transition-all duration-200" onmouseleave="closeMenu('lang-dropdown-public')">
                <button onclick="changeLanguage('en')" class="w-full text-left px-4 py-2 text-xs font-medium text-accents-5 hover:text-foreground hover:bg-white/5 flex items-center group">
                    <span class="mr-2 text-lg">🇺🇸</span> English
                </button>
                <button onclick="changeLanguage('id')" class="w-full text-left px-4 py-2 text-xs font-medium text-accents-5 hover:text-foreground hover:bg-white/5 flex items-center group">
                    <span class="mr-2 text-lg">🇮🇩</span> Indonesia
                </button>
            </div>
        </div>


    </div>

    <script>
        // Toggle Menu Helper (Reuse or define for public if main footer not loaded)
        // Public footer includes site config footer, but maybe not main JS.
        // Let's define simple toggle for public page to be safe and independent.
        function toggleMenu(id, btn) {
            const el = document.getElementById(id);
            if (!el) return;
            const isHidden = el.classList.contains('hidden');
            
            // Close others if needed (optional)
            
            if (isHidden) {
                el.classList.remove('hidden', 'scale-95', 'opacity-0');
                el.classList.add('scale-100', 'opacity-100');
            } else {
                closeMenu(id);
            }
        }

        function closeMenu(id) {
            const el = document.getElementById(id);
            if (el && !el.classList.contains('hidden')) {
                el.classList.remove('scale-100', 'opacity-100');
                el.classList.add('hidden', 'scale-95', 'opacity-0');
            }
        }
    
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();

            // Theme Logic
            const glider = document.getElementById('theme-glider');
            const btnLight = document.getElementById('btn-light');
            const btnDark = document.getElementById('btn-dark');
            const htmlElement = document.documentElement;

            window.setTheme = () => {
                // Light-only lock — abaikan tema apapun yang diminta
                htmlElement.classList.remove('dark');
            };

            // Language Init (Mock)
            const currentLang = localStorage.getItem('kaiarasa_lang') || 'en';
            const langLabel = document.getElementById('current-lang-label');
            if(langLabel) langLabel.innerText = currentLang.toUpperCase();
            
            window.changeLanguage = (lang) => {
                 localStorage.setItem('kaiarasa_lang', lang);
                 // Also set cookie so PHP can render translations server-side (prevents FOUC)
                 document.cookie = 'kaiarasa_lang=' + lang + ';path=/;max-age=31536000;SameSite=Lax';
                 // Reload or use i18n module to reload
                 location.reload(); 
            };
        });
    </script>
