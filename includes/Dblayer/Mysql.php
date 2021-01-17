<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@webnaute.net>
 * @link      http://dev.webnaute.net/wanewsletter/
 * @copyright 2002-2021 Aurélien Maille
 * @license   https://www.gnu.org/licenses/gpl.html  GNU General Public License
 */

namespace Wanewsletter\Dblayer;

class Mysql extends Wadb
{
	/**
	 * Type de base de données
	 */
	const ENGINE = 'mysql';

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

	public function connect($infos = null, $options = null)
	{
		$infos   = (is_null($infos)) ? $this->infos : $infos;
		$options = (is_null($options)) ? $this->options : $options;

		if (is_array($infos)) {
			foreach (['host', 'username', 'passwd', 'port', 'dbname'] as $info) {
				$$info = (isset($infos[$info])) ? $infos[$info] : null;
			}

			$this->infos = $infos;
		}

		if (is_array($options)) {
			$this->options = array_merge($this->options, $options);
		}

		$this->clientVersion = mysql_get_client_info();

		// PHP bug 67563 <https://bugs.php.net/bug.php?id=67563>
		if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			throw new Exception("Mysql extension doesn't have support for IPv6 address. Use mysqli instead.");
		}

		if (!is_null($port)) {
			$host .= ':' . $port;
		}

		$flags = null;

		//
		// Options relatives aux protocoles SSL/TLS
		//
		if (!empty($this->options['ssl'])) {
			$args = ['ssl-key', 'ssl-cert', 'ssl-ca', 'ssl-capath', 'ssl-cipher'];
			$args = array_fill_keys($args, null);

			// Si des options ssl-* ont été fournies, mysqli doit être utilisé.
			if (array_intersect_key($this->options, $args)) {
				throw new Exception("Mysql extension doesn't have support for ssl-* options. Use mysqli instead.");
			}

			$flags = MYSQL_CLIENT_SSL;
		}

		$args = [];
		$args['server']       = $host;
		$args['username']     = $username;
		$args['password']     = $passwd;
		$args['new_link']     = false;
		$args['client_flags'] = $flags;

		$connect = 'mysql_connect';
		if (!empty($this->options['persistent'])) {
			$connect = 'mysql_pconnect';
			// Pas d’argument new_link sur la fonction mysql_pconnect()
			unset($args['new_link']);
		}

		if (!($this->link = call_user_func_array($connect, $args))) {
			$this->errno = mysql_errno();
			$this->error = mysql_error();
			$this->link  = null;

			throw new Exception($this->error, $this->errno);
		}
		else if (!mysql_select_db($dbname)) {
			$this->errno = mysql_errno($this->link);
			$this->error = mysql_error($this->link);
			mysql_close($this->link);
			$this->link  = null;

			throw new Exception($this->error, $this->errno);
		}
		else {
			$this->serverVersion = mysql_get_server_info($this->link);

			if (!empty($this->options['charset'])) {
				$this->encoding($this->options['charset']);
			}
		}
	}

	public function encoding($encoding = null)
	{
		$curEncoding = mysql_client_encoding($this->link);

		if (!is_null($encoding)) {
			mysql_set_charset($encoding, $this->link);
		}

		return $curEncoding;
	}

	public function query($query)
	{
		$curtime = array_sum(explode(' ', microtime()));
		$result  = mysql_query($query, $this->link);
		$endtime = array_sum(explode(' ', microtime()));

		$this->sqltime += ($endtime - $curtime);
		$this->queries++;

		if (!$result) {
			$this->errno = mysql_errno($this->link);
			$this->error = mysql_error($this->link);
			$this->lastQuery = $query;
			$this->rollBack();

			throw new Exception($this->error, $this->errno);
		}
		else {
			$this->errno = 0;
			$this->error = '';
			$this->lastQuery = '';

			if (!is_bool($result)) {// on a réceptionné une ressource ou un objet
				$result = new MysqlResult($result);
			}
		}

		return $result;
	}

	public function quote($name)
	{
		return '`' . $name . '`';
	}

	public function vacuum($tables)
	{
		if (!is_array($tables)) {
			$tables = [$tables];
		}

		array_walk($tables, function (&$value, $key) {
			$value = $this->quote($value);
		});

		mysql_query('OPTIMIZE TABLE ' . implode(', ', $tables), $this->link);
	}

	public function beginTransaction()
	{
		mysql_query('SET AUTOCOMMIT=0', $this->link);

		return mysql_query('BEGIN', $this->link);
	}

	public function commit()
	{
		if (!($result = mysql_query('COMMIT', $this->link))) {
			mysql_query('ROLLBACK', $this->link);
		}

		mysql_query('SET AUTOCOMMIT=1', $this->link);

		return $result;
	}

	public function rollBack()
	{
		$result = mysql_query('ROLLBACK', $this->link);
		mysql_query('SET AUTOCOMMIT=1', $this->link);

		return $result;
	}

	public function affectedRows()
	{
		return mysql_affected_rows($this->link);
	}

	public function lastInsertId()
	{
		return mysql_insert_id($this->link);
	}

	public function escape($string)
	{
		return mysql_real_escape_string($string, $this->link);
	}

	public function ping()
	{
		return mysql_ping($this->link);
	}

	public function close()
	{
		if (!is_null($this->link)) {
			@$this->rollBack();
			$result = mysql_close($this->link);
			$this->link = null;

			return $result;
		}
		else {
			return true;
		}
	}

	public function initBackup()
	{
		return new MysqlBackup($this);
	}
}

class MysqlResult extends WadbResult
{
	public function fetch($mode = null)
	{
		$modes = [
			self::FETCH_NUM   => MYSQL_NUM,
			self::FETCH_ASSOC => MYSQL_ASSOC,
			self::FETCH_BOTH  => MYSQL_BOTH
		];

		return mysql_fetch_array($this->result, $this->getFetchMode($modes, $mode));
	}

	public function fetchObject()
	{
		return mysql_fetch_object($this->result);
	}

	public function column($column)
	{
		$row = mysql_fetch_array($this->result);

		return (is_array($row) && isset($row[$column])) ? $row[$column] : false;
	}

	public function free()
	{
		if (!is_null($this->result)) {
			mysql_free_result($this->result);
			$this->result = null;
		}
	}
}
