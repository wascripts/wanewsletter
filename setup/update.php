<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation; either version 2 
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
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

$lang  = $datetime = $msg_error = array();
$error = FALSE;
$type  = 'update';

$admin_login = ( !empty($_POST['admin_login']) ) ? trim($_POST['admin_login']) : '';
$admin_pass  = ( !empty($_POST['admin_pass']) ) ? trim($_POST['admin_pass']) : '';

if( isset($supported_db[$dbtype]) )
{
	include $waroot . 'sql/' . $dbtype . '.php';
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
require $waroot . 'language/lang_' . $language . '.php';

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
	
	if( !is_writable($waroot . 'includes/config.inc.php') )
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

$sql_file = $schemas_dir . $supported_db[$dbtype]['prefixe_file'] . '_tables.sql';

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

if( DATABASE == 'mssql' )
{
	$sql_update[] = "ALTER TABLE [" . CONFIG_TABLE . "] WITH NOCHECK ADD 
		CONSTRAINT [DF_" . CONFIG_TABLE . "_hebergeur] DEFAULT (0) FOR [hebergeur],
		CONSTRAINT [DF_" . CONFIG_TABLE . "_session_length] DEFAULT (0) FOR [session_length],
		CONSTRAINT [DF_" . CONFIG_TABLE . "_max_filesize] DEFAULT (0) FOR [max_filesize],
		CONSTRAINT [DF_" . CONFIG_TABLE . "_use_ftp] DEFAULT (0) FOR [use_ftp],
		CONSTRAINT [DF_" . CONFIG_TABLE . "_ftp_pasv] DEFAULT (0) FOR [ftp_pasv],
		CONSTRAINT [DF_" . CONFIG_TABLE . "_engine_send] DEFAULT (0) FOR [engine_send],
		CONSTRAINT [DF_" . CONFIG_TABLE . "_emails_sended] DEFAULT (0) FOR [emails_sended],
		CONSTRAINT [DF_" . CONFIG_TABLE . "_use_smtp] DEFAULT (0) FOR [use_smtp],
		CONSTRAINT [DF_" . CONFIG_TABLE . "_smtp_port] DEFAULT (0) FOR [smtp_port],
		CONSTRAINT [DF_" . CONFIG_TABLE . "_disable_stats] DEFAULT (0) FOR [disable_stats],
		CONSTRAINT [DF_" . CONFIG_TABLE . "_check_email_mx] DEFAULT (0) FOR [check_email_mx],
		CONSTRAINT [DF_" . CONFIG_TABLE . "_enable_profil_cp] DEFAULT (0) FOR [enable_profil_cp],
		CONSTRAINT [DF_" . CONFIG_TABLE . "_mailing_startdate] DEFAULT (0) FOR [mailing_startdate]";
}

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

$sql_update[] = "INSERT INTO " . CONFIG_TABLE . " (sitename, urlsite, path, hebergeur, date_format, session_length, language, cookie_name, cookie_path, upload_path, max_filesize, engine_send, emails_sended, use_smtp, smtp_host, smtp_port, smtp_user, smtp_pass, gd_img_type, mailing_startdate) 
	VALUES('" . $db->escape(addslashes($old_config['sitename'])) . "', '" . $old_config['urlsite'] . "', '" . $old_config['path'] . "', '" . $old_config['hebergeur'] . "', '" . $db->escape(addslashes($old_config['date_format'])) . "', " . $old_config['session_duree'] . ", '" . $old_config['language'] . "', 'wanewsletter', '/', 'admin/upload/', 80000, " . $old_config['engine_send'] . ", " . $old_config['emails_sended'] . ", " . $old_config['use_smtp'] . ", '" . $old_config['smtp_host'] . "', '" . $old_config['smtp_port'] . "', '" . $old_config['smtp_user'] . "', '" . $old_config['smtp_pass'] . "', 'png', $startdate)";

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
		
		if( DATABASE == 'mssql' )
		{
			$sql_update[] = "ALTER TABLE [" . BANLIST_TABLE . "] WITH NOCHECK 
				ADD CONSTRAINT [PK_" . BANLIST_TABLE . "] PRIMARY KEY  CLUSTERED ([ban_id]) ON [PRIMARY]";
			$sql_update[] = "ALTER TABLE [" . BANLIST_TABLE . "] WITH NOCHECK 
				ADD CONSTRAINT [DF_" . BANLIST_TABLE . "_liste_id] DEFAULT (0) FOR [liste_id]";
		}
		
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
		
		switch( DATABASE )
		{
			case 'mssql':
				$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . " ADD session_ip char (8) NOT NULL";
				$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . " 
					ADD session_liste smallint NOT NULL, 
					CONSTRAINT [DF_" . SESSIONS_TABLE . "_session_liste] DEFAULT (0) FOR [session_liste]";
				
				$sql_update[] = "ALTER TABLE [" . ABO_LISTE_TABLE . "] WITH NOCHECK ADD 
					CONSTRAINT [DF_" . ABO_LISTE_TABLE . "_liste_id] DEFAULT (0) FOR [liste_id],
					CONSTRAINT [DF_" . ABO_LISTE_TABLE . "_format] DEFAULT (1) FOR [format],
					CONSTRAINT [DF_" . ABO_LISTE_TABLE . "_send] DEFAULT (0) FOR [send]";
				$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . " ADD 
					CONSTRAINT [PK_" . ABO_LISTE_TABLE . "] PRIMARY KEY CLUSTERED ([abo_id], [liste_id]) ON [PRIMARY]";
				
				$sql_update[] = "ALTER TABLE [" . FORBIDDEN_EXT_TABLE . "] WITH NOCHECK ADD 
					CONSTRAINT [PK_" . FORBIDDEN_EXT_TABLE . "] PRIMARY KEY	 CLUSTERED ([fe_id])  ON [PRIMARY]";
				$sql_update[] = "ALTER TABLE [" . FORBIDDEN_EXT_TABLE . "] WITH NOCHECK ADD 
					CONSTRAINT [DF_" . FORBIDDEN_EXT_TABLE . "_liste_id] DEFAULT (0) FOR [liste_id]";
				
				$sql_update[] = "ALTER TABLE [" . JOINED_FILES_TABLE . "] WITH NOCHECK ADD 
					CONSTRAINT [PK_" . JOINED_FILES_TABLE . "] PRIMARY KEY	CLUSTERED ([file_id]) ON [PRIMARY]";
				$sql_update[] = "ALTER TABLE [" . JOINED_FILES_TABLE . "] WITH NOCHECK ADD 
					CONSTRAINT [DF_" . JOINED_FILES_TABLE . "_file_size] DEFAULT (0) FOR [file_size]";
				
				$sql_update[] = "ALTER TABLE [" . LOG_FILES_TABLE . "] WITH NOCHECK ADD 
					CONSTRAINT [DF_" . LOG_FILES_TABLE . "_log_id] DEFAULT (0) FOR [log_id],
					CONSTRAINT [DF_" . LOG_FILES_TABLE . "_file_id] DEFAULT (0) FOR [file_id]";
				$sql_update[] = "ALTER TABLE " . LOG_FILES_TABLE . " ADD 
					CONSTRAINT [PK_" . LOG_FILES_TABLE . "] PRIMARY KEY CLUSTERED ([log_id], [file_id]) ON [PRIMARY]";
				break;
			
			default:
				$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . " 
					ADD session_ip CHAR(8) DEFAULT '' NOT NULL, 
					ADD session_liste TINYINT(3) DEFAULT '0' NOT NULL";
				break;
		}		
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
	
	if( DATABASE == 'mssql' )
	{
		$sql_update[] = "ALTER TABLE [" . AUTH_ADMIN_TABLE . "] WITH NOCHECK ADD 
			CONSTRAINT [DF_" . AUTH_ADMIN_TABLE . "_admin_id] DEFAULT (0) FOR [admin_id], 
			CONSTRAINT [DF_" . AUTH_ADMIN_TABLE . "_liste_id] DEFAULT (0) FOR [liste_id], 
			CONSTRAINT [DF_" . AUTH_ADMIN_TABLE . "_auth_view] DEFAULT (0) FOR [auth_view], 
			CONSTRAINT [DF_" . AUTH_ADMIN_TABLE . "_auth_edit] DEFAULT (0) FOR [auth_edit], 
			CONSTRAINT [DF_" . AUTH_ADMIN_TABLE . "_auth_del] DEFAULT (0) FOR [auth_del], 
			CONSTRAINT [DF_" . AUTH_ADMIN_TABLE . "_auth_send] DEFAULT (0) FOR [auth_send], 
			CONSTRAINT [DF_" . AUTH_ADMIN_TABLE . "_auth_import] DEFAULT (0) FOR [auth_import], 
			CONSTRAINT [DF_" . AUTH_ADMIN_TABLE . "_auth_export] DEFAULT (0) FOR [auth_export],
			CONSTRAINT [DF_" . AUTH_ADMIN_TABLE . "_auth_ban] DEFAULT (0) FOR [auth_ban],
			CONSTRAINT [DF_" . AUTH_ADMIN_TABLE . "_auth_attach] DEFAULT (0) FOR [auth_attach]";
	}
	
	$field_level = 'droits';
}
else
{
	switch( DATABASE )
	{
		case 'mssql':
			$sql_update[] = "ALTER TABLE " . AUTH_ADMIN_TABLE . " 
				ADD auth_ban smallint NOT NULL, 
				ADD auth_attach smallint NOT NULL, 
				CONSTRAINT [DF_" . AUTH_ADMIN_TABLE . "_auth_ban] DEFAULT (0) FOR [auth_ban], 
				CONSTRAINT [DF_" . AUTH_ADMIN_TABLE . "_auth_attach] DEFAULT (0) FOR [auth_attach]";
			break;
		
		default:
			$sql_update[] = "ALTER TABLE " . AUTH_ADMIN_TABLE . " 
				ADD auth_ban TINYINT(1) DEFAULT '0' NOT NULL, 
				ADD auth_attach TINYINT(1) DEFAULT '0' NOT NULL, 
				ADD INDEX (`admin_id`)";
			break;
	}
	
	$field_level = 'level';
}

