<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if( !defined('IN_NEWSLETTER') )
{
	exit('<b>No hacking</b>');
}

define('IN_ADMIN',   true);
define('WA_ROOTDIR', '..');

$secure = true;

require WA_ROOTDIR . '/start.php';
require WA_ROOTDIR . '/includes/class.sessions.php';
require WA_ROOTDIR . '/includes/class.auth.php';

$liste = ( !empty($_REQUEST['liste']) ) ? intval($_REQUEST['liste']) : 0;

//
//// Start session and load settings 
//
$session = new Session();

$admindata = $session->check($liste);
load_settings($admindata);
//
//// End 
//

if( !defined('IN_LOGIN') )
{
	if( !$admindata )
	{
		$redirect  = '?redirect=' . basename(server_info('PHP_SELF'));
		$redirect .= ( server_info('QUERY_STRING') != '' ) ? rawurlencode('?' . server_info('QUERY_STRING')) : '';
		
		Location('login.php' . $redirect);
	}
	
	if( !defined('IN_UPGRADE') )
	{
		//
		// On vérifie si les tables du script sont bien à jour
		//
		if( !check_db_version(@$nl_config['db_version']) )
		{
			$output->addLine($lang['Need_upgrade_db']);
			$output->addLine($lang['Need_upgrade_db_link'], WA_ROOTDIR.'/admin/upgrade.php');
			$output->displayMessage();
		}
		
		$auth = new Auth();
		
		//
		// Si la liste en session n'existe pas, on met à jour la session
		//
		if( !isset($auth->listdata[$admindata['session_liste']]) )
		{
			$admindata['session_liste'] = 0;
			
			$sql = sprintf("UPDATE %s
				SET session_liste = 0 
				WHERE session_id = '%s' 
					AND admin_id = %d",
				SESSIONS_TABLE,
				$session->session_id,
				$admindata['admin_id']
			);
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de mettre à jour le session_liste', ERROR);
			}
		}
	}
	
	if( $secure && strtoupper(server_info('REQUEST_METHOD')) == 'POST' && $session->new_session )
	{
		$output->displayMessage('Invalid_session');
	}
}
