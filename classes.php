<?php require_once 'includes/header.php'; require_once 'includes/ui.php'; require_admin_page(); ?>
<?php
function class_fields($prefix) {
	return ui_input($prefix, 'class_name', 'Class Name', 'text', 'placeholder="e.g. S1A"')
		. '<div class="grid grid-cols-2 gap-4">' . ui_input($prefix, 'level', 'Level / Year', 'text', 'placeholder="e.g. S1"')
		. ui_input($prefix, 'students', 'Number of students', 'number', 'min="1" value="40"') . '</div>'
		. ui_select($prefix, 'class_active', 'Status', array(1 => 'Available', 2 => 'Not Available'), '', null);
}
echo ui_manage_page('Classes', 'Manage Classes', 'manageTable', array('Class', 'Level', 'Students', 'Periods allocated / slots', 'Status', 'Options'), 'addModal', 'Add Class');
echo ui_modal('addModal', 'Add Class', 'fa-plus', 'addForm', 'php_action/classes.php?action=create', class_fields('add_'), 'createBtn');
echo ui_modal('editModal', 'Edit Class', 'fa-edit', 'editForm', 'php_action/classes.php?action=edit', class_fields('edit_'), 'editBtn', 'btn-success');
echo ui_remove_modal('removeModal', 'removeBtn', 'Do you really want to remove this class? Its allocations are removed too.');
?>
<script src="custom/js/crud.js"></script>
<script src="custom/js/classes.js"></script>
<?php require_once 'includes/footer.php'; ?>
