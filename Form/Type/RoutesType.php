<?php
// src/AcmeGroup/LaboBundle/Form/Type/RoutesType.php
namespace labo\Bundle\TestmanuBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
// use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class RoutesType extends AbstractType {

    private $controller;
    private $routes = array();
    private $motif = "acme_site_";
    
    public function __construct($controller) {
        $this->controller = $controller;
		$rts = $this->controller->get("acmeGroup.aetools")->getAllRoutes($this->motif);
		foreach($rts as $route) $this->routes[$route] = str_replace($this->motif, "", $route);
    }

	public function setDefaultOptions(OptionsResolverInterface $resolver) {

		$resolver->setDefaults(array(
			'choices' => $this->routes
		));
	}

	public function getParent()
	{
		return 'choice';
	}

	public function getName()
	{
		return 'routes';
	}
}



?>