<?php
// labo/Bundle/TestmanuBundle/services/aetools/richtextsAetools.php

namespace labo\Bundle\TestmanuBundle\services\aetools;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class richtextsAetools {

	private $container;
	private $em;
	private $repo;


	public function __construct(ContainerInterface $container) {
		$this->container = $container;
		$this->em = $this->container->get('doctrine')->getManager();
		$this->repo = $this->em->getRepository('AcmeGroup\\LaboBundle\\Entity\\richtext');
	}

	/**
	 * getAllTexts
	 * 
	 */
	public function getAllTexts() {
		$t = $this->repo->findAll();
		return $t;
	}

	/**
	 * getTextBySlug
	 * 
	 */
	public function getTextBySlug($slug) {
		$t = $this->repo->findBySlug($slug);
		if(count($t) > 0) return $t[0];
		else return false;
	}

	/**
	 * getTextByNom
	 * 
	 */
	public function getTextByNom($nom) {
		$t = $this->repo->findByNom($nom);
		if(count($t) > 0) return $t[0];
		else return false;
	}

	/**
	 * getListByTag
	 * @param mixed (string/array)
	 */
	public function getListByTag($tags) {
		$t = $this->repo->findListByTag($tags);
		if(count($t) > 0) {
			return $t;
		} else return false;
	}

	/**
	 * getTextById
	 * 
	 */
	public function getTextById($id) {
		$t = $this->repo->find($id);
		if(is_object($t)) return $t;
		else return false;
	}

	/**
	 * getRandomTexts
	 * renvoie $q textes parmi la liste $listOfId
	 */
	public function getRandomTexts($q, $listOfId) {

	}

	/**
	 * typeRichtextList
	 * renvoie tous les types de richtext
	 * @return typeRichtext
	 */
	public function typeRichtextList() {
		return $this->em->getRepository('AcmeGroup\\LaboBundle\\Entity\\typeRichtext')->findAll();
	}

	/**
	 * hasTypeRichtext
	 * renvoie true si le type typeRichtext existe
	 * @param string
	 * @return boolean
	 */
	public function hasTypeRichtext($typeRichtextNom) {
		if(count($this->em->getRepository('AcmeGroup\\LaboBundle\\Entity\\typeRichtext')->findByNom($typeRichtextNom)) > 0) return true;
			else return false;
	}


}

?>
