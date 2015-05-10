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
 * @UniqueEntity(fields={"nomcourt"}, message="Ce nom abrégé existe déjà.")
 */
abstract class base_type extends baseL1_entity {


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
		return 'base_type';
	}

	/**
	 * Complète les données avant enregistrement
	 * @ORM/PreUpdate
	 * @ORM/PrePersist
	 * @return boolean
	 */
	public function verifBase_type() {
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
	public function isBase_typeValid() {
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
