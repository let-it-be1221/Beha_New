/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
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
        sans: ['Inter', 'system-ui', 'sans-serif'],
        heading: ['Lora', 'Georgia', 'serif'],
      },
      boxShadow: {
        card: '0 1px 3px rgba(15, 23, 42, .08)',
        lifted: '0 4px 12px rgba(15, 23, 42, .12)',
      },
    },
  },
  plugins: [],
};
