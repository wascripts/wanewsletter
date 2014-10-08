<?php 
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

define('IN_NEWSLETTER', true);

require './pagestart.php';
require WAMAILER_DIR . '/class.mailer.php';

if( $admindata['admin_level'] != ADMIN )
{
	$output->redirect('./index.php', 6);
	$output->addLine($lang['Message']['Not_authorized']);
	$output->addLine($lang['Click_return_index'], './index.php');
	$output->displayMessage();
}

$old_config = $nl_config;
$move_files = false;

if( isset($_POST['submit']) )
{
	require WA_ROOTDIR . '/includes/class.attach.php';
	require WA_ROOTDIR . '/includes/functions.validate.php';
	
	$new_config = array();
	foreach( $old_config as $name => $value )
	{
		$new_config[$name] = ( isset($_POST[$name]) ) ? trim($_POST[$name]) : $value;
	}
	
	// On ne touche plus à gd_img_type - n'est plus configurable par le panneau de configuration
	unset($new_config['gd_img_type']);
	
	if( $new_config['language'] == '' || !validate_lang($new_config['language']) )
	{
		$new_config['language'] = $nl_config['language'];
	}
	
	$new_config['sitename'] = strip_tags($new_config['sitename']);
	$new_config['urlsite']  = preg_replace('/^http(s)?:\/\/(.*?)\/?$/i', 'http\\1://\\2', $new_config['urlsite']);
	
	if( $new_config['path'] != '/' )
	{
		$new_config['path'] = preg_replace('/^\/?(.*?)\/?$/i', '/\\1/', $new_config['path']);
	}
	
	$new_config['date_format'] = ( $new_config['date_format'] == '' ) ? 'd M Y H:i' : $new_config['date_format'];
	
	// Restriction de caractères sur le nom du cookie
	if( preg_match("/[=,;\s\v]/", $new_config['cookie_name']) )
	{
		$error = true;
		$msg_error[] = nl2br($lang['Message']['Invalid_cookie_name']);
	}
	
	// Restriction sur le chemin de validité du cookie
	if( ($len = strlen($new_config['cookie_path'])) == 0 || strncmp($new_config['cookie_path'], $new_config['path'], $len) != 0 )
	{
		$error = true;
		$msg_error[] = nl2br(sprintf($lang['Message']['Invalid_cookie_path'],
			wan_htmlspecialchars($new_config['path'])
		));
	}
	else
	{
		$new_config['cookie_path'] = '/' . trim($new_config['cookie_path'], '/') . '/';
	}
	
	if( ($new_config['session_length'] = intval($new_config['session_length'])) <= 0 )
	{
		$new_config['session_length'] = 3600;
	}
	
	if( $new_config['upload_path'] != '/' )
	{
		$new_config['upload_path'] = trim($new_config['upload_path'], '/') . '/';
		
		if( $nl_config['use_ftp'] == 0 && $new_config['use_ftp'] == 0
			&& strcmp($nl_config['upload_path'], $new_config['upload_path']) !== 0 )
		{
			$move_files = true;
			$source_upload = WA_ROOTDIR . '/' . $nl_config['upload_path'];
			$dest_upload   = WA_ROOTDIR . '/' . $new_config['upload_path'];
			
			if( !file_exists($dest_upload) )
			{
				if( !mkdir($dest_upload, 0755) )
				{
					$error = true;
					$msg_error[] = sprintf($lang['Message']['Cannot_create_dir'],
						wan_htmlspecialchars(wa_realpath($dest_upload)));
				}
			}
			else if( !is_writable($dest_upload) )
			{
				$error = true;
				$msg_error[] = sprintf($lang['Message']['Dir_not_writable'],
					wan_htmlspecialchars(wa_realpath($dest_upload)));
			}
		}
	}
	
	if( ($new_config['max_filesize'] = intval($new_config['max_filesize'])) <= 0 )
	{
		$new_config['max_filesize'] = 100000;
	}
	
	$new_config['ftp_server']    = preg_replace('/^(?:ftp:\/\/)?(.*)$/i', '\\1', $new_config['ftp_server']);
	$new_config['sending_limit'] = intval($new_config['sending_limit']);
	
	if( !($new_config['ftp_port'] = intval($new_config['ftp_port'])) )
	{
		$new_config['ftp_port'] = 21;
	}
	
	if( !($new_config['smtp_port'] = intval($new_config['smtp_port'])) )
	{
		$new_config['smtp_port'] = 25;
	}
	
	if( empty($new_config['ftp_pass']) )
	{
		$new_config['ftp_pass'] = $old_config['ftp_pass'];
	}
	
	if( $new_config['use_ftp'] && extension_loaded('ftp') )
	{
		$result = Attach::connect_to_ftp(
			$new_config['ftp_server'],
			$new_config['ftp_port'],
			$new_config['ftp_user'],
			$new_config['ftp_pass'],
			$new_config['ftp_pasv'],
			$new_config['ftp_path']
		);
		
		if( $result['error'] )
		{
			$error = true;
			$msg_error[] = sprintf(nl2br($lang['Message']['bad_ftp_param']), $result['message']);
		}
		else
		{
			@ftp_close($result['connect_id']);
		}
	}
	else
	{
		$new_config['use_ftp'] = 0;
	}
	
	if( empty($new_config['smtp_pass']) )
	{
		$new_config['smtp_pass'] = $old_config['smtp_pass'];
	}
	
	if( $new_config['use_smtp'] && function_exists('fsockopen') )
	{
		preg_match('/^http(s)?:\/\/(.*?)\/?$/i', $new_config['urlsite'], $match);
		
		require WAMAILER_DIR . '/class.smtp.php';
		
		$smtp = new Smtp();
		
		$result = $smtp->connect(
			$new_config['smtp_host'],
			$new_config['smtp_port'],
			$new_config['smtp_user'],
			$new_config['smtp_pass'],
			$match[2]
		);
		
		if( !$result )
		{
			$error = true;
			$msg_error[] = sprintf(nl2br($lang['Message']['bad_smtp_param']),
				wan_htmlspecialchars($smtp->msg_error));
		}
		else
		{
			$smtp->quit();
		}
	}
	else
	{
		$new_config['use_smtp'] = 0;
	}
	
	if( !$new_config['disable_stats'] && extension_loaded('gd') )
	{
		require WA_ROOTDIR . '/includes/functions.stats.php';
		
		if( !is_writable(WA_STATSDIR) )
		{
			$error = true;
			$msg_error[] = sprintf($lang['Message']['Dir_not_writable'],
				wan_htmlspecialchars(wa_realpath(WA_STATSDIR)));
		}
	}
	else
	{
		$new_config['disable_stats'] = 1;
	}
	
	if( !$error )
	{
		wa_update_config(array_merge($old_config, $new_config));
		
		//
		// Déplacement des fichiers joints dans le nouveau dossier de stockage s'il est changé
		//
		if( $move_files )
		{
			if( $browse = dir($source_upload) )
			{
				while( ($entry = $browse->read()) !== false )
				{
					$source_file = $source_upload . $entry;
					$dest_file   = $dest_upload . $entry;
					
					if( is_file($source_file) )
					{
						//
						// Copie du fichier
						//
						if( copy($source_file, $dest_file) )
						{
							@chmod($dest_file, 0644);
							
							//
							// Suppression du fichier de l'ancien répertoire
							//
							Attach::remove_file($source_file);
						}
					}
				}
				$browse->close();
			}
		}
		
		$output->displayMessage('Success_modif');
	}
}
else
{
	$new_config = $old_config;
}

