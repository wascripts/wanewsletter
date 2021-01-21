<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@webnaute.net>
 * @link      http://dev.webnaute.net/wanewsletter/
 * @copyright 2002-2021 Aurélien Maille
 * @license   https://www.gnu.org/licenses/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

require './start.inc.php';

if (!Auth::isAdmin($admindata)) {
	http_response_code(401);
	$output->redirect('./index.php', 6);
	$output->addLine($lang['Message']['Not_authorized']);
	$output->addLine($lang['Click_return_index'], './index.php');
	$output->message();
}

$old_config = $nl_config;
$move_files = false;

if (isset($_POST['submit'])) {
	$error = false;
	$new_config = [];

	foreach ($old_config as $name => $value) {
		$new_config[$name] = filter_input(INPUT_POST, $name, FILTER_UNSAFE_RAW, [
			'options' => ['default' => $value]
		]);
		if (is_scalar($new_config[$name])) {
			$new_config[$name] = utf8_normalize(trim($new_config[$name]));
		}
	}

	if ($new_config['language'] == '' || !validate_lang($new_config['language'])) {
		$new_config['language'] = $nl_config['language'];
	}

	$new_config['sitename'] = strip_tags($new_config['sitename']);
	$new_config['urlsite']  = preg_replace('/^http(s)?:\/\/(.*?)\/?$/i', 'http\\1://\\2', $new_config['urlsite']);

	if ($new_config['path'] != '/') {
		$new_config['path'] = preg_replace('/^\/?(.*?)\/?$/i', '/\\1/', $new_config['path']);
	}

	if (!$new_config['date_format']) {
		$new_config['date_format'] = DEFAULT_DATE_FORMAT;
	}

	// Restriction de caractères sur le nom du cookie
	if (preg_match("/[=,;\s\v]/", $new_config['cookie_name'])) {
		$error = true;
		$output->warn('Invalid_cookie_name');
	}

	// Restriction sur le chemin de validité du cookie
	if ($new_config['cookie_path'] == '') {
		$new_config['cookie_path'] = '/';
	}
	else if ($new_config['cookie_path'] != '/') {
		$new_config['cookie_path'] = '/' . trim($new_config['cookie_path'], '/') . '/';
	}

	$len = strlen($new_config['cookie_path']);
	if (strncmp($new_config['cookie_path'], $new_config['path'], $len) != 0) {
		$error = true;
		$output->warn('Invalid_cookie_path', $new_config['path']);
	}

	$new_config['session_length'] = intval($new_config['session_length']);
	if ($new_config['session_length'] <= 0) {
		$new_config['session_length'] = 3600;
	}

	if ($new_config['upload_path'] != '/') {
		$new_config['upload_path'] = trim($new_config['upload_path'], '/') . '/';

		$current_upload_dir = WA_ROOTDIR . '/' . $new_config['upload_path'];
		if (strcmp($nl_config['upload_path'], $new_config['upload_path']) !== 0) {
			$move_files = true;
			$old_upload_dir = WA_ROOTDIR . '/' . $nl_config['upload_path'];
		}

		if (!file_exists($current_upload_dir)) {
			if (!mkdir($current_upload_dir, 0755)) {
				$error = true;
				$output->warn('Cannot_create_dir', $current_upload_dir);
			}
		}
		else if (!is_writable($current_upload_dir)) {
			$error = true;
			$output->warn('Dir_not_writable', $current_upload_dir);
		}
	}

	$new_config['max_filesize'] = intval($new_config['max_filesize']);
	if ($new_config['max_filesize'] <= 0) {
		$new_config['max_filesize'] = 100;
	}

	$new_config['max_filesize'] *= 1024;// KiB => Bytes

	$new_config['sending_limit'] = intval($new_config['sending_limit']);
	$new_config['sending_delay'] = intval($new_config['sending_delay']);

	$new_config['smtp_port'] = intval($new_config['smtp_port']);
	if ($new_config['smtp_port'] < 1 || $new_config['smtp_port'] > 65535) {
		$new_config['smtp_port'] = 25;
	}

	if ($new_config['smtp_pass'] == '' && $new_config['smtp_user'] != '') {
		$new_config['smtp_pass'] = $old_config['smtp_pass'];
	}

	if ($new_config['use_smtp'] && function_exists('stream_socket_client')) {
		$opts = $nl_config['mailer'];
		$opts['starttls'] = ($new_config['smtp_tls'] == SECURITY_STARTTLS);
		$opts['timeout']  = 10;

		$smtp = new \Wamailer\Transport\SmtpClient();
		$smtp->options($opts);

		$server = ($new_config['smtp_tls'] == SECURITY_FULL_TLS) ? 'tls://%s:%d' : '%s:%d';
		$server = sprintf($server, $new_config['smtp_host'], $new_config['smtp_port']);

		try {
			if (!$smtp->connect($server, $new_config['smtp_user'], $new_config['smtp_pass'])) {
				throw new Exception(sprintf(
					"Failed to connect to SMTP server (%s)",
					$smtp->responseData
				));
			}
		}
		catch (\Exception $e) {
			$error = true;
			$output->warn('bad_smtp_param', $e->getMessage());
		}

		$smtp->quit();
	}
	else {
		$new_config['use_smtp'] = 0;
	}

	if (!$new_config['disable_stats'] && extension_loaded('gd')) {
		if (!is_writable($nl_config['stats_dir'])) {
			$error = true;
			$output->warn('Dir_not_writable', $nl_config['stats_dir']);
		}
	}
	else {
		$new_config['disable_stats'] = 1;
	}

	if (!$error) {
		wa_update_config(array_merge($old_config, $new_config));

		//
		// Déplacement des fichiers joints dans le nouveau dossier de stockage s'il est changé
		//
		if ($move_files && is_readable($old_upload_dir)) {
			if ($browse = dir($old_upload_dir)) {
				while (($entry = $browse->read()) !== false) {
					$source_file = $old_upload_dir . $entry;
					$dest_file   = $current_upload_dir . $entry;

					if (is_file($source_file)) {
						rename($source_file, $dest_file);
					}
				}
				$browse->close();
			}
		}

		$output->message('Success_modif');
	}
}
else {
	$new_config = $old_config;
}

