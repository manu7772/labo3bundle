<?php
// labo/Bundle/TestmanuBundle/services/aetools/imageAetools.php

namespace labo\Bundle\TestmanuBundle\services\aetools;

use Doctrine\Common\EventSubscriber; 
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

use AcmeGroup\LaboBundle\Entity\image;

define("FORMATS_IMAGES",	"(jpeg|jpg|gif|png|ico)$");
define("FORMATS_PDF",		"(pdf)$");

class imageAetools {

	protected $currentAeReponse = null;	// Réponse de la dernière opération
	protected $modeFixtures = false;	// true pour mode fixtures actif
	protected $curtImage = array();		// image courante
	protected $newImages = array();		// image dérivée
	protected $aetools;
	protected $modes = array("cut", "in", "deform", "no");
	protected $appliDeclinaisons = array();
	protected $formatsValides;
	protected $maxFixtImageWidth = 1024;
	protected $imgTypes = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF);
	protected $dossiers = array( // a0dcb1336bd75979967e66b7490968e8-1412280035
		// nom : nom du dossier
		// x et y : dimensions en pixels
		// mode : mode de rééchantillonnage (voir méthode "thumb_image()")
		"original"	=> array("nom"	=> "original"),
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
		// "version"	=> array("nom"	=> "version",	"x"	=> 270,		"y"	=> 60,		"mode"	=> "cut",	"type" => null,				"ext" => null),
		"optim"		=> array("nom"	=> "optim",		"x"	=> 800,		"y"	=> 600,		"mode"	=> "in",	"type" => null,				"ext" => null),
		"favicons"	=> array("nom"	=> "favicons",	"x"	=> 16,		"y"	=> 16,		"mode"	=> "in",	"type" => "image/png",		"ext" => 'ico')
		);
	protected $declinaison = array(
		"universel"		=> array("optim", "tn200", "tn128", "tn64"),
		"user"			=> array("optim", "tn200", "tn128", "tn64"),
		"avatar"		=> array("optim", "tn200", "tn128", "tn64", "tn32"),
		"favicon"		=> array("favicons", "tn64"),
		"ambiance"		=> array("optim", "tn200", "tn128", "tn64"),
		"article"		=> array("optim", "article", "tn200", "tn128", "tn64"),
		"diaporama"		=> array("optim", "tn200", "tn128", "tn64"),
		"logo"			=> array("optim", "tn200", "tn128", "tn64", "tn64in", "logo", "favicons"),
		"site"			=> array("optim", "tn200in", "tn64"),
		"atelier"		=> array("optim", "tn265in", "tn200in", "tn152", "tn64"),
		"evenement"		=> array("optim", "tn200in", "tn64"),
		"partenaire"	=> array("optim", "tn200in", "tn64"),
		"magasin"		=> array("optim", "tn200in", "tn64"),
		"version"		=> array("optim", "tn200", "tn64"),
		"admin"			=> array("optim", "tn200", "tn128", "tn64", "optim")
		);

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
		if($this->container->get("request")->attributes->get('_controller') === null) {
			$this->modeFixtures = true;
		} else {
			$this->modeFixtures = false;
		}
		$this->em = $this->container->get('doctrine')->getManager();
		$this->repo = $this->em->getRepository("AcmeGroupLaboBundle:image");
		$this->aetools = $this->container->get("acmeGroup.aetools");
		foreach($this->imgTypes as $it) {
			$this->formatsValides[image_type_to_mime_type($it)]["type"] = image_type_to_mime_type($it);
			$this->formatsValides[image_type_to_mime_type($it)]["maxSize"] = 6000;
			$this->imgMimeType[$it] = image_type_to_mime_type($it);
		}
		// création des dossiers
		$this->aetools->setWebPath("images/");
		$this->aetools->verifDossierAndCreate("original");
		foreach ($this->dossiers as $nom => $contenus) {
			$this->aetools->verifDossierAndCreate($nom);
		}
		// $this->echoFixtures("!!!!!!!!!!!!!!!!!!!!!!!!!! RUN INIT IMAGES TOOL !!!!!!!!!!!!!!!!!!!!!!!!!!\n");
		return $this;
	}

	public function finishFixtures() {
		if($this->modeFixtures === true) {
			$this->echoFixtures("---> CHECK FIXTURES : nettoyage complet des données images\n");
			$this->echoFixtures("---> CHECK FIXTURES : suppression image courante\n");
			$this->deleteCurtImages();
			$this->echoFixtures("---> CHECK FIXTURES : suppression ".count($this->newImages)." images déclinaisons\n");
			$this->deleteAllNewImages();
		}
	}

	/**
	* getAereponse
	* Renvoie la réponse de la dernière opération effectuée
	* @return aeReponse
	*/
	public function getAereponse() {
		return $this->currentAeReponse;
	}

	private function setAeReponse($result, $data = null, $message = "") {
		$this->currentAeReponse = new aeReponse($result, $data, $message);
	}

	/**
	* getAllDossiers
	* Renvoie la liste des dossiers images
	* @return array
	*/
	public function getAllDossiers() {
		return $this->dossiers;
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
		return $this->declinaison;
	}

	/**
	* loadImageBdd
	* charge une image d'après l'objet image (champ file)
	* @param image $imageObj
	* @return boolean
	*/
	public function loadImageBdd(image $imageObj) {
		// $this->curtImage["objet"] = $imageObj;
		// $this->loadImageFile($this->curtImage["objet"]->getFile());
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
		// $this->aetools->setWebPath("images/");
		$this->loadImageFile($this->getUploadRootDir()."original/".$this->curtImage["objet"]->getFichierNom());
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
		if($this->modeFixtures === true) {
			$this->curtImage["file"] = "src/AcmeGroup/SiteBundle/Resources/public/images_fixtures/".$this->curtImage["objet"]->getFichierOrigine();
		} else {
			$this->curtImage["file"] = $this->curtImage["objet"]->getFile();
		}
		$this->curtImage["type"] = getimagesize($this->curtImage["file"]);

		if($this->checkCurrentTypeValide() === true) {
			$this->echoFixtures("Format valide !!! --> ");
			switch($this->curtImage["type"]["mime"]) {
				case image_type_to_mime_type(IMAGETYPE_JPEG):
					$this->echoFixtures(image_type_to_mime_type(IMAGETYPE_JPEG)."\n");
					$this->curtImage["image"] = imagecreatefromjpeg($this->curtImage["file"]); //jpeg file
					// $this->echoFixtures(image_type_to_mime_type(IMAGETYPE_JPEG));
				break;
				case image_type_to_mime_type(IMAGETYPE_PNG):
					$this->echoFixtures(image_type_to_mime_type(IMAGETYPE_PNG)."\n");
					$this->curtImage["image"] = imagecreatefrompng($this->curtImage["file"]); //png file
					imagealphablending($this->curtImage["image"], false);
					imagesavealpha($this->curtImage["image"], true);
					// $this->echoFixtures(image_type_to_mime_type(IMAGETYPE_PNG));
				break;
				case image_type_to_mime_type(IMAGETYPE_GIF):
					$this->echoFixtures(image_type_to_mime_type(IMAGETYPE_GIF)."\n");
					$this->curtImage["image"] = imagecreatefromgif($this->curtImage["file"]); //gif file
					// $this->echoFixtures(image_type_to_mime_type(IMAGETYPE_GIF));
				break;
				default:
					return false;
				break;
			}
			// Mode fixtures : réduit le fichier si trop grand
			// if(($this->modeFixtures === true) && ($this->curtImage["type"][0] > $this->maxFixtImageWidth)) {
			// 	$ratio = $this->curtImage["type"][1] / $this->curtImage["type"][0];
			// 	echo "Mémoire PHP : ".memory_get_usage()." (Création original avant réduction : ".$this->curtImage["objet"]->getFichierOrigine().")\n";
			// 	$newimg = imagecreatetruecolor($this->maxFixtImageWidth, round($this->maxFixtImageWidth * $ratio));
			// 	imagealphablending($newimg, false);
			// 	imagesavealpha($newimg, true);
			// 	$newimg = imagecopyresampled(
			// 		$newimg,
			// 		$this->curtImage["image"],
			// 		0,0,0,0,
			// 		$this->maxFixtImageWidth,
			// 		round($this->maxFixtImageWidth * $ratio),
			// 		$this->curtImage["type"][0],
			// 		$this->curtImage["type"][1]
			// 	);
			// 	imagedestroy($this->curtImage["image"]);
			// 	$this->curtImage["image"] = $newimg;
			// 	imagedestroy($newimg);
			// }
			$this->echoFixtures("Mémoire PHP : ".memory_get_usage()." (Création original : ".$this->curtImage["objet"]->getFichierOrigine().")\n");
		} else {
			$this->echoFixtures("Format non supporté !!!");
			return false;
		}
		// enregistrement dans le dossier "original"
		$this->aetools->setWebPath("images/");
		$this->aetools->verifDossierAndCreate("original");
		$this->echoFixtures("COPY : ".$this->curtImage["file"]."\nVERS : ".$this->getUploadRootDir()."original/".$this->curtImage["objet"]->getFichierNom()."\n");
		if($this->modeFixtures === true) {
			copy($this->curtImage["file"], $this->getUploadRootDir()."original/".$this->curtImage["objet"]->getFichierNom());
		} else {
			$this->curtImage["objet"]->getFile()->move($this->getUploadRootDir()."original/", $this->curtImage["objet"]->getFichierNom());
		}
		$this->curtImage["file"] = $this->getUploadRootDir()."original/".$this->curtImage["objet"]->getFichierNom();
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

		$this->SetAeReponse(true, null, "OK");
		return $this;
	}

	public function getXimage() {
		$path = imagesx($this->curtImage["image"]);
		$this->SetAeReponse(true, $path, "OK");
		return $path;
	}
	public function getYimage() {
		$path = imagesy($this->curtImage["image"]);
		$this->SetAeReponse(true, $path, "OK");
		return $path;
	}

	protected function getUploadRootDir() {
		$path = __DIR__.'/../../../../../../../../web/images/';
		$this->SetAeReponse(true, $path, "OK");
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
			$this->setAeReponse(false, null, "Type fourni non supporté : ".$type);
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
			$this->setAeReponse(true, null, "Format d'image supporté");
			return true;
		} else {
			$this->setAeReponse(false, null, "Type non supporté : ".$this->curtImage["type"]["mime"]);
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
				$this->appliDeclinaisons[$nom] = $this->dossiers[$nom];
			}
		}
		return $this->appliDeclinaisons;
		// $this->echoFixtures("<pre>");var_dump($this->appliDeclinaisons);$this->echoFixtures("</pre>");
	}

	/**
	* newImage
	* Crée une nouvelle image (vide)
	* 
	* @param $nom
	* @param $tailleX
	* @param $tailleY
	*
	* @return imagetools
	*/
	public function createNewImage($nom, $tailleX, $tailleY) {
		// $this->newImages[$nom]["image"]		= image
		$this->newImages[$nom]["image"] = imagecreatetruecolor($tailleX, $tailleY);
		imagealphablending($this->newImages[$nom]["image"], false);
		imagesavealpha($this->newImages[$nom]["image"], true);
		echo "Mémoire PHP : ".memory_get_usage()." (Création thumb : ".$nom.")\n";
		return $this;
	}

	/**
	* deleteAllNewImages
	* Efface de la mémoire une image
	*
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
	*
	*/
	public function deleteCurtImages() {
		$nom = $this->curtImage["objet"]->getFichierOrigine();
		if(isset($this->curtImage["image"])) {
			$d = imagedestroy($this->curtImage["image"]);
			unset($this->curtImage["image"]);
			if($d === true) $this->echoFixtures("---> Desctruction image courante\n");
				else $this->echoFixtures("---> ALERTE : Desctruction image échouée !!!\n");
		}
		$this->curtImage = null;
		unset($this->curtImage);
		$this->curtImage = array();
		echo "Mémoire PHP : ".memory_get_usage()." (destruction originale ".$nom.")\n";
	}

	/**
	* deleteImage
	* Efface de la mémoire une image
	* 
	* @param $nom
	*/
	public function deleteImage($nom) {
		if(isset($this->newImages[$nom]["image"])) {
			$d = imagedestroy($this->newImages[$nom]["image"]);
			if($d === true) $this->echoFixtures("---> Desctruction image thumb ".$nom."\n");
				else $this->echoFixtures("---> ALERTE : Desctruction image thumb ".$nom." échouée !!!\n");
		}
		$this->newImages[$nom] = null;
		unset($this->newImages[$nom]);
		echo "Mémoire PHP : ".memory_get_usage()." (destruction thumb ".$nom.")\n";
	}

	/**
	* generateAllThumb
	* Crée tous les tumbnails de l'image courante selon ses attributs de déclinaison
	* et les enregistre dans les dossiers respectifs
	* 
	* @param string $img
	*/
	protected function generateAllThumb($listOfDeclinaisons = null) {
		$this->aetools->setWebPath("images/");
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
		$this->aetools->setWebPath("images/");
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
		$this->aetools->verifDossierAndCreate($this->dossiers[$nom]['nom']);
		// destination pour l'image thumbnail
		$destination = $this->aetools->getCurrentPath().$this->dossiers[$nom]['nom']."/".$this->curtImage["objet"]->getFichierNom();
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
			@rename($destination, $this->aetools->changeExt($ext, $destination));
			if($this->aetools->getAeReponse()->getResult() === true) $this->echoFixtures($this->aetools->getAeReponse()->getMessage()."\n");
		}
		$this->echoFixtures("Déclinaison -> ".$destination."\n");
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
		return $this->aetools->findAndDeleteFiles($fichier);
	}

	/**
	* getFichiersImagesList
	* renvoie la liste des fichiers sur le serveur (présentes en BDD ou non)
	* 
	* @return array $images
	*/
	public function getFichiersImagesList() {
		return $this->aetools->readAll(".+");	// fichiers images
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

	protected function echoFixtures($t) {
		if($this->modeFixtures === true) echo($t);
	}

}

?>
