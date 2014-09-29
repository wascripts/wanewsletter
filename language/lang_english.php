<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 * 
 * Vous pouvez très facilement traduire Wanewsletter dans une autre langue.
 * Il vous suffit pour cela de traduire ce qui se trouve entre
 * guillemets. Attention, ne touchez pas à la partie $lang['....']
 * 
 * des %1\$s, %s, %d ou autre signe de ce genre signifient qu'ils
 * vont être remplacés par un contenu variable. Placez les de façon
 * adéquat dans la phrase mais ne les enlevez pas.
 * Enfin, les \n représentent un retour à la ligne.
 */


$lang['General_title']              = "Mailing List Administration";

$lang['Title']['accueil']           = "General information about the newsletter";
$lang['Title']['install']           = "Wanewsletter Install";
$lang['Title']['upgrade']           = "Wanewsletter Upgrade";
$lang['Title']['reinstall']         = "Wanewsletter re-install";
$lang['Title']['database']          = "Database access";
$lang['Title']['admin']             = "Administration";
$lang['Title']['error']             = "Error!";
$lang['Title']['info']              = "Information!";
$lang['Title']['select']            = "Selection";
$lang['Title']['confirm']           = "Confirmation";
$lang['Title']['config_lang']       = "Language choice";
$lang['Title']['config_perso']      = "Personalization";
$lang['Title']['config_cookies']    = "Cookies";
$lang['Title']['config_email']      = "Emails sending options";
$lang['Title']['config_files']      = "Attached files"; //file attachment ?
$lang['Title']['config_stats']      = "Statistics Hack"; //show statistics sinon
$lang['Title']['config_divers']     = "Misc";
$lang['Title']['profile']           = "Profile for <q>%s</q>";
$lang['Title']['mod_profile']       = "Profile editing of <q>%s</q>";
$lang['Title']['manage']            = "User permissions";
$lang['Title']['other_options']     = "Misc Options";
$lang['Title']['info_liste']        = "Information about the mailing list";
$lang['Title']['add_liste']         = "Create a mailing list";
$lang['Title']['edit_liste']        = "Edit a mailing list";
$lang['Title']['purge_sys']         = "Purge system";
$lang['Title']['cron']              = "Cron option";
$lang['Title']['logs']              = "List of newsletters sent to this mailing list";
$lang['Title']['abo']               = "Subscribers list for this mailing list";
$lang['Title']['stats']             = "Mailing lists statistics";
$lang['Title']['tools']             = "Wanewsletter tools";
$lang['Title']['export']            = "Export email adresses";
$lang['Title']['import']            = "Import email adresses";
$lang['Title']['ban']               = "Banned emails management";
$lang['Title']['attach']            = "Files extensions management";
$lang['Title']['backup']            = "Backup system";
$lang['Title']['restore']           = "Restore system";
$lang['Title']['generator']         = "Subscription form generator";
$lang['Title']['debug']             = "Debug informations";
$lang['Title']['send']              = "Sending form";
$lang['Title']['join']              = "Attach a file to the newsletter";
$lang['Title']['joined_files']      = "Newsletter attached files";
$lang['Title']['profil_cp']         = "Panel of management of account";
$lang['Title']['sendkey']           = "Receive your identifiers";
$lang['Title']['archives']          = "logs of the mailing lists";
$lang['Title']['sendpass']          = "Generate a new password";
$lang['Title']['form']              = "Subscribe to newsletter";
$lang['Title']['check_update']      = "Check for updates";


//
// Modules de l'administration
//
$lang['Module']['accueil']          = "Home";
$lang['Module']['config']           = "Configuration";
$lang['Module']['login']            = "Login";
$lang['Module']['logout']           = "Logout";
$lang['Module']['logout_2']         = "Logout [%s]";
$lang['Module']['send']             = "Send";
$lang['Module']['users']            = "Users";
$lang['Module']['subscribers']      = "Subscribers";
$lang['Module']['list']             = "Lists";
$lang['Module']['log']              = "Old newsletters";
$lang['Module']['tools']            = "Tools";
$lang['Module']['stats']            = "Statistics";
$lang['Module']['editprofile']      = "Edit your profile";


//
// Texte des divers boutons
//
$lang['Button']['valid']            = "Submit";
$lang['Button']['reset']            = "Reset";
$lang['Button']['go']               = "Go";
$lang['Button']['edit']             = "Modify";
$lang['Button']['delete']           = "Delete";
$lang['Button']['cancel']           = "Cancel";
$lang['Button']['purge']            = "Purge";
$lang['Button']['classer']          = "Classify";
$lang['Button']['search']           = "Search";
$lang['Button']['save']             = "Save";
$lang['Button']['send']             = "Send";
$lang['Button']['preview']          = "Preview";
$lang['Button']['add_file']         = "Attach a file";
$lang['Button']['del_file']         = "Remove the selected files";

$lang['Button']['del_abo']          = "Remove the selected subscribers";
$lang['Button']['del_logs']         = "Remove the selected newsletters";
$lang['Button']['del_account']      = "Delete this account";
$lang['Button']['links']            = "Put the unsubscribe link";
$lang['Button']['dl']               = "Download";
$lang['Button']['conf']             = "Confirm";
$lang['Button']['browse']           = "Browse";


