<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@webnaute.net>
 * @link      http://dev.webnaute.net/wanewsletter/
 * @copyright 2002-2021 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter\Dblayer;

class SqliteBackup extends WadbBackup
{
	public function header($toolname = '')
	{
		if (!($hostname = gethostname())) {
			$hostname = (!empty($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : 'Unknown Host';
		}

		$contents  = '-- ' . $this->eol;
		$contents .= "-- $toolname SQLite Dump" . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= "-- Host       : " . $hostname . $this->eol;
		$contents .= "-- SQLite lib : " . $this->db->libVersion . $this->eol;
		$contents .= "-- Database   : " . basename($this->db->dbname) . $this->eol;
		$contents .= '-- Date       : ' . date(DATE_RFC2822) . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= $this->eol;

		return $contents;
	}

	public function getTablesList()
	{
		$result = $this->db->query("SELECT tbl_name FROM sqlite_master WHERE type = 'table'");
		$tables = [];

		while ($row = $result->fetch()) {
			$tables[] = $row['tbl_name'];
		}

		return $tables;
	}

	public function getStructure($tablename, $drop_option)
	{
		$contents  = '-- ' . $this->eol;
		$contents .= '-- Structure de la table ' . $tablename . ' ' . $this->eol;
		$contents .= '-- ' . $this->eol;

		if ($drop_option) {
			$contents .= 'DROP TABLE IF EXISTS ' . $this->db->quote($tablename) . ';' . $this->eol;
		}

		$sql = "SELECT sql, type
			FROM sqlite_master
			WHERE tbl_name = '%s'
				AND sql IS NOT NULL";
		$sql = sprintf($sql, $this->db->escape($tablename));
		$result = $this->db->query($sql);

		$indexes = '';
		while ($row = $result->fetch()) {
			if ($row['type'] == 'table') {
				$create_table = str_replace(',', ',' . $this->eol, $row['sql']) . ';' . $this->eol;
			}
			else {
				$indexes .= $row['sql'] . ';' . $this->eol;
			}
		}

		$contents .= $create_table . $indexes;

		return $contents;
	}
}
