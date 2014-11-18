<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

define('IN_NEWSLETTER', true);

require './includes/common.inc.php';

require WA_ROOTDIR . '/includes/functions.validate.php';
include WA_ROOTDIR . '/includes/tags.inc.php';

//
// Initialisation de la connexion à la base de données et récupération de la configuration
//
$db = WaDatabase($dsn);
$nl_config = wa_get_config();

if (!$nl_config['enable_profil_cp']) {
	load_settings();
	$output->displayMessage('Profil_cp_disabled');
}

//
// Instanciation d'une session
//
$session = new Session($nl_config);
//
// End
//

function getAboData($abo_id)
{
	global $db, $nl_config, $other_tags;

	//
	// Récupération des champs des tags personnalisés
	//
	if (count($other_tags) > 0) {
		$fields_str = '';
		foreach ($other_tags as $data) {
			$fields_str .= ', ' . $db->quote($data['column_name']);
		}
	}
	else {
		$fields_str = '';
	}

	if (!is_int($abo_id)) {
		$sql_where = "abo_email = '" . $db->escape($abo_id) . "'";
	}
	else {
		$sql_where = 'abo_id = ' . intval($abo_id);
	}

	$sql = "SELECT abo_id, abo_pseudo, abo_pwd, abo_email, abo_lang, abo_status $fields_str
		FROM " . ABONNES_TABLE . "
		WHERE " . $sql_where;
	$result = $db->query($sql);

	if (!($row = $result->fetch())) {
		return false;
	}

	$abodata = array();
	$abodata['id']       = $row['abo_id'];
	$abodata['pseudo']   = $row['abo_pseudo'];
	$abodata['passwd']   = $row['abo_pwd'];
	$abodata['email']    = $row['abo_email'];
	$abodata['language'] = $row['abo_lang'];
	$abodata['status']   = $row['abo_status'];
	$abodata['tags']     = array();
	$abodata['listes']   = array();

	if (empty($abodata['language'])) {
		$abodata['language'] = $nl_config['language'];
	}

	foreach ($other_tags as $tag) {
		if (isset($row[$tag['column_name']])) {
			$abodata['tags'][$tag['column_name']] = $row[$tag['column_name']];
		}
	}

	$sql = "SELECT al.format, al.register_key, al.register_date, l.liste_id, l.liste_name, l.sender_email,
			l.return_email, l.liste_sig, l.liste_format, l.use_cron, l.liste_alias, l.form_url
		FROM " . ABO_LISTE_TABLE . " AS al
			INNER JOIN " . LISTE_TABLE . " AS l ON l.liste_id = al.liste_id
		WHERE al.abo_id = " . $row['abo_id'];
	$result = $db->query($sql);

	while ($row = $result->fetch()) {
		$abodata['listes'][$row['liste_id']] = $row;
	}

	return $abodata;
}

$mode = (!empty($_GET['mode'])) ? $_GET['mode'] : '';

//
// Vérification de l'authentification
//
if ($mode != 'login' && $mode != 'sendkey') {
	if (!$_SESSION['is_logged_in'] || $_SESSION['is_admin_session']) {
		$session->reset();
		http_redirect('profil_cp.php?mode=login');
	}

	$abodata = getAboData($_SESSION['uid']);
	load_settings(array('admin_lang' => $abodata['language']));
}