//
// Différents messages d'information et d'erreur
//
$lang['Message']['Subscribe_1']             = "Successful subscription!\nA confirmation email has been sent to your address.\nCaution, the confirmation link in the email will be valid for %d days!\nAfter this time, you will have to subscribe again.";
$lang['Message']['Subscribe_2']             = "Successful subscription!";
$lang['Message']['Confirm_ok']              = "Your subscription was confirmed!";
$lang['Message']['Unsubscribe_1']           = "Thank you, you will receive an email which will enable you to confirm your choice";
$lang['Message']['Unsubscribe_2']           = "You are now unsubscribed from this mailing list";
$lang['Message']['Unsubscribe_3']           = "Your email address was successfully removed from our database";
$lang['Message']['Success_setformat']       = "The format change operation was carried out successfully";
$lang['Message']['Invalid_email']           = "The email address you gave is not valid";
$lang['Message']['Unrecognized_email']      = "Unknown domain or unrecognized account by the server (%s)";
$lang['Message']['Unknown_email']           = "Unknown email address";
$lang['Message']['Email_banned']            = "This email address or this email type was banished";
$lang['Message']['Allready_reg']            = "You have already subscribed";
$lang['Message']['Allready_reg2']           = "The email address you entered already exist in the database";
$lang['Message']['Reg_not_confirmed']       = "You have already subscribed but you didn't confirm yet your inscription.\nConfirmation email has been sent to you.\nAttention, the confirmation link included in th email will be valid for %1\$d days!\nAfter these %1\$d days, you will have to re-subscribe";
$lang['Message']['Unknown_list']            = "Unknown list";
$lang['Message']['Failed_sending']          = "The email could not be sent!";
$lang['Message']['Inactive_format']         = "Impossible to change the format";
$lang['Message']['Invalid_date']            = "Sorry, the confirm date has exceeded";
$lang['Message']['Invalid_code']            = "Invalid code!";
$lang['Message']['Invalid_email2']          = "Invalid email address!";
$lang['Message']['Failed_sending2']         = "The email could not be sent! %s";

$lang['Message']['Success_export']          = "The emails export operation has been successfully carried out. \nYou will find the the backup file in the script's temporary files folder (Do not forget to delete it after saving it!)";
$lang['Message']['Success_import']          = "The import operation has been successfully carried out.";
$lang['Message']['Success_import2']         = "The import operation has been successfully carried out, but a few email addresses were rejected.";
$lang['Message']['Success_import3']         = "The import operation has been successfully carried out, but a few email addresses were rejected. \nClick %shere%s to download the report. (Do not forget to delete the file from the server after downloading it)";
$lang['Message']['Success_import4_0']       = "No email has been imported";
$lang['Message']['Success_import4_1']       = "%d email has been successfully imported";
$lang['Message']['Success_import4_n']       = "%d emails has been successfully imported";
$lang['Message']['Success_modif']           = "The modifications have been successfully carried out";
$lang['Message']['Success_backup']          = "The tables backup has been successfully carried out. \nYou will find the backup file in the script's temporary files folder (Do not forget to delete it after saving it!)";
$lang['Message']['Success_restore']         = "The data restore operation has been successfully carried out";
$lang['Message']['Success_logout']          = "You have been logged out from the Mailing List Administration";
$lang['Message']['Success_purge']           = "The purge operation has been successfully carried out (%d deleted subscriber(s))";
$lang['Message']['Success_send']            = "The partial sending has been successfully carried out to <b>%d</b> subscribers.\nThe newsletter has been sent until now to <b>%d</b> subscribers on a total of <b>%d</b>";
$lang['Message']['Success_send_finish']     = "Sending successfully finished.\nThis newsletter has been sent to a total of <b>%d</b> subscribers";
$lang['Message']['Success_operation']       = "The operation has been successfully carried out";

$lang['Message']['Profile_updated']         = "The profils has been successfully updated";
$lang['Message']['Admin_added']             = "The user has been successfully added.  He will receive his login information by email.";
$lang['Message']['Admin_deleted']           = "The user has been successfully deleted";
$lang['Message']['liste_created']           = "The new mailing list has been successfully created";
$lang['Message']['liste_edited']            = "The mailing list has been successfully modified";
$lang['Message']['Liste_del_all']           = "The mailing list, and its related subscribers and newsletters have been successfully deleted";
$lang['Message']['Liste_del_move']          = "The new mailing list has been successfully deleted.\nIts related subscribers and newsletters have been moved to the selected list";
$lang['Message']['logs_deleted']            = "The newsletters have been successfully deleted";
$lang['Message']['log_deleted']             = "The newsletter has been successfully deleted";
$lang['Message']['log_saved']               = "The newsletter has been successfully saved";
$lang['Message']['log_ready']               = "The newsletter has been successfully saved and is ready to be sent";
$lang['Message']['abo_deleted']             = "The subscribers have been successfully deleted";
$lang['Message']['Send_canceled']           = "Operation successfully finished. All the remaining sendings for this newsletter have been canceled";
$lang['Message']['List_is_busy']            = "An operation is currently in progress on this list. Please wait a few seconds before attempting the operation again";

$lang['Message']['Not_authorized']          = "You do not have sufficient permissions to access this page or to carry out this action";
$lang['Message']['Not_auth_view']           = "You are not authorized to view this mailing list";
$lang['Message']['Not_auth_edit']           = "You are not authorized to make modifications to this mailing list";
$lang['Message']['Not_auth_del']            = "You are not authorized to make deletions on this mailing list";
$lang['Message']['Not_auth_send']           = "You are not authorized to make sendings on this mailing list";
$lang['Message']['Not_auth_import']         = "You are not authorized to import email addresses in this mailing list";
$lang['Message']['Not_auth_export']         = "You are not authorized to export email addresses from this mailing list";
$lang['Message']['Not_auth_ban']            = "You are not authorized to make modifications to this mailing list's bannishing list";
$lang['Message']['Not_auth_attach']         = "You are not authorized to attach files or view attached files in this mailing list";

