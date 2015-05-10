<?php
// labo/Bundle/TestmanuBundle/services/entitiesServices/entitiesGeneric.php

nameSpace labo\Bundle\TestmanuBundle\services\entitiesServices;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
// use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

class entitiesGeneric {
	protected $container;					// container
	protected $em;							// entity_manager
	protected $repo;						// repository
	protected $repoNameEntite;				// chemin/classe du repository
	protected $formNameEntite;				// chemin/classe du formulaire type
	protected $router;
	protected $route;						// route acturelle
	protected $formFactory;					// objet formFactory
	protected $securityContext;				// données User Security Context
	protected $serviceRequ;
	protected $serviceSess;
	protected $user = false;				// user
	protected $version;						// version (slug !!)
	protected $entitePreviousSave = false;	// nom de l'entité précédente mémorisée
	protected $entiteOriginalSave = false;	// nom de l'entité d'origine mémorisée
	protected $listOfEnties = null;				// liste des entités de AcmeGroup
	protected $aslash = '\\';				// antislash

	protected $classEntite;					// class name de l'entité
	protected $serviceNom;					// nom du service -> utilisé pour mise en session
	protected $bundleName;					// bundle du service
	protected $dossierName;					// dossier de l'entité (souvent : "Entity")
	protected $entiteName;

	protected $findParams = array();		// éléments de recherche sur une entité
	protected $listParams = array(
		"nopage",			// numéro de page actuel
		"nbpages",			// nombre total de pages
		"nblignes",			// nombre de lignes par page
		"ordField",			// champ pour le tri
		"ordSens",			// sens du tri ("ASC" ou "DESC")
		"searchString",		// chaîne de recherche
		"searchField"		// champ de recherche
							// --> si champ d'une entité liée : séparer par un "__"
							//     par ex. : videos__nom
		);

	protected $newObject;					// Objet type courant
	private $ObjTestEntite;					// Objet type utilisé comme modèle

	protected $service = array();
	protected $asset;						// templating assets

	protected $modeFixtures = false; 		// true pour mode fixtures actif

	protected $init = array();				// tableau des entités initialisées

	public function __construct(ContainerInterface $container) {
		$this->container 		= $container;
		$this->router 			= $this->container->get('router');
		$this->aetools 			= $this->container->get('acmeGroup.aetools');
		$this->asset 			= $this->container->get('templating.helper.assets');
		$this->getEm();

		// Détection automatique du mode FIXTURES
		if($this->container->get("request")->attributes->get('_controller') === null) {
			$this->modeFixtures = true;
		} else {
			$this->modeFixtures 	= false;
			$this->serviceRequ 		= $this->container->get('request');
			$this->serviceSess 		= $this->container->get('request')->getSession();
			$this->sessionData		= $this->container->get("session");
			$this->flashBag 		= $this->sessionData->getFlashBag();
			$this->securityContext 	= $this->container->get('security.context');
			$this->route 			= $this->container->get("request")->attributes->get('_route');
			// $this->formFactory 		= $formFactory;
			// récupère la version
			$this->version = $this->sessionData->get('version');
			// $this->user 		= $this->container->get('security.context')->getToken()->getUser();
			// $this->baseUrl = $this->container->get('request')->getBaseUrl();
			// $this->flashBag->set('info', 'Utilisateur : '.$this->user);
	
			// définition du contexte
		}
		$this->setContext("auto");
		// $this->listOfEnties();
		foreach($this->listOfEnties() as $EN => $entity) $this->init[$EN] = false;

		// $this->loadCurrentUser();
	}

	public function getModeFixtures() {
		return $this->modeFixtures;
	}

	public function getName() {
		return $this->serviceNom;
	}

	public function loadCurrentUser() {
		$roles = array(
			"ROLE_USER" 		=> "utilisateur",
			"ROLE_EDITOR" 		=> "éditeur",
			"ROLE_ADMIN" 		=> "Administrateur",
			"ROLE_SUPER_ADMIN" 	=> "super adminstrateur"
			);
		if($this->container->get('security.context')->isGranted('ROLE_USER')) {
			$this->user = $this->container->get('security.context')->getToken()->getUser();
			// $this->flashBag->set('info', 'Utilisateur : '.$this->user->getUsername()." (".$this->user->getId().")");
			// if($this->container->get('security.context')->isGranted('ROLE_EDITOR')) {
			// 	$rolesuser = $this->user->getRoles();
			// 	$txtroles = "";
			// 	foreach($rolesuser as $role) $txtroles .= "• ".$roles[$role]."<br />";
			// 	$this->flashBag->set('info', 'Utilisateur : '.$this->user->getUsername()."<br />Vos droits :<br />".$txtroles."<br />Utilisez la barre de gestion en haut de l'écran pour modifier le site.");
			// }
		} else {
			$this->user = false;
			// $this->flashBag->set('info', 'Utilisateur inconnu (anon.)');
		}
	}

