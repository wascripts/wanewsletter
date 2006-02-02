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
		'MSG_RESULT'   => nl2br(sprintf($message, $db->error, $db->lastQuery))
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
	
	foreach( $sql_ary as $query )
	{
		$result = $db->query($query);
		
		if( !$result && $return_error )
		{
			sql_error();
		}
	}
}

require '/home/web/projects/wanewsletter/branche_2.3/tmp/debug_error.php';

error_reporting(E_ALL);

require WA_ROOTDIR . '/includes/functions.php';
require WA_ROOTDIR . '/includes/constantes.php';
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

$default_lang   = 'francais';
$supported_lang = array(
	'fr' => 'francais',
	'en' => 'english'
);

$supported_db = array(
	'mysql' => array(
		'Name'         => 'MySQL 3.23.x, 4.0.x',
		'prefixe_file' => 'mysql',
		'extension'    => 'mysql'
	),
	'mysqli' => array(
		'Name'         => 'MySQL 4.1.x, 5.x',
		'prefixe_file' => 'mysql',
		'extension'    => 'mysqli'
	),
	'postgres' => array(
		'Name'         => 'PostgreSQL &#8805; 7.2, 8.x',
		'prefixe_file' => 'postgres',
		'extension'    => 'pgsql'
	),
	'sqlite' => array(
		'Name'         => 'SQLite &#8805; 2.8, 3.x',
		'prefixe_file' => 'sqlite',
		'extension'    => 'sqlite | (pdo, pdo_sqlite)'
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

$dsn = '';
$lang    = $datetime = $msg_error = $_php_errors = array();
$error   = false;
$prefixe = ( !empty($_POST['prefixe']) ) ? trim($_POST['prefixe']) : 'wa_';
$infos   = array('driver' => 'mysql', 'host' => '', 'user' => '', 'pass' => '', 'dbname' => '');

if( file_exists(WA_ROOTDIR . '/includes/config.inc.php') )
{
	@include WA_ROOTDIR . '/includes/config.inc.php';
}

if( server_info('HTTP_ACCEPT_LANGUAGE') != '' )
{
	$accept_lang_ary = array_map('trim', explode(',', server_info('HTTP_ACCEPT_LANGUAGE')));
	
	foreach( $accept_lang_ary as $accept_lang )
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
require WA_ROOTDIR . '/includes/wadb_init.php';

//
// Vérification de la version de PHP disponible. Il nous faut la version 4.1.0 minimum
//
if( !function_exists('version_compare') )
{
	message(sprintf($lang['PHP_version_error'], WA_NEW_VERSION));
}

if( !empty($dsn) )
{
	list($infos) = parseDSN($dsn);
}

$infos['driver'] = ( !empty($_POST['dbtype']) ) ? trim($_POST['dbtype']) : $infos['driver'];
$infos['host']   = ( !empty($_POST['dbhost']) ) ? trim($_POST['dbhost']) : $infos['host'];
$infos['user']   = ( !empty($_POST['dbuser']) ) ? trim($_POST['dbuser']) : $infos['user'];
$infos['pass']   = ( !empty($_POST['dbpassword']) ) ? trim($_POST['dbpassword']) : $infos['pass'];
$infos['dbname'] = ( !empty($_POST['dbname']) ) ? trim($_POST['dbname']) : $infos['dbname'];

foreach( array('start', 'confirm', 'sendfile') as $varname )
{
	${$varname} = ( isset($_POST[$varname]) ) ? true : false;
}

if( !defined('IN_INSTALL') && empty($dbname) )
{
	message($lang['Not_installed']);
}
else if( $infos['driver'] == 'mssql' )
{
	message($lang['mssql_support_end']);
}
else if( $infos['driver'] == 'postgre' )
{
	$infos['driver'] = 'postgres';
}
else if( $infos['driver'] == 'mysql4' )
{
	$infos['driver'] = 'mysqli';
}

//
// Le support de PostgreSQL dans Wanewsletter nécessite PHP >= 4.2.0
//
if( version_compare(phpversion(), '4.2.0', '<') )
{
	unset($supported_db['postgres']);
}

//
// SQLite est un cas particulier car peut nécessiter la présence de deux extensions.
// On fait le traitement avant la boucle
//
if( !extension_loaded('sqlite') && ( !extension_loaded('pdo') || !extension_loaded('pdo_sqlite') ) )
{
	unset($supported_db['sqlite']);
}

$db_list = '';
foreach( $supported_db as $name => $data )
{
	$db_list .= ', ' . $data['Name'];
	
	if( $name != 'sqlite' && !extension_loaded($data['extension']) )
	{
		unset($supported_db[$name]);
	}
}

if( count($supported_db) == 0 )
{
	message(sprintf($lang['No_db_support'], WA_NEW_VERSION, substr($db_list, 2)));
}

if( !isset($supported_db[$infos['driver']]) && ( defined('NL_INSTALLED') || defined('IN_UPGRADE') ) )
{
	plain_error($lang['DB_type_undefined']);
}

if( !empty($infos['dbname']) ) // TODO : Problème dans le cas de SQLite ($infos['dbname'] vide)
{
	$dsn = createDSN($infos);
}

$config_file  = '<' . "?php\n\n";
$config_file .= "//\n";
$config_file .= "// Paramètres d'accès à la base de données\n";
$config_file .= "// Ne pas modifier ce fichier ! (Do not edit this file)\n";
$config_file .= "//\n";
$config_file .= "define('NL_INSTALLED', true);\n";
$config_file .= "define('WA_VERSION',   '" . WA_NEW_VERSION . "');\n\n";
$config_file .= "\$dsn = '$dsn';\n";
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