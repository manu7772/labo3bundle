<?php

namespace laboBundle\Entity;

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
use laboBundle\Entity\baseL1Entity;
// Entities
use laboBundle\Entity\typeImage;
// Repositories
use laboBundle\Entity\typeImageRepository;
// aeReponse
use laboBundle\services\aetools\aeReponse;

/**
 * @ORM\MappedSuperclass
 */
abstract class baseEntityImage extends baseL1Entity {

	/**
	 * @var string
	 * @ORM\Column(name="url", type="string", length=255, nullable=true, unique=false)
	 */
	protected $url;

	/**
	 * @var integer
	 * @ORM\ManyToMany(targetEntity="laboBundle\Entity\typeImage")
	 * @ORM\JoinColumn(nullable=false, unique=false)
	 */
	protected $typeImages;

	/**
	 * @var string
	 * @ORM\Column(name="fichierOrigine", type="string", length=200, nullable=true, unique=false)
	 */
	protected $fichierOrigine;

	/**
	 * @var string
	 * @ORM\Column(name="fichierNom", type="string", length=200, nullable=true, unique=false)
	 */
	protected $fichierNom;

	/**
	 * @var integer
	 * @ORM\Column(name="tailleX", type="integer", nullable=true, unique=false)
	 */
	protected $tailleX;

	/**
	 * @var integer
	 * @ORM\Column(name="tailleY", type="integer", nullable=true, unique=false)
	 */
	protected $tailleY;

	/**
	 * @var integer
	 * @ORM\Column(name="tailleMo", type="integer", nullable=true, unique=false)
	 */
	protected $tailleMo;

	/**
	 * @Assert\File(maxSize="6000000")
	 */
	protected $file;

	/**
	 * @var string
	 * @ORM\Column(name="alt", type="string", length=64, nullable=true, unique=false)
	 */
	protected $alt;

	protected $tempFileName;
	protected $ext;
	// Eléments de formulaire
	protected $remove;

	public function __construct() {
		parent::__construct();
		$this->alt = "image";
		$this->fichierNom = "";
		$this->typeImages = new ArrayCollection();
		$this->tempFileName = null;
		$this->fichierOrigine = null;
		$this->ext = null;
		$this->remove = false; // pour effacer l'image
		// typeImage
		$typeImageRepository = new typeImageRepository();
			$typeImages = $typeImageRepository->defaultVal();
			if($typeImages instanceOf typeImage) $typeImages = array($typeImages);
			foreach ($typeImages as $typeImage) $this->addTypeImage($typeImage);
	}

	/**
	 * Renvoie true si la demande correspond correspond
	 * ex. : pour l'entité "baseL0Entity" -> "isbaseL0Entity" renvoie true
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
		return 'baseEntityImage';
	}

	/**
	 * Complète les données avant enregistrement
	 * @ORM/PreUpdate
	 * @ORM/PrePersist
	 * @return boolean
	 */
	public function verifBaseEntityImage() {
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
	public function isBaseEntityImageValid() {
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
	 * Set remove
	 * @param boolean $remove
	 * @return baseEntityImage
	 */
	public function setRemove($remove = false) {
		if(!is_bool($remove)) $remove = false;
		$this->remove = $remove;
		return $this;
	}

	/**
	 * Get remove
	 * @return boolean 
	 */
	public function getRemove() {
		return $this->remove;
	}

	/**
	 * Définit l'url
	 * @param string $url
	 * @return baseEntityImage
	 */
	public function setUrl($url = null) {
		$this->url = $url;
		return $this;
	}

	/**
	 * Renvoie l'url
	 * @return string 
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * Ajoute un type d'image
	 * @param typeImage $typeImages
	 * @return baseEntityImage
	 */
	public function addTypeImage(typeImage $typeImage) {
		$this->typeImages[] = $typeImage;
		$typeImage->addImage($this);
		return $this;
	}

	/**
	 * Supprime un type d'image
	 * @param typeImage $typeImage
	 */
	public function removeTypeImage(typeImage $typeImage) {
		$this->typeImages->removeElement($typeImage);
	}

	/**
	 * Renvoie les types de l'image
	 * @return ArrayCollection 
	 */
	public function getTypeImages() {
		return $this->typeImages;
	}

	/**
	 * Set fichierOrigine
	 * @param string $fichierOrigine
	 * @return baseEntityImage
	 */
	public function setFichierOrigine($fichierOrigine = null) {
		$this->fichierOrigine = $fichierOrigine;
		return $this;
	}

	/**
	 * Get fichierOrigine
	 * @return string 
	 */
	public function getFichierOrigine() {
		return $this->fichierOrigine;
	}

	/**
	 * Set fichierNom
	 * @param string $fichierNom
	 * @return baseEntityImage
	 */
	public function setFichierNom($fichierNom = null) {
		$this->fichierNom = $fichierNom;
		return $this;
	}

	/**
	 * Get fichierNom
	 * @return string 
	 */
	public function getFichierNom() {
		return $this->fichierNom;
	}

	/**
	 * Set tailleX
	 * @param integer $tailleX
	 * @return baseEntityImage
	 */
	public function setTailleX($tailleX) {
		$this->tailleX = $tailleX;
		return $this;
	}

	/**
	 * Get tailleX
	 * @return integer 
	 */
	public function getTailleX() {
		return $this->tailleX;
	}

	/**
	 * Set tailleY
	 * @param integer $tailleY
	 * @return baseEntityImage
	 */
	public function setTailleY($tailleY) {
		$this->tailleY = $tailleY;
	
		return $this;
	}

	/**
	 * Get tailleY
	 * @return integer 
	 */
	public function getTailleY() {
		return $this->tailleY;
	}

	/**
	 * Set tailleMo
	 * @param integer $tailleMo
	 * @return baseEntityImage
	 */
	public function setTailleMo($tailleMo) {
		$this->tailleMo = $tailleMo;
	
		return $this;
	}

	/**
	 * Get tailleMo
	 * @return integer 
	 */
	public function getTailleMo() {
		return $this->tailleMo;
	}

	/**
	 * Set file
	 * @param UploadedFile $file
	 * @return baseEntityImage
	 */
	public function setFile(UploadedFile $file = null) {
		$this->file = $file;
		if(null !== $this->fichierOrigine) {
			$this->tempFileName = $this->fichierOrigine;
			$this->fichierOrigine = null;
		}
		return $this;
	}

	/**
	 * Get file
	 * @return UploadedFile 
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * Set tempFileName
	 * @return baseEntityImage
	 */
	public function setTempFileName($tempFileName = null) {
		$this->tempFileName = $tempFileName;
		return $this;
	}

	/**
	 * Get tempFileName
	 * @return string 
	 */
	public function getTempFileName() {
		return $this->tempFileName;
	}

	/**
	 * Définit l'extension du nom de fichier
	 * @return baseEntityImage
	 */
	public function setExt($ext) {
		$this->ext = $ext;
		return $this;
	}

	/**
	 * Renvoie l'extension du nom de fichier
	 * @return string 
	 */
	public function getExt() {
		return $this->ext;
	}

}