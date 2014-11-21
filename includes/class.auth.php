<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

/**
 * Class Auth
 *
 * Gestion des permissions des utilisateurs
 */
class Auth
{
	const VIEW   = 1;
	const EDIT   = 2;
	const DEL    = 3;
	const SEND   = 4;
	const IMPORT = 5;
	const EXPORT = 6;
	const BAN    = 7;
	const ATTACH = 8;

	public $listdata = array();
	private $rowset  = array();

	public $auth_ary = array(
		self::VIEW   => 'auth_view',
		self::EDIT   => 'auth_edit',
		self::DEL    => 'auth_del',
		self::SEND   => 'auth_send',
		self::IMPORT => 'auth_import',
		self::EXPORT => 'auth_export',
		self::BAN    => 'auth_ban',
		self::ATTACH => 'auth_attach'
	);

	/**
	 * Vérifie si l'utilisateur s'est authentifié
	 */
	public function isLoggedIn()
	{
		return (!empty($_SESSION['is_logged_in']) && defined('IN_ADMIN') == $_SESSION['is_admin_session']);
	}

	/**
	 * Vérification des identifiants de connexion
	 *
	 * @param mixed  $id     Identifiant (peut être un nom d'utilisateur ou une adresse email)
	 * @param string $passwd Mot de passe de l'utilisateur
	 *
	 * @return boolean|array
	 */
	public function checkCredentials($id, $passwd)
	{
		global $db, $nl_config;

		$login = false;

		$userdata = $this->getUserData($id);

		if ($userdata && $userdata['passwd'] != null) {
			// Ugly old md5 hash prior Wanewsletter 2.4-beta2
			if ($userdata['passwd'][0] != '$') {
				if ($userdata['passwd'] === md5($passwd)) {
					$login = true;
				}
			}
			// New password hash using password API
			else if (password_verify($passwd, $userdata['passwd'])) {
				$login = true;
			}
		}

		if ($login) {
			// Avant la version 9 des tables, les colonnes stockant les mots
			// de passe étaient limitées à 32 caractères.
			if (isset($nl_config['db_version']) && $nl_config['db_version'] > 8) {
				$this->updatePassword($userdata['uid'], $passwd);
			}

			return $userdata;
		}

		return false;
	}

	/**
	 * @param integer $uid    Identifiant de l'utilisateur
	 * @param string  $passwd Nouveau mot de passe à hasher et stocker
	 */
	public function updatePassword($uid, $passwd)
	{
		global $db;

		list($tablename, $columns) = $this->getUserTableInfos();

		if (!($passwd_hash = password_hash($passwd, PASSWORD_DEFAULT))) {
			trigger_error("Unexpected error returned by password API", E_USER_ERROR);
		}

		$data = array($columns['passwd'] => $passwd_hash);
		$cond = array($columns['uid'] => $uid);
		$db->update($tablename, $data, $cond);
	}

	/**
	 * Récupération des données utilisateur
	 *
	 * @param mixed $id Identifiant de l'utilisateur (username, email ou ID numérique)
	 *
	 * @return boolean|array
	 */
	public function getUserData($id)
	{
		global $db;

		list($tablename, $columns) = $this->getUserTableInfos();

		if (!is_int($id) && $id != '') {
			$sql_where = sprintf("%s = '%s'",
				(strpos($id, '@') ? $columns['email'] : $columns['username']),
				$db->escape($id)
			);
		}
		else {
			$sql_where = sprintf("%s = %d", $columns['uid'], $id);
		}

		$sql = "SELECT *
			FROM $tablename
			WHERE " . $sql_where;
		$result = $db->query($sql);

		if ($row = $result->fetch()) {
			$row['uid']      = intval($row[$columns['uid']]);
			$row['username'] = $row[$columns['username']];
			$row['email']    = $row[$columns['email']];
			$row['passwd']   = $row[$columns['passwd']];

			return $row;
		}

		return false;
	}

