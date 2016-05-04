<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

use Patchwork\Utf8 as u;

const IN_ADMIN = true;

require '../includes/common.inc.php';

$files = <<<EOD
.gitignore
COPYING
CREDITS
README
admin/admin.php
admin/config.php
admin/envoi.php
admin/index.php
admin/login.php
admin/show.php
admin/start.inc.php
admin/stats.php
admin/tools.php
admin/upgrade.php
admin/view.php
composer.json
composer.lock
contrib/cleaner.php
contrib/convertdb.php
contrib/diff_lang.php
contrib/index.html
contrib/testlock.php
contrib/wanewsletter
contrib/wanewsletter.bat
data/.htaccess
data/config.sample.inc.php
data/tags.sample.inc.php
data/db/.gitignore
data/db/index.html
data/logs/.gitignore
data/logs/index.html
data/stats/.gitignore
data/stats/index.html
data/tmp/.gitignore
data/tmp/index.html
data/uploads/.gitignore
data/uploads/index.html
docs/index.html
docs/wadoc.css
images/barre.gif
images/barre.png
images/button-wa.gif
images/button-wa.png
images/index.html
images/logo-wa.gif
images/logo-wa.png
images/shadow.png
includes/Attach.php
includes/Auth.php
includes/Error.php
includes/Exception.php
includes/Subscription.php
includes/PopClient.php
includes/Sender.php
includes/Session.php
includes/Template.php
includes/common.inc.php
includes/compat.inc.php
includes/constantes.php
includes/functions.db.php
includes/functions.php
includes/functions.stats.php
includes/functions.wrapper.php
includes/index.html
includes/install.inc.php
includes/login.inc.php
includes/Dblayer/index.html
includes/Dblayer/Mysql.php
includes/Dblayer/Mysqli.php
includes/Dblayer/Postgres.php
includes/Dblayer/schemas/data.sql
includes/Dblayer/schemas/index.html
includes/Dblayer/schemas/mysql_tables.sql
includes/Dblayer/schemas/postgres_tables.sql
includes/Dblayer/schemas/sqlite_tables.sql
includes/Dblayer/Sqlite3.php
includes/Dblayer/SqlitePdo.php
includes/Dblayer/Wadb.php
includes/Output/CommandLine.php
includes/Output/Html.php
includes/Output/Json.php
includes/Output/MessageInterface.php
index.html
install.php
languages/DejaVuSans.ttf
languages/en/emails/admin_new_subscribe.txt
languages/en/emails/admin_unsubscribe.txt
languages/en/emails/index.html
languages/en/emails/new_admin.txt
languages/en/emails/reset_passwd.txt
languages/en/emails/unsubscribe_cron.txt
languages/en/emails/unsubscribe_form.txt
languages/en/emails/welcome_cron1.txt
languages/en/emails/welcome_cron2.txt
languages/en/emails/welcome_form1.txt
languages/en/emails/welcome_form2.txt
languages/fr/emails/admin_new_subscribe.txt
languages/fr/emails/admin_unsubscribe.txt
languages/fr/emails/index.html
languages/fr/emails/new_admin.txt
languages/fr/emails/reset_passwd.txt
languages/fr/emails/unsubscribe_cron.txt
languages/fr/emails/unsubscribe_form.txt
languages/fr/emails/welcome_cron1.txt
languages/fr/emails/welcome_cron2.txt
languages/fr/emails/welcome_form1.txt
languages/fr/emails/welcome_form2.txt
languages/index.html
languages/en/index.html
languages/en/main.php
languages/fr/index.html
languages/fr/main.php
languages/fr/tinymce.js
newsletter.php
options/cron.php
options/extra.php
options/index.html
profil_cp.php
subscribe.php
templates/admin/add_admin_body.tpl
templates/admin/admin.js
templates/admin/admin_body.tpl
templates/admin/backup_body.tpl
templates/admin/ban_list_body.tpl
templates/admin/config_body.tpl
templates/admin/confirm_body.tpl
templates/admin/edit_abo_profil_body.tpl
templates/admin/edit_liste_body.tpl
templates/admin/editor.js
templates/admin/export_body.tpl
templates/admin/files_box.tpl
templates/admin/footer.tpl
templates/admin/forbidden_ext_body.tpl
templates/admin/generator_body.tpl
templates/admin/header.tpl
templates/admin/iframe_body.tpl
templates/admin/import_body.tpl
templates/admin/index.html
templates/admin/index_body.tpl
templates/admin/list_box.tpl
templates/admin/message_body.tpl
templates/admin/restore_body.tpl
templates/admin/result_generator_body.tpl
templates/admin/result_upgrade_body.tpl
templates/admin/select_liste_body.tpl
templates/admin/select_log_body.tpl
templates/admin/send_body.tpl
templates/admin/send_progress_body.tpl
templates/admin/sending_body.tpl
templates/admin/simple_header.tpl
templates/admin/stats_body.tpl
templates/admin/tools_body.tpl
templates/admin/upgrade_body.tpl
templates/admin/view_abo_list_body.tpl
templates/admin/view_abo_profil_body.tpl
templates/admin/view_liste_body.tpl
templates/admin/view_logs_body.tpl
templates/archives_body.tpl
templates/editprofile_body.tpl
templates/footer.tpl
templates/header.tpl
templates/images/archive-hover.png
templates/images/archive.png
templates/images/icon_clip.png
templates/images/icon_loupe.png
templates/images/index.html
templates/images/loading.gif
templates/images/puce.png
templates/images/icon_reset.png
templates/index.html
templates/index_body.tpl
templates/install.tpl
templates/login.tpl
templates/lost_passwd.tpl
templates/message_body.tpl
templates/reset_passwd.tpl
templates/simple_header.tpl
templates/subscribe_body.tpl
templates/wanewsletter.css
templates/wanewsletter.custom.css

