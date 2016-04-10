<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

use Patchwork\Utf8 as u;

const IN_INSTALL = true;

if (substr($_SERVER['SCRIPT_FILENAME'], -8) == '.inc.php') {
	exit('<b>No hacking</b>');
}

require './includes/common.inc.php';

function create_config_file($dsn, $prefix)
{
	$config_file  = '<' . "?php\n";
	$config_file .= "//\n";
	$config_file .= "// Paramètres d'accès à la base de données\n";
	$config_file .= "//\n";
	$config_file .= "\$dsn = '$dsn';\n";
	$config_file .= "\$prefix = '$prefix';\n";
	$config_file .= "\n";
	$config_file .= "// Configuration additionnelle (voir data/config.sample.inc.php)\n";
	$config_file .= "\$nl_config = [];\n";
	$config_file .= "\n";

	return $config_file;
}

function message($message)
{
	global $lang, $output, $template;

	if (!empty($lang['Message'][$message])) {
		$message = $lang['Message'][$message];
	}

	$output->httpHeaders();

	$template->assignToBlock('result', [
		'L_TITLE'    => $lang['Title']['install'],
		'MSG_RESULT' => nl2br($message)
	]);

	$template->pparse();
	$output->footer();
}

// On prépare dès maintenant install.tpl. C'est nécessaire en cas d'appel
// précoce à la fonction message()
$template = new Template('install.tpl');

$template->assign([
	'PAGE_TITLE'   => $lang['Title']['install'],
	'CONTENT_LANG' => $lang['CONTENT_LANG'],
	'CONTENT_DIR'  => $lang['CONTENT_DIR']
]);

$infos   = [
	'engine' => 'mysql',
	'host'   => null,
	'port'   => 0,
	'user'   => null,
	'pass'   => null,
	'dbname' => null,
	'path'   => 'data/db/wanewsletter.sqlite'
];

if (!empty($dsn)) {
	$config_file_exists = true;
	$tmp = parseDSN($dsn);
	$infos  = array_merge($infos, $tmp[0]);
	$prefix = $nl_config['db']['prefix'];
}
else {
	$config_file_exists = false;

	$prefix = trim(filter_input(INPUT_POST, 'prefix', FILTER_DEFAULT, [
		'options' => ['default' => 'wa_']
	]));

	foreach (['engine', 'host', 'user', 'pass', 'dbname', 'path'] as $varname) {
		$infos[$varname] = trim(u::filter_input(INPUT_POST, $varname, FILTER_DEFAULT, [
			'options' => ['default' => $infos[$varname]]
		]));
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

	if ($infos['engine'] == 'sqlite' && $infos['path'] != '') {
		$infos['dbname'] = basename($infos['path']);
	}

	if ($infos['dbname']) {
		$dsn = createDSN($infos);
	}
}

$supported_db = get_supported_db();

if (count($supported_db) == 0) {
	message(sprintf($lang['No_db_support'], WANEWSLETTER_VERSION));
}

if ($infos['dbname'] && !isset($supported_db[$infos['engine']])) {
	message($lang['No_db_support']);
}

// Envoi du fichier, seulement s'il n'est pas présent sur le serveur.
if (isset($_POST['sendfile'])) {
	if (file_exists(WA_ROOTDIR . '/data/config.inc.php')
		|| file_exists(WA_ROOTDIR . '/includes/config.inc.php')
	) {
		echo "The config file is already installed on the server.";
		exit;
	}

	sendfile('config.inc.php', 'text/plain', create_config_file($dsn, $prefix));
}

$vararray = [
	'language', 'prev_language', 'admin_login', 'admin_email', 'admin_pass',
	'confirm_pass', 'urlsite', 'urlscript'
];
foreach ($vararray as $varname) {
	${$varname} = trim(u::filter_input(INPUT_POST, $varname));
}

$language = ($language != '') ? $language : $lang['CONTENT_LANG'];

$start = isset($_POST['start']);

if ($start && $language != $prev_language) {
	$start = false;
}

$nl_config['language'] = $language;

$error = false;
$reinstall = false;

if (!empty($dsn)) {
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
		$output->warn($lang['Connect_db_error'], $e->getMessage());
	}
	catch (Exception $e) {
		$error = true;
		$output->warn($e->getMessage());
	}

	if (!$error) {
		$tables = $db->initBackup()->getTablesList();

		if (array_search(CONFIG_TABLE, $tables)) {
			$reinstall = true;
			$nl_config = wa_get_config();
			$language  = $nl_config['language'];
		}

		unset($tables);
	}
}

