<?php
/**
 * Copyright (c) 2002-2014 Aurélien Maille
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

class Wadb_sqlite3 {
	
	/**
	 * Connexion à la base de données
	 * 
	 * @var resource
	 * @access private
	 */
	var $link;
	
	/**
	 * Nom de la base de données
	 * 
	 * @var string
	 * @access public
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
	 * Dernière requète SQL exécutée (en cas d'erreur seulement)
	 * 
	 * @var string
	 * @access public
	 */
	var $lastQuery = '';
	
	/**
	 * Nombre de requètes SQL exécutées depuis le début de la connexion
	 * 
	 * @var integer
	 * @access public
	 */
	var $queries = 0;
	
	/**
	 * Durée totale d'exécution des requètes SQL
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
	 * "Constantes" de la classe
	 */
	var $SQL_INSERT = 1;
	var $SQL_UPDATE = 2;
	var $SQL_DELETE = 3;
	
	/**
	 * Constructeur de classe
	 * 
	 * @param string $sqlite_db   Base de données SQLite
	 * @param array  $options     Options de connexion/utilisation
	 * 
	 * @access public
	 */
	function Wadb_sqlite3($sqlite_db, $options = null)
	{
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
		
		if( is_array($options) ) {
			$this->options = array_merge($this->options, $options);
		}
		
		$this->dbname = $sqlite_db;
		
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
	 * Connexion à la base de données
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
	 * Renvoie le jeu de caractères courant utilisé.
	 * Si l'argument $encoding est fourni, il est utilisé pour définir
	 * le nouveau jeu de caractères de la connexion en cours
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
	 * Exécute une requète sur la base de données
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
	 * Construit une requète de type INSERT ou UPDATE à partir des diverses données fournies
	 * 
	 * @param string $type      Type de requète (peut valoir INSERT ou UPDATE)
	 * @param string $table     Table sur laquelle effectuer la requète
	 * @param array  $data      Tableau des données à insérer. Le tableau a la structure suivante:
	 *                          array(column_name => column_value[, column_name => column_value])
	 * @param array $sql_where  Chaîne de condition
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
		
		if( $type == $this->SQL_INSERT ) {
			$query = sprintf('INSERT INTO %s (%s) VALUES(%s)', $table, implode(', ', $fields), implode(', ', $values));
		}
		else if( $type == $this->SQL_UPDATE ) {
			
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
	 * Protège un nom de base, de table ou de colonne en prévision de son utilisation
	 * dans une requète
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
	 * Démarre le mode transactionnel
	 * 
	 * @access public
	 * @return boolean
	 */
	function beginTransaction()
	{
		return $this->link->exec('BEGIN');
	}
	
	/**
	 * Envoie une commande COMMIT à la base de données pour validation de la
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
	 * Envoie une commande ROLLBACK à la base de données pour annulation de la
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
	 * Renvoie le nombre de lignes affectées par la dernière requète DML
	 * 
	 * @access public
	 * @return boolean
	 */
	function affectedRows()
	{
		return $this->link->changes();
	}
	
	/**
	 * Retourne l'identifiant généré automatiquement par la dernière requète
	 * INSERT sur la base de données
	 * 
	 * @access public
	 * @return integer
	 */
	function lastInsertId()
	{
		return $this->link->lastInsertRowID();
	}
	
	/**
	 * Échappe une chaîne en prévision de son insertion dans une requète sur
	 * la base de données
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
	 * Vérifie l'état de la connexion courante et effectue si besoin une reconnexion
	 * 
	 * @access public
	 * @return boolean
	 */
	function ping()
	{
		return true;
	}
	
	/**
	 * Ferme la connexion à la base de données
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
	 * Objet de résultat PDO de requète
	 * 
	 * @var object
	 * @access private
	 */
	var $result;
	
	/**
	 * Mode de récupération des données
	 * 
	 * @var integer
	 * @access private
	 */
	var $fetchMode;
	
	/**
	 * "Constantes" de la classe
	 */
	var $SQL_FETCH_NUM   = SQLITE3_NUM;
	var $SQL_FETCH_ASSOC = SQLITE3_ASSOC;
	var $SQL_FETCH_BOTH  = SQLITE3_BOTH;
	
	/**
	 * Constructeur de classe
	 * 
	 * @param object $result  Ressource de résultat de requète
	 * 
	 * @access public
	 */
	function WadbResult_sqlite3($result)
	{
		$this->result = $result;
		$this->fetchMode = SQLITE3_BOTH;
	}
	
	/**
	 * Renvoie la ligne suivante dans le jeu de résultat
	 * 
	 * @param integer $mode  Mode de récupération des données
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
	 * Renvoie sous forme d'objet la ligne suivante dans le jeu de résultat
	 * 
	 * @access public
	 * @return object
	 */
	function fetchObject()
	{
		return (object) $this->result->fetchArray(SQLITE3_ASSOC);
	}
	
	/**
	 * Renvoie un tableau de toutes les lignes du jeu de résultat
	 * 
	 * @param integer $mode  Mode de récupération des données
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
	 * Retourne le contenu de la colonne pour l'index ou le nom donné
	 * à l'index suivant dans le jeu de résultat.
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
	 * Configure le mode de récupération par défaut
	 * 
	 * @param integer $mode  Mode de récupération des données
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
	 * Libère la mémoire allouée
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
	 * Connexion à la base de données
	 * 
	 * @var object
	 * @access private
	 */
	var $db = null;
	
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
	 * @param object $db  Connexion à la base de données
	 * 
	 * @access public
	 */
	function WadbBackup_sqlite3($db)
	{
		$this->db = $db;
	}
	
	/**
	 * Génération de l'en-tête du fichier de sauvegarde
	 * 
	 * @param string $toolname  Nom de l'outil utilisé pour générer la sauvegarde
	 * 
	 * @access public
	 * @return string
	 */
	function header($toolname = '')
	{
		$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'Unknown Host';
		
		$contents  = '-- ' . $this->eol;
		$contents .= "-- $toolname SQLite Dump" . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= "-- Host       : " . $host . $this->eol;
		$contents .= "-- SQLite lib : " . $this->db->libVersion . $this->eol;
		$contents .= "-- Database   : " . basename($this->db->dbname) . $this->eol;
		$contents .= '-- Date       : ' . date('d/m/Y H:i:s O') . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= $this->eol;
		
		return $contents;
	}
	
	/**
	 * Retourne la liste des tables présentes dans la base de données considérée
	 * 
	 * @access public
	 * @return array
	 */
	function get_tables()
	{
		if( !($result = $this->db->query("SELECT tbl_name FROM sqlite_master WHERE type = 'table'")) ) {
			trigger_error('Impossible d\'obtenir la liste des tables', ERROR);
		}
		
		$tables = array();
		while( $row = $result->fetch() ) {
			$tables[$row['tbl_name']] = '';
		}
		
		return $tables;
	}
	
	/**
	 * Utilisable pour l'ajout de requète supplémentaires (séquences, configurations diverses, etc)
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
	 * Retourne la structure d'une table de la base de données sous forme de requète SQL de type DDL
	 * 
	 * @param array   $tabledata    Informations sur la table (provenant de self::get_tables())
	 * @param boolean $drop_option  Ajouter une requète de suppression conditionnelle de table
	 * 
	 * @access public
	 * @return string
	 */
	function get_table_structure($tabledata, $drop_option)
	{
		$contents  = '-- ' . $this->eol;
		$contents .= '-- Struture de la table ' . $tabledata['name'] . ' ' . $this->eol;
		$contents .= '-- ' . $this->eol;
		
		if( $drop_option ) {
			$contents .= 'DROP TABLE IF EXISTS ' . $this->db->quote($tabledata['name']) . ';' . $this->eol;
		}
		
		$sql = "SELECT sql, type
			FROM sqlite_master
			WHERE tbl_name = '$tabledata[name]'
				AND sql IS NOT NULL";
		if( !($result = $this->db->query($sql)) ) {
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
	 * Retourne les données d'une table de la base de données sous forme de requètes SQL de type DML
	 * 
	 * @param string $tablename  Nom de la table à considérer
	 * 
	 * @access public
	 * @return string
	 */
	function get_table_data($tablename)
	{
		$contents = '';
		
		$sql = 'SELECT * FROM ' . $this->db->quote($tablename);
		if( !($result = $this->db->query($sql)) ) {
			trigger_error('Impossible d\'obtenir le contenu de la table ' . $tablename, ERROR);
		}
		
		$result->setFetchMode(SQLITE3_ASSOC);
		
		if( $row = $result->fetch() ) {
			$contents  = $this->eol;
			$contents .= '-- ' . $this->eol;
			$contents .= '-- Contenu de la table ' . $tablename . ' ' . $this->eol;
			$contents .= '-- ' . $this->eol;
			
			$fields = array();
			for( $j = 0, $n = $result->result->numColumns(); $j < $n; $j++ ) {
				array_push($fields, $this->db->quote($result->result->columnName($j)));
			}
			
			$fields = implode(', ', $fields);
			
			do {
				$contents .= sprintf("INSERT INTO %s (%s) VALUES", $this->db->quote($tablename), $fields);
				
				foreach( $row as $key => $value ) {
					if( is_null($value) ) {
						$row[$key] = 'NULL';
					}
					else {
						$row[$key] = '\'' . $this->db->escape($value) . '\'';
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
