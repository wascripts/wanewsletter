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

//
// Compression éventuelle des données et réglage du mime-type en conséquence
//
function compress_filedata(&$filename, &$mime_type, $contents, $compress)
{
	switch( $compress )
	{
		case 'zip':
			$mime_type = 'application/zip';
			$zip = new zipfile;
			$zip->addFile($contents, $filename, time());
			$contents  = $zip->file();
			$filename .= '.zip';
			break;
		
		case 'gzip':
			$mime_type = 'application/x-gzip-compressed';
			$contents  = gzencode($contents);
			$filename .= '.gz';
			break;
		
		case 'bz2':
			$mime_type = 'application/x-bzip';
			$contents  = bzcompress($contents);
			$filename .= '.bz2';
			break;
	}
	
	return $contents;
}

//
// Lecture et décompression éventuelle des données
//
function decompress_filedata($filename, $file_ext)
{
	if( $file_ext != 'zip' )
	{
		switch( $file_ext )
		{
			case 'gz':
				$open  = 'gzopen';
				$eof   = 'gzeof';
				$gets  = 'gzgets';
				$close = 'gzclose';
				break;
			
			case 'bz2':
			case 'txt':
			case 'sql':
				$open  = 'fopen';
				$eof   = 'feof';
				$gets  = 'fgets';
				$close = 'fclose';
				break;
		}
		
		if( !($fp = @$open($filename, 'rb')) )
		{
			trigger_error('Failed_open_file', ERROR);
		}
		
		$data = '';
		while( !@$eof($fp) )
		{
			$data .= $gets($fp, 1024);
		}
		$close($fp);
		
		if( $file_ext == 'bz2' )
		{
			$data = bzdecompress($data);
		}
	}
	else
	{
		if( !($zip = zip_open($filename)) )
		{
			trigger_error('Failed_open_file', ERROR);
		}
		
		$zip_entry = zip_read($zip);
		if( !zip_entry_open($zip, $zip_entry, 'rb') )
		{
			trigger_error('Failed_open_file', ERROR);
		}
		
		$data = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
		zip_entry_close($zip_entry);
		zip_close($zip);
	}
	
	return $data;
}

$mode     = ( !empty($_REQUEST['mode']) ) ? $_REQUEST['mode'] : '';
$format   = ( !empty($_POST['format']) ) ? intval($_POST['format']) : FORMAT_TEXTE;
$eformat  = ( !empty($_POST['eformat']) ) ? $_POST['eformat'] : '';
$glue     = ( !empty($_POST['glue']) ) ? trim($_POST['glue']) : '';
$action   = ( !empty($_POST['action']) ) ? $_POST['action'] : 'download';
$compress = ( !empty($_POST['compress']) ) ? $_POST['compress'] : 'none';

$file_local  = ( !empty($_POST['file_local']) ) ? trim($_POST['file_local']) : '';
$file_upload = ( !empty($_FILES['file_upload']) ) ? $_FILES['file_upload'] : array();

switch( $mode )
{
	case 'export':
		$auth_type = AUTH_EXPORT;
		break;
	
	case 'import':
		$auth_type = AUTH_IMPORT;
		break;
	
	case 'ban':
		$auth_type = AUTH_BAN;
		break;
	
	case 'backup':
		if( DATABASE == 'sqlite' )
		{
			trigger_error(sprintf($lang['Message']['SQLite_backup'], wa_realpath($dbhost)), MESSAGE);
		}
	case 'restore':
		if( DATABASE == 'sqlite' )
		{
			trigger_error(sprintf($lang['Message']['SQLite_restore'], wa_realpath($dbhost)), MESSAGE);
		}
		//
		// Les modules de sauvegarde et restauration 
		// supportent actuellement MySQL 3.x ou 4.x, et PostgreSQL
		//
		if( !class_exists('sql_backup') )
		{
			trigger_error('Database_unsupported', MESSAGE);
		}
		
	case 'attach':
		if( $admindata['admin_level'] != ADMIN )
		{
			$output->redirect('./index.php', 4);
			
			$message  = $lang['Message']['Not_authorized'];
			$message .= '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . sessid('./index.php') . '">', '</a>');
			trigger_error($message, MESSAGE);
		}
		
	case 'generator':
		$auth_type = AUTH_VIEW;
		break;
	
	default:
		$mode = '';
		$auth_type = AUTH_VIEW;
		break;
}

$url_page  = './tools.php';
$url_page .= ( $mode != '' ) ? '?mode=' . $mode : '';

if( $mode != 'backup' && $mode != 'restore' && !$admindata['session_liste'] )
{
	$output->build_listbox($auth_type, true, $url_page);
}
else if( $admindata['session_liste'] )
{
	if( !$auth->check_auth($auth_type, $admindata['session_liste']) )
	{
		trigger_error('Not_' . $auth->auth_ary[$auth_type], MESSAGE);
	}
	
	$listdata = $auth->listdata[$admindata['session_liste']];
}

