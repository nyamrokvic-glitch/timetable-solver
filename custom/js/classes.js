var crud;

$(document).ready(function() {
	// top bar active
	$('#navSetup, #subNavClasses').addClass('active');

	crud = new Crud({
		url: 'php_action/classes.php', table: '#manageTable',
		addForm: '#addForm', editForm: '#editForm', editModal: '#editModal', removeModal: '#removeModal',
		required: { class_name: 'Class name', students: 'Number of students' }
	});
});
