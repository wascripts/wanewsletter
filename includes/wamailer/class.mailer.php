<?php
/**
 * Copyright (c) 2002-2005 Aurélien Maille
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
 * @license http://www.gnu.org/copyleft/lesser.html	 GNU Lesser General Public License
 * @version 2.2
 */

if( !defined('CLASS_MAILER_INC') )
{

define('CLASS_MAILER_INC', true);
define('WM_HOST_OTHER',    1);
define('WM_HOST_ONLINE',   2);
define('WM_SMTP_MODE',     3);
define('WM_SENDMAIL_MODE', 4);

/**
 * Classe d'envois d'emails
 * 
 * Fonctionne également sur Online
 * Gère l'attachement de pièces jointes et l'envoi d'emails au format html, ainsi que les emails multi-formats.
 * Gére aussi les pièces jointes dites "embarquées"
 * (incorporées et utilisées dans l'email html, ex: images, sons ..)
 * 
 * Se référer aux RFC 822, 2045, 2046, 2047 et 2822
 * 
 * Les sources qui m'ont bien aidées :
 * 
 * @link http://abcdrfc.free.fr/ (français)
 * @link http://www.rfc-editor.org/ (anglais)
 * @link http://cvs.php.net/cvs.php/php4.fubar/ext/standard/mail.c?login=2
 * @link http://cvs.php.net/cvs.php/php4.fubar/win32/sendmail.c?login=2
 * 
 * @access public
 */
class Mailer {
	
	/************************ REGLAGES SMTP ************************/
	
	/**
	 * Activation du mode smtp
	 * 
	 * @var boolean
	 * @access public
	 */
	var $smtp_mode             = FALSE;
	
	/**
	 * Chemin vers la classe smtp
	 * 
	 * Si laissée vide, le script tentera de reconstituer le chemin vers la classe smtp
	 * (la classe smtp doit alors être dans le même dossier que la présente classe)
	 * 
	 * @var string
	 * @access public
	 */
	var $smtp_path             = '';
	
	/**
	 * Variable qui contiendra l'objet smtp
	 * 
	 * @var object
	 * @access public
	 */
	var $smtp                  = NULL;
	
	/**
	 * Si placé à TRUE, la connexion au serveur SMTP ne sera pas fermée après l'envoi, et sera réutilisée 
	 * pour un envoi ultérieur. 
	 * Ce sera alors au programmeur de refermer lui même la connexion après la fin des envois en faisant 
	 * appel à la méthode quit() de la classe smtp : $mailer->smtp->quit(); 
	 * 
	 * @var boolean
	 * @access public
	 */
	var $persistent_connection = FALSE;
	
	/***************************************************************/
	
	/********************** REGLAGES SENDMAIL **********************/
	
	/**
	 * Activation du mode sendmail
	 * 
	 * @var boolean
	 * @access public
	 */
	var $sendmail_mode           = FALSE;
	
	/**
	 * Chemin d'accés à sendmail
	 * 
	 * @var string
	 * @access public
	 */
	var $sendmail_path           = '/usr/sbin/sendmail';
	
	/**
	 * Paramètres de commandes complémentaires
	 * 
	 * @var string
	 * @access public
	 */
	var $sendmail_cmd           = '';
	
	/***************************************************************/
	
	/**
	 * Chemins par défaut pour les modèles d'emails 
	 *
	 * @var string
	 * @access public
	 */
	var $root                   = './';
	
	/**
	 * Extensions des modèles au format texte
	 *
	 * @var string
	 * @access public
	 */
	var $text_tpl_ext           = 'txt';
	
	/**
	 * Extensions des modèles au format html
	 *
	 * @var string
	 * @access public
	 */
	var $html_tpl_ext           = 'html';
	
	/**
	 * Vous devez définir la fonction mail qu'utilise votre hébergeur 
	 * 
	 * 1 ou WM_HOST_OTHER pour la fonction mail() classique
	 * 2 ou WM_HOST_ONLINE pour la fonction email() de online
	 *
	 * @var integer
	 * @access public
	 */
	var $hebergeur              = WM_HOST_OTHER;
	
	/**
	 * Format de l'email 
	 * 
	 * 1 - pour format texte brut
	 * 2 - pour format html
	 * 3 - Multi-format (html affiché, et texte si html pas supporté)
	 * 
	 * @var integer
	 * @access public
	 */
	var $format                 = 1;
	
	/**
	 * Adresse de l'expéditeur 
	 * 
	 * @var array
	 * @access public
	 */
	var $from                   = array('email' => '', 'name' => '');
	
	/**
	 * Adresse de l'expéditeur (spécifique à Online)
	 * 
	 * @var string
	 * @access public
	 */
	var $from_online            = '';
	
	/**
	 * Adresse de réponse (spécifique à Online)
	 * 
	 * @var string
	 * @access public
	 */
	var $reply_online           = '';
	
	/**
	 * Tableau des destinataires
	 * 
	 * @var array
	 * @access private
	 */
	var $address                = array('To' => array(), 'Cc' => array(), 'Bcc' => array());
	
	/**
	 * Sujet de l'email
	 * 
	 * @var string
	 * @access public
	 */
	var $subject                = '';
	
	/**
	 * Messages non compilés, selon le format
	 * 
	 * @var array
	 * @access private
	 */
	var $uncompiled_message     = array();
	
	/**
	 * Messages alternatifs non compilés, selon le format
	 * 
	 * @var array
	 * @access private
	 */
	var $uncompiled_altmessage  = array();
	
	/**
	 * Messages compilés, selon le format
	 * 
	 * @var array
	 * @access private
	 */
	var $compiled_message       = array();
	
	/**
	 * Tableau des tags à remplacer dans le message
	 * 
	 * @var array
	 * @access private
	 */
	var $tags                   = array();
	
	/**
	 * Tableau des blocks à remplacer dans le message, ainsi que les tags qui leur sont associés
	 * 
	 * @var array
	 * @access private
	 */
	var $block_tags             = array();
	
	/**
	 * "Frontières" utilisées pour séparer les différentes parties de l'email
	 * 
	 * @var array
	 * @access private
	 */
	var $boundary               = array('part0' => array(), 'part1' => array(), 'part2' => array());
	
	/**
	 * Tableau des fichiers attachés
	 * 
	 * @var array
	 * @access private
	 */ 
	var $attachfile             = array('path' => array(), 'name' => array(), 'mimetype' => array(), 'disposition' => array());
	
	/**
	 * Tableau des fichiers incorporés (spécifique aux emails au format html)
	 * 
	 * @var array
	 * @access private
	 */
	var $embeddedfile           = array('path' => array(), 'name' => array(), 'mimetype' => array());
	
	/**
	 * Tableau des en-têtes de l'email
	 * 
	 * @var array
	 * @access private
	 */
	var $headers                = array();
	
	/**
	 * Jeu de caractère utilisé dans l'email
	 * 
	 * @var string
	 * @access public
	 */
	var $charset                = 'iso-8859-1';
	
	/**
	 * Encodage à utiliser 
	 * (7bit, 8bit, quoted-printable, base64 ou binary)
	 * 
	 * @var string
	 * @access public
	 */
	var $encoding               = '8bit';
	
	/**
	 * Longueur maximale des lignes dans l'email, telle que définie dans la rfc2822
	 * 
	 * @var integer
	 * @access private
	 */
	var $maxlen                 = 78;
	
	/**
	 * IP de l'expéditeur
	 * 
	 * @var string
	 * @access public
	 */
	var $sender_ip              = '127.0.0.1';
	
	/**
	 * Nom du serveur émetteur
	 * 
	 * @var string
	 * @access public
	 */
	var $server_from            = 'localhost';
	
	/**
	 * Activer/désactiver le validateur d'adresse email
	 * 
	 * @var boolean
	 * @access public
	 */
	var $valid_syntax           = FALSE;
	
	/**
	 * Activer/désactiver le mode de débogguage
	 * S'il est activé, les messages d'erreur s'afficheront directement à l'écran et l'éxécution du script 
	 * sera interrompu
	 * 
	 * @var boolean
	 * @access public
	 */
	var $debug                  = FALSE;
	
	/**
	 * Statut du traitement de l'envoi
	 * Cette variable ne doit pas être modifiée, si elle est à false, 
	 * l'email n'est tout simplement pas envoyé
	 * 
	 * @var boolean
	 * @access private
	 */
	var $statut                 = TRUE; // ne pas modifier !
	
	/**
	 * Variable contenant le dernier message d'erreur
	 * 
	 * @var string
	 * @access private
	 */
	var $msg_error              = '';
	
	/**
	 * Pour comprendre l'utilité de cette variable, référez vous à la méthode recipients_list()
	 * 
	 * Si votre serveur utilise sendmail, mettez à 1, s'il utilise un serveur smtp, mettez à -1.
	 * Si vous ne savez pas, n'y touchez pas, le script tentera de trouver de lui même.
	 * 
	 * @var mixed
	 * @access public
	 */
	var $fix_bug_mail           = NULL;
	
	/**
	 * Version actuelle de la classe
	 * 
	 * @var string
	 * @access private
	 */
	var $version                = '2.3';
	
	/**
	 * Constructeur de classe
	 * 
	 * @param string $template_path  Chemin vers les modèles d'emails 
	 * 
	 * @access public
	 * @return void
	 */
	function Mailer($template_path = '')
	{
		if( $template_path != '' )
		{
			$this->set_root($template_path);
		}
		
		//
		// On récupère le domaine actuel dans le cas d'un dialogue SMTP
		// et pour certains en-têtes de l'email
		//
		if( $this->server_from == 'localhost' && !empty($_SERVER['SERVER_NAME']) )
		{
			$this->server_from = $_SERVER['SERVER_NAME'];
		}
		
		//
		// On récupère l'adresse IP pour l'en-tête abuse
		//
		if( $this->sender_ip == '127.0.0.1' && !empty($_SERVER['REMOTE_ADDR']) )
		{
			$this->sender_ip = $_SERVER['REMOTE_ADDR'];
			
			if( preg_match('/^(\d+\.\d+\.\d+\.\d+)/', getenv('HTTP_X_FORWARDED_FOR'), $match) ) 
			{
				$private_ip = trim($match[1]);
				
				/**
				 * Liens utiles sur les différentes plages d'ip :
				 * 
				 * @link http://www.commentcamarche.net/internet/ip.php3
				 * @link http://www.usenet-fr.net/fur/comp/reseaux/masques.html
				 */ 
				
				// 
				// Liste d'ip non valides
				// 
				$pattern_ip   = array();
				$pattern_ip[] = '/^0\..*/'; // Réseau 0 n'existe pas
				$pattern_ip[] = '/^127\.0\.0\.1/'; // ip locale
				
				// Plages d'ip spécifiques à l'intranet
				$pattern_ip[] = '/^10\..*/';
				$pattern_ip[] = '/^172\.1[6-9]\..*/';
				$pattern_ip[] = '/^172\.2[0-9]\..*/';
				$pattern_ip[] = '/^172\.30\..*/';
				$pattern_ip[] = '/^172\.31\..*/';
				$pattern_ip[] = '/^192\.168\..*/';
				
				// Plage d'adresse de classe D réservée pour les flux multicast et de classe E, non utilisée 
				$pattern_ip[] = '/^22[4-9]\..*/';
				$pattern_ip[] = '/^2[3-5][0-9]\..*/';
				
				$this->sender_ip = preg_replace($pattern_ip, $this->sender_ip, $private_ip);
			}
		}
		
		if( $this->hebergeur == WM_HOST_OTHER && Mailer::is_online_host() == true )
		{
			$this->hebergeur = WM_HOST_ONLINE;
		}
	}
	
	/**
	 * Indique si l'on est sur un serveur de l'hébergeur Online
	 * 
	 * @access public
	 * @return boolean
	 */
	function is_online_host()
	{
		return !function_exists('mail');
	}
	
	/**
	 * Initialise un objet Smtp pour utilisation ultérieure
	 * 
	 * @param string  $smtp_server  Nom du serveur SMTP
	 * @param integer $smtp_port    Port de connexion (25 dans la grande majorité des cas)
	 * @param string  $smtp_user    Login d'authentification (si AUTH est supporté par le serveur)
	 * @param string  $smtp_pass    Password d'authentification (si AUTH est supporté par le serveur)
	 * @param string  $server_from  Serveur émetteur
	 * 
	 * @access public
	 * @return void
	 */
	function use_smtp($smtp_server = '', $smtp_port = 25, $smtp_user = '', $smtp_pass = '', $server_from = '')
	{
		$this->smtp_mode = true;
		$this->hebergeur = WM_SMTP_MODE;
		
		$smtp = $this->init_smtp($this->debug);
		
		if( $server_from != '' )
		{
			$this->server_from = $smtp->server_from = $server_from;
		}
		
		$vararray = array('smtp_server', 'smtp_port', 'smtp_user', 'smtp_pass');
		foreach( $vararray AS $varname )
		{
			$smtp->{$varname} = ( !empty(${$varname}) ) ? ${$varname} : $smtp->{$varname};
		}
		
		$this->smtp = $smtp;
	}
	
	/**
	 * Créé une nouvelle instance de la classe
	 * 
	 * @param boolean $debug
	 * 
	 * @access private
	 * @return object
	 */
	function init_smtp($debug = false)
	{
		if( !class_exists('Smtp') )
		{
			if( isset($this) && !empty($this->smtp_path) )
			{
				$smtp_path = rtrim($this->smtp_path, '/');
			}
			else
			{
				$smtp_path = dirname(__FILE__);
			}
			
			require $smtp_path . '/class.smtp.php';
		}
		
		$smtp = new Smtp();
		$smtp->debug = $debug;
		
		return $smtp;
	}
	
	/**
	 * Paramétrage des variables concernant sendmail
	 * 
	 * @param string $sendmail_cmd
	 * @param string $sendmail_path
	 * 
	 * @access public
	 * @return boolean
	 */
	function use_sendmail($sendmail_cmd = '', $sendmail_path = '')
	{
		$this->sendmail_mode = true;
		$this->hebergeur     = WM_SENDMAIL_MODE;
		
		$this->sendmail_path = ( $sendmail_path != '' ) ? $sendmail_path : $this->sendmail_path;
		$this->sendmail_cmd  = ( $sendmail_cmd != '' ) ? $sendmail_cmd : $this->sendmail_cmd;
		
		if( !@is_executable($this->sendmail_path) )
		{
			$this->error('use_sendmail() :: ' . $this->sendmail_path . ' n\'est pas exécutable');
			return false;
		}
		
		return true;
	}
	
	/**
	 * Réglages du chemin vers les modèles
	 * 
	 * @param string $template_path  Chemin vers les modèles
	 * 
	 * @access public
	 * @return boolean
	 */
	function set_root($template_path)
	{
		$template_path = preg_replace('/^(.*?)\/?$/', '\\1', $template_path);
		
		if( !file_exists($template_path) || !is_dir($template_path) )
		{
			$this->error("set_root() :: Le chemin \"$template_path/\" est incorrect.");
			return false;
		}
		
		$this->root = $template_path . '/';
		
		return true;
	}
	
	/**
	 * Vérifie qu'un répertoire ou fichier existe et est accessible en lecture
	 * 
	 * @param string $path  Chemin vers le fichier
	 * 
	 * @access private
	 * @return boolean
	 */
	function set_file($path)
	{
		if( file_exists($path) && is_readable($path) )
		{
			return true;
		}
		
		$this->error('set_file() :: Le fichier "' . basename($path) . '" est introuvable ou n\'est pas accessible en lecture');
		return false;
	}
	
	/**
	 * Retourne le contenu d'un fichier
	 * 
	 * @param string  $path         Chemin vers le fichier
	 * @param boolean $binary_file  On spécifie si on charge un fichier binaire (pour windows)
	 * 
	 * @access public
	 * @return mixed
	 */
	function loadfile($path, $binary_file = false)
	{
		$mode = ( $binary_file ) ? 'rb' : 'r';
		
		if( !($fp = @fopen($path, $mode)) )
		{
			$this->error('loadfile() :: Lecture du fichier "' . basename($path) . '" impossible');
			return false;
		}
		
		$contents = fread($fp, filesize($path));
		fclose($fp);
		
		return $contents;
	}
	
	/**
	 * @param mixed $format  Format de l'email
	 * 
	 * @access public
	 * @return void
	 */
	function set_format($format)
	{
		if( !is_numeric($format) )
		{
			$format = strtolower($format);
		}
		
		switch( $format )
		{
			case 'alt':
			case 3:
				$this->format = 3;
				break;
			
			case 'html':
			case 'htm':
			case 2:
				$this->format = 2;
				break;
			
			case 'texte':
			case 'txt':
			case 'text':
			case 1:
			default:
				$this->format = 1;
				break;
		}
	}
	
	/**
	 * @param string $charset
	 * 
	 * @access public
	 * @return void
	 */
	function set_charset($charset)
	{
		$this->charset = $charset;
	}
	
	/**
	 * Vérifie la validité syntaxique d'une adresse email
	 * 
	 * @param string $email
	 * 
	 * @access public
	 * @return boolean
	 */
	function validate_email($email)
	{
		return (bool) preg_match('/^((?(?<!^)\.)[-!#$%&\'*+\/0-9=?a-z^_`{|}~]+)+@'
			. '((?(?<!@)\.)[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])?)+$/i', $email);
	}
	
	/**
	 * Vérifie si une adresse email N'EST PAS valide (domaine et compte).
	 * Ceci est différent d'une vérification de validité.
	 * Le serveur SMTP peut très bien répondre par un 250 ok pour cet email,
	 * les erreurs d'adressage étant traitées ultérieurement au niveau du
	 * serveur POP.
	 * 
	 * @link http://www.sitepoint.com/article/1051
	 * @link http://www.zend.com/codex.php?id=449&single=1
	 * @link http://fr.php.net/getmxrr (troisième User contributed note)
	 * 
	 * @param string $email   Adresse email à vérifier
	 * @param string $errstr  Passé par référence. Contiendra l'éventuel message
	 *                        d'erreur retourné par le serveur SMTP
	 * 
	 * @access public
	 * @return boolean
	 */
	function validate_email_mx($email, &$errstr)
	{
		$result_check_mx = true;
		
		list(, $domain) = explode('@', $email);
		
		$mx = array();
		if( !function_exists('getmxrr') )
		{
			exec(sprintf('nslookup -type=mx %s', escapeshellarg($domain)), $lines);
			
			$regexp = '/^' . preg_quote($domain) . '\s+(?:(?i)MX\s+)?'
				. '(preference\s*=\s*([0-9]+),\s*)?'
				. 'mail\s+exchanger\s*=\s*(?(1)|([0-9]+)\s+)([^ ]+?)\.?$/';
			
			foreach( $lines as $value )
			{
				if( preg_match($regexp, $value, $match) )
				{
					array_push($mx, array(
						$match[3] === '' ? $match[2] : $match[3],
						$match[4]
					));
				}
			}
			
			$result = ( count($mx) > 0 ) ? true : false;
		}
		else
		{
			$result = getmxrr($domain, $hosts, $weight);
			
			for( $i = 0, $m = count($hosts); $i < $m; $i++ )
			{
				array_push($mx, array($weight[$i], $hosts[$i]));
			}
		}
		
		if( !$result )
		{
			array_push($mx, array(0, $domain));
		}
		
		array_multisort($mx);
		
		$smtp = Mailer::init_smtp(false);
		
		foreach( $mx as $record )
		{
			if( $smtp->connect($record[1]) )
			{
				if( $smtp->mail_from($email) )
				{
					if( !$smtp->rcpt_to($email, true) )
					{
						$errstr = $smtp->reponse;
						$result_check_mx = false;
					}
				}
				
				$smtp->quit();
				break;
			}
			else if( !$result )
			{
				$errstr = $smtp->msg_error;
				$result_check_mx = false;
				break;
			}
		}
		
		return $result_check_mx;
	}
	
	/**
	 * Définition du champ expéditeur
	 * 
	 * @param string $email_from  Email de l'expéditeur
	 * @param string $name_from   Personnalisation du nom de l'expéditeur
	 * 
	 * @access public
	 * @return boolean
	 */
	function set_from($email_from, $name_from = '')
	{
		if( $this->valid_syntax && !$this->validate_email($email_from) )
		{
			$this->error('set_from() :: "' . $email_from . '", cette adresse email n\'est pas valide');
			return false;
		}
		
		$this->from['email'] = trim($email_from);
		$this->from['name']  = trim($name_from);
		
		return true;
	}
	
	/**
	 * Définition des destinataires
	 * 
	 * @param mixed  $email_mixed  Email du destinataire ou tableau contenant la liste des destinataires 
	 * @param string $type         Type de destinataire : to, cc, ou bcc
	 * 
	 * @access public
	 * @return boolean
	 */
	function set_address($email_mixed, $type = '')
	{
		$type = ucfirst(strtolower($type));
		if( $type != 'Cc' && $type != 'Bcc' )
		{
			$type = 'To';
		}
		
		if( !is_array($email_mixed) )
		{
			if( preg_match('/^([^<]*) <([^>]*)>$/', $email_mixed, $regs) )
			{
				$email_mixed = array($regs[1] => $regs[2]);
			}
			else
			{
				$email_mixed = array($email_mixed);
			}
		}
		
		foreach( $email_mixed AS $name => $email )
		{
			$email = trim($email);
			
			if( $this->valid_syntax && !$this->validate_email($email) )
			{
				$this->error('set_address() :: "' . $email . '", cette adresse email n\'est pas valide');
				return false;
			}
			
			$name = ( !is_numeric($name) ) ? trim($name) : '';
			
			if( !empty($this->headers[$type]) )
			{
				$this->headers[$type] .= ', ';
			}
			else
			{
				$this->headers[$type] = '';
			}
			
			$this->headers[$type] .= ( ( $name != '' ) ? $this->encode_mime_header('"' . $name . '"', $type) . ' ' : '' ) . '<' . $email . '>';
			
			$this->address[$type][] = $email;
		}
		
		return true;
	}
	
	/**
	 * Ancienne méthode d'ajout de destinataires, présent pour assurer la compatibilité 
	 * 
	 * @see Mailer::set_address()
	 * @access public
	 * @status obsolete
	 * @return boolean
	 */
	function set_to($arg1, $arg2 = '')
	{
		return $this->set_address($arg1, $arg2);
	}
	
	/**
	 * Définition du sujet de l'email
	 * 
	 * @param string $subject  Le sujet de l'email
	 * 
	 * @access public
	 * @return void
	 */
	function set_subject($subject)
	{
		$this->subject = trim($subject);
	}
	
	/**
	 * Corps de l'email
	 * 
	 * @param string $message   Contient le message à envoyer
	 * @param array  $tags_ary  Variables à remplacer dans le texte
	 * 
	 * @access public
	 * @return void
	 */
	function set_message($message, $tags_ary = '')
	{
		$this->compiled_message[$this->format]   = '';
		$this->uncompiled_message[$this->format] = trim($message);
		
		$this->assign_tags($tags_ary);
	}
	
	/**
	 * Alternative texte de l'email (on suppose que set_message() a été appellé
	 * avec un contenu html)
	 * 
	 * @param string $message   Contient le message alternatif
	 * @param array  $tags_ary  Variables à remplacer dans le texte
	 * 
	 * @access public
	 * @return void
	 */
	function set_altmessage($message, $tags_ary = '')
	{
		$this->uncompiled_altmessage[$this->format] = trim($message);
		
		$this->assign_tags($tags_ary);
	}
	
	/**
	 * @param string $file      Nom du modèle (sans l'extension)
	 * @param array  $tags_ary  Variables à remplacer dans le texte
	 * 
	 * @access public
	 * @return boolean
	 */
	function use_template($file, $tags_ary = '')
	{
		$this->compiled_message[$this->format] = '';
		
		if( !$this->set_root($this->root) )
		{
			return false;
		}
		
		if( ( $this->format == 3 || $this->format == 1 ) && $this->set_file($this->root . $file . '.' . $this->text_tpl_ext) )
		{
			$eval  = '$this->uncompiled_' . ( ( $this->format == 3 ) ? 'altmessage' : 'message' );
			$eval .= '[$this->format] = $this->loadfile($this->root . $file . \'.' . $this->text_tpl_ext . '\');';
			
			eval($eval);
		}
		
		if( ( $this->format == 3 || $this->format == 2 ) && $this->set_file($this->root . $file . '.' . $this->html_tpl_ext) )
		{
			$this->uncompiled_message[$this->format] = $this->loadfile($this->root . $file . '.' . $this->html_tpl_ext);
		}
		
		$this->assign_tags($tags_ary);
		
		return true;
	}
	
	/**
	 * @param array $tags_ary  Tableau des tags à remplacer dans le message
	 * 
	 * @access public
	 * @return void
	 */
	function assign_tags($tags_ary)
	{
		if( is_array($tags_ary) )
		{
			foreach( $tags_ary AS $key => $val )
			{
				if( preg_match('/^[[:alnum:]_-]+$/i', $key) )
				{
					$this->tags[$key] = $val;
				}
			}
		}
	}
	
	/**
	 * @param string $block_name  Nom du block et des éventuels sous blocks
	 * @param array  $tags_ary    Tableau des tags à remplacer dans le message
	 * 
	 * @access public
	 * @return void
	 */
	function assign_block_tags($block_name, $tags_ary = '')
	{
		if( preg_match('/^[[:alnum:]_-]+$/i', $block_name) )
		{
			$this->block_tags[$block_name] = array();
			
			if( is_array($tags_ary) )
			{
				foreach( $tags_ary AS $key => $val )
				{
					if( preg_match('/^[[:alnum:]_-]+$/i', $key) )
					{
						$this->block_tags[$block_name][$key] = $val;
					}
				}
			}
		}
	}
	
	/**
	 * Ajout d'un fichier joint
	 * 
	 * @param string  $path         Chemin vers le fichier
	 * @param string  $filename     Nom du fichier
	 * @param string  $disposition  Disposition
	 * @param string  $mime_type    Type de média
	 * @param boolean $embedded     true si fichier incorporé dans l'email html
	 * 
	 * @access public
	 * @return boolean
	 */
	function attachment($path, $filename = '', $disposition = '', $mime_type = '', $embedded = false)
	{
		$this->compiled_message[$this->format] = '';
		
		if( !$this->set_file($path) )
		{
			return false;
		}
		
		if( $embedded )
		{
			$offset = count($this->embeddedfile['path']);
			
			$this->embeddedfile['path'][$offset]     = $path;
			$this->embeddedfile['name'][$offset]     = ( $filename != '' ) ? trim($filename) : basename($path);
			$this->embeddedfile['mimetype'][$offset] = $mime_type;
		}
		else
		{
			$offset = count($this->attachfile['path']);
			
			$this->attachfile['path'][$offset]        = $path;
			$this->attachfile['name'][$offset]        = ( $filename != '' ) ? trim($filename) : basename($path);
			$this->attachfile['mimetype'][$offset]    = $mime_type;
			$this->attachfile['disposition'][$offset] = ( $disposition == 'inline' ) ? 'inline' : 'attachment';
		}
		
		return true; 
	}
	
	/**
	 * @access private
	 * @return string
	 */
	function generate_rand_str()
	{
		$chars = array(
			'A', 'a', 'B', 'b', 'C', 'c', 'D', 'd', 'E', 'e', 'F', 'f', 'G', 'g', 'H', 'h', 'I', 'i', 'J', 'j', 
			'K', 'k', 'L', 'l', 'M', 'm', 'N', 'n', 'O', 'o', 'P', 'p', 'Q', 'q', 'R', 'r', 'S', 's', 'T', 't', 
			'U', 'u', 'V', 'v', 'W', 'w', 'X', 'x', 'Y', 'y', 'Z', 'z', '2', '3', '4', '5', '6', '7', '8', '9', '0'
		);
		
		$max_chars = (count($chars) - 1);
		srand( (double) microtime()*1000000);
		
		$rand_str = '';
		for( $i = 0; $i < 30; $i++ )
		{
			$rand_str .= $chars[rand(0, $max_chars)];
		}
		
		return $rand_str;
	}
	
	/**
	 * Renvoie le type mime à partir de l'extension de fichier
	 * 
	 * @param string $ext  Extension de fichier
	 * 
	 * @access public
	 * @return string
	 */
	function mime_type($ext)
	{
		//
		// Tableau des extensions et de leur Mime-Type
		// Rien ne vous interdit d'en rajouter si besoin est.
		//
		$mime_type_ary = array(
			'css'  => 'text/css',
			'html' => 'text/html',
			'htm'  => 'text/html',
			'js'   => 'text/javascript',
			'txt'  => 'text/plain',
			'rtx'  => 'text/richtext',
			'tsv'  => 'text/tab-separated-value',
			'xml'  => 'text/xml',
			'xls'  => 'text/xml',
			
			'eml'  => 'message/rfc822',
			'nws'  => 'message/rfc822',
			
			'bmp'  => 'image/bmp',
			'pcx'  => 'image/bmp',
			'gif'  => 'image/gif',
			'ief'  => 'image/ief',
			'jpeg' => 'image/jpeg',
			'jpg'  => 'image/jpeg',
			'jpe'  => 'image/jpeg',
			'png'  => 'image/png',
			'tiff' => 'image/tiff',
			'tif'  => 'image/tiff',
			'cmu'  => 'image/x-cmu-raster',
			'pnm'  => 'image/x-portable-anymap',
			'pbm'  => 'image/x-portable-bitmap',
			'pgm'  => 'image/x-portable-graymap',
			'ppm'  => 'image/x-portable-pixmap',
			'rgb'  => 'image/x-rgb',
			'xbm'  => 'image/x-xbitmap',
			'xpm'  => 'image/x-xpixmap',
			'xwd'  => 'image/x-xwindowdump',
			
			'dwg'  => 'application/acad',
			'ccad' => 'application/clariscad',
			'drw'  => 'application/drafting',
			'dxf'  => 'application/dxf',
			'xls'  => 'application/excel',
			'hdf'  => 'application/hdf',
			'unv'  => 'application/i-deas',
			'igs'  => 'application/iges',
			'iges' => 'application/iges',
			'doc'  => 'application/msword',
			'dot'  => 'application/msword',
			'wrd'  => 'application/msword',
			'oda'  => 'application/oda',
			'pdf'  => 'application/pdf',
			'ppt'  => 'application/powerpoint',
			'ai'   => 'application/postscript',
			'eps'  => 'application/postscript',
			'ps'   => 'application/postscript',
			'rtf'  => 'application/rtf',
			'rm'   => 'application/vnd.rn-realmedia',
			'dvi'  => 'application/x-dvi',
			'gtar' => 'application/x-gtar',
			'tgz'  => 'application/x-gtar',
			'swf'  => 'application/x-shockwave-flash',
			'tar'  => 'application/x-tar',
			'gz'   => 'application/x-gzip-compressed',
			'zip'  => 'application/zip',
			'xhtml'=> 'application/xhtml+xml',
			'xht'  => 'application/xhtml+xml',
			
			'au'   => 'audio/basic',
			'snd'  => 'audio/basic',
			'aif'  => 'audio/x-aiff',
			'aiff' => 'audio/x-aiff',
			'aifc' => 'audio/x-aiff',
			'wma'  => 'audio/x-ms-wma',
			
			'mpeg' => 'video/mpeg',
			'mpg'  => 'video/mpeg',
			'mpe'  => 'video/mpeg',
			'mov'  => 'video/quicktime',
			'avi'  => 'video/msvideo',
			'movie'=> 'video/x-sgi-movie',
			
			'unknow' => 'application/octet-stream'
		);
		
		return ( !empty($mime_type_ary[$ext]) ) ? $mime_type_ary[$ext] : $mime_type_ary['unknow'];
	}
	
	/**
	 * Définition de l'adresse de réponse
	 * 
	 * @param string $email_reply  Email de réponse
	 * @param string $name_reply   Personnalisation
	 * 
	 * @access public
	 * @return boolean
	 */
	function set_reply_to($email_reply = '', $name_reply = '')
	{
		if( $email_reply == '' )
		{
			$email_reply = $this->from['email'];
			$name_reply  = $this->from['name'];
		}
		else
		{
			$email_reply = trim($email_reply);
			$name_reply  = trim($name_reply);
			
			if( $this->valid_syntax && !$this->validate_email($email_reply) )
			{
				$this->error('set_reply_to() :: "' . $email_reply . '", cette adresse email n\'est pas valide');
				return false;
			}
		}
		
		$this->headers['Reply-To'] = ( ( $name_reply != '' ) ? $this->encode_mime_header('"' . $name_reply . '"', 'Reply-To') . ' ' : '' ) . '<' . $email_reply . '>';
		
		return true;
	}
	
	/**
	 * Définition de l'adresse de retour d'erreurs
	 * 
	 * @param string $email_return  Email de retour d'erreur
	 * 
	 * @access public
	 * @return boolean
	 */
	function set_return_path($email_return = '')
	{
		if( $email_return == '' )
		{
			$email_return = $this->from['email'];
		}
		else
		{
			$email_return = trim($email_return);
			
			if( $this->valid_syntax && !$this->validate_email($email_return) )
			{
				$this->error('set_return_path() :: "' . $email_return . '", cette adresse email n\'est pas valide');
				return false;
			}
		}
		
		$this->headers['Return-Path'] = $email_return;
		
		return true;
	}
	
	/**
	 * Définition de l'adresse cible pour les notifications de lecture
	 * 
	 * @param string $email_notify  Email pour le retour de notification de lecture 
	 *                                  (par défaut, l'adresse d'envoi est utilisée)
	 * 
	 * @access public
	 * @return boolean
	 * 
	 */
	function set_notify($email_notify = '')
	{
		if( $email_notify == '' )
		{
			$email_notify = $this->from['email'];
		}
		else
		{
			$email_notify = trim($email_notify);
			
			if( $this->valid_syntax && !$this->validate_email($email_notify) )
			{
				$this->error('set_notify() :: "' . $email_notify . '", cette adresse email n\'est pas valide');
				return false;
			}
		}
		
		$this->headers['Disposition-Notification-To'] = '<' . $email_notify . '>';
		
		return true;
	}
	
	/**
	 * @param string $soc
	 * 
	 * @access public
	 * @return void
	 */
	function organization($soc)
	{
		$this->headers['Organization'] = trim($soc);
	}
	
	/**
	 * Priorité de l'email
	 * 
	 * @param mixed $level  Niveau de priorité de l'email
	 * 
	 * @access public
	 * @return void
	 */
	function set_priority($level)
	{
		if( is_numeric($level) )
		{
			if( $level > 0 && $level <= 5 )
			{
				$this->headers['X-Priority'] = $level;
			}
		}
		else
		{
			$level = strtolower($level);
			
			switch( $level )
			{
				case 'highest':
					$this->headers['X-MSMail-Priority'] = 'Highest';
					break;
				
				case 'hight':
					$this->headers['X-MSMail-Priority'] = 'High';
					break;
				
				case 'low':
					$this->headers['X-MSMail-Priority'] = 'Low';
					break;
				
				case 'lowest':
					$this->headers['X-MSMail-Priority'] = 'Lowest';
					break;
				
				case 'normal':
				default:
					$this->headers['X-MSMail-Priority'] = 'Normal';
					break;
			}
		}
	}
	
	/**
	 * Ajout d'en-têtes supplémentaires
	 * 
	 * @param string $name  Nom de l'entête 
	 * @param string $body  Contenu de l'entête
	 * 
	 * @access public
	 * @return boolean
	 */
	function additionnal_header($name, $body)
	{
		if( $name != '' && $body != '' )
		{
			$name = trim(strtolower($name));
			$body = trim($body);
			
			//
			// Le nom de l'en-tête ne doit contenir que des caractères us-ascii, 
			// et ne doit pas contenir le caractère deux points (:)
			// - Section 2.2 de la rfc 2822
			//
			if( preg_match("/([\001-\032\072\127-\377])/", $name) )
			{
				return false;
			}
			
			//
			// Le contenu de l'en-tête ne doit contenir aucun retour chariot ou 
			// saut de ligne
			// - Section 2.2 de la rfc 2822
			//
			$body = preg_replace("/[\012\015]/", '', $body);
			
			if( strpos($name, '-') )
			{
				$elt = explode('-', $name);
				
				$item = array();
				foreach( $elt AS $val )
				{
					$item[] = ucfirst($val);
				}
				
				$name = implode('-', $item);
			}
			else
			{
				$name = ucfirst($name);
			}
			
			$this->headers[$name] = $body;
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * @param string $encoding  Type d'encodage désiré
	 * @param string $str       Chaîne à encoder
	 * 
	 * @access private
	 * @return string
	 */
	function make_encoding($encoding, $str)
	{
		switch( $encoding )
		{
			case '7bit':
			case '8bit':
				$str = preg_replace("/\r\n?/", "\n", $str);
				$str = $this->word_wrap($str, false);
				break;
			
			/**
			 * Encodage quoted-printable
			 * @link http://jlr31130.free.fr/rfc2045.html#6.7.
			 */
			case 'quoted-printable':
				$str = $this->quoted_printable_encode($str);
				break;
			
			/**
			 * Encodage en base64
			 * @link http://jlr31130.free.fr/rfc2045.html#6.8.
			 */
			case 'base64':
				$str = chunk_split(base64_encode($str), 76, "\n");
				break;
			
			case 'binary':
				break;
			
			default:
				$this->error('make_encoding() :: Aucun encodage valide spécifié !');
				break;
		}
		
		return $str;
	}
	
	/**
	 * Encode le texte en chaîne à guillemets
	 * 
	 * @param string $str  Texte à encoder
	 * 
	 * @access private
	 * @return string
	 */
	function quoted_printable_encode($str)
	{
		/**
		 * @link http://www.asciitable.com/
		 * @link http://jlr31130.free.fr/rfc2045.html (paragraphe 6.7)
		 */
		
		$str = preg_replace("/\r\n?/", "\n", $str);
		$str = preg_replace("/([\001-\010\013\014\016-\037\075\177-\377])/e", 'sprintf(\'=%02X\', ord("\\1"));', $str);
		$str = preg_replace("/([\011\040])(?=\n)/e", 'sprintf(\'=%02X\', ord("\\1"));', $str);
		
		if( strlen($str) > $this->maxlen )
		{
			$lines = explode("\n", $str);
			$total_lines = count($lines);
			
			for( $i = 0; $i < $total_lines; $i++ )
			{
				if( ($strlen = strlen($lines[$i])) > $this->maxlen )
				{
					$new_line = '';
					
					do
					{
						$tmp = substr($lines[$i], 0, ($this->maxlen - 1));
						
						if( ($pos = strrpos($tmp, '=')) && $pos > ($this->maxlen - 4) )
						{
							$tmp       = substr($tmp, 0, $pos);
							$lines[$i] = '=' . substr($lines[$i], ($pos + 1));
							$strlen    = ($strlen - strlen($tmp));
						}
						else
						{
							$lines[$i] = substr($lines[$i], ($this->maxlen - 1));
							$strlen    = ($strlen - ($this->maxlen - 1));
						}
						
						$new_line .= $tmp;
						if( $strlen > 0 )
						{
							$new_line .= "=\n";
							
							if( $strlen <= $this->maxlen )
							{
								$new_line .= $lines[$i];
								break;
							}
						}
					}
					while( $strlen > 0 );
					
					$lines[$i] = $new_line;
				}
			}
			
			$str = implode("\n", $lines);
		}
		
		return $str;
	}
	
	/**
	 * @param string $body         Contenu de l'entête
	 * @param string $header_name  Nom de l'entête correspondant
	 * 
	 * @access public
	 * @return string
	 */
	function encode_mime_header($body, $header_name)
	{
		if( preg_match("/([\001-\032\072\177-\377])/", $body) )
		{
			//
			// On encode le sujet au format quoted-printable (?, " et _ en plus)
			//
			// 9 = 2 (=) + 4 (?) + 1 (Q) + 1 <SP> + 1 (:)
			//
			$len  = ($this->maxlen - strlen($header_name) - 9 - strlen($this->charset));
			$body = preg_replace('/^"(.*)"$/', '\\1', $body);
			$body = preg_replace("/([\001-\032\042\075\077\137\177-\377])/e", 'sprintf(\'=%02X\', ord("\\1"));', $body);
			
			if( ($strlen = strlen($body)) > $len )
			{
				$new_body = '';
				
				do
				{
					$tmp = substr($body, 0, $len);
					
					if( ($pos = strrpos($tmp, '=')) && $pos > ($len - 4) )
					{
						$tmp    = substr($tmp, 0, $pos);
						$body   = '=' . substr($body, ($pos + 1));
						$strlen = ($strlen - strlen($tmp));
					}
					else
					{
						$body   = substr($body, $len);
						$strlen = ($strlen - $len);
					}
					
					$new_body .= '=?' . $this->charset . '?Q?' . str_replace(' ', '_', $tmp) . '?=';
					if( $strlen > 0 )
					{
						$new_body .= ' ';
						
						if( $strlen <= $len )
						{
							$new_body .= '=?' . $this->charset . '?Q?' . str_replace(' ', '_', $body) . '?=';
							break;
						}
					}
				}
				while( $strlen > 0 );
				
				$body = $new_body;
			}
			else
			{
				$body = '=?' . $this->charset . '?Q?' . str_replace(' ', '_', $body) . '?=';
			}
		}
		
		return $body;
	}
	
	/**
	 * @param string  $str
	 * @param boolean $is_header
	 * 
	 * @access public
	 * @return string
	 */
	function word_wrap($str, $is_header = true, $maxlen = 78)
	{
		if( isset($this) )
		{
			$maxlen = $this->maxlen;
		}
		
		if( $is_header )
		{
			/**
			 * \n<LWS> mais la fonction mail() ne laisse passer les long entêtes subject et to 
			 * que si on sépare avec \r\n<LWS>
			 * 
			 * LWS : Linear-White-Space (espace ou tabulation)
			 * 
			 * @link http://cvs.php.net/cvs.php/php4.fubar/ext/standard/mail.c?login=2
			 * 
			 * espace au lieu de tabulation sinon le sujet notamment ne s'affiche pas correctement 
			 * selon les lecteurs d'emails.
			 */
			
			$str = wordwrap($str, $maxlen, "\n ", 1);
		}
		else if( strlen($str) > $maxlen )
		{
			$lines = explode("\n", $str);
			$str   = '';
			foreach( $lines AS $line )
			{
				if( strlen($line) > $maxlen )
				{
					//
					// wordwrap bouffe les espaces aux endroits où il ajoute un saut de ligne
					// on réduit la longueur maximale de 1 et on coupe avec <SP>\n
					//
					$line = wordwrap($line, ($maxlen - 1), " \n");
				}
				
				$str .= $line . "\n";
			}
		}
		
		return trim($str);
	}
	
	/**
	 * Envoie de l'email
	 * 
	 * @param boolean $do_not_send  true pour retourner l'entête et le corps
	 *                              du message au lieu d'envoyer l'email
	 * 
	 * @access public
	 * @return boolean
	 */
	function send($do_not_send = false)
	{
		global $php_errormsg;
		
		//
		// Des erreurs se sont produites
		//
		if( !$this->statut )
		{
			return false;
		}
		
		if( $this->smtp_mode )
		{
			$this->hebergeur = WM_SMTP_MODE;
		}
		else if( $this->sendmail_mode )
		{
			$this->hebergeur = WM_SENDMAIL_MODE;
		}
		
		if( $this->format == 3 && empty($this->uncompiled_altmessage[3]) )
		{
			$this->format = 2;
			$this->uncompiled_message[2] = $this->uncompiled_message[3];
		}
		
		$address = $this->recipients_list();
		$headers = $this->compile_headers();
		$message = $this->compile_message();
		$Rpath   = $this->get_return_path();
		
		/**
		 * On encode le sujet de l'email si nécessaire.
		 * 
		 * FIX
		 * 
		 * La fonction mail() n'accepte les entêtes long que si on utilise la séquence CRLFSP (\r\n )
		 * Or, sur certains systèmes, il semble que les retours de ligne soient ... doublés ...
		 * Résultat, le corps de l'email commence au saut de ligne en trop (et donc contient une bonne 
		 * partie des entêtes).
		 * Pour éviter cela, on supprime les séquences LFSP (\n ) ajoutées par la méthode word_wrap()
		 * 
		 * @link http://bugs.php.net/bug.php?id=24805
		 */
		if( $this->subject != '' )
		{
			$subject = $this->encode_mime_header($this->subject, 'subject');
			if( $this->fix_bug_mail == -1 )
			{
				$subject = str_replace("\n ", "\r\n ", $this->word_wrap($subject));
			}
		}
		else
		{
			$subject = 'No subject';
		}
		
		if( $do_not_send )
		{
			return $headers . "\n\n" . $message;
		}
		
		//
		// Détection du safe_mode. S'il est activé, on ne pourra pas
		// régler l'adresse email de retour (return-path) avec le
		// cinquième argument.
		// En alternative, utilisation de ini_get() et ini_set() sur
		// l'option sendmail_from de PHP
		//
		$safe_mode     = @ini_get('safe_mode');
		$safe_mode_gid = @ini_get('safe_mode_gid');// Ajout pour free.fr et sa config php exotique
		
		if( $safe_mode || $safe_mode_gid )
		{
			$old_Rpath = @ini_get('sendmail_from');
			@ini_set('sendmail_from', $Rpath);
		}
		
		switch( $this->hebergeur )
		{
			case WM_HOST_OTHER:
				if( strncasecmp(PHP_OS, 'Win', 3) === 0 )
				{
					$address = preg_replace('/\r\n?|\n/', "\r\n", $address);
					$subject = preg_replace('/\r\n?|\n/', "\r\n", $subject);
					$message = preg_replace('/\r\n?|\n/', "\r\n", $message);
					$headers = preg_replace('/\r\n?|\n/', "\r\n", $headers);
				}
				
				if( !$safe_mode && !$safe_mode_gid )
				{
					$result = @mail($address, $subject, $message, $headers, '-f' . $Rpath);
				}
				else
				{
					$result = @mail($address, $subject, $message, $headers);
				}
				break;
			
			case WM_HOST_ONLINE:
				$result = @email($this->from_online, $address, $subject, $message, $this->reply_online, $headers);
				break;
			
			case WM_SMTP_MODE:
				$result = $this->smtpmail($address, $message, $headers, $Rpath);
				break;
			
			case WM_SENDMAIL_MODE:
				$result = $this->sendmail($address, $message, $headers, $Rpath);
				break;
			
			default:
				$this->error('send() :: Aucune fonction d\'envoi n\'est définie');
				$result = false;
				break;
		}
		
		if( $safe_mode || $safe_mode_gid )
		{
			@ini_set('sendmail_from', $old_Rpath);
		}
		
		if( !$result && !empty($php_errormsg) && stristr($php_errormsg, ' mail()') )
		{
			$this->error('send() :: ' . strip_tags($php_errormsg));
		}
		
		return $result;
	}
	
	/**
	 * Envoi via la classe smtp
	 * 
	 * @param string $address  Adresses des destinataires
	 * @param string $message  Corps de l'email
	 * @param string $headers  Entêtes de l'email
	 * @param string $Rpath    Adresse d'envoi (définit le return-path)
	 * 
	 * @access private
	 * @return boolean
	 */
	function smtpmail($address, $message, $headers, $Rpath)
	{
		if( !is_resource($this->smtp->connect_id) || !$this->smtp->noop() )
		{
			if( !$this->smtp->connect() )
			{
				$this->error($this->smtp->msg_error);
				return false;
			}
		}
		
		if( !$this->smtp->mail_from($Rpath) )
		{
			$this->error($this->smtp->msg_error);
			return false;
		}
		
		foreach( $address AS $email )
		{
			if( !$this->smtp->rcpt_to($email) )
			{
				$this->error($this->smtp->msg_error);
				return false;
			}
		}
		
		if( !$this->smtp->send($headers, $message) )
		{
			$this->error($this->smtp->msg_error);
			return false;
		}
		
		//
		// Apparamment, les commandes ne sont réellement effectuées qu'après la fermeture proprement 
		// de la connexion au serveur SMTP. On quitte donc la connexion courante si l'option de connexion 
		// persistante n'est pas activée.
		//
		if( !$this->persistent_connection )
		{
			$this->smtp->quit();
		}
		
		return true;
	}
	
	/**
	 * Envoi via sendmail
	 * (Je me suis beaucoup inspiré ici de la classe de PEAR concernant l'envoi via sendmail)
	 * 
	 * @param string $address  Adresses des destinataires
	 * @param string $message  Corps de l'email
	 * @param string $headers  Entêtes de l'email
	 * @param string $Rpath    Adresse d'envoi (définit le return-path)
	 * 
	 * @access private
	 * @return boolean
	 */
	function sendmail($address, $message, $headers, $Rpath)
	{
		if( @is_executable($this->sendmail_path) )
		{
			$headers = preg_replace("/\r\n?/", "\n", $headers);
			$message = preg_replace("/\r\n?/", "\n", $message);
			
			//
			// Vu dans les notes d'utilisateur sur php.net :
			// 
			// -t
			// Scan the To:, Cc:, and Bcc: from the message itself
			//
			$this->sendmail_cmd  = ( $this->sendmail_cmd != '' ) ? ' ' . $this->sendmail_cmd : '';
			$this->sendmail_cmd .= ' -t -f' . escapeshellcmd($Rpath);
			
			if( is_array($address) && count($address) > 0 )
			{
				$address = escapeshellcmd(implode(' ', $address));
				$this->sendmail_cmd .= ' -- ' . $address;
			}
			
			$mode = ( stristr(PHP_OS, 'WIN') ) ? 'wb' : 'w';
			$code = 0;
			
			if( !($sm = popen($this->sendmail_path . $this->sendmail_cmd, $mode)) )
			{
				$this->error('sendmail() :: Impossible d\'exécuter sendmail');
				return false;
			}
			
			//
			// On envoie les entêtes 
			//
			fputs($sm, $headers . "\n\n");
			
			//
			// Et maintenant le message 
			//
			fputs($sm, $message . "\n");
			
			$code = pclose($sm) >> 8 & 0xFF;
			
			if( $code != 0 )
			{
				$this->error('sendmail() :: Sendmail a retourné le code d\'erreur suivant -> ' . $code);
				return false;
			}
		}
		else
		{
			$this->error('sendmail() :: ' . $this->sendmail_path . ' n\'est pas exécutable');
			return false;
		}
		
		return true;
	}
	
	/**
	 * Retourne l'adresse d'envoi à utiliser dans l'option -f de sendmail 
	 * ou pour la commande MAIL FROM de SMTP car c'est celle ci qui est utilisée 
	 * pour forger l'entête return-path
	 * Nous utiliserons l'adresse email fournie pour le return-path, si cet entête n'est pas vide.
	 * S'il est vide, nous utiliserons l'adresse d'expéditeur fournie. 
	 * Enfin, si celle ci n'a pas été fournie non plus, on utilise la valeur de sendmail_from
	 * 
	 * @access private
	 * @return string
	 */
	function get_return_path()
	{
		if( !empty($this->headers['Return-Path']) )
		{
			$Rpath = $this->headers['Return-Path'];
		}
		else if( !empty($this->from['email']) )
		{
			$Rpath = $this->from['email'];
		}
		else
		{
			$Rpath = @ini_get('sendmail_from');
			
			if( empty($Rpath) )
			{
				//
				// Pas moyen d'obtenir une adresse à utiliser.
				// En dernier ressort, nous utilisons une adresse factice
				//
				$Rpath = 'wamailer@localhost';
			}
		}
		
		return $Rpath;
	}
	
	/**
	 * Renvoie la liste des destinataires
	 * 
	 * @access private
	 * @return mixed
	 */
	function recipients_list()
	{
		if( empty($this->headers['To']) && empty($this->headers['Cc']) )
		{
			$this->headers['To'] = 'Undisclosed-recipients:;';
		}
		
		//
		// Sendmail/qmail/[...] se charge déja de parser les entêtes To, Cc et Bcc éventuels
		// On renvoie un tableau vide
		//
		if( $this->sendmail_mode )
		{
			$address = array();
		}
		
		//
		// Mode smtp, on renvoie les adresses de tous les destinataires et on supprime 
		// l'entête Bcc
		//
		else if( $this->smtp_mode )
		{
			$this->headers['Bcc'] = '';
			
			$address = $this->address['To'];
			$address = array_merge($address, $this->address['Cc']);
			$address = array_merge($address, $this->address['Bcc']);
		}
		else
		{
			//
			// FIX
			// 
			// Si la fonction mail() utilise sendmail, elle rajoute automatiquement un entête To, 
			// or, nous avons déja rajouté un entête To (pour pouvoir personnaliser les adresses), 
			// et sendmail va parser les deux entête To sans distinction. 
			// Résultat, les emails vont être reçus en double ...
			// 
			// Si un serveur smtp est utilisé, la personnalisation des adresses Cc et Bcc ne fonctionne pas
			//
			if( !isset($this->fix_bug_mail) )
			{
				$this->fix_bug_mail = -1;
				
				if( @ini_get('sendmail_path') != '' )
				{
					$this->fix_bug_mail = 1;
				}
				
				//
				// Certains hébergeurs désactivent la fonction ini_get() (sont cons quand même nan ?)
				// Pas grave, on récupère le contenu du phpinfo et on le scan (Bwaahahaa ..)
				//
				else
				{
					ob_start();
					@phpinfo(INFO_CONFIGURATION);
					$phpinfo = strtolower(strip_tags(ob_get_contents()));
					ob_end_clean();
					
					if( !empty($phpinfo) )
					{
						if( !preg_match('/^sendmail_pathno valueno value$/im', $phpinfo) )
						{
							$this->fix_bug_mail = 1;
						}
					}
					else
					{
						//
						// Bon, pas moyen de récupérer la valeur de sendmail_path :/
						// Pour que l'envoi se passe tout de même sans problème, on supprime l'entête To
						// Tant pis pour la personnalisation.
						//
						$this->fix_bug_mail = 0;
					}
				}
			}
			
			//
			// Sendmail/qmail/[...] est utilisé, on renvoie le contenu de l'entête To et on le supprime
			//
			if( $this->fix_bug_mail == 1 )
			{
				$address = '';
				
				if( !empty($this->headers['To']) )
				{
					$address = $this->headers['To'];
					$this->headers['To'] = '';
				}
			}
			else
			{
				$address = ( count($this->address['To']) > 0 ) ? implode(', ', $this->address['To']) : '';
				
				//
				// FIX
				// 
				// La personnalisation telle que "name" <user@domaine.com> ne marche pas
				// pour les entêtes Cc et Bcc si on utilise la fonction mail() et qu'un serveur
				// smtp est utilisé. On supprime donc la personnalisation des entêtes Cc et Bcc
				// 
				// Dans le doute, on remplace également l'entête To (et donc, la personnalisation)
				//
				if( $this->fix_bug_mail == 0 )
				{
					$this->headers['To'] = $address;
				}
				
				if( count($this->address['Cc']) > 0 )
				{
					$this->headers['Cc'] = implode(', ', $this->address['Cc']);
				}
				
				if( count($this->address['Bcc']) > 0 )
				{
					$this->headers['Bcc'] = implode(', ', $this->address['Bcc']);
				}
			}
		}
		
		return $address;
	}
	
	/**
	 * Renvoie les entêtes correspondant au type demandé
	 * 
	 * @param string $type
	 * 
	 * @access private
	 * @return string
	 */
	function make_content_info($type)
	{
		switch( $type )
		{
			case 'mixed':
				$content_info = "Content-Type: multipart/mixed;\n\tboundary=\"" . $this->boundary['part0'][$this->format] . '"';
				break;
			
			case 'related':
				$content_info = "Content-Type: multipart/related;\n\t";
				
				if( $this->format == 3 )
				{
					$content_info .= "type=\"multipart/alternative\";\n\t";
				}
				
				$content_info .= 'boundary="' . $this->boundary['part1'][$this->format] . '"';
			break;
			
			case 3:
				$content_info = "Content-Type: multipart/alternative;\n\tboundary=\"" . $this->boundary['part2'][$this->format] . '"';
				break;
			
			case 2:
				$content_info  = 'Content-Type: text/html; charset="' . $this->charset . "\"\n";
				$content_info .= 'Content-Transfer-Encoding: ' . $this->encoding; 
				break;
			
			case 1:
				$content_info  = 'Content-Type: text/plain; charset=' . $this->charset . "\n";
				$content_info .= 'Content-Transfer-Encoding: ' . $this->encoding;
				break;
			
			default:
				$this->error('make_content_info() :: Type inconnu');
				break;
		}
		
		return $content_info;
	}
	
	/**
	 * Génération du bloc d'en-têtes
	 * 
	 * @access private
	 * @return string
	 */
	function compile_headers()
	{
		if( $this->smtp_mode || $this->sendmail_mode )
		{
			$this->headers['Subject'] = $this->encode_mime_header($this->subject, 'subject');
		}
		else
		{
			$this->headers['Subject'] = '';
		}
		
		//
		// Si la fonction email() est utilisée, les 
		// modifications spécifiques à Online doivent être effectuées.
		//
		if( !$this->smtp_mode && !$this->sendmail_mode && $this->hebergeur == WM_HOST_ONLINE )
		{
			list($account) = explode('@', $this->from['email']);
			$this->from_online = $this->reply_online = $account;
		}
		else if( !empty($this->from['email']) )
		{
			if( empty($this->headers['Return-Path']) )
			{
				$this->set_return_path($this->from['email']);
			}
			else
			{
				$this->headers['Return-Path'] = preg_replace('/<?([^@]+@[^>]+)>?/', '\\1', $this->headers['Return-Path']);
			}
			
			$this->headers['From'] = '';
			if( $this->from['name'] != '' )
			{
				$this->headers['From'] .= $this->encode_mime_header('"' . $this->from['name'] . '"', 'from') . ' ';
			}
			$this->headers['From'] .= '<' . $this->from['email'] . '>';
		}
		
		$this->headers['Date']         = date('D, d M Y H:i:s O', time());
		$this->headers['X-Mailer']     = 'WAmailer/' . $this->version . ' (http://phpcodeur.net)';
		$this->headers['X-AntiAbuse']  = 'Sender IP - ' . $this->sender_ip . '/Server Name - <' . $this->server_from . '>';
		$this->headers['MIME-Version'] = '1.0'; 
		$this->headers['Message-ID']   = '<' . $this->generate_rand_str() . '@' . $this->server_from . '>';
		
		//
		// La rfc2822 conseille de placer certains entêtes dans un certain ordre
		//
		$header_rank = array('Return-Path', 'Date', 'From', 'Subject', 'X-Sender', 'To', 'Cc', 'Bcc', 'Reply-To');
		
		$headers = '';
		foreach( $header_rank AS $name )
		{
			if( empty($this->headers[$name]) )
			{
				continue;
			}
			
			$headers .= $this->word_wrap(sprintf('%s: %s', $name,
				preg_replace('/(?!\x09|\x20)\r?\n/', '', $this->headers[$name]))) . "\n";
		}
		
		foreach( $this->headers AS $name => $body )
		{
			if( in_array($name, $header_rank) || $body == '' )
			{
				continue;
			}
			
			$headers .= $this->word_wrap(sprintf('%s: %s', $name,
				preg_replace('/(?!\x09|\x20)\r?\n/', '', $body))) . "\n";
		}
		
		if( empty($this->compiled_message[$this->format]) )
		{
			$this->boundary['part0'][$this->format] = '-----=_Part0_' . $this->generate_rand_str() . '--';
			$this->boundary['part1'][$this->format] = '-----=_Part1_' . $this->generate_rand_str() . '--';
			$this->boundary['part2'][$this->format] = '-----=_Part2_' . $this->generate_rand_str() . '--';
		}
		
		$total_attach   = count($this->attachfile['path']);
		$total_embedded = count($this->embeddedfile['path']);
		
		//
		// Si des fichiers joints sont présents, ou si des fichiers incorporés sont 
		// présents et que l'email est au format texte brut
		//
		if( $total_attach > 0 || ( $total_embedded > 0 && $this->format == 1 ) )
		{
			$content_info = $this->make_content_info('mixed');
		}
		
		//
		// On ne peut incorporer des fichiers que dans un email html
		//
		else if( $total_embedded > 0 && $this->format > 1 )
		{
			$content_info = $this->make_content_info('related');
		}
		
		//
		// L'email est au format texte brut ou ne contient pas de fichiers joints ou incorporés
		//
		else
		{
			$content_info = $this->make_content_info($this->format);
		}
		
		return $headers . $content_info;
	}
	
	/**
	 * Génération du corps de l'email
	 * 
	 * @access private
	 * @return string
	 */
	function compile_message()
	{
		if( empty($this->compiled_message[$this->format]) )
		{
			$attach_ary   = $this->attachfile;
			$embedded_ary = $this->embeddedfile;
			
			$total_attach   = count($attach_ary['path']);
			$total_embedded = count($embedded_ary['path']);
			
			if( $total_embedded > 0 && $this->format == 1 )
			{
				for( $i = 0; $i < $total_embedded; $i++ )
				{
					$attach_ary['path'][]        = $embedded_ary['path'][$i];
					$attach_ary['name'][]        = $embedded_ary['name'][$i];
					$attach_ary['mimetype'][]    = $embedded_ary['mimetype'][$i];
					$attach_ary['disposition'][] = 'attachment';
					
					$total_attach++;
				}
				
				$total_embedded = 0;
			}
			
			$message = '{WAMAILER_MSG}';
			
			if( $total_embedded > 0 )
			{
				$tmp_msg = $message;
				
				$message  = '--' . $this->boundary['part1'][$this->format] . "\n";
				$message .= $this->make_content_info($this->format);
				$message .= "\n\n";
				$message .= $tmp_msg;
				$message .= "\n\n";
				
				for( $i = 0; $i < $total_embedded; $i++ )
				{
					$message .= $this->insert_attach(
						$embedded_ary['path'][$i],
						$embedded_ary['name'][$i],
						$embedded_ary['mimetype'][$i],
						'',
						$this->boundary['part1'][$this->format],
						TRUE
					);
				}
				
				$message .= '--' . $this->boundary['part1'][$this->format] . "--\n";
			}
			
			if( $total_attach > 0 )
			{
				$tmp_msg = $message;
				
				if( $total_embedded > 0 )
				{
					$content_info = $this->make_content_info('related');
				}
				else
				{
					$content_info = $this->make_content_info($this->format);
				}
				
				$message  = '--' . $this->boundary['part0'][$this->format] . "\n";
				$message .= $content_info;
				$message .= "\n\n";
				$message .= $tmp_msg;
				$message .= "\n\n";
				
				for( $i = 0; $i < $total_attach; $i++ )
				{
					$message .= $this->insert_attach(
						$attach_ary['path'][$i],
						$attach_ary['name'][$i],
						$attach_ary['mimetype'][$i],
						$attach_ary['disposition'][$i],
						$this->boundary['part0'][$this->format],
						FALSE
					);
				}
				
				$message .= '--' . $this->boundary['part0'][$this->format] . "--\n";
			}
			
			if( $this->format == 3 || $total_attach > 0 || $total_embedded > 0 )
			{
				$message = "This is a multi-part message in MIME format.\n\n" . $message;
			}
			
			$this->compiled_message[$this->format] = $message;
		}
		
		if( $this->format == 3 )
		{
			$altbody = $this->replace_tags($this->uncompiled_altmessage[$this->format]);
			$body    = $this->replace_tags($this->uncompiled_message[$this->format]);
			
			$message  = '--' . $this->boundary['part2'][$this->format] . "\n";
			$message .= $this->make_content_info(1);
			$message .= "\n\n";
			$message .= $this->make_encoding($this->encoding, $altbody);
			$message .= "\n\n";
			$message .= '--' . $this->boundary['part2'][$this->format] . "\n";
			$message .= $this->make_content_info(2);
			$message .= "\n\n";
			$message .= $this->make_encoding($this->encoding, $body);
			$message .= "\n\n";
			$message .= '--' . $this->boundary['part2'][$this->format] . "--\n";
		}
		else
		{
			$message = $this->make_encoding(
				$this->encoding,
				$this->replace_tags($this->uncompiled_message[$this->format])
			);
		}
		
		return str_replace('{WAMAILER_MSG}', $message, $this->compiled_message[$this->format]);
	}
	
	/**
	 * @param string $texte
	 * 
	 * @access private
	 * @return string
	 */
	function replace_tags($texte)
	{
		if( count($this->tags) > 0 )
		{
			$keys = $values = array();
			foreach( $this->tags AS $key => $val )
			{
				$keys[]   = '/(?:(%)|(\{))'.$key.'(?(1)%|\})/i';
				$values[] = $val;
			}
			
			$texte = preg_replace($keys, $values, $texte);
		}
		
		return $this->replace_block_tags($texte);
	}
	
	/**
	 * @param string $texte
	 * 
	 * @access private
	 * @return string
	 */
	function replace_block_tags($texte)
	{
		$total_blocks = preg_match_all(
			"/<!-- start_block ([[:alnum:]_-]+) -->(.*?)<!-- end_block \\1 -->([\r\n]+)/is",
			$texte,
			$matches
		);
		
		for( $i = 0; $i < $total_blocks; $i++ )
		{
			$block_name = $matches[1][$i];
			$tmp = '';
			
			if( isset($this->block_tags[$block_name]) && count($this->block_tags[$block_name]) )
			{
				$keys = $values = array();
				foreach( $this->block_tags[$block_name] AS $key => $val )
				{
					$keys[]   = '/(?:(%)|(\{))' . $block_name . '\.' . $key . '(?(1)%|\})/i';
					$values[] = $val;
				}
				
				$tmp = preg_replace($keys, $values, trim($matches[2][$i])) . $matches[3][$i];
			}
			
			$texte = str_replace($matches[0][$i], $tmp, $texte);
		}
		
		return $texte;
	}
	
	/**
	 * @param string  $path         Chemin vers le fichier
	 * @param string  $filename     Nom du fichier
	 * @param string  $mime_type    Type de média du fichier
	 * @param string  $disposition  Disposition
	 * @param string  $boundary     Frontière à utiliser
	 * @param boolean $embedded     Si fichier incorporé, true
	 * 
	 * @access private
	 * @return string
	 */
	function insert_attach($path, $filename, $mime_type, $disposition, $boundary, $embedded)
	{
		if( $mime_type == '' )
		{
			$extension = 'wa';
			if( $dot_pos = strrpos($filename, '.') )
			{
				$extension = strtolower(substr($filename, ($dot_pos + 1)));
			}
			
			$mime_type = $this->mime_type($extension);
		}
		
		$attach  = '--' . $boundary . "\n";
		$attach .= "Content-Type: $mime_type;\n\tname=\"$filename\"\n";
		$attach .= "Content-Transfer-Encoding: base64\n";
		
		if( $embedded )
		{
			$cid = $this->generate_rand_str();
			
			$attach .= 'Content-ID: <' . $cid . '@Wamailer>' . "\n\n";
			
			$this->uncompiled_message[$this->format] = preg_replace(
				'/<(.+?)"cid:' . preg_quote($filename, '/') . '"([^>]*)?>/si',
				'<\\1"cid:' . $cid . '@Wamailer"\\2>',
				$this->uncompiled_message[$this->format]
			);
		}
		else
		{
			$attach .= 'Content-Disposition: ' . $disposition . ";\n\tfilename=\"" . $filename . "\"\n\n";
		}
		
		$attach .= $this->make_encoding('base64', $this->loadfile($path, true)) . "\n";
		
		return $attach;
	}
	
	/**
	 * @access public
	 * @return void
	 */
	function clear_all()
	{
		$this->clear_from();
		$this->clear_address();
		$this->clear_subject();
		$this->clear_message();
		$this->clear_attach();
		
		$this->format    = 1;
		$this->headers   = array();
		$this->msg_error = '';
		$this->statut    = true;
	}
	
	/**
	 * @access public
	 * @return void
	 */
	function clear_from()
	{
		$this->from_online  = '';
		$this->reply_online = '';
		$this->from         = array('email' => '', 'name' => '');
		$this->msg_error    = '';
		$this->statut       = true;
	}
	
	/**
	 * @access public
	 * @return void
	 */
	function clear_address()
	{
		$this->address   = array('To' => array(), 'Cc' => array(), 'Bcc' => array());
		$this->msg_error = '';
		$this->statut    = true;
		
		unset($this->headers['To'], $this->headers['Cc'], $this->headers['Bcc']);
	}
	
	/**
	 * @access public
	 * @return void
	 */
	function clear_subject()
	{
		$this->subject   = '';
		$this->msg_error = '';
		$this->statut    = true;
	}
	
	/**
	 * @access public
	 * @return void
	 */
	function clear_message()
	{
		$this->uncompiled_message    = array();
		$this->uncompiled_altmessage = array();
		$this->compiled_message      = array();
		$this->tags                  = array();
		$this->block_tags            = array();
		$this->boundary              = array('part0' => array(), 'part1' => array(), 'part2' => array());
		$this->msg_error             = '';
		$this->statut                = true;
	}
	
	/**
	 * @access public
	 * @return void
	 */
	function clear_attach()
	{
		$this->attachfile   = array('path' => array(), 'name' => array(), 'mimetype' => array(), 'disposition' => array());
		$this->embeddedfile = array('path' => array(), 'name' => array(), 'mimetype' => array());
		$this->msg_error    = '';
		$this->statut       = true;
	}
	
	/**
	 * @param string $msg_error  Le message d'erreur à afficher si mode debug
	 * 
	 * @access private
	 * @return void
	 */
	function error($msg_error)
	{
		if( $this->debug )
		{
			exit($msg_error);
		}
		
		if( $this->msg_error == '' )
		{
			$this->msg_error = $msg_error;
		}
		
		$this->statut = false;
	}
}// fin de la classe

}
?>