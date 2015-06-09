<?php

namespace laboBundle\Entity\interfaces;

use \DateTime;

/**
 * Éléments pour entité de base L0
 */
interface baseL0Interface {

	const SLASH = "/";
	const ASLASH = "\\";

	public function getParentClassName();
	public function getParentshortName();
	public function getClassName();
	public function getName();

	// Unique field
	public function setUniquefield($uniquefield);
	public function getUniquefield();
	public function addToUniqueField($key, $value);
	public function removeFromUniqueField($key);

	public function isValid();
	public function verify();

	public function getId();
	public function setNom($nom);
	public function getNom();
	public function setSlug($slug);
	public function getSlug();
	public function addThisread($role = null);
	public function removeThisread($role);
	public function getThisreads();
	public function addThiswrite($role = null);
	public function removeThiswrite($role);
	public function getThiswrites();
	public function addThisdelete($role = null);
	public function removeThisdelete($role);
	public function getThisdeletes();

}