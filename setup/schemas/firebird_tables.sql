-- 
-- Schéma des tables de WAnewsletter 2.3.x pour Firebird
-- 
-- $Id$
-- 

-- 
-- Structure de la table "wa_abo_liste"
-- 
CREATE TABLE wa_abo_liste (
	"abo_id"        INTEGER  NOT NULL DEFAULT 0,
	"liste_id"      SMALLINT NOT NULL DEFAULT 0,
	"format"        SMALLINT NOT NULL DEFAULT 0,
	"send"          SMALLINT NOT NULL DEFAULT 0,
	"register_key"  CHAR(20) DEFAULT NULL,
	"register_date" INTEGER  NOT NULL DEFAULT 0,
	"confirmed"     SMALLINT NOT NULL DEFAULT 0,
	CONSTRAINT "wa_abo_liste_pk" PRIMARY KEY (abo_id, liste_id),
	CONSTRAINT "register_key_idx" UNIQUE (register_key)
);


-- 
-- Structure de la table "wa_abonnes"
-- 
CREATE TABLE wa_abonnes (
	"abo_id"     INTEGER      NOT NULL,
	"abo_pseudo" VARCHAR(30)  NOT NULL DEFAULT '',
	"abo_pwd"    VARCHAR(32)  NOT NULL DEFAULT '',
	"abo_email"  VARCHAR(255) NOT NULL DEFAULT '',
	"abo_lang"   VARCHAR(30)  NOT NULL DEFAULT '',
	"abo_status" SMALLINT     NOT NULL DEFAULT 0,
	CONSTRAINT "wa_abonnes_pk" PRIMARY KEY (abo_id),
	CONSTRAINT "abo_email_idx" UNIQUE (abo_email)
);
CREATE INDEX "abo_status_idx" ON wa_abonnes (abo_status);
CREATE GENERATOR wa_abonnes_gen;
SET GENERATOR wa_abonnes_gen TO 0;
CREATE TRIGGER wa_abonnes_gen_t FOR wa_abonnes
BEFORE INSERT
AS
BEGIN
  NEW.abo_id = GEN_ID(wa_abonnes_gen, 1); <- TODO : problème pour parser!!
END;


-- 
-- Structure de la table "wa_admin"
-- 
CREATE TABLE wa_admin (
	"admin_id"            SMALLINT     NOT NULL,
	"admin_login"         VARCHAR(30)  NOT NULL DEFAULT '',
	"admin_pwd"           VARCHAR(32)  NOT NULL DEFAULT '',
	"admin_email"         VARCHAR(255) NOT NULL DEFAULT '',
	"admin_lang"          VARCHAR(30)  NOT NULL DEFAULT '',
	"admin_dateformat"    VARCHAR(20)  NOT NULL DEFAULT '',
	"admin_level"         SMALLINT     NOT NULL DEFAULT 1,
	"email_new_subscribe" SMALLINT     NOT NULL DEFAULT 0,
	"email_unsubscribe"   SMALLINT     NOT NULL DEFAULT 0,
	CONSTRAINT "wa_admin_pk" PRIMARY KEY (admin_id)
);
CREATE GENERATOR wa_admin_gen;
SET GENERATOR wa_admin_gen TO 0;
CREATE TRIGGER wa_admin_gen_t FOR wa_admin
BEFORE INSERT
AS
BEGIN
  NEW.admin_id = GEN_ID(wa_admin_gen, 1);
END;


-- 
-- Structure de la table "wa_auth_admin"
-- 
CREATE TABLE wa_auth_admin (
	"admin_id"    SMALLINT NOT NULL DEFAULT 0,
	"liste_id"    SMALLINT NOT NULL DEFAULT 0,
	"auth_view"   SMALLINT NOT NULL DEFAULT 0,
	"auth_edit"   SMALLINT NOT NULL DEFAULT 0,
	"auth_del"    SMALLINT NOT NULL DEFAULT 0,
	"auth_send"   SMALLINT NOT NULL DEFAULT 0,
	"auth_import" SMALLINT NOT NULL DEFAULT 0,
	"auth_export" SMALLINT NOT NULL DEFAULT 0,
	"auth_ban"    SMALLINT NOT NULL DEFAULT 0,
	"auth_attach" SMALLINT NOT NULL DEFAULT 0,
	"cc_admin"    SMALLINT NOT NULL DEFAULT 0
);
CREATE INDEX "admin_id_idx" ON wa_auth_admin (admin_id);


