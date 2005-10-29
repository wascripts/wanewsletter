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

class Wanewsletter {
	
	var $update_stats  = FALSE;
	var $new_subscribe = FALSE;
	
	var $email       = '';
	var $code        = '';
	var $format      = FORMAT_TEXTE;
	var $listdata    = array();
	var $liste_email = '';
	
	var $account     = array();
	var $message     = '';
	
	function Wanewsletter($listdata)
	{
		$this->listdata    = $listdata;
		$this->liste_email = ( !empty($listdata['liste_alias']) ) ? $listdata['liste_alias'] : $listdata['sender_email'];
	}
	
	function account_info($email, $pseudo, $code, $format, $action)
	{
		$this->email  = trim($email);
		$this->pseudo = trim($pseudo);
		$this->code   = trim($code);
		$this->format = intval($format);
		
		switch( $this->listdata['liste_format'] )
		{
			case FORMAT_MULTIPLE:
				if( $this->format != FORMAT_TEXTE && $this->format != FORMAT_HTML )
				{
					$this->format = FORMAT_TEXTE;
				}
				break;
			
			case FORMAT_HTML:
			case FORMAT_TEXTE:
				$this->format = $this->listdata['liste_format'];
				break;					
			
			default:
				$this->format = FORMAT_TEXTE;
				break;
		}
		
		$result = check_email($this->email, $this->listdata['liste_id'], $action);
		
		if( !$result['error'] )
		{
			if( is_array($result['abo_data']) )
			{
				$this->new_subscribe = false;
				
				$this->account['abo_id'] = $result['abo_data']['abo_id'];
				$this->account['code']   = $result['abo_data']['abo_register_key'];
				$this->account['date']   = $result['abo_data']['abo_register_date'];
				$this->account['status'] = $result['abo_data']['abo_status'];
			}
			else
			{
				$this->new_subscribe = true;
				
				$this->account['abo_id'] = 0;
				$this->account['code']   = generate_key();
				$this->account['date']   = time();
				$this->account['status'] = ABO_INACTIF;
			}
			
			return true;
		}
		else
		{
			$this->message = $result['message'];
			
			return false;
		}
	}
	
	function subscribe()
	{
		global $db, $nl_config, $lang, $mailer;
		
		if( $this->new_subscribe )
		{
			$status = ( $this->listdata['confirm_subscribe'] ) ? ABO_INACTIF : ABO_ACTIF;
			
			$sql_data = array(
				'abo_email'         => $this->email,
				'abo_pseudo'        => $this->pseudo,
				'abo_register_key'  => $this->account['code'],
				'abo_register_date' => $this->account['date'],
				'abo_status'        => $status
			);
			
			if( !$db->query_build('INSERT', ABONNES_TABLE, $sql_data) )
			{
				trigger_error('Impossible d\'insérer une nouvelle entrée dans la table des abonnés', ERROR);
				return false;
			}
			
			$this->account['abo_id'] = $db->next_id();
		}
		
		$sql = "INSERT INTO " . ABO_LISTE_TABLE . " (abo_id, liste_id, format) 
			VALUES(" . $this->account['abo_id'] . ", " . $this->listdata['liste_id'] . ", " . $this->format . ")";
		if( !$db->query($sql) )
		{
			if( $this->new_subscribe )
			{
				$sql_delete = 'DELETE FROM ' . ABONNES_TABLE . ' 
					WHERE abo_id = ' . $this->account['abo_id'];
				$db->query($sql_delete);
			}
			
			trigger_error('Impossible d\'insérer une nouvelle entrée dans la table des abonnés[2]', ERROR);
			return false;
		}
		
		$mailer->set_from($this->listdata['sender_email'], unhtmlspecialchars($this->listdata['liste_name']));
		$mailer->set_address($this->email);
		$mailer->set_subject(sprintf($lang['Subject_email']['Subscribe'], $nl_config['sitename']));
		$mailer->set_priority(1);
		$mailer->set_return_path($this->listdata['return_email']);
		
		if( $this->listdata['confirm_subscribe'] && $this->new_subscribe )
		{
			$email_tpl = ( $this->listdata['use_cron'] ) ? 'welcome_cron2' : 'welcome_form2';
			$link_action = 'confirmation';
		}
		else
		{
			$email_tpl = ( $this->listdata['use_cron'] ) ? 'welcome_cron1' : 'welcome_form1';
			$link_action = 'desinscription';
			
			$sql = "SELECT a.admin_login, a.admin_email 
				FROM " . ADMIN_TABLE . " AS a, " . AUTH_ADMIN_TABLE . " AS aa 
				WHERE a.admin_id = aa.admin_id 
					AND aa.liste_id = " . $this->listdata['liste_id'] . " 
					AND a.email_new_inscrit = " . SUBSCRIBE_NOTIFY_YES . " 
					AND ( a.admin_level = " . ADMIN . " OR aa.auth_view = " . TRUE . " )";
			if( $result = $db->query($sql) )
			{
				if( $row = $db->fetch_array($result) )
				{
					$mailer->clear_all();
					
					$mailer->set_from($this->listdata['sender_email'], unhtmlspecialchars($this->listdata['liste_name']));
					$mailer->set_subject($lang['Subject_email']['New_subscriber']);
					
					$mailer->use_template('admin_new_subscribe', array(
						'EMAIL'   => $this->email,
						'LISTE'   => unhtmlspecialchars($this->listdata['liste_name']),
						'URLSITE' => $nl_config['urlsite'],
						'SIG'     => $this->listdata['liste_sig']
					));
					
					do
					{
						$mailer->clear_address();
						$mailer->set_address($row['admin_email'], $row['admin_login']);
						
						$mailer->assign_tags(array(
							'USER' => $row['admin_login']
						));
						
						$mailer->send(); // envoi
					}
					while( $row = $db->fetch_array($result) );
				}
			}
		}
		
		$mailer->use_template($email_tpl, array(
			'LISTE'    => unhtmlspecialchars($this->listdata['liste_name']),
			'SITENAME' => $nl_config['sitename'],
			'CODE'     => $this->account['code'],
			'URLSITE'  => $nl_config['urlsite'],
			'SIG'      => $this->listdata['liste_sig']
		));
		
		if( $this->listdata['use_cron'] )
		{
			$mailer->assign_tags(array(
				'EMAIL_NEWSLETTER' => $this->liste_email
			));
		}
		else
		{
			$mailer->assign_tags(array(
				'LINK' => $this->make_link($link_action)
			));
		}
		
		if( $nl_config['enable_profil_cp'] )
		{
			$mailer->assign_block_tags('enable_profil_cp', array(
				'LINK_PROFIL_CP' => make_script_url('profil_cp.php')
			));
		}
		
		if( !$mailer->send() )
		{
			$this->message = $lang['Message']['Failed_sending'];
			return false;
		}
		
		if( !$this->listdata['confirm_subscribe'] )
		{
			if( $this->new_subscribe )
			{
				$this->update_stats = TRUE;
			}
			
			$this->message = $lang['Message']['Subscribe_2'];
		}
		else if( $this->new_subscribe )
		{
			$this->message = nl2br(sprintf($lang['Message']['Subscribe_1'], $this->listdata['limitevalidate']));
		}
		else
		{
			$this->message = $lang['Message']['Subscribe_2'];
		}
	}
	
