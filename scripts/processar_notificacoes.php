<?php
// ================================================
// Hospital Geral do Bengo — Script: Processar Notificações
// Executar via cron ou manualmente:
//   C:\xampp\php\php.exe scripts/processar_notificacoes.php
// ================================================

// Usar o caminho absoluto do projecto
$baseDir = dirname(__DIR__);
require_once $baseDir . '/config/database.php';
require_once $baseDir . '/app/models/Notificacao.php';
require_once $baseDir . '/app/models/Notificador.php';

echo "=== Processamento de Notificações ===\n";
echo "Início: " . date('Y-m-d H:i:s') . "\n\n";

// Buscar pendentes
$pendentes = Notificacao::listarPendentes(50);
$total = count($pendentes);
echo "Notificações pendentes: {$total}\n\n";

if ($total === 0) {
    echo "Nenhuma notificação a processar.\n";
    exit(0);
}

$enviadas = 0;
$falhadas = 0;

foreach ($pendentes as $n) {
    echo "#{$n['id']} [{$n['canal']}] → {$n['destino']} ... ";

    $resultado = Notificador::enviar($n);

    if ($resultado['sucesso']) {
        Notificacao::marcarEnviada((int) $n['id']);
        echo "ENVIADA ✓\n";
        $enviadas++;
    } else {
        Notificacao::marcarFalhada((int) $n['id'], $resultado['erro']);
        $tentativas = (int) $n['tentativas'] + 1;
        echo "FALHOU (tentativa {$tentativas}/3): {$resultado['erro']}\n";
        $falhadas++;
    }

    // Pequena pausa entre envios para não sobrecarregar gateways
    usleep(200000); // 200ms
}

echo "\n=== Resumo ===\n";
echo "Total processadas: {$total}\n";
echo "Enviadas: {$enviadas}\n";
echo "Falhadas: {$falhadas}\n";
echo "Fim: " . date('Y-m-d H:i:s') . "\n";
