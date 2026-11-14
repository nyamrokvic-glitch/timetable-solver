<?php require_once 'includes/header.php'; require_once 'includes/ui.php'; require_once 'includes/validator.php'; ?>
<?php
$P = load_problem($connect);
$all = db_all($connect, "SELECT timetable_id, name, status FROM timetables ORDER BY (status = 'accepted') DESC, created_at DESC");
$id = isset($_GET['id']) ? (int)$_GET['id'] : ($all ? (int)$all[0]['timetable_id'] : 0);
$tt = db_one($connect, "SELECT * FROM timetables WHERE timetable_id = ?", 'i', array($id));
?>
<div class="space-y-4">
	<nav class="text-sm text-slate-500 no-print"><a href="dashboard.php" class="hover:text-indigo-600">Home</a><span class="mx-2">/</span><span class="font-medium text-slate-700">Report</span></nav>
<?php if(!$tt) { echo '<div class="card"><div class="card-body">No timetable to report on yet.</div></div></div>'; require_once 'includes/footer.php'; exit; }
$lessons = db_all($connect, "SELECT * FROM timetable_lessons WHERE timetable_id = ?", 'i', array($id));
$v = validate_lessons($lessons, $P);
$S = $P['D'] * $P['P'];

$tLoad = array(); $tDay = array(); $rUse = array(); $cCount = array();
foreach($lessons as $l) {
	$t = (int)$l['teacher_id']; $d = (int)$l['day_no'];
	$tLoad[$t] = (isset($tLoad[$t]) ? $tLoad[$t] : 0) + 1;
	$tDay[$t][$d] = (isset($tDay[$t][$d]) ? $tDay[$t][$d] : 0) + 1;
	if($l['room_id']) { $rUse[(int)$l['room_id']] = (isset($rUse[(int)$l['room_id']]) ? $rUse[(int)$l['room_id']] : 0) + 1; }
	$cCount[(int)$l['class_id']] = (isset($cCount[(int)$l['class_id']]) ? $cCount[(int)$l['class_id']] : 0) + 1;
}
$gapByTeacher = array();
foreach($v['soft'] as $s) { if($s[0] == 'teacher_gap') { $gapByTeacher[$s[3]] = (isset($gapByTeacher[$s[3]]) ? $gapByTeacher[$s[3]] : 0) + (int)preg_replace('/\D+/', '', explode(' idle', explode(' has ', $s[1])[1])[0]); } }
$want = array(); foreach($P['allocs'] as $a) { $want[$a['class']] = (isset($want[$a['class']]) ? $want[$a['class']] : 0) + $a['ppw']; }
?>
	<div class="card no-print"><div class="card-body flex flex-wrap items-center gap-3">
		<select id="repSel" class="field" style="width:auto;max-width:380px"><?php foreach($all as $t) { echo '<option value="' . (int)$t['timetable_id'] . '"' . ($t['timetable_id'] == $id ? ' selected' : '') . '>' . h($t['name']) . ' (' . $t['status'] . ')</option>'; } ?></select>
		<button class="btn-tool ml-auto" onclick="window.print()"><i class="fa fa-print"></i> Print report</button>
	</div></div>

	<div class="card">
		<div class="card-head"><i class="glyphicon glyphicon-check"></i> Quality report &mdash; <?php echo h($tt['name']); ?></div>
		<div class="card-body">
			<div class="grid gap-3 md:grid-cols-4">
				<?php
				$cards = array(array('Lessons', count($lessons), 'indigo'), array('Hard problems', $v['stats']['hard'], $v['stats']['hard'] ? 'rose' : 'emerald'),
					array('Missing lessons', $v['stats']['missing_periods'], $v['stats']['missing_periods'] ? 'rose' : 'emerald'), array('Teacher idle periods', $v['stats']['teacher_gap_periods'], 'amber'));
				foreach($cards as $c) { echo '<div class="rounded-lg p-3 bg-' . $c[2] . '-100"><div class="text-xs uppercase text-' . $c[2] . '-700">' . $c[0] . '</div><div class="text-2xl font-bold text-' . $c[2] . '-700">' . $c[1] . '</div></div>'; } ?>
			</div>
			<p class="mt-3 text-sm text-slate-600">Checked independently of the solver: class, teacher and room double-booking, unavailable teacher slots, lab type and capacity, teacher daily/weekly maximums, and every allocated lesson present.
				<?php if($tt['habit_match'] > 0) { echo 'Habit match: <b>' . round(100 * $tt['habit_match'], 1) . '%</b>.'; } ?></p>
			<?php if($v['hard']) { echo '<ul class="mt-3 ml-5 list-disc text-sm text-red-800">'; foreach(array_slice($v['hard'], 0, 50) as $h) { echo '<li>' . h($h[1]) . '</li>'; } echo '</ul>'; } else { echo '<p class="mt-3 text-sm text-green-700"><i class="fa fa-check-circle"></i> No clashes, no unavailable-slot breaches, nothing missing.</p>'; } ?>
		</div>
	</div>

	<div class="card">
		<div class="card-head">Teacher workload</div>
		<div class="card-body overflow-x-auto">
			<table class="min-w-full divide-y divide-slate-200">
				<thead class="bg-slate-50"><tr><th class="px-3 py-2 text-left text-xs uppercase text-slate-500">Teacher</th><th class="px-3 py-2 text-left text-xs uppercase text-slate-500">Week</th>
					<?php foreach($P['days'] as $d) { echo '<th class="px-3 py-2 text-xs uppercase text-slate-500">' . h($d) . '</th>'; } ?><th class="px-3 py-2 text-xs uppercase text-slate-500">Idle periods</th></tr></thead>
				<tbody class="divide-y divide-slate-100">
				<?php foreach($P['teachers'] as $tid => $t) { $n = isset($tLoad[$tid]) ? $tLoad[$tid] : 0; if(!$n) continue; ?>
					<tr><td class="px-3 py-2 text-sm"><?php echo h($t['name']); ?></td>
					<td class="px-3 py-2 text-sm <?php echo $n > $t['max_week'] ? 'text-red-700 font-semibold' : ''; ?>"><?php echo $n; ?> / <?php echo (int)$t['max_week']; ?></td>
					<?php for($d = 1; $d <= $P['D']; $d++) { $x = isset($tDay[$tid][$d]) ? $tDay[$tid][$d] : 0; echo '<td class="px-3 py-2 text-center text-sm ' . ($x > $t['max_day'] ? 'text-red-700 font-semibold' : ($x ? '' : 'text-slate-300')) . '">' . $x . '</td>'; } ?>
					<td class="px-3 py-2 text-center text-sm"><?php echo isset($gapByTeacher[$tid]) ? $gapByTeacher[$tid] : 0; ?></td></tr>
				<?php } ?>
				</tbody>
			</table>
		</div>
	</div>

	<div class="grid gap-4 lg:grid-cols-2">
		<div class="card"><div class="card-head">Class completeness</div><div class="card-body">
			<?php foreach($P['classes'] as $cid => $c) { $have = isset($cCount[$cid]) ? $cCount[$cid] : 0; $w = isset($want[$cid]) ? $want[$cid] : 0;
				echo '<div class="mb-2"><div class="flex justify-between text-sm"><span>' . h($c['name']) . '</span><span class="' . ($have != $w ? 'text-red-700 font-semibold' : 'text-slate-600') . '">' . $have . ' / ' . $w . ' lessons &middot; ' . $S . ' slots</span></div><div class="bar-track"><div class="bar-fill" style="width:' . ($S ? min(100, 100 * $have / $S) : 0) . '%"></div></div></div>'; } ?>
		</div></div>
		<div class="card"><div class="card-head">Lab &amp; special room use</div><div class="card-body">
			<?php foreach($P['rooms'] as $rid => $r) { $u = isset($rUse[$rid]) ? $rUse[$rid] : 0;
				echo '<div class="mb-2"><div class="flex justify-between text-sm"><span>' . h($r['name']) . '</span><span class="text-slate-600">' . $u . ' / ' . $S . ' periods (' . round(100 * $u / max(1, $S)) . '%)</span></div><div class="bar-track"><div class="bar-fill" style="width:' . min(100, 100 * $u / max(1, $S)) . '%;background:#0ea5e9"></div></div></div>'; } ?>
		</div></div>
	</div>

	<?php if($v['soft']) { ?>
	<div class="card"><div class="card-head">Softer issues (<?php echo count($v['soft']); ?>)</div><div class="card-body"><ul class="ml-5 list-disc text-sm text-slate-700">
		<?php foreach(array_slice($v['soft'], 0, 60) as $s) { echo '<li>' . h($s[1]) . '</li>'; } ?></ul></div></div>
	<?php } ?>
</div>
<script>$(function() { $('#navReport').addClass('active'); $('#repSel').on('change', function() { location.href = 'report.php?id=' + $(this).val(); }); });</script>
<?php require_once 'includes/footer.php'; ?>
