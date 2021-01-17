<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@webnaute.net>
 * @link      http://dev.webnaute.net/wanewsletter/
 * @copyright 2002-2021 Aurélien Maille
 * @license   https://www.gnu.org/licenses/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

use Patchwork\Utf8 as u;

//
// Vous pouvez, grâce à cette constante, désactiver la vérification de
// l'existence du tag {LINKS} dans les lettres au moment de l'envoi.
//
// N'oubliez pas que permettre aux abonnés à vos listes de se désinscrire
// simplement et rapidement si tel est leur souhait est une obligation légale (1).
// Aussi, et sauf cas particuliers, vous ne devriez pas retirer ces liens
// des lettres que vous envoyez, et donc changer la valeur de cette constante.
//
// (1) Au moins en France, en application de l'article 22 de la loi n° 2004-575
// du 21 juin 2004 pour la confiance dans l'économie numérique (LCEN).
//
const DISABLE_CHECK_LINKS = false;

require './start.inc.php';

if (!$_SESSION['liste']) {
	$output->header();
	$output->listbox(Auth::VIEW)->pparse();
	$output->footer();
}

if (!$auth->check(Auth::SEND, $_SESSION['liste'])) {
	http_response_code(401);
	$output->message('Not_auth_send');
}

$error = false;

$listdata = $auth->getLists(Auth::SEND)[$_SESSION['liste']];
$logdata  = [];
$logdata['liste_id']      = $_SESSION['liste'];
$logdata['joined_files']  = null;

$logdata['log_id'] = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!is_int($logdata['log_id'])) {
	$logdata['log_id'] = (int) filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
}

$logdata['log_subject']   = trim(u::filter_input(INPUT_POST, 'subject'));
$logdata['log_body_text'] = trim(u::filter_input(INPUT_POST, 'body_text'));
$logdata['log_body_html'] = trim(u::filter_input(INPUT_POST, 'body_html'));
$logdata['log_status']    = filter_input(INPUT_POST, 'log_status', FILTER_VALIDATE_INT);
$logdata['log_date']      = filter_input(INPUT_POST, 'log_date', FILTER_VALIDATE_INT, [
	'options' => ['default' => -1]
]);

if (!is_int($logdata['log_status']) || !in_array($logdata['log_status'], [STATUS_MODEL, STATUS_WRITING])) {
	$logdata['log_status'] = STATUS_WRITING;
}

$logdata['prev_status'] = filter_input(INPUT_POST, 'prev_status', FILTER_VALIDATE_INT);
if (!is_int($logdata['prev_status'])) {
	$logdata['prev_status'] = $logdata['log_status'];
}

if (isset($_POST['cancel'])) {
	http_redirect('envoi.php?mode=load&id=' . $logdata['log_id']);
}

$mode = filter_input(INPUT_GET, 'mode');
foreach (['test', 'presend', 'save', 'delete', 'attach', 'unattach'] as $varname) {
	if (isset($_POST[$varname])) {
		$mode = $varname;
		break;
	}
}

//
// Fonction de callback utilisée pour l'appel à preg_replace_callback() plus bas
//
$replace_include = function ($m) use ($mode, &$lang, &$error, $output) {
	preg_match_all('/\\s+([a-z_:][a-z0-9_:.-]*)\\s?=\\s?(["\'])(.+?)(?<!\\\\)(?:\\\\\\\\)*\\2/i',
		$m[1], $attrs, PREG_SET_ORDER);

	$resource = null;
	$tds = false;
	foreach ($attrs as $attr) {
		switch ($attr[1]) {
			case 'src':
				$resource = stripslashes($attr[3]);
				break;
			case 'tds' && $attr[3] == 'true':
			case 'now' && $attr[3] == 'true':
				$tds = true;
				break;
		}
	}

	if (is_null($resource) || (!$tds && $mode != 'presend')) {
		return $m[0];
	}

	try {
		$result = wan_get_contents($resource);
	}
	catch (Exception $e) {
		$error = true;
		$output->warn($e->getMessage());
		return $m[0];
	}

	return convert_encoding($result['data'], $result['charset']);
};

$regexp = '/<\\?inclu[dr]e(\\s+[^>]+)\\?>/i';
foreach (['log_body_text', 'log_body_html'] as $key) {
	if (!empty($logdata[$key])) {
		$logdata[$key] = preg_replace_callback($regexp, $replace_include, $logdata[$key]);
	}
}

unset($replace_include, $regexp);

