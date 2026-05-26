<?php
$json = file_get_contents('http://localhost/hospital-bengo/app/controllers/agenda_api.php?acao=pesquisar_paciente&q=angelo');
if ($json === false) {
    echo "FAILED TO FETCH";
} else {
    echo "LENGTH: " . strlen($json) . "\n";
    echo "CONTENT: " . $json;
    var_dump(json_decode($json, true));
}
