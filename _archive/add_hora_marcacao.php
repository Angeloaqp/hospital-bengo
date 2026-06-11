<?php
require 'c:\xampp\htdocs\hospital-bengo\config\database.php';
try {
    $db = Database::ligar();
    $db->exec('ALTER TABLE marcacoes ADD COLUMN hora_marcacao TIME NULL DEFAULT NULL AFTER data_consulta');
    echo "Column added successfully.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
