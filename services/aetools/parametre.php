<?php
// labo/Bundle/TestmanuBundle/services/aetools/parametre.php

namespace labo\Bundle\TestmanuBundle\services\aetools;

use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use labo\Bundle\TestmanuBundle\services\entitiesServices\entitiesGeneric;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
// use Symfony\Component\Form\FormFactoryInterface;

class parametre extends entitiesGeneric {

	protected $service = array();

	public function __construct(ContainerInterface $container) {
		parent::__construct($container);
		$this->defineEntity("parametre");
	}

	/**
	* serviceEventInit
	* Initialise le service - attention : cette méthode est appelée en requête principale par EventListener !!!
	* 
	* @param FilterControllerEvent $event
	* @param boolean $reLoad
	*/
	public function serviceEventInit(FilterControllerEvent $event, $reLoad = false) {
		$this->defineEntity("parametre");
		// déjà présent ?
		if($this->sessionData->get("parametre") === null) $reLoad = true;
		// reload All ?
		$reloadAll = $this->sessionData->get("siteListener");
		if($reloadAll["reloadAll"] === true) $reLoad = true;

		if(($this->init["parametre"] === false) || ($reLoad === true)) {
			$this->init["parametre"] = true;
			// initialisation
			$serviceData = $this->getRepo()->findAll();
			if(count($serviceData) > 0) {
				foreach($serviceData as $servD) {
					// echo('Load… '.$servD->getSlug()."<br />");
					$this->service[$servD->getSlug()]['nom'] = $servD->getNom();
					$this->service[$servD->getSlug()]['slug'] = $servD->getSlug();
					$this->service[$servD->getSlug()]['valeur'] = $servD->getValeur();
					$this->service[$servD->getSlug()]['groupe'] = $servD->getGroupe();
				}
				$this->service["reloadAll"] = true;
				$this->siteListener_InSession();
			} else {
				$this->service["reloadAll"] = false;
				$this->siteListener_changeDataSession("reloadAll", false);
			}
		} else {
			$this->service["reloadAll"] = false;
			$this->siteListener_changeDataSession("reloadAll", false);
		}
		return $this;
	}

	/**
	 * Get par slugs
	 * @param string/array $paramSlugs
	 * @return parametre
	 */
	public function getParamBySlug($paramSlugs) {
		if(is_string($paramSlugs)) $paramSlugs = array($paramSlugs);
		$t = $this->getRepo()->findBySlug($paramSlugs);
		if(count($t) > 0) {
			return $t[0];
		} else return false;
	}

	/**
	 * Get par groupes
	 * @param string/array $groups
	 * @return parametre
	 */
	public function getParamByGroupeNom($groups) {
		if(is_string($groups)) $groups = array($groups);
		$t = $this->getRepo()->findByGroupeNom($groups);
		if(count($t) > 0) {
			return $t[0];
		} else return false;
	}

	/**
	 * get DiapoIntroSlug
	 * @return string
	 */
	public function getDiapoIntroSlug() {
		$slug = 'diaporama-intro';
		$parametre = $this->siteListener_getData($slug);
		if(($parametre !== null) && (count($parametre["slug"]) > 0)) {
			// récupère en données de session
			// echo("En session : ".$parametre["valeur"]."<br />");
			return $parametre["valeur"];
		} else {
			// sinon recherche en BDD
			$diapo = $this->getParamBySlug($slug);
			if($diapo !== false) {
				// echo("En BDD : ".$diapo->getValeur()."<br />");
				return $diapo->getValeur();
			} else return null;
		}
	}

	/**
	 * get HomePageRegister
	 * @return string
	 */
	public function getHomePageRegister() {
		$slug = 'homepage-register';
		$parametre = $this->siteListener_getData($slug);
		if(($parametre !== null) && (count($parametre["slug"]) > 0)) {
			// récupère en données de session
			// echo("En session : ".$parametre["valeur"]."<br />");
			return $parametre["valeur"];
		} else {
			// sinon recherche en BDD
			$diapo = $this->getParamBySlug($slug);
			if($diapo !== false) {
				// echo("En BDD : ".$diapo->getValeur()."<br />");
				return $diapo->getValeur();
			} else return null;
		}
	}




}

?>
