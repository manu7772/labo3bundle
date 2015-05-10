<?php

namespace labo\Bundle\TestmanuBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
// container
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
// Entité
use acmeGroup\LaboBundle\Entity\typeRemise;

class typeRemises extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface {
    private $ord            = 50;           // Ordre de chargement fixtures
    private $entity         = "typeRemise";
    private $container;
    private $manager;

    public function getOrder() { return $this->ord; } // l'ordre dans lequel les fichiers sont chargés

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
    }

    public function load(ObjectManager $manager) {
        // Remise à zéro de l'auto-incrément
        $this->manager = $manager;
        $connection = $this->manager->getConnection();

        // récupération du service entitiesGeneric
        $this->EntityService = $this->container->get('acmeGroup.entities')->defineEntity($this->entity);

        $connection->exec("ALTER TABLE ".$this->EntityService->getEntiteName()." AUTO_INCREMENT = 1;");

        $entityL = $this->container->get('acmeGroup.fixturesLoader')->loadEntity($this->EntityService, $this->manager);

        if($entityL !== false) {
            echo("Lignes de l'entité enregistrées : ".$this->entity."\n");
        }


        $liste = array();
        $noms = array(
            "Réduction"         => array("Montant à déduire du prix initial", "Formule"),
            "Pourcentage"       => array("Réduction en pourcentage", "Formule"),
            "Prix déterminé"    => array("statut banni", "Formule")
        );
        foreach($noms as $nom => $val) {
            $liste[$nom] = new typeRemise();
            $liste[$nom]->setNom($nom);
            $liste[$nom]->setDescriptif($val[0]);
            $liste[$nom]->setFormule($val[1]);
            $manager->persist($liste[$nom]);
        }
        $manager->flush();
    }


}
