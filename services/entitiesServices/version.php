<?php
// laboBundle/services/entitiesServices/version.php

namespace laboBundle\services\entitiesServices;

use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use laboBundle\services\entitiesServices\entitesService;
use Doctrine\Common\Collections\ArrayCollection;
// use Symfony\Component\Form\FormFactoryInterface;
use \Exception;
use \DateTime;

class version extends entitesService {
	// protected $service = array();
	// protected $serviceData = false; // objet version
	const ALLVERSIONS_NAME = 'allVersions';
	const CURRENTVERSION_NAME = 'currentVersion';

	protected $container;

	protected $newVersionHote = null;
	protected $newVersionSlug = null;
	protected $previousDomaine = null;
	protected $do_load = false;

	public function __construct(ContainerInterface $container) {
		parent::__construct($container);
		// $this->getCurrentVersion();
		$this->consoleLog("SERVICE Version : ".$this->getVersionEntityShortName());
		$this->defineEntity($this->getVersionEntityShortName());
		$this->initDataVersion();
		return $this;
	}

	/**
	 * Initialise les données pour le service
	 * @return version
	 */
	protected function initDataVersion() {
		return $this;
	}

	/**
	 * Initialise les données pour Listener
	 * @return version
	 */
	protected function initDataVersionForListener() {
		$this->do_load = !$this->isSiteListener_InSession();
		$this->newVersionHote = null;
		$this->newVersionSlug = null;
		$this->memoriseActualDomaine();
		$this->verifyChangedDomaine();
		$this->verifyRequestChangeDomaine();
		return $this;
	}

	/**
	 * Renvoie le nouveau domaine s'il a été changé. 
	 * S'il n'a pas changé, renvoie false.
	 * @return string
	 */
	protected function verifyChangedDomaine() {
		$BASEHOST = $this->getActualDomaine();
		$PRECHOST = $this->getMemorisedDomaine();
		$this->memoriseActualDomaine();
		if(($PRECHOST !== $BASEHOST) && ($BASEHOST !== "localhost")) {
			$this->newVersionHote = $BASEHOST;
			$this->do_load = true;
			return $BASEHOST;
		}
		return false;
	}

	/**
	 * Renvoie le nouveau domaine doit être changé via requête
	 * @return boolean
	 */
	protected function verifyRequestChangeDomaine() {
		// Changement de version en GET ou POST (versionDefine=slug_de_la_version)
		$serviceChange = $this->serviceRequ->request->get($this->getShortName()."Define"); // POST en priorité
		if($serviceChange === null) $serviceChange = $this->serviceRequ->query->get($this->getShortName()."Define"); // GET
		if(is_string($serviceChange)) $this->newVersionSlug = $serviceChange;
		if($serviceChange !== null) $this->do_load = true;
		return $serviceChange === null ? false : true;
	}

	/**
	 * Renvoie si la version doit être rechargée en session
	 * @return boolean
	 */
	protected function doReload($reLoad = false) {
		$this->initDataVersionForListener();
		if($reLoad === true) $this->do_load = true;
		return $this->do_load;
	}

	/**
	* serviceEventInit
	* Initialise le service - attention : cette méthode est appelée en requête principale par EventListener !!!
	* 
	* @param FilterControllerEvent $event
	* @param boolean $reLoad
	*/
	public function serviceEventInit(FilterControllerEvent $event, $reLoad = false) {
		// $this->event = $event;
		// $controller = $this->event->getController();
		if($this->doReload($reLoad) === true) {
			$adds = $this->getConfig('version_in_session');
			$this->service = array();
			// echo('<pre><h3>VERSION</h3>');
			// var_dump($adds);
			// echo('</pre>');
			// Chargement de version
			if($this->newVersionHote !== null) {
				// changements d'hôte EN PRIORITÉ
				$this->service[self::CURRENTVERSION_NAME] = $this->getRepo()->findVersionWithLinks($this->newVersionHote, "hote", $adds);
			} else if($this->newVersionSlug !== null) {
				// si changement par requête
				$this->service[self::CURRENTVERSION_NAME] = $this->getRepo()->findVersionWithLinks($this->newVersionSlug, 'slug', $adds);
			} else {
				// version par défaut
				$this->service[self::CURRENTVERSION_NAME] = $this->getRepo()->findVersionWithLinks(null, null, $adds);
			}
			// ajoute les infos des autres versions
			$allVersions = $this->getRepo()->findAll();
			$this->service[self::ALLVERSIONS_NAME] = array();
			$fields = array('nom', 'slug', 'defaultVersion', 'nomDomaine', 'templateIndex');
			if(is_array($allVersions)) {
				foreach ($allVersions as $key => $version) {
					$this->service[self::ALLVERSIONS_NAME][$key] = array();
					foreach ($fields as $field) {
						$method = $this->getMethodNameWith($field, 'get');
						$this->service[self::ALLVERSIONS_NAME][$key][$field] = $version->$method();
					}
				}
			}
			$this->siteListener_InSession();
		}
		return $this;
	}


}
