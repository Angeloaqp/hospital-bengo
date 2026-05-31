<?php
// ================================================
// Hospital Geral do Bengo
// URL base do sistema
// ================================================
// Override via variável de ambiente (opcional):
//   HB_BASE_URL — ex: http://meuservidor.ao/hospital-bengo/
// Se não definida, usa o valor padrão do XAMPP local.
// ================================================

define('BASE_URL', getenv('HB_BASE_URL') ?: 'http://localhost/hospital-bengo/');
define('APP_NOME', 'Hospital Heróis do Caxito');
define('APP_VERSAO', '1.1');

// Fuso horário de Angola (WAT — UTC+1)
date_default_timezone_set('Africa/Luanda');

// ================================================
// Formatar datas em Português (Angola)
// Não depende de setlocale — funciona em qualquer SO
// ================================================
function dataFormatoPT($timestamp = null, $formato = 'completo'): string
{
    if ($timestamp === null) $timestamp = time();
    if (is_string($timestamp)) $timestamp = strtotime($timestamp);

    $diasSemana = [
        0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira',
        3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira',
        6 => 'Sábado',
    ];
    $meses = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];

    $dia     = (int) date('j', $timestamp);
    $mes     = (int) date('n', $timestamp);
    $ano     = date('Y', $timestamp);
    $dSemana = (int) date('w', $timestamp);

    switch ($formato) {
        case 'completo':
            return "{$diasSemana[$dSemana]}, {$dia} de {$meses[$mes]} de {$ano}";
        case 'curto':
            return "{$dia} de {$meses[$mes]} de {$ano}";
        case 'dia_mes':
            return "{$dia} de {$meses[$mes]}";
        case 'mes_ano':
            return "{$meses[$mes]} de {$ano}";
        default:
            return "{$diasSemana[$dSemana]}, {$dia} de {$meses[$mes]} de {$ano}";
    }
}
