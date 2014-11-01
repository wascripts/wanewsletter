<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if (!defined('SQLPARSER_INC')) {

define('SQLPARSER_INC', true);

/**
 * Parse un fichier contenant une liste de requète et
 * renvoie un tableau avec une requète par entrée
 *
 * @param string $input   Contenu du fichier .sql
 * @param string $prefixe Préfixe des tables à mettre à la place du prefixe par défaut
 *
 * @return array
 */
function parseSQL($input, $prefixe = '')
{
	$tmp            = '';
	$output         = array();
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

		if ($between_quotes || (
				!$in_comments &&
				strlen($lines[$i]) > 0 &&
				$lines[$i][0] != '#' &&
				!preg_match('/^--(\s|$)/', $lines[$i])
			)
		) {
			//
			// Nombre de simple quotes non échappés
			//
			$unescaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*'/", $lines[$i], $matches);

			if ((!$between_quotes && !($unescaped_quotes % 2)) ||
				($between_quotes && ($unescaped_quotes % 2))
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

		//
		// Pour tenter de ménager la mémoire
		//
		unset($lines[$i]);
	}

	if ($prefixe != '') {
		$output = str_replace('wa_', $prefixe, $output);
	}

	//
	// Pour tenter de ménager la mémoire
	//
	unset($input, $lines);

	return $output;
}

}