$lang['Message']['Error_login']             = "Incorrect login or password!";
$lang['Message']['Error_sendpass']          = "incorrect login or email!";
$lang['Message']['Bad_confirm_pass']        = "The new password and confirmed password are not the same";
$lang['Message']['Bad_confirm_email']       = "The confirmation of your new email address is incorrect";
$lang['Message']['bad_ftp_param']           = "The connexion to the ftp server could not be established, please check your settings \n(%s)";
$lang['Message']['bad_smtp_param']          = "The connexion to the smtp server could not be established, please check your settings \n(%s)";
$lang['Message']['bad_pop_param']           = "The connexion to the pop server could not be established, please check your settings \n(%s)";
$lang['Message']['Alphanum_pass']           = "The password must consist of a minimum of 6 ASCII printable characters";
$lang['Message']['Invalid_session']         = "Invalid session!";
$lang['Message']['fields_empty']            = "Certain required fields are not filled out";
$lang['Message']['Owner_account']           = "You cannot delete your own account!";
$lang['Message']['Invalid_login']           = "This username is not valid. A username must contain between 2 and 30 characters";
$lang['Message']['Double_login']            = "This username is already taken";
$lang['Message']['No_liste_exists']         = "Not an available list";
$lang['Message']['No_liste_id']             = "No mailing list has been selected";
$lang['Message']['No_log_id']               = "No newsletter has been selected";
$lang['Message']['log_not_exists']          = "This newsletter does not exist!";
$lang['Message']['No_log_to_send']          = "There is currently no sending to resume";
$lang['Message']['No_abo_id']               = "No subscriber has been selected";
$lang['Message']['No_abo_email']            = "None of these addresses email is present in this mailing list";
$lang['Message']['abo_not_exists']          = "This subscriber does not exist!";
$lang['Message']['Failed_open_file']        = "Unable to open the received file";
$lang['Message']['File_not_exists']         = "The file %s doesn't exist or isn't readable";
$lang['Message']['Bad_file_type']           = "The received file type has been prohibited or is not valid";
$lang['Message']['Error_local']             = "No file found in %s";
$lang['Message']['No_data_received']        = "No data has been received";
$lang['Message']['Stats_disabled']          = "The statistics module has been deactivated";
$lang['Message']['No_gd_lib']               = "This module requires the GD library. Apparently it is not present on the server";
$lang['Message']['No_subscribers']          = "You cannot send any newsletter to this list because it does not have any subscriber yet.";
$lang['Message']['Unknown_engine']          = "No send engine specyfied";
$lang['Message']['No_log_found']            = "No newsletter ready to be sent has been found";
$lang['Message']['Invalid_url']             = "Given URL is not valid";
$lang['Message']['Unaccess_host']           = "The host %s seems actually unreachable";
$lang['Message']['Not_found_at_url']        = "The file does not seems to be at indicated URL";
$lang['Message']['Error_load_url']          = "Error while loading url \"%1\$s\" (%2\$s)";
$lang['Message']['No_form_url']             = "you don't have specified the url of the form into the %sconfig of your list%s.\nYou must correct it before starting to send mail(s).";
$lang['Message']['File_not_found']          = "This file was not found on server";

$lang['Message']['Cannot_create_dir']       = "Cannot create %s directory";
$lang['Message']['Dir_not_writable']        = "The directory <samp>%s</samp> doesn't exist or is not writable";
$lang['Message']['sql_file_not_readable']   = "The sql files are not accessible for reading! (setup/schemas/)";

$lang['Message']['Ftp_unable_connect']      = "Unable to connect to the ftp server";
$lang['Message']['Ftp_error_login']         = "Authentification with the ftp server has failed";
$lang['Message']['Ftp_error_mode']          = "Impossible to change server mode";
$lang['Message']['Ftp_error_path']          = "Impossible to access the specified folder";
$lang['Message']['Ftp_error_put']           = "Impossible to upload the file on the ftp server";
$lang['Message']['Ftp_error_get']           = "Impossible to get the file from the ftp server";
$lang['Message']['Ftp_error_del']           = "Impossible to delete the file from the ftp server";

$lang['Message']['Uploaddir_not_writable']  = "The upload dir isn't writable";
$lang['Message']['Upload_error_1']          = "The file exceeds the size authorized by the upload_max_filesize directive in php.ini";
$lang['Message']['Upload_error_2']          = "The file exceeds the size authorized by the MAX_FILE_SIZE field";
$lang['Message']['Upload_error_3']          = "The file has been partially uploaded";
$lang['Message']['Upload_error_4']          = "No file has been uploaded";
$lang['Message']['Upload_error_5']          = "The file could not be uploaded due to a unknown error";
$lang['Message']['Upload_error_6']          = "Missing a temporary folder";
$lang['Message']['Upload_error_7']          = "Failed to write file to disk";
$lang['Message']['Invalid_filename']        = "Invalid file name";
$lang['Message']['Invalid_action']          = "Invalid action";
$lang['Message']['Invalid_ext']             = "This file extension has been prohibited";
$lang['Message']['weight_too_big']          = "The overall size of attached files exceeds the authorized maximum, you have %s remaining";

$lang['Message']['Compress_unsupported']    = "Unsupported compression format";
$lang['Message']['Database_unsupported']    = "This database is not supported by the backup/restore system";

$lang['Message']['Profil_cp_disabled']      = "The panel of management of account is actually disabled";
$lang['Message']['Inactive_account']        = "Your account is actually inactive, you had to receive an email to activate it.";
$lang['Message']['IDs_sended']              = "Your identifiers were sent to you by email";
$lang['Message']['Logs_sent']               = "The selected newsletters were sent at your address: %s";
$lang['Message']['Archive_class_needed']    = "The export module needs the <q>%s</q> <abbr title=\"PHP Extension and Application Repository\">PEAR</abbr> package. Please refer to the documentation for more details.";
$lang['Message']['Chdir_error']             = "Unable to set current directory with %s (chdir() error)";
$lang['Message']['Twice_sending']           = "A newsletter is already in the course of sending for this list. Finish or cancel this sending before beginning another of them.";


