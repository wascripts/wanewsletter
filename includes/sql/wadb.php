<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

/**
 * Exceptions émises par les classes d'accès aux bases de données
 */
class SQLException extends Exception { }

abstract class Wadb
{
	/**
	 * Type de base de données
	 * @todo Une classe fille peut surcharger une constante déclarée sur sa parente.
	 * Normal ou faille dans l'implémentation PHP ?
	 */
//	const ENGINE = '';

	/**
	 * Connexion à la base de données
	 *
	 * @var resource|object
	 */
	protected $link;

	/**
	 * Informations de connexion
	 *
	 * @var array
	 */
	protected $infos = array();
	/**
	 * Options de connexion
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Code d'erreur SQLSTATE
	 *
	 * @var string
	 */
	protected $sqlstate = '';

	/**
	 * Code d'erreur
	 *
	 * @var integer
	 */
	protected $errno = 0;

	/**
	 * Message d'erreur
	 *
	 * @var string
	 */
	protected $error = '';

	/**
	 * Dernière requète SQL exécutée (en cas d'erreur seulement)
	 *
	 * @var string
	 */
	protected $lastQuery = '';

	/**
	 * Nombre de requètes SQL exécutées depuis le début de la connexion
	 *
	 * @var integer
	 */
	protected $queries = 0;

	/**
	 * Durée totale d'exécution des requètes SQL
	 *
	 * @var integer
	 */
	protected $sqltime = 0;

	/**
	 * Constructeur de classe
	 *
	 * @param array $options Options de connexion/utilisation
	 */
	public function __construct($options = null)
	{
		if (is_array($options)) {
			$this->options = array_merge($this->options, $options);
		}
	}

	/**
	 * Accès en lecture seule de certaines propriétés
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get($name)
	{
		switch ($name) {
			case 'infos':
				$retval = $this->infos;
				break;
			case 'sqlstate':
			case 'errno':
			case 'error':
			case 'lastQuery':
			case 'queries':
			case 'sqltime':
				$retval = $this->{$name};
				break;
			// Compatibilité avec d'anciennes versions
			case 'host':
				$retval = $this->infos['host'];
				break;
			case 'dbname':
				$retval = $this->infos['dbname'];
				break;
			default:
				trigger_error(sprintf("Cannot access unknown property %s::\$%s", __CLASS__, $name), E_USER_WARNING);
				break;
		}

		return $retval;
	}

	/**
	 * Connexion à la base de données
	 *
	 * @param array $infos   Informations de connexion
	 * @param array $options Options de connexion/utilisation
	 *
	 * @throws SQLException En cas d'échec de la connexion
	 */
	abstract public function connect($infos = null, $options = null);

	/**
	 * @return boolean
	 */
	public function isConnected()
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
	 * @return string
	 */
	abstract public function encoding($encoding = null);

	/**
	 * Exécute une requète sur la base de données
	 *
	 * @param string $query
	 *
	 * @throws SQLException En cas d'erreur retournée par la base de données
	 *
	 * @return boolean|WadbResult
	 */
	abstract public function query($query);

	/**
	 * Construit une requète INSERT à partir des diverses données fournies
	 *
	 * @param string $tablename Table sur laquelle effectuer la requète
	 * @param array  $dataset   Tableau des données à insérer ou tableau multi-dimensionnel
	 *                          dans le cas où on veut insérer plusieurs lignes.
	 *                          Le tableau des données a la structure suivante:
	 *                          array(column_name => column_value[, column_name => column_value])
	 *
	 * @return boolean
	 */
	public function insert($tablename, $dataset)
	{
		if (empty($dataset)) {
			trigger_error("Empty data array given", E_USER_WARNING);
			return false;
		}

		// Simpliste, mais si un index 0 existe et contient un tableau, on a à
		// faire à un tableau multi-dimensionnel pour l'insertion de plusieurs
		// lignes consécutives.
		// Si ce n'est pas le cas, on créé un tableau multi-dimensionnel pour
		// traiter correctement dans notre boucle l'unique ligne à insérer.
		if (!isset($dataset[0]) || !is_array($dataset[0])) {
			$dataset = array($dataset);
		}

		$values = array();
		foreach ($dataset as $data) {
			$values[] = '(' . implode(', ', $this->prepareData($data)) . ')';
		}

		return $this->query(sprintf('INSERT INTO %s (%s) VALUES %s',
			$this->quote($tablename),
			implode(', ', array_keys($data)),
			implode(', ', $values)
		));
	}

