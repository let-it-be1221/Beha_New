/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './app/View/Components/**/*.php',
    './storage/framework/views/*.php',
  ],
  theme: {
    extend: {
      colors: {
        beha: {
          navy: '#0F3A5F',
          'navy-600': '#145374',
          gold: '#C8A24B',
          'gold-soft': '#E8D7A0',
        },
      },
      fontFamily: {
        sans: ['Inter', 'Noto Sans SC', 'system-ui', 'sans-serif'],
        heading: ['Lora', 'Noto Serif SC', 'Georgia', 'serif'],
      },
      boxShadow: {
        card: '0 1px 3px rgba(15, 23, 42, .08)',
        lifted: '0 4px 12px rgba(15, 23, 42, .12)',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
};
