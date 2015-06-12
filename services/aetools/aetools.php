<?php
namespace laboBundle\services\aetools;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

use \Exception;
use \DateTime;
use \ReflectionClass;


/**
 * Service aeTools
 * - Gestion des fichiers/dossiers
 * - Gestion services Symfony : router / tamplating / etc.
 */
class aetools {

	const SERVEUR_TYPE			= 'UNIX/LINUX';		// Type de serveur
	const SLASH					= '/';				// slash
	const ASLASH 				= '\\';				// anti-slashes
	const WIN_ASLASH			= '/';				// anti-slashes Windows
	const ALL_FILES 			= "^.+$";			// motif PCRE pour tous textes
	const EOLine				= "\n";				// End of line Terminal
	const TAB1					= "   - ";
	const TAB2					= "      - ";
	// Paths
	const GO_TO_ROOT 			= '/../../../../../../';
	const WEB_PATH				= 'web/';
	// Dossiers
	const DEFAULT_CHMOD			= 0755;

	protected $ctrlDefined 		= null;				// boolean : controller dénini ?
	protected $container;							// container
	// autres services
	protected $router;								// router
	protected $asset;								// asset

	protected $controller;
	protected $requAttributes;
	protected $serviceRequ;
	protected $serviceSess;
	protected $sessionData;
	protected $flashBag;
	protected $securityContext;
	protected $route;
	protected $texttools;							// outils de texte

	protected $controllerPath;						// chemin complet du controller
	protected $ctrlFolder;							// dossier du controller
	protected $controllerName;						// nom du controller
	protected $actionName;							// nom de la méthode appelée
	protected $singleActionName;					// nom de la méthode appelée, sans "Action"

	protected $memo = '__self';						// memo pour savePath pour ce service
	protected $pathMemo = array();					// contenu des mémo pour savePath




	protected $currentPath;
	protected $aslash;
	protected $rootPath;
	protected $recursiveTree;
	protected $allRoutes = array();
	protected $nofiles = '^\.';
	protected $liste;
	protected $service = array();
	protected $groupeName;			// nom du groupe
	protected $bundleName;			// nom du bundle
	protected $gotoroot;

	protected $listP = array("groupeName", "bundleName", "ctrlFolder", "controllerName");
	protected $nP;

	public function __construct(ContainerInterface $container = null) {
		$this->container 		= $container;
		// initialisation de données nécessaires au service
		$this->initAllData();
		// initialisation nécessitant la présence du controller
		// return $this;
	}

	public function __destruct() {
		$this->close();
	}

	/**
	 * initialise les données de service.
	 * ATTENTION : nécessite la présence du controller !
	 * @return string
	 */
	protected function initAllData() {
		$this->gotoroot 			= __DIR__.self::GO_TO_ROOT;
		if($this->container !== null) {
			$this->router 			= $this->container->get('router');
			$this->asset 			= $this->container->get('templating.helper.assets');
			$this->texttools		= $this->container->get('labobundle.textutilities');
			$this->datetools		= $this->container->get('labobundle.aedates');
		}
		if($this->isControllerPresent()) {
			$this->serviceRequ 			= $this->container->get('request');
			$this->requAttributes		= $this->serviceRequ->attributes;
			$this->serviceSess 			= $this->serviceRequ->getSession();
			$this->controller			= $this->requAttributes->get('_controller');
			$this->route 				= $this->requAttributes->get('_route');
			$this->sessionData			= $this->container->get("session");
			$this->flashBag 			= $this->sessionData->getFlashBag();
			$this->securityContext 		= $this->container->get('security.context');
			$this->getCurrentVersion();
			// $this->setWebPath();
		}
		// slashes
		switch(strtoupper(self::SERVEUR_TYPE)) {
			case "UNIX/LINUX": $this->aslash = self::ASLASH; break;
			case "WINDOWS": $this->aslash = self::WIN_ASLASH; break;
			default: $this->aslash = self::ASLASH; break;
		}
		// nom du mémo
		$this->memo = $this->getName().$this->memo;
	}

	/**
	 * Renvoie le nom de la classe
	 * @return string
	 */
	public function getName() {
		return get_called_class();
	}

	/**
	 * Renvoie le nom de la classe
	 * @return string
	 */
	public function getShortName() {
		return $this->getClassShortName($this->getName());
	}

	/**
	 * Renvoie le nom court de la classe
	 * @return string
	 */
	public function getClassShortName($class) {
		if(is_object($class)) $class = get_class($class);
		if(is_string($class)) {
			$shortName = explode(self::ASLASH, $class);
			return end($shortName);
		}
		return false;
	}

