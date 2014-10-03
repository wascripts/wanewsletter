<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

//
// Version correspondant au code source en place sur le serveur.
// Remplace la constante obsolète WA_VERSION, jadis définie dans le fichier de configuration.
//
define('WANEWSLETTER_VERSION', '2.4-beta2');

//
// identifiant de version des tables du script.
// Doit correspondre à l'entrée 'db_version' dans la configuration, sinon,
// le script invite l'utilisateur à lancer la procédure de mise à jour des tables
//
define('WANEWSLETTER_DB_VERSION', 12);

//
// Mode de débugguage du script 
// 
// 3 - Toutes les erreurs sont affichées à l'écran
// 2 - Toutes les erreurs provenant de variables/fonctions/autres non précédés d'un @ sont affichées à l'écran 
// 1 - Seules les erreurs en rapport avec la base de données sont affichées (le script donne des détails sur l'erreur)
// 0 - Le script affiche simplement un message d'erreur lors de problèmes SQL, sans donner plus de détails 
//
define('DEBUG_MODE', 3);

//
// Pour visualiser le temps d'exécution du script et le nombre de requètes effectuées
//
define('DEV_INFOS', TRUE);
//define('DEV_INFOS', FALSE);

//
// Active/Désactive l'affichage des erreurs proprement dans un bloc html en bas de page
//
define('DISPLAY_ERRORS_IN_BLOCK', TRUE);

//
// Active/Désactive le passage automatique à l'UTF-8 au moment de l'envoi en présence de 
// caractères invalides provenant de Windows-1252 dans les newsletters.
//
// Si cette constante est placée à TRUE, les caractères en cause subiront une transformation
// vers un caractère simple ou composé graphiquement proche (voir la fonction purge_latin1()
// dans le fichier includes/functions.php).
//
define('TRANSLITE_INVALID_CHARS', FALSE);

//
// Format des exportations d'archive
//
define('EXPORT_FORMAT', 'Tar'); // Tar ou Zip (Attention, respecter la casse)

//
// Prise en compte de l'authentification HTTP pour la connexion automatique
//
define('ENABLE_HTTP_AUTHENTICATION', TRUE);


//
// Il est recommandé de ne rien modifier au-delà de cette ligne
//

//
// Codes des messages d'erreur et d'information 
//
define('CRITICAL_ERROR', E_USER_ERROR);
define('ERROR',          E_USER_WARNING);

//
// Formats d'emails 
//
define('FORMAT_TEXTE',    1);
define('FORMAT_HTML',     2);
define('FORMAT_MULTIPLE', 3);

//
// Statut des newsletter 
//
define('STATUS_WRITING', 0);
define('STATUS_STANDBY', 1);
define('STATUS_SENT',    2);
define('STATUS_MODEL',   3);

//
// Statut des abonnés 
//
define('ABO_ACTIF',   1);
define('ABO_INACTIF', 0);

define('SUBSCRIBE_CONFIRMED',     1);
define('SUBSCRIBE_NOT_CONFIRMED', 0);

//
// Niveau des utilisateurs, ne pas modifier !! 
//
define('ADMIN', 2);
define('USER',  1);

//
// divers 
//
define('SUBSCRIBE_NOTIFY_YES', 1);
define('SUBSCRIBE_NOTIFY_NO',  0);
define('UNSUBSCRIBE_NOTIFY_YES', 1);
define('UNSUBSCRIBE_NOTIFY_NO',  0);

define('MAX_IMPORT', 5000);

define('ENGINE_BCC',  1);
define('ENGINE_UNIQ', 2);

define('CONFIRM_ALWAYS', 2);
define('CONFIRM_ONCE',   1);
define('CONFIRM_NONE',   0);

//
// Si nous avons un accés restreint à cause de open_basedir, certains fichiers uploadés 
// devront être déplacés vers le dossier des fichiers temporaires du script pour être 
// accessible en lecture
//
$open_basedir = config_value('open_basedir');
if( !empty($open_basedir) )
{
	define('OPEN_BASEDIR_RESTRICTION', TRUE);
}
else
{
	define('OPEN_BASEDIR_RESTRICTION', FALSE);
}

//
// On vérifie si l'upload est autorisé sur le serveur
//
if( config_status('file_uploads') )
{
	function get_integer_byte_value($size)
	{
		if( preg_match('/^([0-9]+)([KMG])$/i', $size, $match) )
		{
			switch( strtoupper($match[2]) )
			{
				case 'K':
					$size = ($match[1] * 1024);
					break;
				
				case 'M':
					$size = ($match[1] * 1024 * 1024);
					break;
				
				case 'G': // Since php 5.1.0
					$size = ($match[1] * 1024 * 1024 * 1024);
					break;
			}
		}
		else
		{
			$size = intval($size);
		}
		
		return $size;
	}
	
	if( !($filesize = config_value('upload_max_filesize')) )
	{
        $filesize = '2M'; // 2 Méga-Octets
    }
	$upload_max_size = get_integer_byte_value($filesize);
	
    if( $postsize = config_value('post_max_size') )
	{
        $postsize = get_integer_byte_value($postsize);
        if( $postsize < $upload_max_size )
		{
            $upload_max_size = $postsize;
        }
    }
	
	define('FILE_UPLOADS_ON', TRUE);
	define('MAX_FILE_SIZE',   $upload_max_size);
}
else
{
	define('FILE_UPLOADS_ON', FALSE);
	define('MAX_FILE_SIZE',   0);
}

//
// Infos sur l'utilisateur 
//
$user_agent = server_info('HTTP_USER_AGENT');

if( $user_agent != '' )
{
	if( stristr($user_agent, 'win') )
	{
		define('WA_USER_OS', 'win');
	}
	else if( stristr($user_agent, 'mac') )
	{
		define('WA_USER_OS', 'mac');
	}
	else if( stristr($user_agent, 'linux') )
	{
		define('WA_USER_OS', 'linux');
	}
	else
	{
		define('WA_USER_OS', 'other');
	}
	
	if( stristr($user_agent, 'opera') )
	{
		define('WA_USER_BROWSER', 'opera');
	}
	else if( stristr($user_agent, 'msie') )
	{
		define('WA_USER_BROWSER', 'msie');
	}
	else if( stristr($user_agent, 'konqueror') )
	{
		define('WA_USER_BROWSER', 'konqueror');
	}
	else if( stristr($user_agent, 'mozilla') )
	{
		define('WA_USER_BROWSER', 'mozilla');
	}
	else
	{
		define('WA_USER_BROWSER', 'other');
	}
}
else
{
	define('WA_USER_OS',      'other');
	define('WA_USER_BROWSER', 'other');
}

//
// Signature du script pour divers cas de figure (entête X-Mailer dans les emails
// envoyés, entête User-Agent lors des requètes HTTP, etc)
//
define('WA_SIGNATURE', sprintf('Wanewsletter/%s', WANEWSLETTER_VERSION));
define('WA_X_MAILER', WA_SIGNATURE);

//
// Utilisées dans le cadre de la classe de vérification de mise à jour
//
define('WA_DOWNLOAD_PAGE', 'http://phpcodeur.net/wascripts/wanewsletter/telecharger');
define('WA_CHECK_UPDATE_URL', 'http://phpcodeur.net/wascripts/wanewsletter/releases/latest/version');
define('WA_CHECK_UPDATE_CACHE', 'wa-check-update.cache');
define('WA_CHECK_UPDATE_CACHE_TTL', 3600);

