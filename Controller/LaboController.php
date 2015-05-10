<?php

namespace labo\Bundle\TestmanuBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use labo\Bundle\TestmanuBundle\services\aetools\aeReponse;
// User forms
use AcmeGroup\UserBundle\Form\Type\ProfileFormType;
use AcmeGroup\UserBundle\Form\Type\RegistrationFormType;

class LaboController extends Controller {

	//////////////////////////
	// PAGES
	//////////////////////////

	// Page d'accueil de l'admin (labo)
	public function homeAction() {
		return $this->render('LaboTestmanuBundle:pages:index.html.twig');
	}

	// Page en cours (labo)
	public function workingAction() {
		return $this->render('LaboTestmanuBundle:pages:working.html.twig');
	}

	// Page erreur (labo)
	public function errorAction() {
		return $this->render('LaboTestmanuBundle:pages:error.html.twig');
	}

	/**
	 * Edition des paramètres du site
	 */
	public function editParametresAction($action = "liste", $groupe = "all", $paramSlug = null) {
		$params = $this->get('acmeGroup.parametre');
		if($paramSlug !== null) {
			// recherche du paramètre demandé
			$data["parametre"] = $params->findParams($groupe, $paramSlug);
		} else {

		}

		switch($action) {
			case "edit":
				if($data["parametre"] !== null) {
					//
				} else {
					$this->get('session')->getFlashBag()->add('info', "Ce paramètre <strong>".$paramSlug."</strong> n'a pu être trouvé.");
					return $this->redirect($this->generateUrl('labo_parametre_action', array("action" => "liste", "groupe" => $groupe)));
				}
				break;
			default:
				// liste
				break;
		}
		return $this->render('LaboTestmanuBundle:pages:page-parametres.html.twig');
	}

	/**
	 * imageByTypeAction
	 * @param string/array $type
	 * @return Response
	 */
	public function imageByTypeAction($action = "liste", $classEntite, $element = null) {
		$data = array();
		$types = null;
		$classEntite = urldecode($classEntite);
		$data['URLclassEntite'] = $classEntite;			// richtext@typeRichtext:nom:pageweb:… (à partir du 3ème, on énumère les valeurs, séparées par des ":")
		// sous-catégorie ?
		$exp = explode('@', $classEntite, 2);
		$classEntite = $exp[0];							// image
		if(count($exp) > 1) {
			$deps = explode(":", $exp[1], 3);
			if(count($deps) === 3) {
				$data["souscat"]['attrib'] = $deps[0];	// typeImages
				$champ = $this->metaInfo($classEntite, $data["souscat"]['attrib']);
				$target = explode("\\", $champ['targetEntity']);
				$data["souscat"]['extent'] = $target[count($target) - 1];	// typeImage (sans "s") (targetEntity)
				$data["souscat"]['column'] = $deps[1];	// nom
				$data["souscat"]['values'] = $deps[2];	// diaporama
				$data["souscat"]['url'] = $exp[1];		// typeImages:nom:diaporama
				// récupère les entités à lier (si $action = creation)
				if($action === "creation") {
					if($champ['Association'] === "single") $methodAdd = "set".ucfirst($data["souscat"]['extent']);
					if($champ['Association'] === "collection") $methodAdd = "add".ucfirst($data["souscat"]['extent']);
					$types = $this->get('acmeGroup.entities')->defineEntity($data["souscat"]['extent'])->getRepo()->findByAttrib($data["souscat"]['column'], explode(":", $data["souscat"]['values']));
				}
			} else {
				$data["souscat"] = null;
			}
		} else {
			$data["souscat"] = null;
		}
		// echo("Entite : ".$classEntite."<br />");
		$data['entite'] = $this->get('acmeGroup.entities')->defineEntity($classEntite);
		$data['metaInfo'] = $data['entite']->compileMetaInfo($classEntite);

		$data['action'] = $action;
		$data['classEntite'] = $data["entite"]->getClassEntite();	// nom long de l'entité
		$data['entiteName'] = $data["entite"]->getEntiteName();		// nom court de l'entité
		$data['element'] = $element;
		if($element !== null) {
			$obj = $data['entite']->getById($element);
			if(!is_object($obj)) {
				$data['action'] = "liste";
				$this->get('session')->getFlashBag()->add('error', "L'élément n'a pas pu être trouvé.");
			}
		}

		switch($action) {
			case 'edit':
				$formType = $data['entite']->getFormNameEntite();
				$form = $this->createForm(new $formType($this), $obj);
				$request = $this->get('request');
				if($request->getMethod() == "POST") {
					$form->bind($request);
					if($form->isValid()) {
						// $em = $this->getDoctrine()->getManager();
						// $data['entite']->getEm()->persist($obj);
						$data['entite']->getEm()->flush();
						return $this->redirect($this->generateUrl('labo_page_imageByType', array("action" => 'liste', "classEntite" => urlencode($data['URLclassEntite']))));
					}
				}
				$data["form"] = $form->createView();
				break;
			case 'creation':
				$obj = $data['entite']->newObject(true);
				// ajout des types par défaut
				if($types !== null) foreach($types as $type) $obj->$methodAdd($type);
				$formType = $data['entite']->getFormNameEntite();
				$form = $this->createForm(new $formType($this), $obj);
				$request = $this->get('request');
				if($request->getMethod() == "POST") {
					$form->bind($request);
					if($form->isValid()) {
						// $em = $this->getDoctrine()->getManager();
						$data['entite']->getEm()->persist($obj);
						$data['entite']->getEm()->flush();
						return $this->redirect($this->generateUrl('labo_page_imageByType', array("action" => 'liste', "classEntite" => urlencode($data['URLclassEntite']))));
					}
				}
				$data["form"] = $form->createView();
				break;
			case 'supprime':
				if(is_object($obj)) {
					$data['entite']->getEm()->remove($obj);
					$data['entite']->getEm()->flush();
					// supprime les fichiers images partout sur le serveur
					$this->get('acmeGroup.imagetools')->unlinkEverywhereImage($obj->getFichierNom());
					if(method_exists($obj, "getNom")) $nomobj = $obj->getNom();
						else if(method_exists($obj, "getSlug")) $nomobj = $obj->getNom();
						else $nomobj = $obj->getId();
					$this->get('session')->getFlashBag()->add('info', "L'image ".$nomobj." a été supprimée.");
				} else {
					$this->get('session')->getFlashBag()->add('error', "L'image ".$element." n'existe pas. Elle n'a pu être supprimée.");
				}
				return $this->redirect($this->generateUrl('labo_page_imageByType', array("action" => 'liste', "classEntite" => urlencode($data['URLclassEntite']))));
				break;
			case 'supprime-admin':
				if(is_object($obj)) {
					$data['entite']->getEm()->remove($obj);
					$data['entite']->getEm()->flush();
					// supprime les fichiers images partout sur le serveur
					$this->get('acmeGroup.imagetools')->unlinkEverywhereImage($obj->getFichierNom());
					if(method_exists($obj, "getNom")) $nomobj = $obj->getNom();
						else if(method_exists($obj, "getSlug")) $nomobj = $obj->getNom();
						else $nomobj = $obj->getId();
					$this->get('session')->getFlashBag()->add('info', "L'image ".$nomobj." a été supprimée.");
				} else {
					$this->get('session')->getFlashBag()->add('error', "L'image ".$element." n'existe pas. Elle n'a pu être supprimée.");
				}
				return $this->redirect($this->generateUrl('labo_page_imageByType', array("action" => 'liste', "classEntite" => urlencode($data['URLclassEntite']))));
				break;
			default: // liste
				break;
		}

		$data["pag"] = $this->getPaginationQuery();
		$data["dataEntite"] = $data["entite"]->getRepo()->findElementsPagination($data["pag"], $data["souscat"]);
		$data["pag"]["nbtot"] = count($data["dataEntite"]);
		$data["pag"]["nbpage"] = ceil($data["pag"]["nbtot"] / $data["pag"]["lignes"]);

		return $this->render('LaboTestmanuBundle:pages:page-image.html.twig', $data);
		// return $this->render('LaboTestmanuBundle:pages:index.html.twig', $data);
	}

