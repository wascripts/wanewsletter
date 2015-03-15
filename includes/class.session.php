<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

/**
 * Gestion des connexions à l'administration
 */
class Session
{
	/**
	 * Configuration pour l'envoi des cookies
	 *
	 * @var array
	 */
	protected $cfg_cookie = array();

	/**
	 * La session vient elle d'être créée ?
	 *
	 * @var boolean
	 */
	public $new_session   = false;

	/**
	 * Durée maximale d'une session
	 *
	 * @var integer
	 */
	protected $maxlifetime = 3600;

	/**
	 * Configuration du système de sessions PHP et démarrage d'une session
	 *
	 * @param array $config Configuration de la session
	 */
	public function __construct($config)
	{
		$this->cfg_cookie['name']     = 'wanewsletter';
		$this->cfg_cookie['path']     = str_replace('//', '/', dirname($_SERVER['REQUEST_URI']).'/');
		$this->cfg_cookie['lifetime'] = 0;
		$this->cfg_cookie['domain']   = null;
		$this->cfg_cookie['secure']   = wan_ssl_connection();
		$this->cfg_cookie['httponly'] = true;
		$this->maxlifetime = $config['session_length'];

		foreach ($this->cfg_cookie as $key => $value) {
			if (isset($config['cookie_'.$key])) {
				$this->cfg_cookie[$key] = $config['cookie_'.$key];
			}
		}

		ini_set('session.use_only_cookies', true);
		ini_set('session.use_trans_sid', false);

		session_set_save_handler(
			array($this, 'open'),
			array($this, 'close'),
			array($this, 'read'),
			array($this, 'write'),
			array($this, 'destroy'),
			array($this, 'gc')
		);

		session_set_cookie_params(
			$this->cfg_cookie['lifetime'],
			$this->cfg_cookie['path'],
			$this->cfg_cookie['domain'],
			$this->cfg_cookie['secure'],
			$this->cfg_cookie['httponly']
		);

		session_name($this->cfg_cookie['name'].'_sessid');
		session_start();

		if (!isset($_SESSION['is_logged_in'])) {
			$this->reset();
		}

		session_register_shutdown();
	}

	/**
	 * Réinitialise les données en session
	 */
	public function reset()
	{
		$_SESSION['is_admin_session'] = defined('IN_ADMIN');
		$_SESSION['is_logged_in'] = false;
		$_SESSION['uid']   = null;
		$this->new_session = true;
	}

	/**
	 * Ouverture de session
	 *
	 * @return boolean
	 */
	public function open()
	{
		return true;
	}

	/**
	 * Fermeture de session
	 *
	 * @return boolean
	 */
	public function close()
	{
		$this->gc();
		return true;
	}

	/**
	 * Lecture des données en session
	 *
	 * @param string $sid Identifiant de la session
	 *
	 * @return string
	 */
	public function read($sid)
	{
		global $db;

		$sql = sprintf("SELECT session_data FROM %s
			WHERE session_id = '%s' AND session_expire > %d",
			SESSIONS_TABLE,
			$db->escape($sid),
			time()
		);
		$result = $db->query($sql);

		if ($row = $result->fetch()) {
			return $row['session_data'];
		}

		return '';
	}

	/**
	 * Écriture des données en session
	 *
	 * @param string $sid  Identifiant de la session
	 * @param string $data Données de la session
	 *
	 * @return boolean
	 */
	public function write($sid, $data)
	{
		global $db;

		$sql = sprintf("SELECT COUNT(session_id)
			FROM %s WHERE session_id = '%s'",
			SESSIONS_TABLE,
			$db->escape($sid)
		);
		$result = $db->query($sql);

		$sql_data = array(
			'session_data' => $data
		);

		if ($result->column(0) == 1) {
			$db->update(SESSIONS_TABLE, $sql_data, array('session_id' => $sid));
		}
		else {
			$sql_data['session_id']     = $sid;
			$sql_data['session_start']  = time();
			$sql_data['session_expire'] = (time() + $this->maxlifetime);
			$db->insert(SESSIONS_TABLE, $sql_data);
		}

		return ($db->affectedRows() == 1);
	}

	/**
	 * Destruction de la session
	 *
	 * @param string $sid Identifiant de la session
	 *
	 * @return boolean
	 */
	public function destroy($sid)
	{
		global $db;

		$db->query(sprintf("DELETE FROM %s WHERE session_id = '%s'",
			SESSIONS_TABLE,
			$db->escape($sid)
		));

		session_unset();

		return true;
	}

	/**
	 * Suppression des sessions ayant expiré
	 *
	 * @return boolean
	 */
	public function gc()
	{
		global $db;

		$db->query(sprintf("DELETE FROM %s
			WHERE session_expire < %d",
			SESSIONS_TABLE,
			time()
		));

		if ($db->affectedRows() > 0) {
			$db->vacuum(SESSIONS_TABLE);
		}

		return true;
	}

	/**
	 * Envoi des cookies
	 *
	 * @param string  $name     Nom du cookie
	 * @param string  $value    Données à insérer dans le cookie
	 * @param integer $lifetime Durée de validité du cookie
	 *
	 * @return boolean
	 */
	public function send_cookie($name, $value, $lifetime)
	{
		return setcookie(
			$name,
			$value,
			$lifetime,
			$this->cfg_cookie['path'],
			$this->cfg_cookie['domain'],
			$this->cfg_cookie['secure'],
			$this->cfg_cookie['httponly']
		);
	}
}
