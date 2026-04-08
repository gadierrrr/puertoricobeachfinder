/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.php",
    "./public/assets/js/**/*.js"
  ],
  safelist: [
    // Hero filter chip per-category colors (dynamically assembled in index.php)
    'bg-emerald-500/20', 'border-emerald-400/40', 'hover:bg-emerald-500/30', 'hover:border-emerald-400/60', 'text-emerald-300/70',
    'bg-teal-500/20', 'border-teal-400/40', 'hover:bg-teal-500/30', 'hover:border-teal-400/60', 'text-teal-300/70',
    'bg-amber-500/20', 'border-amber-400/40', 'hover:bg-amber-500/30', 'hover:border-amber-400/60', 'text-amber-300/70',
    'bg-rose-500/20', 'border-rose-400/40', 'hover:bg-rose-500/30', 'hover:border-rose-400/60', 'text-rose-300/70',
  ],
  darkMode: ['selector', '[data-theme="dark"]'],
  theme: {
    extend: {
      fontFamily: {
        'sans': ['"DM Sans"', 'Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
        'serif': ['"DM Serif Display"', 'Playfair Display', 'Georgia', 'serif'],
      },
      colors: {
        // Tropical Daytime palette
        'ocean': {
          50:  '#eefbfb',
          100: '#d4f5f5',
          200: '#a3e8ea',
          300: '#5fd4d8',
          400: '#22b8bf',
          500: '#0e9ca3',
          600: '#0a7f86',
          700: '#0d656c',
          800: '#105258',
          900: '#0c3d42',
        },
        'sand': {
          50:  '#fffcf7',
          100: '#fef7ec',
          200: '#fcecd0',
          300: '#f8d9a5',
        },
        'sunset': {
          300: '#fdba74',
          400: '#fb923c',
          500: '#f97316',
          600: '#ea580c',
        },
        'coral': {
          400: '#f77171',
          500: '#ef4444',
          600: '#dc2626',
        },
        'palm': {
          400: '#4ade80',
          500: '#22c55e',
          600: '#16a34a',
          700: '#15803d',
        },
        // Warm stone grays
        'warm': {
          50:  '#fafaf9',
          100: '#f5f5f4',
          200: '#e7e5e4',
          300: '#d6d3d1',
          400: '#a8a29e',
          500: '#78716c',
          600: '#57534e',
          700: '#44403c',
          800: '#292524',
          900: '#1c1917',
        },
      },
      borderRadius: {
        'xl': '1rem',
        '2xl': '1.5rem',
        '3xl': '2rem',
      },
      boxShadow: {
        'card': '0 2px 8px rgba(0, 0, 0, 0.06), 0 0 0 1px rgba(0, 0, 0, 0.04)',
        'card-hover': '0 8px 30px rgba(0, 0, 0, 0.1)',
        'soft': '0 1px 2px rgba(0, 0, 0, 0.05)',
        'soft-lg': '0 4px 12px rgba(0, 0, 0, 0.08)',
        'sunny': '0 4px 14px rgba(14, 156, 163, 0.2)',
        'search': '0 12px 40px rgba(0, 0, 0, 0.18), 0 0 0 1px rgba(0, 0, 0, 0.04)',
      },
      backgroundImage: {
        'hero-gradient': 'linear-gradient(0deg, rgba(12,61,66,0.88) 0%, rgba(12,61,66,0.55) 30%, rgba(12,61,66,0.15) 55%, rgba(12,61,66,0) 75%)',
      },
      animation: {
        'fade-in-up': 'fade-in-up 0.6s ease-out forwards',
        'bounce-slow': 'bounce 2s infinite',
      },
      keyframes: {
        'fade-in-up': {
          '0%': { opacity: '0', transform: 'translateY(20px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
      },
      // Animation delay utilities (use as delay-100, delay-200, etc.)
      animationDelay: {
        '100': '100ms',
        '200': '200ms',
        '300': '300ms',
        '400': '400ms',
        '500': '500ms',
        '600': '600ms',
      },
    },
  },
  plugins: [
    // Animation delay plugin
    function({ matchUtilities, theme }) {
      matchUtilities(
        {
          'delay': (value) => ({
            animationDelay: value,
          }),
        },
        { values: theme('animationDelay') }
      )
    },
  ],
}
