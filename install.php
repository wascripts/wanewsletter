<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aur�lien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

define('IN_NEWSLETTER', true);
define('IN_INSTALL', true);
define('WA_ROOTDIR', '.');

require WA_ROOTDIR . '/includes/common.inc.php';

function message($message)
{
	global $lang, $output;

	if (!empty($lang['Message'][$message])) {
		$message = $lang['Message'][$message];
	}

	$output->send_headers();

	$output->assign_block_vars('result', array(
		'L_TITLE'    => $lang['Result_install'],
		'MSG_RESULT' => nl2br($message)
	));

	$output->pparse('body');
	$output->page_footer();
	exit;
}

// On pr�pare d�s maintenant install.tpl. C'est n�cessaire en cas d'appel
// pr�coce � la fonction message()
$output->set_filenames( array(
	'body' => 'install.tpl'
));

$output->assign_vars( array(
	'PAGE_TITLE'   => (defined('NL_INSTALLED')) ? $lang['Title']['reinstall'] : $lang['Title']['install'],
	'CONTENT_LANG' => $lang['CONTENT_LANG'],
	'CONTENT_DIR'  => $lang['CONTENT_DIR'],
	'CHARSET'      => $lang['CHARSET']
));

$prefixe = (!empty($_POST['prefixe'])) ? trim($_POST['prefixe']) : 'wa_';
$infos   = array(
	'engine' => 'mysql',
	'host'   => null,
	'user'   => null,
	'pass'   => null,
	'dbname' => null,
	'path'   => 'data/db/wanewsletter.sqlite'
);

if (defined('NL_INSTALLED')) {
	$tmp = parseDSN($dsn);
	$infos = array_merge($infos, $tmp[0]);
}

foreach (array('engine', 'host', 'user', 'pass', 'dbname', 'path') as $varname) {
	$infos[$varname] = (!empty($_POST[$varname])) ? trim($_POST[$varname]) : $infos[$varname];
}

// R�cup�ration du port, si associ� avec le nom d'h�te
if (strpos($infos['host'], ':')) {
	$tmp = explode(':', $infos['host']);
	$infos['host'] = $tmp[0];
	$infos['port'] = $tmp[1];
}

foreach ($supported_db as $name => $data) {
	if (!$data['extension']) {
		unset($supported_db[$name]);
	}
}

if (count($supported_db) == 0) {
	message(sprintf($lang['No_db_support'], WANEWSLETTER_VERSION));
}

if (!isset($supported_db[$infos['engine']]) && defined('NL_INSTALLED')) {
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
	${$varname} = (!empty($_POST[$varname])) ? trim($_POST[$varname]) : '';
}

//
// Envoi du fichier au client si demand�
//
// Attention, $config_file est aussi utilis� � la fin de l'installation pour
// pour cr�er le fichier de configuration.
//
$config_file  = '<' . "?php\n";
$config_file .= "\n";
$config_file .= "//\n";
$config_file .= "// Param�tres d'acc�s � la base de donn�es\n";
$config_file .= "// Ne pas modifier ce fichier ! (Do not edit this file)\n";
$config_file .= "//\n";
$config_file .= "define('NL_INSTALLED', true);\n";
$config_file .= "\n";
$config_file .= "\$dsn = '$dsn';\n";
$config_file .= "\$prefixe = '$prefixe';\n";
$config_file .= "\n";

if (isset($_POST['sendfile'])) {
	Attach::send_file('config.inc.php', 'text/plain', $config_file);
}

$supported_lang = array(
	'fr' => 'francais',
	'en' => 'english'
);

$language = ($language != '') ? $language : $supported_lang[$lang['CONTENT_LANG']];

$start = isset($_POST['start']);

if ($start && $language != $prev_language) {
	$start = false;
}

$nl_config['language'] = $language;

