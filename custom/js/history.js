$(document).ready(function() {
	// top bar active
	$('#navHistory').addClass('active');

	$('#importForm').on('submit', function() {
		var fd = new FormData(this);
		var $btn = $(this).find('button[type=submit]').prop('disabled', true);
		$.ajax({ url: $(this).attr('action'), type: 'post', data: fd, processData: false, contentType: false, dataType: 'json',
			success: function(r) {
				$btn.prop('disabled', false);
				var html = '<div class="alert ' + (r.success ? 'alert-success' : 'alert-danger') + '">' + $('<div>').text(r.messages).html();
				if(r.problems && r.problems.length) {
					html += '<ul class="ml-5 mt-2 list-disc text-sm">';
					$.each(r.problems, function(i, p) { html += '<li>' + $('<div>').text(p).html() + '</li>'; });
					html += '</ul>';
				}
				$('#importMsg').html(html + '</div>');
				if(r.success) { setTimeout(function() { location.reload(); }, 2500); }
			},
			error: function() { $btn.prop('disabled', false); $('#importMsg').html('<div class="alert alert-danger">Upload failed</div>'); }
		});
		return false;
	});

	$('.learnToggle').on('change', function() {
		var act = $(this).is(':checked') ? 'learn' : 'unlearn';
		if($(this).data('accepted') && act == 'unlearn') { act = 'unaccept'; }
		$.post('php_action/timetableActions.php?action=' + act, {id: $(this).data('id')}, function() { location.reload(); }, 'json');
	});
});
