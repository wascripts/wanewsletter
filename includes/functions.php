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

if( !defined('FUNCTIONS_INC') ) {

define('FUNCTIONS_INC', true);

/**
 * generate_key()
 * 
 * Génération d'une chaîne aléatoire
 * 
 * @param integer $num_char    nombre de caractères
 * 
 * @return string
 */
function generate_key($num_char = 32)
{
	$rand_str = md5( uniqid( rand() ) );
	
	return ( $num_char >= 32 ) ? $rand_str : substr($rand_str, 0, $num_char);
}

/**
 * make_script_url()
 * 
 * Construction de l'url du script
 * 
 * @param string $url    Url relative
 * 
 * @return string
 */
function make_script_url($url = '')
{
	global $nl_config;
	
	$excluded_ports = array(80, 8080);
	$server_port    = server_info('SERVER_PORT');
	
	$server_name = preg_replace('/^http(s)?:\/\/(.*?)\/?$/', 'http\\1://\\2', $nl_config['urlsite']);
	$server_port = ( !in_array($server_port, $excluded_ports) ) ? ':' . $server_port : '';
	$script_path = ( $nl_config['path'] != '/' ) ? preg_replace('/^\/?(.*?)\/?$/', '/\\1/', $nl_config['path']) : '/';
	
	return $server_name . $server_port . $script_path . $url;
}

/**
 * Location()
 * 
 * Fonction de redirection du script avec url absolue, d'après les 
 * spécifications HTTP/1.1
 * 
 * @param string $url    Url relative de redirection
 * 
 * @return void
 */
function Location($url)
{
	global $db, $output;
	
	if( function_exists('sessid') && defined('IN_ADMIN') )
	{
		$url = sessid($url);
	}
	
	//
	// On ferme la connexion à la base de données, si elle existe 
	//
	if( isset($db) && is_object($db) )
	{
		$db->close_connexion();
	}
	
	$use_refresh   = preg_match("#Microsoft|WebSTAR|Xitami#i", server_info('SERVER_SOFTWARE'));
	$absolute_url  = make_script_url() . ( ( defined('IN_ADMIN') ) ? 'admin/' : '' );
	$absolute_url .= unhtmlspecialchars($url);
	
	header((( $use_refresh ) ? 'Refresh: 0; URL=' : 'Location: ' ) . $absolute_url);
	
	//
	// Si la fonction header() ne donne rien, on affiche une page de redirection 
	//
	$message = '<p>If your browser doesn\'t support meta redirect, click <a href="' . $url . '">here</a> to go on next page.</p>';
	
	$output->redirect($url, 0);
	$output->basic($message, 'Redirection');
}

/**
 * get_data()
 * 
 * Récupération du nombre de logs, inscrits, inscrits en attente 
 * de confirmation pour la/les liste(s) donnée(s)
 * 
 * @param mixed $liste_id_mixed    Id, ou tableau contenant les id de la/les listes 
 * 
 * @return array
 */
function get_data($liste_id_mixed)
{
	global $db;
	
	if( is_array($liste_id_mixed) )
	{
		$sql_where = 'liste_id IN(' . implode(', ', $liste_id_mixed) . ')';
	}
	else
	{
		$sql_where = 'liste_id = ' . $liste_id_mixed;
	}
	
	$data = array('num_inscrits' => 0, 'num_temp' => 0, 'num_logs' => 0, 'last_log' => 0);
	
	$sql = "SELECT DISTINCT(abo_id)
		FROM " . ABO_LISTE_TABLE . "
		WHERE " . $sql_where;
	if( DATABASE == 'mysql' )
	{
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir le nombre d\'inscrits/inscrits en attente', ERROR);
		}
		
		$abo_ids = array();
		
		if( $db->num_rows() > 0 )
		{
			while( $row = $db->fetch_array($result) )
			{
				array_push($abo_ids, $row['abo_id']);
			}
		}
		else
		{
			$abo_ids[] = 0;
		}
		
		$abo_ids = implode(', ', $abo_ids);
	}
	else
	{
		$abo_ids = $sql;
	}
	
	$sql = "SELECT COUNT(abo_id) AS num_abo, abo_status
		FROM " . ABONNES_TABLE . "
		WHERE abo_id IN($abo_ids)
		GROUP BY abo_status";
	if( !($result = $db->query($sql)) )
	{
		trigger_error('Impossible d\'obtenir le nombre d\'inscrits/inscrits en attente', ERROR);
	}
	
	while( $row = $db->fetch_array($result) )
	{
		if( $row['abo_status'] == ABO_ACTIF )
		{
			$data['num_inscrits'] = $row['num_abo'];
		}
		else
		{
			$data['num_temp'] = $row['num_abo'];
		}
	}
	
	$sql = "SELECT SUM(liste_numlogs) AS num_logs 
		FROM " . LISTE_TABLE . " 
		WHERE " . $sql_where;
	if( !($result = $db->query($sql)) )
	{
		trigger_error('Impossible d\'obtenir le nombre de logs envoyés', ERROR);
	}
	
	if( $row = $db->fetch_array($result) )
	{
		$data['num_logs'] = $row['num_logs'];
	}
	
	$sql = "SELECT MAX(log_date) AS last_log 
		FROM " . LOG_TABLE . " 
		WHERE log_status = " . STATUS_SENDED . " AND " . $sql_where;
	if( !($result = $db->query($sql)) )
	{
		trigger_error('Impossible d\'obtenir la date du dernier envoyé', ERROR);
	}
	
	if( $row = $db->fetch_array($result) )
	{
		$data['last_log'] = $row['last_log'];
	}
	
	return $data;
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
	global $nl_config, $db, $lang, $datetime, $output;
	
	$template_path = WA_ROOTDIR . '/templates/' . ( ( defined('IN_ADMIN') ) ? 'admin/' : '' );
	
	$output = new output($template_path);
	$output->addScript(WA_ROOTDIR . '/templates/DOM-Compat/DOM-Compat.js');
	
	if( defined('IN_ADMIN') )
	{
		$output->addScript(WA_ROOTDIR . '/templates/admin/admin.js');
	}
	
	if( !is_array($admindata) )
	{
		$admindata = array();
	}
	
	if( !empty($admindata['admin_lang']) )
	{
		$nl_config['language'] = $admindata['admin_lang'];
	}
	
	if( !empty($admindata['admin_dateformat']) )
	{
		$nl_config['date_format'] = $admindata['admin_dateformat'];
	}
	
	$language_path = wa_realpath(WA_ROOTDIR . '/language/lang_' . $nl_config['language'] . '.php');
	
	if( !file_exists($language_path) )
	{
		$nl_config['language'] = 'francais';
		$language_path = wa_realpath(WA_ROOTDIR . '/language/lang_' . $nl_config['language'] . '.php');
		
		if( !file_exists($language_path) )
		{
			trigger_error('<b>Les fichiers de language sont introuvables !</b>', CRITICAL_ERROR);
		}
	}
	
	require $language_path;
}

