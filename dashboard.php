<?php require_once 'includes/header.php'; require_once 'includes/ui.php'; require_once 'includes/learner.php'; ?>
<?php
$P = load_problem($connect);
$counts = array(
	'Teachers' => array(count($P['teachers']), 'teachers.php', 'indigo'),
	'Classes' => array(count($P['classes']), 'classes.php', 'sky'),
	'Subjects' => array(count($P['subjects']), 'subjects.php', 'emerald'),
	'Rooms & labs' => array(count($P['rooms']), 'rooms.php', 'amber'),
);
$lessonsToPlace = 0; foreach($P['allocs'] as $a) { $lessonsToPlace += $a['ppw']; }
$accepted = db_one($connect, "SELECT * FROM timetables WHERE status = 'accepted' ORDER BY created_at DESC LIMIT 1");
$latest = db_all($connect, "SELECT t.*, (SELECT COUNT(*) FROM timetable_lessons l WHERE l.timetable_id = t.timetable_id) n FROM timetables t ORDER BY created_at DESC, timetable_id DESC LIMIT 6");
$L = Learner::fromDb($connect, $P['D'], $P['P']);
$pre = $isAdmin ? preflight_checks($P) : array();
$manual = (float)get_setting($connect, 'manual_hours', '40');
$gen = db_one($connect, "SELECT COUNT(*) n, COALESCE(SUM(run_seconds),0) s FROM timetables WHERE source IN ('generated','manual')");
$statusLabel = array('draft' => array('Draft', 'warning'), 'accepted' => array('Accepted', 'success'), 'historical' => array('Past', 'info'), 'archived' => array('Ignored', 'default'));
?>
<div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
	<?php foreach($counts as $label => $c) { ?>
	<div class="lg:col-span-3">
		<a href="<?php echo $isAdmin ? $c[1] : '#'; ?>" class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-indigo-300">
			<div class="flex items-center justify-between">
				<p class="text-sm font-medium text-slate-600"><?php echo $label; ?></p>
				<span class="inline-flex rounded-full bg-<?php echo $c[2]; ?>-100 px-3 py-1 text-sm font-semibold text-<?php echo $c[2]; ?>-700"><?php echo $c[0]; ?></span>
			</div>
		</a>
	</div>
	<?php } ?>

	<div class="space-y-4 lg:col-span-4">
		<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
			<div class="rounded-t-xl bg-indigo-600 px-4 py-5 text-center"><h1 class="text-4xl font-bold text-white"><?php echo $accepted ? 'Live' : 'None'; ?></h1></div>
			<div class="px-4 py-3 text-sm text-slate-600"><?php echo $accepted ? 'Accepted timetable: <a class="text-indigo-600" href="timetable.php?id=' . (int)$accepted['timetable_id'] . '">' . h($accepted['name']) . '</a>' : 'No accepted timetable yet.'; ?></div>
		</div>
		<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
			<div class="rounded-t-xl bg-sky-700 px-4 py-5 text-center"><h1 class="text-3xl font-bold text-white"><?php echo number_format($lessonsToPlace); ?></h1></div>
			<div class="px-4 py-3 text-sm text-slate-600">Lessons to place every week</div>
		</div>
		<div class="rounded-xl border border-slate-200 bg-white shadow-sm">
			<div class="rounded-t-xl bg-slate-700 px-4 py-5 text-center"><h1 class="text-3xl font-bold text-white"><?php echo $L->nTimetables; ?></h1></div>
			<div class="px-4 py-3 text-sm text-slate-600">Past timetables the solver learns from <?php echo $L->hasHistory() ? '(' . number_format($L->nLessons) . ' lessons)' : ''; ?></div>
		</div>
		<?php if($gen['n'] > 0) { ?>
		<div class="rounded-xl border border-slate-200 bg-white shadow-sm px-4 py-3 text-sm text-slate-600">
			<b class="text-slate-800">Time recovered:</b> by hand a term's timetable takes about <?php echo $manual; ?> h; the solver took <?php echo round($gen['s'] / max(1, $gen['n']), 1); ?> s on average over <?php echo (int)$gen['n']; ?> run(s).
		</div>
		<?php } ?>
	</div>

	<div class="space-y-4 lg:col-span-8">
		<?php if($pre) { ?>
		<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
			<div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-800"><i class="glyphicon glyphicon-exclamation-sign"></i> Heads-up before generating</div>
			<div class="p-4 text-sm">
				<?php foreach(array_slice($pre, 0, 6) as $x) { echo '<p class="mb-1 ' . ($x[0] == 'error' ? 'text-red-700' : 'text-amber-700') . '">&bull; ' . h($x[1]) . '</p>'; } ?>
			</div>
		</div>
		<?php } ?>
		<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
			<div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 text-sm font-semibold text-slate-800">
				<span><i class="glyphicon glyphicon-calendar"></i> Recent timetables</span>
				<?php if($isAdmin) { ?><a href="generate.php" class="btn-tool" style="padding:5px 12px">Generate new</a><?php } ?>
			</div>
			<div class="p-4"><div class="overflow-x-auto">
				<table class="min-w-full divide-y divide-slate-200">
					<thead class="bg-slate-50"><tr>
						<?php foreach(array('Name', 'Source', 'Lessons', 'Habit match', 'Status') as $c) { echo '<th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">' . $c . '</th>'; } ?>
					</tr></thead>
					<tbody class="divide-y divide-slate-100 bg-white">
					<?php foreach($latest as $t) { ?>
						<tr>
							<td class="px-4 py-3 text-sm text-slate-700"><a class="text-indigo-600" href="timetable.php?id=<?php echo (int)$t['timetable_id']; ?>"><?php echo h($t['name']); ?></a></td>
							<td class="px-4 py-3 text-sm text-slate-700"><?php echo h($t['source']); ?></td>
							<td class="px-4 py-3 text-sm text-slate-700"><?php echo (int)$t['n']; ?></td>
							<td class="px-4 py-3 text-sm text-slate-700"><?php echo $t['habit_match'] > 0 ? round(100 * $t['habit_match']) . '%' : '-'; ?></td>
							<td class="px-4 py-3 text-sm"><label class="label label-<?php echo $statusLabel[$t['status']][1]; ?>"><?php echo $statusLabel[$t['status']][0]; ?></label></td>
						</tr>
					<?php } if(!$latest) { echo '<tr><td colspan="5" class="px-4 py-3 text-sm text-slate-500">No timetables yet.</td></tr>'; } ?>
					</tbody>
				</table>
			</div></div>
		</div>
	</div>
</div>
<script>$(function() { $('#navDashboard').addClass('active'); });</script>
<?php require_once 'includes/footer.php'; ?>
