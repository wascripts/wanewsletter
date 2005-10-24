<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation; either version 2 
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * 
 * @package Wanewsletter
 * @author  Bobe <wascripts@phpcodeur.net>
 * @link    http://phpcodeur.net/wascripts/wanewsletter/
 * @license http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 * @version $Id$
 */

define('IN_NEWSLETTER', true);

require './pagestart.php';

$mode     = ( !empty($_REQUEST['mode']) ) ? $_REQUEST['mode'] : '';
$admin_id = ( !empty($_REQUEST['admin_id']) ) ? intval($_REQUEST['admin_id']) : 0;

if( isset($_POST['cancel']) )
{
	Location('admin.php');
}

if( isset($_POST['delete_user']) )
{
	$mode = 'deluser';
}

//
// Seuls les administrateurs peuvent ajouter ou supprimer un utilisateur
//
if( ( $mode == 'adduser' || $mode == 'deluser' ) && $admindata['admin_level'] != ADMIN )
{
	$output->redirect('index.php', 4);
	
	$message  = $lang['Message']['Not_authorized'];
	$message .= '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . sessid('./index.php') . '">', '</a>');
	trigger_error($message, MESSAGE);
}

if( $mode == 'adduser' )
{
	$new_login = ( !empty($_POST['new_login']) ) ? trim(strip_tags($_POST['new_login'])) : '';
	$new_email = ( !empty($_POST['new_email']) ) ? trim(strip_tags($_POST['new_email'])) : '';
	
	if( isset($_POST['submit']) )
	{
		require $waroot . 'includes/functions.validate.php';
		
		if( !validate_pseudo($new_login) )
		{
			$error = TRUE;
			$msg_error[] = $lang['Message']['Invalid_login'];
		}
		else
		{
			$sql = "SELECT COUNT(*) AS login_test 
				FROM " . ADMIN_TABLE . " 
				WHERE admin_login = '" . $db->escape($new_login) . "'";
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible de tester le login', ERROR);
			}
			
			if( $db->result($result, 0, 'login_test') > 0 )
			{
				$error = TRUE;
				$msg_error[] = $lang['Message']['Double_login'];
			}
		}
		
		$result = check_email($new_email);
		
		if( $result['error'] )
		{
			$error = TRUE;
			$msg_error[] = $result['message'];
		}
		
		if( !$error )
		{
			$new_pass = generate_key(10);
			
			$sql_data = array();
			$sql_data['admin_login']      = $new_login;
			$sql_data['admin_pwd']        = md5($new_pass);
			$sql_data['admin_email']      = $new_email;
			$sql_data['admin_lang']       = $nl_config['language'];
			$sql_data['admin_dateformat'] = $nl_config['date_format'];
			$sql_data['admin_level']      = USER;
			
			if( !$db->query_build('INSERT', ADMIN_TABLE, $sql_data) )
			{
				trigger_error('Impossible d\'ajouter le nouvel administrateur', ERROR);
			}
			
			require $waroot . 'includes/class.mailer.php';
			
			$mailer = new Mailer($waroot . 'language/email_' . $nl_config['language'] . '/');
			
			if( $nl_config['use_smtp'] )
			{
				$mailer->smtp_path = $waroot . 'includes/';
				$mailer->use_smtp(
					$nl_config['smtp_host'],
					$nl_config['smtp_port'],
					$nl_config['smtp_user'],
					$nl_config['smtp_pass']
				);
			}
			
			$mailer->correctRpath = !is_disabled_func('ini_set');
			$mailer->hebergeur    = $nl_config['hebergeur'];
			
			$mailer->set_charset($lang['CHARSET']);
			$mailer->set_format(FORMAT_TEXTE);
			$mailer->set_from($admindata['admin_email'], $admindata['admin_login']);
			$mailer->set_address($new_email);
			$mailer->set_subject(sprintf($lang['Subject_email']['New_admin'], $nl_config['sitename']));
			
			$mailer->use_template('new_admin', array(
				'PSEUDO'     => $new_login,
				'SITENAME'   => $nl_config['sitename'],
				'PASSWORD'   => $new_pass,
				'LINK_ADMIN' => make_script_url('admin/index.php')
			));
			
			if( !$mailer->send() )
			{
				trigger_error(sprintf($lang['Message']['Failed_sending2'], $mailer->msg_error), ERROR);
			}
			
			$output->redirect('./admin.php', 6);
			
			$message  = $lang['Message']['Admin_added'];
			$message .= '<br /><br />' . sprintf($lang['Click_return_profile'], '<a href="' . sessid('./admin.php') . '">', '</a>');
			$message .= '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . sessid('./index.php') . '">', '</a>');
			trigger_error($message, MESSAGE);
		}
	}
	
	$output->addHiddenField('mode', 'adduser');
	$output->addHiddenField('sessid', $session->session_id);
	
	$output->page_header();
	
	$output->set_filenames(array(
		'body' => 'add_admin_body.tpl'
	));
	
	$output->assign_vars(array(
		'L_TITLE'         => $lang['Add_user'],
		'L_EXPLAIN'       => nl2br($lang['Explain']['admin']),
		'L_LOGIN'         => $lang['Login_new_user'],
		'L_EMAIL'         => $lang['Email_new_user'],
		'L_EMAIL_NOTE'    => nl2br($lang['Email_note']),
		'L_VALID_BUTTON'  => $lang['Button']['valid'],
		'L_CANCEL_BUTTON' => $lang['Button']['cancel'],
		
		'LOGIN' => htmlspecialchars($new_login),
		'EMAIL' => htmlspecialchars($new_email),
		
		'S_HIDDEN_FIELDS' => $output->getHiddenFields()
	));
	
	$output->pparse('body');
	
	$output->page_footer();
}
else if( $mode == 'deluser' )
{
	if( $admindata['admin_id'] == $admin_id )
	{
		trigger_error('Owner_account', MESSAGE);
	}
	
	if( isset($_POST['confirm']) )
	{
		$db->transaction(START_TRC);
		
		$sql = "DELETE FROM " . ADMIN_TABLE . " 
			WHERE admin_id = " . $admin_id;
		if( !$db->query($sql) )
		{
			trigger_error('Impossible de supprimer l\'administrateur', ERROR);
		}
		
		$sql = "DELETE FROM " . AUTH_ADMIN_TABLE . " 
			WHERE admin_id = " . $admin_id;
		if( !$db->query($sql) )
		{
			trigger_error('Impossible de supprimer les permissions de l\'administrateur', ERROR);
		}
		
		$db->transaction(END_TRC);
		
		//
		// Optimisation des tables
		//
		$db->check(array(ADMIN_TABLE, AUTH_ADMIN_TABLE));
		
		$output->redirect('./admin.php', 6);
		
		$message  = $lang['Message']['Admin_deleted'];
		$message .= '<br /><br />' . sprintf($lang['Click_return_profile'], '<a href="' . sessid('./admin.php') . '">', '</a>');
		$message .= '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . sessid('./index.php') . '">', '</a>');
		trigger_error($message, MESSAGE);
	}
	else
	{
		$output->addHiddenField('mode'	  , 'deluser');
		$output->addHiddenField('admin_id', $admin_id);
		$output->addHiddenField('sessid'  , $session->session_id);
		
		$output->page_header();
		
		$output->set_filenames(array(
			'body' => 'confirm_body.tpl'
		));
		
		$output->assign_vars(array(
			'L_CONFIRM' => $lang['Title']['confirm'],
			
			'TEXTE' => $lang['Confirm_del_user'],
			'L_YES' => $lang['Yes'],
			'L_NO'  => $lang['No'],
			
			'S_HIDDEN_FIELDS' => $output->getHiddenFields(),
			'U_FORM' => sessid('./admin.php')
		));
		
		$output->pparse('body');
		
		$output->page_footer();
	}
}

