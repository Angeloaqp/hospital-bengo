/**
 * Hospital Geral do Bengo — Custom Select Dropdown System
 * Replaces native <select> with premium animated dropdowns
 */
const CustomSelect = (() => {

    function init() {
        // Close all dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.cs-dropdown')) {
                closeAll();
            }
        });

        // Keyboard: Escape to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAll();
        });
    }

    function closeAll() {
        document.querySelectorAll('.cs-dropdown.open').forEach(d => {
            d.classList.remove('open');
            const chevron = d.querySelector('.cs-chevron');
            if (chevron) chevron.style.transform = '';
        });
    }

    function toggle(id, event) {
        if (event) event.stopPropagation();
        const dropdown = document.getElementById(id);
        if (!dropdown) return;

        const wasOpen = dropdown.classList.contains('open');

        // Close all others first
        closeAll();

        if (!wasOpen) {
            dropdown.classList.add('open');
            const chevron = dropdown.querySelector('.cs-chevron');
            if (chevron) chevron.style.transform = 'rotate(180deg)';

            // Scroll selected into view
            const panel = dropdown.querySelector('.cs-panel');
            const active = panel ? panel.querySelector('.cs-option.active') : null;
            if (active && panel) {
                active.scrollIntoView({ block: 'nearest' });
            }
        }
    }

    function select(id, value, label, icon, iconColor) {
        const dropdown = document.getElementById(id);
        if (!dropdown) return;

        // Update hidden native select
        const nativeSelect = dropdown.querySelector('select');
        if (nativeSelect) {
            nativeSelect.value = value;
            nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Update button display
        const textEl = dropdown.querySelector('.cs-text');
        if (textEl) {
            textEl.textContent = label;
            textEl.classList.remove('text-on-surface-variant');
            textEl.classList.add('text-black');
        }

        const iconEl = dropdown.querySelector('.cs-icon');
        if (iconEl && icon) {
            iconEl.textContent = icon;
            if (iconColor) {
                iconEl.className = 'material-symbols-outlined cs-icon text-[20px] ' + iconColor;
            }
        }

        // Mark active option
        dropdown.querySelectorAll('.cs-option').forEach(opt => {
            opt.classList.remove('active', 'bg-surface-container-low');
            if (opt.dataset.value === String(value)) {
                opt.classList.add('active', 'bg-surface-container-low');
            }
        });

        // Close
        dropdown.classList.remove('open');
        const chevron = dropdown.querySelector('.cs-chevron');
        if (chevron) chevron.style.transform = '';
    }

    function selectMultiple(id, value, checked) {
        const dropdown = document.getElementById(id);
        if (!dropdown) return;

        const nativeSelect = dropdown.querySelector('select');
        if (!nativeSelect) return;

        // Toggle the option in native select
        const opt = nativeSelect.querySelector(`option[value="${value}"]`);
        if (opt) opt.selected = checked;

        // Update button text with count
        const selected = Array.from(nativeSelect.selectedOptions);
        const textEl = dropdown.querySelector('.cs-text');
        if (textEl) {
            if (selected.length === 0) {
                textEl.textContent = dropdown.dataset.placeholder || 'Seleccione...';
                textEl.classList.add('text-on-surface-variant');
                textEl.classList.remove('text-black');
            } else {
                textEl.textContent = selected.length + ' seleccionados';
                textEl.classList.remove('text-on-surface-variant');
                textEl.classList.add('text-black');
            }
        }

        nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // Auto-init on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    return { toggle, select, selectMultiple, closeAll };
})();
