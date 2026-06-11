<?php
require 'c:\xampp\htdocs\hospital-bengo\config\database.php';
try {
    $db = Database::ligar();
    
    // Check current structure
    $q = $db->query("DESCRIBE senhas");
    $cols = $q->fetchAll(PDO::FETCH_ASSOC);
    echo "Before:\n";
    print_r($cols);
    
    // Alter table
    $db->exec("ALTER TABLE senhas MODIFY codigo VARCHAR(50) NOT NULL;");
    
    // Check after
    $q2 = $db->query("DESCRIBE senhas");
    $cols2 = $q2->fetchAll(PDO::FETCH_ASSOC);
    echo "\nAfter:\n";
    print_r($cols2);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
