<?php
/**
 * Copyright (c) 2002-2010 Aurélien Maille
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

define('IN_NEWSLETTER', true);
define('IN_LOGIN', true);

require './pagestart.php';

$simple_header = TRUE;

$mode     = ( !empty($_REQUEST['mode']) ) ? $_REQUEST['mode'] : '';
$redirect = ( !empty($_REQUEST['redirect']) ) ? trim($_REQUEST['redirect']) : 'index.php';
$redirect = preg_replace('/(\?|&)sessid=[a-zA-Z0-9]{32}/', '', $redirect);

//
// Mot de passe perdu
//
if( $mode == 'sendpass' )
{
	$login = ( !empty($_POST['login']) ) ? trim($_POST['login']) : '';
	$email = ( !empty($_POST['email']) ) ? trim($_POST['email']) : '';
	
	if( isset($_POST['submit']) )
	{
		$sql = "SELECT admin_id
			FROM " . ADMIN_TABLE . "
			WHERE LOWER(admin_login) = '" . $db->escape(strtolower($login)) . "'
				AND admin_email = '" . $db->escape($email) . "'";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir les informations du compte', CRITICAL_ERROR);
		}
		
		if( !($admin_id = $result->column('admin_id')) )
		{
			$error = TRUE;
			$msg_error[] = $lang['Message']['Error_sendpass'];
		}
		
		if( !$error )
		{
			$new_password = generate_key(12);
			
			require WAMAILER_DIR . '/class.mailer.php';
			
			$mailer = new Mailer(WA_ROOTDIR . '/language/email_' . $nl_config['language'] . '/');
			$mailer->signature = WA_X_MAILER;
			
			if( $nl_config['use_smtp'] )
			{
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
			
			if( !$mailer->send() )
			{
				trigger_error('Failed_sending', ERROR);
			}
			
			$db->query("UPDATE " . ADMIN_TABLE . "
				SET admin_pwd = '" . md5($new_password) . "'
				WHERE admin_id = " . $admin_id);
			
			$output->message('IDs_sended');
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
		
		'S_LOGIN' => htmlspecialchars($login),
		'S_EMAIL' => htmlspecialchars($email)
	));
	
	$output->pparse('body');
	
	$output->page_footer();
}

//
// Si l'utilisateur n'est pas connecté, on récupère les données et on démarre une nouvelle session
//
else if( $mode == 'login' && !$session->is_logged_in )
{
	$login     = ( !empty($_POST['login']) ) ? trim($_POST['login']) : '';
	$passwd    = ( !empty($_POST['passwd']) ) ? trim($_POST['passwd']) : '';
	$autologin = ( !empty($_POST['autologin']) ) ? TRUE : FALSE;
	
	$session->login($login, md5($passwd), $autologin);
	
	if( !$session->is_logged_in )
	{
		$error = TRUE;
		$msg_error[] = $lang['Message']['Error_login'];
	}
}

//
// Déconnexion de l'administration
//
else if( $mode == 'logout' )
{
	if( $session->is_logged_in )
	{
		$session->logout($admindata['admin_id']);
	}
	
	$error = TRUE;
	$msg_error[] = $lang['Message']['Success_logout'];
}

//
// L'utilisateur est connecté ?
// Dans ce cas, on le redirige vers la page demandée, ou vers l'accueil de l'administration par défaut
//
if( $session->is_logged_in )
{
	Location($redirect);
}

if( !empty($redirect) )
{
	$output->addHiddenField('redirect', htmlspecialchars($redirect));
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

$output->pparse('body');

$output->page_footer();
?>