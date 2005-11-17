<?php
/**
 * Copyright (c) 2002-2006 Aurélien Maille
 * 
 * This file is part of Wanewsletter.
 * 
 * Wanewsletter is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * Wanewsletter is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Wanewsletter; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * 
 * @package Wanewsletter
 * @author  Bobe <wascripts@phpcodeur.net>
 * @link    http://phpcodeur.net/wascripts/wanewsletter/
 * @license http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 * @version $Id$
 */

if( !defined('CLASS_SQL_INC') ) {

define('CLASS_SQL_INC', true);
define('DATABASE', 'mysql');

class sql {
	
	var $connect_id   = '';
	var $query_result = '';
	var $trc_started  = 0;
	var $sql_error    = array('errno' => '', 'message' => '', 'query' => '');
	
	var $queries      = 0;
	var $sql_time     = 0;
	
	function sql($dbhost, $dbuser, $dbpwd, $dbname, $persistent = false)
	{
		$sql_connect = ( $persistent ) ? 'mysql_pconnect' : 'mysql_connect';
		
		$this->connect_id = @$sql_connect($dbhost, $dbuser, $dbpwd);
		
		if( $this->connect_id )
		{
			$select_db = @mysql_select_db($dbname, $this->connect_id);
			
			if( !$select_db )
			{
				$this->sql_error['errno']   = @mysql_errno();
				$this->sql_error['message'] = @mysql_error();
				@mysql_close($this->connect_id);
			}
		}
		else
		{
			$this->sql_error['errno']   = @mysql_errno();
			$this->sql_error['message'] = @mysql_error();
		}
	}
	
	function prepare_value($value)
	{
		if( is_bool($value) || preg_match('/^[0-9]+$/', $value) )
		{
			$tmp = intval($value);
		}
		else
		{
			$tmp = '\'' . $this->escape($value) . '\'';
		}
		
		return $tmp;
	}
	
	function query_build($query_type, $table, $query_data, $sql_where = '')
	{
		$fields = $values = array();
		
		foreach( $query_data AS $field => $value )
		{
			array_push($fields, $field);
			array_push($values, $this->prepare_value($value));
		}
		
		if( $query_type == 'INSERT' )
		{
			$query_string  = 'INSERT INTO ' . $table . ' ';
			$query_string .= '(' . implode(', ', $fields) . ') VALUES(' . implode(', ', $values) . ')';
		}
		else if( $query_type == 'UPDATE' )
		{
			$query_string  = 'UPDATE ' . $table . ' SET ';
			for( $i = 0; $i < count($fields); $i++ )
			{
				$query_string .= ( $i > 0 ) ? ', ' : '';
				$query_string .= $fields[$i] . ' = ' . $values[$i];
			}
			
			if( is_array($sql_where) && count($sql_where) )
			{
				$ary = array();
				foreach( $sql_where AS $field => $value )
				{
					$ary[] = $field . ' = ' . $this->prepare_value($value);
				}
				
				$query_string .= ' WHERE ' . implode(' AND ', $ary);
			}
		}
		
		return $this->query($query_string);
	}
	
	function query($query, $start = null, $limit = null)
	{
		global $starttime;
		
		unset($this->query_result);
		
		if( isset($start) && !empty($limit) )
		{
			$query .= ' LIMIT ' . $start . ', ' . $limit;
		}
		
		$curtime = explode(' ', microtime());
		$curtime = $curtime[0] + $curtime[1] - $starttime;
		
		$this->query_result = @mysql_query($query, $this->connect_id);
		
		$endtime = explode(' ', microtime());
		$endtime = $endtime[0] + $endtime[1] - $starttime;
		
		$this->sql_time += ($endtime - $curtime);
		$this->queries++;
		
		if( !$this->query_result )
		{
			$this->sql_error['errno']   = @mysql_errno($this->connect_id);
			$this->sql_error['message'] = @mysql_error($this->connect_id);
			$this->sql_error['query']   = $query;
			
			$this->transaction('ROLLBACK');
		}
		else
		{
			$this->sql_error = array('errno' => '', 'message' => '', 'query' => '');
		}
		
		return $this->query_result;
	}
	
	function transaction($transaction)
	{
		switch($transaction)
		{
			case START_TRC:
				if( !$this->trc_started )
				{
					$this->trc_started = true;
					@mysql_query('SET AUTOCOMMIT=0', $this->connect_id);
					$result = @mysql_query('BEGIN', $this->connect_id);
				}
				else
				{
					$result = true;
				}
				break;
				
			case END_TRC:
				if( $this->trc_started )
				{
					$this->trc_started = false;
					
					if( !($result = @mysql_query('COMMIT', $this->connect_id)) )
					{
						@mysql_query('ROLLBACK', $this->connect_id);
						$result = false;
					}
					
					@mysql_query('SET AUTOCOMMIT=1', $this->connect_id);
				}
				else
				{
					$result = true;
				}
				break;
				
			case 'ROLLBACK':
				if( $this->trc_started )
				{
					$this->trc_started = false;
					$result = @mysql_query('ROLLBACK', $this->connect_id);
					@mysql_query('SET AUTOCOMMIT=1', $this->connect_id);
				}
				else
				{
					$result = true;
				}
				break;
		}
		
		return $result;
	}
	
	function check($tables)
	{
		if( !is_array($tables) )
		{
			$tables = array($tables);
		}
		
		$tables_list = implode(', ', $tables);
		
		@mysql_query('OPTIMIZE TABLE ' . $tables_list, $this->connect_id);
		
		return true;
	}
	
	function num_rows($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( is_resource($result) ) ? @mysql_num_rows($result) : false;
	}
	
	function affected_rows()
	{
		return ( is_resource($this->connect_id) ) ? @mysql_affected_rows($this->connect_id) : false;
	}
	
	function fetch_row($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( is_resource($result) ) ? @mysql_fetch_row($result) : false;
	}
	
	function fetch_array($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( is_resource($result) ) ? @mysql_fetch_array($result, MYSQL_ASSOC) : false;
	}
	
	function fetch_rowset($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		$rowset = array();
		while( $row = @mysql_fetch_array($result, MYSQL_ASSOC) )
		{
			$rowset[] = $row;
		}
		
		return $rowset;
	}
	
	function num_fields($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( is_resource($result) ) ? @mysql_num_fields($result) : false;
	}
	
	function field_name($offset, $result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( is_resource($result) ) ? @mysql_field_name($result, $offset) : false;
	}
	
	function result($result, $row, $field = '')
	{
		if( $field != '' )
		{
			return @mysql_result($result, $row, $field);
		}
		else
		{
			return @mysql_result($result, $row);
		}
	}
	
	function next_id()
	{
		return ( is_resource($this->connect_id) ) ? @mysql_insert_id($this->connect_id) : false;
	}
	
	function free_result($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		if( is_resource($result) )
		{
			@mysql_free_result($result);
		}
	}
	
	function escape($str)
	{
		return mysql_escape_string($str);
	}
	
	function close()
	{
		if( is_resource($this->connect_id) )
		{
			$this->free_result($this->query_result);
			$this->transaction(END_TRC);
			
			return @mysql_close($this->connect_id);
		}
		else
		{
			return false;
		}
	}
}// fin de la classe

class sql_backup {
	/**
	 * Fin de ligne
	 * 
	 * @var string
	 * @access public
	 */
	var $eol = "\n";
	
	/**
	 * Protection des noms de table et de colonnes avec un quote inversé ( ` )
	 * 
	 * @var boolean
	 * @access public
	 */
	var $protect_name = TRUE;
	
	function header($dbhost, $dbname, $toolname = '')
	{
		$contents  = '-- ' . $this->eol;
		$contents .= "-- $toolname MySQL Dump" . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= "-- Serveur  : $dbhost" . $this->eol;
		$contents .= "-- Database : $dbname" . $this->eol;
		$contents .= '-- Date     : ' . date('d/m/Y H:i:s') . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= $this->eol;
		
		return $contents;
	}
	
	function get_tables($dbname)
	{
		global $db;
		
		$quote = ( $this->protect_name ) ? '`' : '';
		
		if( !($result = $db->query('SHOW TABLE STATUS FROM ' . $quote . $dbname . $quote)) )
		{
			trigger_error('Impossible d\'obtenir la liste des tables', ERROR);
		}
		
		$tables = array();
		while( $row = $db->fetch_row($result) )
		{
			$tables[$row[0]] = $row[1];
		}
		
		return $tables;
	}
	
	function get_table_structure($tabledata, $drop_option)
	{
		global $db;
		
		$quote = ( $this->protect_name ) ? '`' : '';
		
		$contents  = '-- ' . $this->eol;
		$contents .= '-- Struture de la table ' . $tabledata['name'] . ' ' . $this->eol;
		$contents .= '-- ' . $this->eol;
		
		if( $drop_option )
		{
			$contents .= 'DROP TABLE IF EXISTS ' . $quote . $tabledata['name'] . $quote . ';' . $this->eol;
		}
		
		//
		// La requète 'SHOW CREATE TABLE' est disponible à partir de MySQL 3.23.20
		//
		if( version_compare(mysql_get_client_info(), '3.23.20', '>=') == true )
		{
			if( !($result = $db->query('SHOW CREATE TABLE `' . $tabledata['name'] . '`')) )
			{
				trigger_error('Impossible d\'obtenir la structure de la table', ERROR);
			}
			
			$create_table = $db->result($result, 0, 'Create Table');
			$create_table = preg_replace("/(\r\n?)|\n/", $this->eol, $create_table);
			
			if( !$this->protect_name )
			{
				$create_table = str_replace('`', '', $create_table);
			}
			
			$contents .= $create_table;
			
			$db->free_result($result);
		}
		else
		{
			$contents .= 'CREATE TABLE ' . $quote . $tabledata['name'] . $quote . ' (' . $this->eol;
			
			if( !($result = $db->query('SHOW FIELDS FROM ' . $quote . $tabledata['name'] . $quote)) )
			{
				trigger_error('Impossible d\'obtenir les noms des colonnes de la table', ERROR);
			}
			
			$end_line = false;
			while( $row = $db->fetch_array($result) )
			{
				if( $end_line )
				{
					$contents .= ',' . $this->eol;
				}
				
				$contents .= "\t" . $quote . $row['Field'] . $quote . ' ' . $row['Type'];
				$contents .= ( !empty($row['Default']) ) ? ' DEFAULT \'' . $row['Default'] . '\'' : '';
				$contents .= ( $row['Null'] != 'YES' ) ? ' NOT NULL' : '';
				$contents .= ( $row['Extra'] != '' ) ? ' ' . $row['Extra'] : '';
				
				$end_line = true;
			}
			$db->free_result($result);
			
			if( !($result = $db->query('SHOW KEYS FROM ' . $quote . $tabledata['name'] . $quote)) )
			{
				trigger_error('Impossible d\'obtenir les clés de la table', ERROR);
			}
			
			$index = array();
			while( $row = $db->fetch_array($result) )
			{
				$name = $row['Key_name'];
				
				if( $name != 'PRIMARY' && $row['Non_unique'] == 0 )
				{
					$name = 'unique=' . $name;
				}
				
				if( !isset($index[$name]) )
				{
					$index[$name] = array();
				}
				
				$index[$name][] = $quote . $row['Column_name'] . $quote;
			}
			$db->free_result($result);
			
			foreach( $index AS $var => $columns )
			{
				$contents .= ',' . $this->eol . "\t";
				
				if( $var == 'PRIMARY' )
				{
					$contents .= 'PRIMARY KEY';
				}
				else if( ereg('^unique=(.+)$', $var, $regs) )
				{
					$contents .= 'UNIQUE ' . $quote . $regs[1] . $quote;
				}
				else
				{
					$contents .= 'KEY ' . $quote . $var . $quote;
				}
				
				$contents .= ' (' . $quote . implode($quote . ', ' . $quote, $columns) . $quote . ')';
			}
			
			$contents .= $this->eol . ')' . ( ( !empty($tabledata['type']) ) ? ' TYPE=' . $tabledata['type'] : '' );
		}
		
		return $contents . ';' . $this->eol;
	}
	
	function get_table_data($tablename)
	{
		global $db;
		
		$quote = ( $this->protect_name ) ? '`' : '';
		
		$contents = '';
		
		$sql = 'SELECT * FROM ' . $quote . $tablename . $quote;
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir le contenu de la table ' . $tablename, ERROR);
		}
		
		if( $row = $db->fetch_row($result) )
		{
			$contents  = $this->eol;
			$contents .= '-- ' . $this->eol;
			$contents .= '-- Contenu de la table ' . $tablename . ' ' . $this->eol;
			$contents .= '-- ' . $this->eol;
			
			$fields = array();
			$num_fields = $db->num_fields($result);
			for( $j = 0; $j < $num_fields; $j++ )
			{
				$fields[] = $db->field_name($j, $result);
			}
			
			$columns_list = implode($quote . ', ' . $quote, $fields);
			
			do
			{
				$contents .= 'INSERT INTO ' . $quote . $tablename . $quote . ' (' . $quote . $columns_list . $quote . ') VALUES';
				
				foreach( $row AS $key => $value )
				{
					if( !isset($value) )
					{
						$row[$key] = 'NULL';
					}
					else if( !is_numeric($value) )
					{
						$row[$key] = '\'' . $db->escape($value) . '\'';
					}
				}
				
				$contents .= '(' . implode(', ', $row) . ');' . $this->eol;
			}
			while( $row = $db->fetch_row($result) );
		}
		$db->free_result($result);
		
		return $contents;
	}
}

}
?>