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

if( !defined('FUNCTIONS_VALIDATE_INC') ) {

define('FUNCTIONS_VALIDATE_INC', true);

/**
 * validate_pseudo()
 * 
 * @param string $pseudo
 * 
 * @return boolean
 */
function validate_pseudo($pseudo)
{
	return ( strlen($pseudo) >= 2 && strlen($pseudo) <= 30 );
}

/**
 * validate_pass()
 * 
 * @param string $passwd
 * 
 * @return boolean
 */
function validate_pass($passwd)
{
	return preg_match('/^[\x21-\x7E]{4,32}$/', $passwd);
}

/**
 * validate_lang()
 * 
 * @param string $language
 * 
 * @return boolean
 */
function validate_lang($language)
{
	return preg_match('/^[\w_-]+$/', $language) && file_exists(WA_ROOTDIR . '/language/lang_' . $language . '.php');
}

}
?>