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

if( !defined('HTTP_CLIENT_INC') ) {

define('HTTP_CLIENT_INC', true);

require WA_ROOTDIR . '/includes/http/main.php';
require WA_ROOTDIR . '/includes/http/URL_Parser.php';

class HTTP_Client extends HTTP_Main {
	
	/**
	 * Durée en seconde avant timeout
	 * 
	 * @var integer
	 * @access public
	 */
	var $timeout         = 5;
	
	/**
	 * Suivre ou non les éventuelles redirections HTTP
	 * 
	 * @var boolean
	 * @access public
	 */
	var $followRedirects = true;
	
	/**
	 * Nombre maximum de redirections HTTP à suivre successivement avant d'abandonner
	 * Si cet attribut est positionné à 0, il n'y a pas de limitation au nombre de
	 * redirections suivies
	 * 
	 * @var integer
	 * @access public
	 */
	var $maxRedirects    = 10;
	
	/**
	 * Décoder automatiquement si contenu reçu par morceau (chunked) ou compressé (gzippé)
	 * 
	 * @var boolean
	 * @access public
	 */
	var $autoDecodeData  = true;
	
	/**
	 * Nombre d'octet maximum à récupérer (0 == pas de limite)
	 * 
	 * @var integer
	 * @access public
	 */
	var $byteLimit       = 0;
	
	/**
	 * Le code de la réponse HTTP
	 * 
	 * @var integer
	 * @access public
	 */
	var $responseCode;
	
	/**
	 * Le libellé de la réponse HTTP
	 * 
	 * @var integer
	 * @access public
	 */
	var $responseText;
	
	/**
	 * En-têtes HTTP
	 * 
	 * @var string
	 * @access private
	 */
	var $responseHeaders;
	
	/**
	 * Corps de la réponse HTTP
	 * 
	 * @var string
	 * @access public
	 */
	var $responseData;
	
	/**
	 * @var string
	 * @access public
	 */
	var $url;
	
	/**
	 * Nombre de redirections suivies
	 * 
	 * @var integer
	 * @access private
	 */
	var $_numRedirects   = 0;
	
	/**
	 * En-têtes HTTP de la requète
	 * 
	 * @var array
	 * @access private
	 */
	var $_requestHeaders = array(
		'Host'       => '',
		'User-Agent' => 'HTTP_Client PHP class',
		'Accept'     => '*/*'
	);
	
	/**
	 * Fournit l'URL à appeller et la méthode HTTP à utiliser
	 * 
	 * @param string $requestMethod  Méthode HTTP (peut être GET, POST ou HEAD sans sensibilité à la casse) 
	 * @param string $url
	 * 
	 * @access public
	 * @return void
	 */
	function openURL($requestMethod, $url)
	{
		$requestMethod = strtoupper($requestMethod);
		if( !in_array($requestMethod, array('HEAD', 'GET', 'POST')) )
		{
			//trigger_error("$requestMethod method are not allowed", E_USER_ERROR);
			return false;
		}
		
		$this->requestMethod = $requestMethod;
		
		if( !is_object($url) )
		{
			$this->url =& new URL_Parser($url);
		}
		else
		{
			$this->url = $url;
		}
		$this->setRequestHeader('Host', $this->url->host);
	}
	
	/**
	 * Ajoute un en-tête HTTP pour la requète à effectuer
	 * 
	 * @param string  $name      Nom de l'en-tête
	 * @param string  $value     Valeur de l'en-tête
	 * @param boolean $override  Écraser la valeur précédente si présente
	 * 
	 * @access public
	 * @return string
	 */
	function setRequestHeader($name, $value, $override = true)
	{
		return HTTP_Main::setHeader($name, $value, $override, $this->_requestHeaders);
	}
	
	/**
	 * Renvoie la valeur de l'en-tête de nom donné
	 * 
	 * @param string $name  Nom de l'en-tête
	 * 
	 * @access public
	 * @return string
	 */
	function getResponseHeader($name)
	{
		if( preg_match('/^' .  preg_quote($name, '/') . '\x20*:\x20*(.+)/mi', $this->responseHeaders, $match) )
		{
			return trim($match[1]);
		}
		
		return '';
	}
	
	/**
	 * Effectue la requète HTTP à destination de l'URL fournie avec HTTP_Client::openURL()
	 * 
	 * @param mixed  $postdata  Données à envoyer (si méthode POST utilisée)
	 * @param string $charset   Jeu de caractère/Encodage des données
	 * 
	 * @access public
	 * @return boolean
	 */
	function send($postdata = '', $charset = '')
	{
		if( empty($this->requestMethod) || empty($this->url) )
		{
			//trigger_error('No method and/or URL given', E_USER_WARNING);
			return false;
		}
		
		$this->responseCode    = null;
		$this->responseText    = null;
		$this->responseHeaders = '';
		$this->responseData    = '';
		
		if( $this->requestMethod == 'POST' && !empty($postdata) )
		{
			if( is_array($postdata) )
			{
				$tmp = '';
				foreach( $postdata AS $name => $value )
				{
					$tmp .= '&' . $name . '=' . rawurlencode($value);
				}
				$postdata = ltrim($tmp, '&');
				unset($tmp);
			}
			
			if( !empty($charset) )
			{
				$charset = '; charset=' . $charset;
			}
			
			$this->setRequestHeader('Content-Type', 'application/x-www-form-urlencoded' . $charset);
			$this->setRequestHeader('Content-Length', strlen($postdata));
		}
		else
		{
			$this->setRequestHeader('Content-Type', null);
			$this->setRequestHeader('Content-Length', null);
		}
		
		if( !($fs = fsockopen($this->url->host, $this->url->port, $errno, $errstr, $this->timeout)) )
		{
/*			switch( $errno )
			{
				case -3:
					$this->errormsg = 'Socket creation failed (-3)';
				case -4:
					$this->errormsg = 'DNS lookup failure (-4)';
				case -5:
					$this->errormsg = 'Connection refused or timed out (-5)';
				default:
					$this->errormsg = 'Connection failed ('.$errno.')';
				$this->errormsg .= ' '.$errstr;
				$this->debug($this->errormsg);
			}*/
			//trigger_error($errno . ': ' . $errstr, E_USER_WARNING);
			return false;
		}
		
		$this->debug('Connect to ' . $this->url->host . "...\r\n\r\n");
		$path = $this->url->path . (($this->url->query != '') ? '?' . $this->url->query : '');
		
		$this->write($fs, "$this->requestMethod $path HTTP/1.1\r\n");
		foreach( $this->_requestHeaders AS $name => $value )
		{
			if( !empty($value) )
			{
				$this->write($fs, $name . ': ' . $value . "\r\n");
			}
		}
		$this->write($fs, "Connection: close\r\n");
		$this->write($fs, "\r\n" . $postdata);
		
		//
		// Réception des en-têtes de réponse
		//
		$headers = $tmp = '';
		$contentChunked = false;
		$contentGziped  = false;
		
		do
		{
			$tmp = fgets($fs, 1024);
			
			if( $tmp == "\r\n" )
			{
				break;
			}
			
			if( !isset($this->responseCode) )
			{
				if( preg_match('#^HTTP/(\d\.[x\d])\x20+(\d{3})\x20+(.+)\s#', $tmp, $match) )
				{
					$this->responseCode = intval($match[2]);
					$this->responseText = rtrim($match[3]);
				}
				else
				{
					//trigger_error('Malformed response', E_USER_WARNING);
					return false;
				}
			}
			else
			{
				if( strpos($tmp, ':') != false )
				{
					$header = strtolower(substr($tmp, 0, strpos($tmp, ':')));
					$value  = trim(substr($tmp, strpos($tmp, ':') + 1));
					$this->responseHeaders .= $tmp;
					
					if( $header == 'transfer-encoding' && strtolower($value) == 'chunked' )
					{
						$contentChunked = true;
					}
					else if( $header == 'content-encoding' && strtolower($value) == 'gzip' )
					{
						$contentGziped = true;
					}
				}
				else
				{
					//trigger_error('Malformed header', E_USER_WARNING);
					return false;
				}
			}
			
			$headers .= $tmp;
		}
		while( !feof($fs) );
		
		$this->responseHeaders = preg_replace("/\n(?=[\t\x20])/", '', $this->responseHeaders);
		$this->debug($headers . "\r\n");
		
		$redirectStatus = array(
			HTTP_STATUS_MULTIPLE_CHOICES,
			HTTP_STATUS_MOVED_PERMANENTLY,
			HTTP_STATUS_FOUND,
			HTTP_STATUS_SEE_OTHER,
			HTTP_STATUS_TEMPORARY_REDIRECT
		);
		if( $this->followRedirects == true && in_array($this->responseCode, $redirectStatus) )
		{
			$this->_numRedirects++;
			
			if( $this->maxRedirects > 0 && $this->_numRedirects > $this->maxRedirects )
			{
				//trigger_error('Too many redirections', E_USER_WARNING);
				return false;
			}
			
			if( $this->requestMethod == 'HEAD' && $this->responseCode == HTTP_STATUS_SEE_OTHER )
			{
				$this->requestMethod = 'GET';
			}
			else if( $this->requestMethod == 'POST' && ( $this->responseCode == HTTP_STATUS_FOUND || $this->responseCode == HTTP_STATUS_SEE_OTHER ) )
			{
				$this->requestMethod = 'GET';
			}
			
			$location = $this->getResponseHeader('Location');
			if( $location != '' )
			{
				if( !preg_match('/^https?:\/\//', $location) )
				{
					$query = '';
					if( strpos($location, '?') )
					{
						list($location, $query) = explode('?', $location);
					}
					
					if( substr($location, 0, 1) == '/' )
					{
						$path = $location;
					}
					else if( substr($this->url->path, -1) == '/' )
					{
						$path = $this->url->path . $location;
					}
					else
					{
						$path = str_replace('\\', '/', dirname($this->url->path)) . '/' . $location;
					}
					
					$this->url->path  = URL_Parser::resolvePath($path);
					$this->url->query = $query;
					
					$location = $this->url;
				}
				
				$this->openURL($this->requestMethod, $location);
				$this->send($postdata);
			}
		}
		else
		{
			$this->_numRedirects = 0;
			
			if( $this->requestMethod != 'HEAD' )
			{
				$data = '';
				$chunklen = 0;
				
				while( !feof($fs) )
				{
					$tmp = fgets($fs, 1024);
					
					if( $this->autoDecodeData && $contentChunked == true )
					{
						if( $chunklen == 0 )
						{
							if( !preg_match('/^([a-f0-9]+)\s*$/mi', $tmp, $match) )
							{
								$data .= $tmp;
								$contentChunked = false;
								
								continue;
							}
							
							if( ($chunklen = hexdec($match[1])) == 0 )
							{
								break;
							}
						}
						else
						{
							$chunklen -= strlen($tmp);
							
							if( $chunklen < 0 )
							{
								$data .= substr($tmp, 0, $chunklen);
								$chunklen = 0;
							}
							else
							{
								$data .= $tmp;
								if( $chunklen == 0 )
									fgets($fs, 1024);// On bouffe le prochain CRLF
								
								if( $this->byteLimit > 0 && strlen($data) > $this->byteLimit )
								{
									break;
								}
							}
						}
					}
					else
					{
						$data .= $tmp;
						
						if( $contentGziped == false && $this->byteLimit > 0 && strlen($data) > $this->byteLimit )
						{
							break;
						}
					}
				}
				fclose($fs);
				$this->debug($data);
				
				if( $this->autoDecodeData == true && $contentGziped == true && !empty($data) )
				{
					// RFC 1952 - Users note on http://www.php.net/manual/en/function.gzencode.php
					if( strncmp($data, "\x1f\x8b", 2) != 0 )
					{
						//trigger_error('data is not to GZIP format', E_USER_WARNING);
						return false;
					}
					
					$data = gzinflate(substr($data, 10));
				}
				
				$this->responseData = $data;
			}
		}
		
		$this->debug();
		
		return true;
	}
	
	/**
	 * Envoie des données
	 * 
	 * @param resource $fs   La connexion ouverte à destination du serveur HTTP
	 * @param string   $str  Données à envoyer
	 * 
	 * @access private
	 * @return void
	 */
	function write($fs, $str)
	{
		@fputs($fs, $str);
		$this->debug($str);
	}
}

}
?>
