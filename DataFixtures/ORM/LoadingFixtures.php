<?php
// labo/Bundle/TestmanuBundle/DataFixtures/ORM/LoadingFixtures.php

namespace labo\Bundle\TestmanuBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

define("BASEFOLDER", __DIR__."/../../../../../../../..");
/*
 * Les fixtures sont des objets qui doivent obligatoireemnt implémenter l'interface FixtureInterface
 */
class LoadingFixtures implements FixtureInterface, ContainerAwareInterface {

	private $manager;
	private $connection;
	private $parsList		= array();
	private $entityName;
	private $entityObj;
	private $EntityService;
	private $container;
	private $testFormats	= array("Datetime" => "DATE_");
	private $texttools;
	private $data;
	private $entitiesService;
	private $listOfEnties;
	private $aetools;
	private $imagetools;

	/**
	 * {@inheritDoc}
	 */
	public function setContainer(ContainerInterface $container = null) {
		$this->container = $container;
	}

	public function load(ObjectManager $manager) {
		$this->manager = $manager;
		$this->connection = $this->manager->getConnection();
		// service text utilities
		$this->texttools = $this->container->get("acmeGroup.textutilities");
		// servicve entités
		$this->entitiesService = $this->container->get("acmeGroup.entities");
		$this->listOfEnties = $this->entitiesService->listOfEnties();
		$this->trieEntities();
		$this->afficheEntities();
		// services dossiers/fichiers
		$this->aetools = $this->container->get('acmeGroup.aetools');
		$this->listOfBundles = $this->aetools->getBundlesList();
		$this->afficheBundles();
		// service images
		$this->imagetools = $this->container->get('acmeGroup.imagetools');

		//efface le dossier images
		$this->deleteAllImageFolders();

		$this->writeConsole("**********************************", "succes", true);
		$this->writeConsole("***** LANCEMENT DES FIXTURES *****", "succes", true);
		$this->writeConsole("**********************************", "succes", 2);

		foreach($this->listOfEnties as $name => $namespace) {
			$this->writeConsole("Fixtures remplissage de ".$namespace, "headline", true);
			$entityL = $this->loadEntity($name);
			if($entityL !== false) {
				$this->writeConsole("Lignes de l'entité enregistrées : ".$name, "succes", 2);
			} else $this->writeConsole("Aucune ligne enregistrée.", "error", 2);
		}
	}

	/**
	 * Trie les entités dans le bon ordre
	 */
	private function trieEntities() {
		$ordre = array(
			0 		=> "statut",
			10		=> "pays",
			20		=> "villesFrance",
			30		=> "adresse",
			40		=> "typeImage",
			50		=> "typeRemise",
			60		=> "typeEvenement",
			70		=> "typeRichtext",
			75		=> "typePartenaire",
			78		=> "typeMembre",
			80		=> "panier",
			90		=> "version",
			100		=> "tauxTVA",
			110		=> "video",
			120		=> "image",
			130		=> "collection",
			140 	=> "richtext",
			150 	=> "reseau",
			160		=> "marque",
			170		=> "pageweb",
			180		=> "categorie",
			190		=> "magasin",
			200		=> "ficheCreative",
			210		=> "partenaire",
			220		=> "evenement",
			230		=> "article",
			240		=> "membre",
			);
		ksort($ordre);
		$newordre = array();
		foreach($ordre as $num => $nom) {
			$newordre[$nom] = $this->listOfEnties[$nom];
		}
		$this->listOfEnties = array();
		$this->listOfEnties = $newordre;
	}

	/**
	 * Opération finale : Checke toutes les entités pour les relier entre elles
	 * @return boolean
	 */
	private function linkEntities() {
		printf("***** RELINK DES ENTITES *****\n");

	}

	private function loadEntity($name) {
		$this->EntityService = $this->container->get('acmeGroup.entities')->defineEntity($name);
		// si l'entité existe…
		if(in_array($this->EntityService->getClassEntite(), $this->listOfEnties)) {
			$this->connection->exec("ALTER TABLE ".$name." AUTO_INCREMENT = 1;");
			$this->parsList = array();
			// chargement
			$this->loadXML();
		} else return false;
	}

