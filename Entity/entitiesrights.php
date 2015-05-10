<?php

namespace labo\Bundle\TestmanuBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
// Slug
use Gedmo\Mapping\Annotation as Gedmo;
// Repositories
use labo\Bundle\TestmanuBundle\Entity\versionRepository;
// Entities
use labo\Bundle\TestmanuBundle\Entity\baseL0_entity;
use labo\Bundle\TestmanuBundle\Entity\version;
// aeReponse
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

/**
 * entitiesrights
 *
 * @ORM\Entity
 * @ORM\Table(name="entitiesrights")
 * @ORM\Entity(repositoryClass="labo\Bundle\TestmanuBundle\Entity\entitiesrightsRepository")
 * @UniqueEntity(fields={"nom"}, message="Cette entitiesrights existe déjà")
 */
class entitiesrights extends baseL0_entity {

	/**
	 * @var integer
	 *
	 * @ORM\ManyToOne(targetEntity="labo\Bundle\TestmanuBundle\Entity\version")
	 * @ORM\JoinColumn(nullable=false, unique=false)
	 */
	protected $version;

	/**
	 * @var array
	 * @ORM/Column(name="globalreads", type="array", nullable=true, unique=false)
	 */
	protected $globalreads;
	/**
	 * @var array
	 * @ORM/Column(name="globalwrites", type="array", nullable=true, unique=false)
	 */
	protected $globalwrites;
	/**
	 * @var array
	 * @ORM/Column(name="globaldeletes", type="array", nullable=true, unique=false)
	 */
	protected $globaldeletes;

	public function __construct() {
		parent::__construct();
		$this->globalreads = new ArrayCollection;
		$this->globalwrites = new ArrayCollection;
		$this->globaldeletes = new ArrayCollection;
		// version
		$version = new versionRepository()->defaultVersion();
			if(is_array($version)) $version = reset($version);
			if($version instanceOf version) $this->setVersion($version);
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
		return 'entitiesrights';
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

	/**
	 * Set version
	 *
	 * @param version $version
	 * @return entitiesrights
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


	//// ENTITÉ GLOBALE /////

	/**
	 * Autorise les droit à $role : lecture sur cette entité GLOBALE
	 * @param string $role
	 * @return entitiesrights
	 */
	public function addGlobalread($role = null) {
		if($role !== null) ($this->globalreads[] = $role);
		return $this;
	}

	/**
	 * Supprime les droit à $role : lecture sur cette entité GLOBALE
	 * @param string $role
	 * @return entitiesrights
	 */
	public function removeGlobalread($role) {
		$this->globalreads->removeElement($role);
		return $this;
	}

	/**
	 * Renvoie les rôles ayant-droits : lecture sur cette entité GLOBALE
	 * @return ArrayCollection
	 */
	public function getGlobalreads() {
		return $this->globalreads;
	}

	/**
	 * Autorise les droit à $role : écriture sur cette entité GLOBALE
	 * @param string $role
	 * @return entitiesrights
	 */
	public function addGlobalwrite($role = null) {
		if($role !== null) ($this->globalwrites[] = $role);
		return $this;
	}

	/**
	 * Supprime les droit à $role : écriture sur cette entité GLOBALE
	 * @param string $role
	 * @return entitiesrights
	 */
	public function removeGlobalwrite($role) {
		$this->globalwrites->removeElement($role);
		return $this;
	}

	/**
	 * Renvoie les rôles ayant-droits : écriture sur cette entité GLOBALE
	 * @return ArrayCollection
	 */
	public function getGlobalwrites() {
		return $this->globalwrites;
	}

	/**
	 * Autorise les droit à $role : effacement sur cette entité GLOBALE
	 * @param string $role
	 * @return entitiesrights
	 */
	public function addGlobaldelete($role = null) {
		if($role !== null) ($this->globaldeletes[] = $role);
		return $this;
	}

	/**
	 * Supprime les droit à $role : effacement sur cette entité GLOBALE
	 * @param string $role
	 * @return entitiesrights
	 */
	public function removeGlobaldelete($role) {
		$this->globaldeletes->removeElement($role);
		return $this;
	}

	/**
	 * Renvoie les rôles ayant-droits : effacement sur cette entité GLOBALE
	 * @return ArrayCollection
	 */
	public function getGlobaldeletes() {
		return $this->globaldeletes;
	}


}