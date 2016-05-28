<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

require '../includes/common.inc.php';

//
// Initialisation de la connexion à la base de données et récupération de la configuration
//
$db = WaDatabase($dsn);
$nl_config = wa_get_config();

load_l10n();

$liste_ids = trim(filter_input(INPUT_GET, 'liste'));
$liste_ids = array_unique(array_map('intval', explode(' ', $liste_ids)));

if (count($liste_ids) > 0) {
	$sql = "SELECT COUNT(a.abo_id) AS num_subscribe
		FROM " . ABONNES_TABLE . " AS a
		WHERE a.abo_id IN(
				SELECT al.abo_id
				FROM " . ABO_LISTE_TABLE . " AS al
				WHERE al.liste_id IN(" . implode(', ', $liste_ids) . ")
					AND al.confirmed = " . SUBSCRIBE_CONFIRMED . "
			)
			AND a.abo_status = " . ABO_ACTIVE;
	$result = $db->query($sql);
	$data   = $result->column('num_subscribe');
}
else {
	$data   = '-1';
}

if (filter_input(INPUT_GET, 'output') == 'json') {
	header('Content-Type: application/json');

	printf('{"numSubscribe":"%d"}', $data);
}
else {
	header('Content-Type: application/x-javascript');

	$varname = trim(filter_input(INPUT_GET, 'use-variable'));

	if ($varname) {
		if (!preg_match('/^[A-Za-z0-9_.$\\\\]+$/', $varname)) {
			$varname = 'var numSubscribe';
			echo "console.log('Rejected variable name. Accepted chars are [A-Za-z0-9_.\$\\\\].');\n";
		}

		printf("%s = %d;\n", $varname, $data);
	}
	else {
		printf("document.write('%d');\n", $data);
	}
}
