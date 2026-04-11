<?php
// ================================================
// Hospital Geral do Bengo
// Vista: O Meu Perfil (Dashboard Pessoal & Hub)
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../app/models/Utilizador.php';

// Segurança
exigirPerfil(['admin', 'medico', 'recepcionista']);

$meuId = (int) sessao('utilizador_id');
$meuPerfil = sessao('perfil');

// Obter Dados Sensíveis e Métricas
$dados = Utilizador::obter($meuId);
$estatisticas = Utilizador::estatisticasPessoais($meuId, $meuPerfil);
$historico = Utilizador::ultimasAccoes($meuId, $meuPerfil);

// Helpers visuais baseados no cargo
$corCargoBackground = '#F3F4F6'; // Default
$corCargoText = '#111827';
$iconeCargo = 'person';

if ($meuPerfil === 'medico') {
    $corCargoBackground = '#EFF6FF'; // Azul
    $corCargoText = '#1D4ED8';
    $iconeCargo = 'stethoscope';
} elseif ($meuPerfil === 'recepcionista') {
    $corCargoBackground = '#FEF3C7'; // Amarelo/Laranja
    $corCargoText = '#B45309';
    $iconeCargo = 'front_desk';
} elseif ($meuPerfil === 'admin') {
    $corCargoBackground = '#FEE2E2'; // Vermelho
    $corCargoText = '#991B1B';
    $iconeCargo = 'admin_panel_settings';
}

