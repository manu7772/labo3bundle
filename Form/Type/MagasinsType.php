<?php
// src/AcmeGroup/LaboBundle/Form/Type/MagasinsType.php
namespace labo\Bundle\TestmanuBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
// use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MagasinsType extends AbstractType {

    private $controller;
    private $villemags = array();
    
    public function __construct($controller) {
        $this->controller = $controller;
		$this->villemags = $this->controller->get("acmeGroup.magasin")->listeVilleAvecMagasin();
    }

	public function setDefaultOptions(OptionsResolverInterface $resolver) {

		$resolver->setDefaults(array(
			// 'choices' 	=> $this->villemags,
            "required"  => true,
            'class'     => 'AcmeGroupLaboBundle:magasin',
            'property'  => 'nommagasin',
            'multiple'  => false,
            "label"     => 'Boutique préférée :',
		));
	}

	public function getParent()
	{
		return 'entity';
	}

	public function getName()
	{
		return 'magasins';
	}
}



?>