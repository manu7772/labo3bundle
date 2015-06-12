<?php
// laboBundle/services/entitiesServices/entitesService.php

nameSpace laboBundle\services\entitiesServices;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
// use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
// aetools
use laboBundle\services\aetools\aetools;

// informations classes
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\ClassMetadata;

class entitesService extends aetools {

	const NOM_OBJET_TYPE 		= "objet_type";		// nom de l'objet type basique
	const NOM_OBJET_READY 		= "objet_ready";	// nom de l'objet rempli avec les valeurs par défaut
	const REPO_DEFAULT_VAL		= "defaultVal";		// méthode repository pour récupération des entités par défaut
	const ONLY_CONCRETE			= true;				// ne récupère que les entités concrètes (non abstract ou interface)

	// ENTITÉS / ENTITÉ COURANTE
	protected $entity = array();			// tableau des entités
	protected $current = null;				// className (nom long) de l'entité courante
	protected $onlyConcrete;

	protected $_em;							// entity_manager
	protected $repo;						// repository

	protected $version;

	protected $listOfEnties = null;			// liste des entités de src
	protected $completeListOfEnties = null;	// liste des entités complète


	public function __construct(ContainerInterface $container = null) {
		parent::__construct($container);
		// autres données sans controller
		$this->getEm();
		// Détection automatique du mode FIXTURES
		if($this->isControllerPresent() === true) {
			// autre données dépendant du controller
		}
		$this->setOnlyConcrete();
		// return $this;
	}

