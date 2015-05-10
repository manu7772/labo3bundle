<?php

namespace labo\Bundle\TestmanuBundle\eventListeners\sessionData;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use \DateTime;

class sessionDataListerner {
  protected $dateFin;

  public function __construct($dateFin) {
      $this->dateFin = new DateTime($dateFin);
    }

  public function onKernelResponse(FilterResponseEvent $event) {
      // On teste si la requête est bien la requête principale
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
              return;
            }
        $response = $event->getResponse();

        $joursRestant = $this->dateFin->diff(new DateTime())->days;

        if ($joursRestant > 0) {
          // On utilise notre méthode « reine »
          $response = $this->displayBeta($event->getResponse(), $joursRestant);
        }
        $event->setResponse($response);
      // Puis on insère la réponse modifiée dans l'évènement
  }

  protected function displayBeta(Response $response, $joursRestant)
  {
    // $content = $response->getContent();
    // Code à rajouter
    // $html = '<span class="beta"> - Beta : J-'.(int) $joursRestant.'</span>';
    // Insertion du code dans la page, dans le <h1> du header
    // $content = preg_replace('#<p class="navbar-text navbar-left">(.*?)</p>#iU', '<p class="navbar-text navbar-left">$1'.$html.'</p>', $content, 1);

    $content = '<!DOCTYPE html><!-- HTML 5 --><html lang="fr"><html><body>OK !!!</body></html>';
  
    // Modification du contenu dans la réponse
    $response->setContent($content);
  
    return $response;
  }

}