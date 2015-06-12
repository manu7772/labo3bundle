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
		$this->do_load = false;
		$this->newVersionHote = null;
		$this->newVersionSlug = null;
		$this->memoriseActualDomaine();
		$this->verifyChangedDomaine();
		$this->verifyRequestChangeDomaine();
	}

	protected function getActualDomaine() {
		// !!!!! remplacer par un preg_replace
		if($this->actualDomaine === null) $this->actualDomaine = str_replace(array("http://www.","https://www.","www."), "", $this->container->getRequest()->getHost());
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
	protected function doReload() {
		$this->initDataVersion();
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
		$this->event = $event;
		if($this->doReload() === true || 1 == 1) {
			// rechargement de version
			$this->service = $this->getRepo()->getVersionSlugArray();
			echo('<pre>');
			var_dump($this->aeSerialize($this->service));
			echo('</pre>');
			echo("<h1>Enregistrement en session : ".$this->getShortName()."</h1>");
			$this->siteListener_InSession();
		}
		// $this->defineEntity("version");
		// $this->init["version"] = true;
		// $this->service = array();
		// $this->serviceData = false;
		// // vérifie si le nom de domaine est différent
		// // $BASEHOST = $event->getRequest()->getHost();
		// $BASEHOST = str_replace(array("http://www.","www."), "", $event->getRequest()->getHost());
		// // $PRECHOST = $event->getRequest()->getSession()->get("hote");
		// $PRECHOST = $this->flashBag->get("hote");
		// $this->flashBag->add("hote", $BASEHOST);
		// // echo("<span style='display:none2;'>Base HOST : ".$BASEHOST." ( ".$PRECHOST." )<br /></span>");
		// if(($PRECHOST !== $BASEHOST) && ($BASEHOST !== "localhost")) {
		// 	$changeHost = $BASEHOST;
		// } else $changeHost = null;
		// // Changement de version en GET ou POST (versionDefine=slug_de_la_version)
		// $serviceChange = $event->getRequest()->request->get($this->serviceNom."Define"); // POST en priorité
		// if($serviceChange === null) $serviceChange = $event->getRequest()->query->get($this->serviceNom."Define"); // GET
		// // Débrayage de version (surtout pour admin)
		// $this->service['shutdown'] = $event->getRequest()->request->get($this->serviceNom."Shutdown"); // POST en priorité
		// if($this->service['shutdown'] === null) $this->service['shutdown'] = $event->getRequest()->query->get($this->serviceNom."Shutdown"); // GET
		// // $reloadAll = $this->sessionData->get("siteListener");echo("ReloadAll(sessi) : ".$reloadAll["reloadAll"]);
		// $reloadAll = $this->sessionData->get("siteListener");//echo("ReloadAll(event) : ".$reloadAll["reloadAll"]);

		// if(($this->sessionData->get($this->serviceNom) === null)
		// 	// || ($reloadAll["reloadAll"] === true)
		// 	|| ($this->service['shutdown'] !== null)
		// 	|| ($serviceChange !== null) 
		// 	|| ($reLoad === true)
		// 	|| ($changeHost !== null)) { // ---> !!! recharge !!!

		// 	if(($changeHost !== null) && ($serviceChange === null)) {
		// 		// Charge la version suivant le nom de domaine appelant
		// 		$hote = $this->getRepo()->findByHote($changeHost);
		// 		if(count($hote) < 1) $hote = $this->getRepo()->findByHote("www.".$changeHost);
		// 		if(count($hote) < 1) $hote = $this->getRepo()->findByHote("http://www.".$changeHost);
		// 		if(count($hote) > 0) {
		// 			$this->serviceData = $hote[0];
		// 		}
		// 		if(true === is_object($this->serviceData)) $this->service['find'] = "host";
		// 	}
		// 	if($serviceChange !== null) {
		// 		// Charge la version suivant le slug
		// 		$this->serviceData = $this->getRepo()->findOneBySlug($serviceChange);
		// 		if(true === is_object($this->serviceData)) $this->service['find'] = "request";
		// 	}
		// 	if((false === is_object($this->serviceData)) && (method_exists($this->getRepo(), "defaultVersion"))) {
		// 		// Sinon charge la version par défaut
		// 		$this->serviceData = $this->getRepo()->defaultVersion();
		// 		if(true === is_object($this->serviceData)) $this->service['find'] = "default";
		// 	}
		// 	if(false === is_object($this->serviceData)) {
		// 		// Si aucune version trouvée, charge la première version trouvée
		// 		$f = $this->getAll();
		// 		if(count($f) > 0) {
		// 			if(is_object($f[0])) $this->serviceData = $f[0];
		// 		}
		// 		if(true === is_object($this->serviceData)) $this->service['find'] = "findfirst";
		// 	}
		// 	if(true === is_object($this->serviceData)) {
		// 		// DEBUT : Lignes de personnalisation du service
		// 		$vers = $this->getRepo()->defaultVal();
		// 		$this->service['liste'] = array();
		// 		foreach($vers as $v) $this->service['liste'][$v->getSlug()] = $v->getNom();
		// 		$this->service['nom'] = $this->serviceData->getNom();
		// 		// image entete (normal)
		// 		if(is_object($this->serviceData->getImageEntete()))
		// 			$this->service['imageEntete'] = $this->serviceData->getImageEntete()->getFichierNom();
		// 		else $this->service['imageEntete'] = null;
		// 		// image entete Wide
		// 		if(method_exists($this->serviceData, "getImageEnteteWide")) {
		// 			if(is_object($this->serviceData->getImageEnteteWide()))
		// 				$this->service['imageEnteteWide'] = $this->serviceData->getImageEnteteWide()->getFichierNom();
		// 			else $this->service['imageEnteteWide'] = null;
		// 		}
		// 		// image entete Mini
		// 		if(method_exists($this->serviceData, "getImageEnteteMini")) {
		// 			if(is_object($this->serviceData->getImageEnteteMini()))
		// 				$this->service['imageEnteteMini'] = $this->serviceData->getImageEnteteMini()->getFichierNom();
		// 			else $this->service['imageEnteteMini'] = null;
		// 		}
		// 		// favicon
		// 		if(is_object($this->serviceData->getFavicon()))
		// 			$this->service['favicon'] = "images/favicons/".preg_replace('`\.([[:alnum:]]+)$`' , ".ico", $this->serviceData->getFavicon()->getFichierNom());
		// 		else $this->service['favicon'] = null;
		// 		$this->service['hote'] = $BASEHOST;
		// 		$this->service['domaine'] = $this->serviceData->getNomDomaine();
		// 		$this->service['accroche'] = $this->serviceData->getAccroche();
		// 		$this->service['descriptif'] = $this->serviceData->getDescriptif();
		// 		$this->service['email'] = $this->serviceData->getEmail();
		// 		$this->service['couleur'] = $this->serviceData->getCouleurFond();
		// 		$this->service['telpublic'] = $this->serviceData->getTelpublic();
		// 		// réseaux sociaux
		// 		$this->service['resosociaux'] = array();
		// 		if($this->serviceData->getResofacebook() !== null)
		// 			$this->service['resosociaux']['facebook'] = $this->serviceData->getResofacebook();
		// 		if($this->serviceData->getResotwitter() !== null)
		// 			$this->service['resosociaux']['twitter'] = $this->serviceData->getResotwitter();
		// 		if($this->serviceData->getResogoogleplus() !== null)
		// 			$this->service['resosociaux']['google+'] = $this->serviceData->getResogoogleplus();
		// 		// $this->service['fax'] = $this->serviceData->getFax();
		// 		if($this->serviceData->getAdresse() !== null) {
		// 			$this->service['adresse']['nom'] = $this->serviceData->getAdresse()->getNom();
		// 			$this->service['adresse']['adresse'] = $this->serviceData->getAdresse()->getAdresse();
		// 			$this->service['adresse']['cp'] = $this->serviceData->getAdresse()->getCp();
		// 			$this->service['adresse']['ville'] = $this->serviceData->getAdresse()->getVille();
		// 		} else $this->service['adresse'] = array();
		// 		$this->service['id'] = $this->serviceData->getId();
		// 		$this->service['slug'] = $this->serviceData->getSlug();
		// 		$this->service['fichierCSS'] = $this->serviceData->getFichierCSS();
		// 		$this->service['templateIndex'] = $this->serviceData->getTemplateIndex();
		// 		// FIN : Lignes de personnalisation du service
		// 		$this->service['reloaded'] = true;
		// 		$def = $this->defaultVersion(); // Récupère version par défaut
		// 		$this->service['defaut'] = $def->getSlug();
		// 		$this->service['defaut_nom'] = $def->getNom();
		// 		// Sérialisation pour mise en session
		// 		$this->siteListener_InSession();
		// 		// version = rechargé => Force les autres service à recharger aussi
		// 		$this->sessionData->set("siteListener", array("reloadAll" => true));
		// 		// echo("<pre>");
		// 		// var_dump($this->service);
		// 		// echo("</pre>");
		// 		// $this->defineEntity("version");
		// 	} else {
		// 		// Aucune version disponible en BDD !!!
		// 		$this->container->get("session")->getFlashBag()->add('info', "Aucun élément \"".$this->serviceNom."\". Créez un nouveau \"".$this->serviceNom."\", s.v.p.");
		// 		$this->siteListener_changeDataSession('reloaded', false);
		// 		$this->siteListener_changeDataSession('find', 'not reloaded');
		// 		$this->sessionData->set("siteListener", array("reloadAll" => false));
		// 	}
		// } else {
		// 	// VERSION DÉJÀ CHARGÉE : OK
		// 	$this->siteListener_changeDataSession('reloaded', false);
		// 	$this->siteListener_changeDataSession('find', 'not reloaded');
		// 	$this->sessionData->set("siteListener", array("reloadAll" => false));
		// }
		// $this->defineEntity("version");
		return $this;
	}


}

?>
