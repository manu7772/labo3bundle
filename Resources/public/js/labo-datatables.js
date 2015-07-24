$(document).ready(function() {

	var dataTable_language = {
		fr: {
			decimal:				",",
			processing:				"Traitement en cours...",
			search:					"Rechercher&nbsp; ",
			lengthMenu:				"Afficher _MENU_ &eacute;l&eacute;ments",
			info:					"Affichage de l'&eacute;lement _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
			infoEmpty:				"Affichage de l'&eacute;lement 0 &agrave; 0 sur 0 &eacute;l&eacute;ments",
			infoFiltered:			"(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
			infoPostFix:			"",
			loadingRecords: 		"Chargement en cours...",
			zeroRecords:			"Aucun &eacute;l&eacute;ment &agrave; afficher",
			emptyTable:				"Aucune donnée disponible dans le tableau",
			paginate: {
				first:				"Premier",
				previous:			"Pr&eacute;c&eacute;dent",
				next:				"Suivant",
				last:				"Dernier"
			},
			aria: {
				sortAscending:		": activer pour trier la colonne par ordre croissant",
				sortDescending: 	": activer pour trier la colonne par ordre décroissant"
			},
		},
	};

	// alert("Nombre de tableaux : "+$('.dataTable').length);
	if($('.dataTable').length) {
		$('.dataTable').each(function(index) {
			// var dtJQObjId = $(this);
			$(this).DataTable({
				responsive:			true,
				language:			dataTable_language.fr,
				stateSave:			true,
				// stateLoadCallback: function (settings) {
				// 	// alert('Trouvé Id : '+dtJQObj.attr('id'));
				// 	if(JSdata.get('dtParams-'+dtJQObjId.attr('id')) !== undefined) {
				// 		// alert("LOAD :\n"+JSdata.get('dtParams'));
				// 		return $.parseJSON(JSdata.get('dtParams-'+dtJQObjId.attr('id')));
				// 	} else {
				// 		// alert("LOAD :\naucune donnée trouvée pour cette page");
				// 		return null;
				// 	}
				// },
				// stateSaveCallback:	function (settings, data) {
				// 	passdata = {
				// 		"UrlI": JSdata.get('UrlI'),
				// 		"DtId": dtJQObjId.attr('id'),
				// 		"data": data
				// 	};
				// 	$.ajax( {
				// 		url: JSdata.get('datatables_statesave'),
				// 		data: passdata,
				// 		dataType: "json",
				// 		type: "POST",
				// 		success: function(json) {
				// 			retour = $.parseJSON(json);
				// 			if(retour.result != true) {
				// 				// alert('Erreur à l\'enregistrement de vos paramètres de tri');
				// 			}
				// 			// alert("SAVE :\n"+retour.data);
				// 			// alert(
				// 			// 	'• Json data : result = '+retour.result
				// 			// 	+'\n• Json data : message = '+retour.message
				// 			// 	+'\n• Json data parsed = \n'+$.parseJSON(retour.data)
				// 			// 	+'\n• Json data : data = \n'+retour.data
				// 			// 	// +'\nRéf. page : '+UrlI
				// 			// 	// +'\nJson data : nom = \n'+retour.data.UrlI
				// 			// );
				// 		},
				// 		// error: function(json) {
				// 		// },
				// 	});
				// }
			});
		});
	}

});