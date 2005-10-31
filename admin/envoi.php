<?php
/**
 * Copyright (c) 2002-2006 Aurélien Maille
 * 
 * This file is part of Wanewsletter.
 * 
 * Wanewsletter is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation; either version 2 
 * of the License, or (at your option) any later version.
 * 
 * Wanewsletter is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Wanewsletter; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * 
 * @package Wanewsletter
 * @author  Bobe <wascripts@phpcodeur.net>
 * @link    http://phpcodeur.net/wascripts/wanewsletter/
 * @license http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 * @version $Id$
 */

define('IN_NEWSLETTER', true);

require './pagestart.php';

if( !$admindata['session_liste'] )
{
	$output->build_listbox(AUTH_VIEW);
}

if( !$auth->check_auth(AUTH_VIEW, $admindata['session_liste']) )
{
	trigger_error('Not_auth_view', MESSAGE);
}

$listdata = $auth->listdata[$admindata['session_liste']];

$mode = ( !empty($_REQUEST['mode']) ) ? $_REQUEST['mode'] : '';

$logdata = array();
$logdata['log_id']        = ( !empty($_REQUEST['id']) ) ? intval($_REQUEST['id']) : 0;
$logdata['log_subject']   = ( !empty($_POST['subject']) ) ? trim($_POST['subject']) : '';
$logdata['log_body_text'] = ( !empty($_POST['body_text']) ) ? trim($_POST['body_text']) : '';
$logdata['log_body_html'] = ( !empty($_POST['body_html']) ) ? trim($_POST['body_html']) : '';
$logdata['log_status']    = ( !empty($_POST['log_status']) ) ? STATUS_HANDLE : STATUS_WRITING;

if( isset($_POST['cancel']) )
{
	Location('envoi.php?mode=load&amp;id=' . $logdata['log_id']);
}

$vararray = array('send', 'resend', 'save', 'delete'); 
foreach( $vararray AS $varname )
{
	$mode = ( isset($_REQUEST[$varname]) ) ? $varname : $mode;
	
	if( $mode != '' )
	{
		break;
	}
}

if( $auth->check_auth(AUTH_ATTACH, $listdata['liste_id']) && empty($mode) )
{
	if( isset($_POST['attach']) )
	{
		$mode = 'attach';
	}
	else if( isset($_POST['unattach']) )
	{
		$mode = 'unattach';
	}
}

$output->build_listbox(AUTH_VIEW, false);

