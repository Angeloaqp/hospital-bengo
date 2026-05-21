<?php
require 'config/database.php';
$db = Database::ligar();
$stmt = $db->query('DESCRIBE utilizadores');
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo implode("\n", $cols);
