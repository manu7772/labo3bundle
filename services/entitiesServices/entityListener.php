<?php
// labo/Bundle/TestmanuBundle/services/entitiesServices/entityListener.php

namespace labo\Bundle\TestmanuBundle\services\entitiesServices;

use Doctrine\Common\EventSubscriber; 
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
## entités
use AcmeGroup\LaboBundle\Entity\adresse;
use AcmeGroup\LaboBundle\Entity\article;
use AcmeGroup\LaboBundle\Entity\atelier;
use AcmeGroup\LaboBundle\Entity\bonLivraison;
use AcmeGroup\LaboBundle\Entity\categorie;
use AcmeGroup\LaboBundle\Entity\commande;
use AcmeGroup\LaboBundle\Entity\demandeFinancement;
use AcmeGroup\LaboBundle\Entity\dureeMensualite;
use AcmeGroup\LaboBundle\Entity\evenement;
use AcmeGroup\LaboBundle\Entity\facture;
use AcmeGroup\LaboBundle\Entity\ficheCreative;
use AcmeGroup\LaboBundle\Entity\fichierPdf;
use AcmeGroup\LaboBundle\Entity\fournisseur;
use AcmeGroup\LaboBundle\Entity\image;
use AcmeGroup\LaboBundle\Entity\magasin;
use AcmeGroup\LaboBundle\Entity\marque;
use AcmeGroup\LaboBundle\Entity\panier;
use AcmeGroup\LaboBundle\Entity\pays;
use AcmeGroup\LaboBundle\Entity\reseau;
use AcmeGroup\LaboBundle\Entity\statut;
use AcmeGroup\LaboBundle\Entity\tauxTVA;
use AcmeGroup\LaboBundle\Entity\typeImage;
use AcmeGroup\LaboBundle\Entity\typeRemise;
use AcmeGroup\LaboBundle\Entity\typeRichtext;
use AcmeGroup\LaboBundle\Entity\userStatus;
use AcmeGroup\LaboBundle\Entity\version;
use AcmeGroup\LaboBundle\Entity\video;
use AcmeGroup\LaboBundle\Entity\villesFrance;
use AcmeGroup\LaboBundle\Entity\visite;
use AcmeGroup\LaboBundle\Entity\voteArticle;
use AcmeGroup\LaboBundle\Entity\voteArticleBlack;
use AcmeGroup\UserBundle\Entity\User; // !!! UserBundle !!!

