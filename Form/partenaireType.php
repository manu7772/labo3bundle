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

class partenaireType extends AbstractType {

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
                "label"     => 'Nom'
                ))
            ->add('accroche', 'text', array(
                "required"  => false,
                "label"     => 'Accroche'
                ))
            ->add('url', 'text', array(
                "required"  => false,
                "label"     => 'Adresse du site web (avec "http://" !)'
                ))
            ->add('urlflux', 'text', array(
                "required"  => false,
                "label"     => 'Adresse du flux info (avec "http://" et extension)'
                ))
            ->add('email', 'email', array(
                "required"  => false,
                "label"     => 'Adresse mail'
                ))
            ->add('tel', 'text', array(
                "required"  => false,
                "label"     => 'Téléphone'
                ))
            ->add('fax', 'text', array(
                "required"  => false,
                "label"     => 'Fax'
                ))
            ->add('descriptif', 'textarea', array(
                "required"  => false,
                "label"     => 'Descriptif'
                ))
            ->add('codepub', 'textarea', array(
                "required"  => false,
                "label"     => 'Code html de l\'encart web publicitaire'
                ))
            ->add('niveau', 'choice', array(
                "required"  => true,
                "label"     => 'Niveau du partenaire (priorité)',
                "choices"   => array(
                    1 => "niveau 1 (mini)",
                    2 => "niveau 2 (normal)",
                    3 => "niveau 3 (maxi)"
                    )
                ))
            ->add('logo', 'entity', array(
                'class'     => 'AcmeGroupLaboBundle:image',
                'property'  => 'nom',
                'multiple'  => false,
                "label"     => 'Logo partenaire',
                "required"  => false,
                'query_builder' => function(\AcmeGroup\LaboBundle\Entity\imageRepository $i) {
                    return $i->findImageByTypes(array('logo', 'Universel'));
                    }
                ))
            ->add('image', 'entity', array(
                'class'     => 'AcmeGroupLaboBundle:image',
                'property'  => 'nom',
                'multiple'  => false,
                "label"     => 'Image pub',
                "required"  => false,
                'query_builder' => function(\AcmeGroup\LaboBundle\Entity\imageRepository $i) {
                    return $i->findImageByTypes(array('partenaire', 'universel'));
                    }
                ))
            ->add('adresse', new adresseType($this->controller), array(
                "required"  => false,
                "label"     => "Adresse postale"
                ))
            ->add('commentaire', 'textarea', array(
                "required"  => false,
                "label"     => 'Compléments d\'informations'
                ))
        ;
        // $builder = $this->addHiddenValues($builder);

        // liste des entités
        $listOfEntities = $this->controller->get("acmeGroup.entities")->listOfEnties();
        if(in_array("typePartenaire", $listOfEntities)) {
            $builder->add('typePartenaire', 'entity', array(
                'class'     => 'AcmeGroupLaboBundle:typePartenaire',
                'property'  => 'nom',
                'multiple'  => false,
                "label"     => 'Type de partenaire',
                "required"  => false,
            ));
        }
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
            'data_class' => 'AcmeGroup\LaboBundle\Entity\partenaire'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'acmegroup_labobundle_partenaire';
    }
}
