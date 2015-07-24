<?php

namespace laboBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
// aeReponse
use laboBundle\services\aetools\aeReponse;
// User forms
// use AcmeGroup\UserBundle\Form\Type\ProfileFormType;
// use AcmeGroup\UserBundle\Form\Type\RegistrationFormType;

class laboController extends Controller {

	const DEFAULT_BUNDLE 	= "laboBundle";
	const DEFAULT_VIEWS 	= "pages";
	const DEFAULT_MENUS 	= "menus";

	//////////////////////////
	// PAGES
	//////////////////////////

	/**
	 * Page d'accueil de l'admin (labo)
	 * @return Response
	 */
	public function indexAction() {
		$page = $this->get('labobundle.pages.labo');
		$page->getPageHome();
		$data['version'] = $this->get('labobundle.entities')->getCurrentVersion();
		// return $this->render(self::DEFAULT_BUNDLE.':'.self::DEFAULT_VIEWS.':index.html.twig', array('page' => $page));
		return $page->render($data);
	}

	/**
	 * Page de menu commerce
	 * @return Response
	 */
	public function commerceAction() {
		$data = array();
		$page = $this->get('labobundle.pages.labo');
		$page->getPageHome();
		$data['version'] = $this->get('labobundle.entities')->getCurrentVersion();
		return $page->render($data);
	}

	/**
	 * Actions sur articles
	 * @return Response
	 */
	public function articleAction($id = null, $action = 'view') {
		$page = $this->get('labobundle.pages.labo');
		$page->getPageHome();
		$data = array();
		$data['version'] = $this->get('labobundle.entities')->getCurrentVersion();
		switch ($action) {
			case 'edit':
				# code...
				break;
			
			default: // view
				$em = $this->getDoctrine()->getManager();
				$repo = $em->getRepository("AcmeGroup\\LaboBundle\\Entity\\article");
				$data['articles'] = $repo->findAll();
				break;
		}
		return $page->render($data);
	}


}
