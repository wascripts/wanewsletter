<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

//
// Tables du script
//
define('ABO_LISTE_TABLE',     $prefixe . 'abo_liste');
define('ABONNES_TABLE',       $prefixe . 'abonnes');
define('ADMIN_TABLE',         $prefixe . 'admin');
define('AUTH_ADMIN_TABLE',    $prefixe . 'auth_admin');
define('BANLIST_TABLE',       $prefixe . 'ban_list');
define('CONFIG_TABLE',        $prefixe . 'config');
define('FORBIDDEN_EXT_TABLE', $prefixe . 'forbidden_ext');
define('JOINED_FILES_TABLE',  $prefixe . 'joined_files');
define('LISTE_TABLE',         $prefixe . 'liste');
define('LOG_TABLE',           $prefixe . 'log');
define('LOG_FILES_TABLE',     $prefixe . 'log_files');
define('SESSIONS_TABLE',      $prefixe . 'session');

$GLOBALS['supported_db'] = array(
	'mysql' => array(
		'label'        => 'MySQL',
		'Name'         => 'MySQL &#8805; 5.0.7',
		'extension'    => (extension_loaded('mysql') || extension_loaded('mysqli'))
	),
	'postgres' => array(
		'label'        => 'PostgreSQL',
		'Name'         => 'PostgreSQL &#8805; 8.3',
		'extension'    => extension_loaded('pgsql')
	),
	'sqlite' => array(
		'label'        => 'SQLite',
		'Name'         => 'SQLite 3',
		'extension'    => (class_exists('SQLite3') || (extension_loaded('pdo') && extension_loaded('pdo_sqlite'))
		)
	)
);

$GLOBALS['sql_schemas'] = array(
	ABO_LISTE_TABLE     => array(),
	ABONNES_TABLE       => array(),
	ADMIN_TABLE         => array(),
	AUTH_ADMIN_TABLE    => array(),
	BANLIST_TABLE       => array(),
	CONFIG_TABLE        => array(),
	FORBIDDEN_EXT_TABLE => array(),
	JOINED_FILES_TABLE  => array(),
	LISTE_TABLE         => array(),
	LOG_TABLE           => array(),
	LOG_FILES_TABLE     => array(),
	SESSIONS_TABLE      => array()
);

/**
 * Génère une chaîne DSN
 *
 * @param array $infos   Informations sur l'accès à la base de données
 * @param array $options Options de connexion
 *
 * @return string
 */