	/**
	* defineEntity
	* initialise avec le nom de l'entité : !!! format "groupe\bundle\dossier\entite" !!!
	* @param string $classEntite
	*/
	public function defineEntity($classEntite, $context = "auto", $memo = true) {
		if($this->modeFixtures === false) $this->version = $this->sessionData->get('version');
		// si on a juste mis le nom de l'entité en paramètre $classEntite
		$classEntite = $this->completeEntiteNamespace($classEntite);
		// mémorise l'entité précédente
		if($memo === true) { $this->memorisePreviousEntity(); }
		// mémorise l'entité originale
		$this->memoriseOriginalEntity();
		// définit le contexte d'utilisation
		$this->setContext($context);

		$this->classEntite		= $classEntite;
		// noms
		$this->ObjTestEntite	= new $classEntite();
		$explodName = $this->detachEntityNameSpace($this->classEntite);
		$this->groupeName		= $explodName[0];		// "AcmeGroup" par exemple
		$this->bundleName		= $explodName[1];		// "LaboBundle" par exemple
		$this->dossierName		= $explodName[2];		// "Entity" généralement
		$this->entiteName		= $explodName[3];		// nom court de l'entité, par exemple "article"
		$this->serviceNom		= $this->entiteName;	// nom du service -> utilisé pour mise en session
		$this->bundleNameEntiteName	= $this->bundleName.$this->entiteName; // nom du bundle + entité, par exemple "LabuBundlearticle"
		$this->repoNameEntite	= $this->repoNameWithClassName($this->classEntite); // Classe du repository, par exemple "LaboTestmanuBundle:article"
		$this->formNameEntite	= $this->specialFormNameWithClassName($this->classEntite); // nom de la classe formulaire
		// Objet Repository
		$this->getRepo();
		// Objet recherches
		$this->findParamsInit($this->entiteName);
		// renvoie l'objet entitiesGeneric
		return $this;
	}

	/**
	* serviceEventInit
	* Initialise le service - attention : cette méthode est appelée en requête principale par EventListener !!!
	* 
	* @param FilterControllerEvent $event
	* @param boolean $reLoad
	*/
	public function serviceEventInit(FilterControllerEvent $event, $reLoad = false) {
		$this->serviceSess = $event->getRequest()->getSession();
		// $this->findParamsInit();
	}



	/**
	* setContext
	* initialise le context d'utilisation de l'entité
	* @param string $context
	*/
	public function setContext($context = "auto") {
		if($this->modeFixtures === true) $this->echoFixtures("SERVICES ---> Mode FIXTURES activé.\n\n");
		if($this->modeFixtures === true && $context === "auto") $this->context = "fixtures"; 
			else $this->context = $context;
	}

	/**
	* repoNameWithClassName
	* renvoie le nom du repository d'après le nom de la classe
	*/
	public function repoNameWithClassName($classEntite) {
		$splits = explode("\\", $classEntite);
		return $splits[0].$splits[1].":".$splits[3];
	}

	/**
	* repoNameWithClassName
	* renvoie le nom du repository d'après le nom de la classe
	* @param string $classEntite (nameSpace de l'entite)
	* @param string $special (suffixe pour le nom du formulaire, ex. "Mini" pour "articleMiniType")
	* @param string $dossierForm (nom du dossier contenant le formulaire - "Form" par défaut)
	*/
	public function specialFormNameWithClassName($classEntite, $special = "", $dossierForm = "Form") {
		$splits = explode("\\", $classEntite);
		return $splits[0]."\\".$splits[1]."\\".$dossierForm."\\".$splits[3].$special.'Type';
	}

	/**
	* memorisePreviousEntity
	* Mémorise le nom de l'entité pour récupération ultérieure
	* 
	*/
	public function memorisePreviousEntity() {
		$this->entitePreviousSave = $this->classEntite;
		// $this->memoriseOriginalEntity();
		return $this;
	}

