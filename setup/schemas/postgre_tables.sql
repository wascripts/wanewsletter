/*
  Schéma des tables de WAnewsletter 2.3.x pour PostgreSQL
  
  $Id$
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
	abo_id   INT4 NOT NULL DEFAULT '0',
	liste_id INT4 NOT NULL DEFAULT '0',
	format   INT  NOT NULL DEFAULT '0',
	send     INT  NOT NULL DEFAULT '0',
	CONSTRAINT wa_abo_liste_pk PRIMARY KEY (abo_id, liste_id)
);


/*
  Structure de la table "wa_abonnes"
*/
CREATE TABLE wa_abonnes (
	abo_id            INT4         NOT NULL DEFAULT nextval('wa_abonnes_id_seq'::text),
	abo_pseudo        VARCHAR(30)  NOT NULL DEFAULT '',
	abo_pwd           VARCHAR(32)  NOT NULL DEFAULT '',
	abo_email         VARCHAR(255) NOT NULL DEFAULT '',
	abo_lang          VARCHAR(30)  NOT NULL DEFAULT '',
	abo_register_key  VARCHAR(32)  NOT NULL DEFAULT '',
	abo_register_date INT4         NOT NULL DEFAULT '0',
	abo_status        INT          NOT NULL DEFAULT '0',
	CONSTRAINT wa_abonnes_pk PRIMARY KEY (abo_id)
);
CREATE INDEX abo_status_idx ON wa_abonnes (abo_status);


/*
  Structure de la table "wa_admin"
*/
CREATE TABLE wa_admin (
	admin_id          INT2         NOT NULL DEFAULT nextval('wa_admin_id_seq'::text),
	admin_login       VARCHAR(30)  NOT NULL DEFAULT '',
	admin_pwd         VARCHAR(32)  NOT NULL DEFAULT '',
	admin_email       VARCHAR(255) NOT NULL DEFAULT '',
	admin_lang        VARCHAR(30)  NOT NULL DEFAULT '',
	admin_dateformat  VARCHAR(20)  NOT NULL DEFAULT '',
	admin_level       INT          NOT NULL DEFAULT '1',
	email_new_inscrit INT          NOT NULL DEFAULT '0',
	CONSTRAINT wa_admin_pk PRIMARY KEY (admin_id)
);


/*
  Structure de la table "wa_auth_admin"
*/
CREATE TABLE wa_auth_admin (
	admin_id    INT2 NOT NULL DEFAULT '0',
	liste_id    INT4 NOT NULL DEFAULT '0',
	auth_view   INT  NOT NULL DEFAULT '0',
	auth_edit   INT  NOT NULL DEFAULT '0',
	auth_del    INT  NOT NULL DEFAULT '0',
	auth_send   INT  NOT NULL DEFAULT '0',
	auth_import INT  NOT NULL DEFAULT '0',
	auth_export INT  NOT NULL DEFAULT '0',
	auth_ban    INT  NOT NULL DEFAULT '0',
	auth_attach INT  NOT NULL DEFAULT '0'
);
CREATE INDEX admin_id_idx ON wa_auth_admin (admin_id);


/*
  Structure de la table "wa_ban_list"
*/
CREATE TABLE wa_ban_list (
	ban_id    INT4         NOT NULL DEFAULT nextval('wa_ban_id_seq'::text),
	liste_id  INT4         NOT NULL DEFAULT '0',
	ban_email VARCHAR(250) NOT NULL DEFAULT '',
	CONSTRAINT wa_ban_list_pk PRIMARY KEY (ban_id)
);


/*
  Structure de la table "wa_config"
*/
CREATE TABLE wa_config (
	sitename          VARCHAR(100) NOT NULL DEFAULT '',
	urlsite           VARCHAR(100) NOT NULL DEFAULT '',
	path              VARCHAR(100) NOT NULL DEFAULT '',
	hebergeur         INT          NOT NULL DEFAULT '0',
	date_format       VARCHAR(20)  NOT NULL DEFAULT '',
	session_length    INT2         NOT NULL DEFAULT '0',
	language          VARCHAR(30)  NOT NULL DEFAULT '',
	cookie_name       VARCHAR(100) NOT NULL DEFAULT '',
	cookie_path       VARCHAR(100) NOT NULL DEFAULT '',
	upload_path       VARCHAR(100) NOT NULL DEFAULT '',
	max_filesize      INT4         NOT NULL DEFAULT '0',
	use_ftp           INT          NOT NULL DEFAULT '0',
	ftp_server        VARCHAR(50)  NOT NULL DEFAULT '',
	ftp_port          INT2         NOT NULL DEFAULT '21',
	ftp_pasv          INT          NOT NULL DEFAULT '0',
	ftp_path          VARCHAR(100) NOT NULL DEFAULT '',
	ftp_user          VARCHAR(30)  NOT NULL DEFAULT '',
	ftp_pass          VARCHAR(30)  NOT NULL DEFAULT '',
	engine_send       INT          NOT NULL DEFAULT '0',
	emails_sended     INT2         NOT NULL DEFAULT '0',
	use_smtp          INT          NOT NULL DEFAULT '0',
	smtp_host         VARCHAR(100) NOT NULL DEFAULT '',
	smtp_port         INT2         NOT NULL DEFAULT '25',
	smtp_user         VARCHAR(50)  NOT NULL DEFAULT '',
	smtp_pass         VARCHAR(50)  NOT NULL DEFAULT '',
	disable_stats     INT          NOT NULL DEFAULT '0',
	gd_img_type       VARCHAR(5)   NOT NULL DEFAULT '',
	check_email_mx    INT          NOT NULL DEFAULT '0',
	enable_profil_cp  INT          NOT NULL DEFAULT '0',
	mailing_startdate INT4         NOT NULL DEFAULT '0',
	version           VARCHAR(10)  NOT NULL DEFAULT ''
);


