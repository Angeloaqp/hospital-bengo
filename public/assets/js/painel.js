/* ================================================
   Hospital Geral do Bengo
   Lógica de actualização automática do Painel (TV)
================================================ */

document.addEventListener('DOMContentLoaded', () => {

    // Configurações
    const INTERVALO_ACTUALIZACAO = 5000; // 5 segundos

    // Armazena a senha que está a ser apresentada actualmente
    // para podermos detectar quando muda
    const elemSenha = document.getElementById('senha-actual');
    let senhaActualMemoria = elemSenha ? elemSenha.getAttribute('data-id') : null;

    // Função que toca o som de notificação (Fase 8)
    function tocarCampainha() {
        try {
            // Tenta reproduzir o ficheiro que descarregámos
            const audio = new Audio('assets/audio/chamada.mp3');
            audio.play().catch(e => {
                console.warn("Navegador bloqueou auto-play de áudio. " +
                    "É necessário clicar na página pelo menos uma vez.", e);
            });
        } catch (e) {
            console.error("Erro ao tocar áudio", e);
        }
    }

    // Função para aplicar animação pulsante visual (Fase 8)
    function pulsarPainel() {
        const cardAtendimento = document.querySelector('.em-atendimento-card');
        if (cardAtendimento) {
            // Adiciona a class que faz a animação pulsar-card
            cardAtendimento.classList.add('chamada-pulsante');

            // Remove a animação após 5 segundos
            setTimeout(() => {
                cardAtendimento.classList.remove('chamada-pulsante');
            }, 5000);
        }
    }

    // Actualização do relógio a cada segundo
    setInterval(() => {
        const relogio = document.getElementById('relogio');
        if (relogio) {
            const agora = new Date();
            const h = String(agora.getHours()).padStart(2, '0');
            const m = String(agora.getMinutes()).padStart(2, '0');
            relogio.textContent = `${h}:${m}`;
        }
    }, 1000);

    // Sistema de polling que actualiza a página de 5 em 5 segundos
    // usando Fetch (AJAX) para ler a própria página e extrair as diferenças.
    // Esta abordagem (HTML replace) é simples mas muito eficaz para painéis em kiosk-mode.
    function actualizarPainel() {
        fetch(window.location.href, { cache: 'no-store' })
            .then(res => res.text())
            .then(html => {
                // Cria um DOM virtual para analisar o resultado
                const parser = new DOMParser();
                const vDOM = parser.parseFromString(html, 'text/html');

                // Actualiza zona principal
                const zonaPrincipalNova = vDOM.querySelector('.zona-principal');
                if (zonaPrincipalNova) {
                    document.querySelector('.zona-principal').innerHTML = zonaPrincipalNova.innerHTML;
                }

                // Actualiza a grid de acompanhamento (concluidas, canceladas, espera)
                const zonaGridNova = vDOM.querySelector('.zona-grid');
                if (zonaGridNova) {
                    document.querySelector('.zona-grid').innerHTML = zonaGridNova.innerHTML;
                }

                // Actualiza a última actualização
                const ultimaAct = vDOM.getElementById('ultima-actualizacao');
                if (ultimaAct) {
                    document.getElementById('ultima-actualizacao').innerHTML = ultimaAct.innerHTML;
                }

                // --------- DETECTA NOVA CHAMADA (Fase 8) ---------
                const novoElemSenha = document.getElementById('senha-actual');
                if (novoElemSenha) {
                    const novaSenha = novoElemSenha.getAttribute('data-id');

                    if (novaSenha !== null && novaSenha !== senhaActualMemoria) {
                        console.log("Nova chamada detectada:", novaSenha);
                        senhaActualMemoria = novaSenha; // actualiza a memoria

                        // Toca o som "Ding"
                        tocarCampainha();

                        // Pisca o painel "Em Atendimento"
                        pulsarPainel();
                    }
                } else if (!novoElemSenha && senhaActualMemoria !== null) {
                    // Significa que o sistema ficou sem chamadas, reseta
                    senhaActualMemoria = null;
                }
                // -------------------------------------------------
            })
            .catch(err => console.error("Erro na actualização AJAX do painel:", err));
    }

    // Inicia a actualização periódica
    setInterval(actualizarPainel, INTERVALO_ACTUALIZACAO);

    // Detectar interação inicial no documento para permitir autoplay de audio
    document.addEventListener('click', function initAudio() {
        console.log("Interacção detectada, auto-play desbloqueado pelo browser.");
        // Audio pré-carregamento (opcional, só para aquecer o motor do bowser)
        const a = new Audio('assets/audio/chamada.mp3');
        a.volume = 0; a.play().catch(() => { });

        // Remove listener de si próprio para agir apenas no primeiro clique
        document.removeEventListener('click', initAudio);
    });

});