class entityListener implements EventSubscriber {

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
		// $this->entityService = $this->container->get("acmeGroup.entities")->defineEntity($this->BundleEntityName);
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
		// $this->aetools = $this->container->get('acmeGroup.aetools');
		// service images
		$this->imagetools = $this->container->get('acmeGroup.imagetools');
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
		// $this->parametres();
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
		if($this->entity->getParentName() === 'base_entity_image') {
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

	public function addCurrentVersion() {
		if(method_exists($this->entity, "setVersion")) {
			$this->entity->setVersion($this->currentVersion);
		}
	}

	/**
	 * PreUpdateUpload
	 *
	 * sur PreUpdate()
	 */
	public function PreUpdateUpload() {
		// $this->SPYwrite('- PreUpdateUpload() sur '.$this->entityName);
		// if(method_exists($this->entity, "getImage")) {
		// 	if($this->entity->getImage()->getRemove() === true) {
		// 		$this->SPYwrite("- Suppression de l'image ".$this->entity->getImage()->getNom()." !!!");
		// 	}
		// }
		if($this->entity->getParentName() === 'base_entity_image') {
			// $this->imagetools->checkDeclinaisonsImage($this->entity);
		}
	}

	/**
	 * upload
	 *
	 * sur PostPersist()
	 * sur PostUpdate()
	 */
	public function upload() {
		// $this->SPYwrite('- upload() sur '.$this->entityName);
		// if(null === $this->entity->getFile()) {
		// 	return false;
		// }
		if($this->entity->getParentName() === 'base_entity_image') {
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

	/**
	 * preRemoveUpload
	 *
	 * sur PreRemove()
	 */
	public function preRemoveUpload() {
		// $this->SPYwrite('- preRemoveUpload() sur '.$this->entityName);
		// mémorise l'image à supprimer
		if($this->entity->getParentName() === 'base_entity_image') {
			$this->entity->setTempFileName($this->entity->getFichierNom());
		}
	}

	/**
	 * removeUpload
	 *
	 * sur PostRemove()
	 */
	public function removeUpload() {
		// $this->SPYwrite('- removeUpload() sur '.$this->entityName);
		// Supprime l'image
		if($this->entity->getParentName() === 'base_entity_image') {
			$this->imagetools->unlinkEverywhereImage($this->entity->getTempFileName());
		}
	}

	/**
	 * parametres
	 * Gestion des paramètre passés dans la propriété $parametres
	 * 
	 */
	public function parametres() {
		if(method_exists($this->entity, "getParametres")) {
			$parametres = $this->entity->getParametres();
			foreach($parametres as $param => $list) {
				$sep = explode('-', $param, 3);
				// if($sep[1] == "replace") $this->emptyField($sep[0]);
				$this->fillField($param, $list);
			}
		}
	}

	/**
	 * fillField
	 * remplit le champ $field de $this->entity avec les données $data
	 * @param $field
	 */
	public function fillField($field, $list) {
		$sep = explode('-', $field, 3);
		$info = $this->getMetaInfoField($this->entity, $sep[0]);
		if($info['Association'] === "aucune") {
			// valeur simple
			switch($info['type']) {
				case "array":
					$add = "add".ucfirst($sep[0]);
					if(is_string($list)) $this->entity->$add($list);
					if(is_array($list)) foreach($list as $val) $this->entity->$add($val);
					break;
				case "integer":
					$set = "set".ucfirst($sep[0]);
					if(is_string($list)) $this->entity->$set(intval($list));
					if(is_array($list)) $this->entity->$set(intval($list[0]));
					break;
				case "boolean":
					$set = "set".ucfirst($sep[0]);
					if(is_array($list)) $bool = $list[0]; else $bool = $list;
					if($bool === "0" || strtolower($bool) === "false") $this->entity->$set(false);
						else $this->entity->$set(true);
					break;
				case "datetime":
					$set = "set".ucfirst($sep[0]);
					if(is_array($list)) $date = $list[0]; else $date = $list;
					$newDate = new \Datetime($date);
					$this->entity->$set($newDate);
					break;
				default:
					$set = "set".ucfirst($sep[0]);
					if(is_string($list)) $this->entity->$set($list);
					if(is_array($list)) $this->entity->$set($list[0]);
					break;
			}
		} else if($info['Association'] === "single") {
			// association : single
			$set = "set".ucfirst($sep[0]);
			if(is_array($list)) $elem = $list[0];
			if(is_string($list)) $elem = $list;
			echo('Repo name : '.$this->repoNameWithClassName($info['targetEntity']));
			$repo = $this->em->getRepository($this->repoNameWithClassName($info['targetEntity']));
			$findmthd = "findBy".ucfirst($sep[2]);
			$reps = $repo->$findmthd($elem);
			if(count($reps) > 0) $this->entity->$set($reps[0]);
		} else if($info['Association'] === "collection") {
			// association : collection
			$Esave = $this->entity;
			$add = "add".ucfirst($sep[0]);
			if(is_array($list)) $elem = $list;
			if(is_string($list)) $elem = array($list);
			foreach($elem as $lm) {
				// echo('Repo name : '.$this->repoNameWithClassName($info['targetEntity']));
				$repo = $this->em->getRepository($this->repoNameWithClassName($info['targetEntity']));
				$findmthd = "findBy".ucfirst($sep[2]);
				$reps = $repo->$findmthd($lm);
				if(count($reps) > 0) foreach($reps as $rep) {
					$this->entity = $Esave;
					$this->entity->$add($rep);
					$Esave = $this->entity;
				}
			}
		}
	}


	/**
	 * emptyField
	 * Vide les données du champ $field de l'entité $this->entity
	 * @param $field
	 */
	public function emptyField($field) {
		$info = $this->getMetaInfoField($this->entity, $field);
		if($info['Association'] === "aucune") {
			// valeur simple
			switch($info['type']) {
				case "array":
					$gets = "get".ucfirst($field);
					$this->entity->$gets()->clear();
					break;
				case "integer":
					$set = "set".ucfirst($field);
					if($info['nullable'] === true) $this->entity->$set(null); else $this->entity->$set(0);
					break;
				case "boolean":
					$set = "set".ucfirst($field);
					if($info['nullable'] === true) $this->entity->$set(false); else $this->entity->$set(true);
					break;
				case "datetime":
					$set = "set".ucfirst($field);
					if($info['nullable'] === true) $this->entity->$set(null); else $this->entity->$set("0000-00-00");
					break;
				default:
					$set = "set".ucfirst($field);
					if($info['nullable'] === true) $this->entity->$set(null); else $this->entity->$set("");
					break;
			}
		} else if($info['Association'] === "single") {
			// association : single
			$set = "set".ucfirst($field);
			if($info['nullable'] === true) $this->entity->$set(null);
		} else if($info['Association'] === "collection") {
			// association : collection
			$gets = "get".ucfirst($field);
			$this->entity->$gets()->clear();
		}
	}

	public function getMetaInfoField($newObject, $field) {
		$field = $field.'s';
		$r = array();
		$CMD = $this->em->getClassMetadata(get_class($newObject));
		// $field = $CMD->getFieldForColumn($column);
		if($CMD->hasAssociation($field) === false) {
			// Sans association
			$r = $CMD->getFieldMapping($field);
			$r['Association'] = "aucune";
		} else {
			// Avec association
			$r = $CMD->getAssociationMapping($field);
			if($CMD->isSingleValuedAssociation($field)) {
				$r['Association'] = "single";
				$r['unique'] = $r["joinColumns"][0]["unique"];
				$r['nullable'] = $r["joinColumns"][0]["nullable"];
			} else if($CMD->isCollectionValuedAssociation($field)) {
				$r['Association'] = "collection";
				// $r['nullable'] = $CMD->isNullable($field);
				// $r['unique'] = $CMD->isUniqueField($field);
			} else {
				// Association inconnue !!!
				$r['Association'] = "[inconnue]";
			}
		}
		return $r;
	}

	/**
	* repoNameWithClassName
	* renvoie le nom du repository d'après le nom de la classe
	*/
	public function repoNameWithClassName($classEntite) {
		$splits = explode("\\", $classEntite);
		return $splits[0].$splits[1].":".$splits[3];
	}



}


?>