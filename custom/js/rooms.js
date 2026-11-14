var crud;

$(document).ready(function() {
	// top bar active
	$('#navSetup, #subNavRooms').addClass('active');

	crud = new Crud({
		url: 'php_action/rooms.php', table: '#manageTable',
		addForm: '#addForm', editForm: '#editForm', editModal: '#editModal', removeModal: '#removeModal',
		required: { room_name: 'Room name', room_type: 'Room type', capacity: 'Capacity' }
	});
});