$criadoA = isset($dados['criado_em']) ? date('d/m/Y', strtotime($dados['criado_em'])) : 'Indisponível';
$ultimoAcesso = isset($dados['ultimo_acesso']) ? date('d/m/Y H:i', strtotime($dados['ultimo_acesso'])) : 'Sessão Atual'; // Corrigido p. fallback se não existir na db
$_username = $dados['nome_utilizador'] ?? $dados['username'] ?? sessao('nome_utilizador') ?? 'admin';
$_fotoPath = $dados['foto_path'] ?? '';
$_inicial = strtoupper(substr($dados['nome'] ?? 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>O Meu Perfil — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <style>
        /* Gradiente abstrato para a capa do Perfil */
        .profile-cover {
            background: linear-gradient(135deg, rgba(230,230,230,1) 0%, rgba(245,245,245,1) 100%);
            height: 160px;
            border-radius: 1.5rem 1.5rem 0 0;
            position: relative;
        }
    </style>
</head>

<body class="text-on-surface">

    <?php $paginaActual = 'perfil'; ?>
    <?php include __DIR__ . '/../comum/sidebar.php'; ?>

    <?php $tituloPagina = 'Hub Pessoal'; ?>
    <?php include __DIR__ . '/../comum/header.php'; ?>

    <div class="ml-56 mt-28 p-8 flex justify-center">
        <main class="w-full max-w-[1200px]">

            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-3xl font-extrabold text-black tracking-tight">O Meu Perfil</h2>
                    <p class="text-on-surface-variant font-semibold mt-1 text-sm">Controlo de identidade, atalhos rápidos e desempenho.</p>
                </div>
            </div>

            <!-- GRID PRINCIPAL DE LAYOUT: Esquerda (Identity & Stats) -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                
                <!-- COLUNA 1 & 2 (80% da importância) -->
                <div class="xl:col-span-2 flex flex-col gap-8">
                    
                    <!-- CARTÃO HERO / COVER -->
                    <div class="floating-card bg-white rounded-[1.5rem]">
                        <div class="profile-cover"></div>
                        <div class="p-8 relative">
                            <!-- Avatar Flutuante -->
                            <div class="absolute -top-16 left-8 w-32 h-32 rounded-full border-4 border-white bg-black flex items-center justify-center overflow-hidden shadow-sm">
                                <?php if (!empty($_fotoPath)): ?>
                                    <img src="<?= BASE_URL . 'public/' . $_fotoPath ?>" class="w-full h-full object-cover" alt="Foto Avatar">
                                <?php else: ?>
                                    <span class="text-white text-5xl font-extrabold"><?= $_inicial ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="mt-16 flex items-start justify-between">
                                <div>
                                    <h1 class="text-4xl font-black text-black tracking-tight"><?= htmlspecialchars($dados['nome']) ?></h1>
                                    <p class="text-on-surface-variant font-medium text-lg mt-1">@<?= htmlspecialchars($_username) ?></p>
                                    
                                    <div class="flex items-center gap-3 mt-4">
                                        <!-- BADGE CARGO -->
                                        <div class="px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest flex items-center gap-2" style="background-color: <?= $corCargoBackground ?>; color: <?= $corCargoText ?>;">
                                            <span class="material-symbols-outlined text-[16px]"><?= $iconeCargo ?></span>
                                            <?= htmlspecialchars(ucfirst($meuPerfil)) ?>
                                        </div>
                                        
                                        <!-- BADGE STATUS -->
                                        <div class="px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest flex items-center gap-2 bg-emerald-50 text-emerald-700">
                                            <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                            Acesso Ativo
                                        </div>
                                    </div>
                                </div>

                                <!-- Botão Direto de Editar Personalizado -->
                                <div>
                                    <a href="editar.php" class="bg-black text-white px-6 py-3 rounded-full font-bold text-sm hover:bg-neutral-800 transition-colors flex items-center gap-2 shadow-lg">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                        Editar Definições
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CAIXAS DE PRODUTIVIDADE (Analytics Board) -->
                    <h3 class="text-xl font-extrabold text-black mt-2 mb-2">Desempenho Diário</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- BOX 1: Quantidade processada -->
                        <div class="floating-card bg-white rounded-2xl p-6 flex flex-col justify-between">
                            <div class="w-12 h-12 rounded-full bg-surface-container-low flex items-center justify-center mb-6">
                                <span class="material-symbols-outlined text-black">check_circle</span>
                            </div>
                            <div>
                                <div class="text-5xl font-black text-black tracking-tighter">
                                    <?= htmlspecialchars($estatisticas['hoje']['total'] ?? 0) ?>
                                </div>
                                <div class="text-sm font-semibold text-on-surface-variant mt-2 uppercase tracking-widest">
                                    <?= $meuPerfil === 'medico' ? 'Pacientes Atendidos Hoje' : 'Senhas Geridas Hoje' ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- BOX 2: Fila Pendente Atual (Só para visualizar impacto) -->
                        <div class="floating-card bg-white rounded-2xl p-6 flex flex-col justify-between">
                            <div class="w-12 h-12 rounded-full bg-surface-container-low flex items-center justify-center mb-6">
                                <span class="material-symbols-outlined text-black">hourglass_top</span>
                            </div>
                            <div>
                                <div class="text-5xl font-black text-black tracking-tighter">
                                    <?= htmlspecialchars($estatisticas['pendentes'] ?? 0) ?>
                                </div>
                                <div class="text-sm font-semibold text-on-surface-variant mt-2 uppercase tracking-widest">
                                    Pacientes em Espera (Geral)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUNA 3 (Sidebar Esquerda de Acções) -->
                <div class="flex flex-col gap-8">
                    
                    <!-- CARTÃO DE SEGURANÇA / INFO CIDADÃO -->
                    <div class="floating-card bg-white rounded-[1.5rem] p-6">
                        <h4 class="text-sm font-extrabold text-black uppercase tracking-widest mb-6 border-b border-surface-container-high pb-4">Segurança & Registo</h4>
                        
                        <div class="flex flex-col gap-5">
                            <div>
                                <div class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-1">Membro Desde</div>
                                <div class="text-sm font-semibold text-black flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[16px] text-outline">calendar_month</span>
                                    <?= $criadoA ?>
                                </div>
                            </div>
                            
                            <div>
                                <div class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-1">Último Acesso</div>
                                <div class="text-sm font-semibold text-black flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[16px] text-outline">login</span>
                                    <?= $ultimoAcesso ?>
                                </div>
                            </div>

                            <a href="editar.php" class="mt-4 bg-surface-container-low text-black w-full py-2.5 rounded-full font-bold text-xs hover:bg-surface-container transition-colors text-center">
                                Alterar Palavra-passe
                            </a>
                        </div>
                    </div>

                    <!-- CARTÃO ACTION FEED / MICRO LOG -->
                    <div class="floating-card bg-white rounded-[1.5rem] p-6 flex-1 flex flex-col">
                        <div class="flex items-center justify-between mb-6">
                            <h4 class="text-sm font-extrabold text-black uppercase tracking-widest">Atividade Recente</h4>
                            <a href="historico.php" class="text-xs font-bold text-outline hover:text-black transition-colors">Ver tudo</a>
                        </div>
                        
                        <div class="flex-1">
                            <?php if (empty($historico)): ?>
                                <div class="flex flex-col items-center justify-center h-full text-center py-10 opacity-60">
                                    <span class="material-symbols-outlined text-4xl mb-3 text-outline">history_toggle_off</span>
                                    <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-widest">Ainda não há atividade <br>registada hoje.</p>
                                </div>
                            <?php else: ?>
                                <ul class="space-y-4">
                                    <?php foreach (array_slice($historico, 0, 5) as $accao): ?>
                                        <li class="flex items-start gap-4">
                                            <div class="w-8 h-8 rounded-full bg-surface-container-low flex shadow-sm items-center justify-center shrink-0">
                                                <span class="material-symbols-outlined text-[14px] text-black">
                                                    <?php 
                                                        // Escolhe icone conforme titulo da acao se possível
                                                        if (stripos($accao['descricao'] ?? $accao['accao'] ?? '', 'senha') !== false) {
                                                            echo 'receipt_long';
                                                        } elseif (stripos($accao['descricao'] ?? $accao['accao'] ?? '', 'atendimento') !== false) {
                                                            echo 'clinical_notes';
                                                        } else {
                                                            echo 'bolt';
                                                        }
                                                    ?>
                                                </span>
                                            </div>
                                            <div class="flex-1">
                                                <div class="text-sm font-bold text-black leading-tight"><?= htmlspecialchars($accao['descricao'] ?? $accao['accao'] ?? 'Ação do sistema') ?></div>
                                                <div class="text-[10px] font-semibold text-outline tracking-wider uppercase mt-1">
                                                    <?= date('H:i - d/m/Y', strtotime($accao['data_hora'] ?? 'now')) ?>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
            </div>

        </main>
    </div>

</body>
</html>
