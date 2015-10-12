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
use Wamailer\Mailer;
use Wamailer\Mime;
use ZipArchive;

require './start.inc.php';

$mode      = filter_input(INPUT_GET, 'mode');
$action    = filter_input(INPUT_GET, 'action');
$sql_type  = filter_input(INPUT_GET, 'type');
$sql_order = filter_input(INPUT_GET, 'order');
$page_id   = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
	'options' => ['min_range' => 1, 'default' => 1]
]);

if (!in_array($mode, ['liste', 'log', 'abonnes', 'download', 'iframe', 'export'])) {
	http_redirect('index.php');
}

if (isset($_POST['cancel'])) {
	http_redirect('view.php?mode=' . $mode);
}

$vararray = ['purge', 'edit', 'delete'];
foreach ($vararray as $varname) {
	if (isset($_POST[$varname])) {
		$action = $varname;
	}
}

if (($mode != 'liste' || ($mode == 'liste' && $action != 'add')) && !$_SESSION['liste']) {
	$output->build_listbox(Auth::VIEW);
}
else if ($_SESSION['liste']) {
	$listdata = $auth->listdata[$_SESSION['liste']];
}

$output->build_listbox(Auth::VIEW, false, './view.php?mode=' . $mode);

//
// Mode download : téléchargement des fichiers joints à un log
//
if ($mode == 'download') {
	if (!$auth->check_auth(Auth::VIEW, $listdata['liste_id'])) {
		http_response_code(401);
		$output->displayMessage('Not_auth_view');
	}

	$file_id = (int) filter_input(INPUT_GET, 'fid', FILTER_VALIDATE_INT);

	$attach = new Attach();
	$file   = $attach->getFile($file_id);

	if (!$file || !($fp = fopen($file['path'], 'rb'))) {
		$output->displayMessage(sprintf($lang['Message']['File_not_exists'], ''));
	}

	sendfile($file['name'], $file['type'], $fp);
}

//
// Mode export : Export d'une archive et de ses fichiers joints
//
else if ($mode == 'export') {
	$log_id = (int) filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

	$sql = "SELECT log_subject, log_body_text, log_body_html, log_date
		FROM " . LOG_TABLE . "
		WHERE log_id = " . $log_id;
	$result = $db->query($sql);

	if (!($logdata = $result->fetch())) {
		trigger_error('log_not_exists', E_USER_ERROR);
	}

	$filename = sprintf('newsletter-%s-%d.zip', date('Y.m.d', $logdata['log_date']), $log_id);
	$tmp_filename = tempnam(WA_TMPDIR, 'wa-');

	$zip = new ZipArchive();
	$zip->open($tmp_filename, ZipArchive::CREATE);

	$sql = "SELECT jf.file_real_name, jf.file_physical_name
		FROM " . JOINED_FILES_TABLE . " AS jf
			INNER JOIN " . LOG_FILES_TABLE . " AS lf ON lf.file_id = jf.file_id
			INNER JOIN " . LOG_TABLE . " AS l ON l.log_id = lf.log_id
				AND l.log_id   = $log_id
		ORDER BY jf.file_real_name ASC";
	$result = $db->query($sql);

	//
	// Copie des fichiers joints dans le répertoire temporaire WA_TMPDIR/newsletter
	// et remplacement éventuel des références cid: dans la newsletter HTML.
	//
	while ($row = $result->fetch()) {
		$zip->addFile(
			WA_ROOTDIR . '/' . $nl_config['upload_path'] . $row['file_physical_name'],
			'newsletter/files/' . $row['file_real_name']
		);

		$logdata['log_body_html'] = preg_replace(
			'/<(.+?)"cid:' . preg_quote($row['file_real_name'], '/') . '"([^>]*)?>/si',
			'<\\1"files/' . $row['file_real_name'] . '"\\2>',
			$logdata['log_body_html']
		);
	}

	//
	// Ajout du BOM utf-8 pour l'archive en texte plat
	//
	if (preg_match('/[\x80-\x9F]/', $logdata['log_body_text'])) {
		$logdata['log_body_text'] = "\xEF\xBB\xBF" . $logdata['log_body_text'];
	}

	//
	// Ajout d'un meta charset dans l'archive html
	//
	$logdata['log_body_html'] = str_ireplace(
		'<head>',
		'<head><meta charset="UTF-8">',
		$logdata['log_body_html']
	);

	$zip->addFromString('newsletter/newsletter.txt', $logdata['log_body_text']);
	$zip->addFromString('newsletter/newsletter.html', $logdata['log_body_html']);

	$zip->close();

	$data = file_get_contents($tmp_filename);
	unlink($tmp_filename);

	sendfile($filename, 'application/zip', $data);
}

//
// Mode iframe pour visualisation des logs
//
else if ($mode == 'iframe') {
	if (!$auth->check_auth(Auth::VIEW, $listdata['liste_id'])) {
		http_response_code(401);
		$output->basic($lang['Message']['Not_auth_view']);
	}

	$log_id = (int) filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
	$format = (int) filter_input(INPUT_GET, 'format', FILTER_VALIDATE_INT);

	if ($listdata['liste_format'] != FORMAT_MULTIPLE) {
		$format = $listdata['liste_format'];
	}

	$body_type = ($format == FORMAT_HTML) ? 'log_body_html' : 'log_body_text';

	$sql = "SELECT $body_type
		FROM " . LOG_TABLE . "
		WHERE log_id = $log_id AND liste_id = " . $listdata['liste_id'];
	$result = $db->query($sql);

	if ($row  = $result->fetch()) {
		$body = $row[$body_type];

		if (strlen($body)) {
			if ($format == FORMAT_HTML) {
				$body = preg_replace(
					'/<(.+?)"cid:([^\\:*\/?<">|]+)"([^>]*)?>/si',
					'<\\1"show.php?file=\\2"\\3>',
					$body
				);

				$output->send_headers();

				echo str_replace(
					'{LINKS}',
					sprintf('<a href="#" onclick="return false;">%s (lien fictif)</a>', $lang['Label_link']),
					$body
				);
			}
			else {
				// on normalise les fins de ligne pour s'assurer du bon
				// fonctionnement de wordwrap()
				$body = preg_replace("/\r\n?|\n/", "\r\n", $body);
				$body = wordwrap(trim($body), 78, "\r\n");
				$body = active_urls(htmlspecialchars($body, ENT_NOQUOTES));
				$body = preg_replace('/(?<=^|\s)(\*[^\r\n]+?\*)(?=\s|$)/', '<strong>\\1</strong>', $body);
				$body = preg_replace('/(?<=^|\s)(\/[^\r\n]+?\/)(?=\s|$)/', '<em>\\1</em>', $body);
				$body = preg_replace('/(?<=^|\s)(_[^\r\n]+?_)(?=\s|$)/', '<u>\\1</u>', $body);
				$body = str_replace('{LINKS}', '<a href="#" onclick="return false;">' . $listdata['form_url'] . '... (lien fictif)</a>', $body);
				$output->basic(sprintf('<pre style="font-size: 13px;">%s</pre>', $body));
			}
		}
		else {
			$output->basic($lang['Message']['log_not_exists']);
		}
	}
	else {
		$output->basic($lang['Message']['log_not_exists']);
	}

	exit;
}

