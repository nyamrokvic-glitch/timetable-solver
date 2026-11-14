function renumber() { $('#periodTable tbody tr').each(function(i) { $(this).find('.pno').text('P' + (i + 1)); }); }

$(document).ready(function() {
	// top bar active
	$('#navSetting').addClass('active');

	function post($form, data) {
		var $msg = $form.find('.msg');
		$.post($form.attr('action'), data || $form.serialize(), function(r) {
			$msg.attr('class', 'msg text-sm ' + (r.success ? 'text-green-700' : 'text-red-700')).text(r.messages);
			if(r.success && $form.hasClass('simple')) { $form.find('input[type=password]').val(''); }
		}, 'json');
	}
	$('#generalForm, .simple').on('submit', function() { post($(this)); return false; });

	$('#addPeriod').on('click', function() {
		var $last = $('#periodTable tbody tr:last');
		var $row = $last.length ? $last.clone() : null;
		if($row) { $row.find('input[type=text]').val(''); $row.find('.brk').prop('checked', false); $('#periodTable tbody').append($row); renumber(); }
	});
	$('#periodTable').on('click', '.rm-period', function() { $(this).closest('tr').remove(); renumber(); });
	$('#periodsForm').on('submit', function() {
		var data = [];
		$('#periodTable tbody tr').each(function() {
			data.push({name: 'start[]', value: $(this).find('input[name="start[]"]').val()});
			data.push({name: 'end[]', value: $(this).find('input[name="end[]"]').val()});
			data.push({name: 'brk[]', value: $(this).find('.brk').is(':checked') ? 1 : 0});
		});
		post($(this), $.param(data));
		return false;
	});
});
