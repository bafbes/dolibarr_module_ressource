<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *      \file       test/phpunit/UserTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */




global $conf,$user,$langs,$db;
//inclusion de config des tests.
/*require('./config.php');
require('../lib/ressource.lib.php');
require('../class/ressource.class.php');
require('../class/evenement.class.php');
require('../class/contrat.class.php');*/


$ress = new TRH_Ressource;
$PDOdb = new TPDOdb;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class RessourceTest extends PHPUnit_Framework_TestCase
{
		
		
	public static function setUpBeforeClass()
    {
        print "Début du test de Ressource.\n";
    }
 
    public static function tearDownAfterClass()
    {
    	global $PDOdb;
		print "\nFin du test de Ressource.\n";
    }
	
	public function testcreateRessource()
    {
    	global $ress;
		$ress = new TRH_Ressource;
		$this->assertNotNull($ress);
		print __METHOD__."\n";
    }
	
	public function testLoad_liste_type_ressource(){
		global $ress, $PDOdb;
		$ress->load_liste_type_ressource($PDOdb);
		$this->assertNotEmpty($ress->TType);
		print __METHOD__."\n";
	}
	
	
	public function testLoad_agence(){
		global $ress, $PDOdb;
		$ress->load_agence($PDOdb);
		$this->assertNotEmpty($ress->TAgence);
		$this->assertNotEmpty($ress->TFournisseur);
		print __METHOD__."\n";
	}
	
	public function testLoad_liste_entity(){
		global $ress, $PDOdb;
		$ress->load_liste_entity($PDOdb);
		$this->assertNotEmpty($ress->TEntity);
		print __METHOD__."\n";
	}
	

	public function testLoad_by_numId(){
		global $ress, $PDOdb;
		
		//test avec un numId qui n'existe pas.
		$ret = $ress->load_by_numId($PDOdb, '0');
		$this->assertFalse($ret);
		
		//test avec un numId qui existe
		$sqlReq="SELECT numId FROM ".MAIN_DB_PREFIX."rh_ressource LIMIT 0,1";
		$PDOdb->Execute($sqlReq);
		if ($row = $PDOdb->Get_line()) {$numId = $row->numId;}
		$ress->load_by_numId($PDOdb, $numId);
		$this->assertEquals($ress->numId, $numId);

		print __METHOD__."\n";
	}
	
	public function testLoad(){
		global $ress, $PDOdb;
		
		//récupêrer un rowid qui existe
		$sqlReq="SELECT rowid FROM ".MAIN_DB_PREFIX."rh_ressource LIMIT 0,1";
		$PDOdb->Execute($sqlReq);
		if ($row = $PDOdb->Get_line()) {$rowid = $row->rowid;}
		//test du load.
		$ress->load_by_numId($PDOdb, $rowid);
		$this->assertEquals($ress->getId(), $rowid);
		print __METHOD__."\n";
	}

	public function testLoad_evenement(){
		global $ress, $PDOdb;
		
		$ress->load_evenement($PDOdb);
		$this->assertNotEmpty($ress->TEvenement);
		print __METHOD__."\n";
	} 
   
	public function testLoad_contrat(){
		global $ress, $PDOdb;
		
		$ress->TListeContrat = array(0=>1);
		$ress->load_contrat($PDOdb);
		$this->assertNotEmpty($ress->TContratAssocies);
		$this->assertNotEmpty($ress->TTVA);
		$this->assertNotEmpty($ress->TListeContrat);
		print __METHOD__."\n";
	}
	
	public function testListe_contrat(){
		global $ress, $PDOdb;
		
		$ret = $ress->liste_contrat($PDOdb);
		$this->assertNotNull($ret);
		print __METHOD__."\n";
	}

	public function testStrToTimestamp(){
		global $ress, $PDOdb;
		
		$ts = $ress->strToTimestamp("03/01/1970 00:00:00");
		$this->assertEquals(2*24*3600-3600, $ts);
		print __METHOD__."\n";
	}
	
	public function testNouvelEmpruntSeChevauche(){
		global $ress, $PDOdb;
		
		$sqlReq="SELECT rowid, date_debut,date_fin, fk_rh_ressource FROM ".MAIN_DB_PREFIX."rh_evenement
		WHERE type='emprunt' LIMIT 0,1"; 
		$PDOdb->Execute($sqlReq);
		if($row = $PDOdb->Get_line()) {
				
			//pour l'attribution qui sera acceptée
			$newEmpruntFalse = array(
				'date_debut'=>date("d/m/Y",strtotime($row->date_debut)-365*24*3600*8)
				, 'date_fin'=>date("d/m/Y",strtotime($row->date_fin)+365*24*3600*9)
				, 'idEven'=>$row->rowid
				);
			$idRessourceFalse = $row->fk_rh_ressource;
			
			//pour l'attribution qui sera refusée
			$newEmpruntTrue = array(
				'date_debut'=>date("d/m/Y",strtotime($row->date_debut))
				, 'date_fin'=>date("d/m/Y",strtotime($row->date_fin))
				, 'idEven'=>$row->rowid+10 //rowid différent exprès.
				);
			$idRessourceTrue = $row->fk_rh_ressource;
		}
		print_r($newEmprunt);
		$retFalse = $ress->nouvelEmpruntSeChevauche($PDOdb, $idRessourceFalse, $newEmpruntFalse);
		$this->assertFalse($retFalse);
		$retTrue = $ress->nouvelEmpruntSeChevauche($PDOdb, $idRessourceTrue, $newEmpruntTrue);
		$this->assertTrue($retTrue);
		print __METHOD__."\n";
	}
	
	
	public function testDateSeChevauchent(){
		global $ress;
		
		$res = $ress->dateSeChevauchent(10,20,1,5);
		$this->assertFalse($res);
		
		$res = $ress->dateSeChevauchent(10,20,5,15);
		$this->assertTrue($res);
		
		$res = $ress->dateSeChevauchent(10,20,15,25);
		$this->assertTrue($res);
		
		$res = $ress->dateSeChevauchent(10,20,30,40);
		$this->assertFalse($res);
		
	}
	
	
	public function testLoad_ressource_type(){
		global $ress, $PDOdb;
		$ress->load_ressource_type($PDOdb);
		$this->assertGreaterThan(0, $ress->fk_rh_ressource_type);
	}
	
	public function testInit_variables(){
		global $ress, $PDOdb;
		$ress->init_variables($PDOdb);
	}
	
	public function testSave(){
		global $ress, $PDOdb;
		$ress->save($PDOdb);
		$ress->delete($PDOdb);
	}
	
	
	
}
