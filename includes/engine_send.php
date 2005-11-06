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

if( !defined('IN_NEWSLETTER') )
{
	exit('<b>No hacking</b>');
}

if( !defined('ENGINE_SEND_INC') ) {

define('ENGINE_SEND_INC', true);

include WA_ROOTDIR . '/includes/tags.inc.php';

/**
 * launch_sending()
 * 
 * Cette fonction est appellée soit dans envoi.php lors de l'envoi, soit 
 * dans le fichier appellé originellement cron.php 
 * 
 * @param array $listdata  Tableau des données de la liste concernée
 * @param array $logdata   Tableau des données de la newsletter
 * 
 * @access private
 * 
 * @return void
 */
function launch_sending($listdata, $logdata)
{
	global $nl_config, $db, $dbhost, $dbuser, $dbpassword, $dbname, $lang, $mailer, $other_tags;
	
	//
	// On traite les données de la newsletter à envoyer
	//
	if( strtoupper($lang['CHARSET']) == 'ISO-8859-1' )
	{
		$logdata['log_subject'] = purge_latin1($logdata['log_subject'], true);
	}
	$mailer->set_subject($logdata['log_subject']);
	
	$body = array(
		FORMAT_TEXTE => $logdata['log_body_text'],
		FORMAT_HTML  => $logdata['log_body_html']
	);
	
	//
	// Ajout du lien de désinscription, selon les méthodes d'envoi/format utilisés
	//
	$link = newsletter_links($listdata);
	
	if( $listdata['use_cron'] || $nl_config['engine_send'] == ENGINE_BCC )
	{
		$body[FORMAT_TEXTE] = str_replace('{LINKS}', $link[FORMAT_TEXTE], $body[FORMAT_TEXTE]);
		$body[FORMAT_HTML]  = str_replace('{LINKS}', $link[FORMAT_HTML],  $body[FORMAT_HTML]);
	}
	
	//
	// On s'occupe maintenant des fichiers joints ou incorporés 
	// Si les fichiers sont stockés sur un serveur ftp, on les rapatrie le temps du flot d'envoi
	//
	$total_files = count($logdata['joined_files']);
	$tmp_files   = array();
	
	require WA_ROOTDIR . '/includes/class.attach.php';
	$attach = new Attach();
	
	preg_match_all('/<.+?"cid:([^\\:*\/?<">|]+)"[^>]*>/i', $body[FORMAT_HTML], $matches);
	
	for( $i = 0; $i < $total_files; $i++ )
	{
		$real_name     = $logdata['joined_files'][$i]['file_real_name'];
		$physical_name = $logdata['joined_files'][$i]['file_physical_name'];
		$mime_type     = $logdata['joined_files'][$i]['file_mimetype'];
		
		$error = FALSE;
		$msg   = array();
		
		$attach->joined_file_exists($physical_name, $error, $msg);
		
		if( $error )
		{
			$error = FALSE;
			continue;
		}
		
		if( $nl_config['use_ftp'] )
		{
			$file_path   = $attach->ftp_to_tmp($logdata['joined_files'][$i]);
			$tmp_files[] = $file_path;
		}
		else
		{
			$file_path = WA_ROOTDIR . '/' . $nl_config['upload_path'] . $physical_name;
		}
		
		if( is_array($matches) && in_array($real_name, $matches[1]) )
		{
			$embedded = TRUE;
		}
		else
		{
			$embedded = FALSE;
		}
		
		$mailer->attachment($file_path, $real_name, 'attachment', $mime_type, $embedded);
	}
	
	//
	// Récupération des champs des tags personnalisés
	//
	if( count($other_tags) > 0 )
	{
		$fields_str = '';
		foreach( $other_tags AS $data )
		{
			$fields_str .= 'a.' . $data['column_name'] . ', ';
		}
	}
	else
	{
		$fields_str = '';
	}
	
	//
	// On récupère les infos sur les abonnés destinataires
	//
	$sql = "SELECT a.abo_id, a.abo_pseudo, $fields_str a.abo_email, a.abo_register_key, al.format
		FROM " . ABONNES_TABLE . " AS a
			INNER JOIN " . ABO_LISTE_TABLE . " AS al
			ON al.abo_id = a.abo_id
				AND al.liste_id = $listdata[liste_id]
				AND al.send = 0
		WHERE a.abo_status = " . ABO_ACTIF;
	if( !($result = $db->query($sql, 0, $nl_config['emails_sended'])) )
	{
		trigger_error('Impossible d\'obtenir la liste des adresses emails', ERROR);
	}
	
	if( $row = $db->fetch_array($result) )
	{
		fake_header(false);
		
		$abo_ids = array();
		$format  = ( $listdata['liste_format'] != FORMAT_MULTIPLE ) ? $listdata['liste_format'] : false;
		
		if( strtoupper($lang['CHARSET']) == 'ISO-8859-1' )
		{
			$body[FORMAT_TEXTE] = purge_latin1($body[FORMAT_TEXTE], true);
			$body[FORMAT_HTML]  = purge_latin1($body[FORMAT_HTML]);
		}
		
		if( $nl_config['engine_send'] == ENGINE_BCC )
		{
			$abonnes = array(FORMAT_TEXTE => array(), FORMAT_HTML => array());
			
			do
			{
				array_push($abo_ids, $row['abo_id']);
				$abo_format = ( !$format ) ? $row['format'] : $format;
				array_push($abonnes[$abo_format], $row['abo_email']);
				
				fake_header(true);
			}
			while( $row = $db->fetch_array($result) );
			
			//
			// Tableau pour remplacer les tags par des chaines vides
			// Non utilisation des tags avec le moteur d'envoi en copie cachée
			//
			$tags_replace = array('NAME' => '');
			if( count($other_tags) > 0 )
			{
				foreach( $other_tags AS $data )
				{
					$tags_replace[$data['tag_name']] = '';
				}
			}
			
			if( count($abonnes[FORMAT_TEXTE]) > 0 )
			{
				$mailer->set_address($abonnes[FORMAT_TEXTE], 'Bcc');
				$mailer->set_format(FORMAT_TEXTE);
				$mailer->set_message($body[FORMAT_TEXTE]);
				$mailer->assign_tags($tags_replace);
				
				if( !$mailer->send() )
				{
					trigger_error(sprintf($lang['Message']['Failed_sending2'], $mailer->msg_error), ERROR);
				}
			}
			
			$mailer->clear_address();
			
			if( count($abonnes[FORMAT_HTML]) > 0 )
			{
				$mailer->set_address($abonnes[FORMAT_HTML], 'Bcc');
				$mailer->set_format($listdata['liste_format']);
				$mailer->assign_tags($tags_replace);
				$mailer->set_message($body[FORMAT_HTML]);
				
				if( $listdata['liste_format'] == FORMAT_MULTIPLE )
				{
					$mailer->set_altmessage($body[FORMAT_TEXTE]);
				}
				
				if( !$mailer->send() )
				{
					trigger_error(sprintf($lang['Message']['Failed_sending2'], $mailer->msg_error), ERROR);
				}
			}
		}
		else if( $nl_config['engine_send'] == ENGINE_UNIQ )
		{
			do
			{
				$abo_format = ( !$format ) ? $row['format'] : $format;
				$body_tmp   = $body[$abo_format];
				$link_tmp   = $link[$abo_format];
				
				if( $row['abo_pseudo'] != '' )
				{
					$address = array($row['abo_pseudo'] => $row['abo_email']);
				}
				else
				{
					$address = $row['abo_email'];
				}
				
				$mailer->clear_address();
				$mailer->set_address($address);
				$mailer->set_format($abo_format);
				
				if( empty($mailer->compiled_message[$abo_format]) )
				{
					if( !$listdata['use_cron'] )
					{
						$body_tmp = str_replace('{LINKS}', $link_tmp, $body_tmp);
					}
					
					$mailer->set_message($body_tmp);
				}
				
				//
				// Traitement des tags et tags personnalisés
				//
				$tags_replace = array();
				
				if( $row['abo_pseudo'] != '' )
				{
					$tags_replace['NAME'] = ( $abo_format == FORMAT_HTML ) ? $row['abo_pseudo'] : unhtmlspecialchars($row['abo_pseudo']);
				}
				else
				{
					$tags_replace['NAME'] = '';
				}
				
				if( count($other_tags) > 0 )
				{
					foreach( $other_tags AS $data )
					{
						if( $row[$data['column_name']] != '' )
						{
							if( !is_numeric($row[$data['column_name']]) && $abo_format == FORMAT_HTML )
							{
								$row[$data['column_name']] = htmlspecialchars($row[$data['column_name']]);
							}
							
							$tags_replace[$data['tag_name']] = $row[$data['column_name']];
							
							continue;
						}
						
						$tags_replace[$data['tag_name']] = '';
					}
				}
				
				if( !$listdata['use_cron'] )
				{
					$tags_replace = array_merge($tags_replace, array(
						'WA_CODE'  => $row['abo_register_key'],
						'WA_EMAIL' => rawurlencode($row['abo_email'])
					));
				}
				
				$mailer->assign_tags($tags_replace);
				
				// envoi
				if( $mailer->send() )
				{
					array_push($abo_ids, $row['abo_id']);
				}
				
				fake_header(true);
			}
			while( $row = $db->fetch_array($result) );
			
			//
			// Aucun email envoyé, il y a manifestement un problème, on affiche le message d'erreur
			//
			if( count($abo_ids) == 0 )
			{
				trigger_error(sprintf($lang['Message']['Failed_sending2'], $mailer->msg_error), ERROR);
			}
		}
		else
		{
			trigger_error('Unknown_engine', ERROR);
		}
		
		$db->free_result($result);
	}
	else
	{
		trigger_error('No_subscribers', MESSAGE);
	}
	
	//
	// Si l'option FTP est utilisée, suppression des fichiers temporaires
	//
	if( $nl_config['use_ftp'] )
	{
		foreach( $tmp_files AS $filename )
		{
			$attach->remove_file($filename);
		}
	}
	unset($tmp_files);
	
	$no_send = $sended = 0;
	
	if( $nl_config['emails_sended'] > 0 )
	{
		$sql = "UPDATE " . ABO_LISTE_TABLE . "
			SET send = 1
			WHERE abo_id IN(" . implode(', ', $abo_ids) . ")
				AND liste_id = " . $listdata['liste_id'];
		if( !$db->query($sql) )
		{
			//
			// L'envoi a duré trop longtemps et la connexion au serveur SQL a été perdue
			// On initialise une nouvelle connexion
			//
			unset($db);
			
			$db = new sql($dbhost, $dbuser, $dbpassword, $dbname);
			if( !is_resource($db->connect_id) || !$db->query($sql) )
			{
				trigger_error('Impossible de mettre à jour la table des abonnés (connexion au serveur sql perdue)', ERROR);
			}
		}
		
		$sql = "SELECT COUNT(*) AS num_dest, al.send
			FROM " . ABO_LISTE_TABLE . " AS al
				INNER JOIN " . ABONNES_TABLE . " AS a
				ON a.abo_id = al.abo_id
					AND a.abo_status = " . ABO_ACTIF . "
			WHERE al.liste_id = $listdata[liste_id]
			GROUP BY al.send";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir le nombre d\'envois restants à faire', ERROR);
		}
		
		while( $row = $db->fetch_array($result) )
		{
			if( $row['send'] == 1 )
			{
				$sended = $row['num_dest'];
			}
			else
			{
				$no_send = $row['num_dest'];
			}
		}
		$db->free_result($result);
	}
	else
	{
		$sended = count($abo_ids);
	}
	
	if( $no_send > 0 )
	{
		$message = sprintf($lang['Message']['Success_send'], $nl_config['emails_sended'], $sended, ($sended + $no_send));
		
		if( !defined('IN_CRON') )
		{
			if( !empty($_GET['step']) && $_GET['step'] == 'auto' )
			{
				Location("envoi.php?resend=true&id=$logdata[log_id]&step=auto");
			}
			
			$message .= '<br /><br />' .  sprintf($lang['Click_resend_auto'], '<a href="' . sessid('./envoi.php?resend=true&amp;id=' . $logdata['log_id'] . '&amp;step=auto') . '">', '</a>');
			$message .= '<br /><br />' .  sprintf($lang['Click_resend_manuel'], '<a href="' . sessid('./envoi.php?resend=true&amp;id=' . $logdata['log_id']) . '">', '</a>');
		}
	}
	else
	{
		$sql = "UPDATE " . LOG_TABLE . " 
			SET log_status = " . STATUS_SENDED . ", 
				log_numdest = $sended 
			WHERE log_id = " . $logdata['log_id'];
		if( !$db->query($sql) )
		{
			//
			// L'envoi a duré trop longtemps et la connexion au serveur SQL a été perdue
			// On initialise une nouvelle connexion
			//
			unset($db);
			
			$db = new sql($dbhost, $dbuser, $dbpassword, $dbname);
			if( !is_resource($db->connect_id) || !$db->query($sql) )
			{
				trigger_error('Impossible de mettre à jour la table des logs', ERROR);
			}
		}
		
		$sql = "UPDATE " . ABO_LISTE_TABLE . " 
			SET send = 0 
			WHERE liste_id = " . $listdata['liste_id'];
		if( !$db->query($sql) )
		{
			trigger_error('Impossible de mettre à jour la table des abonnés', ERROR);
		}
		
		$sql = "UPDATE " . LISTE_TABLE . " 
			SET liste_numlogs = liste_numlogs + 1 
			WHERE liste_id = " . $listdata['liste_id'];
		if( !$db->query($sql) )
		{
			trigger_error('Impossible de mettre à jour la table des listes', ERROR);
		}
		
		$message = sprintf($lang['Message']['Success_send_finish'], $sended);
	}
	
	trigger_error(nl2br($message), MESSAGE);
}

