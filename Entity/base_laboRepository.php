<?php

namespace labo\Bundle\TestmanuBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

use \Exception;

use labo\Bundle\TestmanuBundle\Entity\version;
use labo\Bundle\TestmanuBundle\Entity\statut;
// aeReponse
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

/**
 * base_laboRepository
 */
class base_laboRepository extends EntityRepository {

	const CHAMP_DATE 	= "dateCreation";
	const REPO_NAME		= "base_laboRepository";

	protected $em;
	protected $version = null;
	protected $fields = array();
	private $initCMD = false;
	private $ClassMetadata;

	public function __construct(EntityManager $em, ClassMetadata $ClassMetadata) {
		$this->ClassMetadata = $ClassMetadata;
		$this->em = $em;
		parent::__construct($this->em, $this->ClassMetadata);
		$this->defineClassMetaData();
		// $this->setVersion();
	}

	/**
	 * Renvoie le nom de l'entité
	 * @return string
	 */
	public function getName() {
		return self::REPO_NAME;
	}

	/**
	 * defineClassMetaData
	 */
	private function defineClassMetaData() {
		if($this->initCMD === false) {
			$this->initCMD = true;
			// ajout champs single
			$fields = $this->ClassMetadata->getColumnNames();
			foreach($fields as $f) {
				$this->fields[$f]['nom'] = $f;
				$this->fields[$f]['type'] = 'single';
			}
			// ajout champs associated
			$assoc = $this->ClassMetadata->getAssociationMappings();
			foreach($assoc as $nom => $field) {
				$this->fields[$nom]['nom'] = $nom;
				$this->fields[$nom]['type'] = 'association';
			}
			// affichage
			// echo("<pre>");print_r($this->fields);echo("</pre>");
		}
	}

	/**
	 * Définit la version du site à utiliser pour le repository. 
	 * si $version n'est pas précisé, recherche la version par défaut dans l'entité AcmeGroup\LaboBundle\Entity\version
	 * @param version $version
	 * @return base_laboRepository
	 */
	public function setVersion(version $version = null) {
		// version par défaut
		if($version === null) {
			$version = $this->_em->getRepository("laboBundleTestmanuBundle:version")->defaultVersion();
			if(is_array($version)) {
				if(count($version) > 0) {
					reset($version);
					$this->version = current($version);
				}
			}
		}
		if($version instanceOf(version)) {
			$this->version = $version;
		} else {
			throw new Exception("Repository : impossible de définir une version par défaut");
		}
		return $this;
	}

	/**
	 * Renvoie la version utilisé par le repository
	 * @return version
	 */
	public function getVersions() {
		return $this->version;
	}

	/**
	 * Liste les champs de l'entité
	 * @return array
	 */
	public function getFields() {
		return $this->fields;
	}

	/**
	 * Vérifie si un champ existe
	 * @param string $field
	 * @return boolean
	 */
	public function field_exist($field) {
		return array_key_exists($field, $this->getFields());
	}

	/**
	 * Renvoie les entités dont le $champ contient les valeurs $values
	 * @param string $champ
	 * @param array $values
	 * @return array
	 */
	public function findByAttrib($champ, $values) {
		if($this->field_exist($champ)) {
			if(is_string($values)) $values = array($values);
			$qb = $this->createQueryBuilder('element');
			$qb->where($qb->expr()->in('element.'.$champ, $values));
			return $qb->getQuery()->getResult();
		} else {
			throw new Exception("Repository : ce champ n'existe pas.");
		}
	}

	/**
	 * findXrandomElements
	 * récupère $n éléments au hasard dans la BDD
	 * @param integer $n
	 * @return array
	 */
	public function findXrandomElements($X) {
		$X = intval($X);
		if($X < 1) $X = 1;
		$qb = $this->createQueryBuilder('element');
		$qb = $this->defaultStatut($qb);
		// $qb = $this->excludeExpired($qb);
		$qb = $this->withVersion($qb);
		// $qb->setMaxResults($X);
		$r = $qb->getQuery()->getResult();
		if($X > count($r)) $X = count($r);
		shuffle($r);
		$rs = array();
		for ($i=0; $i < $X ; $i++) { 
			$rs[] = $r[$i];
		}
		return $rs;
	}