	/**
	 * Construit une requète UPDATE à partir des diverses données fournies
	 *
	 * @param string $tablename  Table sur laquelle effectuer la requète
	 * @param array  $data       Tableau des données à insérer.
	 *                           Le tableau a la structure suivante:
	 *                           array(column_name => column_value[, column_name => column_value])
	 * @param array  $conditions Conditions pour la clause WHERE
	 *
	 * @return boolean
	 */
	public function update($tablename, $data, $conditions = null)
	{
		$data = $this->prepareData($data);

		$query = sprintf('UPDATE %s SET ', $this->quote($tablename));
		foreach ($data as $field => $value) {
			$query .= sprintf('%s = %s, ', $this->quote($field), $value);
		}

		$query = substr($query, 0, -2);

		if (is_array($conditions) && count($conditions) > 0) {
			$query .= ' WHERE ';
			$conditions = $this->prepareData($conditions);

			foreach ($conditions as $field => $value) {
				$query .= sprintf('%s = %s AND ', $this->quote($field), $value);
			}

			$query = substr($query, 0, -5);
		}

		return $this->query($query);
	}

	/**
	 * Prépare les données du tableau $data en prévision de leur utilisation
	 * dans une requète.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function prepareData($data)
	{
		foreach ($data as &$value) {
			if (is_null($value)) {
				$value = 'NULL';
			}
			else if (is_bool($value)) {
				$value = intval($value);
			}
			else if (!is_int($value) && !is_float($value)) {
				$value = '\'' . $this->escape($value) . '\'';
			}
		}

		return $data;
	}

	/**
	 * Protège un nom de base, de table ou de colonne en prévision de son
	 * utilisation dans une requète
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	abstract public function quote($name);

	/**
	 * @param mixed $tables Nom de table ou tableau de noms de table
	 */
	abstract public function vacuum($tables);

	/**
	 * Démarre le mode transactionnel
	 *
	 * @return boolean
	 */
	abstract public function beginTransaction();

	/**
	 * Envoie une commande COMMIT à la base de données pour validation de la
	 * transaction courante
	 *
	 * @return boolean
	 */
	abstract public function commit();

	/**
	 * Envoie une commande ROLLBACK à la base de données pour annulation de la
	 * transaction courante
	 *
	 * @return boolean
	 */
	abstract public function rollBack();

	/**
	 * Renvoie le nombre de lignes affectées par la dernière requète DML
	 *
	 * @return integer
	 */
	abstract public function affectedRows();

	/**
	 * Retourne l'identifiant généré automatiquement par la dernière requète
	 * INSERT sur la base de données
	 *
	 * @return integer
	 */
	abstract public function lastInsertId();

	/**
	 * Échappe une chaîne en prévision de son insertion dans une requète sur
	 * la base de données
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	abstract public function escape($string);

	/**
	 * Vérifie l'état de la connexion courante et effectue si besoin une reconnexion
	 *
	 * @return boolean
	 */
	abstract public function ping();

	/**
	 * Ferme la connexion à la base de données
	 *
	 * @return boolean
	 */
	abstract public function close();

	/**
	 * Initialise un objet WadbBackup
	 *
	 * @return WadbBackup
	 */
	abstract public function initBackup();

	/**
	 * Destructeur de classe
	 */
	public function __destruct()
	{
		$this->close();
	}
}

abstract class WadbResult
{
	const FETCH_NUM    = 1;
	const FETCH_ASSOC  = 2;
	const FETCH_BOTH   = 3;

	/**
	 * Ressource de résultat de requète
	 *
	 * @var resource|object
	 */
	protected $result;

	/**
	 * Mode de récupération des données
	 *
	 * @var integer
	 */
	protected $fetchMode;

	/**
	 * Constructeur de classe
	 *
	 * @param resource|object $result Ressource de résultat de requète
	 */
	public function __construct($result)
	{
		$this->result = $result;
	}

	/**
	 * Renvoie la ligne suivante dans le jeu de résultat
	 *
	 * @param integer $mode Mode de récupération des données
	 *
	 * @return array
	 */
	abstract public function fetch($mode = null);

	/**
	 * Renvoie sous forme d'objet la ligne suivante dans le jeu de résultat
	 *
	 * @return object
	 */
	abstract public function fetchObject();

