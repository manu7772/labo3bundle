<?php
// laboBundle/DataFixtures/ORM/LoadingFixtures.php

namespace laboBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
// aetools
use laboBundle\services\entitiesServices\entitesService;
// use laboBundle\services\aetools\aetools;

use \DateTime;

/*
 * Les fixtures sont des objets qui doivent obligatoireemnt implémenter l'interface FixtureInterface
 */
class LoadingFixtures extends entitesService implements FixtureInterface, ContainerAwareInterface {

	const TEST_FIXTURES_VERSION = true;

	protected $manager;
	protected $connection;
	protected $parsList;
	protected $entityName;
	protected $entityObj;
	// protected $EntityService;
	protected $container;
	protected $testFormats;
	protected $texttools;
	protected $data;
	protected $entitiesList;
	protected $aetools;
	protected $imagetools;
	protected $baseFolder;
	// mémo pour relinks
	protected $relinks;

	public function __construct(ContainerInterface $container = null) {
		$this->writeConsole("Chargement du Constructeur FIXTURES…", "normal", true);
		// parent::__construct($container);
	}

	/**
	 * {@inheritDoc}
	 */
	public function setContainer(ContainerInterface $container = null) {
		$this->writeConsole("Chargement du Container FIXTURES…", "normal", true);
		$this->container = $container;
		parent::__construct($this->container);
		// $this = $this->container->get('labobundle.entities');
		$this->initFixData();
	}

	protected function initFixData() {
		$this->testFormats = array(
			"DateTime" => "DATE_",
		);
		$this->relinks = array();
		$this->getEm();
		$this->initAllData();
		// $this->baseFolder = __DIR__."/../../../../../../";
		// service images
	}

	protected function test($entity) {
		// $field = 'cuissons';
		// echo $entity;
		$fields = $this->getAssociationNamesOfEntity($entity);
		$CMD = $this->getClassMetadata($entity);
		foreach ($fields as $key => $field) {
			$this->writeConsole('Field : '.$field);
			$this->writeConsole('- getAssociationMappedByTargetField : '.$this->affTrueOrFalseOrElse($CMD->getAssociationMappedByTargetField($field)), 'error');
			$this->writeConsole('- isAssociationInverseSide : '.$this->affTrueOrFalseOrElse($CMD->isAssociationInverseSide($field)), 'error');
			$this->writeConsole('- isBidirectional : '.$this->affTrueOrFalseOrElse($this->isBidirectional($field, $entity)), 'error');
		}
		// var_dump($CMD->getAssociationMapping($field));
		// var_dump($CMD->getNamedQueries());
		die();
	}

	protected function affTrueOrFalseOrElse($var) {
		if($var === true) return 'true';
		if($var === false) return 'false';
		if($var === null) return 'NULL';
		if(is_array($var)) return 'Array('.count($var).')';
		if(is_object($var)) return 'Object('.count($var).')';
		return $var;
	}

	public function load(ObjectManager $manager) {
		$this->writeConsole('Fixtures loading…');
		$this->manager = $manager;
		$this->connection = $this->manager->getConnection();
		// $this->getEm();
		// servicve entités
		// $this->EntityService = $this->container->get("labobundle.entities");
		$this->imagetools = $this->container->get('labobundle.imagetools');
		$this->writeConsole('Tri ordre des entités… ');
		$this->trieEntities();
		$this->afficheEntities();
		$this->afficheEntitiesFound();
		// services dossiers/fichiers
		$this->afficheBundles();

		//efface le dossier images
		$this->imagetools->deleteAllImageFolders();
		// recrée les dossiers images vierges
		$this->imagetools->checkDossiersImages();

		$styleTitre = "succes";
		$this->writeConsole("******************************************************************", $styleTitre);
		$this->writeConsole("*****                 LANCEMENT DES FIXTURES                 *****", $styleTitre);
		$this->writeConsole("******************************************************************", $styleTitre);
		$this->writeConsole("* Informations :                                                 *", $styleTitre);
		$this->writeConsole("*                                                                *", $styleTitre);
		$this->writeConsole('* Champ substitutif pour version slug : '.$this->champSubstitutifForVersion.' ', $styleTitre);
		$this->writeConsole("*                                                                *", $styleTitre);
		$this->writeConsole("******************************************************************", $styleTitre);

		// $this->test('AcmeGroup\\LaboBundle\\Entity\\article');

		foreach($this->entitiesList as $namespace => $name) {
			$this->writeConsole("Fixtures hydratation de ".$name, "headline");
			if($this->loadEntity($name) !== false) {
				$this->writeConsole("Fin de l'entité ".$name, "succes", 2);
			} else $this->writeConsole("Aucune ligne enregistrée.", "error", 2);
		}
		$this->writeConsole('Enregistrement de toutes les entités terminé', 'succes', 2);
		$this->relinkEntities();
	}