if( isset($_POST['submit']) )
{
	if( $admindata['admin_level'] != ADMIN && $admin_id != $admindata['admin_id'] )
	{
		$output->redirect('./index.php', 4);
		
		$message  = $lang['Message']['Not_authorized'];
		$message .= '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . sessid('./index.php') . '">', '</a>');
		trigger_error($message, MESSAGE);
	}
	
	$vararray = array('current_pass', 'new_pass', 'confirm_pass', 'email', 'dateformat', 'language');
	foreach( $vararray AS $varname )
	{
		${$varname} = ( !empty($_POST[$varname]) ) ? trim($_POST[$varname]) : '';
	}
	
	require $waroot . 'includes/functions.validate.php';
	
	if( $dateformat == '' )
	{
		$dateformat = $nl_config['date_format'];
	}
	
	if( $language == '' || !validate_lang($language) )
	{
		$language = $nl_config['language'];
	}
	
	$email_new_inscrit = ( !empty($_POST['email_new_inscrit']) ) ? intval($_POST['email_new_inscrit']) : SUBSCRIBE_NOTIFY_NO;
	
	if( $admin_id == $admindata['admin_id'] && $current_pass != '' && md5($current_pass) != $admindata['admin_pwd'] )
	{
		$error = TRUE;
		$msg_error[] = $lang['Message']['Error_login'];
	}
	
	$set_password = FALSE;
	if( ( $admin_id != $admindata['admin_id'] && $new_pass != '' ) || $current_pass != '' )
	{
		if( !validate_pass($new_pass) )
		{
			$error = TRUE;
			$msg_error[] = $lang['Message']['Alphanum_pass'];
		}
		else if( $new_pass != $confirm_pass )
		{
			$error = TRUE;
			$msg_error[] = $lang['Message']['Bad_confirm_pass'];
		}
		
		$set_password = TRUE;
	}
	
	$result = check_email($email);
	
	if( $result['error'] )
	{
		$error = TRUE;
		$msg_error[] = $result['message'];
	}
	
	if( !$error )
	{
		$sql_data = array(
			'admin_email'       => $email,
			'admin_dateformat'  => $dateformat,
			'admin_lang'        => $language,
			'email_new_inscrit' => $email_new_inscrit
		);
		
		if( $set_password )
		{
			$sql_data['admin_pwd'] = md5($new_pass);
		}
		
		if( $admindata['admin_level'] == ADMIN && $admin_id != $admindata['admin_id'] && !empty($_POST['admin_level']) )
		{
			$sql_data['admin_level'] = ( $_POST['admin_level'] == ADMIN ) ? ADMIN : USER;
		}
		
		if( !$db->query_build('UPDATE', ADMIN_TABLE, $sql_data, array('admin_id' => $admin_id)) )
		{
			trigger_error('Impossible de mettre le profil à jour', ERROR);
		}
		
		if( $admindata['admin_level'] == ADMIN )
		{
			$auth_data = ( $admindata['admin_id'] == $admin_id ) ? $auth->listdata : $auth->read_data($admin_id);
			$liste_ids = ( !empty($_POST['liste_id']) ) ? (array) $_POST['liste_id'] : array();
			
			foreach( $auth->auth_ary AS $auth_name )
			{
				${$auth_name . '_ary'} = ( !empty($_POST[$auth_name]) ) ? $_POST[$auth_name] : array();
			}
			
			for( $i = 0, $total_liste = count($liste_ids); $i < $total_liste; $i++ )
			{
				$sql_data = array();
				
				foreach( $auth->auth_ary AS $auth_name )
				{
					$sql_data[$auth_name] = ${$auth_name . '_ary'}[$i];
				}
				
				if( !isset($auth_data[$liste_ids[$i]]['auth_view']) )
				{
					$sql_data['admin_id'] = $admin_id;
					$sql_data['liste_id'] = $liste_ids[$i];
					
					if( !$db->query_build('INSERT', AUTH_ADMIN_TABLE, $sql_data) )
					{
						trigger_error('Impossible d\'insérer une nouvelle entrée dans la table des permissions', ERROR);
					}
				}
				else
				{
					$sql_where = array('admin_id' => $admin_id, 'liste_id' => $liste_ids[$i]);
					if( !$db->query_build('UPDATE', AUTH_ADMIN_TABLE, $sql_data, $sql_where) )
					{
						trigger_error('Impossible de mettre à jour la table des permissions', ERROR);
					}
				}
			}
		}
		
		if( $set_password )
		{
			$sql = "SELECT admin_login FROM " . ADMIN_TABLE . " 
				WHERE admin_id = " . $admin_id;
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible de récupérer le pseudo de cet utilisateur', ERROR);
			}
			
			$pseudo = $db->result($result, 0, 0);
			
			require $waroot . 'includes/class.mailer.php';
			
			$mailer = new Mailer($waroot . 'language/email_' . $nl_config['language'] . '/');
			
			if( $nl_config['use_smtp'] )
			{
				$mailer->smtp_path = $waroot . 'includes/';
				$mailer->use_smtp(
					$nl_config['smtp_host'],
					$nl_config['smtp_port'],
					$nl_config['smtp_user'],
					$nl_config['smtp_pass']
				);
			}
			
			$mailer->correctRpath = !is_disabled_func('ini_set');
			$mailer->hebergeur    = $nl_config['hebergeur'];
			
			$mailer->set_charset($lang['CHARSET']);
			$mailer->set_format(FORMAT_TEXTE);
			$mailer->set_from($admindata['admin_email'], $admindata['admin_login']);
			$mailer->set_address($email);
			$mailer->set_subject($lang['Subject_email']['New_pass']);
			
			$mailer->use_template('new_admin_pass', array(
				'PSEUDO'   => $pseudo,
				'PASSWORD' => $new_pass
			));
			
			if( !$mailer->send() )
			{
				trigger_error(sprintf($lang['Message']['Failed_sending2'], $mailer->msg_error), ERROR);
			}
		}
		
		$output->redirect('./admin.php', 6);
		
		$message  = $lang['Message']['Profile_updated'];
		$message .= '<br /><br />' . sprintf($lang['Click_return_profile'], '<a href="' . sessid('./admin.php?admin_id=' . $admin_id) . '">', '</a>');
		$message .= '<br /><br />' . sprintf($lang['Click_return_index'], '<a href="' . sessid('./index.php') . '">', '</a>');
		trigger_error($message, MESSAGE);
	}
}

