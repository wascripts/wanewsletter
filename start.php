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

if( !defined('IN_NEWSLETTER') )
{
	exit('<b>No hacking</b>');
}

error_reporting(E_ALL);

$starttime = array_sum(explode(' ', microtime()));

//
// Intialisation des variables pour éviter toute injection malveillante de code 
//
$simple_header = $error = FALSE;
$nl_config     = $lang = $datetime = $admindata = $msg_error = $other_tags = array();
$output = NULL;
$dbtype = $dbhost = $dbuser = $dbpassword = $dbname = $prefixe = '';

include $waroot . 'includes/config.inc.php';

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
	
	header($header_location . $waroot . 'setup/install.php');
	exit;
}

require_once $waroot . 'includes/functions.php';
require_once $waroot . 'includes/constantes.php';
require_once $waroot . 'includes/template.php';
require_once $waroot . 'includes/class.output.php';
require_once $waroot . 'sql/db_type.php';

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
	strip_magic_quotes_gpc($_FILES);
	strip_magic_quotes_gpc($_REQUEST);
}

//
// Intialisation de la connexion à la base de données 
//
$db = new sql($dbhost, $dbuser, $dbpassword, $dbname);

if( !$db->connect_id )
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

$nl_config = $db->fetch_array($result);

//
// Purge 'automatique' des listes (comptes non activés au dela du temps limite)
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

?>