if( DATABASE == 'mssql' )
{
	$sql_update[] = "CREATE INDEX [IX_" . AUTH_ADMIN_TABLE . "] 
		ON [" . AUTH_ADMIN_TABLE . "]([admin_id]) ON [PRIMARY]";
}

exec_queries($sql_update, true);

//
// Modifications sur la table admin
//
$sql_update = array();

switch( DATABASE )
{
	case 'mssql':
		$sql_update[] = "exec sp_rename " . ADMIN_TABLE . ".user, " . ADMIN_TABLE . ".admin_login, 'COLUMN'";
		$sql_update[] = "exec sp_rename " . ADMIN_TABLE . ".passwd, " . ADMIN_TABLE . ".admin_pwd, 'COLUMN'";
		$sql_update[] = "exec sp_rename " . ADMIN_TABLE . ".email, " . ADMIN_TABLE . ".admin_email, 'COLUMN'";
		$sql_update[] = "exec sp_rename " . ADMIN_TABLE . ".$field_level, " . ADMIN_TABLE . ".admin_level, 'COLUMN'";
		
		$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . " 
			ADD admin_lang varchar (30) NOT NULL, 
			ADD admin_dateformat varchar (20) NOT NULL, 
			ADD email_new_inscrit smallint NOT NULL, 
			CONSTRAINT [DF_" . ADMIN_TABLE . "_email_new_inscrit] DEFAULT (0) FOR [email_new_inscrit]";
		
		$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . " 
			ALTER COLUMN [admin_login] [varchar] (30) NOT NULL";
		break;
	
	default:
		$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . " 
			CHANGE user admin_login VARCHAR(30) NOT NULL default '', 
			CHANGE passwd admin_pwd VARCHAR(32) NOT NULL default '', 
			CHANGE email admin_email VARCHAR(255) NOT NULL default '', 
			CHANGE $field_level admin_level TINYINT(1) NOT NULL default '1', 
			ADD admin_lang VARCHAR(30) DEFAULT '' NOT NULL AFTER admin_email, 
			ADD admin_dateformat VARCHAR(20) DEFAULT '' NOT NULL AFTER admin_lang, 
			ADD email_new_inscrit TINYINT(1) DEFAULT '0' NOT NULL";
		break;
}

