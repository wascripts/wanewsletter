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

define('IN_NEWSLETTER', true);
define('IN_LOGIN', true);

require './pagestart.php';

$simple_header = TRUE;

$mode     = ( !empty($_REQUEST['mode']) ) ? $_REQUEST['mode'] : '';
$redirect = ( !empty($_REQUEST['redirect']) ) ? trim($_REQUEST['redirect']) : '';

if( $mode == 'login' )
{
	//
	// Si l'utilisateur n'est pas connecté, on récupère les données et on démarre une nouvelle session
	//
	if( !$session->is_logged_in )
	{
		$login     = ( !empty($_POST['login']) ) ? trim($_POST['login']) : '';
		$passwd    = ( !empty($_POST['passwd']) ) ? trim($_POST['passwd']) : '';
		$autologin = ( !empty($_POST['autologin']) ) ? TRUE : FALSE;
		
		$session->login($login, md5($passwd), $autologin);
	}
	
	//
	// L'utilisateur est connecté ?
	// Dans ce cas, on le redirige vers la page demandée, ou vers l'accueil de l'administration par défaut
	//
	if( $session->is_logged_in )
	{
		if( $redirect != '' )
		{
			$redirect = rawurldecode($redirect);
			list($redirect_path) = explode('?', $redirect);
			$redirect = ( file_exists(wa_realpath($redirect_path)) ) ? $redirect : 'index.php';
		}
		else
		{
			$redirect = 'index.php';
		}
		
		Location($redirect);
	}
	
	$error = TRUE;
	$msg_error[] = $lang['Message']['Error_login'];
}
else if( $mode == 'logout' )
{
	if( $session->is_logged_in )
	{
		$session->logout($admindata['admin_id']);
	}
	
	$error = TRUE;
	$msg_error[] = $lang['Message']['Success_logout'];
}

if( !empty($redirect) )
{
	$output->addHiddenField('redirect', rawurlencode(htmlspecialchars($redirect)));
}

$output->page_header();

$output->set_filenames(array(
	'body' => 'login_body.tpl'
));

$output->assign_vars(array(
	'TITLE'          => $lang['Module']['login'], 
	'L_LOGIN'        => $lang['Login'], 
	'L_PASS'         => $lang['Password'],
	'L_AUTOLOGIN'    => $lang['Autologin'],
	'L_VALID_BUTTON' => $lang['Button']['valid'], 
	
	'S_HIDDEN_FIELDS' => $output->getHiddenFields()
));

$output->pparse('body');

$output->page_footer();
?>