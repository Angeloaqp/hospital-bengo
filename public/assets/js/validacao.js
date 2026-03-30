// ================================================
// Hospital Geral do Bengo — Validação do formulário
// ================================================

document.addEventListener('DOMContentLoaded', () => {
    const form   = document.getElementById('form-login');
    const btn    = document.getElementById('btn-entrar');
    const campos = form.querySelectorAll('input[required]');

    // Desactiva botão enquanto campos estão vazios
    function verificarCampos() {
        const preenchidos = [...campos].every(
            c => c.value.trim() !== ''
        );
        btn.disabled = !preenchidos;
    }

    campos.forEach(c => {
        c.addEventListener('input', verificarCampos);
    });

    verificarCampos();

    // Feedback de loading ao submeter
    form.addEventListener('submit', () => {
        btn.disabled   = true;
        btn.textContent = 'A entrar...';
    });
});
