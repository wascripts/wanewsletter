<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@webnaute.net>
 * @link      http://dev.webnaute.net/wanewsletter/
 * @copyright 2002-2021 Aurélien Maille
 * @license   https://www.gnu.org/licenses/gpl.html  GNU General Public License
 *
 * Vous pouvez très facilement traduire Wanewsletter dans une autre langue.
 * Il vous suffit pour cela de traduire ce qui se trouve entre
 * guillemets. Attention, ne touchez pas à la partie $lang['....']
 *
 * des %1\$s, %s, %d ou autre signe de ce genre signifient qu’ils
 * vont être remplacés par un contenu variable. Placez-les de façon
 * adéquat dans la phrase mais ne les enlevez pas.
 * Enfin, les \n représentent un retour à la ligne.
 */


$lang['General_title']              = "Administration des listes de diffusion";

$lang['Title']['accueil']           = "Informations générales";
$lang['Title']['install']           = "Installation de Wanewsletter";
$lang['Title']['upgrade']           = "Mise à jour de Wanewsletter";
$lang['Title']['reinstall']         = "Réinstallation de Wanewsletter";
$lang['Title']['database']          = "Paramètres d’accès à la base de données";
$lang['Title']['admin']             = "Administration";
$lang['Title']['error']             = "Erreur !";
$lang['Title']['info']              = "Information !";
$lang['Title']['select']            = "Sélection";
$lang['Title']['confirm']           = "Confirmation";
$lang['Title']['config_lang']       = "Choix de la langue";
$lang['Title']['config_perso']      = "Personnalisation";
$lang['Title']['config_cookies']    = "Cookies";
$lang['Title']['config_email']      = "Envoi des emails";
$lang['Title']['config_files']      = "Fichiers joints";
$lang['Title']['config_stats']      = "Module de statistiques";
$lang['Title']['config_debug']      = "Débogage";
$lang['Title']['profile']           = "Profil de <q>%s</q>";
$lang['Title']['mod_profile']       = "Édition du profil de <q>%s</q>";
$lang['Title']['manage']            = "Actions possibles de l’utilisateur";
$lang['Title']['other_options']     = "Options diverses";
$lang['Title']['info_liste']        = "Informations sur la liste de diffusion";
$lang['Title']['add_liste']         = "Créer une liste de diffusion";
$lang['Title']['edit_liste']        = "Éditer une liste de diffusion";
$lang['Title']['purge_sys']         = "Système de purge";
$lang['Title']['cron']              = "Gestion des inscriptions par email";
$lang['Title']['logs']              = "Lettres envoyées à la liste <q>%s</q>";
$lang['Title']['abo']               = "Abonnés inscrits à la liste <q>%s</q>";
$lang['Title']['stats']             = "Statistiques des listes de diffusion";
$lang['Title']['tools']             = "Outils Wanewsletter";
$lang['Title']['export']            = "Exporter des adresses emails";
$lang['Title']['import']            = "Importer des adresses emails";
$lang['Title']['ban']               = "Gestion des emails bannis";
$lang['Title']['attach']            = "Gestion des extensions de fichiers";
$lang['Title']['backup']            = "Sauvegarde des données";
$lang['Title']['restore']           = "Restauration des données";
$lang['Title']['generator']         = "Générateur de formulaires d’inscriptions";
$lang['Title']['debug']             = "Informations de débogage";
$lang['Title']['send']              = "Formulaire d’envoi";
$lang['Title']['join']              = "Joindre un fichier à la newsletter";
$lang['Title']['joined_files']      = "Fichiers joints à cette newsletter";
$lang['Title']['profil_cp']         = "Panneau de gestion de compte";
$lang['Title']['archives']          = "Archives des listes de diffusion";
$lang['Title']['Create_passwd']     = "Création de votre mot de passe";
$lang['Title']['Reset_passwd']      = "Réinitialisation de votre mot de passe";
$lang['Title']['form']              = "Inscription à la liste de diffusion";
$lang['Title']['check_update']      = "Vérification des mises à jour";


//
// Modules de l’administration
//
$lang['Module']['accueil']          = "Accueil";
$lang['Module']['config']           = "Configuration";
$lang['Module']['login']            = "Connexion";
$lang['Module']['logout']           = "Déconnexion";
$lang['Module']['logout_2']         = "Déconnexion [%s]";
$lang['Module']['send']             = "Envoi";
$lang['Module']['users']            = "Utilisateurs";
$lang['Module']['subscribers']      = "Inscrits";
$lang['Module']['list']             = "Listes";
$lang['Module']['log']              = "Archives";
$lang['Module']['tools']            = "Outils";
$lang['Module']['stats']            = "Statistiques";
$lang['Module']['docs']             = "Documentation";
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
$lang['Button']['del_logs']         = "Supprimer les newsletters sélectionnées";
$lang['Button']['del_account']      = "Supprimer ce compte";
$lang['Button']['links']            = "Placer le lien de désinscription";
$lang['Button']['dl']               = "Télécharger";
$lang['Button']['browse']           = "Parcourir";


//
// Différents messages d’information et d’erreur
//
$lang['Message']['Subscribe_1']             = "Inscription réussie !\nVous allez recevoir un email de confirmation.\nAttention, le lien de confirmation contenu dans l’email sera valide pendant %d jours !\nPassé ce délai, il vous faudra vous réinscrire.";
$lang['Message']['Subscribe_2']             = "Inscription réussie !";
$lang['Message']['Confirm_ok']              = "Votre inscription a été confirmée !";
$lang['Message']['Unsubscribe_1']           = "Ok, vous allez recevoir un email qui vous permettra de confirmer votre choix";
$lang['Message']['Unsubscribe_2']           = "Vous n’êtes désormais plus inscrit à cette liste de diffusion";
$lang['Message']['Unsubscribe_3']           = "Votre email a bien été retiré de notre base de données";
$lang['Message']['Success_setformat']       = "Le changement de format a été effectué avec succès";
$lang['Message']['Invalid_email']           = "L’adresse email que vous avez indiquée n’est pas valide";
$lang['Message']['Unknown_email']           = "Email inconnu";
$lang['Message']['Email_banned']            = "Cet email ou ce type d’email a été banni";
$lang['Message']['Allready_reg']            = "Vous êtes déjà inscrit !";
$lang['Message']['Allready_reg2']           = "L’adresse email indiquée est déjà présente dans la base de données";
$lang['Message']['Reg_not_confirmed']       = "Vous êtes déjà inscrit mais n’avez pas encore confirmé votre inscription.\nVous allez recevoir un nouvel email de confirmation.\nAttention, le lien de confirmation contenu dans l’email sera valide pendant %d jours !\nPassé ce délai, il vous faudra vous réinscrire.";
$lang['Message']['Unknown_list']            = "Liste inconnue";
$lang['Message']['Inactive_format']         = "Impossible de changer de format";
$lang['Message']['Invalid_date']            = "Désolé, la date de confirmation est dépassée";
$lang['Message']['Invalid_code']            = "Code invalide !";
$lang['Message']['Invalid_email2']          = "Adresse email invalide !";
$lang['Message']['Failed_sending']          = "L’email n’a pu être envoyé ! (%s)";