	private function loadXML() {
		$XMLfile = BASEFOLDER."/src/AcmeGroup/SiteBundle/Resources/public/xml/".$this->EntityService->getNameFixturesFile();
		if(file_exists($XMLfile)) {
			printf("XML trouvé : ".$this->EntityService->getNameFixturesFile()."\n");
			$r = $this->parseX(@simplexml_load_file($XMLfile));
		} else {
			printf("XML non trouvé : ".$this->EntityService->getNameFixturesFile()."\n");
			$r = null;
		}
		// return $r;
	}

	### Parse des données XML (total)
	private function parseX($XMLfile) {
		$tb = array();
		if($XMLfile != null) {
			$tb = array();
			foreach($XMLfile as $ojbc) {
				$att = $ojbc->attributes();
				$nom = $att["nom"];
				if($nom === null) $nom = $att["nomunique"];
				if($nom === null) $nom = $att["title"];
				printf("--------------------------------------\nNom : ".$nom."\n");
				printf(".");
				$this->parss2($ojbc, $this->createEntry($att, null));
			}
			return $r = $tb;
		} else $r = null;
		return $r;
	}

	private function parss2($XMLfile, $cpt_parent) {
		if($XMLfile->count() > 0) foreach($XMLfile->children() as $nom1 => $entityName1) {
			$att = $entityName1->attributes();
			$this->parss2($entityName1, $this->createEntry($att, $cpt_parent));
		}
	}

