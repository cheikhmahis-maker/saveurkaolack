/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './**/*.php',
    '!./vendor/**',
    '!./node_modules/**',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
};