$lang['Message']['Success_export']          = "L’exportation des emails a été effectuée avec succès. \nVous trouverez le fichier de sauvegarde dans le répertoire des fichiers temporaires du script (Pensez à le supprimer après l’avoir récupéré !)";
$lang['Message']['Success_import']          = "Les emails ont été importés avec succès";
$lang['Message']['Success_import3']         = "L’importation s’est effectuée avec succès mais certains emails ont été refusés. \nVous pouvez %sconsulter le rapport d’erreurs%s (aussi disponible dans le répertoire des fichiers temporaires du script).";
$lang['Message']['Success_import4_0']       = "Aucun email n’a été importé";
$lang['Message']['Success_import4_1']       = "%d email a été importé avec succès";
$lang['Message']['Success_import4_n']       = "%d emails ont été importés avec succès";
$lang['Message']['Success_modif']           = "Les modifications ont été effectuées avec succès";
$lang['Message']['Success_backup']          = "La sauvegarde des tables a été effectuée avec succès. \nVous trouverez le fichier de sauvegarde dans le répertoire des fichiers temporaires du script (Pensez à le supprimer après l’avoir récupéré !)";
$lang['Message']['Success_restore']         = "La restauration des données a été effectuée avec succès";
$lang['Message']['Success_logout']          = "Vous avez été déconnecté avec succès";
$lang['Message']['Success_purge']           = "La purge a été effectuée avec succès (%d abonné(s) supprimé(s))";
$lang['Message']['Success_send']            = "L’envoi partiel a été effectué avec succès à <b>%d</b> abonnés.\nLa lettre de diffusion a été envoyée jusqu’à présent à <b>%d</b> abonnés sur un total de <b>%d</b>";
$lang['Message']['Success_send_finish']     = "Envoi terminé avec succès.\nCette lettre a été envoyée à un total de <b>%d</b> abonnés";
$lang['Message']['Success_operation']       = "L’opération a été effectuée avec succès";

$lang['Message']['Profile_updated']         = "Le profil a été mis à jour avec succès";
$lang['Message']['Admin_added']             = "L’utilisateur a été ajouté avec succès, il va recevoir un email de bienvenue";
$lang['Message']['Admin_deleted']           = "L’utilisateur a été supprimé avec succès";
$lang['Message']['liste_created']           = "La nouvelle liste de diffusion a été créée avec succès";
$lang['Message']['liste_edited']            = "La liste de diffusion a été modifiée avec succès";
$lang['Message']['Liste_del_all']           = "La liste a été supprimée avec succès, ainsi que les abonnés et newsletters qui y étaient rattachés";
$lang['Message']['Liste_del_move']          = "La liste a été supprimée avec succès.\nLes abonnés et newsletters qui y étaient rattachés ont été déplacés vers la liste sélectionnée";
$lang['Message']['logs_deleted']            = "Les newsletters ont été supprimées avec succès";
$lang['Message']['log_deleted']             = "La newsletter a été supprimée avec succès";
$lang['Message']['log_saved']               = "La newsletter a été sauvegardée avec succès";
$lang['Message']['log_ready']               = "La newsletter a été sauvegardée avec succès et est prête à être envoyée à la liste <q>%s</q>";
$lang['Message']['abo_deleted']             = "Les abonnés ont été supprimés avec succès";
$lang['Message']['Send_canceled']           = "Opération effectuée. Tous les envois restants pour cette newsletter ont été annulés";
$lang['Message']['List_is_busy']            = "Une opération est en cours sur cette liste. Veuillez patienter quelques instants et retenter la manipulation";

$lang['Message']['Not_authorized']          = "Vous n’avez pas les permissions suffisantes pour accéder à cette page ou exécuter cette action";
$lang['Message']['Not_auth_view']           = "Vous n’êtes pas autorisé à visualiser cette liste de diffusion";
$lang['Message']['Not_auth_edit']           = "Vous n’êtes pas autorisé à effectuer des modifications sur cette liste de diffusion";
$lang['Message']['Not_auth_del']            = "Vous n’êtes pas autorisé à effectuer des suppressions sur cette liste de diffusion";
$lang['Message']['Not_auth_send']           = "Vous n’êtes pas autorisé à effectuer des envois à cette liste de diffusion";
$lang['Message']['Not_auth_import']         = "Vous n’êtes pas autorisé à importer des adresses emails dans cette liste de diffusion";
$lang['Message']['Not_auth_export']         = "Vous n’êtes pas autorisé à exporter des adresses emails de cette liste de diffusion";
$lang['Message']['Not_auth_ban']            = "Vous n’êtes pas autorisé à effectuer des modifications sur la liste de bannissement de cette liste de diffusion";
$lang['Message']['Not_auth_attach']         = "Vous n’êtes pas autorisé à joindre des fichiers ou à voir les fichiers joints de cette liste de diffusion";

$lang['Message']['Error_login']             = "Ces identifiants sont incorrects. Échec de l’authentification";
$lang['Message']['Bad_confirm_pass']        = "La confirmation du mot de passe ne correspond pas au mot de passe entré";
$lang['Message']['Bad_confirm_email']       = "La confirmation de votre nouvelle adresse email est erronée";
$lang['Message']['bad_smtp_param']          = "La connexion au serveur smtp n’a pu être établie, vérifiez vos paramètres \n(%s)";
$lang['Message']['bad_pop_param']           = "La connexion au serveur pop n’a pu être établie, vérifiez vos paramètres \n(%s)";
$lang['Message']['Alphanum_pass']           = "Le mot de passe doit être composé au minimum de 6 caractères imprimables";
$lang['Message']['Invalid_session']         = "Session non valide !";
$lang['Message']['fields_empty']            = "Certains champs obligatoires ne sont pas remplis";
$lang['Message']['Owner_account']           = "Vous ne pouvez pas supprimer votre propre compte !";
$lang['Message']['Invalid_login']           = "Ce pseudo n’est pas valide, le pseudo doit faire entre 2 et 30 caractères";
$lang['Message']['Double_login']            = "Un utilisateur utilise déjà ce pseudo";
$lang['Message']['No_liste_exists']         = "Aucune liste n’est disponible";
$lang['Message']['No_liste_id']             = "Aucune liste de diffusion n’a été sélectionnée";
$lang['Message']['No_log_id']               = "Aucune newsletter n’a été sélectionnée";
$lang['Message']['log_not_exists']          = "Cette newsletter n’existe pas !";
$lang['Message']['log_format_not_exists']   = "Aucune édition au format %s pour cette archive.";
$lang['Message']['No_log_to_send']          = "Il n’y a actuellement aucun envoi à reprendre";
$lang['Message']['No_abo_id']               = "Aucun abonné n’a été sélectionné";
$lang['Message']['No_abo_email']            = "Aucune de ces adresses email n’est présente dans cette liste de diffusion";
$lang['Message']['abo_not_exists']          = "Cet abonné n’existe pas !";
$lang['Message']['File_not_exists']         = "Le fichier %s n’existe pas ou n’est pas accessible en lecture";
$lang['Message']['Error_local']             = "Aucun fichier trouvé au chemin %s";
$lang['Message']['No_data_received']        = "Aucune donnée valide n’a été réceptionnée";
$lang['Message']['Stats_disabled']          = "Le module de statistiques a été désactivé";
$lang['Message']['No_gd_lib']               = "Ce module requiert la librairie GD, or celle-ci ne semble pas présente sur le serveur";
$lang['Message']['No_subscribers']          = "Vous ne pouvez pas envoyer de newsletter à cette liste car elle ne compte pas encore d’abonné";
$lang['Message']['No_log_found']            = "Aucune newsletter prête à être envoyée n’a été trouvée";
$lang['Message']['Invalid_url']             = "L’url donnée n’est pas valide";
$lang['Message']['Unaccess_host']           = "L’hôte %s semble inaccessible actuellement";
$lang['Message']['Not_found_at_url']        = "Le fichier ne semble pas présent à l’url indiquée";
$lang['Message']['Error_load_url']          = "Erreur dans le chargement de l’url \"%1\$s\" (%2\$s)";
$lang['Message']['File_not_found']          = "Ce fichier est introuvable sur le serveur";
$lang['Message']['Config_loading_url']      = "Pour charger des URLs distantes, vous avez besoin soit de l'extension curl de PHP, soit d'activer l'option PHP allow_url_fopen.";

