<?php
/*******************************************************************
 *
 *          Fichier         :   lang_francais.php [francais]
 *          Créé le         :   29 juin 2002
 *          Dernière modif  :   26 septembre 2005
 *          Email           :   wascripts@phpcodeur.net
 *
 *              Copyright © 2002-2005 phpCodeur
 *
 *******************************************************************/

/*******************************************************************
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 *******************************************************************/

/********************************************************************
 * Vous pouez très facilement modifier WAnewsletter dans une autre
 * langue.
 * Pour cela, il vous suffit de traduire ce qui se trouve entre
 * guillemets. Attention, ne touchez pas à la partie $lang['....']
 *
 * des %1\$s, %s, %d ou autre signe de ce genre signifient qu'ils
 * vont être remplacés par un contenu variable. Placez les de façon
 * adéquat dans la phrase mais ne les enlevez pas.
 * Enfin, les \n représentent un retour à la ligne.
 ********************************************************************/


$lang['General_title']              = "Administration des listes de diffusion";

$lang['Title']['accueil']           = "Informations générales sur la newsletter";
$lang['Title']['install']           = "Installation de WAnewsletter";
$lang['Title']['update']            = "Mise à jour de WAnewsletter";
$lang['Title']['reinstall_update']  = "Réinstallation ou mise à jour de WAnewsletter";
$lang['Title']['database']          = "Accès base de données";
$lang['Title']['admin']             = "Administration";
$lang['Title']['error']             = "Erreur !";
$lang['Title']['info']              = "Information !";
$lang['Title']['select']            = "Sélection";
$lang['Title']['confirm']           = "Confirmation";
$lang['Title']['config_lang']       = "Choix de la langue";
$lang['Title']['config_perso']      = "Personnalisation";
$lang['Title']['config_cookies']    = "Cookies";
$lang['Title']['config_email']      = "Envois des emails";
$lang['Title']['config_files']      = "Fichiers joints";
$lang['Title']['config_stats']      = "Module de statistiques";
$lang['Title']['config_divers']     = "Divers";
$lang['Title']['profile']           = "Profil de %s";
$lang['Title']['manage']            = "Actions possibles de l'utilisateur";
$lang['Title']['other_options']     = "Options diverses";
$lang['Title']['info_liste']        = "Informations sur la liste de diffusion";
$lang['Title']['add_liste']         = "Créer une liste de diffusion";
$lang['Title']['edit_liste']        = "Éditer une liste de diffusion";
$lang['Title']['purge_sys']         = "Système de purge";
$lang['Title']['cron']              = "Option cron";
$lang['Title']['logs']              = "Liste des newsletters envoyées à cette liste";
$lang['Title']['abo']               = "Liste des abonnés de cette liste de diffusion";
$lang['Title']['stats']             = "Statistiques des listes de diffusion";
$lang['Title']['tools']             = "Outils WAnewsletter";
$lang['Title']['export']            = "Exporter des adresses emails";
$lang['Title']['import']            = "Importer des adresses emails";
$lang['Title']['ban']               = "Gestion des emails bannis";
$lang['Title']['attach']            = "Gestion des extensions de fichiers";
$lang['Title']['backup']            = "Système de sauvegarde";
$lang['Title']['restore']           = "Système de restauration";
$lang['Title']['generator']         = "Générateur de formulaires d'inscriptions";
$lang['Title']['send']              = "Formulaire d'envoi";
$lang['Title']['join']              = "Joindre un fichier à la newsletter";
$lang['Title']['joined_files']      = "Fichiers joints à cette newsletter";
$lang['Title']['Show_popup']        = "Aperçu de %s";
$lang['Title']['profil_cp']         = "Panneau de gestion de compte";
$lang['Title']['sendkey']           = "Recevoir vos identifiants";
$lang['Title']['archives']          = "Archives des listes de diffusion";


//
// Modules de l'administration
//
$lang['Module']['accueil']          = "Accueil";
$lang['Module']['config']           = "Configuration";
$lang['Module']['login']            = "Connexion";
$lang['Module']['logout']           = "Déconnexion [%s]";
$lang['Module']['send']             = "Envoi";
$lang['Module']['users']            = "Utilisateurs";
$lang['Module']['subscribers']      = "Inscrits";
$lang['Module']['list']             = "Listes";
$lang['Module']['log']              = "Archives";
$lang['Module']['tools']            = "Outils";
$lang['Module']['stats']            = "Statistiques";
$lang['Module']['editprofile']      = "Éditer votre profil";


//
// Texte des divers boutons
//
$lang['Button']['valid']            = "Valider";
$lang['Button']['reset']            = "Réinitialiser";
$lang['Button']['go']               = "Aller";
$lang['Button']['edit']             = "Modifier";
$lang['Button']['delete']           = "Supprimer";
$lang['Button']['cancel']           = "Annuler";
$lang['Button']['purge']            = "Purger";
$lang['Button']['classer']          = "Classer";
$lang['Button']['search']           = "Chercher";
$lang['Button']['save']             = "Sauvegarder";
$lang['Button']['send']             = "Envoyer";
$lang['Button']['preview']          = "Prévisualiser";
$lang['Button']['add_file']         = "Joindre un fichier";
$lang['Button']['del_file']         = "Supprimer les fichiers sélectionnés";

$lang['Button']['del_abo']          = "Supprimer les abonnés sélectionnés";
$lang['Button']['del_logs']         = "Supprimer les newsletters sélectionnés";
$lang['Button']['del_account']      = "Supprimer ce compte";
$lang['Button']['links']            = "Placer le lien de désinscription";
$lang['Button']['dl']               = "Télécharger";
$lang['Button']['conf']             = "Confirmer";


//
// Différents messages d'information et d'erreur
//
$lang['Message']['Subscribe_1']             = "Inscription réussie !\nVous allez recevoir un email de confirmation.\nAttention, le lien de confirmation contenu dans l'email sera valide pendant %d jours !\nPassé ce délai, il vous faudra vous réinscrire.";
$lang['Message']['Subscribe_2']             = "Inscription réussie !";
$lang['Message']['Confirm_ok']              = "Votre inscription a été confirmée !";
$lang['Message']['Confirm_double']          = "Vous avez déja confirmé votre inscription";
$lang['Message']['Unsubscribe_1']           = "Ok, vous allez recevoir un email qui vous permettra de confirmer votre choix";
$lang['Message']['Unsubscribe_2']           = "Vous n'êtes désormais plus inscrit à cette liste de diffusion";
$lang['Message']['Unsubscribe_3']           = "Votre email a bien été retiré de notre base de données";
$lang['Message']['Success_setformat']       = "Le changement de format a été effectué avec succés";
$lang['Message']['Invalid_email']           = "L'adresse email que vous avez indiquée n'est pas valide";
$lang['Message']['Unrecognized_email']      = "Domaine inconnu ou compte non reconnu par le serveur";
$lang['Message']['Unknown_email']           = "Email inconnu";
$lang['Message']['Email_banned']            = "Cet email ou ce type d'email a été banni";
$lang['Message']['Allready_reg']            = "Vous êtes déja inscrit !";
$lang['Message']['Allready_confirm']        = "Vous avez déja confirmé votre inscription !";
$lang['Message']['Unknown_list']            = "Liste inconnue";
$lang['Message']['Failed_sending']          = "L'email n'a pu être envoyé !";
$lang['Message']['Inactive_format']         = "Impossible de changer de format";
$lang['Message']['Invalid_date']            = "Désolé, la date de confirmation est dépassée";
$lang['Message']['Invalid_code']            = "Code invalide !";
$lang['Message']['Failed_sending2']         = "L'email n'a pu être envoyé ! %s";

