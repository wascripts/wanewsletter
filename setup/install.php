<?php
/**
 * Copyright (c) 2002-2006 Aurélien Maille
 * 
 * This file is part of Wanewsletter.
 * 
 * Wanewsletter is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation; either version 2 
 * of the License, or (at your option) any later version.
 * 
 * Wanewsletter is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Wanewsletter; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * 
 * @package Wanewsletter
 * @author  Bobe <wascripts@phpcodeur.net>
 * @link    http://phpcodeur.net/wascripts/wanewsletter/
 * @license http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 * @version $Id$
 */

define('IN_INSTALL', true);

require './setup.inc.php';

$vararray = array(
	'language', 'prev_language', 'admin_login', 'admin_email', 'admin_pass', 
	'confirm_pass', 'urlsite', 'urlscript'
);
foreach( $vararray as $varname )
{
	${$varname} = ( !empty($_POST[$varname]) ) ? trim($_POST[$varname]) : '';
}

$confirm_pass = ( $confirm_pass != '' ) ? md5($confirm_pass) : '';
$language     = ( $language != '' ) ? $language : $default_lang;

$output->set_filenames( array(
	'body' => 'install.tpl'
));

if( $start && $language != $prev_language )
{
	$start = false;
}

if( defined('NL_INSTALLED') )
{
	$db = WaDatabase($dsn);
	
	if( !$db->isConnected() )
	{
		plain_error(sprintf($lang['Connect_db_error'], $db->error));
	}
	
	$sql = "SELECT language, urlsite, path FROM " . CONFIG_TABLE;
	if( !($result = $db->query($sql)) )
	{
		plain_error('Impossible d\'obtenir la configuration du script');
	}
	
	$old_config = $result->fetch(SQL_FETCH_ASSOC);
	
	$urlsite    = $old_config['urlsite'];
	$urlscript  = $old_config['path'];
	$language   = $old_config['language'];
}

require WA_ROOTDIR . '/language/lang_' . $language . '.php';

$output->send_headers();

$output->assign_vars( array(
	'PAGE_TITLE'   => ( defined('NL_INSTALLED') ) ? $lang['Title']['reinstall'] : $lang['Title']['install'],
	'CONTENT_LANG' => $lang['CONTENT_LANG'],
	'CONTENT_DIR'  => $lang['CONTENT_DIR'],
	'NEW_VERSION'  => WA_NEW_VERSION,
	'TRANSLATE'    => ( $lang['TRANSLATE'] != '' ) ? ' | Translate by ' . $lang['TRANSLATE'] : ''
));

if( $start )
{
	require WA_ROOTDIR . '/includes/functions.validate.php';
	require WAMAILER_DIR . '/class.mailer.php';
	
	if( defined('NL_INSTALLED') )
	{
		$login = false;
		
		$sql = "SELECT admin_email, admin_pwd, admin_level 
			FROM " . ADMIN_TABLE . " 
			WHERE LOWER(admin_login) = '" . $db->escape(strtolower($admin_login)) . "'";
		if( $result = $db->query($sql) )
		{
			if( $row = $result->fetch() )
			{
				if( md5($admin_pass) == $row['admin_pwd'] && $row['admin_level'] == ADMIN )
				{
					$login        = true;
					$start        = true;
					$admin_email  = $row['admin_email'];
					$confirm_pass = $row['admin_pwd'];
				}
			}
		}
		
		if( !$login )
		{
			$error = true;
			$msg_error[] = $lang['Message']['Error_login'];
		}
	}
	else
	{
		if( $infos['driver'] == 'sqlite' )
		{
			if( is_writable(dirname($infos['dbname'])) )
			{
				$db = WaDatabase($dsn);
			}
			else
			{
				$error = true;
				$msg_error[] = $lang['sqldir_perms_problem'];
			}
		}
		else if( !empty($dsn) )
		{
			$db = WaDatabase($dsn);
		}
		else
		{
			$error = true;
			$msg_error[] = sprintf($lang['Connect_db_error'], 'Invalid DB name');
		}
		
		if( !$error && !$db->isConnected() )
		{
			$error = true;
			$msg_error[] = sprintf($lang['Connect_db_error'], $db->error);
		}
	}
	
	$sql_create = SCHEMAS_DIR . '/' . $supported_db[$infos['driver']]['prefixe_file'] . '_tables.sql';
	$sql_data   = SCHEMAS_DIR . '/data.sql';
	
	if( !is_readable($sql_create) || !is_readable($sql_data) )
	{
		$error = true;
		$msg_error[] = $lang['Message']['sql_file_not_readable'];
	}
	
	if( !$error )
	{
		if( $infos['dbname'] == '' || $prefixe == '' || $admin_login == '' )
		{
			$error = true;
			$msg_error[] = $lang['Message']['fields_empty'];
		}
		
		if( !validate_pass($admin_pass) )
		{
			$error = true;
			$msg_error[] = $lang['Message']['Alphanum_pass'];
		}
		else if( md5($admin_pass) != $confirm_pass )
		{
			$error = true;
			$msg_error[] = $lang['Message']['Bad_confirm_pass'];
		}
		
		if( !Mailer::validate_email($admin_email) )
		{
			$error = true;
			$msg_error[] = $lang['Message']['Invalid_email'];
		}
		
		$urlsite = rtrim($urlsite, '/');
		
		if( $urlscript != '/' )
		{
			$urlscript = '/' . trim($urlscript, '/') . '/';
		}
	}
	
	if( !$error )
	{
		//
		// On allonge le temps maximum d'execution du script. 
		//
		@set_time_limit(300);
		
		if( defined('NL_INSTALLED') )
		{
			exec_queries(str_replace('wa_', $prefixe, $sql_drop_index));
			exec_queries(str_replace('wa_', $prefixe, $sql_drop_table));
			
			if( SQL_DRIVER == 'postgres' )
			{
				exec_queries(str_replace('wa_', $prefixe, $sql_drop_sequence));
			}
			else if( SQL_DRIVER == 'firebird' )
			{
				exec_queries(str_replace('wa_', $prefixe, $sql_drop_trigger));
				exec_queries(str_replace('wa_', $prefixe, $sql_drop_generator));
			}
		}
		
		//
		// Création des tables du script 
		//
		$sql_create = parseSQL(implode('', file($sql_create)), $prefixe);
		exec_queries($sql_create, true);
		
		//
		// Insertion des données de base 
		//
		$sql_data = parseSQL(implode('', file($sql_data)), $prefixe);
		
		$sql_data[] = "UPDATE " . ADMIN_TABLE . "
			SET admin_login = '" . $db->escape($admin_login) . "',
				admin_pwd   = '" . md5($admin_pass) . "',
				admin_email = '" . $db->escape($admin_email) . "',
				admin_lang  = '$language'
			WHERE admin_id = 1";
		$sql_data[] = "UPDATE " . CONFIG_TABLE . "
			SET urlsite     = '" . $db->escape($urlsite) . "',
				path        = '" . $db->escape($urlscript) . "',
				cookie_path = '" . $db->escape($urlscript) . "',
				language    = '$language',
				mailing_startdate = " . time();
		$sql_data[] = "UPDATE " . LISTE_TABLE . "
			SET liste_startdate = " . time() . "
			WHERE liste_id = 1";
		
		exec_queries($sql_data, true);
		
		$db->close();
		
		if( !defined('NL_INSTALLED') )
		{
			if( !($fw = @fopen(WA_ROOTDIR . '/includes/config.inc.php', 'w')) )
			{
				$output->addHiddenField('driver',  $infos['driver']);
				$output->addHiddenField('host',    $infos['host']);
				$output->addHiddenField('user',    $infos['user']);
				$output->addHiddenField('pass',    $infos['pass']);
				$output->addHiddenField('dbname',  $infos['dbname']);
				$output->addHiddenField('prefixe', $prefixe);
				
				$output->assign_block_vars('download_file', array(
					'L_TITLE'         => $lang['Result_install'],
					'L_DL_BUTTON'     => $lang['Button']['dl'],
					
					'MSG_RESULT'      => nl2br($lang['Success_without_config']),						
					'S_HIDDEN_FIELDS' => $output->getHiddenFields()
				));
				
				$output->pparse('body');
				exit;
			}
			
			fwrite($fw, $config_file);
			fclose($fw);
		}
		
		message(nl2br(sprintf($lang['Success_install'], '<a href="' . WA_ROOTDIR . '/admin/login.php">', '</a>')), $lang['Result_install']);
	}
}