switch( $mode )
{
	//
	// Téléchargement d'un fichier joint
	//
	case 'download':
		include WA_PATH . 'includes/class.attach.php';
		
		$file_id = ( !empty($_GET['fid']) ) ? intval($_GET['fid']) : 0;
		$attach  = new Attach();
		$attach->download_file($file_id);
		break;
	
	//
	// Chargement d'un log dont on veut reprendre l'écriture ou l'envoi
	//
	case 'load':
	case 'resend':
		if( $mode == 'resend' )
		{
			$sql_where = ' AND log_status = ' . STATUS_STANDBY;
		}
		else
		{
			$sql_where = ' AND ( log_status = ' . STATUS_WRITING . ' OR log_status = ' . STATUS_HANDLE . ' )';
		}
		
		if( isset($_POST['submit']) || $logdata['log_id'] )
		{
			$sql = "SELECT log_id, log_subject, log_body_text, log_body_html, log_status 
				FROM " . LOG_TABLE . " 
				WHERE liste_id = " . $listdata['liste_id'] . " 
					AND log_id = " . $logdata['log_id'] . $sql_where;
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible d\'obtenir les données sur ce log', ERROR);
			}
			
			if( $row = $db->fetch_array($result) )
			{
				$logdata = $row;
			}
			else
			{
				$output->redirect('envoi.php?mode=' . $mode, 4);
				
				$message  = $lang['Message']['log_not_exists'];
				$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./envoi.php?mode=' . $mode) . '">', '</a>');
				trigger_error($message, MESSAGE);
			}
		}
		else
		{
			$sql = "SELECT log_id, log_subject, log_status 
				FROM " . LOG_TABLE . " 
				WHERE liste_id = " . $listdata['liste_id'] . $sql_where . " 
				ORDER BY log_subject ASC";
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible d\'obtenir la liste des log', ERROR);
			}
			
			if( $row = $db->fetch_array($result) )
			{
				$log_box = '<select name="id">';
				
				do
				{
					if( $row['log_status'] == STATUS_HANDLE )
					{
						$status = '[' . $lang['Handle'] . ']';
						$style  = 'color: #25F !important;';
					}
					else
					{
						$status = ''; 
						$style  = 'color: black !important;';
					}
					
					$log_box .= '<option style="' . $style . '" value="' . $row['log_id'] . '"> - ' . htmlspecialchars(cut_str($row['log_subject'], 60)) . ' ' . $status . ' - </option>';
				}
				while( $row = $db->fetch_array($result) );
				
				$log_box .= '</select>';
			}
			else
			{
				$output->redirect('envoi.php', 4);
				
				$message  = ( $mode == 'load' ) ? $lang['Message']['No_log_to_load'] : $lang['Message']['No_log_to_send'];
				$message .= '<br /><br />' . sprintf($lang['Click_return_form'], '<a href="' . sessid('./envoi.php') . '">', '</a>');
				trigger_error($message, MESSAGE);
			}
			
			$output->addHiddenField('mode',   $mode);
			$output->addHiddenField('sessid', $session->session_id);
			
			$output->page_header();
			
			$output->set_filenames(array(
				'body' => 'select_log_body.tpl'
			));
			
			$output->assign_vars(array(
				'L_TITLE'         => $lang['Title']['select'],
				'L_SELECT_LOG'    => ( $mode == 'load' ) ? $lang['Select_log_to_load'] : $lang['Select_log_to_send'],
				'L_VALID_BUTTON'  => $lang['Button']['valid'],
				
				'LOG_BOX'         => $log_box,
				'S_HIDDEN_FIELDS' => $output->getHiddenFields(),
				'U_FORM'          => sessid('./envoi.php')
			));
			
			$output->pparse('body');
			
			$output->page_footer();
		}
		break;
	
	//
	// Suppression d'une newsletter
	//
	case 'delete':
		if( !$logdata['log_id'] )
		{
			$output->redirect('envoi.php', 4);
			
			$message  = $lang['Message']['No_log_id'];
			$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./envoi.php') . '">', '</a>');
			trigger_error($message, MESSAGE);
		}
		
		if( isset($_POST['confirm']) )
		{
			$db->transaction(START_TRC);
			
			$sql = 'DELETE FROM ' . LOG_TABLE . ' 
				WHERE log_id = ' . $logdata['log_id'];
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de supprimer le log', ERROR);
			}
			
			include WA_PATH . 'includes/class.attach.php';
			
			$attach = new Attach();
			$attach->delete_joined_files(true, $logdata['log_id']);
			
			$db->transaction(END_TRC);
			
			//
			// Optimisation des tables
			//
			$db->check(array(LOG_TABLE, LOG_FILES_TABLE, JOINED_FILES_TABLE));
			
			$output->redirect('./envoi.php', 4);
			
			$message  = $lang['Message']['log_deleted'];
			$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./envoi.php') . '">', '</a>');
			trigger_error($message, MESSAGE);
		}
		else
		{
			$output->addHiddenField('mode',   'delete');
			$output->addHiddenField('sessid', $session->session_id);
			$output->addHiddenField('id',     $logdata['log_id']);
			
			$output->page_header();
			
			$output->set_filenames(array(
				'body' => 'confirm_body.tpl'
			));
			
			$output->assign_vars(array(
				'L_CONFIRM' => $lang['Title']['confirm'],
				
				'TEXTE' => $lang['Delete_log'],
				'L_YES' => $lang['Yes'],
				'L_NO'  => $lang['No'],
				
				'S_HIDDEN_FIELDS' => $output->getHiddenFields(),
				'U_FORM' => sessid('./envoi.php')
			));
			
			$output->pparse('body');
			
			$output->page_footer();
		}
		break;
	
	case 'attach':
	case 'send':
	case 'save':
		if( ( $mode == 'attach' && empty($logdata['log_id']) ) || $mode == 'send' || $mode == 'save' )
		{
			if( $logdata['log_subject'] == '' )
			{
				$error = true;
				$msg_error[] = $lang['Subject_empty'];
			}
			
			if( $listdata['liste_format'] != FORMAT_HTML && $logdata['log_body_text'] == '' )
			{
				$error = true;
				$msg_error[] = $lang['Body_empty'];
			}
			
			if( $listdata['liste_format'] != FORMAT_TEXTE && $logdata['log_body_html'] == '' )
			{
				$error = true;
				$msg_error[] = $lang['Body_empty'];
			}
			
			if( $mode == 'send' )
			{
				if( $listdata['liste_format'] != FORMAT_HTML && !strstr($logdata['log_body_text'], '{LINKS}') )
				{
					$error = true;
					$msg_error[] = $lang['No_links_in_body'];
				}
				
				if( $listdata['liste_format'] != FORMAT_TEXTE )
				{
					if( !strstr($logdata['log_body_html'], '{LINKS}') )
					{
						$error = true;
						$msg_error[] = $lang['No_links_in_body'];
					}
					
					$sql = "SELECT jf.file_real_name, l.log_id
						FROM " . JOINED_FILES_TABLE . " AS jf, " . LOG_FILES_TABLE . " AS lf, " . LOG_TABLE . " AS l
						WHERE jf.file_id = lf.file_id
							AND lf.log_id = l.log_id
							AND l.liste_id = " . $listdata['liste_id'] . "
						ORDER BY jf.file_real_name ASC";
					if( !($result = $db->query($sql)) )
					{
						trigger_error('Impossible d\'obtenir la liste des fichiers joints', ERROR);
					}
					
					$files = $files_error = array();
					while( $row = $db->fetch_array($result) )
					{
						if( $row['log_id'] == $logdata['log_id'] )
						{
							$files[] = $row['file_real_name'];
						}
					}
					
					$total_cid = preg_match_all('/<.+?"cid:([^\\:*\/?<">|]+)"[^>]*>/i', $logdata['log_body_html'], $matches);
					
					for( $i = 0; $i < $total_cid; $i++ )
					{
						if( !in_array($matches[1][$i], $files) )
						{
							$files_error[] = htmlspecialchars($matches[1][$i]);
						}
					}
					
					if( count($files_error) > 0 )
					{
						$error = true;
						$msg_error[] = sprintf($lang['Cid_error_in_body'], implode(', ', $files_error));
					}
				}
			}
			
			if( !$error )
			{
				$prev_status    = ( isset($_POST['prev_status']) ) ? $_POST['prev_status'] : 0;
				$sql_where      = '';
				$duplicate_log  = false;
				$duplicate_file = false;
				
				$tmp_id = $logdata['log_id'];
				unset($logdata['log_id']);
				
				//
				// Au cas où la newsletter a le status WRITING mais que son précédent statut était HANDLE, 
				// nous la dupliquons pour garder intact le modèle
				// Si la newsletter a un statut HANDLE et qu'on est en mode send, nous dupliquons newsletter 
				// et entrées pour les fichiers joints
				//
				if( $logdata['log_status'] == STATUS_WRITING )
				{
					if( $mode == 'send' )
					{
						$logdata['log_status'] = STATUS_STANDBY;
					}
					
					if( $prev_status == STATUS_HANDLE )
					{
						$handle_id      = $tmp_id;
						$tmp_id         = 0;
						$duplicate_file = true;
					}
				}
				else if( $mode == 'send' )
				{
					$duplicate_log  = true;
					$duplicate_file = true;
				}
				
				$logdata['log_date'] = time();
				$logdata['liste_id'] = $listdata['liste_id'];
				
				if( empty($tmp_id) )
				{
					$sql_type  = 'INSERT';
				}
				else
				{
					$sql_type  = 'UPDATE';
					$sql_where = array('log_id' => $tmp_id, 'liste_id' => $listdata['liste_id']);
				}
				
				if( !$db->query_build($sql_type, LOG_TABLE, $logdata, $sql_where) )
				{
					trigger_error('Impossible de sauvegarder la newsletter', ERROR);
				}
				
				if( $sql_type == 'INSERT' )
				{
					$tmp_id = $db->next_id();
				}
				
				//
				// Duplication de la newsletter
				//
				if( $duplicate_log )
				{
					$handle_id = $tmp_id;
					$logdata['log_status'] = STATUS_STANDBY;
					
					if( !$db->query_build('INSERT', LOG_TABLE, $logdata) )
					{
						trigger_error('Impossible de dupliquer la newsletter', ERROR);
					}
					
					$tmp_id = $db->next_id();
				}
				
				//
				// Duplication des entrées pour les fichiers joints
				//
				if( $duplicate_file )
				{
					$sql = "SELECT file_id 
						FROM " . LOG_FILES_TABLE . " 
						WHERE log_id = " . $handle_id;
					if( !($result = $db->query($sql)) )
					{
						trigger_error('Impossible d\'obtenir les fichiers joints de ce log', ERROR);
					}
					
					$sql_values = array();
					
					while( $row = $db->fetch_array($result) )
					{
						switch( DATABASE )
						{
							case 'mysql':
							case 'mysql4':
								$sql_values[] = '(' . $tmp_id . ', ' . $row['file_id'] . ')';
								break;
							
							default:
								$sqldata = array('log_id' => $tmp_id, 'file_id' => $row['file_id']);
								
								if( !$db->query_build('INSERT', LOG_FILES_TABLE, $sqldata) )
								{
									trigger_error('Impossible de dupliquer les fichiers joints', ERROR);
								}
								break;
						}
					}
					
					if( count($sql_values) > 0 )
					{
						$sql = "INSERT INTO " . LOG_FILES_TABLE . " (log_id, file_id) 
							VALUES " . implode(', ', $sql_values);
						if( !$db->query($sql) )
						{
							trigger_error('Impossible de dupliquer les fichiers joints', ERROR);
						}
					}
				}
				
				$logdata['log_id'] = $tmp_id;
				unset($tmp_id);
				
				if( $mode == 'save' || $mode == 'send' )
				{
					if( $mode == 'save' )
					{
						$output->redirect('./envoi.php?mode=load&amp;id=' . $logdata['log_id'], 4);
						
						$message  = $lang['Message']['log_saved'];
						$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./envoi.php?mode=load&amp;id=' . $logdata['log_id']) . '">', '</a>');
					}
					else
					{
						$message  = $lang['Message']['log_ready'];
						$message .= '<br /><br />' . sprintf($lang['Click_start_send'], '<a href="' . sessid('./envoi.php?mode=resend&amp;id=' . $logdata['log_id']) . '">', '</a>');
					}
					
					trigger_error($message, MESSAGE);
				}
			}
		}
		
		//
		// Attachement de fichiers
		//
		if( $mode == 'attach' && !empty($logdata['log_id']) && $auth->check_auth(AUTH_ATTACH, $listdata['liste_id']) )
		{
			$tmp_filename = ( !empty($_FILES['join_file']['tmp_name']) && $_FILES['join_file']['tmp_name'] != 'none' ) ? str_replace('\\\\', '\\', $_FILES['join_file']['tmp_name']) : ( ( !empty($_POST['join_file']) ) ? trim($_POST['join_file']) : '' );
			$filename     = ( !empty($_FILES['join_file']['name']) ) ? $_FILES['join_file']['name'] : '';
			$filesize     = ( !empty($_FILES['join_file']['size']) ) ? intval($_FILES['join_file']['size']) : 0;
			$filetype     = ( !empty($_FILES['join_file']['type']) ) ? $_FILES['join_file']['type'] : '';
			$errno_code   = ( !empty($_FILES['join_file']['error']) ) ? intval($_FILES['join_file']['error']) : UPLOAD_ERR_OK;
			$file_id      = ( !empty($_POST['fid']) ) ? intval($_POST['fid']) : 0;
			
			include WA_PATH . 'includes/class.attach.php';
			
			$attach = new Attach();
			
			if( !empty($file_id) )
			{
				//
				// Attachement d'un fichier utilisé dans une autre newsletter de la liste
				//
				$attach->use_file_exists($file_id, $logdata['log_id'], $error, $msg_error);
			}
			else if( !empty($tmp_filename) )
			{
				//
				// On a affaire soit à un fichier présent localement, soit à un fichier 
				// distant, soit à un fichier uploadé
				//
				if( empty($filename) )
				{
					$tmp_filename = str_replace('\\', '/', $tmp_filename);
					
					if( preg_match('#^(?:http|ftp)://.+/([^/]+)$#', $tmp_filename, $match) )
					{
						$upload_mode = 'remote';
						$filename    = $match[1];
					}
					else
					{
						$upload_mode = 'local';
						$filename    = $tmp_filename;
					}
				}
				else
				{
					$upload_mode = 'upload';
				}
				
				$attach->upload_file($upload_mode, $logdata['log_id'], $filename, $tmp_filename, $filesize, $filetype, $errno_code, $error, $msg_error);
			}
			else
			{
				$error = true;
				$msg_error[] = $lang['Message']['No_data_received'];
			}
		}
		break;
	
	case 'unattach':
		$file_ids = ( !empty($_POST['file_ids']) ) ? (array) $_POST['file_ids'] : array();
		
		if( $auth->check_auth(AUTH_ATTACH, $listdata['liste_id']) && count($file_ids) > 0 )
		{
			//
			// Suppression du fichier joint spécifié
			//
			include WA_PATH . 'includes/class.attach.php';
			
			$attach = new Attach();
			$attach->delete_joined_files(false, $logdata['log_id'], $file_ids);
			
			//
			// Optimisation des tables
			//
			$db->check(array(LOG_FILES_TABLE, JOINED_FILES_TABLE));
		}
		break;
}