//
// Divers
//
$lang['Subscribe']                  = "Subscribe";
$lang['Unsubscribe']                = "Unsubscribe";
$lang['Setformat']                  = "Format change";
$lang['Email_address']              = "Email address";
$lang['Format']                     = "Format";
$lang['Diff_list']                  = "Mailing lists";
$lang['Start']                      = "Start";
$lang['End']                        = "End";
$lang['Prev']                       = "Previous";
$lang['Next']                       = "Next";
$lang['First_page']                 = "First page";
$lang['Prev_page']                  = "Previous page";
$lang['Next_page']                  = "Next page";
$lang['Last_page']                  = "Last page";
$lang['Yes']                        = "yes";
$lang['No']                         = "no";
$lang['Login']                      = "Access login";
$lang['Password']                   = "Access password";
$lang['Not_available']              = "Not available";
$lang['Seconds']                    = "seconds";
$lang['Days']                       = "days";
$lang['Other']                      = "Other";
$lang['Unknown']                    = "Unknown";
$lang['Choice_liste']               = "Select a list";
$lang['View_liste']                 = "Manage a list";
$lang['Admin']                      = "Administrator";
$lang['User']                       = "User";
$lang['Page_of']                    = "Page <b>%d</b> of <b>%d</b>";
$lang['Classement']                 = "Sort";
$lang['By_subject']                 = "by subject";
$lang['By_date']                    = "by date";
$lang['By_email']                   = "by email";
$lang['By_format']                  = "by format";
$lang['By_asc']                     = "ascending";
$lang['By_desc']                    = "descending";
$lang['Filename']                   = "Filename";
$lang['Filesize']                   = "Filesize";
$lang['No_data']                    = "No data";
$lang['GO']                         = "GiB";
$lang['MO']                         = "MiB";
$lang['KO']                         = "KiB";
$lang['Octets']                     = "Bytes";
$lang['Show']                       = "Show";
$lang['View']                       = "View";
$lang['Edit']                       = "Edit";
$lang['Import']                     = "Import";
$lang['Export']                     = "Export";
$lang['Ban']                        = "Ban";
$lang['Attach']                     = "Attach";
$lang['Autologin']                  = "Connect automatically";
$lang['Faq']                        = "Script's FAQ";
$lang['Author_note']                = "Author's notes";
$lang['Page_loading']               = "Please wait while the page is loading";
$lang['Label_link']                 = "To unsubscribe";
$lang['Account_login']              = "Enter the email address of your account";
$lang['Account_pass']               = "Password or code of your account";
$lang['Maximum_size']               = "Maximum size: %s";
$lang['Lost_password']              = "Lost password?";
$lang['Name']                       = "Name";
$lang['Value']                      = "Value";
$lang['Cookie_notice']              = "You must enable cookies to log in";

$lang['Click_return_index']         = "Click %shere%s to return to the home page";
$lang['Click_return_back']          = "Click %shere%s to go back to the previous page";
$lang['Click_return_form']          = "Click %shere%s to go back to the form";
$lang['Click_start_send']           = "Click %shere%s if you wish to start the sending now";
$lang['Click_resend_auto']          = "Click %shere%s to continue with automatic sendings";
$lang['Click_resend_manuel']        = "Click %shere%s to send another packet of emails";

//
// Sujets de divers emails envoyés
//
$lang['Subject_email']['Subscribe'] = "Subscription to the %s newsletter";
$lang['Subject_email']['Unsubscribe_1'] = "Unsubscription confirmation";
$lang['Subject_email']['New_subscribe'] = "New newsletter subscriber";
$lang['Subject_email']['Unsubscribe_2'] = "New newsletter unsubscriber";
$lang['Subject_email']['New_admin'] = "Administration of %s newsletter";
$lang['Subject_email']['New_pass']  = "Your new password";
$lang['Subject_email']['Sendkey']   = "The identifiers of your account";


//
// Panneau de gestion de compte (profil_cp.php)
//
$lang['Welcome_profil_cp']          = "Welcome on the manage panel of your account.\nYou can here modify your subscriber profile and view the archives.";
$lang['Explain']['editprofile']     = "Here, you have the possibility to modify the data of your account.\nYou can inform your first name or pseudo to personalize the newsletters which you will receive (according to the settings of the administrator). You can also put a password to your account, what will be simpler to type than the code of your account.";
$lang['Explain']['sendkey']         = "If you lost the identifiers of your account, you can ask them to be sent back to you by email";
$lang['Explain']['archives']        = "You can ask to receive the previous newsletters sent to the mailing lists to which you are registered.\nBe careful, for each newsletter selected, you will receive an email.";
$lang['Explain']['change_email']    = "The next two fields let you change the email address of your account. Remember that your email address is used to connect to this interface.";

$lang['New_Email']                  = "Enter your new email address";
$lang['Confirm_Email']              = "Confirme your email address";


//
// Page d'accueil
//
$lang['Explain']['accueil']         = "Welcome to the Wanewsletter administration. We thank you for choosing Wanewsletter as your newsletter/mailing list solution.\n The administration page will allow you to manage your mailing lists in a very simple manner. \nYou can come back to this page at all times by clicking on the Wanewsletter logo located at the top left of the screen.";
$lang['Registered_subscribers']     = "There are <b>%1\$d</b> subscribers, with an average of <b>%2\$s</b> new subscribers per day";
$lang['Registered_subscriber']      = "There is <b>1</b> subscriber, with an average of <b>%s</b> new subscribers per day";
$lang['No_registered_subscriber']   = "There are no subscribers at the moment";
$lang['Tmp_subscribers']            = "There are <b>%d</b> people that have not confirmed their subscription yet";
$lang['Tmp_subscriber']             = "There is <b>1</b> person who has not confirmed his subscription yet";
$lang['No_tmp_subscriber']          = "There are no unconfirmed subscribes at the moment";
$lang['Last_newsletter']            = "Last newsletter sent on <b>%s</b>";
$lang['Total_newsletters']          = "A total of <b>%1\$d</b> newsletters have been sent, with an average of <b>%2\$s</b> newsletters per month";
$lang['Total_newsletter']           = "A total of <b>1</b> newsletter has been sent, with an average of <b>%s</b> newsletters per month";
$lang['No_newsletter_sended']       = "No newsletter has been sent yet";
$lang['Dbsize']                     = "Database size (script's tables)";
$lang['Total_Filesize']             = "Disk space used by files (joined files and statistics)";


//
// Page : Configuration
//
$lang['Explain']['config']          = "The following form will allow you to configure all of the script's settings";
$lang['Explain']['config_cookies']  = "These parameters allow you to set the cookies used by the script. \nIf you feel unsure about this, leave the settings to their default values";
$lang['Explain']['config_files']    = "You can attach files to your outgoing newsletters. \nTo accomplish this, the script has two options. The easiest option is to have files stored on the server, in the folder defined as a storage directory (the folder must have writing permissions). \nIf, for any reason, this option is not possible on your server, the script offers you the possibility to store the files on a <abbr title=\"File Transfert Protocol\" lang=\"en\">FTP</abbr> server.\n For this option, you must enter acces parameters for the ftp server.";
$lang['Explain']['config_email']    = "These settings allow you to configure sending emails.\nBy default, the script send a personalized email to each subscriber, but you can configure it to send one or more emails with a list of recipients in a BCC field.\nIf you want to use a specific <abbr title=\"Simple Mail Transfert Protocol\">SMTP</abbr> server, turn on the option and fill in the login informations. By default, the script uses the <abbr title=\"PHP: Hypertext Preprocessor\">PHP</abbr> <code>mail()</code> function. See %sFAQ%s about limitations in the context of using <abbr>SMTP</abbr> server.";
$lang['Explain']['config_stats']    = "The script has a small statistics module. The module requires that the GD libraty is installed on your server to make it work. \nIf you don't wish to use this functionnality, it is recommended that you deactivate the statistics module to avoid unnecessary data processing by the script.";

