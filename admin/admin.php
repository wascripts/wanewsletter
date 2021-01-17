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
use Wamailer\Mailer;
use Wamailer\Email;

require './start.inc.php';

$mode     = filter_input(INPUT_GET, 'mode');
$admin_id = (int) filter_input(INPUT_POST, 'uid', FILTER_VALIDATE_INT);

if (isset($_POST['cancel'])) {
	http_redirect('admin.php');
}

if (isset($_POST['delete_user'])) {
	$mode = 'deluser';
}

//
// Seuls les administrateurs peuvent ajouter ou supprimer un utilisateur
//
if (($mode == 'adduser' || $mode == 'deluser') && !Auth::isAdmin($admindata)) {
	http_response_code(401);
	$output->redirect('index.php', 4);
	$output->addLine($lang['Message']['Not_authorized']);
	$output->addLine($lang['Click_return_index'], './index.php');
	$output->message();
}

if ($mode == 'adduser') {
	$new_login = trim(u::filter_input(INPUT_POST, 'new_login'));
	$new_email = trim(u::filter_input(INPUT_POST, 'new_email'));

	$error = false;

	if (isset($_POST['submit'])) {
		if (!validate_pseudo($new_login)) {
			$error = true;
			$output->warn('Invalid_login');
		}
		else {
			$sql = "SELECT COUNT(*) AS login_test
				FROM " . ADMIN_TABLE . "
				WHERE admin_login = '" . $db->escape($new_login) . "'";
			$result = $db->query($sql);

			if ($result->column('login_test') > 0) {
				$error = true;
				$output->warn('Double_login');
			}
		}

		if (!Mailer::checkMailSyntax($new_email)) {
			$error = true;
			$output->warn('Invalid_email');
		}

		if (!$error) {
			$sql_data = [];
			$sql_data['admin_login']      = $new_login;
			$sql_data['admin_email']      = $new_email;
			$sql_data['admin_lang']       = $nl_config['language'];
			$sql_data['admin_dateformat'] = $nl_config['date_format'];
			$sql_data['admin_level']      = USER_LEVEL;

			$db->insert(ADMIN_TABLE, $sql_data);
			$admin_id = $db->lastInsertId();

			$template = '%s/languages/%s/emails/new_admin.txt';
			$template = new Template(sprintf($template, WA_ROOTDIR, $nl_config['language']));
			$template->assign([
				'PSEUDO'        => $new_login,
				'SITENAME'      => $nl_config['sitename'],
				'INIT_PASS_URL' => http_build_url('login.php?mode=cp')
			]);
			$message = $template->pparse(true);

			$email = new Email();
			$email->setFrom($admindata['admin_email'], $admindata['admin_login']);
			$email->addRecipient($new_email);
			$email->setSubject(sprintf($lang['Subject_email']['New_admin'], $nl_config['sitename']));
			$email->setTextBody($message);

			try {
				wamailer()->send($email);
			}
			catch (\Exception $e) {
				trigger_error(sprintf($lang['Message']['Failed_sending'],
					htmlspecialchars($e->getMessage())
				), E_USER_ERROR);
			}

			$output->redirect('./admin.php', 6);
			$output->addLine($lang['Message']['Admin_added']);
			$output->addLine($lang['Click_return_profile'], './admin.php?uid=' . $admin_id);
			$output->addLine($lang['Click_return_index'], './index.php');
			$output->message();
		}
	}

	$output->header();

	$template = new Template('add_admin_body.tpl');

	$template->assign([
		'L_TITLE'         => $lang['Add_user'],
		'L_EXPLAIN'       => nl2br($lang['Explain']['admin']),
		'L_LOGIN'         => $lang['Login'],
		'L_EMAIL'         => $lang['Email_address'],
		'L_VALID_BUTTON'  => $lang['Button']['valid'],
		'L_CANCEL_BUTTON' => $lang['Button']['cancel'],

		'LOGIN' => htmlspecialchars($new_login),
		'EMAIL' => htmlspecialchars($new_email)
	]);

	$template->pparse();
	$output->footer();
}
else if ($mode == 'deluser') {
	if ($admindata['admin_id'] == $admin_id) {
		$output->message('Owner_account');
	}

	if (isset($_POST['confirm'])) {
		$db->beginTransaction();
		$db->query("DELETE FROM " . ADMIN_TABLE . " WHERE admin_id = " . $admin_id);
		$db->query("DELETE FROM " . AUTH_ADMIN_TABLE . " WHERE admin_id = " . $admin_id);
		$db->commit();

		//
		// Optimisation des tables
		//
		$db->vacuum([ADMIN_TABLE, AUTH_ADMIN_TABLE]);

		$output->redirect('./admin.php', 6);
		$output->addLine($lang['Message']['Admin_deleted']);
		$output->addLine($lang['Click_return_profile'], './admin.php');
		$output->addLine($lang['Click_return_index'], './index.php');
		$output->message();
	}
	else {
		$output->addHiddenField('uid', $admin_id);

		$output->header();

		$template = new Template('confirm_body.tpl');

		$template->assign([
			'L_CONFIRM' => $lang['Title']['confirm'],

			'TEXTE' => $lang['Confirm_del_user'],
			'L_YES' => $lang['Yes'],
			'L_NO'  => $lang['No'],

			'S_HIDDEN_FIELDS' => $output->getHiddenFields(),
			'U_FORM' => 'admin.php?mode=deluser'
		]);

		$template->pparse();
		$output->footer();
	}
}