	/**
	 * findElementsPagination
	 * Recherche les elements en fonction de la version
	 * et pagination avec GET
	 */
	// public function findElementsPagination($page = 1, $lignes = null, $ordre = 'id', $sens = 'ASC', $searchString = null, $searchField = "nom") {
	public function findElementsPagination($pag, $souscat = null) {
		// vérifications pagination
		if($pag['page'] < 1) $pag['page'] = 1;
		if($pag['lignes'] > 100) $pag['linges'] = 100;
		if($pag['lignes'] < 10) $pag['lignes'] = 10;
		// Requête…
		$qb = $this->createQueryBuilder('element');
		$qb = $this->rechercheStr($qb, $pag['searchString'], $pag['searchField']);
		// sous-catégories de tri
		if($souscat !== null) {
			$qb->join('element.'.$souscat['attrib'], 'link')
				->andWhere($qb->expr()->in('link.'.$souscat['column'], explode(":", $souscat['values'])));
		}
		// $qb->leftJoin('element.imagePpale', 'i')
		// 	->addSelect('i')
		// 	->leftJoin('element.images', 'ii')
		// 	->addSelect('ii')
		// 	->leftJoin('element.reseaus', 'r')
		// 	->addSelect('r');
		// exclusions
		// $qb = $this->excludeExpired($qb);
		$qb = $this->withVersion($qb);
		// $qb = $this->defaultStatut($qb);
		// Tri/ordre
		if(!in_array($pag['ordre'], $this->getFields())) $pag['ordre'] = "id";
		if(!in_array($pag['sens'], array('ASC', 'DESC'))) $pag['sens'] = "ASC";
		$qb->orderBy('element.'.$pag['ordre'], $pag['sens']);
		// Pagination
		$qb->setFirstResult(($pag['page'] - 1) * $pag['lignes'])
			->setMaxResults($pag['lignes']);
		return new Paginator($qb);
	}


	/**
	 * Sélectionne les éléments comportant le(s) $tag(s) en paramètre
	 * @param mixed $tags
	 */
	public function findListByTags($tags, pagine $pagine = null) {
		if(is_string($tags)) $tags = array($tags);
		if(is_array($tags)) {
			$qb = $this->createQueryBuilder('element');
			$qb->join('element.tags', 'tag')
				->where($qb->expr()->in('tag.slug', $tags))
				// ->orderBy("element.id", "ASC")
			;
		} else {
			throw new Exception("Repository : tags incorrects. Type <i>".gettype($tags)."</i> inattendu.");
		}
		return $qb->getQuery()->getResult();
	}


	/***************************************************************/
	/*** Méthodes conditionnelles / manipulation du QueryBuilder
	/***************************************************************/

	/**
	 * Sélect element de statut/expirés/version
	 * @param Doctrine\ORM\QueryBuilder $qb
	 * @return QueryBuilder
	 */
	protected function genericFilter(QueryBuilder $qb, $statut = null, $published = true, $expired = true, $version = null) {
		$qb = $this->defaultStatut($qb, $statut);
		$qb = $this->withVersion($qb, $version);
		if($expired === true) $qb = $this->excludeExpired($qb);
		if($published === true) $qb = $this->excludeNotPublished($qb);
		return $qb;
	}

