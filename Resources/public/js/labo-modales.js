$(document).ready(function() {

	/* initialisation Modales */
	$("body").on('click', ".launch-modal-delete", function() {
		if($("#delete_modal").length) {
			// $newmodaldelete = $("#delete_modal").clone();
			name = $(this).attr('data-elements');
			href = $(this).attr('data-href');
			$("#delete_modal #modal-delete-name").text(name);
			$("#delete_modal #modal-delete-href").attr('href', href);
		} else {
			// alert('Éléments de modale "delete" absents.');
		}
	});

	var dontPrevent = false;

	$('form').submit(function(e) {
		var parentForModal = $(this);
		method = $(this).find("[name='_method']").first().attr('value');
		if(dontPrevent == false && method == 'DELETE') {
			e.preventDefault();
			if($("#delete_modal_form").length) {
				// modale présente…
				name = $("#form_submit", this).attr('data-name');
				if(name == undefined) name = "cet élément";
				$("#delete_modal_form #modal-form-delete-name").text(name);
				$("#delete_modal_form").modal();
				$('body').on('click', "#delete_modal_form #modal-delete-action", function(e) {
					dontPrevent = true;
					parentForModal.submit();
				});
			} else {
				// pas de modale présente, on utilise une confirm javascript
				name = $("#form_submit", this).attr('data-name');
				if(name == undefined) name = "cet élément";
				result = confirm('Supprimer ' + name + ' ?');
				if(result) {
					dontPrevent = true;
					$(this).submit();
				}
			}
		}
	});

	var confirmSubmit = function() {

	}

});