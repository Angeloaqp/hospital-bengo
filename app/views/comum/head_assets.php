<!-- Tailwind CDN + Plugins -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<!-- Fontes: Manrope (títulos) + Inter (corpo) -->
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<!-- Material Symbols (ícones) -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

<!-- Tactile Editorial CSS -->
<link rel="stylesheet" href="/hospital-bengo/public/css/tactile.css">

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
                lg: "2rem",
                xl: "3rem",
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
</style>

<!-- UX Magic (Etapa 3) -->
<script defer src="/hospital-bengo/public/js/ux.js"></script>

<?php if (isset($_SESSION['mensagem']) && !empty($_SESSION['mensagem'])): ?>
    <meta name="flash-message" content="<?= htmlspecialchars($_SESSION['mensagem']) ?>" data-type="success">
    <?php unset($_SESSION['mensagem']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['erro']) && !empty($_SESSION['erro'])): ?>
    <meta name="flash-message" content="<?= htmlspecialchars($_SESSION['erro']) ?>" data-type="error">
    <?php unset($_SESSION['erro']); ?>
<?php endif; ?>
