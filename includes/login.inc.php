<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

if (substr($_SERVER['SCRIPT_FILENAME'], -8) == '.inc.php') {
	exit('<b>No hacking</b>');
}

$output->set_rootdir(sprintf('%s/templates/', WA_ROOTDIR));

$mode      = filter_input(INPUT_GET, 'mode');
$reset_key = filter_input(INPUT_GET, 'k');
$redirect  = (check_in_admin()) ? 'index.php' : 'profil_cp.php';
$redirect  = (!empty($_REQUEST['redirect'])) ? trim($_REQUEST['redirect']) : $redirect;

//
// Si la clé est fournie, on est forcément dans le mode 'reset_passwd'
//
if ($reset_key) {
	$mode = 'reset_passwd';
}

if ($mode == 'reset_passwd' || $mode == 'cp') {
	require WA_ROOTDIR . '/includes/functions.validate.php';

	if (!is_null($reset_key)) {
		if (!isset($_SESSION['reset_key']) || !hash_equals($_SESSION['reset_key'], $reset_key)) {
			$error = true;
			$msg_error[] = $lang['Message']['Invalid_token'];
		}
		else if (time() > $_SESSION['reset_key_expire']) {
			$error = true;
			$msg_error[] = $lang['Message']['Expired_token'];

			unset($_SESSION['reset_key']);
			unset($_SESSION['reset_key_expire']);
		}

		if (!$error) {
			$userdata = $auth->getUserData($_SESSION['uid']);

			if (isset($_POST['submit'])) {
				$passwd = filter_input(INPUT_POST, 'new_passwd');
				$confirm_passwd = filter_input(INPUT_POST, 'confirm_passwd');

				if (!validate_pass($passwd)) {
					$error = true;
					$msg_error[] = $lang['Message']['Alphanum_pass'];
				}
				else if ($passwd !== $confirm_passwd) {
					$error = true;
					$msg_error[] = $lang['Message']['Bad_confirm_pass'];
				}

				if (!$error) {
					$auth->updatePassword($userdata['uid'], $passwd);

					unset($_SESSION['reset_key']);
					unset($_SESSION['reset_key_expire']);

					if ($userdata['passwd'] == '') {
						$message_id = 'Password_created';
					}
					else {
						$message_id = 'Password_modified';
					}

					$output->addLine($lang['Message'][$message_id], $_SERVER['SCRIPT_NAME']);
					$output->displayMessage();
				}
			}

			if (strtotime('+5 min') > $_SESSION['reset_key_expire']) {
				$_SESSION['reset_key_expire'] = strtotime('+15 min');
			}

			$output->page_header();

			$output->set_filenames(array(
				'body' => 'reset_passwd.tpl'
			));

			$output->assign_vars(array(
				'TITLE'            => ($userdata['passwd'] == '')
					? $lang['Title']['Create_passwd'] : $lang['Title']['Reset_passwd'],
				'L_NEW_PASSWD'     => $lang['New_passwd'],
				'L_CONFIRM_PASSWD' => $lang['Confirm_passwd'],
				'L_VALID_BUTTON'   => $lang['Button']['valid'],

				'S_SCRIPT_NAME'    => $_SERVER['SCRIPT_NAME'],
				'S_RESETKEY'       => $reset_key
			));

			$output->pparse('body');

			$output->page_footer();
		}
	}

	$login_or_email = trim(filter_input(INPUT_POST, 'login_or_email'));

	if (!$error && isset($_POST['submit'])) {
		if (empty($login_or_email)) {
			$error = true;
			$msg_error[] = $lang['Message']['fields_empty'];
		}

		if (!$error) {
			$userdata = $auth->getUserData($login_or_email);

			if ($userdata) {
				$_SESSION['uid'] = $userdata['uid'];
				$_SESSION['reset_key'] = $reset_key = generate_key(12);
				$_SESSION['reset_key_expire'] = strtotime('+15 min');

				$tpl = new Template(WA_ROOTDIR . '/language/email_' . $nl_config['language'] . '/');
				$tpl->set_filenames(array('mail' => 'reset_passwd.txt'));
				$tpl->assign_vars(array(
					'PSEUDO'    => $userdata['username'],
					'RESET_URL' => wan_build_url($_SERVER['SCRIPT_NAME'].'?k='.$reset_key)
				));
				$message = $tpl->pparse('mail', true);

				$hostname = parse_url($nl_config['urlsite'], PHP_URL_HOST);

				$email = new \Wamailer\Email();
				$email->setFrom('postmaster@'.$hostname);
				$email->addRecipient($userdata['email']);
				$email->setSubject(($userdata['passwd'] == '')
					? $lang['Title']['Create_passwd'] : $lang['Title']['Reset_passwd']
				);
				$email->setTextBody($message);

				try {
					wan_sendmail($email);
				}
				catch (\Exception $e) {
					trigger_error(sprintf($lang['Message']['Failed_sending2'],
						htmlspecialchars($e->getMessage())
					), E_USER_ERROR);
				}
			}

			$message_id = (strpos($login_or_email, '@'))
				? 'Reset_using_email_ok' : 'Reset_using_username_ok';
			$output->displayMessage($message_id);
		}
	}

	$output->page_header();

	$output->set_filenames(array(
		'body' => 'lost_passwd.tpl'
	));

	$output->assign_vars(array(
		'TITLE'            => ($mode == 'cp')
			? $lang['Title']['Create_passwd'] : $lang['Title']['Reset_passwd'],
		'L_EXPLAIN'        => $lang['Explain']['Reset_passwd'],
		'L_LOGIN_OR_EMAIL' => $lang['Login_or_email'],
		'L_LOG_IN'         => $lang['Log_in'],
		'L_VALID_BUTTON'   => $lang['Button']['valid'],

		'S_MODE'           => $mode,
		'S_SCRIPT_NAME'    => $_SERVER['SCRIPT_NAME']
	));

	$output->pparse('body');

	$output->page_footer();
}
//
// Si l'utilisateur n'est pas connecté, on récupère les données et on démarre une nouvelle session
//
else if (isset($_POST['submit']) && !$auth->isLoggedIn()) {
	$login  = trim(filter_input(INPUT_POST, 'login'));
	$passwd = trim(filter_input(INPUT_POST, 'passwd'));

	if ($userdata = $auth->checkCredentials($login, $passwd)) {
		session_regenerate_id();
		$_SESSION['is_logged_in'] = true;
		$_SESSION['is_admin_session'] = check_in_admin();
		$_SESSION['uid'] = intval($userdata['uid']);
	}
	else {
		$error = true;
		$msg_error[] = $lang['Message']['Error_login'];
	}
}
//
// Déconnexion de l'administration
//
else if ($mode == 'logout') {
	if ($auth->isLoggedIn()) {
		session_destroy();
	}

	$error = true;
	$msg_error[] = $lang['Message']['Success_logout'];
}

