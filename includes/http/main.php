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

if( !defined('HTTP_MAIN_INC') ) {

	define('HTTP_MAIN_INC', true);
	
	//
	// Codes d?information
	//
	define('HTTP_STATUS_CONTINUE',                      100);
	define('HTTP_STATUS_SWITCHING_PROTOCOLS',           101);
	
	//
	// Codes de succés
	//
	define('HTTP_STATUS_OK',                            200);
	define('HTTP_STATUS_CREATED',                       201);
	define('HTTP_STATUS_ACCEPTED',                      202);
	define('HTTP_STATUS_NON_AUTHORITATIVE_INFORMATION', 203);
	define('HTTP_STATUS_NO_CONTENT',                    204);
	define('HTTP_STATUS_RESET_CONTENT',                 205);
	define('HTTP_STATUS_PARTIAL_CONTENT',               206);
	
	//
	// Codes de redirection
	//
	define('HTTP_STATUS_MULTIPLE_CHOICES',              300);
	define('HTTP_STATUS_MOVED_PERMANENTLY',             301);
	define('HTTP_STATUS_FOUND',                         302);
	define('HTTP_STATUS_SEE_OTHER',                     303);
	define('HTTP_STATUS_NOT_MODIFIED',                  304);
	define('HTTP_STATUS_USE_PROXY',                     305);
	define('HTTP_STATUS_TEMPORARY_REDIRECT',            307);
	
	//
	// Codes d?erreurs client
	//
	define('HTTP_STATUS_BAD_REQUEST',                   400);
	define('HTTP_STATUS_UNAUTHORIZED',                  401);
	define('HTTP_STATUS_PAYMENT_REQUIRED',              402);
	define('HTTP_STATUS_FORBIDDEN',                     403);
	define('HTTP_STATUS_NOT_FOUND',                     404);
	define('HTTP_STATUS_METHOD_NOT_ALLOWED',            405);
	define('HTTP_STATUS_NOT_ACCEPTABLE',                406);
	define('HTTP_STATUS_PROXY_AUTHENTICATION_REQUIRED', 407);
	define('HTTP_STATUS_REQUEST_TIMEOUT',               408);
	define('HTTP_STATUS_CONFLICT',                      409);
	define('HTTP_STATUS_GONE',                          410);
	define('HTTP_STATUS_LENGTH_REQUIRED',               411);
	define('HTTP_STATUS_PRECONDITION_FAILED',           412);
	define('HTTP_STATUS_REQUEST_ENTITY_TOO_LARGE',      413);
	define('HTTP_STATUS_REQUEST_URI_TOO_LONG',          414);
	define('HTTP_STATUS_UNSUPPORTED_MEDIA_TYPE',        415);
	define('HTTP_STATUS_REQUESTED_RANGE_NOT_SATISFIABLE', 416);
	define('HTTP_STATUS_EXPECTATION_FAILED',            417);
	
	//
	// Codes d?erreurs serveur
	//
	define('HTTP_STATUS_INTERNAL_SERVER_ERROR',         500);
	define('HTTP_STATUS_NOT_IMPLEMENTED',               501);
	define('HTTP_STATUS_BAD_GATEWAY',                   502);
	define('HTTP_STATUS_SERVICE_UNAVAILABLE',           503);
	define('HTTP_STATUS_GATEWAY_TIMEOUT',               504);
	define('HTTP_STATUS_HTTP_VERSION_NOT_SUPPORTED',    505);
	
	define('HTTP_HEADER_MAX_LENGTH', 78);
	
class HTTP_Main {
	
	/**
	 * Intitulé des différents codes de retour HTTP
	 * 
	 * @var array
	 * @access public
	 */
	var $statusLabels = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported'
	);
	
	/**
	 * Version de HTTP
	 * 
	 * @var float
	 * @access private
	 */
	var $httpVersion;
	
	/**
	 * Méthode HTTP
	 * 
	 * @var string
	 * @access private
	 */
	var $requestMethod;
	
	/**
	 * Chaîne de déboguage
	 * 
	 * @var string
	 * @access private
	 */
	var $debugText = '';
	
	/**
	 * Active/Désactive le mode de déboguage
	 * 
	 * @var boolean
	 * @access public
	 */
	var $debugMode = false;
	
	/**
	 * Chemin vers le fichier de log
	 * 
	 * @var string
	 * @access public
	 */
	var $log_filename = '/var/log/http_class.log';
	
	/**
	 * Renvoie une chaîne de date conforme à la spécification HTTP
	 * 
	 * @param integer $ts
	 * 
	 * @access public
	 */
	function date($ts = null)
	{
		if( !isset($ts) )
		{
			$ts = time();
		}
		else if( !is_numeric($ts) )
		{
			$ts = strtotime($ts);
			if( $ts == -1 )
			{
				
			}
		}
		
		return gmdate('D, d M Y H:i:s', $ts) . ' GMT';
	}
	
	/**
	 * Vérifie la validité d'un nom d'en-tête HTTP
	 * 
	 * @param string $name
	 * 
	 * @access public
	 */
	function checkHeaderName($name)
	{
		return preg_match('/^[\x21-\x39\x3B-\x7E]+$/', $name);
	}
	
	/**
	 * Vérifie la validité du corps d'un en-tête HTTP
	 * 
	 * @param string $value
	 * 
	 * @access public
	 */
	function checkHeaderBody($value)
	{
		return !preg_match('/\n(?![\t\x20])/', $value);
	}
	
	/**
	 * Vérifie la validité du corps d'un en-tête HTTP
	 * 
	 * @param string $name      Nom de l'en-tête
	 * @param string $value     Valeur de l'en-tête
	 * @param string $override  Surcharger ou non l'en-tête de nom $name
	 * @param string $headers   Le tableau d'en-têtes à remplir
	 * 
	 * @access public
	 */
	function setHeader($name, $value, $override, &$headers)
	{
		if( !isset($value) )
		{
			$value = '';
		}
		
		$name  = implode('-', array_map('ucfirst', explode('-', trim($name))));
		$value = trim($value);
		
		if( HTTP_Main::checkHeaderName($name) && HTTP_Main::checkHeaderBody($value) )
		{
/*			if( (strlen($name) + strlen($value) + 2) > HTTP_Main::HEADER_MAX_LENGTH && preg_match('/[\t\x20]/', $value) )
			{
				$value = wordwrap($value, (HTTP_Main::HEADER_MAX_LENGTH - strlen($name) + 2));
			}*/
			
			$prevValue = (isset($headers[$name])) ? $headers[$name] : '';
			
			if( isset($headers[$name]) && !$override )
			{
				$headers[$name] .= ', ' . $value;
			}
			else
			{
				$headers[$name] = $value;
			}
			
			return $prevValue;
		}
	}
	
	function debug($str = '')
	{
		if( $this->debugMode )
		{
			if( $str != '' )
			{
				$this->debugText .= $str;
			}
			else if( $fp = @fopen($this->log_filename, 'w') )
			{
				fwrite($fp, $this->debugText);
				fclose($fp);
			}
		}
	}
}

}
?>
