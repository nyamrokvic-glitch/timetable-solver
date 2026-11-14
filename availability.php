<?php require_once 'includes/header.php'; require_once 'includes/ui.php'; require_admin_page(); ?>
<?php
$days = day_names($connect); $periods = period_rows($connect);
$teachers = db_all($connect, "SELECT teacher_id, teacher_name, part_time FROM teachers WHERE teacher_status = 1 ORDER BY teacher_name");
$sel = isset($_GET['teacher']) ? (int)$_GET['teacher'] : ($teachers ? (int)$teachers[0]['teacher_id'] : 0);
?>
<div class="space-y-4">
	<nav class="text-sm text-slate-500"><a href="dashboard.php" class="hover:text-indigo-600">Home</a><span class="mx-2">/</span><span class="font-medium text-slate-700">Teacher Availability</span></nav>

	<div class="card">
		<div class="card-head"><i class="glyphicon glyphicon-calendar"></i> Teacher Availability</div>
		<div class="card-body">
			<p class="mb-3 text-sm text-slate-600">Click a slot to cycle it: <span class="avail-cell avail-ok" style="display:inline-block;padding:2px 10px">available</span>
				&rarr; <span class="avail-cell avail-avoid" style="display:inline-block;padding:2px 10px">prefer to avoid</span> (soft - the solver tries to honour it)
				&rarr; <span class="avail-cell avail-no" style="display:inline-block;padding:2px 10px">unavailable</span> (hard - never scheduled). Click a day or period header to set the whole row/column.</p>
			<div class="mb-4 flex flex-wrap items-center gap-3">
				<select id="teacherSel" class="field" style="width:auto">
					<?php foreach($teachers as $t) { echo '<option value="' . (int)$t['teacher_id'] . '"' . ($t['teacher_id'] == $sel ? ' selected' : '') . '>' . h($t['teacher_name']) . ($t['part_time'] ? ' (part-time)' : '') . '</option>'; } ?>
				</select>
				<button type="button" id="saveAvail" class="btn-tool success"><i class="fa fa-save"></i> Save availability</button>
				<span id="availMsg" class="text-sm"></span>
			</div>
			<div class="overflow-x-auto">
				<table class="w-full" style="table-layout:fixed;min-width:520px" id="availGrid">
					<thead><tr><th style="width:110px"></th>
						<?php foreach($days as $i => $d) { echo '<th class="text-xs uppercase text-slate-500 cursor-pointer day-head" data-day="' . ($i + 1) . '">' . h($d) . '</th>'; } ?>
					</tr></thead>
					<tbody>
					<?php foreach($periods as $p) { ?>
						<tr>
							<td class="text-xs text-slate-600 cursor-pointer period-head" data-period="<?php echo (int)$p['period_no']; ?>">P<?php echo (int)$p['period_no']; ?> <small class="text-slate-400"><?php echo h($p['start_time']); ?></small></td>
							<?php foreach($days as $i => $d) { echo '<td><div class="avail-cell avail-ok" data-day="' . ($i + 1) . '" data-period="' . (int)$p['period_no'] . '" data-state="ok">&#10003;</div></td>'; } ?>
						</tr>
					<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<script src="custom/js/availability.js"></script>
<?php require_once 'includes/footer.php'; ?>
