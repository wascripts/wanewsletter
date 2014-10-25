<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if (!defined('CLASS_SESSION_INC')) {

define('CLASS_SESSION_INC', true);

/**
 * Gestion des connexions à l'administration
 */
class Session
{
	/**
	 * Ip de l'utilisateur
	 *
	 * @var string
	 */
	private $user_ip      = '';

	/**
	 * Identifiant de la session
	 *
	 * @var string
	 */
	private $session_id   = '';

	/**
	 * Données de la session
	 *
	 * @var array
	 */
	private $sessiondata  = array();

	/**
	 * Configuration pour l'envoi des cookies
	 *
	 * @var array
	 */
	private $cfg_cookie   = array();

	/**
	 * La session vient elle d'être créée ?
	 *
	 * @var boolean
	 */
	public $new_session  = false;

	/**
	 * Statut utilisateur connecté/non connecté
	 *
	 * @var boolean
	 */
	public $is_logged_in = false;

	/**
	 * Mise à jour du hash de mot de passe à chaque identification réussie

	 * @var boolean
	 */
	public $update_hash  = true;

	/**
	 * Intialisation de la classe, récupération de l'ip ..
	 */
	public function __construct()
	{
		global $nl_config;

		//
		// Récupération de l'IP
		//
		$client_ip = server_info('REMOTE_ADDR');
		$proxy_ip  = server_info('HTTP_X_FORWARDED_FOR');

		if (empty($client_ip)) {
			$client_ip = '127.0.0.1';
		}

		if (preg_match('/^\d+\.\d+\.\d+\.\d+/', $proxy_ip, $match)) {
			$private_ip = $match[0];

			/*
			 * Liens utiles sur les différentes plages d'ip :
			 *
			 * @link http://www.commentcamarche.net/internet/ip.php3
			 * @link http://www.usenet-fr.net/fur/comp/reseaux/masques.html
			 */

			//
			// Liste d'ip non valides
			//
			$pattern_ip = array();
			$pattern_ip[] = '/^0\..*/'; // Réseau 0 n'existe pas
			$pattern_ip[] = '/^127\..*/'; // ip locale

			// Plages d'ip spécifiques à l'intranet
			$pattern_ip[] = '/^10\..*/';
			$pattern_ip[] = '/^172\.1[6-9]\..*/';
			$pattern_ip[] = '/^172\.2[0-9]\..*/';
			$pattern_ip[] = '/^172\.3[0-1]\..*/';
			$pattern_ip[] = '/^192\.168\..*/';

			// Plage d'adresse de classe D réservée pour les flux multicast et de classe E, non utilisée
			$pattern_ip[] = '/^22[4-9]\..*/';
			$pattern_ip[] = '/^2[3-5][0-9]\..*/';

			$client_ip = preg_replace($pattern_ip, $client_ip, $private_ip);
		}

		$this->user_ip = $this->encode_ip($client_ip);

		$this->cfg_cookie['cookie_name']   = $nl_config['cookie_name'];
		$this->cfg_cookie['cookie_path']   = $nl_config['cookie_path'];
		$this->cfg_cookie['cookie_domain'] = null;
		$this->cfg_cookie['cookie_secure'] = wan_ssl_connection();
		$this->cfg_cookie['cookie_httponly'] = true;
	}

	/**
	 * Renvoie l'identifiant de la session actuelle
	 *
	 * @return string
	 */
	public function getId()
	{
		return $this->session_id;
	}

	/**
	 * Ouverture d'une nouvelle session
	 *
	 * @param array   $admindata Données utilisateur
	 * @param boolean $autologin True si activer l'autoconnexion
	 *
	 * @return array
	 */
	public function open($admindata, $autologin)
	{
		global $db;

		$current_time = time();
		$liste = (!empty($this->sessiondata['listeid'])) ? $this->sessiondata['listeid'] : 0;

		if (!empty($admindata['session_id'])) {
			$this->session_id = $admindata['session_id'];
		}

		$sql_data = array(
			'session_id'    => generate_key(),
			'admin_id'      => $admindata['admin_id'],
			'session_start' => $current_time,
			'session_time'  => $current_time,
			'session_ip'    => $this->user_ip,
			'session_liste' => $liste
		);

		if ($this->session_id != '') {
			$db->update(SESSIONS_TABLE, $sql_data, array('session_id' => $this->session_id));

			if ($db->affectedRows() == 0) {
				$this->session_id = '';
			}
		}

		if ($this->session_id == '') {
			$this->new_session = true;
			$db->insert(SESSIONS_TABLE, $sql_data);
		}

		$admindata = array_merge($admindata, $sql_data);
		$this->session_id = $admindata['session_id'];

		$sessiondata = array(
			'adminloginkey' => ($autologin) ? $admindata['admin_pwd'] : '',
			'adminid'       => $admindata['admin_id']
		);

		$this->send_cookie('sessid', $this->session_id, 0);
		$this->send_cookie('data', serialize($sessiondata), strtotime('+1 month'));

		$this->is_logged_in = true;

		return $admindata;
	}

	/**
	 * Vérification de la session et de l'utilisateur
	 *
	 * @param integer $liste Id de la liste actuellement gérée
	 *
	 * @return mixed
	 */
	public function check($liste = 0)
	{
		global $db, $nl_config;

		if (!empty($_COOKIE[$this->cfg_cookie['cookie_name'] . '_sessid']) ||
			!empty($_COOKIE[$this->cfg_cookie['cookie_name'] . '_data'])
		) {
			$this->session_id = (!empty($_COOKIE[$this->cfg_cookie['cookie_name'] . '_sessid']))
				? $_COOKIE[$this->cfg_cookie['cookie_name'] . '_sessid'] : '';
			$sessiondata = (!empty($_COOKIE[$this->cfg_cookie['cookie_name'] . '_data']))
				? unserialize($_COOKIE[$this->cfg_cookie['cookie_name'] . '_data']) : '';
		}
		else {
			$sessiondata = '';
		}

		$current_time = time();
		$expiry_time  = ($current_time - $nl_config['session_length']);
		$this->sessiondata = (is_array($sessiondata)) ? $sessiondata : array();

		//
		// Suppression des sessions périmées
		//
		if (!($current_time % 5)) {
			$sql = "DELETE FROM " . SESSIONS_TABLE . "
				WHERE session_time < $expiry_time
					AND session_id != '{$this->session_id}'";
			$db->query($sql);
		}

		if ($this->session_id != '') {
			//
			// Récupération des infos sur la session et l'utilisateur
			//
			$sql = "SELECT s.*, a.*
				FROM " . SESSIONS_TABLE . " AS s
					INNER JOIN " . ADMIN_TABLE . " AS a ON a.admin_id = s.admin_id
				WHERE s.session_id = '{$this->session_id}'
					AND s.session_start > " . $expiry_time;
			$result = $db->query($sql);

			if ($row = $result->fetch()) {
				//
				// Comparaison des ip pour éviter la substitution des sessions
				// Peut poser problème avec certains proxy
				//
				$len_check_ip = 4;

				if (strncasecmp($row['session_ip'], $this->user_ip, $len_check_ip) == 0) {
					$force_update = false;
					if (($liste > 0 && $liste != $row['session_liste']) || $liste == -1) {
						$force_update = true;
						$row['session_liste'] = ($liste == -1) ? 0 : $liste;
					}

					if (($current_time - $row['session_time']) > 60 || $force_update) {
						$data = array(
							'session_time'  => $current_time,
							'session_liste' => $row['session_liste']
						);
						$db->update(SESSIONS_TABLE, $data, array('session_id' => $this->session_id));

						if ($force_update) {
							$this->send_cookie('listeid', $row['session_liste'], strtotime('+1 month'));
						}
					}

					$this->is_logged_in = true;

					return $row;
				}
			}
		}

		$this->sessiondata['listeid'] = (!empty($_COOKIE[$this->cfg_cookie['cookie_name'] . '_listeid']))
			? intval($_COOKIE[$this->cfg_cookie['cookie_name'] . '_listeid']) : 0;

		//
		// Connexion automatique
		//
		$autologin = true;

		//
		// Authentification HTTP Basic
		//
		if (ENABLE_HTTP_AUTHENTICATION) {
			$username = $passwd = $authorization = null;

			if (!empty($_SERVER['PHP_AUTH_USER'])) {
				$username = $_SERVER['PHP_AUTH_USER'];
				$passwd   = $_SERVER['PHP_AUTH_PW'];
			}
			// Cas particulier : PHP en mode CGI
			else if (!empty($_SERVER['REMOTE_USER'])) {
				$authorization = $_SERVER['REMOTE_USER'];
			}
			// Dans certains cas de redirections internes
			else if (!empty($_SERVER['REDIRECT_REMOTE_USER'])) {
				$authorization = $_SERVER['REDIRECT_REMOTE_USER'];
			}
			// Cas particulier pour IIS et PHP4, dixit le manuel PHP
			else if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
				$authorization = $_SERVER['HTTP_AUTHORIZATION'];
			}

			if (!is_null($authorization) && strncasecmp($authorization, 'Basic ', 6) == 0) {
				list($username, $passwd) = explode(':', base64_decode(substr($authorization, 6)), 2);
			}

			if (!is_null($username)) {
				$autologin = false;
				$this->sessiondata['adminid'] = $username;
				$this->sessiondata['adminloginkey'] = md5($passwd);
			}
		}

		if (!empty($this->sessiondata['adminloginkey'])) {
			$admin_id = (!empty($this->sessiondata['adminid'])) ? $this->sessiondata['adminid'] : 0;

			return $this->login($admin_id, $this->sessiondata['adminloginkey'], $autologin);
		}
		else {
			return false;
		}
	}

	/**
	 * Déconnexion de l'administration
	 *
	 * @param integer $admin_id Id de l'utilisateur concerné
	 */
	public function logout($admin_id)
	{
		global $db;

		$current_time = time();

		if ($this->session_id != '') {
			$sql = "DELETE FROM " . SESSIONS_TABLE . "
				WHERE session_id = '{$this->session_id}'
					AND admin_id = " . $admin_id;
			$db->query($sql);
		}

		$this->is_logged_in = false;
		$ts_expire = strtotime('-1 month');
		$this->send_cookie('sessid', '', $ts_expire);
		$this->send_cookie('data', '', $ts_expire);

	}

	/**
	 * Connexion à l'administration
	 *
	 * @param mixed   $admin_mixed Id ou pseudo de l'utilisateur concerné
	 * @param string  $admin_pwd   Mot de passe de l'utilisateur
	 * @param boolean $autologin   True si autoconnexion demandée
	 *
	 * @return mixed
	 */
	public function login($admin_mixed, $admin_pwd, $autologin)
	{
		global $db;

		$sql = 'SELECT s.*, a.*
			FROM ' . ADMIN_TABLE . ' AS a
			LEFT JOIN ' . SESSIONS_TABLE . ' AS s ON s.admin_id = a.admin_id WHERE ';
		if (is_numeric($admin_mixed)) {
			$sql .= 'a.admin_id = ' . $admin_mixed;
		}
		else {
			$sql .= 'LOWER(a.admin_login) = \'' . $db->escape(strtolower($admin_mixed)) . '\'';
		}

		$result = $db->query($sql);
		$login  = false;
		$hasher = new PasswordHash();

		if ($admindata = $result->fetch()) {
			// Ugly old md5 hash prior Wanewsletter 2.4-beta2
			if ($admindata['admin_pwd'][0] != '$') {
				if ($admindata['admin_pwd'] === md5($admin_pwd)) {
					$login = true;
				}
			}
			// New password hash using phpass
			else if ($hasher->check($admin_pwd, $admindata['admin_pwd'])) {
				$login = true;
			}
		}

		if ($login) {
			if ($this->update_hash) {
				$admindata['admin_pwd'] = $hasher->hash($admin_pwd);

				$data = array('admin_pwd' => $admindata['admin_pwd']);
				$cond = array('admin_id'  => $admindata['admin_id']);
				$db->update(ADMIN_TABLE, $data, $cond);
			}

			return $this->open($admindata, $autologin);
		}

		return false;
	}

	/**
	 * Envoi des cookies
	 *
	 * @param string  $name        Nom du cookie
	 * @param string  $cookie_data Données à insérer dans le cookie
	 * @param integer $cookie_time Durée de validité du cookie
	 *
	 * @return boolean
	 */
	public function send_cookie($name, $cookie_data, $cookie_time)
	{
		return setcookie(
			$this->cfg_cookie['cookie_name'] . '_' . $name,
			$cookie_data,
			$cookie_time,
			$this->cfg_cookie['cookie_path'],
			$this->cfg_cookie['cookie_domain'],
			$this->cfg_cookie['cookie_secure'],
			$this->cfg_cookie['cookie_httponly']
		);
	}

	/**
	 * Renomme les cookies précédemment envoyés par la classe Session
	 *
	 * @param string $new_prefix Nouveau préfixe pour les cookies envoyés
	 */
	public function rename_cookies($new_prefix)
	{
		$old_prefix = $this->cfg_cookie['cookie_name'];
		$cookies_to_rename = array();

		foreach ($_COOKIE as $name => $value) {
			$len = strlen($old_prefix)+1;
			if (strncmp($name, $old_prefix.'_', $len) === 0) {
				$name = substr($name, $len);
				$cookies_to_rename[$name] = $value;
				$this->send_cookie($name, '', strtotime('-1 month'));
			}
		}

		$this->cfg_cookie['cookie_name'] = $new_prefix;

		foreach ($cookies_to_rename as $name => $value) {
			$expires = ($name == 'sessid') ? 0 : strtotime('+1 month');
			$this->send_cookie($name, $value, $expires);
		}
	}

	/**
	 * Encodage des IP pour stockage et comparaisons plus simples
	 * Importé de phpBB et modifié
	 *
	 * @param string $dotquat_ip
	 *
	 * @return string
	 */
	public function encode_ip($dotquad_ip)
	{
		$ip_sep = explode('.', $dotquad_ip);
		return sprintf('%02x%02x%02x%02x', $ip_sep[0], $ip_sep[1], $ip_sep[2], $ip_sep[3]);
	}

	/**
	 * Décodage des IP
	 * Importé de phpBB et modifié
	 *
	 * @param string $hex_ip Ip en hexadécimal
	 *
	 * @return string
	 */
	public function decode_ip($hex_ip)
	{
		$hexip_parts = explode('.', chunk_split($hex_ip, 2, '.'));
		array_pop($hexip_parts);

		return implode('.', array_map('hexdec', $hexip_parts));
	}
}

}

