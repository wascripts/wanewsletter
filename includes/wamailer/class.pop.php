<?php
/**
 * Copyright (c) 2002-2010 Aurélien Maille
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
 * @version 2.5
 */

if( !defined('CLASS_POP_INC') )
{

define('CLASS_POP_INC', true);

/**
 * Classe de connexion et consultation de serveur POP
 * 
 * Les sources qui m'ont bien aidées :
 * 
 * @link http://www.interpc.fr/mapage/billaud/telmail.htm
 * @link http://www.devshed.com/Server_Side/PHP/SocketProgramming/page8.html
 * @link http://www.commentcamarche.net/internet/smtp.php3
 * @link http://abcdrfc.free.fr/
 * 
 * Toutes les commandes de connexion et de dialogue avec le serveur sont
 * détaillées dans la RFC 1939.
 * 
 * @link http://abcdrfc.free.fr/rfc-vf/rfc1939.html (français)
 * @link http://www.rfc-editor.org/rfc/rfc1939.txt (anglais)
 * 
 * @access public
 */
class Pop {
	
	/**
	 * Identifiant de connexion
	 * 
	 * @var resource
	 * @access private
	 */
	var $connect_id     = NULL; 
	
	/**
	 * Nom ou IP du serveur pop à contacter
	 * 
	 * @var string
	 * @access public
	 */
	var $pop_server     = ''; 
	
	/**
	 * Port d'accés (en général, 110)
	 * 
	 * @var integer
	 * @access public
	 */
	var $pop_port       = 110; 
	
	/**
	 * Nom d'utilisateur du compte
	 * 
	 * @var string
	 * @access public
	 */
	var $pop_user       = ''; 
	
	/**
	 * Mot de passe d'accés au compte
	 * 
	 * @var string
	 * @access public
	 */
	var $pop_pass       = ''; 
	
	/**
	 * Dernière réponse envoyée par le serveur
	 * 
	 * @var string
	 * @access private
	 */
	var $reponse        = ''; 
	
	/**
	 * Tableau contenant les données des emails lus
	 * 
	 * @var string
	 * @access private
	 */
	var $contents       = array(); 
	
	/**
	 * Durée maximale d'une tentative de connexion
	 * 
	 * @var string
	 * @access public
	 */
	var $timeout        = 3; 
	
	/**
	 * Log contenant le dialogue avec le serveur POP
	 * 
	 * @var string
	 * @access public
	 */
	var $log            = ''; 
	
	/**
	 * Variable contenant le dernier message d'erreur
	 * 
	 * @var string
	 * @access public
	 */
	var $msg_error      = ''; 
	
	/**
	 * Debug mode activé/désactivé. 
	 * Si activé, le dialogue avec le serveur s'affiche à l'écran, une éventuelle erreur stoppe le script
	 * 
	 * @var boolean
	 * @access public
	 */
	var $debug          = FALSE;
	
	/**
	 * Sauvegarde du log du dialogue avec le serveur pop dans un fichier texte. 
	 *
	 * @var boolean
	 * @access public
	 */
	var $save_log       = FALSE;
	
	/**
	 * Écraser les données présentes dans le fichier log si celui ci est présent
	 *
	 * @var boolean
	 * @access public
	 */
	var $erase_log      = FALSE;
	
	/**
	 * Chemin de stockage du fichier log
	 *
	 * @var string
	 * @access public
	 */
	var $filelog        = './log_pop.txt';
	
	/**
	 * Si l'argument vaut TRUE, la connexion est établie automatiquement avec les paramètres par défaut 
	 * de la classe. (On suppose qu'ils ont été préalablement remplacés par les bons paramètres)
	 * 
	 * @param boolean $auto_connect  TRUE pour établir la connexion à l'instanciation de la classe
	 * 
	 * @return void
	 */
	function Pop($auto_connect = false)
	{
		if( $auto_connect )
		{
			$this->connect($this->pop_server, $this->pop_port, $this->pop_user, $this->pop_pass);
		}
	}
	
	/**
	 * Etablit la connexion au serveur POP et effectue l'identification
	 * 
	 * @param string  $pop_server    Nom ou IP du serveur
	 * @param integer $pop_port      Port d'accés au serveur POP
	 * @param string  $pop_user      Nom d'utilisateur du compte
	 * @param string  $pop_pass      Mot de passe du compte
	 * 
	 * @access public
	 * @return boolean
	 */
	function connect($pop_server = '', $pop_port = 110, $pop_user = '', $pop_pass = '')
	{
		$this->pop_server = ( $pop_server != '' ) ? $pop_server : $this->pop_server;
		$this->pop_port   = ( $pop_port > 0 ) ? $pop_port : $this->pop_port;
		$this->pop_user   = ( $pop_user != '' ) ? $pop_user : $this->pop_user;
		$this->pop_pass   = ( $pop_pass != '' ) ? $pop_pass : $this->pop_pass;
		
		$this->reponse  = $this->log = $this->msg_error = '';
		$this->contents = array();
		
		//
		// Ouverture de la connexion au serveur POP
		//
		if( !($this->connect_id = @fsockopen($this->pop_server, $this->pop_port, $errno, $errstr, $this->timeout)) )
		{
			$this->error("connect_to_pop() :: Echec lors de la connexion au serveur pop : $errno $errstr");
			return false;
		}
		
		if( !$this->get_reponse() )
		{
			return false;
		}
		
		//
		// Identification
		//
		$this->put_data('USER ' . $this->pop_user);
		if( !$this->get_reponse() )
		{
			return false;
		}
		
		$this->put_data('PASS ' . $this->pop_pass);
		if( !$this->get_reponse() )
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
	 * Récupère la réponse du serveur
	 * 
	 * @access private
	 * @return boolean
	 */
	function get_reponse()
	{
		$this->reponse = fgets($this->connect_id, 150);
		
		if( $this->debug )
		{
			echo htmlentities($this->reponse) . '<br />';
			flush();
		}
		
		$this->log .= $this->reponse;
		
		if( !(substr($this->reponse, 0, 3) == '+OK') )
		{
			$this->error('send_data() :: ' . htmlentities($this->reponse));
			return false;
		}
		else
		{
			return true;
		}
	}
	
	/**
	 * Commande STAT
	 * Renvoie le nombre de messages présent et la taille totale (en octets)
	 * 
	 * @access public
	 * @return array
	 */
	function stat_box()
	{
		$this->put_data('STAT');
		if( !$this->get_reponse() )
		{
			return false;
		}
		
		list(, $total_msg, $total_size) = explode(' ', $this->reponse);
		
		return array('total_msg' => $total_msg, 'total_size' => $total_size);
	}
	
	/**
	 * Commande LIST
	 * Renvoie un tableau avec leur numéro en index et leur taille pour valeur
	 * Si un numéro de message est donné, sa taille sera renvoyée
	 * 
	 * @param integer $num  Numéro du message
	 * 
	 * @access public
	 * @return mixed
	 */
	function list_mail($num = 0)
	{
		$msg_send = 'LIST';
		if( $num > 0 )
		{
			$msg_send .= ' ' . $num;
		}
		
		$this->put_data($msg_send);
		if( !$this->get_reponse() )
		{
			return false;
		}
		
		if( $num == 0 )
		{
			$list = array();
			
			do
			{
				$Tmp = fgets($this->connect_id, 150); 
				
				if( $this->debug )
				{
					echo $Tmp . '<br />';
				}
				
				if( substr($Tmp, 0, 1) != '.' )
				{
					list($mail_id, $mail_size) = explode(' ', $Tmp);
					$list[$mail_id] = $mail_size;
				}
			}
			while( substr($Tmp, 0, 1) != '.' );
			
			return $list;
		}
		else
		{
			list(,, $mail_size) = explode(' ', $this->reponse);
			
			return $mail_size;
		}
	}
	
	/**
	 * Commande RETR/TOP
	 * Renvoie un tableau avec leur numéro en index et leur taille pour valeur
	 * 
	 * @param integer $num       Numéro du message
	 * @param integer $max_line  Nombre maximal de ligne à renvoyer (par défaut, tout le message)
	 * 
	 * @access public
	 * @return boolean
	 */
	function read_mail($num, $max_line = 0)
	{
		if( !$max_line )
		{
			$msg_send = 'RETR ' . $num;
		}
		else
		{
			$msg_send = 'TOP ' . $num . ' ' . $max_line;
		}
		
		$this->put_data($msg_send);
		if( !$this->get_reponse() )
		{
			return false;
		}
		
		$output = '';
		
		do
		{
			$Tmp = fgets($this->connect_id, 150);
			
			if( $this->debug )
			{
				echo nl2br(htmlentities($Tmp)) . '<br />';
			}
			
			if( substr($Tmp, 0, 1) != '.' )
			{
				$output .= $Tmp;
			}
		}
		while( substr($Tmp, 0, 1) != '.' );
		
		$output = preg_replace("/\r\n?/", "\n", $output);
		
		list($headers, $message) = explode("\n\n", $output, 2);
		
		$this->contents[$num]['headers'] = trim(preg_replace("/\n( |\t)+/", ' ', $headers));
		$this->contents[$num]['message'] = trim($message);
		
		return true;
	}
	
	/**
	 * Récupère les entêtes de l'email spécifié par $num et renvoi un tableau avec le 
	 * nom des entêtes et leur valeur
	 * 
	 * @param string $str
	 * 
	 * @access public
	 * @return mixed
	 */
	function parse_headers($str)
	{
		if( is_numeric($str) )
		{
			if( !isset($this->contents[$str]['headers']) )
			{
				if( !$this->read_mail($str) )
				{
					return false;
				}
			}
			
			$str = $this->contents[$str]['headers'];
		}
		
		$headers = array();
		
		$lines = explode("\n", $str);
		for( $i = 0; $i < count($lines); $i++ )
		{
			list($name, $value) = explode(':', $lines[$i], 2);
			
			$name = strtolower($name);
			$headers[$name] = $this->decode_mime_header($value);
		}
		
		return $headers;
	}
	
	/**
	 * @param string $str
	 * 
	 * @access public
	 * @return array
	 */
	function infos_header($str)
	{
		$total = preg_match_all("/([^ =]+)=\"?([^\" ]+)/", $str, $matches);
		
		$infos = array();
		for( $i = 0; $i < $total; $i++ )
		{
			$infos[strtolower($matches[1][$i])] = $matches[2][$i];
		}
		
		return $infos;
	}
	
	/**
	 * Décode l'entête donné s'il est encodé
	 * 
	 * @param string $str
	 * 
	 * @access private
	 * @return string
	 */
	function decode_mime_header($str)
	{
		//
		// On vérifie si l'entête est encodé en base64 ou en quoted-printable, et on
		// le décode si besoin est.
		//
		$total = preg_match_all('/=\?[^?]+\?(Q|q|B|b)\?([^?]+)\?\=/', $str, $matches);
		
		for( $i = 0; $i < $total; $i++ )
		{
			if( $matches[1][$i] == 'Q' || $matches[1][$i] == 'q' )
			{
				$tmp = preg_replace('/=([a-zA-Z0-9]{2})/e', 'chr(ord("\\x\\1"));', $matches[2][$i]);
				$tmp = str_replace('_', ' ', $tmp);
			}
			else
			{
				$tmp = base64_decode($matches[2][$i]);
			}
			
			$str = str_replace($matches[0][$i], $tmp, $str);
		}
		
		return trim($str); 
	}
	
	/**
	 * Parse l'email demandé et renvoie des informations sur les fichiers joints éventuels
	 * Retourne un tableau contenant les données (nom, encodage, données du fichier ..) sur les fichiers joints
	 * ou false si aucun fichier joint n'est trouvé ou que l'email correspondant à $num n'existe pas.
	 * 
	 * @param integer $num  Numéro de l'email à parser
	 * 
	 * @access public
	 * @status experimental
	 * @return mixed
	 */
	function extract_files($num)
	{
		if( !isset($this->contents[$num]) )
		{
			if( !$this->read_mail($num) )
			{
				return false;
			}
		}
		
		$headers = $this->parse_headers($this->contents[$num]['headers']);
		$message = $this->contents[$num]['message'];
		
		//
		// On vérifie si le message comporte plusieurs parties
		//
		if( !isset($headers['content-type']) || !stristr($headers['content-type'], 'multipart') )
		{
			return false;
		}
		
		$infos = $this->infos_header($headers['content-type']);
		
		$boundary = $infos['boundary'];
		$parts    = array();
		$files    = array();
		$lines    = explode("\n", $message);
		$offset   = 0;
		
		for( $i = 0; $i < count($lines); $i++ )
		{
			if( strstr($lines[$i], $infos['boundary']) )
			{
				$offset         = sizeof($parts);
				$parts[$offset] = '';
				
				if( isset($parts[$offset - 1]) )
				{
					preg_match("/^(.+?)\n\n(.*?)$/s", trim($parts[$offset - 1]), $match);
					
					$local_headers = trim(preg_replace("/\n( |\t)+/", ' ', $match[1]));
					$local_message = trim($match[2]);
					
					$local_headers = $this->parse_headers($local_headers);
					
					$content_type = $this->infos_header($local_headers['content-type']);
					if( isset($local_headers['content-disposition']) )
					{
						$content_disposition = $this->infos_header($local_headers['content-disposition']);
					}
					
					if( !empty($content_type['name']) || !empty($content_disposition['filename']) )
					{
						$pos = sizeof($files);
						
						$files[$pos]['filename'] = ( !empty($content_type['name']) ) ? $content_type['name'] : $content_disposition['filename'];
						$files[$pos]['encoding'] = $local_headers['content-transfer-encoding'];
						$files[$pos]['data']     = base64_decode($local_message);
						$files[$pos]['filesize'] = strlen($files[$pos]['data']);
						$files[$pos]['filetype'] = substr($local_headers['content-type'], 0, strpos($local_headers['content-type'], ';'));
					}
				}
				
				continue;
			}
			
			if( isset($parts[$offset]) )
			{
				$parts[$offset] .= $lines[$i] . "\n";
			}
		}
		
		return $files;
	}
	
	/**
	 * Commande DELE
	 * Demande au serveur d'effacer le message correspondant au numéro donné
	 * 
	 * @param integer $num  Numéro du message
	 * 
	 * @access public
	 * @return boolean
	 */
	function delete_mail($num)
	{
		$this->put_data('DELE ' . $num);
		
		return $this->get_reponse();
	}
	
	/**
	 * Commande RSET
	 * Annule les dernières commandes (effacement ..)
	 * 
	 * @access public
	 * @return boolean
	 */
	function reset()
	{
		$this->put_data('STAT');
		
		return $this->get_reponse();
	}
	
	/**
	 * Commande QUIT
	 * Ferme la connexion au serveur
	 * 
	 * @access public
	 * @return void
	 */
	function quit()
	{
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
				$log  = 'Connexion au serveur ' . $this->pop_server . ' :: ' . date('d/M/Y H:i:s');
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