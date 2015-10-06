<?php
//
// Utilisez ce fichier si vous souhaitez (re)créer un fichier de configuration
// valide sans utiliser le script d’installation de Wanewsletter.
//
// Des variables $logs_dir, $stats_dir et $tmp_dir peuvent être ajoutées
// pour faire pointer les répertoires correspondants vers d’autres emplacements
// que ceux par défaut dans data/ (voir fichier common.inc.php pour les détails).
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
//$dsn_opts = [
//];

//
// Exemples pour activer les protocoles SSL/TLS pour la base de données
//
/*
$dsn_opts = [
	# MySQL
	# les options ssl-capath et ssl-cipher peuvent également être fournies.
	# Elles correspondent, avec les autres options ssl-*, aux arguments de
	# la méthode mysqli::ssl_set().
	'ssl'      => true,
	'ssl-ca'   => '/etc/mysql/ssl/ca-cert.pem',
	'ssl-cert' => '/etc/mysql/ssl/server-cert.pem',
	'ssl-key'  => '/etc/mysql/ssl/server-key.pem',
	# PostgreSQL
	'sslmode'  => 'require', # ou autre valeur acceptable par le paramètre sslmode de PostgreSQL
	'sslrootcert' => '/etc/postgresql/ssl/ca-cert.pem',
	'sslcert'  => '/etc/postgresql/ssl/server-cert.pem',
	'sslkey'   => '/etc/postgresql/ssl/server-key.pem',
];
*/
