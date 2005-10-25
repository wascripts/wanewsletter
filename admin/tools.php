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

define('IN_NEWSLETTER', true);

require './pagestart.php';

/**
 * Classe zip importée de phpMyAdmin 2.6.4
 * 
 * Zip file creation class.
 * Makes zip files.
 * 
 * Based on :
 * 
 *	http://www.zend.com/codex.php?id=535&single=1
 *	By Eric Mueller <eric@themepark.com>
 * 
 *	http://www.zend.com/codex.php?id=470&single=1
 *	by Denis125 <webmaster@atlant.ru>
 * 
 *	a patch from Peter Listiak <mlady@users.sourceforge.net> for last modified
 *	date and time of the compressed file
 * 
 * Official ZIP file format: http://www.pkware.com/appnote.txt
 * 
 * @access	public
 */
class zipfile {

	/**
	 * Array to store compressed data
	 * 
	 * @var	 array	  $datasec
	 */
	var $datasec      = array();
	
	/**
	 * Central directory
	 * 
	 * @var	 array	  $ctrl_dir
	 */
	var $ctrl_dir     = array();
	
	/**
	 * End of central directory record
	 * 
	 * @var	 string	  $eof_ctrl_dir
	 */
	var $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";
	
	/**
	 * Last offset position
	 * 
	 * @var	 integer  $old_offset
	 */
	var $old_offset   = 0;
	
	/**
	 * Converts an Unix timestamp to a four byte DOS date and time format (date
	 * in high two bytes, time in low two bytes allowing magnitude comparison).
	 * 
	 * @param  integer	the current Unix timestamp
	 * 
	 * @return integer	the current date in a four byte DOS format
	 * 
	 * @access private
	 */
	function unix2DosTime($unixtime = 0)
	{
		$timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);
		
		if ($timearray['year'] < 1980)
		{
			$timearray['year']    = 1980;
			$timearray['mon']     = 1;
			$timearray['mday']    = 1;
			$timearray['hours']   = 0;
			$timearray['minutes'] = 0;
			$timearray['seconds'] = 0;
		} // end if
		
		return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) |
				($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
	} // end of the 'unix2DosTime()' method
	
	/**
	 * Adds "file" to archive
	 * 
	 * @param  string	file contents
	 * @param  string	name of the file in the archive (may contains the path)
	 * @param  integer	the current timestamp
	 * 
	 * @access public
	 */
	function addFile($data, $name, $time = 0)
	{
		$name     = str_replace('\\', '/', $name);
		
		$dtime    = dechex($this->unix2DosTime($time));
		$hexdtime = '\x' . $dtime[6] . $dtime[7]
				  . '\x' . $dtime[4] . $dtime[5]
				  . '\x' . $dtime[2] . $dtime[3]
				  . '\x' . $dtime[0] . $dtime[1];
		eval('$hexdtime = "' . $hexdtime . '";');
		
		$fr	   = "\x50\x4b\x03\x04";
		$fr	  .= "\x14\x00";         // ver needed to extract
		$fr	  .= "\x00\x00";         // gen purpose bit flag
		$fr	  .= "\x08\x00";         // compression method
		$fr	  .= $hexdtime;          // last mod time and date
		
		// "local file header" segment
		$unc_len = strlen($data);
		$crc	 = crc32($data);
		$zdata	 = gzcompress($data);
		$zdata	 = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
		$c_len	 = strlen($zdata);
		$fr	    .= pack('V', $crc);           // crc32
		$fr	    .= pack('V', $c_len);         // compressed filesize
		$fr	    .= pack('V', $unc_len);       // uncompressed filesize
		$fr	    .= pack('v', strlen($name));  // length of filename
		$fr	    .= pack('v', 0);              // extra field length
		$fr	    .= $name;
		
		// "file data" segment
		$fr .= $zdata;
		
		// "data descriptor" segment (optional but necessary if archive is not
		// served as file)
		// nijel(2004-10-19): this seems not to be needed at all and causes
		// problems in some cases (bug #1037737)
		//$fr .= pack('V', $crc);                  // crc32
		//$fr .= pack('V', $c_len);                // compressed filesize
		//$fr .= pack('V', $unc_len);              // uncompressed filesize
		
		// add this entry to array
		$this->datasec[] = $fr;
		
		// now add to central directory record
		$cdrec	= "\x50\x4b\x01\x02";
		$cdrec .= "\x00\x00";                 // version made by
		$cdrec .= "\x14\x00";                 // version needed to extract
		$cdrec .= "\x00\x00";                 // gen purpose bit flag
		$cdrec .= "\x08\x00";                 // compression method
		$cdrec .= $hexdtime;                  // last mod time & date
		$cdrec .= pack('V', $crc);            // crc32
		$cdrec .= pack('V', $c_len);          // compressed filesize
		$cdrec .= pack('V', $unc_len);        // uncompressed filesize
		$cdrec .= pack('v', strlen($name));   // length of filename
		$cdrec .= pack('v', 0);               // extra field length
		$cdrec .= pack('v', 0);               // file comment length
		$cdrec .= pack('v', 0);               // disk number start
		$cdrec .= pack('v', 0);               // internal file attributes
		$cdrec .= pack('V', 32);              // external file attributes - 'archive' bit set

		$cdrec .= pack('V', $this->old_offset); // relative offset of local header
		$this->old_offset += strlen($fr);

		$cdrec .= $name;

		// optional extra field, file comment goes here
		// save to central directory
		$this->ctrl_dir[] = $cdrec;
	} // end of the 'addFile()' method
	
	/**
	 * Dumps out file
	 * 
	 * @return	string	the zipped file
	 * 
	 * @access public
	 */
	function file()
	{
		$data    = implode('', $this->datasec);
		$ctrldir = implode('', $this->ctrl_dir);

		return
			$data .
			$ctrldir .
			$this->eof_ctrl_dir .
			pack('v', sizeof($this->ctrl_dir)) .  // total # of entries "on this disk"
			pack('v', sizeof($this->ctrl_dir)) .  // total # of entries overall
			pack('V', strlen($ctrldir)) .         // size of central dir
			pack('V', strlen($data)) .            // offset to start of central dir
			"\x00\x00";                           // .zip file comment length
	} // end of the 'file()' method

} // end of the 'zipfile' class


//
// MySQL 3.x/4.x
//
class mysql_backup {
	
	var $show_create = FALSE;
	var $crlf        = '';
	
	/**
	 * Protection des noms de table et de colonnes avec un quote inversé ( ` )
	 * 
	 * @access public
	 */
	var $protect_name = TRUE;
	
	function mysql_backup($crlf)
	{
		$this->crlf = $crlf;
		
		//
		// La requète 'SHOW CREATE TABLE' est disponible à partir de MySQL 3.23.20
		//
		if( DATABASE == 'mysql4' || version_compare(mysql_get_client_info(), '3.23.20', '>=') == true )
		{
			$this->show_create = TRUE;
		}
		else
		{
			$this->show_create = FALSE;
		}
	}
	
