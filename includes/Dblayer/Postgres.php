<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@webnaute.net>
 * @link      http://dev.webnaute.net/wanewsletter/
 * @copyright 2002-2021 Aurélien Maille
 * @license   https://www.gnu.org/licenses/gpl.html  GNU General Public License
 */

namespace Wanewsletter\Dblayer;

class Postgres extends Wadb
{
	/**
	 * Type de base de données
	 */
	public const ENGINE = 'postgres';

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

	/**
	 * Liste de séquences telle que ['dbname.tablename' => 'seqname']
	 *
	 * @var array
	 */
	protected static $seqlist = [];

	public function connect($infos = null, $options = null)
	{
		$infos   = $infos ?? $this->infos;
		$options = $options ?? $this->options;

		$connectString = '';

		if (is_array($infos)) {
			foreach (['host', 'username', 'passwd', 'port', 'dbname'] as $info) {
				if (isset($infos[$info])) {
					if ($info == 'username') {
						$connectString .= "user='$infos[$info]' ";
					}
					else if ($info == 'passwd') {
						$connectString .= "password='$infos[$info]' ";
					}
					else {
						if ($info == 'host' && filter_var($infos['host'], FILTER_VALIDATE_IP)) {
							$connectString .= "hostaddr='$infos[host]' ";
							continue;
						}
						$connectString .= "$info='$infos[$info]' ";
					}
				}
			}

			$this->infos = $infos;
		}

		if (is_array($options)) {
			$this->options = array_merge($this->options, $options);
		}

		$connect = 'pg_connect';
		if (!empty($this->options['persistent'])) {
			$connect = 'pg_pconnect';
		}

		if (!empty($this->options['timeout']) && is_int($this->options['timeout'])) {
			$connectString .= sprintf('connect_timeout=%d ', $this->options['timeout']);
		}

		//
		// Options relatives aux protocoles SSL/TLS
		//
		foreach (['sslmode', 'sslcert', 'sslkey', 'sslrootcert'] as $key) {
			if (!empty($this->options[$key])) {
				$connectString .= sprintf("%s='%s' ", $key, $this->options[$key]);
			}
		}

		set_error_handler(function ($errno, $errstr) {
			$this->error = $errstr;
		});
		$this->link = $connect($connectString);
		restore_error_handler();

		if (!$this->link || pg_connection_status($this->link) !== PGSQL_CONNECTION_OK) {
			$this->errno = -1;
			$this->link  = null;

			throw new Exception($this->error, $this->errno);
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
			$this->sqlstate = pg_result_error_field($result, PGSQL_DIAG_SQLSTATE);

			if (0 == $this->sqlstate) {
				$this->error = '';

				if (in_array(strtoupper(substr($query, 0, 6)), ['INSERT', 'UPDATE', 'DELETE'])) {
					$this->_affectedRows = pg_affected_rows($result);
					$result = true;
				}
				else {
					$result = new PostgresResult($result);
				}

				return $result;
			}
			else {
				$this->error = pg_result_error_field($result, PGSQL_DIAG_MESSAGE_PRIMARY);

				$this->rollBack();
			}
		}
		else {
			$this->error = 'Unknown error with database';
		}

		throw new Exception($this->error, $this->errno);
	}

	public function quote($name)
	{
		return pg_escape_identifier($this->link, $name);
	}

	public function vacuum($tables)
	{
		if (!is_array($tables)) {
			$tables = [$tables];
		}

		foreach ($tables as $tablename) {
			pg_query($this->link, 'VACUUM ' . $this->quote($tablename));
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
			$tablename = trim($m[1], '"');// Revert éventuel de l'appel à self::quote()
			$key = $this->dbname . '.' . $tablename;

			if (!isset(self::$seqlist[$key]) ) {
				$sql = "SELECT s.relname AS seqname
					FROM pg_class s
						JOIN pg_depend d ON d.objid = s.oid
						JOIN pg_class t ON t.relname = '%s' AND d.refobjid = t.oid
						JOIN pg_namespace n ON n.oid = s.relnamespace
					WHERE s.relkind = 'S' AND n.nspname = 'public'";
				$sql = sprintf($sql, $tablename);
				$result = pg_query($this->link, $sql);

				if ($seqname = pg_fetch_result($result, 0, 'seqname')) {
					self::$seqlist[$key] = $seqname;
				}
			}
			else {
				$seqname = self::$seqlist[$key];
			}

			if ($seqname) {
				$result = pg_query($this->link, "SELECT currval('$seqname') AS lastId");
				return pg_fetch_result($result, 0, 'lastId');
			}
		}

		return false;
	}

	public function escape($string)
	{
		return pg_escape_string($this->link, $string);
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
		return new PostgresBackup($this);
	}
}

class PostgresResult extends WadbResult
{
	public function fetch($mode = null)
	{
		$modes = [
			self::FETCH_NUM   => PGSQL_NUM,
			self::FETCH_ASSOC => PGSQL_ASSOC,
			self::FETCH_BOTH  => PGSQL_BOTH
		];

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