//
// Sauvegarde du brouillon/modèle
//
if (in_array($mode, ['save','test','presend','attach','unattach'])) {
	// Au cas où la newsletter a le statut WRITING mais que son précédent
	// statut était MODEL, on la duplique pour garder intact le modèle,
	// et on duplique aussi les fichiers joints.
	$duplicate_file = false;
	if ($logdata['log_status'] == STATUS_WRITING && $logdata['prev_status'] == STATUS_MODEL) {
		$duplicate_file    = true;
		$prev_log_id       = $logdata['log_id'];
		$logdata['log_id'] = 0;
	}

	$logdata['log_date'] = time();

	$keys = ['liste_id','log_subject','log_body_text','log_body_html','log_date','log_status'];
	$keys = array_fill_keys($keys, null);
	$sqldata = array_intersect_key(array_replace($keys, $logdata), $keys);

	if (!$logdata['log_id']) {
		$db->insert(LOG_TABLE, $sqldata);
		$logdata['log_id'] = $db->lastInsertId();
	}
	else {
		$sql_where = ['log_id' => $logdata['log_id'], 'liste_id' => $logdata['liste_id']];
		$db->update(LOG_TABLE, $sqldata, $sql_where);
		unset($sql_where);
	}

	// Duplication des entrées pour les fichiers joints
	if ($duplicate_file) {
		$sql = "INSERT INTO %1\$s (log_id, file_id)
			SELECT '%2\$d', f2.file_id
			FROM %1\$s AS f2
			WHERE f2.log_id = %3\$d";
		$sql = sprintf($sql, LOG_FILES_TABLE, $logdata['log_id'], $prev_log_id);
		$result = $db->query($sql);
	}

	$logdata['prev_status'] = $logdata['log_status'];

	unset($keys, $sqldata);

	if ($mode == 'save') {
		$output->notice('log_saved');
	}
}

