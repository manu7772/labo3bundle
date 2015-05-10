jQuery(document).ready(function($) {
	
	if($('#tooltipboostrap').length) {
		$('#tooltipboostrap').Tooltip();
	}
	
	var refreshBalises = function(message, entite, id) {
		if(id == undefined) id = "";
		// alert("Balises : "+"ajaxUpdate-"+entite+id);
		// alert(".ajxu-"+entite+id);
		$target = $(".ajxu-"+entite+id);
		URL = $target.attr('data-prototype');
		$target.each(function() {
			// // méthode $.post --> faire une réponse texte avec new Response() dans le controller !!!
			// y = $(this);
			// $.post( URL, function( data ) {
			// 	$dat = $(data);
			// 	$dat.unwrap();
			// 	y.html( $dat.html() );
			// });	
			// $tar = $(elem);
			$.ajax({
				type: "POST",
				url: URL,
				context: this,
				dataType: "json",
				error: function() {
					ConsoleResult("Cible : ", "Erreur...", true);
				},
				complete: function(json) {
					JR = JsonResult(json);
					$repl = $(JR.html).unwrap();
					// alert($repl.html());
					if(JR != false) $(this).html($repl.html());
				}
			});
		});
	}

});


