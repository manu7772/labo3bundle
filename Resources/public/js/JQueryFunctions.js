
////////////////////////////////
// JsonResult : traitement réponse AJAX
////////////////////////////////

// var parsJson = function(json) {
// 	var rep = eval('(' + json + ')');
// 	ConsoleResult("######## Message retour (parsJson) : ", rep.result, true);
// 	return rep;
// }

var JsonResult = function(json) {
	ResponseArray = new Array();
	if(parseInt(json.status) != 200) {
		ConsoleResult('Erreur ', parseInt(json.status), true);
		ResponseArray["html"] = "<p>BAD RETURN "+parseInt(json.status)+"!!!</p>";
		return ResponseArray;
	} else ConsoleResult('Statut Retour ', parseInt(json.status), true);
	if(typeof json.responseText != "undefined") {
		ResponseArray = $.parseJSON(json.responseText);
		CR = new ConsoleResult();
		CR.add("Résultat : ", ResponseArray["result"]);
		CR.add("Message : ", ResponseArray["message"]);
		CR.add("Retour html : ", ResponseArray["html"].length);
		CR.show();
		return ResponseArray;
	} else {
		ResponseArray["html"] = "<p>BAD RETURN !!! json.responseText = undefined</p>";
		return ResponseArray;
	}
}


var modedev = false;
if(($("#modedev").val() == "dev") || ($("#modedev").val() == "test")) modedev = true;

// Affichage en console (uniquement en mode dev ou test)
// libelle :			libellé de l'information
// texte :				texte de l'information
// afficheToutDeSuite : affiche l'information aussitôt (sans appeler "show()")
// force :				affiche sans condition de mode (même si modedev=false)
var ConsoleResult = function(libelle, texte, afficheToutDeSuite, force) {
	var objCRparent = this;
	this.lib = Array();
	this.tx = Array();
	this.cpt = 0;
	this.frc = force;
	this.show = function(force) {
		if((modedev == true || objCRparent.frc == true || force == true) && objCRparent.lib.length > 0)
		for(i in objCRparent.lib) {
			console.log(objCRparent.lib[i],objCRparent.tx[i]);
		}
	}
	this.add = function(libelle, texte) {
		if(libelle && (texte != null)) {
			if(texte == false) texte = "(boolean) false";
			if(texte == true) texte = "(boolean) true";
			objCRparent.lib[objCRparent.cpt] = libelle+"";
			objCRparent.tx[objCRparent.cpt] = texte+"";
			objCRparent.cpt++;
		}
		return this.objCRparent;
	}
	if(libelle && texte) this.add(libelle, texte);
	if(afficheToutDeSuite == true) this.show();
}

ConsoleResult("mode DEV : ", modedev, true);

$.fn.tagName = function() {
	return this.get(0).tagName.toLowerCase();
}

var PopUpMessage = function(titre, message, time) {
	if(time === undefined) time = 0;
	$win = $('<div class="PopUpMessage" title="'+titre+'"><p>'+message+'</p></div>');
	$win.dialog({
		modal: true,
		width: 500,
		height: "auto",
		show: {
			effect: "fadeIn",
			duration: 500
  		},
  		hide: {
			effect: "fadeOut",
			duration: 500
 		 },
		buttons: {"Fermer": function(){$(this).dialog("close");}},
		close: function() {
			$(this).dialog("destroy").remove();
		},
		create: function() {
			// $(".ui-dialog-title", $(this)).hide();
		}
	});
	if(time > 0) {
		setTimeout(function() { $(".PopUpMessage").dialog('close'); }, time);
	}
	$(".PopUpMessage").bind('clickoutside', function(e) {
		$target = $(e.target);
		if (!$target.filter('.hint').length && !$target.filter('.hintclickicon').length) {
			$(this).dialog('close');
		}
	});
}



/* **************************************************** */
/* MISES A JOUR BLOCS avec majZone
/* **************************************************** */
var majZone = function(zone) {
	// alert("Mise à jour "+zone);
	var nomZone = zone;
	var $groupe = $('.groupe'+zone);
	$groupe.each(function(){
		if($(this).hasClass("majZone")) { // il faut que la zone contienne la classe "majZone" !!!
			var $target = $(this).parent();
			var data = new Object();
			proto = $(this).attr('data-prototype').split("__");
			url = proto[0];
			data["majZone"] = "majZone"; // signature majZone
			data["methode"] = proto[1]; // méthode du controller à appeler
			// récupération des paramètres
			$("input", $(this)).each(function() {
				// alert(nom+" = "+$(this).val());
				data[$(this).attr('class')] = $(this).val();
			});
			ConsoleResult("data send " + zone + " : ", data, true);
			$.ajax({
				type: "POST",
				url: url,
				data: data,
				error: function() {
					ConsoleResult("Désolé : ", "une erreur est survenue. Veuillez recommencer s.v.p.", true);
				}
			}).done( function(data) {
				ConsoleResult("Retour majZone : ", data, true);
				retour = $.parseJSON(data);
				if(retour.result == true) $target.html(retour.html);
			});
		}
	});
}

