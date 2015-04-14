<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

if (PHP_VERSION_ID < 50400) {
	/**
	 * Idem que la fonction htmlspecialchars() native, avec le paramètre $encoding
	 * par défaut à UTF-8. Ce n'est le cas en natif qu'à partir de PHP 5.4.
	 *
	 * @param string $string
	 * @param int    $flags
	 * @param string $encoding
	 * @param bool   $double_encode
	 *
	 * @return string
	 */
	function htmlspecialchars($string, $flags = null, $encoding = null, $double_encode = true)
	{
		if (is_null($flags)) {
			$flags = ENT_COMPAT | ENT_HTML401;
		}

		if (is_null($encoding)) {
			$encoding = 'UTF-8';
		}

		return \htmlspecialchars($string, $flags, $encoding, $double_encode);
	}

	/**
	 * Idem que la fonction html_entity_decode() native, avec le paramètre $encoding
	 * par défaut à UTF-8. Ce n'est le cas en natif qu'à partir de PHP 5.4.
	 *
	 * @param string $string
	 * @param int    $flags
	 * @param string $encoding
	 *
	 * @return string
	 */
	function html_entity_decode($string, $flags = null, $encoding = null)
	{
		if (is_null($flags)) {
			$flags = ENT_COMPAT | ENT_HTML401;
		}

		if (is_null($encoding)) {
			$encoding = 'UTF-8';
		}

		return \html_entity_decode($string, $flags, $encoding);
	}
}

/**
 * parse_str() est affecté par l’option 'magic_quotes_gpc', ainsi que par
 * l’option 'filter.default', qui peut valoir 'magic_quotes'.
 * L’appel à cette fonction sans l’argument $arr n’est pas supporté.
 *
 * @param string $str
 * @param array  $arr
 */
function parse_str($str, &$arr)
{
	\parse_str($str, $arr);
	strip_magic_quotes_gpc($arr);
}
