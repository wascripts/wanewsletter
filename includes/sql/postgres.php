<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

class Wadb_postgres extends Wadb
{
	/**
	 * Type de base de données
	 *
	 * @var string
	 */
	public $engine = 'postgres';

	/**
	 * Version du serveur
	 *
	 * @var string
	 */
	public $serverVersion = '';

	/**
	 * Version du client
	 *
	 * @var string
	 */
	public $clientVersion = '';

	/**
	 * Nombre de lignes affectées par la dernière requète DML
	 *
	 * @var integer
	 */
	protected $_affectedRows = 0;

	public function connect($infos = null, $options = null)
	{
		$connectString = '';

		if (is_array($infos)) {
			foreach (array('host', 'username', 'passwd', 'port', 'dbname') as $info) {
				if (isset($infos[$info])) {
					if ($info == 'username') {
						$connectString .= "user='$infos[$info]' ";
					}
					else if ($info == 'passwd') {
						$connectString .= "password='$infos[$info]' ";
					}
					else {
						$connectString .= "$info='$infos[$info]' ";
					}
				}
			}

			$this->host   = $infos['host'] . (!empty($infos['port']) ? ':'.$infos['port'] : '');
			$this->dbname = $infos['dbname'];
		}

		$connect = 'pg_connect';

		if (is_array($options)) {
			$this->options = array_merge($this->options, $options);
		}

		if (!empty($this->options['persistent'])) {
			$connect = 'pg_pconnect';
		}

		if (!($this->link = $connect($connectString)) || pg_connection_status($this->link) !== PGSQL_CONNECTION_OK) {
			$tmp = wan_error_get_last();
			$this->errno = -1;
			$this->error = $tmp['message'];
			$this->link  = null;

			throw new SQLException($this->error, $this->errno);
		}
		else {
			$tmp = pg_version($this->link);
			$this->clientVersion = $tmp['client'];
			$this->serverVersion = $tmp['server'];

			if (!empty($this->options['charset'])) {
				$this->encoding($this->options['charset']);
			}
		}
	}

	public function encoding($encoding = null)
	{
		$curEncoding = pg_client_encoding($this->link);

		if (!is_null($encoding)) {
			pg_set_client_encoding($this->link, $encoding);
		}

		return $curEncoding;
	}

	public function query($query)
	{
		$curtime = array_sum(explode(' ', microtime()));
		$result  = pg_send_query($this->link, $query);
		$endtime = array_sum(explode(' ', microtime()));

		$this->sqltime += ($endtime - $curtime);
		$this->lastQuery = $query;
		$this->queries++;

		if ($result) {
			$result   = pg_get_result($this->link);
			$sqlstate = pg_result_error_field($result, PGSQL_DIAG_SQLSTATE);

			if (0 == $sqlstate) {
				$this->errno = 0;
				$this->error = '';

				if (in_array(strtoupper(substr($query, 0, 6)), array('INSERT', 'UPDATE', 'DELETE'))) {
					$this->_affectedRows = pg_affected_rows($result);
					$result = true;
				}
				else {
					$result = new WadbResult_postgres($result);
				}

				return $result;
			}
			else {
				$this->errno = $sqlstate;
				$this->error = pg_result_error_field($result, PGSQL_DIAG_MESSAGE_PRIMARY);

				$this->rollBack();
			}
		}
		else {
			$this->errno = -1;
			$this->error = 'Unknown error with database';
		}

		throw new SQLException($this->error, $this->errno);
	}

	public function quote($name)
	{
		return '"' . $name . '"';
	}

	public function vacuum($tables)
	{
		if (!is_array($tables)) {
			$tables = array($tables);
		}

		foreach ($tables as $tablename) {
			pg_query($this->link, 'VACUUM ' . $tablename);
		}
	}

	public function beginTransaction()
	{
		return pg_query($this->link, 'BEGIN');
	}

	public function commit()
	{
		if (!($result = pg_query($this->link, 'COMMIT'))) {
			pg_query($this->link, 'ROLLBACK');
		}

		return $result;
	}

	public function rollBack()
	{
		return pg_query($this->link, 'ROLLBACK');
	}