$debug_box  = '<select name="debug_level">';
foreach ([DEBUG_LEVEL_QUIET, DEBUG_LEVEL_NORMAL, DEBUG_LEVEL_ALL] as $debug_level) {
	$debug_box .= sprintf('<option value="%d"%s>%s</option>',
		$debug_level,
		$output->getBoolAttr('selected', ($new_config['debug_level'] == $debug_level)),
		$lang['Debug_level_'.$debug_level]
	);
}
$debug_box .= '</select>';

$output->header();

$template = new Template('config_body.tpl');

$template->assign([
	'TITLE_CONFIG_LANGUAGE'     => $lang['Title']['config_lang'],
	'TITLE_CONFIG_PERSO'        => $lang['Title']['config_perso'],
	'TITLE_CONFIG_COOKIES'      => $lang['Title']['config_cookies'],
	'TITLE_CONFIG_JOINED_FILES' => $lang['Title']['config_files'],
	'TITLE_CONFIG_EMAIL'        => $lang['Title']['config_email'],
	'TITLE_DEBUG_MODE'          => $lang['Title']['config_debug'],

	'L_EXPLAIN'                 => nl2br($lang['Explain']['config']),
	'L_EXPLAIN_COOKIES'         => nl2br($lang['Explain']['config_cookies']),
	'L_EXPLAIN_JOINED_FILES'    => nl2br($lang['Explain']['config_files']),
	'L_EXPLAIN_EMAIL'           => nl2br(sprintf($lang['Explain']['config_email'],
		sprintf('<a href="%s">', wan_get_faq_url('smtp_server')),
		'</a>'
	)),
	'L_EXPLAIN_DEBUG_MODE'      => nl2br($lang['Explain']['config_debug']),

	'L_DEFAULT_LANG'            => $lang['Default_lang'],
	'L_SITENAME'                => $lang['Sitename'],
	'L_URLSITE'                 => $lang['Urlsite'],
	'L_URLSITE_NOTE'            => nl2br($lang['Urlsite_note']),
	'L_URLSCRIPT'               => $lang['Urlscript'],
	'L_URLSCRIPT_NOTE'          => nl2br($lang['Urlscript_note']),
	'L_DATE_FORMAT'             => $lang['Dateformat'],
	'L_NOTE_DATE'               => nl2br(sprintf($lang['Fct_date'], '<a href="http://www.php.net/date">', '</a>')),
	'L_ENABLE_PROFIL_CP'        => $lang['Enable_profil_cp'],
	'L_COOKIE_NAME'             => $lang['Cookie_name'],
	'L_COOKIE_PATH'             => $lang['Cookie_path'],
	'L_LENGTH_SESSION'          => $lang['Session_length'],
	'L_SECONDS'                 => $lang['Seconds'],
	'L_UPLOAD_PATH'             => $lang['Upload_path'],
	'L_MAX_FILESIZE'            => $lang['Max_filesize'],
	'L_KIB'                     => $lang['KiB'],
	'L_ENGINE_SEND'             => $lang['Choice_engine_send'],
	'L_ENGINE_BCC'              => $lang['With_engine_bcc'],
	'L_ENGINE_UNIQ'             => $lang['With_engine_uniq'],
	'L_SENDING_LIMIT'           => $lang['Sending_limit'],
	'L_SENDING_LIMIT_NOTE'      => nl2br($lang['Sending_limit_note']),
	'L_SENDING_DELAY'           => $lang['Sending_delay'],
	'L_USE_SMTP'                => $lang['Use_smtp'],
	'L_USE_SMTP_NOTE'           => nl2br($lang['Use_smtp_note']),
	'L_YES'                     => $lang['Yes'],
	'L_NO'                      => $lang['No'],
	'L_SMTP_SERVER'             => $lang['Smtp_server'],
	'L_SMTP_PORT'               => $lang['Smtp_port'],
	'L_SMTP_USER'               => $lang['Smtp_user'],
	'L_SMTP_PASS'               => $lang['Smtp_pass'],
	'L_SMTP_PASS_NOTE'          => nl2br($lang['Server_password_note']),
	'L_VALID_BUTTON'            => $lang['Button']['valid'],
	'L_RESET_BUTTON'            => $lang['Button']['reset'],
	'L_DEBUG_LEVEL'             => $lang['Debug_level'],

	'LANG_BOX'                  => lang_box($new_config['language']),
	'SITENAME'                  => htmlspecialchars($new_config['sitename']),
	'URLSITE'                   => $new_config['urlsite'],
	'URLSCRIPT'                 => $new_config['path'],
	'DATE_FORMAT'               => $new_config['date_format'],
	'DEFAULT_DATE_FORMAT'       => DEFAULT_DATE_FORMAT,
	'CHECKED_PROFIL_CP_ON'      => $output->getBoolAttr('checked', $new_config['enable_profil_cp']),
	'CHECKED_PROFIL_CP_OFF'     => $output->getBoolAttr('checked', !$new_config['enable_profil_cp']),
	'COOKIE_NAME'               => $new_config['cookie_name'],
	'COOKIE_PATH'               => $new_config['cookie_path'],
	'LENGTH_SESSION'            => $new_config['session_length'],
	'UPLOAD_PATH'               => $new_config['upload_path'],
	'MAX_FILESIZE'              => ($new_config['max_filesize']) ? round($new_config['max_filesize']/1024) : 0,
	'CHECKED_ENGINE_BCC'        => $output->getBoolAttr('checked', ($new_config['engine_send'] == ENGINE_BCC)),
	'CHECKED_ENGINE_UNIQ'       => $output->getBoolAttr('checked', ($new_config['engine_send'] == ENGINE_UNIQ)),
	'SENDING_LIMIT'             => $new_config['sending_limit'],
	'SENDING_DELAY'             => $new_config['sending_delay'],
	'SMTP_ROW_CLASS'            => ($new_config['use_smtp']) ? '' : 'inactive',
	'CHECKED_USE_SMTP_ON'       => $output->getBoolAttr('checked', $new_config['use_smtp']),
	'CHECKED_USE_SMTP_OFF'      => $output->getBoolAttr('checked', !$new_config['use_smtp']),
	'DISABLED_SMTP'             => $output->getBoolAttr('disabled', !function_exists('stream_socket_client')),
	'WARNING_SMTP'              => (!function_exists('stream_socket_client')) ? ' <span class="unavailable">[not available]</span>' : '',
	'SMTP_HOST'                 => $new_config['smtp_host'],
	'SMTP_PORT'                 => $new_config['smtp_port'],
	'SMTP_USER'                 => $new_config['smtp_user'],
	'DEBUG_BOX'                 => $debug_box
]);

