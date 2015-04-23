<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

use Patchwork\Utf8 as u;
use Wamailer\Mailer;
use Wamailer\Email;
use Wanewsletter\Dblayer\Wadb;
use Wanewsletter\Dblayer\WadbResult;

/**
 * Fonction de chargement automatique de classes.
 * Implémentation à la barbare. On verra plus tard pour
 * faire quelque chose de plus propre...
 *
 * @param string $classname Nom de la classe
 */
function wan_autoloader($classname)
{
	$rootdir = dirname(__DIR__);
	$prefix  = '';

	if (strpos($classname, '\\')) {
		list($prefix, $classname) = explode('\\', $classname, 2);
	}

	if ($prefix != 'Wanewsletter') {
		return null;
	}

	$classname = strtolower($classname);

	if (strpos($classname, '\\')) {
		// cas spécial pour dblayer qui est localisé dans sql/
		$classname = str_replace('dblayer\\', 'sql/', $classname);
		// Chemin includes/<namespace>/<classname>.php
		$filename = sprintf('%s/includes/%s.php', $rootdir, str_replace('\\', '/', $classname));
	}
	else {
		// Ancien nommage de fichiers. Chemin includes/class.<classname>.php
		$filename = sprintf('%s/includes/class.%s.php', $rootdir, $classname);
	}

	if (is_readable($filename)) {
		require $filename;
	}
}

/**
 * Chargement du fichier de configuration initial, et redirection vers
 * install.php si le fichier n’existe pas.
 */
function load_config_file()
{
	global $dsn, $prefixe;// Sale mais bon...

	// Réglage par défaut des divers répertoires utilisés par le script.
	// Le tilde est remplacé par WA_ROOTDIR, qui mène au répertoire d'installation
	// de Wanewsletter (voir plus bas).
	$logs_dir  = '~/data/logs';
	$stats_dir = '~/data/stats';
	$tmp_dir   = '~/data/tmp';

	$need_update  = false;
	$test_files[] = WA_ROOTDIR . '/data/config.inc.php';
	// Emplacement du fichier dans Wanewsletter < 3.0-beta1
	$test_files[] = WA_ROOTDIR . '/includes/config.inc.php';

	foreach ($test_files as $file) {
		if (file_exists($file)) {
			if (!is_readable($file)) {
				echo "Cannot read the config file. Please fix this mistake and reload.";
				exit;
			}

			include $file;
		}
	}

	//
	// Compatibilité avec Wanewsletter < 2.3-beta2
	//
	if (!$dsn && !empty($dbtype)) {
		$infos = array();
		$infos['engine'] = $dbtype;
		$infos['host']   = $dbhost;
		$infos['user']   = $dbuser;
		$infos['pass']   = $dbpassword;
		$infos['dbname'] = $dbname;

		if ($infos['engine'] == 'mssql') {
			echo "Support for Microsoft SQL Server has been removed in Wanewsletter 2.3\n";
			exit;
		}
		else if ($infos['engine'] == 'postgre') {
			$infos['engine'] = 'postgres';
		}
		else if ($infos['engine'] == 'mysql4' || $infos['engine'] == 'mysqli') {
			$infos['engine'] = 'mysql';
		}

		$dsn = createDSN($infos);

		$need_update = true;
	}

	//
	// Les constantes NL_INSTALLED et WA_VERSION sont obsolètes.
	//
	if (defined('NL_INSTALLED') || defined('WA_VERSION') || !file_exists($test_files[0])) {
		$need_update = true;
	}

	//
	// Pas installé ?
	//
	$install_script = 'install.php';

	if (!$dsn && $install_script != basename($_SERVER['SCRIPT_FILENAME'])) {
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

	define(__NAMESPACE__.'\\UPDATE_CONFIG_FILE', $need_update);

	//
	// Déclaration des dossiers et fichiers spéciaux utilisés par le script
	//
	define(__NAMESPACE__.'\\WA_LOGSDIR',  str_replace('~', WA_ROOTDIR, rtrim($logs_dir, '/')));
	define(__NAMESPACE__.'\\WA_STATSDIR', str_replace('~', WA_ROOTDIR, rtrim($stats_dir, '/')));
	define(__NAMESPACE__.'\\WA_TMPDIR',   str_replace('~', WA_ROOTDIR, rtrim($tmp_dir, '/')));
	define(__NAMESPACE__.'\\WA_LOCKFILE', WA_TMPDIR . '/liste-%d.lock');
}

/**
 * Vérifie que les numéros de version des tables dans le fichier constantes.php
 * et dans la table de configuration du script sont synchro
 *
 * @param integer $version Version présente dans la base de données (clé 'db_version')
 *
 * @return boolean
 */
function check_db_version($version)
{
	return (WANEWSLETTER_DB_VERSION == $version);
}

/**
 * Vérifie la présence d'une mise à jour
 *
 * @param boolean $complete true pour vérifier aussi l'URL distante
 *
 * @return boolean|integer
 */
function wa_check_update($complete = false)
{
	$cache_file = sprintf('%s/%s', WA_TMPDIR, CHECK_UPDATE_CACHE);
	$cache_ttl  = CHECK_UPDATE_CACHE_TTL;

	$result = false;
	$data   = '';

	if (is_readable($cache_file) && filemtime($cache_file) > (time() - $cache_ttl)) {
		$data = file_get_contents($cache_file);
	}
	else if ($complete) {
		$result = http_get_contents(CHECK_UPDATE_URL, $errstr);
		$data = $result['data'];

		if (preg_match('#^[A-Za-z0-9.-]+$#', $data)) {
			file_put_contents($cache_file, $data);
		}
		else {
			$data = '';
		}
	}

	if ($data) {
		$result = intval(version_compare(WANEWSLETTER_VERSION, trim($data), '<'));
	}

	return $result;
}

/**
 * Retourne la configuration du script stockée dans la base de données
 *
 * @return array
 */
function wa_get_config()
{
	global $db;

	$result = $db->query("SELECT * FROM " . CONFIG_TABLE);
	$result->setFetchMode(WadbResult::FETCH_ASSOC);
	$row    = $result->fetch();
	$config = array();

	if (isset($row['config_name'])) {
		do {
			if ($row['config_name'] != null) {
				$config[$row['config_name']] = $row['config_value'];
			}
		}
		while ($row = $result->fetch());
	}
	// Wanewsletter < 2.4-beta2
	else {
		$config = $row;
	}

	return $config;
}

/**
 * Sauvegarde les clés de configuration fournies dans la base de données
 *
 * @param mixed $config  Soit le nom de l'option de configuration à mettre à jour,
 *                       soit un tableau d'options
 * @param string $value  Si le premier argument est une chaîne, utilisé comme
 *                       valeur pour l'option ciblée
 */
function wa_update_config($config, $value = null)
{
	global $db;

	if (is_string($config)) {
		$config = array($config => $value);
	}

	foreach ($config as $name => $value) {
		$db->query(sprintf(
			"UPDATE %s SET config_value = '%s' WHERE config_name = '%s'",
			CONFIG_TABLE,
			$db->escape($value),
			$db->escape($name)
		));
	}
}

/**
 * Génération d'une chaîne aléatoire
 *
 * @param integer $length       Nombre de caractères
 * @param boolean $specialChars Ajout de caractères spéciaux
 *
 * @return string
 */
function generate_key($length = 32, $specialChars = false)
{
	static $charList = null;

	if ($charList == null) {
		$charList = range('0', '9');
		$charList = array_merge($charList, range('A', 'Z'));
		$charList = array_merge($charList, range('a', 'z'));

		if ($specialChars) {
			$charList = array_merge($charList, range(' ', '/'));
			$charList = array_merge($charList, range(':', '@'));
			$charList = array_merge($charList, range('[', '`'));
			$charList = array_merge($charList, range('{', '~'));
		}

		shuffle($charList);
	}

	$key = '';
	for ($i = 0, $m = count($charList)-1; $i < $length; $i++) {
		$key .= $charList[mt_rand(0, $m)];
	}

	return $key;
}

/**
 * Indique si on est sur une connexion sécurisée
 *
 * @return boolean
 */
function wan_ssl_connection()
{
	return ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 443 == $_SERVER['SERVER_PORT']);
}

