<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

/**
 * Class Attach
 *
 * Gestion des fichiers joints des newsletters
 */
class Attach
{
	/**
	 * Chemin vers le dossier de stockage des fichiers
	 *
	 * @var string
	 */
	private $upload_path;

	/**
	 * Initialisation des variables de la classe
	 */
	public function __construct()
	{
		global $nl_config;

		$this->upload_path = WA_ROOTDIR . '/' . $nl_config['upload_path'];
	}

	/**
	 * Effectue les vérifications nécessaires et ajoute une entrée dans
	 * les tables de gestion des fichiers joints.
	 *
	 * Le fichier peut être uploadé via le formulaire adéquat, être sur
	 * un serveur distant, ou avoir été uploadé manuellement sur le serveur.
	 *
	 * @param integer $log_id Identifiant du log
	 * @param mixed   $file   Nom ou URL du fichier, ou tableau d’un fichier uploadé
	 *
	 * @throws Exception
	 */
	public function addFile($log_id, $file)
	{
		global $db, $lang, $nl_config, $listdata;

		if (!is_array($file)) {
			$url = parse_url($file);

			if (isset($url['scheme'])) {
				if (!preg_match('#^https?$#', $url['scheme'])) {
					throw new Exception($lang['Message']['Invalid_url']);
				}

				if (!isset($url['path']) || substr($url['path'], -1) == '/') {
					$filename = 'index';
				}
				else {
					$filename = basename($url['path']);
				}

				$mode = 'remote';
				$url  = $file;
			}
			else {
				$mode = 'local';
				$filename = $file;
			}
		}
		else {
			$mode = 'upload';
			$filename = $file['name'];
		}

		//
		// Vérification de l’accès en écriture au répertoire de stockage
		//
		if ($mode != 'local' && !is_writable($this->upload_path)) {
			throw new Exception($lang['Message']['Uploaddir_not_writable']);
		}

		//
		// Vérification de l’extension du fichier
		//
		if (!($extension = pathinfo($filename, PATHINFO_EXTENSION))) {
			$extension = 'x-wa';
		}

		$sql = sprintf("SELECT COUNT(fe_id) AS test_extension
			FROM %s
			WHERE LOWER(fe_ext) = '%s' AND liste_id = %d",
			FORBIDDEN_EXT_TABLE,
			$db->escape(strtolower($extension)),
			$listdata['liste_id']
		);
		$result = $db->query($sql);

		if ($result->column('test_extension') > 0) {
			throw new Exception($lang['Message']['Invalid_ext']);
		}

		//
		// Vérification de la validité du nom du fichier
		//
		if (preg_match('/[\\:*\/?<">|\x00-\x1F\x7F-\x9F]/', $filename)) {
			throw new Exception($lang['Message']['Invalid_filename']);
		}

		if ($mode == 'upload') {
			// Si l’upload a échoué, on récupère le message correspondant à l’erreur survenue
			if ($file['error'] != UPLOAD_ERR_OK) {
				$errstr = $lang['Message']['Upload_error_5'];
				if (isset($lang['Message']['Upload_error_'.$file['error']])) {
					$errstr = $lang['Message']['Upload_error_'.$file['error']];
				}

				throw new Exception($errstr);
			}

			$filesize = $file['size'];
			$filetype = $file['type'];
			$tmp_filename = $file['tmp_name'];
		}
		else if ($mode == 'remote') {
			$tmp_path = (ini_get('open_basedir')) ? WA_TMPDIR : sys_get_temp_dir();
			$tmp_filename = tempnam($tmp_path, 'wa0');

			if (!($fw = fopen($tmp_filename, 'wb'))) {
				throw new Exception($lang['Message']['Upload_error_5']);
			}

			try {
				$result = http_get_contents($url);
			}
			catch (Exception $e) {
				fclose($fw);
				unlink($tmp_filename);
				throw $e;
			}

			fwrite($fw, $result['data']);
			fclose($fw);
			$filesize = strlen($result['data']);
			$filetype = $result['mime'];
		}
		else if ($mode == 'local') {
			if (!file_exists($this->upload_path . $filename)) {
				throw new Exception(sprintf($lang['Message']['File_not_exists'], $filename));
			}

			$filesize = filesize($this->upload_path . $filename);
			$filetype = \Wamailer\Mime::getType($this->upload_path . $filename);
			$tmp_filename = $file;
		}

		if (!$this->checkFileSize($log_id, $filesize, $total_size)) {
			if ($mode == 'remote') {
				// Suppression du fichier temporaire créé par nos soins
				unlink($tmp_filename);
			}

			throw new Exception(sprintf($lang['Message']['weight_too_big'],
				formateSize($nl_config['max_filesize'] - $total_size)
			));
		}

		//
		// Si fichier uploadé ou fichier distant, on déplace le fichier à son emplacement final
		//
		if ($mode != 'local') {
			while (true) {
				$physical_filename = md5($log_id . $filename . microtime()) . '.dl';

				if (!file_exists($this->upload_path . $physical_filename)) {
					break;
				}
			}

			if ($mode == 'remote') {
				$result = copy($tmp_filename, $this->upload_path . $physical_filename);

				// Suppression du fichier temporaire créé par nos soins
				unlink($tmp_filename);
			}
			else {
				$result = move_uploaded_file($tmp_filename, $this->upload_path . $physical_filename);

				if ($result) {
					$filetype = \Wamailer\Mime::getType($this->upload_path . $physical_filename);
				}
			}

			if (!$result) {
				throw new Exception($lang['Message']['Upload_error_5']);
			}
		}
		else {
			$physical_filename = $filename;
		}

		//
		// Tout s’est bien passé, on entre les nouvelles données dans la base de données
		//
		$db->beginTransaction();

		$sql_data = [
			'file_real_name'     => $filename,
			'file_physical_name' => $physical_filename,
			'file_size'          => $filesize,
			'file_mimetype'      => $filetype
		];

		$db->insert(JOINED_FILES_TABLE, $sql_data);

		$sql_data = [
			'log_id'  => $log_id,
			'file_id' => $db->lastInsertId()
		];
		$db->insert(LOG_FILES_TABLE, $sql_data);

		$db->commit();
	}

