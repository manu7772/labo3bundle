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

	protected $versionClassName;
	// protected $actualVersion = null;
	protected $container;


	protected $newVersionHote = null;
	protected $newVersionSlug = null;
	protected $actualDomaine = null;
	protected $previousDomaine = null;
	protected $do_load = false;

	public function __construct(ContainerInterface $container) {
		parent::__construct($container);
		// if(($this->init["categorie"] === false) || ($this->modeFixtures === true))
		$this->versionClassName = $this->getVersionEntityClassName();
		$this->defineEntity($this->versionClassName);
		// $this->initDataVersion();
	}

	protected function initDataVersion() {
		$this->do_load = $this->isSiteListener_InSession();
		$this->newVersionHote = null;
		$this->newVersionSlug = null;
		$this->memoriseActualDomaine();
		$this->verifyChangedDomaine();
		$this->verifyRequestChangeDomaine();
	}

	protected function getActualDomaine() {
		// !!!!! remplacer par un preg_replace
		if($this->actualDomaine === null) $this->actualDomaine = str_replace(array("http://www.","https://www.","www."), "", $this->serviceRequ->getHost());
		return $this->actualDomaine;
 	}

	/**
	 * Mémorise le domaine dans le flashbag
	 * @return tring
	 */
	protected function getMemorisedDomaine() {
		if($this->previousDomaine === null) $this->previousDomaine = $this->sessionData->get("hote");
		return $this->previousDomaine;
	}

	/**
	 * Mémorise le domaine dans la session / le flashbag
	 * @return version
	 */
	protected function memoriseActualDomaine() {
		$this->getMemorisedDomaine();
		$this->sessionData->set("hote", $this->getActualDomaine());
		// $this->flashBag->add("hote", $this->getActualDomaine());
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
		$serviceChange = $this->serviceRequ->request->get($this->serviceNom."Define"); // POST en priorité
		if($serviceChange === null) $serviceChange = $this->serviceRequ->query->get($this->serviceNom."Define"); // GET
		if(is_string($serviceChange)) $this->newVersionSlug = $serviceChange;
		if($serviceChange !== null) $this->do_load = true;
		return $serviceChange === null ? false : true;
	}

	/**
	 * Renvoie si la version doit être rechargée en session
	 * @return boolean
	 */
	protected function doReload($reLoad = false) {
		$this->initDataVersion();
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
		if($this->doReload($reLoad) === true) {
			// rechargement de version
			$this->service = $this->getRepo()->getVersionSlugArray();
			// echo('<pre>');
			// var_dump($this->aeSerialize($this->service));
			// echo('</pre>');
			// echo("<h1>Enregistrement en session : ".$this->getShortName()."</h1>");
			$this->siteListener_InSession();
		}
		return $this;
	}


}

?>
