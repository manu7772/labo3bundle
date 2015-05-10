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

class adresseUserType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('adresse', 'textarea', array(
                "required"  => true,
                "label"     => 'Adresse complète'
                ))
            ->add('cp', 'text', array(
                "required"  => true,
                "label"     => 'Code Postal (5 chiffres)'
                ))
            ->add('ville', 'text', array(
                "required"  => true,
                "label"     => 'Ville'
                ))
            // ->add('commentaire', 'textarea', array(
            //     "required"  => false,
            //     "label"     => 'Compléments d\'adresse'
            //     ))
        ;
        // $builder = $this->addHiddenValues($builder);
    }
    

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'acmegroup_labobundle_adresseuser';
    }
}
