#
# Schéma des tables de WAnewsletter 2.2.x
# MySQL 3.x et MySQL 4.x
#
# 
# 08 octobre 2003 - Bobe
#


#
# Structure de la table "wa_abo_liste"
#
CREATE TABLE `wa_abo_liste` (
	`abo_id` mediumint(8) unsigned NOT NULL default '0',
	`liste_id` tinyint(3) NOT NULL default '0',
	`format` tinyint(1) NOT NULL default '0',
	`send` tinyint(1) NOT NULL default '0',
	PRIMARY KEY (`abo_id`, `liste_id`)
) TYPE=MyISAM;


#
# Structure de la table "wa_abonnes"
#
CREATE TABLE `wa_abonnes` (
	`abo_id` mediumint(8) unsigned NOT NULL auto_increment,
	`abo_pseudo` varchar(30) NOT NULL default '',
	`abo_pwd` varchar(32) NOT NULL default '',
	`abo_email` varchar(255) NOT NULL default '',
	`abo_lang` varchar(30) NOT NULL default '',
	`abo_register_key` varchar(32) NOT NULL default '',
	`abo_register_date` int(11) NOT NULL default '0',
	`abo_status` tinyint(1) NOT NULL default '0',
	PRIMARY KEY (`abo_id`),
	KEY `abo_status` (`abo_status`)
) TYPE=MyISAM;


#
# Structure de la table "wa_admin"
#
CREATE TABLE `wa_admin` (
	`admin_id` tinyint(3) NOT NULL auto_increment,
	`admin_login` varchar(30) NOT NULL default '',
	`admin_pwd` varchar(32) NOT NULL default '',
	`admin_email` varchar(255) NOT NULL default '',
	`admin_lang` varchar(30) NOT NULL default '',
	`admin_dateformat` varchar(20) NOT NULL default '',
	`admin_level` tinyint(1) NOT NULL default '1',
	`email_new_inscrit` tinyint(1) NOT NULL default '0',
	PRIMARY KEY (`admin_id`)
) TYPE=MyISAM;


#
# Structure de la table "wa_auth_admin"
#
CREATE TABLE `wa_auth_admin` (
	`admin_id` tinyint(3) NOT NULL default '0',
	`liste_id` tinyint(3) NOT NULL default '0',
	`auth_view` tinyint(1) NOT NULL default '0',
	`auth_edit` tinyint(1) NOT NULL default '0',
	`auth_del` tinyint(1) NOT NULL default '0',
	`auth_send` tinyint(1) NOT NULL default '0',
	`auth_import` tinyint(1) NOT NULL default '0',
	`auth_export` tinyint(1) NOT NULL default '0',
	`auth_ban` tinyint(1) NOT NULL default '0',
	`auth_attach` tinyint(1) NOT NULL default '0',
	KEY `admin_id` (`admin_id`)
) TYPE=MyISAM;


#
# Structure de la table "wa_ban_list"
#
CREATE TABLE `wa_ban_list` (
	`ban_id` smallint(5) unsigned NOT NULL auto_increment,
	`liste_id` tinyint(3) NOT NULL default '0',
	`ban_email` varchar(250) NOT NULL default '',
	PRIMARY KEY (`ban_id`)
) TYPE=MyISAM;


#
# Structure de la table "wa_config"
#
CREATE TABLE `wa_config` (
	`sitename` varchar(100) NOT NULL default '',
	`urlsite` varchar(100) NOT NULL default '',
	`path` varchar(100) NOT NULL default '',
	`hebergeur` tinyint(1) NOT NULL default '0',
	`date_format` varchar(20) NOT NULL default '',
	`session_length` smallint(5) unsigned NOT NULL default '0',
	`language` varchar(30) NOT NULL default '',
	`cookie_name` varchar(100) NOT NULL default '',
	`cookie_path` varchar(100) NOT NULL default '',
	`upload_path` varchar(100) NOT NULL default '',
	`max_filesize` mediumint(8) unsigned NOT NULL default '0',
	`use_ftp` tinyint(1) NOT NULL default '0',
	`ftp_server` varchar(50) NOT NULL default '',
	`ftp_port` smallint(5) NOT NULL default '21',
	`ftp_pasv` tinyint(1) NOT NULL default '0',
	`ftp_path` varchar(100) NOT NULL default '',
	`ftp_user` varchar(30) NOT NULL default '',
	`ftp_pass` varchar(30) NOT NULL default '',
	`engine_send` tinyint(1) NOT NULL default '0',
	`emails_sended` smallint(5) NOT NULL default '0',
	`use_smtp` tinyint(1) NOT NULL default '0',
	`smtp_host` varchar(100) NOT NULL default '',
	`smtp_port` smallint(5) NOT NULL default '25',
	`smtp_user` varchar(50) NOT NULL default '',
	`smtp_pass` varchar(50) NOT NULL default '',
	`disable_stats` tinyint(1) NOT NULL default '0',
	`gd_img_type` varchar(5) NOT NULL default '',
	`check_email_mx` tinyint(1) NOT NULL default '0',
	`enable_profil_cp` tinyint(1) NOT NULL default '0',
	`mailing_startdate` int(11) NOT NULL default '0',
	`version` varchar(10) NOT NULL default ''
) TYPE=MyISAM;