	/**
	 * Renvoie true si le controller est présent
	 * @return boolean
	 */
	public function isControllerPresent() {
		if($this->container !== null) {
			$this->controllerPath = $this->container->get('request')->attributes->get('_controller');
		} else $this->controllerPath === null;
		if($this->controllerPath === null) {
			// pas de controller
			$this->ctrlDefined = false;
		} else {
			// controller présent
			$this->ctrlDefined = true;
			$d = explode("::", $this->controllerPath."");
			if(count($d) < 2)
				$d = explode(":", $this->controllerPath."");
			$this->actionName = $d[1];
			$this->singleActionName = preg_replace("#Action$#", "", $d[1]);
			$e = explode(self::ASLASH, $d[0]);
			if(count($e) < 2) $e = explode(".", $d[0]);
			foreach($e as $idx => $nom) {
				if($idx < (count($this->listP) + 1)) {
					if(isset($this->listP[$idx])) $nP = $this->listP[$idx];
					$this->$nP = $nom;
				}
			}
		}
		return $this->ctrlDefined;
	}

	/**
	 * Renvoie true si le controller est absent
	 * @return boolean
	 */
	public function isControllerAbsent() {
		return !$this->isControllerPresent();
	}

	public function isContainerPresent() {
		return $this->container !== null ? true : false;
	}

	public function isContainerAbsent() {
		return !$this->isContainerPresent();
	}


	////////////////////////////////////////////////////////////////////////////////////////////////////////
	// VERSIONS
	////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Renvoie les données sur la version courante
	 * Données stockées en session
	 * @return array
	 */
	public function getCurrentVersion() {
		if($this->isControllerPresent()) {
			$this->version = $this->sessionData->get('version');
		} else {
			$this->version = false;
		}
		return $this->version;
	}

	/**
	 * Renvoie le slug la version courante
	 * @return string
	 */
	public function getCurrentVersionSlug() {
		if($this->getCurrentVersion() !== false) {
			return $this->version['slug'];
		}
		return false;
	}

	/**
	 * Renvoie le nom la version courante
	 * @return string
	 */
	public function getCurrentVersionNom() {
		if($this->getCurrentVersion() !== false) {
			return $this->version['nom'];
		}
		return false;
	}


	////////////////////////////////////////////////////////////////////////////////////////////////////////
	// PATHS
	////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Ajoute un slash en fin de path s'il n'existe pas
	 * @param string $path
	 * @return string
	 */
	protected function addEndSlash($path) {
		if(substr($path, -1, 1) != self::SLASH) $path .= self::SLASH;
		// echo($path."\n");
		return $path;
	}

	/**
	 * Définit un nouveau path à partir du dossier WEB
	 * @param string $path
	 * @return string
	 */
	public function setWebPath($path = "") {
		$rootPath = $this->addEndSlash($this->gotoroot.self::WEB_PATH.$path);
		if(file_exists($rootPath)) {
			$this->close();
			$this->rootPath = $rootPath;
			$this->currentPath = $rootPath;
			$this->recursiveTree = array(dir($this->currentPath));
			$this->rewind();
			return $this;
		} else return false;
	}

	/**
	 * Définit un nouveau path à partir du dossier ROOT
	 * @param string $path
	 * @return string
	 */
	public function setRootPath($path = "") {
		$rootPath = $this->addEndSlash($this->gotoroot.$path);
		if(file_exists($rootPath)) {
			$this->close();
			$this->rootPath = $rootPath;
			$this->currentPath = $rootPath;
			$this->recursiveTree = array(dir($this->currentPath));
			$this->rewind();
			return $this;
		} else return false;
	}

	/**
	 * Renvoie le contenu à partir d'un path (ou path courant)
	 * !!! insensible à la casse par défaut
	 * renvoie un tableau : 
	 * 		["path"]	= chemin
	 * 		["nom"]		= nom du fichier
	 * 		["full"]	= chemin + nom
	 *		["type"]	= fichier / dossier
	 * @param string/null - path à analyser (currentPath par défaut) (si "/" au début : "/web/" ou "/", on reprend à la racine du site)
	 * @param string $motif - motif preg pour recherche de nom
	 * @param string $genre - "fichiers" ou "dossiers" ou null (null = tous)
	 * @param boolean $recursive - true par défaut / recherche récursive (true = recherche dans les sous-dossiers également)
	 * @param boolean $casseSensitive - true par défaut
	 * @return array
	 */
	public function exploreDir($path = null, $motif = null, $genre = null, $recursive = true, $casseSensitive = true) {
		$path = $this->addEndSlash($path);
		$this->savePath();
		$this->setRootPath($path);
		$this->liste = array();
		while (false !== ($entry = $this->exploreDirectory($path, $motif, $genre, $recursive, $casseSensitive))) {
			$this->liste[] = $entry;
		}
		$this->close();
		$this->restoreSavedPath();
		return $this->liste;
	}