	public function UserAction($action = "liste", $role = 'tous-roles', $element = 'all', $changeRole = null) {
		$data = array();
		$data['action'] = $action;
		$data['role'] = $role;
		$data['changeRole'] = $changeRole;
		if($element === "all") $element = null;
		$data['element'] = $element;
		$data['entiteName'] = "User";
		$data['classEntite'] = "User";
		$data['user_manager'] = $this->get('fos_user.user_manager');
		$data['entite'] = $this->get('acmeGroup.entities')->defineEntity($data['classEntite']);
		$data['metaInfo'] = $data['entite']->compileMetaInfo($data['classEntite']);
		if($element !== null) {
			$obj = $data['user_manager']->findUserBy(array("id" => $element));
			if(!is_object($obj)) {
				$data['action'] = "liste";
				$this->get('session')->getFlashBag()->add('error', "L'utilisateur n'a pas pu être trouvé.");
			}
		}
		$repo = $this->getDoctrine()->getManager()->getRepository('AcmeGroupUserBundle:User');

		switch($action) {
			case 'edit':
				$formType = $data['entite']->getFormNameEntite();
				$form = $this->createForm(new RegistrationFormType(), $obj);
				$request = $this->get('request');
				if($request->getMethod() == "POST") {
					$form->bind($request);
					if($form->isValid()) {
						// $em = $this->getDoctrine()->getManager();
						$data['entite']->getEm()->persist($obj);
						$data['entite']->getEm()->flush();
						return $this->redirect($this->generateUrl('labo_page_User', array("action" => 'liste', "role" => $data['role'])));
					}
				}
				$data["form"] = $form->createView();
				break;
			case 'creation':
				$form = $this->createForm(new ProfileFormType(), $obj);
				$request = $this->get('request');
				if($request->getMethod() == "POST") {
					$form->bind($request);
					if($form->isValid()) {
						// $em = $this->getDoctrine()->getManager();
						$data['entite']->getEm()->persist($obj);
						$data['entite']->getEm()->flush();
						return $this->redirect($this->generateUrl('labo_page_User', array("action" => 'liste', "role" => $data['role'])));
					}
				}
				$data["form"] = $form->createView();
				break;
			case 'supprime':
				if(is_object($obj)) {
					$nomobj = $obj->getUsername();
					$data['user_manager']->deleteUser($obj);
					$this->get('session')->getFlashBag()->add('info', "L'utilisateur ".$nomobj." a été supprimé.");
				}
				return $this->redirect($this->generateUrl('labo_page_User', array("action" => 'liste', "role" => $data['role'])));
				break;
			case 'supprime-admin':
				if(is_object($obj)) {
					$nomobj = $obj->getUsername();
					$data['user_manager']->deleteUser($obj);
					$this->get('session')->getFlashBag()->add('info', "L'utilisateur ".$nomobj." a été supprimé.");
				}
				return $this->redirect($this->generateUrl('labo_page_User', array("action" => 'liste', "role" => $data['role'])));
				break;
			case 'change-roles':
				$oldRoles = $obj->getRoles();
				foreach ($oldRoles as $rol) $obj->removeRole($rol);
				$obj->addRole($data['changeRole']);
				$data['entite']->getEm()->persist($obj);
				$data['entite']->getEm()->flush();
				$this->get('session')->getFlashBag()->add('info', "L'utilisateur ".$obj->getUsername()." a été changé en ".$data['changeRole'].".");
				break;
			default: // liste
				break;
		}

		$data["pag"] = $this->getPaginationQuery();
		$data["dataEntite"] = $repo->findUserPagination($data['role'], $data["pag"]['page'], $data["pag"]["lignes"], $data["pag"]["ordre"], $data["pag"]["sens"], $data["pag"]["searchString"], $data["pag"]["searchField"], $element);
		$data["pag"]["nbtot"] = count($data["dataEntite"]);
		$data["pag"]["nbpage"] = ceil($data["pag"]["nbtot"] / $data["pag"]["lignes"]);

		if($data['action'] === 'change-roles') $data['action'] = 'liste';
		return $this->render('LaboTestmanuBundle:pages:page-User.html.twig', $data);
	}

