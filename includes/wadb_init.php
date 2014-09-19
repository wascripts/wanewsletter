<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if( !defined('_INC_WADB_INIT') ) {

define('_INC_WADB_INIT', true);

//
// Tables du script 
//
define('ABO_LISTE_TABLE',     $prefixe . 'abo_liste');
define('ABONNES_TABLE',       $prefixe . 'abonnes');
define('ADMIN_TABLE',         $prefixe . 'admin');
define('AUTH_ADMIN_TABLE',    $prefixe . 'auth_admin');
define('BANLIST_TABLE',       $prefixe . 'ban_list');
define('CONFIG_TABLE',        $prefixe . 'config');
define('JOINED_FILES_TABLE',  $prefixe . 'joined_files');
define('FORBIDDEN_EXT_TABLE', $prefixe . 'forbidden_ext');
define('LISTE_TABLE',         $prefixe . 'liste');
define('LOG_TABLE',           $prefixe . 'log');
define('LOG_FILES_TABLE',     $prefixe . 'log_files');
define('SESSIONS_TABLE',      $prefixe . 'session');

/**
 * Génère une chaîne DSN
 * 
 * @param array $infos    Informations sur l'accès à la base de données
 * @param array $options  Options de connexion
 */
function createDSN($infos, $options = null)
{
	if( $infos['driver'] == 'mysqli' ) {
		$infos['driver'] = 'mysql';
	}
	else if( $infos['driver'] == 'sqlite_pdo' || $infos['driver'] == 'sqlite3' ) {
		$infos['driver'] = 'sqlite';
	}
	
	$connect = '';
	
	if( isset($infos['user']) ) {
		$connect .= rawurlencode($infos['user']);
		
		if( isset($infos['pass']) ) {
			$connect .= ':' . rawurlencode($infos['pass']);
		}
		
		$connect .= '@';
		
		if( empty($infos['host']) ) {
			$infos['host'] = 'localhost';
		}
	}
	
	if( !empty($infos['host']) ) {
		$connect .= rawurlencode($infos['host']);
		if( isset($infos['port']) ) {
			$connect .= ':' . intval($infos['port']);
		}
	}
	
	if( !empty($connect) ) {
		$dsn = sprintf('%s://%s/%s', $infos['driver'], $connect, $infos['dbname']);
	}
	else {
		$dsn = sprintf('%s:%s', $infos['driver'], $infos['dbname']);
	}
	
	if( is_array($options) ) {
		$dsn .= '?';
		foreach( $options as $name => $value ) {
			$dsn .= rawurlencode($name) . '=' . rawurlencode($value) . '&';
		}
		
		$dsn = substr($dsn, 0, -1);// Suppression dernier esperluette
	}
	
	return $dsn;
}

/**
 * Décompose une chaîne DSN
 * 
 * @param string $dsn
 */
function parseDSN($dsn)
{
	if( !($dsn_parts = parse_url($dsn)) || !isset($dsn_parts['scheme']) ) {
		return false;
	}
	
	$infos = $options = array();
	$label = array('mysql' => 'MySQL', 'postgres' => 'PostgreSQL', 'sqlite' => 'SQLite');
	
	foreach( $dsn_parts as $key => $value ) {
		switch( $key ) {
			case 'scheme':
				if( !in_array($value, array('mysql', 'postgres', 'sqlite')) ) {
					trigger_error("Unsupported database", CRITICAL_ERROR);
					return false;
				}
				else {
					$infos['label'] = $label[$value];
					
					if( $value == 'mysql' && extension_loaded('mysqli') ) {
						$value = 'mysqli';
					}
				}
				
				$infos['driver'] = $value;
				break;
			
			case 'host':
			case 'port':
			case 'user':
			case 'pass':
				$infos[$key] = rawurldecode($value);
				break;
			
			case 'path':
				$infos['dbname'] = rawurldecode($value);
				
				if( $infos['driver'] != 'sqlite' && isset($infos['host']) ) {
					$infos['dbname'] = ltrim($infos['dbname'], '/');
				}
				break;
			
			case 'query':
				preg_match_all('/([^=]+)=([^&]+)(?:&|$)/', $value, $matches, PREG_SET_ORDER);
				
				foreach( $matches as $data ) {
					$options[rawurldecode($data[1])] = rawurldecode($data[2]);
				}
				break;
		}
	}
	
	if( $infos['driver'] == 'sqlite' ) {
		
		if( class_exists('SQLite3') ) {
			$infos['driver'] = 'sqlite3';
		}
		else if( extension_loaded('pdo') && extension_loaded('pdo_sqlite') ) {
			$infos['driver'] = 'sqlite_pdo';
		}
		else if( !extension_loaded('sqlite') ) {
			trigger_error("No SQLite3, PDO/SQLite or SQLite extension loaded !", CRITICAL_ERROR);
		}
		
		if( is_readable($infos['dbname']) && filesize($infos['dbname']) > 0 ) {
			$fp = fopen($infos['dbname'], 'rb');
			$info = fread($fp, 15);
			fclose($fp);
			
			if( strcmp($info, 'SQLite format 3') == 0 ) {
				if( $infos['driver'] == 'sqlite' ) {
					trigger_error("No SQLite3 or PDO/SQLite extension loaded !", CRITICAL_ERROR);
				}
			}
			else if( $infos['driver'] != 'sqlite' ) {
				if( !extension_loaded('sqlite') ) {
					trigger_error("SQLite extension isn't loaded !", CRITICAL_ERROR);
				}
				else {
					$infos['driver'] = 'sqlite';
				}
			}
		}
	}
	
	return array($infos, $options);
}

/**
 * Initialise la connexion à la base de données à partir d'une chaîne DSN
 * 
 * @param string $dsn
 */
function WaDatabase($dsn)
{
	if( !($tmp = parseDSN($dsn)) ) {
		trigger_error("Invalid DSN argument", CRITICAL_ERROR);
		return false;
	}
	
	list($infos, $options) = $tmp;
	$dbclass = 'Wadb_' . $infos['driver'];
	
	if( !class_exists($dbclass) ) {
		require WA_ROOTDIR . "/includes/sql/$infos[driver].php";
	}
	
	$infos['username'] = isset($infos['user']) ? $infos['user'] : null;
	$infos['passwd']   = isset($infos['pass']) ? $infos['pass'] : null;
	
	$db = new $dbclass($infos['dbname']);
	$db->connect($infos, $options);
	
	$encoding = $db->encoding();
	if( strncmp($infos['driver'], 'sqlite', 6) != 0 && preg_match('#^UTF-?(8|16)|UCS-?2|UNICODE$#i', $encoding) ) {
		/*
		 * WorkAround : Wanewsletter ne gère pas les codages de caractères multi-octets.
		 * Si le jeu de caractères de la connexion est multi-octet, on le change
		 * arbitrairement pour le latin1 et on affiche une alerte à l'utilisateur.
		 */
		$newEncoding = 'latin1';
		$db->encoding($newEncoding);
		
		wanlog("<p>Wanewsletter a détecté que le <strong>jeu de caractères de connexion</strong>
à votre base de données est réglé sur <q>$encoding</q>. Wanewsletter ne gère
pas les codages de caractères multi-octets et a donc changé cette valeur pour
<q>$newEncoding</q>.</p>
<p>Vous devriez éditer le fichier <samp>includes/config.inc.php</samp> et fixer
ce réglage en ajoutant la chaîne <code>?charset=latin1</code> après le nom de votre
base de données dans la variable <code>\$dsn</code> (<q>latin1</q> est utilisé
dans l'exemple mais vous pouvez spécifier n'importe quel jeu de caractère 8 bit
convenant le mieux à votre langue. Référez-vous à la documentation de votre base
de données pour connaître les jeux de caractères utilisables).</p>");
	}
	
	return $db;
}

}
?>