switch ($mode) {
	case 'cancel':
		if (isset($_POST['confirm'])) {
			$sql = "SELECT log_id, liste_id, log_status
				FROM " . LOG_TABLE . "
				WHERE log_id = " . $logdata['log_id'];
			$result = $db->query($sql);

			if (!($logdata = $result->fetch()) || $logdata['log_status'] != STATUS_SENDING) {
				http_redirect('envoi.php');
			}

			$sql = "SELECT COUNT(send) AS sent
				FROM " . ABO_LISTE_TABLE . "
				WHERE liste_id = $logdata[liste_id] AND send = 1";
			$result = $db->query($sql);
			$sent = $result->column('sent');

			//
			// Suppression du fichier lock correspondant s'il existe
			// et qu'aucun envoi n'est en cours.
			//
			$lockfile = sprintf('%s/liste-%d.lock', $nl_config['tmp_dir'], $logdata['liste_id']);

			$fp = fopen($lockfile, (file_exists($lockfile) ? 'r+' : 'w'));
			if (!flock($fp, LOCK_EX|LOCK_NB)) {
				fclose($fp);
				$output->message('List_is_busy');
			}

			$db->beginTransaction();

			$sql = "UPDATE " . LOG_TABLE . "
				SET log_status  = " . STATUS_SENT . ",
					log_numdest = $sent
				WHERE log_id = " . $logdata['log_id'];
			$db->query($sql);

			$sql = "UPDATE " . ABO_LISTE_TABLE . "
				SET send = 0
				WHERE liste_id = " . $logdata['liste_id'];
			$db->query($sql);

			$sql = "UPDATE " . LISTE_TABLE . "
				SET liste_numlogs = liste_numlogs + 1
				WHERE liste_id = " . $logdata['liste_id'];
			$db->query($sql);

			$db->commit();

			flock($fp, LOCK_UN);
			fclose($fp);
			unlink($lockfile);

			$output->message('Send_canceled');
		}
		else {
			$output->addHiddenField('id', $logdata['log_id']);

			$output->header();

			$template = new Template('confirm_body.tpl');

			$template->assign([
				'L_CONFIRM' => $lang['Title']['confirm'],

				'TEXTE' => $lang['Cancel_send_log'],
				'L_YES' => $lang['Yes'],
				'L_NO'  => $lang['No'],

				'S_HIDDEN_FIELDS' => $output->getHiddenFields(),
				'U_FORM' => 'envoi.php?mode=cancel'
			]);

			$template->pparse();
			$output->footer();
		}
		break;

	case 'progress':
		if (!$auth->check(Auth::SEND, $listdata['liste_id'])) {
			http_response_code(401);
			$output->message('Not_auth_send');
		}

		$liste_ids = array_column($auth->getLists(Auth::SEND), 'liste_id');
		$liste_ids = implode(', ', $liste_ids);

		$sql = "SELECT log_id, log_subject, log_body_text, log_body_html,
				log_status, log_date, liste_id
			FROM %s
			WHERE liste_id IN(%s)
				AND log_id = %d
				AND log_status = %d";
		$sql = sprintf($sql, LOG_TABLE, $liste_ids, $logdata['log_id'], STATUS_SENDING);
		$result = $db->query($sql);

		if (!($logdata = $result->fetch())) {
			$output->redirect('envoi.php?mode=send', 4);
			$output->addLine($lang['Message']['No_log_found']);
			$output->addLine($lang['Click_return_back'], './envoi.php?mode=send');
			$output->message();
		}

		$logdata['joined_files'] = get_joined_files($logdata);

		//
		// Envoi des emails
		//

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

		if (time() > ($logdata['log_date'] + $nl_config['sending_delay'])) {
			//
			// On lance l'envoi
			//
			$sender = new Sender($listdata, $logdata);
			$sender->registerHook('post-send', function () { fake_header(); });
			$sender->lock();
			$result = $sender->process();
		}
		else {
			$sql = "SELECT COUNT(send) AS num, send
				FROM %s
				WHERE liste_id = %d
					AND confirmed = %d
				GROUP BY send";
			$sql = sprintf($sql, ABO_LISTE_TABLE, $logdata['liste_id'], SUBSCRIBE_CONFIRMED);
			$result = $db->query($sql);

			$total_sent = $total_to_send = 0;
			while ($row = $result->fetch()) {
				if ($row['send'] == 1) {
					$total_sent = $row['num'];
				}
				else {
					$total_to_send = $row['num'];
				}
			}

			$result = [];
			$result['total_sent']    = $total_sent;
			$result['total_to_send'] = $total_to_send;
		}

		if ($result['total_to_send'] > 0) {
			$total = ($result['total_sent'] + $result['total_to_send']);

			if ($output instanceof Output\Json) {
				$message = sprintf($lang['Next_sending_delay'], $nl_config['sending_delay']);
				$result['percent'] = wa_number_format(round((($result['total_sent'] / $total) * 100), 2));
				$result['next_sending_ts'] = (time() + $nl_config['sending_delay']);
				$output->addParams($result);
			}
			else {
				$progress_url = sprintf('envoi.php?mode=progress&amp;id=%d', $logdata['log_id']);

				$message = sprintf($lang['Message']['Success_send'],
					$nl_config['sending_limit'],
					$result['total_sent'],
					$total
				);
				$message .= '<br /><br />';
				$message .= sprintf($lang['Click_resend'],
					sprintf('<a href="%s">', $progress_url),
					'</a>'
				);
			}
		}
		else {
			if ($output instanceof Output\Json) {
				$result['percent'] = 100;
				$output->addParams($result);
			}

			$message = sprintf($lang['Message']['Success_send_finish'], $result['total_sent']);
		}

		$output->message($message);
		break;

	case 'send':
		if (!$auth->check(Auth::SEND, $listdata['liste_id'])) {
			http_response_code(401);
			$output->message('Not_auth_send');
		}

		$liste_ids = array_column($auth->getLists(Auth::SEND), 'liste_id');
		$liste_ids = implode(', ', $liste_ids);

		if ($logdata['log_id']) {
			$sql = "SELECT log_id, log_subject, log_body_text, log_body_html,
					log_status, log_date, liste_id
				FROM %s
				WHERE liste_id IN(%s)
					AND log_id = %d
					AND log_status = %d";
			$sql = sprintf($sql, LOG_TABLE, $liste_ids, $logdata['log_id'], STATUS_SENDING);
			$result = $db->query($sql);

			if (!($logdata = $result->fetch())) {
				$output->redirect('envoi.php?mode=send', 4);
				$output->addLine($lang['Message']['No_log_found']);
				$output->addLine($lang['Click_return_back'], './envoi.php?mode=send');
				$output->message();
			}

			$sql = "SELECT COUNT(send) AS num, send
				FROM %s
				WHERE liste_id = %d
					AND confirmed = %d
				GROUP BY send";
			$sql = sprintf($sql, ABO_LISTE_TABLE, $logdata['liste_id'], SUBSCRIBE_CONFIRMED);
			$result = $db->query($sql);

			$total_sent = $total_to_send = 0;
			while ($row = $result->fetch()) {
				if ($row['send'] == 1) {
					$total_sent = $row['num'];
				}
				else {
					$total_to_send = $row['num'];
				}
			}

			if (($total_sent + $total_to_send) == 0) {
				$output->message('No_subscribers');
			}

			$percent = wa_number_format(round((($total_sent / ($total_sent + $total_to_send)) * 100), 2));

			$output->header();

			$template = new Template('sending_body.tpl');

			if (time() > ($logdata['log_date'] + $nl_config['sending_delay'])) {
				$next_sending_ts = time();
				$message = $lang['Process_sending'];
			}
			else {
				$next_sending_ts = ($logdata['log_date'] + $nl_config['sending_delay']);
				$message = sprintf($lang['Next_sending_delay'], $next_sending_ts - time());
			}

			$template->assign([
				'L_TITLE'       => $lang['List_send'],
				'L_PROCESS'     => $lang['Process_sending'],
				'L_SENDING_NL'  => sprintf($lang['Sending_newsletter'],
					htmlspecialchars($logdata['log_subject'], ENT_NOQUOTES)
				),

				'MESSAGE'         => $message,
				'NEXT_SENDING_TS' => $next_sending_ts,
				'LOG_ID'          => $logdata['log_id'],
				'TOTAL'           => ($total_sent + $total_to_send),
				'TOTAL_SENT'      => $total_sent,
				'SENT_PERCENT'    => $percent
			]);

			$template->pparse();
			$output->footer();
		}

		// Pas d'identifiant de lettre fourni, on affiche la liste des
		// envois en cours.

		$sql = "SELECT COUNT(send) AS num, send, liste_id
			FROM %s
			WHERE liste_id IN(%s)
				AND confirmed = %d
			GROUP BY liste_id, send";
		$sql = sprintf($sql, ABO_LISTE_TABLE, $liste_ids, SUBSCRIBE_CONFIRMED);
		$result = $db->query($sql);

		$data = [];
		while ($row = $result->fetch()) {
			if (!isset($data[$row['liste_id']])) {
				$data[$row['liste_id']] = [0, 0, 't' => 0];
			}
			$data[$row['liste_id']][$row['send']] = $row['num'];
			$data[$row['liste_id']]['t'] += $row['num'];
		}

		$sql = "SELECT log_id, log_subject, log_status, liste_id
			FROM %s
			WHERE liste_id IN(%s)
				AND log_status = %d
			ORDER BY log_subject ASC";
		$sql = sprintf($sql, LOG_TABLE, $liste_ids, STATUS_SENDING);
		$result = $db->query($sql);

		if (!($row = $result->fetch())) {
			$output->redirect('envoi.php', 4);
			$output->addLine($lang['Message']['No_log_to_send']);
			$output->addLine($lang['Click_return_form'], './envoi.php');
			$output->message();
		}

		$output->header();

		$template = new Template('send_progress_body.tpl');

		$template->assign([
			'L_TITLE'       => $lang['List_send'],
			'L_SUBJECT'     => $lang['Log_subject'],
			'L_DONE'        => $lang['Done'],
			'L_CREATE_LOG'  => $lang['Create_log'],
			'L_LOAD_LOG'    => $lang['Load_log']
		]);

		do {
			if (isset($data[$row['liste_id']])) {
				$percent = round((($data[$row['liste_id']][1] / $data[$row['liste_id']]['t']) * 100), 2);
				$percent = wa_number_format($percent);

				$template->assignToBlock('logrow', [
					'L_DO_SEND'     => $lang['Restart_send'],
					'L_CANCEL_SEND' => $lang['Cancel_send'],
					'LOG_ID'        => $row['log_id'],
					'LOG_SUBJECT'   => htmlspecialchars($row['log_subject'], ENT_NOQUOTES),
					'TOTAL'         => $data[$row['liste_id']]['t'],
					'TOTAL_SENT'    => $data[$row['liste_id']][1],
					'SENT_PERCENT'  => $percent
				]);
			}
			else {
				$template->assignToBlock('logrow2', [
					'L_DO_SEND'      => $lang['Restart_send'],
					'L_CANCEL_SEND'  => $lang['Cancel_send'],
					'LOG_ID'         => $row['log_id'],
					'LOG_SUBJECT'    => htmlspecialchars($row['log_subject'], ENT_NOQUOTES),
					'NO_SUBSCRIBERS' => $lang['No_registered_subscriber']
				]);
			}
		}
		while ($row = $result->fetch());

		$template->pparse();
		$output->footer();
		break;

	//
	// Chargement d'un log dont on veut reprendre l'écriture ou l'envoi
	//
	case 'load':
		$body_text_url = trim(filter_input(INPUT_POST, 'body_text_url'));
		$body_html_url = trim(filter_input(INPUT_POST, 'body_html_url'));

		if (isset($_POST['submit']) || $logdata['log_id']) {
			if ($body_text_url || $body_html_url) {
				if ($body_text_url) {
					try {
						$result = wan_get_contents($body_text_url);
					}
					catch (Exception $e) {
						$output->addLine($e->getMessage());
						$output->addLine($lang['Click_return_back'], './envoi.php?mode=load');
						$output->message();
					}

					$logdata['log_body_text'] = convert_encoding($result['data'], $result['charset']);
				}

				if ($body_html_url) {
					try {
						$result = wan_get_contents($body_html_url);
					}
					catch (Exception $e) {
						$output->addLine($e->getMessage());
						$output->addLine($lang['Click_return_back'], './envoi.php?mode=load');
						$output->message();
					}

					if (preg_match('/<head[^>]*>(.+?)<\/head>/is', $result['data'], $match_head)) {
						if (empty($result['charset'])) {
							preg_match_all('/<meta[^>]+>/si', $match_head[1], $match_meta, PREG_SET_ORDER);

							foreach ($match_meta as $meta) {
								if (preg_match('/http-equiv\s*=\s*("|\')Content-Type\\1/si', $meta[0])
									&& preg_match('/content\s*=\s*("|\').+?;\s*charset\s*=\s*([a-z][a-z0-9._-]*)\\1/si', $meta[0], $match)
								) {
									$result['charset'] = $match[2];
								}
								// HTML5 style <meta charset="<character_set>">
								else if (preg_match('/charset\s*=\s*("|\')([a-z][a-z0-9._-]*)\\1/si', $meta[0], $match)) {
									$result['charset'] = $match[2];
								}
							}
						}

						if (preg_match('/<title[^>]*>(.+?)<\/title>/is', $match_head[1], $match)) {
							$logdata['log_subject'] = convert_encoding(trim($match[1]), $result['charset']);
							$logdata['log_subject'] = html_entity_decode($logdata['log_subject']);
						}

						if (preg_match('#^https?://#', $body_html_url)) {
							$URL = substr($body_html_url, 0, strrpos($body_html_url, '/'));
							$result['data'] = preg_replace('/<(head[^>]*)>/si',
								"<\\1>\n<base href=\"" . htmlspecialchars($URL) . "/\">", $result['data']);
						}
					}

					$logdata['log_body_html'] = convert_encoding($result['data'], $result['charset']);
				}
			}
			else {
				$sql = "SELECT log_id, log_subject, log_body_text, log_body_html, log_status, log_date, liste_id
					FROM " . LOG_TABLE . "
					WHERE liste_id = $listdata[liste_id]
						AND log_id = $logdata[log_id]
						AND (log_status = " . STATUS_WRITING . " OR log_status = " . STATUS_MODEL . ")";
				$result = $db->query($sql);

				if (!($logdata = $result->fetch())) {
					$output->redirect('envoi.php?mode=load', 4);
					$output->addLine($lang['Message']['log_not_exists']);
					$output->addLine($lang['Click_return_back'], './envoi.php?mode=load');
					$output->message();
				}

				$logdata['prev_status'] = $logdata['log_status'];
			}
		}
		else {
			$sql = "SELECT log_id, log_subject, log_status, log_date
				FROM " . LOG_TABLE . "
				WHERE liste_id = $listdata[liste_id]
					AND (log_status = " . STATUS_WRITING . " OR log_status = " . STATUS_MODEL . ")
				ORDER BY log_date DESC";
			$result = $db->query($sql);

			$output->header();

			$template = new Template('select_log_body.tpl');

			if ($row = $result->fetch()) {
				$log_box = '<select name="id" class="logList">';

				do {
					if ($row['log_status'] == STATUS_MODEL) {
						$status = '[' . $lang['Model'] . ']';
						$class  = 'class="model"';
					}
					else {
						$status = '';
						$class  = '';
					}

					if (!$row['log_subject']) {
						$row['log_subject'] = "Untitled";
					}

					$log_box .= sprintf(
						'<option value="%d" %s>%s – [%s] %s</option>',
						$row['log_id'],
						$class,
						htmlspecialchars($row['log_subject']),
						convert_time('d F Y', $row['log_date']),
						$status
					);
					$log_box .= "\n";
				}
				while ($row = $result->fetch());

				$log_box .= '</select>';

				$template->assignToBlock('load_draft', [
					'L_SELECT_LOG' => $lang['Select_log_to_load'],
					'LOG_BOX'      => $log_box
				]);

				$template->assignToBlock('script_load_by_url', [
					'L_FROM_AN_URL' => str_replace('\'', '\\\'', $lang['From_an_URL'])
				]);
			}

			$template->assign([
				'L_TITLE'        => $lang['Title']['select'],
				'L_VALID_BUTTON' => $lang['Button']['valid'],
				'L_EXPLAIN_LOAD' => $lang['Explain']['load']
			]);

			switch ($listdata['liste_format']) {
				case FORMAT_TEXT:
					$bloc_name = 'load_text_by_url';
					break;
				case FORMAT_HTML:
					$bloc_name = 'load_html_by_url';
					break;
				default:
					$bloc_name = 'load_multi_by_url';
					break;
			}

			$template->assignToBlock($bloc_name, [
				'L_LOAD_BY_URL' => $lang['Load_by_URL'],
				'L_FORMAT_TEXT' => $lang['Format_text'],
				'L_FORMAT_HTML' => $lang['Format_html'],

				'BODY_TEXT_URL' => htmlspecialchars($body_text_url),
				'BODY_HTML_URL' => htmlspecialchars($body_html_url)
			]);

			$template->pparse();
			$output->footer();
		}
		break;

	//
	// Suppression d'une newsletter
	//
	case 'delete':
		if (!$logdata['log_id']) {
			$output->redirect('envoi.php', 4);
			$output->addLine($lang['Message']['No_log_id']);
			$output->addLine($lang['Click_return_back'], './envoi.php');
			$output->message();
		}

		if (isset($_POST['confirm'])) {
			$db->beginTransaction();

			$sql = 'DELETE FROM ' . LOG_TABLE . '
				WHERE log_id = ' . $logdata['log_id'];
			$db->query($sql);

			$attach = new Attach();
			$attach->deleteFiles($logdata['log_id']);

			$db->commit();

			//
			// Optimisation des tables
			//
			$db->vacuum([LOG_TABLE, LOG_FILES_TABLE, JOINED_FILES_TABLE]);

			$output->redirect('envoi.php', 4);
			$output->addLine($lang['Message']['log_deleted']);
			$output->addLine($lang['Click_return_back'], './envoi.php');
			$output->message();
		}
		else {
			$output->addHiddenField('id',   $logdata['log_id']);

			$output->header();

			$template = new Template('confirm_body.tpl');

			$template->assign([
				'L_CONFIRM' => $lang['Title']['confirm'],

				'TEXTE' => $lang['Delete_log'],
				'L_YES' => $lang['Yes'],
				'L_NO'  => $lang['No'],

				'S_HIDDEN_FIELDS' => $output->getHiddenFields(),
				'U_FORM' => 'envoi.php?mode=delete'
			]);

			$template->pparse();
			$output->footer();
		}
		break;

	case 'test':
	case 'presend':
		if (!$logdata['log_subject']) {
			$error = true;
			$output->warn('Subject_empty');
		}

		if ($listdata['liste_format'] != FORMAT_HTML) {
			if (!$logdata['log_body_text']) {
				$error = true;
				$output->warn('Body_empty');
			}
			else if (!DISABLE_CHECK_LINKS && $listdata['liste_public']
				&& !strstr($logdata['log_body_text'], '{LINKS}')
			) {
				$error = true;
				$output->warn('No_links_in_body');
			}
		}

		if ($listdata['liste_format'] != FORMAT_TEXT) {
			if (!$logdata['log_body_html']) {
				$error = true;
				$output->warn('Body_empty');
			}
			else if (!DISABLE_CHECK_LINKS && $listdata['liste_public']
				&& !strstr($logdata['log_body_html'], '{LINKS}')
			) {
				$error = true;
				$output->warn('No_links_in_body');
			}

			$sql = "SELECT jf.file_real_name
				FROM %s AS jf
					INNER JOIN %s AS lf ON lf.file_id = jf.file_id
					INNER JOIN %s AS l ON l.log_id = lf.log_id
						AND l.liste_id = %d
						AND l.log_id = %d";
			$sql = sprintf($sql, JOINED_FILES_TABLE, LOG_FILES_TABLE, LOG_TABLE,
				$listdata['liste_id'],
				$logdata['log_id']
			);
			$result = $db->query($sql);

			$files = $files_error = [];
			while ($row = $result->fetch()) {
				$files[] = $row['file_real_name'];
			}

			$total_cid = hasCidReferences($logdata['log_body_html'], $refs);

			for ($i = 0; $i < $total_cid; $i++) {
				if (!in_array($refs[$i], $files)) {
					$files_error[] = $refs[$i];
				}
			}

			if (count($files_error) > 0) {
				$error = true;
				$output->warn('Cid_error_in_body', implode(', ', $files_error));
			}
		}

		if ($mode == 'presend') {
			// Deux newsletters ne peuvent être simultanément en attente
			// d’envoi pour une même liste.
			$sql = "SELECT COUNT(*) AS test
				FROM %s
				WHERE liste_id = %d
					AND log_status = %d";
			$sql = sprintf($sql, LOG_TABLE, $logdata['liste_id'], STATUS_SENDING);
			$result = $db->query($sql);

			if ($result->column('test') > 0) {
				$error = true;
				$output->warn('Twice_sending');
			}

			// La liste doit comporter des abonnés...
			$sql = "SELECT COUNT(abo_id) AS test
				FROM %s
				WHERE liste_id = %d
					AND confirmed = %d";
			$sql = sprintf($sql, ABO_LISTE_TABLE, $logdata['liste_id'], SUBSCRIBE_CONFIRMED);
			$result = $db->query($sql);

			if ($result->column('test') == 0) {
				$error = true;
				$output->warn('No_subscribers');
			}
		}

		if (!$error) {
			if ($mode == 'test') {
				$supp_address = trim(filter_input(INPUT_POST, 'test_address'));
				$supp_address = array_unique(array_map('trim', explode(',', $supp_address)));
				$supp_address = array_filter($supp_address, function ($email) {
					return \Wamailer\Mailer::checkMailSyntax($email);
				});

				$logdata['joined_files'] = get_joined_files($logdata);

				if (count($supp_address) > 0) {
					$logdata['log_subject'] = '[test] '.$logdata['log_subject'];
					$sender = new Sender($listdata, $logdata);
					$sender->process($supp_address);

					$logdata['log_subject'] = substr($logdata['log_subject'], 7);// On retire la mention [test]
					$output->notice($lang['Test_send_finish']);
				}
				else {
					$error = true;
					$output->warn('Invalid_email');
				}
			}
			else {
				if ($logdata['log_status'] == STATUS_MODEL) {
					// Duplication de la newsletter
					$keys = ['liste_id','log_subject','log_body_text','log_body_html','log_date','log_status'];
					$keys = array_fill_keys($keys, null);
					$sqldata = array_intersect_key(array_replace($keys, $logdata), $keys);
					$sqldata['log_status'] = STATUS_SENDING;
					$sqldata['log_date']   = 0;

					$db->insert(LOG_TABLE, $sqldata);

					$prev_log_id = $logdata['log_id'];
					$logdata['log_id'] = $db->lastInsertId();

					// Duplication des entrées pour les fichiers joints
					$sql = "INSERT INTO %1\$s (log_id, file_id)
						SELECT '%2\$d', f2.file_id
						FROM %1\$s AS f2
						WHERE f2.log_id = %3\$d";
					$sql = sprintf($sql, LOG_FILES_TABLE, $logdata['log_id'], $prev_log_id);
					$result = $db->query($sql);
				}
				else {
					$sqldata = [];
					$sqldata['log_status'] = STATUS_SENDING;
					$sqldata['log_date']   = 0;
					$db->update(LOG_TABLE, $sqldata, ['log_id' => $logdata['log_id']]);
				}

				$message = sprintf($lang['Message']['log_ready'],
					htmlspecialchars($listdata['liste_name'])
				);
				$output->addLine($message);
				$output->addLine($lang['Click_start_send'], './envoi.php?mode=send&id=' . $logdata['log_id']);
				$output->message();
			}
		}
		break;

	case 'attach':
		if ($auth->check(Auth::ATTACH, $listdata['liste_id'])) {
			$attach  = new Attach();

			try {
				$file_id = (int) filter_input(INPUT_POST, 'fid', FILTER_VALIDATE_INT);
				if ($file_id) {
					// Ajout d’un fichier déjà existant.
					$file = $attach->useFile($logdata['log_id'], $file_id);
				}
				else {
					$local_file = trim(filter_input(INPUT_POST, 'local_file'));
					$join_file  = (!empty($_FILES['join_file'])) ? $_FILES['join_file'] : [];

					$file = $attach->addFile($logdata['log_id'], $local_file ?: $join_file);
				}

				$output->notice('Joined_file_added', $file['name']);
			}
			catch (Dblayer\Exception $e) {
				throw $e;
			}
			catch (Exception $e) {
				$output->warn($e->getMessage());
			}
		}
		break;

	case 'unattach':
		$file_ids = (array) filter_input(INPUT_POST, 'file_ids',
			FILTER_VALIDATE_INT,
			FILTER_REQUIRE_ARRAY
		);
		$file_ids = array_filter($file_ids);

		if ($auth->check(Auth::ATTACH, $listdata['liste_id']) && count($file_ids) > 0) {
			//
			// Suppression du fichier joint spécifié
			//
			$attach = new Attach();
			$attach->deleteFiles($logdata['log_id'], $file_ids);

			//
			// Optimisation des tables
			//
			$db->vacuum([LOG_FILES_TABLE, JOINED_FILES_TABLE]);

			if (count($file_ids) > 1) {
				$output->notice('Joined_files_removed');
			}
			else {
				$output->notice('Joined_file_removed');
			}
		}
		break;
}

