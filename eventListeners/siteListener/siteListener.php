<?php

namespace labo\Bundle\TestmanuBundle\eventListeners\siteListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class siteListener {

	private $container;
	private $itClass = array();
	private $serviceMethode = "serviceEventInit";
	private $items = array(	// entités à initialiser
		// "acmeGroup.aetools",
		// "acmeGroup.entities",
		// "acmeGroup.parametre",
		// "acmeGroup.version",
		// "acmeGroup.pageweb",
		// "acmeGroup.categorie",
		// "acmeGroup.directeditor",
		);

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
		$this->serviceSess = $this->container->get('request')->getSession();
		foreach($this->items as $item) {
			$this->itClass[$item] = $this->container->get($item);
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
	// private function aff($event) {
	// 	// affichage :
	// 	if(strtolower($this->container->get('acmeGroup.aetools')->getEnv()) !== "prod") {
	// 		echo('MASTER_REQUEST : '.HttpKernelInterface::MASTER_REQUEST."<br >");
	// 		echo('getRequestType : '.$event->getRequestType()."<br >");
	// 	}
	// }

}