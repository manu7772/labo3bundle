<?php

namespace laboBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
// aeReponse
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;
// User forms
// use AcmeGroup\UserBundle\Form\Type\ProfileFormType;
// use AcmeGroup\UserBundle\Form\Type\RegistrationFormType;

class laboController extends Controller {

	//////////////////////////
	// PAGES
	//////////////////////////

	// Page d'accueil de l'admin (labo)
	public function homeAction() {
		return $this->render('laboBundle:pages:index.html.twig');
	}

	public function navbarAction() {
		return $this->render('laboBundle:menus:navbar.html.twig');
	}

}
