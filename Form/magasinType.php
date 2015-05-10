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

class magasinType extends AbstractType {

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
        $magasin = new \AcmeGroup\LaboBundle\Entity\magasin();

        $builder
            ->add('nommagasin', 'text', array(
                'required'  => true,
                'label'     => 'Nom du magasin',
                ))
            ->add('code', 'text', array(
                'required'  => false,
                'label'     => 'Code magasin',
                ))
            ->add('secteur', 'text', array(
                'required'  => false,
                'label'     => 'Secteur',
                ))
            ->add('responsable', 'text', array(
                'required'  => true,
                'label'     => 'Nom du responsable du magasin',
                ))
            ->add('adresse', 'textarea', array(
                'required'  => true,
                'label'     => 'Adresse du magasin',
                ))
            ->add('cp', 'text', array(
                'required'  => true,
                'label'     => 'Code postal',
                ))
            ->add('ville', 'text', array(
                'required'  => true,
                'label'     => 'Ville',
                ))
            ->add('departement', 'text', array(
                'required'  => true,
                'label'     => 'Département',
                ))
            ->add('telmobile', 'text', array(
                'required'  => false,
                'label'     => 'Tél. mobile',
                ))
            ->add('telephone', 'text', array(
                'required'  => false,
                'label'     => 'Tél. fixe',
                ))
            ->add('siteurl', 'url', array(
                'required'  => false,
                'label'     => 'URL du site',
                ))
            ->add('email', 'email', array(
                'required'  => false,
                'label'     => 'Adresse mail',
                ))
            ->add('commentaire', 'textarea', array(
                'required'  => false,
                'label'     => 'Commentaires',
                ))
            // ->add('type')
            // ->add('magvoisin1')
            // ->add('magvoisin2')
            ->add('raisonsociale', 'text', array(
                'required'  => false,
                'label'     => 'Raison sociale (nom) du magasin',
                ))
            // ->add('titreSeo')
            // ->add('descSeo')
            // ->add('metakey')
            ->add('typemagasin', 'choice', array(
                "required"  => true,
                "label"     => 'Type de magasin',
                'multiple'  => false,
                'expanded'  => false,
                "choices"   => $magasin->getTypeMagasins(),
                ))
            // ->add('posithoraire')
            ->add('item', 'text', array(
                'required'  => false
                ))
            ->add('plusVisible', 'checkbox', array(
                'required' => false,
                'label'    => "Mettre en avant sur le site"
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
            ->add('image', 'entity', array(
                'class'     => 'AcmeGroupLaboBundle:image',
                'property'  => 'nom',
                'multiple'  => false,
                "label"     => 'Image du magasin',
                "required"  => false,
                'query_builder' => function(\AcmeGroup\LaboBundle\Entity\imageRepository $i) {
                    return $i->findImageByTypes(array('magasin', 'Universel'));
                    },
                'empty_value' => '(utiliser image standard)'
                ))
            // ->add('image', new imageMiniType($this->controller), array(
            //     "required"  => false,
            //     "label"     => "Fichier photo (PNG / JPEG / GIF)"
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
                                "label"     => 'Statut du magasin'
                                ))
                            ->add('image', 'entity', array(
                                'class'     => 'AcmeGroupLaboBundle:image',
                                'property'  => 'nom',
                                'multiple'  => false,
                                "label"     => 'Image de l\'article',
                                "required"  => false,
                                // 'query_builder' => function(\AcmeGroup\LaboBundle\Entity\imageRepository $i) {
                                //     return $i->findImageByTypes(array('magasin'));
                                //     },
                                'empty_value' => '(utiliser image standard)'
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
            'data_class' => 'AcmeGroup\LaboBundle\Entity\magasin'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'acmegroup_labobundle_magasin';
    }
}
