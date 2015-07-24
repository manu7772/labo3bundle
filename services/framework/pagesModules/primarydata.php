<?php
namespace laboBundle\services\framework\pagesModules;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;

use \Exception;
use \DateTime;
use \ReflectionClass;

class primarydata {

	const SLASH = "/";
	const ASLASH = "\\";

	// const NOM_MESSAGE				= 'message';
	// const NOM_IBOX					= 'ibox';

	const TEMPLATE_SKIN_0			= "skin-0";
	const TEMPLATE_SKIN_1			= "skin-1";
	const TEMPLATE_SKIN_2			= "skin-2";
	const TEMPLATE_SKIN_3			= "skin-3";

	const TEMPLATE_PARAMS_TMPL		= 'template';
	const TEMPLATE_PARAMS_SKIN		= 'skin';
	const TEMPLATE_PARAMS_TITL		= 'title';
	const TEMPLATE_PARAMS_H1		= 'h1';
	const TEMPLATE_PARAMS_KEYW		= 'keywords';
	const TEMPLATE_PARAMS_HDTP		= 'headerTop';
	const TEMPLATE_PARAMS_BREAD		= 'breadcrumb';
	const TEMPLATE_PARAMS_HEAD		= 'header';
	const TEMPLATE_PARAMS_FOOT		= 'footer';
	const TEMPLATE_PARAMS_RTSB		= 'rightSidebar';
	const TEMPLATE_PARAMS_CHAT		= 'chat';
	const TEMPLATE_PARAMS_IBOX		= 'ibox';
	const TEMPLATE_PARAMS_MESS		= 'message';

	const MESSAGES_SUCCESS			= "success";
	const MESSAGES_INFO				= "info";
	const MESSAGES_WARNING			= "warning";
	const MESSAGES_ERROR			= "error";

	const MESSAGES_PARAMS_TEXTE		= 'texte';
	const MESSAGES_PARAMS_TYPE		= 'type';
	const MESSAGES_PARAMS_TITLE		= 'title';
	const MESSAGES_PARAMS_SHWMTD	= 'showMethod';
	const MESSAGES_PARAMS_HIDMTD	= 'hideMethod';
	const MESSAGES_PARAMS_SHWEAS	= 'showEasing';
	const MESSAGES_PARAMS_HIDEAS	= 'hideEasing';
	const MESSAGES_PARAMS_SHWDUR	= 'showDuration';
	const MESSAGES_PARAMS_HIDDUR	= 'hideDuration';
	const MESSAGES_PARAMS_POSCLS	= 'positionClass';
	const MESSAGES_PARAMS_CLSBTN	= 'closeButton';
	const MESSAGES_PARAMS_PRGBAR	= 'progressBar';
	const MESSAGES_PARAMS_DEBUGG	= 'debug';
	const MESSAGES_PARAMS_TIMOUT	= 'timeOut';
	const MESSAGES_PARAMS_EXTIMO	= 'extendedTimeOut';
	const MESSAGES_PARAMS_CLSHTM	= 'closeHtml';
	const MESSAGES_PARAMS_NEWTOP	= 'newestOnTop';

	const ELEMENTS_DEFAULT			= "default";
	const ELEMENTS_PRIMARY			= "primary";
	const ELEMENTS_SUCCESS			= "success";
	const ELEMENTS_WARNING			= "warning";
	const ELEMENTS_DANGER			= "danger";
	const ELEMENTS_INFO				= "info";

	const IBOX_TYPE_HTML			= 'html';
	const IBOX_TYPE_LIST			= 'list';
	const IBOX_TYPE_BLCK			= 'twigblock';

	const IBOX_BUTTON_TEXT			= "texte";
	const IBOX_BUTTON_ICON			= "icon";
	const IBOX_BUTTON_TYPE			= "type";
	const IBOX_BUTTON_URL			= "url";

