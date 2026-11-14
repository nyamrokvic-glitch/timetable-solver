<?php require_once 'includes/header.php'; require_once 'includes/ui.php'; require_once 'includes/learner.php'; require_admin_page(); ?>
<?php
$tts = db_all($connect, "SELECT t.*, (SELECT COUNT(*) FROM timetable_lessons l WHERE l.timetable_id = t.timetable_id) n FROM timetables t ORDER BY created_at DESC, timetable_id DESC");
$label = array('draft' => array('Draft', 'warning'), 'accepted' => array('Accepted', 'success'), 'historical' => array('Past (learned from)', 'info'), 'archived' => array('Ignored', 'default'));
?>
<div class="space-y-4">
	<nav class="text-sm text-slate-500"><a href="dashboard.php" class="hover:text-indigo-600">Home</a><span class="mx-2">/</span><span class="font-medium text-slate-700">History</span></nav>

	<div class="card">
		<div class="card-head"><i class="glyphicon glyphicon-save"></i> Teach the solver from past timetables</div>
		<div class="card-body">
			<p class="mb-3 text-sm text-slate-600">The solver learns this school's habits from timetables the staff already accepted. Upload each past timetable as a CSV (2&ndash;3 past terms is plenty). Names must match the classes, subjects and teachers set up here; anything that does not match is listed so nothing is silently lost.</p>
			<form id="importForm" action="php_action/importHistory.php" method="post" enctype="multipart/form-data" class="grid gap-3 md:grid-cols-4">
				<input type="text" class="field" name="name" placeholder="Name, e.g. Term 2 2025 (accepted)">
				<input type="text" class="field" name="term" placeholder="Term">
				<input type="file" class="field" name="file" accept=".csv,text/csv">
				<div class="flex items-center gap-3">
					<label class="flex items-center gap-1 text-sm font-normal"><input type="checkbox" name="learn" value="1" checked> learn from it</label>
					<button type="submit" class="btn-tool"><i class="fa fa-upload"></i> Import</button>
				</div>
			</form>
			<p class="mt-2 text-sm"><a class="text-indigo-600" href="php_action/downloadTemplate.php"><i class="fa fa-download"></i> Download the CSV template</a> &middot; columns: class, subject, teacher (optional - inferred from the allocation when blank), day (Mon or 1), period, room (optional)</p>
			<div id="importMsg" class="mt-3"></div>
		</div>
	</div>

	<div class="card">
		<div class="card-head">All timetables</div>
		<div class="card-body">
			<div class="overflow-x-auto">
			<table class="min-w-full divide-y divide-slate-200">
				<thead class="bg-slate-50"><tr>
					<?php foreach(array('Name', 'Term', 'Source', 'Lessons', 'Status', 'Learns from it?', '') as $c) { echo '<th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">' . $c . '</th>'; } ?>
				</tr></thead>
				<tbody class="divide-y divide-slate-100">
				<?php foreach($tts as $t) { $learns = in_array($t['status'], array('accepted', 'historical')); ?>
					<tr>
						<td class="px-4 py-3 text-sm"><a class="text-indigo-600" href="timetable.php?id=<?php echo (int)$t['timetable_id']; ?>"><?php echo h($t['name']); ?></a></td>
						<td class="px-4 py-3 text-sm"><?php echo h($t['term']); ?></td>
						<td class="px-4 py-3 text-sm"><?php echo h($t['source']); ?></td>
						<td class="px-4 py-3 text-sm"><?php echo (int)$t['n']; ?></td>
						<td class="px-4 py-3 text-sm"><label class="label label-<?php echo $label[$t['status']][1]; ?>"><?php echo $label[$t['status']][0]; ?></label></td>
						<td class="px-4 py-3 text-sm">
							<?php if($t['status'] == 'draft') { echo '<span class="text-slate-400">accept it first</span>'; }
							else { echo '<label class="flex items-center gap-2 font-normal"><input type="checkbox" class="learnToggle" data-id="' . (int)$t['timetable_id'] . '"' . ($learns ? ' checked' : '') . ($t['status'] == 'accepted' ? ' data-accepted="1"' : '') . '> ' . ($learns ? 'yes' : 'no') . '</label>'; } ?>
						</td>
						<td class="px-4 py-3 text-sm"><a href="php_action/exportTimetable.php?id=<?php echo (int)$t['timetable_id']; ?>" class="text-slate-500"><i class="fa fa-download"></i></a></td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
			</div>
		</div>
	</div>
</div>
<script src="custom/js/history.js"></script>
<?php require_once 'includes/footer.php'; ?>