$lang['Message']['Cannot_create_dir']       = "Impossible de créer le répertoire %s";
$lang['Message']['Dir_not_writable']        = "Le répertoire <samp>%s</samp> n’existe pas ou n’est pas accessible en écriture";
$lang['Message']['sql_file_not_readable']   = "Les fichiers sql ne sont pas accessibles en lecture ! (includes/Dblayer/schemas/)";

$lang['Message']['Uploaddir_not_writable']  = "Le répertoire de stockage des fichiers joints n’est pas accessible en écriture";
$lang['Message']['Upload_error_1']          = "Le fichier excède le poids autorisé par la directive upload_max_filesize de php.ini";
$lang['Message']['Upload_error_2']          = "Le fichier excède le poids autorisé par le champ MAX_FILE_SIZE";
$lang['Message']['Upload_error_3']          = "Le fichier n’a été uploadé que partiellement";
$lang['Message']['Upload_error_4']          = "Aucun fichier n’a été uploadé";
$lang['Message']['Upload_error_5']          = "Une erreur inconnue est survenue, le fichier n’a pu être uploadé";
$lang['Message']['Upload_error_6']          = "Le répertoire des fichiers temporaires est inaccessible ou n’existe pas. Cela peut provenir d’une mauvaise configuration de la directive PHP open_basedir.";
$lang['Message']['Upload_error_7']          = "Échec de l’écriture du fichier sur le disque";
$lang['Message']['Upload_error_8']          = "Une extension PHP inconnue a bloqué le chargement du fichier";
$lang['Message']['Invalid_filename']        = "Nom de fichier non valide";
$lang['Message']['Invalid_action']          = "Action non valide";
$lang['Message']['Invalid_ext']             = "Cette extension de fichier a été interdite";
$lang['Message']['weight_too_big']          = "Le poids total des fichiers joints excède le maximum autorisé, il ne vous reste que %s de libre";

$lang['Message']['Compress_unsupported']    = "Format de compression non supporté";
$lang['Message']['Database_unsupported']    = "Cette base de données n’est pas supportée par le système de sauvegarde/restauration";

$lang['Message']['Profil_cp_disabled']      = "Le panneau de gestion de compte est actuellement désactivé";
$lang['Message']['Inactive_account']        = "Votre compte est actuellement inactif, vous avez dû recevoir un email pour l’activer.";
$lang['Message']['Logs_sent']               = "Les newsletters sélectionnées ont été envoyées à votre adresse: %s";
$lang['Message']['Twice_sending']           = "Une newsletter est déjà en cours d’envoi pour cette liste. Terminez ou annulez cet envoi avant d’en commencer un autre.";

$lang['Message']['Invalid_cookie_name']     = "Les caractères blancs, ainsi que le signe égal, le point-virgule et la virgule ne sont pas autorisés dans le nom du cookie.";
$lang['Message']['Invalid_cookie_path']     = "Le chemin de validité du cookie doit inclure le répertoire d’installation du script (%s)";
$lang['Message']['Critical_error']          = "Une erreur critique s’est produite. Activez le mode de débogage pour obtenir plus de détails.";
$lang['Message']['No_gd_img_support']       = "Aucun format d’image valable n’est disponible";
$lang['Message']['Warning_debug_active']    = "<strong>Note&nbsp;:</strong> Le débogage est activé&nbsp;!";
$lang['Message']['Invalid_prefix']          = "Le préfixe de table doit commencer par une lettre, éventuellement suivie d’autres caractères alphanumériques, et se terminer par un tiret bas ou underscore.";
$lang['Message']['DB_connection_lost']      = "La connexion à la base de données a été perdue";
$lang['Message']['Connect_db_error']        = "Impossible de se connecter à la base de données (%s)";

$lang['Message']['Reset_password_username'] = "Si un compte correspond au nom d’utilisateur que vous avez fourni, un email de réinitialisation du mot de passe sera envoyé à l’adresse email correspondante.";
$lang['Message']['Reset_password_email']    = "Si un compte correspond à l’adresse email que vous avez fournie, un email de réinitialisation du mot de passe sera envoyé à cette adresse email.";
$lang['Message']['Invalid_token']           = "Ce jeton n’est pas valide !";
$lang['Message']['Expired_token']           = "Ce jeton n’est plus valide ! \nRépetez l’opération pour obtenir un nouveau jeton valide.";
$lang['Message']['Password_created']        = "Votre mot de passe a été créé avec succès.\n Vous pouvez désormais %svous connecter%s.";
$lang['Message']['Password_modified']       = "Votre mot de passe a été modifié avec succès.\n Vous pouvez désormais %svous connecter%s.";

$lang['Message']['Unreadable_config_file']  = "Impossible de lire le fichier de configuration. Corrigez cela puis rechargez la page.";
$lang['Message']['No_microsoft_sqlserver']  = "Le support de Microsoft SQL Server a été retiré depuis Wanewsletter 2.3";
$lang['Message']['Not_installed']           = "Wanewsletter ne semble pas installé !\nAppelez install.php dans votre navigateur.";
$lang['Message']['Move_to_data_dir']        = "Utilisation de %s. Vous devriez déplacer ce fichier dans le répertoire data/.";
$lang['Message']['No_json_extension']       = "L’extension JSON est nécessaire pour lire le contenu du fichier composer.lock !";
$lang['Message']['Composer_lock_unreadable'] = "Impossible de lire le fichier composer.lock !";

$lang['Message']['Subject_empty']           = "Vous devez donner un sujet à votre newsletter";
$lang['Message']['Body_empty']              = "Vous devez remplir le(s) champs texte";
$lang['Message']['No_links_in_body']        = "Vous devez placer le lien de désinscription";
$lang['Message']['Cid_error_in_body']       = "Certains fichiers ciblés dans votre newsletter <abbr>HTML</abbr> avec le scheme <samp>cid:</samp> sont manquants (%s)";
$lang['Message']['Joined_file_added']       = "Le fichier <q>%s</q> a été ajouté au message";
$lang['Message']['Joined_files_removed']    = "Les fichiers sélectionnés ont été retirés du message";
$lang['Message']['Joined_file_removed']     = "Le fichier sélectionné a été retiré du message";

$lang['Message']['Invalid_liste_name']      = "Le nom de votre liste de diffusion doit faire entre 3 et 30 caractères";
$lang['Message']['Unknown_format']          = "Format demandé inconnu";
$lang['Message']['Xml_ext_needed']          = "Les extensions XML ou SimpleXML de PHP sont nécessaires pour analyser les fichiers XML";
$lang['Message']['No_list_found']           = 'Aucune liste publique disponible';

