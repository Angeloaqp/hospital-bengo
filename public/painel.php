<?php
// ================================================
// Hospital Geral do Bengo
// Painel Público — Sala de Espera (TV)
// Acesso público — sem autenticação necessária
// ================================================

require_once __DIR__ . '/../config/base_url.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/models/Senha.php';

$emChamada = Senha::emChamadaAgora();
$proximas = Senha::proximasParaPainel(3);
$concluidas = Senha::ultimasConcluidas(3);
$canceladas = Senha::ultimasCanceladas(2);
$tempoMedio = Senha::tempoMedioPublico();
$emEspera = Senha::contarPorEstado('espera');

// Cores por prioridade para o painel
$coresSenha = [
    1 => '#EF4444',  // Urgente — vermelho vivo
    2 => '#F59E0B',  // Idoso — âmbar
    3 => '#A78BFA',  // Grávida — roxo suave
    4 => '#60A5FA',  // Normal — azul claro
];
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel — <?= APP_NOME ?></title>
    <link rel="stylesheet" href="assets/css/painel.css">
</head>

<body>

    <!-- HEADER DO HOSPITAL -->
    <header class="painel-header">
        <div class="hospital-nome">
            <?= htmlspecialchars(APP_NOME) ?>
        </div>
        <div class="painel-hora" id="relogio">
            <?= date('H:i') ?>
        </div>
    </header>

    <!-- ZONA PRINCIPAL: EM ATENDIMENTO -->
    <section class="zona-principal">

        <?php if ($emChamada):
            $cor = $coresSenha[$emChamada['prioridade']]
                ?? '#60A5FA';
            ?>
            <div class="em-atendimento-card" style="border-color: <?= $cor ?>">
                <div class="ea-label">Em atendimento agora</div>
                <div class="ea-senha" id="senha-actual" style="color: <?= $cor ?>" data-id="<?= $emChamada['codigo'] ?>">
                    <?= htmlspecialchars($emChamada['codigo']) ?>
                </div>

                <?php
                // Formata o nome para Primeira e Última palavra apenas
                $nomePartes = explode(' ', trim($emChamada['paciente_nome'] ?? ''));
                $nomeFormatado = $nomePartes[0];
                if (count($nomePartes) > 1) {
                    $nomeFormatado .= ' ' . end($nomePartes);
                }
                ?>
                <div class="ea-paciente" style="font-size: 24px; font-weight: 600; color: #4B5563; margin-bottom: 8px;">
                    <?= htmlspecialchars($nomeFormatado) ?>
                    <span style="font-size:18px; color:#9CA3AF; font-weight: 500;">
                        <?= $emChamada['paciente_idade'] ? "({$emChamada['paciente_idade']} anos)" : '' ?>
                    </span>
                </div>
                <div class="ea-consultorio">
                    Dirija-se ao
                    <strong>
                        <?= htmlspecialchars(
                            $emChamada['consultorio']
                            ?? 'Consultório'
                        ) ?>
                    </strong>
                </div>
            </div>

        <?php else: ?>
            <div class="em-atendimento-card sem-chamada">
                <div class="ea-label">Sistema activo</div>
                <div class="ea-senha" style="color:#4B5563;font-size:48px">
                    —
                </div>
                <div class="ea-consultorio">
                    Aguardando chamada de paciente
                </div>
            </div>
        <?php endif; ?>

    </section>

    <!-- ZONA SECUNDÁRIA: GRID DE 4 QUADRANTES -->
    <section class="zona-grid">

        <!-- QUADRANTE 1: A SEGUIR -->
        <div class="quadrante">
            <div class="quad-titulo seguir">
                A seguir
            </div>
            <?php if (!empty($proximas)): ?>
                <?php foreach ($proximas as $s):
                    $cor = $coresSenha[$s['prioridade']]
                        ?? '#60A5FA';
                    ?>
                    <div class="quad-item">
                        <span class="quad-senha" style="color:<?= $cor ?>">
                            <?= htmlspecialchars($s['codigo']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="quad-vazio">
                    Sem senhas em espera
                </div>
            <?php endif; ?>
        </div>

        <!-- QUADRANTE 2: TOTAL EM ESPERA -->
        <div class="quadrante quadrante-contador">
            <div class="quad-titulo">Em espera</div>
            <div class="contador-num">
                <?= $emEspera ?>
            </div>
            <div class="contador-desc">
                paciente(s) aguardam
            </div>
            <?php if ($tempoMedio > 0): ?>
                <div class="tempo-medio">
                    Tempo médio: ~<?= $tempoMedio ?> min
                </div>
            <?php endif; ?>
        </div>

        <!-- QUADRANTE 3: JÁ ATENDIDOS -->
        <div class="quadrante">
            <div class="quad-titulo concluido">
                Já atendidos
            </div>
            <?php if (!empty($concluidas)): ?>
                <?php foreach ($concluidas as $s): ?>
                    <div class="quad-item">
                        <span class="quad-senha concluido-senha">
                            <?= htmlspecialchars($s['codigo']) ?>
                        </span>
                        <span class="quad-estado concluido-tag">
                            ✓
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="quad-vazio">
                    Nenhum atendimento concluído
                </div>
            <?php endif; ?>
        </div>

        <!-- QUADRANTE 4: CANCELADAS / AUSENTES -->
        <div class="quadrante">
            <div class="quad-titulo cancelado">
                Ausentes
            </div>
            <?php if (!empty($canceladas)): ?>
                <?php foreach ($canceladas as $s): ?>
                    <div class="quad-item">
                        <span class="quad-senha cancelado-senha">
                            <?= htmlspecialchars($s['codigo']) ?>
                        </span>
                        <span class="quad-estado cancelado-tag">
                            ✗
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="quad-vazio">
                    Sem ausências registadas
                </div>
            <?php endif; ?>
        </div>

    </section>

    <!-- RODAPÉ -->
    <footer class="painel-footer">
        <span>
            Actualização automática a cada 5 segundos
        </span>
        <span id="ultima-actualizacao">
            Última actualização: <?= date('H:i:s') ?>
        </span>
    </footer>

    <script src="assets/js/painel.js"></script>
</body>

</html>