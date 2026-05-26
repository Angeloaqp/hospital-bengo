<?php
require 'config/database.php';
$db = Database::ligar();
$stmt = $db->query('SELECT COUNT(*) FROM pacientes WHERE nome IS NULL');
print_r($stmt->fetchColumn());