/**
 * Construction d'une url
 *
 * @param string  $url     Url à compléter
 * @param array   $params  Paramètres à ajouter en fin d'url
 * @param boolean $session Ajout de l'ID de session PHP s'il y a lieu
 *
 * @return string
 */
function wan_build_url($url, $params = array(), $session = false)
{
	$parts = parse_url($url);

	if (!is_array($params)) {
		$params = array();
	}

	if (empty($parts['scheme'])) {
		$proto = (wan_ssl_connection()) ? 'https' : 'http';
	}
	else {
		$proto = $parts['scheme'];
	}

	$server = (!empty($parts['host'])) ? $parts['host'] : $_SERVER['HTTP_HOST'];

	if (!empty($parts['port'])) {
		$server .= ':'.$parts['port'];
	}
	else if ($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
		$server .= ':'.$_SERVER['SERVER_PORT'];
	}

	$path  = (!empty($parts['path'])) ? $parts['path'] : '/';
	$query = (!empty($parts['query'])) ? $parts['query'] : '';

	if ($path[0] != '/') {
		$parts = explode('/', dirname($_SERVER['SCRIPT_NAME']).'/'.$path);
		$path  = array();

		foreach ($parts as $part) {
			if ($part == '.' || $part == '') {
				continue;
			}
			if ($part == '..') {
				array_pop($path);
			}
			else {
				$path[] = $part;
			}
		}
		$path = implode('/', $path);
	}

	$cur_params = array();
	if ($query != '') {
		parse_str($query, $cur_params);
	}

	if ($session && defined('SID') && SID != '') {
		list($name, $value) = explode('=', SID);
		$params[$name] = $value;
	}

	$params = array_merge($cur_params, $params);
	$query  = http_build_query($params);

	$url = $proto . '://' . $server . '/' . ltrim($path, '/') . ($query != '' ? '?' . $query : '');

	return $url;
}

/**
 * Version adaptée de la fonction http_redirect() de pecl_http
 *
 * @param string  $url     Url de redirection
 * @param array   $params  Paramètres à ajouter en fin d'url
 * @param boolean $session Ajout de l'ID de session PHP s'il y a lieu
 * @param integer $status  Code de redirection HTTP
 */
