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
 * @param integer $num_char    Nombre de caractères
 * @param integer $use_uniqid  Active/Désactive l'utilisation de uniqid() (très
 *                             consommateur de ressources lors des importations de masse)
 * 
 * @return string
 */
function generate_key($num_char = 32, $use_uniqid = true)
{
	if( $use_uniqid == true )
	{
		srand((double) microtime() * 1000000);
		$rand_str = md5(uniqid(rand()));
	}
	else
	{
		$rand_str = md5(microtime());
	}
	
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
	
	return rtrim($nl_config['urlsite'], '/')
		. (( !in_array($server_port, $excluded_ports) ) ? ':' . $server_port : '')
		. (( $nl_config['path'] != '/' ) ? '/' . trim($nl_config['path'], '/') . '/' : '/')
		. $url;
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
		$db->close();
	}
	
	$use_refresh   = preg_match("#Microsoft|WebSTAR|Xitami#i", server_info('SERVER_SOFTWARE'));
	$absolute_url  = make_script_url() . (( defined('IN_ADMIN') ) ? 'admin/' : '');
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
	
	if( !defined('IN_COMMANDLINE') )
	{
		global $output;
		
		$template_path = WA_ROOTDIR . '/templates/' . ( ( defined('IN_ADMIN') ) ? 'admin/' : '' );
		
		$output = new output($template_path);
		$output->addScript(WA_ROOTDIR . '/templates/DOM-Compat/DOM-Compat.js');
		
		if( defined('IN_ADMIN') )
		{
			$output->addScript(WA_ROOTDIR . '/templates/admin/admin.js');
		}
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
			trigger_error('<b>Les fichiers de localisation sont introuvables !</b>', CRITICAL_ERROR);
		}
	}
	
	require $language_path;
	
	$lang['CHARSET'] = strtoupper($lang['CHARSET']);
}

/**
 * wan_web_handler()
 * 
 * Gestionnaire d'erreur personnalisé du script (en sortie http)
 * 
 * @param integer $errno      Code de l'erreur
 * @param string  $errstr     Texte proprement dit de l'erreur
 * @param string  $errfile    Fichier où s'est produit l'erreur
 * @param integer $errline    Numéro de la ligne 
 * 
 * @return void
 */
