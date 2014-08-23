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
 */

define('IN_SUBSCRIBE', true);
define('WA_ROOTDIR',   '.');

require WA_ROOTDIR . '/newsletter.php';

$list_box = '';

$sql = "SELECT liste_id, liste_name, liste_format
	FROM " . LISTE_TABLE . "
	WHERE liste_public = " . TRUE;
if( !($result = $db->query($sql)) )
{
	trigger_error('Impossible d\'obtenir la liste des listes de diffusion', ERROR);
}
else
{
	$list_box = '<select id="liste" name="liste">';
	
	if( $row = $result->fetch() )
	{
		do
		{
			if( $row['liste_format'] == FORMAT_TEXTE )
			{
				$f = 'txt';
			}
			else if( $row['liste_format'] == FORMAT_HTML )
			{
				$f = 'html';
			}
			else
			{
				$f = 'txt &amp; html';
			}
			
			$list_box .= '<option value="' . $row['liste_id'] . '"> ' . $row['liste_name'] . ' (' . $f . ') </option>';
		}
		while( $row = $result->fetch() );
	}
	else
	{
		$message = 'No list found';
	}
	
	$list_box .= '</select>';
}

$output->send_headers(true);

$output->set_filenames(array(
	'body' => 'subscribe_body.tpl'
));

$output->assign_vars(array(
	'PAGE_TITLE'      => $lang['Title']['form'],
	'L_INVALID_EMAIL' => str_replace('\'', '\\\'', $lang['Message']['Invalid_email']),
	'L_PAGE_LOADING'  => str_replace('\'', '\\\'', $lang['Page_loading']),
	'L_EMAIL'         => $lang['Email_address'],
	'L_FORMAT'        => $lang['Format'],
	'L_DIFF_LIST'     => $lang['Diff_list'],
	'L_SUBSCRIBE'     => $lang['Subscribe'],
	'L_SETFORMAT'     => $lang['Setformat'],
	'L_UNSUBSCRIBE'   => $lang['Unsubscribe'],
	'L_VALID_BUTTON'  => $lang['Button']['valid'],
	
	'LIST_BOX' => $list_box,
	'MESSAGE'  => $message
));

$output->pparse('body');

//
// On réactive le gestionnaire d'erreur précédent
//
@restore_error_handler();

?>