/*
  Structure de la table "wa_forbidden_ext"
*/
CREATE TABLE wa_forbidden_ext (
	fe_id    INT4        NOT NULL DEFAULT nextval('wa_forbidden_ext_id_seq'::text),
	liste_id INT4        NOT NULL DEFAULT '0',
	fe_ext   VARCHAR(10) NOT NULL DEFAULT '',
	CONSTRAINT wa_forbidden_ext_pk PRIMARY KEY (fe_id)
);


/*
  Structure de la table "wa_joined_files"
*/
CREATE TABLE wa_joined_files (
	file_id            INT4         NOT NULL DEFAULT nextval('wa_joined_files_id_seq'::text),
	file_real_name     VARCHAR(200) NOT NULL DEFAULT '',
	file_physical_name VARCHAR(200) NOT NULL DEFAULT '',
	file_size          INT4         NOT NULL DEFAULT '0',
	file_mimetype      VARCHAR(100) NOT NULL DEFAULT '',
	CONSTRAINT wa_joined_files_pk PRIMARY KEY (file_id)
);


/*
  Structure de la table "wa_liste"
*/
CREATE TABLE wa_liste (
	liste_id          INT4         NOT NULL DEFAULT nextval('wa_liste_id_seq'::text),
	liste_name        VARCHAR(100) NOT NULL DEFAULT '',
	liste_format      INT          NOT NULL DEFAULT '1',
	sender_email      VARCHAR(250) NOT NULL DEFAULT '',
	return_email      VARCHAR(250) NOT NULL DEFAULT '',
	confirm_subscribe INT          NOT NULL DEFAULT '0',
	limitevalidate    INT2         NOT NULL DEFAULT '3',
	form_url          VARCHAR(255) NOT NULL DEFAULT '',
	liste_sig         TEXT         NOT NULL,
	auto_purge        INT          NOT NULL DEFAULT '0',
	purge_freq        INT          NOT NULL DEFAULT '0',
	purge_next        INT4         NOT NULL DEFAULT '0',
	liste_startdate   INT4         NOT NULL DEFAULT '0',
	liste_alias       VARCHAR(250) NOT NULL DEFAULT '',
	liste_numlogs     INT2         NOT NULL DEFAULT '0',
	use_cron          INT          NOT NULL DEFAULT '0',
	pop_host          VARCHAR(100) NOT NULL DEFAULT '',
	pop_port          INT2         NOT NULL DEFAULT '110',
	pop_user          VARCHAR(50)  NOT NULL DEFAULT '',
	pop_pass          VARCHAR(50)  NOT NULL DEFAULT '',
	CONSTRAINT wa_liste_pk PRIMARY KEY (liste_id)
);


/*
  Structure de la table "wa_log"
*/
CREATE TABLE wa_log (
	log_id        INT4         NOT NULL DEFAULT nextval('wa_log_id_seq'::text),
	liste_id      INT2         NOT NULL DEFAULT '0',
	log_subject   VARCHAR(100) NOT NULL DEFAULT '',
	log_body_html TEXT         NOT NULL,
	log_body_text TEXT         NOT NULL,
	log_date      INT4         NOT NULL DEFAULT '0',
	log_status    INT          NOT NULL DEFAULT '0',
	log_numdest   INT2         NOT NULL DEFAULT '0',
	CONSTRAINT wa_log_pk PRIMARY KEY (log_id)
);
CREATE INDEX liste_id_idx ON wa_log (liste_id);
CREATE INDEX log_status_idx ON wa_log (log_status);


/*
  Structure de la table "wa_log_files"
*/
CREATE TABLE wa_log_files (
	log_id  INT4 NOT NULL DEFAULT '0',
	file_id INT4 NOT NULL DEFAULT '0',
	CONSTRAINT wa_log_files_pk PRIMARY KEY (log_id, file_id)
);


/*
  Structure de la table "wa_session"
*/
CREATE TABLE wa_session (
	session_id    CHAR(32) NOT NULL DEFAULT '0',
 	admin_id      INT4     NOT NULL DEFAULT '0',
	session_start INT4     NOT NULL DEFAULT '0',
	session_time  INT4     NOT NULL DEFAULT '0',
	session_ip    CHAR(8)  NOT NULL DEFAULT '0',
	session_liste INT4     NOT NULL DEFAULT '0',
	CONSTRAINT wa_session_pk PRIMARY KEY (session_id)
);