	public function affectedRows()
	{
		return $this->_affectedRows;
	}

	public function lastInsertId()
	{
		if (preg_match('/^INSERT\s+INTO\s+([^\s]+)\s+/i', $this->lastQuery, $m)) {
			$result = pg_query($this->link, "SELECT currval('{$m[1]}_id_seq') AS lastId");

			if (is_resource($result)) {
				return pg_fetch_result($result, 0, 'lastId');
			}
		}

		return false;
	}

	public function escape($string)
	{
		return pg_escape_string($string);
	}

	public function ping()
	{
		return pg_ping($this->link);
	}

	public function close()
	{
		if (!is_null($this->link)) {
			@$this->rollBack();
			$result = pg_close($this->link);
			$this->link = null;

			return $result;
		}
		else {
			return true;
		}
	}

	public function initBackup()
	{
		return new WadbBackup_postgres($this);
	}
}

class WadbResult_postgres extends WadbResult
{
	public function fetch($mode = null)
	{
		$modes = array(
			self::FETCH_NUM   => PGSQL_NUM,
			self::FETCH_ASSOC => PGSQL_ASSOC,
			self::FETCH_BOTH  => PGSQL_BOTH
		);

		return pg_fetch_array($this->result, null, $this->getFetchMode($modes, $mode));
	}

	public function fetchObject()
	{
		return pg_fetch_object($this->result);
	}

	public function column($column)
	{
		$row = pg_fetch_array($this->result);

		return (is_array($row) && isset($row[$column])) ? $row[$column] : false;
	}

	public function free()
	{
		if (!is_null($this->result)) {
			pg_free_result($this->result);
			$this->result = null;
		}
	}
}

/**
 * Certaines parties sont basées sur phpPgAdmin 2.4.2
 */
