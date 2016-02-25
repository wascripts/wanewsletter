<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

/**
 * Retourne la liste des types de base de données supportées par
 * Wanewsletter et par cette installation de PHP.
 *
 * @return array
 */
function get_supported_db()
{
	static $supported_db;

	if (is_null($supported_db)) {
		$supported_db = [];

		if (extension_loaded('mysql') || extension_loaded('mysqli')) {
			$supported_db['mysql'] = [
				'label' => 'MySQL',
				'Name'  => 'MySQL ≥ 5.0.7'
			];
		}

		if (extension_loaded('pgsql')) {
			$supported_db['postgres'] = [
				'label' => 'PostgreSQL',
				'Name'  => 'PostgreSQL ≥ 8.3'
			];
		}

		if (class_exists('SQLite3') || (extension_loaded('pdo') && extension_loaded('pdo_sqlite'))) {
			$supported_db['sqlite'] = [
				'label' => 'SQLite',
				'Name'  => 'SQLite 3'
			];
		}
	}

	return $supported_db;
}

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
 * Décompose une chaîne Data Source Name
 *
 * @param string $dsn
 *
 * @return array
 */
function parseDSN($dsn)
{
	if (!($dsn_parts = parse_url($dsn)) || !isset($dsn_parts['scheme'])) {
		trigger_error("Invalid DSN argument", E_USER_ERROR);
	}

	$infos = $options = [];

	foreach ($dsn_parts as $key => $value) {
		switch ($key) {
			case 'scheme':
				$supported_db = get_supported_db();
				if (!isset($supported_db[$value])) {
					trigger_error("Unsupported database", E_USER_ERROR);
				}

				$infos['label']  = $supported_db[$value]['label'];
				$infos['engine'] = $value;
				$infos['driver'] = ucfirst($value);
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

	if ($infos['engine'] == 'mysql' && extension_loaded('mysqli')) {
		$infos['driver'] = 'Mysqli';
	}
	else if ($infos['engine'] == 'sqlite') {
		if (class_exists('SQLite3')) {
			$infos['driver'] = 'Sqlite3';
		}
		else {
			if (!extension_loaded('pdo') || !extension_loaded('pdo_sqlite')) {
				trigger_error("No SQLite3 or PDO/SQLite extension loaded !", E_USER_ERROR);
			}
			$infos['driver'] = 'SqlitePdo';
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

	return [$infos, $options];
}

/**
 * Initialise la connexion à la base de données à partir d'une chaîne DSN
 *
 * @param string $dsn
 *
 * @return Dblayer\Wadb
 */
function WaDatabase($dsn)
{
	list($infos, $options) = parseDSN($dsn);
	$dbclass = sprintf('%s\\Dblayer\\%s', __NAMESPACE__, $infos['driver']);

	$infos['username'] = (isset($infos['user'])) ? $infos['user'] : null;
	$infos['passwd']   = (isset($infos['pass'])) ? $infos['pass'] : null;

	// Timeout de connexion
	if (empty($options['timeout'])) {
		$options['timeout'] = 5;
	}

	$db = new $dbclass();
	$db->connect($infos, $options);

	if (!empty($options['charset'])) {
		return $db;
	}

	//
	// Charset non précisé dans le DSN. On tente une auto-configuration.
	//
	if ($db::ENGINE != 'sqlite' && ($encoding = $db->encoding())
		&& !preg_match('#^utf-?8$#i', $encoding)
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
			$output->error($message);
		}
	}

	return $db;
}

/**
 * Exécute une ou plusieurs requètes SQL sur la base de données
 *
 * @param array $queries Une ou plusieurs requètes SQL à exécuter
 */
function exec_queries(array &$queries)
{
	global $db;

	foreach ($queries as $query) {
		if (!empty($query)) {
			$db->query($query);
		}
	}

	$queries = [];
}

/**
 * Analyse un fichier contenant une liste de requètes SQL séparées par un ';'
 * et retourne un tableau de requètes.
 *
 * @param string $input   Chaîne à analyser
 * @param string $prefixe Préfixe des tables à mettre à la place du prefixe par défaut
 *
 * @return array
 */
function parse_sql($input, $prefixe = null)
{
	$tmp            = '';
	$output         = [];
	$in_comments    = false;
	$between_quotes = false;

	$lines       = preg_split("/(\r\n?|\n)/", $input, -1, PREG_SPLIT_DELIM_CAPTURE);
	$total_lines = count($lines);

	for ($i = 0; $i < $total_lines; $i++) {
		if (preg_match("/^\r\n?|\n$/", $lines[$i])) {
			if ($between_quotes) {
				$tmp .= $lines[$i];
			}
			else {
				$tmp .= ' ';
			}

			continue;
		}

		//
		// Si on est pas dans des simples quotes, on vérifie si on entre ds des commentaires
		//
		if (!$between_quotes && !$in_comments && preg_match('/^\/\*/', $lines[$i])) {
			$in_comments = true;
		}

		if ($between_quotes
			|| (
				!$in_comments
				&& strlen($lines[$i]) > 0
				&& $lines[$i][0] != '#'
				&& !preg_match('/^--(\s|$)/', $lines[$i])
			)
		) {
			//
			// Nombre de simple quotes non échappés
			//
			$unescaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*'/", $lines[$i]);

			if ((!$between_quotes && !($unescaped_quotes % 2))
				|| ($between_quotes && ($unescaped_quotes % 2))
			) {
				if (preg_match('/;\s*$/i', $lines[$i])) {
					$lines[$i] = ($tmp != '') ? rtrim($lines[$i]) : trim($lines[$i]);
					$output[]  = $tmp . substr($lines[$i], 0, -1);

					$tmp = '';
				}
				else {
					$tmp .= ($tmp != '') ? $lines[$i] : ltrim($lines[$i]);
				}

				$between_quotes = false;
			}
			else {
				$between_quotes = true;
				$tmp .= ($tmp != '') ? $lines[$i] : ltrim($lines[$i]);
			}
		}

		if (!$between_quotes && $in_comments && preg_match('/\*\/$/', rtrim($lines[$i]))) {
			$in_comments = false;
		}
	}

	if ($prefixe) {
		$output = str_replace('wa_', $prefixe, $output);
	}

	return $output;
}
