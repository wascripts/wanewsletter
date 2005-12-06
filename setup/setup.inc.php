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

if( !defined('IN_INSTALL') && !defined('IN_UPGRADE') )
{
	exit('<b>No hacking</b>');
}

define('IN_NEWSLETTER', true);
define('WA_ROOTDIR',    '..');
define('WAMAILER_DIR',  WA_ROOTDIR . '/includes/wamailer');
define('SCHEMAS_DIR',   WA_ROOTDIR . '/setup/schemas');

function message($message, $l_title = null)
{
	global $lang, $output;
	
	if( !empty($lang['Message'][$message]) )
	{
		$message = $lang['Message'][$message];
	}
	
	$output->send_headers();
	
	$output->set_filenames( array(
		'body' => 'result.tpl'
	));
	
	if( is_null($l_title) )
	{
		if( defined('IN_INSTALL') )
		{
			$l_title = ( defined('NL_INSTALLED') ) ? $lang['Title']['reinstall'] : $lang['Title']['install'];
		}
		else
		{
			$l_title = $lang['Title']['upgrade'];
		}
	}
	
	$output->assign_vars(array(
		'PAGE_TITLE'   => $l_title,
		'CONTENT_LANG' => $lang['CONTENT_LANG'],
		'CONTENT_DIR'  => $lang['CONTENT_DIR'],
		'NEW_VERSION'  => WA_NEW_VERSION,
		'TRANSLATE'    => ( $lang['TRANSLATE'] != '' ) ? ' | Translate by ' . $lang['TRANSLATE'] : '',
		'L_TITLE'      => $lang['Title']['info'],
		'MSG_RESULT'   => nl2br($message)
	));
	
	$output->pparse('body');
	exit;
}

function sql_error()
{
	global $db, $lang, $output;
	
	$output->send_headers();
	
	$output->set_filenames( array(
		'body' => 'result.tpl'
	));
	
	if( defined('IN_INSTALL') )
	{
		$l_title = ( defined('NL_INSTALLED') ) ? $lang['Title']['reinstall'] : $lang['Title']['install'];
		$message = $lang['Error_in_install'];
	}
	else
	{
		$l_title = $lang['Title']['upgrade'];
		$message = $lang['Error_in_upgrade'];
	}
	
	$output->assign_vars(array(
		'PAGE_TITLE'   => $l_title,
		'CONTENT_LANG' => $lang['CONTENT_LANG'],
		'CONTENT_DIR'  => $lang['CONTENT_DIR'],
		'NEW_VERSION'  => WA_NEW_VERSION,
		'TRANSLATE'    => ( $lang['TRANSLATE'] != '' ) ? ' | Translate by ' . $lang['TRANSLATE'] : '',
		'L_TITLE'      => $lang['Title']['info'],
		'MSG_RESULT'   => nl2br(sprintf($message, $db->sql_error['message'], $db->sql_error['query']))
	));
	
	$output->pparse('body');
	exit;
}

function exec_queries($sql_ary, $return_error = false)
{
	global $db;
	
	if( !is_array($sql_ary) )
	{
		$sql_ary = array($sql_ary);
	}
	
	foreach( $sql_ary AS $query )
	{
		$result = $db->query($query);
		
		if( !$result && $return_error )
		{
			sql_error();
		}
	}
}

error_reporting(E_ALL);

require WA_ROOTDIR . '/includes/functions.php';
require WA_ROOTDIR . '/includes/template.php';
require WA_ROOTDIR . '/includes/class.output.php';

$output = new output(WA_ROOTDIR . '/templates/');

//
// Désactivation de magic_quotes_runtime + 
// magic_quotes_gpc et retrait éventuel des backslashes 
//
set_magic_quotes_runtime(0);

if( get_magic_quotes_gpc() )
{
	strip_magic_quotes_gpc($_GET);
	strip_magic_quotes_gpc($_POST);
	strip_magic_quotes_gpc($_COOKIE);
	strip_magic_quotes_gpc($_REQUEST);
}

define('WA_NEW_VERSION', '###VERSION###');
$default_lang = 'francais';

$supported_lang = array(
	'fr' => 'francais',
	'en' => 'english'
);

