<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

define('IN_NEWSLETTER', true);
define('IN_LOGIN', true);

require './pagestart.php';

$simple_header = true;

$mode     = (!empty($_REQUEST['mode'])) ? $_REQUEST['mode'] : '';
$redirect = (!empty($_REQUEST['redirect'])) ? trim($_REQUEST['redirect']) : 'index.php';

//
// Mot de passe perdu
//
if ($mode == 'sendpass') {
	$login = (!empty($_POST['login'])) ? trim($_POST['login']) : '';
	$email = (!empty($_POST['email'])) ? trim($_POST['email']) : '';

	if (isset($_POST['submit'])) {
		$sql = "SELECT admin_id
			FROM " . ADMIN_TABLE . "
			WHERE LOWER(admin_login) = '" . $db->escape(strtolower($login)) . "'
				AND admin_email = '" . $db->escape($email) . "'";
		$result = $db->query($sql);

		if (!($admin_id = $result->column('admin_id'))) {
			$error = true;
			$msg_error[] = $lang['Message']['Error_sendpass'];
		}

		if (!$error) {
			$new_password = generate_key(12);

			$mailer = new Mailer(WA_ROOTDIR . '/language/email_' . $nl_config['language'] . '/');
			$mailer->signature = WA_X_MAILER;

			if ($nl_config['use_smtp']) {
				$mailer->smtp_path = WAMAILER_DIR . '/';
				$mailer->use_smtp(
					$nl_config['smtp_host'],
					$nl_config['smtp_port'],
					$nl_config['smtp_user'],
					$nl_config['smtp_pass']
				);
			}

			$mailer->set_charset($lang['CHARSET']);
			$mailer->set_format(FORMAT_TEXTE);
			$mailer->set_from($email);
			$mailer->set_address($email);
			$mailer->set_subject($lang['Subject_email']['New_pass']);

			$mailer->use_template('new_admin_pass', array(
				'PSEUDO'   => $login,
				'PASSWORD' => $new_password
			));

			if (!$mailer->send()) {
				trigger_error('Failed_sending', E_USER_ERROR);
			}

			$hasher = new PasswordHash();

			$db->query("UPDATE " . ADMIN_TABLE . "
				SET admin_pwd = '" . $db->escape($hasher->hash($new_password)) . "'
				WHERE admin_id = " . $admin_id);

			$output->displayMessage('IDs_sended');
		}
	}

	$output->page_header();

	$output->set_filenames(array(
		'body' => 'sendpass_body.tpl'
	));

	$output->assign_vars(array(
		'TITLE'          => $lang['Title']['sendpass'],
		'L_LOGIN'        => $lang['Login'],
		'L_EMAIL'        => $lang['Email_address'],
		'L_VALID_BUTTON' => $lang['Button']['valid'],

		'S_LOGIN' => wan_htmlspecialchars($login),
		'S_EMAIL' => wan_htmlspecialchars($email)
	));

	$output->pparse('body');

	$output->page_footer();
}

//
// Si l'utilisateur n'est pas connecté, on récupère les données et on démarre une nouvelle session
//
else if (isset($_POST['submit']) && !$session->is_logged_in) {
	$login     = (!empty($_POST['login'])) ? trim($_POST['login']) : '';
	$passwd    = (!empty($_POST['passwd'])) ? trim($_POST['passwd']) : '';
	$autologin = false;// (!empty($_POST['autologin'])) ? true : false;

	$session->login($login, $passwd, $autologin);

	if (!$session->is_logged_in) {
		$error = true;
		$msg_error[] = $lang['Message']['Error_login'];
	}
}

//
// Déconnexion de l'administration
//
else if ($mode == 'logout') {
	if ($session->is_logged_in) {
		$session->logout($admindata['admin_id']);
	}

	$error = true;
	$msg_error[] = $lang['Message']['Success_logout'];
}

//
// L'utilisateur est connecté ?
// Dans ce cas, on le redirige vers la page demandée, ou vers l'accueil de l'administration par défaut
//
if ($session->is_logged_in) {
	http_redirect($redirect);
}

if (!empty($redirect)) {
	$output->addHiddenField('redirect', wan_htmlspecialchars($redirect));
}

$output->page_header();

$output->set_filenames(array(
	'body' => 'login_body.tpl'
));

$output->assign_vars(array(
	'TITLE'           => $lang['Module']['login'],
	'L_LOGIN'         => $lang['Login'],
	'L_PASS'          => $lang['Password'],
	'L_AUTOLOGIN'     => $lang['Autologin'],
	'L_LOST_PASSWORD' => $lang['Lost_password'],
	'L_VALID_BUTTON'  => $lang['Button']['valid'],

	'S_HIDDEN_FIELDS' => $output->getHiddenFields()
));

if (!isset($_COOKIE[$nl_config['cookie_name'] . '_data'])) {
	$output->assign_block_vars('cookie_notice', array('L_TEXT' => $lang['Cookie_notice']));
}


$output->pparse('body');

$output->page_footer();