	/**
	 * Trie les entités dans le bon ordre
	 */
	protected function trieEntities() {
		$ordre = array(
			$this->getVersionEntityClassName()
			// "AcmeGroup\\LaboBundle\\Entity\\statut",
			// "AcmeGroup\\LaboBundle\\Entity\\version",

			// "AcmeGroup\\LaboBundle\\Entity\\tag",
			// "AcmeGroup\\LaboBundle\\Entity\\unite",
			// "AcmeGroup\\LaboBundle\\Entity\\typeEmail",
			// "AcmeGroup\\LaboBundle\\Entity\\typeImage",
			// "AcmeGroup\\LaboBundle\\Entity\\typeReseau",
			// "AcmeGroup\\LaboBundle\\Entity\\typeAdresse",
			// "AcmeGroup\\LaboBundle\\Entity\\typeTelephone",
			// "AcmeGroup\\LaboBundle\\Entity\\typeNatureTelephone",
			// "AcmeGroup\\LaboBundle\\Entity\\typeFiche",
			// "AcmeGroup\\LaboBundle\\Entity\\typeVideo",
			// "AcmeGroup\\LaboBundle\\Entity\\typeCollection",

			// "AcmeGroup\\LaboBundle\\Entity\\telephone",
			// "AcmeGroup\\LaboBundle\\Entity\\email",
			// "AcmeGroup\\LaboBundle\\Entity\\adresse",
			// "AcmeGroup\\LaboBundle\\Entity\\panier",
			// "AcmeGroup\\LaboBundle\\Entity\\tva",
			// "AcmeGroup\\LaboBundle\\Entity\\video",
			// "AcmeGroup\\LaboBundle\\Entity\\image",
			// "AcmeGroup\\LaboBundle\\Entity\\collection",
			// "AcmeGroup\\LaboBundle\\Entity\\reseausocial",
			// "AcmeGroup\\LaboBundle\\Entity\\pageweb",
			// "AcmeGroup\\LaboBundle\\Entity\\fichierPdf",
			// "AcmeGroup\\LaboBundle\\Entity\\fiche",
			// "AcmeGroup\\LaboBundle\\Entity\\pageweb",
			// "AcmeGroup\\LaboBundle\\Entity\\cuisson",
			// "AcmeGroup\\LaboBundle\\Entity\\evenement",
			// "AcmeGroup\\LaboBundle\\Entity\\categorie",
			// "AcmeGroup\\LaboBundle\\Entity\\valeur",
			// "AcmeGroup\\LaboBundle\\Entity\\article",
		);
		ksort($ordre);
		$this->entitiesList = array();
		$entitiesList = array();
		// entités ordonnées
		foreach($ordre as $num => $namespace) {
			$name = $this->getEntityShortName($namespace);
			if($name !== false) {
				$entitiesList[$namespace] = $name;
			} else {
				$this->writeConsole('Entité ordonnée non trouvée.', 'error');
			}
		}
		// ne garde que les entités réelles (non abstraites / non interfaces)
		$this->setOnlyConcrete(true);
		// reste des entités
		foreach($this->getListOfEnties(false) as $namespace => $name) {
			if(!array_key_exists($namespace, $entitiesList)) $entitiesList[$namespace] = $name;
		}
		// $this->entitiesList = $this->getListOfEnties(false);
		$this->entitiesList = $entitiesList;
		unset($entitiesList);
	}