$sql_update[] = "UPDATE " . ADMIN_TABLE . " 
	SET admin_lang = '" . $old_config['language'] . "', 
	admin_dateformat = '" . $db->escape(addslashes($old_config['date_format'])) . "', 
	email_new_inscrit = 0";

if( $branch == '2.0' )
{
	$sql_update[] = "UPDATE " . ADMIN_TABLE . " SET admin_level = 1 WHERE admin_level = 2 OR admin_level = 1";
	$sql_update[] = "UPDATE " . ADMIN_TABLE . " SET admin_level = 2 WHERE admin_level = 3";
}

exec_queries($sql_update, true);

//
// Modifications sur la table liste
//
$sql_update = array();

switch( DATABASE )
{
	case 'mssql':
		$sql_update[] = "exec sp_rename " . LISTE_TABLE . ".nom, " . LISTE_TABLE . ".liste_name, 'COLUMN'";
		$sql_update[] = "exec sp_rename " . LISTE_TABLE . ".choix_format, " . LISTE_TABLE . ".liste_format, 'COLUMN'";
		$sql_update[] = "exec sp_rename " . LISTE_TABLE . ".email_confirm, " . LISTE_TABLE . ".confirm_subscribe, 'COLUMN'";
		
		$sql_update[] = "ALTER TABLE " . LISTE_TABLE . " ADD 
			sender_email varchar (250) NOT NULL, 
			return_email varchar (250) NOT NULL, 
			liste_sig text NOT NULL, 
			auto_purge smallint NOT NULL, 
			purge_freq smallint NOT NULL, 
			purge_next int NOT NULL, 
			liste_startdate int NOT NULL, 
			liste_alias varchar(250) NOT NULL, 
			liste_numlogs smallint NOT NULL, 
			use_cron smallint NOT NULL, 
			pop_host varchar(100) NOT NULL, 
			pop_port smallint NOT NULL, 
			pop_user varchar(50) NOT NULL, 
			pop_pass varchar(50) NOT NULL, 
			CONSTRAINT [DF_" . LISTE_TABLE . "_auto_purge] DEFAULT (0) FOR [auto_purge], 
			CONSTRAINT [DF_" . LISTE_TABLE . "_purge_freq] DEFAULT (0) FOR [purge_freq], 
			CONSTRAINT [DF_" . LISTE_TABLE . "_purge_next] DEFAULT (0) FOR [purge_next], 
			CONSTRAINT [DF_" . LISTE_TABLE . "_liste_startdate] DEFAULT (0) FOR [liste_startdate], 
			CONSTRAINT [DF_" . LISTE_TABLE . "_use_cron] DEFAULT (0) FOR [use_cron], 
			CONSTRAINT [DF_" . LISTE_TABLE . "_pop_port] DEFAULT (110) FOR [pop_port], 
			CONSTRAINT [DF_" . LISTE_TABLE . "_liste_numlogs] DEFAULT (0) FOR [liste_numlogs]";
		break;
	
	default:
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
		break;
}

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
		SET liste_name = '" . $db->escape(addslashes(htmlspecialchars($row['liste_name']))) . "', 
			sender_email = '" . $old_config['sender_email'] . "', 
			return_email = '" . $old_config['return_email'] . "', 
			liste_sig = '" . $db->escape(addslashes($old_config['signature'])) . "', 
			auto_purge = '" . $old_config['auto_purge'] . "', 
			purge_freq = '" . $old_config['purge_freq'] . "', 
			purge_next = '" . $old_config['purge_next'] . "', 
			liste_startdate = $startdate, 
			liste_numlogs = $numlogs 
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

