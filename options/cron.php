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

if ($listdata = $result->fetch()) {
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
		 // on récupère le dernier log en statut d'envoi
		$sql = "SELECT log_id, log_subject, log_body_text, log_body_html, log_status
			FROM " . LOG_TABLE . "
			WHERE liste_id = $listdata[liste_id]
				AND log_status = " . STATUS_STANDBY . "
			LIMIT 1 OFFSET 0";
		$result = $db->query($sql);

		if (!($logdata = $result->fetch())) {
			$output->displayMessage('No_log_to_send');
		}

		$sql = "SELECT jf.file_id, jf.file_real_name, jf.file_physical_name, jf.file_size, jf.file_mimetype
			FROM " . JOINED_FILES_TABLE . " AS jf
				INNER JOIN " . LOG_FILES_TABLE . " AS lf ON lf.file_id = jf.file_id
				INNER JOIN " . LOG_TABLE . " AS l ON l.log_id = lf.log_id
					AND l.liste_id = $listdata[liste_id]
					AND l.log_id   = $logdata[log_id]
			ORDER BY jf.file_real_name ASC";
		$result = $db->query($sql);

		$logdata['joined_files'] = $result->fetchAll();

		//
		// On lance l'envoi
		//
		$sender = new Sender($listdata, $logdata);
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

		$output->displayMessage($message);
	}
	else if ($mode == 'validate') {
		require 'includes/functions.stats.php';

		$limit_security = 100; // nombre maximal d'emails dont le script doit s'occuper à chaque appel

		$sub = new Subscription($listdata);
		$pop = new PopClient();
		$pop->options([
			'starttls' => ($listdata['pop_tls'] == SECURITY_STARTTLS)
		]);

		try {
			$server = ($listdata['pop_tls'] == SECURITY_FULL_TLS) ? 'tls://%s:%d' : '%s:%d';
			$server = sprintf($server, $listdata['pop_host'], $listdata['pop_port']);

			if (!$pop->connect($server, $listdata['pop_user'], $listdata['pop_pass'])) {
				throw new Exception(sprintf(
					"Failed to connect to POP server (%s)",
					$pop->responseData
				));
			}
		}
		catch (Exception $e) {
			trigger_error(sprintf($lang['Message']['bad_pop_param'], $e->getMessage()), E_USER_ERROR);
		}

		$cpt = 0;
		$total    = $pop->stat_box();
		$mail_box = $pop->list_mail();

		foreach ($mail_box as $mail_id => $mail_size) {
			$headers = $pop->parse_headers($mail_id);

			if (!isset($headers['from']) || !preg_match('/^(?:"?([^"]*?)"?)?[ ]*(?:<)?([^> ]+)(?:>)?$/i', $headers['from'], $m)) {
				continue;
			}

			$pseudo = (isset($m[1])) ? trim(strip_tags(u::filter($m[1]))) : '';
			$email  = trim($m[2]);

			if (!isset($headers['to']) || !stristr($headers['to'], $sub->liste_email)) {
				continue;
			}

			if (!isset($headers['subject'])) {
				continue;
			}

			$action = mb_strtolower(trim(u::filter($headers['subject'])));

			switch ($action) {
				case 'désinscription':
				case 'unsubscribe':
					$action = 'desinscription';
					break;
				case 'subscribe':
					$action = 'inscription';
					break;
				case 'confirmation':
				case 'setformat':
					break;
			}

			$code = $pop->contents[$mail_id]['message'];

			if (!empty($code) && ($action =='confirmation' || $action == 'desinscription')) {
				if (empty($headers['date']) || !($time = strtotime($headers['date']))) {
					$time = time();
				}

				$sub->check_code($code, $time);
			}
			else if (in_array($action, ['inscription','setformat','desinscription'])) {
				$sub->do_action($action, $email, null, $pseudo);
			}

			//
			// On supprime l'email maintenant devenu inutile
			//
			$pop->delete_mail($mail_id);

			$cpt++;

			if ($cpt > $limit_security) {
				break;
			}
		}//end for

		$pop->quit();

		$output->displayMessage('Success_operation');
	}
	else {
		trigger_error('No valid mode specified', E_USER_ERROR);
	}
}
else {
	trigger_error('Unknown_list', E_USER_ERROR);
}