	// Page de gestion entite
	public function entiteAction($action = "liste", $classEntite, $element = null) {
		$data = array();
		$types = null;
		$classEntite = urldecode($classEntite);
		$data['URLclassEntite'] = $classEntite;			// richtext@typeRichtext:nom:pageweb:… (à partir du 3ème, on énumère les valeurs, séparées par des ":")
		// sous-catégorie ?
		$exp = explode('@', $classEntite, 2);
		$classEntite = $exp[0];							// richtext
		if(count($exp) > 1) {
			$deps = explode(":", $exp[1], 3);
			if(count($deps) === 3) {
				$data["souscat"]['attrib'] = $deps[0];	// typeImages
				$champ = $this->metaInfo($classEntite, $data["souscat"]['attrib']);
				$target = explode("\\", $champ['targetEntity']);
				$data["souscat"]['extent'] = $target[count($target) - 1];	// typeImage (sans "s") (targetEntity)
				$data["souscat"]['column'] = $deps[1];	// nom
				$data["souscat"]['values'] = $deps[2];	// diaporama
				$data["souscat"]['url'] = $exp[1];		// typeImages:nom:diaporama
				// récupère les entités à lier (si $action = creation)
				if($action === "creation") {
					if($champ['Association'] === "single") $methodAdd = "set".ucfirst($data["souscat"]['extent']);
					if($champ['Association'] === "collection") $methodAdd = "add".ucfirst($data["souscat"]['extent']);
					$types = $this->get('acmeGroup.entities')->defineEntity($data["souscat"]['extent'])->getRepo()->findByAttrib($data["souscat"]['column'], explode(":", $data["souscat"]['values']));
				}
			} else {
				$data["souscat"] = null;
			}
		} else {
			$data["souscat"] = null;
		}
		// echo("Entite : ".$classEntite."<br />");
		$data['entite'] = $this->get('acmeGroup.entities')->defineEntity($classEntite);
		$data['metaInfo'] = $data['entite']->compileMetaInfo($classEntite);

		$data['action'] = $action;
		$data['classEntite'] = $data["entite"]->getClassEntite();	// nom long de l'entité
		$data['entiteName'] = $data["entite"]->getEntiteName();		// nom court de l'entité
		$data['element'] = $element;
		if($element !== null) {
			$obj = $data['entite']->getById($element);
			if(!is_object($obj)) {
				$data['action'] = "liste";
				$this->get('session')->getFlashBag()->add('error', "L'élément n'a pas pu être trouvé.");
			}
		}

		switch($action) {
			case 'edit':
				$formType = $data['entite']->getFormNameEntite();
				$form = $this->createForm(new $formType($this), $obj);
				$request = $this->get('request');
				if($request->getMethod() == "POST") {
					$form->bind($request);
					if($form->isValid()) {
						// $em = $this->getDoctrine()->getManager();
						$data['entite']->getEm()->persist($obj);
						$data['entite']->getEm()->flush();
						return $this->redirect($this->generateUrl('labo_page_entite', array("action" => 'liste', "classEntite" => urlencode($data['URLclassEntite']))));
					}
				}
				$data["form"] = $form->createView();
				break;
			case 'creation':
				$obj = $data['entite']->newObject(true);
				// ajout des types par défaut
				if($types !== null) foreach($types as $type) $obj->$methodAdd($type);
				$formType = $data['entite']->getFormNameEntite();
				$form = $this->createForm(new $formType($this), $obj);
				$request = $this->get('request');
				if($request->getMethod() == "POST") {
					$form->bind($request);
					if($form->isValid()) {
						// $em = $this->getDoctrine()->getManager();
						$data['entite']->getEm()->persist($obj);
						$data['entite']->getEm()->flush();
						return $this->redirect($this->generateUrl('labo_page_entite', array("action" => 'liste', "classEntite" => urlencode($data['URLclassEntite']))));
					}
				}
				$data["form"] = $form->createView();
				break;
			case 'supprime':
				if(is_object($obj)) {
					$data['entite']->getEm()->remove($obj);
					$data['entite']->getEm()->flush();
					if(method_exists($obj, "getNom")) $nomobj = $obj->getNom();
						else if(method_exists($obj, "getSlug")) $nomobj = $obj->getNom();
						else $nomobj = $obj->getId();
					$this->get('session')->getFlashBag()->add('info', "La ligne ".$nomobj." a été supprimée.");
				} else {
					$this->get('session')->getFlashBag()->add('error', "La ligne ".$element." n'existe pas. Elle n'a pu être supprimée.");
				}
				return $this->redirect($this->generateUrl('labo_page_entite', array("action" => 'liste', "classEntite" => urlencode($data['URLclassEntite']))));
				break;
			case 'supprime-admin':
				$obj = $data['entite']->getById($element);
				if(is_object($obj)) {
					$data['entite']->getEm()->remove($obj);
					$data['entite']->getEm()->flush();
					if(method_exists($obj, "getNom")) $nomobj = $obj->getNom();
						else if(method_exists($obj, "getSlug")) $nomobj = $obj->getNom();
						else $nomobj = $obj->getId();
					$this->get('session')->getFlashBag()->add('info', "La ligne ".$nomobj." a été supprimée.");
				} else {
					$this->get('session')->getFlashBag()->add('error', "La ligne ".$element." n'existe pas. Elle n'a pu être supprimée.");
				}
				return $this->redirect($this->generateUrl('labo_page_entite', array("action" => 'liste', "classEntite" => urlencode($data['URLclassEntite']))));
				break;
			default: // liste
				break;
		}
		// if($data["souscat"] === null) {
		// 	$trt = $data["entite"]->getRepo()->findByNom($element);
		// 	if(count($trt) > 0) $data["typeRichtext"] = $trt[0]->getDescriptif();
		// 		else $data["typeRichtext"] = null;
		// }

		$data["pag"] = $this->getPaginationQuery();
		$data["dataEntite"] = $data["entite"]->getRepo()->findElementsPagination($data["pag"], $data["souscat"]);
		$data["pag"]["nbtot"] = count($data["dataEntite"]);
		$data["pag"]["nbpage"] = ceil($data["pag"]["nbtot"] / $data["pag"]["lignes"]);

		return $this->render('LaboTestmanuBundle:pages:page-entite.html.twig', $data);
	}