$lang['Default_lang']               = "Select the default language";
$lang['Sitename']                   = "Your site's name";
$lang['Urlsite']                    = "Your site's URL";
$lang['Urlsite_note']               = "(eg : http://www.mysite.com)";
$lang['Urlscript']                  = "The script's URL";
$lang['Urlscript_note']             = "(eg : /directory/)";
$lang['Sig_email']                  = "Signature to add at the end of emails";
$lang['Sig_email_note']             = "(subscription and confirmation emails)";
$lang['Dateformat']                 = "Date format";
$lang['Fct_date']                   = "See the %sdate()%s function";
$lang['Enable_profil_cp']           = "Enable the panel of management of account for subscribers";
$lang['Cookie_name']                = "Cookie name";
$lang['Cookie_path']                = "Cookie path";
$lang['Session_length']             = "Administration session lenght";
$lang['Upload_path']                = "Attached files storage directory";
$lang['Max_filesize']               = "Maximum total size of a newsletter's attached files";
$lang['Max_filesize_note']          = "(sum of the size of all attached files, in bytes)";
$lang['Use_ftp']                    = "Use a ftp server to store files";
$lang['Ftp_server']                 = "Ftp server name";
$lang['Ftp_server_note']            = "(name without the inital ftp://, or ip address)";
$lang['Ftp_port']                   = "Connection port";
$lang['Ftp_port_note']              = "The default value should be appropriate in most cases.";
$lang['Ftp_pasv']                   = "Ftp server passive mode";
$lang['Ftp_pasv_note']              = "(Active or passive mode)";
$lang['Ftp_path']                   = "Path to the file storage directory";
$lang['Ftp_user']                   = "User name";
$lang['Ftp_pass']                   = "Password";
$lang['Check_email']                = "Email check on subscription";
$lang['Check_email_note']           = "(Checks if the domain and associated account exist. See the %sscript's FAQ%s)";
$lang['Choice_engine_send']         = "Email send method to use";
$lang['With_engine_bcc']            = "One email with recipients in hidden copy";
$lang['With_engine_uniq']           = "One email to each subscriber";
$lang['Emails_paquet']              = "Number of emails per sent packet";
$lang['Emails_paquet_note']         = "Leave with 0 to send everything in one packet";
$lang['Use_smtp']                   = "Use of a <abbr title=\"Simple Mail Transfert Protocol\" lang=\"en\">smtp</abbr> server to send emails";
$lang['Use_smtp_note']              = "Use only if your server does not have an email sending function or if you want to use a specific SMTP server!";
$lang['Smtp_server']                = "smtp server address";
$lang['Smtp_port']                  = "Connection port";
$lang['Smtp_port_note']             = "The default value should be appropriate in most cases.";
$lang['Smtp_user']                  = "Your smtp login";
$lang['Smtp_pass']                  = "Your smtp password";
$lang['Auth_smtp_note']             = "Only if your smtp server requires authentication!";
$lang['Disable_stats']              = "Deactivate the statistics module";


//
// Page : Gestion et permissions des admins
//
$lang['Explain']['admin']           = "On this panel, you can manage your profile.\nIf you have proper rights, you can also manage others administrators, their profile, their rights, add new administrators, delete...";
$lang['Click_return_profile']       = "Click %shere%s to return to the profiles management panel";
$lang['Add_user']                   = "Add a user";
$lang['Del_user']                   = "Delete this user";
$lang['Del_note']                   = "Caution, this operation is irreversible";
$lang['Email_new_subscribe']        = "Be notified of new subscriptions by email";
$lang['Email_unsubscribe']          = "Be notified of unsubscriptions by email";
$lang['New_pass']                   = "New password";
$lang['Conf_pass']                  = "Confirm the new password";
$lang['Note_pass']                  = "only if you change your password";
$lang['Choice_user']                = "Select a user";
$lang['View_profile']               = "View profile of";
$lang['Confirm_del_user']           = "Do you confirm the removal of the selected user?";
$lang['Login_new_user']             = "His login";
$lang['Email_new_user']             = "His email";
$lang['Email_note']                 = "(Where he will receive his password)";
$lang['User_level']                 = "This user's level";
$lang['Liste_name2']                = "List name";


//
// Page : Gestion des listes
//
$lang['Explain']['liste']           = "Here, you can add, modify, delete mailing lists, and set up the purge system.";
$lang['Explain']['purge']           = "The purge system allows you to automatically clean the subscribers' table by deleting un-activated subscriptions for which the confirmation delay has expired.\nThis option is useless if your list does not require a subscription confirmation.";
$lang['Explain']['cron']            = "If you want to use the subscription management with cron, fill out the following fields (see %sthe FAQ%s)";
$lang['Click_create_liste']         = "Click %shere%s to create a mailing list";
$lang['Click_return_liste']         = "Click %shere%s to return to this list's information page";
$lang['ID_list']                    = "List's ID";
$lang['Liste_name']                 = "List name";
$lang['Liste_public']               = "Public list";
$lang['Liste_startdate']            = "List created on date";
$lang['Auth_format']                = "Authorized format";
$lang['Sender_email']               = "Sender email address";
$lang['Return_email']               = "Return email address, in case of errors";
$lang['Confirm_subscribe']          = "Confirmation request";
$lang['Confirm_always']             = "Always";
$lang['Confirm_once']               = "At the first inscription";
$lang['Limite_validate']            = "Valid time delay for subscription confirmation";
$lang['Note_validate']              = "(useless if confirmation is not required)";
$lang['Enable_purge']               = "Enable automatic purge";
$lang['Purge_freq']                 = "Purge frequency";
$lang['Total_newsletter_list']      = "Total number of sent newsletters";
$lang['Reg_subscribers_list']       = "Number of subscribers to this list";
$lang['Tmp_subscribers_list']       = "Number of unconfirmed subscriptions";
$lang['Last_newsletter2']           = "Last newsletter sent";
$lang['Form_url']                   = "Absolute URL of the page where the form is located";
$lang['Create_liste']               = "Create a list";
$lang['Edit_liste']                 = "Edit this list";
$lang['Delete_liste']               = "Delete this list";
$lang['Invalid_liste_name']         = "Your list's name must contain between 3 and 30 characters";
$lang['Unknown_format']             = "Unknown format";
$lang['Move_abo_logs']              = "What do you want to do with this list's subscribers and newsletters?";
$lang['Delete_all']                 = "Are you sure you want to delete this list and its associated subscribers and newsletters?";
$lang['Move_to_liste']              = "Move subscribers and newsletters to";
$lang['Delete_abo_logs']            = "Or remove them from the database";
$lang['Use_cron']                   = "Use cron";
$lang['Pop_server']                 = "POP server's name or IP address";
$lang['Pop_port']                   = "Connection port";
$lang['Pop_port_note']              = "The default value should be appropriate in most cases.";
$lang['Pop_user']                   = "Connection login";
$lang['Pop_pass']                   = "Connection password";
$lang['Liste_alias']                = "List's alias (if necessary)";


