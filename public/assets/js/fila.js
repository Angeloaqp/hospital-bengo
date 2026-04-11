// =======================================================
// Hospital do Bengo
// fila.js: Polling Assíncrono e Gráfico da Recepção
// =======================================================

document.addEventListener('DOMContentLoaded', () => {

    /* 1. MÓDULO DO GRÁFICO (SPARKLINE DAS ÚLTIMAS HORAS) */
    const ctxFluxo = document.getElementById('graficoFluxo');
    if (ctxFluxo && typeof DADOS_FLUXO !== 'undefined') {
        new Chart(ctxFluxo.getContext('2d'), {
            type: 'line',
            data: {
                labels: DADOS_FLUXO.labels,
                datasets: [{
                    label: 'Senhas Tiradas',
                    data: DADOS_FLUXO.data,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderColor: '#3B82F6',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 0,
                    pointHoverRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { display: true, ticks: { maxTicksLimit: 5, font: { size: 10 } }, grid: { display: false } },
                    y: { display: false, beginAtZero: true }
                },
                layout: { padding: 0 }
            }
        });
    }

    /* 2. MÓDULO DE POLLING SILENCIOSO (AJAX) */

    // Memoriza a última senha que estava a ser chamada quando a página carregou
    let memNode = document.getElementById('memoria-chamada');
    let ultimaChamadaConhecida = memNode ? memNode.getAttribute('data-codigo') : '';

    // Som de chamada discreto da Recepção
    const somRecepcao = new Audio('/hospital-bengo/public/assets/audio/chamada.mp3');
    somRecepcao.volume = 0.5;

    // Função de notificação visual
    function tocarNotificacaoChamada(codigo, nome) {
        somRecepcao.play().catch(e => console.log('Bloqueado pelo browser (necessita interacção prévia).'));

        const toast = document.getElementById('notificacao-chamada');
        if (toast) {
            document.getElementById('notif-senha').innerText = codigo;
            document.getElementById('notif-paciente').innerText = nome;

            toast.classList.add('mostrar');
            setTimeout(() => { toast.classList.remove('mostrar'); }, 6000);
        }
    }

    setInterval(async () => {
        // Ignora actualizações se estivermos a escrever alguma coisa (foco num input)
        if (document.activeElement && (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA')) {
            return;
        }

        try {
            const resposta = await fetch(window.location.href);
            if (!resposta.ok) return;

            const textoHTML = await resposta.text();
            const parser = new DOMParser();
            const docVirtual = parser.parseFromString(textoHTML, 'text/html');

            // --- 1. Substitui as Métricas ---
            const novasMetricas = docVirtual.querySelector('.metricas');
            const antigasMetricas = document.querySelector('.metricas');
            if (novasMetricas && antigasMetricas) antigasMetricas.innerHTML = novasMetricas.innerHTML;

            // --- 2. Substitui a Tabela / Fila de Espera ---
            const novaFila = docVirtual.querySelector('.coluna-principal .card');
            const antigaFila = document.querySelector('.coluna-principal .card');
            if (novaFila && antigaFila) antigaFila.innerHTML = novaFila.innerHTML;

            // --- 3. Substitui os Alertas (Urgência e Pico de Fila) ---
            document.querySelectorAll('.alerta:not(.alerta-sucesso)').forEach(a => a.remove());
            const novasAlertas = docVirtual.querySelectorAll('.alerta:not(.alerta-sucesso)');
            if (novasAlertas.length > 0) {
                const header = document.querySelector('.page-header');
                if (header) {
                    for (let i = novasAlertas.length - 1; i >= 0; i--) {
                        header.insertAdjacentElement('afterend', novasAlertas[i]);
                    }
                }
            }

            // --- 4. Detecta a nova chamada oculta ---
            const memoriaNova = docVirtual.querySelector('#memoria-chamada');
            if (memoriaNova) {
                const novoCodigo = memoriaNova.getAttribute('data-codigo');
                const novoNome = memoriaNova.getAttribute('data-paciente');

                if (novoCodigo && novoCodigo !== ultimaChamadaConhecida) {
                    ultimaChamadaConhecida = novoCodigo;
                    tocarNotificacaoChamada(novoCodigo, novoNome);
                }
            }

        } catch (erro) {
            console.error('Erro de Polling silencioso:', erro);
        }
    }, 10000); // Executa a verificação a cada 10 segundos
});
