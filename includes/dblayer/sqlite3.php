<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter\Dblayer;

class Sqlite3 extends Wadb
{
	/**
	 * Type de base de données
	 */
	const ENGINE = 'sqlite';

	/**
	 * Version de la librairie SQLite
	 *
	 * @var string
	 */
	public $libVersion = '';

	public function connect($infos = null, $options = null)
	{
		$infos   = (is_null($infos)) ? $this->infos : $infos;
		$options = (is_null($options)) ? $this->options : $options;

		$sqlite_db = ($infos['path'] != '') ? $infos['path'] : null;

		if ($sqlite_db != ':memory:') {
			if (file_exists($sqlite_db)) {
				if (!is_readable($sqlite_db)) {
					trigger_error("SQLite database isn't readable!", E_USER_WARNING);
				}
			}
			else if (!is_writable(dirname($sqlite_db))) {
				trigger_error(dirname($sqlite_db) . " isn't writable. Cannot create "
					. basename($sqlite_db) . " database", E_USER_WARNING);
			}
		}

		$this->infos = $infos;

		if (is_array($options)) {
			$this->options = array_merge($this->options, $options);
		}

		try {
			$this->link = new \SQLite3($sqlite_db,
				SQLITE3_OPEN_READWRITE|SQLITE3_OPEN_CREATE,
				(!empty($options['encryption_key'])) ? $options['encryption_key'] : null
			);

			$this->link->busyTimeout(60000);

			$this->link->exec('PRAGMA short_column_names = 1');
			$this->link->exec('PRAGMA case_sensitive_like = 0');

			$tmp = \SQLite3::version();
			$this->libVersion = $tmp['versionString'];
		}
		catch (\Exception $e) {
			$this->errno = $e->getCode();
			$this->error = $e->getMessage();
			throw new Exception($this->error, $this->errno);
		}
	}

	public function encoding($encoding = null)
	{
		$result = $this->link->query('PRAGMA encoding');
		$row = $result->fetchArray();
		$curEncoding = $row['encoding'];

		if (!is_null($encoding)) {
			if (preg_match('#^UTF-(8|16(le|be)?)$#', $encoding)) {
				$this->link->exec("PRAGMA encoding = \"$encoding\"");
			}
			else {
				trigger_error('Invalid encoding name given. Must be UTF-8 or UTF-16(le|be)', E_USER_WARNING);
			}
		}

		return $curEncoding;
	}

	public function query($query)
	{
		$curtime = array_sum(explode(' ', microtime()));
		$result  = $this->link->query($query);
		$endtime = array_sum(explode(' ', microtime()));

		$this->sqltime += ($endtime - $curtime);
		$this->queries++;

		if (!$result) {
			$this->errno = $this->link->lastErrorCode();
			$this->error = $this->link->lastErrorMsg();
			$this->lastQuery = $query;
			$this->rollBack();

			throw new Exception($this->error, $this->errno);
		}
		else {
			$this->errno = 0;
			$this->error = '';
			$this->lastQuery = '';

			if (in_array(strtoupper(substr($query, 0, 6)), ['INSERT', 'UPDATE', 'DELETE'])) {
				$result = true;
			}
			else {
				$result = new Sqlite3Result($result);
			}
		}

		return $result;
	}

	public function insert($tablename, $dataset)
	{
		if (empty($dataset)) {
			trigger_error("Empty data array given", E_USER_WARNING);
			return false;
		}

		// voir parent::insert()
		if (!is_array($dataset[0])) {
			$dataset = [$dataset];
		}

		//
		// SQLite ne supporte les insertions multiples qu'à partir de la version 3.7.11
		//
		if (!version_compare($this->libVersion, '3.7.11', '>=')) {
			// On veut renvoyer false si au moins un appel à parent::insert() renvoie false
			$result = false;
			foreach ($dataset as $data) {
				$result |= !parent::insert($tablename, $data);
			}
			$result = !$result;
		}
		else {
			$result = parent::insert($tablename, $dataset);
		}

		return $result;
	}

	public function quote($name)
	{
		return '[' . $name . ']';
	}

	public function vacuum($tables)
	{
		if (!is_array($tables)) {
			$tables = [$tables];
		}

		foreach ($tables as $tablename) {
			$this->link->exec('VACUUM ' . $tablename);
		}
	}

