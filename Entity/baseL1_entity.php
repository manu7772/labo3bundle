<?php

namespace labo\Bundle\TestmanuBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use \DateTime;
// Slug
use Gedmo\Mapping\Annotation as Gedmo;
use labo\Bundle\TestmanuBundle\Entity\baseL0_entity;
// Repositories
use labo\Bundle\TestmanuBundle\Entity\statutRepository;
use labo\Bundle\TestmanuBundle\Entity\versionRepository;
// Entities
use labo\Bundle\TestmanuBundle\Entity\statut;
use labo\Bundle\TestmanuBundle\Entity\version;
// aeReponse
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
abstract class baseL1_entity extends baseL0_entity {

	/**
	 * @ORM\ManyToOne(targetEntity="labo\Bundle\TestmanuBundle\Entity\statut")
	 * @ORM\JoinColumn(nullable=false, unique=false)
	 */
	protected $statut;

	/**
	 * @var integer
	 *
	 * @ORM\ManyToOne(targetEntity="labo\Bundle\TestmanuBundle\Entity\version")
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
		return 'baseL1_entity';
	}

	/**
	 * @Assert/True(message = "Cette entité n'est pas valide.")
	 * @return boolean
	 */
	public function isBaseL1_entityValid() {
		return true;
	}

	/**
	 * Complète les données avant enregistrement
	 * @return boolean
	 */
	public function verifBaseL1_entity() {
		return true;
	}


	/**
	 * Set statut
	 * @param statut $statut
	 * @return baseL1_entity
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
	 * @return baseL1_entity
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












