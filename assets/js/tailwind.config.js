/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./index.html",
        "./src/**/*.{vue,js,ts,jsx,tsx}",
        "./../../templates/**/*.{twig}",
    ],
    theme: {
        extend: {},
    },
    plugins: [require('@tailwindcss/forms')],
}

