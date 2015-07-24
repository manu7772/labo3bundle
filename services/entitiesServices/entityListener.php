<?php
// laboBundle/services/entitiesServices/entityListener.php

namespace laboBundle\services\entitiesServices;

use Doctrine\Common\EventSubscriber; 
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
// aetools
use laboBundle\services\aetools\aetools;

use AcmeGroup\LaboBundle\Entity\version;

use \DateTime;

class entityListener implements EventSubscriber {

	// const ENTITY_IMAGE_BASENAME = 'base_entity_image';

	protected $eventArgs;
	protected $em;
	protected $repoVersion;
	protected $currentVersion;
	protected $ession;
	protected $repo;
	protected $entity;
	protected $container;
	protected $entityName;
	protected $entityNameSpace;
	protected $uow;

	protected $entityService;		// service entité
	protected $aetools;
	protected $imagetools;
	protected $creation = false;
	protected $EmSaved = true;
	protected $savedEm;

	public function __construct(ContainerInterface $container) {
		// parent::__construct();
		$this->container = $container;
	}

	public function getSubscribedEvents() {
		return array(
			'postLoad',
			'prePersist',
			'postPersist',
			'preUpdate',
			'postUpdate',
			'preRemove',
			'postRemove'
		);
	}
	public function postLoad(LifecycleEventArgs $eventArgs) {
		$this->defineDefaultsTools($eventArgs);
		$this->postLoadActions();
	}
	public function prePersist(LifecycleEventArgs $eventArgs) {
		$this->defineDefaultsTools($eventArgs);
		$this->prePersistActions();
	}
	public function postPersist(LifecycleEventArgs $eventArgs) {
		$this->defineDefaultsTools($eventArgs);
		$this->postPersistActions();
	}
	public function preUpdate(PreUpdateEventArgs $eventArgs) {
		$this->defineDefaultsTools($eventArgs);
		$this->preUpdateActions();
	}
	public function postUpdate(LifecycleEventArgs $eventArgs) {
		$this->defineDefaultsTools($eventArgs);
		$this->postUpdateActions();
	}
	public function preRemove(LifecycleEventArgs $eventArgs) {
		$this->defineDefaultsTools($eventArgs);
		$this->preRemoveActions();
	}
	public function postRemove(LifecycleEventArgs $eventArgs) {
		$this->defineDefaultsTools($eventArgs);
		$this->postRemoveActions();
	}

	/**
	 * defineDefaultsTools
	 * Initialise EntityManager et Repository
	 * @param LifecycleEventArgs $eventArgs
	 */
	public function defineDefaultsTools(LifecycleEventArgs $eventArgs) {
		$this->entity = $eventArgs->getEntity();
		$this->entityNameSpace = get_class($this->entity);
		$this->em = $eventArgs->getEntityManager();
		$this->eventArgs = $eventArgs;
		$this->aetools = $this->container->get("labobundle.aetools");
		$this->entityService = $this->container->get("labobundle.entities");
		$this->entityService->defineEntity($this->entityNameSpace);
		$this->entityName = $this->entityService->getEntityShortName();
		$this->repo = $this->entityService->getRepo();
		$this->uow = $this->em->getUnitOfWork();
		// détection fixtures :
		$this->currentVersion = false;
		if($this->aetools->isControllerPresent()) {
			// pas en mode fixtures
			// $this->session = $this->container->get('session')->get($this->entityService->getVersionEntityShortName());
			// $ver = $this->entityService->getCurrentVersionSlug();
			$this->repoVersion = $this->em->getRepository($this->entityService->getVersionEntityClassName());
			// $this->repoVersion = $this->em->getRepository("AcmeGroup\\LaboBundle\\Entity\\version");
			$cv = $this->repoVersion->findBySlug($this->entityService->getCurrentVersionSlug());
			if(count($cv) > 0) $this->currentVersion = reset($cv);
		}
		// services dossiers/fichiers
		// $this->aetools = $this->container->get('labobundle.aetools');
		// service images
		$this->imagetools = $this->container->get('labobundle.imagetools');
	}

	/////////////////////////////////////////////////////////
	// FONCTIONS POUR CHANGEMENT TEMPORAIRE DE REPOSITORY
	/////////////////////////////////////////////////////////

	/**
	 * postLoadActions
	 * Actions sur postLoad
	 */
	public function postLoadActions() {
	}

	/**
	 * prePersistActions
	 * Actions sur prePersist
	 */
	public function prePersistActions() {
		$this->creation = true;
		$this->addCurrentVersion();
		$this->PreUpload();
	}

	/**
	 * postPersistActions
	 * Actions sur postPersist
	 */
	public function postPersistActions() {
		$this->creation = true;
		$this->upload();
	}

	/**
	 * preUpdateActions
	 * Actions sur preUpdate
	 * @param PreUpdateEventArgs $eventArgs
	 */
	public function preUpdateActions() {
		$this->creation = false;
		$this->PreUpdateUpload();
		//////////////////////////// IMPORTANT ///////////////////////////////
		// Recompute suite modifs entity (nécessaire dans le cas d'Update) !!!
		$this->recomputeEntity();
	}

