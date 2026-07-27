import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

export default {
    darkMode: 'class', // tema claro forzado: 'class' evita que las utilidades dark: se activen por preferencia del SO
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Albert Sans"', ...defaultTheme.fontFamily.sans],
                display: ['"Hedvig Letters Serif"', 'serif'],
                price: ['"Instrument Serif"', 'serif'],
            },
            colors: {
                // "teal" — remapeado a neutros oscuros ("ink") de Lima América.
                // Antes era la escala teal de Lima View (#15474b/#0f3438); ahora
                // cubre texto/superficies oscuras (header, footer, CTAs oscuros).
                // Mismos nombres de utilidad para no reescribir cada Blade.
                teal: {
                    50:  '#f6f4f2',
                    100: '#ece7e2',
                    200: '#d6cec3',
                    300: '#b3a89a',
                    400: '#8c8073',
                    500: '#6f6a63', // = $lat-muted
                    600: '#524d47',
                    700: '#3a352f', // = $lat-ink-soft
                    800: '#171412', // = $lat-ink (antes "color principal" teal; ahora neutro)
                    900: '#100d0b',
                    950: '#0a0807',
                },
                // "orange" — remapeado al rojo de marca de Lima América
                // (antes acento naranja #e29347; ahora #cb101e / #9d0c16).
                orange: {
                    50:  '#fbeaea', // = $lat-red-tint
                    100: '#f5cbcc',
                    200: '#eb9ea1',
                    300: '#de6d72',
                    400: '#d43e46',
                    500: '#cb101e', // = $lat-red (acento / CTA principal)
                    600: '#9d0c16', // = $lat-red-deep
                    700: '#82060f',
                    800: '#660208',
                    900: '#4a0106',
                },
                // "cream" — remapeado a paper/líneas neutras de Lima América
                // (antes cream cálido; ahora paper #faf7f2 / línea #e9e3da).
                cream: {
                    50:  '#ffffff',
                    100: '#faf7f2', // = $lat-paper
                    200: '#e9e3da', // = $lat-line
                    300: '#dcd4c8', // = $lat-line-strong
                    400: '#c9bfae',
                    500: '#ab9f8c',
                },
                state: {
                    error: '#c81f21',
                    success: '#145212',
                },
                lat: {
                    red:        '#cb101e',
                    'red-deep': '#9d0c16',
                    'red-tint': '#fbeaea',
                    ink:        '#171412',
                    'ink-soft': '#3a352f',
                    paper:      '#faf7f2',
                    surface:    '#ffffff',
                    'surface-2':'#f4efe7',
                    line:       '#e9e3da',
                    'line-strong':'#dcd4c8',
                    muted:      '#6f6a63',
                    wa:         '#25d366',
                },
            },
            container: {
                center: true,
                padding: {
                    DEFAULT: '1rem',
                    sm: '1.5rem',
                    lg: '2rem',
                    xl: '3rem',
                },
                screens: {
                    sm: '640px',
                    md: '768px',
                    lg: '1024px',
                    xl: '1280px',
                    '2xl': '1536px',
                    '3xl': '1716px',
                },
            },
            screens: {
                '3xl': '1800px',
            },
            maxWidth: {
                'container': '1716px',
            },
            borderRadius: {
                pill: '9999px',
            },
        },
    },
    plugins: [forms, typography],
};
