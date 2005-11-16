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

define('IN_UPDATE', true);

require './setup.inc.php';

$lang  = $datetime = $msg_error = $_php_errors = array();
$error = FALSE;
$type  = 'update';

$admin_login = ( !empty($_POST['admin_login']) ) ? trim($_POST['admin_login']) : '';
$admin_pass  = ( !empty($_POST['admin_pass']) ) ? trim($_POST['admin_pass']) : '';

if( isset($supported_db[$dbtype]) )
{
	require WA_ROOTDIR . '/sql/' . $dbtype . '.php';
}
else
{
	plain_error('Le type de base de données n\'est pas défini !');
}

$output->set_filenames( array(
	'body' => 'update.tpl'
));

//
// Connexion base de données et récupération de la configuration 
//
$db = new sql($dbhost, $dbuser, $dbpassword, $dbname);

if( !$db->connect_id )
{
	plain_error('Impossible de se connecter à la base de données');
}

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
	else
	{
		$old_config[$row['nom']] = $row['valeur'];
	}
}

$language = $old_config['language'];
require WA_ROOTDIR . '/language/lang_' . $language . '.php';

$output->send_headers();

$output->assign_vars( array(
	'PAGE_TITLE'   => $lang['Title']['update'],
	'CONTENT_LANG' => $lang['CONTENT_LANG'],
	'CONTENT_DIR'  => $lang['CONTENT_DIR'],
	'NEW_VERSION'  => $new_version,
	'TRANSLATE'    => ( $lang['TRANSLATE'] != '' ) ? ' | Translate by ' . $lang['TRANSLATE'] : ''
));

if( !preg_match('/^(2\.[0-1])[-.0-9a-zA-Z ]+$/', $old_config['version'], $match) )
{
	msg_result($lang['Unknown_version']);
}

$branch = $match[1];

if( isset($_POST['start_update']) )
{
	if( $branch == '2.1' )
	{
		$field_level = 'level';
	}
	else
	{
		$field_level = 'droits';
	}
	
	$login = FALSE;
	
	$sql = "SELECT email, passwd, $field_level 
		FROM " . ADMIN_TABLE . " 
		WHERE LOWER(user) = '" . $db->escape(strtolower($admin_login)) . "'";
	if( $result = $db->query($sql) )
	{
		if( $row = $db->fetch_array($result) )
		{
			if( md5($admin_pass) == $row['passwd'] && $row[$field_level] >= ADMIN )
			{
				$login = TRUE;
				$admin_email     = $row['email'];
				$admin_pass_conf = $row['passwd'];
			}
		}
	}
	
	if( !$login )
	{
		$error = TRUE;
		$msg_error[] = $lang['Message']['Error_login'];
	}
	
	if( !is_writable(WA_ROOTDIR . '/includes/config.inc.php') )
	{
		$error = TRUE;
		$msg_error[] = $lang['File_config_unwritable'];
	}
}

if( !isset($_POST['start_update']) || $error )
{
	$output->assign_block_vars('welcome', array(
		'L_EXPLAIN_UPDATE' => nl2br(sprintf($lang['Welcome_in_update'], '<span style="color: red; font-weight: bold;">' . $old_config['version'] . '</span>')),
		'L_LOGIN'          => $lang['Login'],
		'L_PASS'           => $lang['Password'],
		'L_UPDATE_BUTTON'  => $lang['Start_update']
	));
	
	if( $error )
	{
		$output->error_box($msg_error);
	}
	
	$output->pparse('body');
	exit;
}

//
// Lancement de la mise à jour 
// On allonge le temps maximum d'execution du script. 
//
@set_time_limit(1200);

$sql_file = SCHEMAS_DIR . '/' . $supported_db[$dbtype]['prefixe_file'] . '_tables.sql';

if( !($fp = @fopen($sql_file, 'r')) )
{
	msg_result('sql_file_not_readable');
}

$sql_tmp = make_sql_ary(fread($fp, filesize($sql_file)), $supported_db[$dbtype]['delimiter'], $prefixe);
fclose($fp);

$sql_create = array();
foreach( $sql_tmp AS $query )
{
	preg_match('/' . $prefixe . '([[:alnum:]_-]+)/i', $query, $match);
	
	$sql_create[$match[1]] = $query;
}

unset($sql_tmp);

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
$old_config['hebergeur']     = ( $old_config['hebergeur'] == 3 ) ? 1 : $old_config['hebergeur'];

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
			ADD session_ip CHAR(8) DEFAULT '' NOT NULL, 
			ADD session_liste TINYINT(3) DEFAULT '0' NOT NULL";
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
		ADD auth_ban TINYINT(1) DEFAULT '0' NOT NULL, 
		ADD auth_attach TINYINT(1) DEFAULT '0' NOT NULL, 
		ADD INDEX (`admin_id`)";
	$field_level = 'level';
}

exec_queries($sql_update, true);

