<?php
/**
 * Copyright (c) 2002-2010 Aur�lien Maille
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

if( !defined('_INC_CLASS_WADB_SQLITE3') ) {

define('_INC_CLASS_WADB_SQLITE3', true);

define('SQL_INSERT', 1);
define('SQL_UPDATE', 2);
define('SQL_DELETE', 3);

define('SQL_FETCH_NUM',   SQLITE3_NUM);
define('SQL_FETCH_ASSOC', SQLITE3_ASSOC);
define('SQL_FETCH_BOTH',  SQLITE3_BOTH);

class Wadb_sqlite3 {
	
	/**
	 * Connexion � la base de donn�es
	 * 
	 * @var resource
	 * @access private
	 */
	var $link;
	
	/**
	 * Nom de la base de donn�es
	 * 
	 * @var string
	 * @access private
	 */
	var $dbname = '';
	
	/**
	 * Options de connexion
	 * 
	 * @var array
	 * @access private
	 */
	var $options = array();
	
	/**
	 * Code d'erreur
	 * 
	 * @var integer
	 * @access public
	 */
	var $errno = 0;
	
	/**
	 * Message d'erreur
	 * 
	 * @var string
	 * @access public
	 */
	var $error = '';
	
	/**
	 * Derni�re requ�te SQL ex�cut�e (en cas d'erreur seulement)
	 * 
	 * @var string
	 * @access public
	 */
	var $lastQuery = '';
	
	/**
	 * Nombre de requ�tes SQL ex�cut�es depuis le d�but de la connexion
	 * 
	 * @var integer
	 * @access public
	 */
	var $queries = 0;
	
	/**
	 * Dur�e totale d'ex�cution des requ�tes SQL
	 * 
	 * @var integer
	 * @access public
	 */
	var $sqltime = 0;
	
	/**
	 * Version de la librairie SQLite
	 * 
	 * @var string
	 * @access public
	 */
	var $libVersion = '';
	
	/**
	 * Constructeur de classe
	 * 
	 * @param string $sqlite_db   Base de donn�es SQLite
	 * @param array  $options     Options de connexion/utilisation
	 * 
	 * @access public
	 */
	function Wadb_sqlite3($sqlite_db, $options = null)
	{
		if( file_exists($sqlite_db) ) {
 			if( !is_readable($sqlite_db) ) {
				trigger_error("SQLite database isn't readable!", E_USER_WARNING);
			}
		}
		else if( !is_writable(dirname($sqlite_db)) ) {
			trigger_error(dirname($sqlite_db) . " isn't writable. Cannot create "
				. basename($sqlite_db) . " database", E_USER_WARNING);
		}
		
		if( is_array($options) ) {
			$this->options = array_merge($this->options, $options);
		}
		
		try {
			$this->link = new SQLite3($sqlite_db, SQLITE3_OPEN_READWRITE|SQLITE3_OPEN_CREATE, 
				!empty($options['encryption_key']) ? $options['encryption_key'] : null);
		}
		catch( Exception $e ) {
			$this->error = $e->getMessage();
		}
		
		if( !is_null($this->link) ) {
			$this->link->exec('PRAGMA short_column_names = 1');
			$this->link->exec('PRAGMA case_sensitive_like = 0');
			
			$tmp = SQLite3::version();
			$this->libVersion = $tmp['versionString'];
			
//			if( !empty($this->options['charset']) ) {
//				$this->encoding($this->options['charset']);
//			}
		}
	}
	
	/**
	 * Connexion � la base de donn�es
	 * 
	 * @param array $infos    Informations de connexion
	 * @param array $options  Options de connexion/utilisation
	 * 
	 * @access public
	 * @return boolean
	 */
	function connect($infos = null, $options = null)
	{
		if( is_array($options) ) {
			$this->options = array_merge($this->options, $options);
		}
		
		return true;
	}
	
	/**
	 * @access public
	 * @return boolean
	 */
	function isConnected()
	{
		return !is_null($this->link);
	}
	
	/**
	 * Renvoie le jeu de caract�res courant utilis�.
	 * Si l'argument $encoding est fourni, il est utilis� pour d�finir
	 * le nouveau jeu de caract�res de la connexion en cours
	 * 
	 * @param string $encoding
	 * 
	 * @access public
	 * @return string
	 */
	function encoding($encoding = null)
	{
		$result = $this->link->query('PRAGMA encoding');
		$row = $result->fetchArray();
		$curEncoding = $row['encoding'];
		
		if( !is_null($encoding) ) {
			$this->link->exec("PRAGMA encoding = \"$encoding\"");
		}
		
		return $curEncoding;
	}
	
	/**
	 * Ex�cute une requ�te sur la base de donn�es
	 * 
	 * @param string $query
	 * 
	 * @access public
	 * @return mixed
	 */
	function query($query)
	{
		$curtime = array_sum(explode(' ', microtime()));
		$result  = $this->link->query($query);
		$endtime = array_sum(explode(' ', microtime()));
		
		$this->sqltime += ($endtime - $curtime);
		$this->queries++;
		
		if( !$result ) {
			$this->errno = $this->link->lastErrorCode();
			$this->error = $this->link->lastErrorMsg();
			$this->lastQuery = $query;
			$this->result = null;
			
			$this->rollBack();
		}
		else {
			$this->errno = 0;
			$this->error = '';
			$this->lastQuery = '';
			$this->result = $result;
			
			if( in_array(strtoupper(substr($query, 0, 6)), array('INSERT', 'UPDATE', 'DELETE')) ) {
				$result = true;
			}
			else {
				$result = new WadbResult_sqlite3($result);
			}
		}
		
		return $result;
	}
	
	/**
	 * Construit une requ�te de type INSERT ou UPDATE � partir des diverses donn�es fournies
	 * 
	 * @param string $type      Type de requ�te (peut valoir INSERT ou UPDATE)
	 * @param string $table     Table sur laquelle effectuer la requ�te
	 * @param array  $data      Tableau des donn�es � ins�rer. Le tableau a la structure suivante:
	 *                          array(column_name => column_value[, column_name => column_value])
	 * @param array $sql_where  Cha�ne de condition
	 * 
	 * @access public
	 * @return mixed
	 */
	function build($type, $table, $data, $sql_where = null)
	{
		$fields = $values = array();
		
		foreach( $data as $field => $value ) {
			if( is_null($value) ) {
				$value = 'NULL';
			}
			else if( is_bool($value) ) {
				$value = intval($value);
			}
			else if( !is_int($value) && !is_float($value) ) {
				$value = '\'' . $this->escape($value) . '\'';
			}
			
			array_push($fields, $this->quote($field));
			array_push($values, $value);
		}
		
		if( $type == SQL_INSERT ) {
			$query = sprintf('INSERT INTO %s (%s) VALUES(%s)', $table, implode(', ', $fields), implode(', ', $values));
		}
		else if( $type == SQL_UPDATE ) {
			
			$query = 'UPDATE ' . $table . ' SET ';
			for( $i = 0, $m = count($fields); $i < $m; $i++ ) {
				$query .= $fields[$i] . ' = ' . $values[$i] . ', ';
			}
			
			$query = substr($query, 0, -2);
			
			if( is_array($sql_where) && count($sql_where) > 0 ) {
				$query .= ' WHERE ';
				foreach( $sql_where as $field => $value ) {
					if( is_null($value) ) {
						$value = 'NULL';
					}
					else if( is_bool($value) ) {
						$value = intval($value);
					}
					else if( !is_int($value) && !is_float($value) ) {
						$value = '\'' . $this->escape($value) . '\'';
					}
					
					$query .= sprintf('%s = %s AND ', $this->quote($field), $value);
				}
				
				$query = substr($query, 0, -5);
			}
		}
		
		return $this->query($query);
	}
	
	/**
	 * Prot�ge un nom de base, de table ou de colonne en pr�vision de son utilisation
	 * dans une requ�te
	 * 
	 * @param string $name
	 * 
	 * @access public
	 * @return string
	 */
	function quote($name)
	{
		return '[' . $name . ']';
	}
	
	/**
	 * @param mixed $tables  Nom de table ou tableau de noms de table
	 * 
	 * @access public
	 * @return void
	 */
	function vacuum($tables)
	{
		if( !is_array($tables) ) {
			$tables = array($tables); 
		}
		
		foreach( $tables as $tablename ) {
			$this->link->exec('VACUUM ' . $tablename);
		}
	}
	
	/**
	 * D�marre le mode transactionnel
	 * 
	 * @access public
	 * @return boolean
	 */
	function beginTransaction()
	{
		return $this->link->exec('BEGIN');
	}
	
	/**
	 * Envoie une commande COMMIT � la base de donn�es pour validation de la
	 * transaction courante
	 * 
	 * @access public
	 * @return boolean
	 */
	function commit()
	{
		if( !($result = $this->link->exec('COMMIT')) )
		{
			$this->link->exec('ROLLBACK');
		}
		
		return $result;
	}
	
	/**
	 * Envoie une commande ROLLBACK � la base de donn�es pour annulation de la
	 * transaction courante
	 * 
	 * @access public
	 * @return boolean
	 */
	function rollBack()
	{
		return @$this->link->exec('ROLLBACK');
	}
	
	/**
	 * Renvoie le nombre de lignes affect�es par la derni�re requ�te DML
	 * 
	 * @access public
	 * @return boolean
	 */
	function affectedRows()
	{
		return $this->link->changes();
	}
	
	/**
	 * Retourne l'identifiant g�n�r� automatiquement par la derni�re requ�te
	 * INSERT sur la base de donn�es
	 * 
	 * @access public
	 * @return integer
	 */
	function lastInsertId()
	{
		return $this->link->lastInsertRowID();
	}
	
	/**
	 * �chappe une cha�ne en pr�vision de son insertion dans une requ�te sur
	 * la base de donn�es
	 * 
	 * @param string $string
	 * 
	 * @access public
	 * @return string
	 */
	function escape($string)
	{
		return $this->link->escapeString($string);
	}
	
	/**
	 * V�rifie l'�tat de la connexion courante et effectue si besoin une reconnexion
	 * 
	 * @access public
	 * @return boolean
	 */
	function ping()
	{
		return true;
	}
	
	/**
	 * Ferme la connexion � la base de donn�es
	 * 
	 * @access public
	 * @return boolean
	 */
	function close()
	{
		if( !is_null($this->link) ) {
			try {
				$this->rollBack();
			}
			catch( Exception $e ) {}
			
			$result = $this->link->close();
			$this->link = null;
			
			return $result;
		}
		else {
			return true;
		}
	}
}