$admin_box = '';

if( $admindata['admin_level'] == ADMIN )
{
	$current_admin = NULL;
	
	if( !empty($admin_id) && $admin_id != $admindata['admin_id'] )
	{
		$sql = "SELECT * FROM " . ADMIN_TABLE . " 
			WHERE admin_id = " . $admin_id;
		if( $result = $db->query($sql) )
		{
			if( $row = $db->fetch_array($result) )
			{
				$current_admin = $row;
			}
		}
	}
	
	if( !is_array($current_admin) )
	{
		$current_admin = $admindata;
	}
	
	$sql = "SELECT admin_id, admin_login 
		FROM " . ADMIN_TABLE . " 
		WHERE admin_id <> " . $current_admin['admin_id'] . " 
		ORDER BY admin_login ASC";
	if( !($result = $db->query($sql)) )
	{
		trigger_error('Impossible d\'obtenir la liste des administrateurs', ERROR);
	}
	
	if( $row = $db->fetch_array($result) )
	{
		$admin_box  = '<select id="admin_id" name="admin_id">';
		$admin_box .= '<option value="0"> - ' . $lang['Choice_user'] . ' - </option>';
		
		do 
		{
			$admin_box .= '<option value="' . $row['admin_id'] . '"> - ' . htmlspecialchars($row['admin_login']) . ' - </option>';
		}
		while( $row = $db->fetch_array($result) );
		
		$admin_box .= '</select>';
	}
	
	if( $current_admin['admin_id'] != $admindata['admin_id'] )
	{
		$listdata = $auth->read_data($current_admin['admin_id']);
	}
	else
	{
		$listdata = $auth->listdata;
	}
}
else
{
	$current_admin = $admindata;
}

