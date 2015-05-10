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

class demandedevisType extends AbstractType {

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
            ->add('civilite', "gender", array(
                "required"  => true,
                "label"     => 'Civilité'
                ))
            ->add('nom', 'text', array(
                "required"  => true,
                "label"     => 'Nom'
                ))
            ->add('prenom', 'text', array(
                "required"  => true,
                "label"     => 'Prénom'
                ))
            ->add('entreprise', 'text', array(
                "required"  => true,
                "label"     => 'Entreprise / Raison sociale'
                ))
            ->add('email', 'email', array(
                "required"  => false,
                "label"     => 'Adresse mail'
                ))
            ->add('tel', 'text', array(
                "required"  => true,
                "label"     => 'Téléphone fixe'
                ))
            ->add('port', 'text', array(
                "required"  => false,
                "label"     => 'Téléphone portable'
                ))
            ->add('fax', 'text', array(
                "required"  => false,
                "label"     => 'Fax'
                ))
            ->add('adresse', new adresseMiniType($this->controller), array(
                "required"  => true,
                "label"     => "Adresse postale"
                ))
            ->add('demande', 'textarea', array(
                "required"  => true,
                "label"     => "Détail de votre demande de devis"
                ))
            ->add('copie', 'checkbox', array(
                "required"  => false,
                "label"     => "M'envoyer un mail en copie"
                ))
        ;
        // $builder = $this->addHiddenValues($builder);
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
            'data_class' => 'AcmeGroup\LaboBundle\Entity\demandedevis'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'acmegroup_labobundle_demandedevis';
    }
}
