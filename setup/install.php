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

define('IN_INSTALL', true);

require './setup.inc.php';

$lang  = $datetime = $msg_error = $_php_errors = array();
$error = false;

$vararray = array('start', 'confirm', 'send_file');
foreach( $vararray AS $varname )
{
	${$varname} = ( isset($_POST[$varname]) ) ? true : false;
}

$vararray = array(
	'language', 'prev_language', 'type', 'admin_login', 'admin_email', 'admin_pass', 
	'confirm_pass', 'urlsite', 'urlscript'
);
foreach( $vararray AS $varname )
{
	${$varname} = ( !empty($_POST[$varname]) ) ? trim($_POST[$varname]) : '';
}

$confirm_pass = ( $confirm_pass != '' ) ? md5($confirm_pass) : '';
$language     = ( $language != '' ) ? $language : $default_lang;

if( isset($supported_db[$dbtype]) )
{
	require WA_ROOTDIR . '/sql/' . $dbtype . '.php';
}
else if( defined('NL_INSTALLED') || $start == true )
{
	plain_error('Le type de base de données n\'est pas défini !');
}

$output->set_filenames( array(
	'body' => 'install.tpl'
));

if( $type != 'reinstall' && $type != 'update' )
{
	$type = 'install';
	
	if( $start )
	{
		if( $language != $prev_language )
		{
			$start = false;
		}
	}
	else if( server_info('HTTP_ACCEPT_LANGUAGE') != '' )
	{
		$accept_lang_ary = array_map('trim', explode(',', server_info('HTTP_ACCEPT_LANGUAGE')));
		
		foreach( $accept_lang_ary AS $accept_lang )
		{
			$accept_lang = strtolower(substr($accept_lang, 0, 2));
			
			if( isset($supported_lang[$accept_lang]) && file_exists(WA_ROOTDIR . '/language/lang_' . $supported_lang[$accept_lang] . '.php') )
			{
				$language = $supported_lang[$accept_lang];
				break;
			}
		}
	}
}

if( defined('NL_INSTALLED') )
{
	$db = new sql($dbhost, $dbuser, $dbpassword, $dbname);
	
	if( !$db->connect_id )
	{
		plain_error('Impossible de se connecter à la base de données');
	}
	
	$sql = "SELECT language, urlsite, path, version FROM " . CONFIG_TABLE;
	if( !($result = $db->query($sql)) )
	{
		plain_error('Impossible d\'obtenir la configuration du script');
	}
	
	$old_config = $db->fetch_array($result);
	
	$old_version = $old_config['version'];
	$urlsite     = $old_config['urlsite'];
	$urlscript   = $old_config['path'];
	$language    = $old_config['language'];
	
	require WA_ROOTDIR . '/language/lang_' . $language . '.php';
	
	$login = false;
	
	if( $confirm )
	{
		$sql = "SELECT admin_email, admin_pwd, admin_level 
			FROM " . ADMIN_TABLE . " 
			WHERE LOWER(admin_login) = '" . $db->escape(strtolower($admin_login)) . "'";
		if( $result = $db->query($sql) )
		{
			if( $row = $db->fetch_array($result) )
			{
				if( md5($admin_pass) == $row['admin_pwd'] && $row['admin_level'] == ADMIN )
				{
					$login        = true;
					$start        = true;
					$admin_email  = $row['admin_email'];
					$confirm_pass = $row['admin_pwd'];
				}
			}
		}
		
		if( !$login )
		{
			$error = true;
			$msg_error[] = $lang['Message']['Error_login'];
		}
	}
}
else
{
	require WA_ROOTDIR . '/language/lang_' . $language . '.php';
	
	if( $start )
	{
		if( $dbtype == 'sqlite' )
		{
			if( is_writable(WA_ROOTDIR . '/sql')
				&& is_readable(WA_ROOTDIR . '/sql/wanewsletter.db')
				&& is_writable(WA_ROOTDIR . '/sql/wanewsletter.db') )
			{
				$db = new sql(WA_ROOTDIR . '/sql/wanewsletter.db');
			}
			else
			{
				$error = true;
				$msg_error[] = $lang['sqldir_perms_problem'];
			}
		}
		else
		{
			$db = new sql($dbhost, $dbuser, $dbpassword, $dbname);
		}
		
		if( $error == false && !$db->connect_id )
		{
			$error = true;
			$msg_error[] = sprintf($lang['Connect_db_error'], $db->sql_error['message']);
		}
		
		if( !is_writable(WA_ROOTDIR . '/includes/config.inc.php') )
		{
			$error = true;
			$msg_error[] = $lang['File_config_unwritable'];
		}
	}
}

