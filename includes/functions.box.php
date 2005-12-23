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

if( !defined('FUNCTIONS_BOX_INC') ) {

define('FUNCTIONS_BOX_INC', true);

/**
 * lang_box()
 * 
 * Construction de la liste déroulante des langues disponibles pour le script
 * 
 * @param string $default_lang  Langue actuellement utilisée
 * 
 * @return string
 */
function lang_box($default_lang = '')
{
	$lang_ary = array();
	$browse   = dir(WA_ROOTDIR . '/language');
	
	while( ($entry = $browse->read()) !== false )
	{
		if( preg_match('/^lang_([\w_-]+)\.php$/', $entry, $match) )
		{
			array_push($lang_ary, $match[1]);
		}
	}
	$browse->close();
	
	if( count($lang_ary) > 1 )
	{
		asort($lang_ary);
		
		$lang_box = '<select id="language" name="language">';
		foreach( $lang_ary as $lang_name )
		{
			$selected  = ( $default_lang == $lang_name ) ? ' selected="selected"' : '';
			$lang_box .= sprintf('<option value="%1$s"%2$s>%1$s</option>', $lang_name, $selected);
		}
		$lang_box .= '</select>';
	}
	else
	{
		$lang_box = '<span class="m-texte">' . $lang_ary[0]
			. '<input type="hidden" id="language" name="language" value="' . $lang_ary[0] . '" />';
	}
	
	return $lang_box;
}

/**
 * format_box()
 * 
 * Construction de la liste déroulante des formats de newsletter
 * 
 * @param string  $select_name     Nom de la liste déroulante
 * @param integer $default_format  Format par défaut
 * @param boolean $option_submit   True si submit lors du changement de valeur de la liste
 * @param boolean $multi_format    True si on doit affiche également multi-format comme valeur
 * @param boolean $no_id           True pour ne pas mettre d'attribut id à la balise <select>
 * 
 * @return string
 */
function format_box($select_name, $default_format = 0, $option_submit = false, $multi_format = false, $no_id = false)
{
	$format_box = '<select' . ($no_id == false ? ' id="' . $select_name . '"' : '') . ' name="' . $select_name . '"';
	
	if( $option_submit )
	{
		$format_box .= '>';//' onchange="this.form.submit();">';
	}
	else
	{
		$format_box .= '>';
	}
	
	$format_box .= '<option value="1"' . (( $default_format == FORMAT_TEXTE ) ? 'selected="selected"' : '' ) . '>texte</option>';
	$format_box .= '<option value="2"' . (( $default_format == FORMAT_HTML ) ? 'selected="selected"' : '' ) . '>html</option>';
	
	if( $multi_format )
	{
		$format_box .= '<option value="3"' . (( $default_format == FORMAT_MULTIPLE ) ? 'selected="selected"' : '' ) . '>texte &amp; html</option>';
	}
	
	$format_box .= '</select>';
	
	return $format_box;
}

}
?>