<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if (!defined('CLASS_ATTACH_INC')) {

define('CLASS_ATTACH_INC', true);

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
	private $upload_path = '';

	/**
	 * Utilisation ou non de l'option ftp
	 *
	 * @var boolean
	 */
	private $use_ftp     = false;

	/**
	 * Chemin vers le dossier de stockage des fichiers sur le ftp
	 *
	 * @var string
	 */
	private $ftp_path    = '';

	/**
	 * Identifiant de ressource au serveur ftp
	 *
	 * @var resource
	 */
	private $connect_id  = null;

	/**
	 * Initialisation des variables de la classe
	 * Initialisation de la connexion au serveur ftp le cas échéant
	 */
	public function __construct()
	{
		global $nl_config;

		$this->upload_path = WA_ROOTDIR . '/' . $nl_config['upload_path'];
		$this->use_ftp     = $nl_config['use_ftp'];

		if ($this->use_ftp) {
			$result = $this->connect_to_ftp(
				$nl_config['ftp_server'],
				$nl_config['ftp_port'],
				$nl_config['ftp_user'],
				$nl_config['ftp_pass'],
				$nl_config['ftp_pasv'],
				$nl_config['ftp_path']
			);

			if ($result['error']) {
				trigger_error($result['message'], E_USER_ERROR);
			}

			$this->connect_id = $result['connect_id'];
			$this->ftp_path   = $nl_config['ftp_path'];
		}
	}

	/**
	 * Fonction de connexion au serveur ftp
	 * La fonction a été affranchi de façon à être utilisable sans créer
	 * une instance de la classe. (pour tester la connexion dans la config. générale)
	 *
	 * @param string  $ftp_server Nom du serveur ftp
	 * @param integer $ftp_port   Port de connexion
	 * @param string  $ftp_user   Nom d'utilisateur si besoin
	 * @param string  $ftp_pass   Mot de passe si besoin
	 * @param integer $ftp_pasv   Mode actif ou passif
	 * @param string  $ftp_path   Chemin vers le dossier des fichiers joints
	 *
	 * @return array
	 */
	public static function connect_to_ftp($ftp_server, $ftp_port, $ftp_user, $ftp_pass, $ftp_pasv, $ftp_path)
	{
		if (!($connect_id = @ftp_connect($ftp_server, $ftp_port))) {
			return array('error' => true, 'message' => 'Ftp_unable_connect');
		}

		if ($ftp_user != '' && $ftp_pass != '') {
			if (!@ftp_login($connect_id, $ftp_user, $ftp_pass)) {
				return array('error' => true, 'message' => 'Ftp_error_login');
			}
		}

		if (!@ftp_pasv($connect_id, $ftp_pasv)) {
			return array('error' => true, 'message' => 'Ftp_error_mode');
		}

		if (!@ftp_chdir($connect_id, $ftp_path)) {
			return array('error' => true, 'message' => 'Ftp_error_path');
		}

		return array('error' => false, 'connect_id' => $connect_id);
	}

	/**
	 * Verifie la présence du fichier demandé dans le dossier des fichier joints ou sur le ftp
	 *
	 * @param string  $filename  Nom du fichier
	 * @param boolean $error     True si une erreur s'est produite
	 * @param array   $msg_error Tableau des erreurs
	 *
	 * @return integer
	 */
	public function joined_file_exists($filename, &$error, &$msg_error)
	{
		global $lang;

		$file_exists = false;
		$filesize    = 0;

		if ($this->use_ftp) {
			$listing = @ftp_rawlist($this->connect_id, $this->ftp_path);

			if (is_array($listing) && count($listing)) {
				//
				// On vérifie chaque entrée du listing pour retrouver le fichier spécifié
				//
				foreach ($listing as $line_info) {
					if (preg_match('/^\s*([d-])[rwxst-]{9} .+ ([0-9]*) [a-zA-Z]+ [0-9:\s]+ (.+)$/i', $line_info, $m)) {
						if ($m[1] != 'd' && $m[3] == $filename) {
							$file_exists = true;
							$filesize    = $m[2];

							break;
						}
					}
				}
			}
		}
		else if (file_exists(wa_realpath($this->upload_path . $filename))) {
			$file_exists = true;
			$filesize    = filesize(wa_realpath($this->upload_path . $filename));
		}

		if (!$file_exists) {
			$error = true;
			$msg_error[] = sprintf($lang['Message']['File_not_exists'], '');
		}

		return $filesize;
	}

	/**
	 * Génération d'un nom de fichier unique
	 * Fonction récursive
	 *
	 * @param string $prev_filename Nom du fichier temporaire précédemment généré et refusé
	 *
	 * @return string
	 */
	public function make_filename($prev_filename = '')
	{
		global $db;

		$physical_filename = md5(microtime()) . '.dl';

		if ($physical_filename != $prev_filename) {
			$sql = "SELECT COUNT(file_id) AS test_name
				FROM " . JOINED_FILES_TABLE . "
				WHERE file_physical_name = '" . $db->escape($physical_filename) . "'";
			$result = $db->query($sql);

			$test_name = $result->column('test_name');
		}
		else {
			$test_name = true;
		}

		return ($test_name) ? $this->make_filename($physical_filename) : $physical_filename;
	}

	/**
	 * Effectue les vérifications nécessaires et ajoute une entrée dans les tables de
	 * gestion des fichiers joints
	 *
	 * Le fichier peut être uploadé via le formulaire adéquat, être sur un serveur distant,
	 * ou avoir été uploadé manuellement sur le serveur
	 *
	 * @param string  $upload_mode  Mode d'upload du fichier (upload http, à distance, fichier local)
	 * @param integer $log_id       Identifiant du log
	 * @param string  $filename     Nom du fichier
	 * @param string  $tmp_filename Nom temporaire du fichier/nom du fichier local/url du fichier distant
	 * @param integer $filesize     Taille du fichier
	 * @param string  $filetype     Type mime du fichier
	 * @param string  $errno_code   Code erreur éventuel de l'upload http
	 * @param boolean $error        True si une erreur survient
	 * @param array   $msg_error    Tableau des messages d'erreur
	 */
	public function upload_file($upload_mode, $log_id, $filename, $tmp_filename, $filesize, $filetype, $errno_code, &$error, &$msg_error)
	{
		global $db, $lang, $nl_config;

		$extension = substr($filename, (strrpos($filename, '.') + 1));

		if ($extension == '') {
			$extension = 'x-wa';
		}

		//
		// Vérification de l'accès en écriture au répertoire de stockage
		//
		if ($upload_mode != 'local' && !$this->use_ftp && !is_writable($this->upload_path)) {
			$error = true;
			$msg_error[] = $lang['Message']['Uploaddir_not_writable'];
			return;
		}

		//
		// Vérification de la validité du nom du fichier
		//
		if (!$this->check_filename($filename)) {
			$error = true;
			$msg_error[] = $lang['Message']['Invalid_filename'];
		}

		//
		// Vérification de l'extension du fichier
		//
		if (!$this->check_extension($extension)) {
			$error = true;
			$msg_error[] = $lang['Message']['Invalid_ext'];
		}

		if (!$error) {
			//
			// Si l'upload a échoué, on récupère le message correspondant à l'erreur survenue
			//
			if ($upload_mode == 'upload' && $errno_code != UPLOAD_ERR_OK) {
				$error = true;

				if (isset($lang['Message']['Upload_error_'.$errno_code])) {
					$msg_error[] = $lang['Message']['Upload_error_'.$errno_code];
				}
				else {
					$msg_error[] = $lang['Message']['Upload_error_5'];
				}

				return;
			}

			//
			// Récupération d'un fichier distant
			//
			else if ($upload_mode == 'remote') {
				$URL  = $tmp_filename;
				$part = @parse_url($URL);

				if (!is_array($part) || !isset($part['scheme']) || (
					$part['scheme'] != 'http' && (
						$part['scheme'] != 'ftp' || !extension_loaded('ftp')
					)
				)) {
					$error = true;
					$msg_error[] = $lang['Message']['Invalid_url'];

					return;
				}

				$tmp_path = (config_value('open_basedir')) ? WA_TMPDIR : sys_get_temp_dir();
				$tmp_filename = tempnam($tmp_path, 'wa0');

				if (!($fw = @fopen($tmp_filename, 'wb'))) {
					$error = true;
					$msg_error[] = $lang['Message']['Upload_error_5'];

					return;
				}

				if ($part['scheme'] == 'http') {
					$result = http_get_contents($URL, $errstr);

					if (!$result) {
						$error = true;
						$msg_error[] = $errstr;

						return;
					}

					fwrite($fw, $result['data']);
					$filesize = strlen($result['data']);
					$filetype = $result['type'];
				}
				else {
					if (!isset($part['user'])) {
						$part['user'] = 'anonymous';
					}
					if (!isset($part['pass'])) {
						$part['pass'] = 'anonymous';
					}

					$port = (!isset($part['port'])) ? 21 : $part['port'];

					if (!($cid = @ftp_connect($part['host'], $port)) ||
						!@ftp_login($cid, $part['user'], $part['pass'])
					) {
						$error = true;
						$msg_error[] = sprintf(
							$lang['Message']['Unaccess_host'],
							wan_htmlspecialchars($part['host'])
						);

						return;
					}

					$path  = (!isset($part['path'])) ? '/' : $part['path'];
					$path .= (!isset($part['query'])) ? '' : '?'.$part['query'];

					$filesize = ftp_size($cid, $path);

					if (!ftp_fget($cid, $fw, $path, FTP_BINARY)) {
						$error = true;
						$msg_error[] = $lang['Message']['Not_found_at_url'];

						return;
					}
					ftp_close($cid);

					require WAMAILER_DIR . '/class.mailer.php';

					$filetype = Mailer::mime_type(substr($filename, (strrpos($filename, '.') + 1)));
				}

				fclose($fw);
			}

			//
			// Fichier uploadé manuellement sur le serveur
			//
			else if ($upload_mode == 'local') {
				require WAMAILER_DIR . '/class.mailer.php';

				$filetype = Mailer::mime_type($extension);

				//
				// On verifie si le fichier est bien présent sur le serveur
				//
				$filesize = $this->joined_file_exists($tmp_filename, $error, $msg_error);
			}
		}
		else {
			return;
		}

		//
		// Vérification de la taille du fichier par rapport à la taille maximale autorisée
		//
		if (!$this->check_maxsize($log_id, $filesize, $total_size)) {
			$error = true;
			$msg_error[] = sprintf($lang['Message']['weight_too_big'],
				formateSize($nl_config['max_filesize'] - $total_size));
		}

		//
		// Si fichier uploadé ou fichier distant, on déplace le fichier à son emplacement final
		//
		if (!$error && $upload_mode != 'local') {
			$physical_filename = $this->make_filename();

			if ($this->use_ftp) {
				$mode = $this->get_mode($filetype);

				if (!@ftp_put($this->connect_id, $physical_filename, $tmp_filename, $mode)) {
					$error = true;
					$msg_error[] = $lang['Message']['Ftp_error_put'];
				}
				else {
					@ftp_site($this->connect_id, 'CHMOD 0644 ' . $physical_filename);
				}
			}
			else {
				if ($upload_mode == 'remote') {
					$result_upload = @copy($tmp_filename, $this->upload_path . $physical_filename);
				}
				else {
					$result_upload = @move_uploaded_file($tmp_filename, $this->upload_path . $physical_filename);
				}

				if (!$result_upload) {
					$error = true;
					$msg_error[] = $lang['Message']['Upload_error_5'];
				}

				if (!$error) {
					@chmod($this->upload_path . $physical_filename, 0644);
				}
			}

			//
			// Suppression du fichier temporaire créé par nos soins
			//
			$this->remove_file($tmp_filename);
		}

		if (!$error) {
			//
			// Tout s'est bien passé, on entre les nouvelles données dans la base de données
			//
			$db->beginTransaction();

			$filedata = array(
				'file_real_name'     => $filename,
				'file_physical_name' => ($upload_mode == 'local') ? $tmp_filename : $physical_filename,
				'file_size'          => $filesize,
				'file_mimetype'      => $filetype
			);

			$db->insert(JOINED_FILES_TABLE, $filedata);

			$data = array(
				'log_id'  => $log_id,
				'file_id' => $db->lastInsertId()
			);
			$db->insert(LOG_FILES_TABLE, $data);

			$db->commit();
		}

		$this->quit();
	}

	/**
	 * Ajoute une entrée pour le log courant avec l'identifiant d'un fichier existant
	 *
	 * @param integer $file_id   Identifiant du fichier
	 * @param integer $log_id    Identifiant du log
	 * @param boolean $error     True si erreur
	 * @param array	  $msg_error Tableau des messages d'erreur
	 */
	public function use_file_exists($file_id, $log_id, &$error, &$msg_error)
	{
		global $db, $nl_config, $lang, $listdata;

		$sql = "SELECT jf.file_physical_name
			FROM " . JOINED_FILES_TABLE . " AS jf
				INNER JOIN " . LOG_TABLE . " AS l ON l.liste_id = $listdata[liste_id]
				INNER JOIN " . LOG_FILES_TABLE . " AS lf ON lf.file_id = jf.file_id
					AND lf.log_id = l.log_id
			WHERE jf.file_id = " . $file_id;
		$result = $db->query($sql);

		$physical_name = $result->column('file_physical_name');

		if (!$physical_name) {
			$error = true;
			$msg_error[] = sprintf($lang['Message']['File_not_exists'], '');
		}

		if (!$error) {
			//
			// On verifie si le fichier est bien présent sur le serveur
			//
			$filesize = $this->joined_file_exists($physical_name, $error, $msg_error);
		}

		if (!$error && !$this->check_maxsize($log_id, $filesize, $total_size)) {
			$error = true;
			$msg_error[] = sprintf($lang['Message']['weight_too_big'],
				formateSize($nl_config['max_filesize'] - $total_size));
		}

		//
		// Insertion des données
		//
		if (!$error) {
			$sql = "INSERT INTO " . LOG_FILES_TABLE . " (log_id, file_id)
				VALUES($log_id, $file_id)";
			$db->query($sql);
		}

		$this->quit();
	}

	/**
	 * Vérification de la validité du nom de fichier
	 *
	 * @param string $filename
	 *
	 * @return boolean
	 */
	public function check_filename($filename)
	{
		return !preg_match('/[\\:*\/?<">|\x00-\x1F\x7F-\x9F]/', $filename);
	}

	/**
	 * Vérification de la validité de l'extension du fichier
	 *
	 * @param string $extension
	 *
	 * @return integer
	 */
	public function check_extension($extension)
	{
		global $db, $listdata;

		$sql = "SELECT COUNT(fe_id) AS test_extension
			FROM " . FORBIDDEN_EXT_TABLE . "
			WHERE LOWER(fe_ext) = '" . $db->escape(strtolower($extension)) . "'
				AND liste_id = " . $listdata['liste_id'];
		$result = $db->query($sql);

		return ($result->column('test_extension') == 0);
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
	private function check_maxsize($log_id, $filesize, &$total_size)
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
	 * Récupère les infos sur le fichier joint à télécharger (envoyer au client)
	 *
	 * @param integer $file_id Identifiant du fichier joint
	 */
	public function download_file($file_id)
	{
		global $db, $listdata, $lang, $output;

		$sql = "SELECT jf.file_real_name, jf.file_physical_name, jf.file_size, jf.file_mimetype
			FROM " . JOINED_FILES_TABLE . " AS jf
				INNER JOIN " . LOG_TABLE . " AS l ON l.liste_id = $listdata[liste_id]
				INNER JOIN " . LOG_FILES_TABLE . " AS lf ON lf.file_id = jf.file_id
					AND lf.log_id = l.log_id
			WHERE jf.file_id = " . $file_id;
		$result = $db->query($sql);

		if ($row = $result->fetch()) {
			if ($this->use_ftp) {
				$tmp_filename = $this->ftp_to_tmp($row);
			}
			else {
				$tmp_filename = wa_realpath($this->upload_path . $row['file_physical_name']);
			}

			if (!($fp = @fopen($tmp_filename, 'rb'))) {
				trigger_error('Impossible de récupérer le contenu du fichier (fichier non accessible en lecture)', E_USER_ERROR);
			}

			$data = fread($fp, filesize($tmp_filename));
			fclose($fp);

			if ($this->use_ftp) {
				$this->remove_file($tmp_filename);
			}

			$this->quit();
			$this->send_file($row['file_real_name'], $row['file_mimetype'], $data, $row['file_size']);
		}

		$output->displayMessage(sprintf($lang['Message']['File_not_exists'], ''));
	}

	/**
	 * Déplacement du fichier demandé du serveur ftp vers le dossier temporaire
	 * Retourne le nom du fichier temporaire
	 *
	 * @param array $data Données du fichier joint
	 *
	 * @return string
	 */
	public function ftp_to_tmp($data)
	{
		$mode         = $this->get_mode($data['file_mimetype']);
		$tmp_path     = (config_value('open_basedir')) ? WA_TMPDIR : sys_get_temp_dir();
		$tmp_filename = tempnam($tmp_path, 'wa1');

		if (!@ftp_get($this->connect_id, $tmp_filename, $data['file_physical_name'], $mode)) {
			trigger_error('Ftp_error_get', E_USER_ERROR);
		}

		return $tmp_filename;
	}

	/**
	 * Mode à utiliser pour le ftp, ascii ou binaire
	 *
	 * @param string $mime_type Type mime du fichier concerné
	 *
	 * @return integer
	 */
	private function get_mode($mime_type)
	{
		return (preg_match('/text|html|xml/i', $mime_type)) ? FTP_ASCII : FTP_BINARY;
	}

	/**
	 * Fonction de suppression de fichiers joints
	 * Retourne le nombre des fichiers supprimés, en cas de succés
	 *
	 * @param boolean $massive_delete Si true, suppression des fichiers joints du ou des logs concernés
	 * @param mixed   $log_id_ary     id ou tableau des id des logs concernés
	 * @param mixed   $file_id_ary    id ou tableau des id des fichiers joints concernés (si $massive_delete à false)
	 *
	 * @return mixed
	 */
	public function delete_joined_files($massive_delete, $log_ids, $file_ids = array())
	{
		global $db;

		if (!is_array($log_ids)) {
			$log_ids = array($log_ids);
		}

		if (!is_array($file_ids)) {
			$file_ids = array($file_ids);
		}

		if (count($log_ids) > 0) {
			if ($massive_delete) {
				$sql = "SELECT file_id
					FROM " . LOG_FILES_TABLE . "
					WHERE log_id IN(" . implode(', ', $log_ids) . ")
					GROUP BY file_id";
				$result = $db->query($sql);

				$file_ids = array();
				while ($file_id = $result->column('file_id')) {
					$file_ids[] = $file_id;
				}
			}

			if (count($file_ids) > 0) {
				$filename_ary = array();

				$sql = "SELECT lf.file_id, jf.file_physical_name
					FROM " . LOG_FILES_TABLE . " AS lf
						INNER JOIN " . JOINED_FILES_TABLE . " AS jf ON jf.file_id = lf.file_id
					WHERE lf.file_id IN(" . implode(', ', $file_ids) . ")
					GROUP BY lf.file_id, jf.file_physical_name
					HAVING COUNT(lf.file_id) = 1";
				$result = $db->query($sql);

				$ids = array();
				while ($row = $result->fetch()) {
					$ids[]          = $row['file_id'];
					$filename_ary[] = $row['file_physical_name'];
				}

				if (count($ids) > 0) {
					$sql = "DELETE FROM " . JOINED_FILES_TABLE . "
						WHERE file_id IN(" . implode(', ', $ids) . ")";
					$db->query($sql);
				}

				$sql = "DELETE FROM " . LOG_FILES_TABLE . "
					WHERE log_id IN(" . implode(', ', $log_ids) . ")
						AND file_id IN(" . implode(', ', $file_ids) . ")";
				$db->query($sql);

				//
				// Suppression physique des fichiers joints devenus inutiles
				//
				foreach ($filename_ary as $filename) {
					if ($this->use_ftp) {
						if (!@ftp_delete($this->connect_id, $filename)) {
							trigger_error('Ftp_error_del', E_USER_ERROR);
						}
					}
					else {
						$this->remove_file(wa_realpath($this->upload_path . $filename));
					}
				}

				return count($filename_ary);
			}// end count file_id_ary
		}// end count log_id_ary

		return false;
	}

	/**
	 * Suppression d'un fichier du serveur
	 *
	 * @param string $filename Nom du fichier sur le serveur
	 */
	public static function remove_file($filename)
	{
		if (file_exists($filename)) {
			unlink($filename);
		}
	}

	/**
	 * Fonction d'envois des entêtes nécessaires au téléchargement et
	 * des données du fichier à télécharger
	 *
	 * @param string $filename  Nom réel du fichier
	 * @param string $mime_type Mime type du fichier
	 * @param string $filedata  Contenu du fichier
	 */
	public static function send_file($filename, $mime_type, $data)
	{
		//
		// Si aucun type de média n'est indiqué, on utilisera par défaut
		// le type application/octet-stream (application/octetstream pour IE et Opera).
		// Si le type application/octet-stream	ou application/octetstream est indiqué, on fait
		// éventuellement le changement si le type n'est pas bon pour l'agent utilisateur.
		// Si on a à faire à Opera, on utilise application/octetstream car toute autre type peut poser
		// d'éventuels problèmes.
		//
		if (empty($mime_type) ||
			preg_match('#application/octet-?stream#i', $mime_type) ||
			WA_USER_BROWSER == 'opera'
		) {
			if (WA_USER_BROWSER == 'msie' || WA_USER_BROWSER == 'opera') {
				$mime_type = 'application/octetstream';
			}
			else {
				$mime_type = 'application/octet-stream';
			}
		}

		//
		// Désactivation de la compression de sortie de php au cas où
		// et envoi des en-têtes appropriés au client.
		//
		@ini_set('zlib.output_compression', 'Off');
		header('Content-Length: ' . strlen($data));
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Type: ' . $mime_type . '; name="' . $filename . '"');

		echo $data;
		exit;
	}

	/**
	 * Fermeture de la connexion au serveur ftp
	 */
	public function quit()
	{
		if ($this->use_ftp) {
			@ftp_close($this->connect_id);
		}
	}
}

}