$output->send_headers();

$output->assign_vars( array(
	'PAGE_TITLE'   => ( defined('NL_INSTALLED') ) ? $lang['Title']['reinstall_update'] : $lang['Title']['install'],
	'CONTENT_LANG' => $lang['CONTENT_LANG'],
	'CONTENT_DIR'  => $lang['CONTENT_DIR'],
	'NEW_VERSION'  => $new_version,
	'TRANSLATE'    => ( $lang['TRANSLATE'] != '' ) ? ' | Translate by ' . $lang['TRANSLATE'] : ''
));

if( defined('NL_INSTALLED') && ( !$start || $error ) )
{
	$output->assign_block_vars('reinstall', array(
		'L_EXPLAIN_REINSTALL' => nl2br($lang['Warning_reinstall']),
		'L_LOGIN'          => $lang['Login'],
		'L_PASS'           => $lang['Password'],
		'L_SELECT_TYPE'    => $lang['Select_type'],
		'L_TYPE_REINSTALL' => $lang['Type_reinstall'],
		'L_TYPE_UPDATE'    => $lang['Type_update'],
		'L_CONF_BUTTON'    => $lang['Button']['conf']
	));
	
	if( $error )
	{
		$output->error_box($msg_error);
	}
	
	$output->pparse('body');
	exit;
}

