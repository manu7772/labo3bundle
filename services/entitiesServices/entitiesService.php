<?php
// labo/Bundle/TestmanuBundle/services/entitiesServices/entitesService.php

nameSpace labo\Bundle\TestmanuBundle\services\entitiesServices;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
// use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

class entitesService {

	const ASLASH = '\\';

	protected $container;					// container
	protected $em;							// entity_manager
	protected $repo;						// repository
	// Mode fixtures
	protected $modeFixtures = null;			// fixtures

	protected $listOfEnties = null;			// liste des entités de src
	protected $completeListOfEnties = null;	// liste des entités complète



	public function __construct(ContainerInterface $container) {
		$this->container 		= $container;
		$this->router 			= $this->container->get('router');
		$this->aetools 			= $this->container->get('acmeGroup.aetools');
		$this->asset 			= $this->container->get('templating.helper.assets');
		$this->getEm();

		// Détection automatique du mode FIXTURES
		if($this->detectHorsController() === false) {
			$this->serviceRequ 		= $this->container->get('request');
			$this->serviceSess 		= $this->container->get('request')->getSession();
			$this->sessionData		= $this->container->get("session");
			$this->flashBag 		= $this->sessionData->getFlashBag();
			$this->securityContext 	= $this->container->get('security.context');
			$this->route 			= $this->container->get("request")->attributes->get('_route');
			$this->version = $this->sessionData->get('version');
		}
	}

	/**
	 * détecte si le controller est inaccessible
	 * @return boolean
	 */
	protected function detectHorsController() {
		if($this->modeFixtures === null) {
			if($this->container->get("request")->attributes->get('_controller') === null) {
				$this->modeFixtures = true;
			} else {
				$this->modeFixtures = false;
			}
		}
		return $this->modeFixtures;
	}

	/**
	 * Renvoie un array des entités contenues dans src. 
	 * Sous la forme liste[nom] = namespace
	 * @return array
	 */
	public function listOfEnties() {
		if($this->listOfEnties === null) {
			$this->listOfEnties = array();
			// recherche de tous les dossiers de src/ (donc tous les groupes de bundles)
			$groupesSRC = $this->aetools->exploreDir("src/", null, "dossiers", true);
			$groupes = array();
			foreach($groupesSRC as $nom) $groupes[] = $nom['nom'];
			$entitiesNameSpaces = $this->em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
			foreach($entitiesNameSpaces as $ENS) {
				$EE = explode(self::ASLASH, $ENS);
				if(in_array($EE[0], $groupes)) $this->listOfEnties[$EE[count($EE) - 1]] = $ENS;
			}
		}
		return $this->listOfEnties;
	}

	/**
	 * Renvoie un array des entités. 
	 * Sous la forme liste[nom] = namespace
	 * @return array
	 */
	public function completeListOfEnties() {
		if($this->completeListOfEnties === null) {
			$this->completeListOfEnties = array();
			$entitiesNameSpaces = $this->em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
			foreach($entitiesNameSpaces as $ENS) {
				$EE = explode(self::ASLASH, $ENS);
				if(in_array($EE[0], $groupes)) $this->completeListOfEnties[$EE[count($EE) - 1]] = $ENS;
			}
		}
		return $this->completeListOfEnties;
	}



}

?>
