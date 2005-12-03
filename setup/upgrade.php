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
 * @version $Id: setup.inc.php 239 2005-11-29 14:13:20Z bobe $
 */

define('IN_UPGRADE', true);

require './setup.inc.php';

$confirm = ( isset($_POST['confirm']) ) ? true : false;
$admin_login = ( !empty($_POST['admin_login']) ) ? trim($_POST['admin_login']) : '';
$admin_pass  = ( !empty($_POST['admin_pass']) ) ? trim($_POST['admin_pass']) : '';

if( !defined('NL_INSTALLED') )
{
	plain_error("Wanewsletter ne semble pas installé");
}

$db = new sql($dbhost, $dbuser, $dbpassword, $dbname);

if( !$db->connect_id )
{
	plain_error('Impossible de se connecter à la base de données');
}

//
// Récupération de la configuration
//
$sql = "SELECT * FROM " . CONFIG_TABLE;
if( !($result = $db->query($sql)) )
{
	plain_error("Impossible d'obtenir la configuration du script :\n" . $db->sql_error['message']);
}

$old_config = array();
while( $row = $db->fetch_array($result) )
{
	if( !isset($row['nom']) )
	{
		$old_config = $row;
		break;
	}
	else // branche 2.0
	{
		$old_config[$row['nom']] = $row['valeur'];
	}
}

if( file_exists(WA_ROOTDIR . '/language/lang_' . $old_config['language'] . '.php') )
{
	require WA_ROOTDIR . '/language/lang_' . $old_config['language'] . '.php';
}

$new_version = '2.3-beta1';
$old_config['version'] = '2.2.8';// temp

if( !preg_match('/^(2\.[0-2])[-.0-9a-zA-Z]+$/', $old_config['version'], $match) )
{
	message($lang['Unknown_version']);
}

$branche = $match[1];

$output->set_filenames( array(
	'body' => 'upgrade.tpl'
));

$output->send_headers();

$output->assign_vars( array(
	'PAGE_TITLE'   => $lang['Title']['upgrade'],
	'CONTENT_LANG' => $lang['CONTENT_LANG'],
	'CONTENT_DIR'  => $lang['CONTENT_DIR'],
	'NEW_VERSION'  => $new_version,
	'TRANSLATE'    => ( $lang['TRANSLATE'] != '' ) ? ' | Translate by ' . $lang['TRANSLATE'] : ''
));

if( !is_writable(WA_ROOTDIR . '/includes/config.inc.php') )
{
	$error = true;
	$msg_error[] = $lang['File_config_unwritable'];
}

