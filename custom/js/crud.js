/**
 * Generic add / edit / remove controller used by every "Manage ..." page.
 * Same behaviour as the reference project's brand.js / categories.js, written once.
 *
 *   crud = new Crud({
 *     url: 'php_action/teachers.php', table: '#manageTable',
 *     addForm: '#addForm', editForm: '#editForm', editModal: '#editModal', removeModal: '#removeModal',
 *     required: { teacher_name: 'Teacher name' },
 *     fill: function(form, row) { ... }      // optional: extra work when the edit form is filled
 *   });
 */
function Crud(cfg) {
	var self = this;
	this.cfg = cfg;
	this.table = $(cfg.table).DataTable({
		'ajax': cfg.url + '?action=fetch' + (cfg.query || ''),
		'order': [],
		'pageLength': 25
	});

	function flash($box, response) {
		var cls = response.success ? 'alert-success' : 'alert-danger';
		var icon = response.success ? 'ok-sign' : 'exclamation-sign';
		$box.html('<div class="alert ' + cls + '">' +
			'<button type="button" class="close" data-dismiss="alert">&times;</button>' +
			'<strong><i class="glyphicon glyphicon-' + icon + '"></i></strong> ' + response.messages + '</div>');
		if(response.success) {
			setTimeout(function() { $box.find('.alert').fadeOut(300, function() { $(this).remove(); }); }, 3500);
		}
	}

	function validate($form) {
		$form.find('.text-danger').remove();
		$form.find('.form-group').removeClass('has-error').removeClass('has-success');
		var ok = true;
		$.each(cfg.required || {}, function(name, label) {
			var $f = $form.find('[name="' + name + '"]');
			if($f.length && $.trim($f.val()) === '') {
				$f.after('<p class="text-danger">' + label + ' field is required</p>');
				$f.closest('.form-group').addClass('has-error');
				ok = false;
			} else {
				$f.closest('.form-group').addClass('has-success');
			}
		});
		return ok;
	}

	function submit($form, $btn, reset) {
		if(!validate($form)) { return false; }
		$btn.button('loading');
		$.ajax({
			url: $form.attr('action'),
			type: 'post',
			data: $form.serialize(),
			dataType: 'json',
			success: function(response) {
				$btn.button('reset');
				flash($form.find('.form-messages'), response);
				if(response.success) {
					self.table.ajax.reload(null, false);
					if(reset) {
						$form[0].reset();
						$form.find('.text-danger').remove();
						$form.find('.form-group').removeClass('has-error').removeClass('has-success');
					}
					if(cfg.onSaved) { cfg.onSaved(response); }
				}
			},
			error: function() { $btn.button('reset'); flash($form.find('.form-messages'), {success: false, messages: 'Request failed - please refresh the page'}); }
		});
		return false;
	}

	// add
	$(cfg.addForm).unbind('submit').bind('submit', function() {
		return submit($(this), $(this).find('button[type=submit]'), true);
	});

	// edit (form is filled by self.edit)
	$(cfg.editForm).unbind('submit').bind('submit', function() {
		return submit($(this), $(this).find('button[type=submit]'), false);
	});
}

Crud.prototype.edit = function(id) {
	var self = this, cfg = this.cfg, $form = $(cfg.editForm);
	$form.find('.text-danger').remove();
	$form.find('.form-group').removeClass('has-error').removeClass('has-success');
	$form.find('.form-messages').empty();
	$form.find('[name=id]').remove();
	$(cfg.editModal).find('.modal-loading').removeClass('div-hide');
	$(cfg.editModal).find('.form-fields, .modal-footer').addClass('div-hide');
	$(cfg.editModal).modal('show');

	$.ajax({
		url: cfg.url + '?action=get', type: 'post', data: {id: id}, dataType: 'json',
		success: function(row) {
			$(cfg.editModal).find('.modal-loading').addClass('div-hide');
			$(cfg.editModal).find('.form-fields, .modal-footer').removeClass('div-hide');
			if(!row || row.success === false) {
				$form.find('.form-messages').html('<div class="alert alert-danger">' + (row && row.messages ? row.messages : 'Could not load the record') + '</div>');
				return;
			}
			$.each(row, function(name, value) {
				if($.isArray(value)) {
					$form.find('input[type=checkbox][name="' + name + '[]"]').each(function() {
						$(this).prop('checked', $.inArray(parseInt($(this).val(), 10), $.map(value, function(v) { return parseInt(v, 10); })) !== -1);
					});
				} else {
					var $f = $form.find('[name="' + name + '"]');
					if($f.is(':checkbox')) { $f.prop('checked', String(value) === '1'); } else { $f.val(value); }
				}
			});
			$form.append('<input type="hidden" name="id" value="' + id + '" />');
			if(cfg.fill) { cfg.fill($form, row); }
		}
	});
};

Crud.prototype.remove = function(id) {
	var self = this, cfg = this.cfg, $modal = $(cfg.removeModal);
	$modal.find('.remove-modal-messages').empty();
	$modal.modal('show');
	$modal.find('.btn-danger').unbind('click').bind('click', function() {
		var $btn = $(this);
		$btn.button('loading');
		$.ajax({
			url: cfg.url + '?action=remove', type: 'post', data: {id: id}, dataType: 'json',
			success: function(response) {
				$btn.button('reset');
				if(response.success) {
					$modal.modal('hide');
					self.table.ajax.reload(null, false);
					$('.remove-messages').html('<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button>' +
						'<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> ' + response.messages + '</div>');
					setTimeout(function() { $('.remove-messages .alert').fadeOut(300, function() { $(this).remove(); }); }, 3500);
				} else {
					$modal.find('.remove-modal-messages').html('<div class="alert alert-danger">' + response.messages + '</div>');
				}
			}
		});
	});
};
