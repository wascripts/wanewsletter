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
 * Construction de la liste déroulante des langues disponibles pour le script
 *
 * @param string $default_lang Langue actuellement utilisée
 *
 * @return string
 */
function lang_box($default_lang = '')
{
	global $output;

	$lang_names = [
		'fr' => 'francais',
		'en' => 'english'
	];

	$lang_list = [];
	$browse    = dir(WA_ROOTDIR . '/languages');

	while (($entry = $browse->read()) !== false) {
		if (is_dir(WA_ROOTDIR . '/languages/' . $entry) && preg_match('/^\w+(_\w+)?$/', $entry, $m)) {
			$lang_list[] = $m[0];
		}
	}
	$browse->close();

	if (count($lang_list) > 1) {
		$lang_box = '<select id="language" name="language">';
		foreach ($lang_list as $lang) {
			$selected  = $output->getBoolAttr('selected', ($default_lang == $lang));
			$lang_box .= sprintf('<option value="%1$s"%2$s>%3$s</option>',
				$lang,
				$selected,
				(isset($lang_names[$lang])) ? $lang_names[$lang] : $lang
			);
		}
		$lang_box .= '</select>';
	}
	else {
		$lang = array_pop($lang_list);
		$lang_box  = $lang_names[$lang];
		$lang_box .= '<input type="hidden" id="language" name="language" value="' . $lang . '" />';
	}

	return $lang_box;
}

/**
 * Construction de la liste déroulante des formats de newsletter
 *
 * @param string  $name     Nom de la liste déroulante
 * @param integer $default  Format par défaut
 * @param boolean $multiple True si on doit affiche également multi-format comme valeur
 *
 * @return string
 */
function format_box($name, $default = 0, $multiple = false)
{
	global $output;

	$get_option = function ($format, $label) use ($output, $default) {
		return sprintf('<option value="%d"%s>%s</option>',
			$format,
			$output->getBoolAttr('selected', ($default == $format)),
			$label
		);
	};

	$id_attr = '';
	if (preg_match('/^[a-z]([a-z0-9_-]*[a-z0-9])?$/', $name)) {
		$id_attr = sprintf(' id="%s"', $name);
	}

	$format_box  = sprintf('<select%s name="%s">', $id_attr, $name);
	$format_box .= $get_option(FORMAT_TEXT, 'texte');
	$format_box .= $get_option(FORMAT_HTML, 'html');

	if ($multiple) {
		$format_box .= $get_option(FORMAT_MULTIPLE, 'texte &amp; html');
	}

	$format_box .= '</select>';

	return $format_box;
}
