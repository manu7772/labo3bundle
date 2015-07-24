$(document).ready(function() {

	/* initialisation CHOSEN */
	var config = {
		'.chosen-select'           : {},
		'.chosen-select-deselect'  : {allow_single_deselect:true},
		'.chosen-select-no-single' : {disable_search_threshold:5},
		'.chosen-select-no-results': {no_results_text:'Aucun résultat…'},
		'.chosen-select-width'     : {width:"95%"},
		'.chosen-select-max1'      : {max_selected_options:1},
	}
	for (var selector in config) {
		$(selector).chosen(config[selector]);
	}

	$('.chosen-multi-max1').chosen({
		allow_single_deselect:		true,
		disable_search_threshold:	5,
		no_results_text:			'Aucun résultat…',
		width:						"95%",
		max_selected_options:		1
	});

	$('body .chosen-container').css({width: '100%'});
	// $('select').chosen({width: '100%'});
	// $('body').on('click', 'a[data-toggle="tab"]', function(elem) {
	// 	// cible = $(this).attr('href');
	// 	// alert(cible);
	// 	$('body ' + $(this).attr('href') + ' .chosen-container').css({width: '100%'});
	// });

});