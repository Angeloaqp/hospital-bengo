<?php
require 'config/database.php';
$pdo = Database::ligar();

echo "A gerar dados fictícios para o Hospital Geral do Bengo...\n\n";

// -- UTILIZADORES --
$pass = password_hash('Hospital@2025', PASSWORD_DEFAULT);
$medicos = [];
$recepcoes = [];
$admins = [];

$mock_users = [
    ['nome' => 'Dr. Augusto Cândido', 'nome_utilizador' => 'augusto.candido', 'perfil' => 'medico'],
    ['nome' => 'Dra. Maria Luísa',    'nome_utilizador' => 'maria.luisa',     'perfil' => 'medico'],
    ['nome' => 'Dr. Sérgio Miguel',   'nome_utilizador' => 'sergio.miguel',   'perfil' => 'medico'],
    ['nome' => 'Ana Bela',            'nome_utilizador' => 'ana.bela',        'perfil' => 'recepcionista'],
    ['nome' => 'Carlos Sousa',        'nome_utilizador' => 'carlos.sousa',    'perfil' => 'recepcionista'],
    ['nome' => 'Gestor do Sistema',   'nome_utilizador' => 'gestor',          'perfil' => 'admin']
];

foreach ($mock_users as $u) {
    // Tenta inserir
    try {
        $st = $pdo->prepare("INSERT INTO utilizadores (nome, nome_utilizador, senha_hash, perfil) VALUES (?, ?, ?, ?)");
        $st->execute([$u['nome'], $u['nome_utilizador'], $pass, $u['perfil']]);
        $id = $pdo->lastInsertId();
        if ($u['perfil'] == 'medico') $medicos[] = $id;
        if ($u['perfil'] == 'recepcionista') $recepcoes[] = $id;
        if ($u['perfil'] == 'admin') $admins[] = $id;
        echo "Utilizador {$u['nome']} criado com sucesso (Login: {$u['nome_utilizador']}, Senha: Hospital@2025)\n";
    } catch (PDOException $e) {
        // Se já existir, pega o ID
        $st = $pdo->prepare("SELECT id FROM utilizadores WHERE nome_utilizador = ?");
        $st->execute([$u['nome_utilizador']]);
        $id = $st->fetchColumn();
        if ($u['perfil'] == 'medico') $medicos[] = $id;
        if ($u['perfil'] == 'recepcionista') $recepcoes[] = $id;
        if ($u['perfil'] == 'admin') $admins[] = $id;
        echo "(!) {$u['nome']} já existia na base de dados.\n";
    }
}

// Resgatar os defaults para pacientes e senhas
if (empty($recepcoes)) $recepcoes = [1];
$reg_por = $recepcoes[0];

// -- PACIENTES --
$pacientes_mock = [
    ['nome' => 'João Silva', 'idade' => 34, 'morada' => 'Bairro Caxito, Rua 4'],
    ['nome' => 'Marta Sousa', 'idade' => 28, 'morada' => 'Mabubas, Zona Sul'],
    ['nome' => 'Pedro António', 'idade' => 45, 'morada' => 'Panguila, Bloco B'],
    ['nome' => 'Luzia Fernanda', 'idade' => 60, 'morada' => 'Dande Centro'],
    ['nome' => 'Manuel Diogo', 'idade' => 22, 'morada' => 'Barra do Dande'],
    ['nome' => 'Teresa Clara', 'idade' => 12, 'morada' => 'Bairro Caxito, Rua 2'],
    ['nome' => 'Paulo Fernandes', 'idade' => 50, 'morada' => 'Nambuangongo'],
    ['nome' => 'Joana Freitas', 'idade' => 31, 'morada' => 'Bula Atumba'],
];

