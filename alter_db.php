<?php
require_once __DIR__ . '/config/database.php';
try {
    $db = Database::ligar();
    
    // Add the column if it doesn't exist
    $check = $db->query("SHOW COLUMNS FROM pacientes LIKE 'numero_processo'");
    if ($check->rowCount() === 0) {
        $db->exec("ALTER TABLE pacientes ADD COLUMN numero_processo VARCHAR(20) UNIQUE DEFAULT NULL AFTER id");
        echo "Coluna numero_processo adicionada com sucesso.\n";
    } else {
        echo "Coluna numero_processo já existe.\n";
    }

    // Update existing records
    $db->exec("UPDATE pacientes SET numero_processo = CONCAT('PAC-', LPAD(id, 5, '0')) WHERE numero_processo IS NULL");
    echo "Registos atualizados com sucesso.\n";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
