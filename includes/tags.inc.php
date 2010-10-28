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

if( !defined('IN_NEWSLETTER') )
{
    exit('<b>No hacking</b>');
}

$other_tags = array();
$t = 0;

//
// Placez ici vos tags personnalisés
//
// - column_name doit contenir le nom de la colonne concernée dans la table prefixe_abonnes
// - tag_name peut contenir le nom du tag à remplacer dans les newsletters lors des envois
// - field_name peut contenir le nom du champ de formulaire à réceptionner lors des inscriptions
//   ou des modifications de compte (si field_name n'est pas renseigné, le script utilisera
//   la valeur de 'column_name')
//
// LINKS, NAME, WA_EMAIL et WA_CODE sont des noms de tag réservés
//

//$other_tags[$t]['column_name'] = '';
//$other_tags[$t]['tag_name']    = '';
//$other_tags[$t]['field_name']  = '';
//$t++;

//$other_tags[$t]['column_name'] = '';
//$other_tags[$t]['tag_name']    = '';
//$other_tags[$t]['field_name']  = '';
//$t++;

// etc... Reproduisez les trois lignes si nécessaires.

?>