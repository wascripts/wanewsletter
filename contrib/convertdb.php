#!/usr/bin/php
<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 * 
 * Créé de nouvelles tables à partir des données présentes dans des
 * tables Wanewsletter d'une autre base de données (de type SQLite, MySQL ou PostgreSQL)
 * 
 * TODO : Les champs étrangers (champs personnalisés) ne sont pas pris en compte
 * La correction manuelle consiste à ajouter les descriptions des nouveaux champs dans
 * le fichier de schéma des tables correspondant dans ~/setup/schemas
 */

//
// Ceci est un fichier de test ou d'aide lors du développement. 
// Commentez les lignes suivantes uniquement si vous êtes sûr de ce que vous faites !
//
echo "This script has been disabled for security reasons\n";
exit(0);

//
// Configuration
//
define('WA_ROOTDIR', '..');

$schemas_dir  = WA_ROOTDIR . '/setup/schemas';

//$dsn = "<engine>://<username>:<password>@<host>:<port>/<database>";
$dsn_from     = 'mysql://username:password@localhost/dbname?charset=latin1';
$dsn_to       = 'sqlite:/path/to/db/wanewsletter.sqlite';

$prefixe_from = 'wa_';
$prefixe_to   = 'wa_';
//
// End Of Config
//

$prefixe   = '';// Pas touche. Empêche les notices PHP dans wadb_init.php sur les déclarations de constantes...

$tableList    = array('wa_abo_liste', 'wa_abonnes', 'wa_admin', 'wa_auth_admin', 'wa_ban_list',
	'wa_config', 'wa_joined_files', 'wa_forbidden_ext', 'wa_liste', 'wa_log', 'wa_log_files', 'wa_session'
);
$indexList    = array('abo_status_idx', 'admin_id_idx', 'config_name_idx', 'liste_id_idx', 'log_status_idx');
$sequenceList = array('wa_abonnes_id_seq', 'wa_admin_id_seq', 'wa_ban_id_seq',
	'wa_config_id_seq', 'wa_joined_files_id_seq', 'wa_liste_id_seq', 'wa_log_id_seq'
);

require WA_ROOTDIR . '/includes/functions.php';
require WA_ROOTDIR . '/includes/wadb_init.php';
require WA_ROOTDIR . '/includes/sql/sqlparser.php';

//
// Gestionnaire d'erreur spécifique
//
function wan_error_handler($errno, $errstr, $errfile, $errline)
{
	switch( $errno ) {
		case E_NOTICE: $errno = 'Notice'; break;
		case E_WARNING: $errno = 'Warning'; break;
		case E_ERROR: $errno = 'Error'; break;
		default: $errno = 'Unknown'; break;
	}
	
	if( error_reporting(E_ALL) ) {
		printf("%s : %s at line %d\n", $errno, strip_tags($errstr), $errline);
//		debug_print_backtrace();
		exit(1);
	}
}

set_error_handler('wan_error_handler');

if( php_sapi_name() != 'cli' ) {
	set_time_limit(0);
	header('Content-Type: text/plain; charset=ISO-8859-1');
}

//
// Connect to DB
//
$db_from = WaDatabase($dsn_from);
$db_to   = WaDatabase($dsn_to);

// DROP if any

// Postgresql sequences
if( $db_to->engine == 'postgres' ) {
	foreach( $sequenceList as $seqname ) {
		$db_to->query(sprintf('DROP SEQUENCE IF EXISTS %s',
			$db_to->quote(str_replace('wa_', $prefixe_to, $seqname))));
	}
}

foreach( $indexList as $indexname ) {
	$db_to->query(sprintf('DROP INDEX IF EXISTS %s',
		$db_to->quote(str_replace('wa_', $prefixe_to, $indexname))));
}

foreach( $tableList as $tablename ) {
	$db_to->query(sprintf('DROP TABLE IF EXISTS %s',
		$db_to->quote(str_replace('wa_', $prefixe_to, $tablename))));
}


// Create table
$sql_create = file_get_contents(sprintf('%s/%s_tables.sql', $schemas_dir, $db_to->engine));
$sql_create = parseSQL($sql_create);

foreach( $sql_create as $query ) {
	$query = str_replace('wa_', $prefixe_to, $query);
	$db_to->query($query);
}

//
// Si la base de données de destination est SQLite, on travaille en mémoire et
// on fait la copie sur disque à la fin, c'est beaucoup plus rapide.
//
// Voir ligne ~272 pour la 2e partie du boulot
//
if( $db_to->engine == 'sqlite' ) {
	$sqlite_db = $db_to->dbname;
	$db_to->close();
	$db_to = Wadatabase('sqlite::memory:');
	
	foreach( $sql_create as $query ) {
		$query = str_replace('wa_', $prefixe_to, $query);
		$db_to->query($query);
	}
}