	/**
	* restitutePreviousEntity
	* Restitue l'entité (attention : écrase l'entité courante)
	* 
	*/
	public function restitutePreviousEntity() {
		if($this->entitePreviousSave !== false) {
			$this->defineEntity($this->entitePreviousSave, "auto", false);
			$this->entitePreviousSave = false;
		}
		return $this;
	}

	/**
	* memoriseOriginalEntity
	* Mémorise le nom de l'entité d'origine
	* 
	*/
	public function memoriseOriginalEntity() {
		if($this->entiteOriginalSave === false) {
			$this->entiteOriginalSave = $this->classEntite;
		}
		return $this;
	}

	/**
	* restituteOriginalEntity
	* Restitue l'entité d'origine
	* 
	*/
	public function restituteOriginalEntity() {
		if($this->entiteOriginalSave !== false) {
			$this->defineEntity($this->entiteOriginalSave, "auto", false);
			// $this->entiteOriginalSave = false;
		}
		return $this;
	}

	/**
	* newObject
	* Renvoie un nouvel objet entité
	* si $loadDefaults = true, charge les valeurs par défaut des entités liées
	* @param boolean $loadDefaults
	* 
	*/
	public function newObject($loadDefaults = null) {
		// $this->memoriseOriginalEntity();
		$SN = $this->classEntite;
		$this->newObject = new $SN;
		if($loadDefaults !== null) {
			$data["metaInfo"] = $this->getMetaInfo($this->newObject);
			// var_dump($metaInfo['listColumns']);
			foreach($data["metaInfo"]['listColumns'] as $nom => $datt) {
				if(($datt["Association"] !== "aucune") && ($nom !== 'parent')) {
					$NOAclassName = $datt['targetEntity'];
					$newObjAssoc = new $NOAclassName;
					// $this->echoFixtures('Repo : '.$this->repoNameWithClassName($NOAclassName));
					$repo = $this->em->getRepository($this->repoNameWithClassName($NOAclassName));
					if(method_exists($repo, "defaultVal")) {
						$values = $repo->defaultVal();
						switch ($datt["Association"]) {
							case 'collection':
								$methode = 'add'.ucfirst(substr($nom, 0, strlen($nom) - 1));
								if(is_array($values)) {
									foreach($values as $val) {
										if(method_exists($this->newObject, $methode)) $this->newObject->$methode($val);
									}
								}
								else if(is_object($values) && method_exists($this->newObject, $methode)) $this->newObject->$methode($values);
								break;
							default: // single
								$methode = 'set'.ucfirst($nom);
								if(is_array($values) && count($values) > 0) $vvv = $values[0];
									else $vvv = $values;
								if(is_object($vvv) && method_exists($this->newObject, $methode)) $this->newObject->$methode($vvv);
								break;
						}
					}
				}
			}
		}
		// $this->restituteOriginalEntity();
		// $this->defineEntity($SN);
		// $this->echoFixtures("Objet de classe : ".get_class($this->newObject)."<br />");
		return $this->newObject;
	}

	/**
	 * emptyField
	 * Vide les données d'un champ de l'objet $object
	 * si l'objet n'est pas précisé, un nouvel objet est créé ou utilise l'objet courant
	 * @param $field
	 * @param $object (si null, utilise l'objet courant)
	 */
	public function emptyField($field, $object = null) {
		if(is_string($object)) {
			$this->defineEntity($object);
			$object = $this->newObject(true);
		}
		if(!is_object($object) || is_object($this->newObject)) {
			$object = $this->newObject;
		}
		if(is_object($object)) {
			$info = $this->getMetaInfoField($object, $field);
			if($info['Association'] === "aucune") {
				// valeur simple
				switch($info['type']) {
					case "array":
						$gets = "get".ucfirst($field);
						$object->$gets()->clear();
						break;
					case "integer":
						$set = "set".ucfirst($field);
						if($info['nullable'] === true) $object->$set(null); else $object->$set(0);
						break;
					case "boolean":
						$set = "set".ucfirst($field);
						if($info['nullable'] === true) $object->$set(false); else $object->$set(true);
						break;
					case "datetime":
						$set = "set".ucfirst($field);
						if($info['nullable'] === true) $object->$set(null); else $object->$set("0000-00-00");
						break;
					default:
						$set = "set".ucfirst($field);
						if($info['nullable'] === true) $object->$set(null); else $object->$set("");
						break;
				}
			} else if($info['Association'] === "single") {
				// association : single
				$set = "set".ucfirst($field);
				if($info['nullable'] === true) $object->$set(null);
			} else if($info['Association'] === "collection") {
				// association : collection
				$gets = "get".ucfirst($field);
				$object->$gets()->clear();
			}
		} else return false;
		return $object;
	}


