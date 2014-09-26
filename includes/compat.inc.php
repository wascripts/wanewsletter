<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

if( !defined('COMPAT_PHP_INC') ) {

define('COMPAT_PHP_INC', true);

if( version_compare(PHP_VERSION, '5.3.0', '<') ) {
	define('E_DEPRECATED', 8192);
	define('E_USER_DEPRECATED', 16384);
}

if( version_compare(PHP_VERSION, '5.4.0', '<') ) {
	define('ENT_HTML401', 0);
}

}// end if !defined
