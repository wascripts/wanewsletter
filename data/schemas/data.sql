-- 
-- Données de base de WAnewsletter
-- 


-- 
-- Création d'un compte administrateur (mot de passe par défaut: admin)
-- 
INSERT INTO wa_admin (admin_login, admin_pwd, admin_email, admin_lang, admin_dateformat, admin_level)
	VALUES('admin', '$P$D6MbHmah9V/JY/9H3.dRGKpCVS65su0', 'admin@domaine.com', 'francais', 'd M Y H:i', 2);
INSERT INTO wa_auth_admin (admin_id, liste_id, auth_view, auth_edit, auth_del, auth_send, auth_import, auth_export, auth_ban, auth_attach)
	VALUES (1, 1, 1, 1, 1, 1, 1, 1, 1, 1);


-- 
-- Configuration de base
-- 
INSERT INTO wa_config (config_name, config_value) VALUES('sitename',       'Yourdomaine');
INSERT INTO wa_config (config_name, config_value) VALUES('urlsite',        'http://www.yourdomaine.com');
INSERT INTO wa_config (config_name, config_value) VALUES('path',           '/');
INSERT INTO wa_config (config_name, config_value) VALUES('date_format',    'd M Y H:i');
INSERT INTO wa_config (config_name, config_value) VALUES('session_length', '3600');
INSERT INTO wa_config (config_name, config_value) VALUES('language',       'francais');
INSERT INTO wa_config (config_name, config_value) VALUES('cookie_name',    'wanewsletter');
INSERT INTO wa_config (config_name, config_value) VALUES('cookie_path',    '/');
INSERT INTO wa_config (config_name, config_value) VALUES('upload_path',    'data/uploads/');
INSERT INTO wa_config (config_name, config_value) VALUES('max_filesize',   '80000');
INSERT INTO wa_config (config_name, config_value) VALUES('use_ftp',        '0');
INSERT INTO wa_config (config_name, config_value) VALUES('ftp_server',     '');
INSERT INTO wa_config (config_name, config_value) VALUES('ftp_port',       '21');
INSERT INTO wa_config (config_name, config_value) VALUES('ftp_pasv',       '0');
INSERT INTO wa_config (config_name, config_value) VALUES('ftp_path',       '');
INSERT INTO wa_config (config_name, config_value) VALUES('ftp_user',       '');
INSERT INTO wa_config (config_name, config_value) VALUES('ftp_pass',       '');
INSERT INTO wa_config (config_name, config_value) VALUES('engine_send',    '2');
INSERT INTO wa_config (config_name, config_value) VALUES('sending_limit',  '0');
INSERT INTO wa_config (config_name, config_value) VALUES('use_smtp',       '0');
INSERT INTO wa_config (config_name, config_value) VALUES('smtp_host',      '');
INSERT INTO wa_config (config_name, config_value) VALUES('smtp_port',      '25');
INSERT INTO wa_config (config_name, config_value) VALUES('smtp_user',      '');
INSERT INTO wa_config (config_name, config_value) VALUES('smtp_pass',      '');
INSERT INTO wa_config (config_name, config_value) VALUES('disable_stats',  '0');
INSERT INTO wa_config (config_name, config_value) VALUES('gd_img_type',    'png');
INSERT INTO wa_config (config_name, config_value) VALUES('check_email_mx', '0');
INSERT INTO wa_config (config_name, config_value) VALUES('enable_profil_cp', '0');
INSERT INTO wa_config (config_name, config_value) VALUES('mailing_startdate', '0');
INSERT INTO wa_config (config_name, config_value) VALUES('db_version',     '13');


-- 
-- Extensions interdites par défaut
-- 
INSERT INTO wa_forbidden_ext (liste_id, fe_ext) VALUES(1, 'exe');
INSERT INTO wa_forbidden_ext (liste_id, fe_ext) VALUES(1, 'php');
INSERT INTO wa_forbidden_ext (liste_id, fe_ext) VALUES(1, 'php3');
INSERT INTO wa_forbidden_ext (liste_id, fe_ext) VALUES(1, 'scr');
INSERT INTO wa_forbidden_ext (liste_id, fe_ext) VALUES(1, 'pif');
INSERT INTO wa_forbidden_ext (liste_id, fe_ext) VALUES(1, 'bat');

-- 
-- Insertion d'une liste de diffusion par défaut 
-- 
INSERT INTO wa_liste (liste_name, liste_format, sender_email, form_url, liste_sig)
	VALUES('Default list', 1, 'mailing@yourdomaine.com', 'http://www.yourdomaine.com/form.php', 'Signature de la newsletter');