class WadbResult_sqlite3 {
	
	/**
	 * Objet de r�sultat PDO de requ�te
	 * 
	 * @var object
	 * @access private
	 */
	var $result;
	
	/**
	 * Mode de r�cup�ration des donn�es
	 * 
	 * @var integer
	 * @access private
	 */
	var $fetchMode;
	
	/**
	 * Constructeur de classe
	 * 
	 * @param object $result  Ressource de r�sultat de requ�te
	 * 
	 * @access public
	 */
	function WadbResult_sqlite3($result)
	{
		$this->result = $result;
		$this->fetchMode = SQLITE3_BOTH;
	}
	
	/**
	 * Renvoie la ligne suivante dans le jeu de r�sultat
	 * 
	 * @param integer $mode  Mode de r�cup�ration des donn�es
	 * 
	 * @access public
	 * @return array
	 */
	function fetch($mode = null)
	{
		if( is_null($mode) ) {
			$mode = $this->fetchMode;
		}
		
		return $this->result->fetchArray($mode);
	}
	
	/**
	 * Renvoie sous forme d'objet la ligne suivante dans le jeu de r�sultat
	 * 
	 * @access public
	 * @return object
	 */
	function fetchObject()
	{
		return (object) $this->result->fetchArray(SQLITE3_ASSOC);
	}
	