	/**
	 * defaultStatut
	 * Sélect element de statut = actif uniquement
	 * @param Doctrine\ORM\QueryBuilder $qb
	 * @param array/string $statut
	 * @return QueryBuilder
	 */
	protected function defaultStatut(QueryBuilder $qb, $statut = null) {
		if($this->field_exist("statut")) {
			if($statut === null) $statut = array("Actif");
			if(is_string($statut)) $statut = array($statut);
			$qb->join('element.statut', 'stat')
				->andWhere($qb->expr()->in('stat.nom', $statut));
		}
		return $qb;
	}

	/**
	 * Sélect element de $version uniquement
	 * @param Doctrine\ORM\QueryBuilder $qb
	 * @param mixed $version
	 * @return QueryBuilder
	 */
	protected function withVersion(QueryBuilder $qb, $version = null) {
		if($this->field_exist("versions")) {
			if($this->version !== false || $version !== null) {
				if($version !== null) $this->setVersion($version);
				$version = $this->version;
				$qb->join('element.versions', 'ver')
					->andWhere($qb->expr()->in('ver.slug', $version));
			}
		}
		return $qb;
	}

	/**
	 * excludeExpired
	 * Sélect elements non expirés
	 * @param Doctrine\ORM\QueryBuilder $qb
	 * @return QueryBuilder
	 */
	protected function excludeExpired(QueryBuilder $qb) {
		if($this->field_exist("dateExpiration")) {
			$qb->andWhere('element.dateExpiration > :date OR element.dateExpiration is null')
				->setParameter('date', new \Datetime());
		}
		return $qb;
	}

	/**
	 * excludeNotPublished
	 * Sélect elements publiés
	 * @param Doctrine\ORM\QueryBuilder $qb
	 * @return QueryBuilder
	 */
	protected function excludeNotPublished(QueryBuilder $qb) {
		if($this->field_exist("datePublication")) {
			$qb->andWhere('element.datePublication < :date OR element.datePublication is null')
				->setParameter('date', new \Datetime());
		}
		return $qb;
	}

	/**
	 * Renvoie les éléments dont les dates sont situées entre $debut et $fin
	 * @param Doctrine\ORM\QueryBuilder $qb
	 * @param Datetime $debut
	 * @param Datetime $fin
	 *
	 */
	protected function betweenDates(QueryBuilder $qb, $debut, $fin, $champ = null) {
		// champ par défaut
		if($champ === null) $champ = self::CHAMP_DATE;
		// préparations dates
		$tempDates['debut'] = $debut;
		$tempDates['fin'] = $fin;
		foreach($tempDates as $nom => $date) {
			if(is_string($date)) $dates[$nom] = new \Datetime($date);
			if(is_object($date)) $dates[$nom] = $date;
		}
		if(array_key_exists($champ, $this->getFields()) && is_object($dates['debut']) && is_object($dates['fin'])) {
			$qb->andWhere('element.'.$champ.' BETWEEN :debut AND :fin')
				->setParameter('debut', $dates['debut'])
				->setParameter('fin', $dates['fin'])
				;
		}
		return $qb;
	}

	/**
	 * rechercheStr
	 * trouve les éléments qui contiennent la chaîne $searchString
	 *
	 */
	protected function rechercheStr(QueryBuilder $qb, $searchString, $searchField = null, $mode = null) {
		if($searchField === null) {
			$priori = array("nom", "nommagasin", "nomunique", "fichierNom");
			$firstField = $this->getFields();
			foreach($priori as $field) if(array_key_exists($field, $firstField)) $searchField = $field;
			if ($searchField === null) $searchField = $firstField[1]['nom'];
		}
		switch ($mode) {
			case 'begin':
				$bef = "";
				$aft = "%";
				break;
			case 'end':
				$bef = "%";
				$aft = "";
				break;
			case 'exact':
				$bef = $aft = "";
				break;
			default:
				$bef = $aft = "%";
				break;
		}
		if(is_string($searchString) && $searchString !== "") {
			$qb->where($qb->expr()->like('element.'.$searchField, $qb->expr()->literal($bef.$searchString.$aft)));
		}
		return $qb;
	}


}