//
// Divers
//
$lang['Subscribe']                  = "Inscription";
$lang['Unsubscribe']                = "Désinscription";
$lang['Setformat']                  = "Changer de format";
$lang['Email_address']              = "Adresse email";
$lang['Format']                     = "Format";
$lang['Diff_list']                  = "Listes de diffusion";
$lang['Start']                      = "Début";
$lang['End']                        = "Fin";
$lang['Prev']                       = "Précédent";
$lang['Next']                       = "Suivant";
$lang['Prev_page']                  = "Page précédente";
$lang['Next_page']                  = "Page suivante";
$lang['Yes']                        = "oui";
$lang['No']                         = "non";
$lang['Login']                      = "Nom d’utilisateur";
$lang['Password']                   = "Mot de passe";
$lang['Not_available']              = "Non disponible";
$lang['Seconds']                    = "secondes";
$lang['Days']                       = "jours";
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
$lang['GiB']                        = "Gio";
$lang['MiB']                        = "Mio";
$lang['KiB']                        = "Kio";
$lang['Bytes']                      = "Octets";
$lang['Show']                       = "Visualiser";
$lang['View']                       = "Voir";
$lang['Edit']                       = "Éditer";
$lang['Import']                     = "Importer";
$lang['Export']                     = "Exporter";
$lang['Ban']                        = "Bannir";
$lang['Attach']                     = "Attacher";
$lang['Log_in']                     = "Se connecter";
$lang['Autologin']                  = "Se connecter automatiquement";
$lang['Faq']                        = "FAQ du script";
$lang['Author_note']                = "Notes de l’auteur";
$lang['Page_loading']               = "Veuillez patienter pendant le chargement de la page";
$lang['Label_link']                 = "Se désinscrire";
$lang['Maximum_size']               = "Taille maximum: %s";
$lang['Reset_passwd']               = "Réinitialiser mon mot de passe";
$lang['Name']                       = "Nom";
$lang['Value']                      = "Valeur";
$lang['Cookie_notice']              = "Vous devez activer les cookies pour pouvoir vous connecter";
$lang['Account_status']             = "Statut de ce compte";
$lang['Active']                     = "Actif";
$lang['Inactive']                   = "Inactif";
$lang['None']                       = "Aucune";
$lang['Text']                       = "texte";
$lang['Restore_default']            = "Restaurer la valeur par défaut";
$lang['Connection_security']        = "Sécurité de la connexion";
$lang['Server_password_note']       = "Si laissé vide, le mot de passe précédent est conservé, sauf si aucun nom d’utilisateur n’est fourni.";

$lang['Click_return_index']         = "Cliquez %sici%s pour retourner sur l’accueil";
$lang['Click_return_back']          = "Cliquez %sici%s pour retourner sur la page précédente";
$lang['Click_return_form']          = "Cliquez %sici%s pour retourner au formulaire";
$lang['Click_start_send']           = "Cliquez %sici%s si vous souhaitez démarrer l’envoi maintenant";
$lang['Click_resend']               = "Cliquez %sici%s pour envoyer un autre flot d’emails";

$lang['Explain']['login']           = "Si votre compte ne possède pas encore de mot de passe, vous pouvez en créer un en suivant ce lien&nbsp;: %sCréer mon mot de passe%s.";
$lang['Explain']['Reset_passwd']    = "Entrez votre nom d’utilisateur ou votre adresse email pour recevoir un email contenant les instructions à suivre pour créer un nouveau mot de passe.";

$lang['Third_party_libraries']      = "Librairies tierces";
$lang['Database']                   = "Base de données";
$lang['Client_library']             = "Librairie cliente";
$lang['Charset']                    = "Jeu de caractères";
$lang['Server_software']            = "Serveur HTTP/OS";
$lang['User_agent']                 = "Agent utilisateur";
$lang['Secure_connection']          = "Connexion sécurisée";
$lang['Driver']                     = "Pilote";
$lang['Misc']                       = "Divers";
$lang['Unreadable']                 = "pas d’accès en lecture";
$lang['Unwritable']                 = "pas d’accès en écriture";
$lang['Not_exists']                 = "n’existe pas";

//
// Sujets de divers emails envoyés
//
$lang['Subject_email']['Subscribe']     = "Inscription à la newsletter de %s";
$lang['Subject_email']['Unsubscribe_1'] = "Confirmation de désinscription";
$lang['Subject_email']['New_subscribe'] = "Nouvel inscrit à la newsletter";
$lang['Subject_email']['Unsubscribe_2'] = "Désinscription de la newsletter";
$lang['Subject_email']['New_admin']     = "Administration de la newsletter de %s";


//
// Panneau de gestion de compte (profil_cp.php)
//
$lang['Welcome_profil_cp']          = "Bienvenue sur le panneau de gestion de votre compte.\nVous pouvez ici modifier votre profil abonné et consulter les archives.";
$lang['Explain']['editprofile']     = "Ici, vous avez la possibilité de modifier les données de votre compte.\nVous pouvez renseigner votre prénom ou pseudo pour personnaliser les newsletters que vous recevrez (selon les réglages de l’administrateur). Vous pouvez également mettre un mot de passe à votre compte, ce qui sera plus simple à taper que le code de votre compte.";
$lang['Explain']['archives']        = "Vous pouvez, à partir de cette page, demander à recevoir les précédentes newsletters envoyées aux listes de diffusion auxquelles vous êtes inscrit.\nAttention, pour chaque newsletter sélectionnée, vous recevrez un email.";
$lang['Explain']['change_email']    = "Les deux champs suivant vous permettent de changer l’adresse email de votre compte. N’oubliez pas que votre adresse email vous sert à vous connecter à la présente interface.";

$lang['New_Email']                  = "Entrez votre nouvelle adresse email";
$lang['Confirm_Email']              = "Confirmez l’adresse email";

//
// Page d’accueil
//
$lang['Explain']['accueil']         = "Bienvenue sur l’administration de Wanewsletter, nous vous remercions d’avoir choisi Wanewsletter comme solution de newsletter/mailing liste.\n L’administration vous permet de contrôler vos listes de diffusion de façon très simple. \nVous pouvez à tout moment retourner sur cette page en cliquant sur le logo Wanewsletter en haut à gauche de l’écran.";
$lang['Registered_subscribers']     = "Il y a au total <b>%1\$d</b> inscrits, soit <b>%2\$s</b> nouveaux inscrits par jour";
$lang['Registered_subscriber']      = "Il y a au total <b>1</b> inscrit, soit <b>%s</b> nouveaux inscrits par jour";
$lang['No_registered_subscriber']   = "Il n’y a aucun inscrit pour l’instant";
$lang['Tmp_subscribers']            = "Il y a <b>%d</b> personnes n’ayant pas confirmé leur inscription";
$lang['Tmp_subscriber']             = "Il y a <b>1</b> personne n’ayant pas confirmé son inscription";
$lang['No_tmp_subscriber']          = "Il n’y a actuellement aucune inscription non confirmée";
$lang['Last_newsletter']            = "Dernière newsletter envoyée le <b>%s</b>";
$lang['Total_newsletters']          = "Un total de <b>%1\$d</b> newsletters ont été envoyées, soit <b>%2\$s</b> newsletters par mois";
$lang['Total_newsletter']           = "Un total de <b>1</b> newsletter a été envoyée, soit <b>%s</b> newsletters par mois";
$lang['No_newsletter_sended']       = "Aucune newsletter n’a encore été envoyée";
$lang['Dbsize']                     = "Taille de la base de données (tables du script)";
$lang['Total_Filesize']             = "Espace disque occupé par les fichiers (pièces jointes et statistiques)";