function escape_data($value)
{
	global $db_to;
	
	if( is_null($value) ) {
		$value = 'NULL';
	}
	else {
		$value = "'" . $db_to->escape($value) . "'";
	}
	
	return $value;
}

function fields_list($tablename)
{
	global $db_to;
	
	$fields = array();
	
	if( $db_to->engine == 'mysql' ) {
		$result = $db_to->query(sprintf("SHOW COLUMNS FROM %s", $db_to->quote($tablename)));
		
		while( $row = $result->fetch() ) {
			array_push($fields, $row['Field']);
		}
	}
	else if( $db_to->engine == 'postgres' ) {
		$sql = "SELECT a.attname AS Field
			FROM pg_class c, pg_attribute a
			WHERE c.relname = '$tablename'
				AND a.attnum > 0
				AND a.attrelid = c.oid";
		$result = $db_to->query($sql);
		
		while( $row = $result->fetch() ) {
			array_push($fields, $row['Field']);
		}
	}
	else if( $db_to->engine == 'sqlite' ) {
		$result = $db_to->query(sprintf("PRAGMA table_info(%s)", $db_to->quote($tablename)));
		
		while( $row = $result->fetch() ) {
			array_push($fields, $row['name']);
		}
	}
	else {
		echo "Oops... WTF ?!\n";
		exit(1);
	}
	
	return $fields;
}

// Sequence postgresql
$sequence = array(
	'wa_abonnes'       => array('seqname' => 'wa_abonnes_id_seq', 'seqval' => 0, 'field' => 'abo_id'),
	'wa_admin'         => array('seqname' => 'wa_admin_id_seq', 'seqval' => 0, 'field' => 'admin_id'),
	'wa_ban_list'      => array('seqname' => 'wa_ban_id_seq', 'seqval' => 0, 'field' => 'ban_id'),
	'wa_config'        => array('seqname' => 'wa_config_id_seq', 'seqval' => 0, 'field' => 'config_id'),
	'wa_forbidden_ext' => array('seqname' => 'wa_forbidden_ext_id_seq', 'seqval' => 0, 'field' => 'fe_id'),
	'wa_joined_files'  => array('seqname' => 'wa_joined_files_id_seq', 'seqval' => 0, 'field' => 'file_id'),
	'wa_liste'         => array('seqname' => 'wa_liste_id_seq', 'seqval' => 0, 'field' => 'liste_id'),
	'wa_log'           => array('seqname' => 'wa_log_id_seq', 'seqval' => 0, 'field' => 'log_id')
);

// Populate table
foreach( $tableList as $tablename ) {
	printf("Populate table %s...\n", str_replace('wa_', $prefixe_to, $tablename));
	flush();
	
	$fields = implode(', ', fields_list($tablename));
	
	$result = $db_from->query(sprintf("SELECT %s FROM %s", $fields,
		$db_from->quote(str_replace('wa_', $prefixe_from, $tablename))));
	$result->setFetchMode($result->SQL_FETCH_ASSOC);
	
	$numrows = 0;
	
	if( $row = $result->fetch() ) {
		
		$fields = implode(', ', array_keys($row));
		
		do {
			$values = implode(", ", array_map('escape_data', $row));
			$res = $db_to->query(sprintf("INSERT INTO %s (%s) VALUES(%s)",
				$db_to->quote(str_replace('wa_', $prefixe_to, $tablename)), $fields, $values));
			$numrows++;
			
			if( !$res ) {
				printf("%s\n", $db_to->error);
				exit(1);
			}
			
			if( isset($sequence[$tablename]) && $row[$sequence[$tablename]['field']] > $sequence[$tablename]['seqval'] ) {
				$sequence[$tablename]['seqval'] = $row[$sequence[$tablename]['field']];
			}
		}
		while( $row = $result->fetch() );
		
		if( $db_to->engine == 'postgres' && isset($sequence[$tablename]) ) {
			$db_to->query(sprintf('ALTER SEQUENCE %s RESTART WITH %d',
				$db_to->quote(str_replace('wa_', $prefixe_to, $sequence[$tablename]['seqname'])),
				$sequence[$tablename]['seqval']+1));
		}
	}
	
	printf("%d rows added.\n", $numrows);
	flush();
}

if( $db_to->engine == 'sqlite' ) {
	$db_to->query(sprintf('ATTACH %s AS dest', $db_to->quote($sqlite_db)));
	
	foreach( $tableList as $tablename ) {
		$db_to->query(sprintf('INSERT INTO dest.%1$s SELECT * FROM %1$s',
			$db_to->quote(str_replace('wa_', $prefixe_to, $tablename))));
	}
	
	$db_to->query('DETACH dest');
}

$db_from->close();
$db_to->close();

echo "Your database has been successfully copied/converted!\n";
exit(0);
