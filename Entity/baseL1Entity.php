<?php

namespace laboBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use \DateTime;
use \Exception;
// Slug
use Gedmo\Mapping\Annotation as Gedmo;
// baseInterface
use laboBundle\Entity\baseL0Entity;
use laboBundle\Entity\interfaces\baseL1Interface;

/**
 * Entité de base L0 étendue => L1 pour gestion de dates (création / modification / expiration)
 * 
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
abstract class baseL1Entity extends baseL0Entity implements baseL1Interface {

	/**
	 * @var DateTime
	 *
	 * @ORM\Column(name="dateCreation", type="datetime", nullable=false, unique=false)
	 */
	protected $dateCreation;

	/**
	 * @var DateTime
	 *
	 * @ORM\Column(name="dateMaj", type="datetime", nullable=true, unique=false)
	 */
	protected $dateMaj;

	/**
	 * @var DateTime
	 *
	 * @ORM\Column(name="dateExpiration", type="datetime", nullable=true, unique=false)
	 */
	protected $dateExpiration;



	public function __construct() {
		parent::__construct();
	}

// DEBUT --------------------- à inclure dans toutes les entités ------------------------

	/**
	 * Renvoie true si l'entité est valide
	 * @return boolean
	 */
	public function isValid() {
		$valid = true;
		$valid = parent::isValid();
		if($valid === true) {
			// opérations pour cette entité
			// …
		}
		return $valid;
	}

	/**
	 * Complète les données avant enregistrement
	 * @return boolean
	 */
	public function verify() {
		$verif = true;
		$verif = parent::verify();
		if($verif === true) {
			// opérations pour cette entité
			// …
		}
		return $verif;
	}

	public function __call($method, $args) {
		switch ($method) {
			case 'isBaseL1Entity':
				return true;
				break;
			default:
				return parent::__call($method, $args);
				break;
		}
	}

// FIN --------------------- à inclure dans toutes les entités ------------------------

	/**
	 * Set dateCreation
	 *
	 * @param DateTime $dateCreation
	 * @return baseL1Entity
	 */
	public function setDateCreation(DateTime $dateCreation) {
		$this->dateCreation = $dateCreation;
	
		return $this;
	}

	/**
	 * Get dateCreation
	 *
	 * @return DateTime
	 */
	public function getDateCreation() {
		return $this->dateCreation;
	}

    /**
     * @ORM\PreUpdate
     */
    public function updateDateMaj() {
        $this->setDateMaj(new DateTime());
    }

	/**
	 * Set dateMaj
	 *
	 * @param DateTime $dateMaj
	 * @return baseL1Entity
	 */
	public function setDateMaj(DateTime $dateMaj = null) {
		$this->dateMaj = $dateMaj;
	
		return $this;
	}

	/**
	 * Get dateMaj
	 *
	 * @return DateTime
	 */
	public function getDateMaj() {
		return $this->dateMaj;
	}

	/**
	 * Set dateExpiration
	 *
	 * @param DateTime $dateExpiration
	 * @return baseL1Entity
	 */
	public function setDateExpiration(DateTime $dateExpiration = null) {
		$this->dateExpiration = $dateExpiration;
	
		return $this;
	}

	/**
	 * Get dateExpiration
	 *
	 * @return DateTime 
	 */
	public function getDateExpiration() {
		return $this->dateExpiration;
	}



}












