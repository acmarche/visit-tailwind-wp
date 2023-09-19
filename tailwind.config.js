/** @type {import('tailwindcss').Config} */
const defaultTheme = require('tailwindcss/defaultTheme');
module.exports = {
  content: [
         "./templates/**/*.{js,ts,jsx,html,twig}",
  ],
  theme: {
        extend: {
            fontFamily: {
                montserrat: ["Montserrat", ...defaultTheme.fontFamily.sans],
                "montserrat-medium": [
                    "montserrat-medium",
                    ...defaultTheme.fontFamily.sans
                ],
                "montserrat-thin": ["montserrat-thin", ...defaultTheme.fontFamily.sans],
                "montserrat-light": [
                    "montserrat-light",
                    ...defaultTheme.fontFamily.sans
                ],
                "montserrat-extra-light": [
                    "montserrat-extra-light",
                    ...defaultTheme.fontFamily.sans
                ],
                "montserrat-regular": [
                    "montserrat-regular",
                    ...defaultTheme.fontFamily.sans
                ],
                "montserrat-bold": ["montserrat-bold", ...defaultTheme.fontFamily.sans],
                "montserrat-semi-bold": [
                    "montserrat-semi-bold",
                    ...defaultTheme.fontFamily.sans
                ],
                "montserrat-extra-bold": [
                    "montserrat-extra-bold",
                    ...defaultTheme.fontFamily.sans
                ]
            },
            colors: {
                cta: {
                    light: '#fd8383',
                    dark: '#487F89FF',
                    hover: '#FD83838C',
                    green: '#16BA99',
                },
                patrimony: '#AAB7D8FF',
                walk: '#64966FFF',
                art: '#F5CC73FF',
                delicacy: '#EFBFB1FF',
                party: '#EFD7CDFF',
                home: '#E8DACBFF',
                caractere: '#636061',
                borderjf: '#dee2e6',
                pastel: '#e7dacb',
                grey: {
                    dark: '#636061FF',
                    basic: '#808080FF',
                },
                bglighter: '#ededec',
                body: '#212529'
            },
            boxShadow: {
                topNav: '0 -3px 0 0 #fd8383 inset',
            },
            flex: {
                full: '100% 1 1',
            },
            objectPosition: {
                'top-center': 'top center',
                'center-center': 'center center',
                'bottom-center': 'bottom center',
            }
        },
    },
    plugins: [
        require("@tailwindcss/forms"),
        require("@tailwindcss/typography"),
        require("@tailwindcss/aspect-ratio")
    ],
}

