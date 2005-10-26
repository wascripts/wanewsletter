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

define('DATABASE', 'postgre'); 

class sql {

	var $connect_id   = ''; 
	var $query_result = ''; 
	var $trc_started  = 0;
	var $sql_error    = array('errno' => '', 'message' => '', 'query' => ''); 
	
	var $queries      = 0; 
	var $sql_time     = 0; 
	
	var $row_id       = array(); 
	var $last_insert_table = ''; 
	
	var $dbport       = 5432; 
	
	function sql($dbhost, $dbuser, $dbpwd, $dbname, $persistent = false)
	{
		$sql_connect = ( $persistent ) ? 'pg_pconnect' : 'pg_connect'; 
		
		$login_str = ''; 
		
		if( strpos($dbhost, ':') )
		{
			list($dbhost, $dbport) = explode(':', $dbhost); 
			$login_str .= "host='$dbhost' port='$dbport' "; 
		}
		else
		{
			$login_str .= "host='$dbhost' port='" . $this->dbport . "' "; 
		}
		
		if( $dbname != '' )
		{
			$login_str .= "dbname='$dbname' "; 
		}
		
		if( $dbuser != '' )
		{
			$login_str .= "user='$dbuser' "; 
		}
		
		if( $dbpwd != '' )
		{
			$login_str .= "password='$dbpwd' "; 
		}
		
		$this->connect_id = @$sql_connect($login_str); 
		
		return $this->connect_id; 
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
			$fields[] = $field; 
			$values[] = $this->prepare_value($value);
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
			$query .= ' LIMIT ' . $limit . ' OFFSET ' . $start; 
		}
		else if( preg_match("/^INSERT[\t\r\n ]+INTO[\t\r\n ]+([a-z0-9_-]+)/si", $query, $match) )
		{
			$this->last_insert_table = $match[1]; 
		}
		
		$curtime = explode(' ', microtime()); 
		$curtime = $curtime[0] + $curtime[1] - $starttime; 
		
		$this->query_result = @pg_exec($this->connect_id, $query); 
		
		$endtime = explode(' ', microtime()); 
		$endtime = $endtime[0] + $endtime[1] - $starttime; 
		
		$this->sql_time += ($endtime - $curtime); 
		$this->queries++; 
		
		if( $this->query_result )
		{
			$this->row_id[$this->query_result] = 0; 
			$this->sql_error = array('errno' => '', 'message' => '', 'query' => ''); 
			
			return $this->query_result;
		}
		else
		{
			$this->sql_error['errno']   = 0; 
			$this->sql_error['message'] = @pg_errormessage($this->connect_id); 
			$this->sql_error['query']   = $query; 
			
			if( $this->trc_started )
			{
				$this->transaction('ROLLBACK'); 
			}
			
			return false;
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
					$result = @pg_exec($this->connect_id, 'BEGIN'); 
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
					
					if( !($result = @pg_exec($this->connect_id, 'COMMIT')) )
					{
						@pg_exec($this->connect_id, 'ROLLBACK'); 
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
					$result = @pg_exec($this->connect_id, 'ROLLBACK'); 
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
			@pg_exec($this->connect_id, 'VACUUM ' . $tablename); 
		}
		
		return true; 
	}
	
	function num_rows($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( $result ) ? @pg_numrows($result) : false;
	}
	
	function affected_rows()
	{
		return ( $this->query_result ) ? @pg_cmdtuples($this->query_result) : false;
	}
	
	function fetch_row($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		$row = @pg_fetch_row($result, $this->row_id[$result]); 
		
		if( $row )
		{
			$this->row_id[$result]++; 
			return $row; 
		}
		
		return false; 
	}
	
	function fetch_array($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		$row = @pg_fetch_array($result, $this->row_id[$result], PGSQL_ASSOC); 
		
		if( $row )
		{
			$this->row_id[$result]++; 
			return $row; 
		}
		
		return false; 
	}
	
	function fetch_rowset($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result; 
		}
		
		$rowset = array(); 
		while( $row = @pg_fetch_array($result, $this->row_id[$result], PGSQL_ASSOC) )
		{
			$rowset[] = $row; 
			$this->row_id[$result]++; 
		}
		
		return $rowset; 
	}
	
	function num_fields($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( $result ) ? @pg_numfields($result) : false;
	}
	
	function field_name($offset, $result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}

		return ( $result ) ? @pg_fieldname($result, $offset) : false;
	}
	
	function result($result, $row_id, $field = '')
	{
		if( $field != '' )
		{
			return @pg_result($result, $row_id, $field);
		}
		else
		{
			return @pg_result($result, $row_id); 
		}
	}
	
	function next_id()
	{
		if( $this->query_result )
		{
			$query = "SELECT currval('" . $this->last_insert_table . "_id_seq') AS last_value";
			$result_next_id =  @pg_exec($this->connect_id, $query);
			if( $result_next_id )
			{
				$row_next_id = @pg_fetch_array($result_next_id, 0, PGSQL_ASSOC); 
				return ( $row_next_id ) ? $row_next_id['last_value'] : false; 
			}
		}
		
		return false; 
	}
	
	function free_result($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		if( is_resource($result) )
		{
			@pg_freeresult($result); 
		}
	}
	
	function escape($str)
	{
		return pg_escape_string($str); 
	}
	
	function close_connexion()
	{
		if( $this->connect_id )
		{
			$this->free_result($this->query_result);
			$this->transaction(END_TRC);
			
			return @pg_close($this->connect_id); 
		}
		else
		{
			return false;
		}
	}
}

?>