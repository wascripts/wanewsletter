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

class SQLException extends Exception { }

//
// Tables du script 
//
define('ABO_LISTE_TABLE',     $prefixe . 'abo_liste');
define('ABONNES_TABLE',       $prefixe . 'abonnes');
define('ADMIN_TABLE',         $prefixe . 'admin');
define('AUTH_ADMIN_TABLE',    $prefixe . 'auth_admin');
define('BANLIST_TABLE',       $prefixe . 'ban_list');
define('CONFIG_TABLE',        $prefixe . 'config');
define('FORBIDDEN_EXT_TABLE', $prefixe . 'forbidden_ext');
define('JOINED_FILES_TABLE',  $prefixe . 'joined_files');
define('LISTE_TABLE',         $prefixe . 'liste');
define('LOG_TABLE',           $prefixe . 'log');
define('LOG_FILES_TABLE',     $prefixe . 'log_files');
define('SESSIONS_TABLE',      $prefixe . 'session');

$GLOBALS['supported_db'] = array(
	'mysql' => array(
		'Name'         => 'MySQL &#8805; 5.0.7',
		'extension'    => (extension_loaded('mysql') || extension_loaded('mysqli'))
	),
	'postgres' => array(
		'Name'         => 'PostgreSQL &#8805; 8.x, 9.x',
		'extension'    => extension_loaded('pgsql')
	),
	'sqlite' => array(
		'Name'         => 'SQLite &#8805; 2.8, 3.x',
		'extension'    => (class_exists('SQLite3') || extension_loaded('sqlite') || (extension_loaded('pdo') && extension_loaded('pdo_sqlite')))
	)
);

$GLOBALS['sql_schemas'] = array(
	ABO_LISTE_TABLE     => array(
		'index'    => array('register_key_idx')
	),
	ABONNES_TABLE       => array(
		'index'    => array('abo_email_idx', 'abo_status_idx'),
		'sequence' => array('wa_abonnes_id_seq')
	),
	ADMIN_TABLE         => array(
		'sequence' => array('wa_admin_id_seq')
	),
	AUTH_ADMIN_TABLE    => array(
		'index'    => array('admin_id_idx')
	),
	BANLIST_TABLE       => array(
		'sequence' => array('wa_ban_id_seq')
	),
	CONFIG_TABLE        => array(
		'index'    => array('config_name_idx'),
		'sequence' => array('wa_config_id_seq')
	),
	FORBIDDEN_EXT_TABLE => array(
		'sequence' => array('wa_forbidden_ext_id_seq')
	),
	JOINED_FILES_TABLE  => array(
		'sequence' => array('wa_joined_files_id_seq')
	),
	LISTE_TABLE         => array(
		'sequence' => array('wa_liste_id_seq')
	),
	LOG_TABLE           => array(
		'index'    => array('liste_id_idx', 'log_status_idx'),
		'sequence' => array('wa_log_id_seq')
	),
	LOG_FILES_TABLE     => array(),
	SESSIONS_TABLE      => array()
);

/**
 * Génère une chaîne DSN
 * 
 * @param array $infos    Informations sur l'accès à la base de données
 * @param array $options  Options de connexion
 */
