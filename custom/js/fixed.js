var crud;

$(document).ready(function() {
	// top bar active
	$('#navSetup, #subNavFixed').addClass('active');

	crud = new Crud({
		url: 'php_action/fixed.php', table: '#manageTable',
		addForm: '#addForm', editForm: '#editForm', editModal: '#editModal', removeModal: '#removeModal',
		required: { subject_id: 'Subject', day_no: 'Day', period_no: 'Period' }
	});
});