switch( DATABASE )
{
	case 'mssql':
		$sql_update[] = "exec sp_rename " . LOG_TABLE . ".sujet, " . LOG_TABLE . ".log_subject, 'COLUMN'";
		$sql_update[] = "exec sp_rename " . LOG_TABLE . ".body_html, " . LOG_TABLE . ".log_body_html, 'COLUMN'";
		$sql_update[] = "exec sp_rename " . LOG_TABLE . ".body_text, " . LOG_TABLE . ".log_body_text, 'COLUMN'";
		$sql_update[] = "exec sp_rename " . LOG_TABLE . ".date, " . LOG_TABLE . ".log_date, 'COLUMN'";
		$sql_update[] = "exec sp_rename " . LOG_TABLE . ".send, " . LOG_TABLE . ".log_status, 'COLUMN'";
		
		$sql_update[] = "CREATE INDEX [IX_" . LOG_TABLE . "] 
			ON [" . LOG_TABLE . "]([liste_id]) ON [PRIMARY]";
		$sql_update[] = "CREATE INDEX [IX_" . LOG_TABLE . "] 
			ON [" . LOG_TABLE . "]([log_status]) ON [PRIMARY]";
		$sql_update[] = "ALTER TABLE " . LOG_TABLE . " ADD 
			log_numdest smallint NOT NULL, 
			CONSTRAINT [DF_" . LOG_TABLE . "_log_numdest] DEFAULT (0) FOR [log_numdest]";
		break;
	
	default:
		$sql_update[] = "ALTER TABLE " . LOG_TABLE . " 
			CHANGE sujet log_subject VARCHAR(100) NOT NULL default '', 
			CHANGE body_html log_body_html MEDIUMTEXT NOT NULL default '', 
			CHANGE body_text log_body_text MEDIUMTEXT NOT NULL default '', 
			CHANGE date log_date INT(11) NOT NULL default '0', 
			CHANGE send log_status TINYINT(1) NOT NULL default '0', 
			ADD INDEX (liste_id), ADD INDEX (log_status)";
		$sql_update[] = "ALTER TABLE " . LOG_TABLE . " 
			ADD COLUMN log_numdest smallint(5) NOT NULL DEFAULT '0'";
		break;
}