function wan_web_handler($errno, $errstr, $errfile, $errline)
{
	global $db, $output, $lang, $message, $php_errormsg;
	
	$debug_text = '';
	
	if( defined('IN_CRON') && $errno == ERROR )
	{
		$errno = CRITICAL_ERROR;
	}
	
	if( ( $errno == CRITICAL_ERROR || $errno == ERROR ) && ( defined('IN_ADMIN') || defined('IN_CRON') || DEBUG_MODE ) )
	{
		if( !empty($db->error) && DEBUG_MODE > 0 )
		{
			$debug_text .= '<b>SQL query</b>&#160;:<br /> ' . nl2br($db->lastQuery) . "<br /><br />\n";
			$debug_text .= '<b>SQL errno</b>&#160;: ' . $db->errno . "<br />\n";
			$debug_text .= '<b>SQL error</b>&#160;: ' . $db->error . "<br />\n<br />\n";
		}
		
		$debug_text .= '<b>Fichier</b>&#160;: ' . basename($errfile) . " \n<b>Ligne</b>&#160;: " . $errline . '<br />';
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
	<title>Erreur critique&#160;!</title>
	
	<style type="text/css" media="screen">
	body { margin: 10px; text-align: left; }
	</style>
</head>
<body>
	<div>
		<h1>Erreur critique&#160;!</h1>
		
		<p>$errstr</p>
	</div>
</body>
</html>
BASIC;
			
			exit;
			break;
		
		case ERROR:
			if( defined('IN_CRON') )
			{
				exit($errstr);
			}
			
			if( !defined('IN_WA_FORM') && !defined('IN_SUBSCRIBE') )
			{
				$title = '<span style="color: #DD3333;">' . $lang['Title']['error'] . '</span>';
				
				if( !defined('HEADER_INC') )
				{
					$output->page_header();
				}
				
				$output->set_filenames(array(
					'body' => 'message_body.tpl'
				));
				
				$output->assign_vars( array(
					'MSG_TITLE' => $title,
					'MSG_TEXT'  => $errstr
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
		$php_errormsg .= '<b>Warning !</b>&#160;: ';
	}
	else if( $errno == E_NOTICE )
	{
		$php_errormsg .= '<b>Notice</b>&#160;: ';
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
		
		if( defined('IN_NEWSLETTER') == TRUE && DISPLAY_ERRORS_IN_BLOCK == TRUE && defined('IN_ADMIN') )
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
 * wan_cli_handler()
 * 
 * Gestionnaire d'erreur personnalisé du script (en ligne de commande)
 * 
 * @param integer $errno      Code de l'erreur
 * @param string  $errstr     Texte proprement dit de l'erreur
 * @param string  $errfile    Fichier où s'est produit l'erreur
 * @param integer $errline    Numéro de la ligne 
 * 
 * @return void
 */
function wan_cli_handler($errno, $errstr, $errfile, $errline)
{
	global $db, $lang;
	
	if( !empty($lang['Message'][$errstr]) )
	{
		$errstr = $lang['Message'][$errstr];
	}
	
	$errstr  = strip_tags($errstr);
	$errstr .= ' in ' . basename($errfile) . ' on line ' . $errline;
	
	if( !empty($db->error) && DEBUG_MODE > 0 )
	{
		$errstr .= "\n";
		$errstr .= 'SQL query: ' . $db->lastQuery . "\n";
		$errstr .= 'SQL errno: ' . $db->errno . "\n";
		$errstr .= 'SQL error: ' . $db->error . "\n\n";
	}
	
	//
	// Dans le cas d'une fonction précédée par @, error_reporting() 
	// retournera 0, dans ce cas, pas d'affichage d'erreur
	//
	$display_error = error_reporting(E_ALL);
	
	if( preg_match('/\.UTF-?8/', getenv('LANG')) ) // Au cas où le terminal utilise l'encodage utf-8
	{
		$errstr = wan_utf8_encode($errstr);
	}
	
	if( $errno != E_STRICT && ( DEBUG_MODE == 3 || $display_error ) )
	{
		fputs(STDERR, 'Error: ' . $errstr . "\n");
	}
	
	if( $errno == CRITICAL_ERROR )
	{
		exit(0);
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
 * wanlog()
 * 
 * @param string $str  Chaîne d'information ou d'erreur à stocker dans
 *                     l'historique de Wanewsletter
 * 
 * @return void
 */
function wanlog($str)
{
	if( isset($GLOBALS['_php_errors']) ) {
		array_push($GLOBALS['_php_errors'], $str);
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
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir les listes de diffusion à purger', ERROR);
		}
		
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
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir les entrées à supprimer de la table abo_liste', ERROR);
		}
		
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
			
			switch( SQL_DRIVER )
			{
				case 'mysql':
					$sql = "SELECT abo_id
						FROM " . ABO_LISTE_TABLE . "
						WHERE abo_id IN($sql_abo_ids)
						GROUP BY abo_id
						HAVING COUNT(abo_id) = 1";
					if( $result = $db->query($sql) )
					{
						$abo_ids = array();
						while( $abo_id = $result->column('abo_id') )
						{
							array_push($abo_ids, $abo_id);
						}
						
						if( count($abo_ids) > 0 )
						{
							$sql = "DELETE FROM " . ABONNES_TABLE . " 
								WHERE abo_id IN(" . implode(', ', $abo_ids) . ")";
							if( !$db->query($sql) )
							{
								trigger_error('Impossible de supprimer les entrées inutiles de la table des abonnés', ERROR);
							}
						}
					}
					break;
				
				default:
					$sql = "DELETE FROM " . ABONNES_TABLE . "
						WHERE abo_id IN(
							SELECT abo_id
							FROM " . ABO_LISTE_TABLE . "
							WHERE abo_id IN($sql_abo_ids)
							GROUP BY abo_id
							HAVING COUNT(abo_id) = 1
						)";
					if( !$db->query($sql) )
					{
						trigger_error('Impossible de supprimer les entrées inutiles de la table des abonnés', ERROR);
					}
					break;
			}
			
			$sql = "DELETE FROM " . ABO_LISTE_TABLE . "
				WHERE abo_id IN($sql_abo_ids)
					AND liste_id = " . $liste_id;
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de supprimer les entrées de la table abo_liste', ERROR);
			}
			
			$db->commit();
		}
		
		$sql = "UPDATE " . LISTE_TABLE . " 
			SET purge_next = " . (time() + ($purge_freq * 86400)) . " 
			WHERE liste_id = " . $liste_id;
		if( !$db->query($sql) )
		{
			trigger_error('Impossible de mettre à jour la table liste', ERROR);
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
 * @param string $data       Chaîne à modifier
 * @param string $translite  Active ou non la translitération
 * 
 * @link   http://w3.org/International/questions/qa-forms-utf-8.html
 * @return string
 */
function is_utf8($data)
{
	return preg_match('/^(?:
		 [\x09\x0A\x0D\x20-\x7E]            # ASCII
	   | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
	   |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
	   | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
	   |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
	   |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
	   | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
	   |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
	)*$/xs', $data);
} // function is_utf8

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
	
	if( $charset == 'UTF-8' )
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
	global $lang;
	
	require WA_ROOTDIR . '/includes/http/Client.php';
	
	$client =& new HTTP_Client();
	$client->openURL('HEAD', $URL);
	$client->setRequestHeader('User-Agent', 'Wanewsletter ' . WA_VERSION);
	
	if( extension_loaded('zlib') )
	{
		$client->setRequestHeader('Accept-Encoding', 'gzip');
	}
	
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
	
	if( empty($charset) && preg_match('#(?:/|\+)xml#', $datatype) && strncmp($client->responseData, '<?xml', 5) == 0 )
	{
		$prolog = substr($client->responseData, 0, strpos($client->responseData, "\n"));
		
		if( preg_match('/\s+encoding\s?=\s?("|\')([a-z][a-z0-9._-]*)\\1"/i', $prolog, $match) )
		{
			$charset = $match[2];
		}
	}
	
	return array(
		'URI'     => $client->url->__toString(),
		'type'    => $datatype,
		'charset' => $charset,
		'data'    => $client->responseData
	);
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
	$total = preg_match_all('/<.+?"cid:([^\\:*\/?<">|]+)"[^>]*>/si', $body, $matches);
	$refs  = $matches[1];
	
	return $total;
}

/**
 * formateSize()
 * 
 * Retourne une taille en octet formatée pour être lisible par un humain
 * 
 * @param string  $body
 * @param array   $refs
 * 
 * @return integer
 */
function formateSize($size)
{
	if( $size >= 1048576 )
	{
		$lsize = $GLOBALS['lang']['MO'];
		$size /= 1048576;
	}
	else if( $size > 1024 )
	{
		$lsize = $GLOBALS['lang']['KO'];
		$size /= 1024;
	}
	else
	{
		$lsize = $GLOBALS['lang']['Octets'];
	}
	
	return sprintf("%s\xA0%s", wa_number_format($size), $lsize);
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

}
?>