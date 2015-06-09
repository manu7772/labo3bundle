<?php
// laboBundle/services/aetools/XMLparser.php

namespace laboBundle\services\aetools;

use \Exception;

class CSVreader {

	const DELIMITER = ",";
	const LINE_LENGTH = 0;

	protected $fCSV; //nom du document XML
	protected $CSVdata;
	protected $CSVhead;
	protected $header;
	protected $footer;
	protected $headline;
	protected $endline;

	/**
	 * Constructeur
	 */
	public function __construct() {
		//
	}

	public function loadCSV($file) {
		$this->CSVdata = array();
		$this->CSVhead = array();
		$row = 1;
		if(!file_exists($file)) throw new Exception("Fichier CSV absent : ".$file, 1);
		if (($handle = fopen($file, "r")) !== FALSE) {
			while (($data = fgetcsv($handle, self::LINE_LENGTH, self::DELIMITER)) !== FALSE) {
				$num = count($data);
				if($row === 1) {
					// entête : libellés
					$this->CSVhead = $data;
				} else {
					// lignes de données
					$this->CSVdata[] = array_slice($data, 0, count($this->CSVhead));
				}
				// printf("$num champs à la ligne $row:\n");
				$row++;
				// printf(implode(' / ', $data)."\n");
			}
			fclose($handle);
		}
	}

	public function createXMLfileFromCSV($fileCSV, $fileXML) {
		$this->initForXML();
		$this->loadCSV($fileCSV);
		$corps = "";
		foreach ($this->CSVdata as $ligne => $data) {
			$entree = array();
			foreach ($data as $col => $value) {
				$entree[$col] = $this->CSVhead[$col].'="'.str_replace('"', '', $value).'"';
			}
			$corps .= $this->headline.implode(" ", $entree).$this->endline;
		}
		$corps = $this->header.$corps.$this->footer;
		// printf($corps."\n\n\n");
		// printf("Enregistrement dans : ".$fileXML."\n");
		if(!file_put_contents($fileXML, $corps)) throw new Exception("Fichier XML n'a pu être écrit.".$file, 1);
		return true;
	}

	protected function initForXML() {
		$this->header = '<?xml version="1.0" encoding="UTF-8"?>
<!-- 
entités liées :
nom    -> nomDuChampLocal* + [_nomDeLEntitéLiée] + __nomDuChampDeLentiteLiée (ex. : statut__nom) (* sans \'s\' !!!)
toutes entités :
valeur -> si plusieurs valeurs possibles, séparer les valeurs par un pipe "|"
       -> commencer par un "+" pour ajouter aussi les valeurs par défaut, sinon elles ne seront pas ajoutées

       (ex. : typeImage__nom="+Universel|Ambiance") -> revient à écrire typeImage_typeImage__nom
       (ex. : imagePpale_image__nom="Curvy 2|Curvy 3")

fichiers externes : "import@" + nomDuDossier + "::" + nom du fichier (ex. texte="import@txt::intro.txt")
  -> utiliser "importConcat@" pour concaténer les fichiers textes et n\'obtenir qu\'une seule valeur d\'après tous les fichiers
noms de fichiers multiples : séparer par "|" (ex. texte="import@txt::intro.txt|intro2.txt|xml::intro3.txt")
  -> préciser à chaque fois le dossier / s\'il n\'est pas précisé, le nom du dossier précédent est repris.

 -->
<categories>
';

	$this->footer = '</categories>';

	$this->headline = '	<categorie ';

	$this->endline = ' />
';

	}


}