require WA_ROOTDIR . '/includes/functions.box.php';

$output->page_header();

$output->set_filenames( array(
	'body' => 'config_body.tpl'
));

$output->assign_vars( array(
	'TITLE_CONFIG_LANGUAGE'     => $lang['Title']['config_lang'],
	'TITLE_CONFIG_PERSO'        => $lang['Title']['config_perso'],
	'TITLE_CONFIG_COOKIES'      => $lang['Title']['config_cookies'],
	'TITLE_CONFIG_JOINED_FILES' => $lang['Title']['config_files'],
	'TITLE_CONFIG_EMAIL'        => $lang['Title']['config_email'],
	
	'L_EXPLAIN'                 => nl2br($lang['Explain']['config']),
	'L_EXPLAIN_COOKIES'         => nl2br($lang['Explain']['config_cookies']),
	'L_EXPLAIN_JOINED_FILES'    => nl2br($lang['Explain']['config_files']),
	'L_EXPLAIN_EMAIL'           => nl2br(sprintf($lang['Explain']['config_email'], '<a href="' . WA_ROOTDIR . '/docs/faq.' . $lang['CONTENT_LANG'] . '.html#p9">', '</a>')),
	
	'L_DEFAULT_LANG'            => $lang['Default_lang'],
	'L_SITENAME'                => $lang['Sitename'],
	'L_URLSITE'                 => $lang['Urlsite'],
	'L_URLSITE_NOTE'            => nl2br($lang['Urlsite_note']),
	'L_URLSCRIPT'               => $lang['Urlscript'],
	'L_URLSCRIPT_NOTE'          => nl2br($lang['Urlscript_note']),
	'L_DATE_FORMAT'             => $lang['Dateformat'],
	'L_NOTE_DATE'               => nl2br(sprintf($lang['Fct_date'], '<a href="http://www.php.net/date">', '</a>')), 
	'L_ENABLE_PROFIL_CP'        => $lang['Enable_profil_cp'],
	'L_COOKIE_NAME'             => $lang['Cookie_name'],
	'L_COOKIE_PATH'             => $lang['Cookie_path'],
	'L_LENGTH_SESSION'          => $lang['Session_length'],
	'L_SECONDS'                 => $lang['Seconds'],
	'L_UPLOAD_PATH'             => $lang['Upload_path'],
	'L_MAX_FILESIZE'            => $lang['Max_filesize'],
	'L_MAX_FILESIZE_NOTE'       => nl2br($lang['Max_filesize_note']),
	'L_OCTETS'                  => $lang['Octets'],
	'L_CHECK_EMAIL'             => $lang['Check_email'],
	'L_CHECK_EMAIL_NOTE'        => nl2br(sprintf($lang['Check_email_note'], '<a href="' . WA_ROOTDIR . '/docs/faq.' . $lang['CONTENT_LANG'] . '.html#p11">', '</a>')),
	'L_SENDING_LIMIT'           => $lang['Sending_limit'],
	'L_SENDING_LIMIT_NOTE'      => nl2br($lang['Sending_limit_note']),
	'L_USE_SMTP'                => $lang['Use_smtp'],
	'L_USE_SMTP_NOTE'           => nl2br($lang['Use_smtp_note']),
	'L_YES'                     => $lang['Yes'],
	'L_NO'                      => $lang['No'],
	'L_SMTP_SERVER'             => $lang['Smtp_server'],
	'L_SMTP_PORT'               => $lang['Smtp_port'],
	'L_SMTP_PORT_NOTE'          => nl2br($lang['Smtp_port_note']),
	'L_SMTP_USER'               => $lang['Smtp_user'],
	'L_SMTP_PASS'               => $lang['Smtp_pass'],
	'L_AUTH_SMTP_NOTE'          => nl2br($lang['Auth_smtp_note']),
	'L_VALID_BUTTON'            => $lang['Button']['valid'],
	'L_RESET_BUTTON'            => $lang['Button']['reset'],
	
	'LANG_BOX'                  => lang_box($new_config['language']),
	'SITENAME'                  => wan_htmlspecialchars($new_config['sitename']),
	'URLSITE'                   => $new_config['urlsite'],
	'URLSCRIPT'                 => $new_config['path'],
	'DATE_FORMAT'               => $new_config['date_format'],
	'CHECKED_PROFIL_CP_ON'      => ( $new_config['enable_profil_cp'] ) ? ' checked="checked"' : '',
	'CHECKED_PROFIL_CP_OFF'     => ( !$new_config['enable_profil_cp'] ) ? ' checked="checked"' : '',
	'COOKIE_NAME'               => $new_config['cookie_name'],
	'COOKIE_PATH'               => $new_config['cookie_path'],
	'LENGTH_SESSION'            => $new_config['session_length'],
	'UPLOAD_PATH'               => $new_config['upload_path'],
	'MAX_FILESIZE'              => $new_config['max_filesize'],
	'CHECKED_CHECK_EMAIL_ON'    => ( $new_config['check_email_mx'] ) ? ' checked="checked"' : '',
	'CHECKED_CHECK_EMAIL_OFF'   => ( !$new_config['check_email_mx'] ) ? ' checked="checked"' : '',
	'SENDING_LIMIT'             => $new_config['sending_limit'],
	'SMTP_ROW_CLASS'            => ( $new_config['use_smtp'] ) ? '' : 'inactive',
	'CHECKED_USE_SMTP_ON'       => ( $new_config['use_smtp'] ) ? ' checked="checked"' : '',
	'CHECKED_USE_SMTP_OFF'      => ( !$new_config['use_smtp'] ) ? ' checked="checked"' : '',
	'DISABLED_SMTP'             => ( !function_exists('fsockopen') ) ? ' disabled="disabled"' : '',
	'WARNING_SMTP'              => ( !function_exists('fsockopen') ) ? ' <span style="color: red;">[not available]</span>' : '',
	'SMTP_HOST'                 => $new_config['smtp_host'],
	'SMTP_PORT'                 => $new_config['smtp_port'],
	'SMTP_USER'                 => $new_config['smtp_user']
));

