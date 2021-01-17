<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@webnaute.net>
 * @link      http://dev.webnaute.net/wanewsletter/
 * @copyright 2002-2021 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 *
 * /!\ Ce fichier doit être syntaxiquement valable avec PHP < 5.3 (pas de namespace, const, etc)
 */

// Check PHP version here to avoid parse error with PHP < 5.3 in install.inc.php
define('WA_PHP_VERSION_REQUIRED', '5.6.0');
if (!version_compare(PHP_VERSION, WA_PHP_VERSION_REQUIRED, '>=')) {
	printf("Your server is running PHP %s, but Wanewsletter requires PHP %s or higher",
		PHP_VERSION,
		WA_PHP_VERSION_REQUIRED
	);
	exit;
}

require './includes/install.inc.php';