#
# Structure de la table "wa_forbidden_ext"
#
CREATE TABLE `wa_forbidden_ext` (
	`fe_id` smallint(5) unsigned NOT NULL auto_increment,
	`liste_id` tinyint(3) NOT NULL default '0',
	`fe_ext` varchar(10) NOT NULL default '',
	PRIMARY KEY (`fe_id`)
) TYPE=MyISAM;


#
# Structure de la table "wa_joined_files"
#
CREATE TABLE `wa_joined_files` (
	`file_id` mediumint(8) unsigned NOT NULL auto_increment,
	`file_real_name` varchar(200) NOT NULL default '',
	`file_physical_name` varchar(200) NOT NULL default '',
	`file_size` mediumint(8) unsigned NOT NULL default '0',
	`file_mimetype` varchar(100) NOT NULL default '',
	PRIMARY KEY (`file_id`)
) TYPE=MyISAM;


#
# Structure de la table "wa_liste"
#
CREATE TABLE `wa_liste` (
	`liste_id` tinyint(3) NOT NULL auto_increment,
	`liste_name` varchar(100) NOT NULL default '',
	`liste_format` tinyint(1) NOT NULL default '1',
	`sender_email` varchar(250) NOT NULL default '',
	`return_email` varchar(250) NOT NULL default '',
	`confirm_subscribe` tinyint(1) NOT NULL default '0',
	`limitevalidate` tinyint(3) NOT NULL default '3',
	`form_url` varchar(255) NOT NULL default '',
	`liste_sig` text NOT NULL,
	`auto_purge` tinyint(1) NOT NULL default '0',
	`purge_freq` tinyint(3) unsigned NOT NULL default '0',
	`purge_next` int(11) NOT NULL default '0',
	`liste_startdate` int(11) NOT NULL default '0',
	`liste_alias` varchar(250) NOT NULL default '',
	`liste_numlogs` smallint(5) NOT NULL default '0',
	`use_cron` tinyint(1) NOT NULL default '0',
	`pop_host` varchar(100) NOT NULL default '',
	`pop_port` smallint(5) NOT NULL default '110',
	`pop_user` varchar(50) NOT NULL default '',
	`pop_pass` varchar(50) NOT NULL default '',
	PRIMARY KEY (`liste_id`)
) TYPE=MyISAM;


#
# Structure de la table "wa_log"
#
CREATE TABLE `wa_log` (
	`log_id` smallint(5) unsigned NOT NULL auto_increment,
	`liste_id` tinyint(3) NOT NULL default '0',
	`log_subject` varchar(100) NOT NULL default '',
	`log_body_html` mediumtext NOT NULL,
	`log_body_text` mediumtext NOT NULL,
	`log_date` int(11) NOT NULL default '0',
	`log_status` tinyint(1) NOT NULL default '0',
	`log_numdest` smallint(5) NOT NULL default '0',
	PRIMARY KEY (`log_id`),
	KEY `liste_id` (`liste_id`),
	KEY `log_status` (`log_status`)
) TYPE=MyISAM;


#
# Structure de la table "wa_log_files"
#
CREATE TABLE `wa_log_files` (
	`log_id` smallint(5) unsigned NOT NULL default '0',
	`file_id` mediumint(8) unsigned NOT NULL default '0',
	PRIMARY KEY (`log_id`, `file_id`)
) TYPE=MyISAM;


#
# Structure de la table "wa_session"
#
CREATE TABLE `wa_session` (
	`session_id` char(32) NOT NULL default '',
	`admin_id` tinyint(3) NOT NULL default '0',
	`session_start` int(11) NOT NULL default '0',
	`session_time` int(11) NOT NULL default '0',
	`session_ip` char(8) NOT NULL default '',
	`session_liste` tinyint(3) NOT NULL default '0',
	PRIMARY KEY (`session_id`)
) TYPE=HEAP;
