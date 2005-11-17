-- 
-- Schéma des tables de WAnewsletter 2.3.x pour SQLite
-- 
-- $Id$
-- 

-- 
-- Structure de la table "wa_abo_liste"
-- 
CREATE TABLE wa_abo_liste (
	abo_id        INTEGER NOT NULL DEFAULT 0,
	liste_id      INTEGER NOT NULL DEFAULT 0,
	format        INTEGER NOT NULL DEFAULT 0,
	send          INTEGER NOT NULL DEFAULT 0,
	confirmed     INTEGER NOT NULL DEFAULT 0,
	register_date INTEGER NOT NULL DEFAULT 0,
	CONSTRAINT wa_abo_liste_pk PRIMARY KEY (abo_id, liste_id)
);


-- 
-- Structure de la table "wa_abonnes"
-- 
CREATE TABLE wa_abonnes (
	abo_id            INTEGER      NOT NULL,
	abo_pseudo        VARCHAR(30)  NOT NULL DEFAULT '',
	abo_pwd           VARCHAR(32)  NOT NULL DEFAULT '',
	abo_email         VARCHAR(255) NOT NULL DEFAULT '',
	abo_lang          VARCHAR(30)  NOT NULL DEFAULT '',
	abo_register_key  VARCHAR(32)  NOT NULL DEFAULT '',
	abo_register_date INTEGER      NOT NULL DEFAULT 0,
	abo_status        INTEGER      NOT NULL DEFAULT 0,
	CONSTRAINT wa_abonnes_pk PRIMARY KEY (abo_id),
	CONSTRAINT abo_email_idx UNIQUE (abo_email)
);
CREATE INDEX abo_status_idx ON wa_abonnes (abo_status);


-- 
-- Structure de la table "wa_admin"
-- 
CREATE TABLE wa_admin (
	admin_id          INTEGER      NOT NULL,
	admin_login       VARCHAR(30)  NOT NULL DEFAULT '',
	admin_pwd         VARCHAR(32)  NOT NULL DEFAULT '',
	admin_email       VARCHAR(255) NOT NULL DEFAULT '',
	admin_lang        VARCHAR(30)  NOT NULL DEFAULT '',
	admin_dateformat  VARCHAR(20)  NOT NULL DEFAULT '',
	admin_level       INTEGER      NOT NULL DEFAULT 1,
	email_new_inscrit INTEGER      NOT NULL DEFAULT 0,
	CONSTRAINT wa_admin_pk PRIMARY KEY (admin_id)
);


