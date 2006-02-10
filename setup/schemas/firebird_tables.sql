-- 
-- Schéma des tables de WAnewsletter 2.3.x pour Firebird
-- 
-- $Id$
-- 

-- 
-- Structure de la table wa_abo_liste
-- 
CREATE TABLE wa_abo_liste (
	abo_id        INTEGER  DEFAULT 0 NOT NULL,
	liste_id      SMALLINT DEFAULT 0 NOT NULL,
	format        SMALLINT DEFAULT 0 NOT NULL,
	send          SMALLINT DEFAULT 0 NOT NULL,
	register_key  CHAR(20) DEFAULT NULL,
	register_date INTEGER  DEFAULT 0 NOT NULL,
	confirmed     SMALLINT DEFAULT 0 NOT NULL,
	CONSTRAINT wa_abo_liste_pk PRIMARY KEY (abo_id, liste_id),
	CONSTRAINT register_key_idx UNIQUE (register_key)
);


-- 
-- Structure de la table wa_abonnes
-- 
CREATE TABLE wa_abonnes (
	abo_id     INTEGER      NOT NULL,
	abo_pseudo VARCHAR(30)  DEFAULT '' NOT NULL,
	abo_pwd    VARCHAR(32)  DEFAULT '' NOT NULL,
	abo_email  VARCHAR(200) DEFAULT '' NOT NULL,
	abo_lang   VARCHAR(30)  DEFAULT '' NOT NULL,
	abo_status SMALLINT     DEFAULT 0 NOT NULL,
	CONSTRAINT wa_abonnes_pk PRIMARY KEY (abo_id),
	CONSTRAINT abo_email_idx UNIQUE (abo_email)
);
CREATE INDEX abo_status_idx ON wa_abonnes (abo_status);
CREATE GENERATOR wa_abonnes_gen;
CREATE TRIGGER wa_abonnes_gen_t FOR wa_abonnes
BEFORE INSERT
AS
BEGIN
	IF (NEW.abo_id IS NULL) THEN
		NEW.abo_id = GEN_ID(wa_abonnes_gen, 1);
END;


-- 
-- Structure de la table wa_admin
-- 
CREATE TABLE wa_admin (
	admin_id            SMALLINT     NOT NULL,
	admin_login         VARCHAR(30)  DEFAULT '' NOT NULL,
	admin_pwd           VARCHAR(32)  DEFAULT '' NOT NULL,
	admin_email         VARCHAR(255) DEFAULT '' NOT NULL,
	admin_lang          VARCHAR(30)  DEFAULT '' NOT NULL,
	admin_dateformat    VARCHAR(20)  DEFAULT '' NOT NULL,
	admin_level         SMALLINT     DEFAULT 1 NOT NULL,
	email_new_subscribe SMALLINT     DEFAULT 0 NOT NULL,
	email_unsubscribe   SMALLINT     DEFAULT 0 NOT NULL,
	CONSTRAINT wa_admin_pk PRIMARY KEY (admin_id)
);
CREATE GENERATOR wa_admin_gen;
CREATE TRIGGER wa_admin_gen_t FOR wa_admin
BEFORE INSERT
AS
BEGIN
	IF (NEW.admin_id IS NULL) THEN
		NEW.admin_id = GEN_ID(wa_admin_gen, 1);
END;


-- 
-- Structure de la table wa_auth_admin
-- 
CREATE TABLE wa_auth_admin (
	admin_id    SMALLINT DEFAULT 0 NOT NULL,
	liste_id    SMALLINT DEFAULT 0 NOT NULL,
	auth_view   SMALLINT DEFAULT 0 NOT NULL,
	auth_edit   SMALLINT DEFAULT 0 NOT NULL,
	auth_del    SMALLINT DEFAULT 0 NOT NULL,
	auth_send   SMALLINT DEFAULT 0 NOT NULL,
	auth_import SMALLINT DEFAULT 0 NOT NULL,
	auth_export SMALLINT DEFAULT 0 NOT NULL,
	auth_ban    SMALLINT DEFAULT 0 NOT NULL,
	auth_attach SMALLINT DEFAULT 0 NOT NULL,
	cc_admin    SMALLINT DEFAULT 0 NOT NULL
);
CREATE INDEX admin_id_idx ON wa_auth_admin (admin_id);


