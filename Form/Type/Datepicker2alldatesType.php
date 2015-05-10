<?php
// src/AcmeGroup/LaboBundle/Form/Type/Datepicker2alldatesType.php
namespace labo\Bundle\TestmanuBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class Datepicker2alldatesType extends AbstractType {

	private $formatDate;

	public function __construct($formatDate) {
		$this->formatDate = $formatDate;
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults(array(
			'widget' => 'single_text',
			// 'empty_value' => '(produit non expirable)',
			'format' => $this->formatDate
		));
	}

	public function getParent() {
		return 'date';
	}

	public function getName() {
		return 'datepicker2alldates';
	}
}

?>