	function file_header($dbhost, $dbname)
	{
		global $nl_config;
		
		$contents  = '# ' . $this->crlf;
		$contents .= '# WAnewsletter ' . $nl_config['version'] . ' MySQL Dump ' . $this->crlf;
		$contents .= '# ' . $this->crlf;
		$contents .= "# Serveur  : $dbhost " . $this->crlf;
		$contents .= "# Database : $dbname " . $this->crlf;
		$contents .= '# Date     : ' . date('d/m/Y H:i:s') . ' ' . $this->crlf;
		$contents .= '#' . $this->crlf . $this->crlf;
		
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
		
		$contents  = '# ' . $this->crlf;
		$contents .= '# Struture de la table ' . $tabledata['name'] . ' ' . $this->crlf;
		$contents .= '# ' . $this->crlf;
		
		if( $drop_option )
		{
			$contents .= 'DROP TABLE IF EXISTS ' . $quote . $tabledata['name'] . $quote . ';' . $this->crlf;
		}
		
		if( $this->show_create )
		{
			if( !($result = $db->query('SHOW CREATE TABLE `' . $tabledata['name'] . '`')) )
			{
				trigger_error('Impossible d\'obtenir la structure de la table', ERROR);
			}
			
			$create_table = $db->result($result, 0, 'Create Table');
			$create_table = preg_replace("/(\r\n?)|\n/", $this->crlf, $create_table);
			
			if( !$this->protect_name )
			{
				$create_table = str_replace('`', '', $create_table);
			}
			
			$contents .= $create_table;
			
			$db->free_result($result);
		}
		else
		{
			$contents .= 'CREATE TABLE ' . $quote . $tabledata['name'] . $quote . ' (' . $this->crlf;
			
			if( !($result = $db->query('SHOW FIELDS FROM ' . $quote . $tabledata['name'] . $quote)) )
			{
				trigger_error('Impossible d\'obtenir les noms des colonnes de la table', ERROR);
			}
			
			$end_line = false;
			while( $row = $db->fetch_array($result) )
			{
				if( $end_line )
				{
					$contents .= ',' . $this->crlf;
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
				$contents .= ',' . $this->crlf . "\t";
				
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
			
			$contents .= $this->crlf . ')' . ( ( !empty($tabledata['type']) ) ? ' TYPE=' . $tabledata['type'] : '' );
		}
		
		return $contents . ';' . $this->crlf;
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
			$contents  = $this->crlf;
			$contents .= '# ' . $this->crlf;
			$contents .= '# Contenu de la table ' . $tablename . ' ' . $this->crlf;
			$contents .= '# ' . $this->crlf;
			
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
				
				$contents .= '(' . implode(', ', $row) . ');' . $this->crlf;
			}
			while( $row = $db->fetch_row($result) );
		}
		$db->free_result($result);
		
		return $contents;
	}
}

//
// PostgreSQL
// - Basé sur phpPgAdmin 2.4.2
//
class postgre_backup {
	
	var $crlf = '';
	
	function postgre_backup($crlf)
	{
		$this->crlf = $crlf;
	}
	
	function file_header($dbhost, $dbname)
	{
		global $nl_config;
		
		$contents  = '/* ------------------------------------------------------------ ' . $this->crlf;
		$contents .= '  WAnewsletter ' . $nl_config['version'] . ' PostgreSQL Dump ' . $this->crlf;
		$contents .= $this->crlf;
		$contents .= "  Serveur	 : $dbhost " . $this->crlf;
		$contents .= "  Database : $dbname " . $this->crlf;
		$contents .= '  Date	 : ' . date('d/m/Y H:i:s') . ' ' . $this->crlf;
		$contents .= ' ------------------------------------------------------------ */' . $this->crlf . $this->crlf;
		
		return $contents;
	}
	
	function get_tables($dbname)
	{
		global $db;
		
		$sql = "SELECT tablename 
			FROM pg_tables 
			WHERE tablename NOT LIKE 'pg%' 
			ORDER BY tablename";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir la liste des tables', ERROR);
		}
		
		$tables = array();
		while( $row = $db->fetch_row($result) )
		{
			$tables[$row[0]] = '';
		}
		$db->free_result($result);
		
