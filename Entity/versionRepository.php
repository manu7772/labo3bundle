<?php

namespace laboBundle\Entity;

use laboBundle\Entity\base_laboRepository;
// aeReponse
use laboBundle\services\aetools\aeReponse;

/**
 * versionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class versionRepository extends base_laboRepository {

	/**
	 * Renvoie true si la demande correspond correspond
	 * ex. : pour l'entité "baseL0_entity" -> "isBaseL0_entity" renvoie true
	 * @return boolean
	 */
	public function __call($name, $arguments = null) {
		switch ($name) {
			case 'is'.ucfirst($this->getName()):
				$reponse = true;
				break;
			default:
				$reponse = false;
				break;
		}
		return $reponse;
	}

	/**
	 * Renvoie le nom de l'entité parent
	 * @return string
	 */
	public function getParentName() {
		return parent::getName();
	}

	/**
	 * Renvoie le nom de l'entité
	 * @return string
	 */
	public function getName() {
		return 'versionRepository';
	}

	/**
	* defaultVal
	* Renvoie les versions par défaut
	* @return version / null
	*/
	public function defaultVal() {
		return $this->defaultVersion();
	}

	/**
	* defaultVersion
	* Renvoie l'instance de la version par défaut (ou null)
	* @return version / null
	*/
	public function defaultVersion() {
		$qb = $this->createQueryBuilder('element');
		$qb->where('element.defaut = :true')
			->setParameter('true', 1)
			->setMaxResults(1);
		return $qb->getQuery()->getOneOrNullResult();
	}

	/**
	* defaultVersion
	* Renvoie l'instance de la version en cours (ou null)
	* @return version / null
	*/
	public function currentVersion() {
		$slug = "mettre ici la version par défaut";
		$qb = $this->createQueryBuilder('element');
		$qb->where('element.slug = :slug')
			->setParameter('slug', $slug);
		return $qb->getQuery()->getOneOrNullResult();
	}

}