	/**
	 * Opération finale : Checke toutes les entités pour les relier entre elles
	 * @return boolean
	 */
	protected function relinkEntities($testVersions = true) {
		// entités en passe 1
		$passe1 = array('version');

		// var_dump($this->relinks);
		// die();

		$this->writeConsole("*****************************************", 'succes');
		$this->writeConsole("***** PASSE 1 : RELINK DES VERSIONS *****", 'succes');
		$this->writeConsole("*****************************************", 'succes');
		foreach ($this->relinks as $entity => $one) if($entity === $this->getVersionEntityClassName()) {
			$this->defineEntity($entity);
			foreach($one as $id => $champs) {
				$entiteAtraiter = $this->find($id);
				$this->writeConsole('Entité : '.$entity.' / Id : '.$entiteAtraiter->getId(), 'headline');
				foreach ($champs as $field => $todo) {
					if(in_array($field, $passe1)) {
						$this->writeConsole(self::TAB1.$field.' : combinaisons de versions', 'normal');
						$this->fillAssociatedField($field, $entiteAtraiter, $todo, false);
					}
				}
				$this->writeConsole(self::TAB2.'Flush > ', 'normal', false);
				$this->writeConsole("•", 'headline', false);
				$this->manager->persist($entiteAtraiter);
				$this->writeConsole("•", 'headline', false);
				$this->manager->flush();
				$this->writeConsole("•", 'headline');
			}
		}

		$this->writeConsole("*****************************************", 'succes');
		$this->writeConsole("***** PASSE 2 : RELINK DES ENTITES  *****", 'succes');
		$this->writeConsole("*****************************************", 'succes');
		foreach ($this->relinks as $entity => $one) if($entity !== $this->getVersionEntityClassName()) {
			$this->defineEntity($entity);
			foreach($one as $id => $champs) {
				$entiteAtraiter = $this->find($id);
				$this->writeConsole('Entité : '.$entity.' / Id : '.$entiteAtraiter->getId(), 'headline');
				foreach ($champs as $field => $todo) {
					if(!in_array($field, $passe1)) {
						$this->writeConsole(self::TAB1.$field.' : ajout des autres valeurs', 'normal');
						$this->fillAssociatedField($field, $entiteAtraiter, $todo, self::TEST_FIXTURES_VERSION);
					}
				}
				$this->writeConsole(self::TAB2.'Flush > ', 'normal', false);
				$this->writeConsole("•", 'headline', false);
				$this->manager->persist($entiteAtraiter);
				$this->writeConsole("•", 'headline', false);
				$this->manager->flush();
				$this->writeConsole("•", 'headline');
			}
		}
	}

	private function addAff($obj) {
		if(method_exists($obj, 'getCible')) $nom = $obj->getCible();
			else if(method_exists($obj, 'getSlug')) $nom = $obj->getSlug();
			else $nom = 'id:'.$obj->getId();
		return $nom;
	}

	protected function loadEntity($name) {
		$this->defineEntity($name);
		// var_dump($this->getAssociationNamesOfEntity($this->getEntityClassName()));
		$this->writeConsole('Définition nouvelle entité : '.$name);
		// si l'entité existe…
		// if(in_array($this->getEntityClassName(), $this->entitiesList)) {
			// !!!!! attention, NORMALEMENT, mettre le nom de la table (column) et non du champ Doctrine (field) !!!!!
			$this->connection->exec("ALTER TABLE ".$name." AUTO_INCREMENT = 1;");
			// $this->parsList = null;
			// chargement
			$this->loadXML();
			return true;
		// } else return false;
	}