load_settings();

//
// Idem qu'au début, mais avec éventuellement un fichier de langue différent chargé
//
$template->assign([
	'PAGE_TITLE'   => ($reinstall) ? $lang['Title']['reinstall'] : $lang['Title']['install'],
	'CONTENT_LANG' => $lang['CONTENT_LANG'],
	'CONTENT_DIR'  => $lang['CONTENT_DIR']
]);

if ($start) {
	if ($reinstall) {
		$auth = new Auth();

		if ($admindata = $auth->checkCredentials($admin_login, $admin_pass)) {
			if (!Auth::isAdmin($admindata)) {
				http_response_code(401);
				$output->addLine($lang['Message']['Not_authorized']);
				$output->addLine($lang['Click_return_index'], './index.php');
				$output->message();
			}

			$admin_email  = $admindata['email'];
			$confirm_pass = $admin_pass;
		}
		else {
			$error = true;
			$output->warn('Error_login');
		}
	}

	$schemas_dir = WA_ROOTDIR . '/includes/Dblayer/schemas';
	$sql_create  = sprintf('%s/%s_tables.sql', $schemas_dir, $infos['engine']);
	$sql_data    = sprintf('%s/data.sql', $schemas_dir);

	if (!is_readable($sql_create) || !is_readable($sql_data)) {
		$error = true;
		$output->warn('sql_file_not_readable');
	}

	if (!preg_match('#^[a-z][a-z0-9]*_?$#i', $prefix)) {
		$error = true;
		$output->warn('Invalid_prefix');
	}

	if (!validate_pseudo($admin_login)) {
		$error = true;
		$output->warn('Invalid_login');
	}

	if (!validate_pass($admin_pass)) {
		$error = true;
		$output->warn('Alphanum_pass');
	}
	else if ($admin_pass !== $confirm_pass) {
		$error = true;
		$output->warn('Bad_confirm_pass');
	}

	if (!\Wamailer\Mailer::checkMailSyntax($admin_email)) {
		$error = true;
		$output->warn('Invalid_email');
	}

	if (!$error) {
		if (!($passwd_hash = password_hash($admin_pass, PASSWORD_DEFAULT))) {
			trigger_error("Unexpected error returned by password API", E_USER_ERROR);
		}

		if ($reinstall) {
			$sql_drop = [];

			foreach (get_db_tables() as $tablename) {
				$sql_drop[] = sprintf("DROP TABLE IF EXISTS %s", $db->quote($tablename));
			}

			exec_queries($sql_drop);
		}

		//
		// Création des tables du script
		//
		$sql_create = parse_sql(file_get_contents($sql_create), $prefix);
		exec_queries($sql_create);

		//
		// Insertion des données de base
		//
		$sql_data = parse_sql(file_get_contents($sql_data), $prefix);

		$urlsite  = (is_secure_connection()) ? 'https' : 'http';
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

		exec_queries($sql_data);

		$db->close();

		if (!$config_file_exists) {
			if (!($fp = fopen(WA_ROOTDIR . '/data/config.inc.php', 'w'))) {
				$output->addHiddenField('engine',  $infos['engine']);
				$output->addHiddenField('host',    $infos['host']);
				$output->addHiddenField('user',    $infos['user']);
				$output->addHiddenField('pass',    $infos['pass']);
				$output->addHiddenField('dbname',  $infos['dbname']);
				$output->addHiddenField('prefix',  $prefix);

				$output->httpHeaders();

				$template->assignToBlock('download_file', [
					'L_TITLE'         => $lang['Title']['install'],
					'L_DL_BUTTON'     => $lang['Button']['dl'],

					'MSG_RESULT'      => nl2br(sprintf($lang['Success_install_no_config'],
						'<a href="docs/faq.fr.html#data_access">',
						'</a>',
						'<a href="admin/login.php">',
						'</a>'
					)),
					'S_HIDDEN_FIELDS' => $output->getHiddenFields()
				]);

				$template->pparse();
				exit;
			}

			fwrite($fp, create_config_file($dsn, $prefix));
			fclose($fp);
		}

		message(sprintf($lang['Success_install'],
			'<a href="docs/faq.fr.html#data_access">',
			'</a>',
			'<a href="admin/login.php">',
			'</a>'
		));
	}
}

