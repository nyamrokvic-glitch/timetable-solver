<?php require_once 'includes/header.php'; require_once 'includes/ui.php'; require_admin_page(); ?>
<?php $rules = db_all($connect, "SELECT * FROM rules ORDER BY sort_no"); ?>
<div class="space-y-4">
	<nav class="text-sm text-slate-500"><a href="dashboard.php" class="hover:text-indigo-600">Home</a><span class="mx-2">/</span><span class="font-medium text-slate-700">Rules &amp; Weights</span></nav>

	<div class="card">
		<div class="card-head"><i class="glyphicon glyphicon-check"></i> Hard rules <span class="label label-default">never broken</span></div>
		<div class="card-body">
			<ul class="space-y-2">
				<?php foreach($rules as $r) { if($r['kind'] !== 'hard') continue; echo '<li class="flex items-start gap-2 text-sm text-slate-700"><i class="fa fa-lock text-slate-400 mt-1"></i> ' . h($r['rule_label']) . '</li>'; } ?>
			</ul>
			<p class="mt-3 text-xs text-slate-500">If the data makes a lesson impossible to place without breaking one of these, the solver leaves it out and tells you why - it never produces a clash.</p>
		</div>
	</div>

	<div class="card">
		<div class="card-head"><i class="glyphicon glyphicon-wrench"></i> Soft rules <span class="label label-info">how much the school cares</span></div>
		<div class="card-body">
			<p class="mb-3 text-sm text-slate-600">0 = ignore, 10 = very important. Rules marked <span class="label label-success">learned</span> use the school's past accepted timetables, so their cost comes from this school's own habits.</p>
			<form id="rulesForm" action="php_action/rules.php?action=save" method="post">
			<table class="min-w-full divide-y divide-slate-200">
				<thead class="bg-slate-50"><tr><th class="px-4 py-2 text-left text-xs font-semibold uppercase text-slate-500">Rule</th><th class="px-4 py-2 text-left text-xs font-semibold uppercase text-slate-500" style="width:260px">Weight</th></tr></thead>
				<tbody class="divide-y divide-slate-100">
				<?php foreach($rules as $r) { if($r['kind'] !== 'soft') continue; $learned = strpos($r['rule_key'], 'learned_') === 0; ?>
					<tr>
						<td class="px-4 py-3 text-sm text-slate-700"><?php echo h($r['rule_label']); ?></td>
						<td class="px-4 py-3">
							<div class="flex items-center gap-3">
								<input type="range" min="0" max="10" value="<?php echo min(10, (int)$r['weight']); ?>" class="w-full wt-range" data-target="w_<?php echo h($r['rule_key']); ?>">
								<input type="number" min="0" max="20" id="w_<?php echo h($r['rule_key']); ?>" name="weights[<?php echo h($r['rule_key']); ?>]" value="<?php echo (int)$r['weight']; ?>" class="field" style="width:70px">
							</div>
						</td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
			<div class="mt-4 flex items-center gap-3"><button type="submit" class="btn-tool success"><i class="fa fa-save"></i> Save weights</button><span id="rulesMsg" class="text-sm"></span></div>
			</form>
		</div>
	</div>
</div>
<script>
$(function() {
	$('#navSetup, #subNavRules').addClass('active');
	$('.wt-range').on('input', function() { $('#' + $(this).data('target')).val($(this).val()); });
	$('#rulesForm input[type=number]').on('input', function() { $('[data-target=' + this.id + ']').val($(this).val()); });
	$('#rulesForm').on('submit', function() {
		$.post($(this).attr('action'), $(this).serialize(), function(r) { $('#rulesMsg').attr('class', 'text-sm ' + (r.success ? 'text-green-700' : 'text-red-700')).text(r.messages); }, 'json');
		return false;
	});
});
</script>
<?php require_once 'includes/footer.php'; ?>
