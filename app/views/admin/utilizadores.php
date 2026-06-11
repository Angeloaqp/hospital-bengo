<?php
// ================================================
// Hospital Geral do Bengo
// Gestão de Utilizadores — Admin (Tactile Editorial)
// ================================================

require_once __DIR__ . '/../../../config/base_url.php';
require_once __DIR__ . '/../../../config/sessao.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../app/models/Estatistica.php';

exigirPerfil(['admin']);

$utilizadores = Estatistica::todosUtilizadores();
$mensagem = $_SESSION['mensagem'] ?? '';
$erro = $_SESSION['erro'] ?? '';
unset($_SESSION['mensagem'], $_SESSION['erro']);

// Labels e cores por perfil
$perfilConfig = [
    'admin' => ['label' => 'Administrador', 'icon' => 'shield_person', 'sub' => 'Administração do Sistema'],
    'medico' => ['label' => 'Médico', 'icon' => 'stethoscope', 'sub' => 'Médico Especialista'],
    'recepcionista' => ['label' => 'Recepção', 'icon' => 'badge', 'sub' => 'Atendimento & Triagem'],
];

// Avatar color palette (sofisticada, skeumorphic)
$avatarColors = [
    'bg-primary text-white',
    'bg-blue-600 text-white',
    'bg-purple-600 text-white',
    'bg-emerald-600 text-white',
    'bg-amber-600 text-white',
    'bg-rose-600 text-white',
    'bg-indigo-600 text-white',
    'bg-teal-600 text-white',
];
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilizadores — <?= APP_NOME ?></title>
    <?php include __DIR__ . '/../comum/head_assets.php'; ?>
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: var(--cor-scrollbar-light);
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: var(--cor-scrollbar-light-hover);
        }

        .tactile-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-weight: 800;
            letter-spacing: -0.05em;
        }

        /* User Card — Premium SaaS */
        .user-card {
            background: var(--cor-surface-container-lowest);
            border-radius: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.03);
            transition: all 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .user-card:hover {
            transform: translateY(-8px) scale(1.01);
            box-shadow:
                0 20px 25px -5px rgba(0, 0, 0, 0.05),
                0 10px 10px -5px rgba(0, 0, 0, 0.02),
                0 40px 60px -15px rgba(0, 0, 0, 0.08);
            border-color: rgba(0, 0, 0, 0.06);
        }

        .user-card:hover .btn-primary-action {
            filter: brightness(1.1);
            transform: translateY(-2px);
        }

        .user-card:hover .edit-icon-btn {
            background: var(--cor-toast-bg);
            color: var(--cor-surface-container-lowest);
            transform: rotate(5deg);
        }

        .user-card:hover .meta-icon {
            color: var(--cor-toast-bg);
            transform: scale(1.1);
        }

        /* Button states */
        .btn-action {
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .btn-action:hover {
            filter: brightness(1.05);
        }

        .btn-action:active {
            transform: scale(0.94);
            filter: brightness(0.95);
        }

        /* Status pulse */
        @keyframes pulse-status {
            0% {
                transform: scale(0.9);
                opacity: 0.8;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.4;
            }

            100% {
                transform: scale(0.9);
                opacity: 0.8;
            }
        }

        .animate-pulse-subtle {
            animation: pulse-status 2s infinite ease-in-out;
        }

        /* Premium Glide Entrance */
        @keyframes glideIn {
            0% {
                opacity: 0;
                transform: translateY(40px) scale(0.92);
                filter: blur(8px);
            }

            60% {
                opacity: 1;
                filter: blur(0px);
            }

            80% {
                transform: translateY(-4px) scale(1.01);
            }

            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
                filter: blur(0px);
            }
        }

        .glide-in {
            animation: glideIn 0.9s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
        }

        .stagger-1 {
            animation-delay: 0.06s;
        }

        .stagger-2 {
            animation-delay: 0.15s;
        }

        .stagger-3 {
            animation-delay: 0.24s;
        }

        .stagger-4 {
            animation-delay: 0.33s;
        }

        .stagger-5 {
            animation-delay: 0.42s;
        }

        .stagger-6 {
            animation-delay: 0.51s;
        }

        /* Toast */
        @keyframes toastSlide {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .toast-in {
            animation: toastSlide 0.4s ease-out forwards;
        }

        /* Search */
        .search-input:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.08);
        }

        /* Info cards */
        .info-card {
            transition: all 0.5s ease;
        }

        .info-card:hover {
            background: var(--cor-surface-container-lowest);
        }

        .info-card:hover .info-icon {
            background: var(--cor-toast-bg);
            color: var(--cor-surface-container-lowest);
        }
    </style>
