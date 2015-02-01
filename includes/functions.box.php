<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if (!defined('FUNCTIONS_BOX_INC')) {

define('FUNCTIONS_BOX_INC', true);

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

	$lang_ary = array();
	$browse   = dir(WA_ROOTDIR . '/language');

	while (($entry = $browse->read()) !== false) {
		if (preg_match('/^lang_([\w_-]+)\.php$/', $entry, $m)) {
			$lang_ary[] = $m[1];
		}
	}
	$browse->close();

	if (count($lang_ary) > 1) {
		asort($lang_ary);

		$lang_box = '<select id="language" name="language">';
		foreach ($lang_ary as $lang_name) {
			$selected  = $output->getBoolAttr('selected', ($default_lang == $lang_name));
			$lang_box .= sprintf('<option value="%1$s"%2$s>%1$s</option>', $lang_name, $selected);
		}
		$lang_box .= '</select>';
	}
	else {
		$lang_box = '<span class="notice">' . $lang_ary[0]
			. '<input type="hidden" id="language" name="language" value="' . $lang_ary[0] . '" />';
	}

	return $lang_box;
}

/**
 * Construction de la liste déroulante des formats de newsletter
 *
 * @param string  $select_name    Nom de la liste déroulante
 * @param integer $default_format Format par défaut
 * @param boolean $option_submit  True si submit lors du changement de valeur de la liste
 * @param boolean $multi_format   True si on doit affiche également multi-format comme valeur
 * @param boolean $no_id          True pour ne pas mettre d'attribut id à la balise <select>
 *
 * @return string
 */
function format_box($select_name, $default_format = 0, $option_submit = false, $multi_format = false, $no_id = false)
{
	global $output;

	$format_box = '<select' . (!$no_id ? ' id="' . $select_name . '"' : '') . ' name="' . $select_name . '"';

	if ($option_submit) {
		$format_box .= '>';//' onchange="this.form.submit();">';
	}
	else {
		$format_box .= '>';
	}

	$format_box .= '<option value="1"' . $output->getBoolAttr('selected', ($default_format == FORMAT_TEXTE)) . '>texte</option>';
	$format_box .= '<option value="2"' . $output->getBoolAttr('selected', ($default_format == FORMAT_HTML)) . '>html</option>';

	if ($multi_format) {
		$format_box .= '<option value="3"' . $output->getBoolAttr('selected', ($default_format == FORMAT_MULTIPLE)) . '>texte &amp; html</option>';
	}

	$format_box .= '</select>';

	return $format_box;
}

}
