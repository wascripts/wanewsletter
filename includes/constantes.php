<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

//
// Version correspondant au code source en place sur le serveur.
// Remplace la constante obsolète WA_VERSION, jadis définie dans le fichier de configuration.
//
const WANEWSLETTER_VERSION = '3.0-beta2';

//
// identifiant de version des tables du script.
// Doit correspondre à l'entrée 'db_version' dans la configuration, sinon,
// le script invite l'utilisateur à lancer la procédure de mise à jour des tables
//
const WANEWSLETTER_DB_VERSION = 26;

//
// Modes de débogage du script
// Sauf en mode silencieux, le script affiche aussi des informations
// complémentaires en bas de page (durée d'exécution, nbre de requètes SQL,
// mémoire utilisée, ...)
//
// Le script annonce les erreurs critiques, sans donner de détails
const DEBUG_LEVEL_QUIET  = 1;
// Le script affiche les erreurs PHP et SQL en donnant des détails
const DEBUG_LEVEL_NORMAL = 2;
// Le script affiche aussi les erreurs non inclues dans le niveau d'erreurs PHP
// configuré par error_reporting() ou masquées avec l'opérateur @
const DEBUG_LEVEL_ALL    = 3;

//
// Configure le niveau de débogage souhaité
//
const DEBUG_MODE = DEBUG_LEVEL_NORMAL;

//
// Active/Désactive l'affichage des messages d'erreur en pied de page.
// Si false, les erreurs sont affichées dès qu'elles sont traitées.
//
const DISPLAY_ERRORS_IN_LOG = true;

//
// Active/Désactive l’enregistrement des erreurs dans un fichier de log.
//
// Le fichier de log spécifié dans la configuration de PHP est utilisé par
// défaut par la fonction error_log().
// Pour utiliser un autre fichier de log, indiquez son emplacement dans la
// constante suivante (par exemple 'debug.log').
// Si le chemin est relatif, il sera préfixé par la valeur de la constante
// WA_LOGSDIR (voir load_config_file() dans includes/functions.php).
//
// Note : Toutes les erreurs sont stockées, sans tenir compte de DEBUG_MODE.
// De plus, la taille du fichier de log n’est pas limitée !
//
const DEBUG_LOG_ENABLED = false;
const DEBUG_LOG_FILE    = 'debug.log';

//
// Signature du script pour divers cas de figure (entête X-Mailer dans les emails
// envoyés, entête User-Agent lors des requètes HTTP, etc)
//
const USER_AGENT_SIG  = 'Wanewsletter/%s';// %s est remplacé par la valeur de WANEWSLETTER_VERSION
const X_MAILER_HEADER = USER_AGENT_SIG;

// Format par défaut des dates
const DEFAULT_DATE_FORMAT = 'd F Y H:i';

##################################################################
## Il est recommandé de ne rien modifier au-delà de cette ligne ##
##################################################################

//
// Formats d'emails
//
const FORMAT_TEXT     = 1;
const FORMAT_HTML     = 2;
const FORMAT_MULTIPLE = 3;

//
// Statut des newsletter
//
const STATUS_WRITING = 0;
const STATUS_STANDBY = 1;
const STATUS_SENT    = 2;
const STATUS_MODEL   = 3;

//
// Statut des abonnés
//
const ABO_ACTIVE   = 1;
const ABO_INACTIVE = 0;

const SUBSCRIBE_CONFIRMED     = 1;
const SUBSCRIBE_NOT_CONFIRMED = 0;

//
// Niveau des utilisateurs, ne pas modifier !!
//
const ADMIN_LEVEL = 2;
const USER_LEVEL  = 1;

//
// divers
//
const SUBSCRIBE_NOTIFY_YES   = 1;
const SUBSCRIBE_NOTIFY_NO    = 0;
const UNSUBSCRIBE_NOTIFY_YES = 1;
const UNSUBSCRIBE_NOTIFY_NO  = 0;
const HTML_EDITOR_YES        = 1;
const HTML_EDITOR_NO         = 0;

const MAX_IMPORT = 10000;

const ENGINE_BCC  = 1;
const ENGINE_UNIQ = 2;

const CONFIRM_ALWAYS = 2;
const CONFIRM_ONCE   = 1;
const CONFIRM_NONE   = 0;

//
// Utilisées dans le cadre de la classe de vérification de mise à jour
//
const DOWNLOAD_PAGE      = 'https://phpcodeur.net/wascripts/wanewsletter/telecharger';
// Le serveur renvoie le tag de la dernière version stable si WANEWSLETTER_VERSION
// contient un numéro en x.y.z, ou le tag de la dernière version non stable, si
// WANEWSLETTER_VERSION contient -dev, -alpha, -beta ou -rc.
// Vous pouvez forcer l’une ou l’autre réponse en ajoutant le paramètre d’url
// 'channel' avec la valeur 'stable' ou 'unstable' dans l’url suivante.
const CHECK_UPDATE_URL   = 'https://phpcodeur.net/wascripts/wanewsletter/releases/latest/version';
const CHECK_UPDATE_CACHE = 'wa-check-update.cache';
const CHECK_UPDATE_CACHE_TTL = 3600;

//
// Sécurité des connexions (SMTP, POP, ...)
//
const SECURITY_NONE     = 0;
const SECURITY_STARTTLS = 1;
const SECURITY_FULL_TLS = 2;
