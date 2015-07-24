<?php

namespace laboBundle\Entity\interfaces;

use \DateTime;

/**
 * Éléments pour entité de base avec attributs
 */
interface baseAttributsInterface {

	public function addAttribut($nom, $valeur);
	public function getAttribut($nom);
	public function removeAttribut($nom);
	public function removeAttributs();
	public function existsAttribut($nom);
	public function getAttributs();
	public function getAttributsInArrayCollection();

}