<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if( !defined('IN_INSTALL') )
{
	exit('<b>No hacking</b>');
}

define('IN_NEWSLETTER', true);
define('WA_ROOTDIR',    '..');
define('WAMAILER_DIR',  WA_ROOTDIR . '/includes/wamailer');

function message($message, $l_title = null)
{
	global $lang, $output;
	
	if( !empty($lang['Message'][$message]) )
	{
		$message = $lang['Message'][$message];
	}
	
	$output->send_headers();
	
	$output->set_filenames( array(
		'body' => 'result.tpl'
	));
	
	if( is_null($l_title) )
	{
		if( defined('IN_INSTALL') )
		{
			$l_title = ( defined('NL_INSTALLED') ) ? $lang['Title']['reinstall'] : $lang['Title']['install'];
		}
		else
		{
			$l_title = $lang['Title']['upgrade'];
		}
	}
	
	$output->assign_vars(array(
		'PAGE_TITLE'   => $l_title,
		'CONTENT_LANG' => $lang['CONTENT_LANG'],
		'CONTENT_DIR'  => $lang['CONTENT_DIR'],
		'NEW_VERSION'  => WANEWSLETTER_VERSION,
		'TRANSLATE'    => ( $lang['TRANSLATE'] != '' ) ? ' | Translate by ' . $lang['TRANSLATE'] : '',
		'L_TITLE'      => $lang['Title']['info'],
		'MSG_RESULT'   => nl2br($message)
	));
	
	$output->pparse('body');
	exit;
}

function check_admin($login, $passwd)
{
	global $db;
	
	$sql = "SELECT admin_email, admin_pwd, admin_level 
		FROM " . ADMIN_TABLE . " 
		WHERE LOWER(admin_login) = '" . $db->escape(strtolower($login)) . "'
			AND admin_level = " . ADMIN;
	if( $result = $db->query($sql) )
	{
		if( $row = $result->fetch() )
		{
			$login = false;
			$hasher = new PasswordHash();
			
			// Ugly old md5 hash prior Wanewsletter 2.4-beta2
			if( $row['admin_pwd'][0] != '$' )
			{
				if( $row['admin_pwd'] === md5($passwd) )
				{
					$login = true;
				}
			}
			// New password hash using phpass
			else if( $hasher->check($passwd, $row['admin_pwd']) )
			{
				$login = true;
			}
			
			if( $login )
			{
				return $row;
			}
		}
	}
	
	return false;
}

error_reporting(E_ALL);

require WA_ROOTDIR . '/includes/compat.inc.php';
require WA_ROOTDIR . '/includes/functions.php';
require WA_ROOTDIR . '/includes/constantes.php';
require WA_ROOTDIR . '/includes/template.php';
require WA_ROOTDIR . '/includes/class.output.php';
require WA_ROOTDIR . '/includes/class.phpass.php';

check_php_version();

$output = new output(WA_ROOTDIR . '/templates/');


//
// Les guillemets magiques ont été supprimés dans PHP 5.4.0
//
if( version_compare(PHP_VERSION, '5.4.0', '<') )
{
	//
	// Désactivation de magic_quotes_runtime +
	// magic_quotes_gpc et retrait éventuel des backslashes
	//
	@ini_set('magic_quotes_runtime', 0);

	if( get_magic_quotes_gpc() )
	{
		strip_magic_quotes_gpc($_GET);
		strip_magic_quotes_gpc($_POST);
		strip_magic_quotes_gpc($_COOKIE);
		strip_magic_quotes_gpc($_REQUEST);
	}
}

$default_lang   = 'francais';
$supported_lang = array(
	'fr' => 'francais',
	'en' => 'english'
);

$supported_db = array(
	'mysql' => array(
		'Name'         => 'MySQL &#8805; 5.0.7',
		'prefixe_file' => 'mysql',
		'extension'    => (extension_loaded('mysql') || extension_loaded('mysqli'))
	),
	'postgres' => array(
		'Name'         => 'PostgreSQL &#8805; 8.x, 9.x',
		'prefixe_file' => 'postgres',
		'extension'    => extension_loaded('pgsql')
	),
	'sqlite' => array(
		'Name'         => 'SQLite &#8805; 2.8, 3.x',
		'prefixe_file' => 'sqlite',
		'extension'    => (class_exists('SQLite3') || extension_loaded('sqlite') || (extension_loaded('pdo') && extension_loaded('pdo_sqlite')))
	)
);