	///////////////////////////////////
	// ESPACES DE NOMS

	/**
	* getGroupeName
	* renvoie le nom du groupe de l'entité
	*/
	public function getGroupeName() { return $this->groupeName; }

	/**
	* getBundleName
	* renvoie le nom du bundle de l'entité
	*/
	public function getBundleName() { return $this->bundleName; }

	/**
	* getDossierName
	* renvoie le nom du dossier contenant l'entité
	*/
	public function getDossierName() { return $this->dossierName; }

	/**
	* getBundleNameEntiteName
	* renvoie le nom du bundle + de l'entité
	*/
	public function getBundleNameEntiteName() { return $this->bundleNameEntiteName; }

	/**
	* getEntiteName
	* renvoie le nom de l'entité seul
	*/
	public function getEntiteName() { return $this->entiteName; }

	/**
	* getRepoNameEntite
	* retourne le chemin pour le repository de l'entité
	*/
	public function getRepoNameEntite() { return $this->repoNameEntite; }

	/**
	* getFormNameEntite
	* retourne le chemin pour le formulaire de l'entité
	*/
	public function getFormNameEntite() { return $this->formNameEntite; }

	/**
	* getClassEntite
	* renvoie le nom de l'entité au format "groupe\bundle\dossier\entite"
	*/
	public function getClassEntite() {
		return $this->classEntite;
	}

	/**
	* getRepo
	* renvoie le Repository
	*/
	public function getRepo() {
		if(isset($this->ObjTestEntite)) {
			// echo($this->serviceNom."<br /><pre>");
			// var_dump($this->version);
			// echo("</pre>");
			$this->repo = $this->getEm()->getRepository($this->repoNameEntite);
	
			if($this->modeFixtures === false) {
				$this->version = $this->sessionData->get('version');
				if((method_exists($this->ObjTestEntite, "setVersion")) || (method_exists($this->ObjTestEntite, "addVersion")))
					$hasVersion = true; else $hasVersion = false;
				// définit la version pour le Repository
				if(method_exists($this->repo, "setVersion") && ($this->version !== null)) {
					$this->repo->setVersion($this->version['slug'], $this->version['shutdown']);
					// echo($this->serviceNom." => Version repo (in EG) : ".$this->repo->getVersion()."<br />");
				} else {
					// echo($this->serviceNom." => Pas de version définie<br />");
				}
				if(method_exists($this->repo, "dontTestVersion") && $hasVersion === false) {
					$this->repo->dontTestVersion();
					// echo($this->serviceNom." => Don't test version !<br />");
				}
			}
			return $this->repo;
		} else return false;
	}

	/**
	* getEm
	* renvoie l'entityManager
	*/
	public function getEm() {
		$this->em = $this->container->get('doctrine')->getManager();
		return $this->em;
	}



	///////////////////////////////////
	// REQUETES DE RECHERCHE

	/**
	* findIntelligent
	* Recherche intelligente d'un élément d'après n'importe quel paramètre $texte
	* retourne un array() de toutes les lignes trouvées
	*/
	public function findIntelligent($texte, $nbReponses = 0) {
		if(preg_match("#^(\d+)$#", $texte)) {
			// uniquement un chiffre = id
			$r = $this->getRepo()->find($texte);
		} else {
			$r = $this->getRepo()->findByNom($texte);
			if(count($r) < 1) $r = $this->getRepo()->findBySlug($texte);
		}
		return $r;
	}

	/**
	* getArrayById
	* retourne un array() de toutes les lignes trouvées
	* !!! ARRAY !!!
	*/
	public function getArrayById($id) {
		return array($this->getId($id));
	}

	/**
	* getById
	* retourne l'élément d'id $id
	*/
	public function getById($id) {
		return $this->getRepo()->find($id);
	}

	/**
	* getAll
	* retourne un array() de toutes les lignes trouvées
	* 
	*/
	public function getAll() {
		return $this->getRepo()->findAll();
	}

	/**
	* getByMethode
	* retourne un array() de toutes les lignes trouvées avec la méthode (getRepo())
	* @param string $methode
	* @return array ou null si rien trouvé (ou false si la méthode n'existe pas)
	*/
	public function getByMethode($methode) {
		$r = null;
		if(method_exists($this->getRepo(), $methode)) {
			$r = $this->getRepo()->$methode();
			if(count($r) < 1) $r = null;
		} else $r = false;
		return $r;
	}

