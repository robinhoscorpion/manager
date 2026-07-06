import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

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
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'brand-green': '#5A6B46',
                'brand-blue': '#2C5160',
                'brand-copper': '#A26B45',
                'brand-sand': '#D5C2A4',
                'brand-cream': '#F5F2EB',
            }
        },
    },

    plugins: [forms],
};
