<?php
//
// Utilisez ce fichier si vous souhaitez (re)créer un fichier de configuration
// valide sans utiliser le script d’installation de Wanewsletter.
//
// Des variables $logs_dir, $stats_dir et $tmp_dir peuvent être ajoutées
// pour faire pointer les répertoires correspondants vers d’autres emplacements
// que ceux par défaut dans data/ (voir fonction load_config() dans
// includes/functions.php pour les détails).
//
//$dsn = "<engine>://<username>:<password>@<host>:<port>/<database>";
// exemple de DSN pour MySQL
//$dsn = 'mysql://username:password@localhost/dbname?charset=utf8';
// exemple de DSN pour SQLite
//$dsn = 'sqlite:/path/to/db/wanewsletter.sqlite';
$prefixe = 'wa_';

//
// D'autres options peuvent être fournies à l'aide du tableau suivant lorsque
// cela s'avère plus pratique de cette manière plutôt qu'en les passant sous
// forme de paramètres dans le DSN.
//
$dsn_opts = [];

//
// Exemples pour activer les protocoles SSL/TLS pour la base de données
//

// MySQL
// les options ssl-capath et ssl-cipher peuvent également être fournies.
// Elles correspondent, avec les autres options ssl-*, aux arguments de
// la méthode mysqli::ssl_set().
#$dsn_opts['ssl']      = true;
#$dsn_opts['ssl-ca']   = '/path/to/mysql-ca.crt';
#$dsn_opts['ssl-cert'] = '/path/to/client.crt';
#$dsn_opts['ssl-key']  = '/path/to/client.key';

// PostgreSQL
#$dsn_opts['sslmode']     = 'require'; # ou autre valeur acceptable par le paramètre sslmode de PostgreSQL
#$dsn_opts['sslrootcert'] = '/path/to/postgres-ca.crt';
#$dsn_opts['sslcert']     = '/path/to/client.crt';
#$dsn_opts['sslkey']      = '/path/to/client.key';
