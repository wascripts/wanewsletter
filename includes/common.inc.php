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
define('WA_PHP_VERSION_REQUIRED', '5.2.4');
if (!version_compare(PHP_VERSION, WA_PHP_VERSION_REQUIRED, '>=')) {
	printf("Your server is running PHP %s, but Wanewsletter requires PHP %s or higher",
		PHP_VERSION,
		WA_PHP_VERSION_REQUIRED
	);
	exit;
}

error_reporting(E_ALL & ~(E_STRICT|E_DEPRECATED));

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

if (file_exists(WA_ROOTDIR . '/includes/config.inc.php')) {
	@include WA_ROOTDIR . '/includes/config.inc.php';
}

require WA_ROOTDIR . '/includes/compat.inc.php';
require WA_ROOTDIR . '/includes/functions.php';
require WA_ROOTDIR . '/includes/constantes.php';
require WA_ROOTDIR . '/includes/wadb_init.php';

//
// Configuration des gestionnaires d'erreurs et d'exceptions
//
set_error_handler('wan_error_handler');
set_exception_handler('wan_exception_handler');

//
// Chargement automatique des classes
//
spl_autoload_register('wan_autoloader');


if (!defined('IN_INSTALL') && !defined('NL_INSTALLED')) {
	http_redirect(sprintf('%s/install.php', WA_ROOTDIR));
}

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

//
// Intialisation de la connexion à la base de données et récupération de la configuration
//
if (!defined('IN_INSTALL')) {
	$db = WaDatabase($dsn);

	//
	// On récupère la configuration du script
	//
	$nl_config = wa_get_config();

	//
	// Hors phase de développement ou beta, on affiche une alerte si
	// l'administrateur a activé le débogage.
	//
	if (DEBUG_MODE == DEBUG_LEVEL_QUIET && isset($nl_config['debug_level']) &&
		$nl_config['debug_level'] > DEBUG_LEVEL_QUIET
	) {
		wanlog($lang['Message']['Warning_debug_active']);
	}
}