$lang['Message']['Success_export']          = "L'exportation des emails a été effectuée avec succés. \nVous trouverez le fichier de sauvegarde dans le dossier des fichiers temporaires du script (Pensez à le supprimer après l'avoir récupéré !)";
$lang['Message']['Success_import']          = "Les emails ont été importés avec succés";
$lang['Message']['Success_import2']         = "L'importation s'est effectuée avec succés mais certains emails ont été refusés";
$lang['Message']['Success_import3']         = "L'importation s'est effectuée avec succés mais certains emails ont été refusés. \nCliquez %sici%s pour télécharger le rapport (N'oubliez pas de supprimer le fichier du serveur par la suite)";
$lang['Message']['Success_modif']           = "Les modifications ont été effectuées avec succés";
$lang['Message']['Success_backup']          = "La sauvegarde des tables a été effectuée avec succés. \nVous trouverez le fichier de sauvegarde dans le dossier des fichiers temporaires du script (Pensez à le supprimer après l'avoir récupéré !)";
$lang['Message']['Success_restore']         = "La restauration des données a été effectuée avec succés";
$lang['Message']['Success_logout']          = "Vous avez été déconnecté de l'administration";
$lang['Message']['Success_purge']           = "La purge a été effectuée avec succés (%d abonné(s) supprimé(s))";
$lang['Message']['Success_send']            = "L'envoi partiel a été effectué avec succés à <b>%d</b> abonnés.\nLa lettre de diffusion a été envoyée jusqu'à présent à <b>%d</b> abonnés sur un total de <b>%d</b>";
$lang['Message']['Success_send_finish']     = "Envoi terminé avec succés.\nCette lettre de diffusion a été envoyée à un total de <b>%d</b> abonnés";
$lang['Message']['Success_operation']       = "L'opération a été effectuée avec succés";
$lang['Message']['SQLite_backup']           = "Pour faire une sauvegarde de votre base de données SQLite, il vous suffit de récupérer via FTP le fichier <samp>%s</samp>";
$lang['Message']['SQLite_restore']          = "Pour restaurer votre base de données SQLite, il vous suffit simplement de replacer le fichier de données à l'emplacement suivant: <samp>%s</samp>";


$lang['Message']['Profile_updated']         = "Le profil a été mis à jour avec succés";
$lang['Message']['Admin_added']             = "L'utilisateur a été ajouté avec succés, il va recevoir par email ses identifiants de connexion";
$lang['Message']['Admin_deleted']           = "L'utilisateur a été supprimé avec succés";
$lang['Message']['liste_created']           = "La nouvelle liste de diffusion a été créée avec succés";
$lang['Message']['liste_edited']            = "La liste de diffusion a été modifiée avec succés";
$lang['Message']['Liste_del_all']           = "La liste a été supprimée avec succés, ainsi que les abonnés et newsletters qui y étaient rattachés";
$lang['Message']['Liste_del_move']          = "La liste a été supprimée avec succés.\nLes abonnés et newsletters qui y étaient rattachés ont été déplacés vers la liste sélectionnée";
$lang['Message']['logs_deleted']            = "Les newsletters ont été supprimés avec succés";
$lang['Message']['log_deleted']             = "La newsletter a été supprimée avec succés";
$lang['Message']['log_saved']               = "La newsletter a été sauvegardée avec succés";
$lang['Message']['log_ready']               = "La newsletter a été sauvegardée avec succés et est prète à être envoyée";
$lang['Message']['abo_deleted']             = "Les abonnés ont été supprimés avec succés";

$lang['Message']['Not_authorized']          = "Vous n'avez pas les permissions suffisantes pour accéder à cette page ou exécuter cette action";
$lang['Message']['Not_auth_view']           = "Vous n'êtes pas autorisé à visualiser cette liste de diffusion";
$lang['Message']['Not_auth_edit']           = "Vous n'êtes pas autorisé à effectuer des modifications sur cette liste de diffusion";
$lang['Message']['Not_auth_del']            = "Vous n'êtes pas autorisé à effectuer des suppressions sur cette liste de diffusion";
$lang['Message']['Not_auth_send']           = "Vous n'êtes pas autorisé à effectuer des envois à cette liste de diffusion";
$lang['Message']['Not_auth_import']         = "Vous n'êtes pas autorisé à importer des adresses emails dans cette liste de diffusion";
$lang['Message']['Not_auth_export']         = "Vous n'êtes pas autorisé à exporter des adresses emails de cette liste de diffusion";
$lang['Message']['Not_auth_ban']            = "Vous n'êtes pas autorisé à effectuer des modifications sur la liste de bannissement de cette liste de diffusion";
$lang['Message']['Not_auth_attach']         = "Vous n'êtes pas autorisé à joindre des fichiers ou à voir les fichiers joints de cette liste de diffusion";

