module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
  ],
  plugins: [
    require('daisyui'),
  ],
  daisyui: {
    themes: ["light"],
  },
}