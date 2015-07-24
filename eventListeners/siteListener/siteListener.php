<?php

namespace laboBundle\eventListeners\siteListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
// services
use laboBundle\services\framework\pagesModules\primarydata;

use \Exception;

class siteListener {

	const SERVICE_METHODE = "serviceEventInit";

	protected $container;
	protected $itClass = array();
	protected $serviceMethode;
	protected $translator;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
		$this->aetools = $this->container->get('labobundle.aetools');
		$this->serviceSess = $this->container->get('request')->getSession();
		$this->serviceMethode = self::SERVICE_METHODE;
		$this->translator = $this->container->get('translator');
		// chargement des paramètres
		$launch_service = $this->aetools->getConfig('launch_service');
		// echo('<pre><h3>SERVICES</h3>');
		// var_dump($launch_service);
		// echo('</pre>');
		if(isset($launch_service['activate'])) {
			if($launch_service['activate'] === true) {
				foreach($launch_service['resources']['services'] as $item) {
					$this->itClass[$item['name']] = $this->container->get($item['name']);
				}
			} else {
				throw new Exception($this->translator->trans("loader.services_disabled", array(), 'validators'), 1);
			}
		} else {
			throw new Exception($this->translator->trans("loader.no_service", array(), 'validators'), 1);
		}
	}

	public function load_session_items(FilterControllerEvent $event) {
		// Réinitialisation du reloadAll --> rechargement forcé de tous les services
		// --> si un service active reloadAll (à true), alors tous les services SUIVANTS* sont également forcés au rechargement SI celui-ci est rechargé *(ordre dans $this->items)
		//     pour forcer le rechargement (SI rechargé), mettre $this->eventReloadForcer = true dans son constructeur.
		// $this->serviceSess->set("siteListener", array("reloadAll" => false));
		// $this->aff($event);
		// Chargement des services, dans l'ordre de $this->items
		$serviceMethode = $this->serviceMethode;
		if(HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
			// primarydata
			$primarydata = new primarydata();
			if(method_exists($primarydata, $serviceMethode) && ($this->aetools->getBundleShortName() === $this->aetools->getConfig('labo_bundle'))) {
				// echo('Init Primary -> only for Labo');
				$primarydata->$serviceMethode($event);
			}
			// initialisation des services
			foreach($this->itClass as $nom => $item) {
				if(method_exists($this->itClass[$nom], $serviceMethode)) {
					$this->itClass[$nom]->$serviceMethode($event);
				}
			}
		}
	}

	// public function load_session_aelog(InteractiveLoginEvent $event) {
		// if(HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
			// aelogs
			// $this->container->get('acmeGroup.aelog')->createNewLoginUser($event->getAuthenticationToken()->getUser());
		// }
	// }

	// TEST DEV
	// protected function aff($event) {
	// 	// affichage :
	// 	if(strtolower($this->container->get('acmeGroup.aetools')->getEnv()) !== "prod") {
	// 		echo('MASTER_REQUEST : '.HttpKernelInterface::MASTER_REQUEST."<br >");
	// 		echo('getRequestType : '.$event->getRequestType()."<br >");
	// 	}
	// }

}