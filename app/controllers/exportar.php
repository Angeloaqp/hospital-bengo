<?php
// ================================================
// Hospital Geral do Bengo
// Exportação de Dados para CSV / PDF
// ================================================

require_once __DIR__ . '/../../config/base_url.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sessao.php';
require_once __DIR__ . '/../../app/models/Estatistica.php';

// Segurança apenas para admin (com acesso estrito aos dados consolidados)
exigirPerfil(['admin']);

$acao = $_GET['acao'] ?? '';
$di = $_GET['di'] ?? date('Y-m-d', strtotime('-7 days'));
$df = $_GET['df'] ?? date('Y-m-d');

if ($acao === 'csv_medicos') {
    $dados = Estatistica::porMedico($di, $df);

    // Cabecalhos do ficheiro
    $nomeFicheiro = "Relatorio_Medicos_{$di}_a_{$df}.csv";

    // Força o download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nomeFicheiro . '"');

    // Output para stdout
    $output = fopen('php://output', 'w');

    // Bom (Byte Order Mark) para UTF-8 ser lido correctamente no Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Cabeçalho das colunas do CSV
    fputcsv($output, ['ID Médico', 'Nome do Médico', 'Pacientes Atendidos', 'Média de Espera (Minutos)', 'Pacientes Únicos (Retenção)'], ';');

    // Dados de cada médico iterados
    foreach ($dados as $medico) {
        fputcsv($output, [
            $medico['id'],
            $medico['medico'],
            $medico['total_atendidos'],
            round($medico['tempo_medio_espera']),
            $medico['pacientes_unicos']
        ], ';');
    }

    fclose($output);
    exit;
}

// Se não achar a acção 
die("Ação de exportação inválida.");
