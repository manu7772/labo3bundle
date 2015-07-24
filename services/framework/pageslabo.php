<?php
namespace laboBundle\services\framework;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
// tools
use laboBundle\services\framework\pages;
use laboBundle\services\framework\pagesModules\primarydata;
use laboBundle\services\framework\pagesModules\ibox;
use laboBundle\services\framework\pagesModules\message;

use \Exception;

class pageslabo extends pages {


	// public function __construct(ContainerInterface $container) {
	// 	return parent::__construct($container);
	// }

	/**
	 * Appel de pages
	 * @param string $method
	 * @param mixed $arguments
	 * @return pages
	 */
	public function __call($method, $arguments) {
		if(preg_match('#^getPage#', $method)) {
			$thismethod = preg_replace('#^getPage#', 'page', $method);
			if(method_exists($this, $thismethod)) return $this->$thismethod($arguments);
		}
	}


	/*****************************/
	/** PAGES
	/*****************************/

	/**
	 * Page d'accueil Main
	 * 
	 * @return pages
	 */
	protected function pageHome() {
		$this
			->setSkin('Classic')
			->setHeaderTop(true)
			->setBreadcrumb(false)
			->setHeader(false)
			->setFooter(true)
			->setChat(false)
			->setRighSidebar(false)
		;
		return $this;
	}



	/**
	 * Page tags
	 * @return pages
	 */
	protected function pageBackend() {
		$this
			->setSkin('Classic')
			->setHeaderTop(true)
			->setBreadcrumb(false)
			->setHeader(true)
			->setFooter(false)
			->setChat(false)
			->setRighSidebar(false)
		;
		// 
		return $this;
	}

	/**
	 * Page tags
	 * @return pages
	 */
	protected function pageTag() {
		$this
			->setSkin('Classic')
			->setHeaderTop(true)
			->setBreadcrumb(false)
			->setHeader(true)
			->setFooter(false)
			->setChat(false)
			->setRighSidebar(false)
		;
		// 
		return $this;
	}

	/*****************************/
	/** TESTS
	/*****************************/

	/**
	 * Tests de messages
	 * 
	 * @return pages
	 */
	protected function page1() {
		$this
			->setSkin('Classic')
			->setHeaderTop(true)
			->setBreadcrumb(false)
			->setHeader(array(
				'type'		=> 'html',
				'content'	=> '<h1>Header de la page <small>et son sous-titre</small></h1>',
				))
			->setFooter(true)
			->setChat(false)
			->setRighSidebar(true);
		$this->addMessage(array(
			'type'			=> 'info',
			'title'			=> 'Bienvenue !',
			'texte'			=> 'Découvrez la nouvelle interface d\'administration VEX-bko',
			'closeButton'	=> true,
			'progressBar'	=> false,
			'showMethod'	=> 'fadeIn',
			'timeOut'		=> 3000,
		));
		$this->addMessage(array(
			'type'			=> 'error',
			'title'			=> 'Encore raté !',
			'texte'			=> 'Hé oui, c\'est encore raté pour cette fois-ci !',
			'closeButton'	=> true,
			'progressBar'	=> false,
			'showMethod'	=> 'slideDown',
			'timeOut'		=> 10000,
		));

		// iBox
		$iBox1 = $this->addIBox(array(
				primarydata::IBOX_PARAMS_TYPE		=> primarydata::IBOX_TYPE_HTML, // 'html'
				'title'		=> 'Sébastien Lecerf',
				'tools'		=> array(
					'home' => $this->generateUrl('vex-bko-home'),
					'test1' => $this->generateUrl('vex-bko-test1'),
					'menuX' => 'divider',
					'test2' => $this->generateUrl('vex-bko-test2'),
				)
			)
		);
		$iBox1->setTitle('Sébastien Lecerf - titre modifié');
		$iBox1->addLabel(array(
			'texte'		=> 'OK',
			'style'		=> 'warning',
		));
		$iBox1->setSize(4);
		$iBox1->setTools(array(
				'home' => $this->generateUrl('vex-bko-home'),
				'test1' => $this->generateUrl('vex-bko-test1'),
				'menuX' => 'divider',
				'test2' => $this->generateUrl('vex-bko-test2'),
			)
		);
		$iBox1->addBottomButton(array(
				'texte'			=> 'Voir en détails',
				'icon'			=> 'fa-bicycle',
				'type'			=> 'primary',
				'url'			=> $this->generateUrl('vex-bko-home')
			)
		);
		// iBox 2
		$iBox2 = $this->addIBox(array(
				primarydata::IBOX_PARAMS_TYPE		=> primarydata::IBOX_TYPE_HTML, // 'html'
				'title'		=> 'Nouveaux rapports 2',
				'tools'		=> array(
					'home' => $this->generateUrl('vex-bko-home'),
					'test1' => $this->generateUrl('vex-bko-test1'),
					'menuX' => 'divider',
					'test2' => $this->generateUrl('vex-bko-test2'),
				)
			)
		);
		$iBox1->setSize(6);

		return $this;
	}

	/**
	 * Skin aléatoire
	 * 
	 * @return pages
	 */
	protected function page2() {
		$skins = $this->getTemplate_skins_list();
		shuffle($skins);
		$this
			->setSkin(next($skins))
			->setHeaderTop(false)
			->setBreadcrumb(false)
			->setHeader(false)
			->setFooter(false)
			->setChat(false)
			->setRighSidebar(false);
		return $this;
	}




}