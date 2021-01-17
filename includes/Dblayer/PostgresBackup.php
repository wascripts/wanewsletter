<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@webnaute.net>
 * @link      http://dev.webnaute.net/wanewsletter/
 * @copyright 2002-2021 Aurélien Maille
 * @license   https://www.gnu.org/licenses/gpl.html  GNU General Public License
 *
 * Certaines parties sont basées sur phpPgAdmin 2.4.2
 */

namespace Wanewsletter\Dblayer;

class PostgresBackup extends WadbBackup
{
	public function header($toolname = '')
	{
		$contents  = '-- ' . $this->eol;
		$contents .= "-- $toolname PostgreSQL Dump" . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= "-- Host     : " . $this->db->host . $this->eol;
		$contents .= "-- Server   : " . $this->db->serverVersion . $this->eol;
		$contents .= "-- Database : " . $this->db->dbname . $this->eol;
		$contents .= '-- Date     : ' . date(DATE_RFC2822) . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= $this->eol;

		$contents .= sprintf("SET NAMES '%s';%s", $this->db->encoding(), $this->eol);
		$contents .= "SET standard_conforming_strings = off;" . $this->eol;
		$contents .= "SET escape_string_warning = off;" . $this->eol;
		$contents .= $this->eol;

		return $contents;
	}

	public function getTablesList()
	{
		$sql = "SELECT tablename
			FROM pg_tables
			WHERE NOT tablename ~ '^(pg|sql)_'
			ORDER BY tablename";
		$result = $this->db->query($sql);
		$tables = [];

		while ($row = $result->fetch()) {
			$tables[] = $row['tablename'];
		}

		return $tables;
	}