//
// Page : Configuration
//
$lang['Explain']['config']          = "Le formulaire ci-dessous vous permet de configurer tous les aspects du script";
$lang['Explain']['config_cookies']  = "Ces paramètres vous permettent de régler les cookies utilisés par le script. \nSi vous n’êtes pas sûr de vous, laissez les paramètres par défaut";
$lang['Explain']['config_files']    = "Vous avez la possibilité de joindre des fichiers à vos envois de newsletters. \nLes fichiers seront stockés sur le serveur, dans le répertoire défini comme répertoire de stockage, lequel doit être accessible en écriture.";
$lang['Explain']['config_email']    = "Ces paramètres vous permettent de configurer vos envois d’emails.\nPar défaut, le script envoie un email personnalisé à chaque abonné, mais vous pouvez le configurer pour qu’il envoie un ou plusieurs emails avec une liste de destinataires en copie cachée.\nSi vous souhaitez utiliser un serveur <abbr title=\"Simple Mail Transfert Protocol\" lang=\"en\">SMTP</abbr> spécifique, activez l’option puis renseignez les informations de connexion. Par défaut, le script utilise la fonction <code>mail()</code> de <abbr title=\"PHP: Hypertext Preprocessor\" lang=\"en\">PHP</abbr>. Consultez la %sFAQ%s au sujet des limitations existantes dans le cadre de l’utilisation d’un serveur <abbr>SMTP</abbr>.";
$lang['Explain']['config_stats']    = "Le script dispose d’un petit module de statistique. Celui-ci demande que la librairie GD soit installée sur votre serveur pour fonctionner. \nSi vous ne souhaitez pas utiliser cette fonctionnalité, il est recommandé de désactiver le module de statistiques pour éviter des traitement de données superflus par le script.";
$lang['Explain']['config_debug']    = "Le débogueur permet d’afficher les erreurs non fatales survenant lors de l’exécution du script. Cela peut aider à trouver l’origine d’un dysfonctionnement.\n Les informations de débogage sont visibles uniquement aux administrateurs.";

$lang['Default_lang']               = "Sélectionnez la langue par défaut";
$lang['Sitename']                   = "Nom de votre site";
$lang['Urlsite']                    = "URL du site";
$lang['Urlsite_note']               = "(ex: http://www.monsite.com)";
$lang['Urlscript']                  = "URL du script";
$lang['Urlscript_note']             = "(ex: /repertoire/)";
$lang['Sig_email']                  = "Signature à ajouter à la fin des emails";
$lang['Sig_email_note']             = "(emails d’inscription et de confirmation)";
$lang['Dateformat']                 = "Format des dates";
$lang['Fct_date']                   = "Voir la fonction %sdate()%s";
$lang['Enable_profil_cp']           = "Activer le panneau de gestion de compte pour les abonnés";
$lang['Cookie_name']                = "Nom du cookie";
$lang['Cookie_path']                = "Chemin du cookie";
$lang['Session_length']             = "Durée d’une session sur l’administration";
$lang['Upload_path']                = "Répertoire de stockage des fichiers joints";
$lang['Max_filesize']               = "Poids total des fichiers joints à une newsletter";
$lang['Choice_engine_send']         = "Méthode d’envoi à utiliser";
$lang['With_engine_bcc']            = "Un envoi avec les destinataires en copie cachée";
$lang['With_engine_uniq']           = "Un envoi pour chaque abonné";
$lang['Sending_limit']              = "Nombre d’emails par flôt d’envoi";
$lang['Sending_limit_note']         = "Laissez à 0 pour envoyer tous les emails en une fois";
$lang['Sending_delay']              = "Délai entre chaque flôt d’envoi";
$lang['Use_smtp']                   = "Utilisation d’un serveur <abbr title=\"Simple Mail Transfert Protocol\" lang=\"en\">smtp</abbr> pour les envois";
$lang['Use_smtp_note']              = "Seulement si votre serveur ne dispose d’aucune fonction d’envoi d’emails ou que vous désirez utiliser un serveur SMTP spécifique !";
$lang['Smtp_server']                = "Nom ou IP du serveur SMTP";
$lang['Smtp_port']                  = "Port de connexion";
$lang['Smtp_user']                  = "Nom d’utilisateur";
$lang['Smtp_pass']                  = "Mot de passe";
$lang['Disable_stats']              = "Désactiver le module de statistiques";
$lang['Debug_level']                = "Niveau de débogage";
$lang['Debug_level_1']              = "désactivé";
$lang['Debug_level_2']              = "normal";
$lang['Debug_level_3']              = "développement";


//
// Page : Gestion et permissions des admins
//
$lang['Explain']['admin']           = "Vous pouvez, à partir de ce panneau, gérer votre profil.\nVous pouvez également, si vous en avez les droits, gérer les autres administrateurs, leur profil, leurs droits, ajouter des administrateurs, en retirer...";
$lang['Click_return_profile']       = "Cliquez %sici%s pour retourner au panneau de gestion des profils";
$lang['Add_user']                   = "Ajouter un utilisateur";
$lang['Del_user']                   = "Supprimer cet utilisateur";
$lang['Del_note']                   = "Attention, cette opération est irréversible";
$lang['Email_new_subscribe']        = "Être prévenu par email des nouvelles inscriptions";
$lang['Email_unsubscribe']          = "Être prévenu par email des désinscriptions";
$lang['New_passwd']                 = "Nouveau mot de passe";
$lang['Confirm_passwd']             = "Confirmez le mot de passe";
$lang['Note_passwd']                = "seulement si vous changez votre mot de passe";
$lang['Choice_user']                = "Sélectionnez un utilisateur";
$lang['View_profile']               = "Voir le profil de";
$lang['Confirm_del_user']           = "Vous confirmez la suppression de l’utilisateur sélectionné ?";
$lang['User_level']                 = "Niveau de cet utilisateur";
$lang['Liste_name2']                = "Nom de la liste";
$lang['HTML_editor']                = "Activer l’éditeur HTML";


