<?php

namespace laboBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
// Slug
use Gedmo\Mapping\Annotation as Gedmo;
use laboBundle\Entity\baseAttributsInterface;
use \DateTime;
use \Exception;

/**
 * version
 *
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
abstract class version extends baseAttributsInterface {

	const TEMPLATE 			= "pageweb";
	const COULEUR_FOND 		= "#ffffff";

	/**
	 * @var boolean
	 * @ORM\Column(name="defaultVersion", type="boolean", nullable=false, unique=false)
	 */
	protected $defaultVersion;

	/**
	 * @var string
	 * @ORM\Column(name="accroche", type="string", length=255, nullable=true, unique=false)
	 * @Assert\Length(
	 *      min = "6",
	 *      max = "255",
	 *      minMessage = "L'accroche doit comporter au moins {{ limit }} lettres.",
	 *      maxMessage = "L'accroche doit comporter au maximum {{ limit }} lettres."
	 * )
	 */
	protected $accroche;

	/**
	 * @var string
	 * @ORM\Column(name="tvaIntra", type="string", length=100, nullable=true, unique=false)
	 */
	protected $tvaIntra;

	/**
	 * @var string
	 * @ORM\Column(name="siren", type="string", length=100, nullable=true, unique=false)
	 */
	protected $siren;

	/**
	 * @var string
	 * @ORM\Column(name="refGoogle", type="string", length=20, nullable=true, unique=false)
	 */
	protected $refGoogle;

	/**
	 * @var string
	 * @ORM\Column(name="nomDomaine", type="string", length=200, nullable=false, unique=true)
	 * @Assert\Url(message = "Vous devez indiquer une URL valide et complète.")
	 */
	protected $nomDomaine;

	/**
	 * @var string
	 * @ORM\Column(name="hote", type="string", length=200, nullable=true, unique=false)
	 * @Assert\Url(message = "Vous devez indiquer une URL valide et complète.")
	 */
	protected $hote;

	/**
	 * @var string
	 * @ORM\Column(name="couleurFond", type="string", length=30, nullable=false, unique=false)
	 */
	protected $couleurFond;

	/**
	 * @var string
	 * @ORM\Column(name="templateIndex", type="string", length=30, nullable=false, unique=false)
	 */
	protected $templateIndex;



	public function __construct() {
		parent::__construct();
		$this->defaultVersion 	= false;
		$this->couleurFond 		= self::COULEUR_FOND;
		$this->templateIndex 	= self::TEMPLATE;
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
			if($this->tvaIntra !== null || $this->siren !== null) $valid = true;
				else $valid = false;
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
			case 'isVersion':
				return true;
				break;
			default:
				return parent::__call($method, $args);
				break;
		}
	}

// FIN --------------------- à inclure dans toutes les entités ------------------------


	/**
	 * Set defaultVersion
	 * @param boolean $defaultVersion
	 * @return version
	 */
	public function setDefaultVersion($defaultVersion) {
		if($defaultVersion == 1) $defaultVersion = true;
		if($defaultVersion === true) $this->defaultVersion = $defaultVersion;
			else $this->defaultVersion = false;
		return $this;
	}

	/**
	 * Get defaultVersion
	 * @return boolean 
	 */
	public function getDefaultVersion() {
		return $this->defaultVersion;
	}

	/**
	 * Set accroche
	 * @param string $accroche
	 * @return version
	 */
	public function setAccroche($accroche) {
		$this->accroche = $accroche;
	
		return $this;
	}

	/**
	 * Get accroche
	 * @return string 
	 */
	public function getAccroche() {
		return $this->accroche;
	}

	/**
	 * Set tvaIntra
	 * @param string $tvaIntra
	 * @return version
	 */
	public function setTvaIntra($tvaIntra) {
		$this->tvaIntra = $tvaIntra;
	
		return $this;
	}

	/**
	 * Get tvaIntra
	 * @return string 
	 */
	public function getTvaIntra() {
		return $this->tvaIntra;
	}

	/**
	 * Set siren
	 * @param string $siren
	 * @return version
	 */
	public function setSiren($siren) {
		$this->siren = $siren;
	
		return $this;
	}

	/**
	 * Get siren
	 * @return string 
	 */
	public function getSiren() {
		return $this->siren;
	}

	/**
	 * Set refGoogle
	 * @param string $refGoogle
	 * @return version
	 */
	public function setRefGoogle($refGoogle) {
		$this->refGoogle = $refGoogle;
	
		return $this;
	}

	/**
	 * Get refGoogle
	 * @return string 
	 */
	public function getRefGoogle() {
		return $this->refGoogle;
	}

	/**
	 * Set nomDomaine
	 * @param string $nomDomaine
	 * @return version
	 */
	public function setNomDomaine($nomDomaine) {
		$this->nomDomaine = $nomDomaine;
		$this->setHote();

		return $this;
	}

	/**
	 * Get nomDomaine
	 * @return string 
	 */
	public function getNomDomaine() {
		return $this->nomDomaine;
	}

	/**
	 * Définit l'hôte sous forme de nom de domaine sans "http:". 
	 * ATTENTION : utilisé pour changement de version du site !!!
	 * @return version
	 */
	public function setHote() {
		preg_match('#^[\w.]*\.(\w+\.[a-z]{2,6})[\w/._-]*$#', str_replace(array("http://", "https://"), "", $this->getNomDomaine()), $match);
		if(count($match) > 1) $this->hote = $match[1];
			else $this->hote = null;
		return $this;
	}

	/**
	 * Get hote
	 * @return string 
	 */
	public function getHote() {
		return $this->hote;
	}

	/**
	 * Set couleurFond
	 * @param string $couleurFond
	 * @return version
	 */
	public function setCouleurFond($couleurFond) {
		$this->couleurFond = $couleurFond;
	
		return $this;
	}

	/**
	 * Get couleurFond
	 * @return string 
	 */
	public function getCouleurFond() {
		return $this->couleurFond;
	}

	/**
	 * Set templateIndex
	 * @param string $templateIndex
	 * @return version
	 */
	public function setTemplateIndex($templateIndex) {
		$this->templateIndex = $templateIndex;
	
		return $this;
	}

	/**
	 * Get templateIndex
	 * @return string 
	 */
	public function getTemplateIndex() {
		return $this->templateIndex;
	}

}