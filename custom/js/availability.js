var STATES = ['ok', 'avoid', 'no'];
var GLYPH = {ok: '✓', avoid: '~ avoid', no: '✕ off'};

function setCell($c, state) {
	$c.removeClass('avail-ok avail-avoid avail-no').addClass('avail-' + state).data('state', state).attr('data-state', state).text(GLYPH[state]);
}

function loadTeacher() {
	var id = $('#teacherSel').val();
	$('#availMsg').text('');
	$('.avail-cell[data-day]').each(function() { setCell($(this), 'ok'); });
	$.post('php_action/availability.php?action=get', {teacher_id: id}, function(r) {
		$.each(r.slots, function(key, kind) {
			var parts = key.split('-');
			setCell($('.avail-cell[data-day=' + parts[0] + '][data-period=' + parts[1] + ']'), kind === 'unavailable' ? 'no' : 'avoid');
		});
	}, 'json');
}

$(document).ready(function() {
	// top bar active
	$('#navSetup, #subNavAvailability').addClass('active');

	$('#availGrid').on('click', '.avail-cell[data-day]', function() {
		var i = $.inArray($(this).attr('data-state'), STATES);
		setCell($(this), STATES[(i + 1) % 3]);
	});
	// header click: set the whole day / period to the next state of the first cell
	function bulk(sel) {
		var $cells = $(sel);
		var next = STATES[($.inArray($cells.first().attr('data-state'), STATES) + 1) % 3];
		$cells.each(function() { setCell($(this), next); });
	}
	$('.day-head').on('click', function() { bulk('.avail-cell[data-day=' + $(this).data('day') + ']'); });
	$('.period-head').on('click', function() { bulk('.avail-cell[data-period=' + $(this).data('period') + ']'); });

	$('#teacherSel').on('change', loadTeacher);
	$('#saveAvail').on('click', function() {
		var slots = {};
		$('.avail-cell[data-day]').each(function() {
			var st = $(this).attr('data-state');
			if(st !== 'ok') { slots[$(this).data('day') + '-' + $(this).data('period')] = (st === 'no' ? 'unavailable' : 'avoid'); }
		});
		$.post('php_action/availability.php?action=save', {teacher_id: $('#teacherSel').val(), slots: JSON.stringify(slots)}, function(r) {
			$('#availMsg').attr('class', 'text-sm ' + (r.success ? 'text-green-700' : 'text-red-700')).text(r.messages);
		}, 'json');
	});
	loadTeacher();
});
