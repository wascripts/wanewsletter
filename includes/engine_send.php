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
 * @param array $listdata      Tableau des données de la liste concernée
 * @param array $logdata       Tableau des données de la newsletter
 * @param array $supp_address  Adresses de destinataires supplémentaires
 * 
 * @access private
 * 
 * @return string
 */
function launch_sending($listdata, $logdata, $supp_address = array())
{
	global $nl_config, $db, $lang, $other_tags;
	
	require WAMAILER_DIR . '/class.mailer.php';
	
	//
	// Initialisation de la classe mailer
	//
	$mailer = new Mailer(WA_ROOTDIR . '/language/email_' . $nl_config['language'] . '/');
	
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
	
	$mailer->correctRpath = !is_disabled_func('ini_set');
	$mailer->set_charset($lang['CHARSET']);
	$mailer->set_from($listdata['sender_email'], unhtmlspecialchars($listdata['liste_name']));
	
	if( $listdata['return_email'] != '' )
	{
		$mailer->set_return_path($listdata['return_email']);
	}
	
	//
	// On traite les données de la newsletter à envoyer
	//
	if( preg_match('/[\x80-\x9F]/', $logdata['log_subject']) || preg_match('/[\x80-\x9F]/', $logdata['log_body_text'])
		|| preg_match('/[\x80-\x9F]/', $logdata['log_body_html']) )
	{
		if( TRANSLITE_INVALID_CHARS == false )
		{
			$logdata['log_subject']   = wan_utf8_encode($logdata['log_subject']);
			$logdata['log_body_text'] = wan_utf8_encode($logdata['log_body_text']);
			$logdata['log_body_html'] = wan_utf8_encode($logdata['log_body_html']);
			
			$mailer->set_charset('UTF-8');
		}
		else
		{
			$logdata['log_subject']   = purge_latin1($logdata['log_subject'], true);
			$logdata['log_body_text'] = purge_latin1($logdata['log_body_text'], true);
			$logdata['log_body_html'] = purge_latin1($logdata['log_body_html']);
		}
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
	
	hasCidReferences($body[FORMAT_HTML], $refs);
	
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
			$file_path = $attach->ftp_to_tmp($logdata['joined_files'][$i]);
			array_push($tmp_files, $file_path);
		}
		else
		{
			$file_path = WA_ROOTDIR . '/' . $nl_config['upload_path'] . $physical_name;
		}
		
		if( is_array($refs) && in_array($real_name, $refs) )
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
		foreach( $other_tags as $data )
		{
			$fields_str .= 'a.' . $data['column_name'] . ', ';
		}
	}
	else
	{
		$fields_str = '';
	}
	
	//
	// Adresses supplémentaires à mettre en destinataires
	//
	$sql = "SELECT a.admin_email
		FROM " . ADMIN_TABLE . " AS a
			INNER JOIN " . AUTH_ADMIN_TABLE . " AS aa ON aa.admin_id = a.admin_id
				AND aa.cc_admin = " . TRUE;
	if( !($result = $db->query($sql)) )
	{
		trigger_error('Impossible d\'obtenir la liste des fichiers joints', ERROR);
	}
	
	while( $email = $result->column('admin_email') )
	{
		array_push($supp_address, $email);
	}
	$result->free();
	
	$supp_address = array_unique($supp_address); // Au cas où...
	
	//
	// On récupère les infos sur les abonnés destinataires
	//
	$sql = "SELECT a.abo_id, a.abo_pseudo, $fields_str a.abo_email, al.register_key, al.format
		FROM " . ABONNES_TABLE . " AS a
			INNER JOIN " . ABO_LISTE_TABLE . " AS al ON al.abo_id = a.abo_id
				AND al.liste_id  = $listdata[liste_id]
				AND al.confirmed = " . SUBSCRIBE_CONFIRMED . "
				AND al.send      = 0
		WHERE a.abo_status = " . ABO_ACTIF;
	if( $nl_config['emails_sended'] > 0 )
	{
		$sql .= " LIMIT $nl_config[emails_sended] OFFSET 0";
	}
	
	if( !($result = $db->query($sql)) )
	{
		trigger_error('Impossible d\'obtenir la liste des adresses emails', ERROR);
	}
	
	if( $row = $result->fetch() )
	{
		fake_header(false);
		
		$abo_ids = array();
		$format  = ( $listdata['liste_format'] != FORMAT_MULTIPLE ) ? $listdata['liste_format'] : false;
		
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
			while( $row = $result->fetch() );
			
			if( $listdata['liste_format'] != FORMAT_HTML )
			{
				$abonnes[FORMAT_TEXTE] = array_merge($abonnes[FORMAT_TEXTE], $supp_address);
			}
			
			if( $listdata['liste_format'] != FORMAT_TEXTE )
			{
				$abonnes[FORMAT_HTML] = array_merge($abonnes[FORMAT_HTML], $supp_address);
			}
			
			//
			// Tableau pour remplacer les tags par des chaines vides
			// Non utilisation des tags avec le moteur d'envoi en copie cachée
			//
			$tags_replace = array('NAME' => '');
			if( count($other_tags) > 0 )
			{
				foreach( $other_tags as $data )
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
			if( ($isPHP5 = version_compare(phpversion(), '5.0.0', '>=')) == true )
			{
				eval('$mailerText = clone $mailer;');
				eval('$mailerHTML = clone $mailer;');
			}
			else
			{
				$mailerText = $mailer;
				$mailerHTML = $mailer;
			}
			
			if( !$listdata['use_cron'] )
			{
				$body[FORMAT_TEXTE] = str_replace('{LINKS}', $link[FORMAT_TEXTE], $body[FORMAT_TEXTE]);
				$body[FORMAT_HTML]  = str_replace('{LINKS}', $link[FORMAT_HTML], $body[FORMAT_HTML]);
			}
			
			$mailerText->set_format(FORMAT_TEXTE);
			$mailerText->set_message($body[FORMAT_TEXTE]);
			
			$mailerHTML->set_format(FORMAT_HTML);
			if( $listdata['liste_format'] == FORMAT_MULTIPLE )
			{
				$mailerHTML->set_format(FORMAT_MULTIPLE);
				$mailerHTML->set_altmessage($body[FORMAT_TEXTE]);
			}
			$mailerHTML->set_message($body[FORMAT_HTML]);
			
			$supp_address_ok = array();
			foreach( $supp_address as $address )
			{
				if( $listdata['liste_format'] != FORMAT_HTML )
				{
					array_push($supp_address_ok, array(
						'format' => FORMAT_TEXTE,
						'abo_pseudo' => '',
						'abo_email'  => $address,
						'register_key' => '',
						'abo_id'     => -1
					));
				}
				
				if( $listdata['liste_format'] != FORMAT_TEXTE )
				{
					array_push($supp_address_ok, array(
						'format' => FORMAT_HTML,
						'abo_pseudo' => '',
						'abo_email'  => $address,
						'register_key' => '',
						'abo_id'     => -1
					));
				}
			}
			
			do
			{
				$abo_format = ( !$format ) ? $row['format'] : $format;
				
				if( $abo_format == FORMAT_TEXTE )
				{
					if( $isPHP5 == true )
					{
						eval('$mailer = clone $mailerText;');
					}
					else
					{
						$mailer = $mailerText;
					}
				}
				else
				{
					if( $isPHP5 == true )
					{
						eval('$mailer = clone $mailerHTML;');
					}
					else
					{
						$mailer = $mailerHTML;
					}
				}
				
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
					foreach( $other_tags as $data )
					{
						if( isset($row[$data['column_name']]) )
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
						'WA_CODE'  => $row['register_key'],
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
			while( ($row = $result->fetch()) || ($row = array_pop($supp_address_ok)) != null );
			
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
		
		$result->free();
	}
	else
	{
		trigger_error('No_subscribers', ERROR);
	}
	
	//
	// Si l'option FTP est utilisée, suppression des fichiers temporaires
	//
	if( $nl_config['use_ftp'] )
	{
		foreach( $tmp_files as $filename )
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
			$db->ping();
			
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de mettre à jour la table des abonnés (connexion au serveur sql perdue)', ERROR);
			}
		}
		
		$sql = "SELECT COUNT(*) AS num_dest, al.send
			FROM " . ABO_LISTE_TABLE . " AS al
				INNER JOIN " . ABONNES_TABLE . " AS a ON a.abo_id = al.abo_id
					AND a.abo_status = " . ABO_ACTIF . "
			WHERE al.liste_id    = $listdata[liste_id]
				AND al.confirmed = " . SUBSCRIBE_CONFIRMED . "
			GROUP BY al.send";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir le nombre d\'envois restants à faire', ERROR);
		}
		
		while( $row = $result->fetch() )
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
		$result->free();
	}
	else
	{
		$sended = count($abo_ids);
	}
	
	if( $no_send > 0 )
	{
		$message = sprintf($lang['Message']['Success_send'], $nl_config['emails_sended'], $sended, ($sended + $no_send));
		
		if( !defined('IN_COMMANDLINE') )
		{
			if( !empty($_GET['step']) && $_GET['step'] == 'auto' )
			{
				Location("envoi.php?progress=true&id=$logdata[log_id]&step=auto");
			}
			
			$message .= '<br /><br />' .  sprintf($lang['Click_resend_auto'], '<a href="' . sessid('./envoi.php?progress=true&amp;id=' . $logdata['log_id'] . '&amp;step=auto') . '">', '</a>');
			$message .= '<br /><br />' .  sprintf($lang['Click_resend_manuel'], '<a href="' . sessid('./envoi.php?progress=true&amp;id=' . $logdata['log_id']) . '">', '</a>');
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
			$db->ping();
			
			if( !$db->query($sql) )
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
	
	return $message;
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
			$tmp_link = $listdata['form_url'] . ( ( strstr($listdata['form_url'], '?') ) ? '&' : '?' ) . '{WA_CODE}';
			
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