<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter\Dblayer;

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
	abstract public function getTablesList();

	/**
	 * Retourne la structure d'une table de la base de données sous forme de requète SQL de type DDL
	 *
	 * @param string  $tablename   Nom de la table
	 * @param boolean $drop_option Ajouter une requète de suppression conditionnelle de table
	 *
	 * @return string
	 */
	abstract public function getStructure($tablename, $drop_option);

	/**
	 * Retourne les données d'une table de la base de données sous forme de requètes SQL de type DML
	 *
	 * @param string $tablename Nom de la table
	 *
	 * @return string
	 */
	public function getData($tablename)
	{
		$contents = '';

		$result = $this->db->query('SELECT * FROM ' . $this->db->quote($tablename));
		$result->setFetchMode(WadbResult::FETCH_ASSOC);

		if ($row = $result->fetch()) {
			$contents  = $this->eol;
			$contents .= '-- ' . $this->eol;
			$contents .= '-- Contenu de la table ' . $tablename . $this->eol;
			$contents .= '-- ' . $this->eol;

			$fields = array_map([$this->db, 'quote'], array_keys($row));
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
