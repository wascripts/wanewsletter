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
				@mysql_close($this->connect_id);
			}
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
			
			if( $this->trc_started )
			{
				$this->transaction('ROLLBACK');
			}
		}
		else
		{
			$this->sql_error = array('errno' => '', 'message' => '', 'query' => '');
		}
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
	
	function close_connexion()
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

?>