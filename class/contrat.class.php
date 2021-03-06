<?php
class TRH_Contrat  extends TObjetStd {
	
	function __construct(){
		global $conf;
		parent::set_table(MAIN_DB_PREFIX.'rh_contrat');
		parent::add_champs('libelle,numContrat,bail','type=chaine;');
		parent::add_champs('date_debut, date_fin','type=date;');
		
		parent::add_champs('TVA, kilometre, dureeMois','type=entier;');
		parent::add_champs('loyer_TTC,assurance,entretien,frais','type=float;');
		
		//Un evenement est lié à une ressource et deux tiers (agence utilisatrice et fournisseur)
		parent::add_champs('fk_tier_fournisseur,entity,fk_rh_ressource_type','type=entier;index;');
		
		parent::_init_vars();
		parent::start();
		
		$this->TBail = array('location'=>'Location','immobilisation'=>'Immobilisation');
		
		$this->TTypeRessource = array();
		$this->TAgence = array();
		$this->TTVA = array();
		$this->TRessource = array();
		
	}
	
	function load_liste(&$PDOdb){
		global $conf;
		//chargement des listes pour les combos
		
		$this->TTypeRessource = array();
		$sqlReq="SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."rh_ressource_type ";
		$PDOdb->Execute($sqlReq);
		while($PDOdb->Get_line()) {
			$this->TTypeRessource[$PDOdb->Get_field('rowid')] = $PDOdb->Get_field('libelle');
			}
		
		//chargement d'une liste de touts les groupes
		$this->TAgence = array();
		//$sqlReq="SELECT rowid, nom FROM ".MAIN_DB_PREFIX."usergroup WHERE entity IN (0,".$conf->entity.")";
		$sqlReq="SELECT rowid, nom FROM ".MAIN_DB_PREFIX."usergroup";
		$PDOdb->Execute($sqlReq);
		while($PDOdb->Get_line()) {
			$this->TAgence[$PDOdb->Get_field('rowid')] = htmlentities($PDOdb->Get_field('nom'), ENT_COMPAT , 'ISO8859-1');
			}
		
		//chargement d'une liste des tiers
		$this->TFournisseur = array();
		//$sqlReq="SELECT rowid, nom FROM ".MAIN_DB_PREFIX."societe WHERE entity IN (0,".$conf->entity.")";
		$sqlReq="SELECT rowid, nom FROM ".MAIN_DB_PREFIX."societe";
		$PDOdb->Execute($sqlReq);
		while($PDOdb->Get_line()) {
			$this->TFournisseur[$PDOdb->Get_field('rowid')] = htmlentities($PDOdb->Get_field('nom'), ENT_COMPAT , 'ISO8859-1');
			}
		
		
		//chargement d'une liste de toutes les TVA
		$this->TTVA = array();
		$sqlReq="SELECT rowid, taux FROM ".MAIN_DB_PREFIX."c_tva WHERE fk_pays=".$conf->global->MAIN_INFO_SOCIETE_COUNTRY[0];
		$PDOdb->Execute($sqlReq);
		while($PDOdb->Get_line()) {
			$this->TTVA[$PDOdb->Get_field('rowid')] = $PDOdb->Get_field('taux');
			}
		
	}
	
	function save(&$db) {
		global $conf;
		$this->entity = $conf->entity;
		
		if ($this->date_fin < $this->date_debut) {
			$this->date_fin = $this->date_debut;
		}
		
		parent::save($db);
	}
	
	function delete(&$PDOdb){
		global $conf;
		//avant de supprimer le contrat, on supprime les liaisons contrat-ressource associés.
		$sql="DELETE FROM ".MAIN_DB_PREFIX."rh_contrat_ressource WHERE fk_rh_contrat=".$this->getId();
		$PDOdb->Execute($sql);
		
		//puis on supprime le contrat.
		parent::delete($PDOdb);
		
		
	}
	
	
}	
	
/*
 * Classes d'associations
 * 
 */

class TRH_Contrat_Ressource  extends TObjetStd {
	
	function __construct(){
		parent::set_table(MAIN_DB_PREFIX.'rh_contrat_ressource');
		parent::add_champs('commentaire','type=chaine;');
		
		parent::add_champs('fk_rh_contrat,entity','type=entier;index;');
		parent::add_champs('fk_rh_ressource','type=entier;index;');
		
		parent::_init_vars();
		parent::start();
	}
	
	function save(&$db) {
		global $conf;
		$this->entity = $conf->entity;
		if (empty($this->commentaire)){$this->commentaire = ' ';}
		parent::save($db);
	}
	
	
	
	
	
}	
	