//
// Page : Gestion des logs/archives
//
$lang['Explain']['logs']            = "Here you can view and delete previously sent newsletters";
$lang['Click_return_logs']          = "Click %shere%s to return to the newsletters' list";
$lang['Log_subject']                = "Newsletter subject";
$lang['Log_date']                   = "Date sent";
$lang['Log_numdest']                = "Number of recipients";
$lang['Delete_logs']                = "Are you sure you want to delete selected newsletters?";
$lang['Delete_log']                 = "Are you sure you want to delete this newsletter?";
$lang['No_log_sended']              = "No newsletter was sent to this list";
$lang['Joined_files']               = "This newsletter has %d attached files";
$lang['Joined_file']                = "This newsletter has an attached file";
$lang['Export_nl']                  = "Export this newsletter";


//
// Page : Gestion des abonnés
//
$lang['Explain']['abo']             = "Here you can view, modify and delete the account of people who have subscribed to your mailing lists";
$lang['Click_return_abo']           = "Click %shere%s to return to the subscribers' list";
$lang['Click_return_abo_profile']   = "Click %shere%s to return to the profile of the subscriber";
$lang['Delete_abo']                 = "Are you sure you want to delete selected subscribers?";
$lang['No_abo_in_list']             = "This list does not have any subscriber yet";
$lang['Susbcribed_date']            = "Subscription date";
$lang['Search_abo']                 = "Search with keywords";
$lang['Search_abo_note']            = "(you can use * as wildcard)";
$lang['Days_interval']              = "Subscribed in the %d last days";
$lang['All_abo']                    = "All subscribers";
$lang['Inactive_account']           = "Inactive accounts";
$lang['No_search_result']           = "The search produced no result";
$lang['Abo_pseudo']                 = "Subscriber's username";
$lang['Liste_to_register']          = "This subscriber is registered in the following lists";
$lang['Fast_deletion']              = "Fast deletion";
$lang['Fast_deletion_note']         = "Enter one or several addresses emails, separated by a comma, and they will be deleted from the mailing list";
$lang['Choice_Format']              = "Chosen format";
$lang['Warning_email_diff']         = "Be careful, you are going to modify the email address of this subscriber\nDo you wish to continue?";
$lang['Goto_list']                  = "Return at the subscribers list";
$lang['View_account']               = "View this account";
$lang['Edit_account']               = "Modify this account";
$lang['TagsList']                   = "List of tags";
$lang['TagsEdit']                   = "Edit tags";


//
// Page : Outils du script
//
$lang['Explain']['tools']           = "There are many tools available for you to manage your mailing lists";
$lang['Explain']['export']          = "Here you can export a list's email addresses, in a specified format (not taken into account if the list is not multi-format).\nIf you do not specify any separation character, the file will contain one email address per line.";
$lang['Explain']['import']          = "If you want to add multiple email addresses, put one address per line, or separate each address by a character (like ;) and enter it in the proper field.\nIf authorized by your server, you can also upload a file containing the emails' list, and specify a separation character here as well (unless the file contains one email per line). Alternatively, you can also specify the path to a file previously uploaded via FTP (relative path from the script's root).\nIf the file is compressed in a format supported by the server, it will automatically be de compressed.\n(a limit of %s emails has been set; See the %sscript FAQ%s for more details)";
$lang['Explain']['ban']             = "You can ban a complete email address, type user@domain.com, or a part of the address using * as a wildcard\n\n <u>Examples</u> :\n <ul><li> toto@titi.com, the user having toto@titi.com as an email address cannot subscribe</li><li> *.fr.st; All emails with .fr.st as extension cannot subscribe</li><li> *@domaine.net, all emails with @domain.net as extension cannot subscribe</li><li> saddam@*, all emails with saddam@ as prefix cannot subscribe</li></ul>";
$lang['Explain']['unban']           = "To un-ban an email address, or part of an address, use the proper keyboard/mouse combination on your computer and browser";
$lang['Explain']['forbid_ext']      = "To prohibit multiple file extensions at the same time, separate them with a comma";
$lang['Explain']['reallow_ext']     = "To reauthorize one or more extensions, use the proper keyboard/mouse combination on your computer and browser";
$lang['Explain']['backup']          = "This module will allow you to back up the script's tables, or other specified tables, if any.\nYou can decide whether you want to back up everything, or only the structure and data, and have the file compressed (following available options and libraries installed on the server).\nFinally, you can either download the file, or have the script store it on the server, in which case the file will be saved in the script's temporary files folder.\n\n<strong>Attention :</strong> This tool is only suitable for tables with simple structures. If your tables use foreign keys or store binary data, you have to use more specialized tools to obtain an usable export";
$lang['Explain']['restore']         = "This module will allow you to restore the script's tables previously backed up by Wanewsletter, or with any database manager.\nIf file upload is not authorized on your server, you can specify a file previously uploaded with ftp by indicating its path (relative to the script's root)";
$lang['Explain']['generator']       = "Here you must enter the absolute address where the form's data will be transmitted (usually the address where the form itself is located)";
$lang['Explain']['code_html']       = "Put this code at the address that you have/will indicate in the mailing list configuration";
$lang['Explain']['code_php']        = "You must put this code at the form's destination address (proviously input). The file must have the .php extension!\nThe script sets the absolute path in the WA_ROOTDIR constant. However, if the value is wrong, you have to set the right path yourself.";

