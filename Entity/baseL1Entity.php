<?php

namespace laboBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use \DateTime;
// Slug
use Gedmo\Mapping\Annotation as Gedmo;
use laboBundle\Entity\baseL0Entity;
// Repositories
use laboBundle\Entity\statutRepository;
use laboBundle\Entity\versionRepository;
// Entities
use laboBundle\Entity\statut;
use laboBundle\Entity\version;
// aeReponse
use laboBundle\services\aetools\aeReponse;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
abstract class baseL1Entity extends baseL0Entity {

	/**
	 * @ORM\ManyToOne(targetEntity="laboBundle\Entity\statut")
	 * @ORM\JoinColumn(nullable=false, unique=false)
	 */
	protected $statut;

	/**
	 * @var integer
	 *
	 * @ORM\ManyToOne(targetEntity="laboBundle\Entity\version")
	 * @ORM\JoinColumn(nullable=false, unique=false)
	 */
	protected $version;

	public function __construct() {
		parent::__construct();
		// statut
		$statut = new statutRepository()->defaultVal();
			if(is_array($statut)) $statut = reset($statut);
			if($statut instanceOf statut) $this->setStatut($statut);
		// version
		$version = new versionRepository()->defaultVersion();
			if(is_array($version)) $version = reset($version);
			if($version instanceOf version) $this->setVersion($version);
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
		return 'baseL1Entity';
	}

	/**
	 * @Assert/True(message = "Cette entité n'est pas valide.")
	 * @return boolean
	 */
	public function isBaseL1EntityValid() {
		return true;
	}

	/**
	 * Complète les données avant enregistrement
	 * @ORM/PreUpdate
	 * @ORM/PrePersist
	 */
	public function verifBaseL1Entity() {
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
	 * Set statut
	 * @param statut $statut
	 * @return baseL1Entity
	 */
	public function setStatut(statut $statut) {
		$this->statut = $statut;
	
		return $this;
	}

	/**
	 * Get statut
	 *
	 * @return statut 
	 */
	public function getStatut() {
		return $this->statut;
	}

	/**
	 * Set version
	 *
	 * @param version $version
	 * @return baseL1Entity
	 */
	public function setVersion(version $version) {
		$this->version = $version;
	
		return $this;
	}

	/**
	 * Get version
	 *
	 * @return version 
	 */
	public function getVersion() {
		return $this->version;
	}


}












