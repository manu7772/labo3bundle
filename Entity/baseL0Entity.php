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
use laboBundle\Entity\interfaces\baseL0Interface;

/**
 * Entité de base L0
 * 
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
abstract class baseL0Entity implements baseL0Interface {

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
	 * @ORM\Column(name="uniquefield", type="text", nullable=true, unique=false)
	 */
	protected $uniquefield;

	/**
	 * @var string
	 * @ORM\Column(name="nom", type="string", length=128, nullable=false, unique=false)
	 * @Assert\NotBlank(message = "Vous devez donner un nom.")
	 * @Assert\Length(
	 *      min = "3",
	 *      max = "128",
	 *      minMessage = "Le nom doit comporter au moins {{ limit }} lettres.",
	 *      maxMessage = "Le nom doit comporter au maximum {{ limit }} lettres."
	 * )
	 */
	protected $nom;

	/**
	 * @var string
	 * @ORM\Column(name="cible", type="string", length=128, nullable=true, unique=false)
	 */
	protected $cible;

	/**
	 * @var string
	 * @ORM\Column(name="descriptif", type="text", nullable=true, unique=false)
	 */
	protected $descriptif;

	/**
	 * @Gedmo\Slug(fields={"nom"})
	 * @ORM\Column(length=128, unique=true)
	 */
	protected $slug;

	/**
	 * @var array
	 * @ORM\Column(name="thisreads", type="array", nullable=true, unique=false)
	 */
	protected $thisreads;
	/**
	 * @var array
	 * @ORM\Column(name="thiswrites", type="array", nullable=true, unique=false)
	 */
	protected $thiswrites;
	/**
	 * @var array
	 * @ORM\Column(name="thisdeletes", type="array", nullable=true, unique=false)
	 */
	protected $thisdeletes;

	protected $numberReplaces;
	protected $numberReplacesBy;
	protected $numberSpace;

	public function __construct() {
		$this->dateCreation = new DateTime();
		$this->dateMaj = null;
		$this->dateExpiration = null;
		// field unique
		$this->uniquefield = json_encode(array());
		// droits
		$this->thisreads = new ArrayCollection;
		$this->thiswrites = new ArrayCollection;
		$this->thisdeletes = new ArrayCollection;

		$this->numberReplaces = array('.', '-', ' ', self::SLASH, self::ASLASH);
		$this->numberReplacesBy = array('');
		$this->numberSpace = ' ';
	}



// DEBUT --------------------- réservé exclusivement à cette classe abstraite ------------------------

	/**
	 * Renvoie le namespace de l'entité parent
	 * @return string
	 */
	public function getParentClassName() {
		return get_parent_class();
	}

	/**
	 * Renvoie le nom court de l'entité parent
	 * @return string
	 */
	public function getParentShortName() {
		return $this->getSimpleNameFromString($this->getParentClassName());
	}

	/**
	 * Renvoie le namespace de l'entité
	 * @return string
	 */
	public function getClassName() {
		return get_called_class();
	}

	/**
	 * Renvoie le nom court de l'entité
	 * @return string
	 */
	public function getName() {
		return $this->getSimpleNameFromString($this->getClassName());
	}

	/**
	 * Renvoie le nom court de l'entité
	 * @param string $longName
	 * @return string
	 */
	public function getSimpleNameFromString($longName) {
		if($longName === false) return $longName;
		$longName = explode(self::ASLASH, $longName);
		return end($longName);
	}

	/**
	 * Renvoie true si l'entité est valide
	 * @return boolean
	 */
	public function isValid() {
		$valid = true;
		// $valid = parent::isValid();
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
		// opérations pour cette entité
		// …
		if(is_string($this->getSlug())) $this->addToUniqueField('slug', $this->getSlug());
		return $verif;
	}

	// /**
	//  * Renvoie une représentation texte de l'objet.
	//  * @return string
	//  */
	// public function __toString() {
	// 	return __CLASS__.'@'.spl_object_hash($this);
	// }


	public function __call($method, $args) {
		switch ($method) {
			case 'isBaseL0Entity':
				return true;
				break;
			default:
				return false;
				break;
		}
	}

	///**
	//  * Renvoie le nom de l'entité -> SLUG
	//  * @return string
	//  */
	// public function __toString() {
	// 	return $this->getSlug();
	// }

