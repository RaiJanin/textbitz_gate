import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import safelist from './resources/js/tailwind/safelist.js';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            animation: {
                'pulse-fast': 'pulse 1s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            },
        },
    },

    safelist,

    plugins: [forms],

    darkMode: 'class'
};