-- 
-- Structure de la table wa_ban_list
-- 
CREATE TABLE wa_ban_list (
	ban_id    INTEGER      NOT NULL,
	liste_id  SMALLINT     DEFAULT 0 NOT NULL,
	ban_email VARCHAR(250) DEFAULT '' NOT NULL,
	CONSTRAINT wa_ban_list_pk PRIMARY KEY (ban_id)
);
CREATE GENERATOR wa_ban_list_gen;
CREATE TRIGGER wa_ban_list_gen_t FOR wa_ban_list
BEFORE INSERT
AS
BEGIN
	IF (NEW.ban_id IS NULL) THEN
		NEW.ban_id = GEN_ID(wa_ban_list_gen, 1);
END;


-- 
-- Structure de la table wa_config
-- 
CREATE TABLE wa_config (
	sitename          VARCHAR(100) DEFAULT '' NOT NULL,
	urlsite           VARCHAR(100) DEFAULT '' NOT NULL,
	path              VARCHAR(100) DEFAULT '' NOT NULL,
	date_format       VARCHAR(20)  DEFAULT '' NOT NULL,
	session_length    SMALLINT     DEFAULT 0 NOT NULL,
	language          VARCHAR(30)  DEFAULT '' NOT NULL,
	cookie_name       VARCHAR(100) DEFAULT '' NOT NULL,
	cookie_path       VARCHAR(100) DEFAULT '' NOT NULL,
	upload_path       VARCHAR(100) DEFAULT '' NOT NULL,
	max_filesize      INTEGER      DEFAULT 0 NOT NULL,
	use_ftp           SMALLINT     DEFAULT 0 NOT NULL,
	ftp_server        VARCHAR(100) DEFAULT '' NOT NULL,
	ftp_port          SMALLINT     DEFAULT 21 NOT NULL,
	ftp_pasv          SMALLINT     DEFAULT 0 NOT NULL,
	ftp_path          VARCHAR(100) DEFAULT '' NOT NULL,
	ftp_user          VARCHAR(100) DEFAULT '' NOT NULL,
	ftp_pass          VARCHAR(100) DEFAULT '' NOT NULL,
	engine_send       SMALLINT     DEFAULT 0 NOT NULL,
	emails_sended     SMALLINT     DEFAULT 0 NOT NULL,
	use_smtp          SMALLINT     DEFAULT 0 NOT NULL,
	smtp_host         VARCHAR(100) DEFAULT '' NOT NULL,
	smtp_port         SMALLINT     DEFAULT 25 NOT NULL,
	smtp_user         VARCHAR(100) DEFAULT '' NOT NULL,
	smtp_pass         VARCHAR(100) DEFAULT '' NOT NULL,
	disable_stats     SMALLINT     DEFAULT 0 NOT NULL,
	gd_img_type       VARCHAR(5)   DEFAULT '' NOT NULL,
	check_email_mx    SMALLINT     DEFAULT 0 NOT NULL,
	enable_profil_cp  SMALLINT     DEFAULT 0 NOT NULL,
	mailing_startdate INTEGER      DEFAULT 0 NOT NULL
);


-- 
-- Structure de la table wa_forbidden_ext
-- 
CREATE TABLE wa_forbidden_ext (
	fe_id    SMALLINT    NOT NULL,
	liste_id SMALLINT    DEFAULT 0 NOT NULL,
	fe_ext   VARCHAR(10) DEFAULT '' NOT NULL,
	CONSTRAINT wa_forbidden_ext_pk PRIMARY KEY (fe_id)
);
CREATE GENERATOR wa_forbidden_ext_gen;
CREATE TRIGGER wa_forbidden_ext_gen_t FOR wa_forbidden_ext
BEFORE INSERT
AS
BEGIN
	IF (NEW.fe_id IS NULL) THEN
		NEW.fe_id = GEN_ID(wa_forbidden_ext_gen, 1);
END;


-- 
-- Structure de la table wa_joined_files
-- 
CREATE TABLE wa_joined_files (
	file_id            INTEGER      NOT NULL,
	file_real_name     VARCHAR(200) DEFAULT '' NOT NULL,
	file_physical_name VARCHAR(200) DEFAULT '' NOT NULL,
	file_size          INTEGER      DEFAULT 0  NOT NULL,
	file_mimetype      VARCHAR(100) DEFAULT '' NOT NULL,
	CONSTRAINT wa_joined_files_pk PRIMARY KEY (file_id)
);
CREATE GENERATOR wa_joined_files_gen;
CREATE TRIGGER wa_joined_files_gen_t FOR wa_joined_files
BEFORE INSERT
AS
BEGIN
	IF (NEW.file_id IS NULL) THEN
		NEW.file_id = GEN_ID(wa_joined_files_gen, 1);
