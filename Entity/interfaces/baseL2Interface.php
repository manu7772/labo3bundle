<?php

namespace laboBundle\Entity\interfaces;

use \DateTime;

/**
 * Éléments pour entité de base L2
 */
interface baseL2Interface {

	public function setStatut($statut = null);
	public function getStatut();
	public function setVersion($version = null);
	public function getVersion();

}