<?php
require 'c:\xampp\htdocs\hospital-bengo\config\database.php';
$db = Database::ligar();
$q = $db->query("SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='hospital_bengo' AND COLUMN_NAME IN ('genero', 'sexo')");
print_r($q->fetchAll(PDO::FETCH_ASSOC));
