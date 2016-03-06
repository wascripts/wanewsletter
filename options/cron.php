<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

use Patchwork\Utf8 as u;

require '../includes/common.inc.php';

//
// Initialisation de la connexion à la base de données et récupération de la configuration
//
$db = WaDatabase($dsn);
$nl_config = wa_get_config();

load_settings();

$mode     = filter_input(INPUT_GET, 'mode');
$liste_id = (int) filter_input(INPUT_GET, 'liste', FILTER_VALIDATE_INT);

$sql = 'SELECT liste_id, liste_format, sender_email, liste_alias, limitevalidate,
		liste_name, form_url, return_email, liste_sig, use_cron, confirm_subscribe,
		pop_host, pop_port, pop_user, pop_pass, pop_tls
	FROM ' . LISTE_TABLE . '
	WHERE liste_id = ' . $liste_id;
$result = $db->query($sql);

if (!($listdata = $result->fetch())) {
	trigger_error('Unknown_list', E_USER_ERROR);
}

//
// On règle le script pour ignorer une déconnexion du client et
// poursuivre l'envoi du flot d'emails jusqu'à son terme.
//
@ignore_user_abort(true);

//
// On augmente également le temps d'exécution maximal du script.
//
// Certains hébergeurs désactivent pour des raisons évidentes cette fonction
// Si c'est votre cas, vous êtes mal barré
//
@set_time_limit(3600);

if ($mode == 'send') {
	//
	// On lance l'envoi
	//
	$sender = new Sender($listdata);
	$sender->lock();
	$sender->registerHook('post-send', function () { fake_header(); });

	$result = $sender->process();

	if ($result['total_to_send'] > 0) {
		$message = sprintf($lang['Message']['Success_send'],
			$nl_config['sending_limit'],
			$result['total_sent'],
			($result['total_sent'] + $result['total_to_send'])
		);
	}
	else {
		$message = sprintf($lang['Message']['Success_send_finish'], $result['total_sent']);
	}

	$output->message($message);
}
else if ($mode == 'validate') {
	$message = process_mail_action($listdata);
	$output->message($message);
}
