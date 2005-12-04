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
define('DATABASE', 'sqlite');

/**
 * @todo
 * - Attention à l'encodage
 * Si sqlite_libencoding() retourne UTF-8, faire une conversion vers le charset de 
 * configuration de Wanewsletter ?
 */
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
	function sql($dbpath, $dbuser = null, $dbpwd = null, $dbname = null, $persistent = false)
	{
		$sql_connect = ( $persistent ) ? 'sqlite_popen' : 'sqlite_open';
		
		$this->connect_id = @$sql_connect($dbpath, 0666, $errorstr);
		
		if( is_resource($this->connect_id) )
		{
			$this->query('PRAGMA short_column_names = 1');
			$this->query('PRAGMA case_sensitive_like = 0');
		}
		else
		{
			$this->sql_error['message'] = $errorstr;
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
	 * Construit une requète de type INSERT, UPDATE ou DELETE à partir
	 * des diverses données fournies
	 * 
	 * @param string $query_type  Type de requète (peut valoir INSERT, UPDATE ou DELETE)
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
		
		$this->query_result = @sqlite_query($this->connect_id, $query);
		
		$endtime = explode(' ', microtime());
		$endtime = $endtime[0] + $endtime[1] - $starttime;
		
		$this->sql_time += ($endtime - $curtime);
		$this->queries++;
		
		if( !$this->query_result )
		{
			$this->sql_error['errno']   = sqlite_last_error($this->connect_id);
			$this->sql_error['message'] = sqlite_error_string($this->sql_error['errno']);
			$this->sql_error['query']   = $query;
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
					
					if( !($result = @sqlite_exec($this->connect_id, 'COMMIT')) )
					{
						@sqlite_exec($this->connect_id, 'ROLLBACK');
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
		if( !is_array($tables) )
		{
			$tables = array($tables); 
		}
		
		foreach( $tables AS $tablename )
		{
			@sqlite_exec($this->connect_id, 'VACUUM ' . $tablename); 
		}
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
		
		return ( $result != false ) ? sqlite_num_rows($result) : false;
	}
	
	/**
	 * sql::affected_rows()
	 * 
	 * Nombre de lignes affectées par la dernière requète DML
	 * 
	 * @access public
	 * @return mixed
	 */
	function affected_rows()
	{
		return ( $this->connect_id != false ) ? sqlite_changes($this->connect_id) : false;
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
		
		return ( $result != false ) ? @sqlite_fetch_array($result, SQLITE_NUM) : false;
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
		
		return ( $result != false ) ? @sqlite_fetch_array($result, SQLITE_ASSOC) : false;
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
		
		return ( $result != false ) ? @sqlite_fetch_all($result, SQLITE_ASSOC) : false;
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
		
		return ( $result != false ) ? sqlite_num_fields($result) : false;
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
		
		return ( $result != false ) ? sqlite_field_name($result, $offset) : false;
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
		
		return ( $result != false ) ? sqlite_seek($result, $row) : false;
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
		return ( $this->connect_id != false ) ? sqlite_last_insert_rowid($this->connect_id) : false;
	}
	
	/**
	 * sql::free_result()
	 * 
	 * Libère le résultat de la mémoire (méthode inutile dans le cas de SQLite)
	 * 
	 * @param resource $result  Ressource de résultat de requète
	 * 
	 * @access public
	 * @return void
	 */
	function free_result($result = false)
	{
		// Nothing
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
		return sqlite_escape_string($str);
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
			$this->transaction(END_TRC);
			
			$result = @sqlite_close($this->connect_id);
			$this->connect_id   = null;
			$this->query_result = null;
			
			return $result;
		}
		else
		{
			return false;
		}
	}
}

}
?>