//
// Page : Gestion des listes
//
$lang['Explain']['liste']           = "Ici, vous pouvez ajouter, modifier, supprimer des listes de diffusion, et régler le système de purge.";
$lang['Explain']['purge']           = "Le système de purge vous permet de nettoyer automatiquement la table des abonnés en supprimant les comptes non activés et dont la date de validité est dépassée.\nCette option est inutile si votre liste ne demande pas de confirmation d’inscription";
$lang['Explain']['cron']            = "Si vous voulez utilisez l’option de gestion des inscriptions avec cron, remplissez les champs ci dessous (voir %sla faq%s)";
$lang['Click_create_liste']         = "Cliquez %sici%s pour créer une liste de diffusion";
$lang['Click_return_liste']         = "Cliquez %sici%s pour retourner aux informations sur cette liste";
$lang['ID_list']                    = "ID de la liste";
$lang['Liste_name']                 = "Nom de la liste de diffusion";
$lang['Liste_public']               = "Liste publique";
$lang['Liste_startdate']            = "Date de création de cette liste";
$lang['Auth_format']                = "Format autorisé";
$lang['Sender_email']               = "Adresse email d’envoi";
$lang['Return_email']               = "Adresse email pour les retours d’erreurs";
$lang['Confirm_subscribe']          = "Demande de confirmation";
$lang['Confirm_always']             = "Toujours";
$lang['Confirm_once']               = "À la première inscription";
$lang['Limite_validate']            = "Limite de validité pour la confirmation d’inscription";
$lang['Note_validate']              = "(inutile si on ne demande pas de confirmation)";
$lang['Enable_purge']               = "Activer la purge automatique";
$lang['Purge_freq']                 = "Fréquence des purges";
$lang['Total_newsletter_list']      = "Nombre total de newsletters envoyées";
$lang['Reg_subscribers_list']       = "Nombre d’inscrits à cette liste";
$lang['Tmp_subscribers_list']       = "Nombre d’inscriptions non confirmées";
$lang['Last_newsletter2']           = "Dernière newsletter envoyée le";
$lang['Form_url']                   = "URL absolue de la page où se trouve le formulaire";
$lang['Form_url_note']              = "Si laissé vide, une adresse pointant vers subscribe.php sera utilisée";
$lang['Create_liste']               = "Créer une liste";
$lang['Edit_liste']                 = "Modifier cette liste";
$lang['Delete_liste']               = "Supprimer cette liste";
$lang['Move_abo_logs']              = "Que souhaitez-vous faire des abonnés et newsletters rattachés à cette liste ?";
$lang['Delete_all']                 = "Êtes-vous sûr de vouloir supprimer cette liste, ainsi que les abonnés et newsletters qui y sont rattachés ?";
$lang['Move_to_liste']              = "Déplacer les abonnés et newsletters vers";
$lang['Delete_abo_logs']            = "Ou les retirer de la base de données";
$lang['Use_cron']                   = "Utiliser l’option cron";
$lang['Pop_server']                 = "Nom ou IP du serveur POP";
$lang['Pop_port']                   = "Port de connexion";
$lang['Pop_user']                   = "Nom d’utilisateur";
$lang['Pop_pass']                   = "Mot de passe";
$lang['Liste_alias']                = "Alias de la liste (si nécessaire)";


//
// Page : Gestion des logs/archives
//
$lang['Explain']['logs']            = "Ici, vous pouvez visualiser et supprimer les newsletters précédemment envoyées";
$lang['Click_return_logs']          = "Cliquez %sici%s pour retourner à la liste des newsletters";
$lang['Log_subject']                = "Sujet de la newsletter";
$lang['Log_date']                   = "Date d’envoi";
$lang['Log_numdest']                = "Nombre de destinataires";
$lang['Log_numdest_short']          = "Dest.";
$lang['Delete_logs']                = "Êtes-vous sûr de vouloir supprimer les newsletters sélectionnés ?";
$lang['Delete_log']                 = "Êtes-vous sûr de vouloir supprimer cette newsletter ?";
$lang['No_log_sended']              = "Aucune newsletter n’a été envoyée à cette liste";
$lang['Joined_files']               = "Cette archive a %d fichiers joints";
$lang['Joined_file']                = "Cette archive a un fichier joint";
$lang['Export_nl']                  = "Exporter cette newsletter";


//
// Page : Gestion des abonnés
//
$lang['Explain']['abo']             = "Ici, vous pouvez voir, modifier et supprimer les comptes des personnes qui se sont inscrites à vos listes de diffusion";
$lang['Click_return_abo']           = "Cliquez %sici%s pour retourner à la liste des abonnés";
$lang['Click_return_abo_profile']   = "Cliquez %sici%s pour retourner au profil de l’abonné";
$lang['Delete_abo']                 = "Êtes-vous sûr de vouloir supprimer les abonnés sélectionnés ?";
$lang['No_abo_in_list']             = "Il n’y a pas encore d’abonné à cette liste de diffusion";
$lang['Susbcribed_date']            = "Date d’inscription";
$lang['Search_abo']                 = "Faire une recherche par mots clés";
$lang['Search_abo_note']            = "(vous pouvez utiliser * comme joker)";
$lang['Days_interval']              = "Inscrits les %d derniers jours";
$lang['All_abo']                    = "Tous les abonnés";
$lang['Inactive_account']           = "Les comptes non activés";
$lang['No_search_result']           = "La recherche n’a retourné aucun résultat";
$lang['Abo_pseudo']                 = "Pseudo de l’abonné";
$lang['Liste_to_register']          = "Cet abonné est inscrit aux listes suivantes";
$lang['Fast_deletion']              = "Suppression rapide";
$lang['Fast_deletion_note']         = "Entrez une ou plusieurs adresses emails, séparées par une virgule, et elles seront supprimées de la liste de diffusion";
$lang['Choice_Format']              = "format choisi";
$lang['Warning_email_diff']         = "Attention, vous allez modifier l’adresse email de cet abonné\nSouhaitez-vous continuer ?";
$lang['Goto_list']                  = "Retour à la liste des abonnés";
$lang['View_account']               = "Voir ce compte";
$lang['Edit_account']               = "Modifier ce compte";
$lang['TagsList']                   = "Liste des tags";
$lang['TagsEdit']                   = "Édition des tags";


//
// Page : Outils du script
//
$lang['Explain']['tools']           = "Vous avez à votre disposition plusieurs outils pour gérer au mieux vos listes de diffusion";
$lang['Explain']['export']          = "Vous pouvez ici exporter les adresses email d’une liste donnée, et pour le format donné (non pris en compte si la liste n’est pas multi-format).\nSi vous n’indiquez aucun caractère de séparation, le fichier contiendra un email par ligne";
$lang['Explain']['import']          = "Si vous voulez ajouter plusieurs adresses email, mettez un email par ligne ou séparez-les par un caractère tel que ; et indiquez-le dans le champ en question.\nSi votre serveur l’autorise, vous pouvez uploader un fichier contenant la liste des emails, indiquez également le caractère de séparation (sauf si un email par ligne). Dans le cas contraire, vous avez toutefois la possibilité de spécifier le chemin vers un fichier préalablement uploadé via ftp (chemin relatif à partir de la racine du script) .\nSi le fichier est compressé dans un format supporté par le serveur et le script, il sera automatiquement décompressé.\n(une limite de %s emails a été fixée; Voyez la %sfaq du script%s pour plus de détails)";
$lang['Explain']['ban']             = "Vous pouvez bannir un email entier, de type user@domain.com, ou un fragment d’email en utilisant * comme joker\n\n <u>Exemples</u> :\n <ul><li> toto@titi.com, l’utilisateur ayant l’email toto@titi.com ne pourra s’inscrire</li><li> *.fr.st; Tous les emails ayant pour extension .fr.st ne pourront s’inscrire</li><li> *@domaine.net, tous les emails ayant pour extension @domaine.net ne pourront s’inscrire</li><li> eviluser@*, tous les emails ayant pour prefixe eviluser@ ne pourront s’inscrire</li><li> *warez*, tous les emails contenant le mot warez ne pourront s’inscrire</li></ul>";
$lang['Explain']['unban']           = "Pour débannir un email ou fragment d’email, utilisez la combinaison clavier/souris appropriée sur votre ordinateur et votre navigateur";
$lang['Explain']['forbid_ext']      = "Pour interdire plusieurs extensions de fichiers en même temps, séparez-les par une virgule";
$lang['Explain']['reallow_ext']     = "Pour réautoriser une ou plusieurs extensions, utilisez la combinaison clavier/souris appropriée sur votre ordinateur et votre navigateur";
$lang['Explain']['backup']          = "Ce module vous permet de sauvegarder les tables du script, ainsi que d’éventuelles autres tables spécifiées, s’il y en a.\nVous pouvez décider de sauvegarder tout, uniquement la structure ou les données, et vous pouvez demander à ce que le fichier soit compressé (selon les options disponibles et librairies installées sur le serveur).\nEnfin, vous pouvez soit télécharger directement le fichier, ou demander au script de le stocker sur le serveur, auquel cas, le fichier sera créé dans le répertoire des fichiers temporaires du script.\n\n<strong>Attention :</strong> Cet outil ne convient que pour des tables aux structures relativement simples. Si vos tables utilisent des clés étrangères ou stockent des données binaires, vous devez vous tourner vers d’autres outils plus spécialisés pour obtenir un export utilisable.";
$lang['Explain']['restore']         = "Ce module vous permet de restaurer les tables du script à l’aide d’une sauvegarde SQL générée par wanewsletter.\n N’utilisez que les fichiers SQL générés par Wanewsletter ! (le parseur est minimaliste) \nSi l’upload de fichier n’est pas autorisé sur le serveur, vous avez toutefois la possibilité de spécifier un fichier précédemment uploadé via ftp en indiquant son chemin (relatif à la racine du script)";
$lang['Explain']['generator']       = "Vous devez entrer ici l’adresse absolue où les données du formulaire seront reçues (en général, l’adresse où se trouve le formulaire lui même)";
$lang['Explain']['code_html']       = "Placez ce code à l’adresse que vous avez/allez indiquer dans la configuration de la liste de diffusion";
$lang['Explain']['code_php']        = "Vous devez placer ce code à l’adresse de destination du formulaire (adresse entrée précédemment), le fichier doit avoir l’extension php !";