	public function noteEntiteAction($classEntite, $id = null) {
		$classEntite = urldecode($classEntite);
		$data['entite'] = $this->get('acmeGroup.entities')->defineEntity($classEntite);
		// $data['metaInfo'] = $data['entite']->compileMetaInfo($classEntite);

		$data['classEntite'] = $classEntite;
		$data['entiteName'] = $data["entite"]->getEntiteName();
		$data['bundleNameEntiteName'] = $data["entite"]->getBundleNameEntiteName();
		// $data['bundleEntiteName'] = $data["entite"]->getEntiteName();

		$enRepo = $this->getDoctrine()->getManager()->getRepository("AcmeGroup\\LaboBundle\\Entity\\".$data['entiteName']);
		$version = $this->get("session")->get('version');
		$enRepo->setVersion($version["nom"], $version["shutdown"]);
		if($id === null) {
			// pas d'élément désigné = page générale
			$data["page"] = "generale";
			$data["pag"] = $this->getPaginationQuery($classEntite);
			$data["dataEntite"] = $data["entite"]->getRepo()->findElementsPagination($data["pag"]);
			$data["pag"]["nbtot"] = count($data["dataEntite"]);
			$data["pag"]["nbpage"] = ceil($data["pag"]["nbtot"] / $data["pag"]["lignes"]);
			$data['action'] = "détail";
		} else {
			// id désingé = recherche de l'élément + page d'info notes sur l'élément
			$data["page"] = "detail";
			$data['action'] = "liste";
		}

		return $this->render('LaboTestmanuBundle:pages:page-note-'.$data["entiteName"].'.html.twig', $data);
	}

