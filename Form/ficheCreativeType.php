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

class ficheCreativeType extends AbstractType {

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
        $ficheCreative = new \AcmeGroup\LaboBundle\Entity\ficheCreative();

        $builder
            ->add('nom', 'text', array(
                "label"     => 'Nom de la fiche'
                ))
            ->add('accroche', 'text', array(
                "label"     => 'Titre de la fiche',
                "required"  => false
                ))
            ->add('descriptif', 'richtext', array(
                "required"  => false,
                "label"     => 'Texte de la fiche'
                ))
            ->add('niveau', 'choice', array(
                "required"  => true,
                "label"     => 'Niveau',
                'multiple'  => false,
                'expanded'  => true,
                "choices"   => $ficheCreative->getListeNiveaux(),
                ))
            ->add('duree', 'choice', array(
                "required"  => true,
                "label"     => 'Temps de réalisation',
                'multiple'  => false,
                'expanded'  => false,
                "choices"   => $ficheCreative->getDurees(),
                ))
            ->add('datePublication', 'datepicker', array( // --> datepicker : élément de formulaire personnalisé passé en service !!!
                "required"  => false,
                "label"     => 'Date de publication'
                ))
            // ->add('dateMaj')
            ->add('dateExpiration', 'datepicker', array( // --> datepicker : élément de formulaire personnalisé passé en service !!!
                "required"  => false,
                "label"     => 'Date d\'expiration'
                ))
            // ->add('slug')
            ->add('statut', 'entity', array(
                // "disabled"  => true,
                "required"  => true,
                'class'     => 'AcmeGroupLaboBundle:statut',
                'property'  => 'nom',
                'multiple'  => false,
                "label"     => 'Statut de la fiche',
                "query_builder" => function(\AcmeGroup\LaboBundle\Entity\statutRepository $qb) {
                    return $qb->defaultValClosure();
                    }
                ))
            // ->add('image', new imageMiniType($this->controller), array(
            //     "required"  => false,
            //     "label"     => "Fichier image"
            //     ))
            ->add('image', 'entity', array(
                'class'     => 'AcmeGroupLaboBundle:image',
                'property'  => 'nom',
                'multiple'  => false,
                "label"     => 'Image de l\'article',
                "required"  => false,
                'query_builder' => function(\AcmeGroup\LaboBundle\Entity\imageRepository $i) {
                    return $i->findImageByTypes(array('atelier', 'Universel'));
                    },
                'empty_value' => '(aucune image)'
                ))
            ->add('categorie', 'entity', array(
                "required"  => true,
                'class'     => 'AcmeGroupLaboBundle:categorie',
                'property'  => 'nom',
                'multiple'  => false,
                "label"     => 'Thème atelier',
                "query_builder" => function(\AcmeGroup\LaboBundle\Entity\categorieRepository $cat) {
                    return $cat->getSelectListForFicheCreative();
                    }
                ))
            ->add('articles', 'entity', array(
                'class'     => 'AcmeGroupLaboBundle:article',
                'property'  => 'nom',
                'multiple'  => true,
                'expanded'  => false,
                "required"  => false,
                "label"     => 'Articles Singer associés',
                'empty_value' => '(aucun article associé)'
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
                            ->add('dateCreation', 'datepicker2alldates', array(
                                "required"  => false,
                                "label"     => 'Date de création'
                                ))
                            ->add('datePublication', 'datepicker2alldates', array(
                                "required"  => false,
                                "label"     => 'Date de publication'
                                ))
                            ->add('dateExpiration', 'datepicker2alldates', array(
                                "required"  => false,
                                "label"     => 'Date d\'expiration'
                                ))
                            ->add('versions', 'entity', array(
                                'class'     => 'AcmeGroupLaboBundle:version',
                                'property'  => 'nom',
                                'multiple'  => true,
                                'expanded'  => false,
                                "required"  => true,
                                "label"     => 'Versions du site',
                                ))
                            ->add('propUser', 'entity', array(
                                'class'     => 'AcmeGroupUserBundle:User',
                                'property'  => 'username',
                                'multiple'  => false,
                                'expanded'  => false,
                                "required"  => false,
                                "label"     => 'Possesseur',
                                'empty_value' => '(aucun possesseur)',
                                "query_builder" => function(\AcmeGroup\UserBundle\Entity\UserRepository $user) {
                                    return $user->getEditorsAndMore();
                                    }
                                ))
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
            'data_class' => 'AcmeGroup\LaboBundle\Entity\ficheCreative'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'acmegroup_labobundle_fichecreative';
    }
}
