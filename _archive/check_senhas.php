<?php
require 'c:\xampp\htdocs\hospital-bengo\config\database.php';
$db = Database::ligar();
$q = $db->query('DESCRIBE senhas');
print_r($q->fetchAll(PDO::FETCH_ASSOC));
