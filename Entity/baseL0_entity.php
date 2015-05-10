<?php

namespace labo\Bundle\TestmanuBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use \DateTime;
// Slug
use Gedmo\Mapping\Annotation as Gedmo;
// aeReponse
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
abstract class baseL0_entity {

	/**
	 * @var integer
	 *
	 * @ORM\Id
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="nom", type="string", length=100, nullable=false, unique=false)
	 * @Assert\NotBlank(message = "Vous devez donner un nom.")
	 * @Assert\Length(
	 *      min = "3",
	 *      max = "100",
	 *      minMessage = "Le nom doit comporter au moins {{ limit }} lettres.",
	 *      maxMessage = "Le nom doit comporter au maximum {{ limit }} lettres."
	 * )
	 */
	protected $nom;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="descriptif", type="text", nullable=true, unique=false)
	 */
	protected $descriptif;

	/**
	 * @var DateTime
	 *
	 * @ORM\Column(name="dateCreation", type="datetime", nullable=false)
	 */
	protected $dateCreation;

	/**
	 * @var DateTime
	 *
	 * @ORM\Column(name="dateMaj", type="datetime", nullable=true)
	 */
	protected $dateMaj;

	/**
	 * @var DateTime
	 *
	 * @ORM\Column(name="dateExpiration", type="datetime", nullable=true)
	 */
	protected $dateExpiration;

	/**
	 * @Gedmo\Slug(fields={"nom"})
	 * @ORM\Column(length=128, unique=true)
	 */
	protected $slug;

	/**
	 * @var array
	 * @ORM/Column(name="thisreads", type="array", nullable=true, unique=false)
	 */
	protected $thisreads;
	/**
	 * @var array
	 * @ORM/Column(name="thiswrites", type="array", nullable=true, unique=false)
	 */
	protected $thiswrites;
	/**
	 * @var array
	 * @ORM/Column(name="thisdeletes", type="array", nullable=true, unique=false)
	 */
	protected $thisdeletes;


	public function __construct() {
		$this->dateCreation = new DateTime();
		$this->dateMaj = null;
		$this->dateExpiration = null;
		// droits
		$this->thisreads = new ArrayCollection;
		$this->thiswrites = new ArrayCollection;
		$this->thisdeletes = new ArrayCollection;
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
	 * Renvoie le nom de l'entité
	 * @return string
	 */
	public function getName() {
		return 'baseL0_entity';
	}

	/**
	 * @Assert/True(message = "Cette entité n'est pas valide.")
	 * @return boolean
	 */
	public function isBaseL0_entityValid() {
		return true;
	}

	/**
	 * Complète les données avant enregistrement
	 * @return boolean
	 */
	public function verifBaseL0_entity() {
		return true;
	}

	/**
	 * Renvoie une représentation texte de l'objet.
	 * @return string
	 */
	public function __toString() {
		return __CLASS__ . '@' . spl_object_hash($this);
	}




	/**
	 * Get id
	 *
	 * @return integer 
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set nom
	 *
	 * @param string $nom
	 * @return baseL0_entity
	 */
	public function setNom($nom) {
		$this->nom = $nom;
	
		return $this;
	}

	/**
	 * Get nom
	 *
	 * @return string 
	 */
	public function getNom() {
		return $this->nom;
	}

	/**
	 * Set descriptif
	 *
	 * @param string $descriptif
	 * @return baseL0_entity
	 */
	public function setDescriptif($descriptif = null) {
		$this->descriptif = $descriptif;
	
		return $this;
	}

	/**
	 * Get descriptif
	 *
	 * @return string 
	 */
	public function getDescriptif() {
		return $this->descriptif;
	}

	/**
	 * Set dateCreation
	 *
	 * @param DateTime $dateCreation
	 * @return baseL0_entity
	 */
	public function setDateCreation($dateCreation) {
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
        $this->setDateMaj(new \Datetime());
    }

	/**
	 * Set dateMaj
	 *
	 * @param DateTime $dateMaj
	 * @return baseL0_entity
	 */
	public function setDateMaj($dateMaj = null) {
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
	 * @return baseL0_entity
	 */
	public function setDateExpiration($dateExpiration = null) {
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

	/**
	 * Set slug
	 *
	 * @param integer $slug
	 * @return baseL0_entity
	 */
	public function setSlug($slug) {
		$this->slug = $slug;
		return $this;
	}

	/**
	 * Get slug
	 *
	 * @return string
	 */
	public function getSlug() {
		return $this->slug;
	}


	//// DROITS : LECTURE / ÉCRITURE / EFFACEMENT /////

	/**
	 * Autorise les droit à $role : lecture sur cette entité
	 * @param string $role
	 * @return baseL0_entity
	 */
	public function addThisread($role = null) {
		if($role !== null) ($this->thisreads[] = $role);
		return $this;
	}

	/**
	 * Supprime les droit à $role : lecture sur cette entité
	 * @param string $role
	 * @return baseL0_entity
	 */
	public function removeThisread($role) {
		$this->thisreads->removeElement($role);
		return $this;
	}

	/**
	 * Renvoie les rôles ayant-droits : lecture sur cette entité
	 * @return ArrayCollection
	 */
	public function getThisreads() {
		return $this->thisreads;
	}

	/**
	 * Autorise les droit à $role : écriture sur cette entité
	 * @param string $role
	 * @return baseL0_entity
	 */
	public function addThiswrite($role = null) {
		if($role !== null) ($this->thiswrites[] = $role);
		return $this;
	}

	/**
	 * Supprime les droit à $role : écriture sur cette entité
	 * @param string $role
	 * @return baseL0_entity
	 */
	public function removeThiswrite($role) {
		$this->thiswrites->removeElement($role);
		return $this;
	}

	/**
	 * Renvoie les rôles ayant-droits : écriture sur cette entité
	 * @return ArrayCollection
	 */
	public function getThiswrites() {
		return $this->thiswrites;
	}

	/**
	 * Autorise les droit à $role : effacement sur cette entité
	 * @param string $role
	 * @return baseL0_entity
	 */
	public function addThisdelete($role = null) {
		if($role !== null) ($this->thisdeletes[] = $role);
		return $this;
	}

	/**
	 * Supprime les droit à $role : effacement sur cette entité
	 * @param string $role
	 * @return baseL0_entity
	 */
	public function removeThisdelete($role) {
		$this->thisdeletes->removeElement($role);
		return $this;
	}

	/**
	 * Renvoie les rôles ayant-droits : effacement sur cette entité
	 * @return ArrayCollection
	 */
	public function getThisdeletes() {
		return $this->thisdeletes;
	}

}