$supported_db = array(
	'mysql' => array(
		'Name'         => 'MySQL 3.23.x/4.0.x',
		'prefixe_file' => 'mysql',
		'extension'    => 'mysql',
		'delimiter'    => ';',
		'delimiter2'   => ';'
	),
	'mysqli' => array(
		'Name'         => 'MySQL 4.1.x',
		'prefixe_file' => 'mysql',
		'extension'    => 'mysqli',
		'delimiter'    => ';',
		'delimiter2'   => ';'
	),
	'postgres' => array(
		'Name'         => 'PostgreSQL 7.x/8.x',
		'prefixe_file' => 'postgres',
		'extension'    => 'pgsql',
		'delimiter'    => ';',
		'delimiter2'   => ';'
	),
	'sqlite' => array(
		'Name'         => 'SQLite 2.8.x',
		'prefixe_file' => 'sqlite',
		'extension'    => 'sqlite',
		'delimiter'    => ';',
		'delimiter2'   => ';'
	)
);

$sql_drop = array(
	'DROP TABLE wa_abo_liste',
	'DROP TABLE wa_abonnes',
	'DROP TABLE wa_admin',
	'DROP TABLE wa_auth_admin',
	'DROP TABLE wa_ban_list',
	'DROP TABLE wa_config',
	'DROP TABLE wa_joined_files',
	'DROP TABLE wa_forbidden_ext',
	'DROP TABLE wa_liste',
	'DROP TABLE wa_log',
	'DROP TABLE wa_log_files',
	'DROP TABLE wa_session'
);

$prefixe = 'wa_';
$dbtype  = 'mysql';
$dbhost  = 'localhost';
$dbuser  = $dbpassword = $dbname = '';
$lang    = $datetime = $msg_error = $_php_errors = array();
$error   = false;

if( file_exists(WA_ROOTDIR . '/includes/config.inc.php') )
{
	@include WA_ROOTDIR . '/includes/config.inc.php';
}

if( server_info('HTTP_ACCEPT_LANGUAGE') != '' )
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

require WA_ROOTDIR . '/language/lang_' . $language . '.php';

//
// Vérification de la version de PHP disponible. Il nous faut la version 4.1.0 minimum
//
if( !function_exists('version_compare') )
{
	message(sprintf($lang['PHP_version_error'], WA_NEW_VERSION));
}

$vararray = array('dbtype', 'dbhost', 'dbuser', 'dbpassword', 'dbname', 'prefixe');
foreach( $vararray AS $varname )
{
	${$varname} = ( !empty($_POST[$varname]) ) ? trim($_POST[$varname]) : ${$varname};
}

$vararray = array('start', 'confirm', 'sendfile');
foreach( $vararray AS $varname )
{
	${$varname} = ( isset($_POST[$varname]) ) ? true : false;
}

require WA_ROOTDIR . '/includes/constantes.php';

if( !defined('IN_INSTALL') && empty($dbname) )
{
	message($lang['Not_installed']);
}
else if( $dbtype == 'mssql' )
{
	message($lang['mssql_support_end']);
}
else if( $dbtype == 'postgre' )
{
	$dbtype = 'postgres';
}
else if( $dbtype == 'mysql4' )
{
	$dbtype = 'mysqli';
}

$db_list = '';
foreach( $supported_db AS $db_name => $db_infos )
{
	$db_list .= ', ' . $db_infos['Name'];
	
	if( !extension_loaded($db_infos['extension']) )
	{
		unset($supported_db[$db_name]);
	}
}

if( count($supported_db) == 0 )
{
	message(sprintf($lang['No_db_support'], WA_NEW_VERSION, substr($db_list, 2)));
}

if( isset($supported_db[$dbtype]) )
{
	require WA_ROOTDIR . '/sql/db_type.php';
}
else if( defined('NL_INSTALLED') || defined('IN_UPGRADE') )
{
	plain_error($lang['DB_type_undefined']);
}

$config_file  = '<' . "?php\n\n";
$config_file .= "//\n";
$config_file .= "// Paramètres d'accès à la base de données\n";
$config_file .= "// Ne pas modifier ce fichier ! (Do not edit this file)\n";
$config_file .= "//\n";
$config_file .= "define('NL_INSTALLED', true);\n";
$config_file .= "define('WA_VERSION',   '" . WA_NEW_VERSION . "');\n\n";
$config_file .= "\$dbtype  = '$dbtype';\n\n";
$config_file .= "\$dbhost  = " .  (($dbtype == 'sqlite') ? "WA_ROOTDIR . '/sql/wanewsletter.sqlite'" : "'$dbhost'") . ";\n";
$config_file .= "\$dbuser  = '$dbuser';\n";
$config_file .= "\$dbpassword = '$dbpassword';\n";
$config_file .= "\$dbname  = '$dbname';\n\n";
$config_file .= "\$prefixe = '$prefixe';\n\n";
$config_file .= '?' . '>';

//
// Envoi du fichier au client si demandé
//
if( $sendfile == true )
{
	require WA_ROOTDIR . '/includes/class.attach.php';
	
	Attach::send_file('config.inc.php', 'text/plain', $config_file);
}

?>