function launch_fb_register() {
	// alert("Test inscription !");
	$(".various").fancybox({
		// fitToView	: true,
		// closeBtn	: false,
		maxWidth	: 640,
		width		: 640,
		// height		: '50%',
		// autoSize	: true,
		closeClick	: false,
		openEffect	: 'fade',
		closeEffect	: 'fade',
		title 		: false,
		padding     : 6,
		helpers : {
			overlay : {
				css : {
					'background' : backgroundFCY
				}
			}
		}
	}).trigger('click');
}



jQuery(document).ready(function($) {

	$(".alert").alert();

	////////////////////////////////
	// personnalisation select   //
	// Chosen                   //
	/////////////////////////////
	$("select").attr("data-placeholder", "Selectionner...");
	$("select").chosen({
		no_results_text:'Aucun élément trouvé…',
		allow_single_deselect:true,
		disable_search_threshold:10,
		allow_single_deselect: true
	});

	////////////////////////////////
	// Formulaires dynammiques   //
	//////////////////////////////
	// $("body").on("change", ".dynform select, .dynform input", function() {
	// 	alert("Formulaire modifié");
	// });
	
	$( ".datepicker" ).datepicker({
		// dates : futures uniquement
		minDate: 0,
		dateFormat: "dd-mm-yy"
	});

	$( ".datepicker2" ).datepicker({
		// dates : min - 1 mois / max + 1 mois et 10 jours
		minDate: "-1M",
		maxDate: "+1M +10D",
		dateFormat: "dd-mm-yy"
	});

	$( ".datepickerAll" ).datepicker({
		// toutes dates
		dateFormat: "dd-mm-yy"
	});

	// Initialisation du chemin
	if($("#hiddenStuffs input#homepath").length)
		tinyMCE.baseURL = $("#hiddenStuffs input#homepath").val().replace("app_dev.php/", "") + "bundles/labotestmanu/js/tinymce";
	tinyMCE.init({
		language : 'fr_FR',
		selector: 'form .richtexts',
		plugins: [
		    "advlist autolink lists link image charmap print preview anchor",
		    "searchreplace visualblocks code fullscreen",
		    "insertdatetime media table contextmenu paste"
		],
		toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
	});

	/* **************************************************** */
	/* Liens externes -> dans une nouvelle fenêtre
	/* **************************************************** */
	$(".URLext").on("click", function(event) {
		URL = $(this).attr("href");
		if(URL == undefined) URL = $(">a", this).first().attr("href");
		// alert(URL);
		window.open(URL);
		event.preventDefault();
		return false;
	});

	$(".JSconfirm").on("click", function(event) {
		event.preventDefault();
		URL = $(this).attr("href");
		if(URL == undefined) URL = $(">a", this).first().attr("href");
		var confm = confirm("Souhaitez-vous supprimer cette ligne ?");
		if(confm == true) document.location = URL;
			else return false;
	});

	// Désactivation des liens sur les <a href="#">
	$("a").each(function() {
		if($(this).attr('href') == "#") {
			// $(this).css('cursor', 'default');
			$(this).addClass('disabled');
		}
	});
	// Désactivation des liens "disabled"
	$("body").on("click", ".disabled", function(event) {
		event.preventDefault();
		return false;
	});
	// Liens javascript:history.back();
	$("body").on("click", ".backpage", function() { history.back(); })
	$("body").on("click", ".homepage", function() {
		var homepath = $("#homepath").val();
		document.location = homepath;
	});

	/* **************************************************** */
	/* FANCYBOX
	/* **************************************************** */

	var backgroundFCY = 'rgba(100, 0, 0, 0.90)';

	$('.fancybox').fancybox({
		openEffect	: 'fade',
		closeEffect	: 'fade',
        padding     : 6,
		helpers : {
			overlay : {
				css : {
					'background' : backgroundFCY
				}
			}
		}
	});

	setTimeout("launch_fb_register()", 8000);

	$(".youtubeFancy").fancybox({
		maxWidth	: 800,
		maxHeight	: 600,
		fitToView	: true,
		// width		: '70%',
		// height		: '70%',
		autoSize	: true,
		closeClick	: false,
		openEffect	: 'fade',
		closeEffect	: 'fade',
        padding     : 6,
		helpers : {
			overlay : {
				css : {
					'background' : backgroundFCY
				}
			}
		}
	});

	// // TEST MDP POPUP
	// $('body').on("Change", "#labo_testmanubundle_tempuser_mdp2", function() {
	// 	alert("Mot de passe 2 changé !");
	// });

	/* **************************************************** */
	/* TINYMCE --> directeditor richtext
	/* **************************************************** */

	// Initialisation du chemin --> fait plus haut (pour formulaires)
	// tinyMCE.baseURL = $("#hiddenStuffs input#homepath").val().replace("app_dev.php/", "") + "bundles/labotestmanu/js/tinymce";

	// Sauvegarde son propre contenu
	var saveHtml = function(elem) {
		// CR = new ConsoleResult();
		// CR.add(elem.id, $("#"+elem.id).html());
		// CR.add(elem.id, $("#"+elem.id).attr('data-prototype'));
		// CR.show();
		$("#"+elem.id).data("texte", tinyMCE.html.Entities.decode($("#"+elem.id).html()));
		path = $("#"+elem.id).attr('data-prototype').split("___");
		$("#"+elem.id).data("pathSend", path[0]);
		$("#"+elem.id).data("pathShort", path[1]);
		$("#"+elem.id).data("pathGet", path[2]);
		$("#"+elem.id).removeAttr('data-prototype');
	}

	var saveTinyChanges = function(elem) {
		texte = tinyMCE.html.Entities.decode(elem.getContent());
		ConsoleResult("Path : ", $("#"+elem.id).data("pathSend"), true);
		ConsoleResult("Changement POST", elem.id + " : " + texte, true);
		// $("#"+elem.id).data("texte", tinyMCE.html.Entities.decode(elem.getContent()));
		$.ajax({
			type: "POST",
			url: $("#"+elem.id).data("pathSend"),
			data: {'data': texte},
			error: function() {
				ConsoleResult("Erreur : ", "changement", true);
			},
			success: function() {
				ConsoleResult("Succès : ", "chargement", true);
			}
		}).done( function(data) {
			retour = eval('('+data+')');
			ConsoleResult("Retour enregistrement : ", data, true);
			$("#"+elem.id).load($("#"+elem.id).data("pathShort"));
		});
	}

	tinyMCE.init({
		// language : 'fr_FR',
		selector: ".editableRich",
		inline: true,
		plugins: [
			"advlist autolink lists link image charmap print preview anchor",
			"searchreplace visualblocks code fullscreen",
			"insertdatetime media table contextmenu paste"
		],
		toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image",
		// toolbar: "undo redo | styleselect | bold italic "
		// setup : function(ed){
		// 	saveHtml(ed);
		// 	ed.on('blur', function(e) {
		// 		if($("#"+ed.id).data("texte") != tinyMCE.html.Entities.decode(ed.getContent())) saveTinyChanges(ed);
		// 	}).on('focus', function(e) {
		// 		$("#"+ed.id).load($("#"+ed.id).data("pathGet"))
		// 	}).on('click', function(e) {
		// 		e.preventDefault();
		// 		return false;
		// 	});
		// }
	});

	tinyMCE.init({
		selector: ".editable",
		inline: true,
		plugins: ["insertdatetime media contextmenu"],
		toolbar: "undo redo",
		menubar: false,
		setup : function(ed){
			saveHtml(ed);
			ed.on('blur', function(e) {
				if($("#"+ed.id).data("texte") != tinyMCE.html.Entities.decode(ed.getContent())) saveTinyChanges(ed);
			}).on('focus', function(e) {
				$("#"+ed.id).load($("#"+ed.id).data("pathGet"))
			}).on('click', function(e) {
				e.preventDefault();
				return false;
			});;
		}
	});

	tinyMCE.init({
		selector: ".editablePrix",
		inline: true,
		plugins: ["insertdatetime media contextmenu"],
		toolbar: false,
		menubar: false,
		setup : function(ed){
			saveHtml(ed);
			ed.on('blur', function(e) {
				if($("#"+ed.id).data("texte") != tinyMCE.html.Entities.decode(ed.getContent())) saveTinyChanges(ed);
			}).on('focus', function(e) {
				$("#"+ed.id).load($("#"+ed.id).data("pathGet"))
			}).on('click', function(e) {
				e.preventDefault();
				return false;
			});;
		}
	});

	var col = "#5F5"; // couleur highlight
	var clignote = function(elem) {
		// bgcol = $(elem).css("backgroundColor");
		bgcol = "transparent";
		$(elem)
			.animate({backgroundColor: col}, 200)
			.delay(200).animate({backgroundColor: bgcol}, 200)
			.delay(100).animate({backgroundColor: col}, 30)
			.delay(50).animate({backgroundColor: bgcol}, 50)
			.delay(50).animate({backgroundColor: col}, 30)
			.delay(50).animate({backgroundColor: bgcol}, 50);
	}
	var highlight = function(elem) {
		// bgcol = $(elem).css("backgroundColor");
		bgcol = "transparent";
		$(elem)
			.animate({backgroundColor: col}, 100)
			.delay(200)
			.animate({backgroundColor: bgcol}, 300);
	}

	// Équivalent JS de PHP in_array()
	// Array.prototype.inArray = function(val) {
	// 	for(var count = 0; count < this.length; count++) {
	// 		if(this[count] == val) return true;
	// 	}
	// 	return false;
	// }

	// masquage automatique d'éléments (balises HTML)
	// donner la classe "hideauto" à la balise à masquer
	// et préciser dans data-hide les valeurs séparées par - _ , ; ou espace
	// param 1 : délai en ms
	// param 2 : vitesse en ms
	// param 3 : nom de l'effet : fade, blind, bounce, clip, drop, explode, fold, highlight, puff, pulsate, scale, shake, size, slide
	var hideauto = function(elem) {
		var effets = new Array("fade", "blind", "bounce", "clip", "drop", "explode", "fold", "highlight", "puff", "pulsate", "scale", "shake", "size", "slide");
		var parametres = $(elem).attr("data-hide");
		if(parametres === undefined) parametres = "3000 300 test";
		// alert(parametres);
		var reg = new RegExp("[ ,;_-]+", "g");
		param = parametres.split(reg);
		var timeHideAuto = parseInt(param[0]);	// délai
		var speedHideAuto = parseInt(param[1]);	// vitesse
		var effetHideAuto = param[2];			// effet
		// valeurs par défaut si non valides
		if(isNaN(timeHideAuto) || timeHideAuto < 1 || timeHideAuto === undefined) timeHideAuto = 3000;
		if(isNaN(speedHideAuto) || speedHideAuto < 1 || speedHideAuto === undefined) speedHideAuto = 300;
		var test = false;
		for(var count = 0; count < effets.length; count++) {
			if(effets[count] == effetHideAuto) test = true;
		}
		if(test == false) effetHideAuto = effets[0];
		var options = {};
		if ( effetHideAuto === "scale" ) {
			options = { percent: 0 };
		} else if ( effetHideAuto === "size") {
			options = { to: { width: 200, height: 60 } };
		}
		// alert("Délai : " + timeHideAuto + "\nVitesse : " + speedHideAuto + "\nEffet : " + effetHideAuto);
		if(effetHideAuto === "fade") {
			$(elem).delay(timeHideAuto).fadeOut(speedHideAuto);
		} else {
			$(elem).delay(timeHideAuto).hide(effetHideAuto, options, speedHideAuto);
		}
	}
	$(".hideauto").each(function() { hideauto(this); });

	// mémorise les données texte pour comparaison lors de changements
	$(".editable, .editableRich, .editablePrix").each(function() {
		// html = $(this).html();
		// if(html == null) html = "";
		// $(this).data("texte", html);
		clignote(this);
	});

	$("body").on("mouseenter", ".editable, .editableRich, .editablePrix", function() {
		highlight(this);
	});


	// $("body").on("focusout", ".editable, .editableRich, .editablePrix", function() {
	// 	if(!$(this).hasClass("mce-edit-focus")) {
	// 		newhtml = $(this).html();
	// 		if(newhtml == null) newhtml = "";
	// 		if(newhtml != $(this).data("texte")) {
	// 			// Le texte a été modifié : on le persiste en base de données
	// 			highlight(this);
	// 			alert("Texte modifié : \n" + $(this).data("texte") + "\ndevient\n" + newhtml);
	// 		}
	// 	}
	// });


	/* **************************************************** */
	/* ACTIONS SUR ENTITÉS
	/* **************************************************** */
	$('body').on("click", '.LaboAction', function(event) {
		test = $(this).attr("data-prototype").split("__");
		alert("Action : "+test[0]+" "+test[2]+" d'id = "+test[1]);
		event.preventDefault();
	});



	/* **************************************************** */
	/* GESTION DES MESSAGES EN POP-IN / MODALES
	/* **************************************************** */
	if($(".messages >p").length) {
		$(".messages").dialog({
			autoOpen: true,
			width: 380,
			height: "auto",
			minHeight: 120,
			maxHeight: 500,
			modal: true,
			closeText: 'Fermer',
			draggable: true,
			resizable: false,
			dialogClass: "testss",
			position: ["center", 250],
			// dialogClass: "RedTitleStuff",
			buttons: {
				"Fermer": function() {
					$(this).dialog("close");
				}
			}
		});
		setTimeout(function() { $(".messages").dialog('close'); }, 6000);
		$(".messages").bind('clickoutside', function(e) {
			$target = $(e.target);
			if (!$target.filter('.hint').length && !$target.filter('.hintclickicon').length) {
				$(this).dialog('close');
			}
		});
	}

	$("#container_popup .chosen-container").css('width', "220px");
	$("#container_popup input").css('width', "220px");

});