if( !isset($_POST['submit']) )
{
	if( $mode != 'backup' && $mode != 'restore' )
	{
		$output->build_listbox($auth_type, false, $url_page);
	}
	
	$tools_ary = array('export', 'import', 'ban', 'generator');
	
	if( $admindata['admin_level'] == ADMIN )
	{
		array_push($tools_ary, 'attach', 'backup', 'restore');
	}
	
	$tools_box = '<select id="mode" name="mode">';
	foreach( $tools_ary AS $tool_name )
	{
		$selected = ( $mode == $tool_name ) ? ' selected="selected"' : '';
		$tools_box .= '<option value="' . $tool_name . '"' . $selected . '>' . $lang['Title'][$tool_name] . '</option>';
	}
	$tools_box .= '</select>';
	
	$output->page_header();
	
	if( $session->sessid_url != '' )
	{
		$output->addHiddenField('sessid', $session->session_id);
	}
	
	$output->set_filenames(array(
		'body' => 'tools_body.tpl'
	));
	
	$output->assign_vars(array(
		'L_TITLE'        => $lang['Title']['tools'],
		'L_EXPLAIN'      => nl2br($lang['Explain']['tools']),
		'L_SELECT_TOOL'  => $lang['Select_tool'],
		'L_VALID_BUTTON' => $lang['Button']['valid'],
		
		'S_TOOLS_BOX'    => $tools_box,
		'S_TOOLS_HIDDEN_FIELDS' => $output->getHiddenFields()
	));
}

//
// On vérifie la présence des extensions nécessaires pour les différents formats de fichiers proposés
//
$zziplib_loaded = extension_loaded('zip');
$zlib_loaded    = extension_loaded('zlib');
$bzip2_loaded   = extension_loaded('bz2');

if( $zlib_loaded )
{
	require WA_ROOTDIR . '/includes/zip.lib.php';
}

if( WA_USER_OS == 'win' )
{
	$eol = "\r\n";
}
else if( WA_USER_OS == 'mac' )
{
	$eol = "\r";
}
else
{
	$eol = "\n";
}

define('WA_EOL', $eol);

//
// On augmente le temps d'exécution du script 
// Certains hébergeurs empèchent pour des raisons évidentes cette possibilité
// Si c'est votre cas, vous êtes mal barré 
//
@set_time_limit(1200);