		return $tables;
	}
	
	function get_sequences($drop_option)
	{
		global $db, $backup_type;
		
		$sql = "SELECT relname 
			FROM pg_class 
			WHERE NOT relname ~ 'pg_.*' AND relkind ='S' 
			ORDER BY relname";
		if( !($result_seq = $db->query($sql)) )
		{
			trigger_error('Impossible de récupérer les séquences', ERROR);
		}
		
		$num_seq = $db->num_rows($result_seq);
		
		$contents = '';
		
		for( $i = 0; $i < $num_seq; $i++ )
		{
			$sequence = $db->result($result_seq, $i, 'relname');
			$result   = $db->query('SELECT * FROM ' . $sequence);
			
			if( $row = $db->fetch_array($result) )
			{
				if( $drop_option )
				{
					$contents .= "DROP SEQUENCE $sequence;" . $this->crlf;
				}
				
				$contents .= 'CREATE SEQUENCE ' . $sequence . ' start ' . $row['last_value'] . ' increment ' . $row['increment_by'] . ' maxvalue ' . $row['max_value'] . ' minvalue ' . $row['min_value'] . ' cache ' . $row['cache_value'] . '; ' . $this->crlf;
				
				if( $row['last_value'] > 1 && $backup_type != 1 )
				{
					$contents .= 'SELECT NEXTVALE(\'' . $sequence . '\'); ' . $this->crlf;
				}
			}
		}
		
		return $contents;
	}
	
	function get_table_structure($tabledata, $drop_option)
	{
		global $db;
	
		$contents  = '/* ------------------------------------------------------------ ' . $this->crlf;
		$contents .= '  Sequences ' . $this->crlf;
		$contents .= ' ------------------------------------------------------------ */' . $this->crlf;
		$contents .= $this->get_sequences($drop_option);
		
		$contents .= $this->crlf;
		$contents .= '/* ------------------------------------------------------------ ' . $this->crlf;
		$contents .= '  Struture de la table ' . $tabledata['name'] . ' ' . $this->crlf;
		$contents .= ' ------------------------------------------------------------ */' . $this->crlf;
		
		if( $drop_option )
		{
			$contents .= 'DROP TABLE IF EXISTS ' . $tabledata['name'] . ';' . $this->crlf;
		}
		
		$sql = "SELECT a.attnum, a.attname AS field, t.typname as type, a.attlen AS length, 
				a.atttypmod as lengthvar, a.attnotnull as notnull 
			FROM pg_class c, pg_attribute a, pg_type t 
			WHERE c.relname = '" . $tabledata['name'] . "' 
				AND a.attnum > 0 
				AND a.attrelid = c.oid 
				AND a.atttypid = t.oid 
			ORDER BY a.attnum";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir le contenu de la table ' . $tabledata['name'], ERROR);
		}
		
		$contents .= 'CREATE TABLE ' . $tabledata['name'] . ' (' . $this->crlf;
		
		while( $row = $db->fetch_array($result) )
		{
			$sql = "SELECT d.adsrc AS rowdefault 
				FROM pg_attrdef d, pg_class c 
				WHERE (c.relname = '" . $tabledata['name'] . "') 
					AND (c.oid = d.adrelid) 
					AND d.adnum = " . $row['attnum'];
			if( $res = $db->query($sql) )
			{
				$row['rowdefault'] = $db->result($res, 0, 'rowdefault');
			}
			else
			{
				unset($row['rowdefault']);
			}
			
			if( $row['type'] == 'bpchar' )
			{
				// Internally stored as bpchar, but isn't accepted in a CREATE TABLE statement.
				$row['type'] = 'char';
			}
			
			$contents .= ' ' . $row['field'] . ' ' . $row['type'];
			
			if( eregi('char', $row['type']) && $row['lengthvar'] > 0 )
			{
				$contents .= '(' . ($row['lengthvar'] - 4) . ')';
			}
			else if( eregi('numeric', $row['type']) )
			{
				$contents .= sprintf('(%s,%s)', (($row['lengthvar'] >> 16) & 0xffff), (($row['lengthvar'] - 4) & 0xffff));
			}
			
			if (!empty($row['rowdefault']))
			{
				$contents .= ' DEFAULT \'' . $row['rowdefault'] . '\'';
			}
			
			if ($row['notnull'] == 't')
			{
				$contents .= ' NOT NULL';
			}
			
			$contents .= ',' . $this->crlf;
		}
		
		//
		// Generate constraint clauses for UNIQUE and PRIMARY KEY constraints
		//
		$sql = "SELECT ic.relname AS index_name, bc.relname AS tab_name, ta.attname AS column_name, 
				i.indisunique AS unique_key, i.indisprimary AS primary_key 
			FROM pg_class bc, pg_class ic, pg_index i, pg_attribute ta, pg_attribute ia 
			WHERE (bc.oid = i.indrelid) 
				AND (ic.oid = i.indexrelid) 
				AND (ia.attrelid = i.indexrelid) 
				AND (ta.attrelid = bc.oid)
				AND (bc.relname = '" . $tabledata['name'] . "') 
				AND (ta.attrelid = i.indrelid) 
				AND (ta.attnum = i.indkey[ia.attnum-1]) 
			ORDER BY index_name, tab_name, column_name";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible de récupérer les clés primaires et unique de la table ' . $tabledata['name'], ERROR);
		}
		
		$primary_key = '';
		$index_rows	 = array();
		
		while( $row = $db->fetch_array($result) )
		{
			if( $row['primary_key'] == 't' )
			{
				$primary_key .= ( ( $primary_key != '' ) ? ', ' : '' ) . $row['column_name'];
				$primary_key_name = $row['index_name'];
			}
			else
			{
				//
				// We have to store this all this info because it is possible to have a multi-column key...
				// we can loop through it again and build the statement
				//
				$index_rows[$row['index_name']]['table']  = $tabledata['name'];
				$index_rows[$row['index_name']]['unique'] = ($row['unique_key'] == 't') ? ' UNIQUE ' : '';
				
				if( empty($index_rows[$row['index_name']]['column_names']) )
				{
					$index_rows[$row['index_name']]['column_names'] = $row['column_name'] . ', ';
				}
				else
				{
					$index_rows[$row['index_name']]['column_names'] .= $row['column_name'] . ', ';
				}
			}
		}
		
		$index_create = '';
		if( count($index_rows) )
		{
			foreach( $index_rows AS $idx_name => $props )
			{
				$props['column_names'] = ereg_replace(', $', '', $props['column_names']);
				$index_create .= 'CREATE ' . $props['unique'] . " INDEX $idx_name ON " . $tabledata['name'] . " (" . $props['column_names'] . ');' . $this->crlf;
			}
		}
		
		if( !empty($primary_key) )
		{
			$contents .= "CONSTRAINT $primary_key_name PRIMARY KEY ($primary_key)," . $this->crlf;
		}
		
		//
		// Generate constraint clauses for CHECK constraints
		//
		$sql = "SELECT rcname as index_name, rcsrc 
			FROM pg_relcheck, pg_class bc 
			WHERE rcrelid = bc.oid 
				AND bc.relname = '" . $tabledata['name'] . "' 
				AND NOT EXISTS (
					SELECT * 
					FROM pg_relcheck as c, pg_inherits as i 
					WHERE i.inhrelid = pg_relcheck.rcrelid 
						AND c.rcname = pg_relcheck.rcname 
						AND c.rcsrc = pg_relcheck.rcsrc 
						AND c.rcrelid = i.inhparent
				)";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible de récupérer les clauses de contraintes de la table ' . $tabledata['name'], ERROR);
		}
		
		//
		// Add the constraints to the sql file.
		//
		while( $row = $db->fetch_array($result) )
		{
			$contents .= 'CONSTRAINT ' . $row['index_name'] . ' CHECK ' . $row['rcsrc'] . ',' . $this->crlf;
		}
		
		$contents = ereg_replace(',' . $this->crlf . '$', '', $contents);
		$index_create = ereg_replace(',' . $this->crlf . '$', '', $index_create);
		
		$contents .= $this->crlf . ');' . $this->crlf;
		
		if( !empty($index_create) )
		{
			$contents .= $this->crlf . $index_create;
		}
		
		return $contents;
	}
	
	function get_table_data($tablename)
	{
		global $db;
		
		$contents = '';
		
		$sql = 'SELECT * FROM ' . $tablename;
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir le contenu de la table ' . $tablename, ERROR);
		}
		
		if( $row = $db->fetch_row($result) )
		{
			$contents  = $this->crlf;
			$contents .= '/* ------------------------------------------------------------ ' . $this->crlf;
			$contents .= '  Contenu de la table ' . $tablename . ' ' . $this->crlf;
			$contents .= ' ------------------------------------------------------------ */' . $this->crlf;
			
			$fields = array();
			$num_fields = $db->num_fields($result);
			for( $j = 0; $j < $num_fields; $j++ )
			{
				$fields[] = $db->field_name($j, $result);
			}
			
			$columns_list = implode(', ', $fields);
			
			do
			{
				$contents .= "INSERT INTO $tablename ($columns_list) VALUES";
				
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
				
				$contents .= '(' . implode(', ', $row) . ');' . $this->crlf;
			}
			while( $row = $db->fetch_row($result) );
		}
		$db->free_result($result);
		
		return $contents;
	}
}

//
// Compression éventuelle des données et réglage du mime-type en conséquence
//
function compress_filedata(&$filename, &$mime_type, $contents, $compress)
{
	switch( $compress )
	{
		case 'zip':
			$mime_type = 'application/zip';
			$zip = new zipfile;
			$zip->addFile($contents, $filename, time());
			$contents  = $zip->file();
			$filename .= '.zip';
			break;
		
		case 'gzip':
			$mime_type = 'application/x-gzip-compressed';
			$contents  = gzencode($contents);
			$filename .= '.gz';
			break;
		
		case 'bz2':
			$mime_type = 'application/x-bzip';
			$contents  = bzcompress($contents);
			$filename .= '.bz2';
			break;
		
		default:
			$mime_type = 'text/plain';
			break;
	}
	
	return $contents;
}

//
// Lecture et décompression éventuelle des données
//
function decompress_filedata($filename, $file_ext)
{
	if( $file_ext != 'zip' )
	{
		switch( $file_ext )
		{
			case 'gz':
				$open  = 'gzopen';
				$eof   = 'gzeof';
				$gets  = 'gzgets';
				$close = 'gzclose';
				break;
			
			case 'bz2':
			case 'txt':
			case 'sql':
				$open  = 'fopen';
				$eof   = 'feof';
				$gets  = 'fgets';
				$close = 'fclose';
				break;
		}
		
		if( !($fp = @$open($filename, 'rb')) )
		{
			trigger_error('Failed_open_file', ERROR);
		}
		
		$data = '';
		while( !@$eof($fp) )
		{
			$data .= $gets($fp, 1024);
		}
		$close($fp);
		
		if( $file_ext == 'bz2' )
		{
			$data = bzdecompress($data);
		}
	}
	else
	{
		if( !($zip = zip_open($filename)) )
		{
			trigger_error('Failed_open_file', ERROR);
		}
		
		$zip_entry = zip_read($zip);
		if( !zip_entry_open($zip, $zip_entry, 'rb') )
		{
			trigger_error('Failed_open_file', ERROR);
		}
		
		$data = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
		zip_entry_close($zip_entry);
		zip_close($zip);
	}
	
	return $data;
}

