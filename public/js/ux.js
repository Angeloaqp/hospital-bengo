/**
 * UX Magic & Micro-Interactions
 * Hospital Geral do Bengo - Tactile Editorial
 */

document.addEventListener('DOMContentLoaded', () => {

    /* -------------------------------------------------------------------------- */
    /* 1. TOAST NOTIFICATIONS (Dinâmicas)                                         */
    /* -------------------------------------------------------------------------- */
    const toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container';
    document.body.appendChild(toastContainer);

    window.showToast = function(message, type = 'success', subtitle = 'SISTEMA', duration = 5000) {
        const toast = document.createElement('div');
        toast.className = `toast`;
        
        let icon = 'notifications';
        let bgIcon = 'bg-white/10 text-white';
        if (type === 'success') { icon = 'check_circle'; bgIcon = 'bg-green-500/20 text-green-400'; }
        else if (type === 'error') { icon = 'warning'; bgIcon = 'bg-red-500/20 text-red-400'; subtitle = subtitle === 'SISTEMA' ? 'ALERTA' : subtitle; }
        else if (type === 'info') { icon = 'info'; }
        else if (type === 'audio') { icon = 'volume_up'; subtitle = subtitle === 'SISTEMA' ? 'AVISO SONORO' : subtitle; }
        
        toast.innerHTML = `
            <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 ${bgIcon}">
                <span class="material-symbols-outlined text-[20px]">${icon}</span>
            </div>
            <div class="flex-1 flex flex-col justify-center mt-[-2px]">
                <span class="text-[9px] font-black text-white/50 uppercase tracking-[0.15em] mb-0.5 leading-none">${subtitle}</span>
                <span class="text-sm font-bold text-white leading-none">${message}</span>
            </div>
            <button class="w-6 h-6 flex items-center justify-center text-white/30 hover:text-white transition-colors rounded-full shrink-0 ml-2" onclick="this.parentElement.remove()">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
        `;
        
        toastContainer.appendChild(toast);
        
        // Trigger animation
        requestAnimationFrame(() => {
            toast.classList.add('active');
        });
        
        if(duration > 0) {
            setTimeout(() => {
                if(document.body.contains(toast)) {
                    toast.classList.remove('active');
                    setTimeout(() => toast.remove(), 400); // Wait for transition
                }
            }, duration);
        }
    };

    /* Intercept existing PHP session alerts (if rendered as hidden inputs or data attributes) */
    // For now, this replaces the ugly top banners if we implement a generic checker:
    const phpMessage = document.querySelector('meta[name="flash-message"]');
    if (phpMessage && phpMessage.content) {
        showToast(phpMessage.content, phpMessage.dataset.type || 'success');
    }

    /* -------------------------------------------------------------------------- */
    /* 2. COMMAND PALETTE (Ctrl+K / Cmd+K)                                        */
    /* -------------------------------------------------------------------------- */
    const cmdBackdrop = document.createElement('div');
    cmdBackdrop.className = 'cmd-palette-backdrop';
    cmdBackdrop.innerHTML = `
        <div class="cmd-palette" tabindex="-1">
            <div class="flex items-center px-4 border-b border-gray-100">
                <span class="material-symbols-outlined text-gray-400">search</span>
                <input type="text" id="cmd-input" class="w-full px-4 py-4 text-sm font-bold border-none focus:ring-0 text-black placeholder-gray-400 bg-transparent" placeholder="Procurar paciente, senha ou acção...">
                <div class="px-2 py-1 bg-gray-100 rounded text-[10px] font-extrabold text-gray-500 uppercase">ESC</div>
            </div>
            <div class="max-h-[300px] overflow-y-auto custom-scrollbar p-2" id="cmd-results">
                <div class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-widest">Sugestões Rápidas</div>
                <a href="/hospital-bengo/app/views/recepcionista/registar.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 rounded-xl text-sm font-bold text-gray-700 transition-colors">
                    <span class="material-symbols-outlined text-gray-400">add_circle</span> Nova Senha (Recepção)
                </a>
                <a href="/hospital-bengo/app/views/medico/fila_actual.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 rounded-xl text-sm font-bold text-gray-700 transition-colors">
                    <span class="material-symbols-outlined text-gray-400">stethoscope</span> Fila Actual (Médico)
                </a>
            </div>
        </div>
    `;
    document.body.appendChild(cmdBackdrop);

    const cmdInput = document.getElementById('cmd-input');
    
    function toggleCmdPalette() {
        const isActive = cmdBackdrop.classList.contains('active');
        if (isActive) {
            cmdBackdrop.classList.remove('active');
            cmdInput.blur();
        } else {
            cmdBackdrop.classList.add('active');
            cmdInput.value = '';
            setTimeout(() => cmdInput.focus(), 100);
        }
    }

    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            toggleCmdPalette();
        }
        if (e.key === 'Escape' && cmdBackdrop.classList.contains('active')) {
            toggleCmdPalette();
        }
    });

    cmdBackdrop.addEventListener('click', (e) => {
        if (e.target === cmdBackdrop) toggleCmdPalette();
    });

    /* Simulate search logic for Command Palette */
    cmdInput.addEventListener('input', (e) => {
        const val = e.target.value.toLowerCase();
        const resultsBox = document.getElementById('cmd-results');
        if (val.length > 2) {
            // Simulate API Call / Search
            resultsBox.innerHTML = `
                <div class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-widest">Resultados para "${val}"</div>
                <div class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 rounded-xl cursor-pointer transition-colors" onclick="showToast('Paciente chamado com sucesso!', 'success'); toggleCmdPalette();">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center font-extrabold text-xs">P</div>
                        <div>
                            <div class="text-sm font-bold text-black">Paciente Encontrado</div>
                            <div class="text-xs font-bold text-gray-400">923 456 789</div>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-green-50 text-green-600 rounded-lg text-[10px] font-extrabold uppercase">Gerar Senha</span>
                </div>
            `;
        } else if (val.length === 0) {
            resultsBox.innerHTML = `
                <div class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-widest">Sugestões Rápidas</div>
                <a href="/hospital-bengo/app/views/recepcionista/registar.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 rounded-xl text-sm font-bold text-gray-700 transition-colors">
                    <span class="material-symbols-outlined text-gray-400">add_circle</span> Nova Senha (Recepção)
                </a>
                <a href="/hospital-bengo/app/views/medico/fila_actual.php" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 rounded-xl text-sm font-bold text-gray-700 transition-colors">
                    <span class="material-symbols-outlined text-gray-400">stethoscope</span> Fila Actual (Médico)
                </a>
            `;
        }
    });

    /* -------------------------------------------------------------------------- */
    /* 3. DRAWERS (Painéis Laterais)                                              */
    /* -------------------------------------------------------------------------- */
    const drawerBackdrop = document.createElement('div');
    drawerBackdrop.className = 'drawer-backdrop';
    const drawerPanel = document.createElement('div');
    drawerPanel.className = 'drawer-panel';
    drawerPanel.innerHTML = `
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <h3 class="text-lg font-headline font-black text-black" id="drawer-title">Detalhes</h3>
            <button id="drawer-close" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 transition-colors">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
        <div class="p-6 flex-1 overflow-y-auto custom-scrollbar" id="drawer-content">
            <!-- Content gets injected here -->
        </div>
    `;
    document.body.appendChild(drawerBackdrop);
    document.body.appendChild(drawerPanel);

    window.openDrawer = function(title, contentHtml) {
        document.getElementById('drawer-title').innerText = title;
        document.getElementById('drawer-content').innerHTML = contentHtml;
        
        drawerBackdrop.classList.add('active');
        drawerPanel.classList.add('active');
    };

    window.closeDrawer = function() {
        drawerBackdrop.classList.remove('active');
        drawerPanel.classList.remove('active');
    };

    document.getElementById('drawer-close').addEventListener('click', closeDrawer);
    drawerBackdrop.addEventListener('click', closeDrawer);

    /* -------------------------------------------------------------------------- */
    /* 4. SKELETON LOADERS (Simulação de loading states)                          */
    /* -------------------------------------------------------------------------- */
    // Podes chamar window.withLoading(element, actionCallback)
    window.withLoading = async function(elementId, actionPromise) {
        const el = document.getElementById(elementId);
        if(!el) return actionPromise;
        
        const originalHtml = el.innerHTML;
        // Simple skeleton injection
        el.innerHTML = `
            <div class="flex flex-col gap-4 w-full">
                <div class="h-12 bg-gray-200 rounded-xl skeleton w-full"></div>
                <div class="h-8 bg-gray-200 rounded-xl skeleton w-3/4"></div>
                <div class="h-24 bg-gray-200 rounded-2xl skeleton w-full"></div>
            </div>
        `;
        
        try {
            const result = await actionPromise;
            el.innerHTML = originalHtml;
            return result;
        } catch (e) {
            el.innerHTML = originalHtml;
            throw e;
        }
    };

});