END;


-- 
-- Structure de la table wa_liste
-- 
CREATE TABLE wa_liste (
	liste_id          SMALLINT     NOT NULL,
	liste_name        VARCHAR(100) DEFAULT '' NOT NULL,
	liste_public      SMALLINT     DEFAULT 1 NOT NULL,
	liste_format      SMALLINT     DEFAULT 1 NOT NULL,
	sender_email      VARCHAR(250) DEFAULT '' NOT NULL,
	return_email      VARCHAR(250) DEFAULT '' NOT NULL,
	confirm_subscribe SMALLINT     DEFAULT 0 NOT NULL,
	limitevalidate    SMALLINT     DEFAULT 3 NOT NULL,
	form_url          VARCHAR(255) DEFAULT '' NOT NULL,
	liste_sig         BLOB SUB_TYPE TEXT DEFAULT '' NOT NULL,
	auto_purge        SMALLINT     DEFAULT 0 NOT NULL,
	purge_freq        SMALLINT     DEFAULT 0 NOT NULL,
	purge_next        INTEGER      DEFAULT 0 NOT NULL,
	liste_startdate   INTEGER      DEFAULT 0 NOT NULL,
	liste_alias       VARCHAR(250) DEFAULT '' NOT NULL,
	liste_numlogs     SMALLINT     DEFAULT 0 NOT NULL,
	use_cron          SMALLINT     DEFAULT 0 NOT NULL,
	pop_host          VARCHAR(100) DEFAULT '' NOT NULL,
	pop_port          SMALLINT     DEFAULT 110 NOT NULL,
	pop_user          VARCHAR(100) DEFAULT '' NOT NULL,
	pop_pass          VARCHAR(100) DEFAULT '' NOT NULL,
	CONSTRAINT wa_liste_pk PRIMARY KEY (liste_id)
);
CREATE GENERATOR wa_liste_gen;
CREATE TRIGGER wa_liste_gen_t FOR wa_liste
BEFORE INSERT
AS
BEGIN
	IF (NEW.liste_id IS NULL) THEN
		NEW.liste_id = GEN_ID(wa_liste_gen, 1);
END;


-- 
-- Structure de la table wa_log
-- 
CREATE TABLE wa_log (
	log_id        INTEGER      NOT NULL,
	liste_id      SMALLINT     DEFAULT 0 NOT NULL,
	log_subject   VARCHAR(100) DEFAULT '' NOT NULL,
	log_body_html BLOB SUB_TYPE TEXT DEFAULT '' NOT NULL,
	log_body_text BLOB SUB_TYPE TEXT DEFAULT '' NOT NULL,
	log_date      INTEGER      DEFAULT 0 NOT NULL,
	log_status    SMALLINT     DEFAULT 0 NOT NULL,
	log_numdest   SMALLINT     DEFAULT 0 NOT NULL,
	CONSTRAINT wa_log_pk PRIMARY KEY (log_id)
);
CREATE GENERATOR wa_log_gen;
CREATE TRIGGER wa_log_gen_t FOR wa_log
BEFORE INSERT
AS
BEGIN
	IF (NEW.log_id IS NULL) THEN
		NEW.log_id = GEN_ID(wa_log_gen, 1);
END;
CREATE INDEX liste_id_idx ON wa_log (liste_id);
CREATE INDEX log_status_idx ON wa_log (log_status);


-- 
-- Structure de la table wa_log_files
-- 
CREATE TABLE wa_log_files (
	log_id  INTEGER DEFAULT 0 NOT NULL,
	file_id INTEGER DEFAULT 0 NOT NULL,
	CONSTRAINT wa_log_files_pk PRIMARY KEY (log_id, file_id)
);


-- 
-- Structure de la table wa_session
-- 
CREATE TABLE wa_session (
	session_id    CHAR(32) DEFAULT '' NOT NULL,
 	admin_id      SMALLINT DEFAULT 0  NOT NULL,
	session_start INTEGER  DEFAULT 0  NOT NULL,
	session_time  INTEGER  DEFAULT 0  NOT NULL,
	session_ip    CHAR(8)  DEFAULT '' NOT NULL,
	session_liste SMALLINT DEFAULT 0  NOT NULL,
	CONSTRAINT wa_session_pk PRIMARY KEY (session_id)
);

