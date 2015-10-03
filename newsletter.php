<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

use Patchwork\Utf8 as u;

if (!defined('WA_ROOTDIR')) {
	define('WA_ROOTDIR', str_replace('\\', '/', __DIR__));
}

define('WA_INITIAL_ERROR_REPORTING', error_reporting());

require WA_ROOTDIR . '/includes/common.inc.php';

//
// Initialisation de la connexion à la base de données et récupération de la configuration
//
$db = WaDatabase($dsn);
$nl_config = wa_get_config();

if (!empty($language) && validate_lang($language)) {
	$nl_config['language'] = $language;
}

load_settings();

$action = trim(filter_input(INPUT_POST, 'action'));
$email  = trim(u::filter_input(INPUT_POST, 'email'));
$pseudo = trim(u::filter_input(INPUT_POST, 'pseudo'));
$format = (int) filter_input(INPUT_POST, 'format', FILTER_VALIDATE_INT);
$liste  = (int) filter_input(INPUT_POST, 'liste', FILTER_VALIDATE_INT);
$code   = '';

if (!$action && preg_match('/([a-z0-9]{20})(?:&|$)/i', $_SERVER['QUERY_STRING'], $m)) {
	$code = $m[1];
}

$message = '';

if ($action || $code) {
	//
	// Purge des éventuelles inscriptions dépassées
	// pour parer au cas d'une réinscription
	//
	purge_liste();

	if ($action) {
		if (in_array($action, ['inscription', 'setformat', 'desinscription'])) {
			$sql = "SELECT liste_id, liste_format, sender_email, liste_alias, limitevalidate,
					liste_name, form_url, return_email, liste_sig, use_cron, confirm_subscribe
				FROM " . LISTE_TABLE . "
				WHERE liste_id = " .  $liste;
			$result = $db->query($sql);

			if ($listdata = $result->fetch()) {
				$sub = new Subscription($listdata);
				$sub->message =& $message;
				$sub->do_action($action, $email, $format, $pseudo);
			}
			else {
				$message = $lang['Message']['Unknown_list'];
			}
		}
		else {
			$message = $lang['Message']['Invalid_action'];
		}
	}
	else {
		$sub = new Subscription();
		$sub->message =& $message;
		$sub->check_code($code);
	}
}

if (empty($return_message)) {
	//
	// On réactive le gestionnaire d'erreur précédent
	//
	restore_error_handler();

	// Si besoin, conversion du message vers le charset demandé
	if (!empty($textCharset)) {
		$message = iconv('UTF-8', $textCharset.'//TRANSLIT', $message);
	}

	echo nl2br($message);
}

//
// remise des paramêtres par défaut
//
error_reporting(WA_INITIAL_ERROR_REPORTING);
