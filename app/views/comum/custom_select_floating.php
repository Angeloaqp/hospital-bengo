<?php
/**
 * Custom Select Dropdown Component — Floating Label Variant
 * 
 * Specifically designed for forms using the `.field-wrap` floating label design
 * (e.g., configuracao.php, criar_utilizador.php).
 *
 * Required params:
 *   $sel_id       (string)  — Unique ID for the dropdown
 *   $sel_name     (string)  — Form field name
 *   $sel_options  (array)   — Options array. [ value => label ] or rich format.
 *   $sel_label    (string)  — Floating label text
 *
 * Optional params:
 *   $sel_icon         (string)  — Material Symbols icon for the field (default: 'list')
 *   $sel_value        (string)  — Pre-selected value
 *   $sel_required     (bool)    — Whether the field is required (default: false)
 *   $sel_onchange     (string)  — JS to execute on change
 *   $sel_class        (string)  — Extra classes on wrapper
 */

if (!isset($GLOBALS['custom_select_loaded'])) {
    echo '<link rel="stylesheet" href="' . BASE_URL . 'public/css/custom_select.css?v=' . time() . '">';
    echo '<script src="' . BASE_URL . 'public/js/custom_select.js?v=' . time() . '"></script>';
    $GLOBALS['custom_select_loaded'] = true;
}

$sel_icon     = $sel_icon ?? 'list';
$sel_value    = $sel_value ?? '';
$sel_required = $sel_required ?? false;
$sel_onchange = $sel_onchange ?? '';
$sel_class    = $sel_class ?? '';

// Determine selected label
$selectedLabel = '';
$hasValue = false;

foreach ($sel_options as $val => $opt) {
    $isRich = is_array($opt);
    $label = $isRich ? ($opt['label'] ?? $val) : $opt;

    if ((string)$val === (string)$sel_value && $sel_value !== '') {
        $selectedLabel = $label;
        $hasValue = true;
        break;
    }
}

$onchangeAttr = '';
if ($sel_onchange) {
    $onchangeAttr = ' onchange="' . htmlspecialchars($sel_onchange) . '"';
}
?>

<div class="field-wrap cs-dropdown relative <?= $sel_class ?>" id="<?= $sel_id ?>" data-placeholder="">
    <!-- Trigger Button mimicking .fi -->
    <button type="button"
            class="fi w-full h-full text-left cursor-pointer flex items-center bg-[#f4f5f7] hover:bg-[#f8fafc] focus:bg-white focus:border-[#111] focus:shadow-[0_6px_24px_-4px_rgba(0,0,0,0.06)] border-2 border-transparent transition-all <?= $hasValue ? 'has-value' : '' ?>"
            onclick="CustomSelect.toggle('<?= $sel_id ?>', event)">
        <span class="cs-text truncate block w-full outline-none"><?= htmlspecialchars($selectedLabel) ?></span>
    </button>
    
    <span class="material-symbols-outlined field-icon cs-field-icon transition-colors <?= $hasValue ? 'text-[#111]' : '' ?>"><?= $sel_icon ?></span>
    <label for="<?= $sel_id ?>-native" class="fl pointer-events-none"><?= htmlspecialchars($sel_label) ?></label>
    <span class="material-symbols-outlined select-arrow cs-chevron transition-transform duration-200">expand_more</span>

    <!-- Hidden Native Select (for form submission) -->
    <div class="h-0 w-0 overflow-hidden absolute">
        <select name="<?= $sel_name ?>"
                id="<?= $sel_id ?>-native"
                <?= $sel_required ? 'required' : '' ?>
                class="fi-native-hidden"
                onchange="syncFloatingLabel(this); <?= htmlspecialchars($sel_onchange) ?>">
            <option value="" <?= !$hasValue ? 'selected' : '' ?> disabled></option>
            <?php foreach ($sel_options as $val => $opt):
                $isRich = is_array($opt);
                $label = $isRich ? ($opt['label'] ?? $val) : $opt;
                $isSelected = ((string)$val === (string)$sel_value);
            ?>
                <option value="<?= htmlspecialchars($val) ?>" <?= $isSelected ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Dropdown Panel -->
    <div class="cs-panel absolute top-[calc(100%+4px)] left-0 w-full bg-white rounded-xl p-2 floating-card border border-zinc-100 z-[999] max-h-60 overflow-y-auto">
        <?php foreach ($sel_options as $val => $opt):
            $isRich = is_array($opt);
            $label = $isRich ? ($opt['label'] ?? $val) : $opt;
            $icon  = $isRich ? ($opt['icon'] ?? '') : '';
            $color = $isRich ? ($opt['color'] ?? 'text-on-surface-variant') : 'text-on-surface-variant';
            $isActive = ((string)$val === (string)$sel_value);
            
            // Skip empty placeholder option in the dropdown list
            if ((string)$val === '') continue;
        ?>
            <button type="button"
                    class="cs-option w-full flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-surface-container-low transition-colors text-left <?= $isActive ? 'active bg-surface-container-low' : '' ?>"
                    data-value="<?= htmlspecialchars($val) ?>"
                    onclick="CustomSelect.select('<?= $sel_id ?>', '<?= htmlspecialchars($val) ?>', '<?= htmlspecialchars($label, ENT_QUOTES) ?>', '', ''); document.getElementById('<?= $sel_id ?>').querySelector('button').classList.add('has-value'); document.getElementById('<?= $sel_id ?>').querySelector('.cs-field-icon').classList.add('text-[#111]');">
                <?php if ($icon): ?>
                    <span class="material-symbols-outlined <?= $color ?> text-[20px]"><?= $icon ?></span>
                <?php endif; ?>
                <span class="text-sm font-semibold"><?= htmlspecialchars($label) ?></span>
            </button>
        <?php endforeach; ?>
    </div>
</div>

<script>
    if (typeof syncFloatingLabel !== 'function') {
        function syncFloatingLabel(selectEl) {
            const wrapper = selectEl.closest('.field-wrap');
            if (wrapper) {
                const btn = wrapper.querySelector('button.fi');
                const icon = wrapper.querySelector('.cs-field-icon');
                if (selectEl.value && selectEl.value.trim() !== '' && selectEl.value !== '0') {
                    if (btn) btn.classList.add('has-value');
                    if (icon) icon.classList.add('text-[#111]');
                } else {
                    if (btn) btn.classList.remove('has-value');
                    if (icon) icon.classList.remove('text-[#111]');
                }
            }
        }
    }
</script>

<?php
unset($sel_id, $sel_name, $sel_options, $sel_label, $sel_icon, $sel_value,
      $sel_required, $sel_onchange, $sel_class, $selectedLabel, $hasValue, $onchangeAttr);
?>