# Pour les anciennes versions
includes/config.inc.php
# Dossiers légitimes à ignorer
.git
data
docs
vendor
# Anciens dossiers des versions < 3.0
upload
stats
tmp
EOD;

/**
 * Scanne les dossiers du répertoire de Wanewsletter à la recherche de fichiers
 * inconnus ou de fichiers d’anciennes versions et désormais obsolètes.
 * Fonction récursive.
 *
 * @param string $dir
 *
 * @return string
 */
function scan_dir($dir)
{
	global $files;

	$output = '';
	$browse = dir($dir);

	while (($entry = $browse->read()) !== false) {
		if ($entry == '..' || $entry == '.') {
			continue;
		}

		$filename = $dir.'/'.$entry;
		$relname  = ltrim(str_replace(WA_ROOTDIR, '', $filename), '/');

		$i = array_search($relname, $files);

		if ($i !== false) {
			continue;
		}
		else if (is_dir($filename)) {
			$output .= scan_dir($filename);
		}
		else {
			$output .= "$relname\n";
		}
	}
	$browse->close();

	return $output;
}

/**
 * SQLite a un support très limité de la commande ALTER TABLE
 * Impossible de modifier ou supprimer une colonne donnée
 * On réécrit les tables dont la structure a changé
 * /!\ Ne permet que d'altérer le type des colonnes.
 * Ajouter/Renommer/Supprimer des colonnes ne fonctionnera pas avec $restore_data à true
 *
 * @param string  $tablename    Nom de la table à recréer
 * @param boolean $restore_data true pour restaurer les données
 */
function wa_sqlite_recreate_table($tablename, $restore_data = true)
{
	global $db, $sql_create, $sql_tables_recreated;

	// Table déjà recréée ?
	if ($sql_tables_recreated[$tablename]) {
		return null;
	}

	$sql_tables_recreated[$tablename] = true;

	$get_columns = function ($tablename) use ($db) {
		$result = $db->query(sprintf("PRAGMA table_info(%s)", $db->quote($tablename)));
		$columns = [];
		while ($row = $result->fetch()) {
			$columns[] = $row['name'];
		}

		return $columns;
	};

	$db->query(sprintf('ALTER TABLE %1$s RENAME TO %2$s;',
		$db->quote($tablename),
		$db->quote($tablename.'_tmp')
	));

	exec_queries($sql_create[$tablename]);

	if ($restore_data) {
		$old_columns = $get_columns($tablename.'_tmp');
		$new_columns = $get_columns($tablename);
		$columns = array_intersect($new_columns, $old_columns);

		$db->query(sprintf('INSERT INTO %1$s (%3$s) SELECT %3$s FROM %2$s;',
			$db->quote($tablename),
			$db->quote($tablename.'_tmp'),
			implode(',', $columns)
		));
	}

	$db->query(sprintf('DROP TABLE %s;', $db->quote($tablename.'_tmp')));
}

//
// Initialisation de la connexion à la base de données et récupération de la configuration
//
$db = WaDatabase($dsn);
$nl_config = wa_get_config();

//
// Compatibilité avec Wanewsletter < 2.4-beta2
//
if (!isset($nl_config['db_version'])) {
	// Versions <= 2.2.13
	if (!defined('WA_VERSION')) {
		$currentVersion = strtolower($nl_config['version']);
	}
	// Versions <= 2.4-beta1
	else {
		$currentVersion = WA_VERSION;
	}

	// Les versions des branches 2.0 et 2.1 ne sont plus prises en charge
	if (!version_compare($currentVersion, '2.2.0', '>=' )) {
		$output->message($lang['Unsupported_version']);
	}

	//
	// On sélectionne manuellement db_version en fonction de chaque
	// version ayant apporté des changements dans les tables de données
	// du script.
	// Le numérotage de db_version prenait en compte des versions
	// antérieures à 2.2.0 qui apportaient des modifications, d'où
	// le db_version commençant à 5.
	//
	$nl_config['db_version'] = 5;

	foreach (['2.2.12','2.2.13'] as $version) {
		if (version_compare($currentVersion, $version, '>')) {
			$nl_config['db_version']++;
		}
	}
}

$auth = new Auth();
// Le système de sessions a été réécrit dans la version 19 des tables.
$session = null;
if ($nl_config['db_version'] > 18) {
	$session = new Session($nl_config);
	$admindata = $auth->getUserData($_SESSION['uid']);
}

//
// Ne concerne pas directement le système de mises à jour.
// L’URL ~/admin/upgrade.php?mode=check est appelée à partir de
// l’accueil, soit en AJAX quand c’est possible, soit directement.
//
if (filter_input(INPUT_GET, 'mode') == 'check') {
	if (!$auth->isLoggedIn() || is_null($session) || !Auth::isAdmin($admindata)) {
		// Utilisateur non authentifié ou n'ayant pas le niveau d’administrateur
		if ($output instanceof Output\Html) {
			http_response_code(401);
			$output->redirect('./index.php', 5);
			$output->addLine($lang['Message']['Not_authorized']);
			$output->addLine($lang['Click_return_index'], './index.php');
			$output->message();
		}
		else {
			$output->error('Not_authorized');
		}

		exit;
	}

	try {
		$result = wa_check_update(true);
	}
	catch (Exception $e) {
		$output->error($e->getMessage());
	}

	if ($result == 1) {
		$message = $lang['New_version_available'];
	}
	else {
		$message = $lang['Version_up_to_date'];
	}

	if ($output instanceof Output\Html && $result == 1) {
		$message .= '<br /><br />';
		$message .= sprintf('<a href="%s">%s</a>', DOWNLOAD_PAGE, $lang['Download_page']);
	}

	if ($output instanceof Output\Json) {
		$output->addParams(['code' => $result]);
	}

	$output->message($message);
}