//
// Mode gestion des abonnés
//
else if ($mode == 'abonnes') {
	$other_tags = wan_get_tags();

	switch ($action) {
		case 'delete':
			$auth_type = Auth::DEL;
			break;
		case 'view':
		default:
			$auth_type = Auth::VIEW;
			break;
	}

	if (!$auth->check_auth($auth_type, $listdata['liste_id'])) {
		$output->displayMessage('Not_' . $auth->auth_ary[$auth_type]);
	}

	$abo_id = (int) filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
	$get_string = '';

	//
	// Si la fonction de recherche est sollicitée
	//
	$abo_confirmed   = SUBSCRIBE_CONFIRMED;
	$sql_search      = '';
	$sql_search_date = '';
	$search_keyword  = trim(u::filter_input(INPUT_GET, 'keyword'));
	$search_date     = (int) filter_input(INPUT_GET, 'days');

	if ($search_keyword || $search_date) {
		if (strlen($search_keyword) > 1) {
			$get_string .= '&amp;keyword=' . htmlspecialchars(urlencode($search_keyword));
			$sql_search  = sprintf("WHERE a.abo_email LIKE '%s' ",
				str_replace('*', '%', addcslashes($db->escape($search_keyword), '%_'))
			);
		}

		if ($search_date != 0) {
			$get_string .= '&amp;days=' . $search_date;

			if ($search_date < 0) {
				$abo_confirmed = SUBSCRIBE_NOT_CONFIRMED;
			}
			else {
				$sql_search_date = sprintf(' AND al.register_date >= %d ',
					strtotime(sprintf('-%d days', $search_date))
				);
			}
		}
	}

	//
	// Classement
	//
	if ($sql_type == 'abo_email' || $sql_type == 'register_date' || $sql_type == 'format') {
		$get_string .='&amp;type=' . $sql_type;
	}
	else {
		$sql_type = 'register_date';
	}

	if ($sql_order == 'ASC' || $sql_order == 'DESC') {
		$get_string .='&amp;order=' . $sql_order;
	}
	else {
		$sql_order = 'DESC';
	}

	$get_page = ($page_id > 1) ? '&amp;page=' . $page_id : '';

	if (($action == 'view' || $action == 'edit') && !$abo_id) {
		$output->redirect('./view.php?mode=abonnes', 4);
		$output->displayMessage('No_abo_id');
	}

	//
	// Visualisation du profil d'un abonné
	//
	if ($action == 'view') {
		$liste_ids = $auth->check_auth(Auth::VIEW);

		//
		// Récupération des champs des tags personnalisés
		//
		if (count($other_tags) > 0) {
			$fields_str = '';
			foreach ($other_tags as $tag) {
				$fields_str .= 'a.' . $tag['column_name'] . ', ';
			}
		}
		else {
			$fields_str = '';
		}

		$sql = "SELECT $fields_str a.abo_id, a.abo_pseudo, a.abo_email, a.abo_status, al.register_date, al.liste_id, al.format
			FROM " . ABONNES_TABLE . " AS a
				INNER JOIN " . ABO_LISTE_TABLE . " AS al ON al.abo_id = a.abo_id
					AND al.liste_id IN(" . implode(', ', $liste_ids) . ")
			WHERE a.abo_id = $abo_id";
		$result = $db->query($sql);

		if ($row = $result->fetch()) {
			$output->page_header();

			$output->set_filenames(['body' => 'view_abo_profil_body.tpl']);

			$output->assign_vars([
				'L_TITLE'             => sprintf($lang['Title']['profile'],
					htmlspecialchars((!empty($row['abo_pseudo'])) ? $row['abo_pseudo'] : $row['abo_email'])
				),
				'L_EXPLAIN'           => nl2br($lang['Explain']['abo']),
				'L_PSEUDO'            => $lang['Abo_pseudo'],
				'L_EMAIL'             => $lang['Email_address'],
				'L_STATUS'            => $lang['Account_status'],
				'L_REGISTER_DATE'     => $lang['Susbcribed_date'],
				'L_LISTE_TO_REGISTER' => $lang['Liste_to_register'],
				'L_GOTO_LIST'         => $lang['Goto_list'],

				'U_GOTO_LIST'         => 'view.php?mode=abonnes' . $get_string . $get_page,
				'S_ABO_PSEUDO'        => (!empty($row['abo_pseudo']))
					? htmlspecialchars($row['abo_pseudo']) : '<b>' . $lang['No_data'] . '</b>',
				'S_ABO_EMAIL'         => htmlspecialchars($row['abo_email']),
				'S_STATUS'            => ($row['abo_status'] == ABO_ACTIF) ? $lang['Active'] : $lang['Inactive']
			]);

			//
			// Affichage des valeurs des tags enregistrés
			//
			if (count($other_tags) > 0) {
				$output->assign_block_vars('tags', [
					'L_CAPTION' => $lang['TagsList'],
					'L_NAME'    => $lang['Name'],
					'L_VALUE'   => $lang['Value']
				]);

				foreach ($other_tags as $tag) {
					$value = $row[$tag['column_name']];
					$value = (!is_null($value)) ? nl2br(htmlspecialchars($value)) : '<i>NULL</i>';

					$output->assign_block_vars('tags.row', [
						'NAME'  => $tag['tag_name'],
						'VALUE' => $value,
					]);
				}
			}

			// Actions possibles sur cette liste
			if ($auth->check_auth(Auth::EDIT, $_SESSION['liste'])) {
				$output->assign_block_vars('actions', [
					'L_EDIT_ACCOUNT'   => $lang['Edit_account'],
					'L_DELETE_ACCOUNT' => $lang['Button']['del_account'],
					'S_ABO_ID'         => $row['abo_id']
				]);
			}

			// Affichage des listes de diffusion auxquelles l'abonné est inscrit
			$register_date = time();

			do {
				$liste_name   = $auth->listdata[$row['liste_id']]['liste_name'];
				$liste_format = $auth->listdata[$row['liste_id']]['liste_format'];

				if ($liste_format == FORMAT_MULTIPLE) {
					$format = sprintf(' (%s&nbsp;: %s)',
						$lang['Choice_Format'],
						($row['format'] == FORMAT_HTML ? 'html' : 'texte')
					);
				}
				else {
					$format = sprintf(' (%s&nbsp;: %s)',
						$lang['Format'],
						($liste_format == FORMAT_HTML ? 'html' : 'texte')
					);
				}

				$output->assign_block_vars('listerow', [
					'LISTE_NAME'    => htmlspecialchars($liste_name),
					'CHOICE_FORMAT' => $format,
					'LISTE_ID'      => $row['liste_id']
				]);

				if ($row['register_date'] < $register_date) {
					$register_date = $row['register_date'];
				}
			}
			while ($row = $result->fetch());

			$output->assign_var('S_REGISTER_DATE', convert_time($admindata['admin_dateformat'], $register_date));

			$output->pparse('body');
			$output->page_footer();
		}
		else {
			$output->displayMessage('abo_not_exists');
		}
	}

	//
	// Édition d'un profil d'abonné
	//
	else if ($action == 'edit') {
		$liste_ids = $auth->check_auth(Auth::EDIT);

		$sql = "SELECT liste_id
			FROM " . ABO_LISTE_TABLE . "
			WHERE abo_id = " . $abo_id;
		$result = $db->query($sql);

		$tmp_ids = [];
		while ($tmp_id = $result->column('liste_id')) {
			$tmp_ids[] = $tmp_id;
		}

		$tmp_ids = array_intersect($liste_ids, $tmp_ids);

		//
		// Cet utilisateur n’a pas les droits nécessaires pour faire cette opération
		//
		if (count($tmp_ids) == 0) {
			http_response_code(401);
			$output->displayMessage('Not_auth_edit');
		}

		unset($tmp_ids);

		if (isset($_POST['submit'])) {
			$email = trim(u::filter_input(INPUT_POST, 'email'));

			if (!Mailer::checkMailSyntax($email)) {
				$error = true;
				$msg_error[] = $lang['Message']['Invalid_email'];
			}

			if (!$error) {
				$sql_data = [
					'abo_email'  => $email,
					'abo_pseudo' => strip_tags(trim(u::filter_input(INPUT_POST, 'pseudo'))),
					'abo_status' => (filter_input(INPUT_POST, 'status') == ABO_ACTIF) ? ABO_ACTIF : ABO_INACTIF
				];

				//
				// Récupération des champs des tags personnalisés
				//
				if (count($other_tags) > 0) {
					$tags_data = (array) u::filter_input(INPUT_POST, 'tags',
						FILTER_DEFAULT,
						FILTER_REQUIRE_ARRAY
					);

					foreach ($other_tags as $tag) {
						if (isset($tags_data[$tag['column_name']])) {
							$sql_data[$tag['column_name']] = trim($tags_data[$tag['column_name']]);
						}
					}
				}

				$db->update(ABONNES_TABLE, $sql_data, ['abo_id' => $abo_id]);

				$formatList = (array) filter_input(INPUT_POST, 'format',
					FILTER_VALIDATE_INT,
					FILTER_REQUIRE_ARRAY
				);

				$update = [FORMAT_TEXTE => [], FORMAT_HTML => []];

				foreach ($formatList as $liste_id => $format) {
					if (in_array($format, [FORMAT_TEXTE, FORMAT_HTML]) && $auth->check_auth(Auth::EDIT, $liste_id)) {
						$update[$format][] = $liste_id;
					}
				}

				foreach ($update as $format => $sql_ids) {
					if (count($sql_ids) > 0) {
						$sql = "UPDATE " . ABO_LISTE_TABLE . "
							SET format = $format
							WHERE abo_id = $abo_id
								AND liste_id IN(" . implode(', ', $sql_ids) . ")";
						$db->query($sql);
					}
				}

				$target = './view.php?mode=abonnes&action=view&id=' . $abo_id;
				$output->redirect($target, 4);
				$output->addLine($lang['Message']['Profile_updated']);
				$output->addLine($lang['Click_return_abo_profile'], $target);
				$output->displayMessage();
			}
		}

		//
		// Récupération des champs des tags personnalisés
		//
		if (count($other_tags) > 0) {
			$fields_str = '';
			foreach ($other_tags as $tag) {
				$fields_str .= 'a.' . $tag['column_name'] . ', ';
			}
		}
		else {
			$fields_str = '';
		}

		$sql = "SELECT $fields_str a.abo_id, a.abo_pseudo, a.abo_email, a.abo_status, al.liste_id, al.format
			FROM " . ABONNES_TABLE . " AS a
				INNER JOIN " . ABO_LISTE_TABLE . " AS al ON al.abo_id = a.abo_id
					AND al.liste_id IN(" . implode(', ', $liste_ids) . ")
			WHERE a.abo_id = $abo_id";
		$result = $db->query($sql);

		if ($row = $result->fetch()) {
			require 'includes/functions.box.php';

			$output->page_header();

			$output->set_filenames(['body' => 'edit_abo_profil_body.tpl']);

			$output->assign_vars([
				'L_TITLE'              => sprintf($lang['Title']['mod_profile'],
					htmlspecialchars((!empty($row['abo_pseudo'])) ? $row['abo_pseudo'] : $row['abo_email'])
				),
				'L_EXPLAIN'            => nl2br($lang['Explain']['abo']),
				'L_PSEUDO'             => $lang['Abo_pseudo'],
				'L_EMAIL'              => $lang['Email_address'],
				'L_STATUS'             => $lang['Account_status'],
				'L_LISTE_TO_REGISTER'  => $lang['Liste_to_register'],
				'L_GOTO_LIST'          => $lang['Goto_list'],
				'L_VIEW_ACCOUNT'       => $lang['View_account'],
				'L_DELETE_ACCOUNT'     => $lang['Button']['del_account'],
				'L_VALID_BUTTON'       => $lang['Button']['valid'],
				'L_WARNING_EMAIL_DIFF' => str_replace("\n", '\n', addslashes($lang['Warning_email_diff'])),
				'L_ACTIVE'             => $lang['Active'],
				'L_INACTIVE'           => $lang['Inactive'],

				'U_GOTO_LIST'          => 'view.php?mode=abonnes' . $get_string . $get_page,
				'S_ABO_PSEUDO'         => htmlspecialchars($row['abo_pseudo']),
				'S_ABO_EMAIL'          => htmlspecialchars($row['abo_email']),
				'S_ABO_ID'             => $row['abo_id'],
				'S_STATUS_ACTIVE'      => $output->getBoolAttr('checked', ($row['abo_status'] == ABO_ACTIF)),
				'S_STATUS_INACTIVE'    => $output->getBoolAttr('checked', ($row['abo_status'] == ABO_INACTIF))
			]);

			//
			// Affichage des valeurs des tags enregistrés
			//
			if (count($other_tags) > 0) {
				$output->assign_block_vars('tags', [
					'L_TITLE' => $lang['TagsEdit']
				]);

				foreach ($other_tags as $tag) {
					$output->assign_block_vars('tags.row', [
						'NAME'      => $tag['tag_name'],
						'FIELDNAME' => $tag['column_name'],
						'VALUE'     => htmlspecialchars($row[$tag['column_name']])
					]);
				}
			}

			do {
				if ($auth->listdata[$row['liste_id']]['liste_format'] == FORMAT_MULTIPLE) {
					$format_box = format_box("format[$row[liste_id]]",
						$row['format'], false, false, true
					);
				}
				else {
					$format = $auth->listdata[$row['liste_id']]['liste_format'];
					$format_box = ($format == FORMAT_HTML) ? 'HTML' : 'texte';
				}

				$output->assign_block_vars('listerow', [
					'LISTE_NAME' => htmlspecialchars($auth->listdata[$row['liste_id']]['liste_name']),
					'FORMAT_BOX' => $format_box,
					'LISTE_ID'   => $row['liste_id']
				]);
			}
			while ($row = $result->fetch());

			$output->pparse('body');
			$output->page_footer();
		}
		else {
			$output->displayMessage('abo_not_exists');
		}
	}

	//
	// Suppression d'un ou plusieurs profils abonnés
	//
	else if ($action == 'delete') {
		$email_list = trim(u::filter_input(INPUT_POST, 'email_list'));
		$abo_ids    = (array) filter_input(INPUT_POST, 'id',
			FILTER_VALIDATE_INT,
			FILTER_REQUIRE_ARRAY
		);
		$abo_ids = array_filter($abo_ids);

		// Spécial. Cas où on veut supprimer un  seul compte et l'ID est
		// fourni seul via le lien "Supprimer ce compte".
		if (count($abo_ids) == 0 && $abo_id > 0) {
			$abo_ids = [$abo_id];
		}

		if ($email_list == '' && count($abo_ids) == 0) {
			$output->redirect('./view.php?mode=abonnes', 4);
			$output->displayMessage('No_abo_id');
		}

		if (isset($_POST['confirm'])) {
			$db->beginTransaction();

			$sql = "DELETE FROM " . ABONNES_TABLE . "
				WHERE abo_id IN(
					SELECT abo_id
					FROM " . ABO_LISTE_TABLE . "
					WHERE abo_id IN(" . implode(', ', $abo_ids) . ")
					GROUP BY abo_id
					HAVING COUNT(abo_id) = 1
				)";
			$db->query($sql);

			$sql = "DELETE FROM " . ABO_LISTE_TABLE . "
				WHERE abo_id IN(" . implode(', ', $abo_ids) . ")
					AND liste_id = " . $listdata['liste_id'];
			$db->query($sql);

			$db->commit();

			//
			// Optimisation des tables
			//
			$db->vacuum([ABONNES_TABLE, ABO_LISTE_TABLE]);

			$target = './view.php?mode=abonnes';
			$output->redirect($target, 4);
			$output->addLine($lang['Message']['abo_deleted']);
			$output->addLine($lang['Click_return_abo'], $target);
			$output->displayMessage();
		}
		else {
			if ($email_list != '') {
				$email_list   = array_map('trim', explode(',', $email_list));
				$total_emails = count($email_list);
				$sql_list     = '';

				for ($i = 0; $i < $total_emails; $i++) {
					if (!empty($email_list[$i])) {
						$sql_list .= ($i > 0) ? ', ' : '';
						$sql_list .= '\'' . $db->escape($email_list[$i]) . '\'';
					}
				}

				if ($sql_list == '') {
					$output->redirect('./view.php?mode=abonnes', 4);
					$output->displayMessage('No_abo_id');
				}

				$sql = "SELECT a.abo_id
					FROM " . ABONNES_TABLE . " AS a
						INNER JOIN " . ABO_LISTE_TABLE . " AS al ON al.abo_id = a.abo_id
							AND al.liste_id = $listdata[liste_id]
					WHERE a.abo_email IN($sql_list)";
				$result = $db->query($sql);

				if ($abo_id = $result->column('abo_id')) {
					do {
						$output->addHiddenField('id[]', $abo_id);
					}
					while ($abo_id = $result->column('abo_id'));
				}
				else {
					$output->redirect('./view.php?mode=abonnes', 4);
					$output->displayMessage('No_abo_email');
				}
			}
			else {
				foreach ($abo_ids as $abo_id) {
					$output->addHiddenField('id[]', $abo_id);
				}
			}

			$output->page_header();

			$output->set_filenames(['body' => 'confirm_body.tpl']);

			$output->assign_vars([
				'L_CONFIRM' => $lang['Title']['confirm'],

				'TEXTE' => $lang['Delete_abo'],
				'L_YES' => $lang['Yes'],
				'L_NO'  => $lang['No'],

				'S_HIDDEN_FIELDS' => $output->getHiddenFields(),
				'U_FORM' => 'view.php?mode=abonnes&amp;action=delete'
			]);

			$output->pparse('body');

			$output->page_footer();
		}
	}

	$abo_per_page = 40;
	$start        = (($page_id - 1) * $abo_per_page);

	$sql = "SELECT COUNT(a.abo_id) AS total_abo
		FROM " . ABONNES_TABLE . " AS a
			INNER JOIN " . ABO_LISTE_TABLE . " AS al ON al.abo_id = a.abo_id
				AND al.liste_id  = $listdata[liste_id]
				AND al.confirmed = $abo_confirmed $sql_search_date $sql_search";
	$result = $db->query($sql);

	$total_abo = $result->column('total_abo');

	if ($total_abo > 0) {
		$sql = "SELECT a.abo_id, a.abo_email, al.register_date, al.format
			FROM " . ABONNES_TABLE . " AS a
				INNER JOIN " . ABO_LISTE_TABLE . " AS al ON al.abo_id = a.abo_id
					AND al.liste_id  = $listdata[liste_id]
					AND al.confirmed = $abo_confirmed $sql_search_date $sql_search
			ORDER BY $sql_type " . $sql_order . "
			LIMIT $abo_per_page OFFSET $start";
		$result = $db->query($sql);
		$aborow = $result->fetchAll();

		$total_pages = ceil($total_abo / $abo_per_page);
		if ($page_id > 1) {
			$output->addLink(
				'prev',
				'./view.php?mode=abonnes' . $get_string . '&amp;page=' . ($page_id - 1),
				$lang['Prev_page']
			);
		}

		if ($page_id < $total_pages) {
			$output->addLink(
				'next',
				'./view.php?mode=abonnes' . $get_string . '&amp;page=' . ($page_id + 1),
				$lang['Next_page']
			);
		}
	}
	else {
		$aborow = [];
	}

	$search_days_box  = '<select name="days">';
	$search_days_box .= '<option value="0">' . $lang['All_abo'] . '</option>';

	$selected = $output->getBoolAttr('selected', ($search_date == -1));
	$search_days_box .= '<option value="-1"' . $selected . '>' . $lang['Inactive_account'] . '</option>';

	for ($i = 0, $days = 10; $i < 4; $i++, $days *= 3) {
		$selected = $output->getBoolAttr('selected', ($search_date == $days));
		$search_days_box .= '<option value="' . $days . '"' . $selected . '>' . sprintf($lang['Days_interval'], $days) . '</option>';
	}
	$search_days_box .= '</select>';

	$navigation = navigation('view.php?mode=abonnes' . $get_string, $total_abo, $abo_per_page, $page_id);

	$output->page_header();

	$output->set_filenames(['body' => 'view_abo_list_body.tpl']);

	$output->assign_vars([
		'L_EXPLAIN'            => nl2br($lang['Explain']['abo']),
		'L_TITLE'              => sprintf($lang['Title']['abo'], htmlspecialchars($listdata['liste_name'])),
		'L_SEARCH'             => $lang['Search_abo'],
		'L_SEARCH_NOTE'        => $lang['Search_abo_note'],
		'L_SEARCH_BUTTON'      => $lang['Button']['search'],
		'L_CLASSEMENT'         => $lang['Classement'],
		'L_BY_EMAIL'           => $lang['By_email'],
		'L_BY_DATE'            => $lang['By_date'],
		'L_BY_FORMAT'          => $lang['By_format'],
		'L_BY_ASC'             => $lang['By_asc'],
		'L_BY_DESC'            => $lang['By_desc'],
		'L_CLASSER_BUTTON'     => $lang['Button']['classer'],
		'L_EMAIL'              => $lang['Email_address'],
		'L_DATE'               => $lang['Susbcribed_date'],

		'KEYWORD'              => htmlspecialchars($search_keyword),
		'SEARCH_DAYS_BOX'      => $search_days_box,
		'SELECTED_TYPE_EMAIL'  => $output->getBoolAttr('selected', ($sql_type == 'abo_email')),
		'SELECTED_TYPE_DATE'   => $output->getBoolAttr('selected', ($sql_type == 'register_date')),
		'SELECTED_TYPE_FORMAT' => $output->getBoolAttr('selected', ($sql_type == 'format')),
		'SELECTED_ORDER_ASC'   => $output->getBoolAttr('selected', ($sql_order == 'ASC')),
		'SELECTED_ORDER_DESC'  => $output->getBoolAttr('selected', ($sql_order == 'DESC')),

		'PAGINATION'           => $navigation,
		'PAGEOF'               => ($total_abo > 0) ? sprintf($lang['Page_of'], $page_id, ceil($total_abo / $abo_per_page)) : '',
		'NUM_SUBSCRIBERS'      => ($total_abo > 0) ? '[ <b>' . $total_abo . '</b> ' . $lang['Module']['subscribers'] . ' ]' : '',

		'PAGING'               => $get_page
	]);

	if ($listdata['liste_format'] == FORMAT_MULTIPLE) {
		$output->assign_block_vars('view_format', [
			'L_FORMAT' => $lang['Format']
		]);
	}

	if ($num_abo = count($aborow)) {
		$display_checkbox = false;
		if ($auth->check_auth(Auth::DEL, $listdata['liste_id'])) {
			$output->assign_block_vars('delete_option', [
				'L_FAST_DELETION'      => $lang['Fast_deletion'],
				'L_FAST_DELETION_NOTE' => $lang['Fast_deletion_note'],
				'L_DELETE_BUTTON'      => $lang['Button']['delete'],
				'L_DELETE_ABO_BUTTON'  => $lang['Button']['del_abo']
			]);

			$display_checkbox = true;
		}

		for ($i = 0; $i < $num_abo; $i++) {
			$output->assign_block_vars('aborow', [
				'ABO_EMAIL'         => htmlspecialchars($aborow[$i]['abo_email']),
				'ABO_REGISTER_DATE' => convert_time($admindata['admin_dateformat'], $aborow[$i]['register_date']),
				'U_VIEW'            => sprintf('view.php?mode=abonnes&amp;action=view&amp;id=%d%s%s', $aborow[$i]['abo_id'], $get_string, $get_page)
			]);

			if ($listdata['liste_format'] == FORMAT_MULTIPLE) {
				$output->assign_block_vars('aborow.format', [
					'ABO_FORMAT' => ($aborow[$i]['format'] == FORMAT_HTML) ? 'html' : 'texte'
				]);
			}

			if ($display_checkbox) {
				$output->assign_block_vars('aborow.delete', [
					'ABO_ID' => $aborow[$i]['abo_id']
				]);
			}
		}
	}
	else {
		$output->assign_block_vars('empty', [
			'L_EMPTY' => ($search_keyword || $search_date)
				? $lang['No_search_result'] : $lang['No_abo_in_list']
		]);
	}
}

