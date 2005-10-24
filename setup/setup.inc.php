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

if( !defined('IN_INSTALL') && !defined('IN_UPDATE') )
{
	exit('<b>No hacking</b>');
}

function msg_result($str, $is_query = false)
{
	global $db, $lang, $output, $type;
	
	if( $is_query )
	{
		if( $type == 'update' )
		{
			$message = $lang['Error_in_update'];
		}
		else
		{
			$message = $lang['Error_in_install'];
		}
		
		$title   = '<span style="color: #FF3333;">' . $lang['Title']['error'] . '</span>';
		$message = sprintf($message, $db->sql_error['message'], $str);
	}
	else
	{
		if( $type == 'update' )
		{
			$title = $lang['Result_update'];
		}
		else
		{
			$title = $lang['Result_install'];
		}
		
		$message = $str;
		
		if( !empty($lang['Message'][$str]) )
		{
			$message = $lang['Message'][$str];
		}
	}
	
	$output->assign_block_vars('result', array(
		'L_TITLE'    => $title,
		'MSG_RESULT' => nl2br($message)
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
			msg_result($query, true);
		}
	}
}

error_reporting(E_ALL);

$waroot       = '../';
$new_version  = '2.3-dev';
$default_lang = 'francais';
$schemas_dir  = $waroot . 'setup/schemas/';

$supported_lang = array(
	'fr' => 'francais',
	'en' => 'english'
);

$supported_db = array(
	'mysql' => array(
		'Name'         => 'MySQL 3.x/4.0.x',
		'prefixe_file' => 'mysql',
		'extension'    => 'mysql',
		'delimiter'    => ';',
		'delimiter2'   => ';'
	),
	'mysql4' => array(
		'Name'         => 'MySQL 4.1.x',
		'prefixe_file' => 'mysql',
		'extension'    => 'mysqli',
		'delimiter'    => ';',
		'delimiter2'   => ';'
	),
	'postgre' => array(
		'Name'         => 'Postgre SQL >= 7.2',
		'prefixe_file' => 'postgre',
		'extension'    => 'pgsql',
		'delimiter'    => ';',
		'delimiter2'   => ';'
	)
);

$sql_drop   = array();
$sql_drop[] = "DROP TABLE wa_abo_liste";
$sql_drop[] = "DROP TABLE wa_abonnes";
$sql_drop[] = "DROP TABLE wa_admin";
$sql_drop[] = "DROP TABLE wa_auth_admin";
$sql_drop[] = "DROP TABLE wa_ban_list";
$sql_drop[] = "DROP TABLE wa_config";
$sql_drop[] = "DROP TABLE wa_joined_files";
$sql_drop[] = "DROP TABLE wa_forbidden_ext";
$sql_drop[] = "DROP TABLE wa_liste";
$sql_drop[] = "DROP TABLE wa_log";
$sql_drop[] = "DROP TABLE wa_log_files";
$sql_drop[] = "DROP TABLE wa_session";

//
// Vérification de la version de PHP disponible. Il nous faut la version 4.3.0 minimum
//
if( !function_exists('version_compare') || version_compare(phpversion(), '4.3.0', '>=') == false )
{
	header('Content-Type: text/plain; charset=ISO-8859-1');
	
	echo "Désolé mais WAnewsletter $new_version requiert une version de PHP supérieure ou égale à la version 4.3.0";
	exit;
}

require $waroot . 'includes/functions.php';

//
// Désactivation de magic_quotes_runtime + 
// magic_quotes_gpc et ajout éventuel des backslashes 
//
set_magic_quotes_runtime(0);

if( get_magic_quotes_gpc() )
{
	strip_magic_quotes_gpc($_GET);
	strip_magic_quotes_gpc($_POST);
	strip_magic_quotes_gpc($_COOKIE);
	strip_magic_quotes_gpc($_REQUEST);
}

$vararray = array('dbtype', 'dbhost', 'dbuser', 'dbpassword', 'dbname', 'prefixe');
foreach( $vararray AS $varname )
{
	${$varname} = ( !empty($_POST[$varname]) ) ? trim($_POST[$varname]) : '';
}

if( $dbtype == '' )
{
	$dbtype = 'mysql';
}

if( $prefixe == '' )
{
	$prefixe = 'wa_';
}

if( file_exists($waroot . 'includes/config.inc.php') )
{
	include $waroot . 'includes/config.inc.php';
}

if( defined('IN_UPDATE') && ( $dbhost == '' || $dbname == '' || $dbuser == '' ) )
{
	plain_error('Aucune version de WAnewsletter ne semble présente, le fichier de configuration est vide');
}

require $waroot . 'includes/constantes.php';

foreach( $supported_db AS $db_name => $db_infos )
{
	if( !is_available_extension($db_infos['extension']) )
	{
		unset($supported_db[$db_name]);
	}
}

if( count($supported_db) == 0 )
{
	plain_error('Désolé mais WAnewsletter ' . $new_version . ' requiert une base de données MySQL 3.23.x/4.x ou PostgreSQL 7');
}

require $waroot . 'includes/template.php';
require $waroot . 'includes/class.output.php';

$config_file  = '<' . "?php\n\n";
$config_file .= "//\n";
$config_file .= "// Paramètres d'accés à la base de données\n";
$config_file .= "// Ne pas modifier !\n";
$config_file .= "//\n";
$config_file .= "define('NL_INSTALLED', true);\n\n";
$config_file .= "\$dbtype  = '$dbtype';\n\n";
$config_file .= "\$dbhost  = '$dbhost';\n";
$config_file .= "\$dbuser  = '$dbuser';\n";
$config_file .= "\$dbpassword = '$dbpassword';\n";
$config_file .= "\$dbname  = '$dbname';\n\n";
$config_file .= "\$prefixe = '$prefixe';\n\n";
$config_file .= '?' . '>';

$output = new output($waroot . 'templates/');

?>