	/**
	 * Renvoie un tableau de toutes les lignes du jeu de r�sultat
	 * 
	 * @param integer $mode  Mode de r�cup�ration des donn�es
	 * 
	 * @access public
	 * @return array
	 */
	function fetchAll($mode = null)
	{
		if( is_null($mode) ) {
			$mode = $this->fetchMode;
		}
		
		$rowset = array();
		while( $row = $this->fetch($mode) ) {
			array_push($rowset, $row);
		}
		
		return $rowset;
	}
	
	/**
	 * Retourne le contenu de la colonne pour l'index ou le nom donn�
	 * � l'index suivant dans le jeu de r�sultat.
	 * 
	 * @param mixed $column  Index ou nom de la colonne
	 * 
	 * @access public
	 * @return string
	 */
	function column($column)
	{
		$row = $this->result->fetchArray(SQLITE3_ASSOC);
		
		return (is_array($row) && isset($row[$column])) ? $row[$column] : false;
	}
	
	/**
	 * Configure le mode de r�cup�ration par d�faut
	 * 
	 * @param integer $mode  Mode de r�cup�ration des donn�es
	 * 
	 * @access public
	 * @return boolean
	 */
	function setFetchMode($mode)
	{
		if( in_array($mode, array(SQLITE3_NUM, SQLITE3_ASSOC, SQLITE3_BOTH)) ) {
			$this->fetchMode = $mode;
			return true;
		}
		else {
			trigger_error("Invalid fetch mode", E_USER_WARNING);
			return false;
		}
	}
	
	/**
	 * Lib�re la m�moire allou�e
	 * 
	 * @access public
	 * @return void
	 */
	function free()
	{
		if( !is_null($this->result) ) {
			$this->result = null;
		}
	}
	
	/**
	 * Destructeur de classe
	 * 
	 * @access public
	 * @return void
	 */
	function __destruct()
	{
		$this->free();
	}
}

class WadbBackup_sqlite3 {
	
	/**
	 * Informations concernant la base de donn�es
	 * 
	 * @var array
	 * @access private
	 */
	var $infos = array();
	
	/**
	 * Fin de ligne
	 * 
	 * @var boolean
	 * @access public
	 */
	var $eol = "\n";
	