if (defined('NL_INSTALLED')) {
	try {
		$db = WaDatabase($dsn);
	}
	catch (Exception $e) {
		message(sprintf($lang['Connect_db_error'], $e->getMessage()));
	}

	$nl_config = wa_get_config();
	$language  = $nl_config['language'];
}

load_settings();

//
// Idem qu'au d�but, mais avec �ventuellement un fichier de langue diff�rent charg�
//
$output->assign_vars( array(
	'PAGE_TITLE'   => (defined('NL_INSTALLED')) ? $lang['Title']['reinstall'] : $lang['Title']['install'],
	'CONTENT_LANG' => $lang['CONTENT_LANG'],
	'CONTENT_DIR'  => $lang['CONTENT_DIR'],
	'CHARSET'      => $lang['CHARSET']
));

if ($start) {
	require WA_ROOTDIR . '/includes/functions.validate.php';

	if (defined('NL_INSTALLED')) {
		$login = false;

		$sql = "SELECT admin_email, admin_pwd, admin_level
			FROM " . ADMIN_TABLE . "
			WHERE LOWER(admin_login) = '" . $db->escape(strtolower($admin_login)) . "'
				AND admin_level = " . ADMIN_LEVEL;
		$result = $db->query($sql);

		if ($row = $result->fetch()) {
			$hasher = new PasswordHash();

			// Ugly old md5 hash prior Wanewsletter 2.4-beta2
			if ($row['admin_pwd'][0] != '$') {
				if ($row['admin_pwd'] === md5($admin_pass)) {
					$login = true;
				}
			}
			// New password hash using phpass
			else if ($hasher->check($admin_pass, $row['admin_pwd'])) {
				$login = true;
			}

			if ($login) {
				$admin_email  = $row['admin_email'];
				$confirm_pass = $admin_pass;
			}
		}

		if (!$login) {
			$error = true;
			$msg_error[] = $lang['Message']['Error_login'];
		}
	}
	else {
		try {
			if (empty($dsn)) {
				throw new Exception(sprintf($lang['Connect_db_error'], 'Invalid DB name'));
			}

			if ($infos['engine'] == 'sqlite') {
				$sqlite_dir = dirname($infos['path']);

				if (!is_writable($sqlite_dir)) {
					throw new Exception(sprintf($lang['sqldir_perms_problem'], $sqlite_dir));
				}
			}

			$db = WaDatabase($dsn);
		}
		catch (SQLException $e) {
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

	if (!$error) {
		if ($infos['dbname'] == '' || $prefixe == '' || $admin_login == '') {
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

		if (!Mailer::validate_email($admin_email)) {
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

		if (defined('NL_INSTALLED')) {
			$sql_drop = array();

			foreach ($sql_schemas as $tablename => $schema) {
				$sql_drop[] = sprintf("DROP TABLE IF EXISTS %s",
					str_replace('wa_', $prefixe, $tablename)
				);

				if ($db->engine == 'postgres' && !empty($schema['sequence'])) {
					foreach ($schema['sequence'] as $sequence) {
						$sql_drop[] = sprintf("DROP SEQUENCE IF EXISTS %s",
							str_replace('wa_', $prefixe, $sequence)
						);
					}
				}

				if (!empty($schema['index'])) {
					foreach ($schema['index'] as $index) {
						$sql_drop[] = sprintf("DROP INDEX IF EXISTS %s",
							str_replace('wa_', $prefixe, $index)
						);
					}
				}
			}

			exec_queries($sql_drop);
		}

		//
		// Cr�ation des tables du script
		//
		$sql_create = parseSQL(file_get_contents($sql_create), $prefixe);
		exec_queries($sql_create);

		//
		// Insertion des donn�es de base
		//
		$sql_data = parseSQL(file_get_contents($sql_data), $prefixe);

		$urlsite  = (wan_ssl_connection()) ? 'https' : 'http';
		$urlsite .= '://' . $_SERVER['HTTP_HOST'];

		$urlscript = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';

		$hasher = new PasswordHash();

		$sql_data[] = "UPDATE " . ADMIN_TABLE . "
			SET admin_login = '" . $db->escape($admin_login) . "',
				admin_pwd   = '" . $db->escape($hasher->hash($admin_pass)) . "',
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

		$login_page = WA_ROOTDIR . '/admin/login.php';

		if (!defined('NL_INSTALLED')) {
			if (!($fw = @fopen(WA_ROOTDIR . '/includes/config.inc.php', 'w'))) {
				$output->addHiddenField('engine',  $infos['engine']);
				$output->addHiddenField('host',    $infos['host']);
				$output->addHiddenField('user',    $infos['user']);
				$output->addHiddenField('pass',    $infos['pass']);
				$output->addHiddenField('dbname',  $infos['dbname']);
				$output->addHiddenField('prefixe', $prefixe);

				$output->send_headers();

				$output->assign_block_vars('download_file', array(
					'L_TITLE'         => $lang['Result_install'],
					'L_DL_BUTTON'     => $lang['Button']['dl'],

					'MSG_RESULT'      => nl2br(sprintf($lang['Success_install_no_config'], sprintf('<a href="%s">', $login_page), '</a>')),
					'S_HIDDEN_FIELDS' => $output->getHiddenFields()
				));

				$output->pparse('body');
				exit;
			}

			fwrite($fw, $config_file);
			fclose($fw);
		}

		message(sprintf($lang['Success_install'], sprintf('<a href="%s">', $login_page), '</a>'));
	}
}

$output->send_headers();

if (!defined('NL_INSTALLED')) {
	require WA_ROOTDIR . '/includes/functions.box.php';

	$db_box = '';
	foreach ($supported_db as $name => $data) {
		$selected = $output->getBoolAttr('selected', ($infos['engine'] == $name));
		$db_box  .= '<option value="' . $name . '"' . $selected . '> ' . $data['Name'] . ' </option>';
	}

	$l_explain = nl2br(sprintf(
		$lang['Welcome_in_install'],
		'<a href="' . WA_ROOTDIR . '/docs/readme.' . $lang['CONTENT_LANG'] . '.html">', '</a>',
		'<a href="' . WA_ROOTDIR . '/COPYING">', '</a>',
		'<a href="http://phpcodeur.net/wascripts/GPL">', '</a>'
	));

	if ($infos['host'] == '') {
		$infos['host'] = 'localhost';
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
		'L_PASS_CONF'       => $lang['Conf_pass'],
		'L_EMAIL'           => $lang['Email_address'],
		'L_START_BUTTON'    => $lang['Start_install'],

		'IS_SQLITE' => ($infos['engine'] == 'sqlite') ? 'is-sqlite' : '',
		'DB_BOX'    => $db_box,
		'DBPATH'    => wan_htmlspecialchars($infos['path']),
		'DBHOST'    => wan_htmlspecialchars($infos['host']),
		'DBNAME'    => wan_htmlspecialchars($infos['dbname']),
		'DBUSER'    => wan_htmlspecialchars($infos['user']),
		'PREFIXE'   => wan_htmlspecialchars($prefixe),
		'LOGIN'     => wan_htmlspecialchars($admin_login),
		'EMAIL'     => wan_htmlspecialchars($admin_email),
		'LANG_BOX'  => lang_box($language)
	));
}
else {
	$output->assign_block_vars('reinstall', array(
		'L_EXPLAIN'      => nl2br($lang['Warning_reinstall']),
		'L_LOGIN'        => $lang['Login'],
		'L_PASS'         => $lang['Password'],
		'L_START_BUTTON' => $lang['Start_install'],

		'LOGIN' => wan_htmlspecialchars($admin_login)
	));
}

$output->assign_var('S_PREV_LANGUAGE', $language);

if ($error) {
	$output->error_box($msg_error);
}

$output->pparse('body');
$output->page_footer();