	public function findXrandomElements($n = 3) {
		return $this->getRepo()->findXrandomElements($n);
	}

	/***********************************************/
	/** Actions sur entités avec refresh (par iframe !)
	/***********************************************/

	/**
	* actionById
	* Action sur entité à partir de son id
	*/
	public function actionById($action, $id, $maj = true) {
		return $this->actionGeneric($action, $this->getRepo()->find($id), $maj);
	}

	/**
	* actionBySlug
	* Action sur entité à partir de son slug
	*/
	public function actionBySlug($action, $slug, $maj = true) {
		return $this->actionGeneric($action, $this->getRepo()->findBySlug($slug), $maj);
	}

	/**
	* actionGeneric
	* Actions sur entités
	* @param $action
	*			format : champ + ":" + valeur
	*				-> ajouter un _ devant la valeur pour certaines actions précises définies dans ActionGeneric
	*					_toogle : pour le statut = passe à "Actif"/"Inactif"
	* @param $entiteObj = objet entité
	*/
	public function actionGeneric($action, $entiteObj, $maj = true) {
		if(is_object($entiteObj)) {
			// formalisation des données
			$id = $entiteObj->getId();
			$act = explode(":", $action);
			$champ = $act[0]; // nom du champ
			$action = $act[1]; // action à réaliser
			switch($action) {
				case "_toogle":
					$memrepo = $this->getRepo();
					$memem = $this->em;
					if($entiteObj->getStatut()->getNom() == "Actif")
						$find = "Inactif"; else $find = "Actif";
					$statut = $this->em->getRepository("AcmeGroupe\\LaboBundle\\Entity\\statut")->findByNom($find);
					$entiteObj->setStatut($statut[0]);
					$this->repo = $memrepo;
					$this->em = $memem;
					$this->em->flush();
					break;
				default:
					break;
			}
			if($maj === true) {
				$message = "Les modifications sont enregistrées.";
				$script = "<script>window.top.window.refreshBalises('".$message."', '".$this->serviceBdl."Bundle".$this->serviceNom."', ".$id.");</script>";
			}
			return array("result" => true, "message" => "Opération réussie.", "script" => $script);
		} else return array("result" => false, "message" => "Entité inexistante.", "script" => "");
	}



	/***********************************************/
	/** Fonctions spécifiques à l'event listener
	/***********************************************/

	/**
	* siteListener_getAllData
	* Renvoie les données au format array() sur l'entité enregistrée en session
	* 
	*/
	public function siteListener_getAllData() {
		return $this->serviceSess->get($this->serviceNom);
	}

	/**
	* siteListener_getData
	* Renvoie la donnée demandée (sinon, toutes les données)
	* 
	*/
	public function siteListener_getData($nom = null) {
		if($nom === null) {
			return $this->siteListener_getAllData();
		} else {
			$data = $this->serviceSess->get($this->serviceNom);
			if(isset($data[$nom])) return $data[$nom];
			else return null;
		}
	}

	/**
	* siteListener_changeDataSession
	* Modifie (ou crée) une valeur de l'entité enregistrée en session
	* 
	*/
	public function siteListener_changeDataSession($nom, $data) {
		$this->service = $this->serviceSess->get($this->serviceNom);
		$this->service[$nom] = $data;
		$this->serviceSess->set($this->serviceNom, $this->service);
	}

	/**
	* siteListener_InSession
	* dépose les informations de l'entité dans la session
	*
	*/
	public function siteListener_InSession($nom = null, $data = null) {
		if($nom === null) $nom = $this->serviceNom;
		if($data === null) $data = $this->service;
		$this->serviceSess->set($nom, $data);
		return $this;
	}

	/**
	* siteListener_OutSession
	* suprime les informations de l'entité dans la session
	*
	*/
	public function siteListener_OutSession($nom = null) {
		if($nom === null) $nom = $this->serviceNom;
		$this->serviceSess->set($this->serviceNom, null);
		return $this;
	}


	/***********************************************/
	/** Fonctions spécifiques sur Directeditor
	/***********************************************/