	/**
	 * Ajoute une entrée pour le log courant avec l'identifiant d'un fichier existant
	 *
	 * @param integer $log_id  Identifiant du log
	 * @param integer $file_id Identifiant du fichier
	 *
	 * @throws Exception
	 */
	public function useFile($log_id, $file_id)
	{
		global $db, $nl_config, $lang, $listdata;

		$sql = "SELECT jf.file_physical_name, jf.file_size
			FROM " . JOINED_FILES_TABLE . " AS jf
				INNER JOIN " . LOG_TABLE . " AS l ON l.liste_id = $listdata[liste_id]
				INNER JOIN " . LOG_FILES_TABLE . " AS lf ON lf.file_id = jf.file_id
					AND lf.log_id = l.log_id
			WHERE jf.file_id = " . $file_id;
		$result = $db->query($sql);

		if (!($row = $result->fetch())) {
			throw new Exception("Invalid File ID");
		}

		if (!file_exists($this->upload_path . $row['file_physical_name'])) {
			throw new Exception(sprintf($lang['Message']['File_not_exists'], $row['file_physical_name']));
		}

		if (!$this->checkFileSize($log_id, $row['file_size'], $total_size)) {
			throw new Exception(sprintf($lang['Message']['weight_too_big'],
				formateSize($nl_config['max_filesize'] - $total_size)
			));
		}

		$sql = sprintf("INSERT INTO %s (log_id, file_id) VALUES (%d, %d)",
			LOG_FILES_TABLE,
			$log_id,
			$file_id
		);
		$db->query($sql);
	}