$file_box = '';

//
// Récupération des fichiers joints de la liste
//
if ($auth->check(Auth::ATTACH, $listdata['liste_id'])) {
	$sql_where = '';
	if (!isset($logdata['joined_files'])) {
		$logdata['joined_files'] = get_joined_files($logdata);
	}

	if ($logdata['joined_files']) {
		$file_ids = array_column($logdata['joined_files'], 'file_id');
		$sql_where = "WHERE jf.file_id NOT IN(".implode(',', $file_ids).")";
	}

	//
	// On récupère les fichiers joints de la liste qui ne sont pas déjà liés
	// au brouillon en cours.
	//
	$sql = "SELECT jf.file_id, jf.file_real_name
		FROM " . JOINED_FILES_TABLE . " AS jf
			INNER JOIN " . LOG_FILES_TABLE . " AS lf ON lf.file_id = jf.file_id
			INNER JOIN " . LOG_TABLE . " AS l ON l.log_id = lf.log_id
				AND l.liste_id = $listdata[liste_id]
		$sql_where
		GROUP BY jf.file_id
		ORDER BY jf.file_real_name ASC";
	$result = $db->query($sql);

	while ($row = $result->fetch()) {
		$file_box .= sprintf("<option value=\"%d\">%s</option>\n\t",
			$row['file_id'],
			htmlspecialchars($row['file_real_name'])
		);
	}

	if ($file_box != '') {
		$file_box = '<select name="fid"><option value="0">' . $lang['File_on_server'] . '</option>' . $file_box . '</select>';
	}
}