-- 
-- Structure de la table "wa_auth_admin"
-- 
CREATE TABLE wa_auth_admin (
	admin_id    INTEGER NOT NULL DEFAULT 0,
	liste_id    INTEGER NOT NULL DEFAULT 0,
	auth_view   INTEGER NOT NULL DEFAULT 0,
	auth_edit   INTEGER NOT NULL DEFAULT 0,
	auth_del    INTEGER NOT NULL DEFAULT 0,
	auth_send   INTEGER NOT NULL DEFAULT 0,
	auth_import INTEGER NOT NULL DEFAULT 0,
	auth_export INTEGER NOT NULL DEFAULT 0,
	auth_ban    INTEGER NOT NULL DEFAULT 0,
	auth_attach INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX admin_id_idx ON wa_auth_admin (admin_id);


-- 
-- Structure de la table "wa_ban_list"
-- 
CREATE TABLE wa_ban_list (
	ban_id    INTEGER      NOT NULL,
	liste_id  INTEGER      NOT NULL DEFAULT 0,
	ban_email VARCHAR(250) NOT NULL DEFAULT '',
	CONSTRAINT wa_ban_list_pk PRIMARY KEY (ban_id)
);


-- 
-- Structure de la table "wa_config"
-- 
CREATE TABLE wa_config (
	sitename          VARCHAR(100) NOT NULL DEFAULT '',
	urlsite           VARCHAR(100) NOT NULL DEFAULT '',
	path              VARCHAR(100) NOT NULL DEFAULT '',
	date_format       VARCHAR(20)  NOT NULL DEFAULT '',
	session_length    INTEGER      NOT NULL DEFAULT 0,
	language          VARCHAR(30)  NOT NULL DEFAULT '',
	cookie_name       VARCHAR(100) NOT NULL DEFAULT '',
	cookie_path       VARCHAR(100) NOT NULL DEFAULT '',
	upload_path       VARCHAR(100) NOT NULL DEFAULT '',
	max_filesize      INTEGER      NOT NULL DEFAULT 0,
	use_ftp           INTEGER      NOT NULL DEFAULT 0,
	ftp_server        VARCHAR(50)  NOT NULL DEFAULT '',
	ftp_port          INTEGER      NOT NULL DEFAULT 21,
	ftp_pasv          INTEGER      NOT NULL DEFAULT 0,
	ftp_path          VARCHAR(100) NOT NULL DEFAULT '',
	ftp_user          VARCHAR(30)  NOT NULL DEFAULT '',
	ftp_pass          VARCHAR(30)  NOT NULL DEFAULT '',
	engine_send       INTEGER      NOT NULL DEFAULT 0,
	emails_sended     INTEGER      NOT NULL DEFAULT 0,
	use_smtp          INTEGER      NOT NULL DEFAULT 0,
	smtp_host         VARCHAR(100) NOT NULL DEFAULT '',
	smtp_port         INTEGER      NOT NULL DEFAULT 25,
	smtp_user         VARCHAR(50)  NOT NULL DEFAULT '',
	smtp_pass         VARCHAR(50)  NOT NULL DEFAULT '',
	disable_stats     INTEGER      NOT NULL DEFAULT 0,
	gd_img_type       VARCHAR(5)   NOT NULL DEFAULT '',
	check_email_mx    INTEGER      NOT NULL DEFAULT 0,
	enable_profil_cp  INTEGER      NOT NULL DEFAULT 0,
	mailing_startdate INTEGER      NOT NULL DEFAULT 0,
	version           VARCHAR(10)  NOT NULL DEFAULT ''
);


-- 
-- Structure de la table "wa_forbidden_ext"
-- 
CREATE TABLE wa_forbidden_ext (
	fe_id    INTEGER     NOT NULL,
	liste_id INTEGER     NOT NULL DEFAULT 0,
	fe_ext   VARCHAR(10) NOT NULL DEFAULT '',
	CONSTRAINT wa_forbidden_ext_pk PRIMARY KEY (fe_id)
);


-- 
-- Structure de la table "wa_joined_files"
-- 
CREATE TABLE wa_joined_files (
	file_id            INTEGER      NOT NULL,
	file_real_name     VARCHAR(200) NOT NULL DEFAULT '',
	file_physical_name VARCHAR(200) NOT NULL DEFAULT '',
	file_size          INTEGER      NOT NULL DEFAULT 0,
	file_mimetype      VARCHAR(100) NOT NULL DEFAULT '',
	CONSTRAINT wa_joined_files_pk PRIMARY KEY (file_id)
);


-- 
-- Structure de la table "wa_liste"
-- 
CREATE TABLE wa_liste (
	liste_id          INTEGER      NOT NULL,
	liste_name        VARCHAR(100) NOT NULL DEFAULT '',
	liste_public      INTEGER      NOT NULL DEFAULT 1,
	liste_format      INTEGER      NOT NULL DEFAULT 1,
	sender_email      VARCHAR(250) NOT NULL DEFAULT '',
	return_email      VARCHAR(250) NOT NULL DEFAULT '',
	confirm_subscribe INTEGER      NOT NULL DEFAULT 0,
	limitevalidate    INTEGER      NOT NULL DEFAULT 3,
	form_url          VARCHAR(255) NOT NULL DEFAULT '',
	liste_sig         TEXT         NOT NULL,
	auto_purge        INTEGER      NOT NULL DEFAULT 0,
	purge_freq        INTEGER      NOT NULL DEFAULT 0,
	purge_next        INTEGER      NOT NULL DEFAULT 0,
	liste_startdate   INTEGER      NOT NULL DEFAULT 0,
	liste_alias       VARCHAR(250) NOT NULL DEFAULT '',
	liste_numlogs     INTEGER      NOT NULL DEFAULT 0,
	use_cron          INTEGER      NOT NULL DEFAULT 0,
	pop_host          VARCHAR(100) NOT NULL DEFAULT '',
	pop_port          INTEGER      NOT NULL DEFAULT 110,
	pop_user          VARCHAR(50)  NOT NULL DEFAULT '',
	pop_pass          VARCHAR(50)  NOT NULL DEFAULT '',
	CONSTRAINT wa_liste_pk PRIMARY KEY (liste_id)
);


-- 
-- Structure de la table "wa_log"
-- 
CREATE TABLE wa_log (
	log_id        INTEGER      NOT NULL,
	liste_id      INTEGER      NOT NULL DEFAULT 0,
	log_subject   VARCHAR(100) NOT NULL DEFAULT '',
	log_body_html TEXT         NOT NULL,
	log_body_text TEXT         NOT NULL,
	log_date      INTEGER      NOT NULL DEFAULT 0,
	log_status    INTEGER      NOT NULL DEFAULT 0,
	log_numdest   INTEGER      NOT NULL DEFAULT 0,
	CONSTRAINT wa_log_pk PRIMARY KEY (log_id)
);
CREATE INDEX liste_id_idx ON wa_log (liste_id);
CREATE INDEX log_status_idx ON wa_log (log_status);


-- 
-- Structure de la table "wa_log_files"
-- 
CREATE TABLE wa_log_files (
	log_id  INTEGER NOT NULL DEFAULT 0,
	file_id INTEGER NOT NULL DEFAULT 0,
	CONSTRAINT wa_log_files_pk PRIMARY KEY (log_id, file_id)
);


-- 
-- Structure de la table "wa_session"
-- 
CREATE TABLE wa_session (
	session_id    CHAR(32) NOT NULL DEFAULT '',
	admin_id      INTEGER  NOT NULL DEFAULT 0,
	session_start INTEGER  NOT NULL DEFAULT 0,
	session_time  INTEGER  NOT NULL DEFAULT 0,
	session_ip    CHAR(8)  NOT NULL DEFAULT '',
	session_liste INTEGER  NOT NULL DEFAULT 0,
	CONSTRAINT wa_session_pk PRIMARY KEY (session_id)
);

