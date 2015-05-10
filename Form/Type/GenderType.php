<?php
// src/AcmeGroup/LaboBundle/Form/Type/GenderType.php
namespace labo\Bundle\TestmanuBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GenderType extends AbstractType
{
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
			'choices' => array(
				'm' => 'Monsieur',
				'f' => 'Madame',
			)
		));
	}

	public function getParent()
	{
		return 'choice';
	}

	public function getName()
	{
		return 'gender';
	}
}

?>