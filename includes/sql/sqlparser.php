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

if( !defined('SQLPARSER_INC') ) {

define('SQLPARSER_INC', true);

/**
 * Parse un fichier contenant une liste de requète et 
 * renvoie un tableau avec une requète par entrée
 * 
 * @param string $input    Contenu du fichier .sql
 * @param string $prefixe  Préfixe des tables à mettre à la place du prefixe par défaut
 * 
 * @return array
 */
function parseSQL($input, $prefixe = '')
{
	$tmp            = '';
	$output         = array();
	$in_comments    = false;
	$between_quotes = false;
	
	$lines       = preg_split("/(\r\n?|\n)/", $input, -1, PREG_SPLIT_DELIM_CAPTURE);
	$total_lines = count($lines);
	
	for( $i = 0; $i < $total_lines; $i++ ) {
		if( preg_match("/^\r\n?|\n$/", $lines[$i]) ) {
			if( $between_quotes ) {
				$tmp .= $lines[$i];
			}
			else {
				$tmp .= ' ';
			}
			
			continue;
		}
		
		//
		// Si on est pas dans des simples quotes, on vérifie si on entre ds des commentaires
		//
		if( !$between_quotes && !$in_comments && preg_match('/^\/\*/', $lines[$i]) ) {
			$in_comments = true;
		}
		
		if( $between_quotes || ( !$in_comments && strlen($lines[$i]) > 0 && $lines[$i][0] != '#'
			&& !preg_match('/^--\x20/', $lines[$i]) ) )
		{
			//
			// Nombre de simple quotes non échappés
			//
			$unescaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*'/", $lines[$i], $matches);
			
			if( ( !$between_quotes && !($unescaped_quotes % 2) ) || ( $between_quotes && ($unescaped_quotes % 2) ) ) {
				if( preg_match('/;\s*$/i', $lines[$i]) ) {
					$lines[$i] = ( $tmp != '' ) ? rtrim($lines[$i]) : trim($lines[$i]);
					
					if( $lines[$i] == 'END;' ) {// cas particulier des CREATE TRIGGER pour Firebird
						$output[count($output)-1] .= $tmp . '; END';
					}
					else {
						$output[] = $tmp . substr($lines[$i], 0, -1);
					}
					
					$tmp = '';
				}
				else {
					$tmp .= ( $tmp != '' ) ? $lines[$i] : ltrim($lines[$i]);
				}
				
				$between_quotes = false;
			}
			else {
				$between_quotes = true;
				$tmp .= ( $tmp != '' ) ? $lines[$i] : ltrim($lines[$i]);
			}
		}
		
		if( !$between_quotes && $in_comments && preg_match('/\*\/$/', rtrim($lines[$i])) ) {
			$in_comments = false;
		}
		
		//
		// Pour tenter de ménager la mémoire 
		//
		unset($lines[$i]);
	}
	
	if( $prefixe != '' ) {
		$output = str_replace('wa_', $prefixe, $output);
	}
	
	//
	// Pour tenter de ménager la mémoire
	//
	unset($input, $lines);
	
	return $output;
}

}
?>
