<?php

namespace labo\Bundle\TestmanuBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;

class AelogController extends Controller {


	//////////////////////////
	// PAGES
	//////////////////////////

	/**
	 * statistiquesAction
	 * 
	 */
	public function statistiquesAction($stat = 'general', $details = null) {
		$dateSrv = $this->get("acmeGroup.aedates");
		$data = array();
		$data = array_merge($data, $dateSrv->getCalendEnCours());
		$data["id"] = 0;
		$data['listtypes'] = $this->listOptions();
		// vérification 
		if(in_array(strtolower($stat), $data['listtypes'])) {
			$data['typedata'] = strtolower($stat);
		} else {
			reset($data['listtypes']);
			$data["typedata"] = current($data['listtypes']);
		}
		// Liste des pages disponibles
		// $data["pages"] = $this->get("acmeGroup.pageweb")->getRepo()->findAll();
		// 
		// $statistiques = $this->get("acmeGroup.aelog");
		// $data["statistiques"] = $statistiques->findByType($data['typedata']);

		// infos
		$data["info"]["arriere"] = 12;
		$data["info"]["tempo"] = "mois";
		//
		switch($data['typedata']) {
			case "articles":
				$data["listeArticles"] = $this->get("acmeGroup.article")->getRepo()->aeFindAll();
				return $this->render('LaboTestmanuBundle:pages:statistiquesArticles.html.twig', $data);
				break;
			case "ventes":
				$data['articles'] = array();
				$data_article = array();
				if(is_string($details)) $data_article = $this->get('acmeGroup.article')->getRepo()->findBySlug($details);
				if(count($data_article) < 1) {
					// tous les articles
					$data["listeVentes"] = $this->get("acmeGroup.facture")->getRepo()->findFactures();
					$articles = $this->get('acmeGroup.article')->getRepo()->findArtECommerce();
					foreach ($articles as $key => $art) {
						if($art->getExclureseau() === "internet") {
							$data['articles'][$art->getNom()]['objet'] = $art;
							$data['articles'][$art->getNom()]['ventescalc'] = 0; // nombre total de ventes
							$data['articles'][$art->getNom()]['caventesht'] = 0; // total ventes prix HT
							$data['articles'][$art->getNom()]['caventettc'] = 0; // total ventes prix TTC
							// ajoute ventes
							foreach ($data['listeVentes'] as $key => $vente) {
								if($vente->isValidVente() === true) {
									foreach($vente->getDetailbyarticle() as $ky2 => $article) {
										if(($article['nom'] == $art->getNom())) {
											// vente effective
											$data['articles'][$art->getNom()]['ventescalc'] = $data['articles'][$art->getNom()]['ventescalc'] + $article['quantite'];
											$data['articles'][$art->getNom()]['caventesht'] = $data['articles'][$art->getNom()]['caventesht'] + $article['prixTHt'];
											$data['articles'][$art->getNom()]['caventettc'] = $data['articles'][$art->getNom()]['caventettc'] + $article['prixTTTC'];
										}
									}
								}
							}
							// calcul des totaux
							$data['totaux']['ventescalc'] = 0;
							$data['totaux']['caventesht'] = 0;
							$data['totaux']['caventettc'] = 0;
							foreach ($data['articles'] as $art2 => $values) {
								$data['totaux']['ventescalc'] += $values['ventescalc'];
								$data['totaux']['caventesht'] += $values['caventesht'];
								$data['totaux']['caventettc'] += $values['caventettc'];
							}
							// Ajustement des ventes sur entité article
							// $articles[$key]->setVentes($data['articles'][$art->getNom()]['ventescalc']);
						}
					}
					return $this->render('LaboTestmanuBundle:pages:statistiquesVentes.html.twig', $data);
				} else {
					// Etude sur 1 article
					$data['article'] = current($data_article);
					$ventes = $this->get("acmeGroup.facture")->getRepo()->findFactures($data['article']);
					$data["listeVentes"] = array();
					$data['ventescalc'] = 0;
					$data['caventesht'] = 0;
					$data['caventettc'] = 0;
					$data['periodes'] = array();
					foreach($ventes as $num => $vente) {
						if($vente->isValidVente() === true) {
							// ajout à la liste si la vente est effective (sans erreur de paiement)
							$data["listeVentes"][] = $vente;
							// Quantités et CA HT et TTC
							foreach($vente->getDetailbyarticle() as $k => $art) {
								if($art['nom'] == $data['article']->getNom()) {
									$data['ventescalc'] = $data['ventescalc'] + $art['quantite'];
									$data['caventesht'] = $data['caventesht'] + $art['prixTHt'];
									$data['caventettc'] = $data['caventettc'] + $art['prixTTTC'];
								}
							}
							// Quantités par mois
							$data["quanites"]["mensuelle"] = array();
							foreach($vente->getDetailbyarticle() as $k => $art) {
								if($art['nom'] == $data['article']->getNom() && $dateSrv->isDateValid($vente->getDateCreation(), $data["info"]["tempo"], $data["info"]["arriere"])) {
									$ddd = $vente->getDateCreation()->format("M Y");
									if(!isset($data['periodes'][$ddd])) $data['periodes'][$ddd] = 0;
									$data['periodes'][$ddd] = $data['periodes'][$ddd] + $art['quantite'];
								}
							}
						}
					}
					$data["ventes"] = $this->get("acmeGroup.facture")->getRepo()->getNbVentesArticle($data['article']);
					return $this->render('LaboTestmanuBundle:pages:statistiquesVentes1article.html.twig', $data);
				}
				break;
			default:
				return $this->render('LaboTestmanuBundle:pages:statistiques.html.twig', $data);
				break;
		}
	}

