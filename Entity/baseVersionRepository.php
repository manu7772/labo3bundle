<?php

namespace laboBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use \Exception;
use \DateTime;

/**
 * versionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class baseVersionRepository extends EntityRepository {

	const ELEMENT = 'element';

	/**
	* defaultVal
	* Renvoie l'instance de la version par défaut (ou null)
	*/
	public function defaultVal() {
		return $this->defaultVersion();
	}

	/**
	* defaultVersion
	* Renvoie l'instance de la version par défaut (ou null)
	*/
	public function defaultVersion() {
		$qb = $this->getQbWithDefaultVersion();
		try {
			$result = $qb->getQuery()->getSingleResult();
		} catch (Exception $e) {
			// printf("Aucun résultat pour la version par défaut…\n");
			$result = null;
		}
		return $result;
	}

	protected function getElementsForSession() {
		return array();
	}

	/**
	* Renvoie les données de version en array
	* @param string $valeur - valeur recherchée
	* @param string $champ - champ dans lequel la valeur est recherchée
	* @param array $adds - éléments associés à ajouter (leftJoin) sous forme d'array (multiples niveaux possibles)
	* @return array
	*/
	public function getVersionArray($valeur = null, $champ = 'slug', $adds = null) {
		if(is_string($valeur) && strlen($valeur) > 0) {
			$qb = $this->createQueryBuilder(self::ELEMENT);
			$qb->where(self::ELEMENT.'.'.$champ.' = :val')
				->setParameter('val', $valeur);
			$errorMessage = "La version ".$valeur." (".$champ.") n'a pu être trouvée.";
		} else {
			$qb = $this->getQbWithDefaultVersion($qb);
			$errorMessage = "Il n'existe pas de version par défaut.";
		}
		if($adds === null) $adds = $this->getElementsForSession();
		$qb = $this->addJoins($qb, $adds);
		$result = $qb->getQuery()->getArrayResult();
		if(is_array($result) && count($result) > 0) return reset($result);
		throw new Exception($errorMessage, 1);
	}

	protected function addJoins(QueryBuilder $qb, $adds, $joined = null) {
		if($joined === null || !is_string($joined)) $joined = self::ELEMENT;
		if(!($qb instanceOf QueryBuilder)) $qb = $this->createQueryBuilder($joined);
		if(is_array($adds)) foreach ($adds as $field => $childs) {
			$itemField = $joined.'.'.$field;
			if(!is_array($childs)) $childs = array();
			$qb->leftJoin($itemField, $field)
				->addSelect($field)
				;
			if(count($childs) > 0) $qb = $this->addJoins($qb, $childs, $field);
		}
		return $qb;
	}

	protected function getQbWithDefaultVersion(QueryBuilder $qb = null) {
		if(!($qb instanceOf QueryBuilder)) $qb = $this->createQueryBuilder(self::ELEMENT);
		$qb->where(self::ELEMENT.'.defaultVersion = :true')
			->setParameter('true', 1);
		return $qb;
	}

}