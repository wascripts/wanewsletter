<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 *
 * - Recherche les entrées orphelines dans les tables abonnes et abo_liste
 * et les efface, si demandé.
 * - Peut également effacer les fichiers présents dans le répertoire d'upload
 * sans correspondance dans les tables de fichiers joints ainsi que les entrées
 * dans les tables de fichiers pour lesquelles le fichier n'est plus présent
 */

namespace Wanewsletter;

//
// Ceci est un fichier de test ou d'aide lors du développement.
// Commentez les lignes suivantes uniquement si vous êtes sùr de ce que vous faites !
//
echo "This script has been disabled for security reasons\n";
exit(0);


define('WA_ROOTDIR', dirname(__DIR__));

require WA_ROOTDIR . '/includes/common.inc.php';

$db = WaDatabase($dsn);

load_settings();

$type = filter_input(INPUT_GET, 'type');
$action = filter_input(INPUT_GET, 'action');

if ($type != 'files' && $type != 'files2' && $type != 'subscribers') {
    $output->basic(
        '<h1>Outils&#160;:</h1>

<dl>
	<dt><a href="cleaner.php?type=files">Fichiers joints</a></dt>
	<dd>Supprime les entrées de la table wa_joined_files qui n\'ont pas de correspondance
	dans la table wa_log_files, et vice versa.</dd>
	<dt><a href="cleaner.php?type=files2">Fichiers joints (2)</a></dt>
	<dd>Supprime les entrées des tables wa_joined_files et wa_log_files si le fichier concerné
	n\'est plus présent dans le répertoire des fichiers joints, et vice versa</dd>
	<dt><a href="cleaner.php?type=subscribers">Abonnés</a></dt>
	<dd>Supprime les entrées de la table wa_abonnes qui n\'ont pas de correspondance
	dans la table wa_abo_liste, et vice versa.</dd>
</dl>

<p>Le script affiche le nombre d\'entrées concernées et demande confirmation avant de les supprimer.</p>');
}