require $waroot . 'includes/functions.box.php';

$output->addHiddenField('admin_id', $current_admin['admin_id']);
$output->addHiddenField('sessid',   $session->session_id);

if( $admindata['admin_level'] == ADMIN )
{
	$output->addLink('section', './admin.php?mode=adduser', $lang['Add_user']);
}

$output->page_header();

$output->set_filenames( array(
	'body' => 'admin_body.tpl'
));

$output->assign_vars(array(
	'L_TITLE'               => sprintf($lang['Title']['profile'], htmlspecialchars($current_admin['admin_login'])),
	'L_EXPLAIN'             => nl2br($lang['Explain']['admin']),
	'L_DEFAULT_LANG'        => $lang['Default_lang'],
	'L_EMAIL'               => $lang['Email_address'],
	'L_DATEFORMAT'          => $lang['Dateformat'],
	'L_NOTE_DATE'           => sprintf($lang['Fct_date'], '<a href="http://www.php.net/date">', '</a>'),
	'L_EMAIL_NEW_INSCRIT'   => $lang['Email_new_inscrit'],
	'L_PASS'                => $lang['Password'],
	'L_NEW_PASS'            => $lang['New_pass'],
	'L_CONFIRM_PASS'        => $lang['Conf_pass'],
	'L_NOTE_PASS'           => nl2br($lang['Note_pass']),
	'L_YES'                 => $lang['Yes'],
	'L_NO'                  => $lang['No'],
	'L_VALID_BUTTON'        => $lang['Button']['valid'],
	'L_RESET_BUTTON'        => $lang['Button']['reset'],
	
	'LANG_BOX'              => lang_box($current_admin['admin_lang']),
	'EMAIL'                 => $current_admin['admin_email'],
	'DATEFORMAT'            => $current_admin['admin_dateformat'],
	'EMAIL_NEW_INSCRIT_YES' => ( $current_admin['email_new_inscrit'] == SUBSCRIBE_NOTIFY_YES ) ? ' checked="checked"' : '',
	'EMAIL_NEW_INSCRIT_NO'  => ( $current_admin['email_new_inscrit'] == SUBSCRIBE_NOTIFY_NO ) ? ' checked="checked"' : '',
	
	'S_HIDDEN_FIELDS' => $output->getHiddenFields()
)); 

