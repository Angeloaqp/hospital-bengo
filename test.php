<?php
require 'config/database.php';
$db = Database::ligar();
$termo = 'angelo';
$stmt = $db->prepare('SELECT id, nome, bi_nif, idade, telefone, email, numero_processo FROM pacientes WHERE nome LIKE :q OR bi_nif LIKE :q2 OR numero_processo LIKE :q3 ORDER BY nome ASC LIMIT 10');
$stmt->execute([':q' => "%{$termo}%", ':q2' => "%{$termo}%", ':q3' => "%{$termo}%"]);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
