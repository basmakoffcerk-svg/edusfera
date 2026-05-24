import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './app/Filament/**/*.php',
        './resources/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                primary: '#84CC16', // lime-500
                secondary: '#FACC15', // yellow-400
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [],
};
