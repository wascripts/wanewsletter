<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if( !defined('FUNCTIONS_INC') ) {

define('FUNCTIONS_INC', true);

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
 * Retourne la configuration du script stockée dans la base de données
 *
 * @return array
 */
function wa_get_config()
{
	global $db;
	
	$result = $db->query("SELECT * FROM " . CONFIG_TABLE);
	$row    = $result->fetch($result->SQL_FETCH_ASSOC);
	$config = array();
	
	if( isset($row['config_name']) ) {// Wanewsletter 2.4-beta2+
		do {
			if( $row['config_name'] != null ) {
				$config[$row['config_name']] = $row['config_value'];
			}
		}
		while( $row = $result->fetch() );
	}
	else {
		trigger_error("La table de configuration du script est obsolète. Mise à jour requise", E_USER_WARNING);
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
	
	if( is_string($config) ) {
		$config = array($config => $value);
	}
	
	foreach( $config as $name => $value ) {
		$db->query(sprintf(
			"UPDATE %s SET config_value = '%s' WHERE config_name = '%s'",
			CONFIG_TABLE,
			$db->escape($value),
			$db->escape($name)
		));
	}
}

/**
 * generate_key()
 * 
 * Génération d'une chaîne aléatoire
 * 
 * @param integer $length       Nombre de caractères
 * @param integer $specialChars Ajout de caractères spéciaux
 * 
 * @return string
 */
function generate_key($length = 32, $specialChars = false)
{
	static $charList = null;
	
	if( $charList == null ) {
		$charList = range('0', '9');
		$charList = array_merge($charList, range('A', 'Z'));
		$charList = array_merge($charList, range('a', 'z'));
		
		if( $specialChars ) {
			$charList = array_merge($charList, range(' ', '/'));
			$charList = array_merge($charList, range(':', '@'));
			$charList = array_merge($charList, range('[', '`'));
			$charList = array_merge($charList, range('{', '~'));
		}
		
		shuffle($charList);
	}
	
	$key = '';
	for( $i = 0, $m = count($charList)-1; $i < $length; $i++ ) {
		$key .= $charList[mt_rand(0, $m)];
	}
	
	return $key;
}

/**
 * wan_build_url()
 * 
 * Construction d'une url
 * 
 * @param string  $url      Url à compléter
 * @param array   $params   Paramètres à ajouter en fin d'url
 * @param boolean $session  Ajout de l'ID de session PHP s'il y a lieu
 * 
 * @return string
 */
function wan_build_url($url, $params = array(), $session = false)
{
	$parts = parse_url($url);
	
	if( !is_array($params) ) {
		$params = array();
	}
	
	if( empty($parts['scheme']) ) {
		$proto = 'http';
		if( !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ) {
			$proto = 'https';
		}
	}
	else {
		$proto = $parts['scheme'];
	}
	
	$server = !empty($parts['host']) ? $parts['host'] : $_SERVER['HTTP_HOST'];
	
	if( !empty($parts['port']) ) {
		$server .= ':'.$parts['port'];
	}
	else if( $_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443 ) {
		$server .= ':'.$_SERVER['SERVER_PORT'];
	}
	
	$path  = !empty($parts['path']) ? $parts['path'] : '/';
	$query = !empty($parts['query']) ? $parts['query'] : '';
	
	if( $path[0] != '/' ) {
		$parts = explode('/', dirname($_SERVER['SCRIPT_NAME']).'/'.$path);
		$path  = array();
		
		foreach( $parts as $part ) {
			if( $part == '.' || $part == '' ) {
				continue;
			}
			if( $part == '..' ) {
				array_pop($path);
			}
			else {
				$path[] = $part;
			}
		}
		$path = implode('/', $path);
	}
	
	$cur_params = array();
	if( $query != '' ) {
		parse_str($query, $cur_params);
	}
	
	if( defined('SID') && SID != '' ) {
		list($name, $value) = explode('=', SID);
		$params[$name] = $value;
	}
	
	$params = array_merge($cur_params, $params);
	$query  = http_build_query($params);
	
	$url = $proto . '://' . $server . '/' . ltrim($path, '/') . ($query != '' ? '?' . $query : '');
	
	return $url;
}

/**
 * http_redirect()
 *
 * Version adaptée de la fonction http_redirect() de pecl_http
 *
 * @param string  $url      Url de redirection
 * @param array   $params   Paramètres à ajouter en fin d'url
 * @param boolean $session  Ajout de l'ID de session PHP s'il y a lieu
 * @param integer $status   Code de redirection HTTP
 */
if( !function_exists('http_redirect') ) {
function http_redirect($url, $params = array(), $session = false, $status = 0)
{
	switch( $status ) {
		case 301: $statusText = 'Moved Permanently'; break;
		case 303: $statusText = 'See Other'; break;
		case 302:
		default:
			$statusText = 'Moved Temporarily';
			$status = 302;
			break;
	}
	
	$url = wan_build_url($url, $params, $session);
	header($statusText, true, $status);
	header(sprintf('Location: %s', $url));
	
	//
	// Si la fonction header() ne donne rien, on affiche une page de redirection 
	//
	printf('<p>If your browser doesn\'t support meta redirect, click
		<a href="%s">here</a> to go on next page.</p>', wan_htmlspecialchars($url));
	exit;
}
}

/**
 * load_settings()
 * 
 * Initialisation des préférences et du moteur de templates
 * 
 * @param array $admindata    Données utilisateur
 * 
 * @return void
 */
function load_settings($admindata = array())
{
	global $nl_config, $lang, $datetime;
	
	$check_list = array();
	$supported_lang = array(
		'fr' => 'francais',
		'en' => 'english'
	);
	$file_pattern = WA_ROOTDIR . '/language/lang_%s.php';
	
	$check_list[] = 'francais';
	
	if( server_info('HTTP_ACCEPT_LANGUAGE') != '' )
	{
		$accepted_langs = array_map('trim', explode(',', server_info('HTTP_ACCEPT_LANGUAGE')));
		
		foreach( $accepted_langs as $langcode )
		{
			$langcode = strtolower(substr($langcode, 0, 2));
			
			if( isset($supported_lang[$langcode]) )
			{
				$check_list[] = $supported_lang[$langcode];
				break;
			}
		}
	}
	
	if( !empty($nl_config['language']) )
	{
		$check_list[] = $nl_config['language'];
	}
	
	if( !is_array($admindata) )
	{
		$admindata = array();
	}
	
	if( !empty($admindata['admin_lang']) )
	{
		$check_list[] = $admindata['admin_lang'];
	}
	
	if( !empty($admindata['admin_dateformat']) )
	{
		$nl_config['date_format'] = $admindata['admin_dateformat'];
	}
	
	$check_list = array_unique(array_reverse($check_list));
	
	foreach( $check_list as $language )
	{
		if( @is_readable(sprintf($file_pattern, $language)) )
		{
			if( empty($lang) || $supported_lang[$lang['CONTENT_LANG']] != $language )
			{
				require sprintf($file_pattern, $language);
			}
			
			break;
		}
	}
	
	if( empty($lang) )
	{
		trigger_error('<b>Les fichiers de localisation sont introuvables !</b>', E_USER_ERROR);
	}
}

/**
 * wan_error_handler()
 * 
 * Gestionnaire d'erreur personnalisé du script (en sortie http)
 * 
 * @param integer $errno      Code de l'erreur
 * @param string  $errstr     Texte proprement dit de l'erreur
 * @param string  $errfile    Fichier où s'est produit l'erreur
 * @param integer $errline    Numéro de la ligne
 *
 * @return boolean
 */
function wan_error_handler($errno, $errstr, $errfile, $errline)
{
	$simple = (defined('IN_COMMANDLINE') || defined('IN_SUBSCRIBE') || defined('IN_WA_FORM') || defined('IN_CRON'));
	$fatal  = ($errno == E_USER_ERROR || $errno == E_RECOVERABLE_ERROR);
	
	//
	// On affiche pas les erreurs non prises en compte dans le réglage du
	// error_reporting si error_reporting vaut 0, sauf si DEBUG_MODE est au max
	//
	if( !$fatal && (DEBUG_MODE == DEBUG_LEVEL_QUIET
		|| (DEBUG_MODE == DEBUG_LEVEL_NORMAL && !(error_reporting() & $errno))) )
	{
		return true;
	}
	
	$error = new WanError(array(
		'type'    => $errno,
		'message' => $errstr,
		'file'    => $errfile,
		'line'    => $errline
	));
	
	if( $simple || $fatal ) {
		wan_display_error($error, $simple);
		
		if( $fatal ) {
			exit(1);
		}
	}
	else {
		wanlog($error);
		
		if( !DISPLAY_ERRORS_IN_LOG ) {
			wan_display_error($error, true);
		}
	}
	
	return true;
}

/**
 * wan_exception_handler()
 * 
 * Gestionnaire d'erreur personnalisé du script (en sortie http)
 * 
 * @param Exception $e  Exception "attrapée" par le gestionnaire
 */
function wan_exception_handler($e)
{
	wan_display_error($e);
	exit(1);
}

/**
 * wan_format_error()
 *
 * Formatage du message d'erreurs
 *
 * @param Exception  $error  Exception décrivant l'erreur
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
	
	if( $error instanceof WanError ) {
		// Cas spécial. L'exception personnalisée a été créé dans wan_error_handler()
		// et contient donc l'appel à wan_error_handler() elle-même. On corrige.
		array_shift($backtrace);
	}
	
	foreach( $backtrace as $i => &$t ) {
		$file = wan_htmlspecialchars(str_replace(dirname(dirname(__FILE__)), '~', $t['file']));
		$call = (isset($t['class']) ? $t['class'].$t['type'] : '') . $t['function'];
		$t = sprintf('#%d  %s() called at [%s:%d]', $i, $call, $file, $t['line']);
	}
	
	if( count($backtrace) > 0 ) {
		$backtrace = sprintf("<b>Backtrace:</b>\n%s\n", implode("\n", $backtrace));
	}
	else {
		$backtrace = '';
	}
	
	if( DEBUG_MODE == DEBUG_LEVEL_QUIET ) {
		// Si on est en mode de non-débogage, on a forcément attrapé une erreur
		// critique pour arriver ici.
		$message  = $lang['Message']['Critical_error'];
	}
	else if( $error instanceof SQLException ) {
		$message  = "<b>SQL errno:</b> $errno\n";
		$message .= sprintf("<b>SQL error:</b> %s\n", wan_htmlspecialchars($errstr));
		
		if( is_object($db) ) {
			$message .= sprintf("<b>SQL query:</b> %s\n", wan_htmlspecialchars($db->lastQuery));
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
		
		$label = (isset($labels[$errno])) ? $labels[$errno] : 'Unknown Error';
		$errfile = str_replace(dirname(dirname(__FILE__)), '~', $errfile);
		
		if( !empty($lang['Message']) && !empty($lang['Message'][$errstr]) ) {
			$errstr = $lang['Message'][$errstr];
		}
		
		$message = sprintf(
			"<b>%s:</b> %s in <b>%s</b> on line <b>%d</b>\n",
			($error instanceof WanError) ? $label : get_class($error),
			wan_htmlspecialchars($errstr),
			wan_htmlspecialchars($errfile),
			$errline
		);
		$message .= $backtrace;
	}
	
	return $message;
}

/**
 * wan_display_error()
 *
 * Affichage du message dans le contexte d'utilisation (page web ou ligne de commande)
 *
 * @param Exception  $error       Exception décrivant l'erreur
 * @param boolean    $simpleHTML  Si true, affichage simple dans un paragraphe
 */
function wan_display_error($error, $simpleHTML = false)
{
	global $output;
	
	$message = wan_format_error($error);
	
	if( defined('IN_COMMANDLINE') ) {
		if( defined('ANSI_TERMINAL') ) {
			$message = preg_replace("#<b>#",  "\033[1;31m", $message, 1);
			$message = preg_replace("#</b>#", "\033[0m", $message, 1);
			
			$message = preg_replace("#<b>#",  "\033[1;37m", $message);
			$message = preg_replace("#</b>#", "\033[0m", $message);
		}
		
		$message = htmlspecialchars_decode($message);
		
		// Au cas où le terminal utilise l'encodage utf-8
		if( preg_match('/\.UTF-?8/i', getenv('LANG')) ) {
			$message = wan_utf8_encode($message);
		}
		
		fputs(STDERR, $message);
	}
	else if( $simpleHTML ) {
		echo '<p>' . nl2br($message) . '</p>';
	}
	else if( $output instanceof output ) {
		$output->displayMessage($message);
	}
	else {
		$message = nl2br($message);
		echo <<<BASIC
<!DOCTYPE html>
<html dir="ltr">
<head>
	<title>Erreur critique&nbsp;!</title>
	
	<style>
	body { margin: 10px; text-align: left; }
	</style>
</head>
<body>
	<div>
		<h1>Erreur critique&nbsp;!</h1>
		
		<p>$message</p>
	</div>
</body>
</html>
BASIC;
	}
}

/**
 * wanlog()
 *
 * Si elle est appelée avec un argument, ajoute l'entrée dans le journal,
 * sinon, renvoie le journal.
 * 
 * @param mixed  $entry  Peut être un objet Exception, ou une simple chaîne
 * 
 * @return array
 */
function wanlog($entry = null)
{
	static $entries = array();
	
	if( $entry === null ) {
		return $entries;
	}
	
	array_push($entries, $entry);
}

/**
 * wan_error_get_last()
 *
 * Même fonctionnement que la fonction native error_get_last()
 *
 * @param mixed  $entry  Peut être un objet Exception, ou une simple chaîne
 *
 * @return array
 */
function wan_error_get_last()
{
	$errors = wanlog();
	$error  = null;
	
	while( $e = array_pop($errors) ) {
		if( $e instanceof Exception ) {
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
 * plain_error()
 * 
 * @param mixed   $var      Variable à afficher
 * @param boolean $exit     True pour terminer l'exécution du script
 * @param boolean $verbose  True pour utiliser var_dump() (détails sur le contenu de la variable)
 * 
 * @return void
 */
function plain_error($var, $exit = true, $verbose = false)
{
	if( headers_sent() == false ) {
		header('Content-Type: text/plain; charset=ISO-8859-15');
	}
	
	if( $verbose == true ) {
		var_dump($var);
	} else {
		if( is_scalar($var) ) {
			echo $var;
		} else {
			print_r($var);
		}
	}
	
	if( $exit ) {
		exit;
	}
}

/**
 * navigation()
 * 
 * Fonction d'affichage par page.
 * 
 * @param string  $url              Adresse vers laquelle doivent pointer les liens de navigation
 * @param integer $total_item       Nombre total d'éléments
 * @param integer $item_per_page    Nombre d'éléments par page
 * @param integer $page_id          Identifiant de la page en cours
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
	
	$url .= ( strpos($url, '?') ) ? '&amp;' : '?';
	
	// suppression de l'espace précédemment ajouté 
	$url = substr($url, 1);
	
	if( $total_pages == 1 )
	{
		return '&nbsp;';
	}
	
	$nav_string = '';
	
	if( $total_pages > 10 )
	{
		if( $page_id > 10 )
		{
			$prev = $page_id;
			do
			{
				$prev--;
			}
			while( $prev % 10 );
			
			$nav_string .= '<a href="' . $url . 'page=1">' . $lang['Start'] . '</a>&nbsp;&nbsp;';
			$nav_string .= '<a href="' . $url . 'page=' . $prev . '">' . $lang['Prev'] . '</a>&nbsp;&nbsp;';
		}
		
		$current = $page_id;
		do
		{
			$current--;
		}
		while( $current % 10 );
		
		$current++;
		
		for( $i = $current; $i < ($current + 10); $i++ )
		{
			if( $i <= $total_pages )
			{
				if( $i > $current )
				{
					$nav_string .= ', ';
				}
				
				$nav_string .= ( $i == $page_id ) ? '<b>' . $i . '</b>' : '<a href="' . $url . 'page=' . $i . '">' . $i . '</a>';
			}
		}
		
		$next = $page_id;
		while( $next % 10 )
		{
			$next++;
		}
		$next++;
		
		if( $total_pages >= $next )
		{
			$nav_string .= '&nbsp;&nbsp;<a href="' . $url . 'page=' . $next . '">' . $lang['Next'] . '</a>';
			$nav_string .= '&nbsp;&nbsp;<a href="' . $url . 'page=' . $total_pages . '">' . $lang['End'] . '</a>';
		}
	}
	else
	{
		for( $i = 1; $i <= $total_pages; $i++ )
		{
			if( $i > 1 )
			{
				$nav_string .= ', ';
			}
			
			$nav_string .= ( $i == $page_id ) ? '<b>' . $i . '</b>' : '<a href="' . $url . 'page=' . $i . '">' . $i . '</a>';
			
		}
	}
	
	return $nav_string;
}

/**
 * convert_time()
 * 
 * Fonction de renvoi de date selon la langue
 * 
 * @param string  $dateformat    Format demandé
 * @param integer $timestamp     Timestamp unix à convertir
 * 
 * @return string
 */
function convert_time($dateformat, $timestamp)
{
	static $search, $replace;
	
	if( !isset($search) || !isset($replace) )
	{
		global $datetime;
		
		$search = $replace = array();
		
		foreach( $datetime as $orig_word => $repl_word )
		{
			array_push($search,  '/\b' . $orig_word . '\b/i');
			array_push($replace, $repl_word);
		}
	}
	
	return preg_replace($search, $replace, date($dateformat, $timestamp));
}

/**
 * purge_liste()
 * 
 * Fonction de purge de la table des abonnés 
 * Retourne le nombre d'entrées supprimées
 * Fonction récursive
 * 
 * @param integer $liste_id          Liste concernée
 * @param integer $limitevalidate    Limite de validité pour confirmer une inscription
 * @param integer $purge_freq        Fréquence des purges
 * 
 * @return integer
 */
function purge_liste($liste_id = 0, $limitevalidate = 0, $purge_freq = 0)
{
	global $db, $nl_config;
	
	if( !$liste_id )
	{
		$total_entries_deleted = 0;
		
		$sql = "SELECT liste_id, limitevalidate, purge_freq 
			FROM " . LISTE_TABLE . " 
			WHERE purge_next < " . time() . " 
				AND auto_purge = " . TRUE;
		$result = $db->query($sql);
		
		while( $row = $result->fetch() )
		{
			$total_entries_deleted += purge_liste($row['liste_id'], $row['limitevalidate'], $row['purge_freq']);
		}
		
		//
		// Optimisation des tables
		//
		$db->vacuum(array(ABONNES_TABLE, ABO_LISTE_TABLE));
		
		return $total_entries_deleted;
	}
	else
	{
		$sql = "SELECT abo_id
			FROM " . ABO_LISTE_TABLE . "
			WHERE liste_id = $liste_id
				AND confirmed = " . SUBSCRIBE_NOT_CONFIRMED . "
				AND register_date < " . (time() - ($limitevalidate * 86400));
		$result = $db->query($sql);
		
		$abo_ids = array();
		while( $abo_id = $result->column('abo_id') )
		{
			array_push($abo_ids, $abo_id);
		}
		$result->free();
		
		if( ($num_abo_deleted = count($abo_ids)) > 0 )
		{
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
			SET purge_next = " . (time() + ($purge_freq * 86400)) . " 
			WHERE liste_id = " . $liste_id;
		$db->query($sql);
		
		return $num_abo_deleted;
	}
}

/**
 * strip_magic_quotes_gpc()
 * 
 * Annule l'effet produit par l'option de configuration magic_quotes_gpc à On
 * Fonction récursive
 * 
 * @param array $data    Tableau des données
 * 
 * @return array
 */
function strip_magic_quotes_gpc(&$data, $isFilesArray = false)
{
	if( is_array($data) )
	{
		foreach( $data as $key => $val )
		{
			if( is_array($val) )
			{
				$data[$key] = strip_magic_quotes_gpc($val, $isFilesArray);
			}
			else if( is_string($val) && (!$isFilesArray || $key != 'tmp_name') )
			{
				$data[$key] = stripslashes($val);
			}
		}
	}
	
	return $data;
}

/**
 * wa_realpath()
 * 
 * @param string $relative_path  Chemin relatif à résoudre
 * 
 * @return string
 */
function wa_realpath($relative_path)
{
	if( !function_exists('realpath') || !($absolute_path = @realpath($relative_path)) )
	{
		return $relative_path;
	}
	
	return str_replace('\\', '/', $absolute_path);
}

/**
 * cut_str()
 * 
 * Pour limiter la longueur d'une chaine de caractère à afficher
 * 
 * @param string  $str
 * @param integer $len
 * 
 * @return string
 */
function cut_str($str, $len)
{
	if( strlen($str) > $len )
	{ 
		$str = substr($str, 0, ($len - 3));
		
		if( $space = strrpos($str, ' ') )
		{
			$str = substr($str, 0, $space);
		}
		
		$str .= '...';
	}
	
	return $str;
}

/**
 * active_urls()
 * 
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
 * config_status()
 * 
 * Retourne la valeur d'une directive de configuration
 * 
 * @param string  $name          Nom de la directive
 * 
 * @return boolean
 */
function config_status($name)
{
	return config_value($name, true);
}

/**
 * config_value()
 * 
 * Retourne la valeur d'une directive de configuration
 * 
 * @param string  $name          Nom de la directive
 * @param boolean $need_boolean  Nécessaire pour obtenir un booléen en retour (pour les directives on/off)
 * 
 * @return mixed
 */
function config_value($name, $need_boolean = false)
{
	$value = ini_get($name);
	if( $need_boolean ) {
		if( preg_match('#^off|false$#i', $value) ) {
			$value = false;
		}
		
		settype($value, 'boolean');
	}
	return $value;
}

/**
 * server_info()
 * 
 * Retourne l'information serveur demandée
 * 
 * @param string $name    Nom de l'information
 * 
 * @return string
 */
function server_info($name)
{
	$name = strtoupper($name);
	
	return ( !empty($_SERVER[$name]) ) ? $_SERVER[$name] : ( ( !empty($_ENV[$name]) ) ? $_ENV[$name] : '' );
}

/**
 * fake_header()
 * 
 * Fonctions à utiliser lors des longues boucles (backup, envois) 
 * qui peuvent provoquer un time out du navigateur client 
 * Inspiré d'un code équivalent dans phpMyAdmin 2.5.0 (libraries/build_dump.lib.php précisément)
 * 
 * @param boolean $in_loop    True si on est dans la boucle, false pour initialiser $time
 * 
 * @return void
 */
function fake_header($in_loop)
{
	static $time;
	
	if( $in_loop )
	{
		$new_time = time();
		
		if( ($new_time - $time) >= 30 )
		{
			$time = $new_time;
			header('X-WaPing: Pong');
		}
	}
	else
	{
		$time = time();
	}
}

/**
 * purge_latin1()
 * 
 * Effectue une translitération sur les caractères interdits provenant de Windows-1252
 * ou les transforme en références d'entité numérique selon que la chaîne est du texte brut ou du HTML
 * 
 * @param string $data       Chaîne à modifier
 * @param string $translite  Active ou non la translitération
 * 
 * @return string
 */
function purge_latin1($data, $translite = false)
{
	global $lang;
	
	if( $lang['CHARSET'] == 'ISO-8859-1' )
	{
		$convmap_name = ( $translite == true ) ? 'translite_cp1252' : 'cp1252_to_entity';
		
		return strtr($data, $GLOBALS['CONVMAP'][$convmap_name]);
	}
	
	return $data;
}

/**
 * is_utf8()
 * 
 * Détecte si une chaîne est encodée ou non en UTF-8
 * 
 * @param string $string    Chaîne à modifier
 * 
 * @link   http://w3.org/International/questions/qa-forms-utf-8.html
 * @see    http://bugs.php.net/bug.php?id=37793 (segfault qui oblige à tronçonner la chaîne)
 * @return boolean
 */
function is_utf8($string)
{
	return !strlen(
		preg_replace(
		'/[\x09\x0A\x0D\x20-\x7E]'              # ASCII
		. '|[\xC2-\xDF][\x80-\xBF]'             # non-overlong 2-byte
		. '|\xE0[\xA0-\xBF][\x80-\xBF]'         # excluding overlongs
		. '|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}'  # straight 3-byte
		. '|\xED[\x80-\x9F][\x80-\xBF]'         # excluding surrogates
		. '|\xF0[\x90-\xBF][\x80-\xBF]{2}'      # planes 1-3
		. '|[\xF1-\xF3][\x80-\xBF]{3}'          # planes 4-15
		. '|\xF4[\x80-\x8F][\x80-\xBF]{2}'      # plane 16
		. '/sS', '', $string));
}

/**
 * wan_utf8_encode()
 * 
 * Encode une chaîne en UTF-8
 * 
 * @param string $data
 * 
 * @return string
 */
function wan_utf8_encode($data)
{
	$data = strtr($data, $GLOBALS['CONVMAP']['cp1252_to_entity']);
	$data = utf8_encode($data);
	$data = strtr($data, array_flip($GLOBALS['CONVMAP']['utf8_to_entity']));
	
	return $data;
}

/**
 * wan_utf8_decode()
 * 
 * Décode une chaîne en UTF-8
 * 
 * @param string $data
 * 
 * @return string
 */
function wan_utf8_decode($data)
{
	$data = strtr($data, $GLOBALS['CONVMAP']['utf8_to_entity']);
	$data = utf8_decode($data);
	$data = strtr($data, array_flip($GLOBALS['CONVMAP']['cp1252_to_entity']));
	
	return $data;
}

/**
 * convert_encoding()
 * 
 * Détection d'encodage et conversion vers $charset
 * 
 * @param string $data
 * 
 * @return string
 */
function convert_encoding($data, $charset, $check_bom = true)
{
	if( empty($charset) )
	{
		if( $check_bom == true && strncmp($data, "\xEF\xBB\xBF", 3) == 0 ) // détection du BOM
		{
			$charset = 'UTF-8';
			$data = substr($data, 3);
		}
		else if( is_utf8($data) )
		{
			$charset = 'UTF-8';
		}
	}
	
	if( strtoupper($charset) == 'UTF-8' )
	{
		if( $GLOBALS['lang']['CHARSET'] == 'ISO-8859-1' )
		{
			$data = wan_utf8_decode($data);
		}
		else if( extension_loaded('iconv') )
		{
			$data = iconv($charset, $GLOBALS['lang']['CHARSET'] . '//TRANSLIT', $data);
		}
		else if( extension_loaded('mbstring') )
		{
			$data = mb_convert_encoding($data, $GLOBALS['lang']['CHARSET'], $charset);
		}
	}
	
	return $data;
}

/**
 * wan_get_contents()
 * 
 * Récupère un contenu local ou via HTTP et le retourne, ainsi que le jeu de
 * caractère et le type de média de la chaîne, si disponible.
 * 
 * @param string $URL      L'URL à appeller
 * @param string $errstr  Conteneur pour un éventuel message d'erreur
 * 
 * @return mixed
 */
function wan_get_contents($URL, &$errstr)
{
	global $lang;
	
	if( strncmp($URL, 'http://', 7) == 0 )
	{
		$result = http_get_contents($URL, $errstr);
		if( $result == false )
		{
			$errstr = sprintf($lang['Message']['Error_load_url'], wan_htmlspecialchars($URL), $errstr);
		}
	}
	else
	{
		if( $URL[0] == '~' )
		{
			$URL = server_info('DOCUMENT_ROOT') . substr($URL, 1);
		}
		else if( $URL[0] != '/' )// Chemin non absolu
		{
			$URL = WA_ROOTDIR . '/' . $URL;
		}
		
		if( is_readable($URL) )
		{
			$result = array('data' => file_get_contents($URL), 'charset' => null);
		}
		else
		{
			$result = false;
			$errstr = sprintf($lang['Message']['File_not_exists'], wan_htmlspecialchars($URL));
		}
	}
	
	return $result;
}

/**
 * http_get_contents()
 * 
 * Récupère un contenu via HTTP et le retourne, ainsi que le jeu de
 * caractère et le type de média de la chaîne, si disponible.
 * 
 * @param string $URL      L'URL à appeller
 * @param string $errstr  Conteneur pour un éventuel message d'erreur
 * 
 * @return mixed
 */
function http_get_contents($URL, &$errstr)
{
	global $lang;
	
	if( !($part = parse_url($URL)) || !isset($part['scheme']) || !isset($part['host']) || $part['scheme'] != 'http' )
	{
		$errstr = $lang['Message']['Invalid_url'];
		return false;
	}
	
	$port = !isset($part['port']) ? 80 : $part['port'];
	
	if( !($fs = fsockopen($part['host'], $port, $null, $null, 5)) )
	{
		$errstr = sprintf($lang['Message']['Unaccess_host'], wan_htmlspecialchars($part['host']));
		return false;
	}
	
	stream_set_timeout($fs, 5);
	
	$path  = !isset($part['path']) ? '/' : $part['path'];
	$path .= !isset($part['query']) ? '' : '?'.$part['query'];
	
	fputs($fs, sprintf("GET %s HTTP/1.0\r\n", $path));// HTTP 1.0 pour ne pas recevoir en Transfer-Encoding: chunked
	fputs($fs, sprintf("Host: %s\r\n", $part['host']));
	fputs($fs, sprintf("User-Agent: %s\r\n", WA_SIGNATURE));
	fputs($fs, "Accept: */*\r\n");
	
	if( extension_loaded('zlib') )
	{
		fputs($fs, "Accept-Encoding: gzip\r\n");
	}
	
	fputs($fs, "Connection: close\r\n\r\n");
	
	$isGzipped = false;
	$datatype  = $charset = null;
	$data = '';
	$tmp  = fgets($fs, 1024);
	
	if( !preg_match('#^HTTP/(\d\.[x\d])\x20+(\d{3})\s#', $tmp, $match) || $match[2] != 200 )
	{
		$errstr = $lang['Message']['Not_found_at_url'];
		fclose($fs);
		return false;
	}
	
	// Entêtes
	while( !feof($fs) )
	{
		$tmp = fgets($fs, 1024);
		
		if( !strpos($tmp, ':') ) {
			break;
		}
		
		list($header, $value) = explode(':', $tmp);
		$header = strtolower($header);
		$value  = trim($value);
		
		if( $header == 'content-type' )
		{
			if( preg_match('/^([a-z]+\/[a-z0-9+.-]+)\s*(?:;\s*charset=(")?([a-z][a-z0-9._-]*)(?(2)"))?/i', $value, $match) )
			{
				$datatype = $match[1];
				$charset  = !empty($match[3]) ? strtoupper($match[3]) : '';
			}
		}
		else if( $header == 'content-encoding' && $value == 'gzip' )
		{
			$isGzipped = true;
		}
	}
	
	// Contenu
	while( !feof($fs) )
	{
		$data .= fgets($fs, 1024);
	}
	
	fclose($fs);
	
	if( $isGzipped && !preg_match('/\.t?gz$/i', $part['path']) )
	{
		// RFC 1952 - Users note on http://www.php.net/manual/en/function.gzencode.php
		if( strncmp($data, "\x1f\x8b", 2) != 0 )
		{
			trigger_error('data is not to GZIP format', E_USER_WARNING);
			return false;
		}
		
		$data = gzinflate(substr($data, 10));
	}
	
	if( empty($charset) && preg_match('#(?:/|\+)xml$#', $datatype) && strncmp($data, '<?xml', 5) == 0 )
	{
		$prolog = substr($data, 0, strpos($data, "\n"));
		
		if( preg_match('/\s+encoding\s*=\s*("|\')([a-z][a-z0-9._-]*)\\1/i', $prolog, $match) )
		{
			$charset = $match[2];
		}
	}
	
	return array('type' => $datatype, 'charset' => $charset, 'data' => $data);
}

/**
 * wa_number_format()
 * 
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
	if( substr($number, -2) == '00' )
	{
		$number = substr($number, 0, -3);
	}
	
	return $number;
}

/**
 * hasCidReferences()
 * 
 * Retourne le nombre de références 'cid' (appel d'objet dans un email)
 * 
 * @param string  $body
 * @param array   $refs
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
 * formateSize()
 * 
 * Retourne une taille en octet formatée pour être lisible par un humain
 * 
 * @param string  $size
 * 
 * @return string
 */
function formateSize($size)
{
	$k = 1024;
	$m = $k * $k;
	$g = $m * $k;
	
	if( $size >= $g )
	{
		$unit = $GLOBALS['lang']['GO'];
		$size /= $g;
	}
	else if( $size >= $m )
	{
		$unit = $GLOBALS['lang']['MO'];
		$size /= $m;
	}
	else if( $size >= $k )
	{
		$unit = $GLOBALS['lang']['KO'];
		$size /= $k;
	}
	else
	{
		$unit = $GLOBALS['lang']['Octets'];
	}
	
	return sprintf("%s\xA0%s", wa_number_format($size), $unit);
}

$CONVMAP = array(
	'cp1252_to_entity' => array(
		"\x80" => "&#8364;",    # EURO SIGN
		"\x82" => "&#8218;",    # SINGLE LOW-9 QUOTATION MARK
		"\x83" => "&#402;",     # LATIN SMALL LETTER F WITH HOOK
		"\x84" => "&#8222;",    # DOUBLE LOW-9 QUOTATION MARK
		"\x85" => "&#8230;",    # HORIZONTAL ELLIPSIS
		"\x86" => "&#8224;",    # DAGGER
		"\x87" => "&#8225;",    # DOUBLE DAGGER
		"\x88" => "&#710;",     # MODIFIER LETTER CIRCUMFLEX ACCENT
		"\x89" => "&#8240;",    # PER MILLE SIGN */
		"\x8a" => "&#352;",     # LATIN CAPITAL LETTER S WITH CARON
		"\x8b" => "&#8249;",    # SINGLE LEFT-POINTING ANGLE QUOTATION
		"\x8c" => "&#338;",     # LATIN CAPITAL LIGATURE OE
		"\x8e" => "&#381;",     # LATIN CAPITAL LETTER Z WITH CARON
		"\x91" => "&#8216;",    # LEFT SINGLE QUOTATION MARK
		"\x92" => "&#8217;",    # RIGHT SINGLE QUOTATION MARK
		"\x93" => "&#8220;",    # LEFT DOUBLE QUOTATION MARK
		"\x94" => "&#8221;",    # RIGHT DOUBLE QUOTATION MARK
		"\x95" => "&#8226;",    # BULLET
		"\x96" => "&#8211;",    # EN DASH
		"\x97" => "&#8212;",    # EM DASH
		"\x98" => "&#732;",     # SMALL TILDE
		"\x99" => "&#8482;",    # TRADE MARK SIGN
		"\x9a" => "&#353;",     # LATIN SMALL LETTER S WITH CARON
		"\x9b" => "&#8250;",    # SINGLE RIGHT-POINTING ANGLE QUOTATION
		"\x9c" => "&#339;",     # LATIN SMALL LIGATURE OE
		"\x9e" => "&#382;",     # LATIN SMALL LETTER Z WITH CARON
		"\x9f" => "&#376;"      # LATIN CAPITAL LETTER Y WITH DIAERESIS
	),
	'utf8_to_entity' => array(
		"\xe2\x82\xac" => "&#8364;",
		"\xe2\x80\x9a" => "&#8218;",
		"\xc6\x92"     => "&#402;",
		"\xe2\x80\x9e" => "&#8222;",
		"\xe2\x80\xa6" => "&#8230;",
		"\xe2\x80\xa0" => "&#8224;",
		"\xe2\x80\xa1" => "&#8225;",
		"\xcb\x86"     => "&#710;",
		"\xe2\x80\xb0" => "&#8240;",
		"\xc5\xa0"     => "&#352;",
		"\xe2\x80\xb9" => "&#8249;",
		"\xc5\x92"     => "&#338;",
		"\xc5\xbd"     => "&#381;",
		"\xe2\x80\x98" => "&#8216;",
		"\xe2\x80\x99" => "&#8217;",
		"\xe2\x80\x9c" => "&#8220;",
		"\xe2\x80\x9d" => "&#8221;",
		"\xe2\x80\xa2" => "&#8226;",
		"\xe2\x80\x93" => "&#8211;",
		"\xe2\x80\x94" => "&#8212;",
		"\xcb\x9c"     => "&#732;",
		"\xe2\x84\xa2" => "&#8482;",
		"\xc5\xa1"     => "&#353;",
		"\xe2\x80\xba" => "&#8250;",
		"\xc5\x93"     => "&#339;",
		"\xc5\xbe"     => "&#382;",
		"\xc5\xb8"     => "&#376;"
	),
	'translite_cp1252' => array(
		"\x80" => "euro",
		"\x82" => ",",
		"\x83" => "f",
		"\x84" => ",,",
		"\x85" => "...",
		"\x86" => "?",
		"\x87" => "?",
		"\x88" => "^",
		"\x89" => "?",
		"\x8a" => "S",
		"\x8b" => "?",
		"\x8c" => "OE",
		"\x8e" => "Z",
		"\x91" => "'",
		"\x92" => "'",
		"\x93" => "\"",
		"\x94" => "\"",
		"\x95" => "?",
		"\x96" => "-",
		"\x97" => "--",
		"\x98" => "~",
		"\x99" => "tm",
		"\x9a" => "s",
		"\x9b" => ">",
		"\x9c" => "oe",
		"\x9e" => "z",
		"\x9f" => "Y"
	)
);

/**
 * wan_htmlspecialchars()
 * 
 * Idem que la fonction htmlspecialchars() native, mais avec le jeu de
 * caractère ISO-8859-1 par défaut.
 * 
 * @param string $string
 * @param int    $flags
 * @param string $encoding
 * @param bool   $double_encode
 * 
 * @return string
 */
function wan_htmlspecialchars($string, $flags = null, $encoding = 'ISO-8859-1', $double_encode = true)
{
	if( $flags == null ) {
		$flags = ENT_COMPAT | ENT_HTML401;
	}
	
	return htmlspecialchars($string, $flags, $encoding, $double_encode);
}

/**
 * wan_html_entity_decode()
 * 
 * Idem que la fonction html_entity_decode() native, mais avec le jeu de
 * caractère ISO-8859-1 par défaut.
 * 
 * @param string $string
 * @param int    $flags
 * @param string $encoding
 * 
 * @return string
 */
function wan_html_entity_decode($string, $flags = null, $encoding = 'ISO-8859-1')
{
	if( $flags == null ) {
		$flags = ENT_COMPAT | ENT_HTML401;
	}
	
	return html_entity_decode($string, $flags, $encoding);
}

}
?>