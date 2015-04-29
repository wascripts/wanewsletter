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
use Wamailer\Email;

const IN_PROFILCP = true;

require './includes/common.inc.php';

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
$auth = new Auth();
//
// End
//

function getAboDataList($abodata)
{
	global $db;

	$sql = "SELECT al.format, al.register_key, al.register_date, l.liste_id, l.liste_name, l.sender_email,
			l.return_email, l.liste_sig, l.liste_format, l.use_cron, l.liste_alias, l.form_url
		FROM " . ABO_LISTE_TABLE . " AS al
			INNER JOIN " . LISTE_TABLE . " AS l ON l.liste_id = al.liste_id
		WHERE al.abo_id = " . $abodata['abo_id'];
	$result = $db->query($sql);

	while ($row = $result->fetch()) {
		$abodata['listes'][$row['liste_id']] = $row;
	}

	return $abodata;
}

$mode = filter_input(INPUT_GET, 'mode');
// Spécial. la présence du paramètre 'k' signifie qu'on est dans le mode reset_passwd
$reset_key = filter_input(INPUT_GET, 'k');

if ($reset_key && !$mode) {
	$mode = 'reset_passwd';
}

if ($mode == 'login' || $mode == 'logout' || $mode == 'reset_passwd' || $mode == 'cp') {
	require './includes/login.inc.php';
}

if (!$auth->isLoggedIn()) {
	$session->reset();
	http_redirect('profil_cp.php?mode=login');
}

$abodata = $auth->getUserData($_SESSION['uid']);
$abodata = getAboDataList($abodata);

if (empty($abodata['abo_lang'])) {
	$abodata['abo_lang'] = $nl_config['language'];
}

// Crade, mais on fera avec pour l'instant.
$abodata['admin_lang'] = $abodata['abo_lang'];
load_settings($abodata);

$other_tags = wan_get_tags();

