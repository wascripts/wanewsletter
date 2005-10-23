/*
  Schéma des tables de WAnewsletter 2.2.x
  PostgreSQL
 
  22 février 2004 - Bobe
*/

CREATE SEQUENCE wa_abonnes_id_seq start 1 increment 1 maxvalue 2147483647 minvalue 1 cache 1; 
CREATE SEQUENCE wa_admin_id_seq start 1 increment 1 maxvalue 2147483647 minvalue 1 cache 1;
CREATE SEQUENCE wa_ban_id_seq start 1 increment 1 maxvalue 2147483647 minvalue 1 cache 1;
CREATE SEQUENCE wa_forbidden_ext_id_seq start 1 increment 1 maxvalue 2147483647 minvalue 1 cache 1;
CREATE SEQUENCE wa_joined_files_id_seq start 1 increment 1 maxvalue 2147483647 minvalue 1 cache 1;
CREATE SEQUENCE wa_liste_id_seq start 1 increment 1 maxvalue 2147483647 minvalue 1 cache 1;
CREATE SEQUENCE wa_log_id_seq start 1 increment 1 maxvalue 2147483647 minvalue 1 cache 1;

/*
  Structure de la table "wa_abo_liste"
*/
CREATE TABLE wa_abo_liste (
	abo_id int4 DEFAULT '0' NOT NULL,
	liste_id int4 DEFAULT '0' NOT NULL,
	format int DEFAULT '0' NOT NULL,
	send int DEFAULT '0' NOT NULL,
	CONSTRAINT wa_abo_liste_pkey PRIMARY KEY (abo_id, liste_id)
);

/*
  Structure de la table "wa_abonnes"
*/
CREATE TABLE wa_abonnes (
	abo_id int4 DEFAULT nextval('wa_abonnes_id_seq'::text) NOT NULL,
	abo_pseudo varchar(30) NOT NULL default '',
	abo_pwd varchar(32) NOT NULL default '',
	abo_email varchar(255) NOT NULL default '',
	abo_lang varchar(30) NOT NULL default '',
	abo_register_key varchar(32) NOT NULL default '',
	abo_register_date int4 NOT NULL default '0',
	abo_status int NOT NULL default '0',
	CONSTRAINT wa_abonnes_pkey PRIMARY KEY (abo_id)
);

CREATE  INDEX abo_status_wa_abonnes_index ON wa_abonnes (abo_status);

/*
  Structure de la table "wa_admin"
*/
CREATE TABLE wa_admin (
	admin_id int2 DEFAULT nextval('wa_admin_id_seq'::text) NOT NULL,
	admin_login varchar(30) NOT NULL default '',
	admin_pwd varchar(32) NOT NULL default '',
	admin_email varchar(255) NOT NULL default '',
	admin_lang varchar(30) NOT NULL default '',
	admin_dateformat varchar(20) NOT NULL default '',
	admin_level int NOT NULL default '1',
	email_new_inscrit int NOT NULL default '0',
	CONSTRAINT wa_admin_pkey PRIMARY KEY (admin_id)
);

/*
  Structure de la table "wa_auth_admin"
*/
CREATE TABLE wa_auth_admin (
	admin_id int2 NOT NULL default '0',
	liste_id int4 NOT NULL default '0',
	auth_view int NOT NULL default '0',
	auth_edit int NOT NULL default '0',
	auth_del int NOT NULL default '0',
	auth_send int NOT NULL default '0',
	auth_import int NOT NULL default '0',
	auth_export int NOT NULL default '0',
	auth_ban int NOT NULL default '0',
	auth_attach int NOT NULL default '0'
);

CREATE  INDEX admin_id_wa_auth_admin_index ON wa_auth_admin (admin_id);

/*
  Structure de la table "wa_ban_list"
*/
CREATE TABLE wa_ban_list (
	ban_id int4 DEFAULT nextval('wa_ban_id_seq'::text) NOT NULL,
	liste_id int4 NOT NULL default '0',
	ban_email varchar(250) NOT NULL default '',
	CONSTRAINT wa_ban_list_pkey PRIMARY KEY (ban_id)
);

