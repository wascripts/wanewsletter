<?php
/**
 * Copyright (c) 2002-2010 Aurélien Maille
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

if( !defined('_INC_CLASS_WADB_FIREBIRD') ) {

define('_INC_CLASS_WADB_FIREBIRD', true);

/**
 * @access public
 * @status experimental
 */
class Wadb_firebird {
	
	/**
	 * Connexion à la base de données
	 * 
	 * @var resource
	 * @access private
	 */
	var $link;
	
	/**
	 * Hôte de la base de données
	 * 
	 * @var string
	 * @access public
	 */
	var $host = '';
	
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
	 * Statut de l'autocommit
	 * 
	 * @var boolean
	 * @access private
	 */
	var $autocommit = true;
	
	/**
	 * Mettre à false pour désactiver le remplacement automatique de LIMIT %d OFFSET %d
	 * dans les requêtes passées à la méthode query()
	 * 
	 * @var boolean
	 * @access private
	 */
	var $_matchLimitOffset = true;
	
	/**
	 * "Constantes" de la classe
	 */
	var $SQL_INSERT = 1;
	var $SQL_UPDATE = 2;
	var $SQL_DELETE = 3;
	
	/**
	 * Constructeur de classe
	 * 
	 * @param string $dbname   Nom de la base de données
	 * @param array  $options  Options de connexion/utilisation
	 * 
	 * @access public
	 */
	function Wadb_firebird($dbname, $options = null)
	{
		$this->dbname = $dbname;
		
		if( is_array($options) ) {
			$this->options = array_merge($this->options, $options);
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
				
				if( $info == 'host' ) {
					$this->dbname = $host . ':' . $this->dbname;
				}
			}
			
			$this->host = $host . (!is_null($port) ? ':'.$port : '');
		}
		
		$connect = 'ibase_connect';
		$charset = null;
		
		if( is_array($options) ) {
			$this->options = array_merge($this->options, $options);
		}
		
		if( !empty($this->options['persistent']) ) {
			$connect = 'ibase_pconnect';
		}
		if( !empty($this->options['charset']) ) {
			$charset = $this->options['charset'];
		}
		
		if( !($this->link = $connect($this->dbname, $username, $passwd, $charset)) ) {
			$this->errno = ibase_errcode();
			$this->error = ibase_errmsg();
			$this->link  = null;
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
	 * le nouveau jeu de caractères de la connexion en cours
	 * 
	 * @param string $encoding
	 * 
	 * @access public
	 * @return string
	 */
	function encoding($encoding = null)
	{
		$res = $this->query('SELECT RDB$CHARACTER_SET_NAME FROM RDB$DATABASE');
		$curEncoding = $res->column(0);
		
		if( !is_null($encoding) ) {
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
		if( $this->_matchLimitOffset && preg_match('/\s+LIMIT\s+(\d+)\s+OFFSET\s+(\d+)\s*$/i', $query, $match) ) {
			$query = substr($query, 0, -strlen($match[0]));
			$query = substr($query, 6);
			$query = "SELECT FIRST $match[1] SKIP $match[2]" . $query;
		}
		
		$curtime = array_sum(explode(' ', microtime()));
		$result  = ibase_query($this->link, $query);
		$endtime = array_sum(explode(' ', microtime()));
		
		$this->sqltime += ($endtime - $curtime);
		$this->lastQuery = $query;
		$this->queries++;
		
		if( !$result ) {
			$this->errno = ibase_errcode();
			$this->error = ibase_errmsg();
			
			$this->rollBack();
		}
		else {
			$this->errno = 0;
			$this->error = '';
			
			if( in_array(strtoupper(substr($query, 0, 6)), array('INSERT', 'UPDATE', 'DELETE')) && $this->autocommit ) {
				ibase_commit($this->link);
			}
			
			if( !is_bool($result) ) {// on a réceptionné une ressource ou un objet
				$result = new WadbResult_firebird($this->link, $result);
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
		return '"' . $name . '"';
	}
	
	/**
	 * @param mixed $tables  Nom de table ou tableau de noms de table
	 * 
	 * @access public
	 * @return void
	 */
	function vacuum($tables)
	{
		return true; // TODO
	}
	
	/**
	 * Démarre le mode transactionnel
	 * 
	 * @access public
	 * @return boolean
	 */
	function beginTransaction()
	{
		$this->autocommit = false;
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
		if( !($result = ibase_commit($this->link)) ) {
			ibase_rollback($this->link);
		}
		
		$this->autocommit = true;
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
		$this->autocommit = true;
		return ibase_rollback($this->link);
	}
	
	/**
	 * Renvoie le nombre de lignes affectées par la dernière requète DML
	 * 
	 * @access public
	 * @return boolean
	 */
	function affectedRows()
	{
		return ibase_affected_rows($this->link);
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
		if( preg_match('/^INSERT\s+INTO\s+([^\s]+)\s+/i', $this->lastQuery, $match) ) {
			$result = ibase_query($this->link, "SELECT GEN_ID('{$match[1]}_gen', 0) AS lastId
				FROM RDB\$DATABASE");
			
			if( is_resource($result) ) {
				$o = ibase_fetch_object($result);
				return $o->lastId;
			}
		}
		
		return false;
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
		return str_replace("'", "''", $string);
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
			@$this->rollBack();
			$result = ibase_close($this->link);
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

class WadbResult_firebird {
	
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
	 * "Constantes" de la classe
	 */
	var $SQL_FETCH_NUM   = 1;
	var $SQL_FETCH_ASSOC = 2;
	var $SQL_FETCH_BOTH  = 3;
	
	/**
	 * Constructeur de classe
	 * 
	 * @param resource $link    Ressource de connexion à la base de données
	 * @param resource $result  Ressource de résultat de requète
	 * 
	 * @access public
	 */
	function WadbResult_firebird($link, $result)
	{
		$this->link   = $link;
		$this->result = $result;
		$this->fetchMode = $this->SQL_FETCH_BOTH;
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
		
		if( $mode == $this->SQL_FETCH_NUM ) {
			$row = ibase_fetch_row($this->result, IBASE_TEXT);
		}
		else {
			$row = ibase_fetch_assoc($this->result, IBASE_TEXT);
			
			if( $mode == $this->SQL_FETCH_BOTH && is_array($row) ) {
				$tmp = array_values($row);
				$row = array_merge($row, $tmp);
			}
		}
		
		return $row;
	}
	
	/**
	 * Renvoie sous forme d'objet la ligne suivante dans le jeu de résultat
	 * 
	 * @access public
	 * @return object
	 */
	function fetchObject()
	{
		$row = ibase_fetch_object($this->result, IBASE_TEXT);
		settype($row, 'array');
		$row = array_change_key_case($row, CASE_LOWER);
		settype($row, 'object');
		
		return $row;
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
		$row = $this->fetch($this->SQL_FETCH_BOTH);
		
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
		if( in_array($mode, array($this->SQL_FETCH_NUM, $this->SQL_FETCH_ASSOC, $this->SQL_FETCH_BOTH)) ) {
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
			ibase_free_result($this->result);
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

}
?>
