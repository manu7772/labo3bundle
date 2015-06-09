<?php

namespace laboBundle\Entity\interfaces;

use \DateTime;

/**
 * Éléments pour entité de base L1
 */
interface baseL1Interface {

	public function setDescriptif($descriptif);
	public function getDescriptif();
	public function setdateCreation(DateTime $dateCreation);
	public function getdateCreation();
	public function setDateMaj(DateTime $dateMaj = null);
	public function getDateMaj();
	public function setDateExpiration(DateTime $dateExpiration = null);
	public function getDateExpiration();

}


?>