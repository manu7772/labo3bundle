<?php

namespace labo\Bundle\TestmanuBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use \Datetime;
use \Imagick;
// Slug
use Gedmo\Mapping\Annotation as Gedmo;
// Base
use labo\Bundle\TestmanuBundle\Entity\baseL1_entity;
// aeReponse
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

/**
 * @ORM\MappedSuperclass
 */
abstract class base_entity_pdf extends baseL1_entity {

	/**
	 * @var string
	 *
	 * @ORM\Column(name="fichierOrigine", type="string", length=255)
	 */
	protected $fichierOrigine;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="fichierNom", type="string", length=255)
	 */
	protected $fichierNom;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="thumbFichierNom", type="string", length=255, nullable=true, unique=true)
	 */
	protected $thumbFichierNom;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="tailleMo", type="integer", nullable=true, unique=false)
	 */
	protected $tailleMo;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="nbpages", type="integer", nullable=true, unique=false)
	 */
	protected $nbpages;

	/**
	 * @Assert\File(maxSize="6000000")
	 */
	protected $file;

	protected $tempFilename;

	protected $fichierThumbExt;

	protected $aFileName;


	public function __construct() {
		parent::__construct();
		$this->fichierNom = null;
		$this->tempFileName = null;
		$this->thumbFichierNom = null;
		$this->fichierThumbExt = 'png';
		$this->aFileName = null;
		$this->tempFilename = array();
		// initialisation du nom du fichier
		$this->getAFileName();
	}

	/**
	 * Renvoie true si la demande correspond correspond
	 * ex. : pour l'entité "baseL0_entity" -> "isBaseL0_entity" renvoie true
	 * @return boolean
	 */
	public function __call($name, $arguments = null) {
		switch ($name) {
			case 'is'.ucfirst($this->getName()):
				$reponse = true;
				break;
			default:
				$reponse = false;
				break;
		}
		return $reponse;
	}

	/**
	 * Renvoie le nom de l'entité parent
	 * @return string
	 */
	public function getParentName() {
		return parent::getName();
	}

	/**
	 * Renvoie le nom de l'entité
	 * @return string
	 */
	public function getName() {
		return 'base_type';
	}

	/**
	 * Complète les données avant enregistrement
	 * @ORM/PreUpdate
	 * @ORM/PrePersist
	 * @return boolean
	 */
	public function verifBase_entity_pdf() {
		$verif = true;
		$verifMethod = 'verif'.ucfirst($this->getParentName());
		if(method_exists($this, $verifMethod)) {
			// opérations parents
			$verif = $this->$verifMethod();
		}
		if($verif === true) {
			// opérations pour cette entité
			// …
		}
		return $verif;
	}


	/**
	 * @Assert/True(message = "Cette entité n'est pas valide.")
	 * @return boolean
	 */
	public function isBase_entity_pdfValid() {
		$valid = true;
		$validMethod = 'is'.ucfirst($this->getParentName()).'Valid';
		if(method_exists($this, $validMethod)) {
			$valid = $this->$validMethod();
		}
		// autres vérifications, si le parent est valide…
		if($valid === true) {
			//
		}

		return $valid;
	}


	/**
	 * initialisation du nom du fichier
	 * @param string $ext - extentions du fichier ("pdf" par défaut)
	 * @return string
	 */
	private function getAFileName($ext = "pdf", $force = false) {
		if(($this->aFileName === null) || ($force === true)) {
			$date = new Datetime();
			$this->aFileName = md5(rand(100000, 999999))."-".$date->getTimestamp();
		}
		return $this->aFileName.".".$ext;
	}

	public function setFile(UploadedFile $file) {
		$this->file = $file;
		$this->fichierExt = $this->file->guessExtension();
		if($this->fichierNom !== null) {
			// un fichier existe déjà
			$this->tempFilename['pdf'] = $this->getFichierNom();
			$this->tempFilename['png'] = $this->getThumbFichierNom();
			// nouveau $nom fichier PDF
			$this->setFichierNom($this->getAFileName($this->fichierExt));
			// thumb
			$this->setThumbFichierNom($this->getAFileName($this->fichierThumbExt));
		}
	}

	/**
	 * Génération et enregistrement du thumb, au format PNG
	 * @return boolean
	 */
	public function createThumb() {
		$newPDF = $this->getUploadRootDir().$this->getFichierNom();
		if(file_exists($newPDF) && (class_exists('Imagick'))) {
			// si le fichier PDF existe, bien sûr…
			$image = new Imagick($newPDF);
			$count = $image->getNumberImages();
			$image->thumbnailImage(400);
			$image->setCompression(Imagick::COMPRESSION_LZW);
			$image->setCompressionQuality(90);
			$image->writeImage($this->getUploadRootDir().$this->getThumbFichierNom());
		}
	}

	/**
	 * Vérifie si un thumb existe (PNG)
	 * @return boolean
	 */
	public function hasThumb() {
		// $fnom = $this->getThumbFichierNom();
		// if(file_exists($this->getUploadRootDir().$fnom) && ($fnom."" !== "")) return true;
		$fnom = $this->getUploadRootDir().$this->getThumbFichierNom();
		if(file_exists($fnom) && is_file($fnom)) return true;
			else return false;
	}

	/**
	 * @ORM\PrePersist()
	 * @ORM\PreUpdate()
	 */
	public function preUpload() {
		if($this->file === null) return;
		$this->fichierExt = $this->file->guessExtension();
		$this->fichierOrigine = $this->file->getClientOriginalName();
		$this->tailleMo = filesize($this->file);
		// nom fichier PDF
		$this->setFichierNom($this->getAFileName($this->fichierExt));
		// thumb
		$this->setThumbFichierNom($this->getAFileName($this->fichierThumbExt));
	}

	/**
	 * @ORM\PostPersist()
	 * @ORM\PostUpdate()
	 */
	public function upload() {
		if($this->file === null) return;
		if(count($this->tempFilename) > 0) {
			foreach($this->tempFilename as $fileNom) if((trim($fileNom)."") !== "") {
				$oldFile = $this->getUploadRootDir().$fileNom;
				if(file_exists($oldFile)) unlink($oldFile);
			}
		}
		$this->file->move(
			$this->getUploadRootDir(),
			$this->fichierNom
		);
		// création du thumb
		$this->createThumb();
	}

	/**
	 * @ORM\PreRemove()
	 */
	public function preRemoveUpload() {
		$this->tempFilename['pdf'] = $this->getUploadRootDir().$this->getFichierNom();
		$this->tempFilename['png'] = $this->getUploadRootDir().$this->getThumbFichierNom();
	}

	/**
	 * @ORM\PostRemove()
	 */
	public function removeUpload() {
		foreach($this->tempFilename as $tempFilename) {
			$oldFile = $this->getUploadRootDir().$tempFilename;
			if(file_exists($oldFile)) unlink($oldFile);
		}
	}

	protected function getUploadDir() {
		return "images/pdf/";
	}
	protected function getUploadRootDir() {
		return __DIR__.'/../../../../../../../web/'.$this->getUploadDir();
	}

	public function getWebPath() {
		return $this->getUploadDir().$this->getFichierNom();
	}
	public function getPdfWebPath() {
		return $this->getUploadDir().$this->getFichierNom();
	}
	public function getThumbWebPath() {
		return $this->getUploadDir().$this->getThumbFichierNom();
	}
	public function getPngWebPath() {
		return $this->getUploadDir().$this->getThumbFichierNom();
	}

	/**
	 * Get file
	 *
	 * @return integer 
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * Set fichierOrigine
	 *
	 * @param string $fichierOrigine
	 * @return base_entity_pdf
	 */
	public function setFichierOrigine($fichierOrigine) {
		$this->fichierOrigine = $fichierOrigine;
	
		return $this;
	}

	/**
	 * Get fichierOrigine
	 *
	 * @return string 
	 */
	public function getFichierOrigine() {
		return $this->fichierOrigine;
	}

	/**
	 * Set fichierNom
	 *
	 * @param string $fichierNom
	 * @return base_entity_pdf
	 */
	public function setFichierNom($fichierNom) {
		$this->fichierNom = $fichierNom;
		if($this->getNom() === null) $this->setNom($this->fichierNom);
	
		return $this;
	}

	/**
	 * Get fichierNom
	 *
	 * @return string 
	 */
	public function getFichierNom() {
		return $this->fichierNom;
	}

	/**
	 * Set thumbFichierNom
	 *
	 * @param string $thumbFichierNom
	 * @return base_entity_pdf
	 */
	public function setThumbFichierNom($thumbFichierNom = null) {
		$this->thumbFichierNom = $thumbFichierNom;
	
		return $this;
	}

	/**
	 * Get thumbFichierNom
	 *
	 * @return string 
	 */
	public function getThumbFichierNom() {
		return $this->thumbFichierNom;
	}

	/**
	 * Set tailleMo
	 *
	 * @param integer $tailleMo
	 * @return base_entity_pdf
	 */
	public function setTailleMo($tailleMo) {
		$this->tailleMo = $tailleMo;
	
		return $this;
	}

	/**
	 * Get tailleMo
	 *
	 * @return integer 
	 */
	public function getTailleMo() {
		return $this->tailleMo;
	}


}