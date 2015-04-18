<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
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
			foreach (array('host', 'username', 'passwd', 'port', 'dbname') as $info) {
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
		if (stripos($this->clientVersion, 'mysqlnd') !== false &&
			filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
		) {
			$host = "[$host]";
		}

		if (!empty($this->options['persistent'])) {
			$host = "p:$host";
		}

		if (!($this->link = mysqli_connect($host, $username, $passwd, $dbname, $port))) {
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
		if (is_array($tables)) {
			$tables = implode(', ', $tables);
		}

		mysqli_query($this->link, 'OPTIMIZE TABLE ' . $tables);
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
		return new MysqliBackup($this);
	}
}

class MysqliResult extends WadbResult
{
	public function fetch($mode = null)
	{
		$modes = array(
			self::FETCH_NUM   => MYSQLI_NUM,
			self::FETCH_ASSOC => MYSQLI_ASSOC,
			self::FETCH_BOTH  => MYSQLI_BOTH
		);

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

class MysqliBackup extends WadbBackup
{
	public function header($toolname = '')
	{
		$contents  = '-- ' . $this->eol;
		$contents .= "-- $toolname MySQL Dump" . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= "-- Host     : " . $this->db->host . $this->eol;
		$contents .= "-- Server   : " . $this->db->serverVersion . $this->eol;
		$contents .= "-- Database : " . $this->db->dbname . $this->eol;
		$contents .= '-- Date     : ' . date(DATE_RFC2822) . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= $this->eol;

		$contents .= sprintf("SET NAMES '%s';%s", $this->db->encoding(), $this->eol);
		$contents .= $this->eol;

		return $contents;
	}

	public function get_tables()
	{
		$result = $this->db->query('SHOW TABLE STATUS FROM ' . $this->db->quote($this->db->dbname));
		$tables = array();

		while ($row = $result->fetch()) {
			$tables[$row['Name']] = $row['Engine'];
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

		$result = $this->db->query('SHOW CREATE TABLE ' . $this->db->quote($tabledata['name']));
		$create_table = $result->column('Create Table');
		$result->free();

		$contents .= preg_replace("/(\r\n?)|\n/", $this->eol, $create_table) . ';' . $this->eol;

		return $contents;
	}
}
