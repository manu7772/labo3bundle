<?php

namespace labo\Bundle\TestmanuBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
// Slug
use Gedmo\Mapping\Annotation as Gedmo;
use labo\Bundle\TestmanuBundle\Entity\baseL0_entity;
// Entities
use labo\Bundle\TestmanuBundle\Entity\adresse;
use labo\Bundle\TestmanuBundle\Entity\image;
use labo\Bundle\TestmanuBundle\Entity\reseausocial;
// aeReponse
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

/**
 * version
 *
 * @ORM\Entity
 * @ORM\Table(name="version")
 * @ORM\Entity(repositoryClass="labo\Bundle\TestmanuBundle\Entity\versionRepository")
 * @UniqueEntity(fields={"siren"}, message="Cette version existe déjà")
 */
class version extends baseL0_entity {

	const TEMPLATE 			= "pageweb";
	const COULEUR_FOND 		= "#ffffff";

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="defaut", type="boolean", nullable=false, unique=false)
	 */
	protected $defaut;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="accroche", type="string", length=200, nullable=true, unique=false)
	 * @Assert\Length(
	 *      min = "1",
	 *      max = "200",
	 *      minMessage = "L'accroche doit comporter au moins {{ limit }} lettres.",
	 *      maxMessage = "L'accroche doit comporter au maximum {{ limit }} lettres."
	 * )
	 */
	protected $accroche;

	/**
	 * @var array
	 *
     * @ORM\OneToOne(targetEntity="labo\Bundle\TestmanuBundle\Entity\reseausocial", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true, unique=false)
	 */
	protected $reseausocial;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="tvaIntra", type="string", length=100, nullable=true, unique=false)
	 */
	protected $tvaIntra;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="siren", type="string", length=100, nullable=true, unique=false)
	 */
	protected $siren;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="telpublic", type="string", length=25, nullable=true, unique=false)
	 * @Assert\Length(
	 *      min = "10",
	 *      max = "14",
	 *      minMessage = "Le téléphone doit comporter au moins {{ limit }} chiffres.",
	 *      maxMessage = "Le téléphone doit comporter au plus {{ limit }} chiffres."
	 * )
	 *
	 */
	protected $telpublic;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="telportable", type="string", length=25, nullable=true, unique=false)
	 * @Assert\Length(
	 *      min = "10",
	 *      max = "14",
	 *      minMessage = "Le téléphone portable doit comporter au moins {{ limit }} chiffres.",
	 *      maxMessage = "Le téléphone portable doit comporter au plus {{ limit }} chiffres."
	 * )
	 *
	 */
	protected $telportable;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="descriptif", type="text", nullable=true, unique=false)
	 */
	protected $descriptif;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="nomDomaine", type="string", length=200, nullable=false, unique=true)
	 * @Assert\Url(message = "Vous devez indiquer une URL valide et complète.")
	 * 
	 */
	protected $nomDomaine;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="hote", type="string", length=200, nullable=true, unique=false)
	 * @Assert\Url(message = "Vous devez indiquer une URL valide et complète.")
	 * 
	 */
	protected $hote;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="email", type="string", length=200, nullable=true, unique=false)
	 * @Assert\Email(message = "Vous devez indiquer un email valide et complet.")
	 * 
	 */
	protected $email;

	/**
	 * @var integer
	 *
	 * @ORM\ManyToOne(targetEntity="labo\Bundle\TestmanuBundle\Entity\image", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(nullable=true, unique=false)
	 */
	protected $logo;

	/**
	 * @var integer
	 *
	 * @ORM\OneToOne(targetEntity="labo\Bundle\TestmanuBundle\Entity\image", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(nullable=true, unique=false)
	 */
	protected $favicon;

	/**
	 * @var integer
	 *
	 * @ORM\ManyToOne(targetEntity="labo\Bundle\TestmanuBundle\Entity\image", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(nullable=true, unique=false)
	 */
	protected $imageEntete;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="couleurFond", type="string", length=30, nullable=false, unique=false)
	 */
	protected $couleurFond;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="templateIndex", type="string", length=30, nullable=false, unique=false)
	 */
	protected $templateIndex;

    /**
     * @var string
     *
     * @ORM\ManyToOne(targetEntity="labo\Bundle\TestmanuBundle\Entity\adresse", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true, unique=false)
     */
    protected $adresse;


	public function __construct() {
		parent::__construct();
		$this->defaut 			= false;
		$this->couleurFond 		= self::COULEUR_FOND;
		$this->templateIndex 	= self::TEMPLATE;
		$this->reseausocial 	= new ArrayCollection;
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
		return 'version';
	}

	/**
	 * @Assert\True(message = "Vous devez renseigner soit le numéro TVAintra, soit le SIREN.")
	 */
	public function isVersionValid() {
		$valid = true;
		$validMethod = 'is'.ucfirst($this->getParentName()).'Valid';
		if(method_exists($this, $validMethod)) {
			$valid = $this->$validMethod();
		}
		// autres vérifications, si le parent est valide…
		if($valid === true) {
			if($this->tvaIntra || $this->siren) $valid = true;
				else $valid = false;
		}
		return $valid;
	}

	/**
	 * Complète les données avant enregistrement
	 * @return boolean
	 */
	public function verifBase_entity() {
		return true;
	}




	/**
	 * Ajoute un réseau social
	 * @param reseausocial $reseausocial
	 * @return version
	 */
	public function addReseausocial(reseausocial $reseausocial = null) {
		if($reseausocial !== null) {
			$this->reseausocial->add($reseausocial);
		}	
		return $this;
	}

	/**
	 * Renvoie les réseaux sociaux
	 *
	 * @return string 
	 */
	public function getReseausocials() {
		return $this->reseausocial;
	}

	/**
	 * Set defaut
	 *
	 * @param boolean $defaut
	 * @return version
	 */
	public function setDefaut($defaut) {
		$this->defaut = $defaut;
	
		return $this;
	}

	/**
	 * Get defaut
	 *
	 * @return boolean 
	 */
	public function getDefaut() {
		return $this->defaut;
	}

	/**
	 * Set accroche
	 *
	 * @param string $accroche
	 * @return version
	 */
	public function setAccroche($accroche) {
		$this->accroche = $accroche;
	
		return $this;
	}

	/**
	 * Get accroche
	 *
	 * @return string 
	 */
	public function getAccroche() {
		return $this->accroche;
	}

	/**
	 * Set tvaIntra
	 *
	 * @param string $tvaIntra
	 * @return version
	 */
	public function setTvaIntra($tvaIntra) {
		$this->tvaIntra = $tvaIntra;
	
		return $this;
	}

	/**
	 * Get tvaIntra
	 *
	 * @return string 
	 */
	public function getTvaIntra() {
		return $this->tvaIntra;
	}

	/**
	 * Set siren
	 *
	 * @param string $siren
	 * @return version
	 */
	public function setSiren($siren) {
		$this->siren = $siren;
	
		return $this;
	}

	/**
	 * Get siren
	 *
	 * @return string 
	 */
	public function getSiren() {
		return $this->siren;
	}

	/**
	 * Set telpublic
	 *
	 * @param string $telpublic
	 * @return version
	 */
	public function setTelpublic($telpublic) {
		$this->telpublic = $telpublic;
	
		return $this;
	}

	/**
	 * Get telpublic
	 *
	 * @return string 
	 */
	public function getTelpublic() {
		return $this->telpublic;
	}

	/**
	 * Set telportable
	 *
	 * @param string $telportable
	 * @return version
	 */
	public function setTelportable($telportable) {
		$this->telportable = $telportable;
	
		return $this;
	}

	/**
	 * Get telportable
	 *
	 * @return string 
	 */
	public function getTelportable() {
		return $this->telportable;
	}

	/**
	 * Set nomDomaine
	 *
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
	 *
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
	 *
	 * @return string 
	 */
	public function getHote() {
		return $this->hote;
	}

	/**
	 * Set email
	 *
	 * @param string $email
	 * @return version
	 */
	public function setEmail($email = null) {
		$this->email = $email;
	
		return $this;
	}

	/**
	 * Get email
	 *
	 * @return string 
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * Set couleurFond
	 *
	 * @param string $couleurFond
	 * @return version
	 */
	public function setCouleurFond($couleurFond) {
		$this->couleurFond = $couleurFond;
	
		return $this;
	}

	/**
	 * Get couleurFond
	 *
	 * @return string 
	 */
	public function getCouleurFond() {
		return $this->couleurFond;
	}

	/**
	 * Set templateIndex
	 *
	 * @param string $templateIndex
	 * @return version
	 */
	public function setTemplateIndex($templateIndex) {
		$this->templateIndex = $templateIndex;
	
		return $this;
	}

	/**
	 * Get templateIndex
	 *
	 * @return string 
	 */
	public function getTemplateIndex() {
		return $this->templateIndex;
	}

	/**
	 * Set logo
	 *
	 * @param image $logo
	 * @return version
	 */
	public function setLogo(image $logo = null) {
		$this->logo = $logo;
	
		return $this;
	}

	/**
	 * Get logo
	 *
	 * @return image 
	 */
	public function getLogo() {
		return $this->logo;
	}

	/**
	 * Set favicon
	 *
	 * @param image $favicon
	 * @return version
	 */
	public function setFavicon(image $favicon = null) {
		if($favicon !== null) $this->favicon = $favicon;
	
		return $this;
	}

	/**
	 * Get favicon
	 *
	 * @return image 
	 */
	public function getFavicon() {
		return $this->favicon;
	}

	/**
	 * Set imageEntete
	 *
	 * @param image $imageEntete
	 * @return version
	 */
	public function setImageEntete(image $imageEntete = null) {
		$this->imageEntete = $imageEntete;
	
		return $this;
	}

	/**
	 * Get imageEntete
	 *
	 * @return image 
	 */
	public function getImageEntete() {
		return $this->imageEntete;
	}

    /**
     * Set adresse
     *
     * @param adresse $adresse
     * @return version
     */
    public function setAdresse(adresse $adresse = null)
    {
        $this->adresse = $adresse;
    
        return $this;
    }

    /**
     * Get adresse
     *
     * @return adresse 
     */
    public function getAdresse()
    {
        return $this->adresse;
    }

}