$lang['Select_tool']                = "Sélectionnez l’outil que vous voulez utiliser";
$lang['Export_format']              = "Export au format";
$lang['Plain_text']                 = "texte plat";
$lang['Char_glue']                  = "Caractère de séparation";
$lang['Compress']                   = "Compression";
$lang['Format_to_export']           = "Exporter les abonnés qui ont le format";
$lang['Format_to_import']           = "Format à donner aux abonnés";
$lang['File_upload_restore']        = "Indiquez l’accès au fichier de sauvegarde";
$lang['File_upload']                = "<i>ou</i> bien, vous pouvez spécifier un fichier texte";
$lang['File_local']                 = "<i>ou</i> bien, vous pouvez spécifier un fichier présent sur le serveur";
$lang['No_email_banned']            = "Aucun email banni";
$lang['Ban_email']                  = "Email ou fragment d’email à bannir";
$lang['Unban_email']                = "Email ou fragment d’email à débannir";
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

$lang['Check_update']               = "Vérifier les mises à jour";
$lang['Used_version']               = "Vous utilisez <strong>Wanewsletter %s</strong>";
$lang['New_version_available']      = "Une nouvelle version est disponible";
$lang['Download_page']              = "page de téléchargement";
$lang['Version_up_to_date']         = "Votre version est à jour";


//
// Page : Envoi des newsletters
//
$lang['Explain']['send']            = "Le formulaire d’envoi vous permet de rédiger vos newsletters, de les envoyer, les sauvegarder ou les supprimer, de joindre des fichiers…\nSi vous utilisez le deuxième moteur d’envoi, vous pouvez, à l’instar de <code>{LINKS}</code>, placer <code>{NAME}</code> dans le texte, pour afficher le nom de l’abonné si celui ci l’a indiqué.\nVous pouvez également utiliser des tags d’inclusion pour ajouter du contenu externe. %sConsultez la FAQ%s pour plus de détails.\n\nSi vous créez un modèle réutilisable et que vous lancez l’envoi sans avoir sauvegardé, le modèle sera sauvegardé et une copie sera créée pour les archives. Si vous avez créé un modèle, vous pouvez le recharger, le modifier puis sauvegarder les changements. Toutefois, si vous faites cela en modifiant le statut de la newsletter, une copie sera créée et les modifications seront sauvegardées dessus et non sur le modèle";
$lang['Explain']['join']            = "Vous pouvez ici joindre des fichiers à votre newsletter (attention à ne pas trop alourdir votre newsletter)\nSi l’upload de fichier n’est pas autorisé sur le serveur, vous pourrez indiquer un fichier distant (ex&thinsp;: <samp>http://www.domaine.com/rep/image.gif</samp>) ou un fichier manuellement uploadé dans le répertoire des fichiers joints\nVous pouvez également utiliser un des fichiers joints dans une autre newsletter de cette liste";
$lang['Explain']['text']            = "Rédigez ici votre newsletter au format texte. N’oubliez pas de placer le lien de désinscription, soit en cliquant sur le bouton dédié s’il est disponible, soit en ajoutant manuellement le tag <code>{LINKS}</code> dans votre newsletter";
$lang['Explain']['html']            = "Rédigez ici votre newsletter au format html. N’oubliez pas de placer le lien de désinscription , soit en cliquant sur le bouton dédié s’il est disponible, soit en ajoutant manuellement le tag <code>{LINKS}</code> dans votre newsletter (le lien sera au format html)\nSi vous voulez utiliser un des fichiers joints (une image, un son...) dans la newsletter html, placer au lieu de l’adresse du fichier cid:nom_du_fichier\n\n<em>Exemple&thinsp;:</em>\n\nVous avez uploadé l’image image1.gif et désirez l’utiliser dans une balise image de la newsletter html, vous placerez alors la balise img avec pour l’attribut src : cid:image1.gif ( <code>&lt;img src=\"cid:image1.gif\" alt=\"texte alternatif\" /&gt;</code> )";
$lang['Explain']['load']            = "Vous pouvez spécifier les chemins vers des modèles externes et le script les chargera pour vous. Les urls http sont acceptées, ainsi que les chemins locaux tels que&nbsp;:
<ul>
<li>/path/to/document &ndash; part de la racine du serveur</li>
<li>~/path/to/document &ndash; le  signe tilde (~) est un raccourci pour partir de la racine de votre espace web</li>
<li>path/to/document &ndash; est relatif au répertoire d’installation de Wanewsletter</li>
</ul>";

$lang['Select_log_to_load']         = "Choisissez la newsletter à charger";
$lang['Load_by_URL']                = "Chargez une newsletter depuis une URL";
$lang['From_an_URL']                = "depuis une URL";
$lang['Create_log']                 = "Créer une newsletter";
$lang['Load_log']                   = "Charger une newsletter";
$lang['List_send']                  = "Liste des envois en cours";
$lang['Sending_newsletter']         = "Envoi de la lettre <q>%s</q>";
$lang['Next_sending_delay']         = "Prochain envoi d’emails dans %d secondes";
$lang['Process_sending']            = "Envoi en cours…";
$lang['Restart_send']               = "Reprendre cet envoi";
$lang['Cancel_send']                = "Annuler cet envoi";
$lang['Model']                      = "Modèle";
$lang['Dest']                       = "Destinataire";
$lang['Log_in_text']                = "Newsletter au format texte";
$lang['Log_in_html']                = "Newsletter au format HTML";
$lang['Format_text']                = "Format texte";
$lang['Format_html']                = "Format HTML";
$lang['Last_modified']              = "Dernière modification le %s";
$lang['Total_log_size']             = "Poids approximatif de la newsletter";
$lang['Join_file_to_log']           = "Fichier à joindre à cette newsletter";
$lang['Status']                     = "Statut";
$lang['Done']                       = "Effectué";
$lang['Status_writing']             = "Newsletter normale";
$lang['Status_model']               = "Modèle réutilisable";
$lang['File_on_server']             = "fichier existant";
$lang['Cancel_send_log']            = "Êtes-vous sûr de vouloir annuler cet envoi ? (Cela ne sera effectif que pour les envois restants)";
$lang['Test_send_finish']           = "Test effectué. Vérifiez vos boîtes mail.";
$lang['Test_send']                  = "Faire un test d’envoi";
$lang['Test_send_note']             = "Vous pouvez faire un test d’envoi pour vérifier l’affichage de votre lettre en condition réelle (voir aussi la %sFAQ%s). Indiquez une ou plusieurs adresses email séparées par une virgule et validez";


