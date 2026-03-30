// ================================================
// Hospital Geral do Bengo — Painel Público
// Polling automático a cada 5 segundos
// ================================================

'use strict';

const INTERVALO_MS = 5000;
const URL_API = '../app/controllers/painel_api.php';

// Guarda o último código chamado para detectar mudanças
let ultimoCodigo = document.getElementById(
    'senha-actual'
)?.textContent?.trim() || '';

// Actualiza o relógio
function actualizarRelogio() {
    const el = document.getElementById('relogio');
    if (!el) return;
    const agora = new Date();
    const h = String(agora.getHours()).padStart(2, '0');
    const m = String(agora.getMinutes()).padStart(2, '0');
    el.textContent = h + ':' + m;
}

// Actualiza timestamp da última actualização
function actualizarTimestamp() {
    const el = document.getElementById(
        'ultima-actualizacao'
    );
    if (!el) return;
    const agora = new Date();
    const h = String(agora.getHours()).padStart(2, '0');
    const m = String(agora.getMinutes()).padStart(2, '0');
    const s = String(agora.getSeconds()).padStart(2, '0');
    el.textContent = 'Última actualização: ' +
        h + ':' + m + ':' + s;
}

// Aplica animação de flash quando há nova chamada
function flashSenha(elemento) {
    elemento.classList.remove('flash-anim');
    void elemento.offsetWidth; // reflow
    elemento.classList.add('flash-anim');
    setTimeout(() => {
        elemento.classList.remove('flash-anim');
    }, 500);
}

// Polling — busca dados actualizados do servidor
async function polling() {
    try {
        const resp = await fetch(
            URL_API + '?t=' + Date.now()
        );
        if (!resp.ok) return;

        const dados = await resp.json();
        actualizarPainel(dados);
        actualizarTimestamp();

    } catch (e) {
        // Falha silenciosa — tenta de novo no próximo ciclo
        console.warn('Painel: erro de polling', e);
    }
}

// Actualiza o DOM com os novos dados
function actualizarPainel(d) {
    // ---- Senha em atendimento ----
    const elSenha = document.getElementById('senha-actual');
    if (elSenha && d.em_chamada) {
        const novoCodigo = d.em_chamada.codigo;
        if (novoCodigo !== ultimoCodigo) {
            elSenha.textContent = novoCodigo;
            elSenha.style.color =
                d.em_chamada.cor || '#60A5FA';
            flashSenha(elSenha);
            ultimoCodigo = novoCodigo;

            // Actualiza consultório
            const elCons = document.getElementById(
                'consultorio-actual'
            );
            if (elCons) {
                elCons.textContent =
                    d.em_chamada.consultorio || '';
            }
        }
    }

    // Recarrega a página a cada 5 ciclos 
    // para actualizar todos os quadrantes
    if (!window._ciclos) window._ciclos = 0;
    window._ciclos++;
    if (window._ciclos >= 5) {
        window._ciclos = 0;
        location.reload();
    }
}

// Inicia
actualizarRelogio();
setInterval(actualizarRelogio, 30000);
setInterval(polling, INTERVALO_MS);