	/**
	 * postUpdateActions
	 * Actions sur postUpdate
	 * @param LifecycleEventArgs $eventArgs
	 */
	public function postUpdateActions() {
		$this->creation = false;
		$this->upload();
	}

	/**
	 * preRemoveActions
	 * Actions sur preRemove
	 * @param LifecycleEventArgs $eventArgs
	 */
	public function preRemoveActions() {
		$this->preRemoveUpload();
	}

	/**
	 * postRemoveActions
	 * Actions sur postRemove
	 * @param LifecycleEventArgs $eventArgs
	 */
	public function postRemoveActions() {
		$this->removeUpload();
	}


	/**
	 * recomputeEntity
	 * UPDATE : recompute l'entité pour enregistrement
	 *
	 */
	protected function recomputeEntity() {
		// $this->SPYwrite('- recomputeEntity() sur '.$this->entityName);
		$this->uow->recomputeSingleEntityChangeSet(
			$this->em->getClassMetadata($this->entityNameSpace),
			$this->entity
		);
	}


	/////////////////////////////////////////////////////////
	// Méthodes liées à update
	/////////////////////////////////////////////////////////


	/////////////////////////////////////////////////////////
	// Méthodes liées aux uploads (images / PDF / etc.)
	/////////////////////////////////////////////////////////

	/**
	 * PreUpload
	 *
	 * sur PrePersist()
	 */
	public function PreUpload() {
		// if(method_exists($this->entity, '__call') || method_exists($this->entity, 'isImage')) {
		if(method_exists($this->entity, '__call')) {
			if($this->entity->isImage()) {
				if(null === $this->entity->getFile() && $this->aetools->isControllerPresent()) {
					// image optionnelle
					return;
				} else {
					$this->creation = true;
					if($this->entity->getFichierOrigine() === null) {
						// formualaire ou file
						$this->entity->setFichierOrigine($this->entity->getFile()->getClientOriginalName());
						$this->entity->setExt($this->entity->getFile()->guessExtension());
						$GFile = $this->entity->getFile();
					} else {
						// fixtures
						$dossiersXML = $this->aetools->exploreDir('src/', '^(images_fixtures)$', 'dossiers', true, false);
						// $path = "src/AcmeGroup/SiteBundle/Resources/public/images_fixtures/";
						$GFile = array();
						foreach ($dossiersXML as $path) {
							if(file_exists($path['full'].'/'.$this->entity->getFichierOrigine())) $GFile[] = $path['full'].'/'.$this->entity->getFichierOrigine();
						}
						$GFile = reset($GFile);
						$this->aetools->writeConsole($GFile);
					}
					// nom
					if($this->entity->getNom() === null) $this->entity->setNom($this->entity->getFichierOrigine());
					$this->entity->setTailleMo(filesize($GFile));
					$size = getimagesize($GFile);
					$this->entity->setTailleX($size[0]);
					$this->entity->setTailleY($size[1]);
					// extension
					if($this->entity->getExt() === null) {
						$ext = explode("/", image_type_to_mime_type($size[2]));
						$this->entity->setExt($ext[1]);
						// $ext = explode(".", $this->entity->getFichierOrigine());
						// $this->entity->setExt(end($ext));
					}
					// Création du nom d'enregistrement de l'image / enregistrement du fichier original
					$date = new DateTime();
					$this->entity->setFichierNom(md5(rand(100000, 999999))."-".$date->getTimestamp().".".$this->entity->getExt());
				}
			}
		}
	}

	public function addCurrentVersion() {
		if($this->aetools->isControllerPresent()) {
			if($this->currentVersion instanceOf version) {
				if(method_exists($this->entity, "setVersion")) {
					$this->entity->setVersion($this->currentVersion);
				} else if(method_exists($this->entity, "addVersion")) {
					$this->entity->addVersion($this->currentVersion);
				}
			}
		}
	}

	/**
	 * PreUpdateUpload
	 *
	 * sur PreUpdate()
	 */
	public function PreUpdateUpload() {
		if(method_exists($this->entity, '__call')) {
			if($this->entity->isImage()) {
				// $this->imagetools->checkDeclinaisonsImage($this->entity);
			}
		}
	}

	/**
	 * upload
	 *
	 * sur PostPersist()
	 * sur PostUpdate()
	 */
	public function upload() {
		if(method_exists($this->entity, '__call')) {
			if($this->entity->isImage()) {
				if($this->creation === true) {
					// persist
					$r = $this->imagetools->loadImageFile($this->entity);
					$this->imagetools->deleteCurtImages();
					return $r;
				} else {
					// upadate
				}
			}
		}
	}

	/**
	 * preRemoveUpload
	 *
	 * sur PreRemove()
	 */
	public function preRemoveUpload() {
		// mémorise l'image à supprimer
		if(method_exists($this->entity, '__call')) {
			if($this->entity->isImage()) {
				$this->entity->setTempFileName($this->entity->getFichierNom());
			}
		}
	}

	/**
	 * removeUpload
	 *
	 * sur PostRemove()
	 */
	public function removeUpload() {
		// Supprime l'image
		if(method_exists($this->entity, '__call')) {
			if($this->entity->isImage()) {
				$this->imagetools->unlinkEverywhereImage($this->entity->getTempFileName());
			}
		}
	}


}


