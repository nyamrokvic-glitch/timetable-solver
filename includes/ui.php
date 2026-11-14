<?php
/** Small view helpers so every CRUD page renders the same Tailwind markup as the reference project. */

$UI_INPUT_CLASS = 'form-control block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200';

function ui_input($prefix, $name, $label, $type = 'text', $extra = '') {
	global $UI_INPUT_CLASS;
	return '<div class="form-group mb-4">
		<label for="' . $prefix . $name . '" class="mb-1 block text-sm font-medium text-slate-700">' . h($label) . '</label>
		<input type="' . $type . '" class="' . $UI_INPUT_CLASS . '" id="' . $prefix . $name . '" name="' . $name . '" autocomplete="off" ' . $extra . '>
	</div>';
}

function ui_select($prefix, $name, $label, $options, $extra = '', $placeholder = '~~SELECT~~') {
	global $UI_INPUT_CLASS;
	$html = '<div class="form-group mb-4">
		<label for="' . $prefix . $name . '" class="mb-1 block text-sm font-medium text-slate-700">' . h($label) . '</label>
		<select class="' . $UI_INPUT_CLASS . '" id="' . $prefix . $name . '" name="' . $name . '" ' . $extra . '>';
	if($placeholder !== null) { $html .= '<option value="">' . h($placeholder) . '</option>'; }
	foreach($options as $v => $l) { $html .= '<option value="' . h($v) . '">' . h($l) . '</option>'; }
	return $html . '</select></div>';
}

/** A modal containing a form. */
function ui_modal($id, $title, $icon, $formId, $action, $body, $btnId, $btnClass = 'btn-primary') {
	return '<div class="modal fade" id="' . $id . '" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="' . $formId . '" action="' . $action . '" method="POST">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title"><i class="fa ' . $icon . '"></i> ' . h($title) . '</h4>
        </div>
        <div class="modal-body">
          <div class="form-messages"></div>
          <div class="modal-loading div-hide" style="width:50px; margin:auto;padding-top:50px; padding-bottom:50px;">
            <i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i><span class="sr-only">Loading...</span>
          </div>
          <div class="form-fields">' . $body . '</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"> <i class="glyphicon glyphicon-remove-sign"></i> Close</button>
          <button type="submit" class="btn ' . $btnClass . '" id="' . $btnId . '" data-loading-text="Loading..." autocomplete="off"> <i class="glyphicon glyphicon-ok-sign"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>';
}

function ui_remove_modal($id, $btnId, $text = 'Do you really want to remove this?') {
	return '<div class="modal fade" tabindex="-1" role="dialog" id="' . $id . '">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><i class="glyphicon glyphicon-trash"></i> Remove</h4>
      </div>
      <div class="modal-body"><div class="remove-modal-messages"></div><p>' . h($text) . '</p></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"> <i class="glyphicon glyphicon-remove-sign"></i> Close</button>
        <button type="button" class="btn btn-danger" id="' . $btnId . '" data-loading-text="Loading..."> <i class="glyphicon glyphicon-trash"></i> Remove</button>
      </div>
    </div>
  </div>
</div>';
}

/** Page frame for a "Manage X" table page. $columns = list of column titles. */
function ui_manage_page($crumb, $title, $tableId, $columns, $addModalId, $addLabel, $extraTop = '') {
	$th = '';
	foreach($columns as $c) { $th .= '<th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">' . h($c) . '</th>'; }
	return '<div class="space-y-4">
	<nav class="text-sm text-slate-500">
		<a href="dashboard.php" class="hover:text-indigo-600">Home</a>
		<span class="mx-2">/</span>
		<span class="font-medium text-slate-700">' . h($crumb) . '</span>
	</nav>
	<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
		<div class="border-b border-slate-200 px-4 py-3">
			<div class="text-lg font-semibold text-slate-800"><i class="glyphicon glyphicon-edit"></i> ' . h($title) . '</div>
		</div>
		<div class="p-4">
			<div class="remove-messages mb-3"></div>
			<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
				<div>' . $extraTop . '</div>
				<button class="inline-flex items-center rounded-md bg-slate-600 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700" data-toggle="modal" data-target="#' . $addModalId . '">
					<i class="glyphicon glyphicon-plus-sign"></i> ' . h($addLabel) . '
				</button>
			</div>
			<div class="overflow-x-auto">
				<table class="min-w-full divide-y divide-slate-200" id="' . $tableId . '">
					<thead class="bg-slate-50"><tr>' . $th . '</tr></thead>
				</table>
			</div>
		</div>
	</div>
</div>';
}

function ui_row_buttons($id) {
	return '<div style="white-space:nowrap">
	  <a type="button" class="btn btn-primary btn-sm" onclick="crud.edit(' . (int)$id . ')"><i class="glyphicon glyphicon-edit"></i> Edit</a>
	  <a type="button" class="btn btn-danger btn-sm" onclick="crud.remove(' . (int)$id . ')"><i class="glyphicon glyphicon-trash"></i> Remove</a>
	</div>';
}

function ui_status_label($active) {
	return $active == 1 ? "<label class='label label-success'>Available</label>" : "<label class='label label-danger'>Not Available</label>";
}

?>
