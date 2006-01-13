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
 * 
 * Créé une base de données SQLite à partir des données présentes dans des
 * tables Wanewsletter d'une autre base de données (de type MySQL ou PostgreSQL)
 */

//
// Ceci est un fichier de test ou d'aide lors du développement. 
// Commentez les lignes suivantes uniquement si vous êtes sùr de ce que vous faites !
//
echo "This script has been disabled for security reasons\n";
exit(0);


//
// Configuration
//
define('IN_NEWSLETTER', true);
define('WA_ROOTDIR', '..');

$sqlite_db   = WA_ROOTDIR . '/includes/sql/wanewsletter.sqlite';
$schemas_dir = WA_ROOTDIR . '/setup/schemas';
$remove_db   = true;
//
// End Of Config
//

chdir(dirname(__FILE__));

require WA_ROOTDIR . '/includes/config.inc.php';
require WA_ROOTDIR . '/includes/functions.php';
require WA_ROOTDIR . '/includes/constantes.php';
require WA_ROOTDIR . '/includes/wadb_init.php';

//
// Gestionnaire d'erreur spécifique pour utilisation en ligne de commande
//
function wan_cli_error($errno, $errstr, $errfile, $errline)
{
	switch( $errno ) {
		case E_NOTICE: $errno = 'Notice'; break;
		case E_WARNING: $errno = 'Warning'; break;
		case E_ERROR: $errno = 'Error'; break;
	}
	
	printf("%s : %s at line %d\n", $errno, strip_tags($errstr), $errline);
}

set_error_handler('wan_cli_error');

if( php_sapi_name() != 'cli' ) {
	set_time_limit(0);
	header('Content-Type: text/plain; charset=ISO-8859-1');
}

$sqlite_dir = dirname($sqlite_db);
if( !is_writable($sqlite_dir) ) {
	echo "Error: $sqlite_dir directory is not writable\n";
	exit(0);
}

if( $remove_db == true && file_exists($sqlite_db) ) {
	unlink($sqlite_db);
}

//
// Initialisation de la base de données
//
try {
	$pdo = new PDO('sqlite:' . $sqlite_db);
}
catch( PDOException $e ) {
	printf("Unable to create SQLite DB (%s)\n", $e->getMessage());
	exit(0);
}

chmod($sqlite_db, 0666);

//
// Création de la structure de base
//
$sqldata = file_get_contents($schemas_dir . '/sqlite_tables.sql');
$queries = make_sql_ary($sqldata, ';');

foreach( $queries as $query ) {
	$pdo->query($query);
}

//
// Injection des données en provenance de la base MySQL
//
$db = WaDatabase($dsn);

$tableList = array(
	'wa_abo_liste', 'wa_abonnes', 'wa_admin', 'wa_auth_admin', 'wa_ban_list', 'wa_config',
	'wa_forbidden_ext', 'wa_joined_files', 'wa_liste', 'wa_log', 'wa_log_files'
);
$tableList = str_replace('wa_', $prefixe, $tableList);

foreach( $tableList as $table ) {
	printf("Populate table %s...\n", $table);
	flush();
	
	$fields = array();
	$result = $pdo->query("PRAGMA table_info($table)");
	
	while( $row = $result->fetch() ) {
		array_push($fields, $row['name']);
	}
	$fields = implode(', ', $fields);
	
	$result = $db->query("SELECT $fields FROM $table");
	$result->setFetchMode(SQL_FETCH_NUM);
	
	while( $result->hasMore() ) {
		$row = $result->fetch();
		$values = array();
		
		foreach( $row as $value ) {
			if( is_null($value) ) {
				$value = 'NULL';
			}
			else {
				$value = $pdo->quote($value);
			}
			
			array_push($values, $value);
		}
		
		$pdo->query("INSERT INTO $table ($fields) VALUES(" . implode(", ", $values) . ")\n");
	}
	
	printf("%d rows added.\n", $result->count());
	flush();
}

unset($pdo);
$db->close();

echo "\nSQLite database has been successfully initialized!\n";
exit(0);


