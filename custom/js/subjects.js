var crud;

$(document).ready(function() {
	// top bar active
	$('#navSetup, #subNavSubjects').addClass('active');

	crud = new Crud({
		url: 'php_action/subjects.php', table: '#manageTable',
		addForm: '#addForm', editForm: '#editForm', editModal: '#editModal', removeModal: '#removeModal',
		required: { subject_name: 'Subject name' }
	});
});