	protected function exploreDirectory($path = null, $motif = null, $genre = null, $recursive = true, $casseSensitive = true) {
		$path = $this->addEndSlash($path);
		$path2 = array();
		// motif
		if($motif === null) $motif = ".+";
		// genre fichier/dossier
		$genre === "dossiers" ? $fichier = false : $fichier = true ;
		$genre === "fichiers" ? $dossier = false : $dossier = true ;
		// casseSensitive
		$casseSensitive === false ? $sens = "i" : $sens = "" ;
		// parcours…
		while(count($this->recursiveTree) > 0) {
			$d = end($this->recursiveTree);
			if(false !== ($entry = $d->read())) {
				if(!preg_match("#".$this->nofiles."#", $entry)) {
					if((is_file($d->path.$entry)) && (preg_match("#".$motif."#".$sens, $entry)) && ($fichier === true)) {
						// fichier
						$path2["path"] = $d->path;
						$path2["nom"]  = $entry;
						$path2["full"] = $d->path.$entry;
						$path2["type"] = "fichier";
						return $path2;
					}
					if(is_dir($this->addEndSlash($d->path.$entry))) {
						if((preg_match("#".$motif."#".$sens, $entry)) && ($dossier === true)) {
							// dossier
							$path2["path"] = $d->path;
							$path2["nom"]  = $entry;
							$path2["full"] = $d->path.$entry;
							$path2["type"] = "dossier";
						}
						// sous-dossiers
						if($recursive === true) {
							if(false !== ($child = dir($d->path.$entry.self::SLASH))) {
								// $this->currentPath = $d->path.$entry.$this->aslash;
								$this->recursiveTree[] = $child;
							}
						}
						if(count($path2) > 0) {
							return $path2;
						}
					}
				}
			} else {
				// supprime le dernier élément de recusriveTree en le fermant (close)
				array_pop($this->recursiveTree)->close();
			}
		}
		return false;
	}
	
	/**
	 * read - OBSOLETE
	 * Recherche un fichier $type dans le dossier courant ou ses enfants
	 * !!! insensible à la casse par défaut
	 * renvoie un tableau : 
	 * 		["path"]	= chemin
	 * 		["nom"]		= nom du fichier
	 * 		["full"]	= chemin + nom
	 * @param string $type (expression régulière)
	 * @return array
	 */
	public function read($type = null, $casseSensitive = false) {
		if($casseSensitive === false) $sens = "i"; else $sens = "";
		while(count($this->recursiveTree)>0) {
			$d = end($this->recursiveTree);
			if((false !== ($entry = $d->read()))) {
				if(!preg_match("#".$this->nofiles."#", $entry)) {
					$path["path"] = $d->path;
					$path["nom"]  = $entry;
					$path["full"] = $d->path.$entry;
					
					if(is_file($d->path.$entry)) {
						if($type !== null) $r=preg_match('#'.$type.'#'.$sens, $entry); else $r = true;
						if($r == true || $r == 1) return $path;
					}
					else if(is_dir($d->path.$entry.$this->aslash)) {
						// $this->currentPath = $d->path.$entry.$this->aslash;
						if($child = @dir($d->path.$entry.$this->aslash)) {
							$this->recursiveTree[] = $child;
						}
					}
				}
			} else {
				array_pop($this->recursiveTree)->close();
			}
		}
		return false;
	}

	/**
	 * readAll - OBSOLETE
	 * renvoie la liste de tous les fichiers contenus dans le dossier et ses enfants
	 * !!! insensible à la casse par défaut
	 * renvoie un tableau : 
	 * 		["path"]	= chemin
	 * 		["nom"]		= nom du fichier
	 * 		["full"]	= chemin + nom
	 * @return array
	 */
	public function readAll($type = null, $path = null, $casseSensitive = true) {
		// if(null !== $path) $this->setWebPath($path);
		// 	else $this->setWebPath($this->rootPath); // réinitialise
		if(null !== $path) $this->setRootPath($path);
			// else $this->setRootPath(); // réinitialise
		$this->liste = array();
		// echo "<span style='color:white;'> Path : ".$this->getRootPath()."</span><br /><br />";
		while (false !== ($entry = $this->read($type, $casseSensitive))) {
			// echo $entry["path"]."<span style='color:pink;'>".$entry["nom"]."</span><br />";
			$this->liste[] = $entry;
		}
		$this->close();
		return $this->liste;
	}

	protected function rewind() {
		$this->closeChildren();
		$this->rewindCurrent();
	}

	protected function rewindCurrent() {
		return end($this->recursiveTree)->rewind();
	}

	protected function close() {
		if(is_array($this->recursiveTree)) while(true === ($d = array_pop($this->recursiveTree))) {
			$d->close();
		}
	}

	protected function closeChildren() {
		while(count($this->recursiveTree) > 1 && false !== ($d = array_pop($this->recursiveTree))) {
			$d->close();
			return true;
		}
		return false;
	}