</head>

<body class="text-on-surface bg-background">

    <?php $paginaActual = 'utilizadores'; ?>
    <?php include __DIR__ . '/../comum/sidebar.php'; ?>

    <!-- Header -->
    <?php
    $tituloPagina = 'Gestão de Utilizadores';
    ob_start(); ?>
    <div class="hidden md:flex items-center gap-3">
        <div class="px-3 py-1.5 bg-surface-container-low rounded-full flex items-center gap-1.5 border border-primary/5">
            <span class="material-symbols-outlined text-[14px] text-on-surface-variant">group</span>
            <span
                class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant"><?= count($utilizadores) ?>
                registados</span>
        </div>
    </div>
    <?php $accoesPagina = ob_get_clean(); ?>
    <?php include __DIR__ . '/../comum/header.php'; ?>

    <div class="ml-[17rem] mr-6 mt-28 py-8">
        <main class="w-full">

                <!-- Toast Messages -->
                <?php if ($mensagem): ?>
                    <div class="mb-6 p-4 bg-green-50 rounded-2xl flex items-center gap-3 border border-green-100 toast-in">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-white text-[16px]"
                                style="font-variation-settings: 'FILL' 1;">check</span>
                        </div>
                        <p class="text-sm font-bold text-green-800"><?= htmlspecialchars($mensagem) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($erro): ?>
                    <div class="mb-6 p-4 bg-red-50 rounded-2xl flex items-center gap-3 border border-red-100 toast-in">
                        <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-white text-[16px]">warning</span>
                        </div>
                        <p class="text-sm font-bold text-red-800"><?= htmlspecialchars($erro) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Page Title + CTA -->
                <div class="mb-10 flex justify-between items-end glide-in">
                    <div>
                        <h2 class="text-3xl font-headline font-extrabold text-on-surface tracking-tight">Utilizadores do
                            Sistema</h2>
                        <p class="text-on-surface-variant font-medium mt-1 text-sm">Gerencie permissões e visualize a
                            actividade dos profissionais de saúde.</p>
                    </div>
                    <a href="criar_utilizador.php"
                        class="bg-primary text-white px-8 py-3.5 rounded-full font-bold text-sm flex items-center gap-2 hover:shadow-xl transition-all btn-action">
                        <span class="material-symbols-outlined text-[20px]">person_add</span>
                        Novo Utilizador
                    </a>
                </div>

                <!-- Search Bar -->
                <div class="flex flex-col md:flex-row items-center gap-4 mb-8 glide-in stagger-1">
                    <div
                        class="relative bg-white rounded-[1.5rem] flex flex-col md:flex-row items-center gap-4 p-3 border border-white/50 shadow-sm hover:shadow-md transition-shadow flex-1 w-full md:w-auto">
                        <div class="relative flex-1 w-full">
                            <span
                                class="material-symbols-outlined absolute left-5 top-1/2 -translate-y-1/2 text-on-surface-variant text-xl">search</span>
                            <input type="text" id="searchInput"
                                class="w-full rounded-xl bg-surface-container-low border-none font-semibold placeholder:text-on-surface-variant/50 font-['Manrope'] pl-12 pr-6 py-3 text-sm focus:ring-2 focus:ring-black/10 transition-all outline-none"
                                placeholder="Procurar por nome ou login..." autocomplete="off">
                        </div>
                        <button type="button"
                            class="bg-primary text-white rounded-xl font-black flex items-center gap-2 hover:scale-[1.02] active:scale-[0.98] transition-all shadow-lg h-[46px] px-6 text-sm shrink-0">
                            <span class="material-symbols-outlined text-xl">search</span>
                            Procurar
                        </button>
                    </div>
                    <div class="relative shrink-0 w-full md:w-auto">
                        <?php
                        $sel_id = 'filterPerfil';
                        $sel_name = 'perfil_filtro';
                        $sel_icon = 'badge';
                        $sel_placeholder = 'Todos os perfis';
                        $sel_value = '';
                        $sel_size = 'sm';
                        $sel_options = [
                            '' => ['label' => 'Todos os perfis', 'icon' => 'groups', 'color' => 'text-on-surface-variant'],
                            'admin' => ['label' => 'Administrador', 'icon' => 'shield_person', 'color' => 'text-on-surface'],
                            'medico' => ['label' => 'Médico', 'icon' => 'stethoscope', 'color' => 'text-blue-600'],
                            'recepcionista' => ['label' => 'Recepcionista', 'icon' => 'badge', 'color' => 'text-purple-600'],
                        ];
                        include __DIR__ . '/../comum/custom_select.php';
                        ?>
                    </div>
                </div>

                <!-- Profile Cards Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-12" id="cards-container">
                    <?php foreach ($utilizadores as $idx => $u):
                        $conf = $perfilConfig[$u['perfil']] ?? $perfilConfig['admin'];
                        $isActivo = (int) $u['estado'] === 1;
                        $isSelf = $u['id'] == sessao('utilizador_id');
                        $novoEstado = $isActivo ? 0 : 1;
                        $inicial = strtoupper(substr($u['nome'], 0, 1));
                        $avatarColor = $avatarColors[$idx % count($avatarColors)];
                        $staggerClass = 'stagger-' . min(6, ($idx % 3) + 1);
                        ?>
                        <div class="user-card p-6 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] glide-in <?= $staggerClass ?>"
                            data-nome="<?= strtolower(htmlspecialchars($u['nome'])) ?>"
                            data-login="<?= strtolower(htmlspecialchars($u['nome_utilizador'])) ?>"
                            data-perfil="<?= $u['perfil'] ?>">

                            <!-- Top: Avatar + Name + Edit -->
                            <div class="flex justify-between items-start mb-6">
                                <div class="flex gap-4">
                                    <div class="relative">
                                        <div
                                            class="w-16 h-16 rounded-full <?= $avatarColor ?> flex items-center justify-center text-xl font-black shrink-0 <?= !$isActivo ? 'grayscale opacity-60' : '' ?>">
                                            <?= $inicial ?>
                                        </div>
                                        <?php if ($isActivo): ?>
                                            <div
                                                class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border-2 border-white rounded-full">
                                                <div class="absolute inset-0 bg-green-500 rounded-full animate-pulse-subtle">
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div
                                                class="absolute bottom-0 right-0 w-4 h-4 bg-gray-300 border-2 border-white rounded-full">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex flex-col pt-1">
                                        <h3 class="text-lg font-extrabold text-on-surface leading-tight">
                                            <?= htmlspecialchars($u['nome']) ?>
                                            <?php if ($isSelf): ?>
                                                <span class="text-[10px] font-bold text-blue-500">(eu)</span>
                                            <?php endif; ?>
                                        </h3>
                                        <p class="text-xs text-on-surface-variant font-medium"><?= $conf['sub'] ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Tags -->
                            <div class="flex flex-wrap gap-2 mb-8">
                                <span
                                    class="px-3 py-1 bg-gray-100 text-on-surface text-[10px] font-bold rounded-full"><?= $conf['label'] ?></span>
                                <?php if ($u['especialidade']): ?>
                                    <span
                                        class="px-3 py-1 bg-gray-100 text-on-surface text-[10px] font-bold rounded-full"><?= htmlspecialchars($u['especialidade']) ?></span>
                                <?php endif; ?>
                                <?php if ($u['consultorio']): ?>
                                    <span
                                        class="px-3 py-1 bg-gray-100 text-on-surface text-[10px] font-bold rounded-full"><?= htmlspecialchars($u['consultorio']) ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Metadata Row -->
                            <div class="grid grid-cols-3 gap-0 mb-8 border-t border-gray-50 pt-6">
                                <div class="flex flex-col gap-1 pr-4">
                                    <div class="flex items-center gap-1.5 h-5">
                                        <span
                                            class="material-symbols-outlined text-[16px] text-on-surface-variant/70 transition-all duration-300 meta-icon">alternate_email</span>
                                        <span
                                            class="text-xs font-extrabold text-on-surface truncate">@<?= htmlspecialchars($u['nome_utilizador']) ?></span>
                                    </div>
                                    <span
                                        class="text-[9px] uppercase font-extrabold text-on-surface-variant tracking-[0.1em]">Utilizador</span>
                                </div>
                                <div class="flex flex-col gap-1 px-4 border-x border-primary/5">
                                    <div class="flex items-center h-5">
                                        <span
                                            class="text-xs font-extrabold text-on-surface"><?= $isActivo ? 'Activo' : 'Off' ?></span>
                                    </div>
                                    <span
                                        class="text-[9px] uppercase font-extrabold text-on-surface-variant tracking-[0.1em]">Estado</span>
                                </div>
                                <div class="flex flex-col gap-1 pl-4">
                                    <div class="flex items-center h-5">
                                        <span
                                            class="text-xs font-extrabold text-on-surface"><?= date('d/m/y', strtotime($u['criado_em'])) ?></span>
                                    </div>
                                    <span
                                        class="text-[9px] uppercase font-extrabold text-on-surface-variant tracking-[0.1em]">Registado</span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-3">
                                <button type="button" onclick="carregarPerfilLateral(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nome'])) ?>')"
                                    class="flex-1 bg-primary text-white py-3 rounded-xl font-bold text-xs text-center btn-action btn-primary-action transition-all flex items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-[16px]">bar_chart</span>
                                    Detalhes Rápidos
                                </button>
                                <a href="editar_utilizador.php?id=<?= $u['id'] ?>"
                                    class="w-11 h-11 bg-gray-50 rounded-xl flex items-center justify-center text-on-surface-variant hover:bg-primary hover:text-white transition-all btn-action edit-icon-btn"
                                    title="Editar">
                                    <span class="material-symbols-outlined text-[20px]">edit_note</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Empty State -->
                <div id="emptyState" class="hidden py-16 text-center mb-12">
                    <span
                        class="material-symbols-outlined text-5xl text-surface-container-highest mb-3">search_off</span>
                    <p class="text-on-surface-variant font-bold text-lg">Nenhum utilizador encontrado.</p>
                    <p class="text-sm text-on-surface-variant/60 mt-1">Tente ajustar os filtros de pesquisa.</p>
                </div>

                <!-- Pagination -->
                <div class="flex justify-between items-center px-4 mb-16">
                    <p class="text-sm font-bold text-on-surface-variant/60">A mostrar <span class="text-on-surface"
                            id="visibleCount">3</span> de <span id="totalFiltered"><?= count($utilizadores) ?></span>
                        utilizadores</p>
                    <div class="flex gap-3" id="paginationControls"></div>
                </div>

                <!-- Info Cards Section -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div
                        class="info-card bg-white/40 p-8 rounded-[2.5rem] shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] cursor-default group">
                        <div
                            class="info-icon w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center mb-6 text-on-surface group-hover:bg-primary group-hover:text-white transition-all duration-300">
                            <span class="material-symbols-outlined">verified_user</span>
                        </div>
                        <h4 class="font-extrabold text-lg text-on-surface mb-3 tracking-tight">Acessos Seguros</h4>
                        <p class="text-sm font-medium text-on-surface-variant leading-relaxed">Todos os colaboradores
                            utilizam autenticação segura para garantir a máxima protecção dos dados clínicos.</p>
                    </div>
                    <div
                        class="info-card bg-white/40 p-8 rounded-[2.5rem] shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] cursor-default group">
                        <div
                            class="info-icon w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center mb-6 text-on-surface group-hover:bg-primary group-hover:text-white transition-all duration-300">
                            <span class="material-symbols-outlined">history</span>
                        </div>
                        <h4 class="font-extrabold text-lg text-on-surface mb-3 tracking-tight">Histórico de Auditoria</h4>
                        <p class="text-sm font-medium text-on-surface-variant leading-relaxed">Registo completo e
                            imutável de todas as alterações feitas em perfis de utilizador, em conformidade com as
                            normas hospitalares.</p>
                    </div>
                    <div
                        class="info-card bg-white/40 p-8 rounded-[2.5rem] shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] cursor-default group">
                        <div
                            class="info-icon w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center mb-6 text-on-surface group-hover:bg-primary group-hover:text-white transition-all duration-300">
                            <span class="material-symbols-outlined">badge</span>
                        </div>
                        <h4 class="font-extrabold text-lg text-on-surface mb-3 tracking-tight">Controlo de Funções</h4>
                        <p class="text-sm font-medium text-on-surface-variant leading-relaxed">Permissões baseadas na
                            hierarquia do Hospital do Bengo, garantindo que cada profissional aceda apenas ao
                            necessário.</p>
                    </div>
                </div>

            </main>
    </div>

    <!-- Client-side Search, Filter & Pagination -->
    <script>
        (function () {
            const CARDS_PER_PAGE = 3;
            const searchInput = document.getElementById('searchInput');
            const filterPerfil = document.getElementById('filterPerfil-native');
            const allCards = Array.from(document.querySelectorAll('#cards-container .user-card'));
            const emptyState = document.getElementById('emptyState');
            const visibleCount = document.getElementById('visibleCount');
            const totalFiltered = document.getElementById('totalFiltered');
            const paginationControls = document.getElementById('paginationControls');
            const container = document.getElementById('cards-container');

            let currentPage = 1;
            let filteredCards = [...allCards];

            function getFilteredCards() {
                const q = searchInput.value.toLowerCase().trim();
                const perfil = filterPerfil.value;
                return allCards.filter(card => {
                    const nome = card.dataset.nome || '';
                    const login = card.dataset.login || '';
                    const rPerfil = card.dataset.perfil || '';
                    const matchSearch = !q || nome.includes(q) || login.includes(q);
                    const matchPerfil = !perfil || rPerfil === perfil;
                    return matchSearch && matchPerfil;
                });
            }

            function renderPage(animate) {
                filteredCards = getFilteredCards();
                const totalPages = Math.max(1, Math.ceil(filteredCards.length / CARDS_PER_PAGE));
                if (currentPage > totalPages) currentPage = totalPages;

                const start = (currentPage - 1) * CARDS_PER_PAGE;
                const end = start + CARDS_PER_PAGE;
                const pageCards = filteredCards.slice(start, end);

                // Hide all, show page cards
                allCards.forEach(c => c.style.display = 'none');
                pageCards.forEach((card, i) => {
                    card.style.display = '';
                    if (animate) {
                        // Start state: invisible, shifted down, blurred
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(40px) scale(0.92)';
                        card.style.filter = 'blur(8px)';
                        card.style.transition = 'none';
                        void card.offsetWidth;
                        // Animate in with stagger
                        setTimeout(() => {
                            card.style.transition = 'opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1), filter 0.5s ease-out';
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0) scale(1)';
                            card.style.filter = 'blur(0px)';
                        }, i * 120 + 50);
                    } else {
                        card.style.opacity = '1';
                        card.style.transform = '';
                        card.style.filter = '';
                    }
                });

                // Update counters
                visibleCount.textContent = pageCards.length;
                totalFiltered.textContent = filteredCards.length;
                emptyState.classList.toggle('hidden', filteredCards.length > 0);
                container.style.display = filteredCards.length > 0 ? '' : 'none';

                // Build pagination
                buildPagination(totalPages);
            }

            function buildPagination(totalPages) {
                paginationControls.innerHTML = '';
                if (totalPages <= 1) return;

                // Prev
                const prev = makeBtn('chevron_left', currentPage > 1, () => goTo(currentPage - 1));
                paginationControls.appendChild(prev);

                // Page numbers
                for (let i = 1; i <= totalPages; i++) {
                    const btn = document.createElement('button');
                    btn.className = `w-12 h-12 rounded-2xl flex items-center justify-center text-sm font-bold shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] btn-action transition-all ${i === currentPage ? 'bg-primary text-white' : 'bg-white text-on-surface hover:bg-primary hover:text-white'
                        }`;
                    btn.textContent = i;
                    btn.onclick = () => goTo(i);
                    paginationControls.appendChild(btn);
                }

                // Next
                const next = makeBtn('chevron_right', currentPage < totalPages, () => goTo(currentPage + 1));
                paginationControls.appendChild(next);
            }

            function makeBtn(icon, active, handler) {
                const btn = document.createElement('button');
                btn.className = `w-12 h-12 rounded-2xl bg-white flex items-center justify-center shadow-[0_4px_20px_-2px_rgba(0,0,0,0.05)] btn-action transition-all ${active ? 'hover:bg-primary hover:text-white' : 'opacity-30 cursor-default'
                    }`;
                btn.innerHTML = `<span class="material-symbols-outlined text-lg">${icon}</span>`;
                if (active) btn.onclick = handler;
                return btn;
            }

            function goTo(page) {
                // Premium exit animation: fade up + blur
                const visible = allCards.filter(c => c.style.display !== 'none');
                visible.forEach((card, i) => {
                    setTimeout(() => {
                        card.style.transition = 'opacity 0.25s ease-in, transform 0.35s cubic-bezier(0.4, 0, 1, 1), filter 0.25s ease-in';
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(-20px) scale(0.96)';
                        card.style.filter = 'blur(4px)';
                    }, i * 50);
                });
                setTimeout(() => {
                    currentPage = page;
                    renderPage(true);
                }, visible.length * 50 + 250);
            }

            // Filter handlers
            searchInput.addEventListener('input', () => { currentPage = 1; renderPage(true); });
            filterPerfil.addEventListener('change', () => { currentPage = 1; renderPage(true); });

            // Initial render with animation
            renderPage(true);
        })();

        // UX Magic: Load user details into Drawer
        async function carregarPerfilLateral(id, nome) {
            // Open drawer with skeleton immediately
            window.openDrawer('Perfil: ' + nome, `
                <div class="flex flex-col gap-4 w-full mt-4">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-gray-200 rounded-full skeleton shrink-0"></div>
                        <div class="flex-1 flex flex-col gap-2">
                            <div class="h-6 bg-gray-200 rounded skeleton w-3/4"></div>
                            <div class="h-4 bg-gray-200 rounded skeleton w-1/3"></div>
                        </div>
                    </div>
                    <div class="h-24 bg-gray-200 rounded-2xl skeleton w-full mt-4"></div>
                    <div class="h-32 bg-gray-200 rounded-2xl skeleton w-full mt-2"></div>
                </div>
            `);

            try {
                const response = await fetch('ver_utilizador_ajax.php?id=' + id);
                if (!response.ok) throw new Error('Network error');
                const html = await response.text();
                document.getElementById('drawer-content').innerHTML = html;
            } catch(e) {
                document.getElementById('drawer-content').innerHTML = '<div class="p-6 bg-red-50 text-red-600 rounded-2xl border border-red-100 font-bold text-sm text-center">Erro ao carregar detalhes. <br><span class="text-xs font-normal">Verifique a sua ligação.</span></div>';
            }
        }
    </script>

</body>

</html>