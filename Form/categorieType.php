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

class categorieType extends AbstractType {

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
                "required"  => true,
                ))
            ->add('couleur', 'text', array(
                "label"     => 'Couleur ("#000000" ou nom web)',
                "required"  => true,
                ))
            ->add('nommenu', 'text', array(
                "label"     => 'Nom du menu',
                "required"  => false,
                ))
            ->add('descriptif', 'textarea', array(
                "label"     => 'descriptif de la catégorie',
                "required"  => false,
                ))
            ->add('plusVisible', 'checkbox', array(
                "label"     => 'Augmenter visibilité',
                "required"  => false,
                ))
            ->add('statut', 'entity', array(
                // "disabled"  => true,
                "required"  => true,
                'class'     => 'AcmeGroupLaboBundle:statut',
                'property'  => 'nom',
                'multiple'  => false,
                "label"     => 'Statut de la catégorie',
                "query_builder" => function(\AcmeGroup\LaboBundle\Entity\statutRepository $qb) {
                    return $qb->defaultValClosure();
                    },
                ))
            ->add('page', 'entity', array(
                'multiple'  => false,
                'class'     => 'AcmeGroupLaboBundle:pageweb',
                'expanded'  => false,
                "required"  => false,
                'property'  => 'nom',
                "label"     => 'Page web',
                ))
            ->add('parent', 'entity', array(
                'class'     => 'AcmeGroupLaboBundle:categorie',
                'property'  => 'nom',
                'multiple'  => false,
                "required"  => false,
                'label'     => "Catégorie parent",
                ))
            ->add('children', 'entity', array(
                'class'     => 'AcmeGroupLaboBundle:categorie',
                'property'  => 'nom',
                'multiple'  => true,
                "required"  => false,
                'label'     => "Catégories enfants",
                'disabled'  => true,
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
                            ->add('dateCreation', "datepicker2alldates", array(
                                "required"  => true,
                                "label"     => 'Date de création',
                                ))
                            ->add('dateExpiration', "datepicker2alldates", array(
                                "required"  => false,
                                "label"     => 'Date d\'expiration',
                                ))
                            ->add('versions', 'entity', array(
                                'class'     => 'AcmeGroupLaboBundle:version',
                                'property'  => 'nom',
                                'multiple'  => true,
                                'expanded'  => true,
                                "label"     => 'Versions'
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
            'data_class' => 'AcmeGroup\LaboBundle\Entity\categorie'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'acmegroup_labobundle_categorie';
    }
}
