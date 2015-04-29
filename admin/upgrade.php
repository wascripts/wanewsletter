<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

use Patchwork\Utf8 as u;

const IN_ADMIN = true;

require '../includes/common.inc.php';

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
		$output->displayMessage($lang['Unsupported_version']);
	}

	//
	// On sélectionne manuellement db_version en fonction de chaque
	// version ayant apporté des changements dans les tables de données
	// du script.
	// Le numérotage de db_version prenait en compte des versions
	// antérieures à 2.2.0 qui apportaient des modifications, d'où
	// le db_version commençant à 5.
	//
	$nl_config['db_version'] = 6;

	if (version_compare($currentVersion, '2.2.13', '>')) {
		$nl_config['db_version']++;
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
	if (!$auth->isLoggedIn() || is_null($session) || !wan_is_admin($admindata)) {
		// Utilisateur non authentifié ou n'ayant pas le niveau d’administrateur
		if (filter_input(INPUT_GET, 'output') == 'json') {
			header('Content-Type: application/json');
			echo '{"code":"-1"}';
		}
		else {
			http_response_code(401);
			$output->redirect('./index.php', 5);
			$output->addLine($lang['Message']['Not_authorized']);
			$output->addLine($lang['Click_return_index'], './index.php');
			$output->displayMessage();
		}

		exit;
	}

	$result = wa_check_update(true);

	if (filter_input(INPUT_GET, 'output') == 'json') {
		header('Content-Type: application/json');

		if ($result !== false) {
			printf('{"code":"%d"}', $result);
		}
		else {
			echo '{"code":"2"}';
		}
	}
	else {
		if ($result !== false) {
			if ($result === 1) {
				$output->addLine($lang['New_version_available']);
				$output->addLine(sprintf('<a href="%s">%s</a>', DOWNLOAD_PAGE, $lang['Download_page']));
			}
			else {
				$output->addLine($lang['Version_up_to_date']);
			}
		}
		else {
			$output->addLine($lang['Site_unreachable']);
		}

		$output->displayMessage();
	}

	exit;
}

//
// Envoi du fichier au client si demandé
//
$config_file  = '<' . "?php\n";
$config_file .= "//\n";
$config_file .= "// Paramètres d'accès à la base de données\n";
$config_file .= "//\n";
$config_file .= "\$dsn = '$dsn';\n";
$config_file .= "\$prefixe = '$prefixe';\n";
$config_file .= "\n";

if ($auth->isLoggedIn() && wan_is_admin($admindata) && isset($_POST['sendfile'])) {
	sendfile('config.inc.php', 'text/plain', $config_file);
}

if (check_db_version($nl_config['db_version'])) {
	$output->displayMessage($lang['Upgrade_not_required']);
}

