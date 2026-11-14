<?php require_once 'includes/header.php'; require_once 'includes/ui.php'; require_once 'includes/learner.php'; require_admin_page(); ?>
<?php
$P = load_problem($connect);
$L = Learner::fromDb($connect, $P['D'], $P['P']);
$tol = $L->tolerance;

// colour a probability relative to chance: above chance = indigo, below = grey
function heat($p, $n) {
	$ratio = $p * $n;     // 1.0 = chance
	if($ratio >= 1) { $a = min(0.9, 0.12 + ($ratio - 1) * 0.35); return "background:rgba(79,70,229,$a);color:" . ($a > 0.5 ? '#fff' : '#1e293b'); }
	$a = min(0.5, (1 - $ratio) * 0.45); return "background:rgba(148,163,184,$a);color:#64748b";
}
?>
<div class="space-y-4">
	<nav class="text-sm text-slate-500"><a href="dashboard.php" class="hover:text-indigo-600">Home</a><span class="mx-2">/</span><span class="font-medium text-slate-700">AI Learning</span></nav>

	<div class="card">
		<div class="card-head"><i class="glyphicon glyphicon-stats"></i> What the solver has learned about this school</div>
		<div class="card-body">
		<?php if(!$L->hasHistory()) { ?>
			<div class="alert alert-info">Nothing learned yet. Import past timetables on the <a class="underline" href="history.php">History</a> page, or accept a generated timetable. Until then the solver uses generic rules only.</div>
		<?php } else { ?>
			<p class="text-sm text-slate-600">Learned from <b><?php echo $L->nTimetables; ?></b> accepted timetable(s) containing <b><?php echo number_format($L->nLessons); ?></b> lessons:
				<?php echo h(implode(', ', $L->timetableNames)); ?>. Everything below is plain counting - no black box - so the staff can see <i>why</i> a lesson lands where it does.</p>
			<div class="mt-4 grid gap-3 md:grid-cols-4">
				<?php
				$cards = array(
					array('Teacher-days studied', number_format($tol['teacher_days']), 'indigo'),
					array('Teacher-days with an idle gap', round(100 * $tol['share_days_with_gap']) . '%', 'amber'),
					array('Longest gap normally tolerated', $L->tolerableGap() . ' period' . ($L->tolerableGap() > 1 ? 's' : ''), 'sky'),
					array('Class-days repeating a subject', round(100 * $tol['share_repeat_subject'], 1) . '%', 'rose'),
				);
				foreach($cards as $c) { echo '<div class="rounded-lg p-3 bg-' . $c[2] . '-100"><div class="text-xs uppercase text-' . $c[2] . '-700">' . $c[0] . '</div><div class="text-2xl font-bold text-' . $c[2] . '-700">' . $c[1] . '</div></div>'; } ?>
			</div>
			<p class="mt-3 text-sm text-slate-600"><b>Tolerated clashes.</b> The school's own timetables averaged <?php echo round($tol['mean_gaps'], 2); ?> idle period(s) per teacher-day, up to <?php echo (int)$tol['max_teacher_day']; ?> lessons for a teacher in a day (average <?php echo round($tol['mean_teacher_day'], 1); ?>). The solver only penalises gaps longer than <?php echo $L->tolerableGap(); ?> period(s) as "unusual", instead of chasing a perfect zero the staff never asked for.</p>
		<?php } ?>
		</div>
	</div>

<?php if($L->hasHistory()) { ?>
	<div class="card">
		<div class="card-head">When each subject is taught (period of the day)</div>
		<div class="card-body overflow-x-auto">
			<p class="mb-2 text-xs text-slate-500">Share of that subject's lessons in each period. Indigo = used more than chance; grey = rarely used. Breaks fall after the periods marked &#9646;.</p>
			<table class="w-full" style="min-width:560px">
				<thead><tr><th class="text-left text-xs uppercase text-slate-500 px-2">Subject</th>
					<?php foreach($P['periods'] as $i => $p) { echo '<th class="text-xs text-slate-500">P' . ($i + 1) . ($p['break_after'] ? ' &#9646;' : '') . '</th>'; } ?><th class="text-xs text-slate-500">Lessons</th></tr></thead>
				<tbody>
				<?php foreach($P['subjects'] as $sid => $s) { if(empty($L->subjLessons[$sid])) continue; ?>
					<tr><td class="px-2 py-1 text-sm text-slate-700"><?php echo h($s['name']); ?></td>
					<?php for($p = 0; $p < $P['P']; $p++) { $pr = $L->probSubjectPeriod($sid, $p); echo '<td class="heat-cell" style="' . heat($pr, $P['P']) . '">' . round(100 * $pr) . '%</td>'; } ?>
					<td class="text-center text-xs text-slate-500"><?php echo (int)$L->subjLessons[$sid]; ?></td></tr>
				<?php } ?>
				</tbody>
			</table>
		</div>
	</div>

	<div class="grid gap-4 lg:grid-cols-2">
		<div class="card">
			<div class="card-head">Which days each subject is taught</div>
			<div class="card-body overflow-x-auto">
				<table class="w-full">
					<thead><tr><th></th><?php foreach($P['days'] as $d) { echo '<th class="text-xs text-slate-500">' . h($d) . '</th>'; } ?></tr></thead>
					<tbody>
					<?php foreach($P['subjects'] as $sid => $s) { if(empty($L->subjLessons[$sid])) continue; ?>
						<tr><td class="px-2 py-1 text-sm text-slate-700"><?php echo h($s['code'] ?: $s['name']); ?></td>
						<?php for($d = 0; $d < $P['D']; $d++) { $pr = $L->probSubjectDay($sid, $d); echo '<td class="heat-cell" style="' . heat($pr, $P['D']) . '">' . round(100 * $pr) . '%</td>'; } ?></tr>
					<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
		<div class="card">
			<div class="card-head">Where double lessons start</div>
			<div class="card-body overflow-x-auto">
				<table class="w-full">
					<thead><tr><th></th><?php foreach($P['periods'] as $i => $p) { echo '<th class="text-xs text-slate-500">P' . ($i + 1) . '</th>'; } ?><th class="text-xs text-slate-500">#</th></tr></thead>
					<tbody>
					<?php $any = false; foreach($P['subjects'] as $sid => $s) { if(empty($L->dblCount[$sid])) continue; $any = true; ?>
						<tr><td class="px-2 py-1 text-sm text-slate-700"><?php echo h($s['code'] ?: $s['name']); ?></td>
						<?php for($p = 0; $p < $P['P']; $p++) { $pr = $L->probDoubleStart($sid, $p); echo '<td class="heat-cell" style="' . heat($pr, $P['P']) . '">' . round(100 * $pr) . '%</td>'; } ?>
						<td class="text-center text-xs text-slate-500"><?php echo (int)$L->dblCount[$sid]; ?></td></tr>
					<?php } if(!$any) { echo '<tr><td class="text-sm text-slate-500 p-2">No double lessons in the history.</td></tr>'; } ?>
					</tbody>
				</table>
				<p class="mt-2 text-xs text-slate-500">Starts are limited by breaks and by how many labs exist, so spread is partly capacity, not only habit.</p>
			</div>
		</div>
	</div>
<?php } ?>
</div>
<script>$(function() { $('#navAI').addClass('active'); });</script>
<?php require_once 'includes/footer.php'; ?>
