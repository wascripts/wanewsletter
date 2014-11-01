<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if (!defined('IN_WA_FORM') && !defined('IN_SUBSCRIBE')) {
	exit('<b>No hacking</b>');
}

define('IN_NEWSLETTER', true);

//
// Compatibilité avec les version < 2.3.x
//
if (!defined('WA_ROOTDIR')) {
	if (!isset($waroot)) {
		exit("Le répertoire de Wanewsletter n'est pas défini!");
	}

	define('WA_ROOTDIR', rtrim($waroot, '/'));
}

$default_error_reporting = error_reporting();

require WA_ROOTDIR . '/includes/common.inc.php';
require WA_ROOTDIR . '/includes/functions.validate.php';

//
// Initialisation de la connexion à la base de données et récupération de la configuration
//
$db = WaDatabase($dsn);
$nl_config = wa_get_config();

if (!empty($language) && validate_lang($language)) {
	load_settings(array('admin_lang' => $language));
}
else {
	load_settings();
}

$action  = (!empty($_REQUEST['action'])) ? trim($_REQUEST['action']) : '';
$email   = (!empty($_REQUEST['email'])) ? trim($_REQUEST['email']) : '';
$format  = (isset($_REQUEST['format'])) ? intval($_REQUEST['format']) : 0;
$liste   = (isset($_REQUEST['liste'])) ? intval($_REQUEST['liste']) : 0;
$message = '';
$code    = '';

if (empty($action) && preg_match('/([a-z0-9]{20})(?:&|$)/i', $_SERVER['QUERY_STRING'], $m)) {
	$code = $m[1];
}

//
// Compatibilité avec les version < 2.3.x
//
else if (!empty($action) && !empty($email) && strlen($code) == 32) {
	$code = substr($code, 0, 20);
}

if (!empty($action) || !empty($code)) {
	//
	// Purge des éventuelles inscriptions dépassées
	// pour parer au cas d'une réinscription
	//
	purge_liste();

	if (!empty($action)) {
		if (in_array($action, array('inscription', 'setformat', 'desinscription'))) {
			$sql = "SELECT liste_id, liste_format, sender_email, liste_alias, limitevalidate,
					liste_name, form_url, return_email, liste_sig, use_cron, confirm_subscribe
				FROM " . LISTE_TABLE . "
				WHERE liste_id = " .  $liste;
			$result = $db->query($sql);

			if ($listdata = $result->fetch()) {
				$wanewsletter = new Wanewsletter($listdata);
				$wanewsletter->message =& $message;
				$wanewsletter->do_action($action, $email, $format);
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
		$wanewsletter = new Wanewsletter();
		$wanewsletter->message =& $message;
		$wanewsletter->check_code($code);
	}
}

if (defined('IN_WA_FORM')) {
	//
	// On réactive le gestionnaire d'erreur précédent
	//
	@restore_error_handler();

	// Si besoin, conversion du message vers le charset demandé
	if (!empty($textCharset)) {
		$message = iconv('UTF-8', $textCharset, $message);
	}

	echo nl2br($message);
}

//
// remise des paramêtres par défaut
//
error_reporting($default_error_reporting);
