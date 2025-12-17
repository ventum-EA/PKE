/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: '#2C75FF',
        bgDark: '#302E2B',
        bgCard: '#262421',
      }
    },
  },
  plugins: [],
}