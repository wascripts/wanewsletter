<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@webnaute.net>
 * @link      http://dev.webnaute.net/wanewsletter/
 * @copyright 2002-2021 Aurélien Maille
 * @license   https://www.gnu.org/licenses/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

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
	load_l10n();
	$output->message('Profil_cp_disabled');
}

//
// Instanciation d'une session
//
$session = new Session($nl_config);
$auth = new Auth();
//
// End
//

$error     = false;
$mode      = filter_input(INPUT_GET, 'mode');
// Spécial. la présence du paramètre 'k' signifie qu'on est dans le mode 'reset'
$reset_key = filter_input(INPUT_GET, 'k');

if ($reset_key && !$mode) {
	$mode = 'reset';
}

if ($mode == 'login' || $mode == 'logout' || $mode == 'reset' || $mode == 'cp') {
	require './includes/login.inc.php';
}

if (!$auth->isLoggedIn()) {
	$session->reset();
	http_redirect('profil_cp.php?mode=login');
}

$abodata = $auth->getUserData($_SESSION['uid']);

if (empty($abodata['abo_lang'])) {
	$abodata['abo_lang'] = $nl_config['language'];
}

load_l10n($abodata);

$other_tags = wan_get_tags();

switch ($mode) {
	case 'editprofile':
		if (isset($_POST['submit'])) {
			$vararray = ['new_email', 'confirm_email', 'username', 'language',
				'current_passwd', 'new_passwd', 'confirm_passwd'
			];
			foreach ($vararray as $varname) {
				${$varname} = utf8_normalize(trim(filter_input(INPUT_POST, $varname)));
			}

			if ($language == '' || !validate_lang($language)) {
				$language = $nl_config['language'];
			}

			if ($new_email != '') {
				if (strcmp($new_email, $confirm_email) != 0) {
					$error = true;
					$output->warn('Bad_confirm_email');
				}
				else if (!Mailer::checkMailSyntax($new_email)) {
					$error = true;
					$output->warn('Invalid_email');
				}
				else {
					$sql = "SELECT COUNT(*) AS test
						FROM " . ABONNES_TABLE . "
						WHERE abo_email = '" . $db->escape($new_email) . "'";
					$result = $db->query($sql);

					if ($result->column('test') != 0) {
						$error = true;
						$output->warn('Allready_reg2');
					}
				}
			}

			$set_password = false;
			if ($new_passwd != '') {
				$set_password = true;

				if (!password_verify($current_passwd, $abodata['passwd'])) {
					$error = true;
					$output->warn('Error_login');
				}
				else if (!validate_pass($new_passwd)) {
					$error = true;
					$output->warn('Alphanum_pass');
				}
				else if ($new_passwd !== $confirm_passwd) {
					$error = true;
					$output->warn('Bad_confirm_pass');
				}
			}

			if (!$error) {
				$sql_data = [
					'abo_pseudo' => strip_tags($username),
					'abo_lang'   => $language
				];

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
					$data = filter_input(INPUT_POST, $input_name);

					if (!is_null($data)) {
						$sql_data[$tag['column_name']] = utf8_normalize(trim($data));
					}
				}

				$db->update(ABONNES_TABLE, $sql_data, ['abo_id' => $abodata['uid']]);

				$output->redirect('profil_cp.php', 4);
				$output->message('Profile_updated');
			}
		}

		$output->header();

		$template = new Template('editprofile_body.tpl');

		$template->assign([
			'TITLE'           => $lang['Module']['editprofile'],
			'L_EXPLAIN'       => nl2br($lang['Explain']['editprofile']),
			'L_EXPLAIN_EMAIL' => nl2br($lang['Explain']['change_email']),
			'L_EMAIL'         => $lang['Email_address'],
			'L_NEW_EMAIL'     => $lang['New_Email'],
			'L_CONFIRM_EMAIL' => $lang['Confirm_Email'],
			'L_USERNAME'      => $lang['Login'],
			'L_LANG'          => $lang['Default_lang'],
			'L_PASSWD'        => $lang['Password'],
			'L_NEW_PASSWD'    => $lang['New_passwd'],
			'L_CONFIRM_PASSWD'=> $lang['Confirm_passwd'],
			'L_VALID_BUTTON'  => $lang['Button']['valid'],

			'EMAIL'           => htmlspecialchars($abodata['email']),
			'USERNAME'        => htmlspecialchars($abodata['username']),
			'LANG_BOX'        => lang_box($abodata['abo_lang'])
		]);

		foreach ($other_tags as $tag) {
			if (isset($abodata[$tag['column_name']])) {
				$template->assign([
					$tag['tag_name'] => htmlspecialchars($abodata[$tag['column_name']])
				]);
			}
		}
		break;

	case 'archives':
		// Données des listes
		$sql = "SELECT al.format, al.register_key, al.register_date,
				l.liste_id, l.liste_name, l.sender_email, l.return_email,
				l.liste_sig, l.liste_format, l.use_cron, l.liste_alias, l.form_url
			FROM %s AS l
				INNER JOIN %s AS al ON al.abo_id = %d
					AND al.liste_id = l.liste_id
			ORDER BY l.liste_name ASC";
		$sql = sprintf($sql, LISTE_TABLE, ABO_LISTE_TABLE, $abodata['uid']);
		$result = $db->query($sql);

		$abodata['lists'] = [];
		while ($listdata = $result->fetch($result::FETCH_ASSOC)) {
			$abodata['lists'][$listdata['liste_id']] = $listdata;
		}

		if (isset($_POST['submit'])) {
			$listlog = (array) filter_input(INPUT_POST, 'log',
				FILTER_VALIDATE_INT,
				FILTER_REQUIRE_ARRAY
			);
			$listlog = array_filter($listlog);

			$sql_log_id = [];
			foreach ($listlog as $liste_id => $logs) {
				if (isset($abodata['lists'][$liste_id])) {
					$sql_log_id = array_merge($sql_log_id, $logs);
				}
			}

			if (count($sql_log_id) == 0) {
				$output->message('No_log_id');
			}

			$sql = "SELECT lf.log_id, jf.file_id, jf.file_real_name,
					jf.file_physical_name, jf.file_size, jf.file_mimetype
				FROM " . JOINED_FILES_TABLE . " AS jf
					INNER JOIN " . LOG_FILES_TABLE . " AS lf ON lf.file_id = jf.file_id
						AND lf.log_id IN(" . implode(', ', $sql_log_id) . ")";
			$result = $db->query($sql);

			$files = [];
			while ($row = $result->fetch()) {
				$files[$row['log_id']][] = $row;
			}

			$sql = "SELECT liste_id, log_id, log_subject, log_body_text, log_body_html, log_status
				FROM " . LOG_TABLE . "
				WHERE log_id IN(" . implode(', ', $sql_log_id) . ")
					AND log_status = " . STATUS_SENT;
			$result = $db->query($sql);

			while ($logdata = $result->fetch()) {
				$listdata = $abodata['lists'][$logdata['liste_id']];
				$logdata['joined_files'] = [];
				$abodata['register_key'] = $listdata['register_key'];
				$abodata['format']       = $listdata['format'];// À ne pas confondre avec liste_format
				$abodata['name']         = $abodata['abo_pseudo'];

				if (isset($files[$logdata['log_id']])) {
					$logdata['joined_files'] = $files[$logdata['log_id']];
				}

				$sender = new Sender($listdata, $logdata);

				try {
					$sender->send($abodata);
				}
				catch (\Exception $e) {
					trigger_error(sprintf($lang['Message']['Failed_sending'],
						htmlspecialchars($e->getMessage())
					), E_USER_ERROR);
				}
			}

			$output->message(sprintf($lang['Message']['Logs_sent'], $abodata['email']));
		}

		$liste_ids = [];
		foreach ($abodata['lists'] as $liste_id => $listdata) {
			$liste_ids[] = $liste_id;
		}

		$sql = "SELECT log_id, liste_id, log_subject, log_date
			FROM " . LOG_TABLE . "
			WHERE liste_id IN(" . implode(', ', $liste_ids) . ")
				AND log_status = " . STATUS_SENT . "
			ORDER BY log_date DESC";
		$result = $db->query($sql);

		while ($row = $result->fetch()) {
			$abodata['lists'][$row['liste_id']]['archives'][] = $row;
		}

		$output->header();

		$template = new Template('archives_body.tpl');

		$template->assign([
			'TITLE'           => $lang['Title']['archives'],
			'L_EXPLAIN'       => $lang['Explain']['archives'],
			'L_VALID_BUTTON'  => $lang['Button']['valid']
		]);

		foreach ($abodata['lists'] as $liste_id => $listdata) {
			if (!isset($abodata['lists'][$liste_id]['archives'])) {
				continue;
			}

			$num_logs   = count($abodata['lists'][$liste_id]['archives']);
			$select_log = '<select id="liste_%1$d" name="log[%1$d][]" class="logList" size="%2$d" multiple>';
			$select_log = sprintf($select_log, $liste_id, min(8, $num_logs));

			for ($i = 0; $i < $num_logs; $i++) {
				$logdata = $abodata['lists'][$liste_id]['archives'][$i];

				$select_log .= sprintf('<option value="%d">%s – [%s]</option>',
					$logdata['log_id'],
					htmlspecialchars($logdata['log_subject'], ENT_NOQUOTES),
					convert_time('d/m/Y', $logdata['log_date'])
				);
			}
			$select_log .= '</select>'."\n";

			$template->assignToBlock('listerow', [
				'LISTE_ID'   => $liste_id,
				'LISTE_NAME' => htmlspecialchars($listdata['liste_name']),
				'SELECT_LOG' => $select_log
			]);
		}
		break;

	default:
		$output->header();

		$template = new Template('index_body.tpl');

		$template->assign([
			'TITLE'     => $lang['Title']['profil_cp'],
			'L_EXPLAIN' => nl2br($lang['Welcome_profil_cp'])
		]);
		break;
}

$template->pparse();
$output->footer();