	/**
	 * Vérification de la taille du fichier par rapport à la taille du log et la taille maximale
	 *
	 * @param integer $log_id     Identifiant du log
	 * @param integer $filesize   Taille du fichier
	 * @param integer $total_size Taille totale du log
	 *
	 * @return boolean
	 */
	private function checkFileSize($log_id, $filesize, &$total_size)
	{
		global $db, $nl_config;

		$sql = "SELECT SUM(jf.file_size) AS total_size
			FROM " . JOINED_FILES_TABLE . " AS jf
				INNER JOIN " . LOG_FILES_TABLE . " AS lf ON lf.file_id = jf.file_id
					AND lf.log_id = " . $log_id;
		$result = $db->query($sql);
		$total_size = $result->column('total_size');

		return (($total_size + $filesize) <= $nl_config['max_filesize']);
	}

	/**
	 * Récupère les infos sur le fichier joint
	 *
	 * @param mixed $file Peut être l’identifiant ou le nom du fichier joint
	 *
	 * @return boolean|array
	 */
	public function getFile($file)
	{
		global $db, $listdata;

		if (!is_numeric($file)) {
			$sql_where = 'jf.file_real_name = \'' . $db->escape($file) . '\'';
		}
		else {
			$sql_where = 'jf.file_id = ' . intval($file);
		}

		$sql = "SELECT jf.file_real_name, jf.file_physical_name, jf.file_size, jf.file_mimetype
			FROM " . JOINED_FILES_TABLE . " AS jf
				INNER JOIN " . LOG_TABLE . " AS l ON l.liste_id = $listdata[liste_id]
				INNER JOIN " . LOG_FILES_TABLE . " AS lf ON lf.file_id = jf.file_id
					AND lf.log_id = l.log_id
			WHERE " . $sql_where;
		$result = $db->query($sql);

		if ($row = $result->fetch()) {
			$file = [];
			$file['name'] = $row['file_real_name'];
			$file['path'] = $this->upload_path . $row['file_physical_name'];
			$file['size'] = $row['file_size'];
			$file['type'] = $row['file_mimetype'];

			return $file;
		}

		return false;
	}

	/**
	 * Suppression des fichiers joints aux logs concernés.
	 *
	 * @param mixed $log_ids  id ou tableau des id des logs concernés
	 * @param mixed $file_ids id ou tableau des id des fichiers joints concernés
	 *                        (par défaut, tous les fichiers liés aux logs
	 *                        spécifiés sont supprimés)
	 */
	public function deleteFiles($log_ids, $file_ids = [])
	{
		global $db;

		$log_ids  = (array) $log_ids;
		$file_ids = (array) $file_ids;

		if (count($log_ids) == 0) {
			return null;
		}

		$db->beginTransaction();

		$sql = "DELETE FROM " . LOG_FILES_TABLE . "
			WHERE log_id IN(" . implode(', ', $log_ids) . ")";
		if (count($file_ids) > 0) {
			$sql .= " AND file_id IN(" . implode(', ', $file_ids) . ")";
		}

		$db->query($sql);

		$sql = "SELECT jf.file_id, jf.file_physical_name
			FROM " . JOINED_FILES_TABLE . " AS jf
			LEFT JOIN " . LOG_FILES_TABLE . " AS lf ON lf.file_id = jf.file_id
			WHERE lf.log_id IS NULL";
		$result = $db->query($sql);

		if ($row = $result->fetch()) {
			$file_ids = [];
			do {
				$file_ids[] = $row['file_id'];

				if (file_exists($this->upload_path . $row['file_physical_name'])) {
					unlink($this->upload_path . $row['file_physical_name']);
				}
			}
			while ($row = $result->fetch());

			$sql = "DELETE FROM " . JOINED_FILES_TABLE . "
				WHERE file_id IN(" . implode(', ', $file_ids) . ")";
			$db->query($sql);
		}

		$db->commit();
		$db->vacuum([LOG_FILES_TABLE, JOINED_FILES_TABLE]);
	}
}