$output->httpHeaders();

if (!$reinstall) {
	$db_box = '';
	foreach ($supported_db as $name => $data) {
		$db_box .= sprintf('<option value="%s"%s> %s </option>',
			$name,
			$output->getBoolAttr('selected', ($infos['engine'] == $name)),
			$data['label'] . ' ≥ ' . $data['version']
		);
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
		if (filter_var($infos['host'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			$infos['host'] = sprintf('[%s]', $infos['host']);
		}
		$infos['host'] .= ':'.$infos['port'];
	}

	$preconfig_message = $lang['Config_file_manual'];

	if ($config_file_exists) {
		$preconfig_message = sprintf("<strong>%s</strong><br>", $lang['Config_file_found']);

		if ($infos['engine'] != 'sqlite') {
			$preconfig_message .= sprintf($lang['Install_target_server'],
				$supported_db[$infos['engine']]['label'],
				$infos['host'],
				$infos['dbname']
			);
		}
		else {
			$preconfig_message .= sprintf($lang['Install_target_file'],
				$supported_db[$infos['engine']]['label'],
				$infos['path']
			);
		}
	}

	$template->assignToBlock('install', [
		'L_EXPLAIN'         => $l_explain,
		'TITLE_ADMIN'       => $lang['Title']['admin'],
		'L_DEFAULT_LANG'    => $lang['Default_lang'],
		'L_LOGIN'           => $lang['Login'],
		'L_PASS'            => $lang['Password'],
		'L_PASS_CONF'       => $lang['Confirm_passwd'],
		'L_EMAIL'           => $lang['Email_address'],
		'L_START_BUTTON'    => $lang['Start_install'],
		'L_PRECONFIG'       => $preconfig_message,

		'IS_SQLITE' => ($infos['engine'] == 'sqlite') ? 'is-sqlite' : '',
		'LOGIN'     => htmlspecialchars($admin_login),
		'EMAIL'     => htmlspecialchars($admin_email),
		'LANG_BOX'  => lang_box($language)
	]);

	if (!$config_file_exists) {
		$template->assignToBlock('install.db_infos', [
			'TITLE_DATABASE' => $lang['Title']['database'],
			'L_DBTYPE'       => $lang['dbtype'],
			'L_DBPATH'       => $lang['dbpath'],
			'L_DBPATH_NOTE'  => $lang['dbpath_note'],
			'L_DBHOST'       => $lang['dbhost'],
			'L_DBNAME'       => $lang['dbname'],
			'L_DBUSER'       => $lang['dbuser'],
			'L_DBPWD'        => $lang['dbpwd'],
			'L_PREFIX'       => $lang['prefix'],

			'DB_BOX'         => $db_box,
			'DBPATH'         => htmlspecialchars($infos['path']),
			'DBHOST'         => htmlspecialchars($infos['host']),
			'DBNAME'         => ($infos['engine'] != 'sqlite') ? htmlspecialchars($infos['dbname']) : '',
			'DBUSER'         => htmlspecialchars($infos['user']),
			'PREFIX'         => htmlspecialchars($prefix)
		]);
	}
}
else {
	$template->assignToBlock('reinstall', [
		'L_EXPLAIN'      => nl2br($lang['Warning_reinstall']),
		'L_LOGIN'        => $lang['Login'],
		'L_PASS'         => $lang['Password'],
		'L_START_BUTTON' => $lang['Start_install'],

		'LOGIN' => htmlspecialchars($admin_login)
	]);
}

$template->assign([
	'S_PREV_LANGUAGE' => $language,
	'WARN_BOX'        => $output->msgbox()
]);

$template->pparse();
$output->footer();