	function confirm($time = 0)
	{
		global $db, $nl_config, $lang, $mailer;
		
		if( $this->code == $this->account['code'] )
		{
			$time = ( empty($time) ) ? time() : $time;
			$time_limit = ($time - ($this->listdata['limitevalidate'] * 86400));
			
			if( $this->account['date'] > $time_limit )
			{
				$low_priority = ( ereg('^mysql', DATABASE) ) ? 'LOW_PRIORITY' : '';
				
				$sql = "UPDATE $low_priority " . ABONNES_TABLE . " 
					SET abo_status = " . ABO_ACTIF . " 
					WHERE abo_id = " . $this->account['abo_id'];
				if( !$db->query($sql) )
				{
					trigger_error('Impossible de mettre à jour la table des abonnés', ERROR);
					return false;
				}
				
				$this->message = $lang['Message']['Confirm_ok'];
				
				$sql = "SELECT a.admin_login, a.admin_email 
					FROM " . ADMIN_TABLE . " AS a, " . AUTH_ADMIN_TABLE . " AS aa 
					WHERE a.admin_id = aa.admin_id 
						AND aa.liste_id = " . $this->listdata['liste_id'] . " 
						AND a.email_new_inscrit = " . SUBSCRIBE_NOTIFY_YES . " 
						AND ( a.admin_level = " . ADMIN . " OR aa.auth_view = " . TRUE . " )";
				if( $result = $db->query($sql) )
				{
					if( $row = $db->fetch_array($result) )
					{
						$mailer->clear_all();
						
						$mailer->set_from($this->listdata['sender_email'], unhtmlspecialchars($this->listdata['liste_name']));
						$mailer->set_subject($lang['Subject_email']['New_subscriber']);
						
						$mailer->use_template('admin_new_subscribe', array(
							'EMAIL'   => $this->email,
							'LISTE'   => unhtmlspecialchars($this->listdata['liste_name']),
							'URLSITE' => $nl_config['urlsite'],
							'SIG'     => $this->listdata['liste_sig']
						));
						
						do
						{
							$mailer->clear_address();
							$mailer->set_address($row['admin_email'], $row['admin_login']);
							
							$mailer->assign_tags(array(
								'USER' => $row['admin_login']
							));
							
							$mailer->send(); // envoi
						}
						while( $row = $db->fetch_array($result) );
					}
				}
				
				return true;
			}
			else
			{
				$this->message = $lang['Message']['Invalid_date'];
			}
		}
		else
		{
			$this->message = $lang['Message']['Invalid_code'];
		}
		
		return false;
	}
	
