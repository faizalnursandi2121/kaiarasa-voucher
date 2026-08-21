/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./app/Views/**/*.{html,js,php}",
    "./public/assets/js/**/*.js",
    "./public/js/**/*.js"
  ],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: ['Geist', 'sans-serif'],
        mono: ['Geist Mono', 'monospace'],
      },
      colors: {
        background: 'var(--background)',
        foreground: 'var(--foreground)',
        primary: 'var(--primary)',
        accents: {
          1: 'var(--accents-1)',
          2: 'var(--accents-2)',
          3: 'var(--accents-3)',
          4: 'var(--accents-4)',
          5: 'var(--accents-5)',
          6: 'var(--accents-6)',
          7: 'var(--accents-7)',
          8: 'var(--accents-8)',
        },
        success: '#10b981',
        error: '#ef4444',
        danger: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6',
        question: '#8b5cf6',
      },
      zIndex: {
        'background': '0',
        'content': '10',
        'header': '20',
        'overlay': '30',
        'sidebar': '40',
        'dropdown': '50',
        'modal': '60',
        'toast': '70',
      },
    },
  },
  plugins: [],
}
