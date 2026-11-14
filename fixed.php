<?php require_once 'includes/header.php'; require_once 'includes/ui.php'; require_admin_page(); ?>
<?php
$classes = array(0 => 'Every class that has the subject');
foreach(db_all($connect, "SELECT class_id, class_name FROM classes WHERE class_status = 1 ORDER BY class_name") as $r) { $classes[$r['class_id']] = $r['class_name']; }
$subjects = array(); foreach(db_all($connect, "SELECT subject_id, subject_name FROM subjects WHERE subject_status = 1 ORDER BY subject_name") as $r) { $subjects[$r['subject_id']] = $r['subject_name']; }
$days = array(); foreach(day_names($connect) as $i => $n) { $days[$i + 1] = $n; }
$pers = array(); for($i = 1; $i <= count(period_rows($connect)); $i++) { $pers[$i] = 'Period ' . $i; }
function fixed_fields($prefix, $classes, $subjects, $days, $pers) {
	return ui_select($prefix, 'class_id', 'Class', $classes, '', null) . ui_select($prefix, 'subject_id', 'Subject', $subjects)
		. '<div class="grid grid-cols-2 gap-4">' . ui_select($prefix, 'day_no', 'Day', $days) . ui_select($prefix, 'period_no', 'Period', $pers) . '</div>'
		. '<p class="text-xs text-slate-500">The solver places this lesson here first and never moves it (counts towards the class\'s periods for that subject). Example: Saturday afternoon revision.</p>';
}
echo ui_manage_page('Fixed Slots', 'Fixed Slots (lessons that must stay where the school put them)', 'manageTable', array('Class', 'Subject', 'Day', 'Period', 'Options'), 'addModal', 'Add Fixed Slot');
echo ui_modal('addModal', 'Add Fixed Slot', 'fa-plus', 'addForm', 'php_action/fixed.php?action=create', fixed_fields('add_', $classes, $subjects, $days, $pers), 'createBtn');
echo ui_modal('editModal', 'Edit Fixed Slot', 'fa-edit', 'editForm', 'php_action/fixed.php?action=edit', fixed_fields('edit_', $classes, $subjects, $days, $pers), 'editBtn', 'btn-success');
echo ui_remove_modal('removeModal', 'removeBtn', 'Remove this fixed slot? The lesson becomes free to move.');
?>
<script src="custom/js/crud.js"></script>
<script src="custom/js/fixed.js"></script>
<?php require_once 'includes/footer.php'; ?>