	function unsubscribe()
	{
		global $db, $nl_config, $lang, $mailer;
		
		if( $this->code != '' )
		{
			if( $this->code == $this->account['code'] )
			{
				$sql = "SELECT COUNT(abo_id) 
					FROM " . ABO_LISTE_TABLE . " 
					WHERE abo_id = " . $this->account['abo_id'];
				if( !($result = $db->query($sql)) )
				{
					trigger_error('Impossible de verifier la table de jointure', ERROR);
					return false;
				}
				
				$hash = $db->result($result, 0, 0);
				
				$db->transaction(START_TRC);
				
				$sql = "DELETE FROM " . ABO_LISTE_TABLE . " 
					WHERE abo_id = " . $this->account['abo_id'] . " 
						AND liste_id = " . $this->listdata['liste_id'];
				if( !$db->query($sql) )
				{
					trigger_error('Impossible d\'effacer l\'entrée de la table abo_liste', ERROR);
					return false;
				}
				
				if( $hash == 1 )
				{
					$sql = 'DELETE FROM ' . ABONNES_TABLE . ' 
						WHERE abo_id = ' . $this->account['abo_id'];
					if( !$db->query($sql) )
					{
						trigger_error('Impossible d\'effacer l\'entrée de la table des abonnés', ERROR);
						return false;
					}
					
					$this->message = $lang['Message']['Unsubscribe_3'];
				}
				else
				{
					$this->message = $lang['Message']['Unsubscribe_2'];
				}
				
				$db->transaction(END_TRC);
				
				return true;
			}
			else
			{
				$this->message = $lang['Message']['Invalid_code'];
				
				return false;
			}
		}
		else
		{
			$mailer->set_from($this->listdata['sender_email'], unhtmlspecialchars($this->listdata['liste_name']));
			$mailer->set_address($this->email);
			$mailer->set_subject($lang['Subject_email']['Unsubscribe']);
			$mailer->set_priority(3);
			$mailer->set_return_path($this->listdata['return_email']);
			
			$email_tpl = ( $this->listdata['use_cron'] ) ? 'unsubscribe_cron' : 'unsubscribe_form';
			
			$mailer->use_template($email_tpl, array(
				'LISTE'    => unhtmlspecialchars($this->listdata['liste_name']),
				'SITENAME' => $nl_config['sitename'],
				'URLSITE'  => $nl_config['urlsite'],
				'SIG'      => $this->listdata['liste_sig']
			));
			
			if( $this->listdata['use_cron'] )
			{
				$mailer->assign_tags(array(
					'EMAIL_NEWSLETTER' => $this->liste_email,
					'CODE'             => $this->account['code']
				));
			}
			else
			{
				$mailer->assign_tags(array(
					'LINK' => $this->make_link('desinscription')
				));
			}
			
			if( !($mailer->send()) )
			{
				$this->message = $lang['Message']['Failed_sending'];
				
				return false;
			}
			
			$this->message = $lang['Message']['Unsubscribe_1'];
			
			return true;
		}
	}
	
	function setformat()
	{
		global $db, $lang;
		
		if( $this->listdata['liste_format'] == FORMAT_MULTIPLE )
		{
			$sql = "SELECT format 
				FROM " . ABO_LISTE_TABLE . " 
				WHERE abo_id = " . $this->account['abo_id'] . " 
					AND liste_id = " . $this->listdata['liste_id'];
			if( $result = $db->query($sql) )
			{
				$format = $db->result($result, 0, 'format');
				
				switch( $format )
				{
					case FORMAT_TEXTE:
						$this->format = FORMAT_HTML;
						break;
					
					case FORMAT_HTML:
					default:
						$this->format = FORMAT_TEXTE;
						break;
				}
			}
			
			$low_priority = ( strstr(DATABASE, 'mysql') ) ? 'LOW_PRIORITY' : '';
			
			$sql = "UPDATE $low_priority " . ABO_LISTE_TABLE . " 
				SET format = " . $this->format . " 
				WHERE abo_id = " . $this->account['abo_id'] . " 
					AND liste_id = " . $this->listdata['liste_id'];
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de mettre à jour la table des abonnés', ERROR);
				return false;
			}
			
			$this->message = $lang['Message']['Success_setformat'];
			
			return true;
		}
		else
		{
			$this->message = $lang['Message']['Inactive_format'];
			
			return false;
		}
	}
	
	function make_link($action)
	{
		$prefix = $this->listdata['form_url'] . ( ( strstr($this->listdata['form_url'], '?') ) ? '&' : '?' );
		
		return $prefix . 'action=' . $action . '&email=' . rawurlencode($this->email) . '&code=' . $this->account['code'] . '&liste=' . $this->listdata['liste_id'];
	}
}

?>