// FONCTIONNALITÉS ---------------------

	protected function normalizeCp($cp = null) {
		if($cp !== null) $cp = str_replace($this->numberReplaces, $this->numberReplacesBy, $cp);
		return $cp;
	}

// FIN --------------------- réservé exclusivement à cette classe abstraite ------------------------



	/**
	 * Get id
	 * @return integer 
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set uniquefield
	 * @param string $uniquefield
	 * @return baseL0Entity
	 */
	public function setUniquefield($uniquefield) {
		throw new Exception("NE PAS UTILISER CE CHAMP !!!");
		// $this->uniquefield = $uniquefield;
		return $this;
	}

	/**
	 * Get uniquefield
	 * @return string 
	 */
	public function getUniquefield() {
		return $this->uniquefield;
	}

	/**
	 * ajoute un champ au champ global pour UniqueEntity
	 * @param string $key - clé
	 * @param string $value - valeur
	 * @return baseL0Entity
	 */
	public function addToUniqueField($key, $value) {
		if(is_string($key) && is_string($value)) {
			$field = json_decode($this->uniquefield);
			if(!is_array($field)) $field = array();
			$field[$key] = $value;
			$this->uniquefield = json_encode($field);
		} else {
			throw new Exception("addToUniqueField : les deux paramètres doivent être des textes !");			
		}
		return $this;
	}

	/**
	 * ajoute un champ au champ global pour UniqueEntity
	 * @param string $key - clé
	 * @param string $value - valeur
	 * @return baseL0Entity
	 */
	public function removeFromUniqueField($key) {
		if(is_string($key)) {
			$field = json_decode($this->uniquefield);
			if(array_key_exists($key, $field)) {
				unset($field[$key]);
			}
			$this->uniquefield = json_encode($field);
		} else {
			throw new Exception("removeFromUniqueField : le paramètre doit être un texte !");			
		}
		return $this;
	}

	/**
	 * Set nom
	 * @param string $nom
	 * @return baseL0Entity
	 */
	public function setNom($nom) {
		$this->nom = $nom;
		return $this;
	}

	/**
	 * Get nom
	 * @return string 
	 */
	public function getNom() {
		return $this->nom;
	}

	/**
	 * Set cible
	 * @param string $cible
	 * @return baseL0Entity
	 */
	public function setCible($cible) {
		$this->cible = $cible;
		return $this;
	}

	/**
	 * Get cible
	 * @return string 
	 */
	public function getCible() {
		return $this->cible;
	}

	/**
	 * Set descriptif
	 * @param string $descriptif
	 * @return baseL0Entity
	 */
	public function setDescriptif($descriptif = null) {
		$this->descriptif = $descriptif;
		return $this;
	}

	/**
	 * Get descriptif
	 * @return string 
	 */
	public function getDescriptif() {
		return $this->descriptif;
	}

	/**
	 * Set slug
	 * @param integer $slug
	 * @return baseL0Entity
	 */
	public function setSlug($slug) {
		$this->slug = $slug;
		return $this;
	}

	/**
	 * Get slug
	 * @return string
	 */
	public function getSlug() {
		return $this->slug;
	}


	//// DROITS : LECTURE / ÉCRITURE / EFFACEMENT /////

	/**
	 * Autorise les droit à $role : lecture sur cette entité
	 * @param string $role
	 * @return baseL0Entity
	 */
	public function addThisread($role = null) {
		if($role !== null) ($this->thisreads[] = $role);
		return $this;
	}

	/**
	 * Supprime les droit à $role : lecture sur cette entité
	 * @param string $role
	 * @return baseL0Entity
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
	 * @return baseL0Entity
	 */
	public function addThiswrite($role = null) {
		if($role !== null) ($this->thiswrites[] = $role);
		return $this;
	}

	/**
	 * Supprime les droit à $role : écriture sur cette entité
	 * @param string $role
	 * @return baseL0Entity
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
	 * @return baseL0Entity
	 */
	public function addThisdelete($role = null) {
		if($role !== null) ($this->thisdeletes[] = $role);
		return $this;
	}

	/**
	 * Supprime les droit à $role : effacement sur cette entité
	 * @param string $role
	 * @return baseL0Entity
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












