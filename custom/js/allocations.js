var crud;

// only offer teachers who are qualified for the chosen subject
function filterTeachers($form, keep) {
	var subjectId = $form.find('[data-role=subject]').val();
	var $t = $form.find('[data-role=teacher]');
	var current = keep !== undefined ? keep : $t.val();
	if(subjectId && $.inArray(parseInt(subjectId, 10), NO_TEACHER) !== -1) {
		$t.empty().append('<option value="0">(no teacher - class-supervised)</option>').val('0');
		return;
	}
	var ids = subjectId && QUALIFIED[subjectId] ? QUALIFIED[subjectId] : null;
	$t.empty().append('<option value="">~~SELECT~~</option>');
	$.each(ALL_TEACHERS, function(id, name) {
		if(ids === null && subjectId) { return; }          // nobody qualified yet
		if(ids !== null && $.inArray(parseInt(id, 10), ids) === -1) { return; }
		$t.append('<option value="' + id + '">' + name + '</option>');
	});
	if(subjectId && ids === null) { $t.append('<option value="" disabled>(no teacher is qualified - edit a teacher first)</option>'); }
	if(current) { $t.val(current); }
}

$(document).ready(function() {
	// top bar active
	$('#navSetup, #subNavAllocations').addClass('active');

	crud = new Crud({
		url: 'php_action/allocations.php', table: '#manageTable',
		addForm: '#addForm', editForm: '#editForm', editModal: '#editModal', removeModal: '#removeModal',
		required: { class_id: 'Class', subject_id: 'Subject', teacher_id: 'Teacher', periods_per_week: 'Periods per week' },
		fill: function($form, row) { filterTeachers($form, String(row.teacher_id)); $form.find('[name=teacher_id]').val(String(row.teacher_id)); }
	});

	$('#addForm [data-role=subject], #editForm [data-role=subject]').on('change', function() { filterTeachers($(this).closest('form')); });
	filterTeachers($('#addForm'));

	$('#classFilter').on('change', function() {
		crud.table.ajax.url('php_action/allocations.php?action=fetch' + ($(this).val() ? '&class_id=' + $(this).val() : '')).load();
	});

	// copy a class
	$('#copyForm').on('submit', function() {
		var $form = $(this);
		$.post($form.attr('action'), $form.serialize(), function(response) {
			$form.find('.form-messages').html('<div class="alert ' + (response.success ? 'alert-success' : 'alert-danger') + '">' + response.messages + '</div>');
			if(response.success) { crud.table.ajax.reload(null, false); }
		}, 'json');
		return false;
	});
});
