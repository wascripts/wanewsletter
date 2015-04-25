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

const IN_INSTALL = true;

if (substr($_SERVER['SCRIPT_FILENAME'], -8) == '.inc.php') {
	exit('<b>No hacking</b>');
}

require './includes/common.inc.php';

function message($message)
{
	global $lang, $output;

	if (!empty($lang['Message'][$message])) {
		$message = $lang['Message'][$message];
	}

	$output->send_headers();

	$output->assign_block_vars('result', array(
		'L_TITLE'    => $lang['Title']['install'],
		'MSG_RESULT' => nl2br($message)
	));

	$output->pparse('body');
	$output->page_footer();
	exit;
}

$reinstall = !empty($dsn);

// On prépare dès maintenant install.tpl. C'est nécessaire en cas d'appel
// précoce à la fonction message()
$output->set_filenames(array(
	'body' => 'install.tpl'
));

$output->assign_vars(array(
	'PAGE_TITLE'   => ($reinstall) ? $lang['Title']['reinstall'] : $lang['Title']['install'],
	'CONTENT_LANG' => $lang['CONTENT_LANG'],
	'CONTENT_DIR'  => $lang['CONTENT_DIR']
));

$prefixe = trim(filter_input(INPUT_POST, 'prefixe', FILTER_DEFAULT,
	array('options' => array('default' => 'wa_'))
));
$infos   = array(
	'engine' => 'mysql',
	'host'   => null,
	'port'   => 0,
	'user'   => null,
	'pass'   => null,
	'dbname' => null,
	'path'   => 'data/db/wanewsletter.sqlite'
);

if ($reinstall) {
	$tmp = parseDSN($dsn);
	$infos = array_merge($infos, $tmp[0]);
}

foreach (array('engine', 'host', 'user', 'pass', 'dbname', 'path') as $varname) {
	$infos[$varname] = trim(u::filter_input(INPUT_POST, $varname, FILTER_DEFAULT,
		array('options' => array('default' => $infos[$varname]))
	));
}