if( !defined('NL_INSTALLED') )
{
	require WA_ROOTDIR . '/includes/functions.box.php';
	
	$db_box = '';
	foreach( $supported_db as $name => $data )
	{
		$selected = ( $infos['driver'] == $name ) ? ' selected="selected"' : '';
		$db_box .= '<option value="' . $name . '"' . $selected . '> ' . $data['Name'] . ' </option>';
	}
	
	if( $urlsite == '' )
	{
		$urlsite = 'http://' . server_info('HTTP_HOST');
	}
	
	if( $urlscript == '' )
	{
		$urlscript = preg_replace('/^(.*?)\/setup\/?$/i', '\\1/', dirname(server_info('PHP_SELF')));
	}
	
	$output->assign_block_vars('install', array(
		'L_EXPLAIN'         => nl2br( sprintf($lang['Welcome_in_install'], '<a href="' . WA_ROOTDIR . '/docs/readme.' . $lang['CONTENT_LANG'] . '.html">', '</a>')),
		'TITLE_DATABASE'    => $lang['Title']['database'],
		'TITLE_ADMIN'       => $lang['Title']['admin'],
		'TITLE_DIVERS'      => $lang['Title']['config_divers'],
		'L_DBTYPE'          => $lang['dbtype'],
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
		'L_URLSITE'         => $lang['Urlsite'],
		'L_URLSCRIPT'       => $lang['Urlscript'],
		'L_URLSITE_NOTE'    => $lang['Urlsite_note'],
		'L_URLSCRIPT_NOTE'  => $lang['Urlscript_note'],
		'L_START_BUTTON'    => $lang['Start_install'],
		
		'DB_BOX'    => $db_box,
		'DBHOST'    => htmlspecialchars($infos['host']),
		'DBNAME'    => htmlspecialchars($infos['dbname']),
		'DBUSER'    => htmlspecialchars($infos['user']),
		'PREFIXE'   => htmlspecialchars($prefixe),
		'LOGIN'     => htmlspecialchars($admin_login),
		'EMAIL'     => htmlspecialchars($admin_email),
		'URLSITE'   => htmlspecialchars($urlsite),
		'URLSCRIPT' => htmlspecialchars($urlscript),
		'LANG_BOX'  => lang_box($language)
	));
}
else
{
	$output->assign_block_vars('reinstall', array(
		'L_EXPLAIN'      => nl2br($lang['Warning_reinstall']),
		'L_LOGIN'        => $lang['Login'],
		'L_PASS'         => $lang['Password'],
		'L_START_BUTTON' => $lang['Start_install'],
		
		'LOGIN' => htmlspecialchars($admin_login)
	));
}

$output->assign_var('S_PREV_LANGUAGE', $language);

if( $error )
{
	$output->error_box($msg_error);
}

$output->pparse('body');

?>