	protected function loadXML() {
		// !!!!! faire ici une recherche dans les dossiers des fichiers XML
		// $this->writeConsole("File : ".$XMLfile.self::EOLine);
		// $files = $this->deleteFilesEverywhere($file);
		// $this->writeConsole($files);
		// die("ok\n\n");
		$fileCSV = $this->getNameFixturesFileCSV($this->getEntityShortName());
		$CSVfilepath = $this->gotoroot."src/AcmeGroup/SiteBundle/Resources/public/csv/".$fileCSV;
		$fileXML = $this->getNameFixturesFileXML($this->getEntityShortName());
		$XMLfilepath = $this->gotoroot."src/AcmeGroup/SiteBundle/Resources/public/xml/".$fileXML;
		if(file_exists($CSVfilepath)) {
			$this->writeConsole("CSV trouvé : ".$fileCSV, 'error');
			$CSVreader = $this->container->get('labobundle.CSVreader');
			if($CSVreader->createXMLfileFromCSV($CSVfilepath, $XMLfilepath)) $this->writeConsole("Fichier XML créé : ".$fileXML, 'succes');
		} else {
			// $this->writeConsole("CSV non trouvé : ".$CSVfilepath);
			// $r = false;
		}
		// XML
		if(file_exists($XMLfilepath)) {
			$this->writeConsole("XML trouvé : ".$fileXML);
			$r = $this->parseX(@simplexml_load_file($XMLfilepath));
		} else {
			$this->writeConsole("XML non trouvé : ".$XMLfilepath);
			$r = false;
		}
		return $r;
	}

	### Parse des données XML (total)
	protected function parseX($XMLfile) {
		$tb = array();
		if($XMLfile != null) {
			$this->writeConsole('-> '.count($XMLfile).' ligne'.$this->texttools->plur($XMLfile, 's').' à générer');
			$tb = array();
			foreach($XMLfile as $ojbc) {
				$att = $ojbc->attributes();
				$nom = $att["cible"];
				if($nom === null) $nom = $att["nom"];
				if($nom === null) $nom = $att["username"];
				$this->writeConsole("--------------------------------------");
				$this->writeConsole("Nom : ".$nom);
				$this->parss2($ojbc, $this->createEntry($att, null));
			}
			return $r = $tb;
		} else $r = null;
		return $r;
	}

	protected function parss2($XMLfile, $cpt_parent) {
		if($XMLfile->count() > 0) foreach($XMLfile->children() as $nom1 => $entityName1) {
			$att = $entityName1->attributes();
			$this->parss2($entityName1, $this->createEntry($att, $cpt_parent));
		}
	}

	protected function createEntry($attributs, $cpt_parent = null) {
		$memoLinks = array();
		$this->writeConsole("Begin --> ", "normal", false);
		// création de l'objet entité prérempli (liens externes par défaut) / pas de version pour l'instant
		$this->parsList = $this->newObject(null, false, false);
		$this->writeConsole('Hiérarchie : '.$this->getClassHierarchy($this->parsList, 'string'), 'error');
		if(is_object($this->parsList)) {
			$this->writeConsole("Entité ".$this->getEntityShortName()." : générée");
		} else {
			$this->writeConsole("Entité ".$this->getEntityShortName()." : NON générée", 'error');
			return false;
		}

		foreach($attributs as $nom => $entityString) {
			// initialise $this->data
			$this->data = array();

			$this->initData($nom, $entityString);
			// $this->writeTableConsole("Data :", $this->data, 15, 100);
			$field = $this->data["champSlf"];
			$typeAssoc = $this->getTypeOfAssociation($field, $this->parsList);
			$this->writeConsole("> ".$nom." = ".$entityString." (type ".$typeAssoc.")");
			// !$typeAssoc ? $this->writeConsole(self::TAB2.$field." > aucune association", 'error', false) : $this->writeConsole(self::TAB2.$field." > association ".$typeAssoc, 'error', false);

			switch($typeAssoc) {
				case self::COLLECTION_ASSOC_NAME:
				case self::SINGLE_ASSOC_NAME:
					// mémorisation des données à ajouter en RELINK
					// $nomCsCe = $this->data["champSlf"].'###'.$this->data["champExt"];
					if(!is_array($this->data["entityList"])) $this->data["entityList"] = array();
					$memoLinks[$this->data["champSlf"]][$this->data["champExt"]] = $this->data["entityList"];
					if($this->data["suppStd"] === false) $memoLinks[$this->data["champSlf"]][self::VALUE_DEFAULT] = true;
					break;
				default:
					// aucune + autres
					foreach($this->data["entityList"] as $val) {
						// ajout de liens URL dynamiques liées (dans les textes)
						switch($this->data["format"]) {
							case "DateTime":
								$val = new DateTime($val);
								break;
							default:
								// standard + autres
								$val = $this->dynUrls($val);
								break;
						}
						$set = $this->getMethodOfSetting($field, $this->parsList);
						$this->parsList->$set($val);
					}
					break;
			}
		}

		// ajout du parent (concerne les entités Tree uniquement)
		if($cpt_parent !== null) {
			if (method_exists($this->parsList, "setParent")) $this->parsList->setParent($cpt_parent);
			if (method_exists($this->parsList, "addParent")) $this->parsList->addParent($cpt_parent);
		}
		// Persist & flush
		// $this->writeConsole("Mémoire PHP : ".memory_get_usage()." --> ");
		$this->manager->persist($this->parsList);
		$this->manager->flush();
		// $this->writeConsole(memory_get_usage().self::EOLine);
		$this->writeConsole("* Entité ".$this->getEntityShortName()." enregistrée en BDD => id : ".$this->parsList->getId(), "succes", 2);

		// toutes les associations :
		// $allLinks = $this->getAssociationNamesOfEntity($this->getEntityClassName());
		// foreach ($allLinks as $field) {
		// 	if(!isset($memoLinks[$field])) $memoLinks[$field] = self::VALUE_DEFAULT;
		// }

		//                                           | -> envoyée à fillAssociatedField -> $what
		// $this->relinks|entity    |id |field       |todo
		// $this->relinks[className][id][champEntity][champTarget] => array de valeurs
		// $this->relinks[className][id][champEntity][] => 'defaults'

		$this->relinks[$this->getEntityClassName()][$this->parsList->getId()] = $memoLinks;
		// $this->writeConsole("* Entité enregistrée en BDD *\n\n");
		return $this->parsList; // renvoie l'objet enregistré
	}


