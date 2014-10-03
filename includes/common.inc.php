<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if( !defined('IN_NEWSLETTER') )
{
	exit('<b>No hacking</b>');
}

// @link http://bugs.php.net/bug.php?id=31440
if( isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']) ) {
	exit('GLOBALS overwrite attempt detected');
}

// Check PHP version
define('WA_PHP_VERSION_REQUIRED', '5.2.4');
if( !version_compare(PHP_VERSION, WA_PHP_VERSION_REQUIRED, '>=') ) {
	printf("Your server is running PHP %s, but Wanewsletter requires PHP %s or higher",
		PHP_VERSION,
		WA_PHP_VERSION_REQUIRED
	);
	exit;
}

error_reporting(E_ALL);

$starttime = array_sum(explode(' ', microtime()));

//
// Intialisation des variables pour éviter toute injection malveillante de code 
//
$simple_header = $error = false;
$nl_config     = $lang = $datetime = $admindata = $msg_error = $other_tags = $_php_errors = array();
$output = null;
$dsn = $prefixe = $php_errormsg = '';
$prefixe = isset($_POST['prefixe']) ? $_POST['prefixe'] : 'wa_';
// Compatibilité avec wanewsletter < 2.3-beta2
$dbtype = $dbhost = $dbuser = $dbpassword = $dbname = '';

//
// Réglage des divers répertoires utilisés par le script.
// Le tilde est remplacé par WA_ROOTDIR, qui mène au répertoire d'installation
// de Wanewsletter.
// Ces variables sont ensuite utilisées dans constantes.php pour définir les
// constantes WA_*DIR
//
$logs_dir  = '~/data/logs';
$stats_dir = '~/data/stats';
$tmp_dir   = '~/data/tmp';

if( file_exists(WA_ROOTDIR . '/includes/config.inc.php') )
{
	@include WA_ROOTDIR . '/includes/config.inc.php';
}

if( !defined('IN_INSTALL') && !defined('NL_INSTALLED') )
{
	if( !empty($_SERVER['SERVER_SOFTWARE']) && preg_match("#Microsoft|WebSTAR|Xitami#i", $_SERVER['SERVER_SOFTWARE']) )
	{
		$header_location = 'Refresh: 0; URL=';
	}
	else
	{
		$header_location = 'Location: ';
	}
	
	header($header_location . sprintf('%s/install.php', WA_ROOTDIR));
	exit;
}

require WA_ROOTDIR . '/includes/compat.inc.php';
require WA_ROOTDIR . '/includes/functions.php';
require WA_ROOTDIR . '/includes/constantes.php';
require WA_ROOTDIR . '/includes/wadb_init.php';
require WA_ROOTDIR . '/includes/class.phpass.php';

load_settings();

//
// Appel du gestionnaire d'erreur 
//
if( defined('IN_COMMANDLINE') )
{
	//
	// Compatibilité avec PHP en CGI
	//
	if( php_sapi_name() != 'cli' )
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
	
	$output = new output(sprintf(
		'%s/templates/%s', WA_ROOTDIR, defined('IN_ADMIN') ? 'admin/' : ''
	));
	
	set_error_handler('wan_web_handler');
}

//
// Les guillemets magiques ont été supprimés dans PHP 5.4.0
//
if( version_compare(PHP_VERSION, '5.4.0', '<') )
{
	//
	// Désactivation de magic_quotes_runtime +
	// magic_quotes_gpc et retrait éventuel des backslashes
	//
	@ini_set('magic_quotes_runtime', 0);

	if( get_magic_quotes_gpc() )
	{
		strip_magic_quotes_gpc($_GET);
		strip_magic_quotes_gpc($_POST);
		strip_magic_quotes_gpc($_COOKIE);
		strip_magic_quotes_gpc($_FILES, true);
		strip_magic_quotes_gpc($_REQUEST);
	}
}

//
// Intialisation de la connexion à la base de données 
//

// Compatibilité avec wanewsletter < 2.3-beta2
if( empty($dsn) )
{
	$infos['engine'] = !empty($dbtype) ? $dbtype : 'mysql';
	$infos['host']   = $dbhost;
	$infos['user']   = $dbuser;
	$infos['pass']   = $dbpassword;
	$infos['dbname'] = $dbname;
	
	if( $infos['engine'] == 'mssql' )
	{
		exit($lang['mssql_support_end']);
	}
	else if( $infos['engine'] == 'postgre' )
	{
		$infos['engine'] = 'postgres';
	}
	else if( $infos['engine'] == 'mysql4' || $infos['engine'] == 'mysqli' )
	{
		$infos['engine'] = 'mysql';
	}
	
	$dsn = createDSN($infos);
	
	define('UPDATE_CONFIG_FILE', true);
}

if( !defined('IN_INSTALL') )
{
	$db = WaDatabase($dsn);
	
	if( !$db->isConnected() )
	{
		trigger_error(sprintf($lang['Connect_db_error'], $db->error), E_USER_ERROR);
	}
	
	//
	// On récupère la configuration du script 
	//
	$nl_config = wa_get_config();
	
	//
	// "Constantes" de classe dans le scope global
	// Pas plus haut car on a besoin d'une instance de Wadb_* et WadbResult_*
	//
	define('SQL_INSERT', $db->SQL_INSERT);
	define('SQL_UPDATE', $db->SQL_UPDATE);
	define('SQL_DELETE', $db->SQL_DELETE);
}