//
// Mode Listes de diffusion
//
else if ($mode == 'liste') {
	switch ($action) {
		case 'add':
		case 'delete':
			if (!wan_is_admin($admindata)) {
				http_response_code(401);
				$target = './view.php?mode=liste';
				$output->redirect($target, 4);
				$output->addLine($lang['Message']['Not_authorized']);
				$output->addLine($lang['Click_return_liste'], $target);
				$output->displayMessage();
			}

			$auth_type = false;
			break;
		case 'purge':
			$auth_type = Auth::DEL;
			break;
		case 'edit':
			$auth_type = Auth::EDIT;
			break;
		default:
			$auth_type = Auth::VIEW;
			break;
	}

	if ($auth_type && !$auth->check_auth($auth_type, $_SESSION['liste'])) {
		$output->displayMessage('Not_' . $auth->auth_ary[$auth_type]);
	}

	//
	// Ajout ou édition d'une liste
	//
	if ($action == 'add' || $action == 'edit') {
		$vararray = [
			'liste_name', 'sender_email', 'return_email', 'form_url', 'liste_sig',
			'pop_host', 'pop_user', 'pop_pass', 'liste_alias'
		];
		foreach ($vararray as $varname) {
			${$varname} = trim(u::filter_input(INPUT_POST, $varname));
		}

		$default_values = [
			'liste_format'      => FORMAT_TEXTE,
			'limitevalidate'    => 3,
			'auto_purge'        => true,
			'purge_freq'        => 7,
			'use_cron'          => false,
			'pop_port'          => 110,
			'pop_tls'           => SECURITY_NONE,
			'liste_public'      => true,
			'confirm_subscribe' => CONFIRM_ALWAYS,
		];

		$vararray2 = [
			'liste_format', 'confirm_subscribe', 'liste_public', 'limitevalidate',
			'auto_purge', 'purge_freq', 'use_cron', 'pop_port', 'pop_tls'
		];
		foreach ($vararray2 as $varname) {
			$value = filter_input(INPUT_POST, $varname, FILTER_VALIDATE_INT);
			${$varname} = (is_int($value)) ? $value : $default_values[$varname];
		}

		if (!check_ssl_support()) {
			$pop_tls = SECURITY_NONE;
		}

		if (isset($_POST['submit'])) {
			$liste_name = strip_tags($liste_name);
			$liste_sig  = strip_tags($liste_sig);

			if (mb_strlen($liste_name) < 3 || mb_strlen($liste_name) > 30) {
				$error = true;
				$msg_error[] = $lang['Invalid_liste_name'];
			}

			if (!in_array($liste_format, [FORMAT_TEXTE, FORMAT_HTML, FORMAT_MULTIPLE])) {
				$error = true;
				$msg_error[] = $lang['Unknown_format'];
			}

			if (!Mailer::checkMailSyntax($sender_email)) {
				$error = true;
				$msg_error[] = $lang['Message']['Invalid_email'];
			}

			if ($return_email != '' && !Mailer::checkMailSyntax($return_email)) {
				$error = true;
				$msg_error[] = $lang['Message']['Invalid_email'];
			}

			if ($liste_alias != '' && !Mailer::checkMailSyntax($liste_alias)) {
				$error = true;
				$msg_error[] = $lang['Message']['Invalid_email'];
			}

			if ($pop_pass == '' && $pop_user != '' && $action == 'edit') {
				$pop_pass = $listdata['pop_pass'];
			}

			if ($use_cron && function_exists('stream_socket_client')) {
				$pop = new PopClient();
				$pop->options([
					'starttls' => ($pop_tls == SECURITY_STARTTLS)
				]);

				try {
					$server = ($pop_tls == SECURITY_FULL_TLS) ? 'tls://%s:%d' : '%s:%d';
					$server = sprintf($server, $pop_host, $pop_port);

					if (!$pop->connect($server, $pop_user, $pop_pass)) {
						throw new Exception(sprintf(
							"Failed to connect to POP server (%s)",
							$pop->responseData
						));
					}
				}
				catch (Exception $e) {
					$error = true;
					$msg_error[] = sprintf(nl2br($lang['Message']['bad_pop_param']),
						htmlspecialchars($e->getMessage())
					);
				}

				$pop->quit();
			}
			else {
				$use_cron = 0;
			}

			if (!$error) {
				$sql_data = $sql_where = [];
				$vararray = array_merge($vararray, $vararray2);

				foreach ($vararray as $varname) {
					$sql_data[$varname] = ${$varname};
				}

				if ($action == 'add') {
					$sql_data['liste_startdate'] = time();

					$db->insert(LISTE_TABLE, $sql_data);

					$_SESSION['liste'] = $new_liste_id = $db->lastInsertId();
				}
				else {
					$sql_where['liste_id'] = $listdata['liste_id'];
					$db->update(LISTE_TABLE, $sql_data, $sql_where);
				}

				$target = './view.php?mode=liste';
				$output->redirect($target, 4);
				$output->addLine(
					$action == 'add' ? $lang['Message']['liste_created'] : $lang['Message']['liste_edited']
				);
				$output->addLine($lang['Click_return_liste'], $target);
				$output->displayMessage();
			}
		}
		else if ($action == 'edit') {
			$vararray = array_merge($vararray, $vararray2);

			foreach ($vararray as $varname) {
				${$varname} = $listdata[$varname];
			}
		}

		require 'includes/functions.box.php';

		$output->page_header();

		$output->set_filenames(['body' => 'edit_liste_body.tpl']);

		$output->assign_vars([
			'L_TITLE'              => ($action == 'add') ? $lang['Title']['add_liste'] : $lang['Title']['edit_liste'],
			'L_TITLE_PURGE'        => $lang['Title']['purge_sys'],
			'L_TITLE_CRON'         => $lang['Title']['cron'],
			'L_EXPLAIN'            => nl2br($lang['Explain']['liste']),
			'L_EXPLAIN_PURGE'      => nl2br($lang['Explain']['purge']),
			'L_EXPLAIN_CRON'       => nl2br(sprintf($lang['Explain']['cron'],
				sprintf('<a href="%s">', wan_get_faq_url('where_is_form')),
				'</a>'
			)),
			'L_LISTE_NAME'         => $lang['Liste_name'],
			'L_LISTE_PUBLIC'       => $lang['Liste_public'],
			'L_AUTH_FORMAT'        => $lang['Auth_format'],
			'L_SENDER_EMAIL'       => $lang['Sender_email'],
			'L_RETURN_EMAIL'       => $lang['Return_email'],
			'L_CONFIRM_SUBSCRIBE'  => $lang['Confirm_subscribe'],
			'L_CONFIRM_ALWAYS'     => $lang['Confirm_always'],
			'L_CONFIRM_ONCE'       => $lang['Confirm_once'],
			'L_LIMITEVALIDATE'     => $lang['Limite_validate'],
			'L_NOTE_VALIDATE'      => nl2br($lang['Note_validate']),
			'L_FORM_URL'           => $lang['Form_url'],
			'L_SIG_EMAIL'          => $lang['Sig_email'],
			'L_SIG_EMAIL_NOTE'     => nl2br($lang['Sig_email_note']),
			'L_DAYS'               => $lang['Days'],
			'L_YES'                => $lang['Yes'],
			'L_NO'                 => $lang['No'],
			'L_ENABLE_PURGE'       => $lang['Enable_purge'],
			'L_PURGE_FREQ'         => $lang['Purge_freq'],
			'L_USE_CRON'           => $lang['Use_cron'],
			'L_POP_SERVER'         => $lang['Pop_server'],
			'L_POP_PORT'           => $lang['Pop_port'],
			'L_POP_USER'           => $lang['Pop_user'],
			'L_POP_PASS'           => $lang['Pop_pass'],
			'L_POP_PASS_NOTE'      => nl2br($lang['Server_password_note']),
			'L_LISTE_ALIAS'        => $lang['Liste_alias'],
			'L_VALID_BUTTON'       => $lang['Button']['valid'],
			'L_RESET_BUTTON'       => $lang['Button']['reset'],
			'L_CANCEL_BUTTON'      => $lang['Button']['cancel'],

			'LISTE_NAME'           => htmlspecialchars($liste_name),
			'FORMAT_BOX'           => format_box('liste_format', $liste_format, false, true),
			'SENDER_EMAIL'         => htmlspecialchars($sender_email),
			'RETURN_EMAIL'         => htmlspecialchars($return_email),
			'FORM_URL'             => htmlspecialchars($form_url),
			'SIG_EMAIL'            => htmlspecialchars($liste_sig),
			'LIMITEVALIDATE'       => intval($limitevalidate),
			'PURGE_FREQ'           => intval($purge_freq),
			'CHECK_CONFIRM_ALWAYS' => $output->getBoolAttr('checked', ($confirm_subscribe == CONFIRM_ALWAYS)),
			'CHECK_CONFIRM_ONCE'   => $output->getBoolAttr('checked', ($confirm_subscribe == CONFIRM_ONCE)),
			'CHECK_CONFIRM_NO'     => $output->getBoolAttr('checked', ($confirm_subscribe == CONFIRM_NONE)),
			'CHECK_PUBLIC_YES'     => $output->getBoolAttr('checked', $liste_public),
			'CHECK_PUBLIC_NO'      => $output->getBoolAttr('checked', !$liste_public),
			'CHECKED_PURGE_ON'     => $output->getBoolAttr('checked', $auto_purge),
			'CHECKED_PURGE_OFF'    => $output->getBoolAttr('checked', !$auto_purge),
			'CHECKED_USE_CRON_ON'  => $output->getBoolAttr('checked', $use_cron),
			'CHECKED_USE_CRON_OFF' => $output->getBoolAttr('checked', !$use_cron),
			'DISABLED_CRON'        => $output->getBoolAttr('disabled', !function_exists('stream_socket_client')),
			'WARNING_CRON'         => (!function_exists('stream_socket_client')) ? ' <span style="color: red;">[not available]</span>' : '',
			'POP_HOST'             => htmlspecialchars($pop_host),
			'POP_PORT'             => intval($pop_port),
			'POP_USER'             => htmlspecialchars($pop_user),
			'LISTE_ALIAS'          => htmlspecialchars($liste_alias),
			'ACTION'               => $action
		]);

		if (check_ssl_support()) {
			$output->assign_block_vars('ssl_support', [
				'L_SECURITY'        => $lang['Connection_security'],
				'L_NONE'            => $lang['None'],
				'STARTTLS_SELECTED' => $output->getBoolAttr('selected', $pop_tls == SECURITY_STARTTLS),
				'SSL_TLS_SELECTED'  => $output->getBoolAttr('selected', $pop_tls == SECURITY_FULL_TLS)
			]);
		}

		$output->pparse('body');

		$output->page_footer();
	}

	//
	// Suppression d'une liste avec transvasement éventuel des abonnés
	// et archives vers une autre liste
	//
	else if ($action == 'delete') {
		if (isset($_POST['confirm'])) {
			$db->beginTransaction();

			$sql = "DELETE FROM " . AUTH_ADMIN_TABLE . "
				WHERE liste_id = " . $listdata['liste_id'];
			$db->query($sql);

			$update_abo_ids = $delete_abo_ids = [];

			if (isset($_POST['delete_all'])) {
				$sql = "SELECT abo_id
					FROM " . ABO_LISTE_TABLE . "
					WHERE abo_id IN(
						SELECT abo_id
						FROM " . ABO_LISTE_TABLE . "
						WHERE liste_id = $listdata[liste_id]
					)
					GROUP BY abo_id
					HAVING COUNT(abo_id) = 1";
				$result = $db->query($sql);

				$delete_abo_ids = [];
				while ($abo_id = $result->column('abo_id')) {
					$delete_abo_ids[] = $abo_id;
				}

				//
				// Suppression des comptes existant pour cette liste
				//
				if (count($delete_abo_ids) > 0) {
					$sql = "DELETE FROM " . ABONNES_TABLE . "
						WHERE abo_id IN(" . implode(', ', $delete_abo_ids) . ")";
					$db->query($sql);
				}

				$sql = "DELETE FROM " . ABO_LISTE_TABLE . "
					WHERE liste_id = " . $listdata['liste_id'];
				$db->query($sql);

				//
				// Suppression des archives et éventuelles pièces jointes
				//
				$sql = "SELECT log_id
					FROM " . LOG_TABLE . "
					WHERE liste_id = " . $listdata['liste_id'];
				$result = $db->query($sql);

				$log_ids = [];
				while ($log_id = $result->column('log_id')) {
					$log_ids[] = $log_id;
				}

				$attach = new Attach();
				$attach->deleteFiles($log_ids);

				$sql = "DELETE FROM " . LOG_TABLE . "
					WHERE liste_id = " . $listdata['liste_id'];
				$db->query($sql);

				require 'includes/functions.stats.php';
				remove_stats($listdata['liste_id']);

				$message = $lang['Message']['Liste_del_all'];
			}
			else {
				$liste_id = (int) filter_input(INPUT_POST, 'liste_id', FILTER_VALIDATE_INT);

				if (!isset($auth->listdata[$liste_id])) {
					trigger_error('No_liste_id', E_USER_ERROR);
				}

				$sql = "SELECT abo_id
					FROM " . ABO_LISTE_TABLE . "
					WHERE abo_id IN(
						SELECT abo_id
						FROM " . ABO_LISTE_TABLE . "
						WHERE liste_id = $listdata[liste_id]
					) AND liste_id = " . $liste_id;
				$result = $db->query($sql);

				$delete_abo_ids = [];
				while ($abo_id = $result->column('abo_id')) {
					$delete_abo_ids[] = $abo_id;
				}

				$sql = "SELECT abo_id
					FROM " . ABO_LISTE_TABLE . "
					WHERE abo_id IN(
						SELECT abo_id
						FROM " . ABO_LISTE_TABLE . "
						WHERE liste_id = $listdata[liste_id]
					) AND liste_id <> " . $liste_id;
				$result = $db->query($sql);

				$update_abo_ids = [];
				while ($abo_id = $result->column('abo_id')) {
					$update_abo_ids[] = $abo_id;
				}

				//
				// Suppression des entrées des abonnés déjà inscrits à l'autre liste
				//
				if (count($delete_abo_ids) > 0) {
					$sql = "DELETE FROM " . ABO_LISTE_TABLE . "
						WHERE abo_id IN(" . implode(', ', $delete_abo_ids) . ")
							AND liste_id = " . $listdata['liste_id'];
					$db->query($sql);
				}

				//
				// Mise de l'entrée existante des abonnés pour pointer sur la liste choisie
				//
				if (count($update_abo_ids) > 0) {
					$sql = "UPDATE " . ABO_LISTE_TABLE . "
						SET liste_id = $liste_id
						WHERE abo_id IN(" . implode(', ', $update_abo_ids) . ")
							AND liste_id = " . $listdata['liste_id'];
					$db->query($sql);
				}

				//
				// Passage des archives à la liste choisie
				//
				$sql = "UPDATE " . LOG_TABLE . "
					SET liste_id = $liste_id
					WHERE liste_id = " . $listdata['liste_id'];
				$db->query($sql);

				require 'includes/functions.stats.php';
				remove_stats($listdata['liste_id'], $liste_id);

				$message = $lang['Message']['Liste_del_move'];
			}

			$sql = "DELETE FROM " . LISTE_TABLE . "
				WHERE liste_id = " . $listdata['liste_id'];
			$db->query($sql);

			$db->commit();

			//
			// Optimisation des tables
			//
			$db->vacuum([ABONNES_TABLE, ABO_LISTE_TABLE, LOG_TABLE,
				LOG_FILES_TABLE, JOINED_FILES_TABLE, LISTE_TABLE
			]);

			$target = './index.php';
			$output->redirect($target, 4);
			$output->addLine($message);
			$output->addLine($lang['Click_return_index'], $target);
			$output->displayMessage();
		}
		else {
			$list_box  = '';
			$liste_ids = $auth->check_auth(Auth::VIEW);

			foreach ($auth->listdata as $liste_id => $data) {
				if (in_array($liste_id, $liste_ids) && $liste_id != $listdata['liste_id']) {
					$selected  = $output->getBoolAttr('selected', ($_SESSION['liste'] == $liste_id));
					$list_box .= '<option value="' . $liste_id . '"' . $selected . '> - '
						. htmlspecialchars(cut_str($data['liste_name'], 30)) . ' - </option>';
				}
			}

			if ($list_box != '') {
				$message  = $lang['Move_abo_logs'];
				$message .= '<br /><br />' . $lang['Move_to_liste'] . ' <select id="liste_id" name="liste_id">' . $list_box . '</select>';
				$message .= '<br /><br /><input type="checkbox" id="delete_all" name="delete_all" value="1" /> <label for="delete_all">' . $lang['Delete_abo_logs'] . '</label>';
			}
			else {
				$output->addHiddenField('delete_all', '1');
				$message = $lang['Delete_all'];
			}

			$output->page_header();

			$output->set_filenames(['body' => 'confirm_body.tpl']);

			$output->assign_vars([
				'L_CONFIRM' => $lang['Title']['confirm'],

				'TEXTE' => $message,
				'L_YES' => $lang['Yes'],
				'L_NO'	=> $lang['No'],

				'S_HIDDEN_FIELDS' => $output->getHiddenFields(),
				'U_FORM' => 'view.php?mode=liste&amp;action=delete'
			]);

			$output->pparse('body');

			$output->page_footer();
		}
	}

	//
	// Purge (suppression des inscriptions non confirmées et dont la date de validité est dépassée)
	//
	else if ($action == 'purge') {
		$abo_deleted = purge_liste($listdata['liste_id'], $listdata['limitevalidate'], $listdata['purge_freq']);

		$target = './view.php?mode=liste';
		$output->redirect($target, 4);
		$output->addLine(sprintf($lang['Message']['Success_purge'], $abo_deleted));
		$output->addLine($lang['Click_return_liste'], $target);
		$output->displayMessage();
	}

	//
	// Récupération des nombres d'inscrits
	//
	$num_inscrits = 0;
	$num_temp     = 0;
	$last_log     = 0;

	$sql = "SELECT COUNT(*) AS num_abo, confirmed
		FROM " . ABO_LISTE_TABLE . "
		WHERE liste_id = $listdata[liste_id]
		GROUP BY confirmed";
	$result = $db->query($sql);

	while ($row = $result->fetch()) {
		if ($row['confirmed'] == SUBSCRIBE_CONFIRMED) {
			$num_inscrits = $row['num_abo'];
		}
		else {
			$num_temp = $row['num_abo'];
		}
	}

	//
	// Récupération de la date du dernier envoi
	//
	$sql = "SELECT MAX(log_date) AS last_log
		FROM " . LOG_TABLE . "
		WHERE log_status = " . STATUS_SENT . "
			AND liste_id = " . $listdata['liste_id'];
	$result = $db->query($sql);

	if ($tmp = $result->column('last_log')) {
		$last_log = $tmp;
	}

	switch ($listdata['liste_format']) {
		case FORMAT_TEXTE:
			$l_format = 'txt';
			break;
		case FORMAT_HTML:
			$l_format = 'html';
			break;
		case FORMAT_MULTIPLE:
			$l_format = 'txt &amp; html';
			break;
		default:
			$l_format = $lang['Unknown'];
			break;
	}

	$output->page_header();

	$output->set_filenames(['body' => 'view_liste_body.tpl']);

	switch ($listdata['confirm_subscribe']) {
		case CONFIRM_ALWAYS:
			$l_confirm = $lang['Confirm_always'];
			break;
		case CONFIRM_ONCE:
			$l_confirm = $lang['Confirm_once'];
			break;
		case CONFIRM_NONE:
		default:
			$l_confirm = $lang['No'];
			break;
	}

	$output->assign_vars([
		'L_TITLE'             => $lang['Title']['info_liste'],
		'L_EXPLAIN'           => nl2br($lang['Explain']['liste']),
		'L_LISTE_ID'          => $lang['ID_list'],
		'L_LISTE_NAME'        => $lang['Liste_name'],
		'L_LISTE_PUBLIC'      => $lang['Liste_public'],
		'L_AUTH_FORMAT'       => $lang['Auth_format'],
		'L_SENDER_EMAIL'      => $lang['Sender_email'],
		'L_RETURN_EMAIL'      => $lang['Return_email'],
		'L_CONFIRM_SUBSCRIBE' => $lang['Confirm_subscribe'],
		'L_NUM_SUBSCRIBERS'   => $lang['Reg_subscribers_list'],
		'L_NUM_LOGS'          => $lang['Total_newsletter_list'],
		'L_FORM_URL'          => $lang['Form_url'],
		'L_STARTDATE'         => $lang['Liste_startdate'],

		'LISTE_ID'            => $listdata['liste_id'],
		'LISTE_NAME'          => htmlspecialchars($listdata['liste_name']),
		'LISTE_PUBLIC'        => ($listdata['liste_public']) ? $lang['Yes'] : $lang['No'],
		'AUTH_FORMAT'         => $l_format,
		'SENDER_EMAIL'        => $listdata['sender_email'],
		'RETURN_EMAIL'        => $listdata['return_email'],
		'CONFIRM_SUBSCRIBE'   => $l_confirm,
		'NUM_SUBSCRIBERS'     => $num_inscrits,
		'NUM_LOGS'            => $listdata['liste_numlogs'],
		'FORM_URL'            => htmlspecialchars($listdata['form_url']),
		'STARTDATE'           => convert_time($admindata['admin_dateformat'], $listdata['liste_startdate'])
	]);

	if ($listdata['confirm_subscribe']) {
		$output->assign_block_vars('liste_confirm', [
			'L_LIMITEVALIDATE' => $lang['Limite_validate'],
			'L_NUM_TEMP'       => $lang['Tmp_subscribers_list'],
			'L_DAYS'           => $lang['Days'],

			'LIMITEVALIDATE'   => $listdata['limitevalidate'],
			'NUM_TEMP'         => $num_temp
		]);
	}

	if ($listdata['liste_numlogs'] > 0) {
		$output->assign_block_vars('date_last_log', [
			'L_LAST_LOG' => $lang['Last_newsletter2'],
			'LAST_LOG'   => convert_time($admindata['admin_dateformat'], $last_log)
		]);
	}

	if ($auth->check_auth(Auth::DEL, $listdata['liste_id']) || $auth->check_auth(Auth::EDIT, $listdata['liste_id'])) {
		$output->assign_block_vars('admin_options', []);

		if (wan_is_admin($admindata)) {
			$output->assign_block_vars('admin_options.auth_add', [
				'L_ADD_LISTE' => $lang['Create_liste']
			]);

			$output->assign_block_vars('admin_options.auth_del', [
				'L_DELETE_LISTE' => $lang['Delete_liste']
			]);
		}

		if ($auth->check_auth(Auth::EDIT, $listdata['liste_id'])) {
			$output->assign_block_vars('admin_options.auth_edit', [
				'L_EDIT_LISTE' => $lang['Edit_liste']
			]);
		}

		if ($auth->check_auth(Auth::DEL, $listdata['liste_id'])) {
			$output->assign_block_vars('purge_option', [
				'L_PURGE_BUTTON'  => $lang['Button']['purge'],
				'S_HIDDEN_FIELDS' => $output->getHiddenFields()
			]);
		}
	}
}

