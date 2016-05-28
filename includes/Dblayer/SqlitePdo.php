<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter\Dblayer;

use PDO;
use PDOException;

class SqlitePdo extends Wadb
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

	/**
	 * @var PDO
	 */
	protected $pdo;

	/**
	 * @var PDOStatement
	 */
	protected $result;

	/**
	 * Nombre de lignes affectées par la dernière requète DML
	 *
	 * @var integer
	 */
	protected $_affectedRows = 0;

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

		$opt = [];
		if (!empty($options['persistent'])) {
			$opt[PDO::ATTR_PERSISTENT] = true;
		}

		try {
			$this->pdo = new PDO('sqlite:' . $sqlite_db, null, null, $opt);

			$this->link = true;
			$this->pdo->query('PRAGMA short_column_names = 1');
			$this->pdo->query('PRAGMA case_sensitive_like = 0');
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
			$this->pdo->setAttribute(PDO::ATTR_CASE,    PDO::CASE_NATURAL);

			$res = $this->pdo->query("SELECT sqlite_version()");
			$this->libVersion = $res->fetchColumn(0);

//			if (!empty($this->options['charset'])) {
//				$this->encoding($this->options['charset']);
//			}
		}
		catch (PDOException $e) {
			$this->errno = $e->getCode();
			$this->error = $e->getMessage();
			throw new Exception($this->error, $this->errno);
		}
	}

	public function encoding($encoding = null)
	{
		$result = $this->pdo->query('PRAGMA encoding');
		$row = $result->fetch();
		$curEncoding = $row['encoding'];

		if (!is_null($encoding)) {
			if (preg_match('#^UTF-(8|16(le|be)?)$#', $encoding)) {
				$this->pdo->exec("PRAGMA encoding = \"$encoding\"");
			}
			else {
				trigger_error('Invalid encoding name given. Must be UTF-8 or UTF-16(le|be)', E_USER_WARNING);
			}
		}

		return $curEncoding;
	}

	public function query($query)
	{
		if ($this->result instanceof PDOStatement) {
			$this->result->closeCursor();
		}

		$curtime = array_sum(explode(' ', microtime()));
		$result  = $this->pdo->query($query);
		$endtime = array_sum(explode(' ', microtime()));

		$this->sqltime += ($endtime - $curtime);
		$this->queries++;

		if (!$result) {
			$tmp = $this->pdo->errorInfo();
			$this->errno = $tmp[1];
			$this->error = $tmp[2];
			$this->lastQuery = $query;
			$this->result = null;

			try {
				$this->rollBack();
			}
			catch (PDOException $e) {}

			throw new Exception($this->error, $this->errno);
		}
		else {
			$this->errno = 0;
			$this->error = '';
			$this->lastQuery = '';
			$this->result = $result;

			if (in_array(strtoupper(substr($query, 0, 6)), ['INSERT', 'UPDATE', 'DELETE'])) {
				$this->_affectedRows = $result->rowCount();
				$result = true;
			}
			else {
				$result = new SqlitepdoResult($result);
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
		if (!isset($dataset[0]) || !is_array($dataset[0])) {
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
			$this->pdo->query('VACUUM ' . $this->quote($tablename));
		}
	}

	public function beginTransaction()
	{
		return $this->pdo->beginTransaction();
	}

	public function commit()
	{
		if (!($result = $this->pdo->commit())) {
			$this->pdo->rollBack();
		}

		return $result;
	}

	public function rollBack()
	{
		return $this->pdo->rollBack();
	}

	public function affectedRows()
	{
		return $this->_affectedRows;
	}

	public function lastInsertId()
	{
		return $this->pdo->lastInsertId();
	}

	public function escape($string)
	{
		return substr($this->pdo->quote($string), 1, -1);
	}

	public function ping()
	{
		return true;
	}

	public function close()
	{
		try {
			$this->rollBack();
		}
		catch (PDOException $e) {}

		return true;
	}

	public function initBackup()
	{
		return new SqliteBackup($this);
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
		return $this->pdo->sqliteCreateFunction($name, $callback, $num_args);
	}
}

class SqlitepdoResult extends WadbResult
{
	public function fetch($mode = null)
	{
		$modes = [
			self::FETCH_NUM   => PDO::FETCH_NUM,
			self::FETCH_ASSOC => PDO::FETCH_ASSOC,
			self::FETCH_BOTH  => PDO::FETCH_BOTH
		];

		return $this->result->fetch($this->getFetchMode($modes, $mode));
	}

	public function fetchObject()
	{
		return $this->result->fetch(PDO::FETCH_OBJ);
	}

	public function fetchAll($mode = null)
	{
		$modes = [
			self::FETCH_NUM   => PDO::FETCH_NUM,
			self::FETCH_ASSOC => PDO::FETCH_ASSOC,
			self::FETCH_BOTH  => PDO::FETCH_BOTH
		];

		return $this->result->fetchAll($this->getFetchMode($modes, $mode));
	}

	public function column($column)
	{
		$row = $this->result->fetch(PDO::FETCH_BOTH);

		return (is_array($row) && isset($row[$column])) ? $row[$column] : false;
	}

	public function free()
	{
		if (!is_null($this->result)) {
			$this->result = null;
		}
	}
}