	/**
	 * Importation de fichiers textes externes
	 * @param array $files -> liste des fichiers
	 * @return array
	 */
	protected function importFiles($files) {
		if(is_string($files)) {
			$f = $files;
			$files = array();
			$files[] = $f;
		}
		$contenu = array();
		$dossier = "txt";
		foreach($files as $file) {
			// si le dossier n'est pas précisé, on reprend le dossier du fichier précédent (et ainsi de suite)
			if(count($file) < 2) {
				// si aucun dossier n'est précisé dès le premier fichier, on utilise le dossier "txt" par défaut
				$file[1] = $file[0];
				$file[0] = $dossier;
			} else $dossier = $file[0];
			$importFile = $this->gotoroot."src/AcmeGroup/SiteBundle/Resources/public/".$file[0]."/".$file[1];
			$this->writeConsole("Import : ".$importFile.self::EOLine);
			if(file_exists($importFile)) {
				$txt = @file_get_contents($importFile);
				if($txt !== false) {
					$txt = nl2br($txt);
					$contenu[] = str_replace("><br />", ">", $txt);
					$this->writeConsole(" --> Fichier chargé avec succès ( ".substr($txt, 0, 20)."… )".self::EOLine);
				} else $this->writeConsole(" --> ".$this->writeConsole("ECHEC", "error", false)." (lecture du fichier échouée)".self::EOLine);
			} else $this->writeConsole(" --> ".$this->writeConsole("ECHEC", "error", false)." (fichier non trouvé)".self::EOLine);
		}
		return $contenu;
	}

	protected function dynUrls($texte) {
		// {# IMG:nom:isaac #}
		$texte = preg_replace_callback(
			'|{# (IMG):(\w+):(\w+) #}|', 
			function($matches) {
				if((count($matches) > 3) || ($matches[1] == 'IMG')) {
					$meth = $this->getMethodNameWith($matches[2], 'findBy');
					// $repo = $this->manager->getRepository("AcmeGroup\\LaboBundle\\Entity\\image");
					$repo = $this->manager->getRepository("AcmeGroupLaboBundle:image");
					$image = $repo->$meth($matches[3]);
					if(count($image) > 0) {
						$image = reset($image);
						return "{{ asset('".$this->imagetools->getNomDossierImages().$this->imagetools->getNomDossierOriginal().$image->getFichierNom()."') }}";
					}
				}
			},
			$texte, -1, $nb // $nb remplie par preg_replace_callback avec le nombre de remplacements effectués
		);
		return $texte;
	}


