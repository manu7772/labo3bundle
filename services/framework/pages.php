<?php
namespace laboBundle\services\framework;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
// tools
use laboBundle\services\aetools\aeReponse;
use laboBundle\services\framework\pagesModules\primarydata;
use laboBundle\services\framework\pagesModules\ibox;
use laboBundle\services\framework\pagesModules\message;

use \Exception;
use \DateTime;
use \ReflectionClass;

class pages {

	const FORBIDDEN_KEY = "page";

	protected $container;
	protected $aetools;
	protected $page;
	protected $template;
	protected $currentiBox;
	protected $indexiBox;
	protected $index_message;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
		$this->aetools = $this->container->get('labobundle.aetools');
		$this->pData = $this->container->get('labobundle.primarydata');
		$this->index_message = 0;
		$this->createNewPage();
		return $this;
	}

	/**
	 * Crée une nouvelle page
	 * @param string $skin
	 * @param array $params
	 * @return pages
	 */
	public function createNewPage($skin = null, $params = null) {
		$this->page = $this->pData->getPages_params_defaults();
		// $this->setDefaultTemplate();
		$this->setByParams($params);
		$this->setSkin($skin);
		return $this;
	}

	public function render($otherData = null) {
		$verif = $this->verify();
		if($verif->isValid()) {
			if($this->getTemplate() === null) $this->setDefaultTemplate();
			// $assets = $this->container->get('templating.helper.assets');
			$templating = $this->container->get('templating');
			if(!is_array($otherData)) $otherData = array();
			if(isset($otherData[self::FORBIDDEN_KEY])) throw new Exception("La clé \"".self::FORBIDDEN_KEY."\" ne doit pas être utilisé dans le array de données envoyées au template"." => ".__CLASS__.", ligne ".__LINE__, 1);		
			
			// echo("<h1>".$this->aetools->getSingleActionName()." = ".$this->getTemplate()."</h1>");
			return new Response($templating->render($this->getTemplate(), array_merge(array("page" => $this), $otherData)));
		} else {
			$verif->generateException();
		}
	}

	/**
	 * Définitions d'après paramtères fournis
	 */
	public function setByParams($params = null) {
		if($params === null || !is_array($params)) $params = array();
		foreach ($params as $key => $value) {
			if(array_key_exists($key, $this->pData->getPages_params_defaults())) {
				switch ($key) {
					case primarydata::TEMPLATE_PARAMS_IBOX:
						if($value instanceOf iBox) $this->insertIBox($value);
						if(is_string($value)) $this->addIBox($value);
						if(is_array($value)) foreach($value as $key => $value2) {
							if(is_string($value2)) $this->addIBox($value2);
							if($key === 'nom') {
								isset($value['type']) ? $type = $value['type'] : $type = null;
								isset($value['params']) ? $params = $value['params'] : $params = null;
								$this->addIBox($value, $type, $params);
							}
							if(is_array($value2) && isset($value2['nom']) && isset($value2['type'])) $this->addIBox($value2['nom'], $value2['type']);
							if($value2 instanceOf iBox) $this->insertIBox($value2);
						}
						break;
					case primarydata::TEMPLATE_PARAMS_MESS:
						if($value instanceOf message) $this->insertMessage($value);
						if(is_string($value)) $this->addMessage($value);
						if(is_array($value)) foreach($value as $key => $value2) {
							if(is_string($value2)) $this->addMessage($value2);
							if($key === 'nom') {
								isset($value['type']) ? $type = $value['type'] : $type = null;
								isset($value['params']) ? $params = $value['params'] : $params = null;
								$this->addMessage($value, $type, $params);
							}
							if(is_array($value2) && isset($value2['nom']) && isset($value2['type'])) $this->addMessage($value2['nom'], $value2['type']);
							if($value2 instanceOf message) $this->insertMessage($value2);
						}
						break;
					default:
						$methodSet = $this->aetools->getMethodNameWith($key, 'set');
						$methodAdd = $this->aetools->getMethodNameWith($key, 'add');
						if(method_exists($this, $methodSet)) {
							$this->$methodSet($value);
						} else if(method_exists($this, $methodAdd)) {
							if(is_array($value)) {
								foreach ($value as $key2 => $value2) $this->$methodAdd($value2);
							} else $this->$methodAdd($value);
						}
						break;
				}
			}
		}
	}

	/**
	 * Renvoie true si la page est valide
	 * @Assert\True(message = "Objet Pages invalide.")
	 * @return boolean
	 */
	public function isValid() {
		return $this->verify()->isValid();
	}

	/**
	 * Renvoie l'analyse de la page
	 * @return aeReponse
	 */
	public function verify() {
		$aeReponse = new aeReponse();
		if($this->getTemplate() === null) $aeReponse->setUnvalid("Aucun template n'a été défini.");
		foreach ($this->getMessages() as $key => $message) {
			if($message->isValid() === false) $aeReponse->setUnvalid("Le message n'est pas valide.");
		}
		foreach ($this->getIBoxs() as $key => $ibox) {
			if($ibox->isValid() === false) $aeReponse->setUnvalid("La iBox n'est pas valide.");
		}
		return $aeReponse; 
	}

	/**
	 * définit un template pour la page
	 * @param string $template
	 * @return pages
	 */
	public function setTemplate($template) {
		if($this->container->get('templating')->exists($template)) $this->page['template'] = $template;
			else throw new Exception("Le template \"".$template."\" n'existe pas.", 1);
			// else return false;
		return $this;
	}

	public function setDefaultTemplate() {
		return $this->setTemplate('laboBundle:pages:'.$this->aetools->getSingleActionName().'.html.twig');
	}

	/**
	 * Renvoie la template de la page
	 * @return string
	 */
	public function getTemplate() {
		return $this->page['template'];
	}

	/**
	 * définit une skin pour la page
	 * On peut entrer la clé ou le nom de la skin, indifféremment
	 * @param string $skin
	 * @return pages
	 */
	public function setSkin($skin = null) {
		// contrôle du skin
		$skinslist = $this->pData->getTemplate_skins_list();
		if(array_key_exists($skin, $skinslist)) {
			$this->page['skin'] = $skinslist[$skin];
		} else if(in_array($skin, $skinslist)) {
			$this->page['skin'] = array_search($skin, $skinslist);
		} else if(!isset($this->page['skin'])) {
			$this->page['skin'] = $this->pData->getTemplate_skins_default();
		}
		return $this;
	}

	/**
	 * Renvoie la skin de la page
	 * @return string
	 */
	public function getTemplate_skins_list() {
		return $this->pData->getTemplate_skins_list();
	}

	/**
	 * Renvoie la skin de la page
	 * @return string
	 */
	public function getSkin() {
		$this->setSkin();
		return $this->page['skin'];
	}

	/**
	 * Renvoie le nom de la skin de la page
	 * @return string
	 */
	public function getSkinName() {
		$this->setSkin();
		$skinslist = $this->pData->getTemplate_skins_list();
		return $skinslist[$this->page['skin']];
	}

	/**
	 * définit le headerTop pour la page
	 * @param boolean $value
	 * @return pages
	 */
	public function setHeaderTop($value) {
		$this->page['headerTop'] = $value;
		return $this;
	}

	/**
	 * Renvoie le headerTop de la page
	 * @return boolean
	 */
	public function getHeaderTop() {
		return isset($this->page['headerTop']) ? $this->page['headerTop'] : false ;
	}

	/**
	 * définit le breadcrumb pour la page
	 * @param boolean $value
	 * @return pages
	 */
	public function setBreadcrumb($value = true) {
		if($value === false) $this->page['breadcrumb'] = false;
			else $this->page['breadcrumb'] = true;
		return $this;
	}

	/**
	 * Renvoie le breadcrumb de la page
	 * @return boolean
	 */
	public function getBreadcrumb() {
		return isset($this->page['breadcrumb']) ? $this->page['breadcrumb'] : false ;
	}

	/**
	 * définit le header pour la page
	 * @param boolean $value
	 * @return pages
	 */
	public function setHeader($value = true) {
		$this->page['header'] = $value;
		return $this;
	}

	/**
	 * Renvoie le header de la page
	 * @return boolean
	 */
	public function getHeader() {
		return isset($this->page['header']) ? $this->page['header'] : false ;
	}

	/**
	 * définit le footer pour la page
	 * @param boolean $value
	 * @return pages
	 */
	public function setFooter($value = true) {
		if($value === false) $this->page['footer'] = false;
			else $this->page['footer'] = true;
		return $this;
	}

	/**
	 * Renvoie le footer de la page
	 * @return boolean
	 */
	public function getFooter() {
		return isset($this->page['footer']) ? $this->page['footer'] : false ;
	}

	/**
	 * définit le chat pour la page
	 * @param boolean $value
	 * @return pages
	 */
	public function setChat($value = true) {
		if($value === false) $this->page['chat'] = false;
			else $this->page['chat'] = true;
		return $this;
	}

	/**
	 * Renvoie le chat de la page
	 * @return boolean
	 */
	public function getChat() {
		return isset($this->page['chat']) ? $this->page['chat'] : false ;
	}

	/**
	 * définit le rightSidebar pour la page
	 * @param boolean $value
	 * @return pages
	 */
	public function setRighSidebar($value = true) {
		if($value === false) $this->page['rightSidebar'] = false;
			else $this->page['rightSidebar'] = true;
		return $this;
	}

	/**
	 * Renvoie le rightSidebar de la page
	 * @return boolean
	 */
	public function getRightSidebar() {
		return isset($this->page['rightSidebar']) ? $this->page['rightSidebar'] : false ;
	}


	/************************************************/
	/*** IBOX                                     ***/
	/************************************************/

	/**
	 * Ajoute un nouveau iBox pour la page
	 * @param array $params 
	 * @return iBox
	 */
	public function addIBox($params = null) {
		if(is_array($params)) $params = new iBox($params);
		if($params instanceOf iBox) return $this->insertIBox($params);
		return false;
	}

	/**
	 * Ajoute un objet iBox pour la page
	 * @param iBox $iBox
	 * @return iBox
	 */
	public function insertIBox(iBox $iBox) {
		// $iBoxVerify = aeReponse
		$iBoxVerify = $iBox->verify();
		if($iBoxVerify->isValid()) {
			$nom = $iBox->getNom();
			$this->page[primarydata::TEMPLATE_PARAMS_IBOX]->set($nom, $iBox);
			return $this->getIBox($nom);
		} else {
			$iBoxVerify()->generateException();
		}
	}

	/**
	 * Renvoie les iBox de la page
	 * @return arrayCollection
	 */
	public function getIBoxs() {
		return $this->page[primarydata::TEMPLATE_PARAMS_IBOX];
	}

	/**
	 * Renvoie la iBox $nom
	 * @return iBox
	 */
	public function getIBox($nom) {
		return $this->page[primarydata::TEMPLATE_PARAMS_IBOX]->get($nom);
	}

	/**
	 * Renvoie les noms des iBox
	 * @return array
	 */
	public function getIBoxKeys() {
		return $this->page[primarydata::TEMPLATE_PARAMS_IBOX]->getKeys();
	}


	/************************************************/
	/*** MESSAGES                                 ***/
	/************************************************/

	/**
	 * Ajoute un nouveau message pour la page
	 * il est possible de mettre directement un objet message dans le premier paramètre (dans ce cas, les autres paramètres ne sont pas pris en compte)
	 * @param mixed $title
	 * @param string $texte
	 * @param string $type
	 * @param array $params
	 * @return message
	 */
	public function addMessage($title, $texte = null, $type = null, $params = null) {
		if($title instanceOf message) {
			return $this->insertMessage($title);
		} else {
			return $this->insertMessage(new message($title, $texte, $type, $params));
		}
		// return false;
	}

	/**
	 * Ajoute un objet message pour la page
	 * @param message $message
	 * @return message
	 */
	public function insertMessage(message $message) {
		if($message->isValid()) {
			$nom = $this->index_message++;
			$this->page[primarydata::TEMPLATE_PARAMS_MESS]->set($nom, $message);
			return $nom;
		} else {
			$message->verify()->generateException();
			return false;
		}
	}

	/**
	 * Renvoie les messages de la page
	 * @param boolean $toArray - true : renvoie un array au lieu d'un arrayCollection
	 * @return arrayCollection / array
	 */
	public function getMessages($toArray = false) {
		// echo('<h2>GetMessages() =&gt; "	'.primarydata::TEMPLATE_PARAMS_MESS.'"</h2>');
		// echo('<pre>');
		// var_dump($this->page[primarydata::TEMPLATE_PARAMS_MESS]);
		// echo('</pre>');
		return $toArray ? $this->page[primarydata::TEMPLATE_PARAMS_MESS]->toArray() : $this->page[primarydata::TEMPLATE_PARAMS_MESS];
	}

	public function getMessagesArrays() {
		$array = array();
		foreach ($this->getMessages() as $key => $message) {
			$array[] = $message->getMessage();
		}
		return array("messages" => $array);
	}

	/**
	 * Renvoie le message $nom / false si aucune
	 * @return message
	 */
	public function getMessage($nom) {
		return in_array($nom, $this->getMessageKeys()) ? $this->page[primarydata::TEMPLATE_PARAMS_MESS]->get($nom) : false;
	}

	/**
	 * Renvoie le messages $nom
	 * @return array
	 */
	public function getMessageKeys() {
		return $this->page[primarydata::TEMPLATE_PARAMS_MESS]->getKeys();
	}





}