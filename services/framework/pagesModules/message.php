<?php
namespace laboBundle\services\framework\pagesModules;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

use laboBundle\services\framework\pagesModules\primarydata;
use laboBundle\services\aeReponse;

use \Exception;
use \DateTime;
use \ReflectionClass;

class message {

	protected $message;
	protected $pData;

	public function __construct($title, $texte = null, $type = null, $params = null) {
		$this->pData = new primarydata();
		return $this->createMessage($title, $texte, $type, $params);
	}

	/**
	 * Crée un nouveau message
	 * @param string $texte
	 * @param string $type
	 * @param array $params
	 * @return message
	 */
	public function createMessage($title, $texte = null, $type = null, $params = null) {
		if(is_array($title)) {
			$this->setByParams($title);
		} else {
			$this->message = array();
			$this->setTitle($title);
			$this->setTexte($texte);
			$this->setType($type);
			$this->setByParams($params);
		}
		return $this;
	}

	/**
	 * Renvoie true si le message est valide
	 * @Assert\True(message = "Objet message invalide.")
	 * @return boolean
	 */
	public function isValid() {
		return $this->verify()->isValid();
	}

	/**
	 * Renvoie l'analyse du message
	 * @return aeReponse
	 */
	public function verify() {
		$aeReponse = new aeReponse();
		if(!is_string($this->getTexte())) $aeReponse->setUnvalid("Le message ne contient pas de texte.");
		if($this->getType() === false) $aeReponse->setUnvalid("Le type du message n'est pas défini.");
		$aeReponse->isValid() ? $valide = 'ok' : $valide = 'invalide';
		// echo('<h1>Vérification du message "'.$this->getTitle().'" '.$valide.'</h1>');
		return $aeReponse; 
	}

	/**
	 * Renvoie le contenu array() du message
	 * @return array / false si verif est false
	 */
	public function getMessage($verif = true) {
		return ((!$verif) || $this->isValid()) ? $this->message : false;
	}

	/**
	 * Renvoie la liste des paramètres disponibles
	 * @return array
	 */
	public function getParamsKeys() {
		return $this->pData->getMessages_params_keys();
		// const MESSAGES_PARAMS_TEXTE		= 'texte';
		// const MESSAGES_PARAMS_TYPE		= 'type';
		// const MESSAGES_PARAMS_TITLE		= 'title';
		// const MESSAGES_PARAMS_SHWMTD		= 'showMethod';
		// const MESSAGES_PARAMS_HIDMTD		= 'hideMethod';
		// const MESSAGES_PARAMS_SHWEAS		= 'showEasing';
		// const MESSAGES_PARAMS_HIDEAS		= 'hideEasing';
		// const MESSAGES_PARAMS_SHWDUR		= 'showDuration';
		// const MESSAGES_PARAMS_HIDDUR		= 'hideDuration';
		// const MESSAGES_PARAMS_POSCLS		= 'positionClass';
		// const MESSAGES_PARAMS_CLSBTN		= 'closeButton';
		// const MESSAGES_PARAMS_PRGBAR		= 'progressBar';
		// const MESSAGES_PARAMS_DEBUGG		= 'debug';
		// const MESSAGES_PARAMS_TIMOUT		= 'timeOut';
		// const MESSAGES_PARAMS_EXTIMO		= 'extendedTimeOut';
		// const MESSAGES_PARAMS_CLSHTM		= 'closeHtml';
		// const MESSAGES_PARAMS_NEWTOP		= 'newestOnTop';
	}

	/**
	 * Attribue/récupère un paramètre
	 * @param string $key
	 * @param mixed $value
	 * @return message
	 */
	public function attr($key, $value = null) {
		if(in_array($key, $this->getParamsKeys())) {
			// l'attribut existe
			if($value !== null) {
				// attribution de la valeur
				$methodSet = "set".ucfirst($key);
				$methodAdd = "add".ucfirst($key);
				if(method_exists($this, $methodSet)) {
					$this->$methodSet($value);
				} else if(method_exists($this, $methodAdd)) {
					$this->$methodAdd($value);
				} else {
					$this->message[$key] = $value;
				}
				// var_dump($this->message);
				return $this;
			} else {
				// lecture de la valeur
				return isset($this->message[$key]) ? $this->message[$key] : false;
			}
		} else {
			throw new Exception('Paramètre de message inexistant : '.$key, 1);
			// echo('<h1>Clé de message inexistante : '.$key.'</h1>')
			// var_dump($this->message);
			// return false;
		}
		return $this;
	}

	public function setByParams($params = null) {
		if($params === null || !is_array($params)) $params = array();
		foreach ($params as $key => $value) $this->attr($key, $value);
	}

	/**
	 * Définit le texte du message
	 * @return message
	 */
	public function setTexte($texte) {
		$this->message[primarydata::MESSAGES_PARAMS_TEXTE] = $texte;
		return $this;
	}

	/**
	 * Renvoie le texte du message
	 * @return string
	 */
	public function getTexte() {
		return isset($this->message[primarydata::MESSAGES_PARAMS_TEXTE]) ? $this->message[primarydata::MESSAGES_PARAMS_TEXTE] : false;
	}



	/**
	 * Définit le type du message
	 * @param string $type
	 * @return message
	 */
	public function setType($type = null) {
		if($this->typeExist($type)) {
			// echo('<p>Type "'.$type.'" trouvé !</p>');
			$this->message[primarydata::MESSAGES_PARAMS_TYPE] = $type;
		} else {
			// echo('<p>Type "'.$type.'" non trouvé parmi "'.implode('", "', $this->getTypes()).'"… Type par défaut : "'.$this->getDefaultType().'".</p>');
			$this->message[primarydata::MESSAGES_PARAMS_TYPE] = $this->getDefaultType();
		}
		return $this;
	}

	/**
	 * Renvoie le type du message
	 * @return string
	 */
	public function getType() {
		$this->setType();
		return isset($this->message[primarydata::MESSAGES_PARAMS_TYPE]) ? $this->message[primarydata::MESSAGES_PARAMS_TYPE] : false;
	}

	/**
	 * Renvoie le type par défaut
	 * @return string
	 */
	public function getDefaultType() {
		return $this->pData->getMessage_type_default();
	}

	/**
	 * Renvoie les types de message disponibles
	 * @return array
	 */
	public function getTypes() {
		return $this->pData->getMessages_types_keys();
	}

	/**
	 * Renvoie si un type existe
	 * @return boolean
	 */
	public function typeExist($type) {
		return in_array($type, $this->getTypes());
	}



	/**
	 * Définit le title du message
	 * @return message
	 */
	public function setTitle($title) {
		$this->message[primarydata::MESSAGES_PARAMS_TITLE] = $title;
		return $this;
	}

	/**
	 * Renvoie le title du message
	 * @return string
	 */
	public function getTitle() {
		return isset($this->message[primarydata::MESSAGES_PARAMS_TITLE]) ? $this->message[primarydata::MESSAGES_PARAMS_TITLE] : false;
	}





}