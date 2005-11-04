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

define('DATABASE', 'sqlite');

/**
 * @todo
 * - Attention à l'encodage
 * Si sqlite_libencoding() retourne UTF-8, faire une conversion vers le charset de 
 * configuration de Wanewsletter ?
 */
class sql {
	
	var $connect_id   = '';
	var $query_result = '';
	var $trc_started  = 0;
	
	var $sql_error    = array('errno' => '', 'message' => '', 'query' => '');
	
	var $queries      = 0;
	var $sql_time     = 0;
	
	function sql($dbpath, $dbuser = null, $dbpwd = null, $dbname = null, $persistent = false)
	{
		$sql_connect = ( $persistent ) ? 'sqlite_popen' : 'sqlite_open';
		
		$this->connect_id = @$sql_connect($dbpath, 0666);
		
		if( is_resource($this->connect_id) )
		{
			$this->query('PRAGMA short_column_names = 1');
			$this->query('PRAGMA case_sensitive_like = 0');
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
				$tmp = array(); 
				foreach( $sql_where AS $field => $value )
				{
					$tmp[] = $field . ' = ' . $this->prepare_value($value);
				}
				
				$query_string .= ' WHERE ' . implode(' AND ', $tmp);
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
		
		$this->query_result = sqlite_query($this->connect_id, $query);
		
		$endtime = explode(' ', microtime());
		$endtime = $endtime[0] + $endtime[1] - $starttime;
		
		$this->sql_time += ($endtime - $curtime);
		$this->queries++;
		
		if( !$this->query_result )
		{
			$this->sql_error['errno']   = @sqlite_last_error($this->connect_id);
			$this->sql_error['message'] = @sqlite_error_string($this->sql_error['errno']);
			$this->sql_error['query']   = $query;
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
					$result = @sqlite_exec($this->connect_id, 'BEGIN');
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
					
					if( !@sqlite_exec($this->connect_id, 'COMMIT') )
					{
						@sqlite_exec($this->connect_id, 'ROLLBACK');
						$result = false;
					}
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
					$result = @sqlite_exec($this->connect_id, 'ROLLBACK');
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
		
		foreach( $tables AS $tablename )
		{
			@sqlite_exec($this->connect_id, 'VACUUM ' . $tablename); 
		}
		
		return true;
	}
	
	function num_rows($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( is_resource($result) ) ? sqlite_num_rows($result) : false;
	}
	
	function affected_rows()
	{
		return ( is_resource($this->connect_id) ) ? sqlite_changes($this->connect_id) : false;
	}
	
	function fetch_row($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( is_resource($result) ) ? @sqlite_fetch_array($result, SQLITE_NUM) : false;
	}
	
	function fetch_array($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( is_resource($result) ) ? @sqlite_fetch_array($result, SQLITE_ASSOC) : false;
	}
	
	function fetch_rowset($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( is_resource($result) ) ? @sqlite_fetch_all($result, SQLITE_ASSOC) : false;
	}
	
	function num_fields($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( is_resource($result) ) ? sqlite_num_fields($result) : false;
	}
	
	function field_name($offset, $result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( is_resource($result) ) ? sqlite_field_name($result, $offset) : false;
	}
	
	function result($result, $row, $field = '')
	{
		sqlite_seek($result, $row);
		
		if( $field != '' )
		{
			$r = sqlite_column($result, $field);
		}
		else
		{
			$r = sqlite_current($result);
			$r = $r[0];
		}
		
		return $r;
	}
	
	function next_id()
	{
		return ( is_resource($this->connect_id) ) ? sqlite_last_insert_rowid($this->connect_id) : false;
	}
	
	function free_result($result = false)
	{
		// Nothing
	}
	
	function escape($str)
	{
		return sqlite_escape_string($str);
	}
	
	function close_connexion()
	{
		if( is_resource($this->connect_id) )
		{
			$this->transaction(END_TRC);
			sqlite_close($this->connect_id);
		}
	}
}

?>
