<?php
namespace laboBundle\services\framework\pagesModules;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

use laboBundle\services\framework\pagesModules\primarydata;
use laboBundle\services\aeReponse;
use laboBundle\services\aetools;

use \Exception;
use \DateTime;
use \ReflectionClass;

class ibox {

	protected $iBox;
	protected $pData;
	protected $types;

	public function __construct($nom = null, $params = null) {
		$this->pData = new primarydata();
		// variables
		$this->createIBox($nom, $params);
		return $this;
	}

	/**
	 * Crée une nouvelle iBox
	 * @param mixed $nom
	 * @param array $params
	 * @return ibox
	 */
	public function createIBox($nom = null, $params = null) {
		// initialisation
		$this->getTypes();
		$this->iBox = $this->pData->getIbox_params_defaults();
		// 
		if(is_array($nom)) {
			$params = $nom;
			$nom = null;
		}
		if(!is_array($params)) $params = array();
		if($nom === null || !(is_string($nom))) {
			// définit un nom car inexistant
			$nom = aetools::getRandomName();
			// echo('iBox param::'.primarydata::IBOX_PARAMS_NOM." = ".$nom."<br>");
		}
		if(!isset($params[primarydata::IBOX_PARAMS_NOM])) $params[primarydata::IBOX_PARAMS_NOM] = $nom;
		// injection des paramètres
		// echo('<pre>');
		// var_dump($params);
		// echo('</pre>');
		$this->setByParams($params);
		// echo('<pre>');
		// var_dump($this->iBox);
		// echo('</pre>');
		return $this;
	}

	// public function __clone() {
	// 	$this->setNom($this->getNom().'_copie');
	// }

	public function getParamsKeys() {
		return $this->pData->getIbox_params_keys();
	}

	public function getTypes() {
		if(!isset($this->types)) $this->types = $this->pData->getIbox_types_keys();
		return $this->types;
	}

	/**
	 * Insère un lot de paramètres (array)
	 * @param array $params
	 * @return ibox
	 */
	public function setByParams($params = null) {
		if(!is_array($params)) throw new Exception("Le paramètre doit être un array. ibox::setByParams(), line ".__LINE__, 1);
		foreach ($params as $key => $value) {
			if($value !== null) $this->attr($key, $value);
		}
		return $this;
	}

	/**
	 * Attribue un paramètre
	 * @param string $key
	 * @param mixed $value
	 * @return ibox
	 */
	public function attr($key, $value = null) {
		if(in_array($key, $this->getParamsKeys())) {
			// l'attribut existe
			if($value !== null) {
				$methodSet = "set".ucfirst($key);
				$methodAdd = "add".ucfirst($key);
				if(method_exists($this, $methodSet)) {
					$this->$methodSet($value);
				} else if(method_exists($this, $methodAdd)) {
					$this->$methodAdd($value);
				} else {
					$this->iBox[$key] = $value;
				}
				return $this;
			} else {
				// renvoi de la valeur
				$methodGet = "get".ucfirst($key);
				$methodGets = "get".ucfirst($key).'s';
				if(method_exists($this, $methodGet)) {
					return $this->$methodGet($value);
				} else if(method_exists($this, $methodGets)) {
					return $this->$methodGets($value);
				} else if(isset($this->iBox[$key])) {
					return $this->iBox[$key];
				} else return false;
			}
		} else return false;
	}

	/**
	 * Définit le nom de la iBox
	 * @param string $nom
	 * @return ibox
	 */
	public function setNom($nom) {
		$this->iBox[primarydata::IBOX_PARAMS_NOM] = $nom;
		return $this;
	}

	/**
	 * Renvoie le nom de la iBox
	 * @return string
	 */
	public function getNom() {
		return isset($this->iBox[primarydata::IBOX_PARAMS_NOM]) ? $this->iBox[primarydata::IBOX_PARAMS_NOM] : false; 
	}

	/**
	 * Renvoie true si la iBox est valide
	 * @Assert\True(message = "Objet iBox invalide.")
	 * @return boolean
	 */
	public function isValid() {
		return $this->verify()->isValid();
	}

