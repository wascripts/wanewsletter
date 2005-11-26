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
 * @version $Id: subscribe.php 148 2005-11-12 15:10:35Z bobe $
 */

echo "This module has been disabled for security reasons\n";
exit(0);

//
// Configuration
//
define('WA_ROOTDIR', '/home/web/projects/wanewsletter/branche_2.3');

$mysql_prefix = 'mysql_';
$sqlite_db    = WA_ROOTDIR . '/sql/wanewsletter.db';
$remove_db    = true;
$schemas_dir  = WA_ROOTDIR . '/setup/schemas';
//
// End Of Config
//

require WA_ROOTDIR . '/includes/config.inc.php';
require WA_ROOTDIR . '/includes/functions.php';
require WA_ROOTDIR . '/includes/constantes.php';
require WA_ROOTDIR . '/sql/sqlite.php';

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
	
	printf("%s : %s at line %d\n", $errno, $errstr, $errline);
}

set_error_handler('wan_cli_error');

if( php_sapi_name() != 'cli' ) {
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

$sql_connect  = $mysql_prefix . 'connect';
$sql_selectdb = $mysql_prefix . 'select_db';
$sql_query    = $mysql_prefix . 'query';
$sql_fetchrow = $mysql_prefix . 'fetch_array';
$sql_num_rows = $mysql_prefix . 'num_rows';

//
// Initialisation de la base de données
//
$db =& new sql($sqlite_db);

if( !is_resource($db->connect_id) ) {
	echo "Unable to create SQLite DB\n";
	exit(0);
}

chmod($sqlite_db, 0666);

//
// Création de la structure de base
//
$sqldata = file_get_contents($schemas_dir . '/sqlite_tables.sql');
$queries = make_sql_ary($sqldata, ';');

foreach( $queries AS $query ) {
	$db->query($query);
}

//
// Injection des données en provenance de la base MySQL
//
$sql_connect($dbhost, $dbuser, $dbpassword);
$sql_selectdb($dbname);

$tableList = array(
	'wa_abo_liste', 'wa_abonnes', 'wa_admin', 'wa_auth_admin', 'wa_ban_list', 'wa_config',
	'wa_forbidden_ext', 'wa_joined_files', 'wa_liste', 'wa_log', 'wa_log_files'
);

foreach( $tableList AS $table ) {
	$table  = str_replace('wa_', $prefixe, $table);
	printf("Populate table %s...\n", $table);
	
	$result = $sql_query('SELECT * FROM ' . $table);
	
	while( $row = @$sql_fetchrow($result, MYSQL_ASSOC) ) {
		$db->query_build('INSERT', $table, $row);
	}
	
	printf("%d rows added.\n", $sql_num_rows($result));
}

echo "SQLite database has been successfully initialized!\n";
exit(0);


