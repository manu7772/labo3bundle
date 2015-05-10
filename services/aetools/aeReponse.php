<?php
// labo/Bundle/TestmanuBundle/services/aetools/aeReponse.php

namespace labo\Bundle\TestmanuBundle\services\aetools;

use Symfony\Component\DependencyInjection\ContainerInterface;

class aeReponse {

	const NOM_MESSAGES = 'info';
	const NOM_NOTICES = 'notice';
	const NOM_ERRORMESSAGES = 'error';

	protected $container;		// container
	protected $flashBag;		// session
	protected $data = array();	// data

	public function __construct(ContainerInterface $container) {
		$this->container 	= $container;
		$this->flashBag 	= $this->container->get('request')->getSession()->getFlashBag();
		$this->data["data"] = array();
		$this->data["messages"] = array();
		$this->data["notices"] = array();
		$this->data["ERRORmessages"] = array();

		$this->setResult(true);
	}

	protected function computeData() {
		if($this->hasErrors() === true) $this->setUnvalid();
	}

	// GETTERS

	public function getResult() {
		return $this->data["result"];
	}

	/**
	 * Renvoie les messages de retour
	 * @return array
	 */
	public function getMessages() {
		return $this->data["messages"];
	}

	/**
	 * Renvoie les message de notification
	 * @return array
	 */
	public function getNotices() {
		return $this->data["notices"];
	}

	/**
	 * Renvoie les messages d'erreur
	 * @return array
	 */
	public function getErrorMessages() {
		return $this->data["ERRORmessages"];
	}

	/**
	 * Renvoie tous les messages dans un array + sous-array par type
	 * @param boolean $mix - si $mix est true, alors mélange tous les messages dans le même array
	 * @return array
	 */
	public function getAllMessages($mix = false) {
		if($mix === false) return array(
			"messages" => $this->data["messages"], 
			"messages" => $this->data["notices"], 
			"ERRORmessages" => $this->data["ERRORmessages"]
			);
		return array_merge(
			$this->data["messages"], 
			$this->data["notices"], 
			$this->data["ERRORmessages"]
			);
	}

	// public function getAllMessagesInHtml($mix = false) {
	// 	if($mix === false) return array("messages" => $this->data["messages"], "ERRORmessages" => $this->data["ERRORmessages"]);
	// 	return array_merge($this->data["messages"], $this->data["ERRORmessages"]);
	// }

	/**
	 * Renvoie les données et les supprime de data si $efface = true
	 * @param string $nom
	 * @param boolean $efface
	 * @return 
	 */
	public function getData($nom = null, $efface = false) {
		if(is_string($nom) && isset($this->data["data"][$nom])) {
			$result = $this->data["data"][$nom];
			if($efface === true) unset($this->data["data"][$nom]);
		} else {
			$result = $this->data["data"];
			if($efface === true) {
				unset($this->data["data"]);
				$this->data["data"] = array();
			}
		}
		return $result;
	}

	/**
	 * 
	 * @return 
	 */
	public function getDataAndSupp($nom = null) {
		return $this->getData($nom, true);
	}

	/**
	 * Renvoie le type de data $nom
	 * @param string $nom
	 * @return mixed (false si $nom n'existe pas dans data)
	 */
	public function getDataType($nom) {
		if(isset($this->data["data"][$nom])) return gettype($this->data["data"][$nom]);
			else return false;
	}

	/**
	 * Renvoie les noms des clés de data
	 * @return array
	 */
	public function getDataKeys() {
		return array_keys($this->data["data"]);
	}

	/**
	 * la clé $key de data existe-t-elle ?
	 * @return boolean
	 */
	public function isDataKey($key) {
		return (array_key_exists($key, array_keys($this->data["data"])) ? true : false );
	}

	/**
	 * Renvoie les données au format JSon
	 * @return Json
	 */
	public function getJSONreponse() {
		return json_encode($this->data);
	}

	/**
	 * La Réponse est-elle valide ?
	 * @return boolean
	 */
	public function isValid() {
		return ($this->data["result"] === true ? true : false );
	}

	/**
	 * La Réponse est-elle invalide ?
	 * @return boolean
	 */
	public function isUnvalid() {
		return ($this->data["result"] === false ? true : false );
	}

	/**
	 * La Réponse contient-elle des données data ?
	 * @return boolean
	 */
	public function hasData() {
		return (count($this->data["data"]) > 0 ? true : false );
	}

	/**
	 * La Réponse contient-elle des messages de réponse ?
	 * @return boolean
	 */
	public function hasMessages() {
		return (count($this->data["messages"]) > 0 ? true : false );
	}

	/**
	 * La Réponse contient-elle des messages de notification ?
	 * @return boolean
	 */
	public function hasNotices() {
		return (count($this->data["notices"]) > 0 ? true : false );
	}

	/**
	 * La Réponse contient-elle des messages d'erreur ?
	 * @return boolean
	 */
	public function hasErrors() {
		return (count($this->data["ERRORmessages"]) > 0 ? true : false );
	}