	/**
	 * Renvoie l'analyse de ibox
	 * @return aeReponse
	 */
	public function verify() {
		$aeReponse = new aeReponse();
		if($this->getNom() === false) $aeReponse->setUnvalid("La iBox ne contient pas de ".primarydata::IBOX_PARAMS_NOM);
		if(!isset($iBox[primarydata::IBOX_PARAMS_TITLE])) {
			$aeReponse->setUnvalid('La iBox "'.$this->getNom().'" ne contient pas de '.primarydata::IBOX_PARAMS_TITLE);
		} else {
			if(strlen(trim($iBox[primarydata::IBOX_PARAMS_TITLE])) < 1) $aeReponse->setUnvalid('La iBox "'.$this->getNom().'" ne contient pas de '.primarydata::IBOX_PARAMS_TITLE);
		}
		if(!isset($iBox[primarydata::IBOX_PARAMS_TYPE])) {
			$aeReponse->setUnvalid('La iBox "'.$this->getNom().'" ne contient pas de '.primarydata::IBOX_PARAMS_TYPE);
		} else {
			if(!in_array($iBox[primarydata::IBOX_PARAMS_TYPE], $this->getTypes())) $aeReponse->setUnvalid('La iBox "'.$this->getNom().'" ne contient pas de '.primarydata::IBOX_PARAMS_TYPE);
		}
		return $aeReponse;
	}

	/**
	 * Définit le titre de la iBox
	 * @param string $title
	 * @return ibox
	 */
	public function setTitle($title) {
		$this->iBox[primarydata::IBOX_PARAMS_TITLE] = trim($title);
		return $this;
	}

	/**
	 * Renvoie le titre de la iBox
	 * @return string
	 */
	public function getTitle() {
		return $this->iBox[primarydata::IBOX_PARAMS_TITLE];
	}

	/**
	 * Définit le type de la iBox
	 * @param string $title
	 * @return ibox
	 */
	public function setType($type) {
		// contrôle du type
		if(!in_array($type, $this->getTypes())) $type = reset($this->getTypes());
		$this->iBox[primarydata::IBOX_PARAMS_TYPE] = $type;
		return $this;
	}

	/**
	 * Renvoie le type de la iBox
	 * @return string
	 */
	public function getType() {
		return $this->iBox[primarydata::IBOX_PARAMS_TYPE];
	}

	/**
	 * Définit le menu de la iBox
	 * @param array $tools
	 * @return ibox
	 */
	public function setTools($tools) {
		$this->iBox[primarydata::IBOX_PARAMS_TOOLS] = $tools;
		return $this;
	}

	/**
	 * Renvoie le menu de la iBox
	 * @return array
	 */
	public function getTools() {
		return $this->iBox[primarydata::IBOX_PARAMS_TOOLS];
	}

	/**
	 * Définit la taille de la iBox
	 * @param integer $size
	 * @return ibox
	 */
	public function setSize($size) {
		$this->iBox[primarydata::IBOX_PARAMS_SIZE] = $size;
		return $this;
	}

	/**
	 * Renvoie la taille de la iBox
	 * @return integer
	 */
	public function getSize() {
		return $this->iBox[primarydata::IBOX_PARAMS_SIZE];
	}

	/**
	 * ajoute un label à la iBox
	 * @param string $label
	 * @return ibox
	 */
	public function addLabel($label) {
		$champs = array('texte', 'style');
		if(is_string($label)) $this->iBox[primarydata::IBOX_PARAMS_LABEL]->add(array('texte' => $label, 'style' => self::PRIMARY));
		if(is_array($label)) {
			$values = array();
			foreach ($label as $key => $value) {
				if(in_array($key, $champs)) $values[$key] = $value;
			}
			$this->iBox[primarydata::IBOX_PARAMS_LABEL]->add($values);
		}
		return $this;
	}

	/**
	 * supprime un label de la iBox
	 * @param string $label
	 * @return ibox
	 */
	public function removeLabel($label) {
		$this->iBox[primarydata::IBOX_PARAMS_LABEL]->removeElement($label);
		return $this;
	}

	/**
	 * Renvoie les labels de la iBox
	 * @return ArrayCollection
	 */
	public function getLabels() {
		return $this->iBox[primarydata::IBOX_PARAMS_LABEL];
	}