	public function VenteArticleAction($type = "all") {
		$data["entite"] = "facture";
		$data["comefrom"] = $type;
		switch ($type) {
			case 'erreur': // articles à expédier
				$data["factures"] = $this->get("AcmeGroup.facture")->getVentesErreur();
				break;
			case 'commande': // articles à expédier
				$data["factures"] = $this->get("AcmeGroup.facture")->getVentesCommande();
				break;
			case 'livraison': // articles expédiés
				$data["factures"] = $this->get("AcmeGroup.facture")->getVentesLivraison();
				break;
			case 'termine': // articles expédiés (vente terminée)
				$data["factures"] = $this->get("AcmeGroup.facture")->getVentesTermine();
				break;
			case 'annule': // articles annlés (vente annulée)
				$data["factures"] = $this->get("AcmeGroup.facture")->getVentesAnnule();
				break;
			default: // all
				$data["factures"] = $this->get("AcmeGroup.facture")->getRepo()->findAll();
				break;
		}
		return $this->render('LaboTestmanuBundle:pages:factures.html.twig', $data);
	}

	public function getCSVventeAction($type = 'all') {
		switch ($type) {
			case 'erreur': // articles à expédier
				$factures = $this->get("AcmeGroup.facture")->getVentesErreur();
				break;
			case 'commande': // articles à expédier
				$factures = $this->get("AcmeGroup.facture")->getVentesCommande();
				break;
			case 'livraison': // articles expédiés
				$factures = $this->get("AcmeGroup.facture")->getVentesLivraison();
				break;
			case 'termine': // articles expédiés (vente terminée)
				$factures = $this->get("AcmeGroup.facture")->getVentesTermine();
				break;
			case 'annule': // articles annlés (vente annulée)
				$factures = $this->get("AcmeGroup.facture")->getVentesAnnule();
				break;
			default: // all
				$factures = $this->get("AcmeGroup.facture")->getRepo()->findAll();
				break;
		}
		$i = 0;
		foreach($factures as $facture) {
			$data[$i]["reference"] = $facture->getReference();
			$data[$i]["mode de livraison"] = $facture->getLivraison();
			$data[$i]["Prix"] = $facture->getPrixtotal();
			$magasin = $facture->getPropUser()->getMagasin();
			if($data[$i]["mode de livraison"] == "poste") {
				$data[$i]["nom"] = $facture->getNom();
				$data[$i]["prénom"] = $facture->getPrenom();
				$data[$i]["adresse"] = $facture->getAdresse();
				$data[$i]["cp"] = $facture->getCp();
				$data[$i]["ville"] = $facture->getVille();
				$data[$i]["telephone"] = $facture->getTel();
				$data[$i]["email"] = $facture->getEmail();
			} else {
				$data[$i]["nom"] = $magasin->getNommagasin();
				$data[$i]["responsable"] = $magasin->getResponsable();
				$data[$i]["adresse"] = $magasin->getAdresse();
				$data[$i]["cp"] = $magasin->getCp();
				$data[$i]["ville"] = $magasin->getVille();
				$data[$i]["telephone"] = $magasin->getTelephone();
				$data[$i]["email"] = $magasin->getEmail();
			}
			if(is_object($magasin)) {
				if(trim($magasin->getNommagasin()."") !== "") $nomag = "(".$magasin->getId().") ".$magasin->getNommagasin()." / ".$magasin->getCp()." / ".$magasin->getVille();
					else $nomag = "(sans nom / id = ".$magasin->getId().")";
			} else {
				$nomag = "(aucun)";
			}
			$data[$i]["concessionnaire référent"] = $nomag;
			$data[$i]["statut"] = $facture->getStadeTxt();
			$j = 0;
			foreach($facture->getDetailbyarticle() as $id => $article) {
				$data[$i]["article_".$id] = $article["nom"]." = ".$article["quantite"]." x ".$article["prix"];
				$j++;
			}
			$i++;
		}

		$dd = array();
		$handle = fopen('php://memory', 'r+');
		foreach($data[0] as $nom => $d) {
			if(substr($nom, 0, 8) === "article_") $dd[] = "liste des articles";
				else $dd[] = $nom;
		}
		fputcsv($handle, $dd);
		foreach($data as $d) fputcsv($handle, $d);
		rewind($handle);
		$content = stream_get_contents($handle);
		fclose($handle);

		$date = new \Datetime();
		return new Response($content, 200, array(
			'Content-Type' => 'application/force-download',
			'Content-Disposition' => 'attachment; filename="Ventes-'.$type.'-'.$date->format("Y_m_d").'.csv"'
			));
	}

	public function VenteActionAction($id, $action, $comefrom = null) {
		$data["entite"] = "facture";
		$data["comefrom"] = $comefrom;
		switch ($action) {
			case 'retablir': // rétablir une commande annulée
				$facture = $this->get("AcmeGroup.facture");
				$fac = $facture->getRepo()->find($id);
				$fac->setStade(0);
				$facture->getEm()->flush();
				return $this->VenteArticleAction($comefrom);
				break;
			case 'envoyer': // articles à expédier
			case 'envoyer-mail': // articles à expédier
				$facture = $this->get("AcmeGroup.facture");
				$fac = $facture->getRepo()->find($id);
				$fac->setStade(1);
				$facture->getEm()->flush();
				// envoi mail au client
				if($action === 'envoyer-mail') {
					$this->emailLivraisonClient($fac->getPropUser()->getEmail(), $fac);
					// $this->emailLivraisonClient("manu7772@gmail.com", $fac);
					$this->get('session')->getFlashBag()->add('info', 'Un mail a été envoyé à '.$fac->getPropUser()->getUsername().' pour l\'envoi de sa commande.');
				}
				return $this->VenteArticleAction($comefrom);
				break;
			case 'terminer': // articles expédiés (vente terminée)
				$facture = $this->get("AcmeGroup.facture");
				$fac = $facture->getRepo()->find($id);
				$fac->setStade(2);
				$facture->getEm()->flush();
				return $this->VenteArticleAction($comefrom);
				break;
			case 'annuler': // articles expédiés (vente terminée)
				$facture = $this->get("AcmeGroup.facture");
				$fac = $facture->getRepo()->find($id);
				$fac->setStade(3);
				$facture->getEm()->flush();
				return $this->VenteArticleAction($comefrom);
				break;
			default: // detail
				$data["facture"] = $this->get("AcmeGroup.facture")->getRepo()->find($id);
				return $this->render('LaboTestmanuBundle:pages:factureDetail.html.twig', $data);
				break;
		}
	}