function createDSN($infos, $options = null)
{
	$connect = '';

	if ($infos['engine'] == 'sqlite') {
		$infos['host']   = null;
		$infos['user']   = null;
		$infos['dbname'] = $infos['path'];
	}

	if (!empty($infos['user'])) {
		$connect .= rawurlencode($infos['user']);

		if (!empty($infos['pass'])) {
			$connect .= ':' . rawurlencode($infos['pass']);
		}

		$connect .= '@';

		if (empty($infos['host'])) {
			$infos['host'] = 'localhost';
		}
	}

	if (!empty($infos['host'])) {
		$infos['host'] = trim($infos['host'], '[]');
		if (filter_var($infos['host'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			$infos['host'] = sprintf('[%s]', $infos['host']);
		}

		$connect .= $infos['host'];
		if (!empty($infos['port'])) {
			$connect .= ':' . intval($infos['port']);
		}
	}

	if (!empty($connect)) {
		$dsn = sprintf('%s://%s/%s', $infos['engine'], $connect, $infos['dbname']);
	}
	else {
		$dsn = sprintf('%s:%s', $infos['engine'], $infos['dbname']);
	}

	if (is_array($options) && count($options) > 0) {
		$dsn .= '?';
		$dsn .= http_build_query($options);
	}

	return $dsn;
}

/**
 * Décompose une chaîne DSN
 *
 * @param string $dsn
 *
 * @return boolean|array
 */
function parseDSN($dsn)
{
	global $supported_db;

	if (!($dsn_parts = parse_url($dsn)) || !isset($dsn_parts['scheme'])) {
		trigger_error("Invalid DSN argument", E_USER_ERROR);
		return false;
	}

	$infos = $options = array();

	foreach ($dsn_parts as $key => $value) {
		switch ($key) {
			case 'scheme':
				if (!isset($supported_db[$value])) {
					trigger_error("Unsupported database", E_USER_ERROR);
					return false;
				}

				$infos['label']  = $supported_db[$value]['label'];
				$infos['engine'] = $value;

				if ($value == 'mysql' && extension_loaded('mysqli')) {
					$value = 'mysqli';
				}

				$infos['driver'] = $value;
				break;

			case 'host':
				// trim brackets in case of IPv6
				$value = trim($value, '[]');
			case 'port':
				$infos[$key] = $value;
				break;

			case 'user':
			case 'pass':
				$infos[$key] = rawurldecode($value);
				break;

			case 'path':
				$infos['path']   = rawurldecode($value);
				$infos['dbname'] = basename($infos['path']);

				if ($infos['path'][0] != '/' && $infos['path'] != ':memory:') {
					$infos['path'] = WA_ROOTDIR . '/' . $infos['path'];
				}
				break;

			case 'query':
				parse_str($value, $options);
				break;
		}
	}

	if ($infos['engine'] == 'sqlite') {
		if (class_exists('SQLite3')) {
			$infos['driver'] = 'sqlite3';
		}
		else {
			if (!extension_loaded('pdo') || !extension_loaded('pdo_sqlite')) {
				trigger_error("No SQLite3 or PDO/SQLite extension loaded !", E_USER_ERROR);
			}
			$infos['driver'] = 'sqlitepdo';
		}

		if (is_readable($infos['path']) && filesize($infos['path']) > 0) {
			$fp = fopen($infos['path'], 'rb');
			$info = fread($fp, 15);
			fclose($fp);

			if (strcmp($info, 'SQLite format 3') !== 0) {
				trigger_error("Your database is not in SQLite format 3 !", E_USER_ERROR);
			}
		}
	}

	return array($infos, $options);
}

/**
 * Initialise la connexion à la base de données à partir d'une chaîne DSN
 *
 * @param string $dsn
 *
 * @return boolean|Dblayer\Wadb
 */
function WaDatabase($dsn)
{
	if (!($tmp = parseDSN($dsn))) {
		return false;
	}

	list($infos, $options) = $tmp;
	$dbclass = __NAMESPACE__ . '\\Dblayer\\' . ucfirst($infos['driver']);

	$infos['username'] = (isset($infos['user'])) ? $infos['user'] : null;
	$infos['passwd']   = (isset($infos['pass'])) ? $infos['pass'] : null;

	$db = new $dbclass();
	$db->connect($infos, $options);

	if (!empty($options['charset'])) {
		return $db;
	}

	//
	// Charset non précisé dans le DSN. On tente une auto-configuration.
	//
	if ($db::ENGINE != 'sqlite' && ($encoding = $db->encoding()) &&
		!preg_match('#^utf-?8$#i', $encoding)
	) {
		//
		// Wanewsletter utilise l'UTF-8 comme codage de caractères.
		// Si le jeu de caractères de la connexion est différent, on le change
		// arbitrairement pour l'UTF-8 et on affiche une alerte à l'utilisateur
		// en cas d'échec.
		//
		$newEncoding = 'utf8';
		$db->encoding($newEncoding);

		if (strcasecmp($encoding, $db->encoding()) === 0) {
			global $output;

			$message = <<<ERR
Wanewsletter a détecté que le <strong>jeu de caractères</strong>
de la connexion à votre base de données est <q>$encoding</q>.
Wanewsletter utilise l'UTF-8 comme codage de caractères et a donc tenté
de changer ce réglage, mais sans succès.<br />
Consultez la documentation de votre base de données pour trouver le réglage adéquat
et définir le paramètre charset dans la variable \$dsn du fichier de configuration
(consultez le fichier config.sample.inc.php pour voir un exemple de DSN configuré
de cette manière).
ERR;
			$output->displayMessage(str_replace("\n", " ", $message));
		}
	}

	return $db;
}

/**
 * Exécute une ou plusieurs requètes SQL sur la base de données
 *
 * @param mixed $queries Une ou plusieurs requètes SQL à exécuter
 */
function exec_queries(&$queries)
{
	global $db;

	if (!is_array($queries)) {
		$queries = array($queries);
	}

	foreach ($queries as $query) {
		if (!empty($query)) {
			$db->query($query);
		}
	}

	$queries = array();
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
	global $db, $prefixe, $sql_create, $sql_schemas;

	$schema = &$sql_schemas[$tablename];

	if (!empty($schema['updated'])) {
		return null;
	}

	$schema['updated'] = true;
	$columns = array();

	$result = $db->query(sprintf("PRAGMA table_info(%s)", $db->quote($tablename)));
	while ($row = $result->fetch()) {
		$columns[] = $row['name'];
	}

	$sql_update   = array();
	$sql_update[] = sprintf('ALTER TABLE %1$s RENAME TO %1$s_tmp;', $tablename);
	$sql_update   = array_merge($sql_update, $sql_create[$tablename]);

	if ($restore_data) {
		$sql_update[] = sprintf('INSERT INTO %1$s (%2$s) SELECT %2$s FROM %1$s_tmp;',
			$tablename,
			implode(',', $columns)
		);
	}

	$sql_update[] = sprintf('DROP TABLE %s_tmp;', $tablename);

	exec_queries($sql_update);
}