	public function getStructure($tablename, $drop_option)
	{
		$contents  = '';
		$sequences = [];

		$sql = "SELECT a.attname AS fieldname, s.relname AS seqname
			FROM pg_class s
				JOIN pg_depend d ON d.objid = s.oid
				JOIN pg_class t ON t.relname = '%s' AND d.refobjid = t.oid
				JOIN pg_attribute a ON (d.refobjid, d.refobjsubid) = (a.attrelid, a.attnum)
				JOIN pg_namespace n ON n.oid = s.relnamespace
			WHERE s.relkind = 'S' AND n.nspname = 'public'";
		$sql = sprintf($sql, $this->db->escape($tablename));
		$result = $this->db->query($sql);

		while ($row = $result->fetch()) {
			$sql = sprintf('SELECT * FROM %s', $this->db->quote($row['seqname']));
			$result_seq = $this->db->query($sql);

			if ($seq = $result_seq->fetch()) {
				if (!isset($sequences[$tablename])) {
					$sequences[$tablename] = [];
				}
				$sequences[$tablename][$row['fieldname']] = $seq;
			}
		}

		$contents .= '--' . $this->eol;
		$contents .= '-- Structure de la table ' . $tablename . $this->eol;
		$contents .= '--' . $this->eol;

		if ($drop_option) {
			$contents .= sprintf("DROP TABLE IF EXISTS %s;%s", $this->db->quote($tablename), $this->eol);
		}

		if (isset($sequences[$tablename])) {
			$contents .= $this->eol;

			foreach ($sequences[$tablename] as $seq) {
				// Création de la séquence
				$contents .= sprintf("CREATE SEQUENCE %s start %d increment %d maxvalue %d minvalue %d cache %d;%s",
					$this->db->quote($seq['sequence_name']),
					$seq['start_value'],
					$seq['increment_by'],
					$seq['max_value'],
					$seq['min_value'],
					$seq['cache_value'],
					$this->eol
				);

				// Initialisation à sa valeur courante
				$last_value = $seq['last_value'];
				if ($seq['is_called'] == 't') {
					$last_value++;
				}

				$contents .= sprintf("SELECT setval('%s', %d, false);%s",
					$seq['sequence_name'],
					$last_value,
					$this->eol
				);
			}

			$contents .= $this->eol;
		}

		$sql = "SELECT a.attnum, a.attname AS field, t.typname as type, a.attlen AS length,
				a.atttypmod as lengthvar, a.attnotnull as notnull
			FROM pg_class c, pg_attribute a, pg_type t
			WHERE c.relname = '%s'
				AND a.attnum > 0
				AND a.attrelid = c.oid
				AND a.atttypid = t.oid
			ORDER BY a.attnum";
		$sql = sprintf($sql, $this->db->escape($tablename));
		$result = $this->db->query($sql);

		$contents .= sprintf("CREATE TABLE %s (%s", $this->db->quote($tablename), $this->eol);

		while ($row = $result->fetch()) {
			if ($row['notnull'] == 't') {
				$sql = "SELECT d.adsrc AS rowdefault
					FROM pg_attrdef d, pg_class c
					WHERE (c.relname = '%s')
						AND (c.oid = d.adrelid)
						AND d.adnum = %d";
				$sql = sprintf($sql, $this->db->escape($tablename), $row['attnum']);
				$res = $this->db->query($sql);
				$row['rowdefault'] = $res->column('rowdefault');
			}

			if ($row['type'] == 'bpchar') {
				// Internally stored as bpchar, but isn't accepted in a CREATE TABLE statement.
				$row['type'] = 'character';
			}

			$contents .= ' ' . $this->db->quote($row['field']) . ' ' . $row['type'];

			if (preg_match('#char#i', $row['type']) && $row['lengthvar'] > 0) {
				$contents .= '(' . ($row['lengthvar'] - 4) . ')';
			}
			else if (preg_match('#numeric#i', $row['type'])) {
				$contents .= sprintf('(%s,%s)',
					(($row['lengthvar'] >> 16) & 0xffff),
					(($row['lengthvar'] - 4) & 0xffff)
				);
			}

			if ($row['notnull'] == 't') {
				$contents .= ' DEFAULT ' . $row['rowdefault'];
				$contents .= ' NOT NULL';
			}

			$contents .= ',' . $this->eol;
		}

		//
		// Generate constraint clauses for UNIQUE and PRIMARY KEY constraints
		//
		$sql = "SELECT ic.relname AS index_name, bc.relname AS tab_name, ta.attname AS column_name,
				i.indisunique AS unique_key, i.indisprimary AS primary_key
			FROM pg_class bc, pg_class ic, pg_index i, pg_attribute ta, pg_attribute ia
			WHERE (bc.oid = i.indrelid)
				AND (ic.oid = i.indexrelid)
				AND (ia.attrelid = i.indexrelid)
				AND (ta.attrelid = bc.oid)
				AND (bc.relname = '%s')
				AND (ta.attrelid = i.indrelid)
				AND (ta.attnum = i.indkey[ia.attnum-1])
			ORDER BY index_name, tab_name, column_name";
		$sql = sprintf($sql, $this->db->escape($tablename));
		$result = $this->db->query($sql);

		$primary_key_name = '';
		$primary_key_fields = [];
		$index_rows  = [];

		while ($row = $result->fetch()) {
			if ($row['primary_key'] == 't') {
				$primary_key_fields[] = $row['column_name'];
				$primary_key_name = $row['index_name'];
			}
			else {
				//
				// We have to store this all this info because it is possible to have a multi-column key...
				// we can loop through it again and build the statement
				//
				$index_rows[$row['index_name']]['table']  = $tablename;
				$index_rows[$row['index_name']]['unique'] = ($row['unique_key'] == 't') ? 'UNIQUE' : '';

				if (!isset($index_rows[$row['index_name']]['column_names'])) {
					$index_rows[$row['index_name']]['column_names'] = [];
				}

				$index_rows[$row['index_name']]['column_names'][] = $row['column_name'];
			}
		}
		$result->free();

		if (!empty($primary_key_name)) {
			$primary_key_fields = array_map([$this->db, 'quote'], $primary_key_fields);
			$contents .= sprintf("CONSTRAINT %s PRIMARY KEY (%s),%s",
				$this->db->quote($primary_key_name),
				implode(', ', $primary_key_fields),
				$this->eol
			);
		}

		$index_create = '';

		if (count($index_rows) > 0) {
			foreach ($index_rows as $idx_name => $props) {
				$props['column_names'] = array_map([$this->db, 'quote'], $props['column_names']);
				$props['column_names'] = implode(', ', $props['column_names']);

				if (!empty($props['unique'])) {
					$contents .= sprintf("CONSTRAINT %s UNIQUE (%s),%s",
						$this->db->quote($idx_name),
						$props['column_names'],
						$this->eol
					);
				}
				else {
					$index_create .= sprintf("CREATE %s INDEX %s ON %s (%s);%s",
						$props['unique'],
						$this->db->quote($idx_name),
						$this->db->quote($tablename),
						$props['column_names'],
						$this->eol
					);
				}
			}
		}

		//
		// Generate constraint clauses for CHECK constraints
		//
/*		$sql = "SELECT rcname as index_name, rcsrc
			FROM pg_relcheck, pg_class bc
			WHERE rcrelid = bc.oid
				AND bc.relname = '%s'
				AND NOT EXISTS (
					SELECT *
					FROM pg_relcheck as c, pg_inherits as i
					WHERE i.inhrelid = pg_relcheck.rcrelid
						AND c.rcname = pg_relcheck.rcname
						AND c.rcsrc = pg_relcheck.rcsrc
						AND c.rcrelid = i.inhparent
			)";
		$sql = sprintf($sql, $this->db->escape($tablename));
		$result = $this->db->query($sql);

		//
		// Add the constraints to the sql file.
		//
		while ($row = $result->fetch()) {
			$contents .= sprintf("CONSTRAINT %s CHECK %s,%s",
				$this->db->quote($row['index_name']),
				$row['rcsrc'],
				$this->eol
			);
		}*/

		$len = strlen(',' . $this->eol);
		$contents = substr($contents, 0, -$len);
		$contents .= $this->eol . ');' . $this->eol;

		if (!empty($index_create)) {
			$contents .= $index_create;
		}

		if (isset($sequences[$tablename])) {
			// Rattachement des séquences sur les champs liés
			foreach ($sequences[$tablename] as $field => $seq) {
				$contents .= sprintf("ALTER SEQUENCE %s OWNED BY %s.%s;%s",
					$this->db->quote($seq['sequence_name']),
					$this->db->quote($tablename),
					$this->db->quote($field),
					$this->eol
				);
			}
		}

		return $contents . $this->eol;
	}
}