switch ($mode) {
	case 'logout':
		session_destroy();
		$error = true;
		$msg_error[] = $lang['Message']['Success_logout'];
	case 'login':
		$email  = (!empty($_POST['email'])) ? trim($_POST['email']) : '';
		$regkey = (!empty($_POST['passwd'])) ? trim($_POST['passwd']) : '';

		if (isset($_POST['submit'])) {
			if ($abodata = getAboData($email)) {
				$auth_ok = false;

				if (strcmp($abodata['passwd'], md5($regkey)) != 0) {
					foreach ($abodata['listes'] as $listdata) {
						if (strcmp($regkey, $listdata['register_key']) == 0) {
							$auth_ok = true;
							break;
						}
					}
				}
				else {
					$auth_ok = true;
				}

				if ($auth_ok) {
					if ($abodata['status'] != ABO_ACTIF) {
						$output->displayMessage('Inactive_account');
					}

					session_regenerate_id();
					$_SESSION['is_logged_in'] = true;
					$_SESSION['is_admin_session'] = false;
					$_SESSION['uid'] = intval($abodata['id']);

					http_redirect('profil_cp.php');
				}
			}

			$error = true;
			$msg_error[] = $lang['Message']['Error_login'];
		}

		$output->page_header();

		$output->set_filenames(array(
			'body' => 'login_body.tpl'
		));

		$output->assign_vars(array(
			'TITLE'          => $lang['Module']['login'],
			'L_LOGIN'        => $lang['Account_login'],
			'L_PASS'         => $lang['Account_pass'],
			'L_SENDKEY'      => $lang['Lost_password'],
			'L_VALID_BUTTON' => $lang['Button']['valid'],

			'S_LOGIN' => wan_htmlspecialchars($email)
		));

		if (!isset($_COOKIE[session_name()])) {
			$output->assign_block_vars('cookie_notice', array('L_TEXT' => $lang['Cookie_notice']));
		}

		$output->pparse('body');
		break;

	case 'sendkey':
		$email = (!empty($_POST['email'])) ? trim($_POST['email']) : '';

		if (isset($_POST['submit'])) {
			if ($abodata = getAboData($email)) {
				list($liste_id, $listdata) = each($abodata['listes']);

				$mailer = new Mailer(WA_ROOTDIR . '/language/email_' . $nl_config['language'] . '/');
				$mailer->signature = WA_X_MAILER;

				if ($nl_config['use_smtp']) {
					$mailer->use_smtp(
						$nl_config['smtp_host'],
						$nl_config['smtp_port'],
						$nl_config['smtp_user'],
						$nl_config['smtp_pass']
					);
				}

				$mailer->set_charset('UTF-8');
				$mailer->set_format(FORMAT_TEXTE);

				if ($abodata['pseudo'] != '') {
					$address = array($abodata['pseudo'] => $abodata['email']);
				}
				else {
					$address = $abodata['email'];
				}

				$mailer->set_from($listdata['sender_email'], $listdata['liste_name']);
				$mailer->set_address($address);
				$mailer->set_subject($lang['Subject_email']['Sendkey']);
				$mailer->set_return_path($listdata['return_email']);

				$mailer->use_template('account_info', array(
					'EMAIL'   => $abodata['email'],
					'CODE'    => $listdata['register_key'],
					'URLSITE' => $nl_config['urlsite'],
					'SIG'     => $listdata['liste_sig'],
					'PSEUDO'  => $abodata['pseudo']
				));

				if (count($other_tags) > 0) {
					$tags = array();
					foreach ($other_tags as $tag) {
						$tags[$tag['tag_name']] = $abodata['tags'][$tag['column_name']];
					}

					$mailer->assign_tags($tags);
				}

				if (!$mailer->send()) {
					trigger_error('Failed_sending', E_USER_ERROR);
				}

				$output->displayMessage('IDs_sended');
			}

			$error = true;
			$msg_error[] = $lang['Message']['Unknown_email'];
		}

		$output->page_header();

		$output->set_filenames(array(
			'body' => 'sendkey_body.tpl'
		));

		$output->assign_vars(array(
			'TITLE'          => $lang['Title']['sendkey'],
			'L_EXPLAIN'      => nl2br($lang['Explain']['sendkey']),
			'L_LOGIN'        => $lang['Account_login'],
			'L_VALID_BUTTON' => $lang['Button']['valid']
		));

		$output->pparse('body');
		break;

	case 'editprofile':
		if (isset($_POST['submit'])) {
			$vararray = array('new_email', 'confirm_email', 'pseudo', 'language', 'current_pass', 'new_pass', 'confirm_pass');
			foreach ($vararray as $varname) {
				${$varname} = (!empty($_POST[$varname])) ? trim($_POST[$varname]) : '';
			}

			if ($language == '' || !validate_lang($language)) {
				$language = $nl_config['language'];
			}

			if ($new_email != '') {
				if (strcmp($new_email, $confirm_email) != 0) {
					$error = true;
					$msg_error[] = $lang['Message']['Bad_confirm_email'];
				}
				else if (!Mailer::validate_email($new_email)) {
					$error = true;
					$msg_error[] = $lang['Message']['Invalid_email'];
				}
				else {
					$sql = "SELECT COUNT(*) AS test
						FROM " . ABONNES_TABLE . "
						WHERE LOWER(abo_email) = '" . $db->escape(strtolower($new_email)) . "'";
					$result = $db->query($sql);

					if ($result->column('test') != 0) {
						$error = true;
						$msg_error[] = $lang['Message']['Allready_reg2'];
					}
				}
			}

			if ($current_pass != '' && md5($current_pass) != $abodata['passwd']) {
				$error = true;
				$msg_error[] = $lang['Message']['Error_login'];
			}

			$set_password = false;
			if ($new_pass != '' && $confirm_pass != '') {
				if (!validate_pass($new_pass)) {
					$error = true;
					$msg_error[] = $lang['Message']['Alphanum_pass'];
				}
				else if ($new_pass != $confirm_pass) {
					$error = true;
					$msg_error[] = $lang['Message']['Bad_confirm_pass'];
				}

				$set_password = true;
			}

			if (!$error) {
				$sql_data = array(
					'abo_pseudo' => strip_tags($pseudo),
					'abo_lang'   => $language
				);

				if ($set_password) {
					$sql_data['abo_pwd'] = md5($new_pass);
				}

				if ($new_email != '') {
					$sql_data['abo_email'] = $new_email;
				}

				foreach ($other_tags as $tag) {
					if (!empty($tag['field_name']) && !empty($_REQUEST[$tag['field_name']])) {
						$sql_data[$tag['column_name']] = $_REQUEST[$tag['field_name']];
					}
					else if (!empty($_REQUEST[$tag['column_name']])) {
						$sql_data[$tag['column_name']] = $_REQUEST[$tag['column_name']];
					}
				}

				$db->update(ABONNES_TABLE, $sql_data, array('abo_id' => $abodata['id']));

				$output->redirect('profil_cp.php', 4);
				$output->displayMessage('Profile_updated');
			}
		}

		require WA_ROOTDIR . '/includes/functions.box.php';

		$output->page_header();

		$output->set_filenames(array(
			'body' => 'editprofile_body.tpl'
		));

		$output->assign_vars(array(
			'TITLE'           => $lang['Module']['editprofile'],
			'L_EXPLAIN'       => nl2br($lang['Explain']['editprofile']),
			'L_EXPLAIN_EMAIL' => nl2br($lang['Explain']['change_email']),
			'L_EMAIL'         => $lang['Email_address'],
			'L_NEW_EMAIL'     => $lang['New_Email'],
			'L_CONFIRM_EMAIL' => $lang['Confirm_Email'],
			'L_PSEUDO'        => $lang['Abo_pseudo'],
			'L_LANG'          => $lang['Default_lang'],
			'L_NEW_PASS'      => $lang['New_pass'],
			'L_CONFIRM_PASS'  => $lang['Conf_pass'],
			'L_VALID_BUTTON'  => $lang['Button']['valid'],

			'EMAIL'    => $abodata['email'],
			'PSEUDO'   => $abodata['pseudo'],
			'LANG_BOX' => lang_box($abodata['language'])
		));

		foreach ($other_tags as $tag) {
			if (isset($abodata['tags'][$tag['column_name']])) {
				$output->assign_var($tag['tag_name'],
					wan_htmlspecialchars($abodata['tags'][$tag['column_name']])
				);
			}
		}

		if ($abodata['passwd'] != '') {
			$output->assign_block_vars('password', array(
				'L_PASS' => $lang['Password']
			));
		}

		$output->pparse('body');
		break;

	case 'archives':
		if (isset($_POST['submit'])) {
			$listlog = (!empty($_POST['log'])) ? (array) $_POST['log'] : array();

			$sql_log_id = array();
			foreach ($listlog as $liste_id => $logs) {
				if (isset($abodata['listes'][$liste_id])) {
					$logs = array_map('intval', $logs);
					$sql_log_id = array_merge($sql_log_id, $logs);
				}
			}

			if (count($sql_log_id) == 0) {
				$output->displayMessage('No_log_id');
			}

			$sql = "SELECT lf.log_id, jf.file_id, jf.file_real_name,
					jf.file_physical_name, jf.file_size, jf.file_mimetype
				FROM " . JOINED_FILES_TABLE . " AS jf
					INNER JOIN " . LOG_FILES_TABLE . " AS lf ON lf.file_id = jf.file_id
						AND lf.log_id IN(" . implode(', ', $sql_log_id) . ")";
			$result = $db->query($sql);

			$files = array();
			while ($row = $result->fetch()) {
				$files[$row['log_id']][] = $row;
			}

			$sql = "SELECT liste_id, log_id, log_subject, log_body_text, log_body_html
				FROM " . LOG_TABLE . "
				WHERE log_id IN(" . implode(', ', $sql_log_id) . ")
					AND log_status = " . STATUS_SENT;
			$result = $db->query($sql);

			//
			// Initialisation de la classe mailer
			//
			$mailer = new Mailer(WA_ROOTDIR . '/language/email_' . $nl_config['language'] . '/');
			$mailer->signature = WA_X_MAILER;

			if ($nl_config['use_smtp']) {
				$mailer->use_smtp(
					$nl_config['smtp_host'],
					$nl_config['smtp_port'],
					$nl_config['smtp_user'],
					$nl_config['smtp_pass']
				);
			}

			$mailer->set_charset('UTF-8');

			if ($abodata['pseudo'] != '') {
				$address = array($abodata['pseudo'] => $abodata['email']);
			}
			else {
				$address = $abodata['email'];
			}

			while ($row = $result->fetch()) {
				$listdata = $abodata['listes'][$row['liste_id']];
				$format   = $abodata['listes'][$row['liste_id']]['format'];

				$mailer->clear_all();
				$mailer->set_from($listdata['sender_email'], $listdata['liste_name']);
				$mailer->set_address($address);
				$mailer->set_format($format);
				$mailer->set_subject($row['log_subject']);

				if ($listdata['return_email'] != '') {
					$mailer->set_return_path($listdata['return_email']);
				}

				if ($format == FORMAT_TEXTE) {
					$body = $row['log_body_text'];
				}
				else {
					$body = $row['log_body_html'];
				}

				//
				// Ajout du lien de désinscription, selon le format utilisé
				//
				if ($listdata['use_cron']) {
					$liste_email = (!empty($listdata['liste_alias']))
						? $listdata['liste_alias'] : $listdata['sender_email'];

					if ($format == FORMAT_TEXTE) {
						$link = $liste_email;
					}
					else {
						$link = '<a href="mailto:' . $liste_email . '?subject=unsubscribe">' . $lang['Label_link'] . '</a>';
					}
				}
				else {
					$tmp_link  = $listdata['form_url'] . ((strstr($listdata['form_url'], '?')) ? '&' : '?') . '{CODE}';

					if ($format == FORMAT_TEXTE) {
						$link = $tmp_link;
					}
					else {
						$link = '<a href="' . wan_htmlspecialchars($tmp_link) . '">' . $lang['Label_link'] . '</a>';
					}
				}

				$body = str_replace('{LINKS}', $link, $body);
				$mailer->set_message($body);

				//
				// On s'occupe maintenant des fichiers joints ou incorporés
				// Si les fichiers sont stockés sur un serveur ftp, on les rapatrie le temps du flot d'envoi
				//
				if (isset($files[$row['log_id']]) && count($files[$row['log_id']]) > 0) {
					$total_files = count($files[$row['log_id']]);
					$tmp_files	 = array();

					$attach = new Attach();

					hasCidReferences($body, $refs);

					for ($i = 0; $i < $total_files; $i++) {
						$real_name     = $files[$row['log_id']][$i]['file_real_name'];
						$physical_name = $files[$row['log_id']][$i]['file_physical_name'];
						$mime_type     = $files[$row['log_id']][$i]['file_mimetype'];

						$error = false;
						$msg   = array();

						$attach->joined_file_exists($physical_name, $error, $msg);

						if ($error) {
							$error = false;
							continue;
						}

						if ($nl_config['use_ftp']) {
							$file_path = $attach->ftp_to_tmp($files[$row['log_id']][$i]);
							$tmp_files[] = $file_path;
						}
						else {
							$file_path = WA_ROOTDIR . '/' . $nl_config['upload_path'] . $physical_name;
						}

						if (is_array($refs) && in_array($real_name, $refs)) {
							$embedded = true;
						}
						else {
							$embedded = false;
						}

						$mailer->attachment($file_path, $real_name, 'attachment', $mime_type, $embedded);
					}
				}

				//
				// Traitement des tags et tags personnalisés
				//
				$tags_replace = array();

				if ($abodata['pseudo'] != '') {
					$tags_replace['NAME'] = $abodata['pseudo'];
					if ($format == FORMAT_HTML) {
						$tags_replace['NAME'] = wan_htmlspecialchars($abodata['pseudo']);
					}
				}
				else {
					$tags_replace['NAME'] = '';
				}

				if (count($other_tags) > 0) {
					foreach ($other_tags as $tag) {
						if ($abodata['tags'][$tag['column_name']] != '') {
							if (!is_numeric($abodata['tags'][$tag['column_name']]) && $format == FORMAT_HTML) {
								$tags_replace[$tag['tag_name']] = wan_htmlspecialchars($abodata['tags'][$tag['column_name']]);
							}
							else {
								$tags_replace[$tag['tag_name']] = $abodata['tags'][$tag['column_name']];
							}

							continue;
						}

						$tags_replace[$tag['tag_name']] = '';
					}
				}

				if (!$listdata['use_cron']) {
					$tags_replace = array_merge($tags_replace, array(
						'CODE'  => $listdata['register_key'],
						'EMAIL' => rawurlencode($abodata['email'])
					));
				}

				$mailer->assign_tags($tags_replace);

				// envoi
				if (!$mailer->send()) {
					trigger_error('Failed_sending', E_USER_ERROR);
				}
			}

			$output->displayMessage(sprintf($lang['Message']['Logs_sent'], $abodata['email']));
		}

		$liste_ids = array();
		foreach ($abodata['listes'] as $liste_id => $listdata) {
			$liste_ids[] = $liste_id;
		}

		$sql = "SELECT log_id, liste_id, log_subject, log_date
			FROM " . LOG_TABLE . "
			WHERE liste_id IN(" . implode(', ', $liste_ids) . ")
				AND log_status = " . STATUS_SENT . "
			ORDER BY log_date DESC";
		$result = $db->query($sql);

		while ($row = $result->fetch()) {
			$abodata['listes'][$row['liste_id']]['archives'][] = $row;
		}

		$output->page_header();

		$output->set_filenames(array(
			'body' => 'archives_body.tpl'
		));

		$output->assign_vars(array(
			'TITLE'           => $lang['Title']['archives'],
			'L_EXPLAIN'       => $lang['Explain']['archives'],
			'L_VALID_BUTTON'  => $lang['Button']['valid'],

			'S_HIDDEN_FIELDS' => $output->getHiddenFields()
		));

		foreach ($abodata['listes'] as $liste_id => $listdata) {
			if (!isset($abodata['listes'][$liste_id]['archives'])) {
				continue;
			}

			$num_logs = count($abodata['listes'][$liste_id]['archives']);
			$size     = ($num_logs > 8) ? 8 : $num_logs;

			$select_log = '<select id="liste_' . $liste_id . '" name="log['
				. $liste_id . '][]" class="logList" size="' . $size
				. '" multiple="multiple" style="min-width: 200px;">';
			for ($i = 0; $i < $num_logs; $i++) {
				$logrow = $abodata['listes'][$liste_id]['archives'][$i];

				$select_log .= '<option value="' . $logrow['log_id'] . '"> &#8211; '
					. wan_htmlspecialchars(cut_str($logrow['log_subject'], 40), ENT_NOQUOTES);
				$select_log .= ' [' . convert_time('d/m/Y', $logrow['log_date']) . ']</option>';
			}
			$select_log .= '</select>';

			$output->assign_block_vars('listerow', array(
				'LISTE_ID'   => $liste_id,
				'LISTE_NAME' => wan_htmlspecialchars($listdata['liste_name']),
				'SELECT_LOG' => $select_log
			));
		}

		$output->pparse('body');
		break;

	default:
		$output->page_header();

		$output->set_filenames(array(
			'body' => 'index_body.tpl'
		));

		$output->assign_vars(array(
			'TITLE'     => $lang['Title']['profil_cp'],
			'L_EXPLAIN' => nl2br($lang['Welcome_profil_cp'])
		));

		$output->pparse('body');
		break;
}

$output->page_footer();
