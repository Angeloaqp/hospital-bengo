<!-- Paleta Central de Cores (TODAS as cores do sistema vêm daqui) -->
<link rel="stylesheet" href="/hospital-bengo/public/css/colors.css?v=<?= time() ?>">

<!-- Tailwind CDN + Plugins -->
<script src="<?= BASE_URL ?>public/assets/js/tailwindcss.js"></script>
<!-- Fontes: Manrope (títulos) + Inter (corpo) -->
<link href="<?= BASE_URL ?>public/assets/css/google_fonts.css" rel="stylesheet" />
<!-- Material Symbols (ícones) -->
<link href="<?= BASE_URL ?>public/assets/css/material_symbols.css" rel="stylesheet" />

<!-- Tactile Editorial CSS -->
<link rel="stylesheet" href="/hospital-bengo/public/css/tactile.css?v=<?= time() ?>">

<!-- Tailwind Config: Tactile Editorial Design System (referencia variáveis de colors.css) -->
<script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "background": "var(--cor-background)",
                    "surface-container-highest": "var(--cor-surface-container-highest)",
                    "on-primary": "var(--cor-on-primary)",
                    "surface-container-high": "var(--cor-surface-container-high)",
                    "outline": "var(--cor-outline)",
                    "surface-dim": "var(--cor-surface-dim)",
                    "surface-container": "var(--cor-surface-container)",
                    "on-error": "var(--cor-on-error)",
                    "primary": "var(--cor-primary)",
                    "primary-container": "var(--cor-primary-container)",
                    "secondary": "var(--cor-secondary)",
                    "outline-variant": "var(--cor-outline-variant)",
                    "on-secondary": "var(--cor-on-secondary)",
                    "surface-variant": "var(--cor-surface-variant)",
                    "surface": "var(--cor-surface)",
                    "on-background": "var(--cor-on-background)",
                    "on-surface": "var(--cor-on-surface)",
                    "surface-container-low": "var(--cor-surface-container-low)",
                    "surface-container-lowest": "var(--cor-surface-container-lowest)",
                    "inverse-surface": "var(--cor-inverse-surface)",
                    "surface-bright": "var(--cor-surface-bright)",
                    "on-surface-variant": "var(--cor-on-surface-variant)",
                    "error": "var(--cor-error)",
                },
                boxShadow: {
                    "sm": "var(--shadow-sm)",
                    "DEFAULT": "var(--shadow-md)",
                    "md": "var(--shadow-md)",
                    "lg": "var(--shadow-lg)",
                    "xl": "var(--shadow-floating)",
                    "2xl": "var(--shadow-floating)",
                },
                borderRadius: {
                    DEFAULT: "1rem",
                    lg: "1rem",
                    xl: "0.75rem",
                    "2xl": "1rem",
                    "3xl": "1.5rem",
                    full: "9999px"
                },
                fontFamily: {
                    headline: ["Manrope", "sans-serif"],
                    body: ["Inter", "sans-serif"],
                    label: ["Inter", "sans-serif"]
                }
            },
        }
    }
</script>
<style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: var(--cor-background);
        color: var(--cor-text-primary);
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        font-family: 'Manrope', sans-serif;
        color: var(--cor-text-primary);
    }

    /* Scrollbar Global & Custom */
    ::-webkit-scrollbar,
    .custom-scrollbar::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    ::-webkit-scrollbar-track,
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    ::-webkit-scrollbar-thumb,
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: var(--cor-scrollbar);
        border-radius: 10px;
        border: 3px solid transparent;
        background-clip: padding-box;
    }

    ::-webkit-scrollbar-thumb:hover,
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: var(--cor-scrollbar-hover);
    }

    /* Animações Globais (Páginas, Dashboards e Cartões) */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .fade-in { animation: fadeIn 0.5s ease-out forwards; }
    .fade-in-delay-1 { animation: fadeIn 0.5s ease-out 0.1s forwards; opacity: 0; }
    .fade-in-delay-2 { animation: fadeIn 0.5s ease-out 0.2s forwards; opacity: 0; }
    .fade-in-delay-3 { animation: fadeIn 0.5s ease-out 0.3s forwards; opacity: 0; }
    .fade-in-delay-4 { animation: fadeIn 0.5s ease-out 0.4s forwards; opacity: 0; }

    /* Animação única de abertura de página */
    @keyframes simpleFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    body {
        animation: simpleFadeIn 0.4s ease-out forwards;
    }
</style>

<!-- Tactile UX Scripts -->
<script defer src="/hospital-bengo/public/js/ux.js?v=<?= time() ?>"></script>
<script src="/hospital-bengo/public/js/calendar_widget.js?v=<?= time() ?>"></script>

<?php if (isset($_SESSION['mensagem']) && !empty($_SESSION['mensagem'])): ?>
    <meta name="flash-message" content="<?= htmlspecialchars($_SESSION['mensagem']) ?>" data-type="success">
    <?php unset($_SESSION['mensagem']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['erro']) && !empty($_SESSION['erro'])): ?>
    <meta name="flash-message" content="<?= htmlspecialchars($_SESSION['erro']) ?>" data-type="error">
    <?php unset($_SESSION['erro']); ?>
<?php endif; ?>