if (isset($_POST['start'])) {
	$sql_create = WA_ROOTDIR . '/includes/sql/schemas/' . $db::ENGINE . '_tables.sql';
	$sql_data   = WA_ROOTDIR . '/includes/sql/schemas/data.sql';

	if (!is_readable($sql_create) || !is_readable($sql_data)) {
		$error = true;
		$msg_error[] = $lang['Message']['sql_file_not_readable'];
	}

	if (!$auth->isLoggedIn()) {
		$login  = trim(u::filter_input(INPUT_POST, 'login'));
		$passwd = trim(u::filter_input(INPUT_POST, 'passwd'));
		$admindata = $auth->checkCredentials($login, $passwd);

		if (!$admindata) {
			$error = true;
			$msg_error[] = $lang['Message']['Error_login'];
		}
	}

	if (!$error && !wan_is_admin($admindata)) {
		http_response_code(401);
		$output->redirect('./index.php', 6);
		$output->addLine($lang['Message']['Not_authorized']);
		$output->addLine($lang['Click_return_index'], './index.php');
		$output->displayMessage();
	}

	load_settings($admindata);

	if (!$error) {
		//
		// Lancement de la mise à jour
		// On allonge le temps maximum d'execution du script.
		//
		@set_time_limit(1200);

		require WA_ROOTDIR . '/includes/sql/sqlparser.php';

		$sql_create = Dblayer\parseSQL(file_get_contents($sql_create), $prefixe);
		$sql_data   = Dblayer\parseSQL(file_get_contents($sql_data), $prefixe);

		$sql_create_by_table = $sql_data_by_table = array();

		foreach ($sql_create as $query) {
			if (preg_match("/CREATE\s+(TABLE|INDEX)\s+([A-Za-z0-9_$]+?)\s+/", $query, $m)) {
				foreach ($sql_schemas as $tablename => $schema) {
					if ($m[1] == 'TABLE') {
						if ($tablename != $m[2]) {
							continue;
						}
					}
					else if (!isset($schema[strtolower($m[1])]) ||
						array_search($m[2], $schema[strtolower($m[1])]) === false
					) {
						continue;
					}

					if (!isset($sql_create_by_table[$tablename])) {
						$sql_create_by_table[$tablename] = array();
					}
					$sql_create_by_table[$tablename][] = $query;
				}
			}
		}

		foreach ($sql_data as $query) {
			if (preg_match('/INSERT\s+INTO\s+([A-Za-z0-9_$]+)/', $query, $m)) {
				if (!array_key_exists($m[1], $sql_data_by_table) && array_key_exists($m[1], $sql_schemas)) {
					$sql_data_by_table[$m[1]] = array();
				}
				$sql_data_by_table[$m[1]][] = $query;
			}
		}

		$sql_create = $sql_create_by_table;
		$sql_data   = $sql_data_by_table;

		//
		// Début de la mise à jour
		//
		$sql_update = array();

		if ($nl_config['db_version'] < 7) {
			//
			// Vérification préalable de la présence de doublons dans la table
			// des abonnés.
			// La contrainte d'unicité sur abo_email a été ajoutée dans la
			// version 2.3-beta1 (équivalent db_version = 7).
			// Si des doublons sont présents, la mise à jour ne peut continuer.
			//
			$sql = "SELECT abo_email
				FROM " . ABONNES_TABLE . "
				GROUP BY abo_email
				HAVING COUNT(abo_email) > 1";
			$result = $db->query($sql);

			if ($row = $result->fetch()) {
				$emails = array();

				do {
					$emails[] = $row['abo_email'];
				}
				while ($row = $result->fetch());

				$output->displayMessage(sprintf("Des adresses email sont présentes en plusieurs
					exemplaires dans la table %s, la mise à jour ne peut donc continuer.
					Supprimez les doublons en cause puis relancez la mise à jour.
					Adresses email présentes en plusieurs exemplaires : %s",
					ABONNES_TABLE,
					implode(', ', $emails)
				));
			}

			//
			// La contrainte d'unicité sur abo_email peut avoir été perdue en cas
			// de bug lors de l'importation via l'outil proposé par Wanewsletter.
			// On essaie de recréer cette contrainte d'unicité.
			//
			if ($db::ENGINE == 'postgres') {
				$db->query("ALTER TABLE " . ABONNES_TABLE . "
					ADD CONSTRAINT abo_email_idx UNIQUE (abo_email)");
			}
			else if ($db::ENGINE == 'sqlite') {
				$db->query("CREATE UNIQUE INDEX abo_email_idx ON " . ABONNES_TABLE . "(abo_email)");
			}
			else if ($db::ENGINE == 'mysql') {
				$db->query("ALTER TABLE " . ABONNES_TABLE . "
					ADD UNIQUE abo_email_idx (abo_email)");
			}

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
				if ($row['abo_status'] == ABO_INACTIF) {
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

			$sql_update = array();
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
				$sql_update[] = "ALTER TABLE " . BANLIST_TABLE . "
					ALTER COLUMN ban_email TYPE VARCHAR(254)";
				$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
					ALTER COLUMN sender_email TYPE VARCHAR(254),
					ALTER COLUMN return_email TYPE VARCHAR(254),
					ALTER COLUMN liste_alias TYPE VARCHAR(254)";
			}
			else if ($db::ENGINE == 'sqlite') {
				foreach (array(ABONNES_TABLE, ADMIN_TABLE, BANLIST_TABLE, LISTE_TABLE) as $tablename) {
					wa_sqlite_recreate_table($tablename);
				}
			}
			else {
				$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
					MODIFY COLUMN abo_email VARCHAR(254) NOT NULL DEFAULT ''";
				$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
					MODIFY COLUMN admin_email VARCHAR(254) NOT NULL DEFAULT ''";
				$sql_update[] = "ALTER TABLE " . BANLIST_TABLE . "
					MODIFY COLUMN ban_email VARCHAR(254) NOT NULL DEFAULT ''";
				$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
					MODIFY COLUMN sender_email VARCHAR(254) NOT NULL DEFAULT '',
					MODIFY COLUMN return_email VARCHAR(254) NOT NULL DEFAULT '',
					MODIFY COLUMN liste_alias VARCHAR(254) NOT NULL DEFAULT ''";
			}

			$sql_schemas[CONFIG_TABLE]['updated'] = true;
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
				foreach (array(ABONNES_TABLE, ADMIN_TABLE) as $tablename) {
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
			$moved_dirs = !is_writable(WA_TMPDIR);
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
		if ($nl_config['db_version'] < 15 && empty($sql_schemas[CONFIG_TABLE]['updated'])) {
			$sql_update[] = "INSERT INTO " . CONFIG_TABLE . " (config_name, config_value)
				VALUES('debug_level', '1')";
		}

		//
		// Corrections sur les séquences PostgreSQL créées manuellement et donc
		// non liées à leur table
		//
		if ($nl_config['db_version'] < 16 && $db::ENGINE == 'postgres') {
			// La séquence pour la table ban_list ne suit pas le nommage {tablename}_id_seq
			$sql_update[] = sprintf('ALTER SEQUENCE %1$sban_id_seq RENAME TO %2$s_id_seq', $prefixe, BANLIST_TABLE);

			$sql_update[] = sprintf('ALTER SEQUENCE %1$s_id_seq OWNED BY %1$s.abo_id', ABONNES_TABLE);
			$sql_update[] = sprintf('ALTER SEQUENCE %1$s_id_seq OWNED BY %1$s.admin_id', ADMIN_TABLE);
			$sql_update[] = sprintf('ALTER SEQUENCE %1$s_id_seq OWNED BY %1$s.ban_id', BANLIST_TABLE);
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
				foreach ($sql_schemas as $tablename => $schema) {
					$sql_update[] = sprintf("ALTER TABLE %s CONVERT TO CHARACTER SET utf8", $tablename);
				}
			}
			else if ($db::ENGINE == 'sqlite') {
				$db->createFunction('utf8_encode', array('\Patchwork\Utf8', 'utf8_encode'));

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
					$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . "
						ENGINE = MyISAM";
					$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . "
						MODIFY COLUMN session_id VARCHAR(100)";
					$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . "
						CHANGE COLUMN session_time session_expire INTEGER";
					$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . "
						ADD COLUMN session_data MEDIUMTEXT";
				}
				else {
					$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . "
						ALTER COLUMN session_id TYPE VARCHAR(100)";
					$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . "
						RENAME COLUMN session_time TO session_expire";
					$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . "
						ADD COLUMN session_data TEXT";
				}

				$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . "
					DROP COLUMN admin_id";
				$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . "
					DROP COLUMN session_ip";
				$sql_update[] = "ALTER TABLE " . SESSIONS_TABLE . "
					DROP COLUMN session_liste";
			}
			else {
				wa_sqlite_recreate_table(SESSIONS_TABLE, false);
			}

			$sql_update[] = "DELETE FROM " . SESSIONS_TABLE;

			exec_queries($sql_update);
			$db->vacuum(SESSIONS_TABLE);
		}

		//
		// Support SSL/TLS pour les connexions aux serveurs SMTP et POP
		//
		if ($nl_config['db_version'] < 20) {
			// Seulement si la table config n'a pas été entièrement réécrite plus haut.
			if (empty($sql_schemas[CONFIG_TABLE]['updated'])) {
				$sql_update[] = "INSERT INTO " . CONFIG_TABLE . " (config_name, config_value)
					VALUES('smtp_tls', '0')";
			}

			// Seulement si la table liste n'a pas été entièrement réécrite plus haut.
			if (empty($sql_schemas[LISTE_TABLE]['updated'])) {
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
		if (UPDATE_CONFIG_FILE || $moved_dirs) {
			$output->page_header();

			$output->set_filenames(array(
				'body' => 'result_upgrade_body.tpl'
			));

			$message = $lang['Success_upgrade'];

			if (UPDATE_CONFIG_FILE) {
				$output->assign_block_vars('download_file', array(
					'L_DL_BUTTON' => $lang['Button']['dl']
				));

				$message = $lang['Success_upgrade_no_config'];
			}

			$output->assign_vars(array(
				'L_TITLE_UPGRADE' => $lang['Title']['upgrade'],
				'MESSAGE' => nl2br($message)
			));

			if ($moved_dirs) {
				$output->assign_block_vars('moved_dirs', array(
					'MOVED_DIRS_NOTICE' => nl2br($lang['Moved_dirs_notice'])
				));
			}

			$output->pparse('body');

			$output->page_footer();
		}
		else {
			$output->displayMessage($lang['Success_upgrade']);
		}
	}
}

$output->page_header();

$output->set_filenames( array(
	'body' => 'upgrade_body.tpl'
));

$output->assign_vars( array(
	'L_TITLE_UPGRADE' => $lang['Title']['upgrade'],
	'L_EXPLAIN'       => nl2br(sprintf($lang['Welcome_in_upgrade'], WANEWSLETTER_VERSION)),
	'L_START_BUTTON'  => $lang['Start_upgrade']
));

if (!$auth->isLoggedIn()) {
	// ajouter formulaire de connexion
	$output->assign_block_vars('login_form', array(
		'L_LOGIN'  => $lang['Login'],
		'L_PASSWD' => $lang['Password']
	));
}

$output->pparse('body');

$output->page_footer();

