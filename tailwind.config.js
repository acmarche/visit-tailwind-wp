/** @type {import('tailwindcss').Config} */
const defaultTheme = require("tailwindcss/defaultTheme");
module.exports = {
  content: ["./templates/**/*.{js,ts,jsx,html,twig}"],
  theme: {
    extend: {
      fontFamily: {
        montserrat: ["Montserrat", ...defaultTheme.fontFamily.sans],
        "montserrat-medium": [
          "montserrat-medium",
          ...defaultTheme.fontFamily.sans,
        ],
        "montserrat-thin": ["montserrat-thin", ...defaultTheme.fontFamily.sans],
        "montserrat-light": [
          "montserrat-light",
          ...defaultTheme.fontFamily.sans,
        ],
        "montserrat-extra-light": [
          "montserrat-extra-light",
          ...defaultTheme.fontFamily.sans,
        ],
        "montserrat-regular": [
          "montserrat-regular",
          ...defaultTheme.fontFamily.sans,
        ],
        "montserrat-bold": ["montserrat-bold", ...defaultTheme.fontFamily.sans],
        "montserrat-semi-bold": [
          "montserrat-semi-bold",
          ...defaultTheme.fontFamily.sans,
        ],
        "montserrat-extra-bold": [
          "montserrat-extra-bold",
          ...defaultTheme.fontFamily.sans,
        ],
      },
      colors: {
        cta: {
          light: "#fd8383",
          dark: "#487F89FF",
          hover: "#FD83838C",
          green: "#16BA99",
        },
        patrimony: "#AAB7D8FF",
        walk: "#64966FFF",
        art: "#F5CC73FF",
        delicacy: "#EFBFB1FF",
        party: "#EFD7CDFF",
        home: "#E8DACBFF",
        caractere: "#636061",
        borderjf: "#dee2e6",
        pastel: "#e7dacb",
        grey: {
          dark: "#636061FF",
          basic: "#808080FF",
        },
        bglighter: "#ededec",
        body: "#212529",
        carto: {
          main: "#354254",
          pink: "#bd2d86",
          gray300: "#6b7e9b",
          gray200: "#a8b2c1",
          gray100: "#e6e3e3",
          green: "#bdc900",
        },
      },
      boxShadow: {
        topNav: "0 -3px 0 0 #fd8383 inset",
      },
      flex: {
        full: "100% 1 1",
      },
      objectPosition: {
        "top-center": "top center",
        "center-center": "center center",
        "bottom-center": "bottom center",
      },
      keyframes: {
        shimmer: {
          "0%": {
            backgroundPosition: "left",
          },
          "50%": {
            backgroundPosition: "right",
          },
          "100%": {
            backgroundPosition: "left",
          },
        },
        leftjf: {
          "0%": {
            transform: "translateX(-4rem)",
          },
          "50%": {
            transform: "translateX(-2rem)",
          },
          "100%": {
            transform: "translateX(0)",
          },
        },
        bouncejf: {
          "0%": {
            transform: "translateY(-4rem)",
          },
          "50%": {
            transform: " translateY(0)",
          },
          "100%": {
            transform: "translateY(0)",
          },
        },
      },
      animation: {
        shimmer: "6s infinite shimmer ease-in-out",
        bouncejf: "bouncejf linear 2s",
        leftjf: "leftjf linear 2s",
      },
    },
  },
  plugins: [
    require("@tailwindcss/forms"),
    require("@tailwindcss/typography"),
    require("@tailwindcss/aspect-ratio"),
  ],
};
