<?php
// labo/Bundle/TestmanuBundle/services/aetools/aedates.php

namespace labo\Bundle\TestmanuBundle\services\aetools;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

class aedates {

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
		return $this;
	}

	public function getCalendEnCours() {
		$date = new \Datetime();
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
		$date = new \Datetime();
		// date $date
		$Fmois = intval($date->format("n"));
		$Fannee = intval($date->format("Y"));
		return true;
	}

}
?>
