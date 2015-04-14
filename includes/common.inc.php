<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

if (substr($_SERVER['SCRIPT_FILENAME'], -8) == '.inc.php') {
	exit('<b>No hacking</b>');
}

if (!defined('WA_ROOTDIR')) {
	define('WA_ROOTDIR', str_replace('\\', '/', dirname(__DIR__)));
}

// @link http://bugs.php.net/bug.php?id=31440
if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
	exit('GLOBALS overwrite attempt detected');
}

// $default_error_reporting est utilisé ultérieurement dans le gestionnaire d'erreurs
$default_error_reporting = (E_ALL & ~(E_STRICT|E_DEPRECATED));
error_reporting($default_error_reporting);

$starttime = array_sum(explode(' ', microtime()));

//
// Intialisation des variables pour éviter toute injection malveillante de code
//
$simple_header = $error = false;
$nl_config     = $lang = $datetime = $admindata = $msg_error = array();
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

//
// On vérifie proprement la présence des dépendances.
// Évite que l'utilisateur prenne un méchant et énigmatique fatal error sur le require() suivant.
//
if (!file_exists(WA_ROOTDIR . '/vendor/autoload.php')) {
	echo "Please first install the dependencies using the command: ";
	echo "<samp>composer install</samp><br>";
	echo "See the <a href='https://getcomposer.org/'>official website of Composer</a>.";
	exit;
}

require WA_ROOTDIR . '/includes/compat.inc.php';
require WA_ROOTDIR . '/includes/functions.php';
require WA_ROOTDIR . '/includes/functions.wrapper.php';
require WA_ROOTDIR . '/includes/constantes.php';
require WA_ROOTDIR . '/includes/wadb_init.php';
require WA_ROOTDIR . '/vendor/autoload.php';

//
// Configuration des gestionnaires d'erreurs et d'exceptions
//
set_error_handler(__NAMESPACE__.'\\wan_error_handler');
set_exception_handler(__NAMESPACE__.'\\wan_exception_handler');

if (DEBUG_LOG_ENABLED && DEBUG_LOG_FILE != '') {
	$filename = DEBUG_LOG_FILE;
	if (strncasecmp(PHP_OS, 'Win', 3) === 0) {
		if (!preg_match('#^[a-z]:[/\\]#i', $filename)) {
			$filename = WA_LOGSDIR . '/' . $filename;
		}
	}
	else if ($filename[0] != '/') {
		$filename = WA_LOGSDIR . '/' . $filename;
	}

	ini_set('error_log', $filename);
	unset($filename);
}

//
// Chargement automatique des classes
//
spl_autoload_register(__NAMESPACE__.'\\wan_autoloader');

//
// Pas installé ?
//
$install_script = 'install.php';
$current_script = basename($_SERVER['SCRIPT_FILENAME']);

if ($current_script != $install_script && !defined('NL_INSTALLED')) {
	if (!check_cli()) {
		if (!file_exists($install_script)) {
			$install_script = '../'.$install_script;
		}

		http_redirect($install_script);
	}
	else {
		echo "Wanewsletter seems not to be installed!\n";
		echo "Call $install_script in your web browser.\n";
		exit(1);
	}
}

//
// Initialisation du système de templates
//
if (!check_cli()) {
	$output = new Output(sprintf('%s/templates/%s',
		WA_ROOTDIR,
		(check_in_admin() ? 'admin/' : '')
	));
}

//
// Initialisation de patchwork/utf8
//
\Patchwork\Utf8\Bootup::initAll();

//
// Configuration par défaut
//
load_settings();

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
	$infos = array();
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
	unset($infos);

	define('UPDATE_CONFIG_FILE', true);
}

// Pas la peine de polluer le scope global
unset($current_script, $dbtype, $dbhost, $dbuser, $dbpassword, $dbname);