$lang['Message']['Error_login']             = "Login ou mot de passe incorrect !";
$lang['Message']['Bad_confirm_pass']        = "Nouveau mot de passe et confirmation de mot de passe sont différents";
$lang['Message']['bad_ftp_param']           = "La connexion au serveur ftp n'a pu être établie, vérifiez vos paramètres \n(%s)";
$lang['Message']['bad_smtp_param']          = "La connexion au serveur smtp n'a pu être établie, vérifiez vos paramètres \n(%s)";
$lang['Message']['bad_pop_param']           = "La connexion au serveur pop n'a pu être établie, vérifiez vos paramètres \n(%s)";
$lang['Message']['Alphanum_pass']           = "Le mot de passe doit être composé de 4 à 30 caractères qui soient alphanumériques, du tiret (-) et/ou de _";
$lang['Message']['Invalid_session']         = "Session non valide !";
$lang['Message']['fields_empty']            = "Certains champs obligatoires ne sont pas remplis";
$lang['Message']['Owner_account']           = "Vous ne pouvez pas supprimer votre propre compte !";
$lang['Message']['Invalid_login']           = "Ce pseudo n'est pas valide, le pseudo doit faire entre 2 et 30 caractères";
$lang['Message']['Double_login']            = "Un utilisateur utilise déja ce pseudo";
$lang['Message']['No_liste_exists']         = "Aucune liste n'est disponible";
$lang['Message']['No_liste_id']             = "Aucune liste de diffusion n'a été sélectionnée";
$lang['Message']['No_log_id']               = "Aucune newsletter n'a été sélectionnée";
$lang['Message']['log_not_exists']          = "Cette newsletter n'existe pas !";
$lang['Message']['No_log_to_load']          = "Il n'y a actuellement aucune newsletter à charger";
$lang['Message']['No_log_to_send']          = "Il n'y a actuellement aucun envoi à reprendre";
$lang['Message']['No_abo_id']               = "Aucun abonné n'a été sélectionné";
$lang['Message']['No_abo_email']            = "Aucune de ces adresses email n'est présente dans cette liste de diffusion";
$lang['Message']['abo_not_exists']          = "Cet abonné n'existe pas !";
$lang['Message']['Failed_open_file']        = "Impossible d'ouvrir le fichier reçu";
$lang['Message']['File_not_exists']         = "Le fichier %s ne semble pas être présent sur le serveur";
$lang['Message']['Bad_file_type']           = "Le type de fichier reçu a été interdit ou n'est pas valide";
$lang['Message']['Error_local']             = "Aucun fichier trouvé au chemin %s";
$lang['Message']['No_data_received']        = "Aucune donnée n'a été réceptionnée";
$lang['Message']['Stats_disabled']          = "Le module de statistiques a été désactivé";
$lang['Message']['No_gd_lib']               = "Ce module requiert la librairie GD, or celle-ci ne semble pas présente sur le serveur";
$lang['Message']['No_subscribers']          = "Vous ne pouvez pas envoyer de newsletter à cette liste car elle ne compte pas encore d'abonné";
$lang['Message']['Unknown_engine']          = "Aucun moteur d'envoi spécifié !";
$lang['Message']['No_log_found']            = "Aucune newsletter prête à être envoyée n'a été trouvée";
$lang['Message']['Invalid_url']             = "L'url donnée n'est pas valide";
$lang['Message']['Unaccess_url']            = "L'url %s semble inaccessible actuellement";
$lang['Message']['Not_found_at_url']        = "Le fichier ne semble pas présent à l'url indiquée";
$lang['Message']['No_data_at_url']          = "Aucune donnée disponible sur le fichier";

$lang['Message']['tmp_dir_not_writable']    = "Le dossier des fichiers temporaires du script (tmp/ par défaut) n'existe pas ou n'est pas accessible en écriture";
$lang['Message']['stats_dir_not_writable']  = "Le dossier des statistiques du script (stats/ par défaut) n'existe pas ou n'est pas accessible en écriture";
$lang['Message']['sql_file_not_readable']   = "Les fichiers sql ne sont pas accessibles en lecture ! (setup/schemas/)";

$lang['Message']['Ftp_unable_connect']      = "Impossible de se connecter au serveur ftp";
$lang['Message']['Ftp_error_login']         = "L'authentification auprès du serveur ftp a échoué";
$lang['Message']['Ftp_error_mode']          = "Impossible de changer le mode du serveur";
$lang['Message']['Ftp_error_path']          = "Impossible d'accéder au dossier spécifié";
$lang['Message']['Ftp_error_put']           = "Impossible d'uploader le fichier sur le serveur ftp";
$lang['Message']['Ftp_error_get']           = "Impossible de récupérer le fichier du serveur ftp";
$lang['Message']['Ftp_error_del']           = "Impossible de supprimer le fichier du serveur ftp";

$lang['Message']['Upload_error_1']          = "Le fichier excède le poids autorisé par la directive upload_max_filesize de php.ini";
$lang['Message']['Upload_error_2']          = "Le fichier excède le poids autorisé par le champ MAX_FILE_SIZE";
$lang['Message']['Upload_error_3']          = "Le fichier n'a été uploadé que partiellement";
$lang['Message']['Upload_error_4']          = "Aucun fichier n'a été uploadé";
$lang['Message']['Upload_error_5']          = "Une erreur inconnue est survenue, le fichier n'a pu être uploadé";
$lang['Message']['Invalid_filename']        = "Nom de fichier non valide";
$lang['Message']['Invalid_ext']             = "Cette extension de fichier a été interdite";
$lang['Message']['weight_too_big']          = "Le poids total des fichiers joints excède le maximum autorisé, il ne vous reste que %.2f octets de libre";

$lang['Message']['Compress_unsupported']    = "Format de compression non supporté";
$lang['Message']['Database_unsupported']    = "Cette base de données n'est pas supportée par le système de sauvegarde/restauration";

$lang['Message']['Profil_cp_disabled']      = "Le panneau de gestion de compte est actuellement désactivé";
$lang['Message']['Inactive_account']        = "Votre compte est actuellement inactif, vous avez dù recevoir un email pour l'activer.";
$lang['Message']['IDs_sended']              = "Vos identifiants vous ont été envoyés par email";
$lang['Message']['Logs_sent']               = "Les newsletters sélectionnées ont été envoyées à votre adresse: %s";


//
// Divers
//
$lang['Subscribe']                  = "Inscription";
$lang['Unsubscribe']                = "Désinscription";
$lang['Setformat']                  = "Changer de format";
$lang['Email_address']              = "Adresse email";
$lang['Format']                     = "Format";
$lang['Button_valid']               = "Valider";
$lang['Diff_list']                  = "Listes de diffusion";
$lang['Start']                      = "Début";
$lang['End']                        = "Fin";
$lang['Prev']                       = "Précédent";
$lang['Next']                       = "Suivant";
$lang['First_page']                 = "Première page";
$lang['Prev_page']                  = "Page précédente";
$lang['Next_page']                  = "Page suivante";
$lang['Last_page']                  = "Dernière page";
$lang['Yes']                        = "oui";
$lang['No']                         = "non";
$lang['Login']                      = "Login d'accès";
$lang['Password']                   = "Mot de passe d'accès";
$lang['Not_available']              = "Non disponible";
$lang['Seconds']                    = "secondes";
$lang['Days']                       = "jours";
$lang['Other']                      = "Autres";
$lang['Unknown']                    = "Inconnu";
$lang['Choice_liste']               = "Sélectionnez une liste";
$lang['View_liste']                 = "Gérer une liste";
$lang['Admin']                      = "Administrateur";
$lang['User']                       = "Utilisateur";
$lang['Page_of']                    = "Page <b>%d</b> sur <b>%d</b>";
$lang['Classement']                 = "Classer par";
$lang['By_subject']                 = "par sujet";
$lang['By_date']                    = "par date";
$lang['By_email']                   = "par email";
$lang['By_format']                  = "par format";
$lang['By_asc']                     = "croissant";
$lang['By_desc']                    = "décroissant";
$lang['Filename']                   = "Nom du fichier";
$lang['Filesize']                   = "Taille du fichier";
$lang['No_data']                    = "Non fourni";
$lang['MO']                         = "Mo";
$lang['KO']                         = "Ko";
$lang['Octets']                     = "Octets";
$lang['Wait_loading']               = "Veuillez patienter pendant le chargement de la page";
$lang['Show']                       = "Visualiser";
$lang['View']                       = "Voir";
$lang['Edit']                       = "Éditer";
$lang['Import']                     = "Importer";
$lang['Export']                     = "Exporter";
$lang['Ban']                        = "Bannir";
$lang['Attach']                     = "Attacher";
$lang['Autologin']                  = "Se connecter automatiquement";
$lang['Faq']                        = "FAQ du script";
$lang['Author_note']                = "Notes de l'auteur";
$lang['Page_loading']               = "Veuillez patienter pendant le chargement de la page";
$lang['Label_link']                 = "Se désinscrire";
$lang['Account_login']              = "Entrez l'adresse email de votre compte";
$lang['Account_pass']               = "Mot de passe ou code de votre compte";