	/**
	 * Renvoie un tableau de toutes les lignes du jeu de résultat
	 *
	 * @param integer $mode Mode de récupération des données
	 *
	 * @return array
	 */
	public function fetchAll($mode = null)
	{
		$rowset = array();
		while ($row = $this->fetch($mode)) {
			$rowset[] = $row;
		}

		return $rowset;
	}

	/**
	 * Retourne le contenu de la colonne pour l'index ou le nom donné
	 * à l'index suivant dans le jeu de résultat.
	 *
	 * @param mixed $column Index ou nom de la colonne
	 *
	 * @return string
	 */
	abstract public function column($column);

	/**
	 * Configure le mode de récupération par défaut
	 *
	 * @param integer $mode Mode de récupération des données
	 *
	 * @return boolean
	 */
	final public function setFetchMode($mode)
	{
		if (in_array($mode, array(self::FETCH_NUM, self::FETCH_ASSOC, self::FETCH_BOTH))) {
			$this->fetchMode = $mode;
			return true;
		}
		else {
			trigger_error("Invalid fetch mode", E_USER_WARNING);
			return false;
		}
	}

	/**
	 * Configure le mode de récupération par défaut
	 *
	 * @param array   $modes Liste de modes valides pour la base de données considérée
	 * @param integer $mode  Mode de récupération des données
	 *
	 * @return integer
	 */
	final protected function getFetchMode($modes, $mode)
	{
		if (is_null($mode)) {
			$mode = $this->fetchMode;
		}

		if (is_null($mode) || !isset($modes[$mode])) {
			$mode = self::FETCH_BOTH;
		}

		return $modes[$mode];
	}

	/**
	 * Libère la mémoire allouée
	 */
	abstract public function free();

	/**
	 * Destructeur de classe
	 */
	public function __destruct()
	{
		$this->free();
	}
}

abstract class WadbBackup
{
	/**
	 * Connexion à la base de données
	 *
	 * @var Wadb
	 */
	protected $db = null;

	/**
	 * Fin de ligne
	 *
	 * @var string
	 */
	public $eol = "\n";

	/**
	 * Constructeur de classe
	 *
	 * @param Wadb $db Connexion à la base de données
	 */
	public function __construct(Wadb $db)
	{
		$this->db = $db;
	}

	/**
	 * Génération de l'en-tête du fichier de sauvegarde
	 *
	 * @param string $toolname Nom de l'outil utilisé pour générer la sauvegarde
	 *
	 * @return string
	 */
	abstract public function header($toolname = '');

	/**
	 * Retourne la liste des tables présentes dans la base de données considérée
	 *
	 * @return array
	 */
	abstract public function get_tables();

	/**
	 * Utilisable pour l'ajout de requète supplémentaires (séquences, configurations diverses, etc)
	 *
	 * @param boolean $drop_option
	 *
	 * @return string
	 */
	public function get_other_queries($drop_option)
	{
		return '';
	}

	/**
	 * Retourne la structure d'une table de la base de données sous forme de requète SQL de type DDL
	 *
	 * @param array   $tabledata   Informations sur la table (provenant de self::get_tables())
	 * @param boolean $drop_option Ajouter une requète de suppression conditionnelle de table
	 *
	 * @return string
	 */
	abstract public function get_table_structure($tabledata, $drop_option);

	/**
	 * Retourne les données d'une table de la base de données sous forme de requètes SQL de type DML
	 *
	 * @param string $tablename Nom de la table à considérer
	 *
	 * @return string
	 */
	public function get_table_data($tablename)
	{
		$contents = '';

		$result = $this->db->query('SELECT * FROM ' . $this->db->quote($tablename));
		$result->setFetchMode(WadbResult::FETCH_ASSOC);

		if ($row = $result->fetch()) {
			$contents  = $this->eol;
			$contents .= '-- ' . $this->eol;
			$contents .= '-- Contenu de la table ' . $tablename . $this->eol;
			$contents .= '-- ' . $this->eol;

			$fields = array_map(array($this->db, 'quote'), array_keys($row));
			$fields = implode(', ', $fields);

			do {
				$contents .= sprintf("INSERT INTO %s (%s) VALUES", $this->db->quote($tablename), $fields);

				foreach ($row as $key => $value) {
					if (is_null($value)) {
						$row[$key] = 'NULL';
					}
					else {
						$row[$key] = '\'' . addcslashes($this->db->escape($value), "\r\n") . '\'';
					}
				}

				$contents .= '(' . implode(', ', $row) . ');' . $this->eol;
			}
			while ($row = $result->fetch());
		}
		$result->free();

		return $contents;
	}
}

