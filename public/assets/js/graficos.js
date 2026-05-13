// ================================================
// Hospital Geral do Bengo
// Gráficos Premium — Tactile Editorial (Chart.js)
// ================================================

document.addEventListener('DOMContentLoaded', () => {

    // ── Design Tokens ──────────────────────────────
    const PRETO        = '#111827';
    const CINZA_700    = '#374151';
    const CINZA_400    = '#9ca3af';
    const CINZA_200    = '#e5e7eb';
    const CINZA_100    = '#f3f4f6';
    const VERDE        = '#16a34a';
    const VERDE_SOFT   = 'rgba(22, 163, 74, 0.15)';
    const VERMELHO     = '#dc2626';
    const VERMELHO_SOFT= 'rgba(220, 38, 38, 0.10)';

    // Paleta sofisticada para doughnut (tons escuros intercalados)
    const PALETA_MEDICOS = [
        '#111827', '#374151', '#6b7280', '#9ca3af',
        '#1e40af', '#3b82f6', '#60a5fa', '#93c5fd',
        '#0f766e', '#14b8a6', '#5eead4', '#a7f3d0'
    ];

    // ── Global Defaults ────────────────────────────
    Chart.defaults.font.family = "'Inter', 'Manrope', -apple-system, sans-serif";
    Chart.defaults.font.weight = 600;
    Chart.defaults.color = CINZA_400;

    // Tooltip global premium
    Chart.defaults.plugins.tooltip.backgroundColor = PRETO;
    Chart.defaults.plugins.tooltip.titleFont = { size: 13, weight: 700, family: "'Inter', sans-serif" };
    Chart.defaults.plugins.tooltip.bodyFont = { size: 12, weight: 600, family: "'Inter', sans-serif" };
    Chart.defaults.plugins.tooltip.padding = 14;
    Chart.defaults.plugins.tooltip.cornerRadius = 12;
    Chart.defaults.plugins.tooltip.displayColors = true;
    Chart.defaults.plugins.tooltip.boxPadding = 6;

    // ──────────────────────────────────────────────
    // 1. GRÁFICO DE ÁREA — Fluxo Diário
    // ──────────────────────────────────────────────
    const ctxPeriodo = document.getElementById('chartPeriodo');
    if (ctxPeriodo && DADOS_PERIODO.labels && DADOS_PERIODO.labels.length > 0) {

        // Gradientes para área preenchida
        const ctx1 = ctxPeriodo.getContext('2d');
        const gradVerde = ctx1.createLinearGradient(0, 0, 0, 300);
        gradVerde.addColorStop(0, VERDE_SOFT);
        gradVerde.addColorStop(1, 'rgba(22, 163, 74, 0)');

        const gradVermelho = ctx1.createLinearGradient(0, 0, 0, 300);
        gradVermelho.addColorStop(0, VERMELHO_SOFT);
        gradVermelho.addColorStop(1, 'rgba(220, 38, 38, 0)');

        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: DADOS_PERIODO.labels.map(d => {
                    const dt = new Date(d + 'T00:00:00');
                    return dt.toLocaleDateString('pt', { day: '2-digit', month: 'short' });
                }),
                datasets: [
                    {
                        label: 'Concluídos',
                        data: DADOS_PERIODO.concluidos,
                        borderColor: VERDE,
                        backgroundColor: gradVerde,
                        fill: true,
                        tension: 0.45,
                        borderWidth: 3,
                        pointRadius: 5,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: VERDE,
                        pointBorderWidth: 3,
                        pointHoverRadius: 8,
                        pointHoverBorderWidth: 3
                    },
                    {
                        label: 'Cancelados',
                        data: DADOS_PERIODO.cancelados,
                        borderColor: VERMELHO,
                        backgroundColor: gradVermelho,
                        fill: true,
                        tension: 0.45,
                        borderWidth: 2.5,
                        borderDash: [6, 4],
                        pointRadius: 4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: VERMELHO,
                        pointBorderWidth: 2.5,
                        pointHoverRadius: 7
                    },
                    {
                        label: 'Total Emitido',
                        data: DADOS_PERIODO.total,
                        borderColor: CINZA_400,
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0.45,
                        borderWidth: 2,
                        borderDash: [3, 3],
                        pointRadius: 3,
                        pointBackgroundColor: CINZA_400,
                        pointBorderColor: CINZA_400,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 20,
                            font: { size: 11, weight: 700 }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: CINZA_100, drawBorder: false },
                        ticks: { stepSize: 1, padding: 10, font: { size: 11, weight: 700 } }
                    },
                    x: {
                        border: { display: false },
                        grid: { display: false },
                        ticks: { padding: 8, font: { size: 11, weight: 700 } }
                    }
                },
                animation: { duration: 1200, easing: 'easeOutQuart' }
            }
        });
    }

    // ──────────────────────────────────────────────
    // 2. GRÁFICO DONUT — Produtividade por Médico
    // ──────────────────────────────────────────────
    const ctxProd = document.getElementById('chartProdutividade');
    if (ctxProd && DADOS_PRODUTIVIDADE.labels && DADOS_PRODUTIVIDADE.labels.length > 0) {

        const cores = DADOS_PRODUTIVIDADE.labels.map((_, i) => PALETA_MEDICOS[i % PALETA_MEDICOS.length]);

        new Chart(ctxProd.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: DADOS_PRODUTIVIDADE.labels,
                datasets: [{
                    data: DADOS_PRODUTIVIDADE.total,
                    backgroundColor: cores,
                    borderWidth: 4,
                    borderColor: '#ffffff',
                    hoverOffset: 12,
                    hoverBorderWidth: 0,
                    spacing: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 16,
                            font: { size: 11, weight: 700 },
                            color: PRETO
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? Math.round((ctx.parsed / total) * 100) : 0;
                                return ` ${ctx.label}: ${ctx.parsed} pacientes (${pct}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    duration: 1400,
                    easing: 'easeOutCirc'
                }
            },
            plugins: [{
                // Plugin interno: Texto central do donut
                id: 'centerText',
                beforeDraw(chart) {
                    const { ctx, width, height } = chart;
                    const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    // Número grande
                    ctx.font = `900 36px 'Inter', sans-serif`;
                    ctx.fillStyle = PRETO;
                    ctx.fillText(total, width / 2, height / 2 - 8);

                    // Label pequeno
                    ctx.font = `700 10px 'Inter', sans-serif`;
                    ctx.fillStyle = CINZA_400;
                    ctx.fillText('TOTAL', width / 2, height / 2 + 16);

                    ctx.restore();
                }
            }]
        });
    }

    // ──────────────────────────────────────────────
    // 3. GRÁFICO DE BARRAS — Pico de Horas
    // ──────────────────────────────────────────────
    const ctxPico = document.getElementById('chartPico');
    if (ctxPico && DADOS_PICO.labels && DADOS_PICO.labels.length > 0) {

        const ctx3 = ctxPico.getContext('2d');

        // Gradiente vertical preto → cinza
        const gradBarra = ctx3.createLinearGradient(0, 0, 0, 300);
        gradBarra.addColorStop(0, PRETO);
        gradBarra.addColorStop(1, CINZA_700);

        new Chart(ctx3, {
            type: 'bar',
            data: {
                labels: DADOS_PICO.labels,
                datasets: [{
                    label: 'Volume de Entradas',
                    data: DADOS_PICO.volume,
                    backgroundColor: gradBarra,
                    hoverBackgroundColor: PRETO,
                    borderRadius: 10,
                    borderSkipped: false,
                    barThickness: 'flex',
                    maxBarThickness: 48,
                    minBarLength: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: (items) => `Horário: ${items[0].label}`,
                            label: (ctx) => ` ${ctx.parsed.y} pacientes registados`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: CINZA_100, drawBorder: false },
                        ticks: { stepSize: 1, padding: 10, font: { size: 11, weight: 700 } }
                    },
                    x: {
                        border: { display: false },
                        grid: { display: false },
                        ticks: { padding: 8, font: { size: 11, weight: 700 } }
                    }
                },
                animation: {
                    y: { duration: 1200, easing: 'easeOutQuart' }
                }
            }
        });
    }

    // ──────────────────────────────────────────────
    // 4. GRÁFICO DONUT — Faixas Etárias (Demografia)
    // ──────────────────────────────────────────────
    const ctxIdade = document.getElementById('chartIdade');
    if (ctxIdade && typeof DADOS_IDADE !== 'undefined' && DADOS_IDADE.data) {

        const totalIdade = DADOS_IDADE.data.reduce((a, b) => a + b, 0);

        new Chart(ctxIdade.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: DADOS_IDADE.labels,
                datasets: [{
                    data: DADOS_IDADE.data,
                    backgroundColor: DADOS_IDADE.cores,
                    borderWidth: 3,
                    borderColor: '#ffffff',
                    hoverOffset: 10,
                    spacing: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const pct = totalIdade > 0 ? Math.round((ctx.parsed / totalIdade) * 100) : 0;
                                return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    duration: 1200,
                    easing: 'easeOutCirc'
                }
            },
            plugins: [{
                id: 'centerTextIdade',
                beforeDraw(chart) {
                    const { ctx, width, height } = chart;
                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    ctx.font = `900 28px 'Inter', sans-serif`;
                    ctx.fillStyle = PRETO;
                    ctx.fillText(totalIdade, width / 2, height / 2 - 6);

                    ctx.font = `700 9px 'Inter', sans-serif`;
                    ctx.fillStyle = CINZA_400;
                    ctx.fillText('PACIENTES', width / 2, height / 2 + 14);

                    ctx.restore();
                }
            }]
        });
    }

});
