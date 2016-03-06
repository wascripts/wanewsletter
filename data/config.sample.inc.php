<?php
//
// Utilisez ce fichier si vous souhaitez (re)créer un fichier de configuration
// valide sans utiliser le script d’installation de Wanewsletter.
//
//$dsn = "<engine>://<username>:<password>@<host>:<port>/<database>";
// exemple de DSN pour MySQL
//$dsn = 'mysql://username:password@localhost/dbname?charset=utf8';
// exemple de DSN pour SQLite
//$dsn = 'sqlite:/path/to/db/wanewsletter.sqlite';
$prefixe = 'wa_';

//
// Des paramètres de configuration additionnels peuvent être fournis
// à l'aide du tableau suivant.
//
$nl_config = [];

//
// Des entrées 'logs_dir', 'stats_dir' ou 'tmp_dir' peuvent être paramètrées
// afin d’utiliser d’autres répertoires que ceux par défaut dans data/.
// Si un chemin commence par un tilde (~), celui-ci sera remplacé par
// le chemin vers le répertoire d’installation de wanewsletter.
// Voir fonction load_config() dans includes/functions.php
//
#$nl_config['logs_dir']  = '/path/to/logs_dir';
#$nl_config['stats_dir'] = '/path/to/stats_dir';
#$nl_config['tmp_dir']   = '/path/to/tmp_dir';

//
// Configuration d'une connexion SSL/TLS à la base de données
//

// MySQL
// les options ssl-capath et ssl-cipher peuvent également être fournies.
// Elles correspondent, avec les autres options ssl-*, aux arguments de
// la méthode mysqli::ssl_set().
#$nl_config['db']['ssl']         = true;
#$nl_config['db']['ssl-ca']      = '/path/to/mysql-ca.crt';
#$nl_config['db']['ssl-cert']    = '/path/to/client.crt';
#$nl_config['db']['ssl-key']     = '/path/to/client.key';

// PostgreSQL
#$nl_config['db']['sslmode']     = 'require'; # ou autre valeur acceptable par le paramètre sslmode de PostgreSQL
#$nl_config['db']['sslrootcert'] = '/path/to/postgres-ca.crt';
#$nl_config['db']['sslcert']     = '/path/to/client.crt';
#$nl_config['db']['sslkey']      = '/path/to/client.key';