$file_box = '';
$logdata['joined_files'] = array();

//
// Récupération des fichiers joints de la liste
//
if( $auth->check_auth(AUTH_ATTACH, $listdata['liste_id']) )
{
	//
	// On récupère tous les fichiers joints de la liste pour avoir les fichiers joints de la newsletter
	// en cours, et construire le select box des fichiers existants
	//
	$sql = "SELECT lf.log_id, jf.file_id, jf.file_real_name, jf.file_physical_name, jf.file_size, jf.file_mimetype 
		FROM " . JOINED_FILES_TABLE . " AS jf, " . LOG_FILES_TABLE . " AS lf, " . LOG_TABLE . " AS l 
		WHERE jf.file_id = lf.file_id 
			AND lf.log_id = l.log_id 
			AND l.liste_id = " . $listdata['liste_id'] . " 
		ORDER BY jf.file_real_name ASC";
	if( !($result = $db->query($sql)) )
	{
		trigger_error('Impossible d\'obtenir la liste des fichiers joints', ERROR);
	}
	
	$other_files = $joined_files_id = array();
	
	//
	// On dispatches les données selon que le fichier appartient à la newsletter en cours ou non.
	//
	while( $row = $db->fetch_array($result) )
	{
		if( $row['log_id'] == $logdata['log_id'] )
		{
			$logdata['joined_files'][] = $row;
			$joined_files_id[] = $row['file_id'];
		}
		else
		{
			//
			// file_id sert d'index dans le tableau, pour éviter les doublons ramenés par la requï¿½te
			//
			$other_files[$row['file_id']] = $row;
		}
	}
	
	foreach( $other_files AS $tmp_id => $row )
	{
		if( !in_array($tmp_id, $joined_files_id) )
		{
			$file_box .= '<option value="' . $tmp_id . '"> - ' . htmlspecialchars($row['file_real_name']) . ' - </option>';
		}
	}
	
	if( $file_box != '' )
	{
		$file_box = '<select name="fid"><option value="0"> - ' . $lang['File_on_server'] . ' - </option>' . $file_box . '</select>';
	}
	
	unset($other_files, $joined_files_id);
}

