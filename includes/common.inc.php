<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if (!defined('IN_NEWSLETTER')) {
	exit('<b>No hacking</b>');
}

// @link http://bugs.php.net/bug.php?id=31440
if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
	exit('GLOBALS overwrite attempt detected');
}

// Check PHP version
define('WA_PHP_VERSION_REQUIRED', '5.3.7');
if (!version_compare(PHP_VERSION, WA_PHP_VERSION_REQUIRED, '>=')) {
	printf("Your server is running PHP %s, but Wanewsletter requires PHP %s or higher",
		PHP_VERSION,
		WA_PHP_VERSION_REQUIRED
	);
	exit;
}

// $default_error_reporting est utilisé ultérieurement dans le gestionnaire d'erreurs
$default_error_reporting = (E_ALL & ~(E_STRICT|E_DEPRECATED));
error_reporting($default_error_reporting);

$starttime = array_sum(explode(' ', microtime()));

//
// Intialisation des variables pour éviter toute injection malveillante de code
//
$simple_header = $error = false;
$nl_config     = $lang = $datetime = $admindata = $msg_error = $other_tags = array();
$output = null;
$dsn = $prefixe = '';
$prefixe = (isset($_POST['prefixe'])) ? $_POST['prefixe'] : 'wa_';
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

$config_file = WA_ROOTDIR . '/includes/config.inc.php';
if (file_exists($config_file)) {
	if (!is_readable($config_file)) {
		echo "Cannot read the config file. Please fix this mistake and reload.";
		exit;
	}

	include $config_file;
	unset($config_file);
}

require WA_ROOTDIR . '/includes/compat.inc.php';
require WA_ROOTDIR . '/includes/functions.php';
require WA_ROOTDIR . '/includes/constantes.php';
require WA_ROOTDIR . '/includes/wadb_init.php';
require WA_ROOTDIR . '/vendor/autoload.php';

//
// Configuration des gestionnaires d'erreurs et d'exceptions
//
set_error_handler('wan_error_handler');
set_exception_handler('wan_exception_handler');

//
// Chargement automatique des classes
//
spl_autoload_register('wan_autoloader');

//
// Pas installé ?
//
if (!defined('IN_INSTALL') && !defined('NL_INSTALLED')) {
	if (!defined('IN_COMMANDLINE')) {
		http_redirect(sprintf('%s/install.php', WA_ROOTDIR));
	}
	else {
		echo "Wanewsletter seems not to be installed!\n";
		echo "Call install.php in your web browser.\n";
		exit(1);
	}
}

//
// Initialisation de patchwork/utf8
//
\Patchwork\Utf8\Bootup::initAll();

//
// Configuration par défaut
//
load_settings();

if (defined('IN_COMMANDLINE')) {
	//
	// Compatibilité avec PHP en CGI
	//
	if (PHP_SAPI != 'cli') {
		define('STDIN',  fopen('php://stdin', 'r'));
		define('STDOUT', fopen('php://stdout', 'w'));
		define('STDERR', fopen('php://stderr', 'w'));
	}

	define('ANSI_TERMINAL', function_exists('posix_isatty') && posix_isatty(STDOUT));
}
else {
	$output = new Output(sprintf(
		'%s/templates/%s', WA_ROOTDIR, (defined('IN_ADMIN') ? 'admin/' : '')
	));
}

//
// Désactivation de magic_quotes_runtime +
// magic_quotes_gpc et retrait éventuel des backslashes
//
@ini_set('magic_quotes_runtime', 0);

strip_magic_quotes_gpc($_GET);
strip_magic_quotes_gpc($_POST);
strip_magic_quotes_gpc($_COOKIE);
strip_magic_quotes_gpc($_FILES, true);
strip_magic_quotes_gpc($_REQUEST);

// Compatibilité avec wanewsletter < 2.3-beta2
if (empty($dsn)) {
	$infos['engine'] = (!empty($dbtype)) ? $dbtype : 'mysql';
	$infos['host']   = $dbhost;
	$infos['user']   = $dbuser;
	$infos['pass']   = $dbpassword;
	$infos['dbname'] = $dbname;

	if ($infos['engine'] == 'mssql') {
		exit($lang['mssql_support_end']);
	}
	else if ($infos['engine'] == 'postgre') {
		$infos['engine'] = 'postgres';
	}
	else if ($infos['engine'] == 'mysql4' || $infos['engine'] == 'mysqli') {
		$infos['engine'] = 'mysql';
	}

	$dsn = createDSN($infos);

	define('UPDATE_CONFIG_FILE', true);

	// Pas la peine de polluer le scope global
	unset($infos, $dbtype, $dbhost, $dbuser, $dbpassword, $dbname);
}
