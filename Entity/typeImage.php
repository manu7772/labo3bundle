<?php

namespace labo\Bundle\TestmanuBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
// Slug
use Gedmo\Mapping\Annotation as Gedmo;
use labo\Bundle\TestmanuBundle\Entity\base_type;
// Repositories
use labo\Bundle\TestmanuBundle\Entity\typeImageRepository;
// aeReponse
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

/**
 * typeImage
 *
 * @ORM\Entity
 * @ORM\Table(name="typeImage")
 * @ORM\Entity(repositoryClass="labo\Bundle\TestmanuBundle\Entity\typeImageRepository")
 * @UniqueEntity(fields={"nom"}, message="Ce type d'image existe déjà")
 */
class typeImage extends base_type {

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
		return 'typeImage';
	}

	/**
	 * @ORM/PreUpdate
	 * @ORM/PrePersist
	 */
	public function verifTypeImage() {
		$verifMethod = 'verif'.ucfirst($this->getParentName());
		if(method_exists($this, $verifMethod)) {
			$this->$verifMethod();
		}
		$this->defineNomCourt();
	}

	/**
	 * @Assert/True(message = "Ce type d'image n'est pas valide.")
	 */
	public function isTypeImageValid() {
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
