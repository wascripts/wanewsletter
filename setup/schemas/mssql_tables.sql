/*
  Schéma des tables de WAnewsletter 2.2.x
  Microsoft SQL Server
 
  02 mars 2004 - Bobe
*/


/*
  Structure de la table "wa_abo_liste"
*/
CREATE TABLE [wa_abo_liste] (
	[abo_id] [int] NOT NULL,
	[liste_id] [smallint] NOT NULL,
	[format] [smallint] NOT NULL,
	[send] [smallint] NOT NULL
) ON [PRIMARY]
GO


/*
  Structure de la table "wa_abonnes"
*/
CREATE TABLE [wa_abonnes] (
	[abo_id] [int] IDENTITY (1, 1) NOT NULL,
	[abo_pseudo] [varchar] (30) NOT NULL,
	[abo_pwd] [varchar] (32) NOT NULL,
	[abo_email] [varchar] (255) NOT NULL,
	[abo_lang] [varchar] (30) NOT NULL,
	[abo_register_key] [varchar] (32) NOT NULL,
	[abo_register_date] [int] NOT NULL,
	[abo_status] [smallint] NOT NULL
) ON [PRIMARY]
GO


/*
  Structure de la table "wa_admin"
*/
CREATE TABLE [wa_admin] (
	[admin_id] [smallint] IDENTITY (1, 1) NOT NULL,
	[admin_login] [varchar] (30) NOT NULL,
	[admin_pwd] [varchar] (32) NOT NULL,
	[admin_email] [varchar] (255) NOT NULL,
	[admin_lang] [varchar] (30) NOT NULL,
	[admin_dateformat] [varchar] (20) NOT NULL,
	[admin_level] [smallint] NOT NULL,
	[email_new_inscrit] [smallint] NOT NULL
) ON [PRIMARY]
GO


/*
  Structure de la table "wa_auth_admin"
*/
CREATE TABLE [wa_auth_admin] (
	[admin_id] [smallint] NOT NULL,
	[liste_id] [smallint] NOT NULL,
	[auth_view] [smallint] NOT NULL,
	[auth_edit] [smallint] NOT NULL,
	[auth_del] [smallint] NOT NULL,
	[auth_send] [smallint] NOT NULL,
	[auth_import] [smallint] NOT NULL,
	[auth_export] [smallint] NOT NULL,
	[auth_ban] [smallint] NOT NULL,
	[auth_attach] [smallint] NOT NULL
) ON [PRIMARY]
GO


/*
  Structure de la table "wa_ban_list"
*/
CREATE TABLE [wa_ban_list] (
 	[ban_id] [smallint] IDENTITY (1, 1) NOT NULL,
	[liste_id] [smallint] NOT NULL,
	[ban_email] [varchar] (255) NOT NULL
) ON [PRIMARY]
GO


/*
  Structure de la table "wa_config"
*/
CREATE TABLE [wa_config] (
	[sitename] [varchar] (100) NOT NULL,
	[urlsite] [varchar] (100) NOT NULL,
	[path] [varchar] (100) NOT NULL,
	[hebergeur] [smallint] NOT NULL,
	[date_format] [varchar] (20) NOT NULL,
	[session_length] [smallint] NOT NULL,
	[language] [varchar] (30) NOT NULL,
	[cookie_name] [varchar] (100) NOT NULL,
	[cookie_path] [varchar] (100) NOT NULL,
	[upload_path] [varchar] (100) NOT NULL,
	[max_filesize] [int] NOT NULL,
	[use_ftp] [smallint] NOT NULL,
	[ftp_server] [varchar] (50) NOT NULL,
	[ftp_port] [smallint] NOT NULL,
	[ftp_pasv] [smallint] NOT NULL,
	[ftp_path] [varchar] (100) NOT NULL,
	[ftp_user] [varchar] (30) NOT NULL,
	[ftp_pass] [varchar] (30) NOT NULL,
	[engine_send] [smallint] NOT NULL,
	[emails_sended] [smallint] NOT NULL,
	[use_smtp] [smallint] NOT NULL,
	[smtp_host] [varchar] (100) NOT NULL,
	[smtp_port] [smallint] NOT NULL,
	[smtp_user] [varchar] (50) NOT NULL,
	[smtp_pass] [varchar] (50) NOT NULL,
	[disable_stats] [smallint] NOT NULL,
	[gd_img_type] [varchar] (5) NOT NULL,
	[check_email_mx] [smallint] NOT NULL,
	[enable_profil_cp] [smallint] NOT NULL,
	[mailing_startdate] [int] NOT NULL,
	[version] [varchar] (10) NOT NULL
) ON [PRIMARY]
GO