$pacientes_ids = [];
foreach ($pacientes_mock as $p) {
    try {
        $st = $pdo->prepare("INSERT INTO pacientes (nome, idade, morada, registado_por) VALUES (?, ?, ?, ?)");
        $st->execute([$p['nome'], $p['idade'], $p['morada'], $reg_por]);
        $pacientes_ids[] = $pdo->lastInsertId();
    } catch (Exception $e) {
        $st = $pdo->prepare("SELECT id FROM pacientes WHERE nome = ?");
        $st->execute([$p['nome']]);
        $pacientes_ids[] = $st->fetchColumn();
    }
}

// Mostrar Tipos de Atendimento Disponíveis
$st = $pdo->prepare("SELECT id, nome, prefixo FROM tipos_atendimento");
$st->execute();
$tipos = $st->fetchAll();
if (empty($tipos)) {
    echo "\nERRO: Certifique-se que o dados_iniciais.sql foi importado. Não há tipos_atendimento.\n";
    exit;
}

// Consultorios
$st = $pdo->prepare("SELECT id FROM consultorios");
$st->execute();
$consultorios = $st->fetchAll(PDO::FETCH_COLUMN);
$consultorio_id = !empty($consultorios) ? $consultorios[0] : null;

// -- SENHAS --
echo "\nA gerar historial de filas e senhas...\n";

// Status e Prioridades possíveis:
// Prioridade: 1=Urgente, 2=Idoso, 3=Gravida, 4=Normal
// Estado: 'espera', 'chamada', 'concluida', 'cancelada'

$senhas_gerar = 18; 
for ($i = 0; $i < $senhas_gerar; $i++) {
    // Escoller paciente random
    $pac_id = $pacientes_ids[array_rand($pacientes_ids)];
    
    // Escolher tipo random
    $tipo = $tipos[array_rand($tipos)];
    
    // Escolher prioridade (Mais normais que outros)
    $prios = [4, 4, 4, 4, 3, 2, 1];
    $prioridade = $prios[array_rand($prios)];
    
    // Estado (Maioria em espera)
    $sts = ['espera', 'espera', 'espera', 'espera', 'espera', 'chamada', 'concluida', 'concluida', 'cancelada'];
    $estado = $sts[array_rand($sts)];
    
    // Gerar Código Baseado no tipo prefixo ex: N-021
    $num = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    $codigo = $tipo['prefixo'] . '-' . $num;
    
    // Timing (para aparecer nos relatórios de hoje)
    $horas_atras = rand(0, 5);
    $mins_atras = rand(0, 59);
    $criado = date('Y-m-d H:i:s', strtotime("-$horas_atras hours -$mins_atras minutes"));
    
    try {
        $st = $pdo->prepare("INSERT INTO senhas (codigo, paciente_id, tipo_atendimento_id, prioridade, estado, registado_por, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $st->execute([$codigo, $pac_id, $tipo['id'], $prioridade, $estado, $reg_por, $criado]);
        $senha_id = $pdo->lastInsertId();
        
        // Se a senha não estiver mais em 'espera', simular chamada/conclusão
        if ($estado !== 'espera' && $estado !== 'cancelada') {
            $atendido_por = (!empty($medicos)) ? $medicos[array_rand($medicos)] : null;
            $hora_cha = date('Y-m-d H:i:s', strtotime($criado . ' + ' . rand(5, 45) . ' minutes'));
            
            $hora_con = null;
            if ($estado === 'concluida') {
                $hora_con = date('Y-m-d H:i:s', strtotime($hora_cha . ' + ' . rand(10, 30) . ' minutes'));
            }
            
            $upd = $pdo->prepare("UPDATE senhas SET consultorio_id = ?, atendido_por = ?, hora_chamada = ?, hora_conclusao = ? WHERE id = ?");
            $upd->execute([$consultorio_id, $atendido_por, $hora_cha, $hora_con, $senha_id]);
        }
        
    } catch (Exception $e) {
        // Ignora duplicados no mock
    }
}

echo "\nOperação concluída. O banco de dados agora tem métricas activas para testar relatórios e gráficos.\n";
?>
