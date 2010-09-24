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
	var $message       = '';
	
	var $mailer;
	
	function Wanewsletter($listdata = null)
	{
		global $nl_config, $lang;
		
		require WAMAILER_DIR . '/class.mailer.php';
		
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
		
		$mailer->set_charset($lang['CHARSET']);
		$mailer->set_format(FORMAT_TEXTE);
		$this->mailer =& $mailer;
		
		if( isset($listdata) )
		{
			$this->listdata    = $listdata;
			$this->liste_email = ( !empty($listdata['liste_alias']) ) ? $listdata['liste_alias'] : $listdata['sender_email'];
			
			if( $listdata['liste_format'] == FORMAT_TEXTE || $listdata['liste_format'] == FORMAT_HTML )
			{
				$this->format = $listdata['liste_format'];
			}
		}
	}
	
	function check($action, $email)
	{
		global $db, $nl_config, $lang;
		
		//
		// Vérification syntaxique de l'email
		//
		if( !Mailer::validate_email($email) )
		{
			return array('error' => true, 'message' => $lang['Message']['Invalid_email']);
		}
		
		//
		// Vérification de la liste des masques de bannissements
		//
		if( $action == 'inscription' )
		{
			$sql = "SELECT ban_email
				FROM " . BANLIST_TABLE . "
				WHERE liste_id = " . $this->listdata['liste_id'];
			if( $result = $db->query($sql) )
			{
				while( $ban_email = $result->column('ban_email') )
				{
					if( preg_match('/\b' . str_replace('*', '.*?', $ban_email) . '\b/i', $email) )
					{
						return array('error' => true, 'message' => $lang['Message']['Email_banned']);
					}
				}
			}
		}
		
		$sql = "SELECT a.abo_id, a.abo_pseudo, a.abo_pwd, a.abo_email, a.abo_lang,
				a.abo_status, al.format, al.register_key, al.register_date, al.confirmed
			FROM " . ABONNES_TABLE . " AS a
				LEFT JOIN " . ABO_LISTE_TABLE . " AS al ON al.abo_id = a.abo_id
					AND al.liste_id = {$this->listdata['liste_id']}
			WHERE LOWER(a.abo_email) = '" . $db->escape(strtolower($email)) . "'";
		if( !($result = $db->query($sql)) )
		{
			return array('error' => true, 'message' => 'Impossible de tester les tables d\'inscriptions');
		}
		
		if( $abodata = $result->fetch() )
		{
			if( !is_null($abodata['confirmed']) )
			{
				if( $action == 'inscription' && $abodata['confirmed'] == SUBSCRIBE_CONFIRMED )
				{
					return array('error' => true, 'message' => $lang['Message']['Allready_reg']);
				}
				else if( $action == 'desinscription' && $abodata['confirmed'] == SUBSCRIBE_NOT_CONFIRMED )
				{
					return array('error' => true, 'message' => $lang['Message']['Unknown_email']);
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
		
		if( $nl_config['check_email_mx'] && $abodata == false )
		{
			//
			// Vérification de l'existence d'un Mail eXchanger sur le domaine de l'email, 
			// et vérification de l'existence du compte associé (La vérification de l'existence du 
			// compte n'est toutefois pas infaillible, les serveurs smtp refusant parfois le relaying, 
			// c'est à dire de traiter les demandes émanant d'un entité extérieure à leur réseau, et 
			// pour une adresse email extérieure à ce réseau)
			//
			if( !$this->mailer->validate_email_mx($email, $response) )
			{
				return array('error' => true,
					'message' => sprintf($lang['Message']['Unrecognized_email'], $response));
			}
		}
		
		if( is_array($abodata) )
		{
			$this->hasAccount   = true;
			$this->isRegistered = !is_null($abodata['confirmed']);
			
			$this->account['abo_id'] = $abodata['abo_id'];
			$this->account['email']  = $abodata['abo_email'];
			$this->account['pseudo'] = $abodata['abo_pseudo'];
			$this->account['status'] = $abodata['abo_status'];
		}
		else
		{
			$this->hasAccount = false;
			
			$this->account['abo_id'] = 0;
			$this->account['email']  = $email;
			$this->account['pseudo'] = (!empty($_REQUEST['pseudo'])) ? $_REQUEST['pseudo'] : '';
			$this->account['status'] = ( $this->listdata['confirm_subscribe'] == CONFIRM_NONE ) ? ABO_ACTIF : ABO_INACTIF;
		}
		
		if( $this->isRegistered )
		{
			$this->account['code']   = $abodata['register_key'];
			$this->account['date']   = $abodata['register_date'];
			$this->account['format'] = $abodata['format'];
		}
		else
		{
			$this->account['code']   = generate_key(20);
			$this->account['date']   = time();
			$this->account['format'] = $this->format;
		}
		
		return array('error' => false, 'abodata' => $abodata);
	}
	
	function do_action($action, $email, $format = null)
	{
		if( $this->listdata['liste_format'] == FORMAT_MULTIPLE && !is_null($format)
			&& in_array($format, array(FORMAT_TEXTE, FORMAT_HTML)) )
		{
			$this->format = $format;
		}
		
		$email  = trim($email);
		$result = $this->check($action, $email);
		
		if( $result['error'] == false )
		{
			switch( $action )
			{
				case 'inscription':
					$this->subscribe();
					break;
				case 'desinscription':
					$this->unsubscribe();
					break;
				case 'setformat':
					$this->setformat();
					break;
			}
		}
		else if( empty($this->message) )
		{
			$this->message = $result['message'];
		}
	}
	
	function check_code($code, $time = null)
	{
		global $db, $lang;
		
		$sql = "SELECT a.abo_id, a.abo_email, a.abo_status, al.confirmed, al.register_date, l.liste_id,
				l.liste_format, l.sender_email, l.liste_alias, l.limitevalidate, l.liste_name,
				l.return_email, l.form_url, l.liste_sig, l.use_cron, l.confirm_subscribe
			FROM " . ABONNES_TABLE . " AS a
				INNER JOIN " . ABO_LISTE_TABLE . " AS al ON al.abo_id = a.abo_id
					AND al.register_key = '" . $db->escape($code) . "'
				INNER JOIN " . LISTE_TABLE . " AS l ON l.liste_id = al.liste_id";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible de tester les tables d\'inscriptions', ERROR);
		}
		
		if( $abodata = $result->fetch() )
		{
			$this->account['abo_id'] = $abodata['abo_id'];
			$this->account['email']  = $abodata['abo_email'];
			$this->account['status'] = $abodata['abo_status'];
			$this->account['date']   = $abodata['register_date'];
			$this->account['code']   = $code;
			
			$this->listdata = $abodata;// Récupération des données relatives à la liste
			
			if( $abodata['confirmed'] == SUBSCRIBE_NOT_CONFIRMED )
			{
				$this->confirm($code, $time);
			}
			else
			{
				$this->unsubscribe($code);
			}
		}
		else
		{
			$this->message = $lang['Message']['Invalid_code'];
		}
	}
	
	function subscribe()
	{
		global $db, $nl_config, $lang;
		
		$db->beginTransaction();
		
		if( !$this->hasAccount )
		{
			$sql_data = array(
				'abo_email'  => $this->account['email'],
				'abo_pseudo' => $this->account['pseudo'],
				'abo_pwd'    => md5($this->account['code']),
				'abo_status' => $this->account['status']
			);
			
			@include WA_ROOTDIR . '/includes/tags.inc.php';
			
			foreach( $other_tags as $data )
			{
				if( !empty($data['field_name']) && !empty($_REQUEST[$data['field_name']]) )
				{
					$sql_data[$data['column_name']] = $_REQUEST[$data['field_name']];
				}
			}
			
			if( !$db->build(SQL_INSERT, ABONNES_TABLE, $sql_data) )
			{
				trigger_error('Impossible d\'insérer une nouvelle entrée dans la table des abonnés', ERROR);
				return false;
			}
			
			$this->account['abo_id'] = $db->lastInsertId();
		}
		
		if( !$this->isRegistered )
		{
			$confirmed = SUBSCRIBE_NOT_CONFIRMED;
			
			if( !$this->hasAccount && $this->listdata['confirm_subscribe'] == CONFIRM_NONE )
			{
				$confirmed = SUBSCRIBE_CONFIRMED;
			}
			
			if( $this->hasAccount && $this->account['status'] == ABO_ACTIF && $this->listdata['confirm_subscribe'] != CONFIRM_ALWAYS )
			{
				$confirmed = SUBSCRIBE_CONFIRMED;
			}
			
			$sql = "INSERT INTO " . ABO_LISTE_TABLE . " (abo_id, liste_id, format, register_key, register_date, confirmed) 
				VALUES({$this->account['abo_id']}, {$this->listdata['liste_id']}, $this->format, '{$this->account['code']}', {$this->account['date']}, $confirmed)";
			if( !$db->query($sql) )
			{
				trigger_error('Impossible d\'insérer une nouvelle entrée dans la table des abonnés[2]', ERROR);
				return false;
			}
		}
		
		$db->commit();
		
		if( !$this->hasAccount )
		{
			//
			// Une confirmation est envoyée si la liste le demande
			//
			$confirm = !($this->listdata['confirm_subscribe'] == CONFIRM_NONE);
		}
		else
		{
			//
			// Une confirmation est envoyée si la liste demande une confirmation même
			// si l'email a été validé dans une précédente inscription à une autre liste,
			// et également si l'inscription est faite mais n'a pas encore été confirmée.
			//
			$confirm = ($this->isRegistered || $this->listdata['confirm_subscribe'] == CONFIRM_ALWAYS);
		}
		
		if( !$confirm )
		{
			$this->update_stats();
			$this->alert_admin(true);
			$message = $lang['Message']['Subscribe_2'];
			$email_tpl = $this->listdata['use_cron'] ? 'welcome_cron1' : 'welcome_form1';
		}
		else
		{
			$name = ($this->hasAccount && $this->isRegistered) ? 'Reg_not_confirmed' : 'Subscribe_1';
			$message = sprintf($lang['Message'][$name], $this->listdata['limitevalidate']);
			$email_tpl = $this->listdata['use_cron'] ? 'welcome_cron2' : 'welcome_form2';
		}
		
		$this->mailer->clear_all();
		$this->mailer->set_from($this->listdata['sender_email'], unhtmlspecialchars($this->listdata['liste_name']));
		$this->mailer->set_address($this->account['email']);
		$this->mailer->set_subject(sprintf($lang['Subject_email']['Subscribe'], $nl_config['sitename']));
		$this->mailer->set_priority(1);
		$this->mailer->set_return_path($this->listdata['return_email']);
		
		$this->mailer->use_template($email_tpl, array(
			'LISTE'    => unhtmlspecialchars($this->listdata['liste_name']),
			'SITENAME' => $nl_config['sitename'],
			'URLSITE'  => $nl_config['urlsite'],
			'SIG'      => $this->listdata['liste_sig']
		));
		
		if( $this->listdata['use_cron'] )
		{
			$this->mailer->assign_tags(array(
				'EMAIL_NEWSLETTER' => $this->liste_email
			));
		}
		else
		{
			$this->mailer->assign_tags(array(
				'LINK' => $this->make_link()
			));
		}
		
		if( !$this->hasAccount || $this->isRegistered )
		{
			$this->mailer->assign_block_tags('password', array(
				'CODE' => $this->account['code']
			));
		}
		
		if( $nl_config['enable_profil_cp'] )
		{
			$this->mailer->assign_block_tags('enable_profil_cp', array(
				'LINK_PROFIL_CP' => make_script_url('profil_cp.php')
			));
		}
		
		if( !$this->mailer->send() )
		{
			$this->message = $lang['Message']['Failed_sending'];
			return false;
		}
		
		$this->message = $message;
	}
	
	function confirm($code, $time = null)
	{
		global $db, $nl_config, $lang;
		
		if( strcmp($code, $this->account['code']) == 0 )
		{
			$time = ( is_null($time) ) ? time() : $time;
			$time_limit = ($time - ($this->listdata['limitevalidate'] * 86400));
			
			if( $this->account['date'] > $time_limit )
			{
				$db->beginTransaction();
				
				if( $this->account['status'] == ABO_INACTIF )
				{
					$sql = "UPDATE " . ABONNES_TABLE . "
						SET abo_status = " . ABO_ACTIF . "
						WHERE abo_id = " . $this->account['abo_id'];
					if( !$db->query($sql) )
					{
						trigger_error('Impossible de mettre à jour la table des abonnés', ERROR);
						return false;
					}
				}
				
				$sql = "UPDATE " . ABO_LISTE_TABLE . "
					SET confirmed = " . SUBSCRIBE_CONFIRMED . ",
						register_key = '" . generate_key(20) . "'
					WHERE liste_id = " . $this->listdata['liste_id'] . "
						AND abo_id = " . $this->account['abo_id'];
				if( !$db->query($sql) )
				{
					trigger_error('Impossible de mettre à jour la table des abonnés', ERROR);
					return false;
				}
				
				$db->commit();
				
				$this->update_stats();
				$this->alert_admin(true);
				
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
	
	function unsubscribe($code = '')
	{
		global $db, $nl_config, $lang;
		
		if( !empty($code) )
		{
			$sql = "SELECT COUNT(abo_id) AS num_subscribe
				FROM " . ABO_LISTE_TABLE . "
				WHERE abo_id = " . $this->account['abo_id'];
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible de vérifier la table de jointure', ERROR);
				return false;
			}
			
			$num_subscribe = $result->column('num_subscribe');
			
			$db->beginTransaction();
			
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
			
			$db->commit();
			$this->alert_admin(false);
			
			return true;
		}
		else
		{
			$this->account['code'] = generate_key(20);
			
			$sql = "UPDATE " . ABO_LISTE_TABLE . "
				SET register_key = '{$this->account['code']}'
				WHERE abo_id = {$this->account['abo_id']}
					AND liste_id = " . $this->listdata['liste_id'];
			if( !$db->query($sql) )
			{
				trigger_error('Impossible d\'assigner le nouvelle clé d\'enregistrement', ERROR);
				return false;
			}
			
			$this->mailer->set_from($this->listdata['sender_email'], unhtmlspecialchars($this->listdata['liste_name']));
			$this->mailer->set_address($this->account['email']);
			$this->mailer->set_subject($lang['Subject_email']['Unsubscribe_1']);
			$this->mailer->set_priority(3);
			$this->mailer->set_return_path($this->listdata['return_email']);
			
			$email_tpl = ( $this->listdata['use_cron'] ) ? 'unsubscribe_cron' : 'unsubscribe_form';
			
			$this->mailer->use_template($email_tpl, array(
				'LISTE'    => unhtmlspecialchars($this->listdata['liste_name']),
				'SITENAME' => $nl_config['sitename'],
				'URLSITE'  => $nl_config['urlsite'],
				'SIG'      => $this->listdata['liste_sig']
			));
			
			if( $this->listdata['use_cron'] )
			{
				$this->mailer->assign_tags(array(
					'EMAIL_NEWSLETTER' => $this->liste_email,
					'CODE'             => $this->account['code']
				));
			}
			else
			{
				$this->mailer->assign_tags(array(
					'LINK' => $this->make_link()
				));
			}
			
			if( !$this->mailer->send() )
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
			
			$sql = "UPDATE " . ABO_LISTE_TABLE . "
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
	
	function make_link()
	{
		$formURL = $this->listdata['form_url'];
		if( !empty($GLOBALS['formURL']) && empty($_REQUEST['formURL']) && empty($_FILES['formURL']) )
		{
			$formURL = $GLOBALS['formURL'];
		}
		
		return $formURL . (strstr($formURL, '?') ? '&' : '?') . $this->account['code'];
	}
	
	function alert_admin($new_subscribe)
	{
		global $db, $nl_config, $lang;
		
		if( $new_subscribe == true )
		{
			$fieldname  = 'email_new_subscribe';
			$fieldvalue = SUBSCRIBE_NOTIFY_YES;
			$subject    = $lang['Subject_email']['New_subscribe'];
			$template   = 'admin_new_subscribe';
		}
		else
		{
			$fieldname  = 'email_unsubscribe';
			$fieldvalue = UNSUBSCRIBE_NOTIFY_YES;
			$subject    = $lang['Subject_email']['Unsubscribe_2'];
			$template   = 'admin_unsubscribe';
		}
		
		$sql = "SELECT a.admin_login, a.admin_email, a.admin_level, aa.auth_view
			FROM " . ADMIN_TABLE . " AS a
				LEFT JOIN " . AUTH_ADMIN_TABLE . " AS aa ON aa.admin_id = a.admin_id
					AND aa.liste_id = {$this->listdata['liste_id']}
			WHERE a.$fieldname = " . $fieldvalue;
		if( $result = $db->query($sql) )
		{
			if( $row = $result->fetch() )
			{
				$this->mailer->clear_all();
				$this->mailer->set_from($this->listdata['sender_email'], unhtmlspecialchars($this->listdata['liste_name']));
				$this->mailer->set_subject($subject);
				
				$this->mailer->use_template($template, array(
					'EMAIL'   => $this->account['email'],
					'LISTE'   => unhtmlspecialchars($this->listdata['liste_name']),
					'URLSITE' => $nl_config['urlsite'],
					'SIG'     => $this->listdata['liste_sig']
				));
				
				do
				{
					if( $row['admin_level'] != ADMIN && $row['auth_view'] != true )
					{
						continue;
					}
					
					$this->mailer->clear_address();
					$this->mailer->set_address($row['admin_email'], $row['admin_login']);
					
					$this->mailer->assign_tags(array(
						'USER' => $row['admin_login']
					));
					
					$this->mailer->send(); // envoi
				}
				while( $row = $result->fetch() );
			}
		}
	}
	
	function update_stats()
	{
		@include WA_ROOTDIR . '/includes/functions.stats.php';
		
		if( function_exists('update_stats') )
		{
			update_stats($this->listdata);
		}
	}
}

}
?>