$subject   = htmlspecialchars($logdata['log_subject']);
$body_text = htmlspecialchars($logdata['log_body_text'], ENT_NOQUOTES);
$body_html = htmlspecialchars($logdata['log_body_html'], ENT_NOQUOTES);

$max_filesize = get_max_filesize();

$output->addLink('subsection', './envoi.php?mode=load', $lang['Load_log']);
$output->addLink('subsection', './envoi.php?mode=send', $lang['List_send']);
$output->addScript($nl_config['path'] . 'templates/admin/editor.js');

if ($admindata['html_editor'] == HTML_EDITOR_YES) {
	$output->addScript($nl_config['path'] . 'vendor/tinymce/tinymce/tinymce.min.js');
}

$output->addHiddenField('id',          $logdata['log_id']);
$output->addHiddenField('prev_status', $logdata['prev_status']);
$output->addHiddenField('log_date',    $logdata['log_date']);

$output->header();

$template = new Template('send_body.tpl');

$template->assign([
	'L_EXPLAIN'               => nl2br(sprintf($lang['Explain']['send'],
		sprintf('<a href="%s">', wan_get_faq_url('external_data')),
		'</a>'
	)),
	'L_LOAD_LOG'              => $lang['Load_log'],
	'L_LIST_SEND'             => $lang['List_send'],
	'L_DEST'                  => $lang['Dest'],
	'L_SUBJECT'               => $lang['Log_subject'],
	'L_STATUS'                => $lang['Status'],
	'L_STATUS_WRITING'        => $lang['Status_writing'],
	'L_STATUS_MODEL'          => $lang['Status_model'],

	'L_SEND_BUTTON'           => $lang['Button']['send'],
	'L_SAVE_BUTTON'           => $lang['Button']['save'],
	'L_DELETE_BUTTON'         => $lang['Button']['delete'],
	'L_PREVIEW_BUTTON'        => str_replace('\'', '\\\'', $lang['Button']['preview']),
	'L_ADDLINK_BUTTON'        => str_replace('\'', '\\\'', $lang['Button']['links']),
	'L_YES'                   => $lang['Yes'],
	'L_NO'                    => $lang['No'],

	'S_DEST'                  => htmlspecialchars($listdata['liste_name']),
	'S_SUBJECT'               => $subject,
	'SELECTED_STATUS_WRITING' => $output->getBoolAttr('selected', ($logdata['log_status'] == STATUS_WRITING)),
	'SELECTED_STATUS_MODEL'   => $output->getBoolAttr('selected', ($logdata['log_status'] == STATUS_MODEL)),

	'S_ENCTYPE'               => ($max_filesize) ? 'multipart/form-data' : 'application/x-www-form-urlencoded',
	'S_DELETE_BUTTON_DISABLED' => $output->getBoolAttr('disabled', ($logdata['log_id'] == 0)),
	'S_HIDDEN_FIELDS'         => $output->getHiddenFields(),

	'LISTBOX'                 => $output->listbox(Auth::VIEW, false)
]);