if( $start )
{
	if( $branche == '2.0' || $branche == '2.1' )
	{
		if( $branche == '2.1' )
		{
			$field_level = 'level';
		}
		else
		{
			$field_level = 'droits';
		}
		
		$sql = "SELECT COUNT(*)
			FROM " . ADMIN_TABLE . "
			WHERE LOWER(user) = '" . $db->escape(strtolower($admin_login)) . "'
				AND passwd = '" . md5($admin_pass) . "'
				AND $field_level >= " . ADMIN;
	}
	else if( $branche == '2.2' )
	{
		$sql = "SELECT COUNT(*)
			FROM " . ADMIN_TABLE . "
			WHERE LOWER(admin_login) = '" . $db->escape(strtolower($admin_login)) . "'
				AND admin_pwd = '" . md5($admin_pass) . "'
				AND admin_level >= " . ADMIN;
	}
	
	$res = $db->query($sql);
	if( $db->result($res, 0) == 0 )
	{
		$error = true;
		$msg_error[] = $lang['Message']['Error_login'];
	}
	
	$sql_create = SCHEMAS_DIR . '/' . $supported_db[$dbtype]['prefixe_file'] . '_tables.sql';
	
	if( !is_readable($sql_create) )
	{
		$error = true;
		$msg_error[] = $lang['Message']['sql_file_not_readable'];
	}
	
	if( !$error )
	{
		//
		// Lancement de la mise à jour
		// On allonge le temps maximum d'execution du script.
		//
		@set_time_limit(1200);
		
		$sql_create = make_sql_ary(implode('', file($sql_create)), $supported_db[$dbtype]['delimiter'], $prefixe);
		
		foreach( $sql_create AS $query )
		{
			preg_match('/' . $prefixe . '([[:alnum:]_-]+)/i', $query, $match);
			
			$sql_create[$match[1]] = $query;
		}
		
		if( $branche == '2.0' || $branche == '2.1' )
		{
			//
			// Reconstruction de la table de configuration
			//
			$old_config['engine_send']   = ( !empty($old_config['engine_send']) ) ? $old_config['engine_send'] : ENGINE_BCC;
			$old_config['emails_sended'] = ( !empty($old_config['emails_sended']) ) ? $old_config['emails_sended'] : 0;
			$old_config['date_format']   = ( !empty($old_config['date_format']) ) ? addslashes($old_config['date_format']) : 'j F Y H:i';
			$old_config['sender_email']  = ( !empty($old_config['sender_email']) ) ? $old_config['sender_email'] : $old_config['emailadmin'];
			$old_config['return_email']  = ( !empty($old_config['return_path_email']) ) ? $old_config['return_path_email'] : '';
			$old_config['signature']     = strip_tags($old_config['signature']);
			$old_config['auto_purge']    = ( !empty($old_config['use_auto_purge']) ) ? $old_config['use_auto_purge'] : 0;
			$old_config['purge_freq']    = ( !empty($old_config['purge_freq']) ) ? $old_config['purge_freq'] : 0;
			$old_config['purge_next']    = ( !empty($old_config['purge_next']) ) ? $old_config['purge_next'] : 0;
			
			$sql_update   = array();
			$sql_update[] = "DROP TABLE " . CONFIG_TABLE;
			$sql_update[] = $sql_create['config'];
			
			$startdate = 0;
			
			$sql = "SELECT MIN(date) FROM " . ABONNES_TABLE;
			if( $result = $db->query($sql) )
			{
				$startdate = $db->result($result, 0, 0);
			}
			
			if( !$startdate )
			{
				$startdate = time();
			}
			
			$sql_update[] = "INSERT INTO " . CONFIG_TABLE . " (sitename, urlsite, path, date_format, session_length, language, cookie_name, cookie_path, upload_path, max_filesize, engine_send, emails_sended, use_smtp, smtp_host, smtp_port, smtp_user, smtp_pass, gd_img_type, mailing_startdate)
				VALUES('" . $db->escape($old_config['sitename']) . "', '" . $db->escape($old_config['urlsite']) . "', '" . $db->escape($old_config['path']) . "', '" . $db->escape($old_config['date_format']) . "', " . $old_config['session_duree'] . ", '" . $old_config['language'] . "', 'wanewsletter', '/', 'admin/upload/', 80000, " . $old_config['engine_send'] . ", " . $old_config['emails_sended'] . ", " . $old_config['use_smtp'] . ", '" . $db->escape($old_config['smtp_host']) . "', '" . $old_config['smtp_port'] . "', '" . $db->escape($old_config['smtp_user']) . "', '" . $db->escape($old_config['smtp_pass']) . "', 'png', $startdate)";
			
			exec_queries($sql_update, true);
			
			//
			// Modif table session + ajout éventuel table ban_list + création des
			// nouvelles tables de la version 2.2
			//
			$sql_update = array();
			
			switch( $old_config['version'] )
			{
				case '2.0Beta':
				case '2.0.0':
				case '2.0.1':
					$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . " MODIFY COLUMN session_id CHAR(32) NOT NULL default ''";
					$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . " TYPE=HEAP";
					
				case '2.0.2':
					$sql_update[] = "UPDATE " . LOG_TABLE . " SET send = 2 WHERE send = 1";
					$sql_update[] = $sql_create['ban_list'];
					
				case '2.1Beta':
				case '2.1Beta2':
				case '2.1.0':
				case '2.1.1':
				case '2.1.2':
				case '2.1.3':
				case '2.1.4':
					$sql_update[] = $sql_create['abo_liste'];
					$sql_update[] = $sql_create['joined_files'];
					$sql_update[] = $sql_create['log_files'];
					$sql_update[] = $sql_create['forbidden_ext'];
					$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . "
						ADD COLUMN session_ip CHAR(8) NOT NULL DEFAULT '',
						ADD COLUMN session_liste TINYINT(3) NOT NULL DEFAULT 0";
					break;
			}
			
			exec_queries($sql_update, true);
			
			//
			// Création/Modification de la table auth_admin
			//
			$sql_update = array();
			
			if( $branch == '2.0' )
			{
				$sql_update[] = $sql_create['auth_admin'];
				$field_level = 'droits';
			}
			else
			{
				$sql_update[] = "ALTER TABLE " . AUTH_ADMIN_TABLE . "
					ADD COLUMN auth_ban    TINYINT(1) NOT NULL DEFAULT 0,
					ADD COLUMN auth_attach TINYINT(1) NOT NULL DEFAULT 0,
					ADD COLUMN cc_admin    SMALLINT   NOT NULL DEFAULT 0,
					ADD INDEX (admin_id)";
				$field_level = 'level';
			}
			
			exec_queries($sql_update, true);
			
			//
			// Modifications sur la table admin
			//
			$sql_update = array();
			
			$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
				CHANGE user admin_login VARCHAR(30) NOT NULL DEFAULT '',
				CHANGE passwd admin_pwd VARCHAR(32) NOT NULL DEFAULT '',
				CHANGE email admin_email VARCHAR(255) NOT NULL DEFAULT '',
				CHANGE $field_level admin_level TINYINT(1) NOT NULL DEFAULT 1,
				ADD COLUMN admin_lang VARCHAR(30) NOT NULL DEFAULT '' AFTER admin_email,
				ADD COLUMN admin_dateformat VARCHAR(20) NOT NULL DEFAULT '' AFTER admin_lang,
				ADD COLUMN email_new_subscribe TINYINT(1) NOT NULL DEFAULT 0,
				ADD COLUMN email_unsubscribe SMALLINT NOT NULL DEFAULT 0";
			$sql_update[] = "UPDATE " . ADMIN_TABLE . "
				SET admin_lang = '" . $old_config['language'] . "',
				admin_dateformat = '" . $db->escape($old_config['date_format']) . "',
				email_new_subscribe = 0";
			
			if( $branch == '2.0' )
			{
				$sql_update[] = "UPDATE " . ADMIN_TABLE . " SET admin_level = 1 WHERE admin_level = 2";
				$sql_update[] = "UPDATE " . ADMIN_TABLE . " SET admin_level = 2 WHERE admin_level = 3";
			}
			
			exec_queries($sql_update, true);
			
			//
			// Modifications sur la table liste
			//
			$sql_update = array();
			$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
				CHANGE nom liste_name VARCHAR(100) NOT NULL default '',
				CHANGE choix_format liste_format TINYINT(1) NOT NULL default 1,
				CHANGE email_confirm confirm_subscribe TINYINT(1) NOT NULL default 0,
				ADD COLUMN liste_public SMALLINT NOT NULL DEFAULT 1 AFTER liste_name,
				ADD COLUMN sender_email VARCHAR(250) NOT NULL DEFAULT '' AFTER liste_format,
				ADD COLUMN return_email VARCHAR(250) NOT NULL DEFAULT '' AFTER sender_email,
				ADD COLUMN liste_sig TEXT NOT NULL DEFAULT '',
				ADD COLUMN auto_purge TINYINT(1) NOT NULL DEFAULT 0,
				ADD COLUMN purge_freq TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
				ADD COLUMN purge_next INTEGER NOT NULL DEFAULT 0,
				ADD COLUMN liste_alias VARCHAR(250) NOT NULL DEFAULT '',
				ADD COLUMN liste_numlogs SMALLINT NOT NULL DEFAULT 0,
				ADD COLUMN liste_startdate INTEGER NOT NULL DEFAULT 0,
				ADD COLUMN use_cron TINYINT(1) NOT NULL DEFAULT 0,
				ADD COLUMN pop_host VARCHAR(100) NOT NULL DEFAULT '',
				ADD COLUMN pop_port SMALLINT NOT NULL DEFAULT 110,
				ADD COLUMN pop_user VARCHAR(100) NOT NULL DEFAULT '',
				ADD COLUMN pop_pass VARCHAR(100) NOT NULL DEFAULT ''";
			
			exec_queries($sql_update, true);
			
			$sql = "SELECT COUNT(*) AS numlogs, liste_id
				FROM " . LOG_TABLE . "
				WHERE send = " . STATUS_SENDED . "
				GROUP BY liste_id";
			if( !($result = $db->query($sql)) )
			{
				sql_error();
			}
			
			$num_logs_ary = array();
			while( $row = $db->fetch_array($result) )
			{
				$num_logs_ary[$row['liste_id']] = $row['numlogs'];
			}
			
			$sql = "SELECT liste_id, liste_name FROM " . LISTE_TABLE;
			if( !($result = $db->query($sql)) )
			{
				sql_error();
			}
			
			$sql_update = array();
			
			while( $row = $db->fetch_array($result) )
			{
				$numlogs = ( !empty($num_logs_ary[$row['liste_id']]) ) ? $num_logs_ary[$row['liste_id']] : 0;
				
				$sql_update[] = "UPDATE " . LISTE_TABLE . "
					SET liste_name      = '" . $db->escape(htmlspecialchars($row['liste_name'])) . "',
						sender_email    = '$old_config[sender_email]',
						return_email    = '$old_config[return_email]',
						liste_sig       = '" . $db->escape($old_config['signature']) . "',
						auto_purge      = '$old_config[auto_purge]',
						purge_freq      = '$old_config[purge_freq]',
						purge_next      = '$old_config[purge_next]',
						liste_startdate = $startdate,
						liste_numlogs   = $numlogs
					WHERE liste_id = " . $row['liste_id'];
			}
			
			if( $branch == '2.1' )
			{
				$sql_update[] = "ALTER TABLE " . LISTE_TABLE . " DROP COLUMN email_new_inscrit";
			}
			
			exec_queries($sql_update, true);
			
			//
			// Modifications sur la table log
			//
			$sql = "SELECT log_id, attach FROM " . LOG_TABLE;
			if( !($result = $db->query($sql)) )
			{
				sql_error();
			}
			
			$logrow = $db->fetch_rowset($result);
			
			$sql_update = array();
			$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
				CHANGE sujet log_subject VARCHAR(100) NOT NULL default '',
				CHANGE body_html log_body_html TEXT NOT NULL default '',
				CHANGE body_text log_body_text TEXT NOT NULL default '',
				CHANGE date log_date INTEGER NOT NULL default 0,
				CHANGE send log_status TINYINT(1) NOT NULL default 0,
				ADD INDEX (liste_id), ADD INDEX (log_status)";
			$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
				ADD COLUMN log_numdest SMALLINT NOT NULL DEFAULT 0";
			$sql_update[] = "ALTER TABLE " . LOG_TABLE . " DROP COLUMN attach";
			
			exec_queries($sql_update, true);
			
			require WAMAILER_DIR . '/class.mailer.php';
			
			$total_log = count($logrow);
			for( $i = 0; $i < $total_log; $i++ )
			{
				if( $logrow[$i]['attach'] == '' )
				{
					continue;
				}
				
				$files = array_map('trim', explode(',', $logrow[$i]['attach']));
				
				for( $j = 0, $total_files = count($files); $j < $total_files; $j++ )
				{
					$mime_type = Mailer::mime_type(substr($files[$j], (strrpos($files[$j], '.') + 1)));
					
					$filesize = 0;
					if( file_exists(WA_ROOTDIR . '/admin/upload/' . $files[$j]) )
					{
						$filesize = filesize(WA_ROOTDIR . '/admin/upload/' . $files[$j]);
					}
					
					$sql = "INSERT INTO " . JOINED_FILES_TABLE . " (file_real_name, file_physical_name, file_size, file_mimetype)
						VALUES('" . $db->escape($files[$j]) . "', '" . $db->escape($files[$j]) . "', " . intval($filesize) . ", '" . $db->escape($mime_type) . "')";
					if( $db->query($sql) )
					{
						$file_id = $db->next_id();
						
						$sql = "INSERT INTO " . LOG_FILES_TABLE . " (log_id, file_id)
							VALUES(" . $logrow[$i]['log_id'] . ", $file_id)";
						if( !$db->query($sql) )
						{
							sql_error();
						}
					}
				}
			}
			
			unset($logrow);
			
			//
			// Modifications sur la table abonnes et insertions table abo_liste +
			// élimination des doublons
			//
			$sql = "SELECT * FROM " . ABONNES_TABLE;
			if( !($result = $db->query($sql)) )
			{
				sql_error();
			}
			
			$aborow = $db->fetch_rowset($result);
			
			$sql_update	  = array();
			$sql_update[] = "DROP TABLE " . ABONNES_TABLE;
			$sql_update[] = $sql_create['abonnes'];
			
			exec_queries($sql_update, true);
			
			$abo_ary = array();
			
			$total_abo = count($aborow);
			for( $i = 0; $i < $total_abo; $i++ )
			{
				if( !isset($abo_ary[$aborow[$i]['email']]) )
				{
					$abo_ary[$aborow[$i]['email']] = array();
					
					$abo_ary[$aborow[$i]['email']]['code']   = $aborow[$i]['code'];
					$abo_ary[$aborow[$i]['email']]['date']   = $aborow[$i]['date'];
					$abo_ary[$aborow[$i]['email']]['status'] = $aborow[$i]['actif'];
					$abo_ary[$aborow[$i]['email']]['listes'] = array();
				}
				
				$abo_ary[$aborow[$i]['email']]['listes'][$aborow[$i]['liste_id']] = array(
					'format' => $aborow[$i]['format'],
					'send'   => ( !empty($aborow[$i]['send']) ) ? $aborow[$i]['send'] : 0
				);
			}
			
			foreach( $abo_ary AS $email => $data )
			{
				$sql = "INSERT INTO " . ABONNES_TABLE . " (abo_email, abo_lang, abo_register_key, abo_register_date, abo_status)
					VALUES('" . $db->escape($email) . "', '$language', '" . $db->escape($data['code']) . "', " . $data['date'] . ", " . $data['status'] . ")";
				exec_queries($sql, true);
				
				$abo_id = $db->next_id();
				$sql_update = array();
				
				foreach( $data['listes'] AS $liste_id => $listdata )
				{
					$sql_update[] = "INSERT INTO " . ABO_LISTE_TABLE . " (abo_id, liste_id, format, send)
						VALUES($abo_id, $liste_id, $listdata[format], $listdata[send])";
				}
				
				exec_queries($sql_update, true);
			}
			
			unset($aborow, $abo_ary);
			
			//
			// Mise à jour de la table des logs
			//
			$sql_update = array();
			
			$sql = "SELECT COUNT(DISTINCT(a.abo_id)) AS num_dest, al.liste_id
				FROM " . ABONNES_TABLE . " AS a, " . ABO_LISTE_TABLE . " AS al
				WHERE a.abo_id = al.abo_id AND a.abo_status = " . ABO_ACTIF . "
				GROUP BY al.liste_id";
			if( !($result = $db->query($sql)) )
			{
				sql_error();
			}
			
			while( $row = $db->fetch_array($result) )
			{
				$sql_update[] = "UPDATE " . LOG_TABLE . "
					SET log_numdest = $row[num_dest]
					WHERE liste_id = " . $row['liste_id'];
			}
			
			exec_queries($sql_update, true);
			
			// END
			$sql = "UPDATE " . CONFIG_TABLE . " SET version = '$new_version'";
			
			exec_queries($sql, true);
		}
		else if( $branche == '2.2' )
		{
			$sql_update = array();
			
			switch( $old_config['version'] )
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
								ADD COLUMN smtp_user varchar(100) NOT NULL DEFAULT '',
								ADD COLUMN smtp_pass varchar(100) NOT NULL DEFAULT ''";
							$sql_update[] = "UPDATE " . CONFIG_TABLE . "
								SET smtp_user = smtp_user_old, smtp_pass = smtp_pass_old";
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								DROP COLUMN smtp_user_old,
								DROP COLUMN smtp_pass_old";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN liste_alias VARCHAR(250) NOT NULL DEFAULT ''";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN use_cron SMALLINT NOT NULL DEFAULT 0";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN pop_host VARCHAR(100) NOT NULL DEFAULT ''";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN pop_port SMALLINT NOT NULL DEFAULT 110";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN pop_user VARCHAR(100) NOT NULL DEFAULT ''";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN pop_pass VARCHAR(100) NOT NULL DEFAULT ''";
							break;
						
						default:
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								MODIFY COLUMN smtp_user VARCHAR(100) NOT NULL DEFAULT '',
								MODIFY COLUMN smtp_pass VARCHAR(100) NOT NULL DEFAULT ''";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN liste_alias VARCHAR(250) NOT NULL DEFAULT '',
								ADD COLUMN use_cron TINYINT(1) NOT NULL DEFAULT 0,
								ADD COLUMN pop_host VARCHAR(100) NOT NULL DEFAULT '',
								ADD COLUMN pop_port SMALLINT NOT NULL DEFAULT 110,
								ADD COLUMN pop_user VARCHAR(100) NOT NULL DEFAULT '',
								ADD COLUMN pop_pass VARCHAR(100) NOT NULL DEFAULT ''";
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
						sql_error();
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
						sql_error();
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
								ADD COLUMN liste_numlogs SMALLINT NOT NULL DEFAULT 0";
							$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
								ADD COLUMN log_numdest SMALLINT NOT NULL DEFAULT 0";
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN check_email_mx SMALLINT NOT NULL DEFAULT 0";
							break;
						
						default:
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN liste_numlogs SMALLINT NOT NULL DEFAULT 0 AFTER liste_alias";
							$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
								ADD COLUMN log_numdest SMALLINT NOT NULL DEFAULT 0 AFTER log_date";
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN check_email_mx TINYINT(1) NOT NULL DEFAULT 0 AFTER gd_img_type";
							$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . " DROP INDEX abo_id";
							$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . " DROP INDEX liste_id";
							$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . "
								ADD PRIMARY KEY (abo_id , liste_id)";
							$sql_update[] = "ALTER TABLE " . LOG_FILES_TABLE . " DROP INDEX log_id";
							$sql_update[] = "ALTER TABLE " . LOG_FILES_TABLE . " DROP INDEX file_id";
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
						sql_error();
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
						sql_error();
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
								ADD COLUMN enable_profil_cp SMALLINT NOT NULL DEFAULT 0";
							$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
								ADD COLUMN abo_lang VARCHAR(30) NOT NULL DEFAULT ''";
							break;
						
						default:
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN enable_profil_cp TINYINT(1) NOT NULL DEFAULT 0 AFTER check_email_mx";
							$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
								ADD COLUMN abo_lang VARCHAR(30) NOT NULL DEFAULT '' AFTER abo_email";
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
						sql_error();
					}
					
					while( $row = $db->fetch_array($result) )
					{
						if( $row['num_abo'] == $row['num_send'] )
						{
							$sql_update[] = "UPDATE " . ABO_LISTE_TABLE . "
								SET send = 0
								WHERE liste_id = " . $row['liste_id'];
						}
					}
					
					$sql_update[] = "UPDATE " . ABONNES_TABLE . " SET abo_lang = '$language'";
					
				case '2.2-RC3':
					switch( DATABASE )
					{
						case 'postgre':
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN ftp_port SMALLINT NOT NULL DEFAULT 21";
							break;

						default:
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN ftp_port SMALLINT NOT NULL DEFAULT 21 AFTER ftp_server";
							$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
								CHANGE abo_id abo_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT";
							$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . "
								CHANGE abo_id abo_id INTEGER UNSIGNED NOT NULL DEFAULT 0";
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
					$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . " DROP COLUMN hebergeur";
					
					switch( DATABASE )
					{
						case 'postgre':
							$sql_update[] = "DROP INDEX abo_status_wa_abonnes_index ON " . ABONNES_TABLE;
							$sql_update[] = "DROP INDEX admin_id_wa_auth_admin_index ON " . AUTH_ADMIN_TABLE;
							$sql_update[] = "DROP INDEX liste_id_wa_log_index ON " . LOG_TABLE;
							$sql_update[] = "DROP INDEX log_status_wa_log_index ON " . LOG_TABLE;
							$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
								RENAME COLUMN email_new_inscrit email_new_subscribe";
							$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
								ADD COLUMN email_unsubscribe SMALLINT NOT NULL DEFAULT 0";
							$sql_update[] = "ALTER TABLE " . AUTH_ADMIN_TABLE . "
								ADD COLUMN cc_admin SMALLINT NOT NULL DEFAULT 0";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN liste_public SMALLINT NOT NULL DEFAULT 1";
							break;
						
						default:
							$sql_update[] = "DROP INDEX abo_status ON " . ABONNES_TABLE;
							$sql_update[] = "DROP INDEX admin_id ON " . AUTH_ADMIN_TABLE;
							$sql_update[] = "DROP INDEX liste_id ON " . LOG_TABLE;
							$sql_update[] = "DROP INDEX log_status ON " . LOG_TABLE;
							$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
								CHANGE email_new_inscrit email_new_subscribe TINYINT(1) NOT NULL DEFAULT 0,
								ADD COLUMN email_unsubscribe TINYINT(1) NOT NULL DEFAULT 0";
							$sql_update[] = "ALTER TABLE " . AUTH_ADMIN_TABLE . "
								ADD COLUMN cc_admin TINYINT(1) NOT NULL DEFAULT 0";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN liste_public TINYINT(1) NOT NULL DEFAULT 1 AFTER liste_name";
							break;
					}
					
					$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . "
						ADD COLUMN register_key CHAR(20) DEFAULT NULL,
						ADD COLUMN register_date INTEGER NOT NULL DEFAULT 0,
						ADD COLUMN confirmed SMALLINT NOT NULL DEFAULT 0";
					
					$sql = "SELECT abo_id, abo_register_key, abo_pwd, abo_register_date, abo_status
						FROM " . ABONNES_TABLE;
					if( !($result = $db->query($sql)) )
					{
						sql_error();
					}
					
					while( $row = $db->fetch_array($result) )
					{
						$sql = "UPDATE " . ABO_LISTE_TABLE . "
							SET register_date = $row[abo_register_date],
								confirmed     = $row[abo_status]";
						if( $row['abo_status'] == ABO_INACTIF )
						{
							$sql .= ", register_key = '" . substr($row['abo_register_key'], 0, 20) . "'";
						}
						$db->query($sql . " WHERE abo_id = $row[abo_id]");
						
						if( empty($row['abo_pwd']) )
						{
							$db->query("UPDATE " . ABONNES_TABLE . "
								SET abo_pwd = '" . md5($row['abo_register_key']) . "'
								WHERE abo_id = $row[abo_id]");
						}
					}
					
					$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
						DROP COLUMN abo_register_key,
						DROP COLUMN abo_register_date";
					$sql_update[] = "CREATE INDEX abo_status_idx ON " . ABONNES_TABLE . " (abo_status)";
					$sql_update[] = "CREATE UNIQUE INDEX abo_email_idx ON " . ABONNES_TABLE . " (abo_email)";
					$sql_update[] = "CREATE UNIQUE INDEX register_key_idx ON " . ABO_LISTE_TABLE . " (register_key)";
					$sql_update[] = "CREATE INDEX admin_id_idx ON " . AUTH_ADMIN_TABLE . " (admin_id)";
					$sql_update[] = "CREATE INDEX liste_id_idx ON " . LOG_TABLE . " (liste_id)";
					$sql_update[] = "CREATE INDEX log_status_idx ON " . LOG_TABLE . " (log_status)";
					break;
				
				default:
					message($lang['Update_not_required']);
					break;
			}
			
			$sql_update[] = "UPDATE " . CONFIG_TABLE . " SET version = '$new_version'";
			
			exec_queries($sql_update, true);
		}
		
		if( $branche != '2.2' )
		{
			//
			// Modification fichier de configuration +
			// Affichage message de résultat
			//
			if( $fw = @fopen(WA_ROOTDIR . '/includes/config.inc.php', 'w') )
			{
				fwrite($fw, $config_file);
				fclose($fw);
				
				$config = true;
			}
			else
			{
				$config = false;
			}
		}
		else
		{
			$config = true;
		}
		
		//
		// Modification fichier de configuration +
		// Affichage message de résultat
		//
		if( $config == true )
		{
			$message = sprintf($lang['Success_upgrade'], '<a href="' . WA_ROOTDIR . '/admin/login.php">', '</a>');
			message($message);
		}
		else
		{
			$message = sprintf($lang['Success_without_config2'], htmlspecialchars($config_file));
			message($message);
		}
	}
}

$output->assign_block_vars('upgrade', array(
	'L_EXPLAIN'      => nl2br(sprintf($lang['Welcome_in_upgrade'], $old_config['version'])),
	'L_LOGIN'        => $lang['Login'],
	'L_PASS'         => $lang['Password'],
	'L_START_BUTTON' => $lang['Start_upgrade']
));

if( $error )
{
	$output->error_box($msg_error);
}

$output->pparse('body');

?>
