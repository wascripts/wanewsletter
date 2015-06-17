<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

// PHP 5.6.0
// Déclaration également présente dans la librairie phpass pour la portabilité de la librairie
if (!function_exists('hash_equals')) {
	/**
	 * Timing attack safe string comparison
	 * Native function in PHP >= 5.6.0
	 *
	 * Compares two strings using the same time whether they're equal or not.
	 * This function should be used to mitigate timing attacks; for instance,
	 * when testing crypt() password hashes.
	 *
	 * Notes:
	 * - Both arguments must be of the same length to be compared successfully.
	 *   When arguments of differing length are supplied, FALSE is returned and
	 *   the length of the known string may be leaked in case of a timing attack.
	 * - It is important to provide the user-supplied string as the second parameter, rather than the first.
	 *
	 * @link http://www.php.net/hash_equals
	 * @link https://github.com/php/php-src/blob/PHP-5.6.0/ext/hash/hash.c#L731
	 *
	 * @param string $known_str The string of known length to compare against
	 * @param string $user_str  The user-supplied string
	 *
	 * @return boolean Returns TRUE when the two strings are equal, FALSE otherwise.
	 */
	function hash_equals($known_str, $user_str)
	{
		if (func_num_args() !== 2) {
			trigger_error('hash_equals() expects exactly 2 parameters, ' . func_num_args() . ' given', E_USER_WARNING);
			return null;
		}
		if (is_string($known_str) !== true) {
			trigger_error('hash_equals(): Expected known_str to be a string, ' . gettype($known_str) . ' given', E_USER_WARNING);
			return false;
		}
		if (is_string($user_str) !== true) {
			trigger_error('hash_equals(): Expected user_str to be a string, ' . gettype($user_str) . ' given', E_USER_WARNING);
			return false;
		}

		$known_str_len = strlen($known_str);
		$user_str_len  = strlen($user_str);

		if ($known_str_len != $user_str_len) {
			return false;
		}

		$result = 0;
		for ($i = 0; $i < $known_str_len; $i++) {
			$result |= $known_str[$i] ^ $user_str[$i];
		}

		return (0 === $result);
	}
}