function createDSN($infos, $options = null)
{
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
		$dsn = sprintf('%s://%s/%s', $infos['engine'], $connect, $infos['dbname']);
	}
	else {
		$dsn = sprintf('%s:%s', $infos['engine'], $infos['dbname']);
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
					trigger_error("Unsupported database", E_USER_ERROR);
					return false;
				}
				else {
					$infos['label']  = $label[$value];
					$infos['engine'] = $value;
					
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
				
				if( $infos['engine'] != 'sqlite' && isset($infos['host']) ) {
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
	
	if( $infos['engine'] == 'sqlite' ) {
		if( class_exists('SQLite3') ) {
			$infos['driver'] = 'sqlite3';
		}
		else if( extension_loaded('pdo') && extension_loaded('pdo_sqlite') ) {
			$infos['driver'] = 'sqlite_pdo';
		}
		else if( !extension_loaded('sqlite') ) {
			trigger_error("No SQLite3, PDO/SQLite or SQLite extension loaded !", E_USER_ERROR);
		}
		
		if( is_readable($infos['dbname']) && filesize($infos['dbname']) > 0 ) {
			$fp = fopen($infos['dbname'], 'rb');
			$info = fread($fp, 15);
			fclose($fp);
			
			if( strcmp($info, 'SQLite format 3') == 0 ) {
				if( $infos['driver'] == 'sqlite' ) {
					trigger_error("No SQLite3 or PDO/SQLite extension loaded !", E_USER_ERROR);
				}
			}
			else if( $infos['driver'] != 'sqlite' ) {
				if( !extension_loaded('sqlite') ) {
					trigger_error("SQLite extension isn't loaded !", E_USER_ERROR);
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
		trigger_error("Invalid DSN argument", E_USER_ERROR);
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
	
	if( $db->isConnected() &&  $db->engine != 'sqlite' && ($encoding = $db->encoding())
		&& preg_match('#^UTF-?(8|16)|UCS-?2|UNICODE$#i', $encoding) )
	{
		/*
		 * WorkAround : Wanewsletter ne gère pas les codages de caractères multi-octets.
		 * Si le jeu de caractères de la connexion est multi-octet, on le change
		 * arbitrairement pour le latin1 et on affiche une alerte à l'utilisateur
		 * en cas d'échec.
		 */
		$newEncoding = 'latin1';
		$db->encoding($newEncoding);
		
		if( strcasecmp($encoding, $db->encoding()) === 0 ) {
			global $output;
			
			$message = <<<ERR
Wanewsletter a détecté que le <strong>jeu de caractères</strong>
de connexion à votre base de données est <q>$encoding</q>.
Wanewsletter ne gère pas les codages de caractères multi-octets et a donc tenté
de changer ce réglage en faveur du jeu de caractères <q>$newEncoding</q>, mais sans succès.<br />
Consultez la documentation de votre base de données pour trouver le réglage adéquat
et définir le paramètre charset dans la variable \$dsn du fichier de configuration
(consultez le fichier config.sample.inc.php pour voir un exemple de DSN configuré
de cette manière)."
ERR;
			$output->displayMessage(str_replace("\n", " ", $message));
		}
	}
	
	return $db;
}

/**
 * Exécute une ou plusieurs requètes SQL sur la base de données
 *
 * @param mixed $queries  Une ou plusieurs requètes SQL à exécuter
 */
function exec_queries(&$queries)
{
	global $db;
	
	if( !is_array($queries) )
	{
		$queries = array($queries);
	}
	
	foreach( $queries as $query )
	{
		if( !empty($query) )
		{
			$db->query($query);
		}
	}
	
	$queries = array();
}

/**
 * SQLite a un support très limité de la commande ALTER TABLE
 * Impossible de modifier ou supprimer une colonne donnée
 * On réécrit les tables dont la structure a changé
 *
 * @param string $tablename  Nom de la table à recréer
 */
function wa_sqlite_recreate_table($tablename)
{
	global $db, $prefixe, $sql_create, $sql_schemas;
	
	$schema = &$sql_schemas[$tablename];
	
	if( !empty($schema['updated']) )
	{
		return null;
	}
	
	$schema['updated'] = true;
	$columns = array();
	
	$result = $db->query(sprintf("PRAGMA table_info(%s)", $db->quote($tablename)));
	while( $row = $result->fetch() )
	{
		$columns[] = $row['name'];
	}
	
	$sql_update   = array();
	
	if( isset($schema['index']) )
	{
		foreach( $schema['index'] as $index )
		{
			$sql_update[] = sprintf("DROP INDEX IF EXISTS %s",
				str_replace('wa_', $prefixe, $index)
			);
		}
	}
	
	$sql_update[] = sprintf('ALTER TABLE %1$s RENAME TO %1$s_tmp;', $tablename);
	$sql_update   = array_merge($sql_update, $sql_create[$tablename]);
	$sql_update[] = sprintf('INSERT INTO %1$s (%2$s) SELECT %2$s FROM %1$s_tmp;',
		$tablename,
		implode(',', $columns)
	);
	$sql_update[] = sprintf('DROP TABLE %s_tmp;', $tablename);
	
	exec_queries($sql_update);
}

}
