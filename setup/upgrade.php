<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

define('IN_UPGRADE', true);

require './setup.inc.php';

$admin_login = ( !empty($_POST['admin_login']) ) ? trim($_POST['admin_login']) : '';
$admin_pass  = ( !empty($_POST['admin_pass']) ) ? trim($_POST['admin_pass']) : '';

if( !defined('NL_INSTALLED') )
{
	plain_error("Wanewsletter ne semble pas installé");
}

$db = WaDatabase($dsn);

if( !$db->isConnected() )
{
	plain_error(sprintf($lang['Connect_db_error'], $db->error));
}

//
// Récupération de la configuration
//
$old_config = wa_get_config();

if( file_exists(WA_ROOTDIR . '/language/lang_' . $old_config['language'] . '.php') )
{
	require WA_ROOTDIR . '/language/lang_' . $old_config['language'] . '.php';
}

//
// Compatibilité avec Wanewsletter < 2.4-beta2
//
if( !isset($old_config['db_version']) )
{
	// Versions <= 2.2.13
	if( !defined('WA_VERSION') )
	{
		$currentVersion = strtolower($old_config['version']);
	}
	// Versions <= 2.4-beta1
	else
	{
		$currentVersion = WA_VERSION;
	}
	
	// Les versions des branches 2.0 et 2.1 ne sont plus prises en charge
	if( !version_compare($currentVersion, '2.2-beta', '>=' ) )
	{
		message($lang['Unsupported_version']);
	}
	
	//
	// On incrémente manuellement db_version en fonction de chaque version ayant
	// apporté des changements dans les tables de données du script.
	//
	$old_config['db_version'] = 0;
	$versions = array('2.2-beta2', '2.2-rc2', '2.2-rc2b', '2.2-rc3', '2.2.12', '2.3-beta3', '2.4-beta1');
	
	foreach( $versions as $version )
	{
		$old_config['db_version'] += (int)version_compare($currentVersion, $version, '>');
	}
}

if( check_db_version($old_config['db_version']) )
{
	message($lang['Upgrade_not_required']);
}

$output->set_filenames( array(
	'body' => 'upgrade.tpl'
));

$output->send_headers();

$output->assign_vars( array(
	'PAGE_TITLE'   => $lang['Title']['upgrade'],
	'CONTENT_LANG' => $lang['CONTENT_LANG'],
	'CONTENT_DIR'  => $lang['CONTENT_DIR'],
	'CHARSET'      => $lang['CHARSET'],
	'NEW_VERSION'  => WANEWSLETTER_VERSION,
	'TRANSLATE'    => ( $lang['TRANSLATE'] != '' ) ? ' | Translate by ' . $lang['TRANSLATE'] : ''
));

