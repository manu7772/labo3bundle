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

class tagType extends AbstractType {

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
                "required" => true,
                "label" => 'Nom du tag'))
            ->add('richtexts', 'entity', array(
               'class'     => 'AcmeGroupLaboBundle:richtext',
               'property'  => 'nom',
               'multiple'  => true,
               'expanded'  => false,
               "required"  => false,
               "label"     => 'Richtexts',
               'disabled'  => true
               ))
            ->add('pagewebs', 'entity', array(
               'class'     => 'AcmeGroupLaboBundle:pageweb',
               'property'  => 'nom',
               'multiple'  => true,
               'expanded'  => false,
               "required"  => false,
               "label"     => 'Pages web',
               'disabled'  => true
               )
            )
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
                        //
                    }
                }
            }
        );
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AcmeGroup\LaboBundle\Entity\tag'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'acmegroup_labobundle_tag';
    }

}
