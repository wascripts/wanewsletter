<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@webnaute.net>
 * @link      http://dev.webnaute.net/wanewsletter/
 * @copyright 2002-2021 Aurélien Maille
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

load_l10n();

$action = trim(filter_input(INPUT_POST, 'action'));
$email  = trim(u::filter_input(INPUT_POST, 'email'));
$pseudo = trim(u::filter_input(INPUT_POST, 'pseudo'));
$format = (int) filter_input(INPUT_POST, 'format', FILTER_VALIDATE_INT);
$liste  = (int) filter_input(INPUT_POST, 'liste', FILTER_VALIDATE_INT);
$code   = '';

if (preg_match('/([a-z0-9]{20})(?:&|$)/i', $_SERVER['QUERY_STRING'], $m)) {
	$code = $m[1];
}

$message = '';
$sub = new Subscription();

if ($action) {
	if (in_array($action, ['inscription', 'setformat', 'desinscription'])) {
		$sql = "SELECT liste_id, liste_name, liste_format, form_url,
				return_email, sender_email, liste_alias, liste_sig, use_cron,
				limitevalidate, purge_freq, confirm_subscribe
			FROM %s
			WHERE liste_id = %d";
		$sql = sprintf($sql, LISTE_TABLE, $liste);
		$result = $db->query($sql);

		if ($listdata = $result->fetch()) {
			try {
				if ($action == 'inscription') {
					$message = $sub->subscribe($listdata, $email, $pseudo, $format);
				}
				else if ($action == 'desinscription') {
					$message = $sub->unsubscribe($listdata, $email);
				}
				else {
					$message = $sub->setFormat($listdata, $email, $format);
				}
			}
			catch (Dblayer\Exception $e) {
				throw $e;
			}
			catch (Exception $e) {
				$message = $e->getMessage();
			}
		}
		else {
			$message = $lang['Message']['Unknown_list'];
		}
	}
	else {
		$message = $lang['Message']['Invalid_action'];
	}
}
else if ($code) {
	try {
		$message = $sub->checkCode($code);
	}
	catch (Dblayer\Exception $e) {
		throw $e;
	}
	catch (Exception $e) {
		$message = $e->getMessage();
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

	if ($output instanceof Output\Json) {
		$output->message($message);
	}
	else {
		echo nl2br($message);
	}
}

//
// remise des paramêtres par défaut
//
error_reporting(WA_INITIAL_ERROR_REPORTING);
