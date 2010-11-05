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

if( !defined('IN_WA_FORM') && !defined('IN_SUBSCRIBE') )
{
	exit('<b>No hacking</b>');
}

define('IN_NEWSLETTER', true);

//
// Compatibilité avec les version < 2.3.x
//
if( !defined('WA_ROOTDIR') )
{
	if( !isset($waroot) )
	{
		exit("Le répertoire de Wanewsletter n'est pas défini!");
	}
	
	define('WA_ROOTDIR', rtrim($waroot, '/'));
}

$default_error_reporting = error_reporting(E_ALL);

require WA_ROOTDIR . '/start.php';
require WA_ROOTDIR . '/includes/functions.validate.php';

if( !empty($language) && validate_lang($language) )
{
	load_settings(array('admin_lang' => $language));
}
else
{
	load_settings();
}

$action  = ( !empty($_REQUEST['action']) ) ? trim($_REQUEST['action']) : '';
$email   = ( !empty($_REQUEST['email']) ) ? trim($_REQUEST['email']) : '';
$format  = ( isset($_REQUEST['format']) ) ? intval($_REQUEST['format']) : 0;
$liste   = ( isset($_REQUEST['liste']) ) ? intval($_REQUEST['liste']) : 0;
$message = '';
$code    = '';

if( empty($action) && preg_match('/([a-z0-9]{20})(?:&|$)/i', $_SERVER['QUERY_STRING'], $match) )
{
	$code = $match[1];
}

//
// Compatibilité avec les version < 2.3.x
//
else if( !empty($action) && !empty($email) && strlen($code) == 32 )
{
	$code = substr($code, 0, 20);
}

if( !empty($action) || !empty($code) )
{
	//
	// Purge des éventuelles inscriptions dépassées
	// pour parer au cas d'une réinscription
	//
	purge_liste();
	
	require WA_ROOTDIR . '/includes/class.form.php';
	
	if( !empty($action) )
	{
		if( in_array($action, array('inscription', 'setformat', 'desinscription')) )
		{
			$sql = "SELECT liste_id, liste_format, sender_email, liste_alias, limitevalidate,
					liste_name, form_url, return_email, liste_sig, use_cron, confirm_subscribe
				FROM " . LISTE_TABLE . "
				WHERE liste_id = " .  $liste;
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible d\'obtenir les données sur la liste', ERROR);
			}
			
			if( $listdata = $result->fetch() )
			{
				$wanewsletter = new Wanewsletter($listdata);
				$wanewsletter->message =& $message;
				$wanewsletter->do_action($action, $email, $format);
			}
			else
			{
				$message = $lang['Message']['Unknown_list'];
			}
		}
		else
		{
			$message = $lang['Message']['Invalid_action'];
		}
	}
	else
	{
		$wanewsletter = new Wanewsletter();
		$wanewsletter->message =& $message;
		$wanewsletter->check_code($code);
	}
}

if( defined('IN_WA_FORM') )
{
	//
	// On réactive le gestionnaire d'erreur précédent
	//
	@restore_error_handler();
	
	// Si besoin, conversion du message vers le charset demandé
	if( !empty($textCharset) ) {
		$message = iconv($lang['CHARSET'], $textCharset, $message);
	}
	
	echo nl2br($message);
}

//
// remise des paramêtres par défaut
//
error_reporting($default_error_reporting);

?>