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
	$output->message('Not_auth_view');
}

//
// Vous pouvez, grâce à cette constante, désactiver la vérification de 
// l'existence du tag {LINKS} dans les lettres au moment de l'envoi.
// 
// N'oubliez pas que permettre aux abonnés à vos listes de se désinscrire
// simplement et rapidement si tel est leur souhait est une obligation légale (1).
// Aussi, et sauf cas particuliers, vous ne devriez pas retirer ces liens 
// des lettres que vous envoyez, et donc changer la valeur de cette constante.
// 
// (1) Au moins en France, en application de l'article 22 de la loi n° 2004-575 
// du 21 juin 2004 pour la confiance dans l'économie numérique (LCEN).
//
define('DISABLE_CHECK_LINKS', FALSE);

//
// End of Configuration
//

$listdata = $auth->listdata[$admindata['session_liste']];

$logdata = array();
$logdata['log_id']        = ( !empty($_REQUEST['id']) ) ? intval($_REQUEST['id']) : 0;
$logdata['log_subject']   = ( !empty($_POST['subject']) ) ? trim($_POST['subject']) : '';
$logdata['log_body_text'] = ( !empty($_POST['body_text']) ) ? trim($_POST['body_text']) : '';
$logdata['log_body_html'] = ( !empty($_POST['body_html']) ) ? trim($_POST['body_html']) : '';
$logdata['log_status']    = ( !empty($_POST['log_status']) ) ? STATUS_MODEL : STATUS_WRITING;

$mode = ( !empty($_REQUEST['mode']) ) ? $_REQUEST['mode'] : '';
$prev_status = ( isset($_POST['prev_status']) ) ? intval($_POST['prev_status']) : $logdata['log_status'];

if( isset($_POST['cancel']) )
{
	Location('envoi.php?mode=load&amp;id=' . $logdata['log_id']);
}

$vararray = array('send', 'save', 'delete', 'attach', 'unattach');
foreach( $vararray as $varname )
{
	if( isset($_REQUEST[$varname]) )
	{
		$mode = $varname;
		break;
	}
}

$output->build_listbox(AUTH_VIEW, false);

