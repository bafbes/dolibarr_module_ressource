#!/usr/bin/php
<?php
/*
 * Script envoyant un mail au responsable pour l'informer des attributions terminant très bientôt
 * 
 */
 	define('INC_FROM_CRON_SCRIPT', true);
	require('../../config.php');
	
	
	$PDOdb=new TPDOdb;
	$langs->load('mails');
	
	$sql = "SELECT r.numId, r.libelle, u.lastname, u.firstname, e.date_fin
	FROM ".MAIN_DB_PREFIX."user u
		LEFT JOIN ".MAIN_DB_PREFIX."rh_evenement e ON (e.fk_user=u.rowid)
		LEFT JOIN ".MAIN_DB_PREFIX."rh_ressource r ON (e.fk_rh_ressource=r.rowid)
	WHERE e.type='emprunt'
	AND DATEDIFF(e.date_fin,NOW())<".DAYS_BEFORE_ALERT."
	AND DATEDIFF(e.date_fin,NOW())>=0";
	
	$PDOdb->Execute($sql);
	$TAttribution = array();
	while($PDOdb->Get_line()) {
		$TAttribution[] = array(
			'ressourceID'=>$PDOdb->Get_field('numId')
			,'ressourceName'=>$PDOdb->Get_field('libelle')
			,'userName'=>$PDOdb->Get_field('lastname')
			,'userFirstname'=>$PDOdb->Get_field('firstname')
			,'dateFinAttribution'=>$PDOdb->Get_field('date_fin')
		);
		
	}
	
	_mail_attribution($PDOdb, $TAttribution);
	
	return 1;
	
function _mail_attribution(&$PDOdb, &$TAttribution) {
	
	$from = USER_MAIL_SENDER;
	$sendto = USER_MAIL_RECEIVER;
	
	if(count($TAttribution)>0){
		$TBS=new TTemplateTBS();
		$subject = "Alerte - Ressources en fin d'attribution";
		$message = $TBS->render(dol_buildpath('/ressource/tpl/mail.attribution.alerte.tpl.php')
			,array(
				'attribution'=>$TAttribution
			)
			,array(
			)
		);
		
		// Send mail
		$mail = new TReponseMail($from,$sendto,$subject,$message);
		
	    (int)$result = $mail->send(true, 'utf-8');
	}
	
}