	/**
	 * Renvoie les noms de table et de colonnes de la table utilisateur active
	 *
	 * @return array [string $tablename, array $columns]
	 */
	public function getUserTableInfos()
	{
		if (defined('IN_ADMIN')) {
			$tablename = ADMIN_TABLE;
			$columns   = array();

			$columns['uid']      = 'admin_id';
			$columns['passwd']   = 'admin_pwd';
			$columns['username'] = 'admin_login';
			$columns['email']    = 'admin_email';
		}
		else {
			$tablename = ABONNES_TABLE;
			$columns   = array();

			$columns['uid']      = 'abo_id';
			$columns['passwd']   = 'abo_pwd';
			$columns['username'] = 'abo_pseudo';
			$columns['email']    = 'abo_email';
		}

		return array($tablename, $columns);
	}

	/**
	 * Récupèration des permissions pour l'utilisateur demandé
	 *
	 * @param integer $admin_id Identifiant de l'utilisateur concerné
	 */
	public function read_data($admin_id)
	{
		global $db, $admindata;

		$sql = "SELECT li.liste_id, li.liste_name, li.liste_format, li.sender_email, li.return_email,
				li.confirm_subscribe, li.liste_public, li.limitevalidate, li.form_url, li.liste_sig,
				li.auto_purge, li.purge_freq, li.purge_next, li.liste_startdate, li.use_cron, li.pop_host,
				li.pop_port, li.pop_user, li.pop_pass, li.liste_alias, li.liste_numlogs, aa.auth_view, aa.auth_edit,
				aa.auth_del, aa.auth_send, aa.auth_import, aa.auth_export, aa.auth_ban, aa.auth_attach, aa.cc_admin
			FROM " . LISTE_TABLE . " AS li
				LEFT JOIN " . AUTH_ADMIN_TABLE . " AS aa ON aa.admin_id = $admin_id
					AND aa.liste_id = li.liste_id
			ORDER BY li.liste_name ASC";
		$result = $db->query($sql);

		$tmp_ary = array();
		while ($row = $result->fetch()) {
			$tmp_ary[$row['liste_id']] = $row;
		}

		if (!empty($admindata) && $admindata['admin_id'] != $admin_id) {
			return $tmp_ary;
		}

		$this->listdata = $tmp_ary;
	}

	/**
	 * Fonction de vérification des permissions, selon la permission concernée et la liste concernée
	 * Si vérification pour une liste particulière, retourne un booléen, sinon retourne un tableau d'identifiant
	 * des listes pour lesquelles la permission est accordée
	 *
	 * @param integer $auth_type Code de la permission concernée
	 * @param integer $liste_id  Identifiant de la liste concernée
	 *
	 * @return array|boolean
	 */
	public function check_auth($auth_type, $liste_id = null)
	{
		global $admindata;

		$auth_name = $this->auth_ary[$auth_type];

		if ($liste_id == null) {
			$liste_id_ary = array();
			foreach ($this->listdata as $liste_id => $auth_list) {
				if (wan_is_admin($admindata) || !empty($auth_list[$auth_name])) {
					$liste_id_ary[] = $liste_id;
				}
			}

			return $liste_id_ary;
		}
		else {
			if (isset($this->listdata[$liste_id]) &&
				(wan_is_admin($admindata) || !empty($this->listdata[$liste_id][$auth_name]))
			) {
				return true;
			}

			return false;
		}
	}

	/**
	 * Construction de la liste déroulante oui/non pour la permission concernée et la liste concernée
	 *
	 * @param integer $auth_type Code de la permission
	 * @param array   $listdata  Tableau des permissions pour la liste en cours
	 *
	 * @return string
	 */
	public function box_auth($auth_type, $listdata)
	{
		global $output, $lang;

		$auth_name = $this->auth_ary[$auth_type];

		$selected_yes = $output->getBoolAttr('selected', !empty($listdata[$auth_name]));
		$selected_no  = $output->getBoolAttr('selected', empty($listdata[$auth_name]));

		$box_auth  = '<select name="' . $auth_name . '[]">';
		$box_auth .= '<option value="1"' . $selected_yes . '> ' . $lang['Yes'] . ' </option>';
		$box_auth .= '<option value="0"' . $selected_no . '> ' . $lang['No'] . ' </option>';
		$box_auth .= '</select>';

		return $box_auth;
	}
}
