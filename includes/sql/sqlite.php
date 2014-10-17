<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if( !defined('_INC_CLASS_WADB_SQLITE') ) {

define('_INC_CLASS_WADB_SQLITE', true);

require dirname(__FILE__) . '/wadb.php';

class Wadb_sqlite extends Wadb {

	/**
	 * Type de base de données
	 *
	 * @var string
	 */
	public $engine = 'sqlite';

	/**
	 * Version de la librairie SQLite
	 * 
	 * @var string
	 */
	public $libVersion = '';

	public function connect($infos = null, $options = null)
	{
		$sqlite_db = ($infos['path'] != '') ? $infos['path'] : null;
		
		if( $sqlite_db != ':memory:' ) {
			if( file_exists($sqlite_db) ) {
				if( !is_readable($sqlite_db) ) {
					trigger_error("SQLite database isn't readable!", E_USER_WARNING);
				}
			}
			else if( !is_writable(dirname($sqlite_db)) ) {
				trigger_error(dirname($sqlite_db) . " isn't writable. Cannot create "
					. basename($sqlite_db) . " database", E_USER_WARNING);
			}
		}
		
		$connect = 'sqlite_open';
		$this->dbname = $sqlite_db;
		
		if( is_array($options) ) {
			$this->options = array_merge($this->options, $options);
		}
		
		if( !empty($options['persistent']) ) {
			$connect = 'sqlite_popen';
		}
		
		if( !($this->link = $connect($sqlite_db, 0666, $this->error)) ) {
			$this->errno = -1;
			throw new SQLException($this->error, $this->errno);
		}
		
		sqlite_exec($this->link, 'PRAGMA short_column_names = 1');
		sqlite_exec($this->link, 'PRAGMA case_sensitive_like = 0');
		
		ini_set('sqlite.assoc_case', '0');
		$this->libVersion = sqlite_libversion();
	}
	
	public function encoding($encoding = null)
	{
		if( !is_null($encoding) ) {
			trigger_error("Setting encoding isn't supported by SQLite", E_USER_WARNING);
		}
		
		return sqlite_libencoding();
	}
	
	public function query($query)
	{
		$curtime = array_sum(explode(' ', microtime()));
		$result  = sqlite_query($this->link, $query);
		$endtime = array_sum(explode(' ', microtime()));
		
		$this->sqltime += ($endtime - $curtime);
		$this->queries++;
		
		if( !$result ) {
			$this->errno = sqlite_last_error($this->link);
			$this->error = sqlite_error_string($this->errno);
			$this->lastQuery = $query;
			$this->rollBack();
			
			throw new SQLException($this->error, $this->errno);
		}
		else {
			$this->errno = 0;
			$this->error = '';
			$this->lastQuery = '';
			
			if( !is_bool($result) ) {// on a réceptionné une ressource ou un objet
				$result = new WadbResult_sqlite($result);
			}
		}
		
		return $result;
	}
	
	public function quote($name)
	{
		return '[' . $name . ']';
	}
	
	public function vacuum($tables)
	{
		if( !is_array($tables) ) {
			$tables = array($tables); 
		}
		
		foreach( $tables as $tablename ) {
			sqlite_exec($this->link, 'VACUUM ' . $tablename);
		}
	}
	
	public function beginTransaction()
	{
		return sqlite_exec($this->link, 'BEGIN');
	}
	
	public function commit()
	{
		if( !($result = sqlite_exec($this->link, 'COMMIT')) )
		{
			sqlite_exec($this->link, 'ROLLBACK');
		}
		
		return $result;
	}
	
	public function rollBack()
	{
		return sqlite_exec($this->link, 'ROLLBACK');
	}
	
	public function affectedRows()
	{
		return sqlite_changes($this->link);
	}
	
	public function lastInsertId()
	{
		return sqlite_last_insert_rowid($this->link);
	}
	
	public function escape($string)
	{
		return sqlite_escape_string($string);
	}
	
	public function ping()
	{
		return true;
	}
	
	public function close()
	{
		if( !is_null($this->link) ) {
			@$this->rollBack();
			$result = sqlite_close($this->link);
			$this->link = null;
			
			return $result;
		}
		else {
			return true;
		}
	}
	
	public function initBackup()
	{
		return new WadbBackup_sqlite($this);
	}
}

class WadbResult_sqlite extends WadbResult {

	public function fetch($mode = null)
	{
		$modes = array(
			self::FETCH_NUM   => SQLITE_NUM,
			self::FETCH_ASSOC => SQLITE_ASSOC,
			self::FETCH_BOTH  => SQLITE_BOTH
		);
		
		return sqlite_fetch_array($this->result, $this->getFetchMode($modes, $mode));
	}
	
	public function fetchObject()
	{
		return sqlite_fetch_object($this->result);
	}
	
	public function fetchAll($mode = null)
	{
		$modes = array(
			self::FETCH_NUM   => SQLITE_NUM,
			self::FETCH_ASSOC => SQLITE_ASSOC,
			self::FETCH_BOTH  => SQLITE_BOTH
		);
		
		return sqlite_fetch_all($this->result, $this->getFetchMode($modes, $mode));
	}
	
	public function column($column)
	{
		$row = sqlite_fetch_array($this->result);
		
		return (is_array($row) && isset($row[$column])) ? $row[$column] : false;
	}
	
	public function free()
	{
		if( !is_null($this->result) ) {
			$this->result = null;
		}
	}
}

class WadbBackup_sqlite extends WadbBackup {

	public function header($toolname = '')
	{
		$host = function_exists('php_uname') ? @php_uname('n') : null;
		if( empty($host) ) {
			$host = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'Unknown Host';
		}
		
		$contents  = '-- ' . $this->eol;
		$contents .= "-- $toolname SQLite Dump" . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= "-- Host       : " . $host . $this->eol;
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
		$tables = array();
		
		while( $row = $result->fetch() ) {
			$tables[$row['tbl_name']] = '';
		}
		
		return $tables;
	}
	
	public function get_table_structure($tabledata, $drop_option)
	{
		$contents  = '-- ' . $this->eol;
		$contents .= '-- Structure de la table ' . $tabledata['name'] . ' ' . $this->eol;
		$contents .= '-- ' . $this->eol;
		
		if( $drop_option ) {
			$contents .= 'DROP TABLE IF EXISTS ' . $this->db->quote($tabledata['name']) . ';' . $this->eol;
		}
		
		$sql = "SELECT sql, type
			FROM sqlite_master
			WHERE tbl_name = '$tabledata[name]'
				AND sql IS NOT NULL";
		$result = $this->db->query($sql);
		
		$indexes = '';
		while( $row = $result->fetch() ) {
			if( $row['type'] == 'table' ) {
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

}