/**
 * newsletter_links()
 * 
 * Fonction renvoyant les liens à placer dans les newsletters, selon les réglages
 * 
 * @param array $listdata  Tableau des données de la liste concernée
 * 
 * @access private
 * 
 * @return array
 */
function newsletter_links($listdata)
{
	global $nl_config, $lang;
	
	$link = array(FORMAT_TEXTE => '', FORMAT_HTML => '');
	
	if( $listdata['use_cron'] )
	{
		$liste_email = ( !empty($listdata['liste_alias']) ) ? $listdata['liste_alias'] : $listdata['sender_email'];
		
		$link = array(
			FORMAT_TEXTE => $liste_email,
			FORMAT_HTML  => '<a href="mailto:' . $liste_email . '?subject=unsubscribe">' . $lang['Label_link'] . '</a>'
		);
	}
	else
	{
		if( $nl_config['engine_send'] == ENGINE_BCC )
		{
			$link = array(
				FORMAT_TEXTE => $listdata['form_url'],
				FORMAT_HTML  => '<a href="' . htmlspecialchars($listdata['form_url']) . '">' . $lang['Label_link'] . '</a>'
			);
		}
		else
		{
			$tmp_link  = $listdata['form_url'] . ( ( strstr($listdata['form_url'], '?') ) ? '&' : '?' );
			$tmp_link .= 'action=desinscription&email={WA_EMAIL}&code={WA_CODE}&liste=' . $listdata['liste_id'];
			
			$link = array(
				FORMAT_TEXTE => $tmp_link,
				FORMAT_HTML  => '<a href="' . htmlspecialchars($tmp_link) . '">' . $lang['Label_link'] . '</a>'
			);
			
			unset($tmp_link);
		}
	}
	
	return $link;
}

}
?>