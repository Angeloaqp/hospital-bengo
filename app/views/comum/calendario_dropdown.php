<?php
// Parâmetros esperados:
// $cal_id (string) ex: 'cal-filtro'
// $cal_name (string) ex: 'data'
// $cal_value (string) ex: '2026-05-25'
// $cal_min (string) opcional ex: '2026-05-25'
// $cal_label (string) opcional ex: 'Data'
// $cal_onchange (string) opcional ex: 'this.form.submit()'
// $cal_class (string) opcional
// $cal_right (bool) opcional para alinhar o popover à direita

if (!isset($GLOBALS['calendar_widget_loaded'])) {
    echo '<script src="' . BASE_URL . 'public/js/calendar_widget.js?v=' . time() . '"></script>';
    $GLOBALS['calendar_widget_loaded'] = true;
}
?>
<div class="relative custom-calendar-dropdown <?= $cal_class ?? '' ?>" id="<?= $cal_id ?>-dropdown">
    <button type="button" class="w-full h-10 px-3 bg-white border border-surface-container-high rounded-xl font-bold text-sm cursor-pointer hover:bg-zinc-50 transition-colors flex items-center justify-between" onclick="HospitalCalendar.toggleDropdown('<?= $cal_id ?>', event)">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-on-surface-variant text-[18px]">calendar_month</span>
            <span class="text-on-surface whitespace-nowrap" id="<?= $cal_id ?>-text">
                <?= !empty($cal_value) ? date('d/m/Y', strtotime($cal_value)) : ($cal_label ?? 'Seleccione a data') ?>
            </span>
        </div>
        <span class="material-symbols-outlined text-on-surface-variant text-[18px] pointer-events-none transition-transform duration-200" style="margin-left:8px;">expand_more</span>
    </button>
    
    <input type="hidden" name="<?= $cal_name ?>" id="<?= $cal_id ?>-input" value="<?= htmlspecialchars($cal_value ?? '') ?>" onchange="<?= htmlspecialchars($cal_onchange ?? '') ?>">

    <!-- Popover Container -->
    <div class="custom-cal-wrapper absolute top-[calc(100%+8px)] <?= isset($cal_right) && $cal_right ? 'right-0' : 'left-0' ?> w-[300px] bg-white rounded-[32px] p-2 floating-card border border-zinc-100 z-50 shadow-2xl transition-all duration-200 opacity-0 invisible -translate-y-2 pointer-events-none" id="<?= $cal_id ?>-wrapper" onclick="event.stopPropagation()">
        <!-- JS injeta o calendário aqui -->
    </div>
</div>

<script>
    // Executa imediatamente, uma vez que o wrapper já está no DOM acima
    if (typeof HospitalCalendar !== 'undefined') {
        HospitalCalendar.init('<?= $cal_id ?>', '<?= $cal_value ?? '' ?>', '<?= $cal_min ?? '' ?>');
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            HospitalCalendar.init('<?= $cal_id ?>', '<?= $cal_value ?? '' ?>', '<?= $cal_min ?? '' ?>');
        });
    }
</script>
