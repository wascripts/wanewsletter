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


define('WA_ROOTDIR', '..');

$language_dir = WA_ROOTDIR . '/language';

$Fichier_1 = 'lang_francais.php';
$Fichier_2 = '../../branche_2.2/language/lang_francais.php';

function diff_lang($tab_1, $tab_2)
{
    $new_tab = array();
    
    foreach( $tab_1 AS $varname => $varval )
    {
        if( is_array($varval) )
        {
            $new_tab[$varname] = diff_lang($tab_1[$varname], $tab_2[$varname]);
            
            if( count($new_tab[$varname]) == 0 )
            {
                unset($new_tab[$varname]);
            }
        }
        else if( !isset($tab_2[$varname]) )
        {
            $new_tab[$varname] = htmlspecialchars(addcslashes($tab_1[$varname], "\x0A\x0D"));
        }
		
		//
		// Temporaire, même langue mais branche différente only
		//
		else if( strcmp($varval, $tab_2[$varname]) !== 0 )
		{
			$new_tab[$varname] = htmlspecialchars(addcslashes($tab_1[$varname], "\x0A\x0D"));
		}
    }
    
    return $new_tab;
}

$lang = array();
include $language_dir . '/' . $Fichier_1;

$lang_ary_1 = $lang;
unset($lang);

$lang = array();
include $language_dir . '/' . $Fichier_2;

$lang_ary_2 = $lang;
unset($lang);

$diff_lang = diff_lang($lang_ary_1, $lang_ary_2);

if( count($diff_lang) > 0 )
{
    echo '<h1 style="font-size: 1.1em;">Index manquants ou changements : ' . $Fichier_2 . ' =&gt; ' . $Fichier_1 . '</h1>';
    echo '<pre>';
    print_r($diff_lang);
    echo '</pre>';
}
else
{
    echo '<p>Aucun index manquant</p>';
}

exit(0);

?>