<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

//
// Version correspondant au code source en place sur le serveur.
// Remplace la constante obsolète WA_VERSION, jadis définie dans le fichier de configuration.
//
const WANEWSLETTER_VERSION = '2.4-beta3';

//
// identifiant de version des tables du script.
// Doit correspondre à l'entrée 'db_version' dans la configuration, sinon,
// le script invite l'utilisateur à lancer la procédure de mise à jour des tables
//
const WANEWSLETTER_DB_VERSION = 21;

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


##################################################################
## Il est recommandé de ne rien modifier au-delà de cette ligne ##
##################################################################

//
// Formats d'emails
//
const FORMAT_TEXTE    = 1;
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
const ABO_ACTIF   = 1;
const ABO_INACTIF = 0;

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

const MAX_IMPORT = 10000;

const ENGINE_BCC  = 1;
const ENGINE_UNIQ = 2;

const CONFIRM_ALWAYS = 2;
const CONFIRM_ONCE   = 1;
const CONFIRM_NONE   = 0;

//
// Signature du script pour divers cas de figure (entête X-Mailer dans les emails
// envoyés, entête User-Agent lors des requètes HTTP, etc)
//
const USER_AGENT_SIG  = 'Wanewsletter/%s';// %s est remplacé par la valeur de WANEWSLETTER_VERSION
const X_MAILER_HEADER = USER_AGENT_SIG;

//
// Utilisées dans le cadre de la classe de vérification de mise à jour
//
const WA_DOWNLOAD_PAGE      = 'http://phpcodeur.net/wascripts/wanewsletter/telecharger';
const WA_CHECK_UPDATE_URL   = 'http://phpcodeur.net/wascripts/wanewsletter/releases/latest/version';
const WA_CHECK_UPDATE_CACHE = 'wa-check-update.cache';
const WA_CHECK_UPDATE_CACHE_TTL = 3600;

//
// Déclaration des dossiers et fichiers spéciaux utilisés par le script
//
// TODO fix
define('WA_LOGSDIR',  str_replace('~', WA_ROOTDIR, rtrim($logs_dir, '/')));
define('WA_STATSDIR', str_replace('~', WA_ROOTDIR, rtrim($stats_dir, '/')));
define('WA_TMPDIR',   str_replace('~', WA_ROOTDIR, rtrim($tmp_dir, '/')));

define('WA_LOCKFILE',  WA_TMPDIR . '/liste-%d.lock');

//
// Sécurité des connexions (SMTP, POP, ...)
//
const SECURITY_NONE     = 0;
const SECURITY_STARTTLS = 1;
const SECURITY_FULL_TLS = 2;
