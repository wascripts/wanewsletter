<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if (!defined('IN_NEWSLETTER')) {
	exit('<b>No hacking</b>');
}

define('IN_ADMIN', true);

$secure = true;

require '../includes/common.inc.php';

//
// Initialisation de la connexion à la base de données et récupération de la configuration
//
$db = WaDatabase($dsn);
$nl_config = wa_get_config();

//
// Hors phase de développement ou beta, on affiche une alerte si
// l'administrateur a activé le débogage.
//
if (DEBUG_MODE == DEBUG_LEVEL_QUIET && wan_get_debug_level() > DEBUG_MODE) {
	wanlog($lang['Message']['Warning_debug_active']);
}

$liste = (!empty($_REQUEST['liste'])) ? intval($_REQUEST['liste']) : 0;

//
//// Start session and load settings
//
$session = new Session();
$session->update_hash = (isset($nl_config['db_version']) && $nl_config['db_version'] > 8);
$admindata = $session->check($liste);
load_settings($admindata);
//
//// End
//

if (!defined('IN_LOGIN')) {
	if (!$admindata) {
		$redirect  = '?redirect=' . basename(server_info('PHP_SELF'));
		$redirect .= (server_info('QUERY_STRING') != '') ? rawurlencode('?' . server_info('QUERY_STRING')) : '';

		http_redirect('login.php' . $redirect);
	}

	if (!defined('IN_UPGRADE')) {
		//
		// On vérifie si les tables du script sont bien à jour
		//
		if (!check_db_version(@$nl_config['db_version'])) {
			$output->addLine($lang['Need_upgrade_db']);
			$output->addLine($lang['Need_upgrade_db_link'], 'upgrade.php');
			$output->displayMessage();
		}

		if (!is_writable(WA_TMPDIR)) {
			$output->displayMessage(sprintf(
				$lang['Message']['Dir_not_writable'],
				wan_htmlspecialchars(WA_TMPDIR)
			));
		}

		$auth = new Auth();

		//
		// Si la liste en session n'existe pas, on met à jour la session
		//
		if (!isset($auth->listdata[$admindata['session_liste']])) {
			$admindata['session_liste'] = 0;

			$sql = sprintf("UPDATE %s
				SET session_liste = 0
				WHERE session_id = '%s'
					AND admin_id = %d",
				SESSIONS_TABLE,
				$session->getId(),
				$admindata['admin_id']
			);
			$db->query($sql);
		}
	}

	if ($secure && strtoupper(server_info('REQUEST_METHOD')) == 'POST' && $session->new_session) {
		$output->displayMessage('Invalid_session');
	}
}

//
// Purge 'automatique' des listes (comptes non activés au-delà du temps limite)
//
if (!(time() % 10)) {
	purge_liste();
}
