import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    // Class-based dark mode: the command center defaults to the dark theme
    // (deep neutral canvas + glass surfaces) with a light-mode toggle.
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/views/**/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                // ---- KoriePay brand (static) ----
                'brand': '#158987',       // primary teal  (informational)
                'brand-2': '#29B475',     // secondary green (healthy/positive)
                'brand-orange': '#F88D25',// operational warning
                'brand-gold': '#FCCB1A',  // attention

                // ---- Semantic status (static) ----
                'ok': '#22C55E',
                'warn': '#FCCB1A',
                'alert': '#F88D25',
                'crit': '#EF4444',
                'info': '#38BDF8',

                // ---- Theme-aware surfaces & text (RGB triplets, overridden in .dark) ----
                // Usage: bg-canvas, bg-panel, text-ink, text-muted, border-line
                'canvas': 'rgb(var(--c-bg) / <alpha-value>)',
                'panel': 'rgb(var(--c-surface) / <alpha-value>)',
                'panel-2': 'rgb(var(--c-surface-2) / <alpha-value>)',
                'ink': 'rgb(var(--c-ink) / <alpha-value>)',
                'muted': 'rgb(var(--c-muted) / <alpha-value>)',
                'faint': 'rgb(var(--c-faint) / <alpha-value>)',
                'line': 'rgb(var(--c-line) / <alpha-value>)',
                'brand-soft': 'rgb(var(--c-brand-soft) / <alpha-value>)',
                'ok-soft': 'rgb(var(--c-ok-soft) / <alpha-value>)',
                'alert-soft': 'rgb(var(--c-alert-soft) / <alpha-value>)',
                'crit-soft': 'rgb(var(--c-crit-soft) / <alpha-value>)',
            },
            fontFamily: {
                // System-first stack: zero network dependency, low-bandwidth friendly.
                sans: ['system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
                mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'Consolas', 'monospace'],
            },
            boxShadow: {
                'glass': '0 1px 2px rgba(2, 6, 23, 0.06), 0 8px 24px -12px rgba(2, 6, 23, 0.12)',
                'glass-dark': '0 1px 2px rgba(0, 0, 0, 0.4), 0 12px 32px -16px rgba(0, 0, 0, 0.6)',
                'glow-brand': '0 0 0 1px rgba(21, 137, 135, 0.25), 0 8px 24px -12px rgba(21, 137, 135, 0.35)',
            },
            animation: {
                'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'fade-in': 'fadeIn 0.25s ease-out both',
                'fade-up': 'fadeUp 0.35s cubic-bezier(0.22, 1, 0.36, 1) both',
                'scale-in': 'scaleIn 0.18s cubic-bezier(0.22, 1, 0.36, 1) both',
                'shimmer': 'shimmer 1.6s linear infinite',
            },
            keyframes: {
                fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                fadeUp: { '0%': { opacity: '0', transform: 'translateY(6px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                scaleIn: { '0%': { opacity: '0', transform: 'scale(0.97)' }, '100%': { opacity: '1', transform: 'scale(1)' } },
                shimmer: { '0%': { backgroundPosition: '-200% 0' }, '100%': { backgroundPosition: '200% 0' } },
            },
            transitionTimingFunction: {
                'glass': 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
        },
    },

    plugins: [forms],
};