if (!function_exists('http_redirect')) {
function http_redirect($url, $params = array(), $session = false, $status = 0)
{
	$status = intval($status);
	if (!in_array($status, array(301, 302, 303, 307, 308))) {
		$status = 302;
	}

	$url = wan_build_url($url, $params, $session);
	http_response_code($status);
	header(sprintf('Location: %s', $url));

	//
	// Si la fonction header() ne donne rien, on affiche une page de redirection
	//
	printf('<p>If your browser doesn\'t support meta redirect, click
		<a href="%s">here</a> to go on next page.</p>', htmlspecialchars($url));
	exit;
}
}

/**
 * Initialisation des préférences et du moteur de templates
 *
 * @param array $admindata Données utilisateur
 */
function load_settings(&$admindata = array())
{
	global $nl_config;

	$file_pattern = WA_ROOTDIR . '/languages/%s/main.php';

	$check_list = array();

	if (is_array($admindata)) {
		if (!empty($admindata['admin_lang'])) {
			$check_list[] = $admindata['admin_lang'];
		}

		if (!empty($admindata['admin_dateformat'])) {
			$nl_config['date_format'] = $admindata['admin_dateformat'];
		}
	}

	if (!empty($nl_config['language'])) {
		$check_list[] = $nl_config['language'];
	}

	$accept_language = filter_input(INPUT_SERVER, 'HTTP_ACCEPT_LANGUAGE');
	if ($accept_language) {
		$accept_language = explode(',', $accept_language);

		foreach ($accept_language as $langcode) {
			$langcode = strtolower(substr(trim($langcode), 0, 2));

			if (validate_lang($langcode)) {
				$check_list[] = $langcode;
			}
		}
	}

	$check_list[] = 'fr';
	$check_list = array_unique($check_list);

	foreach ($check_list as $language) {
		if (file_exists(sprintf($file_pattern, $language))) {
			if (empty($lang) || $lang['CONTENT_LANG'] != $language) {
				require sprintf($file_pattern, $language);
			}

			break;
		}
	}

	if (empty($lang)) {
		plain_error('Les fichiers de localisation sont introuvables !');
	}

	if (is_array($admindata)) {
		$admindata['admin_lang'] = $lang['CONTENT_LANG'];
	}

	$GLOBALS['lang'] =& $lang;
	$GLOBALS['datetime'] =& $datetime;
}

/**
 * Gestionnaire d'erreur personnalisé du script
 *
 * @param integer $errno   Code de l'erreur
 * @param string  $errstr  Texte proprement dit de l'erreur
 * @param string  $errfile Fichier où s'est produit l'erreur
 * @param integer $errline Numéro de la ligne
 *
 * @return boolean
 */
function wan_error_handler($errno, $errstr, $errfile, $errline)
{
	//
	// On s'assure que si des erreurs surviennent au sein même du gestionnaire
	// d'erreurs alors que l'opérateur @ a été utilisé en amont, elles seront
	// bien traitées correctement, soit par le gestionnaire d'erreurs personnalisé,
	// soit par le gestionnaire d'erreurs natif de PHP
	// (dans le cas d'erreurs fatales par exemple <= C'est du vécu :().
	//
	$error_reporting = error_reporting($GLOBALS['default_error_reporting']);

	//
	// On affiche pas les erreurs non prises en compte dans le réglage du
	// error_reporting si error_reporting vaut 0, sauf si le niveau de
	// débogage est au maximum.
	//
	$debug_level = wan_get_debug_level();
	$debug  = ($debug_level == DEBUG_LEVEL_ALL);
	$debug |= ($debug_level == DEBUG_LEVEL_NORMAL && ($error_reporting & $errno));

	// Si l’affichage des erreurs peut être délégué à la classe Output.
	$skip  = check_theme_is_used();
	// Si l’affichage en bloc dans le bas de page est activé (défaut).
	$skip &= DISPLAY_ERRORS_IN_LOG;

	$error = new Error(array(
		'type'    => $errno,
		'message' => $errstr,
		'file'    => $errfile,
		'line'    => $errline,
		'ignore'  => (!$debug || !$skip)
	));

	wanlog($error);

	if ($error->isFatal() || ($debug && !$skip)) {
		wan_display_error($error);
	}

	return true;
}

/**
 * Gestionnaire d'erreur personnalisé du script
 *
 * @param Exception $e Exception "attrapée" par le gestionnaire
 */
function wan_exception_handler($e)
{
	wanlog($e);
	wan_display_error($e);
}

/**
 * Formatage du message d'erreurs
 *
 * @param Exception $error Exception décrivant l'erreur
 *
 * @return string
 */
function wan_format_error($error)
{
	global $db, $lang;

	$errno   = $error->getCode();
	$errstr  = $error->getMessage();
	$errfile = $error->getFile();
	$errline = $error->getLine();
	$backtrace = $error->getTrace();

	if ($error instanceof Error) {
		// Cas spécial. L'exception personnalisée a été créé dans wan_error_handler()
		// et contient donc l'appel à wan_error_handler() elle-même. On corrige.
		array_shift($backtrace);
	}

	foreach ($backtrace as $i => &$t) {
		if (!isset($t['file'])) {
			$t['file'] = 'unknown';
			$t['line'] = 0;
		}
		$file = htmlspecialchars(str_replace(dirname(__DIR__), '~', $t['file']));
		$call = (isset($t['class']) ? $t['class'].$t['type'] : '') . $t['function'];
		$t = sprintf('#%d  %s() called at [%s:%d]', $i, $call, $file, $t['line']);
	}

	if (count($backtrace) > 0) {
		$backtrace = sprintf("<b>Backtrace:</b>\n%s\n", implode("\n", $backtrace));
	}
	else {
		$backtrace = '';
	}

	if (wan_get_debug_level() == DEBUG_LEVEL_QUIET) {
		// Si on est en mode de non-débogage, on a forcément attrapé une erreur
		// critique pour arriver ici.
		$message  = $lang['Message']['Critical_error'];

		if ($errno == E_USER_ERROR) {
			if (!empty($lang['Message']) && !empty($lang['Message'][$errstr])) {
				$errstr = $lang['Message'][$errstr];
			}

			$message = $errstr;
		}
	}
	else if ($error instanceof Dblayer\Exception) {
		if ($db instanceof Wadb && $db->sqlstate != '') {
			$errno = $db->sqlstate;
		}

		$message  = sprintf("<b>SQL errno:</b> %s\n", $errno);
		$message .= sprintf("<b>SQL error:</b> %s\n", htmlspecialchars($errstr));

		if ($db instanceof Wadb && $db->lastQuery != '') {
			$message .= sprintf("<b>SQL query:</b> %s\n", htmlspecialchars($db->lastQuery));
		}

		$message .= $backtrace;
	}
	else {
		$labels  = array(
			E_NOTICE => 'PHP Notice',
			E_WARNING => 'PHP Warning',
			E_USER_ERROR => 'Error',
			E_USER_WARNING => 'Warning',
			E_USER_NOTICE => 'Notice',
			E_STRICT => 'PHP Strict',
			E_DEPRECATED => 'PHP Deprecated',
			E_USER_DEPRECATED => 'Deprecated',
			E_RECOVERABLE_ERROR => 'PHP Error'
		);

		$label   = (isset($labels[$errno])) ? $labels[$errno] : 'Unknown Error';
		$errfile = str_replace(dirname(__DIR__), '~', $errfile);

		if (!empty($lang['Message']) && !empty($lang['Message'][$errstr])) {
			$errstr = $lang['Message'][$errstr];
		}

		$message = sprintf(
			"<b>%s:</b> %s in <b>%s</b> on line <b>%d</b>\n",
			($error instanceof Error) ? $label : get_class($error),
			$errstr,
			$errfile,
			$errline
		);
		$message .= $backtrace;
	}

	return $message;
}

/**
 * Affichage du message dans le contexte d'utilisation (page web ou ligne de commande)
 *
 * @param Exception $error Exception décrivant l'erreur
 */
function wan_display_error($error)
{
	global $output;

	if ($error instanceof \Exception) {
		$exit = true;

		if ($error instanceof Error) {
			$exit = $error->isFatal();
		}
	}

	$message = wan_format_error($error);

	if (check_cli()) {
		if (function_exists('posix_isatty') && posix_isatty(STDOUT)) {
			$message = preg_replace("#<b>#",  "\033[1;31m", $message, 1);
			$message = preg_replace("#</b>#", "\033[0m", $message, 1);

			$message = preg_replace("#<b>#",  "\033[1;37m", $message);
			$message = preg_replace("#</b>#", "\033[0m", $message);
		}
		else {
			$message = preg_replace("#</?b>#", "", $message);
		}

		$message = htmlspecialchars_decode($message);

		fwrite(STDERR, $message);
	}
	else if (!$exit) {
		echo '<p>' . nl2br($message) . '</p>';
	}
	else {
		http_response_code(500);

		if (check_theme_is_used()) {
			$output->displayMessage($message, 'error');
		}
		else {
			$message = nl2br($message);
			echo <<<BASIC
<div>
	<h1>Erreur critique&nbsp;!</h1>

	<p>$message</p>
</div>
BASIC;
		}
	}

	if ($exit) {
		exit(1);
	}
}

/**
 * Si elle est appelée avec un argument, ajoute l'entrée dans le journal,
 * sinon, renvoie le journal.
 *
 * @param mixed $entry Peut être un objet Exception, ou n'importe quelle autre valeur
 *
 * @return array
 */
function wanlog($entry = null)
{
	static $entries = array();

	if (func_num_args() == 0) {
		return $entries;
	}

	if ($entry instanceof Exception) {
		$hash = md5(
			$entry->getCode() .
			$entry->getMessage() .
			$entry->getFile() .
			$entry->getLine()
		);

		$entries[$hash] = $entry;

		if (DEBUG_LOG_ENABLED) {
			error_log(preg_replace('#</?b>#', '', trim(wan_format_error($entry))));
		}
	}
	else {
		$entries[] = $entry;
	}
}

/**
 * Même fonctionnement que la fonction native error_get_last()
 *
 * @return array
 */
function wan_error_get_last()
{
	$errors = wanlog();
	$error  = null;

	while ($e = array_pop($errors)) {
		if ($e instanceof Exception) {
			$error = array(
				'type'    => $e->getCode(),
				'message' => $e->getMessage(),
				'file'    => $e->getFile(),
				'line'    => $e->getLine()
			);
			break;
		}
	}

	return $error;
}

/**
 * @param mixed   $var     Variable à afficher
 * @param boolean $exit    True pour terminer l'exécution du script
 * @param boolean $verbose True pour utiliser var_dump() (détails sur le contenu de la variable)
 */
function plain_error($var, $exit = true, $verbose = false)
{
	if (!headers_sent()) {
		header('Content-Type: text/plain; charset=UTF-8');
	}

	if ($verbose) {
		var_dump($var);
	}
	else {
		if (is_scalar($var)) {
			echo $var;
		}
		else {
			print_r($var);
		}
	}

	if ($exit) {
		exit;
	}
}

/**
 * Fonction d'affichage par page.
 *
 * @param string  $url           Adresse vers laquelle doivent pointer les liens de navigation
 * @param integer $total_item    Nombre total d'éléments
 * @param integer $item_per_page Nombre d'éléments par page
 * @param integer $page_id       Identifiant de la page en cours
 *
 * @return string
 */
function navigation($url, $total_item, $item_per_page, $page_id)
{
	global $lang;

	$total_pages = ceil($total_item / $item_per_page);

	// premier caractère de l'url au moins en position 1
	// on place un espace à la position 0 de la chaîne
	$url = ' ' . $url;

	$url .= (strpos($url, '?')) ? '&amp;' : '?';

	// suppression de l'espace précédemment ajouté
	$url = substr($url, 1);

	if ($total_pages == 1) {
		return '&nbsp;';
	}

	$nav_string = '';

	if ($total_pages > 10) {
		if ($page_id > 10) {
			$prev = $page_id;
			do {
				$prev--;
			}
			while ($prev % 10);

			$nav_string .= '<a href="' . $url . 'page=1">' . $lang['Start'] . '</a>&nbsp;&nbsp;';
			$nav_string .= '<a href="' . $url . 'page=' . $prev . '">' . $lang['Prev'] . '</a>&nbsp;&nbsp;';
		}

		$current = $page_id;
		do {
			$current--;
		}
		while ($current % 10);

		$current++;

		for ($i = $current; $i < ($current + 10); $i++) {
			if ($i <= $total_pages) {
				if ($i > $current) {
					$nav_string .= ', ';
				}

				$nav_string .= ($i == $page_id) ? '<b>' . $i . '</b>' : '<a href="' . $url . 'page=' . $i . '">' . $i . '</a>';
			}
		}

		$next = $page_id;
		while ($next % 10) {
			$next++;
		}
		$next++;

		if ($total_pages >= $next) {
			$nav_string .= '&nbsp;&nbsp;<a href="' . $url . 'page=' . $next . '">' . $lang['Next'] . '</a>';
			$nav_string .= '&nbsp;&nbsp;<a href="' . $url . 'page=' . $total_pages . '">' . $lang['End'] . '</a>';
		}
	}
	else {
		for ($i = 1; $i <= $total_pages; $i++) {
			if ($i > 1) {
				$nav_string .= ', ';
			}

			$nav_string .= ($i == $page_id) ? '<b>' . $i . '</b>' : '<a href="' . $url . 'page=' . $i . '">' . $i . '</a>';

		}
	}

	return $nav_string;
}

/**
 * Fonction de renvoi de date selon la langue
 *
 * @param string  $dateformat Format demandé
 * @param integer $timestamp  Timestamp unix à convertir
 *
 * @return string
 */
function convert_time($dateformat, $timestamp)
{
	static $search, $replace;

	if (!isset($search) || !isset($replace)) {
		global $datetime;

		$search = $replace = array();

		foreach ($datetime as $orig_word => $repl_word) {
			$search[]  = '/\b' . $orig_word . '\b/i';
			$replace[] = $repl_word;
		}
	}

	return preg_replace($search, $replace, date($dateformat, $timestamp));
}

/**
 * Fonction de purge de la table des abonnés
 * Retourne le nombre d'entrées supprimées
 * Fonction récursive
 *
 * @param integer $liste_id       Liste concernée
 * @param integer $limitevalidate Limite de validité pour confirmer une inscription
 * @param integer $purge_freq     Fréquence des purges
 *
 * @return integer
 */
function purge_liste($liste_id = 0, $limitevalidate = 0, $purge_freq = 0)
{
	global $db;

	if (!$liste_id) {
		$total_entries_deleted = 0;

		$sql = "SELECT liste_id, limitevalidate, purge_freq
			FROM " . LISTE_TABLE . "
			WHERE purge_next < " . time() . "
				AND auto_purge = 1";
		$result = $db->query($sql);

		while ($row = $result->fetch()) {
			$total_entries_deleted += purge_liste($row['liste_id'], $row['limitevalidate'], $row['purge_freq']);
		}

		//
		// Optimisation des tables
		//
		$db->vacuum(array(ABONNES_TABLE, ABO_LISTE_TABLE));

		return $total_entries_deleted;
	}
	else {
		$sql = "SELECT abo_id
			FROM " . ABO_LISTE_TABLE . "
			WHERE liste_id = $liste_id
				AND confirmed = " . SUBSCRIBE_NOT_CONFIRMED . "
				AND register_date < " . strtotime(sprintf('-%d days', $limitevalidate));
		$result = $db->query($sql);

		$abo_ids = array();
		while ($abo_id = $result->column('abo_id')) {
			$abo_ids[] = $abo_id;
		}
		$result->free();

		if (($num_abo_deleted = count($abo_ids)) > 0) {
			$sql_abo_ids = implode(', ', $abo_ids);

			$db->beginTransaction();

			$sql = "DELETE FROM " . ABONNES_TABLE . "
				WHERE abo_id IN(
					SELECT abo_id
					FROM " . ABO_LISTE_TABLE . "
					WHERE abo_id IN($sql_abo_ids)
					GROUP BY abo_id
					HAVING COUNT(abo_id) = 1
				)";
			$db->query($sql);

			$sql = "DELETE FROM " . ABO_LISTE_TABLE . "
				WHERE abo_id IN($sql_abo_ids)
					AND liste_id = " . $liste_id;
			$db->query($sql);

			$db->commit();
		}

		$sql = "UPDATE " . LISTE_TABLE . "
			SET purge_next = " . strtotime(sprintf('+%d days', $purge_freq)) . "
			WHERE liste_id = " . $liste_id;
		$db->query($sql);

		return $num_abo_deleted;
	}
}

/**
 * Annule l'effet produit par l'option de configuration magic_quotes_gpc à On
 * Fonction récursive
 *
 * @param mixed $data Tableau de données ou chaîne de caractères
 */
function strip_magic_quotes(&$data)
{
	static $doStrip = null;

	if (is_null($doStrip)) {
		$doStrip = false;
		if ((PHP_VERSION_ID < 50400 && get_magic_quotes_gpc()) ||
			ini_get('filter.default') == 'magic_quotes'
		) {
			$doStrip = true;
		}
	}

	if ($doStrip) {
		if (is_array($data)) {
			array_walk($data, function (&$data, $key) {
				strip_magic_quotes($data);
			});
		}
		else if (is_string($data)) {
			$data = stripslashes($data);
		}
	}
}

/**
 * Pour limiter la longueur d'une chaine de caractère à afficher
 *
 * @param string  $str
 * @param integer $len
 *
 * @return string
 */
function cut_str($str, $len)
{
	if (mb_strlen($str) > $len) {
		$str = mb_substr($str, 0, $len);

		if ($space = mb_strrpos($str, ' ')) {
			$str = mb_substr($str, 0, $space);
		}

		$str .= "\xe2\x80\xa6";// (U+2026) Horizontal ellipsis char
	}

	return $str;
}

/**
 * Convertit les liens dans un texte en lien html
 * Importé de WAgoldBook 2.0.x et précédemment importé de phpBB 2.0.x
 *
 * @param string $str
 *
 * @return string
 */
function active_urls($str)
{
	$str = ' ' . $str;

	$str = preg_replace("#([\n ])([a-z]+?)://([^,\t \n\r\"]+)#i", "\\1<a href=\"\\2://\\3\">\\2://\\3</a>", $str);
	$str = preg_replace("#([\n ])www\.([a-z0-9\-]+)\.([a-z0-9\-.\~]+)((?:/[^,\t \n\r\"]*)?)#i", "\\1<a href=\"http://www.\\2.\\3\\4\">www.\\2.\\3\\4</a>", $str);
	$str = preg_replace("#([\n ])([a-z0-9\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)?[\w]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $str);

	// Remove our padding..
	return substr($str, 1);
}

/**
 * Retourne la valeur booléenne d'une directive de configuration.
 * Certaines options de configuration peuvent avoir été incorrectement
 * paramétrées avec la directive php_value|php_admin_value alors que dans
 * le cas des options on/off, il faut utiliser php_flag|php_admin_flag.
 *
 * @param string $name Nom de la directive
 *
 * @return boolean
 */
function ini_get_flag($name)
{
	return (bool) preg_match('#^(on|yes|true|1)$#i', ini_get($name));
}

/**
 * Fonctions à utiliser lors des longues boucles (backup, envois)
 * qui peuvent provoquer un time out du navigateur client
 * Inspiré d'un code équivalent dans phpMyAdmin 2.5.0 (libraries/build_dump.lib.php précisément)
 *
 * @param boolean $in_loop True si on est dans la boucle, false pour initialiser $time
 */
function fake_header($in_loop)
{
	static $time;

	if ($in_loop) {
		$new_time = time();

		if (($new_time - $time) >= 30) {
			$time = $new_time;
			header('X-WaPing: Pong');
		}
	}
	else {
		$time = time();
	}
}

/**
 * Détection d'encodage et conversion de $charset
 *
 * @param string  $data
 * @param string  $charset   Jeu de caractères des données fournies
 * @param boolean $check_bom Détection du BOM en tête des données
 *
 * @return string
 */
function convert_encoding($data, $charset = null, $check_bom = true)
{
	if (empty($charset)) {
		if ($check_bom && strncmp($data, "\xEF\xBB\xBF", 3) == 0) {
			$charset = 'UTF-8';
			$data = substr($data, 3);
		}
		else if (u::isUtf8($data)) {
			$charset = 'UTF-8';
		}
	}

	if (empty($charset)) {
		$charset = 'windows-1252';
		trigger_error("Cannot found valid charset. Using windows-1252 as fallback", E_USER_NOTICE);
	}

	if (strtoupper($charset) != 'UTF-8') {
		$data = iconv($charset, 'UTF-8', $data);
	}

	return $data;
}

/**
 * Récupère un contenu local ou via HTTP et le retourne, ainsi que le jeu de
 * caractère et le type de média de la chaîne, si disponible.
 *
 * @param string $URL    L'URL à appeller
 * @param string $errstr Conteneur pour un éventuel message d'erreur
 *
 * @return boolean|array
 */
function wan_get_contents($URL, &$errstr)
{
	global $lang;

	if (strncmp($URL, 'http://', 7) == 0) {
		$result = http_get_contents($URL, $errstr);
		if (!$result) {
			$errstr = sprintf($lang['Message']['Error_load_url'], htmlspecialchars($URL), $errstr);
		}
	}
	else {
		if ($URL[0] == '~') {
			$URL = $_SERVER['DOCUMENT_ROOT'] . substr($URL, 1);
		}
		else if ($URL[0] != '/') {
			$URL = WA_ROOTDIR . '/' . $URL;
		}

		if (is_readable($URL)) {
			$result = array('data' => file_get_contents($URL), 'charset' => null);
		}
		else {
			$result = false;
			$errstr = sprintf($lang['Message']['File_not_exists'], htmlspecialchars($URL));
		}
	}

	return $result;
}

/**
 * Récupère un contenu via HTTP et le retourne, ainsi que le jeu de
 * caractère et le type de média de la chaîne, si disponible.
 *
 * @param string $URL    L'URL à appeller
 * @param string $errstr Conteneur pour un éventuel message d'erreur
 *
 * @return boolean|array
 */
function http_get_contents($URL, &$errstr)
{
	global $lang;

	if (!($part = parse_url($URL)) || !isset($part['scheme']) || !isset($part['host']) || $part['scheme'] != 'http') {
		$errstr = $lang['Message']['Invalid_url'];
		return false;
	}

	$port = (!isset($part['port'])) ? 80 : $part['port'];

	if (!($fs = fsockopen($part['host'], $port, $null, $null, 5))) {
		$errstr = sprintf($lang['Message']['Unaccess_host'], htmlspecialchars($part['host']));
		return false;
	}

	stream_set_timeout($fs, 5);

	$path  = (!isset($part['path'])) ? '/' : $part['path'];
	$path .= (!isset($part['query'])) ? '' : '?'.$part['query'];

	// HTTP 1.0 pour ne pas recevoir en Transfer-Encoding: chunked
	fwrite($fs, sprintf("GET %s HTTP/1.0\r\n", $path));
	fwrite($fs, sprintf("Host: %s\r\n", $part['host']));
	fwrite($fs, sprintf("User-Agent: %s\r\n", sprintf(USER_AGENT_SIG, WANEWSLETTER_VERSION)));
	fwrite($fs, "Accept: */*\r\n");

	if (extension_loaded('zlib')) {
		fwrite($fs, "Accept-Encoding: gzip\r\n");
	}

	fwrite($fs, "Connection: close\r\n\r\n");

	$isGzipped = false;
	$datatype  = $charset = null;
	$data = '';
	$tmp  = fgets($fs, 1024);

	if (!preg_match('#^HTTP/(\d\.[x\d])\x20+(\d{3})\s#', $tmp, $m) || $m[2] != 200) {
		$errstr = $lang['Message']['Not_found_at_url'];
		fclose($fs);
		return false;
	}

	// Entêtes
	while (!feof($fs)) {
		$tmp = fgets($fs, 1024);

		if (!strpos($tmp, ':')) {
			break;
		}

		list($header, $value) = explode(':', $tmp);
		$header = strtolower($header);
		$value  = trim($value);

		if ($header == 'content-type') {
			if (preg_match('/^([a-z]+\/[a-z0-9+.-]+)\s*(?:;\s*charset=(")?([a-z][a-z0-9._-]*)(?(2)"))?/i', $value, $m)) {
				$datatype = $m[1];
				$charset  = (!empty($m[3])) ? strtoupper($m[3]) : '';
			}
		}
		else if ($header == 'content-encoding' && $value == 'gzip') {
			$isGzipped = true;
		}
	}

	// Contenu
	while (!feof($fs)) {
		$data .= fgets($fs, 1024);
	}

	fclose($fs);

	if ($isGzipped && !preg_match('/\.t?gz$/i', $part['path'])) {
		// RFC 1952 - Users note on http://www.php.net/manual/en/function.gzencode.php
		if (strncmp($data, "\x1f\x8b", 2) != 0) {
			trigger_error('data is not to GZIP format', E_USER_WARNING);
			return false;
		}

		$data = gzinflate(substr($data, 10));
	}

	if (empty($charset) && preg_match('#(?:/|\+)xml$#', $datatype) && strncmp($data, '<?xml', 5) == 0) {
		$prolog = substr($data, 0, strpos($data, "\n"));

		if (preg_match('/\s+encoding\s*=\s*("|\')([a-z][a-z0-9._-]*)\\1/i', $prolog, $m)) {
			$charset = $m[2];
		}
	}

	return array('type' => $datatype, 'charset' => $charset, 'data' => $data);
}

/**
 * Formate un nombre en fonction de paramètres de langue (idem que number_format() mais on ne spécifie
 * que deux arguments max, les deux autres sont récupérés dans $lang)
 *
 * @param float   $number
 * @param integer $decimals
 *
 * @return string
 */
function wa_number_format($number, $decimals = 2)
{
	$number = u::number_format($number, $decimals, $GLOBALS['lang']['DEC_POINT'], $GLOBALS['lang']['THOUSANDS_SEP']);
	$number = rtrim($number, '0');
	$dec_point_len = strlen($GLOBALS['lang']['DEC_POINT']);

	if (substr($number, -$dec_point_len) === $GLOBALS['lang']['DEC_POINT']) {
		$number = substr($number, 0, -$dec_point_len);
	}

	return $number;
}

/**
 * Retourne le nombre de références 'cid' (appel d'objet dans un email)
 *
 * @param string $body
 * @param array  $refs
 *
 * @return integer
 */
function hasCidReferences($body, &$refs)
{
	$total = preg_match_all('/<[^>]+"cid:([^\\:*\/?<">|]+)"[^>]*>/', $body, $matches);
	$refs  = $matches[1];

	return $total;
}

/**
 * Retourne une taille en octet formatée pour être lisible par un humain
 *
 * @param string $size
 *
 * @return string
 */
function formateSize($size)
{
	$k = 1024;
	$m = $k * $k;
	$g = $m * $k;

	if ($size >= $g) {
		$unit = $GLOBALS['lang']['GO'];
		$size /= $g;
	}
	else if ($size >= $m) {
		$unit = $GLOBALS['lang']['MO'];
		$size /= $m;
	}
	else if ($size >= $k) {
		$unit = $GLOBALS['lang']['KO'];
		$size /= $k;
	}
	else {
		$unit = $GLOBALS['lang']['Octets'];
	}

	return sprintf("%s\xC2\xA0%s", wa_number_format($size), $unit);
}

/**
 * Vérifie si l'utilisateur concerné est administrateur
 *
 * @param array $admin Tableau des données de l'utilisateur
 *
 * @return boolean
 */
function wan_is_admin($admin)
{
	return (isset($admin['admin_level']) && $admin['admin_level'] == ADMIN_LEVEL);
}

/**
 * Retourne le niveau de débogage, dépendant de la valeur de la constante DEBUG_MODE
 * ainsi que de la clé de configuration 'debug_level'.
 *
 * @return integer
 */
function wan_get_debug_level()
{
	global $nl_config;

	$debug_level = DEBUG_MODE;
	if (isset($nl_config['debug_level']) && $nl_config['debug_level'] > DEBUG_MODE) {
		$debug_level = min(DEBUG_LEVEL_ALL, $nl_config['debug_level']);
	}

	return $debug_level;
}

/**
 * Forge l'url vers une entrée de la FAQ
 *
 * @param string $chapter
 *
 * @return string
 */
function wan_get_faq_url($chapter)
{
	global $nl_config, $lang;

	return sprintf('%s/docs/faq.%s.html#%s',
		rtrim($nl_config['path'], '/'),
		$lang['CONTENT_LANG'],
		$chapter
	);
}

/**
 * Envoi d'email
 *
 * @param Email   $email
 * @param boolean $keepalive Maintient ouvert la connexion au serveur SMTP, le cas échéant
 *
 * @return boolean
 */
function wan_sendmail(Email $email, $keepalive = false)
{
	global $nl_config;
	static $smtp;

	Mailer::$signature = sprintf(X_MAILER_HEADER, WANEWSLETTER_VERSION);

	if ($nl_config['use_smtp'] && is_null($smtp)) {
		$server = ($nl_config['smtp_tls'] == SECURITY_FULL_TLS) ? 'tls://%s:%d' : '%s:%d';
		$options = array(
			'server'   => sprintf($server, $nl_config['smtp_host'], $nl_config['smtp_port']),
			'starttls' => ($nl_config['smtp_tls'] == SECURITY_STARTTLS),
			'auth' => array(
				'username'  => $nl_config['smtp_user'],
				'secretkey' => $nl_config['smtp_pass']
			),
			'keepalive' => $keepalive
		);
		$smtp = Mailer::setTransport('smtp', $options);
	}

	Mailer::send($email);
}

/**
 * Retourne la liste des tags personnalisés (voir modèle tags.sample.inc.php)
 *
 * @return array
 */
function wan_get_tags()
{
	static $other_tags = array();

	if (count($other_tags) > 0) {
		return $other_tags;
	}

	$tags_file  = WA_ROOTDIR . '/data/tags.inc.php';

	if (is_readable($tags_file)) {
		include $tags_file;
	}
	else {
		// compatibilité Wanewsletter < 3.0-beta1
		$tags_file  = WA_ROOTDIR . '/includes/tags.inc.php';
		if (is_readable($tags_file)) {
			include $tags_file;
			wanlog("Using ~/includes/tags.inc.php. You should move this file into data/ directory.");
		}
	}

	return $other_tags;
}

/**
 * Retourne la taille maximale possible des fichiers uploadés par un formulaire
 * HTML multipart/form-data, ou 0 si l’upload est désactivé sur le serveur.
 * La fonction tient compte des options PHP 'upload_max_filesize' et
 * 'post_max_size' pour le calcul de la taille de fichier autorisée.
 *
 * @return integer
 */
function get_max_filesize()
{
	if (!ini_get_flag('file_uploads')) {
		return 0;
	}

	$literal2integer = function ($size) {
		if (preg_match('/^([0-9]+)([KMG])$/i', $size, $m)) {
			switch (strtoupper($m[2])) {
				case 'K':
					$size = ($m[1] * 1024);
					break;
				case 'M':
					$size = ($m[1] * 1024 * 1024);
					break;
				case 'G': // Since php 5.1.0
					$size = ($m[1] * 1024 * 1024 * 1024);
					break;
			}
		}
		else {
			$size = intval($size);
		}

		return $size;
	};

	if (!($filesize = ini_get('upload_max_filesize'))) {
        $filesize = '2M'; // 2 Méga-Octets
    }

	$upload_max_size = $literal2integer($filesize);

    if ($postsize = ini_get('post_max_size')) {
        $postsize = $literal2integer($postsize);
        if ($postsize < $upload_max_size) {
            $upload_max_size = $postsize;
        }
    }

    return $upload_max_size;
}

/**
 * Indique si PHP supporte les connexion SSL/TLS.
 *
 * @return boolean
 */
function check_ssl_support()
{
	$transports = array();
	if (function_exists('stream_get_transports')) {
		$transports = stream_get_transports();
	}

	return (in_array('ssl', $transports) || in_array('tls', $transports));
}

/**
 * Vérifie si on est en ligne de commande.
 *
 * @return boolean
 */
function check_cli()
{
	if (PHP_SAPI != 'cli' && (PHP_SAPI != 'cgi-fcgi' || !empty($_SERVER['SERVER_ADDR']))) {
		return false;
	}

	if (PHP_SAPI == 'cgi-fcgi' && !defined('STDIN')) {
		define('STDIN',  fopen('php://stdin',  'r'));
		define('STDOUT', fopen('php://stdout', 'w'));
		define('STDERR', fopen('php://stderr', 'w'));
	}

	return true;
}

/**
 * Indique si on se trouve dans l’administration.
 *
 * @return boolean
 */
function check_in_admin()
{
	return defined(__NAMESPACE__.'\\IN_ADMIN');
}

/**
 * Indique si le thème Wanewsletter est utilisé pour afficher cette page.
 *
 * @return boolean
 */
function check_theme_is_used()
{
	return (!check_cli() && (check_in_admin() ||
		defined(__NAMESPACE__.'\\IN_INSTALL') ||
		defined(__NAMESPACE__.'\\IN_PROFILCP')
	));
}

/**
 * @param string $pseudo
 *
 * @return boolean
 */
function validate_pseudo($pseudo)
{
	return (mb_strlen($pseudo) >= 2 && mb_strlen($pseudo) <= 30);
}

/**
 * @param string $passwd
 *
 * @return boolean
 */
function validate_pass($passwd)
{
	return (bool) preg_match('/^[\x20-\x7E]{6,1024}$/', $passwd);
}

/**
 * @param string $language
 *
 * @return boolean
 */
function validate_lang($language)
{
	return (preg_match('/^[\w_]+$/', $language) &&
		file_exists(WA_ROOTDIR . '/languages/' . $language . '/main.php')
	);
}