if ($type == 'subscribers') {
    $sql = "SELECT abo_id
        FROM " . ABONNES_TABLE;
    $result = $db->query($sql);

    $abonnes_id = [];
    while ($abo_id = $result->column('abo_id')) {
        $abonnes_id[] = $abo_id;
    }

    $sql = "SELECT abo_id
        FROM " . ABO_LISTE_TABLE . "
        GROUP BY abo_id";
    $result = $db->query($sql);

    $abo_liste_id = [];
    while ($abo_id = $result->column('abo_id')) {
		$abo_liste_id[] = $abo_id;
    }

    $diff_1 = array_diff($abonnes_id, $abo_liste_id);
    $diff_2 = array_diff($abo_liste_id, $abonnes_id);

    $total_diff_1 = count($diff_1);
    $total_diff_2 = count($diff_2);

    if ($action == 'delete' && ($total_diff_1 > 0 || $total_diff_2 > 0)) {
        if ($total_diff_1 > 0) {
            $sql = "DELETE FROM " . ABONNES_TABLE . "
                WHERE abo_id IN(" . implode(', ', $diff_1) . ")";
            $db->query($sql);
        }

        if ($total_diff_2 > 0) {
            $sql = "DELETE FROM " . ABO_LISTE_TABLE . "
                WHERE abo_id IN(" . implode(', ', $diff_2) . ")";
            $db->query($sql);
        }

        $output->basic('Opération effectuée');
    }

    $data  = '<ul>';
    $data .= '<li>' . $total_diff_1 . ' entrées orphelines dans la table ' . ABONNES_TABLE . ' (' . implode(', ', $diff_1) . ')</li>';
    $data .= '<li>' . $total_diff_2 . ' entrées orphelines dans la table ' . ABO_LISTE_TABLE . ' (' . implode(', ', $diff_2) . ')</li>';
    $data .= '</ul>';

    if ($total_diff_1 > 0 || $total_diff_2 > 0) {
        $data .= '<p><a href="cleaner.php?type=subscribers&amp;action=delete">Effacer les entrées orphelines</a></p>';
    }

    $output->basic($data);
}
else if ($type == 'files') {
    $sql = "SELECT file_id
        FROM " . JOINED_FILES_TABLE;
    $result = $db->query($sql);

    $jf_id = [];
    while ($id = $result->column('file_id')) {
        $jf_id[] = $id;
    }

    $sql = "SELECT file_id
        FROM " . LOG_FILES_TABLE . "
        GROUP BY file_id";
    $result = $db->query($sql);

    $lf_id = [];
    while ($id = $result->column('file_id')) {
        $lf_id[] = $id;
    }

    $diff_1 = array_diff($jf_id, $lf_id);
    $diff_2 = array_diff($lf_id, $jf_id);

    $total_diff_1 = count($diff_1);
    $total_diff_2 = count($diff_2);

    if ($action == 'delete' && ($total_diff_1 > 0 || $total_diff_2 > 0)) {
        if ($total_diff_1 > 0) {
            $sql = "DELETE FROM " . JOINED_FILES_TABLE . "
                WHERE file_id IN(" . implode(', ', $diff_1) . ")";
            $db->query($sql);
        }

        if ($total_diff_2 > 0) {
            $sql = "DELETE FROM " . LOG_FILES_TABLE . "
                WHERE file_id IN(" . implode(', ', $diff_2) . ")";
            $db->query($sql);
        }

        $output->basic('Opération effectuée');
    }

    $data  = '<ul>';
    $data .= '<li>' . $total_diff_1 . ' entrées orphelines dans la table ' . JOINED_FILES_TABLE . ' (ids: ' . implode(', ', $diff_1) . ')</li>';
    $data .= '<li>' . $total_diff_2 . ' entrées orphelines dans la table ' . LOG_FILES_TABLE . ' (ids: ' . implode(', ', $diff_2) . ')</li>';
    $data .= '</ul>';

    if ($total_diff_1 > 0 || $total_diff_2 > 0) {
        $data .= '<p><a href="cleaner.php?type=files&amp;action=delete">Effacer les entrées orphelines</p>';
    }

    $output->basic($data);
}
else if ($type == 'files2') {
	$sql = "SELECT file_id, file_physical_name
		FROM " . JOINED_FILES_TABLE;
	$result = $db->query($sql);

	$upload_path    = WA_ROOTDIR . '/' . $nl_config['upload_path'];
	$sql_delete_ids = [];
	$joined_files   = [];
	$delete_files   = [];
	while ($row = $result->fetch()) {
		if (!file_exists($upload_path . $row['file_physical_name'])) {
			$sql_delete_ids[] = $row['file_id'];
		}

		$joined_files[] = $row['file_physical_name'];
	}

	$browse = dir($upload_path);
	while (($entry = $browse->read()) !== false) {
		if (is_file($upload_path . $entry) && $entry != 'index.html' && !in_array($entry, $joined_files)) {
			$delete_files[] = $entry;

			if ($action == 'delete') {
				unlink($upload_path . $entry);
			}
		}
	}

	if ($action == 'delete') {
		if (count($sql_delete_ids) > 0) {
			$db->beginTransaction();

			$sql = "DELETE FROM " . JOINED_FILES_TABLE . "
				WHERE file_id IN(" . implode(', ', $sql_delete_ids) . ")";
			$db->query($sql);

			$sql = "DELETE FROM " . LOG_FILES_TABLE . "
				WHERE file_id IN(" . implode(', ', $sql_delete_ids) . ")";
			$db->query($sql);

			$db->commit();
		}

        $output->basic('Opération effectuée');
    }

    $data  = '<ul>';
    $data .= '<li>' . count($sql_delete_ids) . ' fichiers manquants (ids: ' . implode(', ', $sql_delete_ids) . ')</li>';
    $data .= '<li>' . count($delete_files) . ' fichiers dans "' . $nl_config['upload_path'] . '" sans entrée correspondante dans la base de données (fichiers: ' . implode(', ', $delete_files) . ')</li>';
    $data .= '</ul>';

    if (count($sql_delete_ids) > 0 || count($delete_files) > 0) {
        $data .= '<p><a href="cleaner.php?type=files2&amp;action=delete">Effacer les entrées orphelines</p>';
    }

    $output->basic($data);
}

exit(0);