/**
 * wanewsletter_handler()
 * 
 * Gestionnaire d'erreur personnalisé du script 
 * 
 * @param integer $errno      Code de l'erreur
 * @param string  $errstr     Texte proprement dit de l'erreur
 * @param string  $errfile    Fichier où s'est produit l'erreur
 * @param integer $errline    Numéro de la ligne 
 * 
 * @return void
 */
function wanewsletter_handler($errno, $errstr, $errfile, $errline)
{
	global $db, $output, $lang, $message, $php_errormsg;
	
	$debug_text = '';
	
	if( defined('IN_CRON') && $errno == ERROR )
	{
		$errno = CRITICAL_ERROR;
	}
	
	if( ( $errno == CRITICAL_ERROR || $errno == ERROR ) && ( defined('IN_ADMIN') || defined('IN_CRON') || DEBUG_MODE ) )
	{
		if( !empty($db->sql_error['message']) )
		{
			$debug_text .= '<b>SQL query</b> :<br /> ' . nl2br($db->sql_error['query']) . "<br /><br />\n";
			$debug_text .= '<b>SQL errno</b> : ' . $db->sql_error['errno'] . "<br />\n";
			$debug_text .= '<b>SQL error</b> : ' . $db->sql_error['message'] . "<br />\n<br />\n";
		}
		
		$debug_text .= '<b>Fichier</b> : ' . basename($errfile) . " \n<b>Ligne</b> : " . $errline . ' <br />';
	}
	
	if( !empty($lang['Message'][$errstr]) )
	{
		$errstr = nl2br($lang['Message'][$errstr]);
	}
	
	if( $debug_text != '' )
	{
		$errstr .= "<br /><br />\n\n" . $debug_text;
	}
	
	switch( $errno )
	{
		case CRITICAL_ERROR:
			echo <<<BASIC
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr" dir="ltr">
<head>
	<title>Erreur critique !</title>
	
	<style type="text/css" media="screen">
	body { margin: 10px; text-align: left; }
	</style>
</head>
<body>
	<div>
		<h1>Erreur critique !</h1>
		
		<p>$errstr</p>
	</div>
</body>
</html>
BASIC;
			
			exit;
			break;
		
		case ERROR:			
		case MESSAGE:
			if( defined('IN_CRON') )
			{
				exit($errstr);
			}
			
			if( !defined('IN_WA_FORM') && !defined('IN_SUBSCRIBE') )
			{
				if( $errno == ERROR )
				{
					$msg_title = '<span style="color: #DD3333;">' . $lang['Title']['error'] . '</span>';
				}
				else if( $errno == MESSAGE )
				{
					$msg_title = '<span style="color: #33DD33;">' . $lang['Title']['info'] . '</span>';
				}
				
				if( !defined('HEADER_INC') )
				{
					$output->page_header();
				}
				
				$output->set_filenames(array(
					'body' => 'message_body.tpl'
				));
				
				$output->assign_vars( array(
					'MSG_TITLE' => $msg_title,
					'MSG_TEXT'	=> $errstr
				));
				
				$output->pparse('body');
				
				$output->page_footer();
			}
			
			$message = $errstr;
			break;
	}
	
	$php_errormsg = '';
	
	if( $errno == E_WARNING )
	{
		$php_errormsg .= '<b>Warning !</b> : ';
	}
	else if( $errno == E_NOTICE )
	{
		$php_errormsg .= '<b>Notice</b> : ';
	}
	
	$php_errormsg .= $errstr . ' in <b>' . basename($errfile) . '</b> on line <b>' . $errline . '</b>';
	
	//
	// Dans le cas d'une fonction précédée par @, error_reporting() 
	// retournera 0, dans ce cas, pas d'affichage d'erreur
	//
	$display_error = error_reporting(E_ALL);
	
	if( $errno != ERROR && $errno != E_STRICT && ( DEBUG_MODE == 3 || ( $display_error && DEBUG_MODE > 1 ) ) )
	{
		if( $errno != E_WARNING && $errno != E_NOTICE )
		{
			exit;
		}
		
		if( DISPLAY_ERRORS_IN_BLOCK == TRUE )
		{
			array_push($GLOBALS['_php_errors'], $php_errormsg);
		}
		else
		{
			echo '<p>' . $php_errormsg . '</p>';
		}
	}
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
		
		foreach( $datetime AS $orig_word => $repl_word )
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
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir les listes de diffusion à purger', ERROR);
		}
		
		while( $row = $db->fetch_array($result) )
		{
			$total_entries_deleted += purge_liste($row['liste_id'], $row['limitevalidate'], $row['purge_freq']);
		}
		
		//
		// Optimisation des tables
		//
		$db->check(array(ABONNES_TABLE, ABO_LISTE_TABLE));
		
		return $total_entries_deleted;
	}
	else
	{
		$sql = "SELECT a.abo_id
			FROM " . ABONNES_TABLE . " AS a
				INNER JOIN " . ABO_LISTE_TABLE . " AS al
				ON al.abo_id = a.abo_id
					AND al.liste_id = $liste_id
			WHERE a.abo_status = " . ABO_INACTIF . "
				AND a.abo_register_date < " . (time() - ($limitevalidate * 86400));
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir les entrées à supprimer', ERROR);
		}
		
		$abo_ids = array();
		while( $row = $db->fetch_array($result) )
		{
			array_push($abo_ids, $row['abo_id']);
		}
		
		if( ($num_abo_deleted = count($abo_ids)) > 0 )
		{
			$sql_abo_ids = implode(', ', $abo_ids);
			$db->transaction(START_TRC);
			
			$sql = "DELETE FROM " . ABO_LISTE_TABLE . "
				WHERE abo_id IN($sql_abo_ids)";
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de supprimer les entrées périmées de la table abo_liste', ERROR);
			}
			
			$sql = "DELETE FROM " . ABONNES_TABLE . "
				WHERE abo_id IN($sql_abo_ids)";
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de supprimer les entrées périmées de la table abonnes', ERROR);
			}
			
			$sql = "UPDATE " . LISTE_TABLE . " 
				SET purge_next = " . (time() + ($purge_freq * 86400)) . " 
				WHERE liste_id = " . $liste_id;
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de mettre à jour la table liste', ERROR);
			}
			
			$db->transaction(END_TRC);
		}
		
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
 * @return void
 */