$lang['Click_return_index']         = "Cliquez %sici%s pour retourner sur l'accueil";
$lang['Click_return_back']          = "Cliquez %sici%s pour retourner sur la page précédente";
$lang['Click_return_form']          = "Cliquez %sici%s pour retourner au formulaire";
$lang['Click_start_send']           = "Cliquez %sici%s si vous souhaitez démarrer l'envoi maintenant";
$lang['Click_resend_auto']          = "Cliquez %sici%s pour continuer l'envoi de façon automatique";
$lang['Click_resend_manuel']        = "Cliquez %sici%s pour envoyer un autre flot d'emails";


//
// Sujets de divers emails envoyés
//
$lang['Subject_email']['Subscribe'] = "Inscription à la newsletter de %s";
$lang['Subject_email']['Unsubscribe'] = "Confirmation de désinscription";
$lang['Subject_email']['New_subscriber'] = "Nouvel inscrit à la newsletter";
$lang['Subject_email']['New_admin'] = "Administration de la newsletter de %s";
$lang['Subject_email']['New_pass']  = "Votre nouveau mot de passe";
$lang['Subject_email']['Sendkey']   = "Les identifiants de votre compte";


//
// Panneau de gestion de compte (profil_cp.php)
//
$lang['Welcome_profil_cp']          = "Bienvenue sur le panneau de gestion de votre compte.\nVous pouvez ici modifier votre profil abonné et consulter les archives.";
$lang['Explain']['editprofile']     = "Ici, vous avez la possibilité de modifier les données de votre compte.\nVous pouvez renseigner votre prénom ou pseudo pour personnaliser les newsletters que vous recevrez (selon les réglages de l'administrateur). Vous pouvez également mettre un mot de passe à votre compte, ce qui sera plus simple à taper que le code de votre compte.";
$lang['Explain']['sendkey']         = "Si vous avez perdu les identifiants de votre compte, vous pouvez demander à ce qu'ils vous soient renvoyés par email";
$lang['Explain']['archives']        = "Vous pouvez, à partir de cette page, demander à recevoir les précédentes newsletters envoyées aux listes de diffusion auxquelles vous êtes inscrit.\nAttention, pour chaque newsletter sélectionnée, vous recevrez un email.";
$lang['Lost_key']                   = "J'ai perdu mon code ou mon mot de passe";


//
// Page d'accueil
//
$lang['Explain']['accueil']         = "Bienvenue sur l'administration de WAnewsletter, nous vous remercions d'avoir choisi WAnewsletter comme solution de newsletter/mailing liste.\n L'administration vous permet de contrôler vos listes de diffusion de façon très simple. \nVous pouvez à tout moment retourner sur cette page en cliquant sur le logo WAnewsletter en haut à gauche de l'écran.";
$lang['Registered_subscribers']     = "Il y a au total <b>%1\$d</b> inscrits, soit <b>%2\$.2f</b> nouveaux inscrits par jour";
$lang['Registered_subscriber']      = "Il y a au total <b>%1\$d</b> inscrit, soit <b>%2\$.2f</b> nouveaux inscrits par jour";
$lang['Tmp_subscribers']            = "Il y a <b>%d</b> personnes n'ayant pas confirmé leur inscription";
$lang['Tmp_subscriber']             = "Il y a <b>%d</b> personne n'ayant pas confirmé son inscription";
$lang['Last_newsletter']            = "Dernière newsletter envoyée le <b>%s</b>";
$lang['Total_newsletters']          = "Un total de <b>%1\$d</b> newsletters ont été envoyées, soit <b>%2\$.2f</b> newsletters par mois";
$lang['Total_newsletter']           = "Un total de <b>%1\$d</b> newsletter a été envoyée, soit <b>%2\$.2f</b> newsletters par mois";
$lang['No_newsletter_sended']       = "Aucune newsletter n'a encore été envoyée";
$lang['Dbsize']                     = "Taille de la base de données (tables du script)";


//
// Page : Configuration
//
$lang['Explain']['config']          = "Le formulaire ci-dessous vous permet de configurer tous les aspects du script";
$lang['Explain']['config_cookies']  = "Ces paramètres vous permettent de régler les cookies utilisés par le script. \nSi vous n'êtes pas sùr de vous, laissez les paramètres par défaut";
$lang['Explain']['config_files']    = "Vous avez la possibilité de joindre des fichiers à vos envois de newsletters. \nPour ce faire, le script offre deux options. Le plus simple est de stocker les fichiers sur le serveur, dans le dossier défini comme répertoire de stockage (le dossier en question doit être accessible en écriture). \nSi, pour une raison ou une autre, cela n'est pas rendu possible sur votre serveur, le script a la possibilité de stocker les fichiers sur un serveur <acronym title=\"File Transfert Protocol\" xml:lang=\"en\">ftp</acronym>.\n Vous devez alors entrer les paramètres d'accés au serveur ftp en question.";
$lang['Explain']['config_email']    = "Ces paramètres vous permettent de configurer les méthodes d'envois d'emails à utiliser. \nLe premier moteur prend comme destinataire l'adresse email de la newsletter elle-même, avec les destinataires en copie cachée. Le deuxième moteur est un peu plus lourd mais envoie un email pour chaque abonné (ce dernier sera automatiquement utilisé si l'hébergeur est <strong>Online</strong>).\n Si, pour une raison quelconque, votre serveur ne dispose pas de fonction mail() ou dérivé, vous avez la possibilité d'utiliser un serveur <acronym title=\"Simple Mail Transfert Protocol\" xml:lang=\"en\">smtp</acronym> précis en indiquant les paramètres d'accés au script. \nAttention cependant, certaines restrictions peuvent survenir dans ce cas précis. Référez vous, pour plus de précisions, à la %sfaq du script%s.";
$lang['Explain']['config_stats']    = "Le script dispose d'un petit module de statistique. Celui ci demande que la librairie GD soit installée sur votre serveur pour fonctionner. \nSi la librairie GD n'est pas installée, il est recommandé de désactiver le module de statistiques pour éviter des traitement de données superflus par le script.";

