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

if( !defined('FUNCTIONS_VALIDATE_INC') ) {

define('FUNCTIONS_VALIDATE_INC', true);

/**
 * check_email()
 * 
 * Vérification de l'email
 * 
 * @param string  $email   Email à vérifier
 * @param integer $liste   Id de la liste concernée
 * @param string  $action  Action en cours
 * 
 * @return array
 */
function check_email($email, $liste = 0, $action = '', $disable_check_mx = false)
{
	global $db, $nl_config, $lang;
	
	if( !class_exists('Mailer') )
	{
		require WAMAILER_DIR . '/class.mailer.php';
	}
	
	//
	// Vérification syntaxique de l'email
	//
	if( Mailer::validate_email($email) == false )
	{
		return array('error' => true, 'message' => $lang['Message']['Invalid_email']);
	}
	
	$sql = 'SELECT ban_email FROM ' . BANLIST_TABLE . ' 
		WHERE liste_id = ' . $liste;
	if( $result = $db->query($sql) )
	{
		while( $row = $db->fetch_array($result) )
		{
			if( preg_match('/\b' . str_replace('*', '.*?', $row['ban_email']) . '\b/i', $email) )
			{
				return array('error' => true, 'message' => $lang['Message']['Email_banned']);
			}
		}
	}
	
	$abodata   = array();
	$sql_email = $db->escape(strtolower($email));
	
	$sql = "SELECT a.abo_id, a.abo_pseudo, a.abo_pwd, a.abo_email, a.abo_lang, a.abo_register_key, a.abo_register_date,
			a.abo_status, al.format, al.confirmed, al.register_date
		FROM " . ABONNES_TABLE . " AS a
			LEFT JOIN " . ABO_LISTE_TABLE . " AS al ON al.abo_id = a.abo_id
				AND al.liste_id = $liste
		WHERE LOWER(a.abo_email) = '$sql_email'";
	if( !($result = $db->query($sql)) )
	{
		trigger_error('Impossible de tester les tables d\'inscriptions', ERROR);
	}
	
	if( $abodata = $db->fetch_array($result) )
	{
		if( isset($abodata['confirmed']) )
		{
			if( $action == 'inscription' && $abodata['confirmed'] == SUBSCRIBE_CONFIRMED )
			{
				return array('error' => true, 'message' => $lang['Message']['Allready_reg']);
			}
			else if( $action == 'confirmation' && $abodata['confirmed'] == SUBSCRIBE_CONFIRMED )
			{
				return array('error' => true, 'message' => $lang['Message']['Allready_confirm']);
			}
		}
		else if( $action != 'inscription' )
		{
			return array('error' => true, 'message' => $lang['Message']['Unknown_email']);
		}
	}
	else if( $action != 'inscription' )
	{
		return array('error' => true, 'message' => $lang['Message']['Unknown_email']);
	}
	
	if( !$disable_check_mx && $nl_config['check_email_mx'] && $abodata === false )
	{
		//
		// Vérification de l'existence d'un Mail eXchanger sur le domaine de l'email, 
		// et vérification de l'existence du compte associé (La vérification de l'existence du 
		// compte n'est toutefois pas infaillible, les serveurs smtp refusant parfois le relaying, 
		// c'est à dire de traiter les demandes émanant d'un entité extérieure à leur réseau, et 
		// pour une adresse email extérieure à ce réseau)
		//
		$mailer = new Mailer();
		$mailer->smtp_path = WAMAILER_DIR . '/';
		
		if( $mailer->validate_email_mx($email) == false )
		{
			return array('error' => true, 'message' => $lang['Message']['Unrecognized_email']);
		}
	}
	
	return array('error' => false, 'abodata' => $abodata);
}

/**
 * validate_pseudo()
 * 
 * @param string $pseudo
 * 
 * @return boolean
 */
function validate_pseudo($pseudo)
{
	return ( strlen($pseudo) >= 2 && strlen($pseudo) <= 30 );
}

/**
 * validate_pass()
 * 
 * @param string $passwd
 * 
 * @return boolean
 */
function validate_pass($passwd)
{
	return preg_match('/^[[:alnum:]][[:alnum:]_-]{2,30}[[:alnum:]]$/', $passwd);
}

/**
 * validate_lang()
 * 
 * @param string $language
 * 
 * @return boolean
 */
function validate_lang($language)
{
	return preg_match('/^[\w_-]+$/', $language) && file_exists(WA_ROOTDIR . '/language/lang_' . $language . '.php');
}

}
?>