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
 * parse_str() est affecté par l’option 'filter.default', qui peut valoir
 * 'magic_quotes'.
 * L’appel à cette fonction sans l’argument $arr n’est pas supporté.
 *
 * @param string $str
 * @param array  $arr
 */
function parse_str($str, &$arr)
{
	\parse_str($str, $arr);
	strip_magic_quotes($arr);
}