	/**
	 * saveData
	 * Enregistre de nouveaux textes dans une entité existante
	 * --> ATTENTION : préciser au préalable l'entité avec $this->defineEntity() !!!
	 *
	 * @param integer $id
	 * @param array $params
	 * $params est un tableau associatif [nomDuChamp] = texte
	 * si le nom du champ n'existe pas, la ligne est ignorée
	 * @return array
	 * 		--> result	: boolean (false = échec)
	 * 		--> texte	: array des textes persistés
	 */
	public function saveData($id, $params) {
		$de = $this->container->get("acmeGroup.directeditor"); //$de->convertDirecteditorText
		$r = array();
		$i = 0;
		$r['result'] = false;
		$r['data'] = array();
		$ent = $this->getRepo()->find($id);
		if(is_object($ent)) {
			foreach($params as $nom => $texte) {
				// $texte = trim($texte);
				$method = "set".ucfirst($nom);
				if(method_exists($ent, $method)) {
					$ent->$method($texte);
					$r['data'][$i] = $de->convertDirecteditorText($texte);
					$i++;
				}
			}
			$this->em->flush();
			$r['result'] = true;
		}
		return $r;
	}

	/**
	* getChampById
	* retourne un array() de toutes les lignes trouvées
	* !!! ARRAY !!!
	*/
	public function getChampById($id, $champ, $short = null) {
		$de = $this->container->get("acmeGroup.directeditor"); //$de->convertDirecteditorText
		$r = array();
		$r['result'] = false;
		$ent = $this->getRepo()->find($id);
		if(is_object($ent)) {
			$method = "get".ucfirst($champ);
			if(method_exists($ent, $method)) {
				$data = $de->convertDirecteditorText($ent->$method());
				if($short !== null) {
					$aetext = $this->container->get('acmeGroup.textutilities');
					$r["data"][] = $aetext->phraseCut($data, $short);
				} else $r["data"][] = $data;
				$r['result'] = true;
			}
		}
		return $r;
	}

	public function detachEntityNameSpace($nameSpace) {
		$nameSpace = $this->completeEntiteNamespace($nameSpace);
		if($nameSpace !== null) {
			$r = explode($this->aslash, $nameSpace);
			if(count($r) > 3) return $r;
				else return null;
		} else return null;
	}

	public function completeEntiteNamespace($nameSpace) {
		if(array_key_exists($nameSpace, $this->listOfEnties)) $nameSpace = $this->listOfEnties[$nameSpace];
		if(in_array($nameSpace, $this->listOfEnties)) return $nameSpace;
			else return null;
	}

	public function listOfEnties() {
		if($this->listOfEnties === null) {
			$this->listOfEnties = array();
			// recherche de tous les dossiers de src/ (donc tous les groupes de bundles)
			$groupesSRC = $this->aetools->exploreDir("src/", null, "dossiers", true);
			$groupes = array();
			foreach($groupesSRC as $nom) $groupes[] = $nom['nom'];
			$entitiesNameSpaces = $this->em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
			foreach($entitiesNameSpaces as $ENS) {
				$EE = explode($this->aslash, $ENS);
				if(in_array($EE[0], $groupes)) $this->listOfEnties[$EE[count($EE) - 1]] = $ENS;
			}
		}
		return $this->listOfEnties;
	}

	public function getAllEntites() {
		$r = array();
		// $conn = $this->container->get('database_connection');
		// $sm = $conn->getSchemaManager();
		// $r['classEntites'] = array();
		// $tables = $sm->listTables();
		// foreach ($tables as $table) if(count(explode("_", $table->getName())) == 1) {
		// $cpt = 0;
		// foreach ($table->getColumns() as $column) $cpt++;
		// $r['classEntites'][$table->getName()]["count"] = $cpt;
		// if($table->getName() == "User") $r['classEntites'][$table->getName()]["bundleEntite"] = "AcmeGroup\\UserBundle\\Entity\\".$table->getName();
		// 		else $r['classEntites'][$table->getName()]["bundleEntite"] = "labo\\Bundle\\TestmanuBundle\\Entity\\".$table->getName();
		// }
		$entitiesNameSpaces = $this->em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
		foreach($entitiesNameSpaces as $ENS) {
			$EE = $this->detachEntityNameSpace($ENS);
			if($EE[0] === "AcmeGroup") {
				$r['classEntites'][$EE[count($EE) - 1]]["bundleEntite"] = $ENS;
				$CMD = $this->em->getClassMetadata($ENS);
				$colNoAssoc = $CMD->getColumnNames();
				$colWtAssoc = $CMD->getAssociationNames();
				$r['classEntites'][$EE[count($EE) - 1]]["count"] = count(array_merge($colNoAssoc, $colWtAssoc));
				$r['classEntites'][$EE[count($EE) - 1]]["options"] = array();
			}
		}
		return $r;
	}