$dsn = '';
$lang    = $datetime = $msg_error = $_php_errors = array();
$error   = false;
$prefixe = ( !empty($_POST['prefixe']) ) ? trim($_POST['prefixe']) : 'wa_';
$infos   = array('engine' => 'mysql', 'host' => null, 'user' => null, 'pass' => null, 'dbname' => null);
$language = $default_lang;

$dbtype = $dbhost = $dbuser = $dbpassword = $dbname = ''; // Compatibilité avec wanewsletter < 2.3-beta2

if( file_exists(WA_ROOTDIR . '/includes/config.inc.php') )
{
	@include WA_ROOTDIR . '/includes/config.inc.php';
}

if( server_info('HTTP_ACCEPT_LANGUAGE') != '' )
{
	$accept_lang_ary = array_map('trim', explode(',', server_info('HTTP_ACCEPT_LANGUAGE')));
	
	foreach( $accept_lang_ary as $accept_lang )
	{
		$accept_lang = strtolower(substr($accept_lang, 0, 2));
		
		if( isset($supported_lang[$accept_lang]) && file_exists(WA_ROOTDIR . '/language/lang_' . $supported_lang[$accept_lang] . '.php') )
		{
			$language = $supported_lang[$accept_lang];
			break;
		}
	}
}

require WA_ROOTDIR . '/language/lang_' . $language . '.php';
require WA_ROOTDIR . '/includes/wadb_init.php';
require WA_ROOTDIR . '/includes/sql/sqlparser.php';

if( !empty($dsn) )
{
	list($infos) = parseDSN($dsn);
}

//
// Compatibilité avec les version antérieures à 2.3-beta2 (début utilisation des DSN)
//
else if( !empty($dbtype) )
{
	$infos['engine'] = !empty($dbtype) ? $dbtype : $infos['engine'];
	$infos['host']   = !empty($dbhost) ? $dbhost : null;
	$infos['user']   = !empty($dbuser) ? $dbuser : null;
	$infos['pass']   = !empty($dbpassword) ? $dbpassword : null;
	$infos['dbname'] = !empty($dbname) ? $dbname : null;
}

foreach( array('engine', 'host', 'user', 'pass', 'dbname') as $varname )
{
	$infos[$varname] = ( !empty($_POST[$varname]) ) ? trim($_POST[$varname]) : @$infos[$varname];
}

// Récupération du port, si associé avec le nom d'hôte
if( strpos($infos['host'], ':') )
{
	$tmp = explode(':', $infos['host']);
	$infos['host'] = $tmp[0];
	$infos['port'] = $tmp[1];
}

if( $infos['engine'] == 'mssql' )
{
	message($lang['mssql_support_end']);
}
else if( $infos['engine'] == 'postgre' )
{
	$infos['engine'] = 'postgres';
}
else if( $infos['engine'] == 'mysql4' || $infos['engine'] == 'mysqli' )
{
	$infos['engine'] = 'mysql';
}
else if( $infos['engine'] == 'sqlite_pdo' || $infos['engine'] == 'sqlite3' )
{
	$infos['engine'] = 'sqlite';
}

foreach( $supported_db as $name => $data )
{
	if( $data['extension'] === false )
	{
		unset($supported_db[$name]);
	}
}

if( count($supported_db) == 0 )
{
	message(sprintf($lang['No_db_support'], WANEWSLETTER_VERSION));
}

if( !isset($supported_db[$infos['engine']]) && defined('NL_INSTALLED') )
{
	plain_error($lang['DB_type_undefined']);
}

if( $infos['engine'] == 'sqlite' )
{
	$infos['dbname'] = wa_realpath(WA_ROOTDIR . '/includes/sql') . '/wanewsletter.sqlite';
}

if( !empty($infos['dbname']) )
{
	$dsn = createDSN($infos);
}
