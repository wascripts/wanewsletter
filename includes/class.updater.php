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
 */

if( !defined('CLASS_UPDATER_INC') ) {

define('CLASS_UPDATER_INC', true);

class Wa_Updater {
	
	var $cache    = '';
	var $cacheTtl = 0;
	var $url      = '';
	
	function check($complete = false)
	{
		$result = false;
		$data   = '';
		
		if( is_readable($this->cache) && filemtime($this->cache) > (time() - $this->cacheTtl) )
		{
			$data = file_get_contents($this->cache);
		}
		else if( $complete )
		{
			$data = file_get_contents($this->url);
			
			if( $data !== false )
			{
				file_put_contents($this->cache, $data);
			}
		}
		
		if( $data != '' )
		{
			$result = intval(version_compare(WA_VERSION, trim($data), '<'));
		}
		
		return $result;
	}
}

}
