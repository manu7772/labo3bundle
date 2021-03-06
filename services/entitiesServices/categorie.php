<?php
// laboBundle/services/entitiesServices/categorie.php

namespace laboBundle\services\entitiesServices;

use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use laboBundle\services\entitiesServices\entitesService;
// use Symfony\Component\Form\FormFactoryInterface;

use \Exception;

class categorie extends entitesService {

	protected $actifPath;				// path de l'élément actif : liste des éléments parents
	protected $actifChil;				// enfants de l'élément actif : liste des éléments enfants
	protected $actifSlug;				// nom de l'élément de catégorie actif
	protected $sayIfChangeOrNo = null;
	protected $container;
	protected $menuSlug = null;
	protected $translator;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
		parent::__construct($this->container);
		// récupération du paramètre de menu dans parameters.yml si existant
		// tester : $this->container->getParameter( 'labo.default_menu_slug' );
		$this->translator = $this->container->get('translator');
		if($this->container->hasParameter('labo.default_menu_slug')) {
			$this->menuSlug = $this->container->getParameter("labo.default_menu_slug");
		} else $this->menuSlug = null;
		// if($this->container->hasParameter('menu_slug')) {
		// 	$this->menuSlug = $this->container->getParameter("menu_slug");
		// } else $this->menuSlug = null;
		// if(($this->init["categorie"] === false) || ($this->modeFixtures === true)) $this->defineEntity("categorie");
	}

	/**
	* serviceEventInit
	* Initialise le service - attention : cette méthode est appelée en requête principale par EventListener !!!
	* 
	* @param FilterControllerEvent $event
	* @param boolean $reLoad
	*/
	public function INACTIVE_serviceEventInit(FilterControllerEvent $event, $reLoad = false) {
		$this->defineEntity("categorie");
		// $this->init["categorie"] = true;
		$serviceData = false;
		// Vérifie si la version a été changée : dans ce cas, on oblige le rechargement de ce service
		// Vérifie également si le menu doit changer (premier paramètre est ou n'est plus "articles")
		$serviceChange = $event->getRequest()->request->get($this->serviceNom."Define"); // POST en priorité
		if(null === $serviceChange) $serviceChange = $event->getRequest()->query->get($this->serviceNom."Define"); // GET
		$reloadAll = $this->sessionData->get("siteListener");
		if($this->sayIfChangeOrNo() === true) $reLoad = true;

		if(($this->sessionData->get($this->serviceNom) === null) 
			|| ($reloadAll["reloadAll"] === true)
			|| ($serviceChange !== null) 
			|| ($reLoad === true)) { // ---> !!! recharge !!!

			if($serviceChange !== null) {
				// Charge la version suivant le slug
				$serviceData = $this->getRepo()->findOneBySlug($serviceChange);
				if(true === is_object($serviceData)) $this->service['find'] = "request";
			}
			if((false === is_object($serviceData)) && (method_exists($this->getRepo(), "defaultVal"))) {
				// Sinon charge la version par défaut
				$serviceData = $this->getRepo()->defaultVal();
				if(true === is_object($serviceData)) $this->service['find'] = "default";
			}
			if(false === is_object($serviceData)) {
				// Si aucune version trouvée, charge la première version trouvée
				$f = $this->findAll();
				if(count($f) > 0) {
					if(is_object($f[0])) $serviceData = $f[0];
				}
				if(true === is_object($serviceData)) $this->service['find'] = "findfirst";
			}
			// echo $this->service['find']." = ".$serviceData->getNom();

			if(true === is_object($serviceData)) {

				//////////////////////////////////////////
				// DEBUT : Lignes de personnalisation du service
				//////////////////////////////////////////
				// --------------------------------------
				// Données sur le lien actif
				// --------------------------------------
				$URL = $this->getPathInfo();
				if($URL !== null) {
					$this->service['actif'] = $URL;
					$this->actifSlug = $URL;
					$actifData = $this->getRepo()->findNodeBySlug($URL);
					// --> path
					$path = $this->getRepo()->getPath($actifData);
					$this->actifPath["nom"] = $this->actifPath["slug"] = array();
					foreach($path as $nom => $val) $this->actifPath["nom"][] = $val->getNom();
					foreach($path as $nom => $val) $this->actifPath["slug"][] = $val->getSlug();
					$this->service['actif_path'] = serialize($this->actifPath);
					// --> children
					$children = $this->getRepo()->getChildren($actifData);
					$this->actifChil["nom"] = $this->actifChil["slug"] = array();
					foreach($children as $nom => $val) $this->actifChil["nom"][] = $val->getNom();
					foreach($children as $nom => $val) $this->actifChil["slug"][] = $val->getSlug();
					$this->service['actif_children'] = serialize($this->actifChil);
				} else {
					$this->service['actif'] = false;
					$this->actifPath = array();
					$this->actifChil = array();
					$this->actifSlug = "";
				}
				// --------------------------------------
				// données sur l'ensemble de la catégorie
				// --------------------------------------
				$this->service['nom'] = $serviceData->getNom();
				$this->service['slug'] = $serviceData->getSlug();
				$this->service['descrirptif'] = $serviceData->getDescriptif();
				//////////////////////////////////////////
				// FIN : Lignes de personnalisation du service
				//////////////////////////////////////////
				$this->service['reloaded'] = true;
				$this->service['default'] = $this->getRepo()->defaultMenu($this->menuSlug)->getSlug();
				// --> éléments ayant un menu
				$this->getRepo(); // ?????????????
				// echo($this->serviceNom." => Version getRepo() : ".$this->getRepo()->getVersion()."<br />");
				$menus = $this->getRepo()->listOfMenus();
				foreach($menus as $menu) {
					$this->service['menu'][$menu->getNom()] = $this->menuCategories($menu);
				}
				// Sérialisation pour mise en session
				// var_dump($this->service);
				$this->siteListener_InSession();
				// echo("<pre>");
				// var_dump($this->service);
				// echo("</pre>");
			} else {
				// Aucune version disponible en BDD !!!
				throw new Exception($this->translator->trans("loader.services_disabled", array(), 'validators'), 1);			
				// $this->container->get("session")->getFlashBag()->add('info', "Aucun élément \"".$this->serviceNom."\". Créez un nouveau \"".$this->serviceNom."\", s.v.p.");
				// $this->siteListener_changeDataSession('find', 'not reloaded');
				// $this->siteListener_changeDataSession('reloaded', false);
			}
		} else {
			// VERSION DÉJÀ CHARGÉE : OK
			$this->siteListener_changeDataSession('reloaded', false);
			$this->siteListener_changeDataSession('find', 'not reloaded');
		}
		$this->defineEntity("categorie");
		return $this;
	}


	/**
	* categorieInSession
	* dépose les informations de société dans la session
	* @param categorie $categorie
	*
	*/
	public function menuCategories($categorie, $menuspecifique = null) {
		if(is_string($categorie)) $categorie = $this->getRepo('categorie')->findOneBySlug($categorie);
		if($menuspecifique === null) $menuspecifique = $categorie->getNommenu();
		$menuOptions = new $menuspecifique($this->container);
		// echo('<p>Catégorie : '.get_class($categorie).'</p>');
		// echo('<p>menuOptions : '.get_class($menuOptions).'</p>');
		$result = $this->getRepo('categorie')->childrenHierarchy(
				$categorie,
				// $this->getRepo()->find($categorie->getId()), // ????????????????
				false,
				$menuOptions->getOptions(),
				true
			);
		return $result;
	}

	/**
	* getPathInfo
	* Renvoie la valeur de categorieSlug dans l'URL
	*
	*/
	public function getPathInfo() {
		$categorieSlug = $this->requAttributes->get('categorieSlug');
		if($categorieSlug === "web") $categorieSlug = null;
		return $categorieSlug;
	}

	/**
	* sayIfChangeOrNo
	* Renvoie la réponse en fonction du paramètre categorieSlug récupéré dans l'URL en requête
	* Renvoie true si la categorie doit être rechargée / sinon renvoie false
	*
	*/
	public function sayIfChangeOrNo() {
		if($this->sayIfChangeOrNo === null) {
			$r = false;
			$newSlug = $this->getPathInfo();
			$flsSlug = $this->flashBag->get("histoCategorieSlug");
			if(count($flsSlug) < 1) $oldSlug = null;
				else $oldSlug = $flsSlug[0];
			// $oldSlug = $this->sessionData->get("histoCategorieSlug");
			// echo("new : ".$newSlug."<br />");
			// echo("old : ".$oldSlug."<br />");
			if($newSlug !== $oldSlug) $r = true;
			// Sauvegarde le new histoCategorieSlug comme old histoCategorieSlug pour la prochaine fois
			$this->flashBag->add("histoCategorieSlug", $newSlug);
			// $this->sessionData->set("histoCategorieSlug", $newSlug);
			$this->sayIfChangeOrNo = $r;
		}
		return $this->sayIfChangeOrNo;
	}

}