	private function emailLivraisonClient($usermail, $facture) {
		$templating = $this->get('templating');
		$contenu = $templating->render("AcmeGroupSiteBundle:Sherlocks:mail-modele002.html.twig", array("facture" => $facture));
		// mail
		if(is_object($facture)) {
			$mailer = $this->get('mailer');
			$message = \Swift_Message::newInstance()
				->setSubject($facture->getNom()." : mise en livraison de votre commande")
				->setContentType('text/html')
				->setFrom('noreply@singerfrance.com')
				->setTo($usermail)
				->setBody($contenu);
			$rmail = $mailer->send($message);
		}
	}

	// Gestion de la base de données : check et débuggage
	public function checkingAction($entite) {
		$data["entite"] = $entite;
		switch($entite) {
			case "article":
				$data["check"] = $this->get("AcmeGroup.article")->check();
				break;
			case "facture":
				$data["check"] = $this->get("AcmeGroup.facture")->check();
				break;
			case "magasin":
				$data["check"] = $this->get("AcmeGroup.magasin")->check();
				break;
			case "ficheCreative":
				$data["check"] = $this->get("AcmeGroup.atelierCreatif")->check();
				break;
			case "video":
				$data["check"] = $this->get("AcmeGroup.video")->check();
				break;
			case "evenement":
				$data["check"] = $this->get("AcmeGroup.events")->check();
				break;
			case "User":
				// USER !!!!!!!
				// $data["check"] = new aeReponse(2, array(), "Check User pas encore programmé…");
				$data["check"] = $this->checkUsers();
				// $data["check"] = $this->get("AcmeGroup.events")->check();
				break;
			default:
				$data["check"] = new aeReponse(false, null, "Entité non reconnue (".$entite.")<br />L'opération de checking n'a pu avoir lieu.");
				break;
		}
		return $this->render('LaboTestmanuBundle:pages:checking-data.html.twig', $data);
	}

	public function generateRandomDataAction($entite) {
		$data["entite"] = $entite;
		switch($entite) {
			case "facture":
				// TEST : création aléatoire d'une facture d'après le panier actuel
				$this->createRandomFacture(12);
				break;
		}
		return $this->homeAction();
	}

	// Page d'affichage des routes
	public function showRoutesAction($motif) {
		$data["routes"] = $this->get("acmeGroup.aetools")->getAllRoutes($motif);
		$data["motif"] = $motif;
		return $this->render('LaboTestmanuBundle:pages:show-routes.html.twig', $data);
	}

	/**
	 * Page d'affichage des info sur la base de données
	 * sur la base si $classEntite == null
	 * sinon, sur l'entité $classEntite
	 */
	public function showDatabaseAction($classEntite = null) {
		$data = array();
		if($classEntite !== null) {
			$classEntite = urldecode($classEntite);
			$data["database"] = $this->metaInfo($classEntite);
			$data["show"] = "entite";
		} else {
			$data["database"] = $this->databaseInfo();
			$data["show"] = "database";
		}
		$data['dbname'] = $this->container->getParameter('database_name');
		return $this->render('LaboTestmanuBundle:pages:show-database.html.twig', $data);
	}

	/**
	 * showFieldAction
	 * Page d'affichage des infos sur un champs d'une entité
	 *
	 */
	public function showFieldAction($classEntite, $champ) {
		$data = array();
		$classEntite = urldecode($classEntite);
		$data["show"] = "champ";
		$data["database"] = $this->metaInfo($classEntite, $champ);
		$data['entiteName'] = $classEntite;

		// $data["database"] = $this->showField($entite, $field);
		$data["champ"] = $champ;
		$data['dbname'] = $this->container->getParameter('database_name');
		return $this->render('LaboTestmanuBundle:pages:show-database.html.twig', $data);
	}

