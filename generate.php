<?php require_once 'includes/header.php'; require_once 'includes/ui.php'; require_once 'includes/learner.php'; require_admin_page(); ?>
<?php
$P = load_problem($connect);
$pre = preflight_checks($P);
$L = Learner::fromDb($connect, $P['D'], $P['P']);
$nBlocks = 0; foreach($P['allocs'] as $a) { $nBlocks += $a['ppw']; }
?>
<div class="space-y-4">
	<nav class="text-sm text-slate-500"><a href="dashboard.php" class="hover:text-indigo-600">Home</a><span class="mx-2">/</span><span class="font-medium text-slate-700">Generate Timetable</span></nav>

	<div class="grid gap-4 lg:grid-cols-3">
		<div class="card lg:col-span-2">
			<div class="card-head"><i class="glyphicon glyphicon-circle-arrow-right"></i> Generate a new timetable</div>
			<div class="card-body">
				<form id="genForm" action="php_action/generateTimetable.php" method="post" class="space-y-4">
					<div class="grid gap-4 md:grid-cols-2">
						<div><label class="mb-1 block text-sm font-medium text-slate-700">Name</label><input type="text" class="field" name="name" value="Timetable <?php echo date('Y'); ?>"></div>
						<div><label class="mb-1 block text-sm font-medium text-slate-700">Term</label><input type="text" class="field" name="term" placeholder="e.g. Term 1 <?php echo date('Y') + 1; ?>"></div>
						<div><label class="mb-1 block text-sm font-medium text-slate-700">Search time (seconds) - longer = tidier</label><input type="number" class="field" name="time_limit" min="2" max="90" value="10"></div>
						<div><label class="mb-1 block text-sm font-medium text-slate-700">Random seed (optional, to repeat a run)</label><input type="number" class="field" name="seed" placeholder="leave blank"></div>
					</div>
					<label class="flex items-start gap-2 text-sm font-normal text-slate-700"><input type="checkbox" name="use_history" value="1" <?php echo $L->hasHistory() ? 'checked' : 'disabled'; ?> class="mt-1">
						<span><b>Learn from this school's past timetables</b> <?php echo $L->hasHistory() ? '(' . $L->nTimetables . ' timetables, ' . number_format($L->nLessons) . ' lessons)' : '- none yet: add some on the History page'; ?></span></label>
					<label class="flex items-start gap-2 text-sm font-normal text-slate-700"><input type="checkbox" name="compare" value="1" <?php echo $L->hasHistory() ? 'checked' : 'disabled'; ?> class="mt-1">
						<span>Also run a <b>generic</b> solver for comparison (takes half as long again)</span></label>
					<div class="flex items-center gap-3">
						<button type="submit" id="genBtn" class="btn-tool"><i class="fa fa-cogs"></i> Generate timetable</button>
						<span id="genWait" class="text-sm text-slate-600 div-hide"><i class="fa fa-spinner fa-pulse"></i> Solving <?php echo count($P['allocs']); ?> allocations (<?php echo $nBlocks; ?> lessons)&hellip; please wait</span>
					</div>
				</form>
			</div>
		</div>

		<div class="card">
			<div class="card-head">Before you run</div>
			<div class="card-body text-sm">
				<?php if(!$pre) { echo '<p class="text-green-700"><i class="fa fa-check-circle"></i> The data looks consistent: no teacher, room or class is over capacity.</p>'; }
				foreach($pre as $x) { echo '<p class="mb-2 ' . ($x[0] == 'error' ? 'text-red-700' : 'text-amber-700') . '"><i class="fa ' . ($x[0] == 'error' ? 'fa-times-circle' : 'fa-exclamation-triangle') . '"></i> ' . h($x[1]) . '</p>'; } ?>
				<p class="mt-3 text-xs text-slate-500"><?php echo count($P['classes']); ?> classes, <?php echo count($P['teachers']); ?> teachers, <?php echo count($P['rooms']); ?> special rooms, <?php echo $P['D']; ?> days &times; <?php echo $P['P']; ?> periods.</p>
			</div>
		</div>
	</div>

	<div id="result" class="div-hide"></div>
</div>
<script src="custom/js/generate.js"></script>
<?php require_once 'includes/footer.php'; ?>
