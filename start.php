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

if( !defined('IN_NEWSLETTER') )
{
	exit('<b>No hacking</b>');
}

error_reporting(E_ALL);

$starttime = array_sum(explode(' ', microtime()));

//
// Intialisation des variables pour éviter toute injection malveillante de code 
//
$simple_header = $error = false;
$nl_config     = $lang = $datetime = $admindata = $msg_error = $other_tags = $_php_errors = array();
$output = null;
$dsn = $prefixe = '';

@include WA_ROOTDIR . '/includes/config.inc.php';

if( !defined('NL_INSTALLED') )
{
	if( !empty($_SERVER['SERVER_SOFTWARE']) && preg_match("#Microsoft|WebSTAR|Xitami#i", $_SERVER['SERVER_SOFTWARE']) )
	{
		$header_location = 'Refresh: 0; URL=';
	}
	else
	{
		$header_location = 'Location: ';
	}
	
	$path = ( file_exists('setup/install.php') ) ? 'setup/install.php' : '../setup/install.php';
	
	header($header_location . $path);
	exit;
}

require WA_ROOTDIR . '/includes/functions.php';
require WA_ROOTDIR . '/includes/constantes.php';
require WA_ROOTDIR . '/includes/wadb_init.php';

//
// Appel du gestionnaire d'erreur 
//
if( defined('IN_COMMANDLINE') )
{
	//
	// Compatibilité avec PHP < 4.3.0 et PHP en CGI
	//
	if( version_compare(phpversion(), '4.3.0', '<') == true || php_sapi_name() != 'cli' )
	{
		define('STDIN',  fopen('php://stdin', 'r'));
		define('STDOUT', fopen('php://stdout', 'w'));
		define('STDERR', fopen('php://stderr', 'w'));
	}
	
	set_error_handler('wan_cli_handler');
}
else
{
	require WA_ROOTDIR . '/includes/template.php';
	require WA_ROOTDIR . '/includes/class.output.php';
	
	set_error_handler('wan_web_handler');
}

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
	strip_magic_quotes_gpc($_FILES);
	strip_magic_quotes_gpc($_REQUEST);
}

//
// Intialisation de la connexion à la base de données 
//
list($infos) = parseDSN($dsn);
define('SQL_DRIVER', $infos['driver']);

$db = WaDatabase($dsn);

if( !$db->isConnected() )
{
	trigger_error('<b>Impossible de se connecter à la base de données</b>', CRITICAL_ERROR);
}

//
// On récupère la configuration du script 
//
$sql = 'SELECT * FROM ' . CONFIG_TABLE;
if( !($result = $db->query($sql)) )
{
	trigger_error('Impossible d\'obtenir la configuration de la newsletter', CRITICAL_ERROR);
}

$nl_config = $result->fetch(SQL_FETCH_ASSOC);

//
// Purge 'automatique' des listes (comptes non activés au-delà du temps limite)
//
if( !(time() % 10) || !defined('IN_ADMIN') )
{
	purge_liste();
}

//
// Nom du dossier des fichiers temporaires du script
// Le nom ne doit contenir / ni au début, ni à la fin
//
$tmp_name = 'tmp';

//
// Si nous avons un accès restreint à cause d'open_basedir sur le serveur, 
// nous devrons utiliser le dossier des fichiers temporaires du script 
//
$tmp_name = trim($tmp_name, '/');

if( OPEN_BASEDIR_RESTRICTION && !is_writable(WA_ROOTDIR . '/' . $tmp_name) )
{
	trigger_error('tmp_dir_not_writable', MESSAGE);
}

define('WA_TMPDIR',    WA_ROOTDIR . '/' . $tmp_name, true);
define('WAMAILER_DIR', WA_ROOTDIR . '/includes/wamailer');
define('WA_LOCKFILE',  WA_TMPDIR . '/liste-%d.lock');

?>