if ($logdata['log_date'] != -1) {
	$template->assignToBlock('last_modified', [
		'S_LAST_MODIFIED' => sprintf($lang['Last_modified'],
			convert_time($admindata['admin_dateformat'], $logdata['log_date'])
		)
	]);
}

if (is_readable(WA_ROOTDIR . '/languages/'.$admindata['admin_lang'].'/tinymce.js')) {
	$template->assignToBlock('tinymce_lang', [
		'FULL_CODE' => strpos($admindata['admin_lang'], '_') ? $admindata['admin_lang'] : $admindata['admin_lang'] . '_' . strtoupper($admindata['admin_lang']),
		'CODE' => $admindata['admin_lang']
	]);
}

if ($listdata['liste_format'] != FORMAT_HTML) {
	$template->assignToBlock('nl_text_textarea', [
		'L_TITLE'    => $lang['Log_in_text'],
		'L_EXPLAIN'  => nl2br($lang['Explain']['text']),

		'S_BODY'     => $body_text
	]);
}

if ($listdata['liste_format'] != FORMAT_TEXT) {
	$template->assignToBlock('nl_html_textarea', [
		'L_TITLE'    => $lang['Log_in_html'],
		'L_EXPLAIN'  => nl2br($lang['Explain']['html']),

		'S_BODY'     => $body_html
	]);
}

