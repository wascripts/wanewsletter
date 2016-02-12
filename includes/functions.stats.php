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
 * @param string $hexColor
 *
 * @return object
 */
function convertToRGB($hexColor)
{
	$pattern  = null;
	$hexColor = strtoupper($hexColor);
	$length   = strlen($hexColor);

	if ($length != 3 && $length != 6) {
		$hexColor = 'FFF';
		$length   = 3;
	}

	if ($length == 6) {
		$pattern = '/^#?([[:xdigit:]]{2})([[:xdigit:]]{2})([[:xdigit:]]{2})$/';
		$repeat  = 1;
	}
	else {
		$pattern = '/^#?([[:xdigit:]])([[:xdigit:]])([[:xdigit:]])$/';
		$repeat  = 2;
	}

	preg_match($pattern, $hexColor, $m);

	$parts = [];
	$parts['red']   = str_repeat($m[1], $repeat);
	$parts['green'] = str_repeat($m[2], $repeat);
	$parts['blue']  = str_repeat($m[3], $repeat);

	return (object) array_map('hexdec', $parts);
}

/**
 * Calcule les coordonnées du rayon
 *
 * @param float   $degre    Degré
 * @param integer $diametre Diamètre du cercle
 *
 * @return array
 */
function xy_arc($degre, $diametre)
{
	$x_arc = (cos($degre * (M_PI / 180.0)) * ($diametre / 2));
	$y_arc = (sin($degre * (M_PI / 180.0)) * ($diametre / 2));

	return [$x_arc, $y_arc];
}

/**
 * Créé le fichier de statistiques pour le mois et l'année donnés
 *
 * @param array   $listdata Données de la liste concernée
 * @param integer $month    Chiffre du mois
 * @param integer $year     Chiffre de l'année
 *
 * @return boolean
 */
function create_stats(array $listdata, $month, $year)
{
	global $db, $nl_config;

	if ($nl_config['disable_stats'] || !extension_loaded('gd')) {
		return false;
	}

	$filename = filename_stats(date('Y_F', mktime(0, 0, 0, $month, 1, $year)), $listdata['liste_id']);

	if ($fp = fopen(WA_STATSDIR . '/' . $filename, 'w')) {
		$stats    = [];
		$max_days = date('t', mktime(0, 0, 0, $month, 15, $year));

		for ($day = 1, $i = 0; $day <= $max_days; $day++, $i++) {
			$stats[$i] = 0;

			$min_time = mktime(0, 0, 0, $month, $day, $year);
			$max_time = mktime(23, 59, 59, $month, $day, $year);

			$sql = "SELECT COUNT(a.abo_id) AS num_abo
				FROM %s AS a
					INNER JOIN %s AS al ON al.abo_id = a.abo_id
						AND al.liste_id  = %d
						AND al.confirmed = %d
						AND ( al.register_date BETWEEN %d AND %d )
				WHERE a.abo_status = %d";
			$sql = sprintf($sql, ABONNES_TABLE, ABO_LISTE_TABLE,
				$listdata['liste_id'],
				SUBSCRIBE_CONFIRMED,
				$min_time,
				$max_time,
				ABO_ACTIVE
			);
			$result = $db->query($sql);
			$stats[$i] = $result->column('num_abo');
		}

		fwrite($fp, implode("\n", $stats));
		fclose($fp);

		return true;
	}
	else {
		return false;
	}
}

/**
 * Mise à jour des données pour les statistiques
 *
 * @param array $listdata Données de la liste concernée
 *
 * @return boolean
 */
function update_stats(array $listdata)
{
	global $nl_config;

	if ($nl_config['disable_stats'] || !extension_loaded('gd')) {
		return false;
	}

	$filename = filename_stats(date('Y_F'), $listdata['liste_id']);

	if (file_exists(WA_STATSDIR . '/' . $filename)) {
		if ($fp = fopen(WA_STATSDIR . '/' . $filename, 'r+')) {
			$stats  = clean_stats(fread($fp, filesize(WA_STATSDIR . '/' . $filename)));
			$offset = (date('j') - 1);
			$stats[$offset] += 1;
			fseek($fp, 0);
			fwrite($fp, implode("\n", $stats));
			fclose($fp);

			return true;
		}

		return false;
	}
	else {
		return create_stats($listdata, date('m'), date('Y'));
	}
}

/**
 * Suppression/déplacement de stats (lors de la suppression d'une liste)
 *
 * @param integer $liste_from Id de la liste dont on supprime/déplace les stats
 * @param mixed   $liste_to   Id de la liste de destination ou boolean (dans ce cas, on supprime)
 *
 * @return boolean
 */
function remove_stats($liste_from, $liste_to = false)
{
	global $nl_config;

	if ($nl_config['disable_stats'] || !extension_loaded('gd')) {
		return false;
	}

	if ($browse = dir(WA_STATSDIR . '/')) {
		$old_stats = [];

		while (($filename = $browse->read()) !== false) {
			if (preg_match("/^([0-9]{4}_[a-zA-Z]+)_list$liste_from\.txt$/i", $filename, $m)) {
				$filename = WA_STATSDIR . '/' . $filename;
				if ($liste_to && ($fp = fopen($filename, 'r'))) {
					$old_stats[$m[1]] = clean_stats(fread($fp, filesize($filename)));
					fclose($fp);
				}

				unlink($filename);
			}
		}
		$browse->close();

		if ($liste_to !== false) {
			foreach ($old_stats as $date => $stats_from) {
				$filename = WA_STATSDIR . '/' . filename_stats($date, $liste_to);

				if ($fp = fopen($filename, 'r+')) {
					$stats_to = clean_stats(fread($fp, filesize($filename)));

					for ($i = 0; $i < count($stats_to); $i++) {
						$stats_to[$i] += $stats_from[$i];
					}

					fseek($fp, 0);
					fwrite($fp, implode("\n", $stats_to));
					fclose($fp);
				}
			}
		}

		return true;
	}

	return false;
}

/**
 * Effectue les traitements adéquats sur la chaine et retourne un tableau
 *
 * @param string $contents Contenu du fichier des statistiques
 *
 * @return array
 */
function clean_stats($contents)
{
	$contents = preg_replace("/\r\n?/", "\n", trim($contents));

	return array_map('intval', explode("\n", $contents));
}

/**
 * Formatage des noms de fichiers de statistiques de Wanewsletter
 *
 * @param string  $date     Sous forme year_month (eg: 2005_April)
 * @param integer $liste_id
 *
 * @return string
 */
function filename_stats($date, $liste_id)
{
	return sprintf('%s_list%d.txt', $date, $liste_id);
}