$lang['Default_lang']               = "Sélectionnez la langue par défaut";
$lang['Sitename']                   = "Nom de votre site";
$lang['Urlsite']                    = "URL du site";
$lang['Urlsite_note']               = "( ex: http://www.monsite.com )";
$lang['Urlscript']                  = "URL du script";
$lang['Urlscript_note']             = "( ex: /repertoire/ )";
$lang['Sig_email']                  = "Signature à ajouter à la fin des emails";
$lang['Sig_email_note']             = "(emails d'inscription et de confirmation)";
$lang['Dateformat']                 = "Format des dates";
$lang['Fct_date']                   = "Voir la fonction %sdate()%s";
$lang['Enable_profil_cp']           = "Activer le panneau de gestion de compte pour les abonnés";
$lang['Cookie_name']                = "Nom du cookie";
$lang['Cookie_path']                = "Chemin du cookie";
$lang['Session_length']             = "Durée d'une session sur l'administration";
$lang['Upload_path']                = "Répertoire de stockage des fichiers joints";
$lang['Max_filesize']               = "Poids total des fichiers joints à une newsletter";
$lang['Max_filesize_note']          = "(somme de la taille en octet des fichiers joints)";
$lang['Use_ftp']                    = "Utilisation d'un serveur ftp pour stocker les fichiers joints";
$lang['Ftp_server']                 = "Nom du serveur ftp";
$lang['Ftp_server_note']            = "(nom sans le ftp:// initial, ou adresse ip)";
$lang['Ftp_port']                   = "Port de connexion";
$lang['Ftp_port_note']              = "La valeur par défaut conviendra la plupart du temps";
$lang['Ftp_pasv']                   = "Serveur ftp en mode passif";
$lang['Ftp_pasv_note']              = "(Mode actif ou passif)";
$lang['Ftp_path']                   = "Chemin vers le dossier de stockage des fichiers";
$lang['Ftp_user']                   = "Nom d'utilisateur";
$lang['Ftp_pass']                   = "Mot de passe";
$lang['Check_email']                = "Vérification approfondie des emails à l'inscription";
$lang['Check_email_note']           = "Vérifie l'existence du domaine et du compte associé\nVoir %sla faq%s";
$lang['Choice_engine_send']         = "Méthode d'envoi à utiliser";
$lang['With_engine_bcc']            = "Un envoi avec les destinataires en copie cachée";
$lang['With_engine_uniq']           = "Un envoi pour chaque abonné";
$lang['Emails_paquet']              = "Nombre d'emails par flot d'envoi";
$lang['Emails_paquet_note']         = "Laissez à 0 pour tout envoyer en un flot";
$lang['Use_smtp']                   = "Utilisation d'un serveur <acronym title=\"Simple Mail Transfert Protocol\" xml:lang=\"en\">smtp</acronym> pour les envois";
$lang['Use_smtp_note']              = "Seulement si votre serveur ne dispose d'aucune fonction d'envoi d'emails ou que vous désirez utiliser un serveur SMTP spécifique !";
$lang['Smtp_server']                = "Adresse du serveur smtp";
$lang['Smtp_port']                  = "Port de connexion";
$lang['Smtp_port_note']             = "La valeur par défaut conviendra dans la grande majorité des cas.";
$lang['Smtp_user']                  = "Votre login smtp";
$lang['Smtp_pass']                  = "Votre password smtp";
$lang['Auth_smtp_note']             = "Seulement si votre serveur smtp requiert une authentification !";
$lang['Disable_stats']              = "Désactiver le module de statistiques";
$lang['GD_version']                 = "Version de la librairie GD";


//
// Page : Gestion et permissions des admins
//
$lang['Explain']['admin']           = "Vous pouvez, à partir de ce panneau, gérer votre profil.\nVous pouvez également, si vous en avez les droits, gérer les autres administrateurs, leur profil, leurs droits, ajouter des administrateurs, en retirer...";
$lang['Click_return_profile']       = "Cliquez %sici%s pour retourner au panneau de gestion des profils";
$lang['Add_user']                   = "Ajouter un utilisateur";
$lang['Del_user']                   = "Supprimer cet utilisateur";
$lang['Del_note']                   = "Attention, cette opération est irréversible";
$lang['Email_new_inscrit']          = "Être prévenu par email des nouvelles inscriptions";
$lang['New_pass']                   = "Nouveau mot de passe";
$lang['Conf_pass']                  = "Confirmez le mot de passe";
$lang['Note_pass']                  = "seulement si vous changez votre mot de passe";
$lang['Choice_user']                = "Sélectionnez un utilisateur";
$lang['View_profile']               = "Voir le profil de";
$lang['Confirm_del_user']           = "Vous confirmez la suppression de l'utilisateur sélectionné ?";
$lang['Login_new_user']             = "Son login";
$lang['Email_new_user']             = "Son email";
$lang['Email_note']                 = "(Où il recevra son mot de passe)";
$lang['User_level']                 = "Niveau de cet utilisateur";
$lang['Liste_name2']                = "Nom de la liste";


