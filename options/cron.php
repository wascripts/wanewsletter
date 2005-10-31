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
define('IN_CRON',       true);
define('WA_PATH',      '../');

require WA_PATH . 'start.php';

load_settings();

$mode     = ( !empty($_REQUEST['mode']) ) ? trim($_REQUEST['mode']) : '';
$liste_id = ( !empty($_REQUEST['liste']) ) ? intval($_REQUEST['liste']) : 0;

$sql = 'SELECT * FROM ' . LISTE_TABLE . ' 
	WHERE liste_id = ' . $liste_id;
if( !($result = $db->query($sql)) )
{
	trigger_error('Impossible de récupérer les informations sur cette liste', ERROR);
}

if( $listdata = $db->fetch_array($result) )
{
	//
	// On règle le script pour ignorer une déconnexion du client et 
	// poursuivre l'envoi du flot d'emails jusqu'à son terme. 
	//
	if( !is_disabled_func('ignore_user_abort') )
	{
		@ignore_user_abort(true);
	}
	
	//
	// On augmente également le temps d'exécution maximal du script. 
	//
	// Certains hébergeurs désactivent pour des raisons évidentes cette fonction
	// Si c'est votre cas, vous êtes mal barré
	//
	if( !is_disabled_func('set_time_limit') )
	{
		@set_time_limit(1200);
	}
	
	include WA_PATH . 'includes/wamailer/class.mailer.php';
	
	//
	// Initialisation de la classe mailer
	//
	$mailer = new Mailer(WA_PATH . 'language/email_' . $nl_config['language'] . '/');
	
	if( $nl_config['use_smtp'] )
	{
		$mailer->smtp_path = WA_PATH . 'includes/wamailer/';
		$mailer->use_smtp(
			$nl_config['smtp_host'],
			$nl_config['smtp_port'],
			$nl_config['smtp_user'],
			$nl_config['smtp_pass']
		);
	}
	
	$mailer->correctRpath = !is_disabled_func('ini_set');
	$mailer->set_charset($lang['CHARSET']);
	$mailer->set_from($listdata['sender_email'], unhtmlspecialchars($listdata['liste_name']));
	
	if( $mode == 'send' )
	{
		include WA_PATH . 'includes/engine_send.php';
		
		$sql = "SELECT log_id, log_subject, log_body_text, log_body_html, log_status
			FROM " . LOG_TABLE . "
			WHERE liste_id = $listdata[liste_id]
				AND log_status = " . STATUS_STANDBY;
		if( !($result = $db->query($sql, 0, 1)) ) // on récupère le dernier log en statut d'envoi
		{
			trigger_error('Impossible d\'obtenir les données sur ce log', ERROR);
		}
		
		if( $row = $db->fetch_array($result) )
		{
			$logdata = $row;
		}
		else
		{
			trigger_error('No_log_to_send', MESSAGE);
		}
		
		$sql = "SELECT jf.file_id, jf.file_real_name, jf.file_physical_name, jf.file_size, jf.file_mimetype
			FROM " . JOINED_FILES_TABLE . " AS jf, " . LOG_FILES_TABLE . " AS lf, " . LOG_TABLE . " AS l
			WHERE l.log_id = $logdata[log_id]
				AND lf.log_id = l.log_id
				AND jf.file_id = lf.file_id
				AND l.liste_id = $listdata[liste_id]
			ORDER BY jf.file_real_name ASC";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir la liste des fichiers joints', ERROR);
		}
		
		$logdata['joined_files'] = $db->fetch_rowset($result);
		
		//
		// On lance l'envoi
		//
		launch_sending($listdata, $logdata);
	}
	else if( $mode == 'validate' )
	{
		$cpt = 0;
		$limit_security = 100; // nombre maximal d'emails dont le script doit s'occuper à chaque appel 
		$mailer->set_format(FORMAT_TEXTE);
		
		include WA_PATH . 'includes/class.form.php';
		include WA_PATH . 'includes/class.pop.php';
		include WA_PATH . 'includes/functions.validate.php';
		include WA_PATH . 'includes/functions.stats.php';
		
		$wan = new Wanewsletter($listdata);
		$pop = new Pop();
		$pop->connect($listdata['pop_host'], $listdata['pop_port'], $listdata['pop_user'], $listdata['pop_pass']);
		
		$total    = $pop->stat_box();
		$mail_box = $pop->list_mail();
		
		foreach( $mail_box AS $mail_id => $mail_size )
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
				
				default:
					$pop->delete_mail($mail_id);
					continue 2;
					break;
			}
			
			$code = $pop->contents[$mail_id]['message'];
			if( $action != 'inscription' && strlen($code) != 32 )
			{
				continue;
			}
			
			if( $wan->account_info($email, $pseudo, $code, $action) )
			{
				switch( $action )
				{
					case 'desinscription':
						$wan->unsubscribe();
						break;
					
					case 'confirmation':
						if( empty($headers['date']) || ($time = strtotime($headers['date'])) === -1 )
						{
							$time = time();
						}
						
						$wan->confirm($time);
						break;
					
					case 'inscription':
						$wan->subscribe();
						break;
					
					case 'setformat':
						$wan->setformat();
						break;
					
					default:
						$pop->delete_mail($mail_id);
						continue 2;
						break;
				}
			}
			
			//
			// On supprime l'email maintenant devenu inutile
			//
			$pop->delete_mail($mail_id);
			
			if( $wan->update_stats )
			{
				update_stats($listdata);
			}
			
			$cpt++;
			
			if( $cpt > $limit_security )
			{
				break;
			}
		}//end for
		
		$pop->quit();
		
		trigger_error('Success_operation', MESSAGE);
	}
	else
	{
		trigger_error('No valid mode specified', MESSAGE);
	}
}
else
{
	exit('Unknown_list');
}

?>