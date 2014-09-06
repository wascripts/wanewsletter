<?php
/**
 * Copyright (c) 2002-2014 Aurélien Maille
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
 */

define('IN_NEWSLETTER', true);
define('IN_CRON',       true);
define('WA_ROOTDIR',    '..');

require WA_ROOTDIR . '/start.php';

// FIX temporaire, sinon bug lors des envois par flot
function sessid($var)
{
	return $var;
}

load_settings();

$mode     = ( !empty($_REQUEST['mode']) ) ? trim($_REQUEST['mode']) : '';
$liste_id = ( !empty($_REQUEST['liste']) ) ? intval($_REQUEST['liste']) : 0;

$sql = 'SELECT liste_id, liste_format, sender_email, liste_alias, limitevalidate,
		liste_name, form_url, return_email, liste_sig, use_cron, confirm_subscribe,
		pop_host, pop_port, pop_user, pop_pass
	FROM ' . LISTE_TABLE . ' 
	WHERE liste_id = ' . $liste_id;
if( !($result = $db->query($sql)) )
{
	trigger_error('Impossible de récupérer les informations sur cette liste', ERROR);
}

if( $listdata = $result->fetch() )
{
	//
	// On règle le script pour ignorer une déconnexion du client et 
	// poursuivre l'envoi du flot d'emails jusqu'à son terme. 
	//
	@ignore_user_abort(true);
	
	//
	// On augmente également le temps d'exécution maximal du script. 
	//
	// Certains hébergeurs désactivent pour des raisons évidentes cette fonction
	// Si c'est votre cas, vous êtes mal barré
	//
	@set_time_limit(1200);
	
	if( $mode == 'send' )
	{
		require WA_ROOTDIR . '/includes/engine_send.php';
		
		$sql = "SELECT log_id, log_subject, log_body_text, log_body_html, log_status
			FROM " . LOG_TABLE . "
			WHERE liste_id = $listdata[liste_id]
				AND log_status = " . STATUS_STANDBY . "
			LIMIT 1 OFFSET 0";
		if( !($result = $db->query($sql)) ) // on récupère le dernier log en statut d'envoi
		{
			trigger_error('Impossible d\'obtenir les données sur ce log', ERROR);
		}
		
		if( !($logdata = $result->fetch()) )
		{
			$output->displayMessage('No_log_to_send');
		}
		
		$sql = "SELECT jf.file_id, jf.file_real_name, jf.file_physical_name, jf.file_size, jf.file_mimetype
			FROM " . JOINED_FILES_TABLE . " AS jf
				INNER JOIN " . LOG_FILES_TABLE . " AS lf ON lf.file_id = jf.file_id
				INNER JOIN " . LOG_TABLE . " AS l ON l.log_id = lf.log_id
					AND l.liste_id = $listdata[liste_id]
					AND l.log_id   = $logdata[log_id]
			ORDER BY jf.file_real_name ASC";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir la liste des fichiers joints', ERROR);
		}
		
		$logdata['joined_files'] = $result->fetchAll();
		
		//
		// On lance l'envoi
		//
		$message = launch_sending($listdata, $logdata);
		
		$output->displayMessage($message);
	}
	else if( $mode == 'validate' )
	{
		require WAMAILER_DIR . '/class.pop.php';
		require WA_ROOTDIR . '/includes/class.form.php';
		require WA_ROOTDIR . '/includes/functions.validate.php';
		require WA_ROOTDIR . '/includes/functions.stats.php';
		
		$limit_security = 100; // nombre maximal d'emails dont le script doit s'occuper à chaque appel
		
		$wan = new Wanewsletter($listdata);
		$pop = new Pop();
		$pop->connect($listdata['pop_host'], $listdata['pop_port'], $listdata['pop_user'], $listdata['pop_pass']);
		
		$cpt = 0;
		$total    = $pop->stat_box();
		$mail_box = $pop->list_mail();
		
		foreach( $mail_box as $mail_id => $mail_size )
		{
			$headers = $pop->parse_headers($mail_id);
			
			if( !isset($headers['from']) || !preg_match('/^(?:"?([^"]*?)"?)?[ ]*(?:<)?([^> ]+)(?:>)?$/i', $headers['from'], $match) )
			{
				continue;
			}
			
			$pseudo = ( isset($match[1]) ) ? strip_tags(trim($match[1])) : '';
			$email  = trim($match[2]);
			
			if( !isset($headers['to']) || !stristr($headers['to'], $wan->liste_email) )
			{
				continue;
			}
			
			if( !isset($headers['subject']) )
			{
				continue;
			}
			
			$action = strtolower(trim($headers['subject']));
			
			switch( $action )
			{
				case 'desinscription':
				case 'désinscription':
				case 'unsubscribe':
					$action = 'desinscription';
					break;
				
				case 'inscription':
				case 'subscribe':
					$action = 'inscription';
					break;
				
				case 'confirmation':
				case 'setformat':
					break;
			}
			
			$code = $pop->contents[$mail_id]['message'];
			if( strlen($code) == 32 ) // Compatibilité avec versions < 2.3
			{
				$code = substr($code, 0, 20);
			}
			
			if( !empty($code) && ($action =='confirmation' || $action == 'desinscription') )
			{
				if( empty($headers['date']) || intval($time = strtotime($headers['date'])) > 0 )
				{
					$time = time();
				}
				
				$wan->check_code($code, $time);
			}
			else if( in_array($action, array('inscription','setformat','desinscription')) )
			{
				$wan->do_action($action, $email);
			}
			
			//
			// On supprime l'email maintenant devenu inutile
			//
			$pop->delete_mail($mail_id);
			
			$cpt++;
			
			if( $cpt > $limit_security )
			{
				break;
			}
		}//end for
		
		$pop->quit();
		
		$output->displayMessage('Success_operation');
	}
	else
	{
		trigger_error('No valid mode specified', ERROR);
	}
}
else
{
	trigger_error('Unknown_list', ERROR);
}

?>