if (in_array('tls', stream_get_transports())) {
	$template->assignToBlock('tls_support', [
		'L_SECURITY'        => $lang['Connection_security'],
		'L_NONE'            => $lang['None'],
		'STARTTLS_SELECTED' => $output->getBoolAttr('selected', $new_config['smtp_tls'] == SECURITY_STARTTLS),
		'SSL_TLS_SELECTED'  => $output->getBoolAttr('selected', $new_config['smtp_tls'] == SECURITY_FULL_TLS)
	]);
}

if (extension_loaded('gd')) {
	$template->assignToBlock('extension_gd', [
		'TITLE_CONFIG_STATS'        => $lang['Title']['config_stats'],
		'L_EXPLAIN_STATS'           => nl2br($lang['Explain']['config_stats']),
		'L_DISABLE_STATS'           => $lang['Disable_stats'],

		'CHECKED_DISABLE_STATS_ON'  => $output->getBoolAttr('checked', $new_config['disable_stats']),
		'CHECKED_DISABLE_STATS_OFF' => $output->getBoolAttr('checked', !$new_config['disable_stats'])
	]);
}
else {
	$output->addHiddenField('disable_stats', '1');
}

$template->assign(['S_HIDDEN_FIELDS' => $output->getHiddenFields()]);

$template->pparse();
$output->footer();