//
// Envois des emails
//
if( $mode == 'resend' )
{
	if( !$auth->check_auth(AUTH_SEND, $listdata['liste_id']) )
	{
		trigger_error('Not_auth_send', MESSAGE);
	}
	
	include WA_PATH . 'includes/wamailer/class.mailer.php';
	include WA_PATH . 'includes/engine_send.php';
	
	//
	// On règle le script pour ignorer une déconnexion du client et 
	// poursuivre l'envoi du flot d'emails jusqu'à son terme. 
	//
	if( !is_disabled_func('ignore_user_abort') )
	{
		@ignore_user_abort(true);
	}
	
	//
	// On augmente également le temps d'exécution maximal du script. 
	//
	// Certains hébergeurs désactivent pour des raisons évidentes cette fonction
	// Si c'est votre cas, vous êtes mal barré
	//
	if( !is_disabled_func('set_time_limit') )
	{
		@set_time_limit(1200);
	}
	
	//
	// Initialisation de la classe mailer
	//
	$mailer = new Mailer(WA_PATH . 'language/email_' . $nl_config['language'] . '/');
	
	if( $nl_config['use_smtp'] )
	{
		$mailer->smtp_path = WA_PATH . 'includes/wamailer/';
		$mailer->use_smtp(
			$nl_config['smtp_host'],
			$nl_config['smtp_port'],
			$nl_config['smtp_user'],
			$nl_config['smtp_pass']
		);
	}
	
	$mailer->correctRpath = !is_disabled_func('ini_set');
	
	$mailer->set_charset($lang['CHARSET']);
	$mailer->set_from($listdata['sender_email'], unhtmlspecialchars($listdata['liste_name']));
	
	if( $listdata['return_email'] != '' )
	{
		$mailer->set_return_path($listdata['return_email']);
	}
	
	//
	// On lance l'envoi
	//
	launch_sending($listdata, $logdata);
}