	// SETTERS

	/**
	 * Valide la Réponse, avec messages optionnels
	 * @param mixed $messages - string ou array de messages
	 * @param mixed $notices - string ou array de notices
	 * @return aeReponse
	 */
	public function setValid($messages = null, $notices = null) {
		if(is_string($messages)) $messages = array($messages);
		if(is_string($notices)) $notices = array($notices);
		$this->setResult(true);
		if(is_array($messages)) foreach($messages as $message) {
			$this->addMessage($message);
		}
		if(is_array($notices)) foreach($notices as $notice) {
			$this->addNoticeMessage($notice);
		}
		return $this;
	}

	/**
	 * INvalide la Réponse, avec messages d'erreur optionnels
	 * @param mixed $ERRORmessages - string ou array de messages d'erreur
	 * @param mixed $notices - string ou array de notices
	 * @return aeReponse
	 */
	public function setUnvalid($ERRORmessages = null, $notices = null) {
		if(is_string($ERRORmessages)) $ERRORmessages = array($ERRORmessages);
		if(is_string($notices)) $notices = array($notices);
		// $this->setResult(false);
		if(is_array($ERRORmessages)) foreach($ERRORmessages as $ERRORmessage) {
			$this->addErrorMessage($ERRORmessage);
		}
		if(is_array($notices)) foreach($notices as $notice) {
			$this->addNoticeMessage($notice);
		}
		return $this;
	}

	/**
	 * Définit le résultat de la Réponse
	 * @param boolean $result
	 * @return aeReponse
	 */
	public function setResult($result = true) {
		if(!is_bool($result)) $result = false;
		$this->data["result"] = $result;
		return $this;
	}

	/**
	 * Ajoute des messages
	 * @param mixed $messages - string ou array de messages
	 * @return aeReponse
	 */
	public function addMessage($messages) {
		if(!is_array($messages)) $messages = array($messages);
		foreach ($messages as $message) {
			$this->data["messages"][] = $message;
		}
		return $this;
	}

	/**
	 * Ajoute des notifications
	 * @param mixed $notices - string ou array de notifications
	 * @return aeReponse
	 */
	public function addNoticeMessage($notices) {
		if(!is_array($notices)) $notices = array($notices);
		foreach ($notices as $notice) {
			$this->data["notices"][] = $notice;
		}
		return $this;
	}

	/**
	 * Ajoute des messages d'erreur ET invalide la Réponse
	 * @param mixed $ERRORmessages - string ou array de messages d'erreur
	 * @param boolean $unvalidate - si false, n'invalide toutefois pas la Réponse
	 * @return aeReponse
	 */
	public function addErrorMessage($ERRORmessages, $unvalidate = true) {
		if(!is_array($ERRORmessages)) $ERRORmessages = array($ERRORmessages);
		foreach ($ERRORmessages as $ERRORmessage) {
			$this->data["ERRORmessages"][] = $ERRORmessage;
		}
		if($unvalidate === true) $this->computeData();
		return $this;
	}

	/**
	 * Ajoute des données dans data
	 * @param mixed $data - données
	 * @param string $nom - nom des données
	 * @return aeReponse
	 */
	public function addData($data, $nom = null) {
		if($nom === null) $this->data["data"][] = $data;
			else $this->data["data"][$nom] = $data;
		return $this;
	}

	// AUTRES

	/**
	 * Envoie TOUS les messages dans le flashbag
	 * @return aeReponse
	 */
	public function putAllMessagesInFlashbag() {
		$this->putMessagesInFlashbag();
		$this->putNoticesInFlashbag();
		$this->putErrorMessagesInFlashbag();
		return $this;
	}

	/**
	 * Envoie les messages de notifications et d'erreur dans le flashbag
	 * @return aeReponse
	 */
	public function putBadMessagesInFlashbag() {
		$this->putNoticesInFlashbag();
		$this->putErrorMessagesInFlashbag();
		return $this;
	}

	/**
	 * Envoie les messages dans le flashbag
	 * @return aeReponse
	 */
	public function putMessagesInFlashbag() {
		foreach ($this->data['messages'] as $value) {
			$this->flashBag->add(self::NOM_MESSAGES, $value);
		}
		return $this;
	}

	/**
	 * Envoie les messages de notifications dans le flashbag
	 * @return aeReponse
	 */
	public function putNoticesInFlashbag() {
		foreach ($this->data['notices'] as $value) {
			$this->flashBag->add(self::NOM_NOTICES, $value);
		}
		return $this;
	}

	/**
	 * Envoie les messages d'erreur dans le flashbag
	 * @return aeReponse
	 */
	public function putErrorMessagesInFlashbag() {
		foreach ($this->data['ERRORmessages'] as $value) {
			$this->flashBag->add(self::NOM_ERRORMESSAGES, $value);
		}
		return $this;
	}


}
?>