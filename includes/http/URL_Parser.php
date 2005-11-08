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

if( !defined('URL_PARSER_INC') ) {

define('URL_PARSER_INC', true);

class URL_Parser {
	
	var $scheme       = '';
	var $user         = '';
	var $pass         = '';
	var $host         = '';
	var $port         = '';
	var $path         = '/';
	var $query        = '';
	var $fragment     = '';
	
	var $passIRI      = false;
	var $isRelative   = false;
	
	function URL_Parser($url = null)
	{
		if( !preg_match('/^[\w\d]+:(\/\/)?/', $url) )
		{
			$this->scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ) ? 'https' : 'http';
			
			if( !empty($_SERVER['HTTP_HOST']) && preg_match('/^(.+?)(?:\:(\d+))?$/', $_SERVER['HTTP_HOST'], $match) )
			{
				$this->host = $match[1];
				$this->port = (!empty($match[2])) ? $match[2] : $this->getDefaultPort($this->scheme);
			}
			else
			{
				$this->host = $_SERVER['SERVER_NAME'];
				$this->port = (!empty($_SERVER['SERVER_PORT'])) ? $_SERVER['SERVER_PORT'] : $this->getDefaultPort($this->_scheme);
			}
			
			if( !empty($_SERVER['REQUEST_URI']) )
			{
				if( strpos($_SERVER['REQUEST_URI'], '?') )
				{
					list($path, $queryString) = explode('?', $_SERVER['REQUEST_URI']);
					$this->path  = $path;
					$this->query = $queryString;
				}
				else
				{
					$this->path  = $_SERVER['REQUEST_URI'];
				}
			}
			
			$this->isRelative = true;
		}
		
		if( !empty($url) )
		{
			$urlinfo = parse_url($url);
			
			foreach( $urlinfo AS $key => $value )
			{
				switch( $key )
				{
					case 'scheme':
						$this->scheme = $value;
						$this->port   = $this->getDefaultPort($value);
						break;
					
					case 'user':
					case 'pass':
					case 'host':
					case 'port':
					case 'fragment':
						eval("\$this->{$key} = \$this->passToURI(\$value);");
						break;
					
					case 'path':
						if( substr($value, 0, 1) == '/' )
						{
							$this->path = $value;
						}
						else
						{
							$this->path = str_replace('\\', '/', dirname($this->path)) . '/' . $value;
						}
						$this->path = $this->passToURI($this->resolvePath($this->path));
						break;
					
					case 'query':
						$this->query = $this->passToURI($value);
						break;
				}
			}
		}
		
		if( empty($this->path) )
		{
			$this->path = '/';
		}
	}
	
	function __toString()
	{
		$pass = ( $this->passIRI == true ) ? 'passToIRI' : 'passToURI';
		
		return $this->scheme . '://'
			. $this->{$pass}($this->user) . (!empty($this->user) ? ( (!empty($this->pass)) ? ':' . $this->{$pass}($this->pass) : '' ) . '@' : '')
			. $this->{$pass}($this->host)
			. (($this->port != $this->getDefaultPort($this->scheme)) ? ':' . $this->port : '')
			. $this->{$pass}($this->path)
			. (!empty($this->query) ? '?' : '') . $this->{$pass}($this->query)
			. (!empty($this->fragment) ? '#' : '') . $this->{$pass}($this->fragment);
	}
	
	function passToURI($str)
	{
		return preg_replace('/([\x7f-\xff])/ie', '\'%\' . strtoupper(dechex(ord(\'\\1\')))', $str);
	}
	
	function passToIRI($str)
	{
		return preg_replace('/%(7f|[a-f89][a-f0-9])/ie', 'chr(hexdec(\'\\1\'))', $str);
	}
	
	function addParameter($name, $value)
	{
		if( empty($this->query) )
		{
			$this->query  = $name . '=' . $value;
		}
		else
		{
			$this->query .= '&' . $name . '=' . $value;
		}
	}
	
	function removeParameter($name)
	{
		
	}
	
	function resolvePath($path)
	{
		$path = preg_replace('/\/{2,}/', '/', $path);
		$path = preg_replace('/(?<=\/)\.\//', '', $path);
		
		$path = explode('/', $path);
		for( $i = 0, $m = count($path); $i < $m; $i++ )
		{
			if( !isset($path[$i]) || $path[$i] != '..' )
			{
				continue;
			}
			
			unset($path[$i--]);
			if( isset($path[$i]) && $i > 0 )
			{
				unset($path[$i--]);
			}
			
			$path = array_values($path);
		}
		
		return implode('/', $path);
	}
	
	function getDefaultPort($scheme)
	{
		switch( strtolower($scheme) )
		{
			case 'http':  return 80;
			case 'https': return 443;
			case 'ftp':   return 21;
			case 'imap':  return 143;
			case 'imaps': return 993;
			case 'pop3':  return 110;
			case 'pop3s': return 995;
			default:      return null;
		}
	}
}

}
?>