switch( $mode )
{
	//
	// Téléchargement d'un fichier joint
	//
	case 'download':
		require WA_ROOTDIR . '/includes/class.attach.php';
		
		$file_id = ( !empty($_GET['fid']) ) ? intval($_GET['fid']) : 0;
		$attach  = new Attach();
		$attach->download_file($file_id);
		break;
	
	case 'cancel':
		if( isset($_POST['confirm']) )
		{
			$sql = "SELECT log_id, liste_id, log_status
				FROM " . LOG_TABLE . "
				WHERE log_id = " . $logdata['log_id'];
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible d\'obtenir la liste d\'appartenance du log', ERROR);
			}
			
			if( !($logdata = $result->fetch()) || $logdata['log_status'] != STATUS_STANDBY )
			{
				Location('envoi.php');
			}
			
			$sql = "SELECT COUNT(send) AS sended
				FROM " . ABO_LISTE_TABLE . "
				WHERE liste_id = $logdata[liste_id] AND send = 1";
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible d\'obtenir les données d\'envoi des log', ERROR);
			}
			
			$sended = $result->column('sended');
			
			//
			// Suppression du fichier lock correspondant s'il existe
			// et qu'aucun envoi n'est en cours.
			//
			$lockfile = sprintf(WA_LOCKFILE, $logdata['liste_id']);
			
			$fp = fopen($lockfile, (file_exists($lockfile) ? 'r+' : 'w'));
			if( !flock($fp, LOCK_EX|LOCK_NB) )
			{
				fclose($fp);
				$output->message('List_is_busy');
			}
			
			$db->beginTransaction();
			
			$sql = "UPDATE " . LOG_TABLE . "
				SET log_status  = " . STATUS_SENDED . ",
					log_numdest = $sended
				WHERE log_id = " . $logdata['log_id'];
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de mettre à jour la table des logs', ERROR);
			}
			
			$sql = "UPDATE " . ABO_LISTE_TABLE . "
				SET send = 0
				WHERE liste_id = " . $logdata['liste_id'];
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de mettre à jour la table des abonnés', ERROR);
			}
			
			$sql = "UPDATE " . LISTE_TABLE . " 
				SET liste_numlogs = liste_numlogs + 1 
				WHERE liste_id = " . $logdata['liste_id'];
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de mettre à jour la table des listes', ERROR);
			}
			
			$db->commit();
			
			flock($fp, LOCK_UN);
			fclose($fp);
			unlink($lockfile);
			
			$output->message('Send_canceled');
		}
		else
		{
			$output->addHiddenField('id', $logdata['log_id']);
			$output->addHiddenField('sessid', $session->session_id);
			
			$output->page_header();
			
			$output->set_filenames(array(
				'body' => 'confirm_body.tpl'
			));
			
			$output->assign_vars(array(
				'L_CONFIRM' => $lang['Title']['confirm'],
				
				'TEXTE' => $lang['Cancel_send_log'],
				'L_YES' => $lang['Yes'],
				'L_NO'  => $lang['No'],
				
				'S_HIDDEN_FIELDS' => $output->getHiddenFields(),
				'U_FORM' => sessid('./envoi.php?mode=cancel')
			));
			
			$output->pparse('body');
			
			$output->page_footer();
		}
		break;
	
	case 'progress':
		$liste_ids = $auth->check_auth(AUTH_SEND);
		
		if( $logdata['log_id'] )
		{
			$sql = "SELECT log_id, log_subject, log_body_text, log_body_html, log_status
				FROM " . LOG_TABLE . "
				WHERE liste_id IN(" . implode(', ', $liste_ids) . ")
					AND log_id = $logdata[log_id]
					AND log_status = " . STATUS_STANDBY;
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible d\'obtenir les données sur ce log', ERROR);
			}
			
			if( !($logdata = $result->fetch()) )
			{
				$output->redirect('envoi.php?mode=progress', 4);
				
				$message  = $lang['Message']['No_log_found'];
				$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./envoi.php?mode=progress') . '">', '</a>');
				$output->message($message);
			}
			
			if( DISABLE_CHECK_LINKS == false && empty($listdata['form_url']) )
			{
				$message = nl2br(sprintf($lang['Message']['No_form_url'], '<a href="' . sessid('view.php?mode=liste&amp;action=edit') . '">', '</a>'));
				$output->message($message);
			}
		}
		else
		{
			foreach( $liste_ids as $liste_id )
			{
				$lockfile = sprintf(WA_LOCKFILE, $liste_id);
				
				if( file_exists($lockfile) && filesize($lockfile) > 0 )
				{
					$fp = fopen($lockfile, 'r+');
					
					if( flock($fp, LOCK_EX|LOCK_NB) )
					{
						$abo_ids = fread($fp, filesize($lockfile));
						$abo_ids = array_map('trim', explode("\n", trim($abo_ids)));
						
						if( count($abo_ids) > 0 )
						{
							$abo_ids = array_unique(array_map('intval', $abo_ids));
							
							$sql = "UPDATE " . ABO_LISTE_TABLE . "
								SET send = 1
								WHERE abo_id IN(" . implode(', ', $abo_ids) . ")
									AND liste_id = " . $liste_id;
							if( !$db->query($sql) )
							{
								trigger_error('Impossible de mettre à jour la table des abonnés', ERROR);
							}
						}
						
						ftruncate($fp, 0);
						flock($fp, LOCK_UN);
					}
					
					fclose($fp);
				}
			}
			
			$sql = "SELECT COUNT(send) AS num, send, liste_id
				FROM " . ABO_LISTE_TABLE . "
				WHERE liste_id IN(" . implode(', ', $liste_ids) . ")
				GROUP BY liste_id, send";
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible d\'obtenir les données d\'envoi des log', ERROR);
			}
			
			$data = array();
			while( $row = $result->fetch() )
			{
				if( !isset($data[$row['liste_id']]) )
				{
					$data[$row['liste_id']] = array(0, 0, 't' => 0);
				}
				$data[$row['liste_id']][$row['send']] = $row['num'];
				$data[$row['liste_id']]['t'] += $row['num'];
			}
			
			$sql = "SELECT log_id, log_subject, log_status, liste_id
				FROM " . LOG_TABLE . "
				WHERE liste_id IN(" . implode(', ', $liste_ids) . ")
					AND log_status = " . STATUS_STANDBY . "
				ORDER BY log_subject ASC";
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible d\'obtenir la liste des log', ERROR);
			}
			
			if( !($row = $result->fetch()) )
			{
				$output->redirect('envoi.php', 4);
				
				$message  = $lang['Message']['No_log_to_send'];
				$message .= '<br /><br />' . sprintf($lang['Click_return_form'], '<a href="' . sessid('./envoi.php') . '">', '</a>');
				$output->message($message);
			}
			
			$output->page_header();
			
			$output->set_filenames(array(
				'body' => 'send_progress_body.tpl'
			));
			
			$output->assign_vars(array(
				'L_TITLE'       => $lang['List_send'],
				'L_SUBJECT'     => $lang['Log_subject'],
				'L_DONE'        => $lang['Done'],
				'L_DO_SEND'     => $lang['Restart_send'],
				'L_CANCEL_SEND' => $lang['Cancel_send'],
				'L_CREATE_LOG'  => $lang['Create_log'],
				'L_LOAD_LOG'    => $lang['Load_log']
			));
			
			do
			{
				$percent = 0;
				if( isset($data[$row['liste_id']]) )
				{
					$percent = wa_number_format(round((($data[$row['liste_id']][1] / $data[$row['liste_id']]['t']) * 100), 2));
				}
				
				$output->assign_block_vars('logrow', array(
					'LOG_ID'       => $row['log_id'],
					'LOG_SUBJECT'  => htmlspecialchars(cut_str($row['log_subject'], 40), ENT_NOQUOTES),
					'SEND_PERCENT' => $percent
				));
			}
			while( $row = $result->fetch() );
			
			$output->pparse('body');
			
			$output->page_footer();
		}
		break;
	
	//
	// Chargement d'un log dont on veut reprendre l'écriture ou l'envoi
	//
	case 'load':
		if( isset($_POST['submit']) || $logdata['log_id'] )
		{
			if( !empty($_POST['body_text_url']) || !empty($_POST['body_html_url']) )
			{
				if( !empty($_POST['body_text_url']) )
				{
					$result = http_get_contents($_POST['body_text_url'], $errstr);
					
					if( $result == false )
					{
						$message  = $errstr;
						$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./envoi.php?mode=load') . '">', '</a>');
						$output->message($message);
					}
					
					$logdata['log_body_text'] = convert_encoding($result['data'], $result['charset']);
				}
				
				if( !empty($_POST['body_html_url']) )
				{
					$result = http_get_contents($_POST['body_html_url'], $errstr);
					
					if( $result == false )
					{
						$message  = $errstr;
						$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./envoi.php?mode=load') . '">', '</a>');
						$output->message($message);
					}
					
					if( preg_match('/<head[^>]*>(.+?)<\/head>/is', $result['data'], $match_head) )
					{
						if( empty($result['charset']) )
						{
							preg_match_all('/<meta[^>]+>/si', $match_head[1], $match_meta, PREG_SET_ORDER);
							
							foreach( $match_meta as $meta )
							{
								if( preg_match('/http-equiv\s*=\s*("|\')Content-Type\\1/si', $meta[0])
									&& preg_match('/content\s*=\s*("|\').+?;\s*charset\s*=\s*([a-z][a-z0-9._-]*)\\1/si', $meta[0], $match) )
								{
									$result['charset'] = $match[2];
								}
							}
						}
						
						if( preg_match('/<title[^>]*>(.+?)<\/title>/is', $match_head[1], $match) )
						{
							$logdata['log_subject'] = convert_encoding(trim($match[1]), $result['charset']);
						}
						
						$URL = substr($_POST['body_html_url'], 0, strrpos($_POST['body_html_url'], '/'));
						$result['data'] = preg_replace('/<(head[^>]*)>/si',
							"<\\1>\n<base href=\"" . htmlspecialchars($URL) . "/\">", $result['data']);
					}
					
					$logdata['log_body_html'] = convert_encoding($result['data'], $result['charset']);
				}
			}
			else
			{
				$sql = "SELECT log_id, log_subject, log_body_text, log_body_html, log_status 
					FROM " . LOG_TABLE . " 
					WHERE liste_id = $listdata[liste_id]
						AND log_id = $logdata[log_id]
						AND (log_status = " . STATUS_WRITING . " OR log_status = " . STATUS_MODEL . ")";
				if( !($result = $db->query($sql)) )
				{
					trigger_error('Impossible d\'obtenir les données sur ce log', ERROR);
				}
				
				if( !($logdata = $result->fetch()) )
				{
					$output->redirect('envoi.php?mode=load', 4);
					
					$message  = $lang['Message']['log_not_exists'];
					$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./envoi.php?mode=load') . '">', '</a>');
					$output->message($message);
				}
				
				$prev_status = $logdata['log_status'];
			}
		}
		else
		{
			$sql = "SELECT log_id, log_subject, log_status 
				FROM " . LOG_TABLE . " 
				WHERE liste_id = $listdata[liste_id]
					AND (log_status = " . STATUS_WRITING . " OR log_status = " . STATUS_MODEL . ")
				ORDER BY log_subject ASC";
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible d\'obtenir la liste des log', ERROR);
			}
			
			$output->addHiddenField('mode',   'load');
			$output->addHiddenField('sessid', $session->session_id);
			
			$output->page_header();
			
			$output->set_filenames(array(
				'body' => 'select_log_body.tpl'
			));
			
			if( $row = $result->fetch() )
			{
				$log_box = '<select name="id">';
				
				do
				{
					if( $row['log_status'] == STATUS_MODEL )
					{
						$status = '[' . $lang['Model'] . ']';
						$style  = 'color: #25F !important;';
					}
					else
					{
						$status = ''; 
						$style  = 'color: black !important;';
					}
					
					$log_box .= sprintf(
						"<option style=\"%s\" value=\"%d\"> %s %s</option>\n",
						$style, $row['log_id'], htmlspecialchars(cut_str($row['log_subject'], 40)), $status
					);
				}
				while( $row = $result->fetch() );
				
				$log_box .= '</select>';
				
				$output->assign_block_vars('load_draft', array(
					'L_SELECT_LOG' => $lang['Select_log_to_load'],
					'LOG_BOX'      => $log_box
				));
				
				$output->assign_block_vars('script_load_by_url', array(
					'L_FROM_AN_URL' => str_replace('\'', '\\\'', $lang['From_an_URL'])
				));
			}
			
			$output->assign_vars(array(
				'L_TITLE'         => $lang['Title']['select'],
				'L_VALID_BUTTON'  => $lang['Button']['valid'],
				
				'S_HIDDEN_FIELDS' => $output->getHiddenFields(),
				'U_FORM'          => sessid('./envoi.php')
			));
			
			switch( $listdata['liste_format'] )
			{
				case FORMAT_TEXTE:
					$bloc_name = 'load_text_by_url';
					break;
				case FORMAT_HTML:
					$bloc_name = 'load_html_by_url';
					break;
				default:
					$bloc_name = 'load_multi_by_url';
					break;
			}
			
			$output->assign_block_vars($bloc_name, array(
				'L_LOAD_BY_URL' => $lang['Load_by_URL'],
				'L_FORMAT_TEXT' => $lang['Log_in_text'],
				'L_FORMAT_HTML' => $lang['Log_in_html'],
				
				'BODY_TEXT_URL' => ( !empty($_POST['body_text_url']) ) ? htmlspecialchars(trim($_POST['body_text_url'])) : '',
				'BODY_HTML_URL' => ( !empty($_POST['body_html_url']) ) ? htmlspecialchars(trim($_POST['body_html_url'])) : ''
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
			$output->message($message);
		}
		
		if( isset($_POST['confirm']) )
		{
			$db->beginTransaction();
			
			$sql = 'DELETE FROM ' . LOG_TABLE . ' 
				WHERE log_id = ' . $logdata['log_id'];
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de supprimer le log', ERROR);
			}
			
			require WA_ROOTDIR . '/includes/class.attach.php';
			
			$attach = new Attach();
			$attach->delete_joined_files(true, $logdata['log_id']);
			
			$db->commit();
			
			//
			// Optimisation des tables
			//
			$db->vacuum(array(LOG_TABLE, LOG_FILES_TABLE, JOINED_FILES_TABLE));
			
			$output->redirect('./envoi.php', 4);
			
			$message  = $lang['Message']['log_deleted'];
			$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./envoi.php') . '">', '</a>');
			$output->message($message);
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
		$cc_admin = ( !empty($_POST['cc_admin']) ) ? 1 : 0;
		
		if( ($mode == 'save' || $mode == 'send') && $listdata['cc_admin'] != $cc_admin )
		{
			$listdata['cc_admin'] = $cc_admin;
			
			$sql_data  = array('cc_admin' => $cc_admin);
			$sql_where = array('admin_id' => $admindata['admin_id'], 'liste_id' => $listdata['liste_id']);
			
			$db->build(SQL_UPDATE, AUTH_ADMIN_TABLE, $sql_data, $sql_where);
			if( $db->affectedRows() == 0 )
			{
				$sql_data = array_merge($sql_data, $sql_where);
				$db->build(SQL_INSERT, AUTH_ADMIN_TABLE, $sql_data);
			}
		}
		
		if( $mode != 'attach' || empty($logdata['log_id']) )
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
			
			//
			// Fonction de callback utilisée pour l'appel à preg_replace_callback() plus bas
			//
			function replace_include($match)
			{
				global $mode, $lang, $error, $msg_error;
				
				preg_match_all('/\\s+([a-z_:][a-z0-9_:.-]*)\\s?=\\s?(["\'])(.+?)(?<!\\\\)(?:\\\\\\\\)*\\2/i',
					$match[1], $attrs, PREG_SET_ORDER);
				
				$resource = null;
				$tds = false;
				foreach( $attrs as $attr )
				{
					switch( $attr[1] )
					{
						case 'src':
							$resource = stripslashes($attr[3]);
							break;
						case 'tds' && $attr[3] == 'true':
						case 'now' && $attr[3] == 'true':
							$tds = true;
							break;
					}
				}
				
				if( is_null($resource) || (!$tds && $mode != 'send') )
				{
					return $match[0];
				}
				
				if( substr($resource, 0, 7) == 'http://' )
				{
					$result = http_get_contents($resource, $errstr);
					if( $result == false )
					{
						$errstr = sprintf($lang['Message']['Error_load_url'], htmlspecialchars($resource), $errstr);
					}
				}
				else
				{
					if( $resource[0] != '/' )// Chemin non absolu
					{
						$resource = WA_ROOTDIR . '/' . $resource;
					}
					
					if( is_readable($resource) )
					{
						$fp = fopen($resource, 'r');
						$data = fread($fp, filesize($resource));
						fclose($fp);
						
						$result = array('data' => $data, 'charset' => '');
					}
					else
					{
						$result = false;
						$errstr = sprintf($lang['Message']['File_not_exists'], htmlspecialchars($resource));
					}
				}
				
				if( $result == false )
				{
					$error = true;
					$msg_error[] = $errstr;
					return $match[0];
				}
				else
				{
					return convert_encoding($result['data'], $result['charset']);
				}
			}
			
			$regexp = '/<\\?inclu[dr]e(\\s+[^>]+)\\?>/i';
			$logdata['log_body_text'] = preg_replace_callback($regexp, 'replace_include', $logdata['log_body_text']);
			$logdata['log_body_html'] = preg_replace_callback($regexp, 'replace_include', $logdata['log_body_html']);
			
			if( $mode == 'send' )
			{
				if( DISABLE_CHECK_LINKS == false && $listdata['liste_format'] != FORMAT_HTML && !strstr($logdata['log_body_text'], '{LINKS}') )
				{
					$error = true;
					$msg_error[] = $lang['No_links_in_body'];
				}
				
				if( $listdata['liste_format'] != FORMAT_TEXTE )
				{
					if( DISABLE_CHECK_LINKS == false && !strstr($logdata['log_body_html'], '{LINKS}') )
					{
						$error = true;
						$msg_error[] = $lang['No_links_in_body'];
					}
					
					$sql = "SELECT jf.file_real_name, l.log_id
						FROM " . JOINED_FILES_TABLE . " AS jf
							INNER JOIN " . LOG_FILES_TABLE . " AS lf ON lf.file_id = jf.file_id
							INNER JOIN " . LOG_TABLE . " AS l ON l.log_id = lf.log_id
								AND l.liste_id = $listdata[liste_id]
						ORDER BY jf.file_real_name ASC";
					if( !($result = $db->query($sql)) )
					{
						trigger_error('Impossible d\'obtenir la liste des fichiers joints', ERROR);
					}
					
					$files = $files_error = array();
					while( $row = $result->fetch() )
					{
						if( $row['log_id'] == $logdata['log_id'] )
						{
							$files[] = $row['file_real_name'];
						}
					}
					
					$total_cid = hasCidReferences($logdata['log_body_html'], $refs);
					
					for( $i = 0; $i < $total_cid; $i++ )
					{
						if( !in_array($refs[$i], $files) )
						{
							$files_error[] = htmlspecialchars($refs[$i]);
						}
					}
					
					if( count($files_error) > 0 )
					{
						$error = true;
						$msg_error[] = sprintf($lang['Cid_error_in_body'], implode(', ', $files_error));
					}
				}
				
				//
				// Deux newsletters ne peuvent être simultanément en attente d'envoi
				// pour une même liste.
				//
				$sql = "SELECT COUNT(*) AS test
					FROM " . LOG_TABLE . "
					WHERE liste_id = $listdata[liste_id]
						AND log_status = " . STATUS_STANDBY;
				if( !($result = $db->query($sql)) )
				{
					trigger_error('Impossible de tester la présence de newsletter déjà en attente d\'envoi', ERROR);
				}
				
				if( $result->column('test') > 0 )
				{
					$error = true;
					$msg_error[] = $lang['Message']['Twice_sending'];
				}
			}
			
			if( !$error )
			{
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
					
					if( $prev_status == STATUS_MODEL )
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
					$sql_type  = SQL_INSERT;
				}
				else
				{
					$sql_type  = SQL_UPDATE;
					$sql_where = array('log_id' => $tmp_id, 'liste_id' => $listdata['liste_id']);
				}
				
				if( !$db->build($sql_type, LOG_TABLE, $logdata, $sql_where) )
				{
					trigger_error('Impossible de sauvegarder la newsletter', ERROR);
				}
				
				if( $sql_type == SQL_INSERT )
				{
					$tmp_id = $db->lastInsertId();
				}
				
				//
				// Duplication de la newsletter
				//
				if( $duplicate_log )
				{
					$handle_id = $tmp_id;
					$logdata['log_status'] = STATUS_STANDBY;
					
					if( !$db->build(SQL_INSERT, LOG_TABLE, $logdata) )
					{
						trigger_error('Impossible de dupliquer la newsletter', ERROR);
					}
					
					$tmp_id = $db->lastInsertId();
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
					
					while( $row = $result->fetch() )
					{
						switch( SQL_DRIVER )
						{
							case 'mysql':
							case 'mysqli':
								$sql_values[] = '(' . $tmp_id . ', ' . $row['file_id'] . ')';
								break;
							
							default:
								$sqldata = array('log_id' => $tmp_id, 'file_id' => $row['file_id']);
								
								if( !$db->build(SQL_INSERT, LOG_FILES_TABLE, $sqldata) )
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
				$prev_status = $logdata['log_status'];
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
						$message .= '<br /><br />' . sprintf($lang['Click_start_send'], '<a href="' . sessid('./envoi.php?mode=progress&amp;id=' . $logdata['log_id']) . '">', '</a>');
					}
					
					$output->message($message);
				}
			}
		}
		
		//
		// Attachement de fichiers
		//
		if( $mode == 'attach' && !empty($logdata['log_id']) && $auth->check_auth(AUTH_ATTACH, $listdata['liste_id']) )
		{
			$join_file  = ( isset($_FILES['join_file']) ) ? $_FILES['join_file'] : array();
			$local_file = ( !empty($_POST['join_file']) ) ? trim($_POST['join_file']) : '';
			
			$tmp_filename = ( !empty($join_file['tmp_name']) && $join_file['tmp_name'] != 'none' ) ? $join_file['tmp_name'] : $local_file;
			$filename     = ( !empty($join_file['name']) ) ? $join_file['name'] : '';
			$filesize     = ( !empty($join_file['size']) ) ? intval($join_file['size']) : 0;
			$filetype     = ( !empty($join_file['type']) ) ? $join_file['type'] : '';
			$errno_code   = ( !empty($join_file['error']) ) ? intval($join_file['error']) : UPLOAD_ERR_OK;
			$file_id      = ( !empty($_POST['fid']) ) ? intval($_POST['fid']) : 0;
			
			require WA_ROOTDIR . '/includes/class.attach.php';
			
			$attach = new Attach();
			
			if( !empty($file_id) )
			{
				//
				// Attachement d'un fichier utilisé dans une autre newsletter de la liste
				//
				$attach->use_file_exists($file_id, $logdata['log_id'], $error, $msg_error);
			}
			else
			{
				//
				// On a affaire soit à un fichier présent localement, soit à un fichier 
				// distant, soit à un fichier uploadé
				//
				if( !empty($local_file) )
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
		}
		break;
	
	case 'unattach':
		$file_ids = ( !empty($_POST['file_ids']) ) ? (array) $_POST['file_ids'] : array();
		
		if( $auth->check_auth(AUTH_ATTACH, $listdata['liste_id']) && count($file_ids) > 0 )
		{
			//
			// Suppression du fichier joint spécifié
			//
			require WA_ROOTDIR . '/includes/class.attach.php';
			
			$attach = new Attach();
			$attach->delete_joined_files(false, $logdata['log_id'], $file_ids);
			
			//
			// Optimisation des tables
			//
			$db->vacuum(array(LOG_FILES_TABLE, JOINED_FILES_TABLE));
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
		FROM " . JOINED_FILES_TABLE . " AS jf
			INNER JOIN " . LOG_FILES_TABLE . " AS lf ON lf.file_id = jf.file_id
			INNER JOIN " . LOG_TABLE . " AS l ON l.log_id = lf.log_id
				AND l.liste_id = $listdata[liste_id]
		ORDER BY jf.file_real_name ASC";
	if( !($result = $db->query($sql)) )
	{
		trigger_error('Impossible d\'obtenir la liste des fichiers joints', ERROR);
	}
	
	$other_files = $joined_files_id = array();
	
	//
	// On dispatches les données selon que le fichier appartient à la newsletter en cours ou non.
	//
	while( $row = $result->fetch() )
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
	
	foreach( $other_files as $tmp_id => $row )
	{
		if( !in_array($tmp_id, $joined_files_id) )
		{
			$file_box .= sprintf("<option value=\"%d\">%s</option>\n\t", $tmp_id, htmlspecialchars($row['file_real_name']));
		}
	}
	
	if( $file_box != '' )
	{
		$file_box = '<select name="fid"><option value="0">' . $lang['File_on_server'] . '</option>' . $file_box . '</select>';
	}
	
	unset($other_files, $joined_files_id);
}

//
// Envois des emails
//
if( $mode == 'progress' )
{
	if( !$auth->check_auth(AUTH_SEND, $listdata['liste_id']) )
	{
		$output->message('Not_auth_send');
	}
	
	require WA_ROOTDIR . '/includes/engine_send.php';
	
	//
	// On règle le script pour ignorer une déconnexion du client et 
	// poursuivre l'envoi du flot d'emails jusqu'à son terme. 
	//
	@ignore_user_abort(true);
	
	//
	// On augmente également le temps d'exécution maximal du script. 
	//
	// Certains hébergeurs désactivent pour des raisons évidentes cette fonction
	// Si c'est votre cas, vous êtes mal barré
	//
	@set_time_limit(1200);
	
	//
	// On lance l'envoi
	//
	$message = launch_sending($listdata, $logdata);
	
	$output->message(nl2br($message));
}

$subject   = htmlspecialchars($logdata['log_subject']);
$body_text = htmlspecialchars($logdata['log_body_text'], ENT_NOQUOTES);
$body_html = htmlspecialchars($logdata['log_body_html'], ENT_NOQUOTES);

$output->addLink('section', './envoi.php?mode=load', $lang['Load_log']);
$output->addLink('section', './envoi.php?mode=progress', $lang['List_send']);
$output->addScript(WA_ROOTDIR . '/templates/admin/editor.js');

$output->addHiddenField('id',          $logdata['log_id']);
$output->addHiddenField('prev_status', $prev_status);
$output->addHiddenField('sessid',      $session->session_id);

$output->page_header();

$output->set_filenames(array(
	'body' => 'send_body.tpl'
));

$output->assign_vars(array(
	'L_EXPLAIN'               => nl2br(sprintf($lang['Explain']['send'],'<a href="' . WA_ROOTDIR . '/docs/faq.' . $lang['CONTENT_LANG'] . '.html#p27">', '</a>')),	
	'L_LOAD_LOG'              => $lang['Load_log'],
	'L_LIST_SEND'             => $lang['List_send'],
	'L_DEST'                  => $lang['Dest'],
	'L_SUBJECT'               => $lang['Log_subject'],
	'L_STATUS'                => $lang['Status'],
	'L_STATUS_WRITING'        => $lang['Status_writing'],
	'L_STATUS_MODEL'          => $lang['Status_model'],
	'L_CC_ADMIN'              => $lang['Receive_copy'],
	'L_CC_ADMIN_TITLE'        => htmlspecialchars($lang['Receive_copy_title']),
	'L_SEND_BUTTON'           => $lang['Button']['send'],
	'L_SAVE_BUTTON'           => $lang['Button']['save'],
	'L_DELETE_BUTTON'         => $lang['Button']['delete'],
	'L_PREVIEW_BUTTON'        => str_replace('\'', '\\\'', $lang['Button']['preview']),
	'L_ADDLINK_BUTTON'        => str_replace('\'', '\\\'', $lang['Button']['links']),
	'L_YES'                   => $lang['Yes'],
	'L_NO'                    => $lang['No'],
	
	'S_DEST'                  => $listdata['liste_name'],
	'S_SUBJECT'               => $subject,
	'SELECTED_STATUS_WRITING' => ( $logdata['log_status'] == STATUS_WRITING ) ? ' selected="selected"' : '',
	'SELECTED_STATUS_MODEL'   => ( $logdata['log_status'] == STATUS_MODEL ) ? ' selected="selected"' : '',
	'SELECTED_CC_ADMIN_ON'    => ( $listdata['cc_admin'] ) ? ' selected="selected"' : '',
	'SELECTED_CC_ADMIN_OFF'   => ( !$listdata['cc_admin'] ) ? ' selected="selected"' : '',
	
	'S_ENCTYPE'               => ( FILE_UPLOADS_ON ) ? 'multipart/form-data' : 'application/x-www-form-urlencoded', 
	'S_HIDDEN_FIELDS'         => $output->getHiddenFields()
));

if( $listdata['liste_format'] != FORMAT_HTML )
{
	$output->assign_block_vars('formulaire', array(
		'L_TITLE'         => $lang['Log_in_text'],
		'L_EXPLAIN_BODY'  => nl2br($lang['Explain']['text']),
		
		'S_TEXTAREA_NAME' => 'body_text',
		'S_BODY'          => $body_text,
		'S_FORMAT'        => FORMAT_TEXTE
	));
}

if( $listdata['liste_format'] != FORMAT_TEXTE )
{
	$output->assign_block_vars('formulaire', array(
		'L_TITLE'         => $lang['Log_in_html'],
		'L_EXPLAIN_BODY'  => nl2br($lang['Explain']['html']),
		
		'S_TEXTAREA_NAME' => 'body_html',
		'S_BODY'          => $body_html,
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
		$output->assign_block_vars('joined_files.upload_input', array(
			'L_MAXIMUM_SIZE' => sprintf($lang['Maximum_size'], formateSize(MAX_FILE_SIZE)),
			'MAX_FILE_SIZE'  => MAX_FILE_SIZE
		));
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