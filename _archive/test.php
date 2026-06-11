<?php
require 'app/config/config.php';
require 'app/config/Database.php';
$db = Database::ligar();
$stmt = $db->query("DESCRIBE senhas");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