	private function createEntry($attributs, $cpt_parent = null) {
		printf("Begin --> ");
		// création de l'objet entité prérempli (liens externes par défaut)
		$this->parsList = $this->EntityService->newObject(true);
		printf("Entité générée\n");

		foreach($attributs as $nom => $entityString) {
			// réinitialise $this->data
			$this->data = array();
			// initData = formats spéciaux : Datetime, etc.
			// + verif ajout ou remplacement des données
			// + traduction des données $entityString
			// $this->data["format"]				= "standard" ou autre
			// $this->data["nom"]					= champ + association (éventuelle)
			// $this->data["vals"]					= valeurs
			// $this->data["suppStd"]				= boolean --> supprime ou non les valeurs ext. par défaut
			// $this->data["champSlf"]				= nom du champ
			// $this->data["champSlf_collection"]	= nom du champ version "colletion" (sinon sans "s")
			// $this->data["champExt"]				= nom du champ externe
			// $this->data["entitExt"]				= nom de l'entité externe
			// $this->data["entityList"]			= Liste des valeurs pour le champs de l'entité
			// $this->data["meta"]["type"] 			= infos META
			// $this->data["meta"]["methode"] 		= nom de methode
			$this->initData($nom, $entityString);

			// Recherche et ajout à $vals des valeurs désignées dans le fichier XML --> Association single/collection
			$set = $this->data["meta"]["methode"];
			switch($this->data["meta"]["type"]["Association"]) {
				case "single":
					$explodName = explode('\\',  $this->data["meta"]["type"]["targetEntity"]);
					printf("Entité liée (single) : ".$this->data["meta"]["type"]["targetEntity"]."\n");
					// printf("Bundle : ".$explodName[0].$explodName[1].":".$explodName[3]."\n");
					$repo = $this->manager->getRepository($explodName[0].$explodName[1].":".$explodName[3]);
					// printf("Find : findBy".ucfirst($this->data["champExt"])." = ".$this->data["entityList"][0]."\n");
					$findMtd = "findBy".ucfirst($this->data["champExt"]);
					$obj = $repo->$findMtd($this->data["entityList"][0]);
					if(count($obj) > 0) {if(is_object($obj[0])) {$this->parsList->$set($obj[0]);}}
					break;
				case "collection":
					$explodName = explode('\\',  $this->data["meta"]["type"]["targetEntity"]);
					// printf("Entité liée (collection) : ".$this->data["meta"]["type"]["targetEntity"]."\n");
					// printf("Bundle : ".$explodName[0].$explodName[1].":".$explodName[3]."\n");
					$repo = $this->manager->getRepository($explodName[0].$explodName[1].":".$explodName[3]);
					foreach($this->data["entityList"] as $val) {
						// printf("Find : findBy".ucfirst($this->data["champExt"])." = ".$val."\n");
						$findMtd = "findBy".ucfirst($this->data["champExt"]);
						foreach($repo->$findMtd($val) as $obj) if(is_object($obj)) $this->parsList->$set($obj);
					}
					break;
				default:
					// aucune + autres
					foreach($this->data["entityList"] as $val) {
						// ajout de liens URL dynamiques liées (dans les textes)
						switch($this->data["format"]) {
							case "Datetime":
								$val = new \Datetime($val);
								break;
							default:
								// standard + autres
								$val = $this->dynUrls($val);
								break;
						}
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
		// printf("Mémoire PHP : ".memory_get_usage()." --> ");
		$this->manager->persist($this->parsList);
		$this->manager->flush();
		// printf(memory_get_usage()."\n");
		$this->writeConsole("* Entité ".$this->EntityService->getName()." enregistrée en BDD *", "succes", 2);
		// printf("* Entité enregistrée en BDD *\n\n");
		return $this->parsList; // renvoie l'objet enregistré
	}


	/**
	 * Importation de fichiers textes externes
	 * @param array $files -> liste des fichiers
	 * @return array
	 */
	private function importFiles($files) {
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
			$importFile = BASEFOLDER."/src/AcmeGroup/SiteBundle/Resources/public/".$file[0]."/".$file[1];
			printf("Import : ".$importFile."\n");
			if(file_exists($importFile)) {
				$txt = @file_get_contents($importFile);
				if($txt !== false) {
					$txt = nl2br($txt);
					$contenu[] = str_replace("><br />", ">", $txt);
					printf(" --> Fichier chargé avec succès ( ".substr($txt, 0, 20)."… )\n");
				} else printf(" --> ".$this->writeConsole("ECHEC", "error", false)." (lecture du fichier échouée)\n");
			} else printf(" --> ".$this->writeConsole("ECHEC", "error", false)." (fichier non trouvé)\n");
		}
		return $contenu;
	}

	private function dynUrls($texte) {
		// {# IMG:nom:isaac #}
		$texte = preg_replace_callback(
			'|{# (IMG):(\w+):(\w+) #}|', 
			function($matches) {
				if((count($matches) > 3) || ($matches[1] == 'IMG')) {
					$meth = 'findBy'.ucfirst($matches[2]);
					// $repo = $this->manager->getRepository("AcmeGroup\\LaboBundle\\Entity\\image");
					$repo = $this->manager->getRepository("AcmeGroupLaboBundle:image");
					$image = $repo->$meth($matches[3]);
					if(count($image) > 0) return ("{{ asset('images/original/".$image[0]->getFichierNom()."') }}");
				}
			},
			$texte, -1, $nb
		);
		return $texte;
	}


	/**
	 * initData
	 * Définit le format : spécial (précisé) ou standard
	 * @param $nom (du champ)
	 * @param $entityString (chaîne de paramètres)
	 */
	private function initData($nom, $entityString) {
		$n = array();
		foreach($this->testFormats as $nomformat => $prefix) {
			if(preg_match("'^(".$prefix.")'", $nom)) {
				$this->data["format"] = $nomformat;
				$this->data["nom"] = str_replace($prefix, "", $nom);
			} else {
				$this->data["format"] = "standard";
				$this->data["nom"] = $nom;
			}
		}
		$this->verifSuppStdLiens($entityString);
		$this->compileNom();
		// printf("Format objet ".$this->data["format"]." : ".$this->data["nom"]."\n");
		// supprime les valeurs par défaut sur le champ
		if($this->data["suppStd"] === true) $this->emptyField();
	}

	/**
	 * verifSuppStdLiens
	 * Verifie si les liens remplacent ou sont ajoutés au champ existant
	 * @param $entityString (chaîne de paramètres)
	 */
	private function verifSuppStdLiens($entityString) {
			if(substr($entityString, 0, 1) == "+") {
				// + : ajoute les valeurs aux valeurs par défaut
				$this->data["suppStd"] = false;
				$this->data["vals"] = substr($entityString, 1); // on enlève le + il ne sert plus à rien
				// $this->suppEntitesLiees($this->data["nom"]);
			} else {
				$this->data["suppStd"] = true;
				$this->data["vals"] = $entityString;
			}
	}

	/**
	 * compileNom
	 * Extrait les paramètres du nom de l'attribut
	 */
	private function compileNom() {
		$this->data["entityList"] = explode("|", $this->data["vals"]);
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
			// var_dump($entityList);
		}
		$this->data["champSlf"] = $nom[0];
		$this->data["champExt"] = $nom[1];
		$this->data["entitExt"] = $o[1];
		$this->getTypeOfAssociation();
	}

	/**
	 * getTypeOfAssociation
	 * Renvoie le type d'Association ["type"] et la méthode associée ["methode"]
	 */
	private function getTypeOfAssociation() {
		$this->data["meta"] = array();
		$this->data["champSlf_collection"] = $this->data["champSlf"]."s";
		if(method_exists($this->parsList, "get".ucfirst($this->data["champSlf_collection"]))) {
			$champ = $this->data["champSlf_collection"];
		} else {
			$champ = $this->data["champSlf_collection"] = $this->data["champSlf"];
		}
		$this->data["meta"]["type"] = $this->EntityService->getMetaInfoField($this->parsList, $champ);
		switch($this->data["meta"]["type"]["Association"]) {
			case "single":
				$this->data["meta"]["methode"] = "set".ucfirst($this->data["champSlf"]);
				printf($this->data["champSlf"]." (single)\n");
				break;
			case "collection":
				$this->data["meta"]["methode"] = "add".ucfirst($this->data["champSlf"]);
				printf($this->data["champSlf"]." (collection)\n");
				break;
			default:
				$this->data["meta"]["methode"] = "set".ucfirst($this->data["champSlf"]);
				printf($this->data["champSlf"]." (aucune Association)\n");
				break;
		}
	}

	/**
	 * emptyField
	 * Vide les données d'un champ
	 * @param $field
	 */
	private function emptyField() {
		return $this->EntityService->emptyField($this->data["champSlf_collection"], $this->parsList);
	}



	// AFFICHAGE DES INFORMATIONS

	/**
	 * Supprime tous les dossiers du dossier images
	 */
	private function deleteAllImageFolders() {
		$this->afficheTitre('Vérification et suppression des dossiers web/images/');
		// $this->aetools->setWebPath("images/");
		foreach ($this->imagetools->getAllDossiers() as $key => $value) {
			$path = $this->aetools->setWebPath("images/".$value["nom"]."/");
			if($path !== false) {
				$this->aetools->findAndDeleteFiles(ALL_FILES);
				if($this->aetools->deleteDir($this->aetools->getCurrentPath()) === true) $result = "Dossier existant : effacé";
					else $result = "Dossier existant : ".$this->returnConsole("!!!", "error", false)." non effacé";
			} else $result = "Dossier non existant";
			$this->writeConsole($this->texttools->fillOfChars("Dossier ".$value["nom"], 25)." | ".$this->texttools->fillOfChars($result, 40), "table_line", true);
		}
		$this->doRT();
		$this->aetools->setWebPath();
	}

	/**
	 * Affiche la liste des entités
	 */
	private function afficheEntities() {
		$this->afficheTitre('Liste des entités présentes détectées par Doctrine2');
		foreach($this->listOfEnties as $nom => $namespace) {
			$this->writeConsole($this->texttools->fillOfChars($nom, 25)." | ".$this->texttools->fillOfChars($namespace, 40), "table_line", true);
		}
		$this->doRT();
	}

	/**
	 * Affiche la liste des bundles
	 */
	private function afficheBundles() {
		$this->afficheTitre('Liste des Bundles présents détectés par Symfony2');
		foreach($this->listOfBundles as $nom => $namespace) {
			$this->writeConsole($this->texttools->fillOfChars($nom, 25)." | ".$this->texttools->fillOfChars($namespace, 40), "table_line", true);
		}
		$this->doRT();
	}

	private function afficheTitre($texte) {
		$this->writeConsole($this->texttools->fillOfChars($texte, 71), "table_titre", true);
	}

	private function writeConsole($t, $color = "normal", $rt = true) {
		printf($this->returnConsole($t, $color, $rt));
	}

	private function returnConsole($t, $color = "normal", $rt = true) {
		if($rt !== false) {
			if($rt === true) $rt = 1;
			$rt2 = "";
			for ($i=0; $i < $rt; $i++) { 
				$rt2 .= "\n";
			}
			$rt = $rt2;
		} else $rt = "";
		switch ($color) {
			case 'error':
				return "\033[1;7;31m".$t."\033[00m".$rt;
				break;
			case 'succes':
				return "\033[1;42;30m".$t."\033[00m".$rt;
				break;
			case 'headline':
				return "\033[1;46;34m".$t."\033[00m".$rt;
				break;
			case 'table_titre':
				return "\033[1;44;36m".$t."\033[00m".$rt;
				break;
			case 'table_line':
				return "\033[1;40;37m".$t."\033[00m".$rt;
				break;
			default:
				return "\033[00m".$t.$rt;
				break;
		}		
	}

	private function doRT() {
		printf("\n");
	}

}