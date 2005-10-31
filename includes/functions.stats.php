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

define('WA_STATSDIR', WA_ROOTDIR . '/stats', true);

@chmod(WA_STATSDIR, 0777);

/**
 * convertToRGB()
 * 
 * @param string $hexColor
 * 
 * @return object
 */
function convertToRGB($hexColor)
{
	$parts    = array();
	$hexColor = strtoupper($hexColor);
	
	if( strlen($hexColor) == 6 )
	{
		preg_match('/^#?([[:xdigit:]]{2})([[:xdigit:]]{2})([[:xdigit:]]{2})$/', $hexColor, $tmp);
		$parts['red']   = $tmp[1];
		$parts['green'] = $tmp[2];
		$parts['blue']  = $tmp[3];
	}
	else if( strlen($hexColor) == 3 )
	{
		preg_match('/^#?([[:xdigit:]])([[:xdigit:]])([[:xdigit:]])$/', $hexColor, $tmp);
		$parts['red']   = $tmp[1] . $tmp[1];
		$parts['green'] = $tmp[2] . $tmp[2];
		$parts['blue']  = $tmp[3] . $tmp[3];
	}
	else
	{
		$parts['red']   = '0';
		$parts['green'] = '0';
		$parts['blue']  = '0';
	}
	
	return (object)array_map('hexdec', $parts);
}

/**
 * xy_arc()
 * 
 * Calcule les coordonnées du rayon
 * 
 * @param float   $degre     Degré
 * @param integer $diametre  Diamètre du cercle
 * 
 * @return array
 */
function xy_arc($degre, $diametre)
{
	$x_arc = (cos($degre * (pi() / 180.0)) * ($diametre / 2));
	$y_arc = (sin($degre * (pi() / 180.0)) * ($diametre / 2));
	
	return array($x_arc, $y_arc);
}

/**
 * create_stats()
 * 
 * Créé le fichier de statistiques pour le mois et l'année donnés
 * 
 * @param array   $listdata  Données de la liste concernée
 * @param integer $month     Chiffre du mois
 * @param integer $year      Chiffre de l'année
 * 
 * @return boolean
 */
function create_stats($listdata, $month, $year)
{
	global $db, $nl_config;
	
	if( $nl_config['disable_stats'] || !is_available_extension('gd') )
	{
		return false;
	}
	
	@set_time_limit(300);
	
	$filename = date('Y_F', mktime(0, 0, 0, $month, 1, $year)) . '_list' . $listdata['liste_id'] . '.txt';
	
	if( $fw = @fopen(WA_STATSDIR . '/' . $filename, 'w') )
	{
		$stats = array();
		
		for( $day = 1, $i = 0; $day <= 31; $day++, $i++ )
		{
			$stats[$i] = 0;
			
			if( checkdate($month, $day, $year) )
			{
				$min_time = mktime(0, 0, 0, $month, $day, $year);
				$max_time = mktime(23, 59, 59, $month, $day, $year);
				
				$sql = "SELECT COUNT(a.abo_id) AS num_abo 
					FROM " . ABONNES_TABLE . " AS a, " . ABO_LISTE_TABLE . " AS al 
					WHERE al.liste_id = " . $listdata['liste_id'] . " 
						AND a.abo_id = al.abo_id 
						AND a.abo_status = " . ABO_ACTIF . " 
						AND ( a.abo_register_date BETWEEN $min_time AND $max_time )";
				if( $result = $db->query($sql) )
				{
					$stats[$i] = $db->result($result, 0, 'num_abo');
				}
			}
		}
		
		fwrite($fw, implode("\n", $stats));
		fclose($fw);
		
		return true;
	}
	
	return false;
}

/**
 * update_stats()
 * 
 * Mise à jour des données pour les statistiques 
 * 
 * @param array $listdata  Données de la liste concernée
 * 
 * @return boolean
 */
function update_stats($listdata)
{
	global $nl_config;
	
	if( $nl_config['disable_stats'] || !is_available_extension('gd') )
	{
		return false;
	}
	
	$filename = date('Y_F') . '_list' . $listdata['liste_id'] . '.txt';
	
	if( !file_exists(WA_STATSDIR . '/' . $filename) )
	{
		return create_stats($listdata, date('m'), date('Y'));
	}
	else
	{
		if( $fp = @fopen(WA_STATSDIR . '/' . $filename, 'r') )
		{
			$stats = clean_stats(fread($fp, filesize(WA_STATSDIR . '/' . $filename)));
			fclose($fp);
			
			$offset = (date('j') - 1);
			$stats[$offset] += 1;
			
			@chmod(WA_STATSDIR . '/' . $filename, 0666);
			if( $fw = @fopen(WA_STATSDIR . '/' . $filename, 'w') )
			{
				fwrite($fw, implode("\n", $stats));
				fclose($fw);
				
				return true;
			}
		}
		
		return false;
	}
}

/**
 * remove_stats()
 * 
 * Suppression/déplacement de stats (lors de la suppression d'une liste) 
 * 
 * @param integer $liste_from  Id de la liste dont on supprime/déplace les stats
 * @param mixed   $liste_to    Id de la liste de destination ou boolean (dans ce cas, on supprime)
 * 
 * @return boolean
 */
function remove_stats($liste_from, $liste_to = false)
{
	global $nl_config;
	
	if( $nl_config['disable_stats'] || !is_available_extension('gd') )
	{
		return false;
	}
	
	@set_time_limit(300);
	
	if( $res = @opendir(WA_STATSDIR . '/') )
	{
		require WA_ROOTDIR . '/includes/class.attach.php';
		
		$old_stats = array();
		
		while( $filename = @readdir($res) )
		{
			if( preg_match("/^([0-9]{4}_[a-zA-Z]+)_list$liste_from\.txt$/i", $filename, $match) )
			{
				if( $liste_to && $fp = @fopen(WA_STATSDIR . '/' . $filename, 'r') )
				{
					$old_stats[$match[1]] = clean_stats(fread($fp, filesize(WA_STATSDIR . '/' . $filename)));
					fclose($fp);
				}
				
				Attach::remove_file(WA_STATSDIR . '/' . $filename);
			}
		}
		closedir($res);
		
		if( $liste_to && count($old_stats) )
		{
			foreach( $old_stats AS $date => $stats_from )
			{
				$filename = $date . '_list' . $liste_to . '.txt';
				
				if( $fp = @fopen(WA_STATSDIR . '/' . $filename, 'r') )
				{
					$stats_to = clean_stats(fread($fp, filesize(WA_STATSDIR . '/' . $filename)));
					fclose($fp);
					
					for( $i = 0; $i < count($stats_to); $i++ )
					{
						$stats_to[$i] += $stats_from[$i];
					}
					
					@chmod(WA_STATSDIR . '/' . $filename, 0666);
					if( $fw = @fopen(WA_STATSDIR . '/' . $filename, 'w') )
					{
						fwrite($fw, implode("\n", $stats_to));
						fclose($fw);
					}
				}
			}
		}
		
		return true;
	}
	
	return false;
}

/**
 * clean_stats()
 * 
 * Effectue les traitements adéquats sur la chaine et retourne un tableau
 * 
 * @param string $contents  Contenu du fichier des statistiques
 * 
 * @return array
 */
function clean_stats($contents)
{
	$contents = preg_replace("/\r\n?/", "\n", trim($contents));
	
	return array_map('intval', explode("\n", $contents));
}

?>