	const IBOX_PARAMS_NOM			= 'nom';
	const IBOX_PARAMS_LABEL			= 'labels';
	const IBOX_PARAMS_HEADG			= 'headings';
	const IBOX_PARAMS_BTMTB			= 'bottomButton';
	const IBOX_PARAMS_TITLE			= 'title';
	const IBOX_PARAMS_TYPE			= 'type';
	const IBOX_PARAMS_TOOLS			= 'tools';
	const IBOX_PARAMS_SIZE			= "size";
	const IBOX_PARAMS_CTTTL			= 'content_title';
	const IBOX_PARAMS_CTHTM			= 'content_html';
	const IBOX_PARAMS_CTLST			= 'content_list';

	protected $data;
	protected $container;

	public function __construct(ContainerInterface $container = null) {
		$this->container = $container;
		$this->data = array();
		$this->initData();
		return $this;
	}

	/**
	 * Initialisation des données du site
	 * @return array
	 */
	protected function initData() {

		// if($this->container instanceOf ContainerInterface) {
		// 	echo("chargement depuis la session…<br>");
		// 	$this->data = $this->container->get('request')->getSession()->get($this->getName());
		// }

		if(count($this->data) < 1) {
			// echo("chargement depuis paramètres d'origine…<br>");
			// SKINS
			$this->data['template']['skins']['list'] = array(
				self::TEMPLATE_SKIN_0			=> "Classic",
				self::TEMPLATE_SKIN_1			=> "BlueLight",
				self::TEMPLATE_SKIN_2			=> "InspiniaUltra",
				self::TEMPLATE_SKIN_3			=> "YellowPurple",
			);
			$this->data['template']['skins']['keys'] = array_keys($this->data['template']['skins']['list']);
			$this->data['template']['skins']['default'] = reset($this->data['template']['skins']['keys']);
	
			// PAGES
			$this->data['pages']['params']['defaults'] = array(
				self::TEMPLATE_PARAMS_TMPL		=> null,
				self::TEMPLATE_PARAMS_SKIN		=> $this->getTemplate_skins_default(),
				self::TEMPLATE_PARAMS_TITL		=> 'title',
				self::TEMPLATE_PARAMS_H1		=> 'h1',
				self::TEMPLATE_PARAMS_KEYW		=> '',
				self::TEMPLATE_PARAMS_HDTP		=> true,
				self::TEMPLATE_PARAMS_BREAD		=> true,
				self::TEMPLATE_PARAMS_HEAD		=> false,
				self::TEMPLATE_PARAMS_FOOT		=> true,
				self::TEMPLATE_PARAMS_RTSB		=> false,
				self::TEMPLATE_PARAMS_CHAT		=> true,
				self::TEMPLATE_PARAMS_IBOX		=> new ArrayCollection(),
				self::TEMPLATE_PARAMS_MESS		=> new ArrayCollection(),
			);
			$this->data['pages']['params']['keys'] = array_keys($this->data['pages']['params']['defaults']);
	
			// MESSAGES
			$this->data['messages']['types']['liste'] = array(
				self::MESSAGES_SUCCESS			=> "Succès",
				self::MESSAGES_INFO				=> "Information",
				self::MESSAGES_WARNING			=> "Alerte",
				self::MESSAGES_ERROR			=> "Erreur",
			);
			$this->data['messages']['types']['keys'] = array_keys($this->data['messages']['types']['liste']);
			// var_dump($this->data['messages']['types']['keys']);
			$this->data['messages']['params']['defaults'] = array(
				self::MESSAGES_PARAMS_TEXTE		=> "Texte",
				self::MESSAGES_PARAMS_TYPE		=> $this->getMessage_type_default(),
				self::MESSAGES_PARAMS_TITLE		=> "Titre",
				self::MESSAGES_PARAMS_SHWMTD	=> "slideDown",
				self::MESSAGES_PARAMS_HIDMTD	=> "fadeOut",
				self::MESSAGES_PARAMS_SHWEAS	=> "swing",
				self::MESSAGES_PARAMS_HIDEAS	=> "swing",
				self::MESSAGES_PARAMS_SHWDUR	=> 400,
				self::MESSAGES_PARAMS_HIDDUR	=> 1000,
				self::MESSAGES_PARAMS_POSCLS 	=> "toast-top-right",
				self::MESSAGES_PARAMS_CLSBTN	=> false,
				self::MESSAGES_PARAMS_PRGBAR	=> true,
				self::MESSAGES_PARAMS_DEBUGG	=> false,
				self::MESSAGES_PARAMS_TIMOUT	=> 6000,
				self::MESSAGES_PARAMS_EXTIMO	=> 1000,
				self::MESSAGES_PARAMS_CLSHTM	=> htmlentities("<button type='button'>&times;</button>", ENT_QUOTES),
				self::MESSAGES_PARAMS_NEWTOP	=> false,
			);
			$this->data['messages']['params']['keys'] = array_keys($this->data['messages']['params']['defaults']);
			// var_dump($this->data['messages']['params']['keys']);
	
			// ÉLÉMENTS (BOOTSTRAP)
			$this->data['elements']['types']['liste'] = array(
				// IMPORTANT : le premier élément de la liste est l'élément par défaut
				self::ELEMENTS_PRIMARY			=> "Primaire",
				self::ELEMENTS_DEFAULT			=> "Défaut",
				self::ELEMENTS_SUCCESS			=> "Succès",
				self::ELEMENTS_WARNING			=> "Alerte",
				self::ELEMENTS_DANGER			=> "Danger",
				self::ELEMENTS_INFO				=> "Information",
			);
			$this->data['elements']['types']['keys'] = array_keys($this->data['elements']['types']['liste']);
	
			// IBOX
			$this->data['ibox']['types']['liste'] = array(
				self::IBOX_TYPE_HTML			=> "Html",
				self::IBOX_TYPE_LIST			=> "Liste",
				self::IBOX_TYPE_BLCK			=> "Block twig",
			);
			$this->data['ibox']['types']['keys'] = array_keys($this->data['ibox']['types']['liste']);
			$this->data['ibox']['params']['defaults'] = array(
				self::IBOX_PARAMS_NOM			=> null,
				self::IBOX_PARAMS_LABEL			=> new ArrayCollection(),
				self::IBOX_PARAMS_HEADG			=> new ArrayCollection(),
				self::IBOX_PARAMS_BTMTB			=> new ArrayCollection(),
				self::IBOX_PARAMS_TITLE			=> "",
				self::IBOX_PARAMS_TYPE			=> $this->getIbox_default_type(),
				self::IBOX_PARAMS_TOOLS			=> array(),
				self::IBOX_PARAMS_SIZE			=> 4,
				self::IBOX_PARAMS_CTTTL			=> '',
				self::IBOX_PARAMS_CTHTM			=> '',
				self::IBOX_PARAMS_CTLST			=> new ArrayCollection(),
			);
			$this->data['ibox']['params']['keys'] = array_keys($this->data['ibox']['params']['defaults']);
			$this->data['ibox']['button']['defaults'] = array(
				self::IBOX_BUTTON_TEXT			=> "OK",
				self::IBOX_BUTTON_ICON			=> "fa-caret-right",
				self::IBOX_BUTTON_TYPE			=> reset($this->data['elements']['types']['keys']),
				self::IBOX_BUTTON_URL			=> "#",
			);
			$this->data['ibox']['button']['keys'] = array_keys($this->data['ibox']['button']['defaults']);
		}
		return $this->data;
	}

