<?php
// labo/Bundle/TestmanuBundle/services/aetools/XMLparser.php

namespace labo\Bundle\TestmanuBundle\services\aetools;

class XMLparser {

	public $fXML; //nom du document XML
	public $XMLdata;
	public $XMLrech;

	private $parseMode = false;	// Mode de renvoie : objet (false) ou array (true)

	### Construction de classe
	### $fXML = fichier xml
	### $recherche = chaîne de recherche des données XML
	public function __construct($fXML=null, $recherche=null) {
		if($fXML && file_exists($fXML)) {
			$this->fXML = $fXML;
			$this->loadSXD($this->fXML, $recherche);
		}
	}

	public function setParseMode($mode) {
		if($mode === false || $mode === true) $this->parseMode = $mode;
	}
	
	### Charge le fichier $fXML
	### le filtre $recherche permet de ne renvoyer que les résultats voulus
	public function loadSXD($fXML, $recherche=null) {
		$this->XMLdata = @simplexml_load_file($fXML);
		if(is_object($this->XMLdata)) {
			if($this->parseMode) {
				if(!is_null($recherche)) return $this->parseX($this->XMLdata->xpath($recherche));
					else return $this->parseX($this->XMLdata);
			} else {
				if(!is_null($recherche)) return $this->XMLdata->xpath($recherche);
					else return $this->XMLdata;
			}
		}
	}

	### Renvoie directement la valeur (string) du PREMIER élément trouvé dans $recherche
	public function ValFist($fXML, $recherche) {
		$this->XMLdata = @simplexml_load_file($fXML);
		if(is_object($this->XMLdata)) {
			$r = $this->XMLdata->xpath($recherche);
			$r = trim((string) $r[0]);
		} else return false;
		return $r;
	}

	### Renvoie le tableau (array) des éléments trouvés dans $recherche
	### sans tenir compte des éléments enfants
	public function ValAllFirst($fXML, $recherche) {
		$this->XMLdata = @simplexml_load_file($fXML);
		if(is_object($this->XMLdata)) {
			$r = array();
			$a = $this->XMLdata->xpath($recherche);
			foreach($a as $vr) {
				$r[] = trim((string) $vr[0]);
			}
		} else return false;
		return $r;
	}

	### Parse des données XML (total) avec parss2
	private function parseX($a) {
		if($a != null) {
			$tb = array();
			foreach($a as $num => $ojbc) $tb[$ojbc->getName()][] = $this->parss2($ojbc);
			return $tb;
		} else return false;
	}
	private function parss2($a) {
		$tb = array();
		$att = $a->attributes();if(sizeof($att) > 0) foreach($att as $natt => $vatt) $tb["@att"][$natt] = trim((string) $vatt);
		$tb["txt"] = trim((string) $a);
		if($a->count() > 0) foreach($a->children() as $nom1 => $val1) $tb["child"][$nom1][] = $this->parss2($val1);
		return $tb;
	}
}

?>
