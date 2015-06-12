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

	public function getVersionSlugArray($versionSlug) {
		$qb = $this->createQueryBuilder('element');
		$qb->where('element.slug = :slug')
			->setParameter('slug', $versionSlug);
		$result = $qb->getQuery()->getArrayResult();
		if(is_array($result) && count($result) > 0) return reset($result);
		throw new Exception("La version ".$versionSlug." (slug) n'a pu être trouvée.", 1);
	}

}