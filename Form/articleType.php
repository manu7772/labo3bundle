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

class articleType extends AbstractType {

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
				"label"     => 'Nom de l\'article'
				))
			->add('marque', 'entity', array(
				'class'     => 'AcmeGroupLaboBundle:marque',
				'property'  => 'nom',
				'multiple'  => false,
				"label"     => 'Marque'
				))
            ->add('categories', 'entity', array(
                "required"  => true,
                'class'     => 'AcmeGroupLaboBundle:categorie',
                'property'  => 'nom',
                'multiple'  => true,
                "label"     => 'Catégories',
                "query_builder" => function(\AcmeGroup\LaboBundle\Entity\categorieRepository $cat) {
                    return $cat->getSelectListForArticle();
                    }
                ))
			->add('reseaus', 'entity', array(
				"required"  => true,
				'class'     => 'AcmeGroupLaboBundle:reseau',
				'property'  => 'nom',
				'multiple'  => true,
				"label"     => 'Réseaux de distribution'
				))
			->add('serie', 'text', array(
				"required"  => false,
				"label"     => 'Série de l\'article',
				))
			->add('dateExpiration', 'datepicker', array( // --> datepicker : élément de formulaire personnalisé passé en service !!!
				"required"  => false,
				"label"     => 'Date d\'expiration'
				))
			->add('refFabricant', 'text', array(
				"required"  => false,
				"label"     => 'Référence fabricant'
				))
			->add('accroche', 'text', array(
				"required"  => false,
				"label"     => 'Texte d\'accroche'
				))
			->add('styleAccroche', 'choice', array(
				"required"  => true,
				"label"     => 'Style de l\'accroche',
				"choices"   => array("normal", "rouge", "bleu", "orange", "pack")
				))
			->add('prix', "number", array(
				"required"  => false,
				"label"     => 'Prix de vente TTC'
				))
			->add('texteprix', 'text', array(
				"required"  => false,
				"label"     => 'Précision avant le prix'
				))
			->add('plusVisible', 'checkbox', array(
				"label"     => 'Augmenter visibilité',
				"required"  => false
				))
			->add('imagePpale', 'entity', array(
				'class'     => 'AcmeGroupLaboBundle:image',
				'property'  => 'nom',
				'multiple'  => false,
				"label"     => 'Image de l\'article',
				"required"  => false,
				'query_builder' => function(\AcmeGroup\LaboBundle\Entity\imageRepository $i) {
					return $i->findImageByTypes(array('Article', 'Universel'));
					},
				'empty_value' => 'Sélectionner…'
				))
			->add('images', 'entity', array(
				'class'     => 'AcmeGroupLaboBundle:image',
				'property'  => 'nom',
				'multiple'  => true,
				"label"     => 'Autres images',
				"required"  => false,
				'query_builder' => function(\AcmeGroup\LaboBundle\Entity\imageRepository $i) {
					return $i->findImageByTypes(array('Universel','Article','Ambiance'));
					}
				))
            ->add('statut', 'entity', array(
                // "disabled"  => true,
                "required"  => true,
                'class'     => 'AcmeGroupLaboBundle:statut',
                'property'  => 'nom',
                'multiple'  => false,
                "label"     => 'Statut de l\'article',
                "query_builder" => function(\AcmeGroup\LaboBundle\Entity\statutRepository $qb) {
                    return $qb->defaultValClosure();
                    }
                ))
			->add('tauxTVA', 'entity', array(
				'class'     => 'AcmeGroupLaboBundle:tauxTVA',
				'property'  => 'nomlong',
				'multiple'  => false,
				"label"     => 'Taux de TVA'
				))
            ->add('fichierPdf', new fichierPdfMiniType($this->controller), array(
                "required"  => false,
                "label"     => "Information produit PDF"
                ))
            ->add('ficheTechniquePdf', new fichierPdfMiniType($this->controller), array(
                "required"  => false,
                "label"     => "Fiche technique légale PDF"
                ))
			->add('articlesLies', 'entity', array(
				'class'     => 'AcmeGroupLaboBundle:article',
				'property'  => 'nom',
				'multiple'  => true,
				'expanded'  => false,
				"required"  => false,
				"label"     => 'Articles conseillés'
				))
			->add('videos', 'entity', array(
				'class'     => 'AcmeGroupLaboBundle:video',
				'property'  => 'nom',
				'multiple'  => true,
				'expanded'  => false,
				"required"  => false,
				"label"     => 'Vidéos associées'
				))
			->add('ficheCreatives', 'entity', array(
				'class'     => 'AcmeGroupLaboBundle:ficheCreative',
				'property'  => 'nom',
				'multiple'  => true,
				'expanded'  => false,
				"required"  => false,
				"label"     => 'Fiches créatives associées'
				))
			->add('versions', 'entity', array(
				'class'     => 'AcmeGroupLaboBundle:version',
				'property'  => 'nom',
				'multiple'  => true,
				'expanded'  => true,
				"label"     => 'Affectations version'
				))
			->add('descriptif', 'richtext', array(
				"required"  => false,
				"label"     => 'Descriptif article'
				))
			->add('avisDuTechnicien', 'richtext', array(
				"required"  => false,
				"label"     => 'Avis du technicien'
				))
			// ->add('fichierPdf', new fichierPdfType(), array(
			//     'label'     => 'Fiche technique'
			//     ))
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
			'data_class' => 'AcmeGroup\LaboBundle\Entity\article'
		));
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'acmegroup_labobundle_article';
	}
}