	/**
	 * ajoute un heading à la iBox
	 * @param string $heading
	 * @return ibox
	 */
	public function addHeading($heading) {
		$this->iBox[primarydata::IBOX_PARAMS_HEADG]->add($heading);
		return $this;
	}

	/**
	 * supprime un heading de la iBox
	 * @param string $heading
	 * @return ibox
	 */
	public function removeHeading($heading) {
		$this->iBox[primarydata::IBOX_PARAMS_HEADG]->removeElement($heading);
		return $this;
	}

	/**
	 * Renvoie les headings de la iBox
	 * @return ArrayCollection
	 */
	public function getHeadings() {
		return $this->iBox[primarydata::IBOX_PARAMS_HEADG];
	}

	/**
	 * Définit le titre du contenu de la iBox
	 * @param string $title
	 * @return ibox
	 */
	public function setContentTitle($title) {
		$this->iBox[primarydata::IBOX_PARAMS_CTTTL] = $title;
		return $this;
	}

	/**
	 * Renvoie le titre du contenu de la iBox
	 * @return string
	 */
	public function getContentTitle() {
		return $this->iBox[primarydata::IBOX_PARAMS_CTTTL];
	}

	/**
	 * Définit le contenu HTML de la iBox
	 * @param string $html
	 * @return ibox
	 */
	public function setContentHtml($html) {
		$this->iBox[primarydata::IBOX_PARAMS_CTHTM] = $html;
		return $this;
	}

	/**
	 * Ajoute au contenu HTML de la iBox
	 * @param string $html
	 * @return ibox
	 */
	public function addContentHtml($html) {
		$this->iBox[primarydata::IBOX_PARAMS_CTHTM] .= $html;
		return $this;
	}

	/**
	 * Renvoie le contenu HTML de la iBox
	 * @return string
	 */
	public function getContentHtml() {
		return $this->iBox[primarydata::IBOX_PARAMS_CTHTM];
	}

	/**
	 * Définit le contenu liste de la iBox
	 * @param array $list
	 * @return ibox
	 */
	public function addContentList($list) {
		if(is_string($list)) $this->iBox[primarydata::IBOX_PARAMS_CTLST]->add($list);
		if(is_array($list)) foreach ($list as $key => $value) {
			$this->iBox[primarydata::IBOX_PARAMS_CTLST]->add($value);
		}
		return $this;
	}

	/**
	 * Supprime le contenu liste de la iBox
	 * @param array $list
	 * @return ibox
	 */
	public function removeContentList($list) {
		$this->iBox[primarydata::IBOX_PARAMS_CTLST]->removeElement($list);
		return $this;
	}

	/**
	 * Renvoie le contenu liste de la iBox
	 * @return ArrayCollection
	 */
	public function getContentLists() {
		return $this->iBox[primarydata::IBOX_PARAMS_CTLST];
	}

	/**
	 * Ajoute un bouton à la iBox
	 * @param array $btbtn
	 * @return ibox
	 */
	public function addBottomButton($btbtn) {
		$values = $this->pData->getIbox_button_defaults();
		// si texte seulement…
		$buttonKeys = $this->pData->getIbox_button_keys();
		if(is_string($btbtn)) $btbtn = array(reset($buttonKeys) => $btbtn);
		$btbtn2 = array();
		foreach ($values as $key => $value) {
			if(isset($btbtn[$key])) $btbtn2[$key] = $btbtn[$key];
				else $btbtn2[$key] = $value;
		}
		$this->iBox[primarydata::IBOX_PARAMS_BTMTB]->add($btbtn2);
		return $this;
	}

	/**
	 * Supprime un bonton de la iBox
	 * @param array $btbtn
	 * @return ibox
	 */
	public function removeBottomButton($btbtn) {
		$this->iBox[primarydata::IBOX_PARAMS_BTMTB]->removeElement($btbtn);
		return $this;
	}

	/**
	 * Renvoie les boutons de la iBox
	 * @return ArrayCollection
	 */
	public function getBottomButtons() {
		return $this->iBox[primarydata::IBOX_PARAMS_BTMTB];
	}














}