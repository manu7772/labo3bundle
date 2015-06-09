<?php
// laboBundle/services/aetools/aedates.php

namespace laboBundle\services\aetools;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use \Twig_Extension;
use \DateTime;

class twigAedates extends Twig_Extension {

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
		return $this;
	}

	public function getFunctions() {
		return array(
			// fonctionnalités de dates
			'intervalDateFR'	=> new \Twig_Function_Method($this, 'intervalDateFR'),
			'dateFR'			=> new \Twig_Function_Method($this, 'dateFR'),
			'dureeHM'			=> new \Twig_Function_Method($this, 'dureeHM'),
			'annee'				=> new \Twig_Function_Method($this, 'annee'),
			'getCalendEnCours'	=> new \Twig_Function_Method($this, 'getCalendEnCours'),
			);
	}

	public function getName() {
		return 'twigAedates';
	}

	public function getCalendEnCours() {
		$date = new Datetime();
		$data = array();
		$data['date']['jourDeLannee'] = intval($date->format("z"));
		$data['date']['jourDeSemaine'] = intval($date->format("w"));
		$data['date']['semaineEnCours'] = intval($date->format("W"));
		$data['date']['moisEnCours'] = intval($date->format("n"));
		$data['date']['anneeEnCours'] = intval($date->format("Y"));
		return $data;
	}

	public function isDateValid($date, $tempo = "mois", $ecart = 12) {
		$tempos = array("jour", "semaine", "mois", "annee");
		if(!in_array(strtoupper($tempo), $tempos)) $tempo = $tempo[2];
		// date actuelle
		$date = new Datetime();
		// date $date
		$Fmois = intval($date->format("n"));
		$Fannee = intval($date->format("Y"));
		return true;
	}

	public function intervalDateFR($datedebut, $datefin = null, $short = false) {
		if(($datefin === null) && (is_object($datedebut))) {
			$txt = "le ".$this->dateFR($datedebut, $short);
		} else if((is_object($datedebut)) && (is_object($datefin))) {
			$dd = $this->dateFR($datedebut, $short);
			$df = $this->dateFR($datefin, $short);
			// supprime l'année sur date de début si identique à celle de la date de fin
			if(substr($dd, -4) == substr($df, -4)) $dd = substr($dd, 0, strlen($dd) - 5);
			$txt = "du ".$dd." au ".$df;
		} else $txt = "";
		return $txt;
	}

	public function dateFR($date, $short = false) {
		$sup = array(1);
		if($short === false) {
			$jours = array(
				"Sunday" 	=> "dimanche",
				"Monday" 	=> "lundi",
				"Tuesday" 	=> "mardi",
				"Wednesday" => "mercredi",
				"Thursday" 	=> "jeudi",
				"Friday" 	=> "vendredi",
				"Saturday" 	=> "samedi",
				);
			$mois = array(
				"January" 	=> "janvier",
				"February" 	=> "février",
				"March" 	=> "mars",
				"April" 	=> "avril",
				"May" 		=> "mai",
				"June" 		=> "juin",
				"July" 		=> "juillet",
				"August" 	=> "août",
				"September" => "septembre",
				"October" 	=> "octobre",
				"November" 	=> "novembre",
				"December" 	=> "décembre",
				);
		} else {
			$jours = array(
				"Sunday" 	=> "dim",
				"Monday" 	=> "lun",
				"Tuesday" 	=> "mar",
				"Wednesday" => "mer",
				"Thursday" 	=> "jeu",
				"Friday" 	=> "ven",
				"Saturday" 	=> "sam",
				);
			$mois = array(
				"January" 	=> "jan",
				"February" 	=> "fév",
				"March" 	=> "mar",
				"April" 	=> "avr",
				"May" 		=> "mai",
				"June" 		=> "jun",
				"July" 		=> "jul",
				"August" 	=> "aou",
				"September" => "sep",
				"October" 	=> "oct",
				"November" 	=> "nov",
				"December" 	=> "déc",
				);
		}
		$jj = $jours[$date->format('l')];
		$j = $date->format('j');
		if(in_array(intval($j), $sup)) $j .= "<sup>er</sup>";
		$m = $mois[$date->format('F')];
		$a = $date->format('Y');
		return $jj." ".$j." ".$m." ".$a;
	}


	/**
	 * dureeHM
	 * 
	 * Renvoie un texte en heures pour une durée $duree en minutes
	 * @param int
	 * @return string
	 */
	public function dureeHM($duree) {
		$duree = intval($duree);
		$t = "";
		if($duree < 2) $t = $duree." minute";
		if($duree < 60 && $t === "") $t = $duree." minutes";
		if($duree > 59 && $t === "") {
			$h = floor($duree / 60);
			$m = fmod($duree, 60);
			$mt = " minute";
			if($h > 1) $s = "s"; else $s = "";
			if($h > 0) {
				$t = $h." heure".$s;
				$esp = " ";
				$mt = "";
			} else {
				$esp = "";
			}
			if($m > 1 && $mt !== "") $mt .= "s";
			if($m > 0) $t .= $esp.$m.$mt;
		}
		return $t;
	}


	/**
	 * annee
	 * Renvoie l'année en cours
	 * @return string
	 */
	public function annee() {
		$date = new DateTime();
		return $date->format("Y");
	}



}