if (isset($_POST['submit'])) {
	if (!Auth::isAdmin($admindata) && $admin_id != $admindata['admin_id']) {
		http_response_code(401);
		$output->redirect('./index.php', 4);
		$output->addLine($lang['Message']['Not_authorized']);
		$output->addLine($lang['Click_return_index'], './index.php');
		$output->message();
	}

	$error = false;

	$vararray = ['current_passwd', 'new_passwd', 'confirm_passwd', 'email', 'date_format', 'language'];
	foreach ($vararray as $varname) {
		${$varname} = trim(u::filter_input(INPUT_POST, $varname));
	}

	if ($date_format == '') {
		$date_format = $nl_config['date_format'];
	}

	if ($language == '' || !validate_lang($language)) {
		$language = $nl_config['language'];
	}

	$vararray = ['email_new_subscribe', 'email_unsubscribe', 'html_editor'];
	foreach ($vararray as $varname) {
		${$varname} = (bool) filter_input(INPUT_POST, $varname, FILTER_VALIDATE_BOOLEAN);
	}

	$set_password = false;
	if ($new_passwd != '') {
		$set_password = true;

		if ($admin_id == $admindata['admin_id'] && !password_verify($current_passwd, $admindata['admin_pwd'])) {
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

	if (!Mailer::checkMailSyntax($email)) {
		$error = true;
		$output->warn('Invalid_email');
	}

	if (!$error) {
		$sql_data = [
			'admin_email'         => $email,
			'admin_dateformat'    => $date_format,
			'admin_lang'          => $language,
			'email_new_subscribe' => $email_new_subscribe,
			'email_unsubscribe'   => $email_unsubscribe,
			'html_editor'         => $html_editor
		];

		if ($set_password) {
			if (!($passwd_hash = password_hash($new_passwd, PASSWORD_DEFAULT))) {
				trigger_error("Unexpected error returned by password API", E_USER_ERROR);
			}
			$sql_data['admin_pwd'] = $passwd_hash;
		}

		if (Auth::isAdmin($admindata) && $admin_id != $admindata['admin_id']) {
			$admin_level = filter_input(INPUT_POST, 'admin_level', FILTER_VALIDATE_INT);

			if (is_int($admin_level) && in_array($admin_level, [ADMIN_LEVEL, USER_LEVEL])) {
				$sql_data['admin_level'] = $admin_level;
			}
		}

		$db->update(ADMIN_TABLE, $sql_data, ['admin_id' => $admin_id]);

		if (Auth::isAdmin($admindata)) {
			$liste_ids = (array) filter_input(INPUT_POST, 'liste_id',
				FILTER_VALIDATE_INT,
				FILTER_REQUIRE_ARRAY
			);
			$liste_ids = array_filter($liste_ids);

			$auth_list = [
				Auth::VIEW, Auth::EDIT, Auth::DEL, Auth::SEND, Auth::IMPORT,
				Auth::EXPORT, Auth::BAN, Auth::ATTACH
			];
			$auth_post = [];
			foreach ($auth_list as $auth_type) {
				$auth_post[$auth_type] = (array) filter_input(INPUT_POST, $auth_type,
					FILTER_VALIDATE_BOOLEAN,
					FILTER_REQUIRE_ARRAY
				);
			}

			$current_admin = $auth->getUserData($admin_id);
			$lists = $auth->getListData($admin_id);

			for ($i = 0, $total = count($liste_ids); $i < $total; $i++) {
				if (!isset($lists[$liste_ids[$i]])) {
					continue;
				}

				$sql_data = [];
				foreach ($auth_list as $auth_type) {
					if (isset($auth_post[$auth_type][$i])) {
						$sql_data[$auth_type] = $auth_post[$auth_type][$i];
					}
				}

				if (!isset($lists[$liste_ids[$i]]['auth_view'])) {
					$sql_data['admin_id'] = $admin_id;
					$sql_data['liste_id'] = $liste_ids[$i];

					$db->insert(AUTH_ADMIN_TABLE, $sql_data);
				}
				else {
					$sql_where = ['admin_id' => $admin_id, 'liste_id' => $liste_ids[$i]];
					$db->update(AUTH_ADMIN_TABLE, $sql_data, $sql_where);
				}
			}
		}

		$output->redirect('./admin.php', 6);
		$output->addLine($lang['Message']['Profile_updated']);
		$output->addLine($lang['Click_return_profile'], './admin.php?uid=' . $admin_id);
		$output->addLine($lang['Click_return_index'], './index.php');
		$output->message();
	}
}

$current_admin = $admindata;
$admin_box = '';

if (Auth::isAdmin($admindata)) {
	//
	// Récupération des données de l’utilisateur concerné.
	//
	$admin_id = filter_input(INPUT_GET, 'uid', FILTER_VALIDATE_INT);

	if (is_int($admin_id)) {
		$current_admin = $auth->getUserData($admin_id);
		if (!$current_admin) {
			$current_admin = $admindata;
		}
		else {
			$current_admin['lists'] = $auth->getListData($admin_id);
		}
	}

	//
	// Boîte déroulante de sélection d’utilisateur.
	//
	$sql = "SELECT admin_id, admin_login
		FROM " . ADMIN_TABLE . "
		WHERE admin_id <> $current_admin[admin_id]
		ORDER BY admin_login ASC";
	$result = $db->query($sql);

	$admin_box = '';
	if ($row = $result->fetch()) {
		$admin_box  = '<select id="uid" name="uid">';
		$admin_box .= '<option value="0">' . $lang['Choice_user'] . '</option>';

		do {
			$admin_box .= sprintf("<option value=\"%d\">%s</option>\n\t", $row['admin_id'], htmlspecialchars($row['admin_login'], ENT_NOQUOTES));
		}
		while ($row = $result->fetch());

		$admin_box .= '</select>';
	}
}

$output->addHiddenField('uid', $current_admin['admin_id']);

if (Auth::isAdmin($admindata)) {
	$output->addLink('subsection', './admin.php?mode=adduser', $lang['Add_user']);
}

$output->header();

$template = new Template('admin_body.tpl');

$template->assign([
	'L_TITLE'               => sprintf($lang['Title']['profile'], htmlspecialchars($current_admin['admin_login'], ENT_NOQUOTES)),
	'L_EXPLAIN'             => nl2br($lang['Explain']['admin']),
	'L_DEFAULT_LANG'        => $lang['Default_lang'],
	'L_EMAIL'               => $lang['Email_address'],
	'L_DATE_FORMAT'         => $lang['Dateformat'],
	'L_NOTE_DATE'           => sprintf($lang['Fct_date'], '<a href="http://www.php.net/date">', '</a>'),
	'L_EMAIL_NEW_SUBSCRIBE' => $lang['Email_new_subscribe'],
	'L_EMAIL_UNSUBSCRIBE'   => $lang['Email_unsubscribe'],
	'L_HTML_EDITOR'         => $lang['HTML_editor'],
	'L_PASSWD'              => $lang['Password'],
	'L_NEW_PASSWD'          => $lang['New_passwd'],
	'L_CONFIRM_PASSWD'      => $lang['Confirm_passwd'],
	'L_NOTE_PASSWD'         => nl2br($lang['Note_passwd']),
	'L_YES'                 => $lang['Yes'],
	'L_NO'                  => $lang['No'],
	'L_VALID_BUTTON'        => $lang['Button']['valid'],
	'L_RESET_BUTTON'        => $lang['Button']['reset'],

	'LANG_BOX'              => lang_box($current_admin['admin_lang']),
	'EMAIL'                 => $current_admin['admin_email'],
	'DATE_FORMAT'           => $current_admin['admin_dateformat'],
	'DEFAULT_DATE_FORMAT'   => DEFAULT_DATE_FORMAT,

	'EMAIL_NEW_SUBSCRIBE_YES' => $output->getBoolAttr('checked', ($current_admin['email_new_subscribe'] == SUBSCRIBE_NOTIFY_YES)),
	'EMAIL_NEW_SUBSCRIBE_NO'  => $output->getBoolAttr('checked', ($current_admin['email_new_subscribe'] == SUBSCRIBE_NOTIFY_NO)),

	'EMAIL_UNSUBSCRIBE_YES' => $output->getBoolAttr('checked', ($current_admin['email_unsubscribe'] == UNSUBSCRIBE_NOTIFY_YES)),
	'EMAIL_UNSUBSCRIBE_NO'  => $output->getBoolAttr('checked', ($current_admin['email_unsubscribe'] == UNSUBSCRIBE_NOTIFY_NO)),

	'HTML_EDITOR_YES'       => $output->getBoolAttr('checked', ($current_admin['html_editor'] == HTML_EDITOR_YES)),
	'HTML_EDITOR_NO'        => $output->getBoolAttr('checked', ($current_admin['html_editor'] == HTML_EDITOR_NO)),

	'S_HIDDEN_FIELDS'       => $output->getHiddenFields()
]);

if (Auth::isAdmin($admindata)) {
	$build_authbox = function ($auth_type, $listdata) use ($output, &$lang) {
		$selected_yes = $output->getBoolAttr('selected', !empty($listdata[$auth_type]));
		$selected_no  = $output->getBoolAttr('selected', empty($listdata[$auth_type]));

		$box_auth  = sprintf('<select name="%s[]">', $auth_type);
		$box_auth .= sprintf('<option value="1"%s>%s</option>', $selected_yes, $lang['Yes']);
		$box_auth .= sprintf('<option value="0"%s>%s</option>', $selected_no, $lang['No']);
		$box_auth .= '</select>';

		return $box_auth;
	};

	$template->assignToBlock('admin_options', [
		'L_ADD_ADMIN'     => $lang['Add_user'],
		'L_TITLE_MANAGE'  => $lang['Title']['manage'],
		'L_TITLE_OPTIONS' => $lang['Title']['other_options'],
		'L_ADMIN_LEVEL'   => $lang['User_level'],
		'L_LISTE_NAME'    => $lang['Liste_name2'],
		'L_VIEW'          => $lang['View'],
		'L_EDIT'          => $lang['Edit'],
		'L_DEL'           => $lang['Button']['delete'],
		'L_SEND'          => $lang['Button']['send'],
		'L_IMPORT'        => $lang['Import'],
		'L_EXPORT'        => $lang['Export'],
		'L_BAN'           => $lang['Ban'],
		'L_ATTACH'        => $lang['Attach'],
		'L_ADMIN'         => $lang['Admin'],
		'L_USER'          => $lang['User'],
		'L_DELETE_ADMIN'  => $lang['Del_user'],
		'L_NOTE_DELETE'   => nl2br($lang['Del_note']),

		'SELECTED_ADMIN'  => $output->getBoolAttr('selected', Auth::isAdmin($current_admin)),
		'SELECTED_USER'   => $output->getBoolAttr('selected', !Auth::isAdmin($current_admin))
	]);

	foreach ($current_admin['lists'] as $liste_id => $data) {
		$template->assignToBlock('admin_options.auth', [
			'LISTE_NAME'      => htmlspecialchars($data['liste_name']),
			'LISTE_ID'        => $liste_id,

			'BOX_AUTH_VIEW'   => $build_authbox(Auth::VIEW,   $data),
			'BOX_AUTH_EDIT'   => $build_authbox(Auth::EDIT,   $data),
			'BOX_AUTH_DEL'    => $build_authbox(Auth::DEL,    $data),
			'BOX_AUTH_SEND'   => $build_authbox(Auth::SEND,   $data),
			'BOX_AUTH_IMPORT' => $build_authbox(Auth::IMPORT, $data),
			'BOX_AUTH_EXPORT' => $build_authbox(Auth::EXPORT, $data),
			'BOX_AUTH_BACKUP' => $build_authbox(Auth::BAN,    $data),
			'BOX_AUTH_ATTACH' => $build_authbox(Auth::ATTACH, $data)
		]);
	}

	if ($admin_box) {
		$template->assignToBlock('admin_box', [
			'L_VIEW_PROFILE' => $lang['View_profile'],
			'L_BUTTON_GO'    => $lang['Button']['go'],

			'ADMIN_BOX'      => $admin_box
		]);
	}
}

if ($current_admin['admin_id'] == $admindata['admin_id']) {
	$template->assignToBlock('owner_profil');
}

$template->pparse();
$output->footer();