//
// Page : Statistiques
//
$lang['Explain']['stats']           = "Cette page vous permet de visualiser un graphique à barre, représentant le nombre d’inscriptions par jour, pour le mois et l’année donnés, ainsi qu’un deuxième graphique représentant la répartition des abonnés, par liste de diffusion.\nSi votre serveur n’a pas de librairie GD installée, vous devriez alors désactiver ce module dans la configuration du script";
$lang['Num_abo_per_liste']          = "Répartition des abonnés par liste de diffusion";
$lang['Subscribe_per_day']          = "Inscriptions/Jours";
$lang['Graph_bar_title']            = "Le nombre d’inscriptions par jour pour le mois donné";
$lang['Camembert_title']            = "Les parts des différentes listes par rapport au nombre total d’abonnés";
$lang['Stats_dir_not_writable']     = "Le répertoire <samp>stats/</samp> ne semble pas accessible en écriture !";
$lang['Prev_month']                 = "Mois précédent";
$lang['Next_month']                 = "Mois suivant";



//
// Installation du script
//
$lang['Welcome_in_install']         = "Bienvenue dans le script d’installation de Wanewsletter.\nAvant de continuer l’installation, prenez le temps de lire le fichier %slisez-moi%s, il contient des directives importantes pour la réussite de l’installation.\nAssurez-vous également d’avoir pris connaissance de la %slicence d’utilisation de Wanewsletter%s avant de continuer.";
$lang['Welcome_in_upgrade']         = "Bienvenue dans le script de mise à jour vers Wanewsletter <strong>%s</strong>.\n Par mesure de sécurité, il est <strong>fortement conseillé</strong> de faire une sauvegarde des tables de données du script avant de procéder à la mise à jour.\nUne fois que vous êtes prêt, lancez la mise à jour avec le bouton ci-dessous.";
$lang['Warning_reinstall']          = "<b>Attention !</b> Wanewsletter semble déjà installé. \nSi vous souhaitez réinstaller le script, entrez votre login et mot de passe d’administrateur. \nAttention, toutes les données de l’installation précédente seront définitivement perdues.\n Si vous souhaitez plutôt effectuer une mise à jour d’une installation existante, utilisez le script upgrade.php";
$lang['Start_install']              = "Démarrer l’installation";
$lang['Start_upgrade']              = "Démarrer la mise à jour";
$lang['No_db_support']              = "Désolé mais Wanewsletter %s requiert une base de données MySQL, PostgreSQL ou SQLite";
$lang['sqldir_perms_problem']       = "Pour utiliser Wanewsletter avec une base de données SQLite, vous devez rendre accessible en lecture et écriture le répertoire <samp>%s</samp> ciblé";
$lang['Config_file_found']          = "Fichier de configuration trouvé et chargé.";
$lang['Config_file_manual']         = "Vous pouvez également créer manuellement le fichier de configuration <samp>data/config.inc.php</samp> en partant d’une copie du fichier <samp>data/config.sample.inc.php</samp>.";
$lang['Install_target_server']      = "L’installation sera effectuée sur le serveur %s <strong>%s</strong>, dans la base de données <strong>%s</strong>.";
$lang['Install_target_file']        = "L’installation sera effectuée dans la base de données %s <strong>%s</strong>.";

$lang['Success_install']            = "L’installation s’est bien déroulée. \n<strong>Important :</strong> Vous devriez consulter l’entrée <q>%sprotection du répertoire <samp>data/</samp>%s</q> de la FAQ. \nVous pouvez maintenant accéder à %sl’administration%s";
$lang['Success_upgrade']            = "La mise à jour s’est bien déroulée.";
$lang['Success_install_no_config']  = "L’installation s’est bien déroulée, mais le fichier de configuration n’a pu être créé. \nVous pouvez le télécharger, puis le placer par vos propres moyens dans le répertoire <samp>data/</samp> du script (voir aussi l’entrée <q>%sprotection du répertoire <samp>data/</samp>%s</q> de la FAQ). \nVous pouvez ensuite accéder à %sl’administration%s.";
$lang['Success_upgrade_no_config']  = "La mise à jour s’est bien déroulée, mais votre fichier de configuration est obsolète et doit être actualisé. \nVous pouvez le télécharger puis l’uploader par vos propres moyens sur le serveur dans le répertoire <samp>data/</samp> du script (l’ancien emplacement dans le répertoire <samp>includes/</samp> est obsolète mais fonctionne toujours).";
$lang['Upgrade_not_required']       = "Aucune mise à jour n’est nécessaire pour votre version actuelle de Wanewsletter";
$lang['Unsupported_version']        = "Cette version de Wanewsletter n’est plus supportée par le script de mise à jour. Vous devriez d’abord faire une mise à jour vers une version 2.3.x.";
$lang['Moved_dirs_notice']          = "<strong>Note&nbsp;:</strong> Les répertoires <samp>stats/</samp> et <samp>tmp/</samp> se trouvent désormais dans le répertoire <samp>data/</samp>.\n Transférez le contenu des répertoires <samp>stats/</samp> et <samp>tmp/</samp> vers leurs équivalents dans <samp>data/</samp> et supprimez-les.\n N’oubliez pas de donner les droits en écriture sur ces répertoires.";
$lang['Unknown_files_notice']       = "Plusieurs fichiers ne faisant pas partie de l’installation de Wanewsletter ont été détectés. Ce sont peut-être des fichiers d’anciennes versions de Wanewsletter. Auquel cas, ils peuvent être supprimés sans crainte.";

$lang['Need_upgrade_db']            = "Une mise à jour des tables de données du script est nécessaire.";
$lang['Need_upgrade_db_link']       = "Cliquez %sici%s pour accéder au script de mise à jour.";

$lang['dbtype']                     = "Type de base de données";
$lang['dbpath']                     = "Chemin d’installation de la base de données sqlite";
$lang['dbpath_note']                = "Le répertoire parent de la base de données doit être accessible en lecture et écriture";
$lang['dbhost']                     = "Nom du serveur de base de données";
$lang['dbname']                     = "Nom de votre base de données";
$lang['dbuser']                     = "Nom d’utilisateur";
$lang['dbpwd']                      = "Mot de passe";
$lang['prefix']                     = "Préfixe des tables";


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
$lang['CONTENT_LANG']   = 'fr';
$lang['CONTENT_DIR']    = 'ltr'; // sens du texte Left To Right ou Right To Left
$lang['TRANSLATE']      = '';


// Formatage de nombres
$lang['DEC_POINT']      = ",";
$lang['THOUSANDS_SEP']  = "\xC2\xA0"; // Espace insécable