switch( $mode )
{
	case 'export':
		if( isset($_POST['submit']) )
		{
			if( $action == 'store' && !is_writable(WA_TMPDIR) )
			{
				trigger_error('tmp_dir_not_writable', MESSAGE);
			}
			
			if( $listdata['liste_format'] != FORMAT_MULTIPLE )
			{
				$format = $listdata['liste_format'];
			}
			
			$glue = ( $glue != '' ) ? $glue : WA_EOL;
			
			$sql = "SELECT a.abo_email 
				FROM " . ABONNES_TABLE . " AS a
					INNER JOIN " . ABO_LISTE_TABLE . " AS al
					ON al.abo_id = a.abo_id
						AND al.liste_id  = $listdata[liste_id]
						AND al.format    = $format
						AND al.confirmed = " . SUBSCRIBE_CONFIRMED . "
				WHERE a.abo_status = " . ABO_ACTIF;
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible d\'obtenir la liste des emails à exporter', ERROR);
			}
			
			$contents = '';
			while( $row = $db->fetch_array($result) )
			{
				if( $eformat == 'xml' )
				{
					$contents .= "\t<email>".$row['abo_email']."</email>\n";
				}
				else
				{
					$contents .= ( $contents != '' ) ? $glue : '';
					$contents .= $row['abo_email'];
				}
			}
			$db->free_result($result);
			
			if( $eformat == 'xml' )
			{
				$label_format = ( $format == FORMAT_HTML ) ? 'HTML' : 'text';
				
				$contents  = '<' . '?xml version="1.0"?' . ">\n"
					. "<!-- Date : " . gmdate('d/m/Y H:i:s') . " GMT \xe2\x80\x93 Format : $label_format -->\n"
					. "<liste>\n" . $contents . "</liste>";
				$mime_type = 'application/xml';
				$ext = 'xml';
			}
			else
			{
				$mime_type = 'text/plain';
				$ext = 'txt';
			}
			
			$filename = sprintf('wa_export_%d.%s', $admindata['session_liste'], $ext);
			
			//
			// Préparation des données selon l'option demandée 
			//
			$contents = compress_filedata($filename, $mime_type, $contents, $compress);
			
			if( $action == 'download' )
			{
				include WA_ROOTDIR . '/includes/class.attach.php';
				
				Attach::send_file($filename, $mime_type, $contents);
			}
			else
			{
				if( !($fw = @fopen(WA_TMPDIR . '/' . $filename, 'wb')) )
				{
					trigger_error('Impossible d\'écrire le fichier de sauvegarde', ERROR);
				}
				
				fwrite($fw, $contents);
				fclose($fw);
				
				trigger_error('Success_export', MESSAGE);
			}
		}
		
		$output->addHiddenField('sessid', $session->session_id);
		
		$output->set_filenames(array(
			'tool_body' => 'export_body.tpl'
		));
		
		$output->assign_vars(array(
			'L_TITLE_EXPORT'    => $lang['Title']['export'],
			'L_EXPLAIN_EXPORT'  => nl2br($lang['Explain']['export']),
			'L_EXPORT_FORMAT'   => $lang['Export_format'],
			'L_PLAIN_TEXT'      => $lang['Plain_text'],
			'L_GLUE'            => $lang['Char_glue'],
			'L_ACTION'          => $lang['File_action'],
			'L_DOWNLOAD'        => $lang['Download_action'],
			'L_STORE_ON_SERVER' => $lang['Store_action'],
			'L_VALID_BUTTON'    => $lang['Button']['valid'],
			'L_RESET_BUTTON'    => $lang['Button']['reset'],
			
			'S_HIDDEN_FIELDS'   => $output->getHiddenFields()
		));
		
		if( $zlib_loaded || $bzip2_loaded )
		{
			$output->assign_block_vars('compress_option', array(
				'L_COMPRESS' => $lang['Compress'],
				'L_NO'       => $lang['No']
			)); 
			
			if( $zlib_loaded )
			{
				$output->assign_block_vars('compress_option.gzip_compress', array());
			}
			
			if( $bzip2_loaded )
			{
				$output->assign_block_vars('compress_option.bz2_compress', array());
			}
		}
		
		if( $listdata['liste_format'] == FORMAT_MULTIPLE )
		{
			require WA_ROOTDIR . '/includes/functions.box.php';
			
			$output->assign_block_vars('format_box', array(
				'L_FORMAT'   => $lang['Format_to_export'],
				'FORMAT_BOX' => format_box('format')
			));
		}
		
		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;
	
	case 'import':
		if( isset($_POST['submit']) )
		{
			$list_email = ( !empty($_POST['list_email']) ) ? trim($_POST['list_email']) : '';
			$list_tmp   = '';
			
			//
			// Import via upload ou fichier local ? 
			//
			if( !empty($file_local) || !empty($file_upload['name']) )
			{
				$unlink = false;
				
				if( !empty($file_local) )
				{
					$tmp_filename = wa_realpath(WA_ROOTDIR . '/' . str_replace('\\', '/', $file_local));
					$filename     = $file_local;
					
					if( !file_exists($tmp_filename) )
					{
						$output->redirect('./tools.php?mode=import', 4);
						
						$message  = sprintf($lang['Message']['Error_local'], htmlspecialchars($filename));
						$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./tools.php?mode=import') . '">', '</a>');
						trigger_error($message, MESSAGE);
					}
				}
				else
				{
					$tmp_filename = $file_upload['tmp_name'];
					$filename     = $file_upload['name'];
					
					if( !isset($file_upload['error']) && empty($tmp_filename) )
					{
						$file_upload['error'] = -1;
					}
					
					if( $file_upload['error'] != UPLOAD_ERR_OK )
					{
						if( isset($lang['Message']['Upload_error_'.$file_upload['error']]) )
						{
							$upload_error = 'Upload_error_'.$file_upload['error'];
						}
						else
						{
							$upload_error = 'Upload_error_5';
						}
						
						trigger_error($upload_error, MESSAGE);
					}
					
					//
					// Si nous avons un accés restreint à cause de open_basedir, le fichier doit être déplacé 
					// vers le dossier des fichiers temporaires du script pour être accessible en lecture
					//
					if( OPEN_BASEDIR_RESTRICTION )
					{
						$unlink = true;
						$tmp_filename = wa_realpath(WA_TMPDIR . '/' . $filename);
						
						if( !move_uploaded_file($file_upload['tmp_name'], $tmp_filename) )
						{
							trigger_error('Upload_error_5', MESSAGE);
						}
					}
				}
				
				if( !preg_match('/\.(txt|zip|gz|bz2)$/i', $filename, $match) )
				{
					trigger_error('Bad_file_type', MESSAGE);
				}
				
				$file_ext = $match[1];
				
				if( ( !$zziplib_loaded && $file_ext == 'zip' ) || ( !$zlib_loaded && $file_ext == 'gz' ) || ( !$bzip2_loaded && $file_ext == 'bz2' ) )
				{
					trigger_error('Compress_unsupported', MESSAGE);
				}
				
				$list_tmp = decompress_filedata($tmp_filename, $file_ext);
				
				//
				// S'il y a une restriction d'accés par l'open_basedir, et que c'est un fichier uploadé, 
				// nous avons dù le déplacer dans le dossier tmp/ du script, on le supprime.
				//
				if( $unlink )
				{
					require WA_ROOTDIR . '/includes/class.attach.php';
					
					Attach::remove_file($tmp_filename);
				}
			}
			
			//
			// Mode importation via le textarea 
			//
			else if( strlen($list_email) > 5 )
			{
				$list_tmp = $list_email;
			}
			
			// 
			// Aucun fichier d'import reçu et textarea vide 
			//
			else
			{
				$output->redirect('./tools.php?mode=import', 4);
				
				$message  = $lang['Message']['No_data_received'];
				$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./tools.php?mode=import') . '">', '</a>');
				trigger_error($message, MESSAGE);
			}
			
			require WA_ROOTDIR . '/includes/functions.validate.php'; 
			
			if( $glue == '' )
			{
				$list_tmp = preg_replace("/\r\n?/", "\n", $list_tmp);
				$glue = "\n";
			}
			
			if( $listdata['liste_format'] != FORMAT_MULTIPLE )
			{
				$format = $listdata['liste_format'];
			}
			
			$cpt = 0;
			$report = '';
			$emails = array_unique(array_map('trim', explode($glue, $list_tmp)));
			$current_time = time();
			
			fake_header(false);
			
			$db->query("DROP INDEX abo_email_idx ON " . ABONNES_TABLE);
			$db->query("DROP INDEX abo_status_idx ON " . ABONNES_TABLE);
			
			foreach( $emails AS $email )
			{
				// on désactive le check_mx si cette option est valide, cela prendrait trop de temps
				$result = check_email($email, $listdata['liste_id'], 'inscription', true);
				
				//
				// Si l'email est ok après vérification, on commence l'insertion, 
				// autrement, on ajoute au rapport d'erreur
				//
				if( !$result['error'] )
				{
					$db->transaction(START_TRC);
					
					if( empty($result['abodata']) )
					{
						$sql_data = array();
						$sql_data['abo_email']         = $email;
						$sql_data['abo_register_key']  = generate_key();
						$sql_data['abo_register_date'] = $current_time;
						$sql_data['abo_status']        = ABO_ACTIF;
						
						if( !$db->query_build('INSERT', ABONNES_TABLE, $sql_data) )
						{
							trigger_error('Impossible d\'ajouter un nouvel abonné dans la table des abonnés', ERROR);
						}
						
						$abo_id = $db->next_id();
					}
					else
					{
						$abo_id = $result['abodata']['abo_id'];
						
						// Déja inscrit à cette liste, mais n'a pas encore confirmé son inscription, on ignore
						if( isset($result['abodata']['confirmed']) )
						{
							$report .= sprintf('%s : %s%s', $email, $lang['Message']['Reg_not_confirmed2'], WA_EOL);
						}
					}
					
					$sql = "INSERT INTO " . ABO_LISTE_TABLE . " (abo_id, liste_id, format, confirmed, register_date) 
						VALUES($abo_id, $listdata[liste_id], $format, " . SUBSCRIBE_CONFIRMED . ", $current_time)";
					if( !$db->query($sql) )
					{
						trigger_error('Impossible d\'insérer une nouvelle entrée dans la table abo_liste', ERROR);
					}
					
					$db->transaction(END_TRC);
				}
				else
				{
					$report .= sprintf('%s : %s%s', $email, $result['message'], WA_EOL);
				}
				
				fake_header(true);
				
				if( $cpt >= MAX_IMPORT )
				{
					break;
				}
				
				$cpt++;
			}
			
			$db->query("CREATE UNIQUE INDEX abo_email_idx ON " . ABONNES_TABLE . " (abo_email)");
			$db->query("CREATE INDEX abo_status_idx ON " . ABONNES_TABLE . " (abo_status)");
			
			//
			// Selon que des emails ont été refusés ou pas, affichage du message correspondant 
			// et écriture éventuelle du rapport d'erreur 
			//
			if( $report != '' )
			{
				if( is_writable(WA_TMPDIR) && ($fw = fopen(WA_TMPDIR . '/wa_import_report.txt', 'w')) )
				{
					$report_str  = '#' . WA_EOL;
					$report_str .= '# Rapport des adresses emails refusées / Bad address email report' . WA_EOL;
					$report_str .= '#' . WA_EOL;
					$report_str .= WA_EOL;
					$report_str .= $report . WA_EOL;
					$report_str .= '# END' . WA_EOL;
					
					fwrite($fw, $report_str);
					fclose($fw);
					
					$message = nl2br(sprintf($lang['Message']['Success_import3'], '<a href="' . WA_TMPDIR . '/wa_import_report.txt">', '</a>'));
				}
				else
				{
					$message = $lang['Message']['Success_import2'];
				}
			}
			else
			{
				$message = $lang['Message']['Success_import'];
			}
			
			trigger_error($message, MESSAGE);
		}
		
		$output->addHiddenField('sessid', $session->session_id);
		
		$output->set_filenames(array(
			'tool_body' => 'import_body.tpl'
		));
		
		$output->assign_vars(array(
			'L_TITLE_IMPORT'   => $lang['Title']['import'],
			'L_EXPLAIN_IMPORT' => nl2br(sprintf($lang['Explain']['import'], MAX_IMPORT, '<a href="' . WA_ROOTDIR . '/docs/faq.' . $lang['CONTENT_LANG'] . '.html#4">', '</a>')),
			'L_GLUE'           => $lang['Char_glue'],
			'L_FILE_LOCAL'     => $lang['File_local'],
			'L_VALID_BUTTON'   => $lang['Button']['valid'],
			'L_RESET_BUTTON'   => $lang['Button']['reset'],
			
			'S_HIDDEN_FIELDS'  => $output->getHiddenFields(),
			'S_ENCTYPE'        => ( FILE_UPLOADS_ON ) ? 'multipart/form-data' : 'application/x-www-form-urlencoded'
		));
		
		if( $listdata['liste_format'] == FORMAT_MULTIPLE )
		{
			require WA_ROOTDIR . '/includes/functions.box.php';
			
			$output->assign_block_vars('format_box', array(
				'L_FORMAT'   => $lang['Format_to_import'],
				'FORMAT_BOX' => format_box('format')
			));
		}
		
		if( FILE_UPLOADS_ON )
		{
			//
			// L'upload est disponible sur le serveur
			// Affichage du champ file pour importation
			//
			$output->assign_block_vars('upload_file', array(
				'L_FILE_UPLOAD' => $lang['File_upload']
			));
		}
		
		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;
	
	case 'ban':
		if( isset($_POST['submit']) )
		{
			$pattern       = ( !empty($_POST['pattern']) ) ? trim(str_replace('\\\'', '', $_POST['pattern'])) : '';
			$unban_list_id = ( !empty($_POST['unban_list_id']) ) ? array_map('intval', $_POST['unban_list_id']) : array();
			
			if( $pattern != '' )
			{
				$pattern_ary = array_map('trim', explode(',', $pattern));
				$sql_values  = array();
				
				foreach( $pattern_ary AS $pattern )
				{
					switch( DATABASE )
					{
						case 'mysql':
						case 'mysql4':
							$sql_values[] = "($listdata[liste_id], '" . $db->escape($pattern) . "')";
							break;
						
						default:
							$sql = "INSERT INTO " . BANLIST_TABLE . " (liste_id, ban_email) 
								VALUES($listdata[liste_id], '" . $db->escape($pattern) . "')";
							if( !$db->query($sql) )
							{
								trigger_error('Impossible de mettre à jour la table des bannis', ERROR);
							}
							break;
					}
				}
				
				if( count($sql_values) > 0 )
				{
					$sql = "INSERT INTO " . BANLIST_TABLE . " (liste_id, ban_email) 
						VALUES " . implode(', ', $sql_values);
					if( !$db->query($sql) )
					{
						trigger_error('Impossible d\'insérer les données dans la table des bannis', ERROR);
					}
				}
			}
			
			if( count($unban_list_id) > 0 )
			{
				$sql = "DELETE FROM " . BANLIST_TABLE . " 
					WHERE ban_id IN (" . implode(', ', $unban_list_id) . ")";
				if( !$db->query($sql) )
				{
					trigger_error('Impossible de supprimer les emails bannis sélectionnés', ERROR);
				}
				
				//
				// Optimisation des tables
				//
				$db->check(BANLIST_TABLE);
			}
			
			$output->redirect('./tools.php?mode=ban', 4);
			
			$message  = $lang['Message']['Success_modif'];
			$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./tools.php?mode=ban') . '">', '</a>');
			$message .= '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . sessid('./index.php') . '">', '</a>');
			trigger_error($message, MESSAGE);
		}
		
		$sql = "SELECT ban_id, ban_email 
			FROM " . BANLIST_TABLE . " 
			WHERE liste_id = " . $listdata['liste_id'];
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir la liste des masques de bannissement', ERROR);
		}
		
		$unban_email_box = '<select id="unban_list_id" name="unban_list_id[]" multiple="multiple" size="10">';
		if( $row = $db->fetch_array($result) )
		{
			do
			{		
				$unban_email_box .= sprintf("<option value=\"%d\">%s</option>\n\t", $row['ban_id'], $row['ban_email']);
			}
			while( $row = $db->fetch_array($result) );
		}
		else
		{
			$unban_email_box .= '<option value="0">' . $lang['No_email_banned'] . '</option>';
		}
		$unban_email_box .= '</select>';
		
		$output->addHiddenField('sessid', $session->session_id);
		
		$output->set_filenames( array(
			'tool_body' => 'ban_list_body.tpl'
		));
		
		$output->assign_vars(array(
			'L_TITLE_BAN'     => $lang['Title']['ban'],
			'L_EXPLAIN_BAN'   => nl2br($lang['Explain']['ban']),
			'L_EXPLAIN_UNBAN' => nl2br($lang['Explain']['unban']),
			'L_BAN_EMAIL'     => $lang['Ban_email'],
			'L_UNBAN_EMAIL'   => $lang['Unban_email'],
			'L_VALID_BUTTON'  => $lang['Button']['valid'],
			'L_RESET_BUTTON'  => $lang['Button']['reset'],
			
			'UNBAN_EMAIL_BOX' => $unban_email_box,
			'S_HIDDEN_FIELDS' => $output->getHiddenFields()
		));
		
		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;
	
	case 'attach':
		if( isset($_POST['submit']) )
		{
			$ext_list    = ( !empty($_POST['ext_list']) ) ? trim($_POST['ext_list']) : '';
			$ext_list_id = ( !empty($_POST['ext_list_id']) ) ? array_map('intval', $_POST['ext_list_id']) : array();
			
			if( $ext_list != '' )
			{
				$ext_ary	= array_map('trim', explode(',', $ext_list));
				$sql_values = array();
				
				foreach( $ext_ary AS $ext )
				{
					$ext = strtolower($ext);
					
					if( preg_match('/^[\w_-]+$/', $ext) )
					{
						switch( DATABASE )
						{
							case 'mysql':
							case 'mysql4':
								$sql_values[] = "($listdata[liste_id], '$ext')";
								break;
							
							default:
								$sql = "INSERT INTO " . FORBIDDEN_EXT_TABLE . " (liste_id, fe_ext) 
									VALUES($listdata[liste_id], '$ext')";
								if( !$db->query($sql) )
								{
									trigger_error('Impossible de mettre à jour la table des extensions interdites', ERROR);
								}
								break;
						}
					}
				}
				
				if( count($sql_values) > 0 )
				{
					$sql = "INSERT INTO " . FORBIDDEN_EXT_TABLE . " (liste_id, fe_ext) 
						VALUES " . implode(', ', $sql_values);
					if( !$db->query($sql) )
					{
						trigger_error('Impossible de mettre à jour la table des extensions interdites', ERROR);
					}
				}
			}
			
			if( count($ext_list_id) > 0 )
			{
				$sql = "DELETE FROM " . FORBIDDEN_EXT_TABLE . " 
					WHERE fe_id IN (" . implode(', ', $ext_list_id) . ")";
				if( !$db->query($sql) )
				{
					trigger_error('Impossible de supprimer les extensions interdites sélectionnées', ERROR);
				}
				
				//
				// Optimisation des tables
				//
				$db->check(FORBIDDEN_EXT_TABLE);
			}
			
			$output->redirect('./tools.php?mode=attach', 4);
			
			$message  = $lang['Message']['Success_modif'];
			$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./tools.php?mode=attach') . '">', '</a>');
			$message .= '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . sessid('./index.php') . '">', '</a>');
			trigger_error($message, MESSAGE);
		}
		
		$sql = "SELECT fe_id, fe_ext 
			FROM " . FORBIDDEN_EXT_TABLE . " 
			WHERE liste_id = " . $listdata['liste_id'];
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir la liste des extensions interdites', ERROR);
		}
		
		$reallow_ext_box = '<select id="ext_list_id" name="ext_list_id[]" multiple="multiple" size="10">';
		if( $row = $db->fetch_array($result) )
		{
			do
			{		
				$reallow_ext_box .= sprintf("<option value=\"%d\">%s</option>\n\t", $row['fe_id'], $row['fe_ext']);
			}
			while( $row = $db->fetch_array($result) );
		}
		else
		{
			$reallow_ext_box .= '<option value="0">' . $lang['No_forbidden_ext'] . '</option>';
		}
		$reallow_ext_box .= '</select>';
		
		$output->addHiddenField('sessid', $session->session_id);
		
		$output->set_filenames( array(
			'tool_body' => 'forbidden_ext_body.tpl'
		));
		
		$output->assign_vars(array(
			'L_TITLE_EXT'          => $lang['Title']['attach'],
			'L_EXPLAIN_TO_FORBID'  => nl2br($lang['Explain']['forbid_ext']),
			'L_EXPLAIN_TO_REALLOW' => nl2br($lang['Explain']['reallow_ext']),
			'L_FORBID_EXT'         => $lang['Forbid_ext'],
			'L_REALLOW_EXT'        => $lang['Reallow_ext'],
			'L_VALID_BUTTON'       => $lang['Button']['valid'],
			'L_RESET_BUTTON'       => $lang['Button']['reset'],
			
			'REALLOW_EXT_BOX'      => $reallow_ext_box,
			'S_HIDDEN_FIELDS'      => $output->getHiddenFields()
		));
		
		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;
	
	case 'backup':
		$tables_wa = array(
			ABO_LISTE_TABLE, ABONNES_TABLE, ADMIN_TABLE, AUTH_ADMIN_TABLE, BANLIST_TABLE, CONFIG_TABLE, 
			JOINED_FILES_TABLE, FORBIDDEN_EXT_TABLE, LISTE_TABLE, LOG_TABLE, LOG_FILES_TABLE, SESSIONS_TABLE
		);
		
		$tables      = array();
		$tables_plus = ( !empty($_POST['tables_plus']) ) ? array_map('trim', $_POST['tables_plus']) : array();
		$backup_type = ( isset($_POST['backup_type']) ) ? intval($_POST['backup_type']) : 0;
		$drop_option = ( !empty($_POST['drop_option']) ) ? true : false;
		
		$backup = new sql_backup();
		$backup->eol = WA_EOL;
		$tables_ary  = $backup->get_tables($dbname);
		
		foreach( $tables_ary AS $tablename => $tabletype )
		{
			if( !isset($_POST['submit']) )
			{
				if( !in_array($tablename, $tables_wa) )
				{
					$tables_plus[] = $tablename;
				}
			}
			else
			{
				if( in_array($tablename, $tables_wa) || in_array($tablename, $tables_plus) )
				{
					$tables[] = array('name' => $tablename, 'type' => $tabletype);
				}
			}
		}
		
		if( isset($_POST['submit']) )
		{
			if( $action == 'store' && !is_writable(WA_TMPDIR) )
			{
				trigger_error('tmp_dir_not_writable', MESSAGE);
			}
			
			//
			// Lancement de la sauvegarde. Pour commencer, l'entête du fichier sql 
			//
			$contents = $backup->header($dbhost, $dbname, 'WAnewsletter ' . $nl_config['version']);
			
			fake_header(false);
			
			foreach( $tables AS $tabledata )
			{
				if( $backup_type != 2 )// save complète ou structure uniquement
				{
					$contents .= $backup->get_table_structure($tabledata, $drop_option);
				}
				
				if( $backup_type != 1 )// save complète ou données uniquement
				{
					$contents .= $backup->get_table_data($tabledata['name']);
				}
				
				$contents .= WA_EOL . WA_EOL;
				
				fake_header(true);
			}
			
			$filename  = 'wanewsletter_backup.sql';
			$mime_type = 'text/plain';
			
			//
			// Préparation des données selon l'option demandée 
			//
			$contents = compress_filedata($filename, $mime_type, $contents, $compress);
			
			if( $action == 'download' )
			{
				include WA_ROOTDIR . '/includes/class.attach.php';
				
				Attach::send_file($filename, $mime_type, $contents);
			}
			else
			{
				if( !($fw = @fopen(WA_TMPDIR . '/' . $filename, 'wb')) )
				{
					trigger_error('Impossible d\'écrire le fichier de sauvegarde', ERROR);
				}
				
				fwrite($fw, $contents);
				fclose($fw);
				
				trigger_error('Success_backup', MESSAGE);
			}
		}
		
		$output->addHiddenField('sessid', $session->session_id);
		
		$output->set_filenames(array(
			'tool_body' => 'backup_body.tpl'
		));
		
		$output->assign_vars(array(
			'L_TITLE_BACKUP'    => $lang['Title']['backup'],
			'L_EXPLAIN_BACKUP'  => nl2br($lang['Explain']['backup']),
			'L_BACKUP_TYPE'     => $lang['Backup_type'],
			'L_FULL'            => $lang['Backup_full'],
			'L_STRUCTURE'       => $lang['Backup_structure'],
			'L_DATA'            => $lang['Backup_data'],
			'L_DROP_OPTION'     => $lang['Drop_option'],
			'L_ACTION'          => $lang['File_action'],
			'L_DOWNLOAD'        => $lang['Download_action'],
			'L_STORE_ON_SERVER' => $lang['Store_action'],
			'L_YES'             => $lang['Yes'],
			'L_NO'              => $lang['No'],
			'L_VALID_BUTTON'    => $lang['Button']['valid'],
			'L_RESET_BUTTON'    => $lang['Button']['reset'],
			
			'S_HIDDEN_FIELDS'   => $output->getHiddenFields()
		));
		
		if( $total_tables = count($tables_plus) )
		{
			if( $total_tables > 10 )
			{
				$total_tables = 10;
			}
			else if( $total_tables < 5 )
			{
				$total_tables = 5;
			}
			
			$tables_box = '<select id="tables_plus" name="tables_plus[]" multiple="multiple" size="' . $total_tables . '">';
			foreach( $tables_plus AS $table_name )
			{
				$tables_box .= sprintf("<option value=\"%1\$s\">%1\$s</option>\n\t", $table_name);
			}
			$tables_box .= '</select>';
			
			$output->assign_block_vars('tables_box', array(
				'L_ADDITIONAL_TABLES' => $lang['Additionnal_tables'],
				'S_TABLES_BOX'        => $tables_box
			));
		}
		
		if( $zlib_loaded || $bzip2_loaded )
		{
			$output->assign_block_vars('compress_option', array(
				'L_COMPRESS' => $lang['Compress']
			));
			
			if( $zlib_loaded )
			{
				$output->assign_block_vars('compress_option.gzip_compress', array());
			}
			
			if( $bzip2_loaded )
			{
				$output->assign_block_vars('compress_option.bz2_compress', array());
			}
		}
		
		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;
	
	case 'restore':
		if( isset($_POST['submit']) )
		{
			//
			// On règle le script pour ignorer une déconnexion du client et mener 
			// la restauration à son terme
			//
			@ignore_user_abort(true);
			
			//
			// Import via upload ou fichier local ? 
			//
			if( !empty($file_local) || !empty($file_upload['name']) )
			{
				$unlink = false;
				
				if( !empty($file_local) )
				{
					$tmp_filename = wa_realpath(WA_ROOTDIR . '/' . str_replace('\\', '/', $file_local));
					$filename     = $file_local;
					
					if( !file_exists($tmp_filename) )
					{
						$output->redirect('./tools.php?mode=restore', 4);
						
						$message  = sprintf($lang['Message']['Error_local'], htmlspecialchars($filename));
						$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./tools.php?mode=restore') . '">', '</a>');
						trigger_error($message, MESSAGE);
					}
				}
				else
				{
					$tmp_filename = $file_upload['tmp_name'];
					$filename     = $file_upload['name'];
					
					if( !isset($file_upload['error']) && empty($tmp_filename) )
					{
						$file_upload['error'] = -1;
					}
					
					if( $file_upload['error'] != UPLOAD_ERR_OK )
					{
						if( isset($lang['Message']['Upload_error_'.$file_upload['error']]) )
						{
							$upload_error = 'Upload_error_'.$file_upload['error'];
						}
						else
						{
							$upload_error = 'Upload_error_5';
						}
						
						trigger_error($upload_error, MESSAGE);
					}
					
					//
					// Si nous avons un accés restreint à cause de open_basedir, le fichier doit être déplacé 
					// vers le dossier des fichiers temporaires du script pour être accessible en lecture
					//
					if( OPEN_BASEDIR_RESTRICTION )
					{
						$unlink = true;
						$tmp_filename = wa_realpath(WA_TMPDIR . '/' . $filename);
						
						if( !move_uploaded_file($file_upload['tmp_name'], $tmp_filename) )
						{
							trigger_error('Upload_error_5', MESSAGE);
						}
					}
				}
				
				if( !preg_match('/\.(sql|zip|gz|bz2)$/i', $filename, $match) )
				{
					trigger_error('Bad_file_type', MESSAGE);
				}
				
				$file_ext = $match[1];
				
				if( ( !$zziplib_loaded && $file_ext == 'zip' ) || ( !$zlib_loaded && $file_ext == 'gz' ) || ( !$bzip2_loaded && $file_ext == 'bz2' ) )
				{
					trigger_error('Compress_unsupported', MESSAGE);
				}
				
				$data = decompress_filedata($tmp_filename, $file_ext);
				
				//
				// S'il y a une restriction d'accés par l'open_basedir, et que c'est un fichier uploadé, 
				// nous avons dù le déplacer dans le dossier des fichiers temporaires du script, on le supprime.
				//
				if( $unlink )
				{
					require WA_ROOTDIR . '/includes/class.attach.php';
					
					Attach::remove_file($tmp_filename);
				}
			}
			
			// 
			// Aucun fichier de restauration reçu 
			//
			else
			{
				$output->redirect('./tools.php?mode=restore', 4);
				
				$message  = $lang['Message']['No_data_received'];
				$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./tools.php?mode=restore') . '">', '</a>');
				trigger_error($message, MESSAGE);
			}
			
			$queries = make_sql_ary($data, ';');
			
			$db->transaction(START_TRC);
			
			fake_header(false);
			
			foreach( $queries AS $query )
			{
				$db->query($query) || trigger_error('Erreur sql lors de la restauration', ERROR);
				
				fake_header(true);
			}
			
			$db->transaction(END_TRC);
			
			trigger_error('Success_restore', MESSAGE);
		}
		
		$output->addHiddenField('sessid', $session->session_id);
		
		$output->set_filenames(array(
			'tool_body' => 'restore_body.tpl'
		));
		
		$output->assign_vars(array(
			'L_TITLE_RESTORE'   => $lang['Title']['restore'],
			'L_EXPLAIN_RESTORE' => nl2br($lang['Explain']['restore']),
			'L_FILE_LOCAL'      => $lang['File_local'],
			'L_VALID_BUTTON'    => $lang['Button']['valid'],
			'L_RESET_BUTTON'    => $lang['Button']['reset'],
			
			'S_HIDDEN_FIELDS'   => $output->getHiddenFields(),
			'S_ENCTYPE'         => ( FILE_UPLOADS_ON ) ? 'multipart/form-data' : 'application/x-www-form-urlencoded'
		));
		
		if( FILE_UPLOADS_ON )
		{
			//
			// L'upload est disponible sur le serveur
			// Affichage du champ file pour importation
			//
			$output->assign_block_vars('upload_file', array(
				'L_FILE_UPLOAD' => $lang['File_upload_restore']
			));
		}
		
		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;
	
	case 'generator':
		if( isset($_POST['generate']) )
		{
			$url_form = ( !empty($_POST['url_form']) ) ? trim($_POST['url_form']) : '';
			
			$code_html  = "<form method=\"post\" action=\"" . htmlspecialchars($url_form) . "\">\n";
			$code_html .= $lang['Email_address'] . " : <input type=\"text\" name=\"email\" maxlength=\"100\" /> &nbsp; \n";
			
			if( $listdata['liste_format'] == FORMAT_MULTIPLE )
			{
				$code_html .= $lang['Format'] . " : <select name=\"format\">\n";
				$code_html .= "<option value=\"" . FORMAT_TEXTE . "\">TXT</option>\n";
				$code_html .= "<option value=\"" . FORMAT_HTML . "\">HTML</option>\n";
				$code_html .= "</select>\n";
			}
			else
			{
				$code_html .= "<input type=\"hidden\" name=\"format\" value=\"$listdata[liste_format]\" />\n";
			}
			
			$code_html .= "<input type=\"hidden\" name=\"liste\" value=\"$listdata[liste_id]\" />\n";
			$code_html .= "<br />\n";
			$code_html .= "<input type=\"radio\" name=\"action\" value=\"inscription\" checked=\"checked\" /> $lang[Subscribe] <br />\n";
			$code_html .= ( $listdata['liste_format'] == FORMAT_MULTIPLE ) ? "<input type=\"radio\" name=\"action\" value=\"setformat\" /> $lang[Setformat] <br />\n" : "";
			$code_html .= "<input type=\"radio\" name=\"action\" value=\"desinscription\" /> $lang[Unsubscribe] <br />\n";
			$code_html .= "<input type=\"submit\" name=\"wanewsletter\" value=\"" . $lang['Button']['valid'] . "\" />\n";
			$code_html .= "</form>";
			
			$path = wa_realpath(WA_ROOTDIR . '/newsletter.php');
			
			$code_php  = '<' . "?php\n";
			$code_php .= "define('IN_WA_FORM', true);\n";
			$code_php .= "define('WA_ROOTDIR', '" . substr($path, 0, strrpos($path, '/')) . "');\n";
			$code_php .= "\n";
			$code_php .= "include WA_ROOTDIR . '/newsletter.php';\n";
			$code_php .= '?' . '>';
			
			$output->set_filenames(array(
				'tool_body' => 'result_generator_body.tpl'
			));
			
			$output->assign_vars(array(
				'L_TITLE_GENERATOR'   => $lang['Title']['generator'],
				'L_EXPLAIN_CODE_HTML' => nl2br($lang['Explain']['code_html']),
				'L_EXPLAIN_CODE_PHP'  => nl2br($lang['Explain']['code_php']),
				
				'CODE_HTML' => nl2br(htmlspecialchars($code_html, ENT_NOQUOTES)),
				'CODE_PHP'  => nl2br(htmlspecialchars($code_php, ENT_NOQUOTES))
			));
		}
		else
		{
			$output->addHiddenField('sessid', $session->session_id);
			
			$output->set_filenames(array(
				'tool_body' => 'generator_body.tpl'
			));
			
			$output->assign_vars(array(
				'L_TITLE_GENERATOR'   => $lang['Title']['generator'],
				'L_EXPLAIN_GENERATOR' => nl2br($lang['Explain']['generator']),
				'L_TARGET_FORM'       => $lang['Target_form'],
				'L_VALID_BUTTON'      => $lang['Button']['valid'],
				
				'S_HIDDEN_FIELDS' => $output->getHiddenFields()
			));
		}
		
		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;
}

$output->pparse('body');

$output->page_footer();
?>