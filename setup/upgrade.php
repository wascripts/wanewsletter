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

//
// Compatibilité avec les versions < 2.1.2
//
if( !defined('NL_INSTALLED') && isset($dbhost) && !isset($_REQUEST['dbhost']) )
{
	define('NL_INSTALLED', true);
}

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
$sql = "SELECT * FROM " . CONFIG_TABLE;
if( !($result = $db->query($sql)) )
{
	plain_error("Impossible d'obtenir la configuration du script :\n" . $db->error);
}

$old_config = array();
while( $row = $result->fetch() )
{
	if( !isset($row['nom']) )
	{
		$old_config = $row;
		break;
	}
	else // branche 2.0
	{
		$old_config[$row['nom']] = $row['valeur'];
	}
}

//
// Compatibilité avec les versions < 2.3
//
if( !defined('WA_VERSION') )
{
	define('WA_VERSION', $old_config['version']);
}

if( file_exists(WA_ROOTDIR . '/language/lang_' . $old_config['language'] . '.php') )
{
	require WA_ROOTDIR . '/language/lang_' . $old_config['language'] . '.php';
}

if( !preg_match('/^(2\.[0-4])[-.0-9a-zA-Z]+$/', WA_VERSION, $match) )
{
	message($lang['Unknown_version']);
}

define('WA_BRANCHE', $match[1]);

if( WA_BRANCHE == '2.0' || WA_BRANCHE == '2.1' )
{
	message($lang['Unsupported_version']);
}

$output->set_filenames( array(
	'body' => 'upgrade.tpl'
));

$output->send_headers();

$output->assign_vars( array(
	'PAGE_TITLE'   => $lang['Title']['upgrade'],
	'CONTENT_LANG' => $lang['CONTENT_LANG'],
	'CONTENT_DIR'  => $lang['CONTENT_DIR'],
	'NEW_VERSION'  => WA_NEW_VERSION,
	'TRANSLATE'    => ( $lang['TRANSLATE'] != '' ) ? ' | Translate by ' . $lang['TRANSLATE'] : ''
));

