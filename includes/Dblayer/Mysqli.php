<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter\Dblayer;

class Mysqli extends Wadb
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

		$this->clientVersion = mysqli_get_client_info();

		// libmysqlclient veut une ipv6 sans crochets (eg: ::1), mais
		// mysqlnd veut une ipv6 délimitée par des crochets (eg: [::1])
		// PHP bug 67563 <https://bugs.php.net/bug.php?id=67563>
		if (stripos($this->clientVersion, 'mysqlnd') !== false
			&& filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
		) {
			$host = "[$host]";
		}

		if (!empty($this->options['persistent'])) {
			$host = "p:$host";
		}

		$this->link = mysqli_init();
		$flags = null;

		if (!empty($this->options['timeout']) && is_int($this->options['timeout'])) {
			mysqli_options($this->link, MYSQLI_OPT_CONNECT_TIMEOUT, $this->options['timeout']);
		}

		//
		// Options relatives aux protocoles SSL/TLS
		//
		if (!empty($this->options['ssl'])) {
			$flags = MYSQLI_CLIENT_SSL;
			$args = ['ssl-key', 'ssl-cert', 'ssl-ca', 'ssl-capath', 'ssl-cipher'];
			$args = array_fill_keys($args, null);
			$args = array_intersect_key(array_replace($args, $this->options), $args);
			call_user_func_array(array($this->link, 'ssl_set'), $args);
		}

		if (!mysqli_real_connect($this->link, $host, $username, $passwd, $dbname, $port, null, $flags)) {
			$this->errno = mysqli_connect_errno();
			$this->error = mysqli_connect_error();
			$this->link  = null;

			throw new Exception($this->error, $this->errno);
		}
		else {
			$this->serverVersion = mysqli_get_server_info($this->link);

			if (!empty($this->options['charset'])) {
				$this->encoding($this->options['charset']);
			}
		}
	}

	public function encoding($encoding = null)
	{
		$o = mysqli_get_charset($this->link);
		$curEncoding = $o->charset;

		if (!is_null($encoding)) {
			mysqli_set_charset($this->link, $encoding);
		}

		return $curEncoding;
	}

	public function query($query)
	{
		$curtime = array_sum(explode(' ', microtime()));
		$result  = mysqli_query($this->link, $query);
		$endtime = array_sum(explode(' ', microtime()));

		$this->sqltime += ($endtime - $curtime);
		$this->queries++;

		if (!$result) {
			$this->errno = mysqli_errno($this->link);
			$this->error = mysqli_error($this->link);
			$this->lastQuery = $query;
			$this->rollBack();

			throw new Exception($this->error, $this->errno);
		}
		else {
			$this->errno = 0;
			$this->error = '';
			$this->lastQuery = '';

			if (!is_bool($result)) {// on a réceptionné une ressource ou un objet
				$result = new MysqliResult($result);
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

		mysqli_query($this->link, 'OPTIMIZE TABLE ' . implode(', ', $tables));
	}

	public function beginTransaction()
	{
		return mysqli_autocommit($this->link, false);
	}

	public function commit()
	{
		if (!($result = mysqli_commit($this->link))) {
			mysqli_rollback($this->link);
		}

		mysqli_autocommit($this->link, true);

		return $result;
	}

	public function rollBack()
	{
		$result = mysqli_rollback($this->link);
		mysqli_autocommit($this->link, true);

		return $result;
	}

	public function affectedRows()
	{
		return mysqli_affected_rows($this->link);
	}

	public function lastInsertId()
	{
		return mysqli_insert_id($this->link);
	}

	public function escape($string)
	{
		return mysqli_real_escape_string($this->link, $string);
	}

	public function ping()
	{
		return mysqli_ping($this->link);
	}

	public function close()
	{
		if (!is_null($this->link)) {
			@$this->rollBack();
			$result = mysqli_close($this->link);
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

class MysqliResult extends WadbResult
{
	public function fetch($mode = null)
	{
		$modes = [
			self::FETCH_NUM   => MYSQLI_NUM,
			self::FETCH_ASSOC => MYSQLI_ASSOC,
			self::FETCH_BOTH  => MYSQLI_BOTH
		];

		return mysqli_fetch_array($this->result, $this->getFetchMode($modes, $mode));
	}

	public function fetchObject()
	{
		return mysqli_fetch_object($this->result);
	}

	public function column($column)
	{
		$row = mysqli_fetch_array($this->result);

		return (is_array($row) && isset($row[$column])) ? $row[$column] : false;
	}

	public function free()
	{
		if (!is_null($this->result)) {
			mysqli_free_result($this->result);
			$this->result = null;
		}
	}
}
