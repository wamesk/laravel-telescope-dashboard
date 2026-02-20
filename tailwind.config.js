/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/js/**/*.{vue,js}',
        './resources/views/**/*.blade.php',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                telescope: {
                    dark: '#1a1f36',
                    darker: '#151929',
                    sidebar: '#1e2340',
                    card: '#212748',
                    border: '#2d3361',
                    accent: '#7c3aed',
                    'accent-light': '#8b5cf6',
                },
            },
        },
    },
    plugins: [],
};