	public function beginTransaction()
	{
		return $this->link->exec('BEGIN');
	}

	public function commit()
	{
		if (!($result = $this->link->exec('COMMIT'))) {
			$this->link->exec('ROLLBACK');
		}

		return $result;
	}

	public function rollBack()
	{
		return @$this->link->exec('ROLLBACK');
	}

	public function affectedRows()
	{
		return $this->link->changes();
	}

	public function lastInsertId()
	{
		return $this->link->lastInsertRowID();
	}

	public function escape($string)
	{
		return $this->link->escapeString($string);
	}

	public function ping()
	{
		return true;
	}

	public function close()
	{
		if (!is_null($this->link)) {
			try {
				$this->rollBack();
			}
			catch (\Exception $e) {}

			$result = $this->link->close();
			$this->link = null;

			return $result;
		}
		else {
			return true;
		}
	}

	public function initBackup()
	{
		return new Sqlite3Backup($this);
	}

	/**
	 * Enregistre une fonction PHP ou une fonction utilisateur à utiliser comme
	 * fonction scalaire SQL, pour utilisation dans les requête SQL.
	 *
	 * @link http://www.php.net/sqlite3.createfunction
	 *
	 * @param string   $name
	 * @param callable $callback
	 * @param integer  $num_args
	 *
	 * @return boolean
	 */
	public function createFunction($name, $callback, $num_args = -1)
	{
		return $this->link->createFunction($name, $callback, $num_args);
	}
}

class Sqlite3Result extends WadbResult
{
	public function fetch($mode = null)
	{
		$modes = [
			self::FETCH_NUM   => SQLITE3_NUM,
			self::FETCH_ASSOC => SQLITE3_ASSOC,
			self::FETCH_BOTH  => SQLITE3_BOTH
		];

		return $this->result->fetchArray($this->getFetchMode($modes, $mode));
	}

	public function fetchObject()
	{
		return (object) $this->result->fetchArray(SQLITE3_ASSOC);
	}

	public function column($column)
	{
		$row = $this->result->fetchArray();

		return (is_array($row) && isset($row[$column])) ? $row[$column] : false;
	}

	public function free()
	{
		if (!is_null($this->result)) {
			$this->result = null;
		}
	}
}

class Sqlite3Backup extends WadbBackup
{
	public function header($toolname = '')
	{
		if (!($hostname = gethostname())) {
			$hostname = (!empty($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : 'Unknown Host';
		}

		$contents  = '-- ' . $this->eol;
		$contents .= "-- $toolname SQLite Dump" . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= "-- Host       : " . $hostname . $this->eol;
		$contents .= "-- SQLite lib : " . $this->db->libVersion . $this->eol;
		$contents .= "-- Database   : " . basename($this->db->dbname) . $this->eol;
		$contents .= '-- Date       : ' . date(DATE_RFC2822) . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= $this->eol;

		return $contents;
	}

	public function get_tables()
	{
		$result = $this->db->query("SELECT tbl_name FROM sqlite_master WHERE type = 'table'");
		$tables = [];

		while ($row = $result->fetch()) {
			$tables[$row['tbl_name']] = '';
		}

		return $tables;
	}

	public function get_table_structure($tabledata, $drop_option)
	{
		$contents  = '-- ' . $this->eol;
		$contents .= '-- Structure de la table ' . $tabledata['name'] . ' ' . $this->eol;
		$contents .= '-- ' . $this->eol;

		if ($drop_option) {
			$contents .= 'DROP TABLE IF EXISTS ' . $this->db->quote($tabledata['name']) . ';' . $this->eol;
		}

		$sql = "SELECT sql, type
			FROM sqlite_master
			WHERE tbl_name = '$tabledata[name]'
				AND sql IS NOT NULL";
		$result = $this->db->query($sql);

		$indexes = '';
		while ($row = $result->fetch()) {
			if ($row['type'] == 'table') {
				$create_table = str_replace(',', ',' . $this->eol, $row['sql']) . ';' . $this->eol;
			}
			else {
				$indexes .= $row['sql'] . ';' . $this->eol;
			}
		}

		$contents .= $create_table . $indexes;

		return $contents;
	}
}