if( extension_loaded('ftp') )
{
	$output->assign_block_vars('extension_ftp', array(
		'L_USE_FTP'            => $lang['Use_ftp'],
		'L_FTP_SERVER'         => $lang['Ftp_server'],
		'L_FTP_SERVER_NOTE'    => $lang['Ftp_server_note'],
		'L_FTP_PORT'           => $lang['Ftp_port'],
		'L_FTP_PORT_NOTE'      => $lang['Ftp_port_note'],
		'L_FTP_PASV'           => $lang['Ftp_pasv'],
		'L_FTP_PASV_NOTE'      => $lang['Ftp_pasv_note'],
		'L_FTP_PATH'           => $lang['Ftp_path'],
		'L_FTP_USER'           => $lang['Ftp_user'],
		'L_FTP_PASS'           => $lang['Ftp_pass'],
		
		'FTP_ROW_CLASS'        => ( $new_config['use_ftp'] ) ? '' : 'inactive',
		'CHECKED_USE_FTP_ON'   => ( $new_config['use_ftp'] ) ? ' checked="checked"' : '',
		'CHECKED_USE_FTP_OFF'  => ( !$new_config['use_ftp'] ) ? ' checked="checked"' : '',
		'CHECKED_FTP_PASV_ON'  => ( $new_config['ftp_pasv'] ) ? ' checked="checked"' : '',
		'CHECKED_FTP_PASV_OFF' => ( !$new_config['ftp_pasv'] ) ? ' checked="checked"' : '',
		'FTP_SERVER'           => $new_config['ftp_server'],
		'FTP_PORT'             => $new_config['ftp_port'],
		'FTP_PATH'             => $new_config['ftp_path'],
		'FTP_USER'             => $new_config['ftp_user'],
	));
}