//
// Envoi du fichier au client si demandé
//
$config_file  = '<' . "?php\n";
$config_file .= "//\n";
$config_file .= "// Paramètres d'accès à la base de données\n";
$config_file .= "//\n";
$config_file .= "\$dsn = '$dsn';\n";
$config_file .= "\$prefix = '".$nl_config['db']['prefix']."';\n";
$config_file .= "\n";

if ($auth->isLoggedIn() && Auth::isAdmin($admindata) && isset($_POST['sendfile'])) {
	sendfile('config.inc.php', 'text/plain', $config_file);
}

// Préparation du listing et scan du répertoire
$files = explode("\n", $files);
foreach ($files as &$file) {
	$file = trim($file);
	if (!$file || $file[0] == '#') {
		$file = null;
	}
}

$files = array_filter($files);
$unknown_files = scan_dir(WA_ROOTDIR);
unset($files);

if (check_db_version($nl_config['db_version'])) {
	$message = $lang['Upgrade_not_required'];

	if ($unknown_files) {
		$output->header();

		$template = new Template('result_upgrade_body.tpl');

		$template->assign([
			'L_TITLE_UPGRADE' => $lang['Title']['upgrade'],
			'MESSAGE' => nl2br($message)
		]);

		$template->assignToBlock('unknown_files', [
			'NOTICE'  => nl2br($lang['Unknown_files_notice']),
			'LISTING' => $unknown_files
		]);

		$template->pparse();
		$output->footer();
	}
	else {
		$output->message($message);
	}
}

