<?php
	require('config.php');
	require('./class/contrat.class.php');
	require('./lib/ressource.lib.php');
	
	require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
	require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
	require_once(DOL_DOCUMENT_ROOT."/core/class/fileupload.class.php");
	require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
	
	$langs->load('ressource@ressource');
	$langs->load('main');
	$langs->load('other');
	
	$PDOdb=new TPDOdb;
	$contrat=new TRH_contrat;
	
	if(isset($_REQUEST['id'])) {
		$contrat->load($PDOdb, $_REQUEST['id']);
		_fiche($PDOdb, $contrat);
	}
	
	$PDOdb->close();
	llxFooter();
	
	function _fiche(&$PDOdb, &$contrat) {
		global $db,$user,$conf,$langs;
		llxHeader('','Fichiers joints');
		$dir_base = DOL_DATA_ROOT.'/ressource/';
		$upload_dir_base = $dir_base.'contrat/';
		
		$confirm = $_REQUEST['confirm'];
		$action = $_REQUEST['action'];
		
		$error = false;
		$message = false;
		$formconfirm = false;
		
		$html = new Form($db);
		$formfile = new FormFile($db);
		
		if ($_REQUEST["sendit"])
		{
			$upload_dir = $upload_dir_base.dol_sanitizeFileName($contrat->getId());
		
			if (dol_mkdir($upload_dir) >= 0)
			{
				
				$resupload = dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name'],0,0,$_FILES['userfile']['error']);
				
		        if (is_numeric($resupload) && $resupload > 0)
				{
					$message = $langs->trans("FileTransferComplete");
		            $error = false;
				}
				else
				{
					$langs->load("errors");
		
					if ($resupload < 0)	// Unknown error
					{
						$message = $langs->trans("ErrorFileNotUploaded");
					}
					else if (preg_match('/ErrorFileIsInfectedWithAVirus/',$resupload))	// Files infected by a virus
					{
						$message = $langs->trans("ErrorFileIsInfectedWithAVirus");
					}
					else	// Known error
					{
						$message = $langs->trans($resupload);
					}
				}
			}
		
		}
		
		// Delete
		if ($action == 'confirm_deletefile' && $confirm == 'yes')
		{
		
			$file = $dir_base . '/' . $_REQUEST['urlfile'];
			dol_delete_file( $file, 0, 0, 0, 'FILE_DELETE', $object);
		
			$message = $langs->trans("FileHasBeenRemoved");
		}
		
		// Get all files
		$sortfield  = GETPOST("sortfield", 'alpha');
		$sortorder  = GETPOST("sortorder", 'alpha');
		$page       = GETPOST("page", 'int');
		
		if ($page == -1)
		{
		    $page = 0;
		}
		
		$offset = $conf->liste_limit * $page;
		$pageprev = $page - 1;
		$pagenext = $page + 1;
		
		if (!$sortorder) $sortorder = "ASC";
		if (!$sortfield) $sortfield = "name";
		
		
		$upload_dir = $upload_dir_base.dol_sanitizeFileName($contrat->getId());
		
		$filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
		$totalsize = 0;
		foreach($filearray as $key => $file)
		{
			$totalsize += $file['size'];
		}
		
		if ($action == 'delete')
		{
			$formconfirm = $html->formconfirm($_SERVER["PHP_SELF"].'?id='.$contrat->getId().'&urlfile='.urldecode($_REQUEST['urlfile']), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 0);
		}
		
		$can_upload = 1;
		
		echo dol_get_fiche_head(ressourcePrepareHead($contrat, 'contrat'), 'document', 'Contrat');
		
		echo ($message ? dol_htmloutput_mesg($message, '', ($error ? 'error' : 'ok'), 0) : '');

		echo ($formconfirm ? $formconfirm : '');
		
		if($user->rights->ressource->ressource->uploadFiles){
			$formfile->form_attach_new_file($_SERVER["PHP_SELF"].'?id='.$contrat->getId(), '', 0, 0, $can_upload);
			$formfile->list_of_documents($filearray, $contrat, 'ressource', '&id='.$contrat->getId(),0,'contrat/'.$contrat->getId().'/',1);
		}else{
			$formfile->list_of_documents($filearray, $contrat, 'ressource', '&id='.$contrat->getId(),0,'contrat/'.$contrat->getId().'/',0);
		}
		
		?><div style="clear:both"></div><?
		
		dol_fiche_end();
		llxFooter();
		
		$db->close();
	}