if ($auth->check(Auth::SEND, $listdata['liste_id'])) {
	$template->assignToBlock('test_send', [
		'L_TEST_SEND'      => $lang['Test_send'],
		'L_TEST_SEND_NOTE' => nl2br(sprintf($lang['Test_send_note'],
			sprintf('<a href="%s">', wan_get_faq_url('mailing_quality')),
			'</a>'
		)),
		'L_SEND_BUTTON'    => $lang['Button']['send']
	]);
}

if ($auth->check(Auth::ATTACH, $listdata['liste_id'])) {
	$rowspan = 2;
	if ($max_filesize) {
		$rowspan++;
	}

	if ($file_box != '') {
		$rowspan++;
	}

	$template->assignToBlock('joined_files', [
		'L_TITLE_ADD_FILE'   => $lang['Title']['join'],
		'L_EXPLAIN_ADD_FILE' => nl2br($lang['Explain']['join']),
		'L_ADD_FILE'         => $lang['Join_file_to_log'],
		'L_ADD_FILE_BUTTON'  => $lang['Button']['add_file'],

		'S_ROWSPAN' => $rowspan
	]);

	//
	// Si l'upload est autorisé, on affiche le champs type file
	//
	if ($max_filesize) {
		$template->assignToBlock('joined_files.upload_input', [
			'L_BROWSE_BUTTON' => $lang['Button']['browse'],
			'L_MAXIMUM_SIZE'  => sprintf($lang['Maximum_size'], formateSize($max_filesize)),
			'MAX_FILE_SIZE'   => $max_filesize
		]);
	}

	//
	// Box de sélection de fichiers existants
	//
	if ($file_box != '') {
		$template->assignToBlock('joined_files.select_box', [
			'SELECT_BOX' => $file_box
		]);
	}

	if ($logdata['joined_files']) {
		$template->assignToBlock('joined_files.delete', [
			'L_DEL_FILE_BUTTON' => $lang['Button']['del_file']
		]);
	}

	$template->assign([
		'JOINED_FILES_BOX' => $output->filesList($logdata)
	]);
}

$template->pparse();
$output->footer();