/*
  Structure de la table "wa_config"
*/
CREATE TABLE wa_config (
	sitename varchar(100) NOT NULL default '',
	urlsite varchar(100) NOT NULL default '',
	path varchar(100) NOT NULL default '',
	hebergeur int NOT NULL default '0',
	date_format varchar(20) NOT NULL default '',
	session_length int2 NOT NULL default '0',
	language varchar(30) NOT NULL default '',
	cookie_name varchar(100) NOT NULL default '',
	cookie_path varchar(100) NOT NULL default '',
	upload_path varchar(100) NOT NULL default '',
	max_filesize int4 NOT NULL default '0',
	use_ftp int NOT NULL default '0',
	ftp_server varchar(50) NOT NULL default '',
	ftp_port int2 NOT NULL default '21',
	ftp_pasv int NOT NULL default '0',
	ftp_path varchar(100) NOT NULL default '',
	ftp_user varchar(30) NOT NULL default '',
	ftp_pass varchar(30) NOT NULL default '',
	engine_send int NOT NULL default '0',
	emails_sended int2 NOT NULL default '0',
	use_smtp int NOT NULL default '0',
	smtp_host varchar(100) NOT NULL default '',
	smtp_port int2 NOT NULL default '25',
	smtp_user varchar(50) NOT NULL default '',
	smtp_pass varchar(50) NOT NULL default '',
	disable_stats int NOT NULL default '0',
	gd_img_type varchar(5) NOT NULL default '',
	check_email_mx int NOT NULL default '0',
	enable_profil_cp int NOT NULL default '0',
	mailing_startdate int4 NOT NULL default '0',
	version varchar(10) NOT NULL default ''
);

/*
  Structure de la table "wa_forbidden_ext"
*/
CREATE TABLE wa_forbidden_ext (
	fe_id int4 DEFAULT nextval('wa_forbidden_ext_id_seq'::text) NOT NULL,
	liste_id int4 NOT NULL default '0',
	fe_ext varchar(10) NOT NULL default '',
	CONSTRAINT wa_forbidden_ext_pkey PRIMARY KEY (fe_id)
);

/*
  Structure de la table "wa_joined_files"
*/
CREATE TABLE wa_joined_files (
	file_id int4 DEFAULT nextval('wa_joined_files_id_seq'::text) NOT NULL,
	file_real_name varchar(200) NOT NULL default '',
	file_physical_name varchar(200) NOT NULL default '',
	file_size int4 NOT NULL default '0',
	file_mimetype varchar(100) NOT NULL default '',
	CONSTRAINT wa_joined_files_pkey PRIMARY KEY (file_id)
);

/*
  Structure de la table "wa_liste"
*/
CREATE TABLE wa_liste (
	liste_id int4 DEFAULT nextval('wa_liste_id_seq'::text) NOT NULL,
	liste_name varchar(100) NOT NULL default '',
	liste_format int NOT NULL default '1',
	sender_email varchar(250) NOT NULL default '',
	return_email varchar(250) NOT NULL default '',
	confirm_subscribe int NOT NULL default '0',
	limitevalidate int2 NOT NULL default '3',
	form_url varchar(255) NOT NULL default '',
	liste_sig text,
	auto_purge int NOT NULL default '0',
	purge_freq int NOT NULL default '0',
	purge_next int4 NOT NULL default '0',
	liste_startdate int4 NOT NULL default '0',
	liste_alias varchar(250) NOT NULL default '',
	liste_numlogs int2 NOT NULL default '0',
	use_cron int NOT NULL default '0',
	pop_host varchar(100) NOT NULL default '',
	pop_port int2 NOT NULL default '110',
	pop_user varchar(50) NOT NULL default '',
	pop_pass varchar(50) NOT NULL default '',
	CONSTRAINT wa_liste_pkey PRIMARY KEY (liste_id)
);

/*
  Structure de la table "wa_log"
*/
CREATE TABLE wa_log (
	log_id int4 DEFAULT nextval('wa_log_id_seq'::text) NOT NULL,
	liste_id int2 NOT NULL default '0',
	log_subject varchar(100) NOT NULL default '',
	log_body_html text NOT NULL,
	log_body_text text NOT NULL,
	log_date int4 NOT NULL default '0',
	log_status int NOT NULL default '0',
	log_numdest int2 NOT NULL default '0',
	CONSTRAINT wa_log_pkey PRIMARY KEY (log_id)
);

CREATE  INDEX liste_id_wa_log_index ON wa_log (liste_id);
CREATE  INDEX log_status_wa_log_index ON wa_log (log_status);

/*
  Structure de la table "wa_log_files"
*/
CREATE TABLE wa_log_files (
	log_id int4 NOT NULL default '0',
	file_id int4 NOT NULL default '0',
	CONSTRAINT wa_log_files_pkey PRIMARY KEY (log_id, file_id)
);

/*
  Structure de la table "wa_session"
*/
CREATE TABLE wa_session (
	session_id char(32) DEFAULT '0' NOT NULL,
 	admin_id int4 DEFAULT '0' NOT NULL,
	session_start int4 DEFAULT '0' NOT NULL,
	session_time int4 DEFAULT '0' NOT NULL,
	session_ip char(8) DEFAULT '0' NOT NULL,
	session_liste int4 DEFAULT '0' NOT NULL,
	CONSTRAINT phpbb_session_pkey PRIMARY KEY (session_id)
);

