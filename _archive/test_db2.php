<?php
require 'config/database.php';
require 'app/models/Senha.php';
$medicoId = 4; // We need to know a valid medico id. Let's find one.
$db = Database::ligar();
$stmt = $db->query("SELECT id, nome, perfil FROM utilizadores WHERE perfil = 'medico' LIMIT 1");
$medico = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Médico: " . print_r($medico, true) . "\n";

if ($medico) {
    $fila = Senha::filaDoMedico($medico['id']);
    echo "Fila:\n";
    print_r($fila);
    
    // Also dump all "espera" senhas regardless of medico:
    $stmt2 = $db->query("SELECT s.id, s.codigo, s.origem, s.estado, s.prioridade, m.data_consulta, m.medico_id, ta.especialidade_id FROM senhas s LEFT JOIN marcacoes m ON s.marcacao_id = m.id LEFT JOIN tipos_atendimento ta ON s.tipo_atendimento_id = ta.id WHERE s.estado = 'espera'");
    echo "\nTodas em espera:\n";
    print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
}
