<?php require_once 'includes/header.php'; require_once 'includes/ui.php'; require_admin_page(); ?>
<?php
$classes = array(); foreach(db_all($connect, "SELECT class_id, class_name FROM classes WHERE class_status = 1 ORDER BY class_name") as $r) { $classes[$r['class_id']] = $r['class_name']; }
$subjects = array(); $noTeacher = array(); foreach(db_all($connect, "SELECT subject_id, subject_name, needs_teacher FROM subjects WHERE subject_status = 1 ORDER BY subject_name") as $r) { $subjects[$r['subject_id']] = $r['subject_name']; if(!$r['needs_teacher']) { $noTeacher[] = (int)$r['subject_id']; } }
$teachers = array(); $qual = array();
foreach(db_all($connect, "SELECT teacher_id, teacher_name FROM teachers WHERE teacher_status = 1 ORDER BY teacher_name") as $r) { $teachers[$r['teacher_id']] = $r['teacher_name']; }
foreach(db_all($connect, "SELECT teacher_id, subject_id FROM teacher_subjects") as $r) { $qual[(int)$r['subject_id']][] = (int)$r['teacher_id']; }

function alloc_fields($prefix, $classes, $subjects, $teachers) {
	return '<div class="grid grid-cols-2 gap-4">' . ui_select($prefix, 'class_id', 'Class', $classes) . ui_select($prefix, 'subject_id', 'Subject', $subjects, 'data-role="subject"') . '</div>'
		. ui_select($prefix, 'teacher_id', 'Teacher (only teachers qualified for the subject are offered)', $teachers, 'data-role="teacher"')
		. '<div class="grid grid-cols-2 gap-4">' . ui_input($prefix, 'periods_per_week', 'Periods per week', 'number', 'min="1" value="3"')
		. ui_input($prefix, 'doubles', 'Of which double lessons', 'number', 'min="0" value="0"') . '</div>'
		. '<p class="text-xs text-slate-500">A double lesson is two back-to-back periods (never across a break). 5 periods with 1 double = one double + three singles.</p>';
}
$classFilter = '<select id="classFilter" class="field" style="width:auto;display:inline-block"><option value="">All classes</option>';
foreach($classes as $id => $n) { $classFilter .= '<option value="' . (int)$id . '">' . h($n) . '</option>'; }
$classFilter .= '</select> <button type="button" class="btn-tool secondary" data-toggle="modal" data-target="#copyModal"><i class="fa fa-copy"></i> Copy a class</button>';

echo ui_manage_page('Allocations', 'Manage Allocations (who teaches what to whom)', 'manageTable', array('Class', 'Subject', 'Teacher', 'Periods / week', 'Doubles', 'Options'), 'addModal', 'Add Allocation', $classFilter);
echo ui_modal('addModal', 'Add Allocation', 'fa-plus', 'addForm', 'php_action/allocations.php?action=create', alloc_fields('add_', $classes, $subjects, $teachers), 'createBtn');
echo ui_modal('editModal', 'Edit Allocation', 'fa-edit', 'editForm', 'php_action/allocations.php?action=edit', alloc_fields('edit_', $classes, $subjects, $teachers), 'editBtn', 'btn-success');
echo ui_remove_modal('removeModal', 'removeBtn', 'Do you really want to remove this allocation?');
echo ui_modal('copyModal', 'Copy allocations from one class to another', 'fa-copy', 'copyForm', 'php_action/allocations.php?action=copy',
	ui_select('copy_', 'from_class', 'Copy FROM', $classes) . ui_select('copy_', 'to_class', 'Copy TO (subjects it already has are skipped)', $classes), 'copyBtn');
?>
<script> var QUALIFIED = <?php echo json_encode($qual); ?>; var ALL_TEACHERS = <?php echo json_encode($teachers); ?>; var NO_TEACHER = <?php echo json_encode($noTeacher); ?>; </script>
<script src="custom/js/crud.js"></script>
<script src="custom/js/allocations.js"></script>
<?php require_once 'includes/footer.php'; ?>
