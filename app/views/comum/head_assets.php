<!-- Tailwind CDN + Plugins -->
<script src="<?= BASE_URL ?>public/assets/js/tailwindcss.js"></script>
<!-- Fontes: Manrope (títulos) + Inter (corpo) -->
<link href="<?= BASE_URL ?>public/assets/css/google_fonts.css" rel="stylesheet"/>
<!-- Material Symbols (ícones) -->
<link href="<?= BASE_URL ?>public/assets/css/material_symbols.css" rel="stylesheet"/>

<!-- Tactile Editorial CSS -->
<link rel="stylesheet" href="/hospital-bengo/public/css/tactile.css?v=<?= time() ?>">

<!-- Tailwind Config: Tactile Editorial Design System -->
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                "background": "#f9f9f9",
                "surface-container-highest": "#e2e2e2",
                "on-primary": "#e5e2e1",
                "surface-container-high": "#e8e8e8",
                "outline": "#777777",
                "surface-dim": "#dadada",
                "surface-container": "#eeeeee",
                "on-error": "#ffffff",
                "primary": "#000000",
                "primary-container": "#3c3b3b",
                "secondary": "#5e5e5e",
                "outline-variant": "#c6c6c6",
                "on-secondary": "#ffffff",
                "surface-variant": "#e2e2e2",
                "surface": "#f9f9f9",
                "on-background": "#1a1c1c",
                "on-surface": "#1a1c1c",
                "surface-container-low": "#f3f3f3",
                "surface-container-lowest": "#ffffff",
                "inverse-surface": "#2f3131",
                "surface-bright": "#f9f9f9",
                "on-surface-variant": "#474747",
                "error": "#ba1a1a",
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
    body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    h1, h2, h3, h4, h5, h6 { font-family: 'Manrope', sans-serif; }

    /* Scrollbar Global & Custom - Mais visível e elegante */
    ::-webkit-scrollbar, .custom-scrollbar::-webkit-scrollbar { width: 10px; height: 10px; }
    ::-webkit-scrollbar-track, .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb, .custom-scrollbar::-webkit-scrollbar-thumb { 
        background-color: #9ca3af; 
        border-radius: 10px; 
        border: 3px solid transparent; 
        background-clip: padding-box; 
    }
    ::-webkit-scrollbar-thumb:hover, .custom-scrollbar::-webkit-scrollbar-thumb:hover { 
        background-color: #6b7280; 
    }

    /* Animações Globais (Páginas, Dashboards e Cartões) */
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .fade-in { animation: fadeIn 0.5s ease-out forwards; }
    .fade-in-delay-1 { animation: fadeIn 0.5s ease-out 0.1s forwards; opacity: 0; }
    .fade-in-delay-2 { animation: fadeIn 0.5s ease-out 0.2s forwards; opacity: 0; }
    .fade-in-delay-3 { animation: fadeIn 0.5s ease-out 0.3s forwards; opacity: 0; }
    .fade-in-delay-4 { animation: fadeIn 0.5s ease-out 0.4s forwards; opacity: 0; }
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
