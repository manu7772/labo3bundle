<?php

namespace labo\Bundle\TestmanuBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
// Slug
use Gedmo\Mapping\Annotation as Gedmo;
use labo\Bundle\TestmanuBundle\Entity\baseL0_entity;
// aeReponse
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

/**
 * statut
 *
 * @ORM\Entity
 * @ORM\Table(name="statut")
 * @ORM\Entity(repositoryClass="labo\Bundle\TestmanuBundle\Entity\statutRepository")
 * @UniqueEntity(fields={"nom"}, message="Ce statut existe déjà.")
 */
class statut extends baseL0_entity {

	public function __construct() {
		parent::__construct();
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
		return 'statut';
	}

	/**
	 * Complète les données avant enregistrement
	 * @ORM/PreUpdate
	 * @ORM/PrePersist
	 */
	public function verifStatut() {
		$verif = true;
		$verifMethod = 'verif'.ucfirst($this->getParentName());
		if(method_exists($this, $verifMethod)) {
			// opérations parents
			$verif = $this->$verifMethod();
		}
		if($verif === true) {
			// opérations pour cette entité
			$verif = $this->defineNomCourt();
		}
		return $verif;
	}

	/**
	 * @Assert/True(message = "Ce statut n'est pas valide.")
	 */
	public function isStatutValid() {
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

}