	/**
	 * Constructeur de classe
	 * 
	 * @param array $infos  Informations concernant la base de donn�es
	 * 
	 * @access public
	 */
	function WadbBackup_sqlite3($infos)
	{
		$this->infos = $infos;
		
		if( !isset($this->infos['host']) ) {
			$this->infos['host'] = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'Unknown';
		}
	}
	
	/**
	 * G�n�ration de l'en-t�te du fichier de sauvegarde
	 * 
	 * @param string $toolname  Nom de l'outil utilis� pour g�n�rer la sauvegarde
	 * 
	 * @access public
	 * @return string
	 */
	function header($toolname = '')
	{
		global $db;
		
		$contents  = '-- ' . $this->eol;
		$contents .= "-- $toolname SQLite Dump" . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= "-- Host       : " . $this->infos['host'] . $this->eol;
		$contents .= "-- SQLite lib : " . $db->libVersion . $this->eol;
		$contents .= "-- Database   : " . basename($this->infos['dbname']) . $this->eol;
		$contents .= '-- Date       : ' . date('d/m/Y H:i:s O') . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= $this->eol;
		
		return $contents;
	}
	
	/**
	 * Retourne la liste des tables pr�sentes dans la base de donn�es consid�r�e
	 * 
	 * @access public
	 * @return array
	 */
	function get_tables()
	{
		global $db;
		
		if( !($result = $db->query("SELECT tbl_name FROM sqlite_master WHERE type = 'table'")) ) {
			trigger_error('Impossible d\'obtenir la liste des tables', ERROR);
		}
		
		$tables = array();
		while( $row = $result->fetch() ) {
			$tables[$row['tbl_name']] = '';
		}
		
		return $tables;
	}
	
	/**
	 * Utilisable pour l'ajout de requ�te suppl�mentaires (s�quences, configurations diverses, etc)
	 * 
	 * @param boolean $drop_option
	 * 
	 * @access public
	 * @return string
	 */
	function get_other_queries($drop_option)
	{
		return '';
	}
	
	/**
	 * Retourne la structure d'une table de la base de donn�es sous forme de requ�te SQL de type DDL
	 * 
	 * @param array   $tabledata    Informations sur la table (provenant de self::get_tables())
	 * @param boolean $drop_option  Ajouter une requ�te de suppression conditionnelle de table
	 * 
	 * @access public
	 * @return string
	 */
	function get_table_structure($tabledata, $drop_option)
	{
		global $db;
		
		$contents  = '-- ' . $this->eol;
		$contents .= '-- Struture de la table ' . $tabledata['name'] . ' ' . $this->eol;
		$contents .= '-- ' . $this->eol;
		
		if( $drop_option ) {
			$contents .= 'DROP TABLE ' . $tabledata['name'] . ';' . $this->eol;
		}
		
		$sql = "SELECT sql, type
			FROM sqlite_master
			WHERE tbl_name = '$tabledata[name]'
				AND sql IS NOT NULL";
		if( !($result = $db->query($sql)) ) {
			trigger_error('Impossible d\'obtenir la structure de la table', ERROR);
		}
		
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
	
	/**
	 * Retourne les donn�es d'une table de la base de donn�es sous forme de requ�tes SQL de type DML
	 * 
	 * @param string $tablename  Nom de la table � consid�rer
	 * 
	 * @access public
	 * @return string
	 */
	function get_table_data($tablename)
	{
		global $db;
		
		$contents = '';
		
		$sql = 'SELECT * FROM ' . $tablename;
		if( !($result = $db->query($sql)) ) {
			trigger_error('Impossible d\'obtenir le contenu de la table ' . $tablename, ERROR);
		}
		
		$result->setFetchMode(SQL_FETCH_ASSOC);
		
		if( $row = $result->fetch() ) {
			$contents  = $this->eol;
			$contents .= '-- ' . $this->eol;
			$contents .= '-- Contenu de la table ' . $tablename . ' ' . $this->eol;
			$contents .= '-- ' . $this->eol;
			
			$fields = array();
			for( $j = 0, $n = $result->result->numColumns(); $j < $n; $j++ ) {
				array_push($fields, $result->result->columnName($j));
			}
			
			$fields = implode(', ', $fields);
			
			do {
				$contents .= "INSERT INTO $tablename ($fields) VALUES";
				
				foreach( $row as $key => $value ) {
					if( is_null($value) ) {
						$row[$key] = 'NULL';
					}
					else {
						$row[$key] = '\'' . $db->escape($value) . '\'';
					}
				}
				
				$contents .= '(' . implode(', ', $row) . ');' . $this->eol;
			}
			while( $row = $result->fetch() );
		}
		
		return $contents;
	}
}

}
?>
