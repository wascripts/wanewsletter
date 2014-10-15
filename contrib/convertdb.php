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
 * le fichier de schéma des tables correspondant dans ~/includes/sql/schemas
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

$schemas_dir  = WA_ROOTDIR . '/includes/sql/schemas';

//$dsn = "<engine>://<username>:<password>@<host>:<port>/<database>";
$dsn_from     = 'mysql://username:password@localhost/dbname?charset=latin1';
$dsn_to       = 'sqlite:/path/to/db/wanewsletter.sqlite';

$prefixe_from = 'wa_';
$prefixe_to   = 'wa_';
//
// End Of Config
//

if( php_sapi_name() != 'cli' ) {
	set_time_limit(0);
	header('Content-Type: text/plain; charset=ISO-8859-1');
}
else {
	define('IN_COMMANDLINE', true);
}

$prefixe = 'wa_';// Pas touche. Empêche les notices PHP dans wadb_init.php sur les déclarations de constantes...

require WA_ROOTDIR . '/includes/functions.php';
require WA_ROOTDIR . '/includes/wadb_init.php';
require WA_ROOTDIR . '/includes/sql/sqlparser.php';

//
// Connect to DB
//
$db_from = WaDatabase($dsn_from);
$db_to   = WaDatabase($dsn_to);

// DROP if any

foreach( $sql_schemas as $tablename => $schema ) {
	if( $db_to->engine == 'postgres' && !empty($schema['sequence']) )
	{
		foreach( $schema['sequence'] as $sequence )
		{
			$db_to->query(sprintf('DROP SEQUENCE IF EXISTS %s',
				$db_to->quote(str_replace('wa_', $prefixe_to, $sequence))
			));
		}
	}
	
	if( !empty($schema['index']) )
	{
		foreach( $schema['index'] as $index )
		{
			$db_to->query(sprintf('DROP INDEX IF EXISTS %s',
				$db_to->quote(str_replace('wa_', $prefixe_to, $index))
			));
		}
	}
	
	$db_to->query(sprintf('DROP TABLE IF EXISTS %s',
		$db_to->quote(str_replace('wa_', $prefixe_to, $tablename))
	));
}

// Create table
$sql_create = file_get_contents(sprintf('%s/%s_tables.sql', $schemas_dir, $db_to->engine));
$sql_create = parseSQL($sql_create, $prefixe_to);

foreach( $sql_create as $query ) {
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
	
	return $fields;
}

// Sequence postgresql
$sequence = array();

foreach( $sql_schemas as $tablename => $schema ) {
	if( !empty($schema['sequence']) ) {
		$seq = each($schema['sequence']);
		
		$sequence[$tablename] = array(
			'field'   => $seq['key'],
			'seqname' => $seq['value'],
			'seqval'  => 0
		);
	}
}

// Populate table
foreach( $sql_schemas as $tablename => $schema ) {
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
	
	foreach( $sql_schemas as $tablename => $schema ) {
		$db_to->query(sprintf('INSERT INTO dest.%1$s SELECT * FROM %1$s',
			$db_to->quote(str_replace('wa_', $prefixe_to, $tablename))));
	}
	
	$db_to->query('DETACH dest');
}

$db_from->close();
$db_to->close();

echo "Your database has been successfully copied/converted!\n";
exit(0);