/*
  Structure de la table "wa_forbidden_ext"
*/
CREATE TABLE [wa_forbidden_ext] (
	[fe_id] [smallint] IDENTITY (1, 1) NOT NULL,
	[liste_id] [smallint] NOT NULL,
	[fe_ext] [varchar] (10) NOT NULL
) ON [PRIMARY]
GO


/*
  Structure de la table "wa_joined_files"
*/
CREATE TABLE [wa_joined_files] (
	[file_id] [int] IDENTITY (1, 1) NOT NULL,
	[file_real_name] [varchar] (200) NOT NULL,
	[file_physical_name] [varchar] (200) NOT NULL,
	[file_size] [int] NOT NULL,
	[file_mimetype] varchar(100) NOT NULL
) ON [PRIMARY]
GO


/*
  Structure de la table "wa_liste"
*/
CREATE TABLE [wa_liste] (
	[liste_id] [smallint] IDENTITY (1, 1) NOT NULL, 
	[liste_name] [varchar] (100) NOT NULL,
	[liste_format] [smallint] NOT NULL,
  	[sender_email] [varchar] (250) NOT NULL,
	[return_email] [varchar] (250) NOT NULL,
	[confirm_subscribe] [smallint] NOT NULL,
	[limitevalidate] [smallint] NOT NULL,
	[form_url] [varchar] (255) NOT NULL,
	[liste_sig] [text] NOT NULL,
	[auto_purge] [smallint] NOT NULL,
	[purge_freq] [smallint] NOT NULL,
	[purge_next] [int] NOT NULL,
	[liste_startdate] [int] NOT NULL,
	[liste_alias] [varchar] (250) NOT NULL,
	[liste_numlogs] [smallint] NOT NULL,
	[use_cron] [smallint] NOT NULL,
	[pop_host] [varchar] (100) NOT NULL,
	[pop_port] [smallint] NOT NULL,
	[pop_user] [varchar] (50) NOT NULL,
	[pop_pass] [varchar] (50) NOT NULL
) ON [PRIMARY]
GO


/*
  Structure de la table "wa_log"
*/
CREATE TABLE [wa_log] (
  [log_id] [smallint] IDENTITY (1, 1) NOT NULL, 
  [liste_id] [smallint] NOT NULL,
  [log_subject] [varchar] (100) NOT NULL,
  [log_body_html] [text] NOT NULL,
  [log_body_text] [text] NOT NULL,
  [log_date] [int] NOT NULL,
  [log_status] [smallint] NOT NULL,
  [log_numdest] [smallint] NOT NULL
) ON [PRIMARY]
GO


/*
  Structure de la table "wa_log_files"
*/
CREATE TABLE [wa_log_files] (
	[log_id] [smallint] NOT NULL,
	[file_id] [int] NOT NULL
) ON [PRIMARY]
GO


/*
  Structure de la table "wa_session"
*/
CREATE TABLE [wa_session] (
	[session_id] [char] (32) NOT NULL,
	[admin_id] [smallint] NOT NULL,
	[session_start] [int] NOT NULL,
	[session_time] [int] NOT NULL,
	[session_ip] [char] (8) NOT NULL,
	[session_liste] [smallint] NOT NULL
) ON [PRIMARY]
GO


ALTER TABLE [wa_abo_liste] WITH NOCHECK ADD 
	CONSTRAINT [PK_wa_abo_liste] PRIMARY KEY  CLUSTERED 
	(
		[abo_id], [liste_id]
	)  ON [PRIMARY] 
GO

ALTER TABLE [wa_abonnes] WITH NOCHECK ADD 
	CONSTRAINT [PK_wa_abonnes] PRIMARY KEY  CLUSTERED 
	(
		[abo_id]
	)  ON [PRIMARY] 
GO

CREATE INDEX [IX_wa_abonnes] ON [wa_abonnes] ([abo_status]) ON [PRIMARY]
GO

ALTER TABLE [wa_admin] WITH NOCHECK ADD 
	CONSTRAINT [PK_wa_admin] PRIMARY KEY  CLUSTERED 
	(
		[admin_id]
	)  ON [PRIMARY] 
GO

CREATE INDEX [IX_wa_auth_admin] ON [wa_auth_admin] ([admin_id]) ON [PRIMARY]
GO