// Récupération du port, si associé avec le nom d’hôte ou l’IP.
if (strpos($infos['host'], ':')) {
	// Est-ce une IPv6 délimitée avec des crochets ?
	if (preg_match('#^(?<ip>\[[^]]+\])(?::(?<port>\d+))?$#', $infos['host'], $m)) {
		$infos['host'] = $m['ip'];
		$infos['port'] = (!empty($m['port'])) ? $m['port'] : 0;
	}
	else if (!filter_var($infos['host'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
		$tmp = explode(':', $infos['host']);
		$infos['host'] = $tmp[0];
		$infos['port'] = $tmp[1];
	}
}

foreach ($supported_db as $name => $data) {
	if (!$data['extension']) {
		unset($supported_db[$name]);
	}
}

if (count($supported_db) == 0) {
	message(sprintf($lang['No_db_support'], WANEWSLETTER_VERSION));
}

if (!isset($supported_db[$infos['engine']]) && $reinstall) {
	message($lang['DB_type_undefined']);
}

if ($infos['engine'] == 'sqlite' && $infos['path'] != '') {
	$infos['dbname'] = basename($infos['path']);
}

if (!empty($infos['dbname'])) {
	$dsn = createDSN($infos);
}

$vararray = array(
	'language', 'prev_language', 'admin_login', 'admin_email', 'admin_pass',
	'confirm_pass', 'urlsite', 'urlscript'
);
foreach ($vararray as $varname) {
	${$varname} = trim(u::filter_input(INPUT_POST, $varname));
}

//
// Envoi du fichier au client si demandé
//
// Attention, $config_file est aussi utilisé à la fin de l'installation pour
// pour créer le fichier de configuration.
//
$config_file  = '<' . "?php\n";
$config_file .= "//\n";
$config_file .= "// Paramètres d'accès à la base de données\n";
$config_file .= "//\n";
$config_file .= "\$dsn = '$dsn';\n";
$config_file .= "\$prefixe = '$prefixe';\n";
$config_file .= "\n";

if (isset($_POST['sendfile'])) {
	if (file_exists(WA_ROOTDIR . '/data/config.inc.php') ||
		file_exists(WA_ROOTDIR . '/includes/config.inc.php')
	) {
		echo "The config file is already installed on the server.";
		exit;
	}

	Attach::send_file('config.inc.php', 'text/plain', $config_file);
}

$language = ($language != '') ? $language : $lang['CONTENT_LANG'];

$start = isset($_POST['start']);

if ($start && $language != $prev_language) {
	$start = false;
}

$nl_config['language'] = $language;

if ($reinstall) {
	try {
		$db = WaDatabase($dsn);
	}
	catch (Dblayer\Exception $e) {
		message(sprintf($lang['Connect_db_error'], $e->getMessage()));
	}

	$nl_config = wa_get_config();
	$language  = $nl_config['language'];
}

load_settings();

//
// Idem qu'au début, mais avec éventuellement un fichier de langue différent chargé
//
$output->assign_vars(array(
	'PAGE_TITLE'   => ($reinstall) ? $lang['Title']['reinstall'] : $lang['Title']['install'],
	'CONTENT_LANG' => $lang['CONTENT_LANG'],
	'CONTENT_DIR'  => $lang['CONTENT_DIR']
));

if ($start) {
	if ($reinstall) {
		$auth = new Auth();

		if ($admindata = $auth->checkCredentials($admin_login, $admin_pass)) {
			if (!wan_is_admin($admindata)) {
				http_response_code(401);
				$output->addLine($lang['Message']['Not_authorized']);
				$output->addLine($lang['Click_return_index'], './index.php');
				$output->displayMessage();
			}

			$admin_email  = $admindata['email'];
			$confirm_pass = $admin_pass;
		}
		else {
			$error = true;
			$msg_error[] = $lang['Message']['Error_login'];
		}
	}
	else {
		try {
			if ($infos['engine'] == 'sqlite') {
				$sqlite_dir = dirname($infos['path']);

				if (!is_writable($sqlite_dir)) {
					throw new Exception(sprintf($lang['sqldir_perms_problem'], $sqlite_dir));
				}
			}
			else if ($infos['dbname'] == '') {
				throw new Exception(sprintf($lang['Connect_db_error'], 'Invalid DB name'));
			}

			$db = WaDatabase($dsn);
		}
		catch (Dblayer\Exception $e) {
			$error = true;
			$msg_error[] = sprintf($lang['Connect_db_error'], $e->getMessage());
		}
		catch (Exception $e) {
			$error = true;
			$msg_error[] = $e->getMessage();
		}
	}

	$sql_create = WA_ROOTDIR . '/includes/sql/schemas/' . $infos['engine'] . '_tables.sql';
	$sql_data   = WA_ROOTDIR . '/includes/sql/schemas/data.sql';

	if (!is_readable($sql_create) || !is_readable($sql_data)) {
		$error = true;
		$msg_error[] = $lang['Message']['sql_file_not_readable'];
	}

	if (!preg_match('#^[a-z][a-z0-9]*_?$#i', $prefixe)) {
		$error = true;
		$msg_error[] = $lang['Message']['Invalid_prefix'];
	}
	else if (strpos($prefixe, '_') == false) {
		$prefixe .= '_';
	}

	if (!$error) {
		if ($infos['dbname'] == '' || $admin_login == '') {
			$error = true;
			$msg_error[] = $lang['Message']['fields_empty'];
		}

		if (!validate_pass($admin_pass)) {
			$error = true;
			$msg_error[] = $lang['Message']['Alphanum_pass'];
		}
		else if ($admin_pass !== $confirm_pass) {
			$error = true;
			$msg_error[] = $lang['Message']['Bad_confirm_pass'];
		}

		if (!\Wamailer\Mailer::checkMailSyntax($admin_email)) {
			$error = true;
			$msg_error[] = $lang['Message']['Invalid_email'];
		}
	}

	if (!$error) {
		require WA_ROOTDIR . '/includes/sql/sqlparser.php';

		//
		// On allonge le temps maximum d'execution du script.
		//
		@set_time_limit(300);

		if (!($passwd_hash = password_hash($admin_pass, PASSWORD_DEFAULT))) {
			trigger_error("Unexpected error returned by password API", E_USER_ERROR);
		}

		if ($reinstall) {
			$sql_drop = array();

			foreach ($sql_schemas as $tablename => $schema) {
				$sql_drop[] = sprintf("DROP TABLE IF EXISTS %s",
					str_replace('wa_', $prefixe, $tablename)
				);
			}

			exec_queries($sql_drop);
		}

		//
		// Création des tables du script
		//
		$sql_create = Dblayer\parseSQL(file_get_contents($sql_create), $prefixe);
		exec_queries($sql_create);

		//
		// Insertion des données de base
		//
		$sql_data = Dblayer\parseSQL(file_get_contents($sql_data), $prefixe);

		$urlsite  = (wan_ssl_connection()) ? 'https' : 'http';
		$urlsite .= '://' . $_SERVER['HTTP_HOST'];

		$urlscript = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';

		$sql_data[] = "UPDATE " . ADMIN_TABLE . "
			SET admin_login = '" . $db->escape($admin_login) . "',
				admin_pwd   = '" . $db->escape($passwd_hash) . "',
				admin_email = '" . $db->escape($admin_email) . "',
				admin_lang  = '$language'
			WHERE admin_id = 1";
		$sql_data[] = sprintf(
			"UPDATE %s SET config_value = '%s' WHERE config_name = 'urlsite'",
			CONFIG_TABLE,
			$db->escape($urlsite)
		);
		$sql_data[] = sprintf(
			"UPDATE %s SET config_value = '%s' WHERE config_name = 'path'",
			CONFIG_TABLE,
			$db->escape($urlscript)
		);
		$sql_data[] = sprintf(
			"UPDATE %s SET config_value = '%s' WHERE config_name = 'cookie_path'",
			CONFIG_TABLE,
			$db->escape($urlscript)
		);
		$sql_data[] = sprintf(
			"UPDATE %s SET config_value = '%s' WHERE config_name = 'language'",
			CONFIG_TABLE,
			$db->escape($language)
		);
		$sql_data[] = sprintf(
			"UPDATE %s SET config_value = '%s' WHERE config_name = 'mailing_startdate'",
			CONFIG_TABLE,
			time()
		);
		$sql_data[] = "UPDATE " . LISTE_TABLE . "
			SET form_url        = '" . $db->escape($urlsite.$urlscript.'subscribe.php') . "',
				sender_email    = '" . $db->escape($admin_email) . "',
				liste_startdate = " . time() . "
			WHERE liste_id = 1";

		exec_queries($sql_data);

		$db->close();

		if (!$reinstall) {
			if (!($fw = fopen(WA_ROOTDIR . '/data/config.inc.php', 'w'))) {
				$output->addHiddenField('engine',  $infos['engine']);
				$output->addHiddenField('host',    $infos['host']);
				$output->addHiddenField('user',    $infos['user']);
				$output->addHiddenField('pass',    $infos['pass']);
				$output->addHiddenField('dbname',  $infos['dbname']);
				$output->addHiddenField('prefixe', $prefixe);

				$output->send_headers();

				$output->assign_block_vars('download_file', array(
					'L_TITLE'         => $lang['Title']['install'],
					'L_DL_BUTTON'     => $lang['Button']['dl'],

					'MSG_RESULT'      => nl2br(sprintf($lang['Success_install_no_config'],
						'<a href="docs/faq.fr.html#data_access">',
						'</a>',
						'<a href="admin/login.php">',
						'</a>'
					)),
					'S_HIDDEN_FIELDS' => $output->getHiddenFields()
				));

				$output->pparse('body');
				exit;
			}

			fwrite($fw, $config_file);
			fclose($fw);
		}

		$server = filter_input(INPUT_SERVER, 'SERVER_SOFTWARE');
		if (stripos($server, 'Apache') !== false) {
			message(sprintf($lang['Success_install'],
				'<a href="admin/login.php">',
				'</a>'
			));
		}
		else {
			message(sprintf($lang['Success_install2'],
				'<a href="docs/faq.fr.html#data_access">',
				'</a>',
				'<a href="admin/login.php">',
				'</a>'
			));
		}
	}
}

$output->send_headers();

if (!$reinstall) {
	require WA_ROOTDIR . '/includes/functions.box.php';

	$db_box = '';
	foreach ($supported_db as $name => $data) {
		$selected = $output->getBoolAttr('selected', ($infos['engine'] == $name));
		$db_box  .= '<option value="' . $name . '"' . $selected . '> ' . $data['Name'] . ' </option>';
	}

	$l_explain = nl2br(sprintf(
		$lang['Welcome_in_install'],
		'<a href="docs/readme.' . $lang['CONTENT_LANG'] . '.html">', '</a>',
		'<a href="COPYING">', '</a>',
		'<a href="http://phpcodeur.net/wascripts/GPL">', '</a>'
	));

	if ($infos['host'] == '') {
		$infos['host'] = 'localhost';
	}

	if ($infos['port'] > 0) {
		$infos['host'] .= ':'.$infos['port'];
	}

	$output->assign_block_vars('install', array(
		'L_EXPLAIN'         => $l_explain,
		'TITLE_DATABASE'    => $lang['Title']['database'],
		'TITLE_ADMIN'       => $lang['Title']['admin'],
		'L_DBTYPE'          => $lang['dbtype'],
		'L_DBPATH'          => $lang['dbpath'],
		'L_DBPATH_NOTE'     => $lang['dbpath_note'],
		'L_DBHOST'          => $lang['dbhost'],
		'L_DBNAME'          => $lang['dbname'],
		'L_DBUSER'          => $lang['dbuser'],
		'L_DBPWD'           => $lang['dbpwd'],
		'L_PREFIXE'         => $lang['prefixe'],
		'L_DEFAULT_LANG'    => $lang['Default_lang'],
		'L_LOGIN'           => $lang['Login'],
		'L_PASS'            => $lang['Password'],
		'L_PASS_CONF'       => $lang['Confirm_passwd'],
		'L_EMAIL'           => $lang['Email_address'],
		'L_START_BUTTON'    => $lang['Start_install'],

		'IS_SQLITE' => ($infos['engine'] == 'sqlite') ? 'is-sqlite' : '',
		'DB_BOX'    => $db_box,
		'DBPATH'    => htmlspecialchars($infos['path']),
		'DBHOST'    => htmlspecialchars($infos['host']),
		'DBNAME'    => ($infos['engine'] != 'sqlite') ? htmlspecialchars($infos['dbname']) : '',
		'DBUSER'    => htmlspecialchars($infos['user']),
		'PREFIXE'   => htmlspecialchars($prefixe),
		'LOGIN'     => htmlspecialchars($admin_login),
		'EMAIL'     => htmlspecialchars($admin_email),
		'LANG_BOX'  => lang_box($language)
	));
}
else {
	$output->assign_block_vars('reinstall', array(
		'L_EXPLAIN'      => nl2br($lang['Warning_reinstall']),
		'L_LOGIN'        => $lang['Login'],
		'L_PASS'         => $lang['Password'],
		'L_START_BUTTON' => $lang['Start_install'],

		'LOGIN' => htmlspecialchars($admin_login)
	));
}

$output->assign_var('S_PREV_LANGUAGE', $language);

if ($error) {
	$output->error_box($msg_error);
}

$output->pparse('body');
$output->page_footer();