switch ($mode) {
	case 'editprofile':
		if (isset($_POST['submit'])) {
			$vararray = array('new_email', 'confirm_email', 'pseudo', 'language',
				'current_passwd', 'new_passwd', 'confirm_passwd'
			);
			foreach ($vararray as $varname) {
				${$varname} = trim(u::filter_input(INPUT_POST, $varname));
			}

			if ($language == '' || !validate_lang($language)) {
				$language = $nl_config['language'];
			}

			if ($new_email != '') {
				if (strcmp($new_email, $confirm_email) != 0) {
					$error = true;
					$msg_error[] = $lang['Message']['Bad_confirm_email'];
				}
				else if (!Mailer::checkMailSyntax($new_email)) {
					$error = true;
					$msg_error[] = $lang['Message']['Invalid_email'];
				}
				else {
					$sql = "SELECT COUNT(*) AS test
						FROM " . ABONNES_TABLE . "
						WHERE abo_email = '" . $db->escape($new_email) . "'";
					$result = $db->query($sql);

					if ($result->column('test') != 0) {
						$error = true;
						$msg_error[] = $lang['Message']['Allready_reg2'];
					}
				}
			}

			$set_password = false;
			if ($new_passwd != '') {
				$set_password = true;

				if (!password_verify($current_passwd, $abodata['passwd'])) {
					$error = true;
					$msg_error[] = $lang['Message']['Error_login'];
				}
				else if (!validate_pass($new_passwd)) {
					$error = true;
					$msg_error[] = $lang['Message']['Alphanum_pass'];
				}
				else if ($new_passwd !== $confirm_passwd) {
					$error = true;
					$msg_error[] = $lang['Message']['Bad_confirm_pass'];
				}
			}

			if (!$error) {
				$sql_data = array(
					'abo_pseudo' => strip_tags($pseudo),
					'abo_lang'   => $language
				);

				if ($set_password) {
					if (!($passwd_hash = password_hash($new_passwd, PASSWORD_DEFAULT))) {
						trigger_error("Unexpected error returned by password API", E_USER_ERROR);
					}
					$sql_data['abo_pwd'] = $passwd_hash;
				}

				if ($new_email != '') {
					$sql_data['abo_email'] = $new_email;
				}

				foreach ($other_tags as $tag) {
					$input_name = (!empty($tag['field_name'])) ? $tag['field_name'] : $tag['column_name'];
					$data = u::filter_input(INPUT_POST, $input_name);

					if (!is_null($data)) {
						$sql_data[$tag['column_name']] = trim($data);
					}
				}

				$db->update(ABONNES_TABLE, $sql_data, array('abo_id' => $abodata['uid']));

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
			'L_PASSWD'        => $lang['Password'],
			'L_NEW_PASSWD'    => $lang['New_passwd'],
			'L_CONFIRM_PASSWD'=> $lang['Confirm_passwd'],
			'L_VALID_BUTTON'  => $lang['Button']['valid'],

			'EMAIL'    => htmlspecialchars($abodata['email']),
			'PSEUDO'   => htmlspecialchars($abodata['username']),
			'LANG_BOX' => lang_box($abodata['abo_lang'])
		));

		foreach ($other_tags as $tag) {
			if (isset($abodata[$tag['column_name']])) {
				$output->assign_var($tag['tag_name'],
					htmlspecialchars($abodata[$tag['column_name']])
				);
			}
		}

		$output->pparse('body');
		break;

	case 'archives':
		if (isset($_POST['submit'])) {
			$listlog = (array) filter_input(INPUT_POST, 'log',
				FILTER_VALIDATE_INT,
				FILTER_REQUIRE_ARRAY
			);
			$listlog = array_filter($listlog);

			$sql_log_id = array();
			foreach ($listlog as $liste_id => $logs) {
				if (isset($abodata['listes'][$liste_id])) {
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

			while ($row = $result->fetch()) {
				$listdata = $abodata['listes'][$row['liste_id']];
				$format   = $abodata['listes'][$row['liste_id']]['format'];// = format choisi par l'abonné

				if ($listdata['liste_format'] != FORMAT_MULTIPLE) {
					$format = $listdata['liste_format'];
				}

				$email = new Email();
				$email->setFrom($listdata['sender_email'], $listdata['liste_name']);
				$email->setSubject($row['log_subject']);

				if ($abodata['username'] != '') {
					$email->addRecipient($abodata['email'], $abodata['username']);
				}
				else {
					$email->addRecipient($abodata['email']);
				}

				if ($listdata['return_email'] != '') {
					$email->setReturnPath($listdata['return_email']);
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
						$link = '<a href="' . htmlspecialchars($tmp_link) . '">' . $lang['Label_link'] . '</a>';
					}
				}

				$body = str_replace('{LINKS}', $link, $body);

				//
				// On s’occupe maintenant des fichiers joints ou incorporés.
				//
				if (isset($files[$row['log_id']]) && count($files[$row['log_id']]) > 0) {
					$total_files = count($files[$row['log_id']]);

					for ($i = 0; $i < $total_files; $i++) {
						$real_name     = $files[$row['log_id']][$i]['file_real_name'];
						$physical_name = $files[$row['log_id']][$i]['file_physical_name'];
						$mime_type     = $files[$row['log_id']][$i]['file_mimetype'];

						$file_path = WA_ROOTDIR . '/' . $nl_config['upload_path'] . $physical_name;
						if (!file_exists($file_path)) {
							continue;
						}

						$email->attach($file_path, $real_name, $mime_type);
					}
				}

				//
				// Traitement des tags et tags personnalisés
				//
				$tags_replace = array();

				if ($abodata['username'] != '') {
					$tags_replace['NAME'] = $abodata['username'];
					if ($format == FORMAT_HTML) {
						$tags_replace['NAME'] = htmlspecialchars($abodata['username']);
					}
				}
				else {
					$tags_replace['NAME'] = '';
				}

				if (count($other_tags) > 0) {
					foreach ($other_tags as $tag) {
						if ($abodata[$tag['column_name']] != '') {
							if (!is_numeric($abodata[$tag['column_name']]) && $format == FORMAT_HTML) {
								$tags_replace[$tag['tag_name']] = htmlspecialchars($abodata[$tag['column_name']]);
							}
							else {
								$tags_replace[$tag['tag_name']] = $abodata[$tag['column_name']];
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

				$tpl = new Template();
				$tpl->loadFromString('mail', $body);
				$tpl->assign_vars($tags_replace);
				$body = $tpl->pparse('mail', true);

				if ($format == FORMAT_TEXTE) {
					$email->setTextBody($body);
				}
				else {
					$email->setHTMLBody($body);
				}

				try {
					wan_sendmail($email);
				}
				catch (\Exception $e) {
					trigger_error(sprintf($lang['Message']['Failed_sending'],
						htmlspecialchars($e->getMessage())
					), E_USER_ERROR);
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
					. htmlspecialchars(cut_str($logrow['log_subject'], 40), ENT_NOQUOTES);
				$select_log .= ' [' . convert_time('d/m/Y', $logrow['log_date']) . ']</option>';
			}
			$select_log .= '</select>'."\n";

			$output->assign_block_vars('listerow', array(
				'LISTE_ID'   => $liste_id,
				'LISTE_NAME' => htmlspecialchars($listdata['liste_name']),
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