$mode     = ( !empty($_REQUEST['mode']) ) ? $_REQUEST['mode'] : '';
$format   = ( !empty($_POST['format']) ) ? intval($_POST['format']) : FORMAT_TEXTE;
$glue     = ( !empty($_POST['glue']) ) ? trim($_POST['glue']) : '';
$action   = ( !empty($_POST['action']) ) ? $_POST['action'] : 'download';
$compress = ( !empty($_POST['compress']) ) ? $_POST['compress'] : 'none';

$file_local  = ( !empty($_POST['file_local']) ) ? trim($_POST['file_local']) : '';
$file_upload = ( !empty($_FILES['file_upload']) ) ? $_FILES['file_upload'] : array();

switch( $mode )
{
	case 'export':
		$auth_type = AUTH_EXPORT;
		break;
	
	case 'import':
		$auth_type = AUTH_IMPORT;
		break;
	
	case 'ban':
		$auth_type = AUTH_BAN;
		break;
	
	case 'backup':
	case 'restore':
		//
		// Les modules de sauvegarde et restauration 
		// supportent actuellement MySQL 3.x ou 4.x, et PostgreSQL
		//
		$classname = ( ( DATABASE == 'mysql4' ) ? 'mysql' : DATABASE ) . '_backup';
		
		if( !class_exists($classname) )
		{
			trigger_error('Database_unsupported', MESSAGE);
		}
		
	case 'attach':
		if( $admindata['admin_level'] != ADMIN )
		{
			$output->redirect('./index.php', 4);
			
			$message  = $lang['Message']['Not_authorized'];
			$message .= '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . sessid('./index.php') . '">', '</a>');
			trigger_error($message, MESSAGE);
		}
		
	case 'generator':
		$auth_type = AUTH_VIEW;
		break;
	
	default:
		$mode = '';
		$auth_type = AUTH_VIEW;
		break;
}

$url_page  = './tools.php';
$url_page .= ( $mode != '' ) ? '?mode=' . $mode : '';

if( $mode != 'backup' && $mode != 'restore' && !$admindata['session_liste'] )
{
	$output->build_listbox($auth_type, true, $url_page);
}
else if( $admindata['session_liste'] )
{
	if( !$auth->check_auth($auth_type, $admindata['session_liste']) )
	{
		trigger_error('Not_' . $auth->auth_ary[$auth_type], MESSAGE);
	}
	
	$listdata = $auth->listdata[$admindata['session_liste']];
}

if( !isset($_POST['submit']) )
{
	if( $mode != 'backup' && $mode != 'restore' )
	{
		$output->build_listbox($auth_type, false, $url_page);
	}
	
	$tools_ary = array('export', 'import', 'ban', 'generator');
	
	if( $admindata['admin_level'] == ADMIN )
	{
		array_push($tools_ary, 'attach', 'backup', 'restore');
	}
	
	$tools_box = '<select id="mode" name="mode">';
	foreach( $tools_ary AS $tool_name )
	{
		$selected = ( $mode == $tool_name ) ? ' selected="selected"' : '';
		$tools_box .= '<option value="' . $tool_name . '"' . $selected . '> - ' . $lang['Title'][$tool_name] . ' - </option>';
	}
	$tools_box .= '</select>';
	
	$output->page_header();
	
	if( $session->sessid_url != '' )
	{
		$output->addHiddenField('sessid', $session->session_id);
	}
	
	$output->set_filenames(array(
		'body' => 'tools_body.tpl'
	));
	
	$output->assign_vars(array(
		'L_TITLE'        => $lang['Title']['tools'],
		'L_EXPLAIN'      => nl2br($lang['Explain']['tools']),
		'L_SELECT_TOOL'  => $lang['Select_tool'],
		'L_VALID_BUTTON' => $lang['Button']['valid'],
		
		'S_TOOLS_BOX'    => $tools_box,
		'S_TOOLS_HIDDEN_FIELDS' => $output->getHiddenFields()
	));
}

//
// On vérifie la présence des extensions nécessaires pour les différents formats de fichiers proposés
//
$zziplib_loaded = is_available_extension('zip');
$zlib_loaded    = is_available_extension('zlib');
$bzip2_loaded   = is_available_extension('bz2');

if( WA_USER_OS == 'win' )
{
	$crlf = "\r\n";
}
else if( WA_USER_OS == 'mac' )
{
	$crlf = "\r";
}
else
{
	$crlf = "\n";
}

//
// On augmente le temps d'exécution du script 
// Certains hébergeurs empèchent pour des raisons évidentes cette possibilité
// Si c'est votre cas, vous êtes mal barré 
//
if( !is_disabled_func('set_time_limit') )
{
	@set_time_limit(1200);
}