//
// Page : Gestion des listes
//
$lang['Explain']['liste']           = "Ici, vous pouvez ajouter, modifier, supprimer des listes de diffusion, et régler le système de purge.";
$lang['Explain']['purge']           = "Le système de purge vous permet de nettoyer automatiquement la table des abonnés en supprimant les comptes non activés et dont la date de validité est dépassée.\nCette option est inutile si votre liste ne demande pas de confirmation d'inscription";
$lang['Explain']['cron']            = "Si vous voulez utilisez l'option de gestion des inscription avec cron, remplissez les champs ci dessous (voir %sla faq%s)";
$lang['Click_create_liste']         = "Cliquez %sici%s pour créer une liste de diffusion";
$lang['Click_return_liste']         = "Cliquez %sici%s pour retourner aux informations sur cette liste";
$lang['ID_list']                    = "ID de la liste";
$lang['Liste_name']                 = "Nom de la liste de diffusion";
$lang['Liste_startdate']            = "Date de création de cette liste";
$lang['Auth_format']                = "Format autorisé";
$lang['Sender_email']               = "Adresse email d'envoi";
$lang['Return_email']               = "Adresse de retour pour les erreurs";
$lang['Confirm_subscribe']          = "Demande de confirmation";
$lang['Limite_validate']            = "Limite de validité pour la confirmation d'inscription";
$lang['Note_validate']              = "(inutile si on ne demande pas de confirmation)";
$lang['Enable_purge']               = "Activer la purge automatique";
$lang['Purge_freq']                 = "Fréquence des purges";
$lang['Total_newsletter_list']      = "Nombre total de newsletters envoyées";
$lang['Reg_subscribers_list']       = "Nombre d'inscrits à cette liste";
$lang['Tmp_subscribers_list']       = "Nombre d'inscriptions non confirmées";
$lang['Last_newsletter2']           = "Dernière newsletter envoyée le";
$lang['Form_url']                   = "URL absolu de la page où se trouve le formulaire";
$lang['Create_liste']               = "Créer une liste";
$lang['Edit_liste']                 = "Modifier cette liste";
$lang['Delete_liste']               = "Supprimer cette liste";
$lang['Invalid_liste_name']         = "Le nom de votre liste de diffusion doit faire entre 3 et 30 caractères";
$lang['Unknown_format']             = "Format demandé inconnu";
$lang['Move_abo_logs']              = "Que souhaitez-vous faire des abonnés et newsletters rattachés à cette liste ?";
$lang['Delete_all']                 = "Êtes-vous sûr de vouloir supprimer cette liste, ainsi que les abonnés et newsletters qui y sont rattachés ?";
$lang['Move_to_liste']              = "Déplacer les abonnés et newsletters vers";
$lang['Delete_abo_logs']            = "Ou les retirer de la base de données";
$lang['Use_cron']                   = "Utiliser l'option cron";
$lang['Pop_server']                 = "Nom ou IP du serveur POP";
$lang['Pop_port']                   = "Port de connexion";
$lang['Pop_port_note']              = "La valeur par défaut conviendra dans la grande majorité des cas.";
$lang['Pop_user']                   = "Login de connexion";
$lang['Pop_pass']                   = "Mot de passe de connexion";
$lang['Liste_alias']                = "Alias de la liste (si nécessaire)";


//
// Page : Gestion des logs/archives
//
$lang['Explain']['logs']            = "Ici, vous pouvez visualiser et supprimer les newsletter précédemment envoyées";
$lang['Click_return_logs']          = "Cliquez %sici%s pour retourner à la liste des newsletters";
$lang['Log_subject']                = "Sujet de la newsletter";
$lang['Log_date']                   = "Date d'envoi";
$lang['Log_numdest']                = "Nombre de destinataires";
$lang['Delete_logs']                = "Êtes-vous sûr de vouloir supprimer les newsletters sélectionnés ?";
$lang['Delete_log']                 = "Êtes-vous sûr de vouloir supprimer cette newsletter ?";
$lang['No_log_sended']              = "Aucune newsletter n'a été envoyée à cette liste";
$lang['Joined_files']               = "Cette archive a %d fichiers joints";
$lang['Joined_file']                = "Cette archive a un fichier joint";


//
// Page : Gestion des abonnés
//
$lang['Explain']['abo']             = "Ici, vous pouvez voir et supprimer les comptes des personnes qui se sont inscrites à vos listes de diffusion";
$lang['Click_return_abo']           = "Cliquez %sici%s pour retourner à la liste des abonnés";
$lang['Click_return_abo_profile']   = "Cliquez %sici%s pour retourner au profil de l'abonné";
$lang['Delete_abo']                 = "Êtes-vous sûr de vouloir supprimer les abonnés sélectionnés ?";
$lang['No_abo_in_list']             = "Il n'y a pas encore d'abonné à cette liste de diffusion";
$lang['Susbcribed_date']            = "Date d'inscription";
$lang['Search_abo']                 = "Faire une recherche par mots clés";
$lang['Search_abo_note']            = "(vous pouvez utiliser * comme joker)";
$lang['Days_interval']              = "Inscrit les %d derniers jours";
$lang['All_abo']                    = "Tous les abonnés";
$lang['Inactive_account']           = "Les comptes non activés";
$lang['No_search_result']           = "La recherche n'a retourné aucun résultat";
$lang['Abo_pseudo']                 = "Pseudo de l'abonné";
$lang['Liste_to_register']          = "Cet abonné est inscrit aux listes suivantes";
$lang['Fast_deletion']              = "Suppression rapide";
$lang['Fast_deletion_note']         = "Entrez une ou plusieurs adresses emails, séparées par une virgule, et elles seront supprimées de la liste de diffusion";
$lang['Choice_Format']              = "format choisi";
$lang['Warning_email_diff']         = "Attention, vous allez modifier l'adresse email de cet abonné\nSouhaitez-vous continuer ?";
$lang['Goto_list']                  = "Retour à la liste des abonnés";
$lang['View_account']               = "Voir ce compte";
$lang['Edit_account']               = "Modifier ce compte";


//
// Page : Outils du script
//
$lang['Explain']['tools']           = "Vous avez à votre disposition plusieurs outils pour gérer au mieux vos listes de diffusion";
$lang['Explain']['export']          = "Vous pouvez ici exporter les adresses email d'une liste donnée, et pour le format donné (non pris en compte si la liste n'est pas multi-format).\nSi vous n'indiquez aucun caractère de séparation, le fichier contiendra un email par ligne";
$lang['Explain']['import']          = "Si vous voulez ajouter plusieurs adresses email, mettez un email par ligne ou séparez les par un caractère tel que ; et indiquez le dans le champ en question.\nSi votre serveur l'autorise, vous pouvez uploader un fichier contenant la liste des emails, indiquez également le caractère de séparation (sauf si un email par ligne). Dans le cas contraire, vous avez toutefois la possibilité de spécifier le chemin vers un fichier préalablement uploadé via ftp (chemin relatif à partir de la racine du script) .\nSi le fichier est compressé dans un format supporté par le serveur et le script, il sera automatiquement décompressé.\n(une limite de %s emails a été fixée; Voyez la %sfaq du script%s pour plus de détails)";
$lang['Explain']['ban']             = "Vous pouvez bannir un email entier, de type user@domain.com, ou un fragment d'email en utilisant * comme joker\n\n <u>Exemples</u> :\n <ul><li> toto@titi.com, l'utilisateur ayant l'email toto@titi.com ne pourra s'inscrire</li><li> *.fr.st; Tous les emails ayant pour extension .fr.st ne pourront s'inscrire</li><li> *@domaine.net, tous les emails ayant pour extension @domaine.net ne pourront s'inscrire</li><li> saddam@*, tous les emails ayant pour prefixe saddam@ ne pourront s'inscrire</li><li> *warez*, tous les emails contenant le mot warez ne pourront s'inscrire</li></ul>";
$lang['Explain']['unban']           = "Pour débannir un email ou fragment d'email, utilisez la combinaison clavier/souris appropriée à votre ordinateur et votre navigateur";
$lang['Explain']['forbid_ext']      = "Pour interdire plusieurs extensions de fichiers en même temps, séparez les par une virgule";
$lang['Explain']['reallow_ext']     = "Pour réautoriser une ou plusieurs extensions, utilisez la combinaison clavier/souris appropriée à votre ordinateur et votre navigateur";
$lang['Explain']['backup']          = "Ce module vous permet de sauvegarder les tables du script, ainsi que d'éventuelles autres tables spécifiées, s'il y en a.\nVous pouvez décider de sauvegarder tout, uniquement la structure ou les données, et vous pouvez demander à ce que le fichier soit compressé (selon les options disponibles et librairies installées sur le serveur).\nEnfin, vous pouvez soit télécharger directement le fichier, ou demander au script de le stocker sur le serveur, auquel cas, le fichier sera créé dans le dossier des fichiers temporaires du script";
$lang['Explain']['restore']         = "Ce module vous permet de restaurer les tables du script à l'aide d'une sauvegarde générée par wanewsletter ou un quelconque gestionnaire de bases de données.\nSi l'upload de fichier n'est pas autorisé sur le serveur, vous avez toutefois la possibilité de spécifier un fichier précédemment uploadé via ftp en indiquant son chemin (relatif à la racine du script)";
$lang['Explain']['generator']       = "Vous devez entrer ici l'adresse absolue ou les données du formulaire seront reçues (en général, l'adresse où se trouvera le formulaire lui même)";
$lang['Explain']['code_html']       = "Placez ce code à l'adresse que vous avez/allez indiquer dans la configuration de la liste de diffusion";
$lang['Explain']['code_php']        = "Vous devez placer ce code à l'adresse de destination du formulaire (adresse entrée précédemment), le fichier doit avoir l'extension php !\nLe script s'occupe de trouver le chemin canonique à placer dans la variable \$waroot, si toutefois il n'est pas bon, vous devrez le modifier vous même et indiquer le bon chemin (le chemin doit être relatif, pas absolus)";

