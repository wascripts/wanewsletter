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

if( !defined('_INC_CLASS_WADB_MYSQL') ) {

define('_INC_CLASS_WADB_MYSQL', true);

define('SQL_INSERT', 1);
define('SQL_UPDATE', 2);
define('SQL_DELETE', 3);

define('SQL_FETCH_NUM',   MYSQL_NUM);
define('SQL_FETCH_ASSOC', MYSQL_ASSOC);
define('SQL_FETCH_BOTH',  MYSQL_BOTH);

class Wadb_mysql {
	
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
	 * Version du serveur
	 * 
	 * @var string
	 * @access public
	 */
	var $serverVersion = '';
	
	/**
	 * Version du client
	 * 
	 * @var string
	 * @access public
	 */
	var $clientVersion = '';
	
	/**
	 * Constructeur de classe
	 * 
	 * @param string $dbname   Nom de la base de données
	 * @param array  $options  Options de connexion/utilisation
	 * 
	 * @access public
	 */
	function Wadb_mysql($dbname, $options = null)
	{
		$this->dbname = $dbname;
		
		if( is_array($options) ) {
			$this->options = $options;
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
		if( is_array($infos) ) {
			foreach( array('host', 'username', 'passwd', 'port') as $info ) {
				$$info = ( isset($infos[$info]) ) ? $infos[$info] : null;
			}
		}
		
		$connect = 'mysql_connect';
		
		if( is_array($options) ) {
			$this->options = array_merge($this->options, $options);
			
			if( !empty($this->options['persistent']) ) {
				$connect = 'mysql_pconnect';
			}
		}
		
		if( !is_null($port) ) {
			$host .= ':' . $port;
		}
		
		if( !($this->link = $connect($host, $username, $passwd)) ) {
			$this->errno = mysql_errno();
			$this->error = mysql_error();
			$this->link  = null;
		}
		else if( !mysql_select_db($this->dbname) ) {
			$this->errno = mysql_errno($this->link);
			$this->error = mysql_error($this->link);
			
			mysql_close($this->link);
			$this->link  = null;
		}
		else {
			$this->serverVersion = mysql_get_server_info($this->link);
			$this->clientVersion = mysql_get_client_info();
			
			if( !empty($this->options['charset']) ) {
				$this->encoding($this->options['charset']);
			}
		}
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
	 * le nouveau jeu de caractères de la connexion en cours.
	 * Utilisable uniquement avec MySQL >= 4.1.1
	 * 
	 * @param string $encoding
	 * 
	 * @access public
	 * @return string
	 */
	function encoding($encoding = null)
	{
		$charsetSupport = version_compare($this->serverVersion, '4.1.1', '>=');
		
		if( $charsetSupport ) {
			$res = $this->query("SHOW VARIABLES LIKE 'character_set_client'");
			$curEncoding = $res->column('Value');
		}
		else {
			$curEncoding = 'latin1'; // TODO
		}
		
		if( $charsetSupport && !is_null($encoding) ) {
			$this->query("SET NAMES $encoding");
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
		//
		// Pour MySQL < 4.0.6 uniquement
		// @see http://dev.mysql.com/doc/refman/4.1/en/news-4-0-6.html
		//
		if( version_compare($this->serverVersion, '4.0.6', '<')
			&& preg_match('/\s+LIMIT\s+(\d+)\s+OFFSET\s+(\d+)\s*$/i', $query, $match) )
		{
			$query  = substr($query, 0, -strlen($match[0]));
			$query .= " LIMIT $match[2], $match[1]";
		}
		
		$curtime = array_sum(explode(' ', microtime()));
		$result  = mysql_query($query, $this->link);
		$endtime = array_sum(explode(' ', microtime()));
		
		$this->sqltime += ($endtime - $curtime);
		$this->queries++;
		
		if( !$result ) {
			$this->errno = mysql_errno($this->link);
			$this->error = mysql_error($this->link);
			$this->lastQuery = $query;
			
			$this->rollBack();
		}
		else {
			$this->errno = 0;
			$this->error = '';
			$this->lastQuery = '';
			
			if( !is_bool($result) ) {// on a réceptionné une ressource ou un objet
				$result = new WadbResult_mysql($this->link, $result);
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
		return '`' . $name . '`';
	}
	
	/**
	 * @param mixed $tables  Nom de table ou tableau de noms de table
	 * 
	 * @access public
	 * @return void
	 */
	function vacuum($tables)
	{
		if( is_array($tables) ) {
			$tables = implode(', ', $tables);
		}
		
		mysql_query('OPTIMIZE TABLE ' . $tables, $this->link);
	}
	
	/**
	 * Démarre le mode transactionnel
	 * 
	 * @access public
	 * @return boolean
	 */
	function beginTransaction()
	{
		mysql_query('SET AUTOCOMMIT=0', $this->link);
		return mysql_query('BEGIN', $this->link);
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
		if( !($result = mysql_query('COMMIT', $this->link)) ) {
			mysql_query('ROLLBACK', $this->link);
		}
		
		mysql_query('SET AUTOCOMMIT=1', $this->link);
		
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
		$result = mysql_query('ROLLBACK', $this->link);
		mysql_query('SET AUTOCOMMIT=1', $this->link);
		
		return $result;
	}
	
	/**
	 * Renvoie le nombre de lignes affectées par la dernière requète DML
	 * 
	 * @access public
	 * @return boolean
	 */
	function affectedRows()
	{
		return mysql_affected_rows($this->link);
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
		return mysql_insert_id($this->link);
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
		if( function_exists('mysql_real_escape_string') ) {
			$string = mysql_real_escape_string($string, $this->link);
		}
		else {
			$string = mysql_escape_string($string);
		}
		
		return $string;
	}
	
	/**
	 * Vérifie l'état de la connexion courante et effectue si besoin une reconnexion
	 * 
	 * @access public
	 * @return boolean
	 */
	function ping()
	{
		// mysql_ping() - php >= 4.3.0
		return ( function_exists('mysql_ping') ) ? mysql_ping($this->link) : false;
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
			@$this->rollBack();
			$result = mysql_close($this->link);
			$this->link = null;
			
			return $result;
		}
		else {
			return true;
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
		$this->close();
	}
}

class WadbResult_mysql {
	
	/**
	 * Connexion à la base de données
	 * 
	 * @var resource
	 * @access private
	 */
	var $link;
	
	/**
	 * Ressource de résultat de requète
	 * 
	 * @var resource
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
	 * Constructeur de classe
	 * 
	 * @param resource $link    Ressource de connexion à la base de données
	 * @param resource $result  Ressource de résultat de requète
	 * 
	 * @access public
	 */
	function WadbResult_mysql($link, $result)
	{
		$this->link   = $link;
		$this->result = $result;
		$this->fetchMode = MYSQL_BOTH;
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
		
		return mysql_fetch_array($this->result, $mode);
	}
	
	/**
	 * Renvoie sous forme d'objet la ligne suivante dans le jeu de résultat
	 * 
	 * @access public
	 * @return object
	 */
	function fetchObject()
	{
		return mysql_fetch_object($this->result);
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
		$row = mysql_fetch_array($this->result);
		
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
		if( in_array($mode, array(MYSQL_NUM, MYSQL_ASSOC, MYSQL_BOTH)) ) {
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
		if( !is_null($this->result) && is_resource($this->link) ) {
			mysql_free_result($this->result);
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

class WadbBackup_mysql {
	
	/**
	 * Informations concernant la base de données
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
	 * @param array $infos  Informations concernant la base de données
	 * 
	 * @access public
	 */
	function WadbBackup_mysql($infos)
	{
		$this->infos = $infos;
		
		if( !isset($this->infos['host']) ) {
			$this->infos['host'] = 'localhost';
		}
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
		global $db;
		
		$contents  = '-- ' . $this->eol;
		$contents .= "-- $toolname MySQL Dump" . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= "-- Host     : " . $this->infos['host'] . $this->eol;
		$contents .= "-- Server   : " . $db->serverVersion . $this->eol;
		$contents .= "-- Database : " . $this->infos['dbname'] . $this->eol;
		$contents .= '-- Date     : ' . date('d/m/Y H:i:s O') . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= $this->eol;
		
		if( version_compare($db->serverVersion, '4.1.2', '>=') ) {
			$contents .= sprintf("SET NAMES '%s';%s", $db->encoding(), $this->eol);
			$contents .= $this->eol;
		}
		
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
		global $db;
		
		if( !($result = $db->query('SHOW TABLE STATUS FROM ' . $db->quote($this->infos['dbname']))) ) {
			trigger_error('Impossible d\'obtenir la liste des tables', ERROR);
		}
		
		$tables = array();
		while( $row = $result->fetch() ) {
			$tables[$row['Name']] = ( isset($row['Engine']) ) ? $row['Engine'] : $row['Type'];
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
		global $db;
		
		$contents  = '-- ' . $this->eol;
		$contents .= '-- Struture de la table ' . $tabledata['name'] . ' ' . $this->eol;
		$contents .= '-- ' . $this->eol;
		
		if( $drop_option ) {
			$contents .= 'DROP TABLE IF EXISTS ' . $db->quote($tabledata['name']) . ';' . $this->eol;
		}
		
		//
		// La requète 'SHOW CREATE TABLE' est disponible à partir de MySQL 3.23.20
		//
		if( version_compare($db->serverVersion, '3.23.20', '<') ) {
			if( !($result = $db->query('SHOW CREATE TABLE ' . $db->quote($tabledata['name']))) ) {
				trigger_error('Impossible d\'obtenir la structure de la table', ERROR);
			}
			
			$create_table = $result->column('Create Table');
			$result->free();
			
			$contents .= preg_replace("/(\r\n?)|\n/", $this->eol, $create_table);
		}
		else {
			$contents .= 'CREATE TABLE ' . $db->quote($tabledata['name']) . ' (' . $this->eol;
			
			if( !($result = $db->query('SHOW COLUMNS FROM ' . $db->quote($tabledata['name']))) ) {
				trigger_error('Impossible d\'obtenir les noms des colonnes de la table', ERROR);
			}
			
			$end_line = false;
			while( $row = $result->fetch() ) {
				if( $end_line ) {
					$contents .= ',' . $this->eol;
				}
				
				$contents .= "\t" . $quote . $row['Field'] . $quote . ' ' . $row['Type'];
				$contents .= ( $row['Null'] != 'YES' ) ? ' NOT NULL' : '';
				$contents .= ( !is_null($row['Default']) ) ? ' DEFAULT \'' . $row['Default'] . '\'' : ' DEFAULT NULL';
				$contents .= ( $row['Extra'] != '' ) ? ' ' . $row['Extra'] : '';
				
				$end_line = true;
			}
			$result->free();
			
			if( !($result = $db->query('SHOW INDEX FROM ' . $db->quote($tabledata['name']))) ) {
				trigger_error('Impossible d\'obtenir les clés de la table', ERROR);
			}
			
			$index = array();
			while( $row = $result->fetch() ) {
				$name = $row['Key_name'];
				
				if( $name != 'PRIMARY' && $row['Non_unique'] == 0 ) {
					$name = 'unique=' . $name;
				}
				
				if( !isset($index[$name]) ) {
					$index[$name] = array();
				}
				
				$index[$name][] = $db->quote($row['Column_name']);
			}
			$result->free();
			
			foreach( $index as $var => $columns ) {
				$contents .= ',' . $this->eol . "\t";
				
				if( $var == 'PRIMARY' ) {
					$contents .= 'CONSTRAINT PRIMARY KEY';
				}
				else if( preg_match('/^unique=(.+)$/', $var, $match) ) {
					$contents .= 'CONSTRAINT ' . $db->quote($match[1]) . ' UNIQUE';
				}
				else {
					$contents .= 'INDEX ' . $db->quote($var);
				}
				
				$contents .= ' (' . implode(', ', $columns) . ')';
			}
			
			$contents .= $this->eol . ')' . ( ( !empty($tabledata['type']) ) ? ' TYPE=' . $tabledata['type'] : '' );
		}
		
		return $contents . ';' . $this->eol;
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
		global $db;
		
		$contents = '';
		
		$sql = 'SELECT * FROM ' . $db->quote($tablename);
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
			for( $j = 0, $n = mysql_num_fields($result->result); $j < $n; $j++ ) {
				$data = mysql_fetch_field($result->result, $j);
				$fields[] = $db->quote($data->name);
			}
			
			$fields = implode(', ', $fields);
			
			do {
				$contents .= 'INSERT INTO ' . $db->quote($tablename) . " ($fields) VALUES";
				
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
		$result->free();
		
		return $contents;
	}
}

}
?>
