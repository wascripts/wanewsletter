#!/usr/bin/php
<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 *
 * Créé de nouvelles tables à partir des données présentes dans des
 * tables Wanewsletter d'une autre base de données (de type SQLite, MySQL ou PostgreSQL)
 *
 * TODO : Les champs étrangers (champs personnalisés) ne sont pas pris en compte
 * La correction manuelle consiste à ajouter les descriptions des nouveaux champs dans
 * le fichier de schéma des tables correspondant dans ~/includes/sql/schemas
 */

namespace Wanewsletter;

//
// Ceci est un fichier de test ou d'aide lors du développement.
// Commentez les lignes suivantes uniquement si vous êtes sûr de ce que vous faites !
//
echo "This script has been disabled for security reasons\n";
exit(0);

//
// Configuration
//
define('WA_ROOTDIR', dirname(__DIR__));

$schemas_dir  = WA_ROOTDIR . '/includes/sql/schemas';

//$dsn = "<engine>://<username>:<password>@<host>:<port>/<database>";
$dsn_from     = 'mysql://username:password@localhost/dbname?charset=utf8';
$dsn_to       = 'sqlite:/path/to/db/wanewsletter.sqlite';

$prefixe_from = 'wa_';
$prefixe_to   = 'wa_';
//
// End Of Config
//

require WA_ROOTDIR . '/includes/common.inc.php';
require WA_ROOTDIR . '/includes/sql/sqlparser.php';

if (!check_cli()) {
	set_time_limit(0);
	header('Content-Type: text/plain; charset=UTF-8');
}

//
// Connect to DB
//
$db_from = WaDatabase($dsn_from);
$db_to   = WaDatabase($dsn_to);

// DROP if any

foreach ($sql_schemas as $tablename => $schema) {
	$db_to->query(sprintf('DROP TABLE IF EXISTS %s',
		$db_to->quote(str_replace('wa_', $prefixe_to, $tablename))
	));
}

// Create table
$sql_create = file_get_contents(sprintf('%s/%s_tables.sql', $schemas_dir, $db_to::ENGINE));
$sql_create = Dblayer\parseSQL($sql_create, $prefixe_to);

foreach ($sql_create as $query) {
	$db_to->query($query);
}

// On récupère les séquences PostgreSQL pour les initialiser correctement après les insertions
if ($db_to::ENGINE == 'postgres') {
	$sequences = [];

	$sql = "SELECT t.relname AS tablename, a.attname AS fieldname, s.relname AS seqname
		FROM pg_class s
			JOIN pg_depend d ON d.objid = s.oid
			JOIN pg_class t ON d.refobjid = t.oid
			JOIN pg_attribute a ON (d.refobjid, d.refobjsubid) = (a.attrelid, a.attnum)
			JOIN pg_namespace n ON n.oid = s.relnamespace
		WHERE s.relkind = 'S' AND n.nspname = 'public'";
	$res = $db_to->query($sql);

	while ($row = $res->fetch()) {
		if (isset($sql_schemas[$row['tablename']])) {
			$sequences[$row['tablename']] = [
				'seqname' => $row['seqname'],
				'seqval'  => 1,
				'field'   => $row['fieldname']
			];
		}
	}
}

//
// Si la base de données de destination est SQLite, on travaille en mémoire et
// on fait la copie sur disque à la fin, c'est beaucoup plus rapide.
//
if ($db_to::ENGINE == 'sqlite') {
	$sqlite_db = $db_to->dbname;
	$db_to->close();
	$db_to = Wadatabase('sqlite::memory:');

	foreach ($sql_create as $query) {
		$db_to->query($query);
	}
}

function fields_list($tablename)
{
	global $db_to;

	$fields = [];

	if ($db_to::ENGINE == 'mysql') {
		$result = $db_to->query(sprintf("SHOW COLUMNS FROM %s", $db_to->quote($tablename)));

		while ($row = $result->fetch()) {
			$fields[] = $row['Field'];
		}
	}
	else if ($db_to::ENGINE == 'postgres') {
		$sql = "SELECT a.attname AS field
			FROM pg_class c, pg_attribute a
			WHERE c.relname = '$tablename'
				AND a.attnum > 0
				AND a.attrelid = c.oid";
		$result = $db_to->query($sql);

		while ($row = $result->fetch()) {
			$fields[] = $row['field'];
		}
	}
	else if ($db_to::ENGINE == 'sqlite') {
		$result = $db_to->query(sprintf("PRAGMA table_info(%s)", $db_to->quote($tablename)));

		while ($row = $result->fetch()) {
			$fields[] = $row['name'];
		}
	}

	return $fields;
}

// Populate table
foreach ($sql_schemas as $tablename => $schema) {
	printf("Populate table %s...\n", str_replace('wa_', $prefixe_to, $tablename));
	flush();

	$fields = implode(', ', fields_list($tablename));

	$result = $db_from->query(sprintf("SELECT %s FROM %s", $fields,
		$db_from->quote(str_replace('wa_', $prefixe_from, $tablename))
	));
	$result->setFetchMode(WadbResult::FETCH_ASSOC);

	$numrows = 0;

	if ($row = $result->fetch()) {

		$fields = implode(', ', array_keys($row));

		do {
			$values = implode(', ', $db_to->prepareData($row));
			$res = $db_to->query(sprintf("INSERT INTO %s (%s) VALUES(%s)",
				$db_to->quote(str_replace('wa_', $prefixe_to, $tablename)),
				$fields,
				$values
			));
			$numrows++;

			if (!$res) {
				printf("%s\n", $db_to->error);
				exit(1);
			}

			if ($db_to::ENGINE == 'postgres' && isset($sequences[$tablename])) {
				$sequences[$tablename]['seqval'] = max(
					$sequences[$tablename]['seqval'],
					++$row[$sequences[$tablename]['field']]
				);
			}
		}
		while ($row = $result->fetch());

		if ($db_to::ENGINE == 'postgres' && isset($sequences[$tablename])) {
			$db_to->query(sprintf("SELECT setval('%s', %d, false)",
				$sequences[$tablename]['seqname'],
				$sequences[$tablename]['seqval']
			));
		}
	}

	printf("%d rows added.\n", $numrows);
	flush();
}

if ($db_to::ENGINE == 'sqlite') {
	$db_to->query(sprintf('ATTACH %s AS dest', $db_to->quote($sqlite_db)));

	foreach ($sql_schemas as $tablename => $schema) {
		$db_to->query(sprintf('INSERT INTO dest.%1$s SELECT * FROM %1$s',
			$db_to->quote(str_replace('wa_', $prefixe_to, $tablename))
		));
	}

	$db_to->query('DETACH dest');
}

$db_from->close();
$db_to->close();

echo "Your database has been successfully copied/converted!\n";
exit(0);