$output->assign_block_vars('choice_engine_send', array(
	'L_ENGINE_SEND'       => $lang['Choice_engine_send'],
	'L_ENGINE_BCC'        => $lang['With_engine_bcc'],
	'L_ENGINE_UNIQ'       => $lang['With_engine_uniq'],
	
	'CHECKED_ENGINE_BCC'  => ( $new_config['engine_send'] == ENGINE_BCC ) ? ' checked="checked"' : '',
	'CHECKED_ENGINE_UNIQ' => ( $new_config['engine_send'] == ENGINE_UNIQ ) ? ' checked="checked"' : ''
));

if( extension_loaded('gd') )
{
	$output->assign_block_vars('extension_gd', array(
		'TITLE_CONFIG_STATS'        => $lang['Title']['config_stats'],
		'L_EXPLAIN_STATS'           => nl2br($lang['Explain']['config_stats']),
		'L_DISABLE_STATS'           => $lang['Disable_stats'],
		
		'CHECKED_DISABLE_STATS_ON'  => ( $new_config['disable_stats'] ) ? ' checked="checked"' : '',
		'CHECKED_DISABLE_STATS_OFF' => ( !$new_config['disable_stats'] ) ? ' checked="checked"' : ''
	));
}
else
{
	$output->addHiddenField('disable_stats', '1');
}

$output->assign_var('S_HIDDEN_FIELDS', $output->getHiddenFields());

$output->pparse('body');

$output->page_footer();
?>