$lang['Select_tool']                = "Sélectionnez l'outil que vous voulez utiliser";
$lang['Char_glue']                  = "Caractère de séparation";
$lang['Compress']                   = "Compression";
$lang['Format_to_export']           = "Exporter les abonnés qui ont le format";
$lang['Format_to_import']           = "Format à donner aux abonnés";
$lang['File_upload_restore']        = "Indiquez l'accés au fichier de sauvegarde";
$lang['File_upload']                = "<i>ou</i> bien, vous pouvez spécifier un fichier texte";
$lang['File_local']                 = "<i>ou</i> bien, vous pouvez spécifier un fichier local";
$lang['No_email_banned']            = "Aucun email banni";
$lang['Ban_email']                  = "Email ou fragment d'email à bannir";
$lang['Unban_email']                = "Email ou fragment d'email à débannir";
$lang['No_forbidden_ext']           = "Aucune extension interdite";
$lang['Forbid_ext']                 = "Interdire une extension";
$lang['Reallow_ext']                = "Extension(s) à ré-autoriser";
$lang['Backup_type']                = "Type de sauvegarde";
$lang['Backup_full']                = "Complète";
$lang['Backup_structure']           = "Structure uniquement";
$lang['Backup_data']                = "Données uniquement";
$lang['Drop_option']                = "Ajouter des énoncés DROP TABLE";
$lang['File_action']                = "Que voulez-vous faire du fichier";
$lang['Download_action']            = "Le télécharger";
$lang['Store_action']               = "Le stocker sur le serveur";
$lang['Additionnal_tables']         = "Tables supplémentaires à sauvegarder";
$lang['Target_form']                = "URL de réception du formulaire";


//
// Page : Envoi des newsletters
//
$lang['Explain']['send']            = "Le formulaire d'envoi vous permet de rédiger vos newsletters, de les envoyer, les sauvegarder ou les supprimer, de joindre des fichiers joints..\nSi vous utiliser le deuxième moteur d'envoi, vous pouvez, à l'instar de <code>{LINKS}</code>, placer <code>{NAME}</code> dans le texte, pour afficher le nom de l'abonné si celui ci l'a indiqué.\n\nSi vous créez un modèle réutilisable et que vous lancez l'envoi sans avoir sauvegardé, le modèle sera sauvegardé et une copie sera créée pour les archives. Si vous avez créé un modèle, vous pouvez le recharger, le modifier puis sauvegarder les changements. Toutefois, si vous faites cela en modifiant le statut de la newsletter, une copie sera créée et les modifications seront sauvegardées dessus et non sur le modèle";
$lang['Explain']['join']            = "Vous pouvez ici joindre des fichiers à votre newsletter (attention à ne pas trop alourdir votre newsletter)\nSi l'upload de fichier n'est pas autorisé sur le serveur, vous pourrez indiquer un fichier distant (ex&thinsp;: <samp>http://www.domaine.com/rep/image.gif</samp>) ou un fichier manuellement uploadé dans le dossier des fichiers joints\nVous pouvez également utiliser un des fichiers joints dans une autre newsletter de cette liste";
$lang['Explain']['text']            = "Rédigez ici votre newsletter au format texte. N'oubliez pas de placer le lien de désinscription, soit en cliquant sur le bouton dédié s'il est disponible, soit en ajoutant manuellement le tag <code>{LINKS}</code> dans votre newsletter";
$lang['Explain']['html']            = "Rédigez ici votre newsletter au format html. N'oubliez pas de placer le lien de désinscription , soit en cliquant sur le bouton dédié s'il est disponible, soit en ajoutant manuellement le tag <code>{LINKS}</code> dans votre newsletter (le lien sera au format html)\nSi vous voulez utiliser un des fichiers joints (une image, un son...) dans la newsletter html, placer au lieu de l'adresse du fichier cid:nom_du_fichier\n\n<em>Exemple&thinsp;:</em>\n\nVous avez uploadé l'image image1.gif et désirez l'utiliser dans une balise image de la newsletter html, vous placerez alors la balise img avec pour l'attribut src : cid:image1.gif ( <code>&lt;img src=\"cid:image1.gif\" alt=\"texte alternatif\" /&gt;</code> )";

$lang['Select_log_to_load']         = "Choisissez la newsletter à charger";
$lang['Select_log_to_send']         = "Choisissez la newsletter dont vous voulez reprendre l'envoi";
$lang['Load_by_URL']                = "Chargez une newsletter depuis une URL";
$lang['From_an_URL']                = "depuis une URL";
$lang['Resend_log']                 = "Reprendre un envoi";
$lang['Load_log']                   = "Charger une newsletter";
$lang['Handle']                     = "Modèle";
$lang['Dest']                       = "Destinataire";
$lang['Log_in_text']                = "Newsletter au format texte";
$lang['Log_in_html']                = "Newsletter au format html";
$lang['Total_log_size']             = "Poids approximatif de la newsletter";
$lang['Join_file_to_log']           = "Fichier à joindre à cette newsletter";
$lang['Subject_empty']              = "Vous devez donner un sujet à votre newsletter";
$lang['Body_empty']                 = "Vous devez remplir le(s) champs texte";
$lang['No_links_in_body']           = "Vous devez placer le lien de désinscription";
$lang['Cid_error_in_body']          = "Certains fichiers ciblés dans votre newsletter <abbr>HTML</abbr> avec le scheme <samp>cid:</samp> sont manquants (%s)";
$lang['Status']                     = "Statut";
$lang['Status_writing']             = "Newsletter normale";
$lang['Status_handle']              = "Modèle réutilisable";
$lang['File_on_server']             = "fichier existant";


