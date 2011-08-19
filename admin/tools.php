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
 */

define('IN_NEWSLETTER', true);

require './pagestart.php';

/**
 * Imported from PHP_Compat PEAR package
 * Replace array_udiff()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/function.array_udiff
 * @author      Stephan Schmidt <schst@php.net>
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision$
 * @since       PHP 5
 * @require     PHP 4.0.6 (is_callable)
 */
if (!function_exists('array_udiff')) {
    function array_udiff()
    {
        $args = func_get_args();

        if (count($args) < 3) {
            user_error('Wrong parameter count for array_udiff()', E_USER_WARNING);
            return;
        }

        // Get compare function
        $compare_func = array_pop($args);
        if (!is_callable($compare_func)) {
            if (is_array($compare_func)) {
                $compare_func = $compare_func[0] . '::' . $compare_func[1];
            }
            user_error('array_udiff() Not a valid callback ' .
                $compare_func, E_USER_WARNING);
            return;
        }

        // Check arrays
        $cnt = count($args);
        for ($i = 0; $i < $cnt; $i++) {
            if (!is_array($args[$i])) {
                user_error('array_udiff() Argument #' .
                    ($i + 1). ' is not an array', E_USER_WARNING);
                return;
            }
        }

        $diff = array ();
        // Traverse values of the first array
        foreach ($args[0] as $key => $value) {
            // Check all arrays
            for ($i = 1; $i < $cnt; $i++) {
                foreach ($args[$i] as $cmp_value) {
                    $result = call_user_func($compare_func, $value, $cmp_value);
                    if ($result === 0) {
                        continue 3;
                    }
                }
            }
            $diff[$key] = $value;
        }
        return $diff;
    }
}

//
// Compression éventuelle des données et réglage du mime-type et du
// nom de fichier en conséquence.
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
			case 'xml':
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

if( !in_array($format, array(FORMAT_TEXTE, FORMAT_HTML)) )
{
	$format = FORMAT_TEXTE;
}

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
	case 'restore':
		$backupclass = 'WadbBackup_' . SQL_DRIVER;
		
		if( !class_exists($backupclass) )
		{
			$output->message('Database_unsupported');
		}
		
	case 'attach':
		if( $admindata['admin_level'] != ADMIN )
		{
			$output->redirect('./index.php', 4);
			
			$message  = $lang['Message']['Not_authorized'];
			$message .= '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . sessid('./index.php') . '">', '</a>');
			$output->message($message);
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
		$output->message('Not_' . $auth->auth_ary[$auth_type]);
	}
	
	$listdata = $auth->listdata[$admindata['session_liste']];
}