	/**
	 * richtextLinksAction
	 * Calcule et enregistre les liens stockés dans les textes
	 *
	 */
	public function richtextLinksAction($id = null) {
		$txtools = $this->get("acmeGroup.texttools");
		$txt = array();
		switch ($id) {
			case null:
				# rien…
				break;
			case 0:
				$txt = $txtools->getAllTexts();
				break;
			default:
				$txt[0] = $txtools->getTextById($id);
				break;
		}
		if($id !== null) {
			$em = $this->getDoctrine()->getManager();
			foreach($txt as $t) {
				$convertext = $t->getTexte();
				$t->setTexte($this->convertext($this->get("twig"), array(), $convertext));
				$t->setTwigConverti(true);
				$em->persist($t);
				$em->flush();
			}
		}
		$data["richtexts"] = $txtools->getAllTexts();
		return $this->render('LaboTestmanuBundle:pages:prod-richtextLinks.html.twig', $data);
	}


	/**
	 * imagesVersionAction
	 * Ajoute les images d'entête de la version (pour l'instant impossible par fixtures)
	 */
	public function imagesVersionAction() {
		$em = $this->getDoctrine()->getManager();
		$repoImg = $em->getRepository("AcmeGroup\\LaboBundle\\Entity\\image");
		$repoVer = $em->getRepository("AcmeGroup\\LaboBundle\\Entity\\version");
		$imnoms = array("Singer" => "Logo Singer entête", "Singer-V2" => "Logo Singer.v2 entête", "DemoSinger" => "Logo Singer Démo");
		$cpt = 0;$echec = 0;
		foreach($imnoms as $ver => $img) {
			$i = $repoImg->findByNom($img);
			$v = $repoVer->findByNom($ver);
			if(is_object($v[0]) && is_object($i[0])) {
				$v[0]->setImageEntete($i[0]);
				$em->persist($v[0]);
				$cpt++;
			} else $echec++;
		}
		$em->flush();
		$this->get('session')->getFlashBag()->add("info", $cpt." images ajoutées / ".$echec." échec");
		$Tidx = $this->get("session")->get('version');
		return $this->redirect($this->generateUrl("acme_site_home", array("versionDefine" => $Tidx["slug"])));
	}

	//////////////////////////
	// MENUS
	//////////////////////////

	public function leftSideMenuAction() {
		return $this->render('LaboTestmanuBundle:menu:left-side-menu.html.twig');
	}

	public function navbarAction($pageweb = null) {
		if($pageweb !== null) {
			$data["pageweb"] = $this->container->get('acmeGroup.pageweb')->getDynPages($pageweb);
		} else {
			$data["pageweb"] = null;
		}
		$data['entity']['typeRichtext'] = $this->get('acmeGroup.texttools')->typeRichtextList();
		$data['entity']['typeEvenement'] = $this->get('acmeGroup.entities')->defineEntity('typeEvenement')->getRepo()->findAll();
		return $this->render(':common:navbar.html.twig', $data);
	}


	//////////////////////////
	// BLOCS AJAXUPDATE
	//////////////////////////

	public function listeEntiteLigneAction($classEntite, $id) {
		$classEntite = urldecode($classEntite);
		$data['entite'] = $this->get('acmeGroup.entities')->defineEntity($classEntite);

		// $data["ligne"] = $this->getDoctrine()->getManager()->getRepository($data['entite']->getRepoNameEntite())->find($id);
		$data["ligne"] = $data['entite']->getById($id);

		$rep['result'] = true;
		$rep['html'] = $this->renderView('LaboTestmanuBundle:bloc:liste'.ucfirst($data["entite"]->getEntiteName()).'Ligne.html.twig', $data);
		return new JsonResponse($rep);
	}


	//////////////////////////
	// Actions sur entités
	//////////////////////////

	public function actionEntiteAction($action, $entite, $id) {
		$ent = $this->get('acmeGroup.entities');
		$ent->defineEntity($entite); // --> attention nom de classe entier
		$result = $ent->actionById($action, $id);
		if($result->getResult() === true) {
			// Succès
			// $this->alertMessage($result->getMessage());
		} else {
			// Erreur
			$this->alertMessage("Erreur : ".$result->getMessage());
		}
		return new Response($result); // pas JSON !!!
	}

	//////////////////////////
	// Actions AJAX
	//////////////////////////

	/**
	 * saveEntiteAction
	 * Enregistre un texte dans une entité existante / préciser le champ $champ
	 * si le slug du champ n'existe pas, la ligne est ignorée : result = true, mais nb = 0 et txt est vide
	 * 
	 * @param string $entiteNom // --> ATTENTION : nom de classe entier
	 * @param string $id
	 * @param string $champ
	 * @return Json
	 * 		--> result : boolean
	 * 		--> data : array des textes persistés
	 */
	public function saveEntiteAction($entiteNom, $id, $champ) {
		$data = $this->get('acmeGroup.entities');
		$data->defineEntity($entiteNom);
		return new JsonResponse(json_encode(
			$data->saveData($id, array($champ => $this->getRequest()->request->get('data')))
			// array("result" => true, "data" => array("Réponse du serveur."))
			));
	}

	/**
	 * getEntiteAction
	 * Renvoie les données du $champ concernant l'entité $entiteNom d'id = $id
	 * si $short est précisé, renvoie le texte raccourci à $short caractères
	 * 
	 * @param string $entiteNom // --> ATTENTION : nom de classe entier
	 * @param string $id
	 * @param string $champ
	 * @param string $short --> récupérer le nom raccourci à $hort caractères
	 * @return Json
	 * 		--> result : boolean
	 * 		--> data : array des textes persistés
	 */
	public function getEntiteAction($entiteNom, $id, $champ, $short = null) {
		$data = $this->get('acmeGroup.entities');
		$data->defineEntity($entiteNom);
		return new JsonResponse(json_encode(
			$data->getChampById($id, $champ, $short)
			));
	}

