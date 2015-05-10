<?php

namespace labo\Bundle\TestmanuBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
// User
use Symfony\Component\Security\Core\SecurityContext;
// Paramétrage de formulaire
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class collectionType extends AbstractType {

    private $controller;
    private $securityContext;
    private $parametres;
    
    public function __construct(Controller $controller, $parametres = null) {
        $this->controller = $controller;
        $this->securityContext = $controller->get('security.context');
        if($parametres === null) $parametres = array();
        $this->parametres = $parametres;
    }

    /**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('nom', 'text', array(
				"required"  => true,
				"label"     => 'Nom de la collection'
				))
			->add('descriptif', 'text', array(
				"required"  => false,
				"label"     => 'Texte descriptif'
				))
			->add('dateExpiration', 'datepicker', array( // --> datepicker : élément de formulaire personnalisé passé en service !!!
				"required"  => false,
				"label"     => 'Date d\'expiration'
				))
			->add('firstmedia', 'entity', array(
				'class'     => 'AcmeGroupLaboBundle:image',
				'property'  => 'nom',
				'multiple'  => false,
				"label"     => 'Premier média',
				"required"  => false,
                'query_builder' => function(\AcmeGroup\LaboBundle\Entity\imageRepository $i) {
                    return $i->findImageByTypes(array('diaporama','universel','ambiance'));
                    },
                'empty_value' => '(aucun)'
				))
			->add('medias', 'entity', array(
				'class'     => 'AcmeGroupLaboBundle:image',
				'property'  => 'nom',
				'multiple'  => true,
				"label"     => 'Médias',
				"required"  => false,
                'query_builder' => function(\AcmeGroup\LaboBundle\Entity\imageRepository $i) {
                    return $i->findImageByTypes(array('diaporama','universel','ambiance'));
                    },
                'empty_value' => '(aucun)'
				))
            ->add('statut', 'entity', array(
                // "disabled"  => true,
                "required"  => true,
                'class'     => 'AcmeGroupLaboBundle:statut',
                'property'  => 'nom',
                'multiple'  => false,
                "label"     => 'Statut du magasin',
                "query_builder" => function(\AcmeGroup\LaboBundle\Entity\statutRepository $qb) {
                    return $qb->defaultValClosure();
                    }
                ))
			->add('versions', 'entity', array(
				'class'     => 'AcmeGroupLaboBundle:version',
				'property'  => 'nom',
				'multiple'  => true,
				'expanded'  => true,
				"label"     => 'Affectations version'
				))
			->add('vitesse', 'text', array(
				"required"  => true,
				"label"     => 'Tempo entre diapositives (en ms)'
				))
		;
		// $builder = $this->addHiddenValues($builder);

		///////////////////////////////////////////////
		// Changement du formulaire selon paramètres //
		///////////////////////////////////////////////
		// $factory = $builder->getFormFactory();
		$user = $this->securityContext->getToken()->getUser();

		$builder->addEventListener(
			FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($user) {
				$data = $event->getData();
				$form = $event->getForm();

                if(is_object($data) && method_exists($data, "getId")) {
                    if($data->getId() === null) {
                        // L'entité n'existe pas
                        // $event->getForm()->add(
                        //     $factory->createNamed(
                        //         'file', 'file', array("label" => "Fichier (png/jpeg/gif)")
                        //     ));
                    } else {
                        // L'entité existe
                        // $form
                        //  ->remove('file')
                        //  ->add('nom', 'text', array(
                        //      "required"  => false,
                        //      "label"     => 'Nom de l\'image'
                        //  ));
                    }
                }
                if($user !== "anon.") {
					// Si ROLE_EDITOR, on change ces champs :
					if(in_array("ROLE_EDITOR", $user->GetRoles())) {
						//
					}
					// Si ROLE_ADMIN, on change ces champs :
					if(in_array("ROLE_ADMIN", $user->GetRoles())) {
						//
					}
					// Si ROLE_SUPER_ADMIN, on change ces champs :
					if(in_array("ROLE_SUPER_ADMIN", $user->GetRoles())) {
                	    $form
                	        ->add('statut', 'entity', array(
                	            'class'     => 'AcmeGroupLaboBundle:statut',
                	            'property'  => 'nom',
                	            'multiple'  => false,
                	            "label"     => 'Statut'
                	            ))
                	        ;
					}
				}
			}
		);
	}
	
	/**
	 * addHiddenValues
	 * @param FormBuilderInterface $builder
	 * @return FormBuilderInterface
	 */
	public function addHiddenValues(FormBuilderInterface $builder) {
		if(array_key_exists("hidden", $this->parametres)) {
			foreach($this->parametres as $nom => $hidd) {
				if($builder->has($nom)) $builder->remove($nom);
				$builder->add($nom, 'hidden', array(
					'data' => serialize($hidd)
				));
			}
		}
		return $builder;
	}

	/**
	 * @param OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'AcmeGroup\LaboBundle\Entity\collection'
		));
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'acmegroup_labobundle_collection';
	}
}