	/**
	 * getRootPath
	 * Renvoie le dossier racine
	 * @return string
	 */
	public function getRootPath() {
		return isset($this->rootPath) ? $this->rootPath : false ;
	}

	/**
	 * getCurrentPath
	 * Renvoie le dossier courant
	 * @return string
	 */
	public function getCurrentPath() {
		return isset($this->currentPath) ? $this->currentPath : false ;
	}

	/**
	 * Retrouve et efface les fichiers $file dans le dossier courant et tous les dossiers enfants
	 * @param array $files (peut être des expressions régulières => voir la méthode "read()")
	 * @return array
	 */
	public function findFilesEverywhere($files) {
		$r = array();
		if(is_string($files)) $files = array($files);
		foreach($files as $file) {
			$search = $this->readAll($file, null, true);
			if(count($search) > 0) foreach($search as $found) {
				$r[] = $found;
			}
		}
		return $r;
	}

	/**
	 * Retrouve et efface les fichiers $file dans le dossier courant et tous les dossiers enfants
	 * @param array $files (peut être des expressions régulières => voir la méthode "read()")
	 * @return array
	 */
	public function deleteFilesEverywhere($files) {
		$r = array();
		if(is_string($files)) $files = array($files);
		foreach($files as $file) {
			$search = $this->readAll($file, null, false);
			if(count($search) > 0) foreach($search as $erase) {
				$t = $this->deleteFile($erase["full"]);
				if($t === true) $r['succes'][] = $t;
					else $r['echec'][] = $t;
			}
		}
		return $r;
	}

	/**
	 * Efface le fichier $fileName s’il est dans le dossier courant (ou préciser le chemin !)
	 * @param $fileName
	 * @return boolean
	 */
	public function deleteFile($fileName) {
		$r = false;
		if(is_string($fileName)) {
			if(file_exists($fileName)) {
				if(@unlink($fileName)) $r = true;
			}
		}
		return $r;
	}

	/**
	 * Efface tous les fichiers contenus dans le tableau $files, depuis le dossier courant (ou préciser les chemins !)
	 * @param array $files
	 * @return array
	 */
	public function deleteFiles($files) {
		$r = array();
		if(is_string($files)) { $f = $files; $files = array(); $files[0] = $f; }
		$err = 0;
		foreach($files as $file) {
			$res = $this->deleteFile($file);
			if($res === false) $err++; else $r[] = $res;
		}
		if($err > 0) $r = false;
		return $r;
	}

	/**
	 * Efface le dossier $dir (préciser le chemin !)
	 * @param array $files
	 * @param boolean $deleteIn - efface les fichiers contenus avant
	 * @return array
	 */
	public function deleteDir($dir, $deleteIn = false) {
		$r = false;
		if((file_exists($dir)) && (is_dir($dir))) {
			if($deleteIn === true) {
				// efface les fichiers contenus // Ne marche pas POUR L'INSTANT !!!
				$this->findAndDeleteFiles(self::ALL_FILES, $dir);
			}
			if(@rmdir($dir)) $r = true;
				else $r = false;
		} else $r = false;
		return $r;
	}

	/**
	 * Recherche et efface tous les fichiers contenus dans $files
	 * (préciser le chemin de départ ou utilise la valeur de $rootPath)
	 * @param array/string $files
	 * @param string $path - depuis root site
	 */
	public function findAndDeleteFiles($files, $path = null) {
		$r = array();
		if(null !== $path) $this->setRootPath($path);
			// else $this->setWebPath($this->rootPath); // réinitialise
		if(is_string($files)) $files = array($files);
		$err = 0;
		foreach($files as $file) {
			// $this->readAll("^".$file."$");
			$this->readAll($file); // --> dans $this->liste
			if(count($this->liste) > 0) foreach($this->liste as $fichier) {
				$res = $this->deleteFile($fichier['full']);
				if($res === false) $err++; else $r[] = $res;
			}
		}
		if($err > 0) $r = false;
		return $r;
	}

	///// Créations/suppressions de dossiers

