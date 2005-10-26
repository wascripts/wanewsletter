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
	
/*	if( version_compare(PHP_VERSION, '4.3.0', '>=') == 1 )
	{
		header('Found', TRUE, 302);
	}
	else
	{
		header('HTTP/1.x 302 Found');
	}*/
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
		$sql_where = 'IN(' . implode(', ', $liste_id_mixed) . ')';
	}
	else
	{
		$sql_where = '= ' . $liste_id_mixed;
	}
	
	$data = array('num_inscrits' => 0, 'num_temp' => 0, 'num_logs' => 0, 'last_log' => 0);
	
	$sql = "SELECT DISTINCT(abo_id)
		FROM " . ABO_LISTE_TABLE . "
		WHERE liste_id " . $sql_where;
	if( !($result = $db->query($sql)) )
	{
		trigger_error('Impossible d\'obtenir le nombre d\'inscrits/inscrits en attente', ERROR);
	}
	
	if( $db->num_rows() > 0 )
	{
		$abo_ids = array();
		while( $row = $db->fetch_array($result) )
		{
			array_push($abo_ids, $row['abo_id']);
		}
		
		$sql = "SELECT COUNT(abo_id) AS num_abo, abo_status
			FROM " . ABONNES_TABLE . "
			WHERE abo_id IN(" . implode(', ', $abo_ids) . ")
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
	}
	
	$sql = "SELECT SUM(liste_numlogs) AS num_logs 
		FROM " . LISTE_TABLE . " 
		WHERE liste_id " . $sql_where;
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
		WHERE log_status = " . STATUS_SENDED . " AND liste_id " . $sql_where;
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
	
	$template_path = WA_PATH . 'templates/' . ( ( defined('IN_ADMIN') ) ? 'admin/' : '' );
	
	$output = new output($template_path);
	$output->addScript(WA_PATH . 'templates/DOM-Compat/DOM-Compat.js');
	
	if( defined('IN_ADMIN') )
	{
		$output->addScript(WA_PATH . 'templates/admin/admin.js');
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
	
	$language_path = wa_realpath(WA_PATH . 'language/lang_' . $nl_config['language'] . '.php');
	
	if( !file_exists($language_path) )
	{
		$nl_config['language'] = 'francais';
		$language_path = wa_realpath(WA_PATH . 'language/lang_' . $nl_config['language'] . '.php');
		
		if( !file_exists($language_path) )
		{
			trigger_error('<b>Les fichiers de language sont introuvables !</b>', CRITICAL_ERROR);
		}
	}
	
	include $language_path;
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
			echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
			echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"fr\" lang=\"fr\" dir=\"ltr\">\n";
			echo "<head>\n";
			echo "<title>Erreur critique !</title>\n";
			echo "<style type=\"text/css\" media=\"screen\">\n";
			echo 'body { margin: 10px; text-align: left; }' . "\n";
			echo "</style>\n</head>\n<body>\n\n<div>\n";
			echo "<h1>Erreur critique !</h1>\n\n<p>" . $errstr . '</p>';
			echo "\n</div>\n\n</body>\n</html>\n";
			
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
		
		echo '<p>' . $php_errormsg . '</p>';
	}
}

/**
 * plain_error()
 * 
 * @param mixed   $var     Variable à afficher
 * @param boolean $exit    True pour terminer l'exécution du script
 * 
 * @return void
 */
function plain_error($var, $exit = true)
{
	header('Content-Type: text/plain; charset=ISO-8859-15');
	
	var_dump($var);
	
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
	
	if( !isset($orig_ary) || !isset($repl_ary) )
	{
		global $datetime;
		
		$search = $replace = array();
		
		foreach( $datetime AS $orig_word => $repl_word )
		{
			$search[]  = '/\b' . $orig_word . '\b/i';
			$replace[] = $repl_word;
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
			FROM " . ABONNES_TABLE . " AS a, " . ABO_LISTE_TABLE . " AS al 
			WHERE al.liste_id = $liste_id 
				AND a.abo_id = al.abo_id 
				AND a.abo_status = " . ABO_INACTIF . " 
				AND a.abo_register_date < " . (time() - ($limitevalidate * 86400));
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir les entrées à supprimer', ERROR);
		}
		
		$abo_id_ary = array();
		while( $row = $db->fetch_array($result) )
		{
			$abo_id_ary[] = $row['abo_id'];
		}
		
		if( $num_abo_deleted = count($abo_id_ary) )
		{
			$db->transaction(START_TRC);
			
			$sql = "DELETE FROM " . ABO_LISTE_TABLE . " 
				WHERE abo_id IN(" . implode(', ', $abo_id_ary) . ")";
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de supprimer les entrées périmées de la table abo_liste', ERROR);
			}
			
			$sql = "DELETE FROM " . ABONNES_TABLE . " 
				WHERE abo_id IN(" . implode(', ', $abo_id_ary) . ")";
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
	if( !@function_exists('realpath') || !@realpath(WA_PATH . 'includes/functions.php') )
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
function is_available_extension($module)
{
/*	$module_file = ( stristr(PHP_OS, 'WIN') ) ? 'php_' . $module . '.dll' : $module . '.so';
	
	if( extension_loaded($module) || ( !config_status('safe_mode') && config_status('enable_dl') && @dl($module_file) ) )
	{
		return true;
	}
	
	return false;
	
*/	return extension_loaded($module);
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
	
	$lines       = preg_split("/(\r\n?)|\n/", $input, -1, PREG_SPLIT_DELIM_CAPTURE);
	$total_lines = count($lines);
	
	fake_header(false);
	
	for( $i = 0; $i < $total_lines; $i++ )
	{
		if( preg_match("/^(\r\n?)|\n$/", $lines[$i]) )
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
		
		if( $between_quotes || ( !$in_comments && strlen($lines[$i]) > 0 && $lines[$i]{0} != '#' ) )
		{
			//
			// Nombre de simple quotes non échappés
			//
			$unescaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*'/", $lines[$i], $matches);
			
			if( ( !$between_quotes && !($unescaped_quotes % 2) ) || ( $between_quotes && ($unescaped_quotes % 2) ) )
			{
				if( preg_match('/' . $delimiter . '$/i', rtrim($lines[$i])) )
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
		
		if( !$between_quotes && $in_comments && preg_match("/\*\/$/", rtrim($lines[$i])) )
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
		$output = preg_replace('/wa_/', $prefixe, $output);
	}
	
	//
	// Pour tenter de ménager la mémoire 
	//
	unset($input, $lines);
	
	return $output;
}

//
// Appel du gestionnaire d'erreur 
//
set_error_handler('wanewsletter_handler');

?>