-- 
-- Structure de la table "wa_ban_list"
-- 
CREATE TABLE wa_ban_list (
	"ban_id"    INTEGER      NOT NULL,
	"liste_id"  SMALLINT     NOT NULL DEFAULT 0,
	"ban_email" VARCHAR(250) NOT NULL DEFAULT '',
	CONSTRAINT "wa_ban_list_pk" PRIMARY KEY (ban_id)
);
CREATE GENERATOR wa_ban_list_gen;
SET GENERATOR wa_ban_list_gen TO 0;
CREATE TRIGGER wa_ban_list_gen_t FOR wa_ban_list
BEFORE INSERT
AS
BEGIN
  NEW.ban_id = GEN_ID(wa_ban_list_gen, 1);
END;


-- 
-- Structure de la table "wa_config"
-- 
CREATE TABLE wa_config (
	"sitename"          VARCHAR(100) NOT NULL DEFAULT '',
	"urlsite"           VARCHAR(100) NOT NULL DEFAULT '',
	"path"              VARCHAR(100) NOT NULL DEFAULT '',
	"date_format"       VARCHAR(20)  NOT NULL DEFAULT '',
	"session_length"    SMALLINT     NOT NULL DEFAULT 0,
	"language"          VARCHAR(30)  NOT NULL DEFAULT '',
	"cookie_name"       VARCHAR(100) NOT NULL DEFAULT '',
	"cookie_path"       VARCHAR(100) NOT NULL DEFAULT '',
	"upload_path"       VARCHAR(100) NOT NULL DEFAULT '',
	"max_filesize"      INTEGER      NOT NULL DEFAULT 0,
	"use_ftp"           SMALLINT     NOT NULL DEFAULT 0,
	"ftp_server"        VARCHAR(100) NOT NULL DEFAULT '',
	"ftp_port"          SMALLINT     NOT NULL DEFAULT 21,
	"ftp_pasv"          SMALLINT     NOT NULL DEFAULT 0,
	"ftp_path"          VARCHAR(100) NOT NULL DEFAULT '',
	"ftp_user"          VARCHAR(100) NOT NULL DEFAULT '',
	"ftp_pass"          VARCHAR(100) NOT NULL DEFAULT '',
	"engine_send"       SMALLINT     NOT NULL DEFAULT 0,
	"emails_sended"     SMALLINT     NOT NULL DEFAULT 0,
	"use_smtp"          SMALLINT     NOT NULL DEFAULT 0,
	"smtp_host"         VARCHAR(100) NOT NULL DEFAULT '',
	"smtp_port"         SMALLINT     NOT NULL DEFAULT 25,
	"smtp_user"         VARCHAR(100) NOT NULL DEFAULT '',
	"smtp_pass"         VARCHAR(100) NOT NULL DEFAULT '',
	"disable_stats"     SMALLINT     NOT NULL DEFAULT 0,
	"gd_img_type"       VARCHAR(5)   NOT NULL DEFAULT '',
	"check_email_mx"    SMALLINT     NOT NULL DEFAULT 0,
	"enable_profil_cp"  SMALLINT     NOT NULL DEFAULT 0,
	"mailing_startdate" INTEGER      NOT NULL DEFAULT 0
);


-- 
-- Structure de la table "wa_forbidden_ext"
-- 
CREATE TABLE wa_forbidden_ext (
	"fe_id"    SMALLINT    NOT NULL,
	"liste_id" SMALLINT    NOT NULL DEFAULT 0,
	"fe_ext"   VARCHAR(10) NOT NULL DEFAULT '',
	CONSTRAINT "wa_forbidden_ext_pk" PRIMARY KEY (fe_id)
);
CREATE GENERATOR wa_forbidden_ext_gen;
SET GENERATOR wa_forbidden_ext_gen TO 0;
CREATE TRIGGER wa_forbidden_ext_gen_t FOR wa_forbidden_ext
BEFORE INSERT
AS
BEGIN
  NEW.fe_id = GEN_ID(wa_forbidden_ext_gen, 1);
END;


-- 
-- Structure de la table "wa_joined_files"
-- 
CREATE TABLE wa_joined_files (
	"file_id"            INTEGER      NOT NULL,
	"file_real_name"     VARCHAR(200) NOT NULL DEFAULT '',
	"file_physical_name" VARCHAR(200) NOT NULL DEFAULT '',
	"file_size"          INTEGER      NOT NULL DEFAULT 0,
	"file_mimetype"      VARCHAR(100) NOT NULL DEFAULT '',
	CONSTRAINT "wa_joined_files_pk" PRIMARY KEY (file_id)
);
CREATE GENERATOR wa_joined_files_gen;
SET GENERATOR wa_joined_files_gen TO 0;
CREATE TRIGGER wa_joined_files_gen_t FOR wa_joined_files
BEFORE INSERT
AS
BEGIN
  NEW.file_id = GEN_ID(wa_joined_files_gen, 1);
