var crud;

$(document).ready(function() {
	// top bar active
	$('#navSetup, #subNavTeachers').addClass('active');

	crud = new Crud({
		url: 'php_action/teachers.php', table: '#manageTable',
		addForm: '#addForm', editForm: '#editForm', editModal: '#editModal', removeModal: '#removeModal',
		required: { teacher_name: 'Teacher name', max_per_day: 'Max per day', max_per_week: 'Max per week' }
	});
});
