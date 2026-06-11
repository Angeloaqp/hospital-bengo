<?php
require 'config/database.php';
require 'app/models/Utilizador.php';
$json = file_get_contents('http://localhost/hospital-bengo/app/controllers/agenda_api.php?acao=pesquisar_paciente&q=');
echo $json;
