<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

define('IN_NEWSLETTER', true);
define('IN_CRON',       true);

require '../includes/common.inc.php';

//
// Initialisation de la connexion à la base de données et récupération de la configuration
//
$db = WaDatabase($dsn);
$nl_config = wa_get_config();

load_settings();

$mode     = (!empty($_REQUEST['mode'])) ? trim($_REQUEST['mode']) : '';
$liste_id = (!empty($_REQUEST['liste'])) ? intval($_REQUEST['liste']) : 0;

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
	@set_time_limit(1200);

	if ($mode == 'send') {
		require WA_ROOTDIR . '/includes/engine_send.php';

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
		$message = launch_sending($listdata, $logdata);

		$output->displayMessage($message);
	}
	else if ($mode == 'validate') {
		require WA_ROOTDIR . '/includes/functions.validate.php';
		require WA_ROOTDIR . '/includes/functions.stats.php';

		$limit_security = 100; // nombre maximal d'emails dont le script doit s'occuper à chaque appel

		$wan = new Wanewsletter($listdata);
		$pop = new PopClient();
		$pop->options(array(
			'starttls' => ($pop_tls == WA_SECURITY_STARTTLS)
		));

		try {
			if (!$pop->connect(
				($listdata['pop_tls'] == WA_SECURITY_FULL_TLS ? 'tls://' : '') . $listdata['pop_host'],
				$listdata['pop_port'],
				$listdata['pop_user'],
				$listdata['pop_pass']
			)) {
				throw new Exception(sprintf("POP server response: '%s'", $pop->responseData));
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

			$pseudo = (isset($m[1])) ? strip_tags(trim($m[1])) : '';
			$email  = trim($m[2]);

			if (!isset($headers['to']) || !stristr($headers['to'], $wan->liste_email)) {
				continue;
			}

			if (!isset($headers['subject'])) {
				continue;
			}

			$action = mb_strtolower(trim($headers['subject']));

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
			// Compatibilité avec versions < 2.3
			if (strlen($code) == 32) {
				$code = substr($code, 0, 20);
			}

			if (!empty($code) && ($action =='confirmation' || $action == 'desinscription')) {
				if (empty($headers['date']) || intval($time = strtotime($headers['date'])) > 0) {
					$time = time();
				}

				$wan->check_code($code, $time);
			}
			else if (in_array($action, array('inscription','setformat','desinscription'))) {
				$wan->do_action($action, $email);
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