	/**
	 * statistiquesIpAction
	 * 
	 */
	public function statistiquesIpAction($ip, $dateDebut = null, $dateFin = null) {
		$data = array();
		// contrôle dates et transformation en objet Datetime
		$data = $this->datesFit($dateDebut, $dateFin);
		$data["semaineEnCours"] = $this->getSemaineEnCours();
		$data['listtypes'] = $this->listOptions();
		$data["ip"] = $ip;
		$statistiques = $this->get("acmeGroup.aelog");
		$data["statistiques"] = $statistiques->findByIp($ip, $dateDebut, $dateFin);
		return $this->render('LaboTestmanuBundle:pages:statistiquesIp.html.twig', $data);
	}

	/**
	 * statistiquesPageAction
	 *
	 */
	public function statistiquesPageAction($pageSlug, $dateDebut = null, $dateFin = null) {
		$data = array();
		// contrôle dates et transformation en objet Datetime
		$data = $this->datesFit($dateDebut, $dateFin);
		$data["semaineEnCours"] = $this->getSemaineEnCours();
		$data['listtypes'] = $this->listOptions();
		// Liste des pages disponibles
		$data["pages"] = $this->get("acmeGroup.pageweb")->getRepo()->findAll();
		$data["pageSlug"] = $pageSlug;
		$data["pageNom"] = $pageSlug;
		$p = false;
		foreach($data["pages"] as $pageStat) if($pageSlug === $pageStat->getSlug()) { $data["pageStat"] = $pageStat; }
		if(isset($data["pageStat"])) {
			$data["error"] = false;
			$data["pageNom"] = $data["pageStat"]->getNom();
		} else {
			$data["error"] = "Cette page n'existe pas.";
		}
		return $this->render('LaboTestmanuBundle:pages:statistiquesPages.html.twig', $data);
	}

	/**
	 * statArticlesAction
	 *
	 */
	public function statArticlesAction($articleSlug, $dateDebut = null, $dateFin = null) {
		$data = array();
		// contrôle dates et transformation en objet Datetime
		$data = $this->datesFit($dateDebut, $dateFin);
		$data["semaineEnCours"] = $this->getSemaineEnCours();
		$data['listtypes'] = $this->listOptions();
		$data["listeArticles"] = $this->get("acmeGroup.article")->getRepo()->aeFindAll();
		$data["typedata"] = $data['listtypes'][1];
		$statistiques = $this->get("acmeGroup.aelog");
		$a = $this->get("acmeGroup.article")->getRepo()->findBySlug($articleSlug);
		$data["article"] = $a[0];
		$data["id"] = $data["article"]->getId();
		$s = $statistiques->findArticle($articleSlug, $dateDebut, $dateFin);
		$data["articleStat"] = $statistiques->trieByDate($s, "semaine");
		foreach($data["articleStat"]["data"] as $year => $st1) {
			foreach($st1 as $sem => $st2) {
				$mem = array();
				$data["articleStat"]["unique"][$year][$sem] = 0;
				$data["articleStat"]["totale"][$year][$sem] = count($st2);
				foreach($st2 as $st3) {
					if(!in_array($st3->getIp(), $mem)) {
						$data["articleStat"]["unique"][$year][$sem]++;
						$mem[] = $st3->getIp();
					}
				}
			}
		}
		// Création des graphs
		foreach($data["articleStat"]["data"] as $year => $dd) {
			$data["graphs"][$year] = serialize(array(
				"Vues totales" => $data["articleStat"]["totale"][$year],
				"Vues uniques" => $data["articleStat"]["unique"][$year]
				));
		}
		return $this->render('LaboTestmanuBundle:pages:statistiquesArticles.html.twig', $data);
	}


	public function graphLinePlot($data) {
		$data = unserialize($data);
		include(__DIR__.'/../../../../app/Resources/jpgraph-3.5.0b1/src/jpgraph.php');
		include(__DIR__.'/../../../../app/Resources/jpgraph-3.5.0b1/src/jpgraph_line.php');
		// Creation du graphique
		$graph = new Graph(400,300);
		$graph->SetScale("textlin");

		// foreach($data as $grnom => $grdata) {

		// Création du système de points
		$lineplot1 = new LinePlot($ydata1);
		$lineplot2 = new LinePlot($ydata2);
		
		$graph->img->SetMargin(40,20,20,40);
		$graph->title->Set('Vistes des pages articles');
		$graph->xaxis->title->Set('CA 2000 ');
		$graph->yaxis->title->Set('Paramètre fictif... ');
		
		// Antialiasing
		$graph->img->SetAntiAliasing();
		// On rajoute les points au graphique
		$graph->Add($lineplot1);
		$graph->Add($lineplot2);

		return new Response($graph->Stroke(), 200, array(
			'Content-Type' 			=> 'Content-Type: image/png',
			'Content-Disposition'	=> 'attachment; filename="image.png"'
			));
	}



	  //////////////////////
	 // Méthodes privées //
	//////////////////////

	private function datesFit($dateDebut, $dateFin) {
		if(is_string($dateDebut)) $dateDebut = new \Datetime(date(urldecode($dateDebut)));
		if(is_string($dateFin)) $dateFin = new \Datetime(date(urldecode($dateFin)));
		if($dateFin === null) $dateFin = new \Datetime();
		$data["dateDebut"] = $dateDebut;
		$data["dateFin"] = $dateFin;
		return $data;
	}

	private function listOptions() {
		return array("general", "articles", "ventes", "magasins", "autres");
	}

}