//
// Mode Gestion des logs/archives
//
else if ($mode == 'log') {
	$auth_type = ($action == 'delete') ? Auth::DEL : Auth::VIEW;

	if (!$auth->check_auth($auth_type, $listdata['liste_id'])) {
		$output->displayMessage('Not_' . $auth->auth_ary[$auth_type]);
	}

	//
	// Suppression d'une archive
	//
	if ($action == 'delete') {
		$log_ids = (array) filter_input(INPUT_POST, 'log_id',
			FILTER_VALIDATE_INT,
			FILTER_REQUIRE_ARRAY
		);
		$log_ids = array_filter($log_ids);

		if (count($log_ids) == 0) {
			$output->redirect('./view.php?mode=log', 4);
			$output->displayMessage('No_log_id');
		}

		if (isset($_POST['confirm'])) {
			$db->beginTransaction();

			$sql = "DELETE FROM " . LOG_TABLE . "
				WHERE log_id IN(" . implode(', ', $log_ids) . ")";
			$db->query($sql);

			$attach = new Attach();
			$attach->deleteFiles($log_ids);

			$db->commit();

			//
			// Optimisation des tables
			//
			$db->vacuum([LOG_TABLE, LOG_FILES_TABLE, JOINED_FILES_TABLE]);

			$target = './view.php?mode=log';
			$output->redirect($target, 4);
			$output->addLine($lang['Message']['logs_deleted']);
			$output->addLine($lang['Click_return_logs'], $target);
			$output->displayMessage();
		}
		else {
			foreach ($log_ids as $log_id) {
				$output->addHiddenField('log_id[]', $log_id);
			}

			$output->page_header();

			$output->set_filenames(['body' => 'confirm_body.tpl']);

			$output->assign_vars([
				'L_CONFIRM' => $lang['Title']['confirm'],

				'TEXTE' => $lang['Delete_logs'],
				'L_YES' => $lang['Yes'],
				'L_NO'  => $lang['No'],

				'S_HIDDEN_FIELDS' => $output->getHiddenFields(),
				'U_FORM' => 'view.php?mode=log&amp;action=delete'
			]);

			$output->pparse('body');

			$output->page_footer();
		}
	}

	$log_id = (int) filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
	$get_string = '';

	//
	// Classement
	//
	if ($sql_type == 'log_subject' || $sql_type == 'log_date') {
		$get_string .= '&amp;type=' . $sql_type;
	}
	else {
		$sql_type = 'log_date';
	}

	if ($sql_order == 'ASC' || $sql_order == 'DESC') {
		$get_string .= '&amp;order=' . $sql_order;
	}
	else {
		$sql_order = 'DESC';
	}

	$log_per_page = 20;
	$start        = (($page_id - 1) * $log_per_page);

	$sql = "SELECT COUNT(log_id) AS total_logs
		FROM " . LOG_TABLE . "
		WHERE log_status = " . STATUS_SENT . "
			AND liste_id = " . $listdata['liste_id'];
	$result = $db->query($sql);

	$total_logs = $result->column('total_logs');

	$logdata  = '';
	$logrow   = [];
	$num_logs = 0;

	if ($total_logs) {
		$sql = "SELECT log_id, log_subject, log_date, log_body_text, log_body_html, log_numdest
			FROM " . LOG_TABLE . "
			WHERE log_status = " . STATUS_SENT . "
				AND liste_id = $listdata[liste_id]
			ORDER BY $sql_type " . $sql_order . "
			LIMIT $log_per_page OFFSET $start";
		$result = $db->query($sql);

		while ($row = $result->fetch()) {
			if ($action == 'view' && $log_id == $row['log_id']) {
				$logdata = $row;
				$logdata['joined_files'] = [];
			}

			$logrow[] = $row;
		}

		$sql = "SELECT COUNT(jf.file_id) as num_files, l.log_id
			FROM " . JOINED_FILES_TABLE . " AS jf
				INNER JOIN " . LOG_TABLE . " AS l ON l.liste_id = $listdata[liste_id]
				INNER JOIN " . LOG_FILES_TABLE . " AS lf ON lf.log_id = l.log_id
			WHERE jf.file_id = lf.file_id
			GROUP BY l.log_id";
		$result = $db->query($sql);

		$files_count = [];
		while ($row = $result->fetch()) {
			$files_count[$row['log_id']] = $row['num_files'];
		}

		$total_pages = ceil($total_logs / $log_per_page);
		if ($page_id > 1) {
			$output->addLink(
				'prev',
				'./view.php?mode=log' . $get_string . '&amp;page=' . ($page_id - 1),
				$lang['Prev_page']
			);
		}

		if ($page_id < $total_pages) {
			$output->addLink(
				'next',
				'./view.php?mode=log' . $get_string . '&amp;page=' . ($page_id + 1),
				$lang['Next_page']
			);
		}

		if (is_array($logdata) && !empty($files_count[$log_id])) {
			$sql = "SELECT jf.file_id, jf.file_real_name, jf.file_physical_name, jf.file_size, jf.file_mimetype
				FROM " . JOINED_FILES_TABLE . " AS jf
					INNER JOIN " . LOG_FILES_TABLE . " AS lf ON lf.file_id = jf.file_id
					INNER JOIN " . LOG_TABLE . " AS l ON l.log_id = lf.log_id
						AND l.liste_id = $listdata[liste_id]
						AND l.log_id   = $log_id
				ORDER BY jf.file_real_name ASC";
			$result = $db->query($sql);

			$logdata['joined_files'] = $result->fetchAll();
		}
	}

	$navigation = navigation('view.php?mode=log' . $get_string, $total_logs, $log_per_page, $page_id);

	$get_string .= ($page_id > 1) ? '&amp;page=' . $page_id : '';

	$output->page_header();

	$output->set_filenames(['body' => 'view_logs_body.tpl']);

	$output->assign_vars([
		'L_EXPLAIN'             => nl2br($lang['Explain']['logs']),
		'L_TITLE'               => sprintf($lang['Title']['logs'], htmlspecialchars($listdata['liste_name'])),
		'L_CLASSEMENT'          => $lang['Classement'],
		'L_BY_SUBJECT'          => $lang['By_subject'],
		'L_BY_DATE'             => $lang['By_date'],
		'L_BY_ASC'              => $lang['By_asc'],
		'L_BY_DESC'             => $lang['By_desc'],
		'L_CLASSER_BUTTON'      => $lang['Button']['classer'],
		'L_SUBJECT'             => $lang['Log_subject'],
		'L_DATE'                => $lang['Log_date'],

		'SELECTED_TYPE_SUBJECT' => $output->getBoolAttr('selected', ($sql_type == 'log_subject')),
		'SELECTED_TYPE_DATE'    => $output->getBoolAttr('selected', ($sql_type == 'log_date')),
		'SELECTED_ORDER_ASC'    => $output->getBoolAttr('selected', ($sql_order == 'ASC')),
		'SELECTED_ORDER_DESC'   => $output->getBoolAttr('selected', ($sql_order == 'DESC')),

		'PAGINATION'            => $navigation,
		'PAGEOF'                => ($total_logs > 0) ? sprintf($lang['Page_of'], $page_id, ceil($total_logs / $log_per_page)) : '',
		'NUM_LOGS'              => ($total_logs > 0) ? '[ <b>' . $total_logs . '</b> ' . $lang['Module']['log'] . ' ]' : '',

		'PAGING'                => $get_string
	]);

	if ($num_logs = count($logrow)) {
		$display_checkbox = false;
		if ($auth->check_auth(Auth::DEL, $listdata['liste_id'])) {
			$output->assign_block_vars('delete_option', [
				'L_DELETE' => $lang['Button']['del_logs']
			]);

			$display_checkbox = true;
		}

		for ($i = 0; $i < $num_logs; $i++) {
			if (!empty($files_count[$logrow[$i]['log_id']])) {
				if ($files_count[$logrow[$i]['log_id']] > 1) {
					$s_title_clip = sprintf($lang['Joined_files'], $files_count[$logrow[$i]['log_id']]);
				}
				else {
					$s_title_clip = $lang['Joined_file'];
				}

				$s_clip = '<img src="../templates/images/icon_clip.png" width="10" height="13" alt="@" title="' . $s_title_clip . '" />';
			}
			else {
				$s_clip = '&nbsp;&nbsp;';
			}

			$output->assign_block_vars('logrow', [
				'ITEM_CLIP'   => $s_clip,
				'LOG_SUBJECT' => htmlspecialchars(cut_str($logrow[$i]['log_subject'], 60), ENT_NOQUOTES),
				'LOG_DATE'    => convert_time($admindata['admin_dateformat'], $logrow[$i]['log_date']),
				'U_VIEW'      => sprintf('view.php?mode=log&amp;action=view&amp;id=%d%s', $logrow[$i]['log_id'], $get_string)
			]);

			if ($display_checkbox) {
				$output->assign_block_vars('logrow.delete', [
					'LOG_ID' => $logrow[$i]['log_id']
				]);
			}
		}

		if ($action == 'view' && is_array($logdata)) {
			$format = (int) filter_input(INPUT_GET, 'format', FILTER_VALIDATE_INT);

			$output->set_filenames(['iframe_body' => 'iframe_body.tpl']);

			$output->assign_vars([
				'L_SUBJECT'  => $lang['Log_subject'],
				'L_NUMDEST'  => $lang['Log_numdest'],

				'SUBJECT'    => htmlspecialchars($logdata['log_subject'], ENT_NOQUOTES),
				'NUMDEST'    => $logdata['log_numdest'],
				'FORMAT'     => $format,
				'LOG_ID'     => $log_id
			]);

			if (extension_loaded('zip')) {
				$output->assign_block_vars('export', [
					'L_EXPORT_T' => $lang['Export_nl'],
					'L_EXPORT'   => $lang['Export']
				]);
			}

			if ($listdata['liste_format'] == FORMAT_MULTIPLE) {
				require 'includes/functions.box.php';

				// Par champ caché, car ce formulaire est en méthode GET.
				$output->addHiddenField('mode', 'log');
				$output->addHiddenField('action', 'view');
				$output->addHiddenField('id', $log_id);

				if ($page_id > 1) {
					$output->addHiddenField('page', $page_id);
				}

				$output->assign_block_vars('format_box', [
					'L_FORMAT'        => $lang['Format'],
					'L_GO_BUTTON'     => $lang['Button']['go'],
					'S_HIDDEN_FIELDS' => $output->getHiddenFields(),
					'FORMAT_BOX'      => format_box('format', $format, true)
				]);
			}

			$output->files_list($logdata, $format);
			$output->assign_var_from_handle('IFRAME', 'iframe_body');
		}
	}
	else {
		$output->assign_block_vars('empty', [
			'L_EMPTY' => $lang['No_log_sended']
		]);
	}
}

$output->pparse('body');

$output->page_footer();
