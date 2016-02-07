<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

// PHP 5.5.0
/**
 * This is part of the array_column library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Ben Ramsey (http://benramsey.com)
 * @license http://opensource.org/licenses/MIT MIT
 */
if (!function_exists('array_column')) {
    /**
     * Returns the values from a single column of the input array, identified by
     * the $columnKey.
     *
     * Optionally, you may provide an $indexKey to index the values in the returned
     * array by the values from the $indexKey column in the input array.
     *
     * @param array $input A multi-dimensional array (record set) from which to pull
     *                     a column of values.
     * @param mixed $columnKey The column of values to return. This value may be the
     *                         integer key of the column you wish to retrieve, or it
     *                         may be the string key name for an associative array.
     * @param mixed $indexKey (Optional.) The column to use as the index/keys for
     *                        the returned array. This value may be the integer key
     *                        of the column, or it may be the string key name.
     * @return array
     */
    function array_column($input = null, $columnKey = null, $indexKey = null)
    {
        // Using func_get_args() in order to check for proper number of
        // parameters and trigger errors exactly as the built-in array_column()
        // does in PHP 5.5.
        $argc = func_num_args();
        $params = func_get_args();

        if ($argc < 2) {
            trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
            return null;
        }

        if (!is_array($params[0])) {
            trigger_error(
                'array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given',
                E_USER_WARNING
            );
            return null;
        }

        if (!is_int($params[1])
            && !is_float($params[1])
            && !is_string($params[1])
            && $params[1] !== null
            && !(is_object($params[1]) && method_exists($params[1], '__toString'))
        ) {
            trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
            return false;
        }

        if (isset($params[2])
            && !is_int($params[2])
            && !is_float($params[2])
            && !is_string($params[2])
            && !(is_object($params[2]) && method_exists($params[2], '__toString'))
        ) {
            trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
            return false;
        }

        $paramsInput = $params[0];
        $paramsColumnKey = ($params[1] !== null) ? (string) $params[1] : null;

        $paramsIndexKey = null;
        if (isset($params[2])) {
            if (is_float($params[2]) || is_int($params[2])) {
                $paramsIndexKey = (int) $params[2];
            }
            else {
                $paramsIndexKey = (string) $params[2];
            }
        }

        $resultArray = array();

        foreach ($paramsInput as $row) {
            $key = $value = null;
            $keySet = $valueSet = false;

            if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
                $keySet = true;
                $key = (string) $row[$paramsIndexKey];
            }

            if ($paramsColumnKey === null) {
                $valueSet = true;
                $value = $row;
            }
            else if (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
                $valueSet = true;
                $value = $row[$paramsColumnKey];
            }

            if ($valueSet) {
                if ($keySet) {
                    $resultArray[$key] = $value;
                }
                else {
                    $resultArray[] = $value;
                }
            }

        }

        return $resultArray;
    }

}

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