$output->addLink('section', './envoi.php?mode=load', $lang['Load_log']);
$output->addLink('section', './envoi.php?mode=resend', $lang['Resend_log']);
$output->addScript(WA_PATH . 'templates/admin/editor.js');

$output->addHiddenField('id',          $logdata['log_id']);
$output->addHiddenField('prev_status', $logdata['log_status']);
$output->addHiddenField('sessid',      $session->session_id);

$output->page_header();

$output->set_filenames(array(
	'body' => 'send_body.tpl'
));

$output->assign_vars(array(
	'L_EXPLAIN'               => nl2br($lang['Explain']['send']),	
	'L_LOAD_LOG'              => $lang['Load_log'],
	'L_RESEND_LOG'            => $lang['Resend_log'],
	'L_DEST'                  => $lang['Dest'],
	'L_SUBJECT'               => $lang['Log_subject'],
	'L_STATUS'                => $lang['Status'],
	'L_STATUS_WRITING'        => $lang['Status_writing'],
	'L_STATUS_HANDLE'         => $lang['Status_handle'],
	'L_SEND_BUTTON'           => $lang['Button']['send'],
	'L_SAVE_BUTTON'           => $lang['Button']['save'],
	'L_DELETE_BUTTON'         => $lang['Button']['delete'],
	'L_PREVIEW_BUTTON'        => str_replace('\'', '\\\'', $lang['Button']['preview']),
	'L_ADDLINK_BUTTON'        => str_replace('\'', '\\\'', $lang['Button']['links']),
	
	'S_DEST'                  => $listdata['liste_name'],
	'S_SUBJECT'               => htmlspecialchars($logdata['log_subject']),
	'S_STATUS'                => ( $logdata['log_status'] == STATUS_WRITING ) ? $lang['Status_writing'] : $lang['Status_handle'],
	'SELECTED_STATUS_WRITING' => ( $logdata['log_status'] == STATUS_WRITING ) ? ' selected="selected"' : '',
	'SELECTED_STATUS_HANDLE'  => ( $logdata['log_status'] == STATUS_HANDLE ) ? ' selected="selected"' : '',
	
	'S_ENCTYPE'               => ( FILE_UPLOADS_ON ) ? 'multipart/form-data' : 'application/x-www-form-urlencoded', 
	'S_HIDDEN_FIELDS'         => $output->getHiddenFields()
));