//
// Modifications sur la table admin
//
$sql_update = array();

$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . " 
	CHANGE user admin_login VARCHAR(30) NOT NULL default '', 
	CHANGE passwd admin_pwd VARCHAR(32) NOT NULL default '', 
	CHANGE email admin_email VARCHAR(255) NOT NULL default '', 
	CHANGE $field_level admin_level TINYINT(1) NOT NULL default '1', 
	ADD admin_lang VARCHAR(30) DEFAULT '' NOT NULL AFTER admin_email, 
	ADD admin_dateformat VARCHAR(20) DEFAULT '' NOT NULL AFTER admin_lang, 
	ADD email_new_inscrit TINYINT(1) DEFAULT '0' NOT NULL";
$sql_update[] = "UPDATE " . ADMIN_TABLE . " 
	SET admin_lang = '" . $old_config['language'] . "', 
	admin_dateformat = '" . $db->escape($old_config['date_format']) . "', 
	email_new_inscrit = 0";

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
	CHANGE choix_format liste_format TINYINT(1) NOT NULL default '1', 
	CHANGE email_confirm confirm_subscribe TINYINT(1) NOT NULL default '0', 
	ADD COLUMN sender_email VARCHAR(250) DEFAULT '' NOT NULL AFTER liste_format, 
	ADD COLUMN return_email VARCHAR(250) DEFAULT '' NOT NULL AFTER sender_email, 
	ADD COLUMN liste_sig TEXT DEFAULT '' NOT NULL, 
	ADD COLUMN auto_purge TINYINT(1) DEFAULT '0' NOT NULL, 
	ADD COLUMN purge_freq TINYINT(3) UNSIGNED DEFAULT '0' NOT NULL, 
	ADD COLUMN purge_next INT(11) DEFAULT '0' NOT NULL, 
	ADD COLUMN liste_alias varchar(250) NOT NULL DEFAULT '', 
	ADD COLUMN liste_numlogs smallint(5) NOT NULL DEFAULT '0', 
	ADD COLUMN liste_startdate INT(11) DEFAULT '0' NOT NULL, 
	ADD COLUMN use_cron tinyint(1) NOT NULL DEFAULT '0', 
	ADD COLUMN pop_host varchar(100) NOT NULL DEFAULT '', 
	ADD COLUMN pop_port smallint(5) NOT NULL DEFAULT '110', 
	ADD COLUMN pop_user varchar(50) NOT NULL DEFAULT '', 
	ADD COLUMN pop_pass varchar(50) NOT NULL DEFAULT ''";

exec_queries($sql_update, true);

$sql = "SELECT COUNT(*) AS numlogs, liste_id 
	FROM " . LOG_TABLE . " 
	WHERE send = " . STATUS_SENDED . " 
	GROUP BY liste_id";
if( !($result = $db->query($sql)) )
{
	msg_result($sql, true);
}

$num_logs_ary = array();
while( $row = $db->fetch_array($result) )
{
	$num_logs_ary[$row['liste_id']] = $row['numlogs'];
}

$sql = "SELECT liste_id, liste_name FROM " . LISTE_TABLE;
if( !($result = $db->query($sql)) )
{
	msg_result($sql, true);
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
	msg_result($sql, true);
}

$logrow = $db->fetch_rowset($result);

$sql_update = array();
$sql_update[] = "ALTER TABLE " . LOG_TABLE . " 
	CHANGE sujet log_subject VARCHAR(100) NOT NULL default '', 
	CHANGE body_html log_body_html MEDIUMTEXT NOT NULL default '', 
	CHANGE body_text log_body_text MEDIUMTEXT NOT NULL default '', 
	CHANGE date log_date INT(11) NOT NULL default '0', 
	CHANGE send log_status TINYINT(1) NOT NULL default '0', 
	ADD INDEX (liste_id), ADD INDEX (log_status)";
$sql_update[] = "ALTER TABLE " . LOG_TABLE . " 
	ADD COLUMN log_numdest smallint(5) NOT NULL DEFAULT '0'";
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
				msg_result($sql, true);
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
	msg_result($sql, true);
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
	msg_result($sql, true);
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

//
// Modification fichier de configuration + 
// Affichage message de résultat
//
@chmod(WA_ROOTDIR . '/includes', 0777);

if( $fw = @fopen(WA_ROOTDIR . '/includes/config.inc.php', 'w') )
{
	fwrite($fw, $config_file);
	fclose($fw);
	
	@chmod(WA_ROOTDIR . '/includes', 0755);
	
	$message = sprintf($lang['Success_update'], '<a href="' . WA_ROOTDIR . '/admin/login.php">', '</a>');
	msg_result($message);
}
else
{
	@chmod(WA_ROOTDIR . '/includes', 0755);
	
	$message = sprintf($lang['Success_whithout_config2'], htmlspecialchars($config_file));
	msg_result($message);
}

exit;
?>