$sql_update[] = "ALTER TABLE " . LOG_TABLE . " DROP COLUMN attach";

exec_queries($sql_update, true);

include $waroot . 'includes/class.mailer.php';

$total_log = count($logrow);
for( $i = 0; $i < $total_log; $i++ )
{
	if( $logrow[$i]['attach'] == '' )
	{
		continue;
	}
	
	$files = array_map('trim', explode(',', $logrow[$i]['attach']));
	
	$total_files = count($files);
	for( $j = 0; $j < $total_files; $j++ )
	{
		$mime_type = Mailer::mime_type(substr($files[$j], (strrpos($files[$j], '.') + 1)));
		
		$filesize = 0;
		if( file_exists($waroot . 'admin/upload/' . $files[$j]) )
		{
			$filesize = filesize($waroot . 'admin/upload/' . $files[$j]);
		}
		
		$sql = "INSERT INTO " . JOINED_FILES_TABLE . " (file_real_name, file_physical_name, file_size, file_mimetype) 
			VALUES('" . $db->escape(addslashes($files[$j])) . "', '" . $db->escape(addslashes($files[$j])) . "', " . intval($filesize) . ", '$mime_type')";
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

if( DATABASE == 'mssql' )
{
	$sql_update[] = "ALTER TABLE [" . ABONNES_TABLE . "] WITH NOCHECK ADD 
		CONSTRAINT [PK_" . ABONNES_TABLE . "] PRIMARY KEY CLUSTERED ([abo_id]) ON [PRIMARY]";
	$sql_update[] = "ALTER TABLE [" . ABONNES_TABLE . "] WITH NOCHECK ADD 
		CONSTRAINT [DF_" . ABONNES_TABLE . "_abo_register_date] DEFAULT (0) FOR [abo_register_date],
		CONSTRAINT [DF_" . ABONNES_TABLE . "_abo_status] DEFAULT (0) FOR [abo_status]";
}

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
		VALUES('$email', '$language', '" . $data['code'] . "', " . $data['date'] . ", " . $data['status'] . ")";
	exec_queries($sql, true);
	
	$abo_id = $db->next_id();
	$sql_update = array();
	
	foreach( $data['listes'] AS $liste_id => $listdata )
	{
		$sql_update[] = "INSERT INTO " . ABO_LISTE_TABLE . " (abo_id, liste_id, format, send) 
			VALUES($abo_id, " . $liste_id . ", " . $listdata['format'] . ", " . $listdata['send'] . ")";
	}
	
	exec_queries($sql_update, true);
}

unset($aborow, $abo_ary);

if( DATABASE == 'mssql' )
{
	$sql_update = array();
	
	$sql_update[] = "CREATE INDEX [IX_" . ABONNES_TABLE . "] 
		ON [" . ABONNES_TABLE . "]([abo_status]) ON [PRIMARY]";
	$sql_update[] = "CREATE INDEX [IX_" . ABO_LISTE_TABLE . "] 
		ON [" . ABO_LISTE_TABLE . "]([abo_id], [liste_id]) ON [PRIMARY]";
	
	exec_queries($sql_update, true);
}

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
		SET log_numdest = " . $row['num_dest'] . " 
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
@chmod($waroot . 'includes', 0777);

if( $fw = @fopen($waroot . 'includes/config.inc.php', 'w') )
{
	fwrite($fw, $config_file);
	fclose($fw);
	
	@chmod($waroot . 'includes', 0755);
	
	$message = sprintf($lang['Success_update'], '<a href="' . $waroot . 'admin/login.php">', '</a>');
	msg_result($message);
}
else
{
	@chmod($waroot . 'includes', 0755);
	
	$message = sprintf($lang['Success_whithout_config2'], htmlspecialchars($config_file));
	msg_result($message);
}

exit;
?>