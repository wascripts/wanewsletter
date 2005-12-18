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
	/**
	 * Ressource de connexion
	 * 
	 * @var resource
	 */
	var $connect_id   = '';
	
	/**
	 * Ressource de résultat
	 * 
	 * @var resource
	 */
	var $query_result = '';
	
	/**
	 * Transaction en cours ou non
	 * 
	 * @var integer
	 */
	var $trc_started  = 0;
	
	/**
	 * Retours d'erreur (code et message)
	 * 
	 * @var array
	 */
	var $sql_error    = array('errno' => '', 'message' => '', 'query' => '');
	
	/**
	 * Nombre de requètes effectuées depuis le lancement du script
	 * 
	 * @var integer
	 */
	var $queries      = 0;
	
	/**
	 * Temps d'exécution du script affecté au traitement des requètes SQL
	 * 
	 * @var string
	 */
	var $sql_time     = 0;
	
	/**
	 * sql::sql()
	 * 
	 * Constructeur de classe
	 * Initialise la connexion à la base de données
	 * 
	 * @param string  $dbhost      Hôte de la base de données
	 * @param string  $dbuser      Nom d'utilisateur
	 * @param string  $dbpwd       Mot de passe
	 * @param string  $dbname      Nom de la base de données
	 * @param boolean $persistent  Connexion persistante ou non
	 * 
	 * @access public
	 * @return void
	 */
	function sql($dbhost, $dbuser, $dbpwd, $dbname, $persistent = false)
	{
		$sql_connect = ( $persistent ) ? 'mysql_pconnect' : 'mysql_connect';
		
		$this->connect_id = @$sql_connect($dbhost, $dbuser, $dbpwd);
		
		if( $this->connect_id != false )
		{
			$select_db = mysql_select_db($dbname, $this->connect_id);
			
			if( !$select_db )
			{
				$this->sql_error['errno']   = mysql_errno($this->connect_id);
				$this->sql_error['message'] = mysql_error($this->connect_id);
				$this->close($this->connect_id);
			}
		}
		else
		{
			$this->sql_error['errno']   = mysql_errno();
			$this->sql_error['message'] = mysql_error();
		}
	}
	
	/**
	 * sql::prepare_value()
	 * 
	 * Prépare une valeur pour son insertion dans la base de données
	 * (Dans la pratique, échappe les caractères potentiellement dangeureux)
	 * 
	 * @param mixed $value
	 * 
	 * @access private
	 * @return mixed
	 */
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
	
	/**
	 * sql::query_build()
	 * 
	 * Construit une requète de type INSERT ou UPDATE à partir
	 * des diverses données fournies
	 * 
	 * @param string $query_type  Type de requète (peut valoir INSERT ou UPDATE)
	 * @param string $table       Table sur laquelle effectuer la requète
	 * @param array  $query_data  Tableau des données à insérer. Le tableau a la structure suivante:
	 *                            array(column_name => column_value[, column_name => column_value])
	 * @param string $sql_where   Chaîne de condition
	 * 
	 * @access public
	 * @return string
	 */
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
	
	/**
	 * sql::query()
	 * 
	 * Effectue une requète à destination de la base de données et retourne le résultat
	 * En cas d'erreur, la méthode stocke les informations d'erreur dans sql::sql_error
	 * et retourne false
	 * 
	 * @param string  $query  La requète SQL à exécuter
	 * @param integer $start  Réupére les lignes de résultat à partir de la position $start
	 * @param integer $limit  Limite le nombre de résultat à retourner
	 * 
	 * @access public
	 * @return resource
	 */
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
	
	/**
	 * sql::transaction()
	 * 
	 * Gestion des transactions
	 * 
	 * @param integer $transaction
	 * 
	 * @access public
	 * @return boolean
	 */
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
	
	/**
	 * sql::check()
	 * 
	 * Optimisation des tables
	 * 
	 * @param mixed $tables  Nom de la table ou tableau de noms de table à optimiser
	 * 
	 * @access public
	 * @return void
	 */
	function check($tables)
	{
		if( is_array($tables) )
		{
			$tables_list = implode(', ', $tables);
		}
		
		$tables = implode(', ', $tables);
		
		@mysql_query('OPTIMIZE TABLE ' . $tables, $this->connect_id);
	}
	
	/**
	 * sql::num_rows()
	 * 
	 * Nombre de lignes retournées
	 * 
	 * @param resource $result  Ressource de résultat de requète
	 * 
	 * @access public
	 * @return mixed
	 */
	function num_rows($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( $result != false ) ? @mysql_num_rows($result) : false;
	}
	
	/**
	 * sql::affected_rows()
	 * 
	 * Nombre de lignes affectées par la dernière requète DML
	 * 
	 * @param resource $result  Ressource de résultat de requète
	 * 
	 * @access public
	 * @return mixed
	 */
	function affected_rows()
	{
		return ( $this->connect_id != false ) ? @mysql_affected_rows($this->connect_id) : false;
	}
	
	/**
	 * sql::fetch_row()
	 * 
	 * Retourne un tableau indexé numériquement correspondant à la ligne de résultat courante
	 * et déplace le pointeur de lecture des résultats
	 * 
	 * @param resource $result  Ressource de résultat de requète
	 * 
	 * @access public
	 * @return mixed
	 */
	function fetch_row($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( $result != false ) ? @mysql_fetch_row($result) : false;
	}
	
	/**
	 * sql::fetch_array()
	 * 
	 * Retourne un tableau associatif correspondant à la ligne de résultat courante
	 * et déplace le pointeur de lecture des résultats
	 * 
	 * @param resource $result  Ressource de résultat de requète
	 * 
	 * @access public
	 * @return mixed
	 */
	function fetch_array($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( $result != false ) ? @mysql_fetch_array($result, MYSQL_ASSOC) : false;
	}
	
	/**
	 * sql::fetch_rowset()
	 * 
	 * Retourne un tableau bi-dimensionnel correspondant à toutes les lignes de résultat
	 * 
	 * @param resource $result  Ressource de résultat de requète
	 * 
	 * @access public
	 * @return array
	 */
	function fetch_rowset($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		$rowset = array();
		while( $row = @mysql_fetch_array($result, MYSQL_ASSOC) )
		{
			array_push($rowset, $row);
		}
		
		return $rowset;
	}
	
	/**
	 * sql::num_fields()
	 * 
	 * Retourne le nombre de champs dans le résultat
	 * 
	 * @param resource $result  Ressource de résultat de requète
	 * 
	 * @access public
	 * @return mixed
	 */
	function num_fields($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( $result != false ) ? @mysql_num_fields($result) : false;
	}
	
	/**
	 * sql::field_name()
	 * 
	 * Retourne le nom de la colonne à l'index $offset dans le résultat
	 * 
	 * @param integer  $offset  Position de la colonne dans le résultat
	 * @param resource $result  Ressource de résultat de requète
	 * 
	 * @access public
	 * @return mixed
	 */
	function field_name($offset, $result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( $result != false ) ? @mysql_field_name($result, $offset) : false;
	}
	
	/**
	 * sql::result()
	 * 
	 * Retourne la valeur d'une colonne dans une ligne de résultat donnée
	 * 
	 * @param resource $result  Ressource de résultat de requète
	 * @param integer  $row     Numéro de la ligne de résultat
	 * @param string   $field   Nom de la colonne
	 * 
	 * @access public
	 * @return mixed
	 */
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
	
	/**
	 * sql::result_seek()
	 * 
	 * Déplace le pointeur interne de résultat
	 * 
	 * @param integer  $row     Numéro de la ligne de résultat
	 * @param resource $result  Ressource de résultat de requète
	 * 
	 * @access public
	 * @return boolean
	 */
	function result_seek($row, $result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( $result != false ) ? mysql_data_seek($result, $row) : false;
	}
	
	/**
	 * sql::next_id()
	 * 
	 * Retourne l'identifiant généré par la dernière requête INSERT
	 * 
	 * @access public
	 * @return mixed
	 */
	function next_id()
	{
		return ( $this->connect_id != false ) ? @mysql_insert_id($this->connect_id) : false;
	}
	
	/**
	 * sql::free_result()
	 * 
	 * Libère le résultat de la mémoire
	 * 
	 * @param resource $result  Ressource de résultat de requète
	 * 
	 * @access public
	 * @return void
	 */
	function free_result($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		if( $result != false )
		{
			@mysql_free_result($result);
		}
	}
	
	/**
	 * sql::escape()
	 * 
	 * Échappe une chaîne de caractère en prévision de son insertion dans la base de données
	 * 
	 * @param string $str
	 * 
	 * @access public
	 * @return string
	 */
	function escape($str)
	{
		return mysql_escape_string($str);
	}
	
	/**
	 * sql::close()
	 * 
	 * Clôt la connexion à la base de données
	 * 
	 * @access public
	 * @return boolean
	 */
	function close()
	{
		if( $this->connect_id != false )
		{
			$this->free_result($this->query_result);
			$this->transaction(END_TRC);
			
			$result = @mysql_close($this->connect_id);
			$this->connect_id   = null;
			$this->query_result = null;
			
			return $result;
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
	
	/**
	 * sql_backup::header()
	 * 
	 * Génération de l'en-tête du fichier de sauvegarde
	 * 
	 * @param string $dbhost    Hôte de la base de données
	 * @param string $dbname    Nom de la base de données
	 * @param string $toolname  Nom de l'outil utilisé pour générer la sauvegarde
	 * 
	 * @access public
	 * @return string
	 */
	function header($dbhost, $dbname, $toolname = '')
	{
		global $db;
		
		$contents  = '-- ' . $this->eol;
		$contents .= "-- $toolname MySQL Dump" . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= "-- Host     : $dbhost" . $this->eol;
		$contents .= "-- Server   : " . mysql_get_server_info($db->connect_id) . $this->eol;
		$contents .= "-- Database : $dbname" . $this->eol;
		$contents .= '-- Date     : ' . date('d/m/Y H:i:s') . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= $this->eol;
		
		return $contents;
	}
	
	/**
	 * sql_backup::get_tables()
	 * 
	 * Retourne la liste des tables présentes dans la base de données considérée
	 * 
	 * @param string $dbname
	 * 
	 * @access public
	 * @return array
	 */
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
		$db->free_result($result);
		
		return $tables;
	}
	
	/**
	 * sql_backup::get_table_structure()
	 * 
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
		if( version_compare(mysql_get_server_info($db->connect_id), '3.23.20', '>=') == true )
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
			
			if( !($result = $db->query('SHOW COLUMNS FROM ' . $quote . $tabledata['name'] . $quote)) )
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
				$contents .= ( $row['Null'] != 'YES' ) ? ' NOT NULL' : '';
				$contents .= ( !is_null($row['Default']) ) ? ' DEFAULT \'' . $row['Default'] . '\'' : ' DEFAULT NULL';
				$contents .= ( $row['Extra'] != '' ) ? ' ' . $row['Extra'] : '';
				
				$end_line = true;
			}
			$db->free_result($result);
			
			if( !($result = $db->query('SHOW INDEX FROM ' . $quote . $tabledata['name'] . $quote)) )
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
				
				$index[$name][] = $row['Column_name'];
			}
			$db->free_result($result);
			
			foreach( $index AS $var => $columns )
			{
				$contents .= ',' . $this->eol . "\t";
				
				if( $var == 'PRIMARY' )
				{
					$contents .= 'CONSTRAINT PRIMARY KEY';
				}
				else if( preg_match('/^unique=(.+)$/', $var, $match) )
				{
					$contents .= 'CONSTRAINT ' . $quote . $match[1] . $quote . ' UNIQUE';
				}
				else
				{
					$contents .= 'INDEX ' . $quote . $var . $quote;
				}
				
				$contents .= ' (' . $quote . implode($quote . ', ' . $quote, $columns) . $quote . ')';
			}
			
			$contents .= $this->eol . ')' . ( ( !empty($tabledata['type']) ) ? ' TYPE=' . $tabledata['type'] : '' );
		}
		
		return $contents . ';' . $this->eol;
	}
	
	/**
	 * sql_backup::get_table_data()
	 * 
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
			for( $j = 0, $n = $db->num_fields($result); $j < $n; $j++ )
			{
				$fields[] = $db->field_name($j, $result);
			}
			
			$fields = implode($quote . ', ' . $quote, $fields);
			
			do
			{
				$contents .= 'INSERT INTO ' . $quote . $tablename . $quote . ' (' . $quote . $fields . $quote . ') VALUES';
				
				foreach( $row AS $key => $value )
				{
					if( is_null($value) )
					{
						$row[$key] = 'NULL';
					}
					else
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