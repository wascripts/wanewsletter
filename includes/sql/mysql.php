<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if (!defined('_INC_CLASS_WADB_MYSQL')) {

define('_INC_CLASS_WADB_MYSQL', true);

require dirname(__FILE__) . '/wadb.php';

class Wadb_mysql extends Wadb
{
	/**
	 * Type de base de données
	 *
	 * @var string
	 */
	public $engine = 'mysql';

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
		if (is_array($infos)) {
			foreach (array('host', 'username', 'passwd', 'port', 'dbname') as $info) {
				$$info = (isset($infos[$info])) ? $infos[$info] : null;
			}

			$this->host   = $host . (!is_null($port) ? ':'.$port : '');
			$this->dbname = $dbname;
		}

		$connect = 'mysql_connect';

		if (is_array($options)) {
			$this->options = array_merge($this->options, $options);
		}

		if (!empty($this->options['persistent'])) {
			$connect = 'mysql_pconnect';
		}

		if (!is_null($port)) {
			$host .= ':' . $port;
		}

		if (!($this->link = $connect($host, $username, $passwd))) {
			$this->errno = mysql_errno();
			$this->error = mysql_error();
			$this->link  = null;

			throw new SQLException($this->error, $this->errno);
		}
		else if (!mysql_select_db($dbname)) {
			$this->errno = mysql_errno($this->link);
			$this->error = mysql_error($this->link);
			mysql_close($this->link);
			$this->link  = null;

			throw new SQLException($this->error, $this->errno);
		}
		else {
			$this->serverVersion = mysql_get_server_info($this->link);
			$this->clientVersion = mysql_get_client_info();

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

			throw new SQLException($this->error, $this->errno);
		}
		else {
			$this->errno = 0;
			$this->error = '';
			$this->lastQuery = '';

			if (!is_bool($result)) {// on a réceptionné une ressource ou un objet
				$result = new WadbResult_mysql($result);
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

		mysql_query('OPTIMIZE TABLE ' . $tables, $this->link);
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
		return new WadbBackup_mysql($this);
	}
}

class WadbResult_mysql extends WadbResult
{
	public function fetch($mode = null)
	{
		$modes = array(
			self::FETCH_NUM   => MYSQL_NUM,
			self::FETCH_ASSOC => MYSQL_ASSOC,
			self::FETCH_BOTH  => MYSQL_BOTH
		);

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

class WadbBackup_mysql extends WadbBackup
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

}
