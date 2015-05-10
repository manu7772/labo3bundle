
$(document).ready(function() {
	// On ajoute la classe "js" à la liste pour mettre en place par la suite du code CSS uniquement dans le cas où le Javascript est activé
	$("ul#notes-echelle.etoile_avis").addClass("js");
	// On passe chaque etoile à l'état grisé par défaut
	$("ul#notes-echelle.etoile_avis li").addClass("note-off");
	
	$("ul#notes-echelle.etoile_avis input")
		// Lorsque le focus est sur un bouton radio
		.focus(function() {
			// On supprime les classes de focus
			$(this).parents("ul#notes-echelle.etoile_avis").find("li").removeClass("note-focus");
			// On applique la classe de focus sur l'item tabulé
			$(this).parent("li").addClass("note-focus");
			// On passe les etoiles supérieures à l'état inactif (par défaut)
			$(this).parent("li").nextAll("li").addClass("note-off");
			// On passe les etoiles inférieures à l'état actif
			$(this).parent("li").prevAll("li").removeClass("note-off");
			// On passe l'etoile du focus à l'état actif (par défaut)
			$(this).parent("li").removeClass("note-off");
		})
		// Lorsque l'on sort du sytème de notation au clavier
		.blur(function() {
			// On supprime les classes de focus
			$(this).parents("ul#notes-echelle.etoile_avis").find("li").removeClass("note-focus");
			// Si il n'y a pas de case cochée
			if($(this).parents("ul#notes-echelle.etoile_avis").find("li input:checked").length == 0) {
				// On passe toutes les etoiles à l'état inactif
				$(this).parents("ul#notes-echelle.etoile_avis").find("li").addClass("note-off");
			}
		})
		// Lorsque l'etoile est cochée
		.click(function() {
			// On supprime les classes de l'etoile cochée
			$(this).parents("ul#notes-echelle.etoile_avis").find("li").removeClass("note-checked");
			// On applique la classe de l'etoile cochée sur l'item choisi
			$(this).parent("li").addClass("note-checked");
		});
	// Au survol de chaque etoile à la souris
	$("ul#notes-echelle.etoile_avis li").mouseover(function() {
		// On passe les etoiles supérieures à l'état inactif (par défaut)
		$(this).nextAll("li").addClass("note-off");
		// On passe les etoiles inférieures à l'état actif
		$(this).prevAll("li").removeClass("note-off");
		// On passe l'etoile survolée à l'état actif (par défaut)
		$(this).removeClass("note-off");
	});
	// Lorsque l'on sort du sytème de notation à la souris	
	$("ul#notes-echelle.etoile_avis").mouseout(function() {
		// On passe toutes les etoiles à l'état inactif
		$(this).children("li").addClass("note-off");
		// On simule (trigger) un mouseover sur l'etoile cochée s'il y a lieu
		$(this).find("li input:checked").parent("li").trigger("mouseover");
	});
	// On simule un survol souris des boutons cochés par défaut	
	$("ul#notes-echelle.etoile_avis input:checked").parent("li").trigger("mouseover");
	// On simule un click souris des boutons cochés
	$("ul#notes-echelle.etoile_avis input:checked").trigger("click");
	//On ajoute un nom à l'input pour permettre de le selectionner et de le differencier de l' ul#notes-echelle.etoile_deja_donnee
	$("ul#notes-echelle.etoile_avis input").attr("name", "etoile_a_selectionner");
	
		// Pour la classe etoile_deja_donnee 
		$("ul#notes-echelle.etoile_deja_donnee").addClass("js");
		$("ul#notes-echelle.etoile_deja_donnee li").addClass("note-off");
		//On ajoute un autre id input et un autre for au label pour le différencier de la class etoile_avis
		$("ul#notes-echelle.etoile_deja_donnee label").attr("for", "etoile_selection");
		$("ul#notes-echelle.etoile_deja_donnee input").attr("id", "etoile_selection");
		$("ul#notes-echelle.etoile_deja_donnee input:checked").parent("li").prevAll("li").removeClass("note-off");
		$("ul#notes-echelle.etoile_deja_donnee input:checked").parent("li").removeClass("note-off");
		$("ul#notes-echelle.etoile_deja_donnee input:checked").parent("li").nextAll("li").removeClass("note-checked");		
});