if( $send_file )
{
	require WA_ROOTDIR . '/includes/class.attach.php';
	
	Attach::send_file('config.inc.php', 'text/plain', $config_file);
	exit;
}
else
{
	if( $start )
	{
		require WA_ROOTDIR . '/includes/functions.validate.php';
		
		if( $dbhost == '' || $dbname == '' || $dbuser == '' || $prefixe == '' || $admin_login == '' )
		{
			$error = true;
			$msg_error[] = $lang['Message']['fields_empty'];
		}
		
		if( !validate_pass($admin_pass) )
		{
			$error = true;
			$msg_error[] = $lang['Message']['Alphanum_pass'];
		}
		else if( md5($admin_pass) != $confirm_pass )
		{
			$error = true;
			$msg_error[] = $lang['Message']['Bad_confirm_pass'];
		}
		
		$result = check_email($admin_email);
		
		if( $result['error'] )
		{
			$error = true;
			$msg_error[] = $result['message'];
		}
		
		$urlsite = rtrim($urlsite, '/');
		
		if( $urlscript != '/' )
		{
			$urlscript = '/' . trim($urlscript, '/') . '/';
		}
		
		//
		// On allonge le temps maximum d'execution du script. 
		//
		@set_time_limit(120);
		
		if( ( $type == 'install' || $type == 'reinstall' ) && !$error )
		{
			$db->transaction(START_TRC);
			
			if( $type == 'reinstall' )
			{
				$sql_drop = preg_replace('/wa_/', $prefixe, $sql_drop);
				
				exec_queries($sql_drop);
			}
			
			//
			// Création des tables du script 
			//
			$sql_file = SCHEMAS_DIR . '/' . $supported_db[$dbtype]['prefixe_file'] . '_tables.sql';
			
			if( !($fp = @fopen($sql_file, 'r')) )
			{
				msg_result('sql_file_not_readable');
			}
			
			$sql_create = make_sql_ary(fread($fp, filesize($sql_file)), $supported_db[$dbtype]['delimiter'], $prefixe);
			fclose($fp);
			
			exec_queries($sql_create, true);
			
			//
			// Insertion des données de base 
			//
			$sql_file = SCHEMAS_DIR . '/' . $supported_db[$dbtype]['prefixe_file'] . '_data.sql';
			
			if( !($fp = @fopen($sql_file, 'r')) )
			{
				msg_result('sql_file_not_readable');
			}
			
			$sql_data = make_sql_ary(fread($fp, filesize($sql_file)), $supported_db[$dbtype]['delimiter2'], $prefixe);
			fclose($fp);
			
			$sql_data[] = "UPDATE " . ADMIN_TABLE . "
				SET admin_login = '" . $db->escape($admin_login) . "',
					admin_pwd   = '" . md5($admin_pass) . "',
					admin_email = '" . $db->escape($admin_email) . "',
					admin_lang  = '$language'
				WHERE admin_id = 1";
			$sql_data[] = "UPDATE " . CONFIG_TABLE . "
				SET urlsite  = '" . $db->escape($urlsite) . "',
					path     = '" . $db->escape($urlscript) . "',
					language = '$language',
					mailing_startdate = " . time() . ",
					version  = '$new_version'";
			$sql_data[] = "UPDATE " . LISTE_TABLE . "
				SET liste_startdate = " . time() . " WHERE liste_id = 1";
			
			exec_queries($sql_data, true);
			
			$db->transaction(END_TRC);
		}
		else if( $type == 'update' && !$error )
		{
			$sql_update = array();
			
			switch( $old_version )
			{
				case '2.2-Beta':
				case '2.2-Beta2':
					switch( DATABASE )
					{
						case 'postgre':
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								RENAME COLUMN smtp_user TO smtp_user_old, 
								RENAME COLUMN smtp_pass TO smtp_pass_old";
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN smtp_user varchar(50) NOT NULL DEFAULT '', 
								ADD COLUMN smtp_pass varchar(50) NOT NULL DEFAULT ''";
							$sql_update[] = "UPDATE " . CONFIG_TABLE . " SET smtp_user = smtp_user_old, smtp_pass = smtp_pass_old";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . " 
								ADD COLUMN liste_alias varchar(250) NOT NULL DEFAULT ''";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . " 
								ADD COLUMN use_cron int NOT NULL DEFAULT '0'";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . " 
								ADD COLUMN pop_host varchar(100) NOT NULL DEFAULT ''";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . " 
								ADD COLUMN pop_port int2 NOT NULL DEFAULT '110'";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . " 
								ADD COLUMN pop_user varchar(50) NOT NULL DEFAULT ''";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . " 
								ADD COLUMN pop_pass varchar(50) NOT NULL DEFAULT ''";
							break;
						
						default:
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . " 
								MODIFY COLUMN smtp_user varchar(50) NOT NULL DEFAULT '', 
								MODIFY COLUMN smtp_pass varchar(50) NOT NULL DEFAULT ''";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . " 
								ADD COLUMN liste_alias varchar(250) NOT NULL DEFAULT '', 
								ADD COLUMN use_cron tinyint(1) NOT NULL DEFAULT '0',
								ADD COLUMN pop_host varchar(100) NOT NULL DEFAULT '',
								ADD COLUMN pop_port smallint(5) NOT NULL DEFAULT '110',
								ADD COLUMN pop_user varchar(50) NOT NULL DEFAULT '',
								ADD COLUMN pop_pass varchar(50) NOT NULL DEFAULT ''";
							break;
					}
				
				//
				// Un bug était présent dans la rc1, comme une seconde édition du package avait été mise 
				// à disposition pour pallier à un bug de dernière minute assez important, le numéro de version 
				// était 2.2-RC2 pendant une dizaine de jours (alors qu'il me semblait avoir recorrigé 
				// le package après coup). 
				// Nous effectuons donc la mise à jour également pour les versions 2.2-RC2. 
				// Le nom de la vrai release candidate 2 est donc 2.2-RC2b pour éviter des problèmes lors des mises 
				// à jour par les gens qui ont téléchargé le package les dix premiers jours.
				//
				case '2.2-RC1':
				case '2.2-RC2':
					//
					// Suppression des éventuelles entrées orphelines dans les tables abonnes et abo_liste
					//
					$sql = "SELECT abo_id 
						FROM " . ABONNES_TABLE;
					if( !($result = $db->query($sql)) )
					{
						msg_result($sql, true);
					}
					
					$abonnes_id = array();
					while( $row = $db->fetch_array($result) )
					{
						$abonnes_id[] = $row['abo_id'];
					}
					
					$sql = "SELECT abo_id 
						FROM " . ABO_LISTE_TABLE . " 
						GROUP BY abo_id";
					if( !($result = $db->query($sql)) )
					{
						msg_result($sql, true);
					}
					
					$abo_liste_id = array();
					while( $row = $db->fetch_array($result) )
					{
						$abo_liste_id[] = $row['abo_id'];
					}
					
					$diff_1 = array_diff($abonnes_id, $abo_liste_id);
					$diff_2 = array_diff($abo_liste_id, $abonnes_id);
					
					$total_diff_1 = count($diff_1);
					$total_diff_2 = count($diff_2);
					
					if( $total_diff_1 > 0 )
					{
						$sql_update[] = "DELETE FROM " . ABONNES_TABLE . " 
							WHERE abo_id IN(" . implode(', ', $diff_1) . ")";
					}
					
					if( $total_diff_2 > 0 )
					{
						$sql_update[] = "DELETE FROM " . ABO_LISTE_TABLE . " 
							WHERE abo_id IN(" . implode(', ', $diff_2) . ")";
					}
					
					switch( DATABASE )
					{
						case 'postgre':
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . " 
								ADD COLUMN liste_numlogs int2 NOT NULL";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . " 
								ALTER COLUMN liste_numlogs SET DEFAULT '0'";
							$sql_update[] = "ALTER TABLE " . LOG_TABLE . " 
								ADD COLUMN log_numdest int2 NOT NULL";
							$sql_update[] = "ALTER TABLE " . LOG_TABLE . " 
								ALTER COLUMN log_numdest SET DEFAULT '0'";
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . " 
								ADD COLUMN check_email_mx int NOT NULL";
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . " 
								ALTER COLUMN check_email_mx SET DEFAULT '0'";
							break;
						
						default:
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . " 
								ADD COLUMN liste_numlogs smallint(5) NOT NULL DEFAULT '0' AFTER liste_alias";
							$sql_update[] = "ALTER TABLE " . LOG_TABLE . " 
								ADD COLUMN log_numdest smallint(5) NOT NULL DEFAULT '0' AFTER log_date";
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . " 
								ADD COLUMN check_email_mx tinyint(1) NOT NULL DEFAULT '0' AFTER gd_img_type";
							$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . " DROP INDEX `abo_id`";
							$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . " DROP INDEX `liste_id`";
							$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . " 
								ADD PRIMARY KEY (abo_id , liste_id)";
							$sql_update[] = "ALTER TABLE " . LOG_FILES_TABLE . " DROP INDEX `log_id`";
							$sql_update[] = "ALTER TABLE " . LOG_FILES_TABLE . " DROP INDEX `file_id`";
							$sql_update[] = "ALTER TABLE " . LOG_FILES_TABLE . " 
								ADD PRIMARY KEY (log_id , file_id)";
							break;
					}
					
					$sql = "SELECT COUNT(*) AS numlogs, liste_id 
						FROM " . LOG_TABLE . " 
						WHERE log_status = " . STATUS_SENDED . " 
						GROUP BY liste_id";
					if( !($result = $db->query($sql)) )
					{
						msg_result($sql, true);
					}
					
					while( $row = $db->fetch_array($result) )
					{
						$sql_update[] = "UPDATE " . LISTE_TABLE . " 
							SET liste_numlogs = " . $row['numlogs'] . " 
							WHERE liste_id = " . $row['liste_id'];
					}
					
					$sql = "SELECT COUNT(DISTINCT(a.abo_id)) AS num_dest, al.liste_id 
						FROM " . ABONNES_TABLE . " AS a, " . ABO_LISTE_TABLE . " AS al 
						WHERE a.abo_id = al.abo_id AND a.abo_status = " . ABO_ACTIF . " 
						GROUP BY al.liste_id";
					if( !($result = $db->query($sql)) )
					{
						msg_result($sql, true);
					}
					
					while( $row = $db->fetch_array($result) )
					{
						$sql_update[] = "UPDATE " . LOG_TABLE . " 
							SET log_numdest = " . $row['num_dest'] . " 
							WHERE liste_id = " . $row['liste_id'];
					}
				
				case '2.2-RC2b':
					switch( DATABASE )
					{
						case 'postgre':
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . " 
								ADD COLUMN enable_profil_cp int NOT NULL DEFAULT '0'";
							$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . " 
								ADD COLUMN abo_lang varchar(30) NOT NULL DEFAULT ''";
							break;
						
						default:
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . " 
								ADD COLUMN enable_profil_cp tinyint(1) NOT NULL DEFAULT '0' AFTER check_email_mx";
							$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . " 
								ADD COLUMN abo_lang varchar(30) NOT NULL DEFAULT '' AFTER abo_email";
							break;
					}
					
					//
					// Correction du bug de mise à jour de la table abo_liste après un envoi.
					// Si tous les abonnés d'une liste ont send à 1, on remet celui ci à 0
					//
					$sql = "SELECT COUNT(al.abo_id) AS num_abo, SUM(al.send) AS num_send, al.liste_id 
						FROM " . ABONNES_TABLE . " AS a, " . ABO_LISTE_TABLE . " AS al 
						WHERE a.abo_id = al.abo_id AND a.abo_status = " . ABO_ACTIF . " 
						GROUP BY al.liste_id";
					if( !($result = $db->query($sql)) )
					{
						msg_result($sql, true);
					}
					
					while( $row = $db->fetch_array($result) )
					{
						if( $row['num_abo'] == $row['num_send'] )
						{
							$sql_update[] = "UPDATE " . ABO_LISTE_TABLE . " 
								SET send = 0 WHERE liste_id = " . $row['liste_id'];
						}
					}
					
					$sql_update[] = "UPDATE " . ABONNES_TABLE . " SET abo_lang = '$language'";
				
				case '2.2-RC3':
					switch( DATABASE )
					{
						case 'postgre':
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . " 
								ADD COLUMN ftp_port int NOT NULL DEFAULT '21'";
							break;
						
						default:
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . " 
								ADD COLUMN ftp_port smallint(5) NOT NULL DEFAULT '21' AFTER ftp_server";
							$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . " 
								CHANGE abo_id abo_id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT";
							$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . " 
								CHANGE abo_id abo_id MEDIUMINT(8) UNSIGNED DEFAULT '0' NOT NULL";
							break;
					}
					
				case '2.2-RC4':
				case '2.2.0':
				case '2.2.1':
				case '2.2.2':
				case '2.2.3':
				case '2.2.4':
					//
					// On désactive la vérification approfondie des adresses email, pas encore au point...
					//
					$sql_update[] = "UPDATE " . CONFIG_TABLE . " SET check_email_mx = 0";
				case '2.2.5':
				case '2.2.6':
				case '2.2.7':
				case '2.2.8':
					switch( DATABASE )
					{
						case 'postgre':
							$sql_update[] = "DROP INDEX abo_status_wa_abonnes_index ON " . ABONNES_TABLE;
							break;
						default:
							$sql_update[] = "DROP INDEX abo_status ON " . ABONNES_TABLE;
							break;
					}
					
					$sql_update[] = "CREATE INDEX abo_status_idx ON " . ABONNES_TABLE . " (abo_status)";
					$sql_update[] = "CREATE UNIQUE INDEX abo_email_idx ON " . ABONNES_TABLE . " (abo_email)";
					$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . " DROP COLUMN hebergeur";
					$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
						ADD COLUMN liste_public tinyint(1) NOT NULL DEFAULT '1' AFTER liste_name";
					break;
				
				default:
					msg_result($lang['Update_not_required']);
					break;
			}
			
			$sql_update[] = "UPDATE " . CONFIG_TABLE . " SET version = '$new_version'";
			
			exec_queries($sql_update, true);
		}// end if update
		
		if( is_object($db) )
		{
			$db->close();
		}
		
		if( !$error )
		{
			if( $type == 'install' || $type == 'reinstall' )
			{
				$l_title = $lang['Result_install'];
			}
			else
			{
				$l_title = $lang['Result_update'];
			}
			
			if( $type == 'install' )
			{
				if( !($fw = @fopen(WA_ROOTDIR . '/includes/config.inc.php', 'w')) )
				{
					$output->addHiddenField('dbtype',     $dbtype);
					$output->addHiddenField('dbhost',     $dbhost);
					$output->addHiddenField('dbuser',     $dbuser);
					$output->addHiddenField('dbpassword', $dbpassword);
					$output->addHiddenField('dbname',     $dbname);
					$output->addHiddenField('prefixe',    $prefixe);
					
					$output->assign_block_vars('download_file', array(
						'L_TITLE'         => $l_title,
						'L_DL_BUTTON'     => $lang['Button']['dl'],
						
						'MSG_RESULT'      => nl2br($lang['Success_whithout_config']),						
						'S_HIDDEN_FIELDS' => $output->getHiddenFields()
					));
					
					$output->pparse('body');
					exit;
				}
				
				fwrite($fw, $config_file);
				fclose($fw);
			}
			
			if( $type == 'install' || $type == 'reinstall' )
			{
				$msg_result = $lang['Success_install'];
			}
			else
			{
				$msg_result = $lang['Success_update'];
			}
			
			$output->assign_block_vars('result', array(
				'L_TITLE'    => $l_title,
				'MSG_RESULT' => nl2br(sprintf($msg_result, '<a href="' . WA_ROOTDIR . '/admin/login.php">', '</a>'))
			));
			
			$output->pparse('body');
			exit;
		}
	}
	
	require WA_ROOTDIR . '/includes/functions.box.php';
	
	$db_box = '';
	foreach( $supported_db AS $db_name => $db_infos )
	{
		$selected = ( $dbtype == $db_name ) ? ' selected="selected"' : '';
		$db_box .= '<option value="' . $db_name . '"' . $selected . '> ' . $db_infos['Name'] . ' </option>';
	}
	
	if( $urlsite == '' )
	{
		$urlsite = 'http://' . server_info('HTTP_HOST');
	}
	
	if( $urlscript == '' )
	{
		$urlscript = preg_replace('/^(.*?)\/setup\/?$/i', '\\1/', dirname(server_info('PHP_SELF')));
	}
	
	$output->addHiddenField('prev_language', $language);
	
	$output->assign_block_vars('welcome', array(
		'L_WELCOME'         => nl2br( sprintf($lang['Welcome_in_install'], '<a href="' . WA_ROOTDIR . '/docs/readme.' . $lang['CONTENT_LANG'] . '.html">', '</a>')),
		'TITLE_DATABASE'    => $lang['Title']['database'],
		'TITLE_ADMIN'       => $lang['Title']['admin'],
		'TITLE_DIVERS'      => $lang['Title']['config_divers'],
		'L_DBTYPE'          => $lang['dbtype'],
		'L_DBHOST'          => $lang['dbhost'],
		'L_DBNAME'          => $lang['dbname'],
		'L_DBUSER'          => $lang['dbuser'],
		'L_DBPWD'           => $lang['dbpwd'],
		'L_PREFIXE'         => $lang['prefixe'],
		'L_DEFAULT_LANG'    => $lang['Default_lang'],
		'L_LOGIN'           => $lang['Login'],
		'L_PASS'            => $lang['Password'],
		'L_PASS_CONF'       => $lang['Conf_pass'],
		'L_EMAIL'           => $lang['Email_address'],
		'L_URLSITE'         => $lang['Urlsite'],
		'L_URLSCRIPT'       => $lang['Urlscript'],
		'L_URLSITE_NOTE'    => $lang['Urlsite_note'],
		'L_URLSCRIPT_NOTE'  => $lang['Urlscript_note'],
		'L_BUTTON_START'    => $lang['Start_install'],
		
		'DB_BOX'    => $db_box,
		'DBHOST'    => htmlspecialchars($dbhost),
		'DBNAME'    => htmlspecialchars($dbname),
		'DBUSER'    => htmlspecialchars($dbuser),
		'PREFIXE'   => htmlspecialchars($prefixe),
		'LOGIN'     => htmlspecialchars($admin_login),
		'EMAIL'     => htmlspecialchars($admin_email),
		'URLSITE'   => htmlspecialchars($urlsite),
		'URLSCRIPT' => htmlspecialchars($urlscript),
		'LANG_BOX'  => lang_box($language),
		
		'S_HIDDEN_FIELD' => $output->getHiddenFields()
	));
	
	if( $error )
	{
		$output->error_box($msg_error);
	}
}

$output->pparse('body');

?>