ALTER TABLE [wa_ban_list] WITH NOCHECK ADD 
	CONSTRAINT [PK_wa_ban_list] PRIMARY KEY  CLUSTERED 
	(
		[ban_id]
	)  ON [PRIMARY] 
GO

ALTER TABLE [wa_forbidden_ext] WITH NOCHECK ADD 
	CONSTRAINT [PK_wa_forbidden_ext] PRIMARY KEY  CLUSTERED 
	(
		[fe_id]
	)  ON [PRIMARY] 
GO

ALTER TABLE [wa_joined_files] WITH NOCHECK ADD 
	CONSTRAINT [PK_wa_joined_files] PRIMARY KEY  CLUSTERED 
	(
		[file_id]
	)  ON [PRIMARY] 
GO

ALTER TABLE [wa_liste] WITH NOCHECK ADD 
	CONSTRAINT [PK_wa_liste] PRIMARY KEY  CLUSTERED 
	(
		[liste_id]
	)  ON [PRIMARY] 
GO

ALTER TABLE [wa_log] WITH NOCHECK ADD 
	CONSTRAINT [PK_wa_log] PRIMARY KEY  CLUSTERED 
	(
		[log_id]
	)  ON [PRIMARY] 
GO

CREATE INDEX [IX_wa_log] ON [wa_log] ([liste_id]) ON [PRIMARY]
GO

CREATE INDEX [IX_wa_log_status] ON [wa_log] ([log_status]) ON [PRIMARY]
GO

ALTER TABLE [wa_log_files] WITH NOCHECK ADD 
	CONSTRAINT [PK_wa_log_files] PRIMARY KEY  CLUSTERED 
	(
		[log_id], [file_id]
	)  ON [PRIMARY] 
GO

CREATE INDEX [IX_wa_session] ON [wa_session] ([session_id]) ON [PRIMARY]
GO

ALTER TABLE [wa_abo_liste] WITH NOCHECK ADD 
	CONSTRAINT [DF_wa_abo_liste_liste_id] DEFAULT (0) FOR [liste_id],
	CONSTRAINT [DF_wa_abo_liste_format] DEFAULT (1) FOR [format],
	CONSTRAINT [DF_wa_abo_liste_send] DEFAULT (0) FOR [send]
GO

ALTER TABLE [wa_abonnes] WITH NOCHECK ADD 
	CONSTRAINT [DF_wa_abonnes_abo_register_date] DEFAULT (0) FOR [abo_register_date],
	CONSTRAINT [DF_wa_abonnes_abo_status] DEFAULT (0) FOR [abo_status]
GO

ALTER TABLE [wa_admin] WITH NOCHECK ADD 
	CONSTRAINT [DF_wa_admin_admin_level] DEFAULT (1) FOR [admin_level],
	CONSTRAINT [DF_wa_admin_email_new_inscrit] DEFAULT (1) FOR [email_new_inscrit]
GO

ALTER TABLE [wa_auth_admin] WITH NOCHECK ADD 
	CONSTRAINT [DF_wa_auth_admin_admin_id] DEFAULT (0) FOR [admin_id],
	CONSTRAINT [DF_wa_auth_admin_liste_id] DEFAULT (0) FOR [liste_id],
	CONSTRAINT [DF_wa_auth_admin_auth_view] DEFAULT (0) FOR [auth_view],
	CONSTRAINT [DF_wa_auth_admin_auth_edit] DEFAULT (0) FOR [auth_edit],
	CONSTRAINT [DF_wa_auth_admin_auth_del] DEFAULT (0) FOR [auth_del],
	CONSTRAINT [DF_wa_auth_admin_auth_send] DEFAULT (0) FOR [auth_send],
	CONSTRAINT [DF_wa_auth_admin_auth_import] DEFAULT (0) FOR [auth_import],
	CONSTRAINT [DF_wa_auth_admin_auth_export] DEFAULT (0) FOR [auth_export],
	CONSTRAINT [DF_wa_auth_admin_auth_ban] DEFAULT (0) FOR [auth_ban],
	CONSTRAINT [DF_wa_auth_admin_auth_attach] DEFAULT (0) FOR [auth_attach]
GO

ALTER TABLE [wa_ban_list] WITH NOCHECK ADD 
	CONSTRAINT [DF_wa_ban_list_liste_id] DEFAULT (0) FOR [liste_id]
GO

