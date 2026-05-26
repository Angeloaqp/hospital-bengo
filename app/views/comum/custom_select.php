<?php
/**
 * Custom Select Dropdown Component — Hospital Geral do Bengo
 * 
 * Replaces native <select> with a premium animated dropdown
 * matching the design system from recepcionista/marcacao.php.
 *
 * Required params:
 *   $sel_id       (string)  — Unique ID for the dropdown
 *   $sel_name     (string)  — Form field name
 *   $sel_options  (array)   — Options array. Two formats:
 *       Simple:  [ value => label, ... ]
 *       Rich:    [ value => ['label'=>'...', 'icon'=>'...', 'color'=>'...'], ... ]
 *
 * Optional params:
 *   $sel_icon         (string)  — Material Symbols icon for the button (default: 'list')
 *   $sel_placeholder  (string)  — Placeholder text (default: 'Seleccione...')
 *   $sel_value        (string)  — Pre-selected value
 *   $sel_required     (bool)    — Whether the field is required (default: false)
 *   $sel_onchange     (string)  — JS to execute on change (e.g. 'this.form.submit()')
 *   $sel_class        (string)  — Extra classes on wrapper
 *   $sel_multiple     (bool)    — Allow multi-select with checkboxes (default: false)
 *   $sel_size         (string)  — 'sm' for compact filters, 'md' default, 'lg' for forms
 */

// Auto-load JS once per page
if (!isset($GLOBALS['custom_select_loaded'])) {
    echo '<link rel="stylesheet" href="' . BASE_URL . 'public/css/custom_select.css?v=' . time() . '">';
    echo '<script src="' . BASE_URL . 'public/js/custom_select.js?v=' . time() . '"></script>';
    $GLOBALS['custom_select_loaded'] = true;
}

// Defaults
$sel_icon        = $sel_icon ?? 'list';
$sel_placeholder = $sel_placeholder ?? 'Seleccione...';
$sel_value       = $sel_value ?? '';
$sel_required    = $sel_required ?? false;
$sel_onchange    = $sel_onchange ?? '';
$sel_class       = $sel_class ?? '';
$sel_multiple    = $sel_multiple ?? false;
$sel_size        = $sel_size ?? 'md';

// Size classes
$sizeBtn = 'h-14 px-5 rounded-xl';
$sizePanel = 'rounded-xl p-2';
$sizeOpt = 'px-4 py-3 rounded-lg';
if ($sel_size === 'sm') {
    $sizeBtn = 'h-10 px-3 rounded-lg';
    $sizePanel = 'rounded-lg p-1.5';
    $sizeOpt = 'px-3 py-2 rounded-md';
} elseif ($sel_size === 'lg') {
    $sizeBtn = 'h-16 px-6 rounded-2xl';
    $sizePanel = 'rounded-2xl p-3';
    $sizeOpt = 'px-5 py-4 rounded-xl';
}

// Determine selected label / icon / color
$selectedLabel = $sel_placeholder;
$selectedIcon  = $sel_icon;
$selectedColor = 'text-on-surface-variant';
$textClass     = 'text-on-surface-variant'; // placeholder style

foreach ($sel_options as $val => $opt) {
    $isRich = is_array($opt);
    $label = $isRich ? ($opt['label'] ?? $val) : $opt;

    if ((string)$val === (string)$sel_value && $sel_value !== '') {
        $selectedLabel = $label;
        $selectedIcon  = $isRich ? ($opt['icon'] ?? $sel_icon) : $sel_icon;
        $selectedColor = $isRich ? ($opt['color'] ?? 'text-on-surface-variant') : 'text-on-surface-variant';
        $textClass     = 'text-black';
        break;
    }
}

// Add onchange handler to the native select if needed
$onchangeAttr = '';
if ($sel_onchange) {
    $onchangeAttr = ' onchange="' . htmlspecialchars($sel_onchange) . '"';
}
?>