if( $listdata['liste_format'] != FORMAT_HTML )
{
	$output->assign_block_vars('formulaire', array(
		'L_TITLE'         => $lang['Log_in_text'],
		'L_EXPLAIN_BODY'  => nl2br($lang['Explain']['text']),
		
		'S_TEXTAREA_NAME' => 'body_text',
		'S_BODY'          => htmlspecialchars($logdata['log_body_text'], ENT_NOQUOTES),
		'S_FORMAT'        => FORMAT_TEXTE
	));
}

if( $listdata['liste_format'] != FORMAT_TEXTE )
{
	$output->assign_block_vars('formulaire', array(
		'L_TITLE'         => $lang['Log_in_html'],
		'L_EXPLAIN_BODY'  => nl2br($lang['Explain']['html']),
		
		'S_TEXTAREA_NAME' => 'body_html',
		'S_BODY'          => htmlspecialchars($logdata['log_body_html'], ENT_NOQUOTES),
		'S_FORMAT'        => FORMAT_HTML
	));
}

if( $auth->check_auth(AUTH_ATTACH, $listdata['liste_id']) )
{
	$rowspan = 2;
	if( FILE_UPLOADS_ON )
	{
		$rowspan++;
	}
	
	if( $file_box != '' )
	{
		$rowspan++;
	}
	
	$output->assign_block_vars('joined_files', array(
		'L_TITLE_ADD_FILE'   => $lang['Title']['join'],
		'L_EXPLAIN_ADD_FILE' => nl2br($lang['Explain']['join']),
		'L_ADD_FILE'         => $lang['Join_file_to_log'],
		'L_ADD_FILE_BUTTON'  => $lang['Button']['add_file'],		
		
		'S_ROWSPAN' => $rowspan
	));
	
	//
	// Si l'upload est autorisé, on affiche le champs type file
	//
	if( FILE_UPLOADS_ON )
	{
		$output->assign_block_vars('joined_files.upload_input', array());
	}
	
	//
	// Box de sélection de fichiers existants
	//
	if( $file_box != '' )
	{
		$output->assign_block_vars('joined_files.select_box', array(
			'SELECT_BOX' => $file_box
		));
	}
	
	$output->files_list($logdata);
}

$output->pparse('body');

$output->page_footer();
?>