function strip_magic_quotes_gpc(&$data)
{
	if( is_array($data) )
	{
		foreach( $data AS $key => $val )
		{
			if( is_array($val) )
			{
				strip_magic_quotes_gpc($val);
			}
			else if( is_string($val) )
			{
				$data[$key] = stripslashes($val);
			}
		}
	}
}

/**
 * wa_realpath()
 * 
 * Fonction similaire à realpath() mais utilise un autre moyen d'obtenir l'url canonique si 
 * la fonction realpath() est désactivée
 * 
 * @param string $relative_path    Url relative à résoudre
 * 
 * @return string
 */
function wa_realpath($relative_path)
{
	if( !@function_exists('realpath') || !@realpath(WA_ROOTDIR . '/includes/functions.php') )
	{
		return $relative_path;
	}
	
	return str_replace('\\', '/', realpath($relative_path));
}

/**
 * unhtmlspecialchars()
 * 
 * Fonction inverse de la fonction htmlspecialchars()
 * 
 * @param string $input
 * 
 * @return string
 */
function unhtmlspecialchars($input)
{
	$html_entities = array('/&lt;/', '/&gt;/', '/&quot;/', '/&amp;/');
	$html_replace  = array('<', '>', '"', '&');
	
	return preg_replace($html_entities, $html_replace, $input);
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
 * is_available_extension()
 * 
 * Vérifie si une extension est chargée, et tente de le faire si ce n'est pas le cas
 * 
 * @param string $module	: Nom de l'extension
 * 
 * @return boolean
 */
function is_available_extension($module, $use_dl = false)
{
	$module_file = ( stristr(PHP_OS, 'WIN') ) ? 'php_' . $module . '.dll' : $module . '.so';
	
	if( extension_loaded($module) || ($use_dl == true && !config_status('safe_mode') && config_status('enable_dl') && @dl($module_file)) )
	{
		return true;
	}
	
	return false;
//	return extension_loaded($module);
}

/**
 * config_status()
 * 
 * Retourne le statut d'une directive de configuration (telle que réglée sur On ou Off)
 * 
 * @param string $config_name    Nom de la directive
 * 
 * @return boolean
 */
function config_status($config_name)
{
	return ( ($config_val = @ini_get($config_name)) == 1 || strtolower($config_val) == 'on' ) ? true : false;
}

/**
 * is_disabled_func()
 * 
 * Vérifie si la fonction donnée est activée ou non dans la configuration de PHP
 * 
 * @param string $func_name
 * 
 * @return boolean
 */
function is_disabled_func($func_name)
{
	$liste = @ini_get('disable_functions');
	
	if( $liste === NULL )
	{
		return TRUE;
	}
	
	return in_array($func_name, array_map('trim', explode(',', $liste)));
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
 * make_sql_ary()
 * 
 * Parse un fichier contenant une liste de requète et 
 * renvoie un tableau avec une requète par entrée
 * 
 * @param string $input        Contenu du fichier .sql
 * @param string $delimiter    Délimiteur entre chaque requète (en général -> ; )
 * @param string $prefixe      Préfixe des tables à mettre à la place du prefixe par défaut
 * 
 * @return array
 */
function make_sql_ary($input, $delimiter, $prefixe = '')
{
	$tmp            = '';
	$output         = array();
	$in_comments    = false;
	$between_quotes = false;
	
	$lines       = preg_split("/(\r\n?|\n)/", $input, -1, PREG_SPLIT_DELIM_CAPTURE);
	$total_lines = count($lines);
	
	fake_header(false);
	
	for( $i = 0; $i < $total_lines; $i++ )
	{
		if( preg_match("/^\r\n?|\n$/", $lines[$i]) )
		{
			if( $between_quotes )
			{
				$tmp .= $lines[$i];
			}
			
			continue;
		}
		
		//
		// Si on est pas dans des simples quotes, on vérifie si on entre ds des commentaires
		//
		if( !$between_quotes && !$in_comments && preg_match('/^\/\*/', $lines[$i]) )
		{
			$in_comments = true;
		}
		
		if( $between_quotes || ( !$in_comments && strlen($lines[$i]) > 0 && $lines[$i]{0} != '#' && !preg_match('/^--\x20/', $lines[$i]) ) )
		{
			//
			// Nombre de simple quotes non échappés
			//
			$unescaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*'/", $lines[$i], $matches);
			
			if( ( !$between_quotes && !($unescaped_quotes % 2) ) || ( $between_quotes && ($unescaped_quotes % 2) ) )
			{
				if( preg_match('/' . $delimiter . '\s*$/i', $lines[$i]) )
				{
					$lines[$i] = ( $tmp != '' ) ? rtrim($lines[$i]) : trim($lines[$i]);
					$output[]  = $tmp . substr($lines[$i], 0, -(strlen($delimiter)));
					$tmp = '';
				}
				else
				{
					$tmp .= ( $tmp != '' ) ? $lines[$i] : ltrim($lines[$i]);
				}
				
				$between_quotes = false;
			}
			else
			{
				$between_quotes = true;
				$tmp .= ( $tmp != '' ) ? $lines[$i] : ltrim($lines[$i]);
			}
		}
		
		if( !$between_quotes && $in_comments && preg_match('/\*\/$/', rtrim($lines[$i])) )
		{
			$in_comments = false;
		}
		
		//
		// Pour tenter de ménager la mémoire 
		//
		unset($lines[$i]);
		
		fake_header(true);
	}
	
	if( $prefixe != '' )
	{
		$output = str_replace('wa_', $prefixe, $output);
	}
	
	//
	// Pour tenter de ménager la mémoire 
	//
	unset($input, $lines);
	
	return $output;
}

/**
 * purge_latin1()
 * 
 * Effectue une translitération sur les caractères interdits provenant de Windows-1252
 * ou les transforme en références d'entité numérique selon que la chaîne est du texte brut ou du HTML
 * 
 * @param string $str        Chaîne à modifier
 * @param string $translite  Active ou non la translitération
 * 
 * @return string
 */
function purge_latin1($str, $translite = false)
{
	if( $translite == true )
	{
		$convmap = array(
			"\x80" => "euro",    # EURO SIGN
			"\x82" => ",",       # SINGLE LOW-9 QUOTATION MARK
			"\x83" => "f",       # LATIN SMALL LETTER F WITH HOOK
			"\x84" => ",,",      # DOUBLE LOW-9 QUOTATION MARK
			"\x85" => "...",     # HORIZONTAL ELLIPSIS
			"\x86" => "?",       # DAGGER
			"\x87" => "?",       # DOUBLE DAGGER
			"\x88" => "^",       # MODIFIER LETTER CIRCUMFLEX ACCENT
			"\x89" => "?",       # PER MILLE SIGN
			"\x8a" => "S",       # LATIN CAPITAL LETTER S WITH CARON
			"\x8b" => "?",       # SINGLE LEFT-POINTING ANGLE QUOTATION
			"\x8c" => "OE",      # LATIN CAPITAL LIGATURE OE
			"\x8e" => "Z",       # LATIN CAPITAL LETTER Z WITH CARON
			"\x91" => "'",       # LEFT SINGLE QUOTATION MARK
			"\x92" => "'",       # RIGHT SINGLE QUOTATION MARK
			"\x93" => "\"",      # LEFT DOUBLE QUOTATION MARK
			"\x94" => "\"",      # RIGHT DOUBLE QUOTATION MARK
			"\x95" => "?",       # BULLET
			"\x96" => "-",       # EN DASH
			"\x97" => "--",      # EM DASH
			"\x98" => "~",       # SMALL TILDE
			"\x99" => "tm",      # TRADE MARK SIGN
			"\x9a" => "s",       # LATIN SMALL LETTER S WITH CARON
			"\x9b" => ">",       # SINGLE RIGHT-POINTING ANGLE QUOTATION
			"\x9c" => "oe",      # LATIN SMALL LIGATURE OE
			"\x9e" => "z",       # LATIN SMALL LETTER Z WITH CARON
			"\x9f" => "Y"        # LATIN CAPITAL LETTER Y WITH DIAERESIS
		);
	}
	else
	{
		$convmap = array(
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
		);
	}
	
	return strtr($str, $convmap);
}

function is_utf8($str)
{
	// From http://w3.org/International/questions/qa-forms-utf-8.html
	return preg_match('/^(?:
		 [\x09\x0A\x0D\x20-\x7E]            # ASCII
	   | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
	   |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
	   | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
	   |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
	   |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
	   | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
	   |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
	)*$/xs', $str);
} // function is_utf8

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
	
	if( $charset == 'UTF-8' )
	{
		if( $GLOBALS['lang']['CHARSET'] == 'ISO-8859-1' )
		{
			// Conversion caractères illégaux provenant de Windows-1252
			$convmap = array(
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
			);
			
			$data = utf8_decode(strtr($data, $convmap));
			
			$convmap = array(
				"&#8364;" => "\x80",
				"&#8218;" => "\x82",
				"&#402;"  => "\x83",
				"&#8222;" => "\x84",
				"&#8230;" => "\x85",
				"&#8224;" => "\x86",
				"&#8225;" => "\x87",
				"&#710;"  => "\x88",
				"&#8240;" => "\x89",
				"&#352;"  => "\x8a",
				"&#8249;" => "\x8b",
				"&#338;"  => "\x8c",
				"&#381;"  => "\x8e",
				"&#8216;" => "\x91",
				"&#8217;" => "\x92",
				"&#8220;" => "\x93",
				"&#8221;" => "\x94",
				"&#8226;" => "\x95",
				"&#8211;" => "\x96",
				"&#8212;" => "\x97",
				"&#732;"  => "\x98",
				"&#8482;" => "\x99",
				"&#353;"  => "\x9a",
				"&#8250;" => "\x9b",
				"&#339;"  => "\x9c",
				"&#382;"  => "\x9e",
				"&#376;"  => "\x9f"
			);
			
			$data = strtr($data, $convmap);
		}
		else if( is_available_extension('mbstring') )
		{
			$data = mb_convert_encoding($data, $GLOBALS['lang']['CHARSET'], $charset);
		}
	}
	
	return $data;
}

/**
 * http_get_contents()
 * 
 * Récupère un contenu via HTTP et le retourne, ainsi que le jeu de caractère de la chaîne,
 * si disponible, et le type de média
 * 
 * @param mixed $URL      L'URL à appeller
 * @param string $errstr  Conteneur pour un éventuel message d'erreur
 * 
 * @return array
 */
function http_get_contents($URL, &$errstr)
{
	global $nl_config, $lang;
	
	require WA_ROOTDIR . '/includes/http/Client.php';
	
	$client =& new HTTP_Client();
	$client->openURL('HEAD', $URL);
	$client->setRequestHeader('User-Agent', "Wanewsletter $nl_config[version]");
	$client->setRequestHeader('Accept-Encoding', 'gzip');
	
	if( $client->send() == false )
	{
		$errstr = sprintf($lang['Message']['Unaccess_host'], htmlspecialchars($client->url->host));
		return false;
	}
	
	if( $client->responseCode != HTTP_STATUS_OK )
	{
		$errstr = $lang['Message']['Not_found_at_url'];
		return false;
	}
	
	//
	// Recherche du type mime des données
	//
	$datatype = $client->getResponseHeader('Content-Type');
	
	if( !preg_match('/^([a-z]+\/[a-z0-9+.-]+)\s*(?:;\s*charset=(")?([a-z][a-z0-9._-]*)(?(2)"))?/i', $datatype, $match) )
	{
		$errstr = $lang['Message']['No_data_at_url'] . ' (type manquant)';
		return false;
	}
	
	$datatype = $match[1];
	$charset  = !empty($match[3]) ? strtoupper($match[3]) : '';
	
	//
	// Ok, Tout va bien, on récupère les données
	//
	$client->openURL('GET', $URL);
	$client->send();
	
	if( empty($charset) && preg_match('#(?:/|\+)xml#', $datatype) && substr($client->responseData, 0, 5) == '<?xml' )
	{
		$prolog = substr($client->responseData, 0, strpos($client->responseData, "\n"));
		
		if( preg_match('/encoding=("|\')([a-z][a-z0-9._-]*)\\1"/i', $prolog, $match) )
		{
			$charset = $match[2];
		}
	}
	
	return array('type' => $datatype, 'charset' => $charset, 'data' => $client->responseData);
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
	return number_format($number, $decimals, $GLOBALS['lang']['DEC_POINT'], $GLOBALS['lang']['THOUSANDS_SEP']);
}

//
// Appel du gestionnaire d'erreur 
//
set_error_handler('wanewsletter_handler');

}
?>