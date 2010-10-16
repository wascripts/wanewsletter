<?php
/**
 * Copyright (c) 2002-2010 Aurélien Maille
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

require '../admin/pagestart.php';

if( !$auth->check_auth(AUTH_VIEW, $admindata['session_liste']) )
{
	plain_error($lang['Message']['Not_auth_view']);
}

$listdata = $auth->listdata[$admindata['session_liste']];

$file_id  = ( !empty($_GET['fid']) ) ? intval($_GET['fid']) : 0;
$filename = ( !empty($_GET['file']) ) ? trim($_GET['file']) : '';

if( $filename != '' )
{
	$sql_where = 'jf.file_real_name = \'' . $db->escape($filename) . '\'';
}
else
{
	$sql_where = 'jf.file_id = ' . $file_id;
}

$sql = "SELECT jf.file_real_name, jf.file_physical_name, jf.file_size, jf.file_mimetype
	FROM " . JOINED_FILES_TABLE . " AS jf
		INNER JOIN " . LOG_FILES_TABLE . " AS lf ON lf.file_id = jf.file_id
		INNER JOIN " . LOG_TABLE . " AS l ON l.log_id = lf.log_id
			AND l.liste_id = $listdata[liste_id]
	WHERE $sql_where";
if( !($result = $db->query($sql)) )
{
	plain_error('Impossible de récupérer les données sur le fichier : ' . $db->error);
}

if( $filedata = $result->fetch() )
{
	if( $nl_config['use_ftp'] )
	{
		require WA_ROOTDIR . '/includes/class.attach.php';
		
		$attach = new Attach();
		$tmp_filename = $attach->ftp_to_tmp($filedata);
	}
	else
	{
		$tmp_filename = wa_realpath(WA_ROOTDIR . '/' . $nl_config['upload_path'] . $filedata['file_physical_name']);
	}
	
	$is_svg = (strcasecmp($filedata['file_mimetype'], 'image/svg+xml') == 0);
	
	if( ($data = file_get_contents($tmp_filename)) === false )
	{
		exit('Impossible de récupérer le contenu du fichier (fichier non accessible en lecture)');
	}
	
	header('Date: ' . gmdate(DATE_RFC1123));
	header('Cache-Control: public, max-age=3600');
	header('Content-Disposition: inline; filename="' . $filedata['file_real_name'] . '"');
	header('Content-Length: ' . $filedata['file_size']);
	
	if( preg_match('#^image/svg\+xml$#i', $filedata['file_mimetype']) )
	{
		$charset = 'UTF-8';
		if( preg_match('/^<\?xml(.+?)\?>/', $data, $match) )
		{
			if( preg_match('/encoding="([a-z0-9.:_-]+)"/i', $match[0], $match2) )
			{
				$charset = $match2[1];
			}
		}
		
		header('Content-Type: ' . $filedata['file_mimetype'] . '; charset=' . $charset);
	}
	else
	{
		header('Content-Type: ' . $filedata['file_mimetype']);
	}
	
	echo $data;
	
	//
	// Si l'option FTP est utilisée, suppression du fichier temporaire
	//
	if( $nl_config['use_ftp'] )
	{
		$attach->remove_file($tmp_filename);
	}
	
	exit;
}
else
{
	plain_error('Unknown file !');
}

?>