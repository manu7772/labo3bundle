<?php
// laboBundle/services/aetools/imageAetools.php

namespace laboBundle\services\aetools;

use Doctrine\Common\EventSubscriber; 
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
// use laboBundle\services\aetools\aeReponse;
// aetools
use laboBundle\services\aetools\aetools;

use AcmeGroup\LaboBundle\Entity\image;


class imageAetools extends aetools {

	const IMAGE_MAX_SIZE		= 12000;
	const FORMATS_IMAGES		= "(jpeg|jpg|gif|png|ico)$";
	const FORMATS_PDF			= "(pdf)$";
	const NOM_DOSSIER_IMAGES	= "images";
	const NOM_DOSSIER_ORIGINAL	= "original";

	protected $curtImage = array();		// image courante
	protected $newImages = array();		// image dérivée
	protected $_em;						// EntityManager
	protected $repo;					// repository
	protected $aetools;
	protected $modes = array("cut", "in", "deform", "no");
	protected $appliDeclinaisons = array();
	protected $formatsValides;
	protected $maxFixtImageWidth = 1024;
	protected $imgTypes = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF);
	protected $dossiersImages;
	protected $declinaison;
	protected $default_formats = array("universel", "ambiance");

	public function __construct(ContainerInterface $container = null) {
		$this->container = $container;
		$this->writeConsole("Chargement utilitaire images…", "normal", true);
		parent::__construct($this->container);
		$this->writeConsole("Initialisation data images…");
		$this->initImagesData();

		foreach($this->imgTypes as $it) {
			$this->formatsValides[image_type_to_mime_type($it)] = array(
				"type"		=> image_type_to_mime_type($it),
				"maxSize"	=> self::IMAGE_MAX_SIZE,
				);
			$this->imgMimeType[$it] = image_type_to_mime_type($it);
		}
		// création des dossiers
		// $this->checkDossiersImages();
		return $this;
	}

	protected function initImagesData() {
		if(is_object($this->container)) {
			$this->_em = $this->container->get('doctrine')->getManager();
			$this->repo = $this->_em->getRepository("AcmeGroupLaboBundle:image");
		}
		$this->dossiersImages = array( // a0dcb1336bd75979967e66b7490968e8-1412280035
			// nom : nom du dossier
			// x et y : dimensions en pixels
			// mode : mode de rééchantillonnage (voir méthode "thumb_image()")
			self::NOM_DOSSIER_ORIGINAL	=> array("nom"	=> self::NOM_DOSSIER_ORIGINAL,	"x"	=> null,		"y"	=> null,		"mode"	=> null,	"type" => null,				"ext" => null),
			"article"	=> array("nom"	=> "article",	"x"	=> 205,		"y"	=> 156,		"mode"	=> "cut",	"type" => null,				"ext" => null),
			"tn152"		=> array("nom"	=> "tn152",		"x"	=> 152,		"y"	=> 152,		"mode"	=> "cut",	"type" => null,				"ext" => null),
			"tn265in"	=> array("nom"	=> "tn265in",	"x"	=> 265,		"y"	=> 265,		"mode"	=> "in",	"type" => null,				"ext" => null),
			"tn200"		=> array("nom"	=> "tn200",		"x"	=> 200,		"y"	=> 200,		"mode"	=> "cut",	"type" => null,				"ext" => null),
			"tn200in"	=> array("nom"	=> "tn200in",	"x"	=> 200,		"y"	=> 200,		"mode"	=> "in",	"type" => null,				"ext" => null),
			"tn128"		=> array("nom"	=> "tn128",		"x"	=> 128,		"y"	=> 128,		"mode"	=> "cut",	"type" => null,				"ext" => null),
			"tn64"		=> array("nom"	=> "tn64",		"x"	=> 64,		"y"	=> 64,		"mode"	=> "cut",	"type" => null,				"ext" => null),
			"tn64in"	=> array("nom"	=> "tn64in",	"x"	=> 64,		"y"	=> 64,		"mode"	=> "in",	"type" => null,				"ext" => null),
			"tn32"		=> array("nom"	=> "tn32",		"x"	=> 32,		"y"	=> 32,		"mode"	=> "cut",	"type" => null,				"ext" => null),
			"logo"		=> array("nom"	=> "logo",		"x"	=> 172,		"y"	=> 55,		"mode"	=> "in",	"type" => null,				"ext" => null),
			"optim"		=> array("nom"	=> "optim",		"x"	=> 800,		"y"	=> 600,		"mode"	=> "in",	"type" => null,				"ext" => null),
			"favicons"	=> array("nom"	=> "favicons",	"x"	=> 16,		"y"	=> 16,		"mode"	=> "in",	"type" => "image/png",		"ext" => 'ico')
		);
		$this->declinaison = array(
			"universel"		=> array(self::NOM_DOSSIER_ORIGINAL, "optim", "tn200", "tn128", "tn64"),
			"user"			=> array(self::NOM_DOSSIER_ORIGINAL, "optim", "tn200", "tn128", "tn64"),
			"avatar"		=> array(self::NOM_DOSSIER_ORIGINAL, "optim", "tn200", "tn128", "tn64", "tn32"),
			"favicon"		=> array(self::NOM_DOSSIER_ORIGINAL, "favicons", "tn64"),
			"ambiance"		=> array(self::NOM_DOSSIER_ORIGINAL, "optim", "tn200", "tn128", "tn64"),
			"article"		=> array(self::NOM_DOSSIER_ORIGINAL, "optim", "article", "tn200", "tn128", "tn64"),
			"diaporama"		=> array(self::NOM_DOSSIER_ORIGINAL, "optim", "tn200", "tn128", "tn64"),
			"logo"			=> array(self::NOM_DOSSIER_ORIGINAL, "optim", "tn200", "tn128", "tn64", "tn64in", "logo", "favicons"),
			"site"			=> array(self::NOM_DOSSIER_ORIGINAL, "optim", "tn200in", "tn64"),
			"fiche"			=> array(self::NOM_DOSSIER_ORIGINAL, "optim", "tn265in", "tn200in", "tn152", "tn64"),
			"evenement"		=> array(self::NOM_DOSSIER_ORIGINAL, "optim", "tn200in", "tn64"),
			"partenaire"	=> array(self::NOM_DOSSIER_ORIGINAL, "optim", "tn200in", "tn64"),
			"magasin"		=> array(self::NOM_DOSSIER_ORIGINAL, "optim", "tn200in", "tn64"),
			"cuisson"		=> array(self::NOM_DOSSIER_ORIGINAL, "tn64"),
			"version"		=> array(self::NOM_DOSSIER_ORIGINAL, "optim", "tn200", "tn64"),
			"admin"			=> array(self::NOM_DOSSIER_ORIGINAL, "optim", "tn200", "tn128", "tn64", "optim")
		);
	}

	// public function finishFixtures() {
	// 	if($this->isControllerAbsent()) {
	// 		$this->writeConsole("---> CHECK FIXTURES : nettoyage complet des données images");
	// 		$this->writeConsole("---> CHECK FIXTURES : suppression image courante");
	// 		$this->deleteCurtImages();
	// 		$this->writeConsole("---> CHECK FIXTURES : suppression ".count($this->newImages)." images déclinaisons");
	// 		$this->deleteAllNewImages();
	// 	}
	// }

	public function getDefaultFormats() {
		return $this->default_formats;
	}

	public function getNomDossierImages() {
		return self::NOM_DOSSIER_IMAGES.self::SLASH;
	}

	public function getNomDossierOriginal() {
		return self::NOM_DOSSIER_ORIGINAL.self::SLASH;
	}

	/**
	* Renvoie la liste des dossiers images
	* @return array
	*/
	public function getAllDossiers() {
		return $this->dossiersImages;
	}

	/**
	* getDeclinaisonList
	* Renvoie la liste des déclinaisons possibles
	* @return array
	*/
	public function getDeclinaisonList() {
		return $this->declinaison;
	}

	/**
	* existDeclinaison
	* Précise si une déclinaison $nom est possible
	* @return boolean
	*/
	public function existDeclinaison($nom) {
		return array_key_exists($nom, $this->declinaison) ? true : false ;
	}

	/**
	* loadImageBdd
	* charge une image d'après l'objet image (champ file)
	* @param image $imageObj
	* @return boolean
	*/
	public function loadImageBdd(image $imageObj) {
		$this->loadImageFile($imageObj);
	}

	/**
	* loadImageOriginal
	* charge une image (dossier "original") d'après l'objet image (champ file)
	* @param image $imageObj
	* @return boolean
	*/
	public function loadImageOriginal(image $imageObj) {
		$this->curtImage["objet"] = $imageObj;
		// $this->setWebPath($this->getNomDossierImages());
		$this->loadImageFile($this->getUploadRootDir().$this->getNomDossierOriginal().$this->curtImage["objet"]->getFichierNom());
	}

	/**
	* loadImageFile
	* charge l'image (chemin ou objet imageObj) d'après un fichier et crée les déclinaisons
	* 
	* @param string $image (chemin + nom du fichier)
	* @param image $imageObj
	* @param boolean $generateThumb
	* @return imagetools
	*/
	public function loadImageFile(image $image, $generateThumb = true) {
		// $this->curtImage["file"]			= nom du fichier
		// $this->curtImage["type"]			= infos sur fichier image (résultat de getimagezise())
		// $this->curtImage["declinaisons"]	= array des déclinaisons de l'image
		// $this->curtImage["image"]		= image
		// $this->curtImage["objet"]		= objet entité image
		$this->curtImage["objet"] = $image;
		if($this->isControllerAbsent()) {
			$this->curtImage["file"] = "src/AcmeGroup/SiteBundle/Resources/public/images_fixtures/".$this->curtImage["objet"]->getFichierOrigine();
		} else {
			$this->curtImage["file"] = $this->curtImage["objet"]->getFile();
		}
		$this->curtImage["type"] = getimagesize($this->curtImage["file"]);

		if($this->checkCurrentTypeValide() === true) {
			$this->writeConsole("Format valide !!! --> ");
			switch($this->curtImage["type"]["mime"]) {
				case image_type_to_mime_type(IMAGETYPE_JPEG):
					$this->writeConsole(image_type_to_mime_type(IMAGETYPE_JPEG));
					$this->curtImage["image"] = imagecreatefromjpeg($this->curtImage["file"]); //jpeg file
					// $this->writeConsole(image_type_to_mime_type(IMAGETYPE_JPEG));
				break;
				case image_type_to_mime_type(IMAGETYPE_PNG):
					$this->writeConsole(image_type_to_mime_type(IMAGETYPE_PNG));
					$this->curtImage["image"] = imagecreatefrompng($this->curtImage["file"]); //png file
					imagealphablending($this->curtImage["image"], false);
					imagesavealpha($this->curtImage["image"], true);
					// $this->writeConsole(image_type_to_mime_type(IMAGETYPE_PNG));
				break;
				case image_type_to_mime_type(IMAGETYPE_GIF):
					$this->writeConsole(image_type_to_mime_type(IMAGETYPE_GIF));
					$this->curtImage["image"] = imagecreatefromgif($this->curtImage["file"]); //gif file
					// $this->writeConsole(image_type_to_mime_type(IMAGETYPE_GIF));
				break;
				default:
					return false;
				break;
			}
			$this->writeConsole(" (Création original : ".$this->curtImage["objet"]->getFichierOrigine().")");
		} else {
			$this->writeConsole("Format non supporté !!!");
			return false;
		}
		// enregistrement dans le dossier "original"
		$this->setWebPath($this->getNomDossierImages());
		$this->verifDossierAndCreate(self::NOM_DOSSIER_ORIGINAL);
		$this->writeConsole("COPY : ".$this->curtImage["file"].self::EOLine."VERS : ".$this->getUploadRootDir().$this->getNomDossierOriginal().$this->curtImage["objet"]->getFichierNom());
		if($this->isControllerAbsent()) {
			copy($this->curtImage["file"], $this->getUploadRootDir().$this->getNomDossierOriginal().$this->curtImage["objet"]->getFichierNom());
		} else {
			$this->curtImage["objet"]->getFile()->move($this->getUploadRootDir().$this->getNomDossierOriginal(), $this->curtImage["objet"]->getFichierNom());
		}
		$this->curtImage["file"] = $this->getUploadRootDir().$this->getNomDossierOriginal().$this->curtImage["objet"]->getFichierNom();
		if($generateThumb === true) $this->generateAllThumb();
		return true;
	}

	/**
	* updateImageThums
	* recrée les thumnails d'une image existante en BDD
	* 
	* @param image $imageObj
	* @return imagetools
	*/
	public function updateImageThums(image $image) {
		$this->curtImage["file"] = $this->curtImage["objet"]->getFile();

		return $this;
	}

	public function getXimage() {
		$path = imagesx($this->curtImage["image"]);
		return $path;
	}
	public function getYimage() {
		$path = imagesy($this->curtImage["image"]);
		return $path;
	}

	protected function getUploadRootDir() {
		$path = $this->gotoroot.self::WEB_PATH.$this->getNomDossierImages();
		return $path;
	}

	
	/**
	* changeImageType
	* Change le type de l'image fournie en paramètre
	* 
	* @param $image
	* @param $type (mime)
	* @return boolean
	*/
	public function changeImageType(image $image, $type) {
		if(in_array($type, $this->imgMimeType) || in_array($type, $this->imgTypes)) {
			// 
		} else {
			// 
		}
		return $this;
	}
	
	/**
	* checkCurrentTypeValide
	* Renvoie true si l'image courante est un format supporté / false sinon
	* 
	* @return boolean
	*/
	public function checkCurrentTypeValide() {
		return $this->checkTypeValide($this->curtImage["type"]["mime"]);
	}

	/**
	* checkTypeValide
	* Renvoie true si le $type est un format supporté / false sinon
	* 
	* @return boolean
	*/
	public function checkTypeValide($type) {
		if(array_key_exists($this->curtImage["type"]["mime"], $this->formatsValides)) {
			return true;
		} else {
			return false;
		}
	}


	/**
	* checkDeclinaisons
	* Renvoie la liste des déclinaisons à opérer sur l'image courante ($curtImage)
	* 
	* @return imagetools
	*/
	public function checkDeclinaisons() {
		$this->appliDeclinaisons = array();
		$types = $this->curtImage["objet"]->getTypeImages();
		foreach($types as $type) if(array_key_exists($type->getNom(), $this->declinaison)) {
			foreach($this->declinaison[$type->getNom()] as $nom) {
				$this->appliDeclinaisons[$nom] = $this->dossiersImages[$nom];
			}
		}
		return $this->appliDeclinaisons;
	}

	/**
	* newImage
	* Crée une nouvelle image (vide)
	* @param $nom
	* @param $tailleX
	* @param $tailleY
	* @return imagetools
	*/
	public function createNewImage($nom, $tailleX, $tailleY) {
		$this->newImages[$nom]["image"] = imagecreatetruecolor($tailleX, $tailleY);
		imagealphablending($this->newImages[$nom]["image"], false);
		imagesavealpha($this->newImages[$nom]["image"], true);
		$this->writeConsole(" (Création thumb : ".$nom.")");
		return $this;
	}

	/**
	* deleteAllNewImages
	* Efface de la mémoire une image
	*/
	public function deleteAllNewImages() {
		foreach($this->newImages as $nom => $newImage) $this->deleteImage($nom);
		$this->newImages = null;
		unset($this->newImages);
		$this->newImages = array();
	}

	/**
	* deleteCurtImages
	* Efface de la mémoire l'image courante
	*/
	public function deleteCurtImages() {
		$nom = $this->curtImage["objet"]->getFichierOrigine();
		if(isset($this->curtImage["image"])) {
			$d = imagedestroy($this->curtImage["image"]);
			unset($this->curtImage["image"]);
			if($d === true) $this->writeConsole("---> Desctruction image courante");
				else $this->writeConsole("---> ALERTE : Desctruction image échouée !!!");
		}
		$this->curtImage = null;
		unset($this->curtImage);
		$this->curtImage = array();
		$this->writeConsole(" (destruction originale ".$nom.")");
	}

	/**
	* deleteImage
	* Efface de la mémoire une image
	* @param $nom
	*/
	public function deleteImage($nom) {
		if(isset($this->newImages[$nom]["image"])) {
			$d = imagedestroy($this->newImages[$nom]["image"]);
			if($d === true) $this->writeConsole("---> Desctruction image thumb ".$nom);
				else $this->writeConsole("---> ALERTE : Desctruction image thumb ".$nom." échouée !!!");
		}
		$this->newImages[$nom] = null;
		unset($this->newImages[$nom]);
		$this->writeConsole(" (destruction thumb ".$nom.")");
	}

	public function checkDossiersImages() {
 		$this->writeConsole("Checking des dossiers images…");
 		$this->verifDossierAndCreate($this->getNomDossierImages());
		$this->setWebPath($this->getNomDossierImages());
		foreach ($this->dossiersImages as $nom => $contenus) {
			if($this->verifDossierAndCreate($contenus['nom']))
				$this->writeConsole($contenus['nom']." : ok.");
				else $this->writeConsole($contenus['nom']." : création échouée.", "error");
		}
		return $this;
	}

	/**
	 * Supprime tous les dossiers du dossier images
	 */
	public function deleteAllImageFolders() {
		$this->afficheTitre('Vérification et suppression des dossiers web/'.$this->getNomDossierImages());
		// $this->setWebPath($this->getNomDossierImages());
		foreach ($this->getAllDossiers() as $key => $value) {
			$path = $this->setWebPath($this->getNomDossierImages().$value["nom"]."/");
			if($path !== false) {
				$this->findAndDeleteFiles(self::ALL_FILES);
				if($this->deleteDir($this->getCurrentPath()) === true) $result = "Dossier existant : effacé";
					else $result = "Dossier existant : ".$this->returnConsole("!!!", "error", false)." non effacé";
			} else $result = "Dossier non existant";
			$this->writeConsole($this->texttools->fillOfChars("Dossier ".$value["nom"], 25)." | ".$this->texttools->fillOfChars($result, 40), "table_line", true);
		}
		$this->echoRT();
		$this->setWebPath();
	}

	/**
	* generateAllThumb
	* Crée tous les tumbnails de l'image courante selon ses attributs de déclinaison
	* et les enregistre dans les dossiers respectifs
	* 
	* @param string $img
	*/
	protected function generateAllThumb($listOfDeclinaisons = null) {
		$this->setWebPath($this->getNomDossierImages());
		if($listOfDeclinaisons === null) {
			$listOfDeclinaisons = $this->checkDeclinaisons();
		}
		foreach($listOfDeclinaisons as $nom => $declin) {
			$this->thumb_image($nom, $declin["x"], $declin["y"], $declin["mode"], $declin["type"], $declin["ext"]);
		}
		return $this;
	}


	/**
	* thumb_image
	* Crée et enregistre un tumbnail de l'image courante
	* 
	* @param string $img
	*/
	protected function thumb_image($nom, $Xsize = null, $Ysize = null, $mode = "no", $type = null, $ext = null) {
		// $mode =
		// cut 		: remplit le format avec l'image et la coupe si besoin
		// in 		: inclut l'image pour qu'elle soit entièrerement visible
		// deform 	: déforme l'image pour qu'elle soit exactement à la taille
		// no 		: ne modifie pas la taille de l'image
		$this->setWebPath($this->getNomDossierImages());
		if(!in_array($mode, $this->modes)) $mode = $this->modes[0];
		$x = $this->getXimage();
		$y = $this->getYimage();
		$ratio = $x / $y;
		if($Xsize == null && $Ysize == null) {
			$Xsize = $x;
			$Ysize = $y;
		}
		if($Xsize == null) $Xsize = $Ysize * $ratio;
		if($Ysize == null) $Ysize = $Xsize / $ratio;

		$Dratio = $Xsize / $Ysize;

		if(($x != $Xsize) || ($y != $Ysize)) {
			switch($mode) {
				case('deform') :
					$nx = $Xsize;
					$ny = $Ysize;
					$posx = $posy = 0;
				break;
				case('cut') :
					if($ratio > $Dratio) {
						$posx = ($x - ($y * $Dratio)) / 2;
						$posy = 0;
						$x = $y * $Dratio;
					} else {
						$posx = 0;
						$posy = ($y - ($x / $Dratio)) / 2;
						$y = $x / $Dratio;
					}
					$nx = $Xsize;
					$ny = $Ysize;
				break;
				case('in') :
					if($x > $Xsize || $y > $Xsize) {
						if($x > $y) {
							$nx = $Xsize;
							$ny = $y/($x/$Xsize);
						} else {
							$nx = $x/($y/$Xsize);
							$ny = $Xsize;
						}
					} else {
						$nx = $x;
						$ny = $y;
					}
					$posx = $posy = 0;
				break;
				default: // "no" et autres…
					$posx = $posy = 0;
					$nx = $x;
					$ny = $y;
				break;
			}
			$this->createNewImage($nom, $nx, $ny);
			imagecopyresampled($this->newImages[$nom]["image"], $this->curtImage["image"], 0, 0, $posx, $posy, $nx, $ny, $x, $y);
		} else {
			$this->createNewImage($nom, $x, $y);
			imagecopy($this->newImages[$nom]["image"], $this->curtImage["image"], 0, 0, 0, 0, $x, $y);
		}
		$this->verifDossierAndCreate($this->dossiersImages[$nom]['nom']);
		// destination pour l'image thumbnail
		$destination = $this->getCurrentPath().$this->dossiersImages[$nom]['nom']."/".$this->curtImage["objet"]->getFichierNom();
		// définition du type d'image thumbnail
		if($type !== null && in_array($type, $this->imgTypes)) {
			$this->curtImage["type"]["mime"] = $type;
		} else $type = $this->curtImage["type"]["mime"];
		switch($this->curtImage["type"]["mime"]){
			case image_type_to_mime_type(IMAGETYPE_JPEG):
				imagejpeg($this->newImages[$nom]["image"], $destination, 60); //jpeg file
			break;
			case image_type_to_mime_type(IMAGETYPE_PNG):
				imagepng($this->newImages[$nom]["image"], $destination, 6); //png file
			break;
			case image_type_to_mime_type(IMAGETYPE_GIF):
				imagegif($this->newImages[$nom]["image"], $destination); //gif file
			break;
		}
		// définition du dossier + nom de l'image thumbnail
		if($ext !== null && preg_match('`[[:alnum:]]{3,4}$`', $ext)) {
			@rename($destination, $this->texttools->changeExt($ext, $destination));
			// if($this->getAeReponse()->getResult() === true) $this->writeConsole($this->getAeReponse()->getMessage());
		}
		$this->writeConsole("Déclinaison -> ".$destination);
		$this->deleteImage($nom);
		// $this->deleteAllNewImages();
		return $this;
	}

	/**
	* unlinkEverywhereImage
	* Supprime un fichier image de tous
	* 
	* @param string $img
	*/
	public function unlinkEverywhereImage($fichier) {
		// recherche le fichier dans tous les emplacements
		return $this->findAndDeleteFiles($fichier);
	}

	/**
	* getFichiersImagesList
	* renvoie la liste des fichiers sur le serveur (présentes en BDD ou non)
	* 
	* @return array $images
	*/
	public function getFichiersImagesList() {
		return $this->readAll(".+");	// fichiers images
	}

	/**
	* getDBimagesList
	* renvoie la liste brute des images en BDD
	* 
	* @return array $images
	*/
	public function getDBimagesList() {
		return $this->repo->findAllFileNames(); // récupère l'entité image (findAll)
	}

	/**
	* getOrphelinImagesList
	* renvoie la liste des images orphelines (présentes sur le serveur, mais pas en BDD)
	* 
	* @return array $orphelines
	*/
	public function getOrphelinImagesList() {
		$orphelines = array();
		$fichiers = $this->getFichiersImagesList();		// fichiers images
		$imagesDb = $this->getDBimagesList();			// images en BDD
		foreach($fichiers as $fichier) if(!in_array($fichier["nom"], $imagesDb)) {
			$orphelines[] = $fichier; // $orphelines[] = liste des images orphelines
		}
		return $orphelines;
	}

}