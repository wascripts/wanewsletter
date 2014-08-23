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
 */

if( !defined('_INC_CLASS_WADB_POSTGRES') ) {

define('_INC_CLASS_WADB_POSTGRES', true);

define('SQL_INSERT', 1);
define('SQL_UPDATE', 2);
define('SQL_DELETE', 3);

define('SQL_FETCH_NUM',   PGSQL_NUM);
define('SQL_FETCH_ASSOC', PGSQL_ASSOC);
define('SQL_FETCH_BOTH',  PGSQL_BOTH);

class Wadb_postgres {
	
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
	 * Nombre de lignes affectées par la dernière requète DML
	 * 
	 * @var integer
	 * @access private
	 */
	var $_affectedRows = 0;
	
	/**
	 * Constructeur de classe
	 * 
	 * @param string $dbname   Nom de la base de données
	 * @param array  $options  Options de connexion/utilisation
	 * 
	 * @access public
	 */
	function Wadb_postgres($dbname, $options = null)
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
		$connectString = "dbname='$this->dbname' ";
		
		if( is_array($infos) ) {
			foreach( array('host', 'username', 'passwd', 'port') as $info ) {
				if( isset($infos[$info]) ) {
					if( $info == 'username' ) {
						$connectString .= "user='$infos[$info]' ";
					}
					else if( $info == 'passwd' ) {
						$connectString .= "password='$infos[$info]' ";
					}
					else {
						$connectString .= "$info='$infos[$info]' ";
					}
				}
			}
		}
		
		$connect = 'pg_connect';
		
		if( is_array($options) ) {
			$this->options = array_merge($this->options, $options);
			
			if( !empty($this->options['persistent']) ) {
				$connect = 'pg_pconnect';
			}
		}
		
		if( !($this->link = $connect($connectString)) || pg_connection_status($this->link) !== PGSQL_CONNECTION_OK ) {
			$this->error = @$php_errormsg;
			$this->link  = null;
		}
		else {
			if( function_exists('pg_version') ) {// PHP >= 5.0
				$tmp = pg_version($this->link);
				$this->clientVersion = $tmp['client'];
				$this->serverVersion = $tmp['server'];
			}
			else {
				$res = pg_query($this->link, "SELECT VERSION() AS version");
				$this->serverVersion = pg_fetch_result($res, 0, 'version');
			}
			
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
	 * le nouveau jeu de caractères de la connexion en cours
	 * 
	 * @param string $encoding
	 * 
	 * @access public
	 * @return string
	 */
	function encoding($encoding = null)
	{
		$curEncoding = pg_client_encoding($this->link);
		
		if( !is_null($encoding) ) {
			pg_set_client_encoding($this->link, $encoding);
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
		$result  = pg_query($this->link, $query);
		$endtime = array_sum(explode(' ', microtime()));
		
		$this->sqltime += ($endtime - $curtime);
		$this->lastQuery = $query;
		$this->queries++;
		
		if( !$result ) {
			$this->error = pg_last_error($this->link);
			
			$this->rollBack();
		}
		else {
			$this->error = '';
			
			if( in_array(strtoupper(substr($query, 0, 6)), array('INSERT', 'UPDATE', 'DELETE')) ) {
				$this->_affectedRows = @pg_affected_rows($result);
				$result = true;
			}
			
			if( !is_bool($result) ) {// on a réceptionné une ressource ou un objet
				$result = new WadbResult_postgres($this->link, $result);
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
		if( !is_array($tables) ) {
			$tables = array($tables);
		}
		
		foreach( $tables as $tablename ) {
			pg_query($this->link, 'VACUUM ' . $tablename);
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
		return pg_query($this->link, 'BEGIN');
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
		if( !($result = pg_query($this->link, 'COMMIT')) ) {
			pg_query($this->link, 'ROLLBACK');
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
		return pg_query($this->link, 'ROLLBACK');
	}
	
	/**
	 * Renvoie le nombre de lignes affectées par la dernière requète DML
	 * 
	 * @access public
	 * @return boolean
	 */
	function affectedRows()
	{
		return $this->_affectedRows;
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
			$result = pg_query($this->link, "SELECT currval('{$match[1]}_id_seq') AS lastId");
			
			if( is_resource($result) ) {
				return pg_fetch_result($result, 0, 'lastId');
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
		return pg_escape_string($string);
	}
	
	/**
	 * Vérifie l'état de la connexion courante et effectue si besoin une reconnexion
	 * 
	 * @access public
	 * @return boolean
	 */
	function ping()
	{
		return pg_ping($this->link);
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
			$result = pg_close($this->link);
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

class WadbResult_postgres {
	
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
	function WadbResult_postgres($link, $result)
	{
		$this->link   = $link;
		$this->result = $result;
		$this->fetchMode = PGSQL_BOTH;
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
		
		return pg_fetch_array($this->result, null, $mode);
	}
	
	/**
	 * Renvoie sous forme d'objet la ligne suivante dans le jeu de résultat
	 * 
	 * @access public
	 * @return object
	 */
	function fetchObject()
	{
		return pg_fetch_object($this->result);
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
		$row = pg_fetch_array($this->result);
		
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
		if( in_array($mode, array(PGSQL_NUM, PGSQL_ASSOC, PGSQL_BOTH)) ) {
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
			pg_free_result($this->result);
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

/**
 * Certaines parties sont basées sur phpPgAdmin 2.4.2
 */
class WadbBackup_postgres {
	
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
	function WadbBackup_postgres($infos)
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
		
		$contents  = '/* ------------------------------------------------------------ ' . $this->eol;
		$contents .= "  $toolname PostgreSQL Dump" . $this->eol;
		$contents .= $this->eol;
		$contents .= "  Host     : " . $this->infos['host'] . $this->eol;
		$contents .= "  Server   : " . $db->serverVersion . $this->eol;
		$contents .= "  Database : " . $this->infos['dbname'] . $this->eol;
		$contents .= '  Date     : ' . date('d/m/Y H:i:s O') . $this->eol;
		$contents .= ' ------------------------------------------------------------ */' . $this->eol;
		$contents .= $this->eol;
		
		$contents .= sprintf("SET NAMES '%s';%s", $db->encoding(), $this->eol);
		$contents .= "SET standard_conforming_strings = off;" . $this->eol;
		$contents .= "SET escape_string_warning = off;" . $this->eol;
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
		global $db;
		
		$sql = "SELECT tablename 
			FROM pg_tables 
			WHERE tablename NOT LIKE 'pg%' 
			ORDER BY tablename";
		if( !($result = $db->query($sql)) ) {
			trigger_error('Impossible d\'obtenir la liste des tables', ERROR);
		}
		
		$tables = array();
		while( $row = $result->fetch() ) {
			$tables[$row['tablename']] = '';
		}
		
		return $tables;
	}
	
	/**
	 * Retourne une chaîne de requète pour la regénération des séquences
	 * 
	 * @param boolean $drop_option  Ajouter une requète de suppression conditionnelle de séquence
	 * 
	 * @access public
	 * @return string
	 */
	function get_other_queries($drop_option)
	{
		global $db, $backup_type;
		
		$contents  = '/* ------------------------------------------------------------ ' . $this->eol;
		$contents .= '  Sequences ' . $this->eol;
		$contents .= ' ------------------------------------------------------------ */' . $this->eol;
		
		$sql = "SELECT relname
			FROM pg_class
			WHERE NOT relname ~ 'pg_.*' AND relkind ='S'
			ORDER BY relname";
		if( !($result = $db->query($sql)) ) {
			trigger_error('Impossible de récupérer les séquences', ERROR);
		}
		
		$contents = '';
		while( $sequence = $result->column('relname') ) {
			
			$result_seq = $db->query('SELECT * FROM ' . $sequence);
			
			if( $row = $result_seq->fetch() ) {
				if( $drop_option ) {
					$contents .= "DROP SEQUENCE IF EXISTS $sequence;" . $this->eol;
				}
				
				$contents .= 'CREATE SEQUENCE ' . $sequence
					. ' start ' . $row['last_value']
					. ' increment ' . $row['increment_by']
					. ' maxvalue ' . $row['max_value']
					. ' minvalue ' . $row['min_value']
					. ' cache ' . $row['cache_value'] . '; ' . $this->eol;
				
				if( $row['last_value'] > 1 && $backup_type != 1 ) {
					//$contents .= 'SELECT NEXTVAL(\'' . $sequence . '\'); ' . $this->eol;
				}
			}
		}
		
		return $contents . $this->eol;
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
		
		$contents  = '/* ------------------------------------------------------------ ' . $this->eol;
		$contents .= '  Struture de la table ' . $tabledata['name'] . ' ' . $this->eol;
		$contents .= ' ------------------------------------------------------------ */' . $this->eol;
		
		if( $drop_option ) {
			$contents .= 'DROP TABLE IF EXISTS ' . $tabledata['name'] . ';' . $this->eol;
		}
		
		$sql = "SELECT a.attnum, a.attname AS field, t.typname as type, a.attlen AS length, 
				a.atttypmod as lengthvar, a.attnotnull as notnull 
			FROM pg_class c, pg_attribute a, pg_type t 
			WHERE c.relname = '" . $tabledata['name'] . "' 
				AND a.attnum > 0 
				AND a.attrelid = c.oid 
				AND a.atttypid = t.oid 
			ORDER BY a.attnum";
		if( !($result = $db->query($sql)) ) {
			trigger_error('Impossible d\'obtenir le contenu de la table ' . $tabledata['name'], ERROR);
		}
		
		$contents .= 'CREATE TABLE ' . $tabledata['name'] . ' (' . $this->eol;
		
		while( $row = $result->fetch() ) {
			$sql = "SELECT d.adsrc AS rowdefault 
				FROM pg_attrdef d, pg_class c 
				WHERE (c.relname = '" . $tabledata['name'] . "') 
					AND (c.oid = d.adrelid) 
					AND d.adnum = " . $row['attnum'];
			if( $res = $db->query($sql) ) {
				$row['rowdefault'] = $res->column('rowdefault');
			}
			else {
				unset($row['rowdefault']);
			}
			
			if( $row['type'] == 'bpchar' ) {
				// Internally stored as bpchar, but isn't accepted in a CREATE TABLE statement.
				$row['type'] = 'character';
			}
			
			$contents .= ' ' . $row['field'] . ' ' . $row['type'];
			
			if( preg_match('#char#i', $row['type']) && $row['lengthvar'] > 0 ) {
				$contents .= '(' . ($row['lengthvar'] - 4) . ')';
			}
			else if( preg_match('#numeric#i', $row['type']) ) {
				$contents .= sprintf('(%s,%s)', (($row['lengthvar'] >> 16) & 0xffff), (($row['lengthvar'] - 4) & 0xffff));
			}
			
			if( $row['notnull'] == 't' ) {
				$contents .= ' DEFAULT ' . $row['rowdefault'];
				$contents .= ' NOT NULL';
			}
			
			$contents .= ',' . $this->eol;
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
		if( !($result = $db->query($sql)) ) {
			trigger_error('Impossible de récupérer les clés primaires et unique de la table ' . $tabledata['name'], ERROR);
		}
		
		$primary_key = $primary_key_name = '';
		$index_rows  = array();
		
		while( $row = $result->fetch() ) {
			if( $row['primary_key'] == 't' ) {
				$primary_key .= ( ( $primary_key != '' ) ? ', ' : '' ) . $row['column_name'];
				$primary_key_name = $row['index_name'];
			}
			else {
				//
				// We have to store this all this info because it is possible to have a multi-column key...
				// we can loop through it again and build the statement
				//
				$index_rows[$row['index_name']]['table']  = $tabledata['name'];
				$index_rows[$row['index_name']]['unique'] = ($row['unique_key'] == 't') ? 'UNIQUE' : '';
				
				if( !isset($index_rows[$row['index_name']]['column_names']) ) {
					$index_rows[$row['index_name']]['column_names'] = array();
				}
				
				$index_rows[$row['index_name']]['column_names'][] = $row['column_name'];
			}
		}
		$result->free();
		
		if( !empty($primary_key) ) {
			$contents .= sprintf("CONSTRAINT %s PRIMARY KEY (%s),", $primary_key_name, $primary_key);
			$contents .= $this->eol;
		}
		
		$index_create = '';
		if( count($index_rows) ) {
			foreach( $index_rows as $idx_name => $props ) {
				$props['column_names'] = implode(', ', $props['column_names']);
				
				if( !empty($props['unique']) ) {
					$contents .= sprintf("CONSTRAINT %s UNIQUE (%s),", $idx_name, $props['column_names']);
					$contents .= $this->eol;
				}
				else {
					$index_create .= sprintf("CREATE %s INDEX %s ON %s (%s);", $props['unique'], $idx_name, $tabledata['name'], $props['column_names']);
					$index_create .= $this->eol;
				}
			}
		}
		
		//
		// Generate constraint clauses for CHECK constraints
		//
/*		$sql = "SELECT rcname as index_name, rcsrc 
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
		if( !($result = $db->query($sql)) ) {
			trigger_error('Impossible de récupérer les clauses de contraintes de la table ' . $tabledata['name'], ERROR);
		}
		
		//
		// Add the constraints to the sql file.
		//
		while( $row = $result->fetch() ) {
			$contents .= 'CONSTRAINT ' . $row['index_name'] . ' CHECK ' . $row['rcsrc'] . ',' . $this->eol;
		}
		*/
		$len = strlen(',' . $this->eol);
		$contents = substr($contents, 0, -$len);
		$contents .= $this->eol . ');' . $this->eol;
		
		if( !empty($index_create) ) {
			$contents .= $index_create;
		}
		
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
		global $db;
		
		$contents = '';
		
		$sql = 'SELECT * FROM ' . $tablename;
		if( !($result = $db->query($sql)) ) {
			trigger_error('Impossible d\'obtenir le contenu de la table ' . $tablename, ERROR);
		}
		
		$result->setFetchMode(SQL_FETCH_ASSOC);
		
		if( $row = $result->fetch() ) {
			$contents  = $this->eol;
			$contents .= '/* ------------------------------------------------------------ ' . $this->eol;
			$contents .= '  Contenu de la table ' . $tablename . ' ' . $this->eol;
			$contents .= ' ------------------------------------------------------------ */' . $this->eol;
			
			$fields = array();
			for( $j = 0, $n = pg_num_fields($result->result); $j < $n; $j++ ) {
				array_push($fields, pg_field_name($result->result, $j));
			}
			
			$fields = implode(', ', $fields);
			
			do {
				$contents .= "INSERT INTO $tablename ($fields) VALUES";
				
				foreach( $row as $key => $value ) {
					if( is_null($value) ) {
						$row[$key] = 'NULL';
					}
					else {
						$row[$key] = '\'' . addcslashes($db->escape($value), "\r\n") . '\'';
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
