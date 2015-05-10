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

class pagewebType extends AbstractType {

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
				"label"     => 'Nom',
				))
			->add('title', 'textarea', array(
				"label"     => 'Balise <title>',
				'required'  => false
				))
			->add('titreh1', 'textarea', array(
				"label"     => 'Balise <H1>',
				'required'  => false
				))
			// ->add('metatitle', 'textarea', array(
			//     "label"     => 'Balise <meta title>',
			//     'required'  => false
			//     ))
			->add('metadescription', 'textarea', array(
				"label"     => 'Balise <meta description>',
				'required'  => false
				))
			->add('code', 'richtext', array(
				"label"     => 'Code de la page web',
				'required'  => false
				))
			->add('fichierhtml', 'text', array(
				"label"     => 'Template (html)',
				'required'  => false,
				))
			->add('tags', 'entity', array(
				'class'     => 'AcmeGroupLaboBundle:tag',
				'property'  => 'nom',
				'multiple'  => true,
				'expanded'  => false,
				"required"  => false,
				"label"     => 'Tags'
				))
			->add('richtexts', 'entity', array(
			   'class'     => 'AcmeGroupLaboBundle:richtext',
			   'property'  => 'nom',
			   'multiple'  => true,
			   'expanded'  => false,
			   "required"  => false,
			   "label"     => 'Textes (richtext)'
			   ))
			->add('diaporama', 'entity', array(
				'class'     => 'AcmeGroupLaboBundle:collection',
				'property'  => 'nom',
				'multiple'  => false,
				"label"     => 'Diaporama',
				"required"  => false,
                'empty_value' => '(aucun)'
				))
			->add('route', 'text', array(
				"label"     => "Nom de la route",
				"required"  => true,
				))
			// ->add('route', 'routes', array(
			//     'multiple'  => false,
			//     'expanded'  => false,
			//     "label"     => 'Route'
			//     ))
			->add('firstmedia', 'entity', array(
				'class'     => 'AcmeGroupLaboBundle:image',
				'property'  => 'nom',
				'multiple'  => false,
				"label"     => 'Première image',
				"required"  => false,
                'query_builder' => function(\AcmeGroup\LaboBundle\Entity\imageRepository $i) {
                    return $i->findImageByTypes(array('site', 'evenement', 'partenaire','diaporama','universel','ambiance'));
                    },
                'empty_value' => '(aucun)'
				))
			->add('medias', 'entity', array(
				'class'     => 'AcmeGroupLaboBundle:image',
				'property'  => 'nom',
				'multiple'  => true,
				"label"     => 'Autres images…',
				"required"  => false,
                'query_builder' => function(\AcmeGroup\LaboBundle\Entity\imageRepository $i) {
                    return $i->findImageByTypes(array('site', 'evenement', 'partenaire','diaporama','universel','ambiance'));
                    },
                'empty_value' => '(aucun)'
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
						$form
							->add('nom', 'text', array(
								"label"     => 'Nom',
								'disabled'  => true
								)
							);
                    }
                }
                if($user !== "anon.") {
					// Si ROLE_EDITOR, on change ces champs :
					if(in_array("ROLE_EDITOR", $user->GetRoles())) {
						$form->add('nom', 'text', array(
								"label"     => 'Nom',
								'disabled'  => true
								))
							->add('fichierhtml', 'text', array(
								"label"     => 'Template (html)',
								'required'  => false,
								'disabled'  => true
								))
							->add('route', 'text', array(
								"label"     => "Nom de la route",
								"required"  => true,
								'disabled'  => true
								)
						);
					}
					// Si ROLE_ADMIN, on change ces champs :
					if(in_array("ROLE_ADMIN", $user->GetRoles())) {
						//
					}
					// Si ROLE_SUPER_ADMIN, on change ces champs :
					if(in_array("ROLE_SUPER_ADMIN", $user->GetRoles())) {
						$form->add('nom', 'text', array(
								"label"     => 'Nom',
								'disabled'  => false
								)
							);
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
			'data_class' => 'AcmeGroup\LaboBundle\Entity\pageweb'
		));
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'acmegroup_labobundle_pageweb';
	}
}
