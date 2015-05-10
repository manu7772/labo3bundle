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

class imageType extends AbstractType {

    private $controller;
    private $securityContext;
    private $parametres;
    
    public function __construct(Controller $controller, $parametres = null) {
        $this->controller = $controller;
        $this->securityContext = $controller->get('security.context');
        // gestion des paramètres
        if($parametres === null) $parametres = array();
        if(is_string($parametres)) $parametres = array($parametres);
        $this->parametres = $parametres;
    }

    /**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('nom', 'text', array(
				"required"  => false,
				"label"     => 'Nom de l\'image'
				))
			->add('descriptif', 'richtext', array(
				"required"  => false,
				'label'		=> 'Description de l\'image',
				))
			// ->add('notation')
			// ->add('dateCreation')
			// ->add('dateExpiration')
			->add('file', 'file', array(
                "required"  => true,
				"label"     => "Fichier (png/jpeg/gif)",
				))
			// ->add('fichierOrigine')
			// ->add('fichierNom')
			// ->add('tailleX')
			// ->add('tailleY')
			// ->add('tailleMo')
			// ->add('proprietaireId')
		;

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
						// Edition des paramètres de l'image existante (sans champs file, puisque l'image est chargée)
						$form
							->remove('file')
	                        ->add('statut', 'entity', array(
	                            'class'     => 'AcmeGroupLaboBundle:statut',
	                            'property'  => 'nom',
	                            'multiple'  => false,
	                            "label"     => 'Statut de l\'image',
	                			"query_builder" => function(\AcmeGroup\LaboBundle\Entity\statutRepository $qb) {
	                			    return $qb->defaultValClosure();
	                			    }
	                            ))
	                        ;
                    }
                    // ajout du champs URL uniquement pour le typeImage diaporama
                    // --> à vérifier, mais valable aussi pour une nouvelle image, puisque le type est inséré avant la création du formulaire
                    $typesIm = $data->getTypeImages();
                    foreach($typesIm as $typ) if($typ->getNom() === "diaporama") {
						$form->add('url', 'textarea', array(
							"required"  => false,
							"label"		=> "Url liée à l'image",
							))
						;
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
							->add('typeImages', 'entity', array(
								'class'     => 'AcmeGroupLaboBundle:typeImage',
								'property'  => 'nom',
								'multiple'  => true,
								"label"     => 'Types de l\'image'
								))
           				    ->add('dateCreation', 'datepicker2alldates', array(
                	            "required"  => false,
                	            "label"     => 'Date de création'
                	            ))
                	        ->add('dateExpiration', 'datepicker2alldates', array(
                	            "required"  => false,
                	            "label"     => 'Date d\'expiration'
                	            ))
							->add('statut', 'entity', array(
								'class'     => 'AcmeGroupLaboBundle:statut',
								'property'  => 'nom',
								'multiple'  => false,
								"label"     => 'Statut de l\'image'
								))
                	        ;
					}
				}
			});
    }
    

	/**
	 * @param OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'AcmeGroup\LaboBundle\Entity\image'
		));
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'acmegroup_labobundle_image';
	}


}
