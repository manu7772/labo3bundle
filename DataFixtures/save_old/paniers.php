<?php

namespace labo\Bundle\TestmanuBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
// use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use acmeGroup\LaboBundle\Entity\panier;

class paniers extends AbstractFixture implements OrderedFixtureInterface {

    private $entity         = "panier";

    public function getOrder() { return 70; } // l'ordre dans lequel les fichiers sont chargés

    public function load(ObjectManager $manager) {
        // Remise à zéro de l'auto-incrément
        // $connection = $manager->getConnection();
        // $connection->exec("ALTER TABLE ".$this->entity." AUTO_INCREMENT = 1;");

        // $noms = array(
        //     array('France', "fr", "Paris"),
        //     array('Angleterre', "gb", "London"),
        //     array('Allemagne', "al", "Berlin"),
        //     array('Italie', "it", "Rome"),
        //     array('Espagne', "es", "Madrid")
        // );
        // foreach($noms as $i => $nom) {
        //     $liste[$i] = new panier();
        //     $liste[$i]->setNom($nom[0]);
        //     $liste[$i]->setSigle($nom[1]);
        //     $liste[$i]->setCapitale($nom[2]);
        //     $manager->persist($liste[$i]);
        // }
        // $manager->flush();
    }



}
