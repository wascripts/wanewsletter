<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if( !defined('_INC_CLASS_WADB_MYSQL') ) {

define('_INC_CLASS_WADB_MYSQL', true);

class Wadb_mysql {

	/**
	 * Type de base de données
	 *
	 * @var string
	 * @access private
	 */
	var $engine = 'mysql';
	
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
	function Wadb_mysql($dbname, $options = null)
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
			}
			
			$this->host = $host . (!is_null($port) ? ':'.$port : '');
		}
		
		$connect = 'mysql_connect';
		
		if( is_array($options) ) {
			$this->options = array_merge($this->options, $options);
		}
		
		if( !empty($this->options['persistent']) ) {
			$connect = 'mysql_pconnect';
		}
		
		if( !is_null($port) ) {
			$host .= ':' . $port;
		}
		
		if( !($this->link = $connect($host, $username, $passwd)) ) {
			$this->errno = mysql_errno();
			$this->error = mysql_error();
			$this->link  = null;
			
			throw new SQLException($this->error, $this->errno);
		}
		else if( !mysql_select_db($this->dbname) ) {
			$this->errno = mysql_errno($this->link);
			$this->error = mysql_error($this->link);
			mysql_close($this->link);
			$this->link  = null;
			
			throw new SQLException($this->error, $this->errno);
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
	 * 
	 * @param string $encoding
	 * 
	 * @access public
	 * @return string
	 */
	function encoding($encoding = null)
	{
		$curEncoding = mysql_client_encoding($this->link);
		
		if( !is_null($encoding) ) {
			mysql_set_charset($encoding, $this->link);
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
		$result  = mysql_query($query, $this->link);
		$endtime = array_sum(explode(' ', microtime()));
		
		$this->sqltime += ($endtime - $curtime);
		$this->queries++;
		
		if( !$result ) {
			$this->errno = mysql_errno($this->link);
			$this->error = mysql_error($this->link);
			$this->lastQuery = $query;
			$this->rollBack();
			
			throw new SQLException($this->error, $this->errno);
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
		return mysql_real_escape_string($string, $this->link);
	}
	
	/**
	 * Vérifie l'état de la connexion courante et effectue si besoin une reconnexion
	 * 
	 * @access public
	 * @return boolean
	 */
	function ping()
	{
		return mysql_ping($this->link);
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
	
	/**
	 * Initialise un objet WadbBackup_{self::$engine}
	 *
	 * @access public
	 * @return object
	 */
	function initBackup()
	{
		return new WadbBackup_mysql($this);
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
	 * "Constantes" de la classe
	 */
	var $SQL_FETCH_NUM   = MYSQL_NUM;
	var $SQL_FETCH_ASSOC = MYSQL_ASSOC;
	var $SQL_FETCH_BOTH  = MYSQL_BOTH;
	
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
	function WadbBackup_mysql($db)
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
		$contents  = '-- ' . $this->eol;
		$contents .= "-- $toolname MySQL Dump" . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= "-- Host     : " . $this->db->host . $this->eol;
		$contents .= "-- Server   : " . $this->db->serverVersion . $this->eol;
		$contents .= "-- Database : " . $this->db->dbname . $this->eol;
		$contents .= '-- Date     : ' . date(DATE_RFC2822) . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= $this->eol;
		
		$contents .= sprintf("SET NAMES '%s';%s", $this->db->encoding(), $this->eol);
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
		$result = $this->db->query('SHOW TABLE STATUS FROM ' . $this->db->quote($this->db->dbname));
		$tables = array();
		
		while( $row = $result->fetch() ) {
			$tables[$row['Name']] = $row['Engine'];
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
		$contents .= '-- Structure de la table ' . $tabledata['name'] . ' ' . $this->eol;
		$contents .= '-- ' . $this->eol;
		
		if( $drop_option ) {
			$contents .= 'DROP TABLE IF EXISTS ' . $this->db->quote($tabledata['name']) . ';' . $this->eol;
		}
		
		$result = $this->db->query('SHOW CREATE TABLE ' . $this->db->quote($tabledata['name']));
		$create_table = $result->column('Create Table');
		$result->free();
		
		$contents .= preg_replace("/(\r\n?)|\n/", $this->eol, $create_table) . ';' . $this->eol;
		
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
		$result = $this->db->query($sql);
		
		$result->setFetchMode(MYSQL_ASSOC);
		
		if( $row = $result->fetch() ) {
			$contents  = $this->eol;
			$contents .= '-- ' . $this->eol;
			$contents .= '-- Contenu de la table ' . $tablename . ' ' . $this->eol;
			$contents .= '-- ' . $this->eol;
			
			$fields = array();
			for( $j = 0, $n = mysql_num_fields($result->result); $j < $n; $j++ ) {
				$data = mysql_fetch_field($result->result, $j);
				$fields[] = $this->db->quote($data->name);
			}
			
			$fields = implode(', ', $fields);
			
			do {
				$contents .= 'INSERT INTO ' . $this->db->quote($tablename) . " ($fields) VALUES";
				
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
		$result->free();
		
		return $contents;
	}
}

}
