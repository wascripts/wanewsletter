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

if( !defined('CLASS_OUTPUT_INC') ) {

define('CLASS_OUTPUT_INC', true);

class output extends Template {

	/**
	 * Liens relatifs au document
	 * 
	 * @var string
	 * @access private
	 */
	var $links         = '';
	
	/**
	 * Scripts clients liés au document
	 * 
	 * @var string
	 * @access private
	 */
	var $javascript    = '';
	
	/**
	 * Champs cachés d'un formulaire du document
	 * 
	 * @var string
	 * @access private
	 */
	var $hidden_fields = '';
	
	/**
	 * Meta de redirection
	 * 
	 * @var string
	 * @access private
	 */
	var $meta_redirect = '';
	
	/**
	 * @param string $template_root
	 * 
	 * @access public
	 * @return void
	 */
	function output($template_root)
	{
		//
		// Réglage du dossier contenant les templates
		//
		$this->set_rootdir($template_root);
	}
	
	/**
	 * Ajout d'un lien relatif au document
	 * 
	 * @param string $rel      Relation qui lie le document cible au document courant
	 * @param string $url      URL du document cible
	 * @param string $title    Titre éventuel
	 * @param string $type     Type MIME du document cible
	 * 
	 * @access public
	 * @return void
	 */
	function addLink($rel, $url, $title = '', $type = '')
	{
		$this->links .= "\r\n\t<link rel=\"$rel\" href=\""
			. (( function_exists('sessid') ) ? sessid($url) : $url) . "\" title=\"$title\" />";
	}
	
	/**
	 * Retourne les liens relatifs au document
	 * 
	 * @access private
	 * @return string
	 */
	function getLinks()
	{
		return trim($this->links);
	}
	
	/**
	 * Ajout d'un script client
	 * 
	 * @param string $url
	 * 
	 * @access public
	 * @return void
	 */
	function addScript($url)
	{
		$this->javascript .= "\r\n\t<script src=\"$url\"></script>";
	}
	
	/**
	 * Retourne les scripts clients liés au document
	 * 
	 * @access private
	 * @return string
	 */
	function getScripts()
	{
		return trim($this->javascript);
	}
	
	/**
	 * Ajoute un champs caché pour un formulaire
	 * 
	 * @param string $name
	 * @param string $value
	 * 
	 * @access public
	 * @return void
	 */
	function addHiddenField($name, $value)
	{
		$this->hidden_fields .= sprintf('<input type="hidden" name="%s" value="%s" />', $name, $value) . "\r\n";
	}
	
	/**
	 * Retourne l'ensemble des champs cachés ajoutés et réinitialise la propriété hidden_fields
	 * 
	 * @access public
	 * @return string
	 */
	function getHiddenFields()
	{
		$tmp = $this->hidden_fields;
		$this->hidden_fields = '';
		
		return trim($tmp);
	}
	
	/**
	 * Ajoute un meta de redirection pour la page en cours
	 * 
	 * @param string  $url
	 * @param integer $timer
	 * 
	 * @access public
	 * @return void
	 */
	function redirect($url, $timer)
	{
		$this->meta_redirect = sprintf('<meta http-equiv="Refresh" content="%d; url=%s" />',
			$timer, (( function_exists('sessid') ) ? sessid($url) : $url));
	}
	
	/**
	 * Envoie en sortie les en-têtes HTTP appropriés et l'en-tête du document
	 * 
	 * @param boolean $use_template
	 * @param string  $page_title
	 * 
	 * @access public
	 * @return void
	 */
	function page_header($use_template = true, $page_title = '')
	{
		global $nl_config, $lang, $template, $admindata, $auth;
		global $meta, $simple_header, $error, $msg_error;
		
		define('HEADER_INC', true);
		
		$this->send_headers();
		
		$this->set_filenames(array(
			'header' => ( $simple_header ) ? 'simple_header.tpl' :'header.tpl'
		));
		
		if( defined('IN_ADMIN') )
		{
			$this->addLink('top index', './index.php',              $lang['Title']['accueil']);
			$this->addLink('chapter', './config.php',               $lang['Module']['config']);
			$this->addLink('chapter', './envoi.php',                $lang['Title']['send']);
			$this->addLink('chapter', './view.php?mode=abonnes',    $lang['Module']['subscribers']);
			$this->addLink('chapter', './view.php?mode=liste',      $lang['Module']['list']);
			$this->addLink('chapter', './view.php?mode=log',        $lang['Module']['log']);
			$this->addLink('chapter', './tools.php?mode=export',    $lang['Title']['export']);
			$this->addLink('chapter', './tools.php?mode=import',    $lang['Title']['import']);
			$this->addLink('chapter', './tools.php?mode=ban',       $lang['Title']['ban']);
			$this->addLink('chapter', './tools.php?mode=generator', $lang['Title']['generator']);
			
			if( isset($admindata['admin_level']) && $admindata['admin_level'] == ADMIN )
			{
				$this->addLink('chapter', './tools.php?mode=attach' , $lang['Title']['attach']);
				$this->addLink('chapter', './tools.php?mode=backup' , $lang['Title']['backup']);
				$this->addLink('chapter', './tools.php?mode=restore', $lang['Title']['restore']);
			}
			
			$this->addLink('chapter',   './admin.php', $lang['Module']['users']);
			$this->addLink('chapter',   './stats.php', $lang['Title']['stats']);
			$this->addLink('help',      WA_ROOTDIR . '/docs/faq.' . $lang['CONTENT_LANG'] . '.html'   , $lang['Faq']);
			$this->addLink('author',    WA_ROOTDIR . '/docs/readme.' . $lang['CONTENT_LANG'] . '.html', $lang['Author_note']);
			$this->addLink('copyright', 'http://www.gnu.org/copyleft/gpl.html', 'Copyleft');
			
			$page_title = sprintf($lang['General_title'], wan_htmlspecialchars($nl_config['sitename']));
		}
		else
		{
			$this->addLink('top index', './profil_cp.php',                  $lang['Title']['accueil']);
			$this->addLink('section',   './profil_cp.php?mode=editprofile', $lang['Module']['editprofile']);
			$this->addLink('section',   './profil_cp.php?mode=archives',    $lang['Module']['log']);
			$this->addLink('section',   './profil_cp.php?mode=logout',      $lang['Module']['logout']);
			
			$page_title = $lang['Title']['profil_cp'];
		}
		
		if( !defined('IN_ADMIN') || empty($admindata['admin_login']) )
		{
			$l_logout = $lang['Module']['logout'];
		}
		else
		{
			$l_logout = sprintf($lang['Module']['logout_2'], wan_htmlspecialchars($admindata['admin_login'], ENT_NOQUOTES));
		}
		
		$this->assign_vars( array(
			'PAGE_TITLE'   => $page_title,
			'META'         => $this->meta_redirect,
			'CONTENT_LANG' => $lang['CONTENT_LANG'],
			'CONTENT_DIR'  => $lang['CONTENT_DIR'],
			'CHARSET'      => $lang['CHARSET'],
			'L_LOG'        => $lang['Module']['log'],
			
			'L_LOGOUT'     => $l_logout,
			'S_NAV_LINKS'  => $this->getLinks(),
			'S_SCRIPTS'    => $this->getScripts()
		));
		
		if( defined('IN_ADMIN') )
		{
			$this->assign_vars(array(
				'L_INDEX'       => $lang['Module']['accueil'],
				'L_CONFIG'      => $lang['Module']['config'],
				'L_SEND'        => $lang['Module']['send'],
				'L_SUBSCRIBERS' => $lang['Module']['subscribers'],
				'L_LIST'        => $lang['Module']['list'],
				'L_TOOLS'       => $lang['Module']['tools'],
				'L_USERS'       => $lang['Module']['users'],
				'L_STATS'       => $lang['Module']['stats'],
				
				'SITENAME'      => wan_htmlspecialchars($nl_config['sitename'], ENT_NOQUOTES),
			));
		}
		else
		{
			$this->assign_vars(array(
				'L_EDITPROFILE' => $lang['Module']['editprofile']
			));
		}
		
		$this->assign_block_vars('meta_content_type', array(
			'CHARSET' => $lang['CHARSET']
		));
		
		if( $error )
		{
			$this->error_box($msg_error);
		}
		
		$this->pparse('header');
	}
	
	/**
	 * Envoi le pied de page et termine l'exécution du script
	 * 
	 * @access public
	 * @return void
	 */
	function page_footer()
	{
		global $db, $lang, $starttime;
		
		$this->set_filenames(array(
			'footer' => 'footer.tpl'
		));
		
		$dev_infos = (defined('DEV_INFOS') && DEV_INFOS == true);
		
		if( $dev_infos )
		{
			$version = sprintf('%s (%s)', WA_VERSION, substr(get_class($db), 5));
		}
		else
		{
			$version = WA_VERSION;
		}
		
		$this->assign_vars( array(
			'VERSION'   => $version,
			'TRANSLATE' => ( !empty($lang['TRANSLATE']) ) ? ' | Translate by ' . $lang['TRANSLATE'] : ''
		));
		
		if( $dev_infos )
		{
			$endtime   = array_sum(explode(' ', microtime()));
			$totaltime = ($endtime - $starttime);
			
			$this->assign_block_vars('dev_infos', array(
				'TIME_TOTAL' => sprintf('%.8f', $totaltime),
				'TIME_PHP'   => sprintf('%.3f', $totaltime - $db->sqltime),
				'TIME_SQL'   => sprintf('%.3f', $db->sqltime),
				'QUERIES'    => $db->queries
			));
		}
		
		if( !defined('IN_SUBSCRIBE') && !defined('IN_LOGIN') && count($GLOBALS['_php_errors']) > 0 )
		{
			$this->assign_block_vars('php_errors', array());
			
			foreach( $GLOBALS['_php_errors'] as $entry )
			{
				if( !is_scalar($entry) ) {
					$entry = nl2br(print_r($entry, true));
				}
				
				$this->assign_block_vars('php_errors.item', array(
					'TEXT' => $entry
				));
			}
		}
		
		$this->pparse('footer');
		
		$data = ob_get_contents();
		ob_end_clean();
		
		echo purge_latin1($data);
		
		//
		// On ferme la connexion à la base de données, si elle existe 
		//
		if( isset($db) && is_object($db) )
		{
			$db->close();
		}
		
		exit;
	}
	
	/**
	 * Envoie des en-têtes HTTP
	 * 
	 * @access public
	 * @return void
	 */
	function send_headers()
	{
		global $lang;
		
		header('Last-Modified: ' . gmdate(DATE_RFC1123));
		header('Expires: ' . gmdate(DATE_RFC1123));
		header('Cache-Control: no-cache, no-store, must-revalidate, private, pre-check=0, post-check=0, max-age=0');
		header('Pragma: no-cache');
		header('Content-Language: ' . $lang['CONTENT_LANG']);
		
		header('Content-Type: text/html; charset=' . $lang['CHARSET']);
		
		ob_start();
		ob_implicit_flush(0);
	}
	
	/**
	 * Envoi des en-têtes appropriés et d'une page html simplifiée avec les données fournies
	 * Termine également l'exécution du script
	 * 
	 * @param string $content
	 * @param string $page_title
	 * 
	 * @access public
	 * @return void
	 */
	function basic($content, $page_title = '')
	{
		global $lang;
		
		$lg      = ( !empty($lang['CONTENT_LANG']) ) ? $lang['CONTENT_LANG'] : 'fr';
		$dir     = ( !empty($lang['CONTENT_DIR']) ) ? $lang['CONTENT_DIR'] : 'ltr';
		$charset = ( !empty($lang['CHARSET']) ) ? $lang['CHARSET'] : 'ISO-8859-1';
		$content = purge_latin1($content);
		
		$this->send_headers();
		
		echo <<<BASIC
<!DOCTYPE html>
<html lang="$lg" dir="$dir">
<head>
	<meta charset="$charset" />
	$this->meta_redirect
	<title>$page_title</title>
	
	<style>
	body { margin: 10px; text-align: left; }
	</style>
</head>
<body>
	<div>$content</div>
</body>
</html>
BASIC;
		
		exit;
	}
	
	/**
	 * Affiche de message d'information
	 * 
	 * @param string $str
	 * 
	 * @access public
	 * @return void
	 */
	function message($str)
	{
		global $lang, $message;
		
		if( !empty($lang['Message'][$str]) )
		{
			$str = nl2br($lang['Message'][$str]);
		}
		
		if( defined('IN_CRON') )
		{
			exit($str);
		}
		
		if( !defined('IN_WA_FORM') && !defined('IN_SUBSCRIBE') )
		{
			$title = '<span style="color: #33DD33;">' . $lang['Title']['info'] . '</span>';
			
			if( !defined('HEADER_INC') )
			{
				$this->page_header();
			}
			
			$this->set_filenames(array(
				'body' => 'message_body.tpl'
			));
			
			$this->assign_vars( array(
				'MSG_TITLE' => $title,
				'MSG_TEXT'  => $str
			));
			
			$this->pparse('body');
			
			$this->page_footer();
			exit;
		}
		
		$message = $str;
	}
	
	/**
	 * Génération et affichage de liste d'erreur
	 * 
	 * @param string $msg_error
	 * 
	 * @access public
	 * @return void
	 */
	function error_box($msg_error)
	{
		$error_box = "<ul id=\"errorbox\">\n\t<li> ";
		if( is_array($msg_error) )
		{
			$error_box .= implode(" </li>\n\t<li> ", $msg_error);
		}
		else
		{
			$error_box .= $msg_error;
		}
		$error_box .= " </li>\n</ul>";
		
		$this->assign_vars(array(
			'ERROR_BOX' => $error_box
		));
	}
	
	/**
	 * Affichage des fichiers joints 
	 * 
	 * @param array   $logdata    Données du log concerné
	 * @param integer $format     Format du log visualisé (si dans view.php)
	 * 
	 * @access public
	 * @return boolean
	 */
	function files_list($logdata, $format = 0)
	{
		global $lang, $nl_config;
		
		$page_envoi  = ( strstr(server_info('PHP_SELF'), 'envoi.php') ) ? true : false;
		$body_size   = (strlen($logdata['log_body_text']) + strlen($logdata['log_body_html']));
		$total_size  = 1024; // ~ 1024 correspond au poids de base d'un email (en-têtes)
		$total_size += ( $body_size > 0 ) ? ($body_size / 2) : 0;
		$num_files   = count($logdata['joined_files']);
		
		if( $num_files == 0 )
		{
			return false;
		}
		
		$test_ary = array();
		for( $i = 0; $i < $num_files; $i++ )
		{
			$total_size  += $logdata['joined_files'][$i]['file_size'];
			$test_files[] = $logdata['joined_files'][$i]['file_real_name'];
		}
		
		if( $format == FORMAT_HTML && hasCidReferences($logdata['log_body_html'], $refs) > 0 )
		{
			$embed_files = array_intersect($test_files, $refs);
			
			if( ($num_files - count($embed_files)) == 0 )
			{
				return false;
			}
		}
		else
		{
			$embed_files = array();
		}
		
		$this->set_filenames(array(
			'files_box_body' => 'files_box.tpl'
		));
		
		$this->assign_vars(array(
			'L_FILENAME'       => $lang['Filename'],
			'L_FILESIZE'       => $lang['Filesize'],
			'L_TOTAL_LOG_SIZE' => $lang['Total_log_size'],
			
			'TOTAL_LOG_SIZE'   => formateSize($total_size),
			'S_ROWSPAN'        => ( $page_envoi ) ? '4' : '3'
		));
		
		if( $page_envoi == true )
		{
			$this->assign_block_vars('del_column', array());
			$this->assign_block_vars('joined_files.files_box', array(
				'L_TITLE_JOINED_FILES' => $lang['Title']['joined_files'],
				'L_DEL_FILE_BUTTON'    => $lang['Button']['del_file']
			));
			
			$u_download = './envoi.php?mode=download&amp;fid=%d';
		}
		else
		{
			$this->assign_block_vars('files_box', array(
				'L_TITLE_JOINED_FILES'	=> $lang['Title']['joined_files']
			));
			
			$u_download = './view.php?mode=download&amp;fid=%d';
		}
		
		$u_show = '../options/show.php?fid=%d';
		
		for( $i = 0; $i < $num_files; $i++ )
		{
			$filesize  = $logdata['joined_files'][$i]['file_size'];
			$filename  = $logdata['joined_files'][$i]['file_real_name'];
			$file_id   = $logdata['joined_files'][$i]['file_id'];
			$mime_type = $logdata['joined_files'][$i]['file_mimetype'];
			
			$tmp_filename = WA_ROOTDIR . '/' . $nl_config['upload_path'] . $logdata['joined_files'][$i]['file_physical_name'];
			$s_show = '';
			
			if( $nl_config['use_ftp'] || file_exists($tmp_filename) )
			{
				//
				// On affiche pas dans la liste les fichiers incorporés dans 
				// une newsletter au format HTML.
				//
				if( $format == FORMAT_HTML && in_array($filename, $embed_files) )
				{
					continue;
				}
				
				$filename = sprintf('<a href="%s">%s</a>',
					sessid(sprintf($u_download, $file_id)), wan_htmlspecialchars($filename));
				
				if( preg_match('#^image/#', $mime_type) )
				{
					$s_show  = '<a rel="show" href="' . sessid(sprintf($u_show, $file_id)) . '" type="' . $mime_type . '">';
					$s_show .= '<img src="../templates/images/icon_loupe.png" width="14" height="14" alt="voir" title="' . $lang['Show'] . '" />';
					$s_show .= '</a>';
				}
			}
			else
			{
				$filename = sprintf('<del title="%s">%s</del>',
					$lang['Message']['File_not_found'], wan_htmlspecialchars($filename));
			}
			
			$this->assign_block_vars('file_info', array(
				'OFFSET'     => ($i + 1),
				'FILENAME'   => $filename,
				'FILESIZE'   => formateSize($filesize),
				'S_SHOW'     => $s_show
			));
			
			if( $page_envoi )
			{
				$this->assign_block_vars('file_info.delete_options', array(
					'FILE_ID' => $file_id
				));
			}
		}
		
		$this->assign_var_from_handle('JOINED_FILES_BOX', 'files_box_body');
		
		return true;
	}
	
	/**
	 * Affichage de la page de sélection de liste ou insertion du select de choix de liste dans 
	 * le coin inférieur gauche de l'administration
	 * 
	 * @param integer $auth_type
	 * @param boolean $display
	 * @param string  $jump_to
	 * 
	 * @access public
	 * @return void
	 */
	function build_listbox($auth_type, $display = true, $jump_to = '')
	{
		global $admindata, $auth, $session, $lang;
		
		$tmp_box = '';
		$liste_id_ary = $auth->check_auth($auth_type);
		
		if( empty($jump_to) )
		{
			$jump_to = './' . wan_htmlspecialchars(basename(server_info('PHP_SELF')));
			$query_string = server_info('QUERY_STRING');
			
			if( $query_string != '' )
			{
				$jump_to .= '?' . wan_htmlspecialchars($query_string);
			}
		}
		
		foreach( $auth->listdata as $liste_id => $data )
		{
			if( in_array($liste_id, $liste_id_ary) )
			{
				$selected = ( $admindata['session_liste'] == $liste_id ) ? ' selected="selected"' : '';
				$tmp_box .= sprintf("<option value=\"%d\"%s>%s</option>\n\t", $liste_id, $selected, cut_str($data['liste_name'], 30));
			}
		}
		
		if( $tmp_box == '' )
		{
			if( $display )
			{
				$message = $lang['Message']['No_liste_exists'];
				if( $admindata['admin_level'] == ADMIN )
				{
					$message .= '<br /><br />' . sprintf($lang['Click_create_liste'], '<a href="' . sessid('./view.php?mode=liste&amp;action=add') . '">', '</a>');
				}
				
				$this->message($message);
			}
			
			return '';
		}
		
		$list_box = '<select id="liste" name="liste">';
		if( !$display )
		{
			$list_box .= '<option value="0">' . $lang['Choice_liste'] . '</option>';
		}
		$list_box .= $tmp_box . '</select>';
		
		$this->addHiddenField('sessid', $session->session_id);
		
		if( $display )
		{
			$this->page_header();
			
			$this->set_filenames(array(
				'body' => 'select_liste_body.tpl'
			));
			
			$this->assign_vars(array(
				'L_TITLE'         => $lang['Title']['select'],
				'L_SELECT_LISTE'  => $lang['Choice_liste'],
				'L_VALID_BUTTON'  => $lang['Button']['valid'],
				
				'LISTE_BOX'       => $list_box,
				'S_HIDDEN_FIELDS' => $this->getHiddenFields(),
				'U_FORM'          => sessid($jump_to)
			));
			
			$this->pparse('body');
			
			$this->page_footer();
		}
		else
		{
			$this->set_filenames(array(
				'list_box_body' => 'list_box.tpl'
			));
			
			$this->assign_vars(array(
				'L_VIEW_LIST'     => $lang['View_liste'],
				'L_BUTTON_GO'     => $lang['Button']['go'],
				
				'S_LISTBOX'       => $list_box,
				'S_HIDDEN_FIELDS' => $this->getHiddenFields(),
				
				'U_LISTBOX'       => sessid($jump_to)
			));
			
			$this->assign_var_from_handle('LISTBOX', 'list_box_body');
		}
	}
}

}
?>