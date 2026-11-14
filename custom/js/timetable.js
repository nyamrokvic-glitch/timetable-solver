$(document).ready(function() {
	// top bar active
	$('#navTimetable').addClass('active');

	$('#ttSel').on('change', function() { location.href = 'timetable.php?id=' + $(this).val() + '&view=' + TT_VIEW; });
	$('#entSel').on('change', function() { location.href = 'timetable.php?id=' + TT_ID + '&view=' + TT_VIEW + '&e=' + $(this).val(); });

	function done(r, reload) {
		$('#actMsg').html('<div class="alert ' + (r.success ? 'alert-success' : 'alert-danger') + '">' + r.messages + '</div>');
		if(r.success && reload) { setTimeout(function() { location.reload(); }, 700); }
	}

	$('.act').on('click', function() {
		$.post('php_action/timetableActions.php?action=' + $(this).data('act'), {id: TT_ID}, function(r) { done(r, true); }, 'json');
	});

	$('#renameForm').on('submit', function() {
		$.post($(this).attr('action'), $(this).serialize(), function(r) { done(r, true); $('#renameModal').modal('hide'); }, 'json');
		return false;
	});

	$('#deleteBtn').on('click', function() {
		$.post('php_action/timetableActions.php?action=delete', {id: TT_ID}, function(r) { if(r.success) { location.href = 'timetable.php'; } else { done(r); } }, 'json');
	});

	// click a lesson to move it
	$('.tt-lesson.mv').on('click', function() {
		$('#moveLesson').val($(this).data('lesson'));
		$('#moveLabel').text($(this).data('label'));
		$('#moveForce').val('');
		$('#moveForm [name=day]').val($(this).data('day'));
		$('#moveForm [name=period]').val($(this).data('period'));
		$('#moveForm .form-messages').empty();
		$('#moveBtn').text('Move');
		$('#moveModal').modal('show');
	});

	$('#moveForm').on('submit', function() {
		var $form = $(this);
		$.post($form.attr('action'), $form.serialize(), function(r) {
			if(r.success) { $form.find('.form-messages').html('<div class="alert alert-success">' + r.messages + '</div>'); setTimeout(function() { location.reload(); }, 600); return; }
			var html = '<div class="alert alert-danger"><b>' + r.messages + '</b>';
			if(r.conflicts) { html += '<ul class="ml-5 list-disc">'; $.each(r.conflicts, function(i, c) { html += '<li>' + $('<div>').text(c).html() + '</li>'; }); html += '</ul>'; }
			if(r.can_force) { html += '<p class="mt-2">Press <b>Move anyway</b> to force it (the clash will be flagged in red).</p>'; $('#moveForce').val('1'); $('#moveBtn').text('Move anyway'); }
			$form.find('.form-messages').html(html + '</div>');
		}, 'json');
		return false;
	});
});
