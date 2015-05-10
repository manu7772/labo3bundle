<?php
// src/AcmeGroup/LaboBundle/Form/Type/richtextType.php
namespace labo\Bundle\TestmanuBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class richtextType extends AbstractType {

	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults(array(
		));
	}

	public function getParent() {
		return 'textarea';
	}

	public function getName() {
		return 'richtext';
	}
}

?>