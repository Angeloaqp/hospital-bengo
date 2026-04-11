// ================================================
// Hospital Geral do Bengo
// Integração Chart.js para o Dashboard de Relatórios
// ================================================

document.addEventListener('DOMContentLoaded', () => {

    // Helpers
    const corVerde = 'rgba(22, 163, 74, 0.8)';
    const corVerdeBorda = 'rgb(22, 163, 74)';

    const corVermelho = 'rgba(220, 38, 38, 0.8)';
    const corVermelhoBorda = 'rgb(220, 38, 38)';

    const corAzul = 'rgba(30, 111, 217, 0.8)';
    const corAzulBorda = 'rgb(30, 111, 217)';

    const corAmarelo = 'rgba(217, 119, 6, 0.8)';
    const corAmareloBorda = 'rgb(217, 119, 6)';

    // Opcões globais
    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, sans-serif";
    Chart.defaults.color = '#6B7280';
    const chartOptionsScale = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: '#F3F4F6' } },
            x: { grid: { display: false } }
        }
    };

    // ==========================================
    // 1. Gráfico de Linhas (Periodo)
    // ==========================================
    const ctxPeriodo = document.getElementById('chartPeriodo');
    if (ctxPeriodo && typeof Object.keys(DADOS_PERIODO.labels).length > 0) {
        new Chart(ctxPeriodo.getContext('2d'), {
            type: 'line',
            data: {
                labels: DADOS_PERIODO.labels,
                datasets: [
                    {
                        label: 'Total Acumulado',
                        data: DADOS_PERIODO.total,
                        borderColor: '#9CA3AF',
                        backgroundColor: 'rgba(156, 163, 175, 0.2)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4
                    },
                    {
                        label: 'Concluídos',
                        data: DADOS_PERIODO.concluidos,
                        borderColor: corVerdeBorda,
                        backgroundColor: corVerde,
                        tension: 0.3,
                        pointRadius: 4
                    },
                    {
                        label: 'Cancelados / Ausentes',
                        data: DADOS_PERIODO.cancelados,
                        borderColor: corVermelhoBorda,
                        backgroundColor: corVermelho,
                        tension: 0.3,
                        borderDash: [5, 5],
                        pointRadius: 4
                    }
                ]
            },
            options: chartOptionsScale
        });
    }

    // ==========================================
    // 2. Gráfico Donut (Produtividade dos Médicos)
    // ==========================================
    const ctxProd = document.getElementById('chartProdutividade');
    if (ctxProd && DADOS_PRODUTIVIDADE.labels.length > 0) {

        // Gerar paleta de cores azuis aleatórias a partir do total
        const bgColors = DADOS_PRODUTIVIDADE.labels.map((_, i) => {
            const alpha = 0.9 - (i * 0.1);
            return `rgba(30, 111, 217, ${alpha > 0.2 ? alpha : 0.2})`;
        });

        new Chart(ctxProd.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: DADOS_PRODUTIVIDADE.labels,
                datasets: [{
                    data: DADOS_PRODUTIVIDADE.total,
                    backgroundColor: bgColors,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 12, usePointStyle: true } }
                },
                cutout: '60%'
            }
        });
    }

    // ==========================================
    // 3. Gráfico de Barras (Pico de Horas)
    // ==========================================
    const ctxPico = document.getElementById('chartPico');
    if (ctxPico && DADOS_PICO.labels.length > 0) {
        new Chart(ctxPico.getContext('2d'), {
            type: 'bar',
            data: {
                labels: DADOS_PICO.labels,
                datasets: [{
                    label: 'Volume de Entradas (Tráfego)',
                    data: DADOS_PICO.volume,
                    backgroundColor: corAzul,
                    borderColor: corAzulBorda,
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: chartOptionsScale
        });
    }

});