$lang['Select_tool']                = "Please select the tool you want to use";
$lang['Export_format']              = "Export format";
$lang['Plain_text']                 = "plain text";
$lang['Char_glue']                  = "Separation character";
$lang['Compress']                   = "Compression";
$lang['Format_to_export']           = "Export subscribers with format";
$lang['Format_to_import']           = "Subscribers' format";
$lang['File_upload_restore']        = "Please indicate the backup file access path";
$lang['File_upload']                = "<i>or</i>, you can specify a text file";
$lang['File_local']                 = "<i>or</i>, you can specify a local file";
$lang['No_email_banned']            = "No banned email";
$lang['Ban_email']                  = "Email or part of email to ban";
$lang['Unban_email']                = "Email or part of email to un-ban";
$lang['No_forbidden_ext']           = "No prohibited extension";
$lang['Forbid_ext']                 = "Prohibit an extension";
$lang['Reallow_ext']                = "Extension(s) to re-authorize";
$lang['Backup_type']                = "Type of backup";
$lang['Backup_full']                = "Complete";
$lang['Backup_structure']           = "Structure only";
$lang['Backup_data']                = "Data only";
$lang['Drop_option']                = "Add DROP TABLE statements";
$lang['File_action']                = "What do you want to do with the file";
$lang['Download_action']            = "Back it up";
$lang['Store_action']               = "Store it on the server";
$lang['Additionnal_tables']         = "Extra tables to back up";
$lang['Target_form']                = "Form reception URL";

$lang['Check_update']               = "Check for updates";
$lang['Used_version']               = "You use <strong>Wanewsletter %s</strong>";
$lang['New_version_available']      = "A new version is available";
$lang['Download_page']              = "download page";
$lang['Version_up_to_date']         = "Your version is up to date";
$lang['Site_unreachable']           = "The website phpcodeur.net is currently unreachable";


//
// Page : Envoi des newsletters
//
$lang['Explain']['send']            = "The sending form allows you to write, send, save or delete newsletters, and attach files.\nIf you are using the second send engine, you can insert <code>{NAME}</code> in the text, as you do with <code>{LINKS}</code>, to display the subscriber's name if available.\nYou can also use include tags to add external contents. %sPlease read FAQ%s for more details.\n\nIf you create a re-usable template, and send a newsletter without having previously saved the template, it will be saved and a copy will be created for archives. If you have previously created a template, you can load it, edit it and save the changes. However, if you do this and change the status of the newsletter, a copy will be created and changes will be saved on the newsletter, not on the template.";
$lang['Explain']['join']            = "Here you can attach files to your newsletter (be carefull not to get your newsletter to big)\nIf file upload is not allowed on your server, you can indicate a distant file (e.g&thinsp;: <samp>http://www.domaine.com/rep/picture.gif</samp>) or a file manually uploaded into the dir of joined files\nYou can also use one the files attached to another newsletter of the same mailing list";
$lang['Explain']['text']            = "Compose your newsletter in text format here. Do not forget to insert the unsubscribe link, either clicking on the dedicated button if it is available, or by adding manually the tag <code>{LINKS}</code> in your newsletter";
$lang['Explain']['html']            = "Compose your newsletter in html format here. Do not forget to insert the unsubscribe link, either clicking on the dedicated button if it is available, or by adding manually the tag <code>{LINKS}</code> in your newsletter (the link will be in html format)\nIf you want to use one of the attached files (image, sound...) inside the newsletter, insert cid:file_name instead of the file's address\n\n<em>Example :</em>\n\nYou have uploaded an image named image1.gif and want to use it inside an image tag in your html newsletter. Put the img tag with src attribute : cid:image1.gif ( <code>&lt;img src=\"cid:image1.gif\" alt=\"Alternative text\" /&gt;</code> )";
$lang['Explain']['load']            = "You can specify external models and the script will load them for you. HTTP urls are allowed, along local path such as:
<ul>
<li>/path/to/document &ndash; absolute path</li>
<li>~/path/to/document &ndash; the tilde (~) is shorthand for the document_root of your web space</li>
<li>path/to/document &ndash; is relative to the installation directory of Wanewsletter</li>
</ul>";

$lang['Select_log_to_load']         = "Select a newsletter to load";
$lang['Load_by_URL']                = "Load a newsletter from an URL";
$lang['From_an_URL']                = "from an URL";
$lang['Create_log']                 = "Create a newsletter";
$lang['Load_log']                   = "Load newsletter";
$lang['List_send']                  = "Standby sendings";
$lang['Restart_send']               = "Resume this sending";
$lang['Cancel_send']                = "Cancel this sending";
$lang['Model']                      = "Template";
$lang['Dest']                       = "Recipient";
$lang['Log_in_text']                = "Text format newsletter";
$lang['Log_in_html']                = "Html format newsletter";
$lang['Format_text']                = "Text format";
$lang['Format_html']                = "HTML format";
$lang['Last_modified']              = "Last modified : %s";
$lang['Total_log_size']             = "Approx. newsletter size";
$lang['Join_file_to_log']           = "File to attach to this newsletter";
$lang['Subject_empty']              = "You must indicate a subject";
$lang['Body_empty']                 = "You must fill in the text field(s)";
$lang['No_links_in_body']           = "You must insert a unsubscribe link";
$lang['Cid_error_in_body']          = "Some files targeted in your <abbr>HTML</abbr> with the scheme <samp>cid:</samp> are missing (%s)";
$lang['Status']                     = "Status";
$lang['Done']                       = "Done";
$lang['Status_writing']             = "Normal newsletter";
$lang['Status_model']               = "Reusable template";
$lang['File_on_server']             = "existing file";
$lang['Cancel_send_log']            = "Are you sure you want to cancel this sending? (Only effective for the remaining sendings)";
$lang['Receive_copy']               = "Receive a copy";
$lang['Receive_copy_title']         = "If actived, you will receive a copy of the newsletter which has been sent";
$lang['Test_send_finish']           = "Test performed. Check your mailboxes.";
$lang['Test_send']                  = "Do a test mailing";
$lang['Test_send_note']             = "You can do a test mailing to check the display of your newsletter in real conditions. Enter one or more email address separated by a comma and valid";