class WadbBackup_postgres extends WadbBackup
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

	public function get_tables()
	{
		$sql = "SELECT tablename
			FROM pg_tables
			WHERE NOT tablename ~ '^(pg|sql)_'
			ORDER BY tablename";
		$result = $this->db->query($sql);
		$tables = array();

		while ($row = $result->fetch()) {
			$tables[$row['tablename']] = '';
		}

		return $tables;
	}

	public function get_other_queries($drop_option)
	{
		global $backup_type;

		$contents  = '-- ' . $this->eol;
		$contents .= '-- Sequences ' . $this->eol;
		$contents .= '-- ' . $this->eol;

		$sql = "SELECT relname
			FROM pg_class
			WHERE NOT relname ~ '^pg_.*' AND relkind ='S'
			ORDER BY relname";
		$result = $this->db->query($sql);

		$contents = '';
		while ($sequence = $result->column('relname')) {

			$result_seq = $this->db->query('SELECT * FROM ' . $this->db->quote($sequence));

			if ($row = $result_seq->fetch()) {
				if ($drop_option) {
					$contents .= "DROP SEQUENCE IF EXISTS ".$this->db->quote($sequence).";" . $this->eol;
				}

				$contents .= 'CREATE SEQUENCE ' . $this->db->quote($sequence)
					. ' start ' . $row['last_value']
					. ' increment ' . $row['increment_by']
					. ' maxvalue ' . $row['max_value']
					. ' minvalue ' . $row['min_value']
					. ' cache ' . $row['cache_value'] . '; ' . $this->eol;

				if ($row['last_value'] > 1 && $backup_type != 1) {
					//$contents .= 'SELECT NEXTVAL(\'' . $sequence . '\'); ' . $this->eol;
				}
			}
		}

		return $contents . $this->eol;
	}

	public function get_table_structure($tabledata, $drop_option)
	{
		$contents  = '-- ' . $this->eol;
		$contents .= '-- Structure de la table ' . $tabledata['name'] . $this->eol;
		$contents .= '-- ' . $this->eol;

		if ($drop_option) {
			$contents .= 'DROP TABLE IF EXISTS ' . $this->db->quote($tabledata['name']) . ';' . $this->eol;
		}

		$sql = "SELECT a.attnum, a.attname AS field, t.typname as type, a.attlen AS length,
				a.atttypmod as lengthvar, a.attnotnull as notnull
			FROM pg_class c, pg_attribute a, pg_type t
			WHERE c.relname = '" . $tabledata['name'] . "'
				AND a.attnum > 0
				AND a.attrelid = c.oid
				AND a.atttypid = t.oid
			ORDER BY a.attnum";
		$result = $this->db->query($sql);

		$contents .= 'CREATE TABLE ' . $this->db->quote($tabledata['name']) . ' (' . $this->eol;

		while ($row = $result->fetch()) {
			$sql = "SELECT d.adsrc AS rowdefault
				FROM pg_attrdef d, pg_class c
				WHERE (c.relname = '" . $tabledata['name'] . "')
					AND (c.oid = d.adrelid)
					AND d.adnum = " . $row['attnum'];
			try {
				$res = $this->db->query($sql);
				$row['rowdefault'] = $res->column('rowdefault');
			}
			catch (Exception $e) {
				unset($row['rowdefault']);
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
				AND (bc.relname = '" . $tabledata['name'] . "')
				AND (ta.attrelid = i.indrelid)
				AND (ta.attnum = i.indkey[ia.attnum-1])
			ORDER BY index_name, tab_name, column_name";
		$result = $this->db->query($sql);

		$primary_key_name = '';
		$primary_key_fields = array();
		$index_rows  = array();

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
				$index_rows[$row['index_name']]['table']  = $tabledata['name'];
				$index_rows[$row['index_name']]['unique'] = ($row['unique_key'] == 't') ? 'UNIQUE' : '';

				if (!isset($index_rows[$row['index_name']]['column_names'])) {
					$index_rows[$row['index_name']]['column_names'] = array();
				}

				$index_rows[$row['index_name']]['column_names'][] = $row['column_name'];
			}
		}
		$result->free();

		if (!empty($primary_key_name)) {
			$primary_key_fields = array_map(array($this->db, 'quote'), $primary_key_fields);
			$contents .= sprintf("CONSTRAINT %s PRIMARY KEY (%s),",
				$this->db->quote($primary_key_name),
				implode(', ', $primary_key_fields)
			);
			$contents .= $this->eol;
		}

		$index_create = '';

		if (count($index_rows) > 0) {
			foreach ($index_rows as $idx_name => $props) {
				$props['column_names'] = array_map(array($this->db, 'quote'), $props['column_names']);
				$props['column_names'] = implode(', ', $props['column_names']);

				if (!empty($props['unique'])) {
					$contents .= sprintf("CONSTRAINT %s UNIQUE (%s),",
						$this->db->quote($idx_name),
						$props['column_names']
					);
					$contents .= $this->eol;
				}
				else {
					$index_create .= sprintf("CREATE %s INDEX %s ON %s (%s);",
						$props['unique'],
						$this->db->quote($idx_name),
						$this->db->quote($tabledata['name']),
						$props['column_names']
					);
					$index_create .= $this->eol;
				}
			}
		}

		//
		// Generate constraint clauses for CHECK constraints
		//
/*		$sql = "SELECT rcname as index_name, rcsrc
			FROM pg_relcheck, pg_class bc
			WHERE rcrelid = bc.oid
				AND bc.relname = '" . $tabledata['name'] . "'
				AND NOT EXISTS (
					SELECT *
					FROM pg_relcheck as c, pg_inherits as i
					WHERE i.inhrelid = pg_relcheck.rcrelid
						AND c.rcname = pg_relcheck.rcname
						AND c.rcsrc = pg_relcheck.rcsrc
						AND c.rcrelid = i.inhparent
				)";
		$result = $this->db->query($sql);

		//
		// Add the constraints to the sql file.
		//
		while ($row = $result->fetch()) {
			$contents .= 'CONSTRAINT ' . $this->db->quote($row['index_name']) . ' CHECK ' . $row['rcsrc'] . ',' . $this->eol;
		}
		*/
		$len = strlen(',' . $this->eol);
		$contents = substr($contents, 0, -$len);
		$contents .= $this->eol . ');' . $this->eol;

		if (!empty($index_create)) {
			$contents .= $index_create;
		}

		return $contents;
	}
}
