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

if( !defined('CLASS_FORM_INC') ) {

define('CLASS_FORM_INC', true);

class Wanewsletter {
	
	var $code          = '';
	var $format        = FORMAT_TEXTE;
	var $listdata      = array();
	var $liste_email   = '';
	
	var $account       = array();
	var $hasAccount    = false;
	var $isRegistered  = false;
	var $update_stats  = false;
	var $message       = '';
	
	function Wanewsletter($listdata)
	{
		$this->listdata    = $listdata;
		$this->liste_email = ( !empty($listdata['liste_alias']) ) ? $listdata['liste_alias'] : $listdata['sender_email'];
	}
	
	function account_info($email, $pseudo, $code, $action)
	{
		$email = trim($email);
		$this->code = trim($code);
		
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
		
		$result = check_email($email, $this->listdata['liste_id'], $action);
		
		if( !$result['error'] )
		{
			if( is_array($result['abodata']) )
			{
				$this->hasAccount   = true;
				$this->isRegistered = isset($result['abodata']['confirmed']);
				
				$this->account['abo_id']    = $result['abodata']['abo_id'];
				$this->account['email']     = $result['abodata']['abo_email'];
				$this->account['pseudo']    = $result['abodata']['abo_pseudo'];
				$this->account['code']      = $result['abodata']['abo_register_key'];
				$this->account['date']      = $result['abodata']['register_date'];
				$this->account['format']    = $result['abodata']['format'];
				$this->account['status']    = $result['abodata']['abo_status'];
			}
			else
			{
				$this->hasAccount = false;
				
				$this->account['abo_id'] = 0;
				$this->account['email']  = $email;
				$this->account['pseudo'] = trim($pseudo);
				$this->account['code']   = generate_key();
				$this->account['date']   = time();
				$this->account['format'] = $this->format;
				$this->account['status'] = ( $this->listdata['confirm_subscribe'] == CONFIRM_NONE ) ? ABO_ACTIF : ABO_INACTIF;
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
		
		$db->transaction(START_TRC);
		
		if( $this->hasAccount == false )
		{
			$sql_data = array(
				'abo_email'         => $this->account['email'],
				'abo_pseudo'        => $this->account['pseudo'],
				'abo_register_key'  => $this->account['code'],
				'abo_register_date' => $this->account['date'],
				'abo_status'        => $this->account['status']
			);
			
			if( !$db->query_build('INSERT', ABONNES_TABLE, $sql_data) )
			{
				trigger_error('Impossible d\'insérer une nouvelle entrée dans la table des abonnés', ERROR);
				return false;
			}
			
			$this->account['abo_id'] = $db->next_id();
		}
		
		if( $this->isRegistered == false )
		{
			$confirmed = SUBSCRIBE_NOT_CONFIRMED;
			
			if( $this->hasAccount == false && $this->listdata['confirm_subscribe'] == CONFIRM_NONE )
			{
				$confirmed = SUBSCRIBE_CONFIRMED;
			}
			
			if( $this->hasAccount == true && $this->account['status'] == ABO_ACTIF && $this->listdata['confirm_subscribe'] != CONFIRM_ALWAYS )
			{
				$confirmed = SUBSCRIBE_CONFIRMED;
			}
			
			$sql = "INSERT INTO " . ABO_LISTE_TABLE . " (abo_id, liste_id, format, confirmed, register_date) 
				VALUES({$this->account[abo_id]}, {$this->listdata[liste_id]}, $this->format, $confirmed, " . time() . ")";
			if( !$db->query($sql) )
			{
				trigger_error('Impossible d\'insérer une nouvelle entrée dans la table des abonnés[2]', ERROR);
				return false;
			}
		}
		
		$db->transaction(END_TRC);
		
		if( $this->listdata['confirm_subscribe'] == CONFIRM_ALWAYS || ($this->listdata['confirm_subscribe'] == CONFIRM_ONCE && $this->hasAccount == false) )
		{
			$email_tpl = ( $this->listdata['use_cron'] ) ? 'welcome_cron2' : 'welcome_form2';
			$link_action = 'confirmation';
		}
		else
		{
			$email_tpl = ( $this->listdata['use_cron'] ) ? 'welcome_cron1' : 'welcome_form1';
			$link_action = 'desinscription';
			
			$this->alertAdmin();
		}
		
		$mailer->clear_all();
		$mailer->set_from($this->listdata['sender_email'], unhtmlspecialchars($this->listdata['liste_name']));
		$mailer->set_address($this->account['email']);
		$mailer->set_subject(sprintf($lang['Subject_email']['Subscribe'], $nl_config['sitename']));
		$mailer->set_priority(1);
		$mailer->set_return_path($this->listdata['return_email']);
		
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
		
		if( $this->hasAccount == false )
		{
			if( $this->listdata['confirm_subscribe'] == CONFIRM_NONE )
			{
				$this->update_stats = true;
				$message = $lang['Message']['Subscribe_2'];
			}
			else
			{
				$message = sprintf($lang['Message']['Subscribe_1'], $this->listdata['limitevalidate']);
			}
		}
		else
		{
			if( $this->isRegistered == true )
			{
				$message = sprintf($lang['Message']['Reg_not_confirmed'], $this->listdata['limitevalidate']);
			}
			else if( $this->listdata['confirm_subscribe'] != CONFIRM_ALWAYS )
			{
				$this->update_stats = true;
				$message = $lang['Message']['Subscribe_2'];
			}
			else
			{
				$message = sprintf($lang['Message']['Subscribe_1'], $this->listdata['limitevalidate']);
			}
		}
		
		$this->message = nl2br($message);
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
				$low_priority = ( strncmp(DATABASE, 'mysql', 5) == 0 ) ? 'LOW_PRIORITY' : '';
				
				$db->transaction(START_TRC);
				
				$sql = "UPDATE $low_priority " . ABONNES_TABLE . "
					SET abo_status = " . ABO_ACTIF . "
					WHERE abo_id = " . $this->account['abo_id'];
				if( !$db->query($sql) )
				{
					trigger_error('Impossible de mettre à jour la table des abonnés', ERROR);
					return false;
				}
				
				$sql = "UPDATE $low_priority " . ABO_LISTE_TABLE . "
					SET confirmed = " . SUBSCRIBE_CONFIRMED . "
					WHERE liste_id = " . $this->listdata['liste_id'] . "
						AND abo_id = " . $this->account['abo_id'];
				if( !$db->query($sql) )
				{
					trigger_error('Impossible de mettre à jour la table des abonnés', ERROR);
					return false;
				}
				
				$db->transaction(END_TRC);
				
				$this->update_stats = true;
				$this->alertAdmin();
				
				$this->message = $lang['Message']['Confirm_ok'];
				
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
				$sql = "SELECT COUNT(abo_id) AS num_subscribe
					FROM " . ABO_LISTE_TABLE . "
					WHERE abo_id = " . $this->account['abo_id'];
				if( !($result = $db->query($sql)) )
				{
					trigger_error('Impossible de vérifier la table de jointure', ERROR);
					return false;
				}
				
				$num_subscribe = $db->result($result, 0, 'num_subscribe');
				
				$db->transaction(START_TRC);
				
				$sql = "DELETE FROM " . ABO_LISTE_TABLE . "
					WHERE liste_id = " . $this->listdata['liste_id'] . "
						AND abo_id = " . $this->account['abo_id'];
				if( !$db->query($sql) )
				{
					trigger_error('Impossible d\'effacer l\'entrée de la table abo_liste', ERROR);
					return false;
				}
				
				if( $num_subscribe == 1 )
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
			$mailer->set_address($this->account['email']);
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
			if( $this->account['format'] == FORMAT_TEXTE )
			{
				$this->format = FORMAT_HTML;
			}
			else
			{
				$this->format = FORMAT_TEXTE;
			}
			
			$low_priority = ( strncmp(DATABASE, 'mysql', 5) == 0 ) ? 'LOW_PRIORITY' : '';
			
			$sql = "UPDATE $low_priority " . ABO_LISTE_TABLE . "
				SET format = " . $this->format . "
				WHERE liste_id = " . $this->listdata['liste_id'] . "
					AND abo_id = " . $this->account['abo_id'];
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
		
		return $prefix . 'action=' . $action . '&email=' . rawurlencode($this->account['email']) . '&code=' . $this->account['code'] . '&liste=' . $this->listdata['liste_id'];
	}
	
	function alertAdmin()
	{
		global $nl_config, $db, $mailer;
		
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
					'EMAIL'   => $this->account['email'],
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
}

}
?>