//
// Page : Statistiques
//
$lang['Explain']['stats']           = "This page allows you to view a bar graph representing the number of subscriptions per day, for a given month and year, along with a second graph representing the dividing up of subscribers by mailing list.\nIf the GD library is not installed on your server, you should deactivate the module in the script's configuration";
$lang['Num_abo_per_liste']          = "Dividing up of subscribers by mailing list";
$lang['Subscribe_per_day']          = "Subscriptions/Days";
$lang['Graph_bar_title']            = "The number of inscriptions per day for the month given";
$lang['Camembert_title']            = "Shares of the various lists compared to the total number of subscribers";
$lang['Stats_dir_not_writable']     = "The <samp>stats/</samp> directory doesn't seems to be writable!";
$lang['Prev_month']                 = "Previous month";
$lang['Next_month']                 = "Next month";


//
// Installation du script
//
$lang['Welcome_in_install']         = "Welcome to Wanewsletter's install script. \nThis script requires <strong>PHP %s</strong> or higher.\nBefore continuing the installation, please read the %sreadme%s file, it contains important instructions to make the installation work properly.\nPlease read the %slicense agreement of Wanewsletter%s before continuing. A copy of this license is readable at %sphpcodeur.net/wascripts/gpl%s";
$lang['Welcome_in_upgrade']         = "Welcome to Wanewsletter's upgrade script to version <strong>%s</strong>.\n For security reasons, it is <strong>strongly advisable</strong> to make a backup of the script's tables before proceeding with the upgrade.";
$lang['Warning_reinstall']          = "<b>Caution!</b> Wanewsletter seems to be already installed. \nIf you wish to reinstall the script, enter your admin login and password. \nAttention, all of your data will be lost if you make a reinstallation of the script. \n if you wish to do an upgrade, use the script upgrade.php";
$lang['Start_install']              = "Launch install";
$lang['Start_upgrade']              = "Launch upgrade";
$lang['Result_install']             = "Install result";
$lang['Result_upgrade']             = "upgrade result";
$lang['PHP_version_error']          = "PHP version is <strong>%s</strong>, however, Wanewsletter requires <strong>PHP %s or higher</strong>";
$lang['Not_installed']              = "No version of Wanewsletter seems to be present,  the configuration file is empty or is not on the server";
$lang['mssql_support_end']          = "Sorry but the support for SQL Server has been removed in Wanewsletter 2.3";
$lang['No_db_support']              = "Sorry but Wanewsletter %s requires a database of type %s";
$lang['Connect_db_error']           = "Unable to connect to database (%s)";
$lang['sqldir_perms_problem']       = "To use Wanewsletter with a SQLite database, you have to give the right permissions (read and write) to the includes/sql/ directory";
$lang['DB_type_undefined']          = "The database type isn't defined!";

$lang['Success_install']            = "The installation was succesfully completed.\nYou can now go to the script %sadministration%s";
$lang['Success_upgrade']            = "The upgrade was succesfully completed.\nYou can now go to the script %sadministration%s";
$lang['Success_without_config']     = "The installation was succesfully completed, but the configuration file could not be created.\nYou can dowload the file and upload it in the includes/ directory with your ftp client.";
$lang['Error_in_install']           = "Errors occurred during the installation.\n\nError is : %s\nQuery is : %s";
$lang['Error_in_upgrade']           = "Errors occurred during the upgrade.\n\nError is : %s\nQuery is : %s";
$lang['Upgrade_not_required']       = "No upgrade is required for your current version of Wanewsletter";
$lang['Unsupported_version']        = "This version of Wanewsletter no longer supported by the upgrade script. You should first upgrade your installation to version 2.3.x.";

$lang['dbtype']                     = "Database Type";
$lang['dbhost']                     = "Database Server Hostname";
$lang['dbname']                     = "Database Name";
$lang['dbuser']                     = "Database Username";
$lang['dbpwd']                      = "Database Password";
$lang['prefixe']                    = "Prefix for tables";


//
// Conversions des formats de date
//
$datetime['Monday']     = "Monday";
$datetime['Tuesday']    = "Tuesday";
$datetime['Wednesday']  = "Wednesday";
$datetime['Thursday']   = "Thursday";
$datetime['Friday']     = "Friday";
$datetime['Saturday']   = "Saturday";
$datetime['Sunday']     = "Sunday";
$datetime['Mon']        = "Mon";
$datetime['Tue']        = "Tue";
$datetime['Wed']        = "Wed";
$datetime['Thu']        = "Thu";
$datetime['Fri']        = "Fri";
$datetime['Sat']        = "Sat";
$datetime['Sun']        = "Sun";

$datetime['January']    = "January";
$datetime['February']   = "February";
$datetime['March']      = "March";
$datetime['April']      = "April";
$datetime['May']        = "May";
$datetime['June']       = "June";
$datetime['July']       = "July";
$datetime['August']     = "August";
$datetime['September']  = "September";
$datetime['October']    = "October";
$datetime['November']   = "November";
$datetime['December']   = "December";
$datetime['Jan']        = "Jan";
$datetime['Feb']        = "Feb";
$datetime['Mar']        = "Mar";
$datetime['Apr']        = "Apr";
$datetime['May']        = "May";
$datetime['Jun']        = "Jun";
$datetime['Jul']        = "Jul";
$datetime['Aug']        = "Aug";
$datetime['Sep']        = "Sep";
$datetime['Oct']        = "Oct";
$datetime['Nov']        = "Nov";
$datetime['Dec']        = "Dec";


//
// Données diverses sur la langue
//
$lang['CHARSET']        = 'ISO-8859-1';
$lang['CONTENT_LANG']   = 'en';
$lang['CONTENT_DIR']    = 'ltr'; // sens du texte Left To Right ou Right To Left
$lang['TRANSLATE']      = '<a href="mailto:robert.leroux@percuweb.ca">Rleroux</a>';


// Formatage de nombres
$lang['DEC_POINT']      = ".";
$lang['THOUSANDS_SEP']  = ",";

?>