switch( $mode )
{
	case 'export':
		if( isset($_POST['submit']) )
		{
			if( $action == 'store' && !is_writable(wa_tmp_path) )
			{
				trigger_error('tmp_dir_not_writable', MESSAGE);
			}
			
			$glue = ( $glue != '' ) ? $glue : $crlf;
			
			$sql = "SELECT a.abo_email 
				FROM " . ABONNES_TABLE . " AS a, " . ABO_LISTE_TABLE . " AS al
				WHERE al.liste_id = $listdata[liste_id]
					AND a.abo_id = al.abo_id
					AND a.abo_status = " . ABO_ACTIF;
			$sql .= ( $listdata['liste_format'] == FORMAT_MULTIPLE ) ? ' AND al.format = ' . $format : '';
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible d\'obtenir la liste des emails à exporter', ERROR);
			}
			
			$contents = '';
			while( $row = $db->fetch_array($result) )
			{
				$contents .= ( $contents != '' ) ? $glue : '';
				$contents .= $row['abo_email'];
			}
			$db->free_result($result);
			
			$filename  = 'wa_export_' . $admindata['session_liste'] . '.txt';
			$mime_type = '';
			
			//
			// Préparation des données selon l'option demandée 
			//
			$contents = compress_filedata($filename, $mime_type, $contents, $compress);
			
			if( $action == 'download' )
			{
				include WA_PATH . 'includes/class.attach.php';
				
				Attach::send_file($filename, $mime_type, $contents);
			}
			else
			{
				if( !($fw = @fopen(wa_tmp_path . '/' . $filename, 'wb')) )
				{
					trigger_error('Impossible d\'écrire le fichier de sauvegarde', ERROR);
				}
				
				fwrite($fw, $contents);
				fclose($fw);
				
				trigger_error('Success_export', MESSAGE);
			}
		}
		
		$output->addHiddenField('sessid', $session->session_id);
		
		$output->set_filenames(array(
			'tool_body' => 'export_body.tpl'
		));
		
		$output->assign_vars(array(
			'L_TITLE_EXPORT'    => $lang['Title']['export'],
			'L_EXPLAIN_EXPORT'  => nl2br($lang['Explain']['export']),
			'L_GLUE'            => $lang['Char_glue'],
			'L_ACTION'          => $lang['File_action'],
			'L_DOWNLOAD'        => $lang['Download_action'],
			'L_STORE_ON_SERVER' => $lang['Store_action'],
			'L_VALID_BUTTON'    => $lang['Button']['valid'],
			'L_RESET_BUTTON'    => $lang['Button']['reset'],
			
			'S_HIDDEN_FIELDS'   => $output->getHiddenFields()
		));
		
		if( $zlib_loaded || $bzip2_loaded )
		{
			$output->assign_block_vars('compress_option', array(
				'L_COMPRESS' => $lang['Compress'],
				'L_NO'       => $lang['No']
			)); 
			
			if( $zlib_loaded )
			{
				$output->assign_block_vars('compress_option.gzip_compress', array());
			}
			
			if( $bzip2_loaded )
			{
				$output->assign_block_vars('compress_option.bz2_compress', array());
			}
		}
		
		if( $listdata['liste_format'] == FORMAT_MULTIPLE )
		{
			include WA_PATH . 'includes/functions.box.php';
			
			$output->assign_block_vars('format_box', array(
				'L_FORMAT'	 => $lang['Format_to_export'],
				'FORMAT_BOX' => format_box('format')
			));
		}
		
		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;
	
	case 'import':
		if( isset($_POST['submit']) )
		{
			$list_email = ( !empty($_POST['list_email']) ) ? trim($_POST['list_email']) : '';
			$list_tmp   = '';
			
			//
			// Import via upload ou fichier local ? 
			//
			if( !empty($file_local) || !empty($file_upload['name']) )
			{
				$unlink = false;
				
				if( !empty($file_local) )
				{
					//$file_local   = str_replace('\\\\', '\\', str_replace('\\\'', '\'', $file_local));
					
					$tmp_filename = wa_realpath(WA_PATH . str_replace('\\', '/', $file_local));
					$filename     = $file_local;
					
					if( !file_exists($tmp_filename) )
					{
						$output->redirect('./tools.php?mode=import', 4);
						
						$message  = sprintf($lang['Message']['Error_local'], htmlspecialchars($filename));
						$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./tools.php?mode=import') . '">', '</a>');
						trigger_error($message, MESSAGE);
					}
				}
				else
				{
					$tmp_filename = $file_upload['tmp_name'];//str_replace('\\\\', '\\', $file_upload['tmp_name']);
					$filename     = $file_upload['name'];
					
					if( !is_uploaded_file($tmp_filename) )
					{
						trigger_error('Upload_error_5', MESSAGE);
					}
					
					//
					// Si nous avons un accés restreint à cause de open_basedir, le fichier doit être déplacé 
					// vers le dossier des fichiers temporaires du script pour être accessible en lecture
					//
					if( OPEN_BASEDIR_RESTRICTION )
					{
						$unlink = true;
						$tmp_filename = wa_realpath(wa_tmp_path . '/' . $filename);
						
						move_uploaded_file($file_upload['tmp_name'], $tmp_filename);
					}
				}
				
				if( !preg_match('/\.(txt|zip|gz|bz2)$/i', $filename, $match) )
				{
					trigger_error('Bad_file_type', MESSAGE);
				}
				
				$file_ext = $match[1];
				
				if( ( !$zziplib_loaded && $file_ext == 'zip' ) || ( !$zlib_loaded && $file_ext == 'gz' ) || ( !$bzip2_loaded && $file_ext == 'bz2' ) )
				{
					trigger_error('Compress_unsupported', MESSAGE);
				}
				
				$list_tmp = decompress_filedata($tmp_filename, $file_ext);
				
				//
				// S'il y a une restriction d'accés par l'open_basedir, et que c'est un fichier uploadé, 
				// nous avons dù le déplacer dans le dossier tmp/ du script, on le supprime.
				//
				if( $unlink )
				{
					include WA_PATH . 'includes/class.attach.php';
					
					Attach::remove_file($tmp_filename);
				}
			}
			
			//
			// Mode importation via le textarea 
			//
			else if( strlen($list_email) > 5 )
			{
				$list_tmp = $list_email;
			}
			
			// 
			// Aucun fichier d'import reçu et textarea vide 
			//
			else
			{
				$output->redirect('./tools.php?mode=import', 4);
				
				$message  = $lang['Message']['No_data_received'];
				$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./tools.php?mode=import') . '">', '</a>');
				trigger_error($message, MESSAGE);
			}
			
			include WA_PATH . 'includes/functions.validate.php'; 
			
			if( $glue == '' )
			{
				$list_tmp = preg_replace("/\r\n?/", "\n", $list_tmp);
				$glue = "\n";
			}
			
			if( $listdata['liste_format'] != FORMAT_MULTIPLE )
			{
				$format = $listdata['liste_format'];
			}
			
			$cpt = 0;
			$current_time = time();
			$tmp_report   = '';
			$emails_ary   = array_unique(array_map('trim', explode($glue, $list_tmp)));
			
			fake_header(false);
			
			foreach( $emails_ary AS $email )
			{
				// on désactive le check_mx si cette option est valide, cela prendrait trop de temps
				$resultat = check_email($email, $admindata['session_liste'], 'inscription', true);
				
				//
				// Si l'email est ok après vérification, on commence l'insertion, 
				// autrement, on ajoute au rapport d'erreur
				//
				if( !$resultat['error'] )
				{
					$db->transaction(START_TRC);
					
					if( empty($resultat['abo_data']['abo_id']) )
					{
						$sql_data = array();
						$sql_data['abo_email']         = $email;
						$sql_data['abo_register_key']  = generate_key();
						$sql_data['abo_register_date'] = $current_time;
						$sql_data['abo_status']        = ABO_ACTIF;
						
						if( !$db->query_build('INSERT', ABONNES_TABLE, $sql_data) )
						{
							trigger_error('Impossible d\'ajouter un nouvel abonné dans la table des abonnés', ERROR);
						}
						
						$abo_id = $db->next_id();
					}
					else
					{
						$abo_id = $resultat['abo_data']['abo_id'];
					}
					
					$sql = "INSERT INTO " . ABO_LISTE_TABLE . " (abo_id, liste_id, format) 
						VALUES($abo_id, $listdata[liste_id], $format)";
					if( !$db->query($sql) )
					{
						trigger_error('Impossible d\'insérer une nouvelle entrée dans la table abo_liste', ERROR);
					}
					
					$db->transaction(END_TRC);
				}
				else
				{
					$tmp_report .= sprintf('%s : %s%s', $email, $resultat['message'], $crlf);
				}
				
				fake_header(true);
				
				if( $cpt >= MAX_IMPORT )
				{
					break;
				}
				
				$cpt++;
			}
			
			//
			// Selon que des emails ont été refusés ou pas, affichage du message correspondant 
			// et écriture éventuelle du rapport d'erreur 
			//
			if( $tmp_report != '' )
			{
				if( is_writable(wa_tmp_path) && ($fw = @fopen(wa_tmp_path . '/wa_import_report.txt', 'w')) )
				{
					$report_str  = "#$crlf# Rapport des adresses emails refusées / Bad address email report$crlf";
					$report_str .= "#$crlf $crlf" . $tmp_report . $crlf . "# END";
					
					fwrite($fw, $report_str);
					fclose($fw);
					
					$message = nl2br(sprintf($lang['Message']['Success_import3'], '<a href="' . wa_tmp_path . '/wa_import_report.txt">', '</a>'));
				}
				else
				{
					$message = $lang['Message']['Success_import2'];
				}
			}
			else
			{
				$message = $lang['Message']['Success_import'];
			}
			
			trigger_error($message, MESSAGE);
		}
		
		$output->addHiddenField('sessid', $session->session_id);
		
		$output->set_filenames(array(
			'tool_body' => 'import_body.tpl'
		));
		
		$output->assign_vars(array(
			'L_TITLE_IMPORT'   => $lang['Title']['import'],
			'L_EXPLAIN_IMPORT' => nl2br(sprintf($lang['Explain']['import'], MAX_IMPORT, '<a href="' . WA_PATH . 'docs/faq.' . $lang['CONTENT_LANG'] . '.html#4">', '</a>')),
			'L_GLUE'           => $lang['Char_glue'],
			'L_FILE_LOCAL'     => $lang['File_local'],
			'L_VALID_BUTTON'   => $lang['Button']['valid'],
			'L_RESET_BUTTON'   => $lang['Button']['reset'],
			
			'S_HIDDEN_FIELDS'  => $output->getHiddenFields(),
			'S_ENCTYPE'        => ( FILE_UPLOADS_ON ) ? 'multipart/form-data' : 'application/x-www-form-urlencoded'
		));
		
		if( $listdata['liste_format'] == FORMAT_MULTIPLE )
		{
			include WA_PATH . 'includes/functions.box.php';
			
			$output->assign_block_vars('format_box', array(
				'L_FORMAT'   => $lang['Format_to_import'],
				'FORMAT_BOX' => format_box('format')
			));
		}
		
		if( FILE_UPLOADS_ON )
		{
			//
			// L'upload est disponible sur le serveur
			// Affichage du champ file pour importation
			//
			$output->assign_block_vars('upload_file', array(
				'L_FILE_UPLOAD' => $lang['File_upload']
			));
		}
		
		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;
	
	case 'ban':
		if( isset($_POST['submit']) )
		{
			$pattern       = ( !empty($_POST['pattern']) ) ? trim(str_replace('\\\'', '', $_POST['pattern'])) : '';
			$unban_list_id = ( !empty($_POST['unban_list_id']) ) ? array_map('intval', $_POST['unban_list_id']) : array();
			
			if( $pattern != '' )
			{
				$pattern_ary = array_map('trim', explode(',', $pattern));
				$sql_values  = array();
				
				foreach( $pattern_ary AS $pattern )
				{
					switch( DATABASE )
					{
						case 'mysql':
						case 'mysql4':
							array_push($sql_values, "($listdata[liste_id], '" . $db->escape($pattern) . "')");
							break;
						
						default:
							$sql = "INSERT INTO " . BANLIST_TABLE . " (liste_id, ban_email) 
								VALUES($listdata[liste_id], '" . $db->escape($pattern) . "')";
							if( !$db->query($sql) )
							{
								trigger_error('Impossible de mettre à jour la table des bannis', ERROR);
							}
							break;
					}
				}
				
				if( count($sql_values) > 0 )
				{
					$sql = "INSERT INTO " . BANLIST_TABLE . " (liste_id, ban_email) 
						VALUES " . implode(', ', $sql_values);
					if( !$db->query($sql) )
					{
						trigger_error('Impossible d\'insérer les données dans la table des bannis', ERROR);
					}
				}
			}
			
			if( count($unban_list_id) > 0 )
			{
				$sql = "DELETE FROM " . BANLIST_TABLE . " 
					WHERE ban_id IN (" . implode(', ', $unban_list_id) . ")";
				if( !$db->query($sql) )
				{
					trigger_error('Impossible de supprimer les emails bannis sélectionnés', ERROR);
				}
				
				//
				// Optimisation des tables
				//
				$db->check(BANLIST_TABLE);
			}
			
			$output->redirect('./tools.php?mode=ban', 4);
			
			$message  = $lang['Message']['Success_modif'];
			$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./tools.php?mode=ban') . '">', '</a>');
			$message .= '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . sessid('./index.php') . '">', '</a>');
			trigger_error($message, MESSAGE);
		}
		
		$sql = "SELECT ban_id, ban_email 
			FROM " . BANLIST_TABLE . " 
			WHERE liste_id = " . $listdata['liste_id'];
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir la liste des masques de bannissement', ERROR);
		}
		
		$unban_email_box = '<select id="unban_list_id" name="unban_list_id[]" multiple="multiple" size="10">';
		if( $row = $db->fetch_array($result) )
		{
			do
			{		
				$unban_email_box .= '<option value="' . $row['ban_id'] . '"> - ' . $row['ban_email'] . ' - </option>';
			}
			while( $row = $db->fetch_array($result) );
		}
		else
		{
			$unban_email_box .= '<option value="0"> - ' . $lang['No_email_banned'] . ' - </option>';
		}
		$unban_email_box .= '</select>';
		
		$output->addHiddenField('sessid', $session->session_id);
		
		$output->set_filenames( array(
			'tool_body' => 'ban_list_body.tpl'
		));
		
		$output->assign_vars(array(
			'L_TITLE_BAN'     => $lang['Title']['ban'],
			'L_EXPLAIN_BAN'   => nl2br($lang['Explain']['ban']),
			'L_EXPLAIN_UNBAN' => nl2br($lang['Explain']['unban']),
			'L_BAN_EMAIL'     => $lang['Ban_email'],
			'L_UNBAN_EMAIL'   => $lang['Unban_email'],
			'L_VALID_BUTTON'  => $lang['Button']['valid'],
			'L_RESET_BUTTON'  => $lang['Button']['reset'],
			
			'UNBAN_EMAIL_BOX' => $unban_email_box,
			'S_HIDDEN_FIELDS' => $output->getHiddenFields()
		));
		
		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;
	
	case 'attach':
		if( isset($_POST['submit']) )
		{
			$ext_list    = ( !empty($_POST['ext_list']) ) ? trim($_POST['ext_list']) : '';
			$ext_list_id = ( !empty($_POST['ext_list_id']) ) ? array_map('intval', $_POST['ext_list_id']) : array();
			
			if( $ext_list != '' )
			{
				$ext_ary	= array_map('trim', explode(',', $ext_list));
				$sql_values = array();
				
				foreach( $ext_ary AS $ext )
				{
					$ext = strtolower($ext);
					
					if( preg_match('/^[\w_-]+$/', $ext) )
					{
						switch( DATABASE )
						{
							case 'mysql':
							case 'mysql4':
								array_push($sql_values, "($listdata[liste_id], '$ext')");
								break;
							
							default:
								$sql = "INSERT INTO " . FORBIDDEN_EXT_TABLE . " (liste_id, fe_ext) 
									VALUES($listdata[liste_id], '$ext')";
								if( !$db->query($sql) )
								{
									trigger_error('Impossible de mettre à jour la table des extensions interdites', ERROR);
								}
								break;
						}
					}
				}
				
				if( count($sql_values) > 0 )
				{
					$sql = "INSERT INTO " . FORBIDDEN_EXT_TABLE . " (liste_id, fe_ext) 
						VALUES " . implode(', ', $sql_values);
					if( !$db->query($sql) )
					{
						trigger_error('Impossible de mettre à jour la table des extensions interdites', ERROR);
					}
				}
			}
			
			if( count($ext_list_id) > 0 )
			{
				$sql = "DELETE FROM " . FORBIDDEN_EXT_TABLE . " 
					WHERE fe_id IN (" . implode(', ', $ext_list_id) . ")";
				if( !$db->query($sql) )
				{
					trigger_error('Impossible de supprimer les extensions interdites sélectionnées', ERROR);
				}
				
				//
				// Optimisation des tables
				//
				$db->check(FORBIDDEN_EXT_TABLE);
			}
			
			$output->redirect('./tools.php?mode=attach', 4);
			
			$message  = $lang['Message']['Success_modif'];
			$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./tools.php?mode=attach') . '">', '</a>');
			$message .= '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . sessid('./index.php') . '">', '</a>');
			trigger_error($message, MESSAGE);
		}
		
		$sql = "SELECT fe_id, fe_ext 
			FROM " . FORBIDDEN_EXT_TABLE . " 
			WHERE liste_id = " . $listdata['liste_id'];
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir la liste des extensions interdites', ERROR);
		}
		
		$reallow_ext_box = '<select id="ext_list_id" name="ext_list_id[]" multiple="multiple" size="10">';
		if( $row = $db->fetch_array($result) )
		{
			do
			{		
				$reallow_ext_box .= '<option value="' . $row['fe_id'] . '"> - ' . $row['fe_ext'] . ' - </option>';
			}
			while( $row = $db->fetch_array($result) );
		}
		else
		{
			$reallow_ext_box .= '<option value="0"> - ' . $lang['No_forbidden_ext'] . ' - </option>';
		}
		$reallow_ext_box .= '</select>';
		
		$output->addHiddenField('sessid', $session->session_id);
		
		$output->set_filenames( array(
			'tool_body' => 'forbidden_ext_body.tpl'
		));
		
		$output->assign_vars(array(
			'L_TITLE_EXT'          => $lang['Title']['attach'],
			'L_EXPLAIN_TO_FORBID'  => nl2br($lang['Explain']['forbid_ext']),
			'L_EXPLAIN_TO_REALLOW' => nl2br($lang['Explain']['reallow_ext']),
			'L_FORBID_EXT'         => $lang['Forbid_ext'],
			'L_REALLOW_EXT'        => $lang['Reallow_ext'],
			'L_VALID_BUTTON'       => $lang['Button']['valid'],
			'L_RESET_BUTTON'       => $lang['Button']['reset'],
			
			'REALLOW_EXT_BOX'      => $reallow_ext_box,
			'S_HIDDEN_FIELDS'      => $output->getHiddenFields()
		));
		
		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;
	
	case 'backup':
		$tables_wa = array(
			ABO_LISTE_TABLE, ABONNES_TABLE, ADMIN_TABLE, AUTH_ADMIN_TABLE, BANLIST_TABLE, CONFIG_TABLE, 
			JOINED_FILES_TABLE, FORBIDDEN_EXT_TABLE, LISTE_TABLE, LOG_TABLE, LOG_FILES_TABLE, SESSIONS_TABLE
		);
		
		$tables      = array();
		$tables_plus = ( !empty($_POST['tables_plus']) ) ? array_map('trim', $_POST['tables_plus']) : array();
		$backup_type = ( isset($_POST['backup_type']) ) ? intval($_POST['backup_type']) : 0;
		$drop_option = ( !empty($_POST['drop_option']) ) ? true : false;
		
		$backup = new $classname($crlf);
		$tables_ary = $backup->get_tables($dbname);
		
		foreach( $tables_ary AS $tablename => $tabletype )
		{
			if( !isset($_POST['submit']) )
			{
				if( !in_array($tablename, $tables_wa) )
				{
					$tables_plus[] = $tablename;
				}
			}
			else
			{
				if( in_array($tablename, $tables_wa) || in_array($tablename, $tables_plus) )
				{
					$tables[] = array('name' => $tablename, 'type' => $tabletype);
				}
			}
		}
		
		if( isset($_POST['submit']) )
		{
			if( $action == 'store' && !is_writable(wa_tmp_path) )
			{
				trigger_error('tmp_dir_not_writable', MESSAGE);
			}
			
			//
			// Lancement de la sauvegarde. Pour commencer, l'entête du fichier sql 
			//
			$contents = $backup->file_header($dbhost, $dbname);
			
			fake_header(false);
			
			foreach( $tables AS $tabledata )
			{
				if( $backup_type != 2 )// save complète ou structure uniquement
				{
					$contents .= $backup->get_table_structure($tabledata, $drop_option);
				}
				
				if( $backup_type != 1 )// save complète ou données uniquement
				{
					$contents .= $backup->get_table_data($tabledata['name']);
				}
				
				$contents .= $crlf . $crlf;
				
				fake_header(true);
			}
			
			$filename  = 'wanewsletter_backup.sql';
			$mime_type = '';
			
			//
			// Préparation des données selon l'option demandée 
			//
			$contents = compress_filedata($filename, $mime_type, $contents, $compress);
			
			if( $action == 'download' )
			{
				include WA_PATH . 'includes/class.attach.php';
				
				Attach::send_file($filename, $mime_type, $contents);
			}
			else
			{
				if( !($fw = @fopen(wa_tmp_path . '/' . $filename, 'wb')) )
				{
					trigger_error('Impossible d\'écrire le fichier de sauvegarde', ERROR);
				}
				
				fwrite($fw, $contents);
				fclose($fw);
				
				trigger_error('Success_backup', MESSAGE);
			}
		}
		
		$output->addHiddenField('sessid', $session->session_id);
		
		$output->set_filenames(array(
			'tool_body' => 'backup_body.tpl'
		));
		
		$output->assign_vars(array(
			'L_TITLE_BACKUP'    => $lang['Title']['backup'],
			'L_EXPLAIN_BACKUP'  => nl2br($lang['Explain']['backup']),
			'L_BACKUP_TYPE'     => $lang['Backup_type'],
			'L_FULL'            => $lang['Backup_full'],
			'L_STRUCTURE'       => $lang['Backup_structure'],
			'L_DATA'            => $lang['Backup_data'],
			'L_DROP_OPTION'     => $lang['Drop_option'],
			'L_ACTION'          => $lang['File_action'],
			'L_DOWNLOAD'        => $lang['Download_action'],
			'L_STORE_ON_SERVER' => $lang['Store_action'],
			'L_YES'             => $lang['Yes'],
			'L_NO'              => $lang['No'],
			'L_VALID_BUTTON'    => $lang['Button']['valid'],
			'L_RESET_BUTTON'    => $lang['Button']['reset'],
			
			'S_HIDDEN_FIELDS'   => $output->getHiddenFields()
		));
		
		if( $total_tables = count($tables_plus) )
		{
			if( $total_tables > 10 )
			{
				$total_tables = 10;
			}
			else if( $total_tables < 5 )
			{
				$total_tables = 5;
			}
			
			$tables_box = '<select id="tables_plus" name="tables_plus[]" multiple="multiple" size="' . $total_tables . '">';
			foreach( $tables_plus AS $table_name )
			{
				$tables_box .= '<option value="' . $table_name . '"> - ' . $table_name . ' - </option>';
			}
			$tables_box .= '</select>';
			
			$output->assign_block_vars('tables_box', array(
				'L_ADDITIONAL_TABLES' => $lang['Additionnal_tables'],
				'S_TABLES_BOX'        => $tables_box
			));
		}
		
		if( $zlib_loaded || $bzip2_loaded )
		{
			$output->assign_block_vars('compress_option', array(
				'L_COMPRESS' => $lang['Compress']
			));
			
			if( $zlib_loaded )
			{
				$output->assign_block_vars('compress_option.gzip_compress', array());
			}
			
			if( $bzip2_loaded )
			{
				$output->assign_block_vars('compress_option.bz2_compress', array());
			}
		}
		
		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;
	
	case 'restore':
		if( isset($_POST['submit']) )
		{
			//
			// On règle le script pour ignorer une déconnexion du client et mener 
			// la restauration à son terme
			//
			if( !is_disabled_func('ignore_user_abort') )
			{
				@ignore_user_abort(true);
			}
			
			//
			// Import via upload ou fichier local ? 
			//
			if( !empty($file_local) || !empty($file_upload['name']) )
			{
				$unlink = false;
				
				if( !empty($file_local) )
				{
					//$file_local   = str_replace('\\\\', '\\', str_replace('\\\'', '\'', $file_local));
					
					$tmp_filename = wa_realpath(WA_PATH . str_replace('\\', '/', $file_local));
					$filename     = $file_local;
					
					if( !file_exists($tmp_filename) )
					{
						$output->redirect('./tools.php?mode=restore', 4);
						
						$message  = sprintf($lang['Message']['Error_local'], htmlspecialchars($filename));
						$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./tools.php?mode=restore') . '">', '</a>');
						trigger_error($message, MESSAGE);
					}
				}
				else
				{
					$tmp_filename = $file_upload['tmp_name'];//str_replace('\\\\', '\\', $file_upload['tmp_name']);
					$filename     = $file_upload['name'];
					
					if( !is_uploaded_file($tmp_filename) )
					{
						trigger_error('Upload_error_5', MESSAGE);
					}
					
					//
					// Si nous avons un accés restreint à cause de open_basedir, le fichier doit être déplacé 
					// vers le dossier des fichiers temporaires du script pour être accessible en lecture
					//
					if( OPEN_BASEDIR_RESTRICTION )
					{
						$unlink = true;
						$tmp_filename = wa_realpath(wa_tmp_path . '/' . $filename);
						
						move_uploaded_file($file_upload['tmp_name'], $tmp_filename);
					}
				}
				
				if( !preg_match('/\.(sql|zip|gz|bz2)$/i', $filename, $match) )
				{
					trigger_error('Bad_file_type', MESSAGE);
				}
				
				$file_ext = $match[1];
				
				if( ( !$zziplib_loaded && $file_ext == 'zip' ) || ( !$zlib_loaded && $file_ext == 'gz' ) || ( !$bzip2_loaded && $file_ext == 'bz2' ) )
				{
					trigger_error('Compress_unsupported', MESSAGE);
				}
				
				$data = decompress_filedata($tmp_filename, $file_ext);
				
				//
				// S'il y a une restriction d'accés par l'open_basedir, et que c'est un fichier uploadé, 
				// nous avons dù le déplacer dans le dossier des fichiers temporaires du script, on le supprime.
				//
				if( $unlink )
				{
					include WA_PATH . 'includes/class.attach.php';
					
					Attach::remove_file($tmp_filename);
				}
			}
			
			// 
			// Aucun fichier de restauration reçu 
			//
			else
			{
				$output->redirect('./tools.php?mode=restore', 4);
				
				$message  = $lang['Message']['No_data_received'];
				$message .= '<br /><br />' . sprintf($lang['Click_return_back'], '<a href="' . sessid('./tools.php?mode=restore') . '">', '</a>');
				trigger_error($message, MESSAGE);
			}
			
			$queries = make_sql_ary($data, ';');
			
			$db->transaction(START_TRC);
			
			fake_header(false);
			
			foreach( $queries AS $query )
			{
				$db->query($query) || trigger_error('Erreur sql lors de la restauration', ERROR);
				
				fake_header(true);
			}
			
			$db->transaction(END_TRC);
			
			trigger_error('Success_restore', MESSAGE);
		}
		
		$output->addHiddenField('sessid', $session->session_id);
		
		$output->set_filenames(array(
			'tool_body' => 'restore_body.tpl'
		));
		
		$output->assign_vars(array(
			'L_TITLE_RESTORE'   => $lang['Title']['restore'],
			'L_EXPLAIN_RESTORE' => nl2br($lang['Explain']['restore']),
			'L_FILE_LOCAL'      => $lang['File_local'],
			'L_VALID_BUTTON'    => $lang['Button']['valid'],
			'L_RESET_BUTTON'    => $lang['Button']['reset'],
			
			'S_HIDDEN_FIELDS'   => $output->getHiddenFields(),
			'S_ENCTYPE'         => ( FILE_UPLOADS_ON ) ? 'multipart/form-data' : 'application/x-www-form-urlencoded'
		));
		
		if( FILE_UPLOADS_ON )
		{
			//
			// L'upload est disponible sur le serveur
			// Affichage du champ file pour importation
			//
			$output->assign_block_vars('upload_file', array(
				'L_FILE_UPLOAD' => $lang['File_upload_restore']
			));
		}
		
		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;
	
	case 'generator':
		if( isset($_POST['generate']) )
		{
			$url_form = ( !empty($_POST['url_form']) ) ? trim($_POST['url_form']) : '';
			
			$code_html  = "<form method=\"post\" action=\"" . htmlspecialchars($url_form) . "\">\n";
			$code_html .= $lang['Email_address'] . " : <input type=\"text\" name=\"email\" maxlength=\"100\" /> &nbsp; \n";
			
			if( $listdata['liste_format'] == FORMAT_MULTIPLE )
			{
				$code_html .= $lang['Format'] . " : <select name=\"format\">\n";
				$code_html .= "<option value=\"" . FORMAT_TEXTE . "\">TXT</option>\n";
				$code_html .= "<option value=\"" . FORMAT_HTML . "\">HTML</option>\n";
				$code_html .= "</select>\n";
			}
			else
			{
				$code_html .= "<input type=\"hidden\" name=\"format\" value=\"$listdata[liste_format]\" />\n";
			}
			
			$code_html .= "<input type=\"hidden\" name=\"liste\" value=\"$listdata[liste_id]\" />\n";
			$code_html .= "<br />\n";
			$code_html .= "<input type=\"radio\" name=\"action\" value=\"inscription\" checked=\"checked\" /> $lang[Subscribe] <br />\n";
			$code_html .= ( $listdata['liste_format'] == FORMAT_MULTIPLE ) ? "<input type=\"radio\" name=\"action\" value=\"setformat\" /> $lang[Setformat] <br />\n" : "";
			$code_html .= "<input type=\"radio\" name=\"action\" value=\"desinscription\" /> $lang[Unsubscribe] <br />\n";
			$code_html .= "<input type=\"submit\" name=\"wanewsletter\" value=\"" . $lang['Button']['valid'] . "\" />\n";
			$code_html .= "</form>";
			
			$path = wa_realpath(WA_PATH . 'newsletter.php');
			
			$code_php  = '<' . "?php\n";
			$code_php .= "define('IN_WA_FORM', true);\n";
			$code_php .= "define('WA_PATH', '" . substr($path, 0, (strrpos($path, '/') + 1)) . "');\n";
			$code_php .= "\n";
			$code_php .= "include WA_PATH . 'newsletter.php';\n";
			$code_php .= '?' . '>';
			
			$output->set_filenames(array(
				'tool_body' => 'result_generator_body.tpl'
			));
			
			$output->assign_vars(array(
				'L_TITLE_GENERATOR'   => $lang['Title']['generator'],
				'L_EXPLAIN_CODE_HTML' => nl2br($lang['Explain']['code_html']),
				'L_EXPLAIN_CODE_PHP'  => nl2br($lang['Explain']['code_php']),
				
				'CODE_HTML' => nl2br(htmlspecialchars($code_html, ENT_NOQUOTES)),
				'CODE_PHP'  => nl2br(htmlspecialchars($code_php, ENT_NOQUOTES))
			));
		}
		else
		{
			$output->addHiddenField('sessid', $session->session_id);
			
			$output->set_filenames(array(
				'tool_body' => 'generator_body.tpl'
			));
			
			$output->assign_vars(array(
				'L_TITLE_GENERATOR'   => $lang['Title']['generator'],
				'L_EXPLAIN_GENERATOR' => nl2br($lang['Explain']['generator']),
				'L_TARGET_FORM'       => $lang['Target_form'],
				'L_VALID_BUTTON'      => $lang['Button']['valid'],
				
				'S_HIDDEN_FIELDS' => $output->getHiddenFields()
			));
		}
		
		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;
}

$output->pparse('body');

$output->page_footer();
?>