//
// Page : Statistiques
//
$lang['Explain']['stats']           = "Cette page vous permet de visualiser un graphique à barre, représentant le nombre d'inscriptions par jour, pour le mois et l'année donnés, ainsi qu'un deuxième graphique représentant la répartition des abonnés, par liste de diffusion.\nSi votre serveur n'a pas de librairie GD installée, vous devriez alors désactiver ce module dans la configuration du script";
$lang['Num_abo_per_liste']          = "Répartition des abonnés par liste de diffusion";
$lang['Subscribe_per_day']          = "Inscriptions/Jours";
$lang['Graph_bar_title']            = "Le nombre d'inscriptions par jour pour le mois donné";
$lang['Camembert_title']            = "Les parts des différentes listes par rapport au nombre total d'abonnés";


//
// Installation du script
//
$lang['Welcome_in_install']         = "Bienvenue dans le script d'installation de WAnewsletter. \nCe script nécessite une version de php <b>supérieure ou égale à 4.1.0</b>.\nAvant de continuer l'installation, prenez le temps de lire le fichier %slisez-moi%s, il contient des directives importantes pour la réussite de l'installation";
$lang['Welcome_in_update']          = "Bienvenue dans le script de mise à jour de WAnewsletter. \nVous disposez actuellement de la version %s de WAnewsletter.\n Par mesure de sécurité, il est fortement conseillé de faire une sauvegarde des tables du script avant de procéder à la mise à jour.";
$lang['Warning_reinstall']          = "<b>Attention !</b> WAnewsletter semble déja installé. \nSi vous souhaitez faire une mise à jour, faites une sauvegarde des tables du script par précaution. \nSi vous souhaitez réinstaller le script ou le mettre à jour, entrez votre login et mot de passe d'admin. \nAttention, en cas de réinstallation, toutes les données seront perdues.";
$lang['Select_type']                = "Sélectionnnez le type d'installation";
$lang['Type_reinstall']             = "Réinstallation";
$lang['Type_update']                = "Mise à jour";
$lang['Start_install']              = "Démarrer l'installation";
$lang['Start_update']               = "Démarrer la mise à jour";
$lang['Result_install']             = "Résultat de l'installation";
$lang['Result_update']              = "Résultat de la mise à jour";
$lang['File_config_unwritable']     = "Le fichier config.inc.php n'est pas accessible en écriture, vous devez donner les droits d'accés en écriture à ce fichier le temps de la mise à jour";

$lang['Success_install']            = "L'installation s'est bien déroulée.\nVous pouvez maintenant accéder à l'administration en cliquant %sici%s";
$lang['Success_update']             = "La mise à jour s'est bien déroulée.\nVous pouvez maintenant accéder à l'administration en cliquant %sici%s";
$lang['Success_whithout_config']    = "L'opération s'est bien effectuée mais le fichier de configuration n'a pu être créé.\nVous pouvez le télécharger et l'uploader par vos propres moyens sur le serveur dans le dossier includes/ du script.";
$lang['Success_whithout_config2']   = "L'opération s'est bien effectuée mais le fichier de configuration n'a pu être modifié.\nVeuillez remplacer le contenu du fichier config.inc.php par ce qui suit : \n\n<pre>%s</pre>";
$lang['Error_in_install']           = "Une erreur s'est produite durant l'installation.\n\nL'erreur est : %s\nLa requète est : %s";
$lang['Error_in_update']            = "Une erreur s'est produite durant la mise à jour.\n\nL'erreur est : %s\nLa requète est : %s";
$lang['Update_not_required']        = "Aucune mise à jour n'est nécessaire pour votre version actuelle de WAnewsletter";
$lang['Unknown_version']            = "Version inconnue, la mise à jour ne peut continuer.\nSi vous souhaitez faire une mise à jour à partir d'une version 2.2.x, utilisez le fichier install.php";

$lang['dbtype']                     = "Type de base de données";
$lang['dbhost']                     = "Nom du serveur de base de données";
$lang['dbname']                     = "Nom de votre base de données";
$lang['dbuser']                     = "Nom d'utilisateur";
$lang['dbpwd']                      = "Mot de passe";
$lang['prefixe']                    = "Préfixe des tables";


//
// Conversions des formats de date
//
$datetime['Monday']     = "Lundi";
$datetime['Tuesday']    = "Mardi";
$datetime['Wednesday']  = "Mercredi";
$datetime['Thursday']   = "Jeudi";
$datetime['Friday']     = "Vendredi";
$datetime['Saturday']   = "Samedi";
$datetime['Sunday']     = "Dimanche";
$datetime['Mon']        = "Lun";
$datetime['Tue']        = "Mar";
$datetime['Wed']        = "Mer";
$datetime['Thu']        = "Jeu";
$datetime['Fri']        = "Ven";
$datetime['Sat']        = "Sam";
$datetime['Sun']        = "Dim";

$datetime['January']    = "Janvier";
$datetime['February']   = "Février";
$datetime['March']      = "Mars";
$datetime['April']      = "Avril";
$datetime['May']        = "Mai";
$datetime['June']       = "Juin";
$datetime['July']       = "Juillet";
$datetime['August']     = "Août";
$datetime['September']  = "Septembre";
$datetime['October']    = "Octobre";
$datetime['November']   = "Novembre";
$datetime['December']   = "Décembre";
$datetime['Jan']        = "Jan";
$datetime['Feb']        = "Fév";
$datetime['Mar']        = "Mar";
$datetime['Apr']        = "Avr";
$datetime['May']        = "Mai";
$datetime['Jun']        = "Juin";
$datetime['Jul']        = "Juil";
$datetime['Aug']        = "Aoû";
$datetime['Sep']        = "Sep";
$datetime['Oct']        = "Oct";
$datetime['Nov']        = "Nov";
$datetime['Dec']        = "Déc";


//
// Données diverses sur la langue
//
$lang['CHARSET']        = 'ISO-8859-15';
$lang['CONTENT_LANG']   = 'fr';
$lang['CONTENT_DIR']    = 'ltr'; // sens du texte Left To Right ou Right To Left
$lang['TRANSLATE']      = '';

?>