	public function getMetaInfo($newObject = null) {
		if($newObject === null) $newObject = $this->newObject;
		$r['CMData'] = $this->em->getClassMetadata(get_class($newObject));
		// informations sur la classe (entité)
		$r['classInfo']['className'] = $r['CMData']->getName();
		$r['classInfo']['tableName'] = $r['CMData']->getTableName();
		$r['classInfo']['repoName'] = $r['CMData']->customRepositoryClassName;
		$r['classInfo']['reflexProp'] = $r['CMData']->getReflectionProperties();
		// $r['classInfo']['lifecycleCallbacks'] = $r['CMData']->getLifecycleCallbacks(!!!!!!!argument!!!!!!!!);
		// $r['CMDataMethods'] = get_class_methods($r['CMData']);
		// $colNoAssoc = $r['CMData']->getColumnNames();
		$colNoAssoc = $r['CMData']->getFieldNames();
		$colWtAssoc = $r['CMData']->getAssociationNames();
		foreach(array_merge($colNoAssoc, $colWtAssoc) as $nom) {
			// if((substr($nom, -1) == "s" && substr($nom, -2, -1) != "s") || (substr($nom, -2) == "ss")) $nom = substr($nom, 0, -1);
			$r['listColumns'][$r['CMData']->getFieldName($nom)] = $this->getMetaInfoField($newObject, $r['CMData']->getFieldName($nom));
		}
		// Liste des libellés du tableau -> pour admin
		$rr = array();
		foreach($r['listColumns'] as $val) {
			foreach($val as $nom => $val2) {
				$rr[$nom] = $nom;
			}
		}
		$r['libelles'] = $rr;
		// $r['entiteName'] = get_class($newObject);
		return $r;
	}

	public function getMetaInfoField($newObject, $field) {
		$r = array();
		$CMD = $this->em->getClassMetadata(get_class($newObject));
		// $field = $CMD->getFieldForColumn($column);
		if($CMD->hasAssociation($field) === false) {
			// Sans association
			$r = $CMD->getFieldMapping($field);
			$r['Association'] = "aucune";
		} else {
			// Avec association
			$r = $CMD->getAssociationMapping($field);
			if($CMD->isSingleValuedAssociation($field)) {
				$r['Association'] = "single";
				$r['unique'] = $r["joinColumns"][0]["unique"];
				$r['nullable'] = $r["joinColumns"][0]["nullable"];
			} else if($CMD->isCollectionValuedAssociation($field)) {
				$r['Association'] = "collection";
				// $r['nullable'] = $CMD->isNullable($field);
				// $r['unique'] = $CMD->isUniqueField($field);
			} else {
				// Association inconnue !!!
				$r['Association'] = "[inconnue]";
			}
		}
		return $r;
	}


	//////////////////////////////////////////////
	// Paramètres de recherche
	//////////////////////////////////////////////

	public function compileMetaInfo($classEntite) {
		$libells = array( // noms des libellés
			"id" 			=> "id",
			"nom"			=> "nom",
			"descriptif"	=> "descriptif",
			"statut"		=> "statut",
			);
		$r = array();
		$metaInfo = $this->getMetaInfo($this->newObject());
		foreach($metaInfo['listColumns'] as $nom => $info) {
			if(in_array($nom, $libells)) $nomL = $libells[$nom];
				else $nomL = $nom;
			if($info['Association'] == "aucune") {
				// pas de relation
				switch($info['type']) {
					case "string" :
						$r[$nom]['colbloc'] = "col_texteCentre";
						$r[$nom]['libelle'] = $nomL;
						break;
					case "integer" :
						$r[$nom]['colbloc'] = "col_texteCentre";
						$r[$nom]['libelle'] = $nomL;
						break;
					case "smallint" :
						$r[$nom]['colbloc'] = "col_texteCentre";
						$r[$nom]['libelle'] = $nomL;
						break;
					case "datetime" :
						$r[$nom]['colbloc'] = "col_date";
						$r[$nom]['libelle'] = $nomL;
						break;
				}
			} else if($info['Association'] == "single") {
				// relation One
				switch("fieldTargetClass") {
					// case "AcmeGroup\LaboBundle\Entity\statut" :
					// 	$r[$nom]['colbloc'] = "col_statut";
					// 	$r[$nom]['libelle'] = $nomL;
					// 	break;

				}
			} else if($info['Association'] == "collection") {
				// relation Many
				
			}
		}
		return $r;
	}

