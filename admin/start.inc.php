<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

const IN_ADMIN = true;

if (substr($_SERVER['SCRIPT_FILENAME'], -8) == '.inc.php') {
	exit('<b>No hacking</b>');
}

require '../includes/common.inc.php';

//
// Initialisation de la connexion à la base de données et récupération de la configuration
//
$db = WaDatabase($dsn);
$nl_config = wa_get_config();

//
// On vérifie si les tables du script sont bien à jour
//
if (!check_db_version(@$nl_config['db_version'])) {
	$output->addLine($lang['Need_upgrade_db']);
	$output->addLine($lang['Need_upgrade_db_link'], 'upgrade.php');
	$output->message();
}

//
// Hors phase de développement ou beta, on affiche une alerte si
// l'administrateur a activé le débogage.
//
if (DEBUG_MODE == DEBUG_LEVEL_QUIET && wan_get_debug_level() > DEBUG_MODE) {
	wanlog($lang['Message']['Warning_debug_active']);
}

//
//// Start session
//
$session = new Session($nl_config);
$auth = new Auth();
//
// End
//

if (!defined(__NAMESPACE__.'\\IN_LOGIN')) {
	if (!$auth->isLoggedIn() || !($admindata = $auth->getUserData($_SESSION['uid']))) {
		$session->reset();
		$_SESSION['redirect'] = filter_input(INPUT_SERVER, 'REQUEST_URI');

		http_redirect('login.php');
	}

	load_settings($admindata);

	if (!is_writable(WA_TMPDIR)) {
		$output->message(sprintf(
			$lang['Message']['Dir_not_writable'],
			htmlspecialchars(WA_TMPDIR)
		));
	}

	//
	// Si la liste en session n'existe pas, on met à jour la session.
	// On teste aussi un éventuel identifiant de liste donné en paramètre.
	//
	if (!isset($_SESSION['liste'])) {
		$_SESSION['liste'] = 0;
	}

	if (!empty($_REQUEST['liste'])) {
		$_SESSION['liste'] = intval($_REQUEST['liste']);
	}

	if (!isset($auth->getLists(Auth::VIEW)[$_SESSION['liste']])) {
		$_SESSION['liste'] = 0;
	}

	if (strtoupper(filter_input(INPUT_SERVER, 'REQUEST_METHOD')) == 'POST' && $session->new_session) {
		$output->message('Invalid_session');
	}
}

//
// Purge 'automatique' des listes (comptes non activés au-delà du temps limite)
//
if (!(time() % 10)) {
	purge_liste();
}