<div class="cs-dropdown relative <?= $sel_class ?>" id="<?= $sel_id ?>" data-placeholder="<?= htmlspecialchars($sel_placeholder) ?>">
    <!-- Trigger Button -->
    <button type="button"
            class="w-full <?= $sizeBtn ?> bg-surface-container-low border-none font-semibold text-sm cursor-pointer hover:bg-surface-container transition-colors flex items-center justify-between"
            onclick="CustomSelect.toggle('<?= $sel_id ?>', event)">
        <div class="flex items-center gap-3 min-w-0 flex-1">
            <span class="material-symbols-outlined cs-icon text-[20px] <?= $selectedColor ?> shrink-0"><?= $selectedIcon ?></span>
            <span class="cs-text truncate <?= $textClass ?>"><?= htmlspecialchars($selectedLabel) ?></span>
        </div>
        <span class="material-symbols-outlined text-on-surface-variant cs-chevron transition-transform duration-200 shrink-0 ml-2">expand_more</span>
    </button>

    <!-- Hidden Native Select (for form submission) -->
    <div class="h-0 w-0 overflow-hidden absolute">
        <select name="<?= $sel_name ?><?= $sel_multiple ? '[]' : '' ?>"
                id="<?= $sel_id ?>-native"
                <?= $sel_required ? 'required' : '' ?>
                <?= $sel_multiple ? 'multiple' : '' ?>
                <?= $onchangeAttr ?>>
            <?php if (!$sel_multiple && !$sel_value): ?>
                <option disabled selected value=""><?= htmlspecialchars($sel_placeholder) ?></option>
            <?php endif; ?>
            <?php foreach ($sel_options as $val => $opt):
                $isRich = is_array($opt);
                $label = $isRich ? ($opt['label'] ?? $val) : $opt;
                $isSelected = $sel_multiple
                    ? (is_array($sel_value) && in_array((string)$val, array_map('strval', $sel_value)))
                    : ((string)$val === (string)$sel_value);
            ?>
                <option value="<?= htmlspecialchars($val) ?>" <?= $isSelected ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Dropdown Panel -->
    <div class="cs-panel absolute top-[calc(100%+8px)] left-0 w-full bg-white <?= $sizePanel ?> floating-card border border-zinc-100 z-[999] max-h-60 overflow-y-auto">
        <?php foreach ($sel_options as $val => $opt):
            $isRich = is_array($opt);
            $label = $isRich ? ($opt['label'] ?? $val) : $opt;
            $icon  = $isRich ? ($opt['icon'] ?? $sel_icon) : $sel_icon;
            $color = $isRich ? ($opt['color'] ?? 'text-on-surface-variant') : 'text-on-surface-variant';
            $isActive = $sel_multiple
                ? (is_array($sel_value) && in_array((string)$val, array_map('strval', $sel_value)))
                : ((string)$val === (string)$sel_value);
        ?>
            <?php if ($sel_multiple): ?>
                <label class="cs-option w-full flex items-center gap-3 <?= $sizeOpt ?> hover:bg-surface-container-low transition-colors text-left cursor-pointer <?= $isActive ? 'active bg-surface-container-low' : '' ?>" data-value="<?= htmlspecialchars($val) ?>">
                    <input type="checkbox" class="w-4 h-4 rounded accent-black" <?= $isActive ? 'checked' : '' ?> onchange="CustomSelect.selectMultiple('<?= $sel_id ?>', '<?= htmlspecialchars($val) ?>', this.checked)">
                    <span class="material-symbols-outlined <?= $color ?> text-[20px]"><?= $icon ?></span>
                    <span class="text-sm font-semibold"><?= htmlspecialchars($label) ?></span>
                </label>
            <?php else: ?>
                <button type="button"
                        class="cs-option w-full flex items-center gap-3 <?= $sizeOpt ?> hover:bg-surface-container-low transition-colors text-left <?= $isActive ? 'active bg-surface-container-low' : '' ?>"
                        data-value="<?= htmlspecialchars($val) ?>"
                        onclick="CustomSelect.select('<?= $sel_id ?>', '<?= htmlspecialchars($val) ?>', '<?= htmlspecialchars($label, ENT_QUOTES) ?>', '<?= $icon ?>', '<?= $color ?>')">
                    <span class="material-symbols-outlined <?= $color ?> text-[20px]"><?= $icon ?></span>
                    <span class="text-sm font-semibold"><?= htmlspecialchars($label) ?></span>
                </button>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php
// Clean up variables to avoid bleeding into parent scope
unset($sel_id, $sel_name, $sel_options, $sel_icon, $sel_placeholder, $sel_value,
      $sel_required, $sel_onchange, $sel_class, $sel_multiple, $sel_size,
      $sizeBtn, $sizePanel, $sizeOpt, $selectedLabel, $selectedIcon, $selectedColor,
      $textClass, $onchangeAttr);
?>
