<?php

namespace labo\Bundle\TestmanuBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
// container
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
// Entité
use acmeGroup\LaboBundle\Entity\pays;

class payss extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface {
    private $ord            = 10;          // Ordre de chargement fixtures
    private $entity         = "pays";
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

        $noms = array(
            array('France', "fr", "Paris"),
            array('Angleterre', "en", "London"),
            array('Allemagne', "al", "Berlin"),
            array('Italie', "it", "Rome"),
            array('Espagne', "es", "Madrid")
        );
        foreach($noms as $i => $nom) {
            $liste[$i] = new pays();
            $liste[$i]->setNom($nom[0]);
            $liste[$i]->setSigle($nom[1]);
            $liste[$i]->setCapitale($nom[2]);
            $manager->persist($liste[$i]);
        }
        $manager->flush();
    }



}