if( $admindata['admin_level'] == ADMIN )
{
	$output->assign_block_vars('admin_options', array(
		'L_ADD_ADMIN'     => $lang['Add_user'],
		'L_TITLE_MANAGE'  => $lang['Title']['manage'],
		'L_TITLE_OPTIONS' => $lang['Title']['other_options'],
		'L_ADMIN_LEVEL'   => $lang['User_level'],
		'L_LISTE_NAME'    => $lang['Liste_name2'],
		'L_VIEW'          => $lang['View'],
		'L_EDIT'          => $lang['Edit'],
		'L_DEL'           => $lang['Button']['delete'],
		'L_SEND'          => $lang['Button']['send'],
		'L_IMPORT'        => $lang['Import'],
		'L_EXPORT'        => $lang['Export'],
		'L_BAN'           => $lang['Ban'],
		'L_ATTACH'        => $lang['Attach'],
		'L_ADMIN'         => $lang['Admin'],
		'L_USER'          => $lang['User'],
		'L_DELETE_ADMIN'  => $lang['Del_user'],
		'L_NOTE_DELETE'   => nl2br($lang['Del_note']),
		
		'SELECTED_ADMIN'  => ( $current_admin['admin_level'] == ADMIN ) ? ' selected="selected"' : '',
		'SELECTED_USER'   => ( $current_admin['admin_level'] == USER ) ? ' selected="selected"' : ''
	));
	
	foreach( $listdata AS $listrow )
	{
		$output->assign_block_vars('admin_options.auth', array(
			'LISTE_NAME'      => $listrow['liste_name'],
			'LISTE_ID'        => $listrow['liste_id'],
			
			'BOX_AUTH_VIEW'   => $auth->box_auth(AUTH_VIEW,   $listrow),
			'BOX_AUTH_EDIT'   => $auth->box_auth(AUTH_EDIT,   $listrow),
			'BOX_AUTH_DEL'    => $auth->box_auth(AUTH_DEL,    $listrow),
			'BOX_AUTH_SEND'   => $auth->box_auth(AUTH_SEND,   $listrow),
			'BOX_AUTH_IMPORT' => $auth->box_auth(AUTH_IMPORT, $listrow),
			'BOX_AUTH_EXPORT' => $auth->box_auth(AUTH_EXPORT, $listrow),
			'BOX_AUTH_BACKUP' => $auth->box_auth(AUTH_BAN,    $listrow),
			'BOX_AUTH_ATTACH' => $auth->box_auth(AUTH_ATTACH, $listrow)
		));
	}
	
	if( $admin_box != '' )
	{
		$output->addHiddenField('sessid', $session->session_id);
		
		$output->assign_block_vars('admin_box', array(
			'L_VIEW_PROFILE'  => $lang['View_profile'],
			'L_BUTTON_GO'     => $lang['Button']['go'],
			
			'ADMIN_BOX'       => $admin_box,
			'S_HIDDEN_FIELDS' => $output->getHiddenFields()
		));
	}
}

if( $current_admin['admin_id'] == $admindata['admin_id'] )
{
	$output->assign_block_vars('owner_profil', array());
}

$output->pparse('body');

$output->page_footer();
?>