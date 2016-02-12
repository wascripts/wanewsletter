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
 * Class Auth
 *
 * Gestion des permissions des utilisateurs
 */
class Auth
{
	const VIEW   = 'auth_view';
	const EDIT   = 'auth_edit';
	const DEL    = 'auth_del';
	const SEND   = 'auth_send';
	const IMPORT = 'auth_import';
	const EXPORT = 'auth_export';
	const BAN    = 'auth_ban';
	const ATTACH = 'auth_attach';

	/**
	 * Vérifie si l'utilisateur s'est authentifié
	 *
	 * @return boolean
	 */
	public function isLoggedIn()
	{
		return (!empty($_SESSION['is_logged_in'])
			&& check_in_admin() == $_SESSION['is_admin_session']);
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
		global $nl_config;

		$login = false;

		$userdata = $this->getUserData($id);

		if ($userdata && $userdata['passwd'] != '') {
			// Ugly old md5 hash prior Wanewsletter 2.4-beta2
			if ($userdata['passwd'][0] != '$') {
				if (hash_equals($userdata['passwd'], md5($passwd))) {
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

		$data = [$columns['passwd'] => $passwd_hash];
		$cond = [$columns['uid'] => $uid];
		$db->update($tablename, $data, $cond);
	}

	/**
	 * Récupération des données utilisateur
	 *
	 * @param mixed $id Identifiant de l'utilisateur (username, email ou ID numérique)
	 *
	 * @return array
	 */
	public function getUserData($id)
	{
		global $db, $admindata;

		if ($_SESSION['uid'] && $_SESSION['uid'] == $id && !empty($admindata)) {
			return $admindata;
		}

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

		if ($userdata = $result->fetch($result::FETCH_ASSOC)) {
			$userdata['uid']      = intval($userdata[$columns['uid']]);
			$userdata['username'] =& $userdata[$columns['username']];
			$userdata['email']    =& $userdata[$columns['email']];
			$userdata['passwd']   =& $userdata[$columns['passwd']];
			$userdata['language'] =& $userdata[$columns['language']];

			if (check_in_admin()) {
				$sql = "SELECT l.liste_id, l.liste_name, l.liste_format,
						l.sender_email, l.return_email, l.confirm_subscribe,
						l.liste_public, l.limitevalidate, l.form_url,
						l.liste_sig, l.auto_purge, l.purge_freq,
						l.purge_next, l.liste_startdate, l.use_cron,
						l.pop_host, l.pop_port, l.pop_user, l.pop_pass,
						l.pop_tls, l.liste_alias, l.liste_numlogs,
						aa.auth_view, aa.auth_edit, aa.auth_del, aa.auth_send,
						aa.auth_import, aa.auth_export, aa.auth_ban,
						aa.auth_attach, aa.cc_admin
					FROM %s AS l
						LEFT JOIN %s AS aa ON aa.admin_id = %d
							AND aa.liste_id = l.liste_id
					ORDER BY l.liste_name ASC";
				$sql = sprintf($sql, LISTE_TABLE, AUTH_ADMIN_TABLE, $userdata['uid']);
			}
			else {
				$sql = "SELECT al.format, al.register_key, al.register_date,
						l.liste_id, l.liste_name, l.sender_email, l.return_email,
						l.liste_sig, l.liste_format, l.use_cron, l.liste_alias, l.form_url
					FROM %s AS l
						INNER JOIN %s AS al ON al.abo_id = %d
							AND al.liste_id = l.liste_id
					ORDER BY l.liste_name ASC";
				$sql = sprintf($sql, LISTE_TABLE, ABO_LISTE_TABLE, $userdata['uid']);
			}

			$result = $db->query($sql);

			while ($listdata = $result->fetch($result::FETCH_ASSOC)) {
				$userdata['lists'][$listdata['liste_id']] = $listdata;
			}

			return $userdata;
		}

		return null;
	}

	/**
	 * Renvoie les noms de table et de colonnes de la table utilisateur active
	 *
	 * @return array [string $tablename, array $columns]
	 */
	public function getUserTableInfos()
	{
		if (check_in_admin() || defined(__NAMESPACE__.'\\IN_INSTALL')) {
			$tablename = ADMIN_TABLE;
			$columns   = [];

			$columns['uid']      = 'admin_id';
			$columns['passwd']   = 'admin_pwd';
			$columns['username'] = 'admin_login';
			$columns['email']    = 'admin_email';
			$columns['language'] = 'admin_lang';
		}
		else {
			$tablename = ABONNES_TABLE;
			$columns   = [];

			$columns['uid']      = 'abo_id';
			$columns['passwd']   = 'abo_pwd';
			$columns['username'] = 'abo_pseudo';
			$columns['email']    = 'abo_email';
			$columns['language'] = 'abo_lang';
		}

		return [$tablename, $columns];
	}

	/**
	 * Vérification des permissions, selon la permission et la liste concernées.
	 *
	 * @param string  $auth_type Identifiant de la permission concernée
	 * @param integer $liste_id  Identifiant de la liste concernée
	 *
	 * @return boolean
	 */
	public function check($auth_type, $liste_id)
	{
		global $admindata;

		return (wan_is_admin($admindata) || !empty($admindata['lists'][$liste_id][$auth_type]));
	}

	/**
	 * Retourne un tableau d’identifiants des listes pour lesquelles
	 * la permission est accordée.
	 *
	 * @param string $auth_type Identifiant de la permission concernée
	 *
	 * @return array
	 */
	public function getLists($auth_type)
	{
		global $admindata;

		$lists = [];
		foreach ($admindata['lists'] as $liste_id => $data) {
			if (wan_is_admin($admindata) || !empty($data[$auth_type])) {
				$lists[$liste_id] = $data;
			}
		}

		return $lists;
	}
}
