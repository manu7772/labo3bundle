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

	const DEFAULT_VIEWS = "pages";
	const DEFAULT_BUNDLE = "laboBundle";
	const DEFAULT_MENUS = "menus";

	//////////////////////////
	// PAGES
	//////////////////////////

	// Page d'accueil de l'admin (labo)
	public function homeAction() {
		return $this->render(self::DEFAULT_BUNDLE.':'.self::DEFAULT_VIEWS.':index.html.twig');
	}

	public function navbarAction() {
		return $this->render(self::DEFAULT_BUNDLE.':'.self::DEFAULT_MENUS.':navbar.html.twig');
	}

}
