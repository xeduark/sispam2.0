/** @type {import('tailwindcss').Config} */
module.exports = {
    // Se activa junto con el mismo atributo [data-theme="dark"] que ya usa
    // public/assets/js/theme.js (evita tener dos mecanismos de tema distintos).
    darkMode: ['selector', '[data-theme="dark"]'],
    content: [
        './resources/views/**/*.blade.php',
    ],
    // Bootstrap ya se usa en el resto de la app; el preset (reset global de
    // Tailwind) rompería sus estilos en TODAS las páginas, no solo las que
    // migremos. Se apaga y solo se usan utilidades.
    corePlugins: {
        preflight: false,
    },
    theme: {
        extend: {},
    },
    plugins: [],
};
