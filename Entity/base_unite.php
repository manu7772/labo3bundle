<?php

namespace labo\Bundle\TestmanuBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
// Slug
use Gedmo\Mapping\Annotation as Gedmo;
// Base
use labo\Bundle\TestmanuBundle\Entity\baseL1_entity;
// aeReponse
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

/**
 * @ORM\MappedSuperclass
 */
abstract class base_unite extends baseL1_entity {

	/**
	 * @var string
	 *
	 * @ORM\Column(name="nomcourt", type="string", length=3, nullable=true, unique=false)
	 * @Assert\NotBlank(message = "Vous devez remplir ce champ.")
	 * @Assert\Length(
	 *      min = "1",
	 *      max = "3",
	 *      minMessage = "Le nom court doit comporter au moins {{ limit }} lettres.",
	 *      maxMessage = "Le nom court doit comporter au maximum {{ limit }} lettres."
	 * )
	 */
	protected $nomcourt;

	// nombre de lettre max pour $nomcourt
	protected $lengthNomCourt;


	public function __construct() {
		parent::__construct();
		$this->lengthNomCourt = 3;
		$this->nomcourt = null;
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
		return 'base_unite';
	}

	/**
	 * Complète les données avant enregistrement
	 * @ORM/PreUpdate
	 * @ORM/PrePersist
	 * @return boolean
	 */
	public function verifBase_unite() {
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
	public function isBase_uniteValid() {
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
	 * Redéfinit le nom court de l'entité
	 * @return base_unite
	 */
	public function defineNomCourt() {
		if($this->nomcourt === null || strlen(trim($this->nomcourt)) < 1) {
			$this->nomcourt = substr($this->nom, 0, $this->lengthNomCourt);
		}
		return $this;
	}

	/**
	 * Set nomcourt
	 *
	 * @param string $nomcourt
	 * @return base_unite
	 */
	public function setNomcourt($nomcourt = null) {
		if(is_string($nomcourt)) {
			if (strlen(trim($nomcourt)) > 0) {
				$this->nomcourt = substr(trim($nomcourt), 0, $this->lengthNomCourt);
			} else $this->nomcourt = null;
		} else $this->nomcourt = null;
	
		return $this;
	}

	/**
	 * Get nomcourt
	 *
	 * @return string 
	 */
	public function getNomcourt() {
		return $this->nomcourt;
	}



}