<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

/**
 * Gestion des connexions à l'administration
 */
class Session implements \SessionHandlerInterface
{
	/**
	 * Configuration pour l'envoi des cookies
	 *
	 * @var array
	 */
	protected $cfg_cookie = [];

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
	 * Configuration du système de sessions PHP et démarrage d’une session
	 *
	 * @param array $config Configuration de la session
	 */
	public function __construct($config)
	{
		$this->maxlifetime = $config['session_length'];

		$this->cfg_cookie['name']     = 'wanewsletter';
		$this->cfg_cookie['path']     = str_replace('//', '/', dirname($_SERVER['REQUEST_URI']).'/');
		$this->cfg_cookie['lifetime'] = 0;
		$this->cfg_cookie['domain']   = null;
		$this->cfg_cookie['secure']   = wan_ssl_connection();
		$this->cfg_cookie['httponly'] = true;

		foreach ($this->cfg_cookie as $key => $value) {
			if (isset($config['cookie_'.$key])) {
				$this->cfg_cookie[$key] = $config['cookie_'.$key];
			}
		}

		ini_set('session.use_only_cookies', true);
		ini_set('session.use_trans_sid', false);

		session_set_save_handler($this);

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
	}

	/**
	 * Réinitialise les données en session
	 */
	public function reset()
	{
		$_SESSION['is_admin_session'] = check_in_admin();
		$_SESSION['is_logged_in'] = false;
		$_SESSION['uid']   = null;

		if (!$this->new_session) {
			session_regenerate_id();
			$this->new_session = true;
		}
	}

	/**
	 * Fin de la session.
	 * Équivaut à appeler session_destroy() mais évite d’exposer l’API session
	 * de PHP en dehors de la classe.
	 */
	public function end()
	{
		$this->destroy(session_id());
	}

	/**
	 * Nom de la session.
	 * Équivaut à appeler session_name() mais évite d’exposer l’API session
	 * de PHP en dehors de la classe.
	 */
	public function getName()
	{
		return session_name();
	}

	/**
	 * Ouverture de session
	 *
	 * @param string $save_path
	 * @param string $sid
	 *
	 * @return boolean
	 */
	public function open($save_path, $sid)
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
			SESSION_TABLE,
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
			SESSION_TABLE,
			$db->escape($sid)
		);
		$result = $db->query($sql);

		$sql_data = [
			'session_data' => $data
		];

		if ($result->column(0) == 1) {
			$db->update(SESSION_TABLE, $sql_data, ['session_id' => $sid]);
		}
		else {
			$sql_data['session_id']     = $sid;
			$sql_data['session_start']  = time();
			$sql_data['session_expire'] = (time() + $this->maxlifetime);
			$db->insert(SESSION_TABLE, $sql_data);
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
			SESSION_TABLE,
			$db->escape($sid)
		));

		session_unset();

		return true;
	}

	/**
	 * Suppression des sessions ayant expiré
	 *
	 * @param integer $lifetime
	 *
	 * @return boolean
	 */
	public function gc($lifetime = -1)
	{
		global $db;

		$db->query(sprintf("DELETE FROM %s
			WHERE session_expire < %d",
			SESSION_TABLE,
			time()
		));

		if ($db->affectedRows() > 0) {
			$db->vacuum(SESSION_TABLE);
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