	/**
	 * Crée un dossier s'il n'existe pas. 
	 * Crée tout les dossiers intermédiaires si besoin. 
	 * ex. : pour "web/images/thumbnails/mini/" => créera "thumbnails", puis "mini" s'ils n'existent pas
	 * @param string $dossier
	 * @param integer $chmod (en mode octal)
	 * @return boolean / string
	 */
	public function verifDossierAndCreate($dossier, $chmod = null) {
		$result = true;
		$dossiersTmp = explode(self::SLASH, $dossier);
		$dossiers = array();
		foreach ($dossiersTmp as $dossier) {
			if(strlen(trim($dossier)) > 0) $dossiers[] = $dossier;
		}
		if($chmod === null || !preg_match('#^[0-7]{4}$#', $chmod."")) $chmod = self::DEFAULT_CHMOD;
		// création des dossiers
		foreach ($dossiers as $dossier) {
			$doss = $this->getCurrentPath().$dossier;
			if(!file_exists($doss)) {
				if(!is_dir($doss)) {
					if (!mkdir($doss, $chmod, true)) {
						$result = false;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * avance de $path depuis le path courant
	 * @param string $path
	 * @return boolean
	 */
	public function gotoFromCurrentPath($path = null) {
		$rootPath = $this->getCurrentPath().$path;
		if(file_exists($rootPath)) {
			$this->close();
			$this->rootPath = $rootPath;
			$this->currentPath = $rootPath;
			$this->recursiveTree = array(dir($this->currentPath));
			$this->rewind();
			return $this;
		} else return false;
	}

	/**
	 * Vérifie si un dossier existe (le crée si nécessaire) et s'y place en tant que dossier courant
	 * @param string $type - type de rapport
	 * @return string - chemin courant
	 */
	public function verifAndGotoFromCurrentPath($type = null) {
		$this->rootpath = $this->fmparameters['dossiers']['pathrapports'];
		// vérifie la présence du dossier pathrapports et pointe dessus
		$this->setWebPath();
		$this->verifDossierAndCreate($this->rootpath);
		$this->setWebPath($this->rootpath);
		if(is_string($type)) {
			$path = $this->rootpath.$type.self::SLASH;
			$this->verifDossierAndCreate($type);
			$this->setWebPath($path);
			// echo('Current path : '.$this->getCurrentPath().'<br>');
			return $path;
		}
		return $this->rootpath;
	}


	///// Mémorisations de chemins courants (paths)

	/**
	 * sauvegarde le chemin courant avec un nom
	 * @param $nom
	 * @return aetools
	 */
	public function savePath($nom = null) {
		if(!is_string($nom)) $nom = $this->memo;
		$this->pathMemo[$nom] = $this->getCurrentPath();
		return $this;
	}

	/**
	 * Récupère la liste des paths sauvagardés
	 * @return array
	 */
	public function getSavedPaths() {
		return $this->pathMemo;
	}

	/**
	 * Récupère la liste des noms des paths sauvagardés
	 * @return array
	 */
	public function getSavedPathNames() {
		return array_keys($this->pathMemo);
	}

	/**
	 * Supprime les paths sauvegardés (tous, ou celui nommé / ceux nommés)
	 * si $nom = true, supprime tous les paths
	 * @param mixed $nom
	 * @return aetools
	 */
	public function reinitSavePath($nom = null) {
		if(!is_string($nom) && $nom !== true) $nom = $this->memo;
		if($nom !== true) {
			if(is_string($nom)) $nom = array($nom);
			foreach($nom as $n) if(isset($this->pathMemo[$n])) {
				$this->pathMemo[$n] = null;
				unset($this->pathMemo[$n]);
			}
		} else {
			$this->pathMemo = array();
		}
		return $this;
	}

	/**
	 * Revient au chemin sauvegardé, avec un nom
	 * @param $nom
	 * @return string
	 */
	public function restoreSavedPath($nom = null) {
		if(!is_string($nom)) $nom = $this->memo;
		if(isset($this->pathMemo[$nom])) {
			$rootPath = $this->pathMemo[$nom];
			if(file_exists($rootPath)) {
				$this->close();
				$this->rootPath = $rootPath;
				$this->currentPath = $rootPath;
				$this->recursiveTree = array(dir($this->rootPath));
				$this->rewind();
				return $this->rootPath;
			}
		}
		return false;
	}

	///// Xxxxxxxxxx

	///// Xxxxxxxxxx

	///// Xxxxxxxxxx

	///// Xxxxxxxxxx

	///// Xxxxxxxxxx

	///// Xxxxxxxxxx


	////////////////////////////////////////////////////////////////////////////////////////////////////////
	// STRUCTURE DES DOSSIERS
	////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Renvoie la liste des dossiers de src (donc la liste des groupes)
	 * @return array
	 */
	public function getSrcGroupes() {
		return $this->getDirs("/src/");
	}

	public function getDirs($path = null) {
		$this->savePath();
		if($path !== null) $this->setRootPath($path);
		// lecture du contenu du dossier
		while($file = @readdir()) {
			if(is_dir($file) && !preg_match("#".$this->nofiles."#", $file));
		}
		$this->restoreSavedPath();
	}


	////////////////////////////////////////////////////////////////////////////////////////////////////////
	// SERVICE EVENTS
	////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Initialise le service - attention : cette méthode est appelée en requête principale par EventListener !!!
	 * @param FilterControllerEvent $event
	 * @param boolean $reLoad
	 */
	public function serviceEventInit(FilterControllerEvent $event, $reLoad = false) {
		$this->service = array();
		// paramètres URL et route
		$this->service['actuelpath'] 		= $this->getURL();
		$this->service['baseURL'] 			= $this->getBaseUrl();
		$this->service['URL'] 				= $this->getURLentier();
		$this->service['route'] 			= $this->getRoute();
		$this->service['parameters'] 		= $this->getParameters();
		$this->service['controller'] 		= $this->getController();
		$this->service['actionName'] 		= $this->getActionName();
		$this->service['groupeName'] 		= $this->getGroupeName();
		$this->service['bundleName'] 		= $this->getBundleName();
		$this->service['controllerName'] 	= $this->getControllerName();
		$this->service['environnement'] 	= $this->getEnv();
		$this->service['clientIP'] 			= $this->getIP();
		$this->siteListener_InSession();
	}

	/**
	* Dépose les informations de l'entité dans la session
	* @return aetools
	*/
	public function siteListener_InSession() {
		$this->serviceSess->set($this->getShortName(), $this->service);
		return $this;
	}

	/**
	* Renvoie true si les informations de l'entité sont bien dans la session
	* @return boolean
	*/
	public function isSiteListener_InSession() {
		return $this->serviceSess->get($this->getShortName()) !== null ? true : false;
	}


	////////////////////////////////////////////////////////////////////////////////////////////////////////
	// ROUTES & URL
	////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Renvoie l'url de base
	 * @return string
	 */
	public function getBaseUrl() {
		return $this->isControllerPresent() ? $this->serviceRequ->getBaseUrl() : null;
	}

	/**
	 * Renvoie le path (string)
	 * @return string
	 */
	public function getURL() {
		return $this->isControllerPresent() ? $this->serviceRequ->getPathInfo() : null;
	}

	/**
	 * Renvoie l'URL entier
	 * @return string
	 */
	public function getURLentier() {
		return $this->isControllerPresent() ? $this->serviceRequ->getUri() : null;
	}

	/**
	 * Renvoie un array des routes contenant le préfixe $prefix
	 * @param $prefix
	 * @return array
	 */
	public function getAllRoutes($prefix = null) {
		if(is_string($prefix)) $pattern = '/^'.$prefix.'/'; // commence par $prefix
			else $pattern = '/.*/';
		$this->allRoutes = array();
		foreach($this->router->getRouteCollection()->all() as $nom => $route) {
			if(preg_match($pattern, $nom)) $this->allRoutes[] = $nom;
		}
		return $this->allRoutes;
	}

	/**
	 * Renvoie la route actuelle
	 * @return string
	 */
	public function getRoute() {
		return $this->route;
	}

	/**
	 * Renvoie un array des paramètres de route
	 * @return array
	 */
	public function getParameters() {
		if($this->isControllerPresent()) {
			$r = array();
			$params = explode($this->aslash, $this->getURL());
			foreach($params as $nom => $pr) if(strlen($pr) > 0) $r[$nom] = $pr;
			// return $this->requAttributes->all();
			// if(count($r) == 0) $r = null;
			return $r;
		} else return null;
	}


	////////////////////////////////////////////////////////////////////////////////////////////////////////
	// BUNDLES
	////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Renvoie la liste des bundles disponibles
	 * @return array
	 */
	public function getBundlesList() {
		return $this->isContainerPresent() ? $this->container->getParameter('kernel.bundles') : false;
	}

	/**
	 * Renvoie le nom du bundle courant
	 * @return string
	 */
	public function getBundleName() {
		return $this->isControllerPresent() ? $this->bundleName : null;
	}

	/**
	 * Affiche la liste des bundles
	 */
	protected function afficheBundles() {
		$this->writeTableConsole('Liste des Bundles présents détectés par Symfony2', $this->getBundlesList());
	}


	////////////////////////////////////////////////////////////////////////////////////////////////////////
	// CONTROLLER
	////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Renvoie le controller complet
	 * @return string
	 */
	public function getController() {
		return $this->isControllerPresent() ? $this->controllerPath : null;
	}

	/**
	 * Renvoie le dossier du controller
	 * @return string
	 */
	public function getCtrlFolder() {
		return $this->isControllerPresent() ? $this->ctrlFolder : null;
	}

	/**
	 * Renvoie le nom du controller
	 * @return string
	 */
	public function getControllerName() {
		return $this->isControllerPresent() ? $this->controllerName : null;
	}

	/**
	 * Renvoie le nom du groupeName
	 * @return string
	 */
	public function getGroupeName() {
		return $this->isControllerPresent() ? $this->groupeName : null;
	}

	/**
	 * Renvoie le nom de la méthode appelée dans le controller
	 * @return string
	 */
	public function getActionName() {
		return $this->isControllerPresent() ? $this->actionName : null;
	}

	/**
	 * Renvoie le nom de la méthode, sans "Action" appelée dans le controller
	 * @return string
	 */
	public function getSingleActionName() {
		return $this->isControllerPresent() ? $this->singleActionName : null;
	}


	////////////////////////////////////////////////////////////////////////////////////////////////////////
	// IP
	////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Renvoie l'adresse IP utilisateur
	 * @return string
	 */
	public function getIP() {
		return $this->serviceRequ->getClientIp();
	}


	////////////////////////////////////////////////////////////////////////////////////////////////////////
	// ENVIRONNEMENT
	////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Renvoie mode d'environnement (dev, test, prod…)
	 * @return string
	 */
	public function getEnv() {
		return $this->isControllerPresent() ? $this->container->get('kernel')->getEnvironment() : null;
	}


	////////////////////////////////////////////////////////////////////////////////////////////////////////
	// USER
	////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Charge l'utilisateur
	 * @return string
	 */
	public function loadCurrentUser() {
		if($this->isControllerPresent()) {
			$roles = array(
				"ROLE_USER" 		=> "utilisateur",
				"ROLE_EDITOR" 		=> "éditeur",
				"ROLE_ADMIN" 		=> "Administrateur",
				"ROLE_SUPER_ADMIN" 	=> "super adminstrateur"
				);
			if($this->container->get('security.context')->isGranted('ROLE_USER')) {
				$this->user = $this->container->get('security.context')->getToken()->getUser();
			}
		} else {
			$this->user = false;
		}
		return $this->user;
	}


	////////////////////////////////////////////////////////////////////////////////////////////////////////
	// SERIALIZATION
	////////////////////////////////////////////////////////////////////////////////////////////////////////

	public function aeSerialize($data) {
		if(is_array($data)) foreach($data as $key => $value) {
			if(is_object($value)) {
				$class = explode(self::ASLASH, get_class($value));
				switch (end($class)) {
					case 'ArrayCollection':
						$data[$key] = $value->toArray();
						break;
					case 'DateTime':
						// $data[$key] = $value->format('Y-m-d H:i:s');
						break;
					default:
						// $data[$key] = $value;
						break;
				}
			}
		}
		return $data;
	}


	////////////////////////////////////////////////////////////////////////////////////////////////////////
	// STRUCTURES DE CLASSES
	////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Renvoie un array de la hiérarchie de la classe
	 * @param mixed $className - nom de la classe (AVEC namespace !!) ou objet
	 * @param string $format - 'string' ou 'array' (défaut)
	 * @return array
	 */
	public function getClassHierarchy($className, $format = 'array') {
		if(!is_string($format)) $format = 'array';
		if(is_object($className)) $className = get_class($className);
		$parents = array();
		$treeB = $this->getClassTree($className);
		do {
			$treeB = reset($treeB);
			$parents[] = $treeB['shortName'];
			$treeB = $treeB['parent'];
		} while ($treeB !== false);
		unset($treeB);
		return strtolower($format) === 'string' ? implode(self::SLASH, $parents) : $parents;
	}

	public function getClassTree($className) {
		if(is_object($className)) $className = get_class($className);
		$tree = array();
		$ReflectionClass = new ReflectionClass($className);
		// $meth = get_class_methods($className);
		// foreach ($meth as $key => $method) {
		// 	$methods[$method] = $this->getInfoMethod($ReflectionClass, $method);
		// }
		$tree[$className]['shortName'] = $this->getClassShortName($className);
		$tree[$className]['longName'] = $className;
		$tree[$className]['abstract'] = $ReflectionClass->isAbstract();
		// $tree[$className]['docComment'] = trim(str_replace(array("/**", "*/", "\n", "\r"), "", $ReflectionClass->getDocComment()));
		// $tree[$className]['methods'] = $methods;
		// parents
		$tree[$className]['parent'] = false;
		$parentClassName = get_parent_class($className);
		if($parentClassName !== false) $tree[$className]['parent'] = $this->getClassTree($parentClassName);
		return $tree;
	}

	// protected function getInfoMethod($ReflectionClass, $method) {
	// 	if($ReflectionClass->getMethod($method)->isPrivate()) 	$scope = 'private';
	// 	if($ReflectionClass->getMethod($method)->isProtected()) $scope = 'protected';
	// 	if($ReflectionClass->getMethod($method)->isPublic()) 	$scope = 'public';
	// 	$annotations = array();
	// 	$docDocument = $ReflectionClass->getMethod($method)->getDocComment();
	// 	// preg_match_all('#( )?@(.*?)\n#s', $docDocument, $annotations);
	// 	// returns
	// 	preg_match_all('#\ ?@return\ (.*?)\n#s', $docDocument, $result);
	// 	$annotations['return'] = $result[1];
	// 	// params
	// 	preg_match_all('#\ ?@param\ (.*?)\n#s', $docDocument, $result);
	// 	$annotations['param'] = $result[1];
	// 	// fulltext
	// 	// preg_match_all('#\*[^\*\\]\ ?((@param\ )|(@return\ )|(.*?))[\n\r]#s', $docDocument, $result);
	// 	$annotations['fulltext'] = trim(str_replace(array("/**", "*/", "\n", "\r"), "", $docDocument));
	// 	return array(
	// 		'scope'			=> $scope,
	// 		'static'		=> $ReflectionClass->getMethod($method)->isStatic(),
	// 		'abstract'		=> $ReflectionClass->getMethod($method)->isAbstract(),
	// 		'docComment'	=> $annotations
	// 	);
	// }


	////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Fonctionnalités diverses
	////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Renvoie le nom de la méthode en fonction de l'attribut et du préfix
	 * @param string $attribute
	 * @param string $prefix
	 * @return string
	 */
	public function getMethodNameWith($attribute, $prefix = "set") {
		return $prefix.ucfirst($attribute);
	}


	////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Fonctionnalités pour fixtures
	////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * getNameFixturesFileXML
	 * Renvoie le nom de fichier standard pour les données fixtures en XML
	 * @return string
	 */
	public function getNameFixturesFileXML($EntityClassName) {
		return "fixtures_".$this->getClassShortName($EntityClassName)."s.xml";
	}

	/**
	 * getNameFixturesFileCSV
	 * Renvoie le nom de fichier standard pour les données fixtures en CSV
	 * @return string
	 */
	public function getNameFixturesFileCSV($EntityClassName) {
		return "fixtures_".$this->getClassShortName($EntityClassName)."s.csv";
	}

	/**
	 * getDossierTextFiles
	 * Renvoie le nom du dossier contenant les fichiers texte
	 * @return string
	 */
	public function getDossierTextFiles() {
		return "txt";
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////
	// AFFICHAGES HORS CONTROLLER (pour Terminal ou Fixtures)
	////////////////////////////////////////////////////////////////////////////////////////////////////////

	protected function writeConsole($t, $color = "normal", $rt = true) {
		if($this->isControllerAbsent()) {
			if(is_string($t)) printf($this->returnConsole($t, $color, $rt));
			if(is_array($t)) var_dump($t);
		}
	}

	protected function echoMemoryHorsController($texte) {
		$this->writeConsole('Mémoire PHP : '.memory_get_usage().' '.$texte);
	}

	protected function writeTableConsole($titre, $table) {
		$this->afficheTitre($titre);
		if(is_array($table)) {
			foreach($table as $nom => $value) {
				$this->afficheLine($nom, $value);
			}
		} else throw new Exception('Élément fourni n\'est pas un array : '.gettype($table));
		$this->echoRT();
	}

	protected function afficheTitre($texte) {
		$this->writeConsole($this->texttools->fillOfChars($texte, 81), "table_titre", true);
	}

	protected function afficheLine($name, $value) {
		$this->writeConsole($this->texttools->fillOfChars($name, 50)." | ".$this->texttools->fillOfChars($value, 25), "table_line", true);
	}

	protected function returnConsole($t, $color = "normal", $rt = true) {
		switch ($color) {
			case 'error':
				return "\033[1;7;31m".$t."\033[00m".$this->getXRT($rt);
				break;
			case 'succes':
				return "\033[1;42;30m".$t."\033[00m".$this->getXRT($rt);
				break;
			case 'headline':
				return "\033[1;46;34m".$t."\033[00m".$this->getXRT($rt);
				break;
			case 'table_titre':
				return "\033[1;44;36m".$t."\033[00m".$this->getXRT($rt);
				break;
			case 'table_line':
				return "\033[1;40;37m".$t."\033[00m".$this->getXRT($rt);
				break;
			default:
				return "\033[00m".$t.$this->getXRT($rt);
				break;
		}		
	}

	protected function getXRT($n = 1) {
		$rt = "";
		if($n !== false) {
			if($n === true) $n = 1;
			for ($i=0; $i < $n; $i++) { 
				$rt .= self::EOLine;
			}
		}
		return $rt;
	}

	protected function echoRT($n = 1) {
		if($this->isControllerAbsent()) printf($this->getXRT($n));
	}



}


// Grâce à PHP, il est possible d'afficher le contenu d'un répertoire et de ses sous-répertoires. Voici ci-dessous une fonction permettant de parcourir récursivement les répertoires et sous-répertoires et d'en afficher les fichiers :

// function ScanDirectory($Directory){

//   $MyDirectory = opendir($Directory) or die('Erreur');
//  while($Entry = @readdir($MyDirectory)) {
//   if(is_dir($Directory.'/'.$Entry)&& $Entry != '.' && $Entry != '..') {
//                          echo '<ul>'.$Directory;
//    ScanDirectory($Directory.'/'.$Entry);
//                         echo '</ul>';
//   }
//   else {
//    echo '<li>'.$Entry.'</li>';
//                 }
//  }
//   closedir($MyDirectory);
// }

// ScanDirectory('.');