//
// L'utilisateur est connecté ?
// Dans ce cas, on le redirige vers la page demandée
//
if ($auth->isLoggedIn()) {
	http_redirect($redirect);
}

$output->addHiddenField('redirect', htmlspecialchars($redirect));

$output->page_header();

$output->set_filenames(array(
	'body' => 'login.tpl'
));

$output->assign_vars(array(
	'TITLE'          => $lang['Module']['login'],
	'L_EXPLAIN'      => sprintf($lang['Explain']['login'],
		sprintf('<a href="%s">', $_SERVER['SCRIPT_NAME'].'?mode=cp'),
		'</a>'
	),
	'L_LOGIN'        => $lang['Login_or_email'],
	'L_PASSWD'       => $lang['Password'],
	'L_AUTOLOGIN'    => $lang['Autologin'],
	'L_RESET_PASSWD' => $lang['Reset_passwd'],
	'L_VALID_BUTTON' => $lang['Button']['valid'],

	'S_SCRIPT_NAME'   => $_SERVER['SCRIPT_NAME'],
	'S_HIDDEN_FIELDS' => $output->getHiddenFields()
));

if (!isset($_COOKIE[session_name()])) {
	$output->assign_block_vars('cookie_notice', array('L_TEXT' => $lang['Cookie_notice']));
}


$output->pparse('body');

$output->page_footer();