	/**
	 * initData
	 * Définit le format : spécial (précisé) ou standard
	 * @param $nom (du champ)
	 * @param $entityString (chaîne de paramètres)
	 */
	protected function initData($nom, $entityString) {
		foreach($this->testFormats as $nomformat => $prefix) {
			if(preg_match("'^(".$prefix.")'", $nom)) {
				$this->data["format"] = $nomformat;
				$this->data["nom"] = str_replace($prefix, "", $nom);
			} else {
				$this->data["format"] = "standard";
				$this->data["nom"] = $nom;
			}
		}
		$this->writeConsole(self::TAB1."Format : ".$this->data["format"]);
		$this->verifSuppStdLiens($entityString);
		$this->data["suppStd"] === false ? $donnees = "ajoutées" : $donnees = "supprimées";
		$this->writeConsole(self::TAB1."Données STD : ".$donnees);
		$this->compileNom();
	}

	/**
	 * verifSuppStdLiens
	 * Verifie si les liens remplacent ou sont ajoutés au champ existant
	 * @param $entityString (chaîne de paramètres)
	 */
	protected function verifSuppStdLiens($entityString) {
			// if(substr($entityString, 0, 1) == "+") {
			if(preg_match("#^\+#", $entityString)) {
				// + : ajoute les valeurs aux valeurs par défaut
				$this->data["suppStd"] = false;
				$this->data["vals"] = substr($entityString, 1); // on enlève le + il ne sert plus à rien
				// $this->suppEntitesLiees($this->data["nom"]);
			} else if($entityString === '-') {
				$this->data["suppStd"] = true;
				$this->data["vals"] = "";
			} else {
				$this->data["suppStd"] = true;
				$this->data["vals"] = $entityString;
			}
	}

	/**
	 * compileNom
	 * Extrait les paramètres du nom de l'attribut
	 */
	protected function compileNom() {
		$this->data["entityList"] = explode("|", $this->data["vals"]);
		if($this->data["vals"] === "") $this->data["entityList"] = array();
		$nom = explode("__", $this->data["nom"]); // $this->data["champSlf"] = version  ==> méthode
		if(count($nom) > 1) {
			// si c'est une entité liée externe
			$o = explode("_", $nom[0]);
			if(count($o) > 1) { // si le nom du champs != nom de l'entité liée
				$nom[0] = $o[0];
			} else $o[1] = $nom[0];
		} else {
			// valeur(s) simple(s) : on attribue les variables… quand même !
			$o = array();
			$nom[1] = $nom[0];
			$o[1] = $nom[0];
			// fichiers externes : "import###" + nomDuDossier + "::" + nom du fichier (ex. texte="import###txt::intro.txt")
			//   --> utiliser "importConcat###" pour concaténer les fichiers textes et n'obtenir qu'une seule valeur d'après tous les fichiers
			// noms de fichiers multiples : séparer par "|" (ex. texte="import###txt::intro.txt|intro2.txt|xml::intro3.txt")
			//   --> préciser à chaque fois le dossier / s'il n'est pas précisé, le nom du dossier précédent est repris.
			$ex = explode("@", $this->data["vals"], 2);
			if(count($ex) == 2) {
				$this->data["entityList"] = array();
				$files = array();
				$ey = explode("|", $ex[1]);
				foreach($ey as $num => $param) $files[$num] = explode("::", $param);
				// traitement des données
				switch($ex[0]) {
					case "import":
						$this->data["entityList"] = $this->importFiles($files);
						break;
					case "importConcat":
						$impf = $this->importFiles($files);
						foreach($impf as $el) $this->data["entityList"][] .= $el;
						break;
					default:
						$this->data["entityList"][] = htmlspecialchars_decode($this->data["vals"]); // htmlentities / html_entity_decode / 
						break;
				}
				// au cas où il n'y a pas de résultat…
				if(count($this->data["entityList"]) < 1) $this->data["entityList"][] = "";
			}
			// $this->writeConsole($entityList);
		}
		$this->data["champSlf"] = $nom[0];
		$this->data["champExt"] = $nom[1];
		$this->data["entitExt"] = $o[1];
		// $this->getTypeOfAssociation();
	}



}