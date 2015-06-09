<?php
// laboBundle/services/entitiesServices/entityListener.php

namespace laboBundle\services\entitiesServices;

use Doctrine\Common\EventSubscriber; 
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;

class entityListener implements EventSubscriber {

	const ENTITY_IMAGE_BASENAME = 'base_entity_image';

	private $eventArgs;
	private $em;
	private $repoVersion;
	private $currentVersion;
	private $ession;
	private $repo;
	private $entity;
	private $container;
	private $entityName;
	private $entityNameSpace;
	private $uow;
	// private $aetools;
	private $imagetools;
	private $creation = false;
	private $EmSaved = true;
	private $savedEm;

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
		$this->em = $eventArgs->getEntityManager();
		$this->eventArgs = $eventArgs;
		// info MetaData sur l'entité
		// $this->info = $this->getMetaInfo($this->entity);
		// namespace de l'entité
		$this->entityNameSpace = $this->em->getClassMetadata(get_class($this->entity))->getName();
		$ex = explode("\\", $this->entityNameSpace);
		// nom de l'entité
		$this->entityName = $ex[count($ex) - 1];
		$this->BundleEntityName = str_replace("Bundle", "", $ex[count($ex) - 3]).":".$this->entityName;
		// Service entité
		// $this->entityService = $this->container->get("labobundle.entities")->defineEntity($this->BundleEntityName);
		// Repository
		$this->repo = $this->em->getRepository($this->entityNameSpace);
		$this->uow = $this->em->getUnitOfWork();
		// détection fixtures :
		if($this->container->get("request")->attributes->get('_controller') === null) {
			// en mode fixtures
			$this->modeFixtures = true;
			$this->currentVersion = false;
		} else {
			// pas en mode fixtures
			$this->modeFixtures = false;
			$this->session = $this->container->get('session')->get('version');
			$ver = $this->session["slug"];
			$this->repoVersion = $this->em->getRepository('AcmeGroup\\LaboBundle\\Entity\\version');
			// $this->repoVersion = $this->em->getRepository("AcmeGroup\\LaboBundle\\Entity\\version");
			$cv = $this->repoVersion->findBySlug($ver);
			if(count($cv) > 0) $this->currentVersion = $cv[0];
				else $this->currentVersion = false;
		}
		// services dossiers/fichiers
		// $this->aetools = $this->container->get('labobundle.aetools');
		// service images
		$this->imagetools = $this->container->get('labobundle.imagetools');
		// $this->SPYwrite("\r\n\r\n--------------- Events sur ".$this->entityName);
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
		// $this->SPYwrite('- preUpload() sur '.$this->entityName);
		if(method_exists($this->entity, 'getParentshortName')) {
			if($this->entity->isImage()) {
				if(null === $this->entity->getFile() && $this->modeFixtures === false) {
					// $this->SPYwrite('- Pas d\'image pour '.$this->entityName);
					return;
				} else {
					// $this->SPYwrite('- Remplissage des données image');
					$this->creation = true;
					if($this->entity->getFichierOrigine() === null) {
						// formualaire ou file
						$fichOrig = $this->entity->getFile()->getClientOriginalName();
						$this->entity->setFichierOrigine($fichOrig);
						$this->entity->setExt($this->entity->getFile()->guessExtension());
						$GFile = $this->entity->getFile();
					} else {
						// fixtures
						$path = "src/AcmeGroup/SiteBundle/Resources/public/images_fixtures/";
						$GFile = $path.$this->entity->getFichierOrigine();
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
						// $this->entity->setExt($ext[count($ext) - 1]);
					}
					// Création du nom d'enregistrement de l'image / enregistrement du fichier original
					$date = new \Datetime();
					$this->entity->setFichierNom(md5(rand(100000, 999999))."-".$date->getTimestamp().".".$this->entity->getExt());
				}
			}
		}
	}

	public function addCurrentVersion() {
		if(method_exists($this->entity, "setVersion") && $this->currentVersion instanceOf AcmeGroup\LaboBundle\Entity\version) {
			$this->entity->setVersion($this->currentVersion);
		}
	}

	/**
	 * PreUpdateUpload
	 *
	 * sur PreUpdate()
	 */
	public function PreUpdateUpload() {
		if(method_exists($this->entity, 'getParentshortName')) {
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
		if(method_exists($this->entity, 'getParentshortName')) {
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
		if(method_exists($this->entity, 'getParentshortName')) {
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
		if(method_exists($this->entity, 'getParentshortName')) {
			if($this->entity->isImage()) {
				$this->imagetools->unlinkEverywhereImage($this->entity->getTempFileName());
			}
		}
	}


}


