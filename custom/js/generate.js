function esc(s) { return $('<div>').text(s).html(); }

$(document).ready(function() {
	// top bar active
	$('#navGenerate').addClass('active');

	$('#genForm').on('submit', function() {
		var $form = $(this);
		$('#genBtn').prop('disabled', true).addClass('disabled');
		$('#genWait').removeClass('div-hide');
		$('#result').addClass('div-hide').empty();

		$.ajax({
			url: $form.attr('action'), type: 'post', data: $form.serialize(), dataType: 'json', timeout: 0,
			success: function(r) {
				$('#genBtn').prop('disabled', false).removeClass('disabled');
				$('#genWait').addClass('div-hide');
				if(!r.success) { $('#result').removeClass('div-hide').html('<div class="alert alert-danger">' + esc(r.messages) + '</div>'); return; }

				var ok = r.hard == 0 && r.unplaced.length == 0;
				var h = '<div class="card"><div class="card-head">' + (ok ? '<i class="fa fa-check-circle text-green-700"></i> Timetable ready' : '<i class="fa fa-exclamation-triangle text-amber-700"></i> Timetable generated with problems') + '</div><div class="card-body">';
				h += '<div class="grid gap-3 md:grid-cols-4">';
				h += stat('Lessons placed', r.lessons, 'indigo') + stat('Clashes (hard)', r.hard, r.hard ? 'rose' : 'emerald') + stat('Unplaced lessons', r.unplaced.length, r.unplaced.length ? 'rose' : 'emerald') + stat('Solved in', r.seconds + ' s', 'sky');
				h += '</div>';

				if(r.used_history) {
					h += '<div class="mt-4 rounded-lg bg-indigo-50 p-4" style="background:#eef2ff"><p class="font-semibold text-slate-800">Resemblance to this school\'s own habits</p>' +
						'<p class="text-sm text-slate-600">Share of lessons placed in a period the school has historically used for that subject (learned from ' + r.history_timetables + ' past timetables).</p>' +
						'<div class="mt-2 flex items-center gap-3"><div class="bar-track" style="width:100%"><div class="bar-fill" style="width:' + r.habit_match + '%"></div></div><b>' + r.habit_match + '%</b></div>';
					if(r.baseline) {
						h += '<div class="mt-2 flex items-center gap-3"><div class="bar-track" style="width:100%"><div class="bar-fill" style="width:' + r.baseline.habit_match + '%;background:#94a3b8"></div></div><b>' + r.baseline.habit_match + '%</b></div>' +
							'<p class="mt-1 text-xs text-slate-500">Top bar: this timetable. Grey bar: a generic optimiser with no knowledge of the school (what the staff would be likely to reject).</p>';
					}
					h += '</div>';
				} else {
					h += '<p class="mt-3 text-sm text-slate-600">Generic run: no school history was used.</p>';
				}

				h += '<p class="mt-4 text-sm text-slate-700">Softer problems left: ' + r.teacher_gaps + ' teacher idle period(s), ' + r.avoid_hits + ' "prefer to avoid" slot(s) used, ' + r.class_gaps + ' class free period(s) mid-day, ' + r.repeats + ' repeated-subject day(s).</p>';
				h += '<p class="mt-1 text-sm text-slate-700">Senior-staff time recovered: about <b>' + r.hours_saved + ' hours</b> compared with the ' + r.manual_hours + ' hours it takes by hand.</p>';

				if(r.unplaced.length) {
					h += '<div class="alert alert-warning mt-3"><b>These lessons could not be placed without breaking a rule:</b><ul class="ml-5 list-disc">';
					$.each(r.unplaced, function(i, u) { h += '<li>' + esc(u) + '</li>'; });
					h += '</ul>Fix the data (more availability, another room, fewer periods) and generate again.</div>';
				}
				h += '<div class="mt-4 flex gap-3"><a class="btn-tool" href="timetable.php?id=' + r.timetable_id + '"><i class="fa fa-calendar"></i> Open the timetable</a>' +
					'<a class="btn-tool secondary" href="report.php?id=' + r.timetable_id + '"><i class="fa fa-check"></i> Full report</a></div>';
				h += '</div></div>';
				$('#result').removeClass('div-hide').html(h);
			},
			error: function(xhr) {
				$('#genBtn').prop('disabled', false).removeClass('disabled');
				$('#genWait').addClass('div-hide');
				$('#result').removeClass('div-hide').html('<div class="alert alert-danger">The solver did not finish (' + xhr.status + '). Try a shorter search time.</div>');
			}
		});
		return false;
	});
});

function stat(label, value, colour) {
	var map = {indigo: ['#eef2ff', '#4338ca'], rose: ['#ffe4e6', '#be123c'], emerald: ['#d1fae5', '#047857'], sky: ['#e0f2fe', '#0369a1']};
	return '<div class="rounded-lg p-3" style="background:' + map[colour][0] + '"><div class="text-xs uppercase" style="color:' + map[colour][1] + '">' + label + '</div><div class="text-2xl font-bold" style="color:' + map[colour][1] + '">' + value + '</div></div>';
}