END;


-- 
-- Structure de la table "wa_liste"
-- 
CREATE TABLE wa_liste (
	"liste_id"          SMALLINT     NOT NULL,
	"liste_name"        VARCHAR(100) NOT NULL DEFAULT '',
	"liste_public"      SMALLINT     NOT NULL DEFAULT 1,
	"liste_format"      SMALLINT     NOT NULL DEFAULT 1,
	"sender_email"      VARCHAR(250) NOT NULL DEFAULT '',
	"return_email"      VARCHAR(250) NOT NULL DEFAULT '',
	"confirm_subscribe" SMALLINT     NOT NULL DEFAULT 0,
	"limitevalidate"    SMALLINT     NOT NULL DEFAULT 3,
	"form_url"          VARCHAR(255) NOT NULL DEFAULT '',
	"liste_sig"         TEXT         NOT NULL DEFAULT '',
	"auto_purge"        SMALLINT     NOT NULL DEFAULT 0,
	"purge_freq"        SMALLINT     NOT NULL DEFAULT 0,
	"purge_next"        INTEGER      NOT NULL DEFAULT 0,
	"liste_startdate"   INTEGER      NOT NULL DEFAULT 0,
	"liste_alias"       VARCHAR(250) NOT NULL DEFAULT '',
	"liste_numlogs"     SMALLINT     NOT NULL DEFAULT 0,
	"use_cron"          SMALLINT     NOT NULL DEFAULT 0,
	"pop_host"          VARCHAR(100) NOT NULL DEFAULT '',
	"pop_port"          SMALLINT     NOT NULL DEFAULT 110,
	"pop_user"          VARCHAR(100) NOT NULL DEFAULT '',
	"pop_pass"          VARCHAR(100) NOT NULL DEFAULT '',
	CONSTRAINT "wa_liste_pk" PRIMARY KEY (liste_id)
);
CREATE GENERATOR wa_liste_gen;
SET GENERATOR wa_liste_gen TO 0;
CREATE TRIGGER wa_liste_gen_t FOR wa_liste
BEFORE INSERT
AS
BEGIN
  NEW.liste_id = GEN_ID(wa_liste_gen, 1);
END;


-- 
-- Structure de la table "wa_log"
-- 
CREATE TABLE wa_log (
	"log_id"        INTEGER      NOT NULL,
	"liste_id"      SMALLINT     NOT NULL DEFAULT 0,
	"log_subject"   VARCHAR(100) NOT NULL DEFAULT '',
	"log_body_html" BLOB SUB_TYPE TEXT NOT NULL DEFAULT '',
	"log_body_text" BLOB SUB_TYPE TEXT NOT NULL DEFAULT '',
	"log_date"      INTEGER      NOT NULL DEFAULT 0,
	"log_status"    SMALLINT     NOT NULL DEFAULT 0,
	"log_numdest"   SMALLINT     NOT NULL DEFAULT 0,
	CONSTRAINT "wa_log_pk" PRIMARY KEY (log_id)
);
CREATE GENERATOR wa_log_gen;
SET GENERATOR wa_log_gen TO 0;
CREATE TRIGGER wa_log_gen_t FOR wa_log
BEFORE INSERT
AS
BEGIN
  NEW.log_id = GEN_ID(wa_log_gen, 1);
END;
CREATE INDEX "liste_id_idx" ON wa_log (liste_id);
CREATE INDEX "log_status_idx" ON wa_log (log_status);


-- 
-- Structure de la table "wa_log_files"
-- 
CREATE TABLE wa_log_files (
	"log_id"  INTEGER NOT NULL DEFAULT 0,
	"file_id" INTEGER NOT NULL DEFAULT 0,
	CONSTRAINT "wa_log_files_pk" PRIMARY KEY (log_id, file_id)
);


-- 
-- Structure de la table "wa_session"
-- 
CREATE TABLE wa_session (
	"session_id"    CHAR(32) NOT NULL DEFAULT '',
 	"admin_id"      SMALLINT NOT NULL DEFAULT 0,
	"session_start" INTEGER  NOT NULL DEFAULT 0,
	"session_time"  INTEGER  NOT NULL DEFAULT 0,
	"session_ip"    CHAR(8)  NOT NULL DEFAULT '',
	"session_liste" SMALLINT NOT NULL DEFAULT 0,
	CONSTRAINT "wa_session_pk" PRIMARY KEY (session_id)
);

