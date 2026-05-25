/**
 * Hospital Bengo - Custom Select Dropdowns
 * Converte automaticamente todos os elementos <select> para um design Tailwind
 */

const HospitalSelect = {
    init: function(selector = 'select:not([multiple]):not(.no-custom)') {
        document.querySelectorAll(selector).forEach(select => {
            // Se já foi convertido, ignora
            if (select.closest('.custom-dropdown') || select.dataset.customized) return;
            
            this.convert(select);
        });

        // Fechar dropdowns ao clicar fora
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.custom-dropdown')) {
                document.querySelectorAll('.custom-dropdown.open').forEach(dropdown => {
                    dropdown.classList.remove('open');
                });
            }
        });
    },

    convert: function(select) {
        // Marca como convertido
        select.dataset.customized = 'true';
        
        // Esconde o select nativo
        select.style.display = 'none';

        // Container principal
        const container = document.createElement('div');
        container.className = 'relative custom-dropdown w-full ' + (select.className.includes('mt-') ? select.className.match(/mt-\d+/)[0] : '');
        const dropdownId = 'dropdown-' + Math.random().toString(36).substr(2, 9);
        container.id = dropdownId;

        // Tentar obter um ícone baseado no nome ou classe
        let defaultIcon = 'list';
        const selectName = (select.name || '').toLowerCase();
        if (selectName.includes('medico')) defaultIcon = 'groups';
        else if (selectName.includes('especialidade')) defaultIcon = 'medical_services';
        else if (selectName.includes('turno')) defaultIcon = 'schedule';
        else if (selectName.includes('estado')) defaultIcon = 'task_alt';
        else if (selectName.includes('prioridade')) defaultIcon = 'priority';

        // Opção selecionada atualmente
        const selectedOption = select.options[select.selectedIndex] || select.options[0];
        const selectedText = selectedOption ? selectedOption.text : 'Selecione...';

        // Botão principal
        const button = document.createElement('button');
        button.type = 'button';
        // Usa as classes de altura/bg do original se possível, senão usa padrão
        button.className = 'w-full h-14 px-5 bg-surface-container-low border-none rounded-2xl font-semibold text-sm cursor-pointer hover:bg-surface-container transition-colors flex items-center justify-between text-left';
        
        // Pequena variação se o select original tiver classes específicas
        if (select.className.includes('input-recessed')) {
            button.classList.add('bg-surface-container-lowest', 'border', 'border-surface-container-high');
            button.classList.remove('bg-surface-container-low', 'border-none');
        }

        button.innerHTML = `
            <div class="flex items-center gap-3 overflow-hidden">
                <span class="material-symbols-outlined text-on-surface-variant text-[20px] shrink-0" data-icon>${defaultIcon}</span>
                <span class="text-black truncate" data-text>${selectedText}</span>
            </div>
            <span class="material-symbols-outlined text-on-surface-variant pointer-events-none transition-transform duration-200 shrink-0" data-arrow>expand_more</span>
        `;

        // Evento de abrir/fechar
        button.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = container.classList.contains('open');
            document.querySelectorAll('.custom-dropdown.open').forEach(d => d.classList.remove('open'));
            if (!isOpen) container.classList.add('open');
        });

        // Lista de opções flutuante
        const dropdownContent = document.createElement('div');
        dropdownContent.className = 'custom-dropdown-content absolute top-[calc(100%+8px)] left-0 w-full bg-white rounded-2xl p-2 floating-card border border-zinc-100 z-50 max-h-60 overflow-y-auto opacity-0 invisible -translate-y-2 pointer-events-none transition-all duration-200';
        
        // Adicionar estilos CSS para a animação da lista (injeção global apenas uma vez)
        if (!document.getElementById('custom-select-styles')) {
            const style = document.createElement('style');
            style.id = 'custom-select-styles';
            style.innerHTML = `
                .custom-dropdown.open .custom-dropdown-content {
                    opacity: 1;
                    visibility: visible;
                    transform: translateY(0);
                    pointer-events: auto;
                }
                .custom-dropdown.open [data-arrow] {
                    transform: rotate(180deg);
                }
            `;
            document.head.appendChild(style);
        }

        // Criar os botões de opção
        Array.from(select.options).forEach(option => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'w-full flex items-center gap-3 px-4 py-3 hover:bg-surface-container-low rounded-xl transition-colors text-left';
            
            // Ícone da opção (tenta usar data-icon ou o padrão)
            const optIcon = option.dataset.icon || defaultIcon;
            let iconColor = 'text-on-surface-variant';
            
            // Cores baseadas no texto para as prioridades (como no marcacao.php)
            if (option.text.toLowerCase().includes('urgente')) { iconColor = 'text-red-500'; }
            else if (option.text.toLowerCase().includes('idoso')) { iconColor = 'text-amber-500'; }
            else if (option.text.toLowerCase().includes('grávida')) { iconColor = 'text-purple-500'; }
            else if (option.text.toLowerCase().includes('normal')) { iconColor = 'text-blue-500'; }

            btn.innerHTML = `
                <span class="material-symbols-outlined ${iconColor} text-[20px] shrink-0">${optIcon}</span>
                <span class="text-sm font-semibold truncate ${option.disabled ? 'text-on-surface-variant opacity-60' : 'text-black'}">${option.text}</span>
            `;

            if (option.disabled) {
                btn.style.cursor = 'not-allowed';
            } else {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    // Atualiza o select nativo
                    select.value = option.value;
                    select.selectedIndex = Array.from(select.options).indexOf(option);
                    
                    // Atualiza o UI
                    const iconEl = button.querySelector('[data-icon]');
                    const textEl = button.querySelector('[data-text]');
                    iconEl.textContent = optIcon;
                    iconEl.className = `material-symbols-outlined ${iconColor} text-[20px] shrink-0`;
                    textEl.textContent = option.text;
                    
                    // Dispara evento onchange no select nativo para código legado funcionar
                    const changeEvent = new Event('change', { bubbles: true });
                    select.dispatchEvent(changeEvent);
                    
                    // Força a chamada do atributo onchange se existir e não tiver sido disparado (fallback seguro)
                    if (typeof select.onchange === 'function') {
                        try { select.onchange(changeEvent); } catch(e) { console.error(e); }
                    }
                    
                    container.classList.remove('open');
                });
            }
            
            dropdownContent.appendChild(btn);
        });

        // Montar no DOM
        select.parentNode.insertBefore(container, select);
        container.appendChild(button);
        container.appendChild(select); // move o select para dentro do container
        container.appendChild(dropdownContent);

        // Adicionar MutationObserver para reagir a alterações AJAX nas opções do select nativo
        const observer = new MutationObserver((mutations) => {
            let hasOptionsChange = false;
            for (const mutation of mutations) {
                if (mutation.type === 'childList') {
                    hasOptionsChange = true;
                    break;
                }
            }
            
            if (hasOptionsChange) {
                observer.disconnect(); // Parar de observar para evitar loops
                
                // Remover o estado customizado
                select.dataset.customized = '';
                select.style.display = '';
                
                // Mover o select de volta para fora do container
                const parentNode = container.parentNode;
                if (parentNode) {
                    parentNode.insertBefore(select, container);
                    container.remove(); // Destruir o UI antigo
                    
                    // Recriar o UI com as novas opções
                    HospitalSelect.convert(select);
                }
            }
        });

        // Observar mudanças nas tags <option> dentro deste select
        observer.observe(select, { childList: true });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    HospitalSelect.init();
});