if (isset($_POST['start'])) {
	$schemas_dir = WA_ROOTDIR . '/includes/Dblayer/schemas';
	$sql_create  = sprintf('%s/%s_tables.sql', $schemas_dir, $db::ENGINE);
	$sql_data    = sprintf('%s/data.sql', $schemas_dir);
	$error = false;

	if (!is_readable($sql_create) || !is_readable($sql_data)) {
		$error = true;
		$output->warn('sql_file_not_readable');
	}

	if (!$auth->isLoggedIn()) {
		$login  = trim(u::filter_input(INPUT_POST, 'login'));
		$passwd = trim(u::filter_input(INPUT_POST, 'passwd'));
		$admindata = $auth->checkCredentials($login, $passwd);

		if (!$admindata) {
			$error = true;
			$output->warn('Error_login');
		}
	}

	if (!$error && !Auth::isAdmin($admindata)) {
		http_response_code(401);
		$output->redirect('./index.php', 6);
		$output->addLine($lang['Message']['Not_authorized']);
		$output->addLine($lang['Click_return_index'], './index.php');
		$output->message();
	}

	if (!$error) {
		load_settings($admindata);

		//
		// Lancement de la mise à jour
		// On allonge le temps maximum d'execution du script.
		//
		@set_time_limit(3600);

		$sql_create = parse_sql(file_get_contents($sql_create), $nl_config['db']['prefix']);
		$sql_data   = parse_sql(file_get_contents($sql_data), $nl_config['db']['prefix']);

		$sql_create_by_table = $sql_data_by_table = [];

		foreach ($sql_create as $query) {
			if (preg_match("/^CREATE\s+TABLE\s+([A-Za-z0-9_]+)\s+/", $query, $m)) {
				$sql_create_by_table[$m[1]][] = $query;
			}
			else if (preg_match("/^CREATE\s+INDEX\s+(?:[A-Za-z0-9_]+)\s+ON\s+([A-Za-z0-9_]+)/", $query, $m)) {
				$sql_create_by_table[$m[1]][] = $query;
			}
		}

		foreach ($sql_data as $query) {
			if (preg_match('/^INSERT\s+INTO\s+([A-Za-z0-9_]+)/', $query, $m)) {
				$sql_data_by_table[$m[1]][] = $query;
			}
		}

		$sql_create = $sql_create_by_table;
		$sql_data   = $sql_data_by_table;

		// Nécessaire pour marquer les tables recréées.
		$sql_tables_recreated = array_fill_keys(get_db_tables(), false);

		//
		// Sur les versions antérieures à la 2.3.0, il n’y avait pas de
		// contrainte d’unicité sur abo_email. Avant de commencer la mise
		// à jour, il faut vérifier l’absence de doublons dans la table
		// des abonnés.
		//
		if ($nl_config['db_version'] < 7) {
			$sql = "SELECT abo_email
				FROM " . ABONNES_TABLE . "
				GROUP BY abo_email
				HAVING COUNT(abo_email) > 1";
			$result = $db->query($sql);

			if ($row = $result->fetch()) {
				$emails = [];

				do {
					$emails[] = $row['abo_email'];
				}
				while ($row = $result->fetch());

				$output->message(sprintf("Des adresses email sont présentes en plusieurs
					exemplaires dans la table %s, la mise à jour ne peut donc continuer.
					Supprimez les doublons en cause puis relancez la mise à jour.
					Adresses email présentes en plusieurs exemplaires : %s",
					ABONNES_TABLE,
					implode(', ', $emails)
				));
			}
		}

		//
		// Début de la mise à jour
		//
		$sql_update = [];

		if ($nl_config['db_version'] < 6) {
			unset($nl_config['hebergeur']);
			unset($nl_config['version']);

			if ($db::ENGINE == 'postgres') {
				$sql_update[] = "DROP INDEX abo_status_wa_abonnes_index";
				$sql_update[] = "DROP INDEX admin_id_wa_auth_admin_index";
				$sql_update[] = "DROP INDEX liste_id_wa_log_index";
				$sql_update[] = "DROP INDEX log_status_wa_log_index";
				$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
					RENAME COLUMN email_new_inscrit email_new_subscribe";
				$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
					ADD COLUMN email_unsubscribe SMALLINT NOT NULL DEFAULT 0";
				$sql_update[] = "ALTER TABLE " . AUTH_ADMIN_TABLE . "
					ADD COLUMN cc_admin SMALLINT NOT NULL DEFAULT 0";
				$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
					ADD COLUMN liste_public SMALLINT NOT NULL DEFAULT 1";
			}
			else {
				$sql_update[] = "DROP INDEX abo_status ON " . ABONNES_TABLE;
				$sql_update[] = "DROP INDEX admin_id ON " . AUTH_ADMIN_TABLE;
				$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
					DROP INDEX liste_id,
					DROP INDEX log_status";
				$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
					CHANGE email_new_inscrit email_new_subscribe TINYINT(1) NOT NULL DEFAULT 0,
					ADD COLUMN email_unsubscribe TINYINT(1) NOT NULL DEFAULT 0";
				$sql_update[] = "ALTER TABLE " . AUTH_ADMIN_TABLE . "
					ADD COLUMN cc_admin TINYINT(1) NOT NULL DEFAULT 0";
				$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
					ADD COLUMN liste_public TINYINT(1) NOT NULL DEFAULT 1 AFTER liste_name";
			}

			$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . "
				ADD COLUMN register_key CHAR(20) DEFAULT NULL,
				ADD COLUMN register_date INTEGER NOT NULL DEFAULT 0,
				ADD COLUMN confirmed SMALLINT NOT NULL DEFAULT 0";

			exec_queries($sql_update);

			$sql = "SELECT abo_id, abo_register_key, abo_pwd, abo_register_date, abo_status
				FROM " . ABONNES_TABLE;
			$result = $db->query($sql);

			while ($row = $result->fetch()) {
				$sql = "UPDATE " . ABO_LISTE_TABLE . "
					SET register_date = $row[abo_register_date],
						confirmed     = $row[abo_status]";
				if ($row['abo_status'] == ABO_INACTIVE) {
					$sql .= ", register_key = '" . substr($row['abo_register_key'], 0, 20) . "'";
				}
				$db->query($sql . " WHERE abo_id = " . $row['abo_id']);

				if (empty($row['abo_pwd'])) {
					$db->query("UPDATE " . ABONNES_TABLE . "
						SET abo_pwd = '" . md5($row['abo_register_key']) . "'
						WHERE abo_id = $row[abo_id]");
				}
			}
			$result->free();

			$sql = "SELECT abo_id, liste_id
				FROM " . ABO_LISTE_TABLE . "
				WHERE register_key IS NULL";
			$result = $db->query($sql);

			while ($row = $result->fetch()) {
				$sql = "UPDATE " . ABO_LISTE_TABLE . "
					SET register_key = '" . generate_key(20, false) . "'
					WHERE liste_id = $row[liste_id]
						AND abo_id = " . $row['abo_id'];
				$db->query($sql);
			}
			$result->free();

			$sql_update = [];
			$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
				DROP COLUMN abo_register_key,
				DROP COLUMN abo_register_date";

			if ($db::ENGINE == 'postgres') {
				$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . "
					ADD CONSTRAINT register_key_idx UNIQUE (register_key)";
				$sql_update[] = "CREATE INDEX abo_status_idx ON " . ABONNES_TABLE . " (abo_status)";
				$sql_update[] = "CREATE INDEX admin_id_idx ON " . AUTH_ADMIN_TABLE . " (admin_id)";
				$sql_update[] = "CREATE INDEX liste_id_idx ON " . LOG_TABLE . " (liste_id)";
				$sql_update[] = "CREATE INDEX log_status_idx ON " . LOG_TABLE . " (log_status)";
			}
			else {
				$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . "
					ADD UNIQUE register_key_idx (register_key)";
				$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
					ADD INDEX abo_status_idx (abo_status)";
				$sql_update[] = "ALTER TABLE " . AUTH_ADMIN_TABLE . "
					ADD INDEX admin_id_idx (admin_id)";
				$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
					ADD INDEX liste_id_idx (liste_id),
					ADD INDEX log_status_idx (log_status)";
			}
		}

		//
		// Ajout contrainte d’unicité sur abo_email
		//
		if ($nl_config['db_version'] < 7) {
			$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
				ADD CONSTRAINT abo_email_idx UNIQUE (abo_email)";

			exec_queries($sql_update);
		}

		//
		// Passage de toutes les colonnes stockant une adresse email en VARCHAR(254)
		// - On uniformise les tailles de colonne pour ce type de données
		// - le protocole SMTP nécessite une longueur max de 254 octets des adresses email
		// - Nouveau format de table de configuration
		//
		if ($nl_config['db_version'] < 8) {
			if ($db::ENGINE == 'postgres') {
				$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
					ALTER COLUMN abo_email TYPE VARCHAR(254)";
				$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
					ALTER COLUMN admin_email TYPE VARCHAR(254)";
				$sql_update[] = "ALTER TABLE " . BAN_LIST_TABLE . "
					ALTER COLUMN ban_email TYPE VARCHAR(254)";
				$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
					ALTER COLUMN sender_email TYPE VARCHAR(254),
					ALTER COLUMN return_email TYPE VARCHAR(254),
					ALTER COLUMN liste_alias TYPE VARCHAR(254)";
			}
			else if ($db::ENGINE == 'sqlite') {
				foreach ([ABONNES_TABLE, ADMIN_TABLE, BAN_LIST_TABLE, LISTE_TABLE] as $tablename) {
					wa_sqlite_recreate_table($tablename);
				}
			}
			else {
				$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
					MODIFY COLUMN abo_email VARCHAR(254) NOT NULL DEFAULT ''";
				$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
					MODIFY COLUMN admin_email VARCHAR(254) NOT NULL DEFAULT ''";
				$sql_update[] = "ALTER TABLE " . BAN_LIST_TABLE . "
					MODIFY COLUMN ban_email VARCHAR(254) NOT NULL DEFAULT ''";
				$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
					MODIFY COLUMN sender_email VARCHAR(254) NOT NULL DEFAULT '',
					MODIFY COLUMN return_email VARCHAR(254) NOT NULL DEFAULT '',
					MODIFY COLUMN liste_alias VARCHAR(254) NOT NULL DEFAULT ''";
			}

			$sql_tables_recreated[CONFIG_TABLE] = true;
			$sql_update[] = "DROP TABLE " . CONFIG_TABLE;
			$sql_update   = array_merge($sql_update, $sql_create[CONFIG_TABLE]);
			$sql_update   = array_merge($sql_update, $sql_data[CONFIG_TABLE]);

			exec_queries($sql_update);

			//
			// On remet en place la configuration actuelle du script
			//
			$new_config = wa_get_config();
			wa_update_config(array_intersect_key($nl_config, $new_config));
			$ext_config = array_diff_key($nl_config, $new_config);
			$nl_config  = array_merge($new_config, $nl_config);

			foreach ($ext_config as $name => $value) {
				$db->query(sprintf(
					"INSERT INTO %s (config_name, config_value) VALUES('%s', '%s')",
					CONFIG_TABLE,
					$db->escape($name),
					$db->escape($value)
				));
			}
		}

		//
		// Passage des colonnes abo_pwd et admin_pwd en VARCHAR(255) pour pouvoir
		// stocker les hashages renvoyés par phpass
		//
		if ($nl_config['db_version'] < 9) {
			if ($db::ENGINE == 'postgres') {
				$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
					ALTER COLUMN abo_pwd TYPE VARCHAR(255)";
				$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
					ALTER COLUMN admin_pwd TYPE VARCHAR(255)";
			}
			else if ($db::ENGINE == 'sqlite') {
				foreach ([ABONNES_TABLE, ADMIN_TABLE] as $tablename) {
					wa_sqlite_recreate_table($tablename);
				}
			}
			else {
				$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
					MODIFY COLUMN abo_pwd VARCHAR(255) NOT NULL DEFAULT ''";
				$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
					MODIFY COLUMN admin_pwd VARCHAR(255) NOT NULL DEFAULT ''";
			}
		}

		//
		// Les champs TEXT sur MySQL ont un espace de stockage de 2^16 octets
		// soit environ 64 Kio. Ça pourrait être un peu léger dans des cas
		// d'utilisation extrème.
		// On les passe en MEDIUMTEXT.
		//
		if ($nl_config['db_version'] < 10) {
			if ($db::ENGINE == 'mysql') {
				$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
					MODIFY COLUMN log_body_html MEDIUMTEXT,
					MODIFY COLUMN log_body_text MEDIUMTEXT";
			}
		}

		//
		// Correction d'une horrible faute de conjuguaison sur le nom d'une
		// entrée de la configuration.
		//
		if ($nl_config['db_version'] < 11) {
			if (isset($nl_config['sending_limit'])) {// Table de configuration recréée dans l'update 8
				$sql_update[] = "UPDATE " . CONFIG_TABLE . "
					SET config_value = '$nl_config[emails_sended]'
					WHERE config_name = 'sending_limit'";
				$sql_update[] = "DELETE FROM " . CONFIG_TABLE . "
					WHERE config_name = 'emails_sended'";
			}
			else {
				$sql_update[] = "UPDATE " . CONFIG_TABLE . "
					SET config_name = 'sending_limit'
					WHERE config_name = 'emails_sended'";
			}
		}

		//
		// Les noms de listes de diffusion sont stockés avec des entités html
		// Suprème bétise (je sais pas ce qui m'a pris :S)
		//
		if ($nl_config['db_version'] < 12) {
			$result = $db->query("SELECT liste_id, liste_name FROM ".LISTE_TABLE);
			while ($row = $result->fetch()) {
				$sql_update[] = sprintf("UPDATE %s SET liste_name = '%s' WHERE liste_id = %d",
					LISTE_TABLE,
					$db->escape(htmlspecialchars_decode($row['liste_name'])),
					$row['liste_id']
				);
			}
		}

		//
		// Ajout du répertoire data/ centralisant les données "volatiles".
		// On ajoutera une note à ce propos dans le message de résultat de
		// la mise à jour.
		//
		$moved_dirs = false;

		if ($nl_config['db_version'] < 13) {
			// fake. Permet simplement de savoir que les répertoires stats, tmp, ...
			// ont changé de place et le notifier à l'administrateur
			$moved_dirs = !is_writable($nl_config['tmp_dir']);
		}

		//
		// Entrée de configuration 'gd_img_type' obsolète. On la supprime.
		//
		if ($nl_config['db_version'] < 14) {
			$sql_update[] = "DELETE FROM " . CONFIG_TABLE . "
				WHERE config_name = 'gd_img_type'";
		}

		//
		// Ajout de l'entrée de configuration 'debug_level', mais seulement
		// si table config n’a pas été entièrement réécrite dans dans le
		// bloc db_version < 8 plus haut.
		//
		if ($nl_config['db_version'] < 15 && !$sql_tables_recreated[CONFIG_TABLE]) {
			$sql_update[] = "INSERT INTO " . CONFIG_TABLE . " (config_name, config_value)
				VALUES('debug_level', '1')";
		}

		//
		// Corrections sur les séquences PostgreSQL créées manuellement et donc
		// non liées à leur table
		//
		if ($nl_config['db_version'] < 16 && $db::ENGINE == 'postgres') {
			// La séquence pour la table ban_list ne suit pas le nommage {tablename}_id_seq
			$sql_update[] = sprintf('ALTER SEQUENCE %1$sban_id_seq RENAME TO %2$s_id_seq',
				$nl_config['db']['prefix'],
				BAN_LIST_TABLE
			);

			$sql_update[] = sprintf('ALTER SEQUENCE %1$s_id_seq OWNED BY %1$s.abo_id', ABONNES_TABLE);
			$sql_update[] = sprintf('ALTER SEQUENCE %1$s_id_seq OWNED BY %1$s.admin_id', ADMIN_TABLE);
			$sql_update[] = sprintf('ALTER SEQUENCE %1$s_id_seq OWNED BY %1$s.ban_id', BAN_LIST_TABLE);
			$sql_update[] = sprintf('ALTER SEQUENCE %1$s_id_seq OWNED BY %1$s.config_id', CONFIG_TABLE);
			$sql_update[] = sprintf('ALTER SEQUENCE %1$s_id_seq OWNED BY %1$s.fe_id', FORBIDDEN_EXT_TABLE);
			$sql_update[] = sprintf('ALTER SEQUENCE %1$s_id_seq OWNED BY %1$s.file_id', JOINED_FILES_TABLE);
			$sql_update[] = sprintf('ALTER SEQUENCE %1$s_id_seq OWNED BY %1$s.liste_id', LISTE_TABLE);
			$sql_update[] = sprintf('ALTER SEQUENCE %1$s_id_seq OWNED BY %1$s.log_id', LOG_TABLE);
		}

		//
		// MySQL : Définition du jeu de caractères des tables et colonnes en
		// UTF-8 avec conversion automatique des données. Merci MySQL :D
		// SQLite : Conversion manuelle des données :(
		//
		if ($nl_config['db_version'] < 17) {
			if ($db::ENGINE == 'mysql') {
				foreach (get_db_tables() as $tablename) {
					$sql_update[] = sprintf("ALTER TABLE %s CONVERT TO CHARACTER SET utf8", $tablename);
				}
			}
			else if ($db::ENGINE == 'sqlite') {
				$db->createFunction('utf8_encode', ['\Patchwork\Utf8', 'utf8_encode']);

				$sql_update[] = "UPDATE " . ABONNES_TABLE . " SET abo_pseudo = utf8_encode(abo_pseudo)";
				$sql_update[] = "UPDATE " . ADMIN_TABLE . " SET admin_login = utf8_encode(admin_login)";
				$sql_update[] = "UPDATE " . CONFIG_TABLE . "
					SET config_name = utf8_encode(config_name),
					config_value = utf8_encode(config_value)";
				$sql_update[] = "UPDATE " . JOINED_FILES_TABLE . " SET file_real_name = utf8_encode(file_real_name)";
				$sql_update[] = "UPDATE " . LISTE_TABLE . "
					SET liste_name = utf8_encode(liste_name),
						form_url = utf8_encode(form_url),
						liste_sig = utf8_encode(liste_sig)";
				$sql_update[] = "UPDATE " . LOG_TABLE . "
					SET log_subject = utf8_encode(log_subject),
						log_body_text = utf8_encode(log_body_text),
						log_body_html = utf8_encode(log_body_html)";
			}
		}

		//
		// Avant le support UTF-8 dans Wanewsletter, PostgreSQL a considéré
		// toutes les chaînes qui lui étaient transmises comme du latin1, non
		// comme du windows-1252 comme le fait MySQL.
		// Les caractères spécifiques à windows-1252 sont donc incorrectement
		// codés en UTF-8 dans les tables, ce qui est devenu problématique
		// maintenant que Wanewsletter travaille directement en UTF-8
		//
		if ($nl_config['db_version'] < 18 && $db::ENGINE == 'postgres') {
			$res = $db->query(sprintf("SELECT pg_encoding_to_char(encoding) as db_encoding
				FROM pg_database WHERE datname = '%s'",
				$db->dbname
			));

			if ($res->column('db_encoding') == 'UTF8') {
				$sql_update[] = "UPDATE " . ABONNES_TABLE . "
					SET abo_pseudo = convert_from(convert(abo_pseudo::bytea, 'utf8', 'latin1'),'win1252')";
				$sql_update[] = "UPDATE " . ADMIN_TABLE . "
					SET admin_login = convert_from(convert(admin_login::bytea, 'utf8', 'latin1'),'win1252')";
				$sql_update[] = "UPDATE " . CONFIG_TABLE . "
					SET config_name = convert_from(convert(config_name::bytea, 'utf8', 'latin1'),'win1252'),
					config_value = convert_from(convert(config_value::bytea, 'utf8', 'latin1'),'win1252')";
				$sql_update[] = "UPDATE " . JOINED_FILES_TABLE . "
					SET file_real_name = convert_from(convert(file_real_name::bytea, 'utf8', 'latin1'),'win1252')";
				$sql_update[] = "UPDATE " . LISTE_TABLE . "
					SET liste_name = convert_from(convert(liste_name::bytea, 'utf8', 'latin1'),'win1252'),
						form_url = convert_from(convert(form_url::bytea, 'utf8', 'latin1'),'win1252'),
						liste_sig = convert_from(convert(liste_sig::bytea, 'utf8', 'latin1'),'win1252')";
				$sql_update[] = "UPDATE " . LOG_TABLE . "
					SET log_subject = convert_from(convert(log_subject::bytea, 'utf8', 'latin1'),'win1252'),
						log_body_text = convert_from(convert(log_body_text::bytea, 'utf8', 'latin1'),'win1252'),
						log_body_html = convert_from(convert(log_body_html::bytea, 'utf8', 'latin1'),'win1252')";
			}
		}

		//
		// Réorganisation de la table des sessions
		//
		if ($nl_config['db_version'] < 19) {
			if ($db::ENGINE != 'sqlite') {
				if ($db::ENGINE == 'mysql') {
					$sql_update[] = "ALTER TABLE " . SESSION_TABLE . "
						ENGINE = MyISAM";
					$sql_update[] = "ALTER TABLE " . SESSION_TABLE . "
						MODIFY COLUMN session_id VARCHAR(100)";
					$sql_update[] = "ALTER TABLE " . SESSION_TABLE . "
						CHANGE COLUMN session_time session_expire INTEGER";
					$sql_update[] = "ALTER TABLE " . SESSION_TABLE . "
						ADD COLUMN session_data MEDIUMTEXT";
				}
				else {
					$sql_update[] = "ALTER TABLE " . SESSION_TABLE . "
						ALTER COLUMN session_id TYPE VARCHAR(100)";
					$sql_update[] = "ALTER TABLE " . SESSION_TABLE . "
						RENAME COLUMN session_time TO session_expire";
					$sql_update[] = "ALTER TABLE " . SESSION_TABLE . "
						ADD COLUMN session_data TEXT";
				}

				$sql_update[] = "ALTER TABLE " . SESSION_TABLE . "
					DROP COLUMN admin_id";
				$sql_update[] = "ALTER TABLE " . SESSION_TABLE . "
					DROP COLUMN session_ip";
				$sql_update[] = "ALTER TABLE " . SESSION_TABLE . "
					DROP COLUMN session_liste";
			}
			else {
				wa_sqlite_recreate_table(SESSION_TABLE, false);
			}

			$sql_update[] = "DELETE FROM " . SESSION_TABLE;

			exec_queries($sql_update);
			$db->vacuum(SESSION_TABLE);
		}

		//
		// Support SSL/TLS pour les connexions aux serveurs SMTP et POP
		//
		if ($nl_config['db_version'] < 20) {
			// Seulement si la table config n'a pas été entièrement réécrite plus haut.
			if (!$sql_tables_recreated[CONFIG_TABLE]) {
				$sql_update[] = "INSERT INTO " . CONFIG_TABLE . " (config_name, config_value)
					VALUES('smtp_tls', '0')";
			}

			// Seulement si la table liste n'a pas été entièrement réécrite plus haut.
			if (!$sql_tables_recreated[LISTE_TABLE]) {
				switch ($db::ENGINE) {
					case 'mysql':
						$type = 'TINYINT(1)';
						break;
					case 'postgres':
						$type = 'SMALLINT';
						break;
					case 'sqlite':
						$type = 'INTEGER';
						break;
				}

				$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
					ADD COLUMN pop_tls $type NOT NULL DEFAULT 0";
			}
		}

		//
		// Entrée de configuration 'check_email_mx' obsolète. On la supprime.
		//
		if ($nl_config['db_version'] < 21) {
			$sql_update[] = "DELETE FROM " . CONFIG_TABLE . "
				WHERE config_name = 'check_email_mx'";
		}

		//
		// Suppression des options relatives au système de stockage des
		// pièces jointes sur FTP.
		//
		if ($nl_config['db_version'] < 22) {
			$sql_update[] = "DELETE FROM " . CONFIG_TABLE . "
				WHERE config_name IN('use_ftp','ftp_server','ftp_port',
					'ftp_user','ftp_pass','ftp_pasv','ftp_path')";
		}

		//
		// Stockage du code langue au lieu du nom complet.
		//
		if ($nl_config['db_version'] < 23) {
			$sql_update[] = "UPDATE " . CONFIG_TABLE . " SET config_value = 'en'
				WHERE config_name = 'language' AND config_value = 'english'";
			$sql_update[] = "UPDATE " . CONFIG_TABLE . " SET config_value = 'fr'
				WHERE config_name = 'language' AND config_value = 'francais'";
			$sql_update[] = "UPDATE " . ABONNES_TABLE . " SET abo_lang = 'en' WHERE abo_lang = 'english'";
			$sql_update[] = "UPDATE " . ABONNES_TABLE . " SET abo_lang = 'fr' WHERE abo_lang = 'francais'";
			$sql_update[] = "UPDATE " . ADMIN_TABLE . " SET admin_lang = 'en' WHERE admin_lang = 'english'";
			$sql_update[] = "UPDATE " . ADMIN_TABLE . " SET admin_lang = 'fr' WHERE admin_lang = 'francais'";
		}

		//
		// Activation/Désactivation de l’éditeur HTML intégré
		//
		if ($nl_config['db_version'] < 24) {
			// Seulement si la table admin n'a pas été entièrement réécrite plus haut.
			if (!$sql_tables_recreated[ADMIN_TABLE]) {
				switch ($db::ENGINE) {
					case 'mysql':
						$type = 'TINYINT(1)';
						break;
					case 'postgres':
						$type = 'SMALLINT';
						break;
					case 'sqlite':
						$type = 'INTEGER';
						break;
				}

				$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
					ADD COLUMN html_editor $type NOT NULL DEFAULT 1";
			}
		}

		//
		// Clé primaire sur la table auth_admin
		//
		if ($nl_config['db_version'] < 25) {
			if ($db::ENGINE != 'sqlite') {
				$sql_update[] = "ALTER TABLE " . AUTH_ADMIN_TABLE . " DROP INDEX admin_id_idx";
				$sql_update[] = "ALTER TABLE " . AUTH_ADMIN_TABLE . " ADD PRIMARY KEY(admin_id,liste_id)";
			}
			else {
				wa_sqlite_recreate_table(AUTH_ADMIN_TABLE);
			}
		}

		//
		// Suppression de la fonctionnalité d’envoi de copie des
		// newsletters aux admins
		//
		if ($nl_config['db_version'] < 26) {
			if ($db::ENGINE != 'sqlite') {
				$sql_update[] = "ALTER TABLE " . AUTH_ADMIN_TABLE . " DROP COLUMN cc_admin";
			}
			else {
				wa_sqlite_recreate_table(AUTH_ADMIN_TABLE);
			}
		}

		//
		// Ajout contrainte d’unicité sur admin_login (unicité déjà "garantie"
		// au niveau du bloc de code créant un nouvel admin dans admin.php).
		//
		if ($nl_config['db_version'] < 27) {
			if ($db::ENGINE != 'sqlite') {
				$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
					ADD CONSTRAINT admin_login_idx UNIQUE (admin_login)";
			}
			else {
				wa_sqlite_recreate_table(ADMIN_TABLE);
			}
		}

		//
		// Ajout du paramètre de configuration 'sending_delay'
		//
		if ($nl_config['db_version'] < 28) {
			// Seulement si la table config n'a pas été entièrement réécrite plus haut.
			if (!$sql_tables_recreated[CONFIG_TABLE]) {
				$sql_update[] = "INSERT INTO " . CONFIG_TABLE . " (config_name, config_value)
					VALUES('sending_delay', '10')";
			}
		}

		exec_queries($sql_update);

		//
		// On met à jour le numéro identifiant la version des tables du script
		//
		wa_update_config('db_version', WANEWSLETTER_DB_VERSION);

		//
		// Création d’une session si besoin.
		//
		if (!$auth->isLoggedIn()) {
			if (is_null($session)) {
				$session = new Session($nl_config);
			}
			else {
				$session->reset();
			}

			$_SESSION['is_logged_in'] = true;
			$_SESSION['uid'] = intval($admindata['uid']);
		}

		//
		// Affichage message de résultat
		//
		$message = $lang['Success_upgrade'];

		if (UPDATE_CONFIG_FILE || $moved_dirs || $unknown_files) {
			$output->header();

			$template = new Template('result_upgrade_body.tpl');

			if (UPDATE_CONFIG_FILE) {
				$template->assignToBlock('download_file', [
					'L_DL_BUTTON' => $lang['Button']['dl']
				]);

				$message = $lang['Success_upgrade_no_config'];
			}

			$template->assign([
				'L_TITLE_UPGRADE' => $lang['Title']['upgrade'],
				'MESSAGE' => nl2br($message)
			]);

			if ($moved_dirs) {
				$template->assignToBlock('moved_dirs', [
					'MOVED_DIRS_NOTICE' => nl2br($lang['Moved_dirs_notice'])
				]);
			}
			if ($unknown_files) {
				$template->assignToBlock('unknown_files', [
					'NOTICE'  => nl2br($lang['Unknown_files_notice']),
					'LISTING' => $unknown_files
				]);
			}

			$template->pparse();
			$output->footer();
		}
		else {
			$output->message($message);
		}
	}
}

$output->header();

$template = new Template('upgrade_body.tpl');

$template->assign([
	'L_TITLE_UPGRADE' => $lang['Title']['upgrade'],
	'L_EXPLAIN'       => nl2br(sprintf($lang['Welcome_in_upgrade'], WANEWSLETTER_VERSION)),
	'L_START_BUTTON'  => $lang['Start_upgrade']
]);

if (!$auth->isLoggedIn()) {
	// ajouter formulaire de connexion
	$template->assignToBlock('login_form', [
		'L_LOGIN'  => $lang['Login'],
		'L_PASSWD' => $lang['Password']
	]);
}

$template->pparse();
$output->footer();