ALTER TABLE [wa_config] WITH NOCHECK ADD 
	CONSTRAINT [DF_wa_config_hebergeur] DEFAULT (0) FOR [hebergeur],
	CONSTRAINT [DF_wa_config_session_length] DEFAULT (0) FOR [session_length],
	CONSTRAINT [DF_wa_config_max_filesize] DEFAULT (0) FOR [max_filesize],
	CONSTRAINT [DF_wa_config_use_ftp] DEFAULT (0) FOR [use_ftp],
	CONSTRAINT [DF_wa_config_ftp_port] DEFAULT (21) FOR [ftp_port],
	CONSTRAINT [DF_wa_config_ftp_pasv] DEFAULT (0) FOR [ftp_pasv],
	CONSTRAINT [DF_wa_config_engine_send] DEFAULT (0) FOR [engine_send],
	CONSTRAINT [DF_wa_config_emails_sended] DEFAULT (0) FOR [emails_sended],
	CONSTRAINT [DF_wa_config_use_smtp] DEFAULT (0) FOR [use_smtp],
	CONSTRAINT [DF_wa_config_smtp_port] DEFAULT (25) FOR [smtp_port],
	CONSTRAINT [DF_wa_config_disable_stats] DEFAULT (0) FOR [disable_stats],
	CONSTRAINT [DF_wa_config_check_email_mx] DEFAULT (0) FOR [check_email_mx],
	CONSTRAINT [DF_wa_config_enable_profil_cp] DEFAULT (0) FOR [enable_profil_cp],
	CONSTRAINT [DF_wa_config_mailing_startdate] DEFAULT (0) FOR [mailing_startdate]
GO

ALTER TABLE [wa_forbidden_ext] WITH NOCHECK ADD 
	CONSTRAINT [DF_wa_forbidden_ext_liste_id] DEFAULT (0) FOR [liste_id]
GO

ALTER TABLE [wa_joined_files] WITH NOCHECK ADD 
	CONSTRAINT [DF_wa_joined_files_file_size] DEFAULT (0) FOR [file_size]
GO

ALTER TABLE [wa_liste] WITH NOCHECK ADD 
	CONSTRAINT [DF_wa_liste_liste_format] DEFAULT (1) FOR [liste_format],
	CONSTRAINT [DF_wa_liste_confirm_subscribe] DEFAULT (0) FOR [confirm_subscribe],
	CONSTRAINT [DF_wa_liste_limitevalidate] DEFAULT (3) FOR [limitevalidate],
	CONSTRAINT [DF_wa_liste_auto_purge] DEFAULT (0) FOR [auto_purge],
	CONSTRAINT [DF_wa_liste_purge_freq] DEFAULT (0) FOR [purge_freq],
	CONSTRAINT [DF_wa_liste_purge_next] DEFAULT (0) FOR [purge_next],
	CONSTRAINT [DF_wa_liste_liste_startdate] DEFAULT (0) FOR [liste_startdate],
	CONSTRAINT [DF_wa_liste_use_cron] DEFAULT (0) FOR [use_cron],
	CONSTRAINT [DF_wa_liste_pop_port] DEFAULT (110) FOR [pop_port],
	CONSTRAINT [DF_wa_liste_liste_numlogs] DEFAULT (0) FOR [liste_numlogs]
GO

ALTER TABLE [wa_log] WITH NOCHECK ADD 
	CONSTRAINT [DF_wa_log_liste_id] DEFAULT (0) FOR [liste_id],
	CONSTRAINT [DF_wa_log_log_date] DEFAULT (0) FOR [log_date],
	CONSTRAINT [DF_wa_log_log_status] DEFAULT (0) FOR [log_status],
	CONSTRAINT [DF_wa_log_log_numdest] DEFAULT (0) FOR [log_numdest]
GO

ALTER TABLE [wa_log_files] WITH NOCHECK ADD 
	CONSTRAINT [DF_wa_log_files_log_id] DEFAULT (0) FOR [log_id],
	CONSTRAINT [DF_wa_log_files_file_id] DEFAULT (0) FOR [file_id]
GO

ALTER TABLE [wa_session] WITH NOCHECK ADD 
	CONSTRAINT [DF_wa_session_admin_id] DEFAULT (0) FOR [admin_id],
	CONSTRAINT [DF_wa_session_session_start] DEFAULT (0) FOR [session_start],
	CONSTRAINT [DF_wa_session_session_time] DEFAULT (0) FOR [session_time],
	CONSTRAINT [DF_wa_session_session_liste] DEFAULT (0) FOR [session_liste]
GO
