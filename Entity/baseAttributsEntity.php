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
use laboBundle\Entity\baseL1Entity;
use laboBundle\Entity\interfaces\baseAttributsInterface;

/**
 * Entité de base L0 étendue => L1 pour gestion de dates (création / modification / expiration)
 * 
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
abstract class baseAttributsEntity extends baseL1Entity implements baseAttributsInterface {

	/**
	 * @var array
	 * @ORM\Column(name="attributs", type="array", nullable=true, unique=false)
	 */
	protected $attributs;


	public function __construct() {
		parent::__construct();
		$this->attributs = new ArrayCollection;
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

// FIN --------------------- à inclure dans toutes les entités ------------------------

	/**
	 * Ajoute un attribut
	 * @param string $nom
	 * @param misex $valeur
	 * @return categorie
	 */
	public function addAttribut($nom, $valeur) {
		$this->attributs->set($nom, $valeur);
		return $this;
	}

	/**
	 * Récupère un attribut
	 * @param string $nom
	 * @return mixed
	 */
	public function getAttribut($nom) {
		return $this->attributs->get($nom);
	}

	/**
	 * Efface un attribut
	 * @param string $nom
	 * @return mixed
	 */
	public function removeAttribut($nom) {
		return $this->attributs->remove($nom);
	}

	/**
	 * Efface tous les attributs
	 * @return categorie
	 */
	public function removeAttributs() {
		$this->attributs->clear();
		return $this;
	}

	/**
	 * Renvoie true si un attribut existe
	 * @param string $nom
	 * @return boolean
	 */
	public function existsAttribut($nom) {
		// return in_array($nom, $this->attributs->getKeys());
		return $this->attributs->containsKey($nom);
	}

	/**
	 * Récupère tous les attributs
	 * @return arrayCollection
	 */
	public function getAttributs() {
		return $this->attributs;
	}

	/**
	 * Récupère tous les attributs sous forme de tableau
	 * @return array
	 */
	public function getAttributsToArray() {
		return $this->attributs->toArray();
	}



}