if( $start )
{
	$sql = "SELECT COUNT(*)
		FROM " . ADMIN_TABLE . "
		WHERE LOWER(admin_login) = '" . $db->escape(strtolower($admin_login)) . "'
			AND admin_pwd = '" . md5($admin_pass) . "'
			AND admin_level >= " . ADMIN;
	$res = $db->query($sql);
	if( $res->column(0) == 0 )
	{
		$error = true;
		$msg_error[] = $lang['Message']['Error_login'];
	}
	
	$sql_create = SCHEMAS_DIR . '/' . $supported_db[$infos['driver']]['prefixe_file'] . '_tables.sql';
	
	if( !is_readable($sql_create) )
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
		
		foreach( $sql_create as $query )
		{
			preg_match('/CREATE TABLE ' . $prefixe . '([[:alnum:]_-]+)/i', $query, $match);
			
			$sql_create[$match[1]] = $query;
		}
		
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
		
		if( WA_BRANCHE == '2.2' )
		{
			$sql_update = array();
			
			switch( WA_VERSION )
			{
				case '2.2-Beta':
				case '2.2-Beta2':
					switch( SQL_DRIVER )
					{
						case 'postgres':
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								RENAME COLUMN smtp_user TO smtp_user_old,
								RENAME COLUMN smtp_pass TO smtp_pass_old";
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN smtp_user varchar(100) NOT NULL DEFAULT '',
								ADD COLUMN smtp_pass varchar(100) NOT NULL DEFAULT ''";
							$sql_update[] = "UPDATE " . CONFIG_TABLE . "
								SET smtp_user = smtp_user_old, smtp_pass = smtp_pass_old";
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								DROP COLUMN smtp_user_old,
								DROP COLUMN smtp_pass_old";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN liste_alias VARCHAR(250) NOT NULL DEFAULT ''";
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
							break;
						
						default:
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								MODIFY COLUMN smtp_user VARCHAR(100) NOT NULL DEFAULT '',
								MODIFY COLUMN smtp_pass VARCHAR(100) NOT NULL DEFAULT ''";
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN liste_alias VARCHAR(250) NOT NULL DEFAULT '',
								ADD COLUMN use_cron TINYINT(1) NOT NULL DEFAULT 0,
								ADD COLUMN pop_host VARCHAR(100) NOT NULL DEFAULT '',
								ADD COLUMN pop_port SMALLINT NOT NULL DEFAULT 110,
								ADD COLUMN pop_user VARCHAR(100) NOT NULL DEFAULT '',
								ADD COLUMN pop_pass VARCHAR(100) NOT NULL DEFAULT ''";
							break;
					}
				
				//
				// Un bug était présent dans la rc1, comme une seconde édition du package avait été mise
				// à disposition pour pallier à un bug de dernière minute assez important, le numéro de version
				// était 2.2-RC2 pendant une dizaine de jours (alors qu'il me semblait avoir recorrigé
				// le package après coup).
				// Nous effectuons donc la mise à jour également pour les versions 2.2-RC2.
				// Le nom de la vrai release candidate 2 est donc 2.2-RC2b pour éviter des problèmes lors des mises
				// à jour par les gens qui ont téléchargé le package les dix premiers jours.
				//
				case '2.2-RC1':
				case '2.2-RC2':
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
					
					switch( SQL_DRIVER )
					{
						case 'postgres':
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN liste_numlogs SMALLINT NOT NULL DEFAULT 0";
							$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
								ADD COLUMN log_numdest SMALLINT NOT NULL DEFAULT 0";
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN check_email_mx SMALLINT NOT NULL DEFAULT 0";
							break;
						
						default:
							$sql_update[] = "ALTER TABLE " . LISTE_TABLE . "
								ADD COLUMN liste_numlogs SMALLINT NOT NULL DEFAULT 0 AFTER liste_alias";
							$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
								ADD COLUMN log_numdest SMALLINT NOT NULL DEFAULT 0 AFTER log_date";
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN check_email_mx TINYINT(1) NOT NULL DEFAULT 0 AFTER gd_img_type";
							$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . " DROP INDEX abo_id";
							$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . " DROP INDEX liste_id";
							$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . "
								ADD PRIMARY KEY (abo_id , liste_id)";
							$sql_update[] = "ALTER TABLE " . LOG_FILES_TABLE . " DROP INDEX log_id";
							$sql_update[] = "ALTER TABLE " . LOG_FILES_TABLE . " DROP INDEX file_id";
							$sql_update[] = "ALTER TABLE " . LOG_FILES_TABLE . "
								ADD PRIMARY KEY (log_id , file_id)";
							break;
					}
					
					$sql = "SELECT COUNT(*) AS numlogs, liste_id
						FROM " . LOG_TABLE . "
						WHERE log_status = " . STATUS_SENDED . "
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
				
				case '2.2-RC2b':
					switch( SQL_DRIVER )
					{
						case 'postgres':
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN enable_profil_cp SMALLINT NOT NULL DEFAULT 0";
							$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
								ADD COLUMN abo_lang VARCHAR(30) NOT NULL DEFAULT ''";
							break;
						
						default:
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN enable_profil_cp TINYINT(1) NOT NULL DEFAULT 0 AFTER check_email_mx";
							$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
								ADD COLUMN abo_lang VARCHAR(30) NOT NULL DEFAULT '' AFTER abo_email";
							break;
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
					
				case '2.2-RC3':
					switch( SQL_DRIVER )
					{
						case 'postgres':
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN ftp_port SMALLINT NOT NULL DEFAULT 21";
							break;

						default:
							$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
								ADD COLUMN ftp_port SMALLINT NOT NULL DEFAULT 21 AFTER ftp_server";
							$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
								CHANGE abo_id abo_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT";
							$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . "
								CHANGE abo_id abo_id INTEGER UNSIGNED NOT NULL DEFAULT 0";
							break;
					}
					
				case '2.2-RC4':
				case '2.2.0':
				case '2.2.1':
				case '2.2.2':
				case '2.2.3':
				case '2.2.4':
				case '2.2.5':
				case '2.2.6':
				case '2.2.7':
				case '2.2.8':
				case '2.2.9':
				case '2.2.10':
				case '2.2.11':
				case '2.2.12':
					$sql_update[] = "ALTER TABLE " . CONFIG_TABLE . "
						DROP COLUMN hebergeur, DROP COLUMN version";
					
					if( SQL_DRIVER == 'postgres' )
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
					
					if( SQL_DRIVER == 'postgres' )
					{
						$sql_update[] = "ALTER TABLE " . ABO_LISTE_TABLE . "
							ADD CONSTRAINT register_key_idx UNIQUE (register_key)";
						$sql_update[] = "ALTER TABLE " . ABONNES_TABLE . "
							ADD CONSTRAINT abo_email_idx UNIQUE (abo_email)";
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
							ADD UNIQUE abo_email_idx (abo_email),
							ADD INDEX abo_status_idx (abo_status)";
						$sql_update[] = "ALTER TABLE " . AUTH_ADMIN_TABLE . "
							ADD INDEX admin_id_idx (admin_id)";
						$sql_update[] = "ALTER TABLE " . LOG_TABLE . "
							ADD INDEX liste_id_idx (liste_id),
							ADD INDEX log_status_idx (log_status)";
					}
					break;
				
				default:
					message($lang['Upgrade_not_required']);
					break;
			}
			
			exec_queries($sql_update, true);
		}
		else if( WA_BRANCHE == '2.3' || WA_BRANCHE == '2.4' )
		{
			$version = str_replace('rc', 'RC', WA_VERSION);
			$sql_update = array();
			
			if( !version_compare($version, WA_NEW_VERSION, '<' ) )
			{
				message($lang['Upgrade_not_required']);
			}
			
			if( version_compare($version, '2.3-beta3', '<=') )
			{
				//
				// En cas de bug lors d'une importation d'emails, les clefs
				// peuvent ne pas avoir été recréées si une erreur est survenue
				//
				if( SQL_DRIVER == 'postgres' )
				{
					$db->query("ALTER TABLE " . ABONNES_TABLE . "
						ADD CONSTRAINT abo_email_idx UNIQUE (abo_email)");
				}
				else if( strncmp(SQL_DRIVER, 'mysql', 5) == 0 )
				{
					$db->query("ALTER TABLE " . ABONNES_TABLE . "
						ADD UNIQUE abo_email_idx (abo_email)");
				}
			}
			
			exec_queries($sql_update, true);
		}
		
		//
		// Modification fichier de configuration +
		// Affichage message de résultat
		//
		if( !is_writable(WA_ROOTDIR . '/includes/config.inc.php') )
		{
			$output->addHiddenField('driver',  $infos['driver']);
			$output->addHiddenField('host',    $infos['host']);
			$output->addHiddenField('user',    $infos['user']);
			$output->addHiddenField('pass',    $infos['pass']);
			$output->addHiddenField('dbname',  $infos['dbname']);
			$output->addHiddenField('prefixe', $prefixe);
			
			$output->assign_block_vars('download_file', array(
				'L_TITLE'         => $lang['Result_upgrade'],
				'L_DL_BUTTON'     => $lang['Button']['dl'],
				
				'MSG_RESULT'      => nl2br($lang['Success_without_config']),						
				'S_HIDDEN_FIELDS' => $output->getHiddenFields()
			));
			
			$output->pparse('body');
			exit;
		}
		
		$fw = fopen(WA_ROOTDIR . '/includes/config.inc.php', 'w');
		fwrite($fw, $config_file);
		fclose($fw);
		
		//
		// Modification fichier de configuration +
		// Affichage message de résultat
		//
		$message = sprintf($lang['Success_upgrade'], '<a href="' . WA_ROOTDIR . '/admin/login.php">', '</a>');
		
		message($message, $lang['Result_upgrade']);
	}
}

$output->assign_block_vars('upgrade', array(
	'L_EXPLAIN'      => nl2br(sprintf($lang['Welcome_in_upgrade'], WA_VERSION)),
	'L_LOGIN'        => $lang['Login'],
	'L_PASS'         => $lang['Password'],
	'L_START_BUTTON' => $lang['Start_upgrade']
));

if( $error )
{
	$output->error_box($msg_error);
}

$output->pparse('body');

?>