	/**
	 * getHtmlEntiteAction
	 * Renvoie les données du $champ concernant l'entité $entiteNom d'id = $id --> en HTML directement !!
	 * si $short est précisé, renvoie le texte raccourci à $short caractères
	 * 
	 * @param string $entiteNom // --> ATTENTION : nom de classe entier
	 * @param string $id
	 * @param string $champ
	 * @param string $short --> récupérer le nom raccourci à $hort caractères
	 * @return Json
	 * 		--> result : boolean
	 * 		--> data : array des textes persistés
	 */
	public function getHtmlEntiteAction($entiteNom, $id, $champ, $short = null) {
		$data = $this->get('acmeGroup.entities');
		$data->defineEntity($entiteNom);
		$r = $data->getChampById($id, $champ, $short);
		$html = $r["data"][0];
		return new Response($html);
	}


	//////////////////////////
	// CHECK USERS
	//////////////////////////

	/**
	 * checkUsers
	 *
	 * @return aeReponse
	 */
	private function checkUsers() {
		return new aeReponse(2, array(), "Check User pas encore programmé…");
	}

	//////////////////////////
	// Autres fonctions
	//////////////////////////

	private function convertext(\Twig_Environment $environment, $context, $string) {
        $tempEnv = $environment;
        // backup the original loader
        $originalLoader = $tempEnv->getLoader();
        // set temp loader
        $arrayLoader = new \Twig_Loader_Array(array(
            $string => $string,
        ));
        $stringLoader = new \Twig_Loader_String();
        $chainLoader = new \Twig_Loader_Chain(array(
            $originalLoader,
            $arrayLoader,
            $stringLoader,
        ));
        $tempEnv->setLoader($chainLoader);
        // render
        $parsed = $tempEnv->render(html_entity_decode($string), $context);
        // reset original loaders
        $tempEnv->setLoader($originalLoader);
        return str_replace("app_dev.php/", "", $parsed);
    }

	private function alertMessage($message) {
			?><script>window.top.window.alert("<?php echo($message) ?>");</script><?php
	}

	private function showField($entite, $field) {
		$entityTools = $this->get('acmeGroup.entities')->defineEntity($entite);
		$r = $entityTools->getMetaInfoField($entityTools->newObject(), $field);
		return $r;
	}



	
	private function databaseInfo() {
		$serviceEntite = $this->get('acmeGroup.entities');
		return $serviceEntite->getAllEntites();
	}

	private function metaInfo($classEntite, $champ = null) {
		$serviceEntite = $this->get('acmeGroup.entities');
		$serviceEntite->defineEntity($classEntite);
		if($champ === null) $metaInfo = $serviceEntite->getMetaInfo($serviceEntite->newObject());
			else $metaInfo = $serviceEntite->getMetaInfoField($serviceEntite->newObject(), $champ);
		// var_dump($metaInfo);
		return $metaInfo;
	}


	private function getPaginationQuery() {
		$getMtd = $this->getRequest()->query; // GET
		$r["page"] = $getMtd->get('page');
		$r["lignes"] = $getMtd->get('lignes');
		$r["ordre"] = $getMtd->get('ordre');
		$r["sens"] = $getMtd->get('sens');
		$r["searchString"] = $getMtd->get('searchString');
		$r["searchField"] = $getMtd->get('searchField');

		if($r["lignes"] == null) $r["lignes"] = 20;
		if($r["page"] == null) $r["page"] = 1;
		if($r["page"] < 1) $r["page"] = 1;

		return $r;
	}



	private function createRandomFacture($nombre) {
		if($this->get('security.context')->isGranted('ROLE_SUPER_ADMIN') ) {
			$panier = $this->get('acmeGroup.panier');
			$facture = $this->get('acmeGroup.facture');
			$article = $this->get('acmeGroup.article');
			// version
			$Tidx = $this->get("session")->get('version');
			$versObj = $this->getDoctrine()->getManager()->getRepository('AcmeGroup\\LaboBundle\\Entity\\version')->find($Tidx["id"]);
			// user
			$user = $this->get('security.context')->getToken()->getUser();
			// statut
			$statut = $this->getDoctrine()->getManager()->getRepository('AcmeGroup\\LaboBundle\\Entity\\statut')->findByNom("Test");
			for($i = 1; $i <= $nombre; $i++) {
				$articles = $article->getArticlesByReseau("e-commerce");
				$nba = rand(1,10);if($nba > 3) $nba = 1;
				for($j = 1; $j <= $nba; $j++) {
					$Q = rand(1,6);if($Q > 2) $Q = 1;
					$panier->ajouteArticle($articles[rand(0, count($articles) - 1)], $user, $Q);
				}
				// référence
				$data["reference"] = "test-".rand(100000, 999999);
				$facture->createNewByPanier($user, $versObj, $panier, $data, $statut[0]);

				$panier->videPanier();
			}
		}
	}


}