	public function getAllData() {
		return $this->data;
	}


	/**********************/
	/* PAGE.PARAMETRES    */
	/**********************/

	/**
	 * Renvoie la liste des types de messages
	 * @return array
	 */
	public function getPages_params_defaults() {
		return $this->data['pages']['params']['defaults'];
	}

	/**
	 * Renvoie la liste des types de messages
	 * @return array
	 */
	public function getPages_params_keys() {
		return $this->data['pages']['params']['keys'];
	}

	/**********************/
	/* MESSAGES           */
	/**********************/

	/**
	 * Renvoie la liste des clés de types de messages
	 * @return array
	 */
	public function getMessages_types_keys() {
		return $this->data['messages']['types']['keys'];
	}

	/**
	 * Renvoie le type de message par défaut
	 * @return array
	 */
	public function getMessage_type_default() {
		$message_type_default = $this->getMessages_types_keys();
		return reset($message_type_default);
	}

	/**
	 * Renvoie la liste des types de messages
	 * @return array
	 */
	public function getMessages_types_liste() {
		return $this->data['messages']['types']["liste"];
	}

	/**
	 * Renvoie les paramètres par défaut de messages
	 * @return array
	 */
	public function getMessages_params_defaults() {
		return $this->data['messages']['params']['defaults'];
	}

