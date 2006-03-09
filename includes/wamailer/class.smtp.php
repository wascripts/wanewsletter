<?php
/**
 * Copyright (c) 2002-2006 Aurélien Maille
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 * 
 * @package Wamailer
 * @author  Bobe <wascripts@phpcodeur.net>
 * @link    http://phpcodeur.net/wascripts/wamailer/
 * @license http://www.gnu.org/copyleft/lesser.html  GNU Lesser General Public License
 * @version 2.4
 */

if( !defined('CLASS_SMTP_INC') )
{

define('CLASS_SMTP_INC', true);

/**
 * Classe de connexion et d'envois d'emails via un serveur SMTP
 * 
 * Les sources qui m'ont bien aidées :
 * 
 * @link http://www.rfc-editor.org/
 * @link http://strasbourg.ort.asso.fr/examen2000/np.html#SMTP
 * @link http://www.commentcamarche.net/internet/smtp.php3
 * @link http://abcdrfc.free.fr/
 * @link http://www.interpc.fr/mapage/billaud/telmail.htm
 * 
 * Toutes les commandes de connexion et de dialogue avec le serveur sont
 * détaillées dans la RFC 821.
 * 
 * Les commandes d'authentification au serveur sont détaillées dans la RFC 2554
 * 
 * @link http://abcdrfc.free.fr/rfc-vf/rfc821.html (français)
 * @link http://www.rfc-editor.org/rfc/rfc821.txt (anglais)
 * @link http://www.rfc-editor.org/rfc/rfc2554.txt (anglais)
 * 
 * @access public
 */
class Smtp {
	
	/**
	 * Identifiant de connexion
	 * 
	 * @var array
	 * @access private
	 */
	var $connect_id      = NULL;
	
	/**
	 * Nom ou IP du serveur smtp à contacter
	 * 
	 * @var string
	 * @access public
	 */
	var $smtp_server     = '';
	
	/**
	 * Port d'accés (en général, 25)
	 * 
	 * @var integer
	 * @access public
	 */
	var $smtp_port       = 25;
	
	/**
	 * login pour la connexion (seulement si nécessaire)
	 * 
	 * @var string
	 * @access public
	 */
	var $smtp_user       = '';
	
	/**
	 * password pour la connexion (seulement si nécessaire)
	 * 
	 * @var string
	 * @access public
	 */
	var $smtp_pass       = '';
	
	/**
	 * Nom du serveur émetteur
	 * 
	 * @var string
	 * @access public
	 */
	var $server_from     = 'localhost';
	
	/**
	 * Dernière réponse envoyée par le serveur
	 * 
	 * @var string
	 * @access private
	 */
	var $reponse         = '';
	
	/**
	 * Code de la dernière réponse
	 * 
	 * @var string
	 * @access private
	 */
	var $code            = '';
	
	/**
	 * Durée maximale d'une tentative de connexion
	 * 
	 * @var string
	 * @access public
	 */
	var $timeout         = 3;
	
	/**
	 * Variable contenant les divers messages
	 * 
	 * @var string
	 * @access public
	 */
	var $log             = '';
	
	/**
	 * Variable contenant le dernier message d'erreur
	 * 
	 * @var string
	 * @access public
	 */
	var $msg_error       = '';
	
	/**
	 * Debug mode activé/désactivé. 
	 * Si activé, le dialogue avec le serveur s'affiche à l'écran, une éventuelle erreur stoppe le script
	 * 
	 * @var boolean
	 * @access public
	 */
	var $debug           = FALSE;
	
	/**
	 * Sauvegarde du log du dialogue avec le serveur smtp dans un fichier texte. 
	 * 
	 * @var boolean
	 * @access public
	 */
	var $save_log        = FALSE;
	
	/**
	 * Ecraser les données présentes dans le fichier log si celui ci est présent
	 * 
	 * @var boolean
	 * @access public
	 */
	var $erase_log       = FALSE;
	
	/**
	 * Chemin de stockage du fichier log
	 * 
	 * @var string
	 * @access public
	 */
	var $filelog         = './log_smtp.txt';
	
	/**
	 * Si l'argument vaut TRUE, la connexion est établie automatiquement avec les paramètres par défaut 
	 * de la classe. (On suppose qu'ils ont été préalablement remplacés par les bons paramètres)
	 * 
	 * @param boolean $auto_connect  TRUE pour établir la connexion à l'instanciation de la classe
	 * 
	 * @return void
	 */
	function Smtp($auto_connect = false)
	{
		if( $auto_connect )
		{
			$this->connect($this->smtp_server, $this->smtp_port, $this->smtp_user, $this->smtp_pass, $this->server_from);
		}
	}
	
	/**
	 * Établit la connexion au serveur SMTP et effectue l'identification
	 * 
	 * @param string  $smtp_server  Nom ou IP du serveur
	 * @param integer $smtp_port    Port d'accés au serveur SMTP
	 * @param string  $smtp_user    login pour la connexion (seulement si nécessaire)
	 * @param string  $smtp_pass    password pour la connexion (seulement si nécessaire)
	 * @param string  $server_from  Serveur émetteur
	 * 
	 * @access public
	 * @return boolean
	 */
	function connect($smtp_server = '', $smtp_port = 25, $smtp_user = '', $smtp_pass = '', $server_from = '')
	{
		$vararray = array('smtp_server', 'smtp_port', 'smtp_user', 'smtp_pass', 'server_from');
		foreach( $vararray AS $varname )
		{
			$this->{$varname} = ( !empty(${$varname}) ) ? ${$varname} : $this->{$varname};
		}
		
		$this->reponse = $this->code = $this->log = $this->msg_error = '';
		
		//
		// Ouverture de la connexion au serveur SMTP
		//
		if( !($this->connect_id = @fsockopen($this->smtp_server, $this->smtp_port, $errno, $errstr, $this->timeout)) )
		{
			$this->error("connect() :: Echec lors de la connexion au serveur smtp : $errno $errstr");
			return false;
		}
		
		// 
		// Code success : 220
		// Code failure : 421
		//
		if( !$this->get_reponse(220) )
		{
			return false;
		}
		
		//
		// Comme on est poli, on dit bonjour, et on s'authentifie le cas échéant 
		// 
		// Code success : 250
		// Code error   : 500, 501, 504, 421
		//
		$this->put_data('EHLO ' . $this->server_from);
		if( !$this->get_reponse(250, false) )
		{
			$this->put_data('HELO ' . $this->server_from);
			if( !$this->get_reponse(250) )
			{
				return false;
			}
		}
		
		if( !empty($this->smtp_user) && !empty($this->smtp_pass) )
		{
			return $this->authenticate();
		}
		
		return true;
	}
	
	/**
	 * Authentification auprès du serveur, s'il le supporte
	 * 
	 * @access public
	 * @return boolean
	 */
	function authenticate()
	{
		$this->put_data('AUTH LOGIN');
		if( !$this->get_reponse(334) )
		{
			return false;
		}
		
		$this->put_data(base64_encode($this->smtp_user));
		if( !$this->get_reponse(334) )
		{
			return false;
		}
		
		$this->put_data(base64_encode($this->smtp_pass));
		if( !$this->get_reponse(235) )
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Envoit les données au serveur
	 * 
	 * @param string $input  Données à envoyer
	 * 
	 * @access private
	 * @return void
	 */
	function put_data($input)
	{
		if( $this->debug )
		{
			echo nl2br(htmlentities($input)) . '<br />';
			flush();
		}
		
		$this->log .= $input . "\r\n";
		
		fputs($this->connect_id, $input . "\r\n");
	}
	
	/**
	 * Récupère la réponse du serveur et la parse pour obtenir le code réponse
	 * 
	 * @access private
	 * @return boolean
	 */
	function get_reponse()
	{
		$disable_error = false;
		
		$num_args = func_num_args();
		
		$code_accept = array();
		for( $i = 0; $i < $num_args; $i++ )
		{
			$arg = func_get_arg($i);
			if( is_numeric($arg) )
			{
				$code_accept[] = $arg;
			}
			else
			{
				$disable_error = true;
			}
		}
		
		while( $this->reponse = fgets($this->connect_id, 512) )
		{
			if( $this->debug )
			{
				echo htmlentities($this->reponse) . '<br />';
				flush();
			}
			
			$this->log .= $this->reponse . "\r\n";
			
			if( substr($this->reponse, 3, 1) == ' ' )
			{
				$this->code = substr($this->reponse, 0, 3);
				break;
			}
		}
		
		if( !in_array($this->code, $code_accept) )
		{
			if( !$disable_error )
			{
				$this->error('send_data() :: ' . htmlentities($this->reponse));
			}
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * Commande MAIL FROM
	 * Envoi l'adresse email de l'expéditeur au serveur SMTP
	 * 
	 * @param string $email_from
	 * 
	 * @access public
	 * @return boolean
	 */
	function mail_from($email_from)
	{
		//
		// On spécifie l'adresse de l'expéditeur
		//
		// Code success : 250
		// Code failure : 552, 451, 452
		// Code error   : 500, 501, 421
		//
		$this->put_data('MAIL FROM:<' . $email_from . '>');
		
		return $this->get_reponse(250);
	}
	
	/**
	 * Commande RCPT TO
	 * Envoi une adresse email de destination au serveur
	 * 
	 * @param string  $email_to
	 * @param boolean $strict (si true, retourne true uniquement si code 250)
	 * 
	 * @access public
	 * @return boolean
	 */
	function rcpt_to($email_to, $strict = false)
	{
		//
		// On spécifie les destinatires au serveur smtp
		// 
		// Code success : 250, 251
		// Code failure : 550, 551, 552, 553, 450, 451, 452
		// Code error   : 500, 501, 503, 421
		//
		$this->put_data('RCPT TO:<' . $email_to . '>');
		
		return ( $strict ) ? $this->get_reponse(250) : $this->get_reponse(250, 251);
	}
	
	/**
	 * Commande DATA
	 * Envoie le message (entêtes et corps) au serveur et demande l'envoi
	 * 
	 * @param string $headers
	 * @param string $message
	 * 
	 * @access public
	 * @return true
	 */
	function send($headers, $message)
	{
		$headers = preg_replace("/(\r\n?)|\n/", "\r\n", $headers);
		$message = preg_replace("/(\r\n?)|\n/", "\r\n", $message);
		
		//
		// Si un point se trouve seul sur une ligne, on le remplace par deux points
		// pour éviter que le serveur ne l'interprète comme la fin de l'envoi
		//
		$message = preg_replace("/\r\n\./", "\r\n..", $message);
		
		//
		// On indique au serveur que l'on va lui livrer les données
		//
		// Code intermédiaire : 354
		//
		$this->put_data('DATA');
		if( !$this->get_reponse(354) )
		{
			return false;
		}
		
		//
		// On envoie les entêtes
		//
		$this->put_data($headers . "\r\n");
		
		//
		// Et maintenant le message
		//
		$this->put_data($message);
		
		//
		// On indique la fin de l'envoi de données au serveur
		//
		// Code success : 250
		// Code failure : 552, 554, 451, 452
		// Code error   : 500, 501, 503, 421
		//
		
		$this->put_data('.');
		if( !$this->get_reponse(250) )
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Envoi la commande NOOP
	 * 
	 * @access public
	 * @return boolean
	 */
	function noop()
	{
		//
		// Code success : 250
		// Code error   : 500, 421
		//
		$this->put_data('NOOP');
		
		return $this->get_reponse(250);
	}
	
	/**
	 * Envoi la commande VRFY
	 * 
	 * @access public
	 * @return boolean
	 */
	function verify($str)
	{
		//
		// Code success : 250, 251
		// Code error   : 500, 501, 502, 504, 421
		// Code failure : 550, 551, 553
		//
		$this->put_data('VRFY ' . $str);
		
		return $this->get_reponse(250, 251);
	}
	
	/**
	 * Commande QUIT
	 * Ferme la connexion au serveur SMTP
	 * 
	 * @access public
	 * @return void
	 */
	function quit()
	{
		//
		// Comme on est poli, on dit aurevoir au serveur avec la commande adéquat QUIT 
		//
		// Code success : 221
		// Code failure : 500
		//
		if( is_resource($this->connect_id) )
		{
			$this->put_data('QUIT');
			fclose($this->connect_id);
			
			$this->connect_id = NULL;
		}
		
		if( $this->save_log )
		{
			$mode = ( $this->erase_log ) ? 'w' : 'a';
			
			if( $fw = fopen($this->filelog, $mode) )
			{
				$log  = 'Connexion au serveur ' . $this->smtp_server . ' :: ' . date('d/M/Y H:i:s');
				$log .= "\r\n~~~~~~~~~~~~~~~~~~~~\r\n";
				$log .= $this->log . "\r\n\r\n";
				
				fwrite($fw, $log);
				fclose($fw);
			}
		}
	}
	
	/**
	 * @param string $msg_error  Le message d'erreur, à afficher si mode debug
	 * 
	 * @access private
	 * @return void
	 */
	function error($msg_error)
	{
		if( $this->debug )
		{
			$this->quit();
			exit($msg_error);
		}
		
		if( $this->msg_error == '' )
		{
			$this->msg_error = $msg_error;
		}
	}
}

}
?>