//
// Affichage de la boîte de sélection des modules
//
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
	foreach( $tools_ary as $tool_name )
	{
		$selected = ( $mode == $tool_name ) ? ' selected="selected"' : '';
		$tools_box .= sprintf("<option value=\"%s\"%s> %s </option>\n\t", $tool_name, $selected, $lang['Title'][$tool_name]);
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
				$output->message(sprintf($lang['Message']['Dir_not_writable'],
					wan_htmlspecialchars(wa_realpath(WA_TMPDIR))));
			}
			
			if( $listdata['liste_format'] != FORMAT_MULTIPLE )
			{
				$format = $listdata['liste_format'];
			}
			
			$glue = ( $glue != '' ) ? $glue : WA_EOL;
			
			$sql = "SELECT a.abo_email 
				FROM " . ABONNES_TABLE . " AS a
					INNER JOIN " . ABO_LISTE_TABLE . " AS al ON al.abo_id = a.abo_id
						AND al.liste_id  = $listdata[liste_id]
						AND al.format    = $format
						AND al.confirmed = " . SUBSCRIBE_CONFIRMED . "
				WHERE a.abo_status = " . ABO_ACTIF;
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible d\'obtenir la liste des emails à exporter', ERROR);
			}
			
			$contents = '';
			if( $eformat == 'xml' )
			{
				while( $email = $result->column('abo_email') )
				{
					$contents .= sprintf("\t<email>%s</email>\n", $email);
				}
				
				$format = ( $format == FORMAT_HTML ) ? 'HTML' : 'text';
				$contents  = '<' . '?xml version="1.0"?' . ">\n"
					. "<!-- Date : " . date('d/m/Y H:i:s O') . " - Format : $format -->\n"
					. "<Wanliste>\n" . $contents . "</Wanliste>\n";
				
				$mime_type = 'application/xml';
				$ext = 'xml';
			}
			else
			{
				while( $email = $result->column('abo_email') )
				{
					$contents .= (( $contents != '' ) ? $glue : '') . $email;
				}
				
				$mime_type = 'text/plain';
				$ext = 'txt';
			}
			$result->free();
			
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
				
				$output->message('Success_export');
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
			$list_email  = ( !empty($_POST['list_email']) ) ? trim($_POST['list_email']) : '';
			$list_tmp    = '';
			$data_is_xml = false;
			
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
						
						$message  = sprintf($lang['Message']['Error_local'], wan_htmlspecialchars($filename));
						$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./tools.php?mode=import') . '">', '</a>');
						$output->message($message);
					}
				}
				else
				{
					$tmp_filename = $file_upload['tmp_name'];
					$filename     = $file_upload['name'];
					$data_is_xml  = preg_match('#(?:/|\+)xml#', $file_upload['type']);
					
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
						
						$output->message($upload_error);
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
							$output->message('Upload_error_5');
						}
					}
				}
				
				if( !preg_match('/\.(txt|xml|zip|gz|bz2)$/i', $filename, $match) )
				{
					$output->message('Bad_file_type');
				}
				
				$file_ext = $match[1];
				
				if( ( !$zziplib_loaded && $file_ext == 'zip' ) || ( !$zlib_loaded && $file_ext == 'gz' ) || ( !$bzip2_loaded && $file_ext == 'bz2' ) )
				{
					$output->message('Compress_unsupported');
				}
				
				$list_tmp = decompress_filedata($tmp_filename, $file_ext);
				
				if( !empty($file_local) )
				{
					$data_is_xml = (strncmp($list_tmp, '<?xml', 5) == 0);
				}
				
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
			if( empty($list_tmp) )
			{
				$output->redirect('./tools.php?mode=import', 4);
				
				$message  = $lang['Message']['No_data_received'];
				$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./tools.php?mode=import') . '">', '</a>');
				$output->message($message);
			}
			
			if( $listdata['liste_format'] != FORMAT_MULTIPLE )
			{
				$format = $listdata['liste_format'];
			}
			
			require WAMAILER_DIR . '/class.mailer.php';
			
			if( $data_is_xml == true )
			{
				$emails = array();
				
				if( extension_loaded('simplexml') )
				{
					$xml = simplexml_load_string($list_tmp);
					$xml = $xml->xpath('/Wanliste/email');
					
					foreach( $xml as $email )
					{
						array_push($emails, "$email");
					}
				}
				else if( extension_loaded('xml') )
				{
					$depth = 0;
					$tagName = '';
					
					$parser = xml_parser_create();
					xml_set_element_handler($parser,
						create_function('$parser,$name,$attrs',
							'if( ($GLOBALS["depth"] == 0 && strtolower($name) == "wanliste") || $GLOBALS["depth"] > 0 ) {
								$GLOBALS["depth"]++;
							}
							
							$GLOBALS["tagName"] = strtolower($name);'
						),
						create_function('$parser,$name', '$GLOBALS["depth"]--;')
					);
					xml_set_character_data_handler($parser, create_function('$parser, $data',
						'if( $GLOBALS["tagName"] == "email" && $GLOBALS["depth"] == 2 ) {
							array_push($GLOBALS["emails"], $data);
						}'
					));
					
					if( !xml_parse($parser, $list_tmp) )
					{
						$output->message(sprintf(
							$lang['Message']['Invalid_xml_data'],
							wan_htmlspecialchars(xml_error_string(xml_get_error_code($parser)), ENT_NOQUOTES),
							xml_get_current_line_number($parser)
						));
					}
					
					xml_parser_free($parser);
				}
				else
				{
					$output->message('Xml_ext_needed');
				}
			}
			else
			{
				if( $glue == '' )
				{
					$list_tmp = preg_replace("/\r\n?/", "\n", $list_tmp);
					$glue = "\n";
				}
				
				$emails = explode($glue, trim($list_tmp));
			}
			
			$report = '';
			$emails = array_slice($emails, 0, MAX_IMPORT);
			$emails = array_map('trim', $emails);
			$emails = array_unique($emails);
			
			$current_time = time();
			$emails_ok    = array();
			
			fake_header(false);
			
			//
			// Vérification syntaxique des emails
			//
			$emails = array_filter($emails, create_function('$email',
				'global $lang, $report;
				
				if( Mailer::validate_email($email) ) {
					return true;
				} else {
					$report .= sprintf(\'%s : %s%s\', $email, $lang[\'Message\'][\'Invalid_email2\'], WA_EOL);
					return false;
				}'
			));
			
			if( count($emails) > 0 )
			{
				$sql_emails = array_map(create_function('$email',
					'return $GLOBALS["db"]->escape(strtolower($email));'), $emails);
				
				$sql = "SELECT a.abo_id, a.abo_email, a.abo_status, al.confirmed
					FROM " . ABONNES_TABLE . " AS a
						LEFT JOIN " . ABO_LISTE_TABLE . " AS al ON al.abo_id = a.abo_id
							AND al.liste_id = $listdata[liste_id]
					WHERE LOWER(a.abo_email) IN('" . implode("', '", $sql_emails) . "')";
				if( !($result = $db->query($sql)) )
				{
					trigger_error('Impossible de tester les tables d\'inscriptions', ERROR);
				}
				
				//
				// Traitement des adresses email déjà présentes dans la base de données
				//
				while( $abodata = $result->fetch() )
				{
					if( !isset($abodata['confirmed']) ) // N'est pas inscrit à cette liste
					{
						$sql_data = array();
						$sql_data['abo_id']        = $abodata['abo_id'];
						$sql_data['liste_id']      = $listdata['liste_id'];
						$sql_data['format']        = $format;
						$sql_data['register_key']  = generate_key(20, false);
						$sql_data['register_date'] = $current_time;
						$sql_data['confirmed']     = ($abodata['abo_status'] == ABO_ACTIF) ? SUBSCRIBE_CONFIRMED : SUBSCRIBE_NOT_CONFIRMED;
						
						if( !$db->build(SQL_INSERT, ABO_LISTE_TABLE, $sql_data) )
						{
							trigger_error('Impossible d\'insérer une nouvelle entrée dans la table abo_liste', ERROR);
						}
					}
					else
					{
						$report .= sprintf('%s : %s%s', $abodata['abo_email'], $lang['Message']['Allready_reg'], WA_EOL);
					}
					
					array_push($emails_ok, $abodata['abo_email']);
				}
				
				//
				// Traitement des adresses email inconnues
				//
				$emails = array_udiff($emails, $emails_ok, 'strcasecmp');
				
				foreach( $emails as $email )
				{
					$db->beginTransaction();
					
					$sql_data = array();
					$sql_data['abo_email']  = $email;
					$sql_data['abo_status'] = ABO_ACTIF;
					
					if( !$db->build(SQL_INSERT, ABONNES_TABLE, $sql_data) )
					{
						$report .= sprintf('%s : SQL error (#%d: %s)%s', $email, $db->errno, $db->error, WA_EOL);
						$db->rollBack();
						continue;
					}
					
					$sql_data = array();
					$sql_data['abo_id']        = $db->lastInsertId();
					$sql_data['liste_id']      = $listdata['liste_id'];
					$sql_data['format']        = $format;
					$sql_data['register_key']  = generate_key(20, false);
					$sql_data['register_date'] = $current_time;
					$sql_data['confirmed']     = SUBSCRIBE_CONFIRMED;
					
					if( !$db->build(SQL_INSERT, ABO_LISTE_TABLE, $sql_data) )
					{
						trigger_error('Impossible d\'insérer une nouvelle entrée dans la table abo_liste', ERROR);
					}
					
					$db->commit();
					
					fake_header(true);
				}
			}
			
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
			
			$output->message($message);
		}
		
		$output->addHiddenField('sessid', $session->session_id);
		
		$output->set_filenames(array(
			'tool_body' => 'import_body.tpl'
		));
		
		$output->assign_vars(array(
			'L_TITLE_IMPORT'   => $lang['Title']['import'],
			'L_EXPLAIN_IMPORT' => nl2br(sprintf($lang['Explain']['import'], MAX_IMPORT, '<a href="' . WA_ROOTDIR . '/docs/faq.' . $lang['CONTENT_LANG'] . '.html#p3">', '</a>')),
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
				'L_FILE_UPLOAD'  => $lang['File_upload'],
				'L_MAXIMUM_SIZE' => sprintf($lang['Maximum_size'], formateSize(MAX_FILE_SIZE)),
				'MAX_FILE_SIZE'  => MAX_FILE_SIZE
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
				
				foreach( $pattern_ary as $pattern )
				{
					switch( SQL_DRIVER )
					{
						case 'mysql':
						case 'mysqli':
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
				$db->vacuum(BANLIST_TABLE);
			}
			
			$output->redirect('./tools.php?mode=ban', 4);
			
			$message  = $lang['Message']['Success_modif'];
			$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./tools.php?mode=ban') . '">', '</a>');
			$message .= '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . sessid('./index.php') . '">', '</a>');
			$output->message($message);
		}
		
		$sql = "SELECT ban_id, ban_email 
			FROM " . BANLIST_TABLE . " 
			WHERE liste_id = " . $listdata['liste_id'];
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir la liste des masques de bannissement', ERROR);
		}
		
		$unban_email_box = '<select id="unban_list_id" name="unban_list_id[]" multiple="multiple" size="10">';
		if( $row = $result->fetch() )
		{
			do
			{
				$unban_email_box .= sprintf("<option value=\"%d\">%s</option>\n\t", $row['ban_id'], $row['ban_email']);
			}
			while( $row = $result->fetch() );
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
				
				foreach( $ext_ary as $ext )
				{
					$ext = strtolower($ext);
					
					if( preg_match('/^[\w_-]+$/', $ext) )
					{
						switch( SQL_DRIVER )
						{
							case 'mysql':
							case 'mysqli':
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
				$db->vacuum(FORBIDDEN_EXT_TABLE);
			}
			
			$output->redirect('./tools.php?mode=attach', 4);
			
			$message  = $lang['Message']['Success_modif'];
			$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./tools.php?mode=attach') . '">', '</a>');
			$message .= '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . sessid('./index.php') . '">', '</a>');
			$output->message($message);
		}
		
		$sql = "SELECT fe_id, fe_ext 
			FROM " . FORBIDDEN_EXT_TABLE . " 
			WHERE liste_id = " . $listdata['liste_id'];
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir la liste des extensions interdites', ERROR);
		}
		
		$reallow_ext_box = '<select id="ext_list_id" name="ext_list_id[]" multiple="multiple" size="10">';
		if( $row = $result->fetch() )
		{
			do
			{
				$reallow_ext_box .= sprintf("<option value=\"%d\">%s</option>\n\t", $row['fe_id'], $row['fe_ext']);
			}
			while( $row = $result->fetch() );
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
		
		list($infos) = parseDSN($dsn);
		
		$tables      = array();
		$tables_plus = ( !empty($_POST['tables_plus']) ) ? array_map('trim', $_POST['tables_plus']) : array();
		$backup_type = ( isset($_POST['backup_type']) ) ? intval($_POST['backup_type']) : 0;
		$drop_option = ( !empty($_POST['drop_option']) ) ? true : false;
		
		$backup = new $backupclass($infos);// Voir ligne 160 pour $infos et $backupclass
		$backup->eol = WA_EOL;
		$tables_ary  = $backup->get_tables();
		
		foreach( $tables_ary as $tablename => $tabletype )
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
				$output->message(sprintf($lang['Message']['Dir_not_writable'],
					wan_htmlspecialchars(wa_realpath(WA_TMPDIR))));
			}
			
			//
			// Lancement de la sauvegarde. Pour commencer, l'entête du fichier sql 
			//
			$contents  = $backup->header('Wanewsletter ' . WA_VERSION);
			$contents .= $backup->get_other_queries($drop_option);
			
			fake_header(false);
			
			foreach( $tables as $tabledata )
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
				
				$output->message('Success_backup');
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
			foreach( $tables_plus as $table_name )
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
			require WA_ROOTDIR . '/includes/sql/sqlparser.php';
			
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
						
						$message  = sprintf($lang['Message']['Error_local'], wan_htmlspecialchars($filename));
						$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./tools.php?mode=restore') . '">', '</a>');
						$output->message($message);
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
						
						$output->message($upload_error);
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
							$output->message('Upload_error_5');
						}
					}
				}
				
				if( !preg_match('/\.(sql|zip|gz|bz2)$/i', $filename, $match) )
				{
					$output->message('Bad_file_type');
				}
				
				$file_ext = $match[1];
				
				if( ( !$zziplib_loaded && $file_ext == 'zip' ) || ( !$zlib_loaded && $file_ext == 'gz' ) || ( !$bzip2_loaded && $file_ext == 'bz2' ) )
				{
					$output->message('Compress_unsupported');
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
				$output->message($message);
			}
			
			$queries = parseSQL($data);
			
			$db->beginTransaction();
			
			fake_header(false);
			
			foreach( $queries as $query )
			{
				$db->query($query) || trigger_error('Erreur sql lors de la restauration', ERROR);
				
				fake_header(true);
			}
			
			$db->commit();
			
			$output->message('Success_restore');
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
				'L_FILE_UPLOAD' => $lang['File_upload_restore'],
				'L_MAXIMUM_SIZE' => sprintf($lang['Maximum_size'], formateSize(MAX_FILE_SIZE)),
				'MAX_FILE_SIZE'  => MAX_FILE_SIZE
			));
		}
		
		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;
	
	case 'generator':
		if( isset($_POST['generate']) )
		{
			$url_form = ( !empty($_POST['url_form']) ) ? trim($_POST['url_form']) : '';
			
			$code_html  = "<form method=\"post\" action=\"" . wan_htmlspecialchars($url_form) . "\">\n";
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
				
				'CODE_HTML' => nl2br(wan_htmlspecialchars($code_html, ENT_NOQUOTES)),
				'CODE_PHP'  => nl2br(wan_htmlspecialchars($code_php, ENT_NOQUOTES))
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