	/**
	* findParamsInit
	* initialise les données de recherche sur un ou plusieurs entités existantes
	* @param string/array/null $classEntite
	* @return ???
	*/
	public function findParamsInit($classEntite = null) {
		// route : route actuelle (automatique)
		// recherche :
		// 	- searchField : nom du champ
		// 	- searchFieldRel : nom du champ relation (collection ou single) -> optionnel sinon null
		// 	- searchString : valeur à rechercher
		// pagination : 
		// 	- page (numéro de page ou 0 ou null)
		// 	- nbbypage (nombre de résultats par page)
		// 	- list_nbbypage (liste des options de nombre de résultat par page : ex. array(“10”, “20”, “50”, “100”))
		// order :
		// 	- champ
		// 	- sens (DESC, ASC)
		// affichage :
		// 	- nom du remplate de liste (vignette, ligne, etc. sinon null)
		if($this->modeFixtures === false) {
			// Définition des entités à définir
			$entitesList = array();
			if($classEntite !== null) {
				if(is_string($classEntite)) $classEntite = array($classEntite);
				// Un tableau d'entités $classEntite
				$r = count($classEntite);
				foreach($classEntite as $nomCE) {
					$de = $this->detachEntityNameSpace($nomCE);
					if($de !== null) {
						$entitesList[$de[3]] = $this->listOfEnties[$de[3]];
					} else $r--;
				}
				if($r < 1) $r = null;
			} else {
				// Toutes les entités si null
				$entitesList = $this->listOfEnties;
			}
			// Chargement des paramètres des entités
			if(($entitesList !== null) && (count($entitesList) > 0)) {
				foreach($entitesList as $shortName => $className) {
					$byPost = $this->findParamsPostGet();
					if($byPost !== null) {
						// paramètres modifiés en Post
					}
					$bySess = $this->serviceSess->get('findParams_'.$shortName);
					if(isset($bySess[$this->route])) {
						// paramètres présents en session
						// $this->findParams[$shortName] = unserialize($bySess);
						$this->findParams[$shortName][$this->route] = $bySess[$this->route];
					}
					$paramData[$shortName][$this->route]["entiteNom"] = $shortName;
					// findParams --> enregistrement en session
					$this->siteListener_InSession("findParams_".$shortName, $paramData[$shortName]);
					$r = $paramData[$shortName][$this->route];
				}
			} else $r = null;
		} else $r = null;
		return $r;
	}

	public function findParamsPostGet() {
		$Mtd = array();
		$Mtd["get"]		= $this->serviceRequ->query;	// GET
		$Mtd["post"]	= $this->serviceRequ->request;	// POST
		foreach($Mtd as $method => $data) {
			if($data->get("findParamsEntiteNom") !== "") {
				// 
			} else return null;
		}
	}

	public function getPaginationQuery($classEntite) {
		// $getMtd = $this->serviceRequ->query; // GET
		$getMtd = $this->serviceRequ->request; // POST
		$r["classEntite"] = $getMtd->get('classEntite');
		if($r["classEntite"] == $classEntite) {
			// données POST concernent l'entité en question :
			$r["page"] = $getMtd->get('page');
			$r["lignes"] = $getMtd->get('lignes');
			$r["ordre"] = $getMtd->get('ordre');
			$r["sens"] = $getMtd->get('sens');
			$r["searchString"] = $getMtd->get('searchString');
			$r["searchField"] = $getMtd->get('searchField');
	
			if($r["lignes"] == null) $r["lignes"] = 20;
			if($r["page"] == null) $r["page"] = 1;
			if($r["page"] < 1) $r["page"] = 1;
		} else {
			// sinon récupère les données en session
		}

		return $r;
	}




	//////////////////////////////////////////////
	// Fonctionnalités pour fixtures
	//////////////////////////////////////////////

	/**
	 * getNameFixturesFile
	 * Renvoie le nom de fichier standard pour les données fixtures en xml
	 * @return string
	 */
	public function getNameFixturesFile() {
		return "fixtures_".$this->getEntiteName()."s.xml";
	}

	/**
	 * getDossierTextFiles
	 * Renvoie le nom du dossier contenant les fichiers texte
	 * @return string
	 */
	public function getDossierTextFiles() {
		return "txt";
	}

	protected function echoFixtures($t) {
		if($this->modeFixtures === true) echo($t);
	}


}

?>