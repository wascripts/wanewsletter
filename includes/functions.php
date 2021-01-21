<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@webnaute.net>
 * @link      http://dev.webnaute.net/wanewsletter/
 * @copyright 2002-2021 Aurélien Maille
 * @license   https://www.gnu.org/licenses/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

use Wamailer\Mailer;

/**
 * Chargement de la localisation, puis du fichier de configuration initial,
 * avec redirection vers install.php si le fichier n’existe pas.
 */
function load_config()
{
	global $output;

	load_l10n();

	$dsn = '';
	$prefix = 'wa_';
	$nl_config = [];

	$need_update  = false;
	$test_files[] = WA_ROOTDIR . '/data/config.inc.php';
	// Emplacement du fichier dans Wanewsletter < 3.0-beta1
	$test_files[] = WA_ROOTDIR . '/includes/config.inc.php';

	foreach ($test_files as $file) {
		if (file_exists($file)) {
			if (!is_readable($file)) {
				$output->message('Unreadable_config_file');
			}

			include $file;
			break;
		}

		$need_update = true;
	}

	if (!is_array($nl_config)) {
		$nl_config = [];
	}

	$base_config = [];
	// Ajout des répertoires par défaut utilisés par le script.
	// Le tilde est remplacé par WA_ROOTDIR, qui mène au répertoire
	// d’installation de Wanewsletter (voir plus bas).
	$base_config['logs_dir']  = '~/data/logs';
	$base_config['stats_dir'] = '~/data/stats';
	$base_config['tmp_dir']   = '~/data/tmp';

	$nl_config = array_merge($base_config, $nl_config);

	// Configuration initiale pour Wamailer
	if (!isset($nl_config['mailer']) || !is_array($nl_config['mailer'])) {
		$nl_config['mailer'] = [];
	}

	//
	// Les constantes NL_INSTALLED et WA_VERSION sont obsolètes.
	//
	if (defined('NL_INSTALLED') || defined('WA_VERSION')) {
		$need_update = true;
	}

	// $prefixe renommée $prefix
	if (isset($prefixe)) {
		$prefix = $prefixe;
	}

	// Utilisé à la fin d’une mise à jour pour mettre à jour également un
	// fichier de configuration obsolète.
	define(__NAMESPACE__.'\\UPDATE_CONFIG_FILE', $need_update);

	// Si on est dans l’installeur, on récupère ici l’entrée 'prefix' du
	// formulaire, car on en a besoin pour créer les constantes *_TABLE.
	if (defined(__NAMESPACE__.'\\IN_INSTALL')) {
		$prefix = filter_input(INPUT_POST, 'prefix', FILTER_DEFAULT, [
			'options' => ['default' => $prefix]
		]);
	}
	// Si le script n’est pas installé, on redirige vers l’installeur, ou
	// on affiche un message explicatif.
	else if (!$dsn) {
		if (!check_cli()) {
			$install_script = 'install.php';
			if (!file_exists($install_script)) {
				$install_script = '../'.$install_script;
			}

			http_redirect($install_script);
		}
		else {
			$output->message('Not_installed');
		}
	}

	$nl_config['db']['prefix'] = $prefix;

	//
	// Options supplémentaires transmises par commodité sous forme de tableau
	//
	if ($dsn) {
		$args = http_build_query($nl_config['db'], '', '&');
		$dsn .= (strpos($dsn, '?') ? '&' : '?') . $args;
	}

	//
	// Déclaration des constantes de tables
	//
	foreach (get_db_tables($prefix) as $tablename) {
		$constant = sprintf('%s\\%s_TABLE', __NAMESPACE__,
			strtoupper(substr($tablename, strlen($prefix)))
		);
		define($constant, $tablename);
	}

	//
	// Déclaration des dossiers et fichiers spéciaux utilisés par le script
	//
	$realpath = function ($path) {
		if ($path && $path[0] == '~') {
			$path = substr($path, 1);
			$path = WA_ROOTDIR . $path;
		}

		return rtrim(str_replace('\\', '/', $path), '/');
	};

	// Liste des entrées de configuration pouvant contenir un chemin de fichier
	$config_list = ['logs_dir','stats_dir','tmp_dir','db.ssl-ca','db.ssl-cert',
		'db.ssl-key','db.rootcert','db.sslcert','db.sslkey',
		'mailer.dkim.privkey','mailer.ssl.cafile','mailer.ssl.capath',
		'mailer.ssl.local_cert','mailer.ssl.local_pk'];

	foreach ($config_list as $name) {
		$parts = explode('.', $name);

		$value =& $nl_config;
		foreach ($parts as $part) {
			if (!isset($value[$part])) {
				continue 2;
			}
			$value =& $value[$part];
		}

		// Cas particulier. Peut aussi contenir une clé au format PEM
		if ($name == 'mailer.dkim.privkey' && strpos($value, '-----BEGIN') === 0) {
			continue;
		}

		$value = $realpath($value);
		unset($value);// On détruit la référence.
	}

	if (!is_writable($nl_config['tmp_dir'])) {
		$output->message(sprintf(
			$GLOBALS['lang']['Message']['Dir_not_writable'],
			htmlspecialchars($nl_config['tmp_dir'])
		));
	}

	if (DEBUG_LOG_ENABLED && DEBUG_LOG_FILE) {
		$add_prefix = false;
		$filename = DEBUG_LOG_FILE;

		if (strncasecmp(PHP_OS, 'Win', 3) === 0) {
			if (!preg_match('#^[a-z]:[/\\\\]#i', $filename)) {
				$add_prefix = true;
			}
		}
		else if ($filename[0] != '/') {
			$add_prefix = true;
		}

		if ($add_prefix) {
			$filename = $nl_config['logs_dir'] . '/' . $filename;
		}

		ini_set('error_log', $filename);
	}

	// Injection dans le scope global
	$GLOBALS['dsn'] = $dsn;
	$GLOBALS['nl_config'] = $nl_config;
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
 * @return integer
 */
function wa_check_update($complete = false)
{
	global $nl_config;

	$cache_file = sprintf('%s/%s', $nl_config['tmp_dir'], CHECK_UPDATE_CACHE);
	$cache_ttl  = CHECK_UPDATE_CACHE_TTL;

	$result = -1;
	$data   = '';

	if (is_readable($cache_file) && filemtime($cache_file) > (time() - $cache_ttl)) {
		$data = trim(file_get_contents($cache_file));
	}
	else if ($complete) {
		$result = http_get_contents(CHECK_UPDATE_URL);
		$data   = trim($result['data']);

		if (!preg_match('#^[0-9]+\.[0-9]+(\.[0-9]+|-[A-Za-z0-9]+)$#', $data)) {
			throw new Exception("Bad version identifier received!");
		}

		file_put_contents($cache_file, $data);
	}

	if ($data) {
		$result = intval(version_compare(WANEWSLETTER_VERSION, $data, '<'));
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
	global $db, $nl_config;

	$result = $db->query("SELECT * FROM " . CONFIG_TABLE);
	$result->setFetchMode($result::FETCH_ASSOC);
	$row    = $result->fetch();
	$config = [];

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

	return array_merge($nl_config, $config);
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
		$config = [$config => $value];
	}

	$result = $db->query("SELECT config_name FROM " . CONFIG_TABLE);
	$result->setFetchMode($result::FETCH_ASSOC);

	$config_list = [];

	while ($row = $result->fetch()) {
		$config_list[] = $row['config_name'];
	}

	$config = array_intersect_key($config, array_flip($config_list));

	foreach ($config as $name => $value) {
		$sql = sprintf(
			"UPDATE %s SET config_value = '%s' WHERE config_name = '%s'",
			CONFIG_TABLE,
			$db->escape($value),
			$db->escape($name)
		);
		$db->query($sql);
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
function is_secure_connection()
{
	return ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 443 == $_SERVER['SERVER_PORT']);
}

/**
 * Construction d'une url
 *
 * @param string  $url     Url à compléter
 * @param array   $params  Paramètres à ajouter en fin d'url
 *
 * @return string
 */
function http_build_url($url, array $params = [])
{
	$parts = parse_url($url);

	if (empty($parts['scheme'])) {
		$proto = (is_secure_connection()) ? 'https' : 'http';
	}
	else {
		$proto = $parts['scheme'];
	}

	$server = (!empty($parts['host'])) ? $parts['host'] : $_SERVER['SERVER_NAME'];

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
		$path  = [];

		foreach ($parts as $part) {
			if ($part == '.') {
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

	$cur_params = [];
	if ($query != '') {
		parse_str($query, $cur_params);
	}

	$params = array_merge($cur_params, $params);
	$query  = http_build_query($params);

	$url = $proto . '://' . $server . '/' . ltrim($path, '/') . ($query != '' ? '?' . $query : '');

	return $url;
}

/**
 * Version adaptée de la fonction http_redirect() de pecl_http < 2.0
 *
 * @param string  $url     Url de redirection
 * @param array   $params  Paramètres à ajouter en fin d'url
 * @param boolean $session Ajout de l'ID de session PHP s'il y a lieu
 * @param integer $status  Code de redirection HTTP
 */
function http_redirect($url, array $params = [], $session = false, $status = 0)
{
	$status = intval($status);
	if (!in_array($status, [301, 302, 303, 307, 308])) {
		$status = 302;
	}

	if ($session && defined('SID') && SID != '') {
		list($name, $value) = explode('=', SID);
		$params[$name] = $value;
	}

	$url = http_build_url($url, $params);
	http_response_code($status);
	header(sprintf('Location: %s', $url));

	//
	// Si la fonction header() ne donne rien, on affiche une page de redirection
	//
	printf('<p>If your browser doesn\'t support meta redirect, click
		<a href="%s">here</a> to go on next page.</p>', htmlspecialchars($url));
	exit;
}

/**
 * Chargement des chaînes de localisation
 *
 * @param array $userdata Données utilisateur
 */
function load_l10n(array &$userdata = [])
{
	global $nl_config, $output;

	$file_pattern = WA_ROOTDIR . '/languages/%s/main.php';

	$check_list = [];

	if (!empty($userdata['language'])) {
		$check_list[] = $userdata['language'];
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
		$output->basic('Les fichiers de localisation sont introuvables !');
	}

	$userdata['language'] = $lang['CONTENT_LANG'];

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
	global $output;

	//
	// On s'assure que si des erreurs surviennent au sein même du gestionnaire
	// d'erreurs alors que l'opérateur @ a été utilisé en amont, elles seront
	// bien traitées correctement, soit par le gestionnaire d'erreurs personnalisé,
	// soit par le gestionnaire d'erreurs natif de PHP
	// (dans le cas d'erreurs fatales par exemple <= C'est du vécu :().
	//
	$error_reporting = error_reporting(DEFAULT_ERROR_REPORTING);

	//
	// On affiche pas les erreurs non prises en compte dans le réglage du
	// error_reporting si error_reporting vaut 0, sauf si le niveau de
	// débogage est au maximum.
	//
	$debug_level = wan_get_debug_level();
	$debug  = ($debug_level == DEBUG_LEVEL_ALL);
	$debug |= ($debug_level == DEBUG_LEVEL_NORMAL && ($error_reporting & $errno));

	$error = new Error([
		'type'    => $errno,
		'message' => $errstr,
		'file'    => $errfile,
		'line'    => $errline,
		'ignore'  => !$debug
	]);

	wanlog($error);
	$output->error($error);

	return true;
}

/**
 * Gestionnaire d’exception personnalisé du script
 *
 * @param \Throwable $e Objet "attrapé" par le gestionnaire
 */
function wan_exception_handler($e)
{
	global $output;

	wanlog($e);
	$output->error($e);
}

/**
 * Si elle est appelée avec un argument, ajoute l'entrée dans le journal,
 * sinon, retourne le journal.
 *
 * @param mixed $entry Peut être un objet \Throwable, ou n’importe quelle autre valeur
 *
 * @return array
 */
function wanlog($entry = null)
{
	static $entries = [];

	if (func_num_args() == 0) {
		return $entries;
	}

	if ($entry instanceof \Throwable || $entry instanceof \Exception) {
		$hash = md5(
			$entry->getCode() .
			$entry->getMessage() .
			$entry->getFile() .
			$entry->getLine()
		);

		$entries[$hash] = $entry;

		if (DEBUG_LOG_ENABLED) {
			$entry = trim(Output\CommandLine::formatError($entry));
		}
	}
	else {
		$entries[] = $entry;
	}

	if (DEBUG_LOG_ENABLED) {
		error_log($entry);
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
		if ($e instanceof \Throwable || $e instanceof \Exception) {
			$error = [
				'type'    => $e->getCode(),
				'message' => $e->getMessage(),
				'file'    => $e->getFile(),
				'line'    => $e->getLine()
			];
			break;
		}
	}

	return $error;
}

/**
 * Fonction d'affichage par page.
 *
 * @param string  $url           Adresse vers laquelle doivent pointer les liens de navigation
 * @param integer $total_items   Nombre total d'éléments
 * @param integer $item_per_page Nombre d'éléments par page
 * @param integer $page_id       Identifiant de la page en cours
 *
 * @return string
 */
function navigation($url, $total_items, $item_per_page, $page_id)
{
	global $lang;

	$total_pages = ceil($total_items / $item_per_page);

	$url .= (strpos($url, '?') !== false) ? '&amp;' : '?';

	if ($total_pages == 1) {
		return '&nbsp;';
	}

	$get_page_url = function ($i) use ($url, $page_id) {
		return ($i == $page_id)
			? sprintf('<b>%d</b>', $i)
			: sprintf('<a href="%1$spage=%2$d">%2$d</a>', $url, $i);
	};

	$nav_string = '';

	if ($total_pages > 10) {
		if ($page_id > 10) {
			$prev = $page_id;
			do {
				$prev--;
			}
			while ($prev % 10);

			$template = '<a href="%spage=%d">%s</a>&nbsp;&nbsp;';
			$nav_string .= sprintf($template, $url, 1, $lang['Start']);
			$nav_string .= sprintf($template, $url, $prev, $lang['Prev']);
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

				$nav_string .= $get_page_url($i);
			}
		}

		$next = $page_id;
		while ($next % 10) {
			$next++;
		}
		$next++;

		if ($total_pages >= $next) {
			$template = '&nbsp;&nbsp;<a href="%spage=%d">%s</a>';
			$nav_string .= sprintf($template, $url, $next, $lang['Next']);
			$nav_string .= sprintf($template, $url, $total_pages, $lang['End']);
		}
	}
	else {
		for ($i = 1; $i <= $total_pages; $i++) {
			if ($i > 1) {
				$nav_string .= ', ';
			}

			$nav_string .= $get_page_url($i);

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

		$search = $replace = [];

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
 * @param array $listdata Liste concernée
 *
 * @return integer
 */
function purge_liste(array $listdata = [])
{
	global $db;

	if (!$listdata) {
		$total_entries_deleted = 0;

		$sql = "SELECT liste_id, limitevalidate, purge_freq
			FROM %s
			WHERE purge_next < %d AND auto_purge = 1";
		$sql = sprintf($sql, LISTE_TABLE, time());
		$result = $db->query($sql);

		while ($listdata = $result->fetch()) {
			$total_entries_deleted += purge_liste($listdata);
		}

		//
		// Optimisation des tables
		//
		$db->vacuum([ABONNES_TABLE, ABO_LISTE_TABLE]);

		return $total_entries_deleted;
	}
	else {
		$sql = "SELECT abo_id
			FROM %s
			WHERE liste_id = %d AND confirmed = %d AND register_date < %d";
		$sql = sprintf($sql, ABO_LISTE_TABLE, $listdata['liste_id'],
			SUBSCRIBE_NOT_CONFIRMED,
			strtotime(sprintf('-%d days', $listdata['limitevalidate']))
		);
		$result = $db->query($sql);

		$abo_ids = [];
		while ($abo_id = $result->column('abo_id')) {
			$abo_ids[] = $abo_id;
		}
		$result->free();

		if (($num_abo_deleted = count($abo_ids)) > 0) {
			$sql_abo_ids = implode(', ', $abo_ids);

			$db->beginTransaction();

			$sql = "DELETE FROM %s
				WHERE abo_id IN(
					SELECT abo_id
					FROM %s
					WHERE abo_id IN(%s)
					GROUP BY abo_id
					HAVING COUNT(abo_id) = 1
				)";
			$sql = sprintf($sql, ABONNES_TABLE, ABO_LISTE_TABLE, $sql_abo_ids);
			$db->query($sql);

			$sql = "DELETE FROM %s
				WHERE abo_id IN(%s) AND liste_id = %d";
			$sql = sprintf($sql, ABO_LISTE_TABLE, $sql_abo_ids, $listdata['liste_id']);
			$db->query($sql);

			$db->commit();
		}

		$sql = "UPDATE %s SET purge_next = %d WHERE liste_id = %d";
		$sql = sprintf($sql, LISTE_TABLE,
			strtotime(sprintf('+%d days', $listdata['purge_freq'])),
			$listdata['liste_id']
		);
		$db->query($sql);

		return $num_abo_deleted;
	}
}

/**
 * Annule l’effet produit par l’option de configuration 'filter.default'
 * positionnée sur 'magic_quotes'.
 * Fonction récursive
 *
 * @param mixed $data Tableau de données ou chaîne de caractères
 */
function strip_magic_quotes(&$data)
{
	if (ini_get('filter.default') == 'magic_quotes') {
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
 */
function fake_header()
{
	static $ts;

	$new_ts = time();

	if (!$ts || ($new_ts - $ts) >= 30) {
		header('X-WaPing: Pong');
		$ts = $new_ts;
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
	if (!$charset) {
		if ($check_bom && strncmp($data, "\xEF\xBB\xBF", 3) == 0) {
			$charset = 'UTF-8';
			$data = substr($data, 3);
		}
		else if (!empty($data) && preg_match('//u', $data)) {
			$charset = 'UTF-8';
		}
	}

	if (!$charset) {
		$charset = 'windows-1252';
		trigger_error("Cannot found valid charset. Using windows-1252 as fallback", E_USER_NOTICE);
	}

	if (strtoupper($charset) != 'UTF-8') {
		$data = mb_convert_encoding($data, 'UTF-8', $charset);
	}

	return $data;
}

/**
 * Récupère un contenu local ou via HTTP et le retourne, ainsi que le jeu de
 * caractères et le type de média de la chaîne, si disponible.
 *
 * @param string $url L’URL à appeller
 *
 * @throws Exception
 * @return array
 */
function wan_get_contents($url)
{
	global $lang;

	if (preg_match('#^https?://#', $url)) {
		try {
			$result = http_get_contents($url);
		}
		catch (Exception $e) {
			throw new Exception(sprintf(
				$lang['Message']['Error_load_url'],
				htmlspecialchars($url),
				$e->getMessage()
			));
		}
	}
	else {
		if ($url[0] == '~') {
			$url = $_SERVER['DOCUMENT_ROOT'] . substr($url, 1);
		}
		else if ($url[0] != '/') {
			$url = WA_ROOTDIR . '/' . $url;
		}

		if (!is_readable($url)) {
			throw new Exception(sprintf($lang['Message']['File_not_exists'], htmlspecialchars($url)));
		}

		$result = ['data' => file_get_contents($url), 'charset' => null];
	}

	return $result;
}

/**
 * Récupère un contenu via HTTP et le retourne, ainsi que le jeu de
 * caractères et le type de média de la chaîne, si disponible.
 *
 * @param string $url L’URL à appeller
 *
 * @throws Exception
 * @return array
 */
function http_get_contents($url)
{
	global $lang;

	if (!preg_match('#^https?://#', $url)) {
		throw new Exception($lang['Message']['Invalid_url']);
	}

	$data = $mime = $charset = '';

	$connect_timeout = 10;
	$user_agent = sprintf(USER_AGENT_SIG, WANEWSLETTER_VERSION);

	if (function_exists('curl_init')) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_COOKIESESSION,  true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
		curl_setopt($ch, CURLOPT_USERAGENT,      $user_agent);

		$data = curl_exec($ch);

		if (curl_errno($ch)) {
			throw new Exception(curl_error($ch));
		}

		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
			throw new Exception($lang['Message']['Not_found_at_url']);
		}

		if (!($mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE))) {
			$mime = 'application/octet-stream';
		}

		curl_close($ch);
	}
	else {
		if (!ini_get_flag('allow_url_fopen')) {
			throw new Exception($lang['Message']['Config_loading_url']);
		}

		$ctx = stream_context_create([
			'http' => [
				'timeout' => $connect_timeout,
				'user_agent' => $user_agent
			]
		]);

		if (!($fp = fopen($url, 'r', false, $ctx))) {
			throw new Exception($lang['Message']['Not_found_at_url']);
		}

		$meta = stream_get_meta_data($fp);
		$headers = $meta['wrapper_data'];

		foreach ($headers as $header) {
			if (preg_match('#^content-type:(.+)#i', $header, $m)) {
				$mime = $m[1];
				break;
			}
		}

		while (!feof($fp)) {
			$data .= fgets($fp);
		}

		fclose($fp);
	}

	if (strpos($mime, ';')) {
		$tmp = explode(';', $mime, 2);
		$mime = $tmp[0];

		if (preg_match('/\W*charset\s*=\s*(")?([a-z][a-z0-9._-]*)(?(1)")/i', $tmp[1], $m)) {
			$charset = strtoupper($m[2]);
		}
	}

	if (!$charset && preg_match('#(?:/|\+)xml$#', $mime) && strncmp($data, '<?xml', 5) == 0) {
		$pr = substr($data, 0, strpos($data, "\n"));

		if (preg_match('/\s+encoding\s*=\s*("|\')([a-z][a-z0-9._-]*)\\1/i', $pr, $m)) {
			$charset = $m[2];
		}
	}

	return ['data' => $data, 'mime' => $mime, 'charset' => $charset];
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
	$number = number_format($number, $decimals, $GLOBALS['lang']['DEC_POINT'], $GLOBALS['lang']['THOUSANDS_SEP']);
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
		$unit = $GLOBALS['lang']['GiB'];
		$size /= $g;
	}
	else if ($size >= $m) {
		$unit = $GLOBALS['lang']['MiB'];
		$size /= $m;
	}
	else if ($size >= $k) {
		$unit = $GLOBALS['lang']['KiB'];
		$size /= $k;
	}
	else {
		$unit = $GLOBALS['lang']['Bytes'];
	}

	return sprintf("%s\xC2\xA0%s", wa_number_format($size), $unit);
}

/**
 * Retourne le niveau de débogage.
 * Dépendant de la valeur de la constante DEBUG_MODE ainsi que de
 * la clé de configuration 'debug_level'.
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
 * Indique si le débogage est activé.
 *
 * @return boolean
 */
function wan_is_debug_enabled()
{
	return (wan_get_debug_level() > DEBUG_LEVEL_QUIET);
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
 * Initialisation du module d’envoi des mails.
 *
 * @param array   $opts  Tableau d’options à transmettre au transport
 * @param boolean $reset Forcer la création d’un nouvel objet transport
 *
 * @return \Wamailer\Transport\Transport
 */
function wamailer(array $opts = [], $reset = false)
{
	global $nl_config;
	static $transport;

	if (!$transport || $reset) {
		$name = 'mail';

		if ($nl_config['use_smtp']) {
			$name  = 'smtp';
			$proto = ($nl_config['smtp_tls'] == SECURITY_FULL_TLS) ? 'tls' : 'tcp';
			$opts  = array_replace_recursive([
				'server'   => sprintf('%s://%s:%d', $proto, $nl_config['smtp_host'], $nl_config['smtp_port']),
				'starttls' => ($nl_config['smtp_tls'] == SECURITY_STARTTLS),
				'auth' => [
					'username'  => $nl_config['smtp_user'],
					'secretkey' => $nl_config['smtp_pass']
				]
			], $opts);

#			$opts['debug'] = function ($str) {
#				wanlog(htmlspecialchars($str));
#			};
		}

		if (!empty($nl_config['mailer'])) {
			$opts = array_replace_recursive($nl_config['mailer'], $opts);
		}

		Mailer::$signature = sprintf(X_MAILER_HEADER, WANEWSLETTER_VERSION);
		$transport = Mailer::setTransport($name);
	}

	$transport->options($opts);

	return $transport;
}

/**
 * Retourne la liste des tags personnalisés (voir modèle tags.sample.inc.php)
 *
 * @return array
 */
function wan_get_tags()
{
	global $lang;
	static $other_tags;

	if (is_array($other_tags)) {
		return $other_tags;
	}

	$other_tags  = [];

	$tags_file[] = WA_ROOTDIR . '/data/tags.inc.php';
	// compatibilité Wanewsletter < 3.0-beta1
	$tags_file[] = WA_ROOTDIR . '/includes/tags.inc.php';

	$need_to_move = false;

	foreach ($tags_file as $tag_file) {
		if (file_exists($tag_file)) {
			if ($need_to_move) {
				wanlog(sprintf($lang['Message']['Move_to_data_dir'],
					str_replace(WA_ROOTDIR, '~', $tag_file)
				));
			}

			include $tag_file;
			break;
		}

		$need_to_move = true;
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
				case 'G':
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
        $filesize = '2M';
    }

	$upload_max_size = $literal2integer($filesize);

    if ($postsize = ini_get('post_max_size')) {
        $postsize = $literal2integer($postsize);
        $upload_max_size = min($upload_max_size, $postsize);
    }

    return $upload_max_size;
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
 * @param string $pseudo
 *
 * @return boolean
 */
function validate_pseudo($pseudo)
{
	$len = mb_strlen($pseudo);
	return ($len >= 2 && $len <= 30);
}

/**
 * @param string $passwd
 *
 * @return boolean
 */
function validate_pass($passwd)
{
	$len = mb_strlen($passwd);
	if ($len >= 6 && $len <= 1024) {
		return !preg_match('/[\x00-\x1F]|\xC2[\x80-\x9F]/', $passwd);
	}

	return false;
}

/**
 * @param string $language
 *
 * @return boolean
 */
function validate_lang($language)
{
	return (preg_match('/^[\w_]+$/', $language)
		&& file_exists(WA_ROOTDIR . '/languages/' . $language . '/main.php')
	);
}

/**
 * Envoi d’un fichier au client.
 * Sert les en-têtes nécessaires au téléchargement.
 *
 * @param string  $filename
 * @param string  $mime_type
 * @param mixed   $data      Soit directement les données à envoyer, soit une
 *                           ressource de flux.
 * @param boolean $download  true pour 'forcer' le téléchargement
 */
function sendfile($filename, $mime_type, $data, $download = true)
{
	if (is_resource($data)) {
		$meta = stream_get_meta_data($data);
		$size = filesize($meta['uri']);
	}
	else {
		$size = strlen($data);
	}

	//
	// Désactivation de la compression de sortie de php au cas où
	// et envoi des en-têtes appropriés au client.
	//
	@ini_set('zlib.output_compression', 'Off');
	header(sprintf('Content-Length: %d', $size));
	header('Content-Transfer-Encoding: binary');
	header(sprintf('Content-Type: %s', $mime_type));

	if (preg_match('#[\x80-\xFF]#', $filename)) {
		$filenameParam = sprintf("filename*=UTF-8''%s", rawurlencode($filename));
	}
	else {
		$filenameParam = sprintf("filename=\"%s\"", $filename);
	}

	$disposition = ($download) ? 'attachment' : 'inline';
	header(sprintf('Content-Disposition: %s; %s', $disposition, $filenameParam));

	// Repris de phpMyAdmin. Évite la double compression (par exemple avec apache + mod_deflate)
	if (strpos($mime_type, 'gzip') !== false) {
		header('Content-Encoding: gzip');
	}

	if (is_resource($data)) {
		while (!feof($data)) {
			echo fgets($data, 1048576);
		}

		fclose($data);
	}
	else {
		echo $data;
	}

	exit;
}

/**
 * Affichage des informations de débogage
 */
function print_debug_infos()
{
	global $lang, $db, $nl_config;

	$dir_status = function ($dir) use (&$lang) {
		if (file_exists($dir)) {
			if (!is_readable($dir)) {
				$str = sprintf('%s [%s]', $lang['No'], $lang['Unreadable']);
			}
			else if (!is_writable($dir)) {
				$str = sprintf('%s [%s]', $lang['No'], $lang['Unwritable']);
			}
			else {
				$str = $lang['Yes'];
			}
		}
		else {
			$str = sprintf('%s [%s]', $lang['No'], $lang['Not_exists']);
		}

		return $str;
	};

	$print_head = function ($str) {
		echo "<u><b>", htmlspecialchars($str), "</b></u>\n";
	};

	$print_row  = function ($name, $value = null) use (&$lang) {
		echo '  ';// 2x NBSP
		echo htmlspecialchars($name);
		$l = 34 - mb_strlen($name);
		$i = 0;
		while ($i < $l) {
			echo ' ';
			$i++;
		}

		if (!is_null($value)) {
			echo ' : ';

			if (is_bool($value)) {
				echo ($value) ? $lang['Yes'] : $lang['No'];
			}
			else if (is_int($value) || $value != '') {
				echo htmlspecialchars($value);
			}
			else {
				echo '<i class="novalue">no value</i>';
			}
		}

		echo "\n";
	};

	printf("<h2>%s</h2>\n", $lang['Title']['debug']);
	echo "<pre id='debug-infos'>";

	$print_head('Wanewsletter');
	$print_row('Version/db_version', WANEWSLETTER_VERSION.'/'.$nl_config['db_version']);
	$print_row('session_length',     $nl_config['session_length']);
	$print_row('language',           $nl_config['language']);
	$print_row('upload dir',         $dir_status(WA_ROOTDIR.'/'.$nl_config['upload_path']));

	if (!$nl_config['disable_stats']) {
		$print_row('stats dir', $dir_status($nl_config['stats_dir']));
	}
	$print_row('max_filesize',  $nl_config['max_filesize']);
	$print_row('engine_send',   $nl_config['engine_send']);
	$print_row('sending_limit', $nl_config['sending_limit']);
	$print_row('sending_delay', $nl_config['sending_delay']);
	$print_row('use_smtp',      (bool) $nl_config['use_smtp']);

	$print_head($lang['Third_party_libraries']);

	$composer_file = WA_ROOTDIR . '/composer.lock';
	if (!function_exists('json_decode')) {
		$print_row($lang['Message']['No_json_extension']);
	}
	else if (is_readable($composer_file)) {
		$composer = json_decode(file_get_contents($composer_file), true);

		foreach ($composer['packages'] as $package) {
			$ver = $package['version'];

			if (strpos($ver, 'dev-') === 0) {
				if (isset($package['dist'])) {
					$ref = $package['dist']['reference'];
				}
				else {
					$ref = $package['source']['reference'];
				}

				if (isset($package['extra']['branch-alias'][$ver])) {
					$ver = $package['extra']['branch-alias'][$ver];
				}

				$ver .= ' ('.$ref.')';
			}
			else {
				$ver = ltrim($ver, 'v');// eg: v2.3.4 => 2.3.4
			}

			$print_row($package['name'], $ver);
		}
	}
	else {
		$print_row($lang['Message']['Composer_lock_unreadable']);
	}

	$print_head('PHP');
	$print_row('Version/SAPI', sprintf('%s (%s)', PHP_VERSION, PHP_SAPI));
	$print_row('Extension Bz2', extension_loaded('zlib'));
	$print_row('Extension Curl', extension_loaded('curl'));

	if (extension_loaded('gd')) {
		$tmp = gd_info();
		$format = (imagetypes() & IMG_GIF) ? 'GIF' : 'Unavailable';
		$format = (imagetypes() & IMG_PNG) ? 'PNG' : $format;
		$str = sprintf('%s – %s/%s', $lang['Yes'], $tmp['GD Version'], $format);
	}
	else {
		$str = $lang['No'];
	}

	$print_row('Extension GD', $str);
	$print_row('Extension Iconv',
		extension_loaded('iconv')
			? sprintf('%s – %s/%s', $lang['Yes'], ICONV_VERSION, ICONV_IMPL)
			: $lang['No']
	);
	$print_row('Extension JSON', extension_loaded('json'));
	$print_row('Extension Mbstring', extension_loaded('mbstring'));
	$print_row('Extension OpenSSL',
		extension_loaded('openssl')
			? sprintf('%s – %s', $lang['Yes'], OPENSSL_VERSION_TEXT)
			: $lang['No']
	);
	$print_row('Extension SimpleXML', extension_loaded('simplexml'));
	$print_row('Extension XML', extension_loaded('xml'));
	$print_row('Extension Zip', extension_loaded('zip'));
	$print_row('Extension Zlib', extension_loaded('zlib'));

	$print_row('open_basedir',  ini_get('open_basedir'));
	$print_row('sys_temp_dir', sys_get_temp_dir());
	$print_row('filter.default', ini_get('filter.default'));
	$print_row('allow_url_fopen', ini_get_flag('allow_url_fopen'));
	$print_row('allow_url_include', ini_get_flag('allow_url_include'));
	$print_row('file_uploads', ini_get_flag('file_uploads'));
	$print_row('upload_tmp_dir', ini_get('upload_tmp_dir'));
	$print_row('upload_max_filesize', ini_get('upload_max_filesize'));
	$print_row('post_max_size', ini_get('post_max_size'));
	$print_row('max_input_time', ini_get('max_input_time'));
	$print_row('max_input_vars', ini_get('max_input_vars'));
	$print_row('memory_limit', ini_get('memory_limit'));
	$print_row('mail.add_x_header', ini_get_flag('mail.add_x_header'));
	$print_row('mail.force_extra_parameters', ini_get('mail.force_extra_parameters'));
	$print_row('sendmail_path', ini_get('sendmail_path'));

	if (strncasecmp(PHP_OS, 'Win', 3) === 0) {
		$print_row('sendmail_from', ini_get('sendmail_from'));
		$print_row('SMTP Server', ini_get('SMTP').':'.ini_get('smtp_port'));
	}

	$print_head($lang['Database']);

	$print_row('Type/Version', sprintf('%s %s',
		$db->infos['label'],
		($db::ENGINE == 'sqlite') ? $db->libVersion : $db->serverVersion
	));

	if ($db::ENGINE != 'sqlite') {
		$print_row($lang['Client_library'], $db->clientVersion);
		$print_row($lang['Charset'], $db->encoding());
	}

	$print_row($lang['Driver'], $db->infos['driver']);

	if (isset($_SERVER['SERVER_SOFTWARE'])) {
		$print_head($lang['Misc']);

		$user_agent = filter_input(INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_SPECIAL_CHARS);

		$print_row($lang['User_agent'],      $user_agent);
		$print_row($lang['Server_software'], sprintf('%s – %s – %s',
			$_SERVER['SERVER_SOFTWARE'],
			$_SERVER['SERVER_PROTOCOL'],
			PHP_OS
		));
		$print_row($lang['Secure_connection'], is_secure_connection());
	}

	echo "</pre>";
}

/**
 * Validation des actions faites par email
 *
 * @param array $listdata
 *
 * @return string
 */
function process_mail_action(array $listdata)
{
	global $lang;

	$sub = new Subscription();
	$pop = new PopClient();
	$pop->options([
		'starttls' => ($listdata['pop_tls'] == SECURITY_STARTTLS)
	]);

	try {
		$proto = ($listdata['pop_tls'] == SECURITY_FULL_TLS) ? 'tls' : 'tcp';
		$server = sprintf('%s://%s:%d', $proto, $listdata['pop_host'], $listdata['pop_port']);

		if (!$pop->connect($server, $listdata['pop_user'], $listdata['pop_pass'])) {
			throw new Exception(sprintf("Failed to connect to POP server (%s)", $pop->responseData));
		}
	}
	catch (Exception $e) {
		trigger_error(sprintf($lang['Message']['bad_pop_param'], $e->getMessage()), E_USER_ERROR);
	}

	$mail_box = $pop->list();

	foreach ($mail_box as $mail_id => $mail_size) {
		$mail = $pop->read($mail_id);
		$headers = parse_headers($mail['headers']);

		if (!isset($headers['from'])
			|| !preg_match('/^(?:"?([^"]*?)"?)?[ ]*(?:<)?([^> ]+)(?:>)?$/i', $headers['from'], $m)
		) {
			continue;
		}

		$pseudo = (isset($m[1])) ? strip_tags(utf8_normalize(trim($m[1]))) : '';
		$email  = utf8_normalize(trim($m[2]));

		if (!isset($headers['to']) || !stristr($headers['to'], $sub->liste_email)) {
			continue;
		}

		if (!isset($headers['subject'])) {
			continue;
		}

		$action = mb_strtolower(utf8_normalize(trim($headers['subject'])));

		switch ($action) {
			case 'désinscription':
			case 'unsubscribe':
				$action = 'desinscription';
				break;
			case 'subscribe':
				$action = 'inscription';
				break;
			case 'confirmation':
			case 'setformat':
				break;
		}

		$code = trim($mail['message']);

		try {
			if ($action == 'inscription') {
				$sub->subscribe($listdata, $email, $pseudo);
			}
			else if ($action == 'desinscription') {
				$sub->unsubscribe($listdata, $email);
			}
			else if ($action == 'setformat') {
				$sub->setFormat($listdata, $email);
			}
			else if ($action == 'confirmation') {
				if (empty($headers['date']) || !($time = strtotime($headers['date']))) {
					$time = time();
				}

				$sub->checkCode($code, $time);
			}
		}
		catch (Dblayer\Exception $e) {
			throw $e;
		}
		catch (Exception $e) { }

		//
		// On supprime l’email maintenant devenu inutile
		//
		$pop->delete($mail_id);
	}//end for

	$pop->quit();

	return $lang['Message']['Success_operation'];
}

/**
 * Parse les en-têtes et retourne un tableau avec le nom des
 * en-têtes et leur valeur
 *
 * @param string $headers_str
 *
 * @return array
 *
 * @status experimental
 *         /!\ TODO – Cette fonction a vocation à migrer à terme dans Wamailer
 */
function parse_headers($headers_str)
{
	$headers_str = trim($headers_str);
	// On normalise les fins de ligne.
	$headers_str = preg_replace("/(\r\n?)|\n/", "\r\n", $headers_str);
	// On supprime les 'pliures' (FWS) des en-têtes.
	$headers_str = preg_replace("/\r\n[ \t]+/", ' ', $headers_str);

	$headers = [];
	$lines   = explode("\r\n", $headers_str);

	foreach ($lines as $line) {
		list($header_name, $header_value) = explode(': ', $line, 2);

		$atoms = explode(' ', $header_value);

		$header_value = $prev_atom_type = '';

		foreach ($atoms as &$atom) {
			if (preg_match('/=\?([^?]+)\?(Q|B)\?([^?]+)\?\=/i', $atom, $m)) {
				$atom_type = 'encoded-word';

				if ($m[2] == 'Q' || $m[2] == 'q') {
					$atom = preg_replace_callback('/=([A-F0-9]{2})/i',
						function ($m2) { return chr(hexdec($m2[1])); },
						$m[3]
					);
					$atom = str_replace('_', ' ', $atom);
				}
				else {
					$atom = base64_decode($m[3]);
				}
			}
			else {
				$atom_type = 'text';
			}

			/**
			 * Les espaces blancs (LWS) entre deux 'encoded-word' doivent
			 * être ignorés lors du décodage de ceux-ci.
			 * @see RFC 2047#6.2 – Display of 'encoded-word's
			 */
			if ($atom_type == 'text' || $prev_atom_type == 'text') {
				$header_value .= ' ';
			}

			$header_value .= $atom;
			$prev_atom_type = $atom_type;
		}

		$headers[strtolower($header_name)] = trim($header_value);
	}

	return $headers;
}

/**
 * Construction de la liste déroulante des langues disponibles pour le script
 *
 * @param string $default_lang Langue actuellement utilisée
 *
 * @return string
 */
function lang_box($default_lang = '')
{
	global $output;

	$lang_names = [
		'fr' => 'francais',
		'en' => 'english'
	];

	$lang_list = [];
	$browse    = dir(WA_ROOTDIR . '/languages');

	while (($entry = $browse->read()) !== false) {
		if (is_dir(WA_ROOTDIR . '/languages/' . $entry) && preg_match('/^\w+(_\w+)?$/', $entry, $m)) {
			$lang_list[] = $m[0];
		}
	}
	$browse->close();

	if (count($lang_list) > 1) {
		$lang_box = '<select id="language" name="language">';
		foreach ($lang_list as $lang) {
			$selected  = $output->getBoolAttr('selected', ($default_lang == $lang));
			$lang_box .= sprintf('<option value="%1$s"%2$s>%3$s</option>',
				$lang,
				$selected,
				(isset($lang_names[$lang])) ? $lang_names[$lang] : $lang
			);
		}
		$lang_box .= '</select>';
	}
	else {
		$lang = array_pop($lang_list);
		$lang_box  = $lang_names[$lang];
		$lang_box .= '<input type="hidden" id="language" name="language" value="' . $lang . '" />';
	}

	return $lang_box;
}

/**
 * Construction de la liste déroulante des formats de newsletter
 *
 * @param string  $name     Nom de la liste déroulante
 * @param integer $default  Format par défaut
 * @param boolean $multiple True si on doit affiche également multi-format comme valeur
 *
 * @return string
 */
function format_box($name, $default = 0, $multiple = false)
{
	global $lang, $output;

	$get_option = function ($format, $label) use ($output, $default) {
		return sprintf('<option value="%d"%s>%s</option>',
			$format,
			$output->getBoolAttr('selected', ($default == $format)),
			$label
		);
	};

	$id_attr = '';
	if (preg_match('/^[a-z]([a-z0-9_-]*[a-z0-9])?$/', $name)) {
		$id_attr = sprintf(' id="%s"', $name);
	}

	$format_box  = sprintf('<select%s name="%s">', $id_attr, $name);
	$format_box .= $get_option(FORMAT_TEXT, $lang['Text']);
	$format_box .= $get_option(FORMAT_HTML, 'html');

	if ($multiple) {
		$format_box .= $get_option(FORMAT_MULTIPLE, sprintf('%s/html', $lang['Text']));
	}

	$format_box .= '</select>';

	return $format_box;
}

/**
 * Émission d’un cookie.
 *
 * @param string  $name
 * @param string  $value
 * @param integer $lifetime
 * @param array   $opts
 *
 * @return boolean
 */
function send_cookie($name, $value, $lifetime, array $opts = [])
{
	$opts = array_replace([
		'path'     => str_replace('//', '/', dirname($_SERVER['REQUEST_URI']).'/'),
		'domain'   => null,
		'secure'   => is_secure_connection(),
		'httponly' => true
	], $opts);

	return setcookie(
		$name,
		$value,
		$lifetime,
		$opts['path'],
		$opts['domain'],
		$opts['secure'],
		$opts['httponly']
	);
}

/**
 * Retourne la liste des fichiers joints à cette lettre.
 *
 * @param array $logdata
 *
 * @return array
 */
function get_joined_files(array $logdata)
{
	global $db;

	$sql = "SELECT jf.file_id, jf.file_real_name, jf.file_physical_name, jf.file_size, jf.file_mimetype
		FROM %s AS jf
			INNER JOIN %s AS lf ON lf.file_id = jf.file_id
			INNER JOIN %s AS l ON l.log_id = lf.log_id
				AND l.liste_id = %d
				AND l.log_id   = %d
		ORDER BY jf.file_real_name ASC";
	$sql = sprintf($sql, JOINED_FILES_TABLE, LOG_FILES_TABLE, LOG_TABLE,
		$logdata['liste_id'],
		$logdata['log_id']
	);
	$result = $db->query($sql);

	return $result->fetchAll($result::FETCH_ASSOC);
}

/**
 * Retourne l'adresse URL du formulaire d’inscription/désinscription
 *
 * @param array $listdata
 *
 * @return string
 */
function get_form_url(array $listdata)
{
	global $nl_config;

	$form_url = $listdata['form_url'];

	if (!$form_url) {
		$form_url  = $nl_config['urlsite'];
		$form_url .= $nl_config['path'];
		$form_url .= 'subscribe.php';
	}

	return $form_url;
}

/**
 * Normalise le codage des chaînes UTF-8 au format NFC.
 *
 * @param string $str
 *
 * @return string
 */
function utf8_normalize($str)
{
	if (preg_match('/[\x80-\xFF]/', $str)) {
		$str = (string)\Normalizer::normalize($str, \Normalizer::FORM_C);
	}

	return $str;
}
