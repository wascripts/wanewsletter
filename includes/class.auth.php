<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if (!defined('CLASS_AUTH_INC')) {

define('CLASS_AUTH_INC', true);

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
	 * Initialisation de la classe, et récupération des permissions de l'utilisateur courant
	 */
	public function __construct()
	{
		global $admindata;

		$this->read_data($admindata['admin_id']);
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

		if ($admindata['admin_id'] != $admin_id) {
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

}
