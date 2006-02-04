-- 
-- Données de base de WAnewsletter 2.3.x
-- 
-- $Id$
-- 


-- 
-- Création d'un compte administrateur
-- 
INSERT INTO wa_admin (admin_login, admin_pwd, admin_email, admin_lang, admin_dateformat, admin_level)
	VALUES('admin', '', 'admin@domaine.com', 'francais', 'd M Y H:i', 2);
INSERT INTO wa_auth_admin (admin_id, liste_id, auth_view, auth_edit, auth_del, auth_send, auth_import, auth_export, auth_ban, auth_attach)
	VALUES (1, 1, 1, 1, 1, 1, 1, 1, 1, 1);


-- 
-- Configuration de base
-- 
INSERT INTO wa_config (sitename, urlsite, path, date_format, session_length, language, cookie_name, cookie_path, upload_path, max_filesize, engine_send, gd_img_type) 
	VALUES('Yourdomaine', 'http://www.yourdomaine.com', '/', 'd M Y H:i', 3600, 'francais', 'wanewsletter', '/', 'upload/', 80000, 2, 'png');


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