	/**
	 * Définit le mode de récupération de la liste des entités
	 * @param boolean $val - true : ne récupère que les entités concrètes / false : récupère tout
	 * @return boolean
	 */
	public function setOnlyConcrete($val = null) {
		$val !== false ? $this->onlyConcrete = self::ONLY_CONCRETE : $this->onlyConcrete = !self::ONLY_CONCRETE;
		return $this->onlyConcrete;
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////
	// VERSIONS
	////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Renvoie le nom de la classe servant de version
	 * @return string - false si aucune entité version
	 */
	public function getVersionEntityClassName() {
		$this->setOnlyConcrete(true);
		foreach ($this->getListOfEnties(false) as $entity => $shortname) {
			$entity = new $entity;
			if(method_exists($entity, "__call")) {
				if($entity->isVersion()) return get_class($entity);
			}
		}
		throw new Exception("Service version : aucune entité de version n'existe.");
		// return false;
	}



	/**
	 * Renvoie le nom de la classe
	 * @return string
	 */
	public function getName() {
		return get_called_class();
	}

	/**
	 * Renvoie le nom de la classe
	 * @return string
	 */
	public function getShortName() {
		return $this->getClassShortName($this->getName());
	}

	/**
	 * Initialise le service - attention : cette méthode est appelée en requête principale par EventListener !!!
	 * @param FilterControllerEvent $event
	 * @param boolean $reLoad
	 */
	public function serviceEventInit(FilterControllerEvent $event, $reLoad = false) {
		// $this->service = array();
		// paramètres URL et route
	}

	/**
	 * Vérifie si une entité existe : si oui, renvoie le className
	 * @param string $name - nom long ou court
	 * @param boolean $extended - recherche étendue à toutes ou uniquement /src
	 * @param boolean $getShortName - true = renvoie le nom court plutôt que le className
	 * @return string / false si l'entité n'existe pas
	 */
	public function entityClassExists($name, $extended = false, $getShortName = false) {
		if(in_array($name, $this->getListOfEnties($extended))) {
			$find = array_keys($this->completeListOfEnties, $name);
			return $getShortName === true ? $name : reset($find);
		}
		// le nom est déjà un nom long : on le renvoie tel quel
		if(array_key_exists($name, $this->getListOfEnties($extended))) {
			return $getShortName === true ? $this->completeListOfEnties[$name] : $name;
		}
		// sinon, renvoie false : l'entité n'existe pas
		return false;
	}

	/**
	 * Renvoie le className de l'entité courante (ou de l'entité passée en paramètre) si elle existe
	 * @param mixed $entity
	 * @return string / false si l'entité n'existe pas
	 */
	public function getEntityClassName($entity = null) {
		if($entity === null) $entity = $this->current;
		if(is_object($entity)) $entity = get_class($entity);
		return $this->entityClassExists($entity, false, false);
	}

	/**
	 * Renvoie le nom court de l'entité courante (ou de l'entité passée en paramètre) si elle existe
	 * @param mixed $entity
	 * @return string / false si l'entité n'existe pas
	 */
	public function getEntityShortName($entity = null) {
		if($entity === null) $entity = $this->current;
		if(is_object($entity)) $entity = get_class($entity);
		return $this->entityClassExists($entity, false, true);
	}

	/**
	 * Renvoie un array des entités contenues dans src (ou toutes les entités, si $extended = true)
	 * Sous la forme liste[shortName] = nameSpace
	 * @param boolean $extended
	 * @param boolean $force
	 * @return array
	 */
	public function getListOfEnties($extended = false, $force = false) {
		if($this->listOfEnties === null || $this->completeListOfEnties === null || $force === true) {
			$this->listOfEnties = array();
			$this->completeListOfEnties = array();
			$entitiesNameSpaces = $this->_em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
			// recherche de tous les dossiers de src/ (donc tous les groupes de bundles)
			$groupesSRC = $this->exploreDir("src/", null, "dossiers", false);
			$groupes = array();
			foreach($groupesSRC as $nom) $groupes[] = $nom['nom'];
			// var_dump($groupes);die();
			foreach($entitiesNameSpaces as $ENS) {
				$do_it = true;
				if($this->onlyConcrete === true) {
					// supprime les classes abstraites et les interfaces
					$CMD = $this->getEm()->getClassMetaData($ENS);
					if(is_object($CMD)) {
						$reflectionClass = $CMD->getReflectionClass();
						if($reflectionClass->isAbstract() || $reflectionClass->isInterface()) $do_it = false;
					} else $do_it = false;
				}
				if($do_it === true) {
					$EE = $this->getClassShortName($ENS);
					$exp = explode(self::ASLASH, $ENS);
					$group = reset($exp);
					if(in_array($group, $groupes)) $this->listOfEnties[$ENS] = $EE;
					$this->completeListOfEnties[$ENS] = $EE;
				}
			}
		}
		// var_dump($this->listOfEnties);die();
		return $extended === false ? $this->listOfEnties : $this->completeListOfEnties ;
	}

	/**
	 * initialise avec le nom de l'entité : !!! format "groupe\bundle\dossier\entite" !!!
	 * @param string $classEntite
	 */
	public function defineEntity($classEntite) {
		// récupère le nom long s'il est en version courte
		$classEntite = $this->entityClassExists($classEntite);
		$shortName = $this->getEntityShortName($classEntite);
		if($classEntite !== false) {
			// l'entité existe et n'est pas l'entité courante
			if(!$this->isDefined($classEntite) && !$this->isCurrent($classEntite)) {
				// l'entité n'est pas initialisée, on la crée
				$this->current = $classEntite;
				$this->entity[$this->current] = array();
				// $this->entity[$this->current][self::NOM_OBJET_TYPE] = $this->newObject($classEntite, false);
				// $this->entity[$this->current][self::NOM_OBJET_READY] = $this->newObject($classEntite, true);
				$this->entity[$this->current]['className'] = $classEntite;
				$this->entity[$this->current]['name'] = $shortName;
				$this->serviceNom = $shortName;
			} else {
				// sinon on déclare juste l'entité comme entité courante
				$this->current = $classEntite;
			}
		} else {
			// l'entité n'existe pas
			return false;
		}
		// Objet Repository
		$this->getRepo();
		// renvoie l'objet entitiesGeneric
		return $this;
	}

	/**
	* Renvoie si l'entité est déjà définie
	* @param string $classEntite
	* @return boolean
	*/
	public function isDefined($classEntite) {
		// récupère le nom long si c'est un court
		$classEntite = $this->entityClassExists($classEntite);
		if(array_key_exists($classEntite, $this->entity)) return true;
		return false;
	}

	/**
	* Renvoie si l'entité est l'entité courante
	* @param string $classEntite
	* @return boolean
	*/
	public function isCurrent($classEntite) {
		// récupère le nom long si c'est un court
		$classEntite = $this->entityClassExists($classEntite);
		if($classEntite === $this->current) return true;
		return false;
	}

	/**
	* Renvoie les données de l'entité courante
	* @return array
	*/
	public function getCurrent() {
		if($this->current !== null) {
			return $this->entity[$this->current];
		}
		return false;
	}

	// /**
	// * Renvoie l'entité courante type (objet)
	// * @return object
	// */
	// public function getCurrentEntity($ready = false) {
	// 	if($this->current !== null) {
	// 		return $ready === false ? $this->entity[$this->current][self::NOM_OBJET_TYPE] : $this->entity[$this->current][self::NOM_OBJET_READY];
	// 	}
	// 	return false;
	// }

	/**
	 * Renvoie un nouvel objet entité
	 * si $loadDefaults = true, charge les valeurs par défaut des entités liées
	 * @param string $classEntite
	 * @param boolean $loadDefaults
	 * @return object
	 */
	public function newObject($classEntite = null, $loadDefaults = false) {
		if($classEntite === null) {
			// $current = $this->getCurrent();
			// $classEntite = $current['className'];
			$classEntite = $this->getEntityClassName();
		}
		if($this->isDefined($classEntite)) {
			$defaultVal = self::REPO_DEFAULT_VAL;
			$nameEntite = $this->getClassShortName($classEntite);
			$this->writeConsole('Recherche d\'entités liées par défaut de '.$nameEntite.' (méthode utilisée : ->'.$defaultVal.'())');
			$newObject = new $classEntite;
			if($loadDefaults !== false) {
				$data["metaInfo"] = $this->getMetaInfo($classEntite);
				// $this->writeConsole($metaInfo['listColumns']);
				foreach($data["metaInfo"]['listColumns'] as $nom => $datt) {
					if(($datt["Association"] !== "aucune") && ($nom !== 'parent')) {
						$NOAclassName = $datt['targetEntity'];
						$newObjAssoc = new $NOAclassName();
						$repo = $this->getEm()->getRepository($NOAclassName);
						if(method_exists($repo, $defaultVal)) {
							$values = $repo->$defaultVal();
							$this->writeConsole('Entités : '.$NOAclassName.', '.count($values).' élement(s) trouvé(s)');
							switch ($datt["Association"]) {
								case 'collection':
									$methode = $this->getMethodNameWith(substr($nom, 0, -1), 'add');
									if(is_array($values)) {
										foreach($values as $val) {
											if(method_exists($newObject, $methode)) $newObject->$methode($val);
											// ajout inverse si relation bi-directionnelle
											$add = $this->getMethodNameWith($nameEntite, 'add');
											if(method_exists($val, $add)) $val->$add($newObject);
											$set = $this->getMethodNameWith($nameEntite, 'set');
											if(method_exists($val, $set)) $val->$set($newObject);
										}
									}
									else if(is_object($values) && method_exists($newObject, $methode)) $newObject->$methode($values);
									break;
								default: // single
									$methode = $this->getMethodNameWith($nom, 'set');
									if(is_array($values) && count($values) > 0) $val = reset($values);
										else $val = $values;
									if(is_object($val) && method_exists($newObject, $methode)) {
										$newObject->$methode($val);
										// ajout inverse si relation bi-directionnelle
										$add = $this->getMethodNameWith($nameEntite, 'add');
										if(method_exists($val, $add)) $val->$add($newObject);
										$set = $this->getMethodNameWith($nameEntite, 'set');
										if(method_exists($val, $set)) $val->$set($newObject);
									}
									break;
							}
						}
					}
				}
			}
		} else {
			// $this->writeConsole("L'entité ".$classEntite." n'a pas pu être crée", 'error');
			return false;
		}
		// $this->restituteOriginalEntity();
		// $this->defineEntity($SN);
		// $this->echoFixtures("Objet de classe : ".get_class($newObject)."<br />");
		return $newObject;
	}

	/**
	 * Vide les données d'un champ de l'objet $object
	 * si l'objet n'est pas précisé, un nouvel objet est créé ou utilise l'objet courant
	 * @param string $field
	 * @param object $object
	 * @param $object
	 */
	public function emptyField($field, $object) {
		if(is_object($object)) {
			// $info = $this->getMetaInfoField($object, $field);
			$CMD = $this->getClassMetadata($object);
			if(!$CMD->hasAssociation($field)) {
				// valeur simple
				// $this->writeConsole('Type de '.$field.' = '.$CMD->getTypeOfField($field));
				switch($CMD->getTypeOfField($field)) {
					case "array":
						$gets = $this->getMethodNameWith($field, "get");
						$object->$gets()->clear();
						break;
					case "integer":
						$set = $this->getMethodNameWith($field, "set");
						$CMD->isNullable($field) ? $object->$set(null) : $object->$set(0);
						break;
					case "boolean":
						$set = $this->getMethodNameWith($field, "set");
						$CMD->isNullable($field) ? $object->$set(false) : $object->$set(true);
						break;
					case "datetime":
						$set = $this->getMethodNameWith($field, "set");
						$CMD->isNullable($field) ? $object->$set(null) : $object->$set("0000-00-00");
						break;
					default:
						$set = $this->getMethodNameWith($field, "set");
						$CMD->isNullable($field) ? $object->$set(null) : $object->$set("");
						break;
				}
			}
			if($CMD->isAssociationWithSingleJoinColumn($field)) {
				// $this->writeConsole("Champ ".$field." en association avec single join column", "error");
			}
			if($CMD->isSingleValuedAssociation($field)) {
				// association : single
				// $this->writeConsole("Champ ".$field." en association single valued");
				$AM = $CMD->getAssociationMapping($field);
				$set = $this->getMethodNameWith($field, "set");
				if($AM["joinColumns"][0]["nullable"]) $object->$set(null);
			}
			if($CMD->isCollectionValuedAssociation($field)) {
				// association : collection
				// $this->writeConsole("Champ ".$field." en association avec collection valued");
				// $AM = $CMD->getAssociationMapping($field);
				$gets = $this->getMethodNameWith($field, "get");
				$object->$gets()->clear();
			}
		} else return false;
		return $object;
	}



	/**
	* Renvoie l'Entity Manager
	* @return manager
	*/
	public function getEm() {
		if(is_object($this->container)) {
			$this->_em = $this->container->get('doctrine')->getManager();
			return $this->_em;
		} else return false;
	}

	/**
	* Renvoie le Repository
	* @return repository / false
	*/
	public function getRepo() {
		if($this->current !== null) {
			$this->repo = $this->getEm()->getRepository($this->current);
			if($this->isControllerPresent()) {
				if((method_exists($this->getCurrent(), "setVersion")) || (method_exists($this->getCurrent(), "addVersion")))
					$hasVersion = true; else $hasVersion = false;
				// définit la version pour le Repository
				if(method_exists($this->repo, "setVersion") && ($this->version !== false)) {
					$this->repo->setVersion($this->version);
				} else {
					// else
				}
				if($hasVersion === false) {
					// $this->repo->dontTestVersion();
				}
			} else {
				$this->writeConsole('Repository '.$this->getEntityShortName()." : hors controller / sans test de version courante.");
			}
			return $this->repo;
		}
		return false;
	}


	/**
	 * Renvoie la ClassMetadataInfo de l'entité
	 * @param mixed $entity (nom ou objet)
	 * @return ClassMetadata
	 */
	public function getClassMetadata($entity = null) {
		$entity = $this->getEntityClassName($entity);
		// $this->writeConsole($entity);
		if($entity !== false) {
			return $this->getEm()->getClassMetadata($entity);
		}
		return false;
	}

	/**
	 * Renvoie la description de l'entité
	 * @param mixed $entityClassName (nom ou objet)
	 * @return array
	 */

	public function getMetaInfo($className) {
		$r = array();
		$r['CMData'] = $this->getClassMetadata($className);
		if($r['CMData'] !== false) {
			// informations sur la classe (entité)
			$r['classInfo']['className'] = $r['CMData']->getName();
			$r['classInfo']['tableName'] = $r['CMData']->getTableName();
			$r['classInfo']['repoName'] = $r['CMData']->customRepositoryClassName;
			$r['classInfo']['reflexProp'] = $r['CMData']->getReflectionProperties();
			// $r['classInfo']['lifecycleCallbacks'] = $r['CMData']->getLifecycleCallbacks(!!!!!!!argument!!!!!!!!);
			// $r['CMDataMethods'] = get_class_methods($r['CMData']);
			// $colNoAssoc = $r['CMData']->getColumnNames();
			$colNoAssoc = $r['CMData']->getFieldNames();
			$colWtAssoc = $r['CMData']->getAssociationNames();
			foreach(array_merge($colNoAssoc, $colWtAssoc) as $nom) {
				// if((substr($nom, -1) == "s" && substr($nom, -2, -1) != "s") || (substr($nom, -2) == "ss")) $nom = substr($nom, 0, -1);
				$r['listColumns'][$r['CMData']->getFieldName($nom)] = $this->getMetaInfoField($className, $r['CMData']->getFieldName($nom));
			}
			// Liste des libellés du tableau -> pour admin
			$rr = array();
			foreach($r['listColumns'] as $val) {
				foreach($val as $nom => $val2) {
					$rr[$nom] = $nom;
				}
			}
			$r['libelles'] = $rr;
		} else return false;
		// $r['entiteName'] = $className;
		return $r;
	}

	/**
	 * Renvoie la description d'un champ de l'entité
	 * @param mixed $entityClassName (nom ou objet)
	 * @param string $field
	 * @return array
	 */
	public function getMetaInfoField($className, $field) {
		$CMD = $this->getClassMetadata($className);
		if($CMD !== false) {
			$r = array();
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
		} else return false;
		return $r;
	}



}