if( $start )
{
	if( !check_admin($admin_login, $admin_pass) )
	{
		$error = true;
		$msg_error[] = $lang['Message']['Error_login'];
	}
	
	$sql_create = SCHEMAS_DIR . '/' . $supported_db[$infos['engine']]['prefixe_file'] . '_tables.sql';
	$sql_data   = SCHEMAS_DIR . '/data.sql';
	
	if( !is_readable($sql_create) || !is_readable($sql_data) )
	{
		$error = true;
		$msg_error[] = $lang['Message']['sql_file_not_readable'];
	}
	
	if( !$error )
	{
		//
		// Lancement de la mise à jour
		// On allonge le temps maximum d'execution du script.
		//
		@set_time_limit(1200);
		
		$sql_create = parseSQL(file_get_contents($sql_create), $prefixe);
		$sql_data   = parseSQL(file_get_contents($sql_data), $prefixe);
		
		$pattern = ($db->engine == 'postgres' ? 'SEQUENCE|' : '') . 'TABLE|INDEX';
		
		$sql_create_by_table = $sql_data_by_table = array();
		
		foreach( $sql_create as $query )
		{
			if( preg_match("/CREATE\s+($pattern)\s+([A-Za-z0-9_$]+?)\s+/", $query, $m) )
			{
				foreach( $sql_schemas as $tablename => $schema )
				{
					if( $m[1] == 'TABLE' )
					{
						if( $tablename != $m[2] )
						{
							continue;
						}
					}
					else if( !isset($schema[strtolower($m[1])]) || array_search($m[2], $schema[strtolower($m[1])]) === false )
					{
						continue;
					}
					
					if( !isset($sql_create_by_table[$tablename]) )
					{
						$sql_create_by_table[$tablename] = array();
					}
					$sql_create_by_table[$tablename][] = $query;
				}
			}
		}
		
		foreach( $sql_data as $query )
		{
			if( preg_match('/INSERT\s+INTO\s+([A-Za-z0-9_$]+)/', $query, $m) )
			{
				if( !array_key_exists($m[1], $sql_data_by_table) && array_key_exists($m[1], $sql_schemas) )
				{
					$sql_data_by_table[$m[1]] = array();
				}
				$sql_data_by_table[$m[1]][] = $query;
			}
		}
		
		$sql_create = $sql_create_by_table;
		$sql_data   = $sql_data_by_table;
		
		//
		// Nous vérifions tout d'abord si des doublons sont présents dans
		// la table des abonnés.
		// Si des doublons sont présents, la mise à jour ne peut continuer.
		//
		$sql = "SELECT abo_email
			FROM " . ABONNES_TABLE . "
			GROUP BY abo_email
			HAVING COUNT(abo_email) > 1";
		if( !($result = $db->query($sql)) )
		{
			sql_error();
		}
		
		if( $row = $result->fetch() )
		{
			$emails = array();
			
			do
			{
				array_push($emails, $row[$fieldname]);
			}
			while( $row = $result->fetch() );
			
			message("Des adresses email sont présentes en plusieurs exemplaires dans la table " . ABONNES_TABLE . ", la mise à jour ne peut continuer.
			Supprimez les doublons en cause puis relancez la mise à jour.
			Adresses email présentes en plusieurs exemplaires : " . implode(', ', $emails));
		}
		
		$sql_update = array();
		
		if( $old_config['db_version'] < 2 )
		{
			if( $db->engine == 'postgres' )
			{
				$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
					ADD COLUMN liste_alias VARCHAR(254) NOT NULL DEFAULT ''";
				$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
					ADD COLUMN use_cron SMALLINT NOT NULL DEFAULT 0";
				$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
					ADD COLUMN pop_host VARCHAR(100) NOT NULL DEFAULT ''";
				$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
					ADD COLUMN pop_port SMALLINT NOT NULL DEFAULT 110";
				$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
					ADD COLUMN pop_user VARCHAR(100) NOT NULL DEFAULT ''";
				$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
					ADD COLUMN pop_pass VARCHAR(100) NOT NULL DEFAULT ''";
			}
			else
			{
				$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
					ADD COLUMN liste_alias VARCHAR(254) NOT NULL DEFAULT '',
					ADD COLUMN use_cron TINYINT(1) NOT NULL DEFAULT 0,
					ADD COLUMN pop_host VARCHAR(100) NOT NULL DEFAULT '',
					ADD COLUMN pop_port SMALLINT NOT NULL DEFAULT 110,
					ADD COLUMN pop_user VARCHAR(100) NOT NULL DEFAULT '',
					ADD COLUMN pop_pass VARCHAR(100) NOT NULL DEFAULT ''";
			}
		}
		
		//
		// Un bug était présent dans la rc1, comme une seconde édition du package avait été mise
		// à disposition pour pallier à un bug de dernière minute assez important, le numéro de version
		// était 2.2-rc2 pendant une dizaine de jours (alors qu'il me semblait avoir recorrigé
		// le package après coup).
		// Nous effectuons donc la mise à jour également pour les versions 2.2-rc2.
		// Le nom de la vrai release candidate 2 est donc 2.2-rc2b pour éviter des problèmes lors des mises
		// à jour par les gens qui ont téléchargé le package les dix premiers jours.
		//
		if( $old_config['db_version'] < 3 )
		{
			//
			// Suppression des éventuelles entrées orphelines dans les tables abonnes et abo_liste
			//
			$sql = "SELECT abo_id
				FROM " . ABONNES_TABLE;
			if( !($result = $db->query($sql)) )
			{
				sql_error();
			}
			
			$abonnes_id = array();
			while( $abo_id = $result->column('abo_id') )
			{
				array_push($abonnes_id, $abo_id);
			}
			
			$sql = "SELECT abo_id
				FROM " . ABO_LISTE_TABLE . "
				GROUP BY abo_id";
			if( !($result = $db->query($sql)) )
			{
				sql_error();
			}
			
			$abo_liste_id = array();
			while( $abo_id = $result->column('abo_id') )
			{
				array_push($abo_liste_id, $abo_id);
			}
			
			$diff_1 = array_diff($abonnes_id, $abo_liste_id);
			$diff_2 = array_diff($abo_liste_id, $abonnes_id);
			
			$total_diff_1 = count($diff_1);
			$total_diff_2 = count($diff_2);
			
			if( $total_diff_1 > 0 )
			{
				$sql_update[] = "DELETE FROM " . ABONNES_TABLE . "
					WHERE abo_id IN(" . implode(', ', $diff_1) . ")";
			}
			
			if( $total_diff_2 > 0 )
			{
				$sql_update[] = "DELETE FROM " . ABO_LISTE_TABLE . "
					WHERE abo_id IN(" . implode(', ', $diff_2) . ")";
			}
			
			if( $db->engine == 'postgres' )
			{
				$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
					ADD COLUMN liste_numlogs SMALLINT NOT NULL DEFAULT 0";
				$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
					ADD COLUMN log_numdest SMALLINT NOT NULL DEFAULT 0";
			}
			else
			{
				$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
					ADD COLUMN liste_numlogs SMALLINT NOT NULL DEFAULT 0 AFTER liste_alias";
				$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
					ADD COLUMN log_numdest SMALLINT NOT NULL DEFAULT 0 AFTER log_date";
				$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . " DROP INDEX abo_id";
				$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . " DROP INDEX liste_id";
				$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . "
					ADD PRIMARY KEY (abo_id , liste_id)";
				$sql_update[] = "ALTER TABLE " . LOG_FILES_TABLE . " DROP INDEX log_id";
				$sql_update[] = "ALTER TABLE " . LOG_FILES_TABLE . " DROP INDEX file_id";
				$sql_update[] = "ALTER TABLE " . LOG_FILES_TABLE . "
					ADD PRIMARY KEY (log_id , file_id)";
			}
			
			$sql = "SELECT COUNT(*) AS numlogs, liste_id
				FROM " . LOG_TABLE . "
				WHERE log_status = " . STATUS_SENT . "
				GROUP BY liste_id";
			if( !($result = $db->query($sql)) )
			{
				sql_error();
			}
			
			while( $row = $result->fetch() )
			{
				$sql_update[] = "UPDATE " . LISTE_TABLE . "
					SET liste_numlogs = " . $row['numlogs'] . "
					WHERE liste_id = " . $row['liste_id'];
			}
			
			$sql = "SELECT COUNT(DISTINCT(a.abo_id)) AS num_dest, al.liste_id
				FROM " . ABONNES_TABLE . " AS a, " . ABO_LISTE_TABLE . " AS al
				WHERE a.abo_id = al.abo_id AND a.abo_status = " . ABO_ACTIF . "
				GROUP BY al.liste_id";
			if( !($result = $db->query($sql)) )
			{
				sql_error();
			}
			
			while( $row = $result->fetch() )
			{
				$sql_update[] = "UPDATE " . LOG_TABLE . "
					SET log_numdest = " . $row['num_dest'] . "
					WHERE liste_id = " . $row['liste_id'];
			}
		}
		
		if( $old_config['db_version'] < 4 )
		{
			if( $db->engine == 'postgres' )
			{
				$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
					ADD COLUMN abo_lang VARCHAR(30) NOT NULL DEFAULT ''";
			}
			else
			{
				$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
					ADD COLUMN abo_lang VARCHAR(30) NOT NULL DEFAULT '' AFTER abo_email";
			}
			
			//
			// Correction du bug de mise à jour de la table abo_liste après un envoi.
			// Si tous les abonnés d'une liste ont send à 1, on remet celui ci à 0
			//
			$sql = "SELECT COUNT(al.abo_id) AS num_abo, SUM(al.send) AS num_send, al.liste_id
				FROM " . ABONNES_TABLE . " AS a, " . ABO_LISTE_TABLE . " AS al
				WHERE a.abo_id = al.abo_id AND a.abo_status = " . ABO_ACTIF . "
				GROUP BY al.liste_id";
			if( !($result = $db->query($sql)) )
			{
				sql_error();
			}
			
			while( $row = $result->fetch() )
			{
				if( $row['num_abo'] == $row['num_send'] )
				{
					$sql_update[] = "UPDATE " . ABO_LISTE_TABLE . "
						SET send = 0
						WHERE liste_id = " . $row['liste_id'];
				}
			}
			
			$sql_update[] = "UPDATE " . ABONNES_TABLE . " SET abo_lang = '$language'";
		}
		
		if( $old_config['db_version'] < 5 )
		{
			if( $db->engine == 'mysql' )
			{
				$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
					CHANGE abo_id abo_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT";
				$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . "
					CHANGE abo_id abo_id INTEGER UNSIGNED NOT NULL DEFAULT 0";
			}
		}
		
		if( $old_config['db_version'] < 6 )
		{
			unset($old_config['hebergeur']);
			unset($old_config['version']);
			
			if( $db->engine == 'postgres' )
			{
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
			else
			{
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
			
			exec_queries($sql_update, true);
			
			$sql = "SELECT abo_id, abo_register_key, abo_pwd, abo_register_date, abo_status
				FROM " . ABONNES_TABLE;
			if( !($result = $db->query($sql)) )
			{
				sql_error();
			}
			
			while( $row = $result->fetch() )
			{
				$sql = "UPDATE " . ABO_LISTE_TABLE . "
					SET register_date = $row[abo_register_date],
						confirmed     = $row[abo_status]";
				if( $row['abo_status'] == ABO_INACTIF )
				{
					$sql .= ", register_key = '" . substr($row['abo_register_key'], 0, 20) . "'";
				}
				$db->query($sql . " WHERE abo_id = " . $row['abo_id']);
				
				if( empty($row['abo_pwd']) )
				{
					$db->query("UPDATE " . ABONNES_TABLE . "
						SET abo_pwd = '" . md5($row['abo_register_key']) . "'
						WHERE abo_id = $row[abo_id]");
				}
			}
			$result->free();
			
			$sql = "SELECT abo_id, liste_id
				FROM " . ABO_LISTE_TABLE . "
				WHERE register_key IS NULL";
			if( !($result = $db->query($sql)) )
			{
				sql_error();
			}
			
			while( $row = $result->fetch() )
			{
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
			
			if( $db->engine == 'postgres' )
			{
				$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . "
					ADD CONSTRAINT register_key_idx UNIQUE (register_key)";
				$sql_update[] = "CREATE INDEX abo_status_idx ON " . ABONNES_TABLE . " (abo_status)";
				$sql_update[] = "CREATE INDEX admin_id_idx ON " . AUTH_ADMIN_TABLE . " (admin_id)";
				$sql_update[] = "CREATE INDEX liste_id_idx ON " . LOG_TABLE . " (liste_id)";
				$sql_update[] = "CREATE INDEX log_status_idx ON " . LOG_TABLE . " (log_status)";
			}
			else
			{
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
		// Début du support de SQLite en plus de MySQL et PostgreSQL
		// (2.3-beta1 pour SQLite 2; 2.3-beta2 pour SQLite 3)
		//
		
		//
		// La contrainte d'unicité sur abo_email peut avoir été perdue en cas
		// de bug lors de l'importation via l'outil proposé par Wanewsletter.
		// On essaie de recréer cette contrainte d'unicité.
		//
		if( $old_config['db_version'] < 7 )
		{
			//
			// En cas de bug lors d'une importation d'emails, les clefs
			// peuvent ne pas avoir été recréées si une erreur est survenue
			//
			if( $db->engine == 'postgres' )
			{
				$db->query("ALTER TABLE " . ABONNES_TABLE . "
					ADD CONSTRAINT abo_email_idx UNIQUE (abo_email)");
			}
			else if( $db->engine == 'sqlite' )
			{
				$db->query("CREATE UNIQUE INDEX abo_email_idx ON " . ABONNES_TABLE . "(abo_email)");
			}
			else if( $db->engine == 'mysql' )
			{
				$db->query("ALTER TABLE " . ABONNES_TABLE . "
					ADD UNIQUE abo_email_idx (abo_email)");
			}
		}
		
		//
		// Passage de toutes les colonnes stockant une adresse email en VARCHAR(254)
		// - On uniformise les tailles de colonne pour ce type de données
		// - le protocole SMTP nécessite une longueur max de 254 octets des adresses email
		//
		if( $old_config['db_version'] < 8 )
		{
			if( $db->engine == 'postgres' )
			{
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
			else if( $db->engine == 'sqlite' )
			{
				foreach( array(ABONNES_TABLE, ADMIN_TABLE, BANLIST_TABLE, LISTE_TABLE) as $tablename )
				{
					wa_sqlite_recreate_table($tablename);
				}
			}
			else
			{
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
			
			$sql_update[] = "DROP TABLE " . CONFIG_TABLE;
			$sql_update   = array_merge($sql_update, $sql_create[CONFIG_TABLE]);
			$sql_update   = array_merge($sql_update, $sql_data[CONFIG_TABLE]);
			
			exec_queries($sql_update, true);
			
			//
			// On remet en place la configuration actuelle du script
			//
			$new_config = wa_get_config();
			$new_config = array_intersect_key($old_config, $new_config);
			wa_update_config($new_config);
			$old_config = array_diff_key($old_config, $new_config);
			
			foreach( $old_config as $name => $value )
			{
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
		if( $old_config['db_version'] < 9 )
		{
			if( $db->engine == 'postgres' )
			{
				$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
					ALTER COLUMN abo_pwd TYPE VARCHAR(255)";
				$sql_update[] = "ALTER TABLE " . ADMIN_TABLE . "
					ALTER COLUMN admin_pwd TYPE VARCHAR(255)";
			}
			else if( $db->engine == 'sqlite' )
			{
				foreach( array(ABONNES_TABLE, ADMIN_TABLE) as $tablename )
				{
					wa_sqlite_recreate_table($tablename);
				}
			}
			else
			{
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
		if( $old_config['db_version'] < 10 )
		{
			if( $db->engine == 'mysql' )
			{
				$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
					MODIFY COLUMN log_body_html MEDIUMTEXT,
					MODIFY COLUMN log_body_text MEDIUMTEXT";
			}
		}
		
		//
		// Correction d'une horrible faute de conjuguaison sur le nom d'une
		// entrée de la configuration.
		//
		if( $old_config['db_version'] < 11 )
		{
			$sql_update[] = "UPDATE " . CONFIG_TABLE . "
				SET config_name = 'sending_limit'
				WHERE config_name = 'emails_sended'";
		}
		
		exec_queries($sql_update, true);
		
		//
		// On met à jour le numéro identifiant la version des tables du script
		//
		wa_update_config(array('db_version' => WANEWSLETTER_DB_VERSION));
		
		//
		// Affichage message de résultat
		//
		$message = sprintf($lang['Success_upgrade'], '<a href="' . WA_ROOTDIR . '/admin/login.php">', '</a>');
		message($message, $lang['Result_upgrade']);
	}
}

$output->assign_block_vars('upgrade', array(
	'L_EXPLAIN'      => nl2br(sprintf($lang['Welcome_in_upgrade'], WANEWSLETTER_VERSION)),
	'L_LOGIN'        => $lang['Login'],
	'L_PASS'         => $lang['Password'],
	'L_START_BUTTON' => $lang['Start_upgrade']
));

if( $error )
{
	$output->error_box($msg_error);
}

$output->pparse('body');