	/**
	 * Renvoie la liste paramètres pour messages
	 * @return array
	 */
	public function getMessages_params_keys() {
		return $this->data['messages']['params']['keys'];
	}

	/**********************/
	/* IBOX.ELEMENTS      */
	/**********************/

	/**
	 * Renvoie la liste des clés de types d'elements
	 * @return array
	 */
	public function getElements_types_keys() {
		return $this->data['elements']['types']['keys'];
	}

	/**
	 * Renvoie la liste des types d'elements
	 * @return array
	 */
	public function getElements_types_liste() {
		return $this->data['elements']['types']['liste'];
	}

	/**
	 * Renvoie la liste des clés de types d'ibox
	 * @return array
	 */
	public function getIbox_types_keys() {
		return $this->data['ibox']['types']['keys'];
	}

	/**
	 * Renvoie le type d'ibox par défaut
	 * @return array
	 */
	public function getIbox_default_type() {
		$types = $this->getIbox_types_keys();
		return reset($types);
	}

	/**
	 * Renvoie la liste des types d'ibox
	 * @return array
	 */
	public function getIbox_types_liste() {
		return $this->data['ibox']['types']['liste'];
	}

	/**********************/
	/* IBOX.BUTTON        */
	/**********************/

	/**
	 * Renvoie la liste des clés de types d'ibox
	 * @return array
	 */
	public function getIbox_button_keys() {
		return $this->data['ibox']['button']['keys'];
	}

	/**
	 * Renvoie la liste des types d'ibox
	 * @return array
	 */
	public function getIbox_button_defaults() {
		return $this->data['ibox']['button']['defaults'];
	}

	/**
	 * Renvoie la liste des paramètres d'ibox
	 * @return array
	 */
	public function getIbox_params_defaults() {
		return $this->data['ibox']['params']['defaults'];
	}

	/**
	 * Renvoie la liste des clés de paramètres d'ibox
	 * @return array
	 */
	public function getIbox_params_keys() {
		return $this->data['ibox']['params']['keys'];
	}


	/**********************/
	/* TEMPLATE.SKIN      */
	/**********************/

	/**
	 * Renvoie la liste des clés des skins de templates
	 * @return array
	 */
	public function getTemplate_skins_keys() {
		return $this->data['template']['skins']['keys'];
	}

	/**
	 * Renvoie la liste des skins de templates
	 * @return array
	 */
	public function getTemplate_skins_list() {
		return $this->data['template']['skins']['list'];
	}

	/**
	 * Renvoie la liste des skins de templates
	 * @return array
	 */
	public function getTemplate_skins_default() {
		return $this->data['template']['skins']['default'];
	}






	/**********************/
	/* INFO               */
	/**********************/

	/**
	 * Renvoie le nom de l'entité
	 * @return string
	 */
	public function getClassName() {
		return get_called_class();
	}

	/**
	 * Renvoie le nom de l'entité
	 * @return string
	 */
	public function getName() {
		return $this->getSimpleNameFromString($this->getClassName());
	}

	/**
	 * Renvoie le nom court de l'entité
	 * @param string $longName
	 * @return string
	 */
	public function getSimpleNameFromString($longName) {
		if($longName === false) return $longName;
		$longName = explode(self::ASLASH, $longName);
		return end($longName);
	}

	/**
	* Initialise le service
	* Attention : cette méthode est à appeler en requête (principale) par EventListener !!!
	* @param FilterControllerEvent $event
	*/
	public function serviceEventInit(FilterControllerEvent $event) {
		if($this->getName() !== false)
			$event->getRequest()->getSession()->set($this->getName(), $this->initData());
	}

}