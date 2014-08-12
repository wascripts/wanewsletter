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
 * @version $Id$
 * 
 * Affiche les entrées présentes dans le premier fichier de language 
 * qui ne sont pas présentes dans le deuxième fichier de language.
 */

//
// Ceci est un fichier de test ou d'aide lors du développement. 
// Commentez les lignes suivantes uniquement si vous êtes sùr de ce que vous faites !
//
echo "This script has been disabled for security reasons\n";
exit(0);

define('WA_LANGUAGE_DIR', '../language');

$FICHIER_REFERENCE = 'lang_francais.php';
$FICHIER_A_TESTER  = 'lang_english.php';

ini_set('default_mimetype', 'text/plain');

function diff_lang($tab_1, $tab_2, $namespace = '')
{
    global $FICHIER_REFERENCE, $FICHIER_A_TESTER;
    
    foreach( $tab_1 as $varname => $varval )
    {
        if( is_array($varval) )
        {
            diff_lang($tab_1[$varname], $tab_2[$varname], $namespace.'.'.$varname);
        }
        else if( !isset($tab_2[$varname]) )
        {
        	printf("%s => index non présent\n", $namespace.'.'.$varname);
        }
        else
		{
			$a = preg_match_all('#%(?!%)#', $tab_1[$varname], $m);
			$b = preg_match_all('#%(?!%)#', $tab_2[$varname], $m);
			
			if( $a != $b )
			{
				printf("%s => Nombre de paramètres de formatage différent\n", $namespace.'.'.$varname);
				printf("%s : \"%s\"\n", $FICHIER_REFERENCE, addcslashes($tab_1[$varname], "\x0A\x0D\x22\x24"));
				printf("%s : \"%s\"\n", $FICHIER_A_TESTER, addcslashes($tab_2[$varname], "\x0A\x0D\x22\x24"));
			}
		}
    }
}

$lang = array();
require WA_LANGUAGE_DIR . '/' . $FICHIER_REFERENCE;

$lang_ary_1 = $lang;
unset($lang);

$lang = array();
require WA_LANGUAGE_DIR . '/' . $FICHIER_A_TESTER;

$lang_ary_2 = $lang;
unset($lang);

diff_lang($lang_ary_1, $lang_ary_2);

exit(0);
