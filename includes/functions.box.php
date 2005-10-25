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

/**
 * lang_box()
 * 
 * Construction de la liste déroulante des langues disponibles pour le script
 * 
 * @param string $default_lang    Langue actuellement utilisée
 * 
 * @return string
 */
function lang_box($default_lang = '')
{
	$lang_ary = array();
	
	$res = @opendir(WA_PATH . 'language/');
	while( $filename = @readdir($res) ) 
	{
		if( preg_match('/^lang_([\w_-]+)\.php$/', $filename, $match) )
		{
			$lang_ary[] = $match[1];
		}
	}
	@closedir($res);
	
	if( count($lang_ary) > 1 )
	{
		$lang_box = '<select id="language" name="language">';
		
		asort($lang_ary);
		foreach( $lang_ary AS $lang_name )
		{
			$selected = ( $default_lang == $lang_name ) ? ' selected="selected"' : '';
			$lang_box .= '<option value="' . $lang_name . '"' . $selected . '> - ' . $lang_name . ' - </option>';
		}
		
		$lang_box .= '</select>';
	}
	else
	{
		list($lang_name) = $lang_ary;
		
		$lang_box = '<span class="m-texte">' . $lang_name . '<input type="hidden" id="language" name="language" value="' . $lang_name . '" />';
	}
	
	return $lang_box;
}

/**
 * format_box()
 * 
 * Construction de la liste déroulante des formats de newsletter
 * 
 * @param string  $select_name       Nom de la liste déroulante
 * @param integer $default_format    Format par défaut
 * @param boolean $option_submit     True si submit lors du changement de valeur de la liste
 * @param boolean $multi_format      True si on doit affiche également multi-format comme valeur
 * 
 * @return string
 */
function format_box($select_name, $default_format = 0, $option_submit = false, $multi_format = false)
{
	$format_box = '<select id="' . $select_name . '" name="' . $select_name . '"';
	
	if( $option_submit )
	{
		$format_box .= '>';//' onchange="this.form.submit();">';
	}
	else
	{
		$format_box .= '>';
	}
	
	$format_box .= '<option value="1"' . (( $default_format == FORMAT_TEXTE ) ? 'selected="selected"' : '' ) . '> - texte - </option>';
	$format_box .= '<option value="2"' . (( $default_format == FORMAT_HTML ) ? 'selected="selected"' : '' ) . '> - html - </option>';
	
	if( $multi_format )
	{
		$format_box .= '<option value="3"' . (( $default_format == FORMAT_MULTIPLE ) ? 'selected="selected"' : '' ) . '> - texte &amp; html - </option>';
	}
	
	$format_box .= '</select>';
	
	return $format_box;
}

/**
 * date_box()
 * 
 * Construction de la liste déroulante des périodes mois/année pour les statistiques
 * 
 * @param array   $listdata    Données de la liste concernée
 * @param integer $month       Chiffre du mois de valeur par défaut
 * @param integer $year        Chiffre de l'année de valeur par défaut
 * 
 * @return string
 */
function date_box($listdata, $month, $year)
{
	global $db, $datetime;
	
	$m = date('n');
	$y = date('Y');
	
	$first_m = date('n', $listdata['liste_startdate']);
	$first_y = date('Y', $listdata['liste_startdate']);
	
	$date_box = '<select name="date">';
	
	do
	{
		$toc = ( $m == $first_m && $y == $first_y ) ? false : true;
		
		$str_month = date('F', mktime(0, 0, 0, $m, 1, $y));
		
		$selected = ( $month == $m && $year == $y ) ? ' selected="selected"' : '';
		$date_box .= '<option value="' . $m . '_' . $y . '"' . $selected . '> - ' . $datetime[$str_month] . ' ' . $y . ' - </option>';
		
		$m--;
		if( $m == 0 )
		{
			$m = 12;
			$y--;
		}
	}
	while( $toc );
	
	$date_box .= '</select>';
	
	return $date_box;
}

?>