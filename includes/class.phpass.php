<?php
/**
 * Portable PHP password hashing framework.
 * @package   phpass
 * @version   0.3 / bobe
 * @author    Solar Designer <solar at openwall.com>
 * @link      http://www.openwall.com/phpass/
 * @link      https://github.com/bobe17/phpass
 *
 * Modifications:
 * - Nommage des méthodes en camelCase
 * - Utilisation de uniqid() pour l'initialisation de $random_state (repris de
 *   l'adaptation de phpass dans Wordpress)
 * - Les arguments du constructeur sont rendus optionnels (le coût est fixé par défaut à 10)
 * - La classe tente par défaut d'utiliser crypt() avant de se rabattre sur les hashages portables
 * - Ajout de la propriété $blowfish_mode. Utilisation du mode '2y' avec fallback
 *   vers le mode '2a' si PHP < 5.3.7
 * - Utilisation si possible de la fonction openssl_random_pseudo_bytes() dans PasswordHash::getRandomBytes()
 * - Ajout d'une fonction de compatibilité hash_equals() pour PHP < 5.6.0
 * - Utilisation en deuxième alternative de mcrypt_create_iv() dans PasswordHash::getRandomBytes()
 */

#
# Portable PHP password hashing framework.
#
# Version 0.3 / genuine.
#
# Written by Solar Designer <solar at openwall.com> in 2004-2006 and placed in
# the public domain.  Revised in subsequent years, still public domain.
#
# There's absolutely no warranty.
#
# The homepage URL for this framework is:
#
#	http://www.openwall.com/phpass/
#
# Please be sure to update the Version line if you edit this file in any way.
# It is suggested that you leave the main version number intact, but indicate
# your project name (after the slash) and add your own revision information.
#
# Please do not change the "private" password hashing method implemented in
# here, thereby making your hashes incompatible.  However, if you must, please
# change the hash type identifier (the "$P$") to something different.
#
# Obviously, since this code is in the public domain, the above are not
# requirements (there can be none), but merely suggestions.
#
class PasswordHash {
	var $itoa64;
	var $blowfish_mode = '2y';
	var $iteration_count_log2 = 10;
	var $portable_hashes;
	var $random_state;

	function PasswordHash($iteration_count_log2 = null, $portable_hashes = false)
	{
		$this->itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

		if (is_int($iteration_count_log2) && $iteration_count_log2 >= 4 && $iteration_count_log2 <= 31) {
			$this->iteration_count_log2 = $iteration_count_log2;
		}

		$this->portable_hashes = $portable_hashes;
		$this->random_state = microtime().uniqid(rand(), true);

		if (version_compare(PHP_VERSION, '5.3.7', '<')) {
			$this->blowfish_mode = '2a';
		}
	}

	function getRandomBytes($count)
	{
		$is_win = (strncasecmp(PHP_OS, 'WIN', 3) === 0);
		$output = '';

		if (function_exists('openssl_random_pseudo_bytes')
			&& (!$is_win || version_compare(PHP_VERSION, '5.3.4', '>=')))
		{
			$output = openssl_random_pseudo_bytes($count);
		}
		else if (function_exists('mcrypt_create_iv')
			&& (!$is_win || version_compare(PHP_VERSION, '5.3.7', '>=')))
		{
			$output = mcrypt_create_iv($count, MCRYPT_DEV_URANDOM);
		}
		else if (@is_readable('/dev/urandom')
			&& ($fh = @fopen('/dev/urandom', 'rb')))
		{
			$output = fread($fh, $count);
			fclose($fh);
		}

		if (strlen($output) < $count) {
			$output = '';
			for ($i = 0; $i < $count; $i += 16) {
				$this->random_state =
				    md5(microtime() . $this->random_state);
				$output .=
				    pack('H*', md5($this->random_state));
			}
			$output = substr($output, 0, $count);
		}

		return $output;
	}

	function encode64($input, $count)
	{
		$output = '';
		$i = 0;
		do {
			$value = ord($input[$i++]);
			$output .= $this->itoa64[$value & 0x3f];
			if ($i < $count)
				$value |= ord($input[$i]) << 8;
			$output .= $this->itoa64[($value >> 6) & 0x3f];
			if ($i++ >= $count)
				break;
			if ($i < $count)
				$value |= ord($input[$i]) << 16;
			$output .= $this->itoa64[($value >> 12) & 0x3f];
			if ($i++ >= $count)
				break;
			$output .= $this->itoa64[($value >> 18) & 0x3f];
		} while ($i < $count);

		return $output;
	}

	function gensaltPrivate($input)
	{
		$output = '$P$';
		$output .= $this->itoa64[min($this->iteration_count_log2 +
			((PHP_VERSION >= '5') ? 5 : 3), 30)];
		$output .= $this->encode64($input, 6);

		return $output;
	}

	function cryptPrivate($password, $setting)
	{
		$output = '*0';
		if (substr($setting, 0, 2) == $output)
			$output = '*1';

		$id = substr($setting, 0, 3);
		# We use "$P$", phpBB3 uses "$H$" for the same thing
		if ($id != '$P$' && $id != '$H$')
			return $output;

		$count_log2 = strpos($this->itoa64, $setting[3]);
		if ($count_log2 < 7 || $count_log2 > 30)
			return $output;

		$count = 1 << $count_log2;

		$salt = substr($setting, 4, 8);
		if (strlen($salt) != 8)
			return $output;

		# We're kind of forced to use MD5 here since it's the only
		# cryptographic primitive available in all versions of PHP
		# currently in use.  To implement our own low-level crypto
		# in PHP would result in much worse performance and
		# consequently in lower iteration counts and hashes that are
		# quicker to crack (by non-PHP code).
		if (PHP_VERSION >= '5') {
			$hash = md5($salt . $password, TRUE);
			do {
				$hash = md5($hash . $password, TRUE);
			} while (--$count);
		} else {
			$hash = pack('H*', md5($salt . $password));
			do {
				$hash = pack('H*', md5($hash . $password));
			} while (--$count);
		}

		$output = substr($setting, 0, 12);
		$output .= $this->encode64($hash, 16);

		return $output;
	}

	function gensaltExtended($input)
	{
		$count_log2 = min($this->iteration_count_log2 + 8, 24);
		# This should be odd to not reveal weak DES keys, and the
		# maximum valid value is (2**24 - 1) which is odd anyway.
		$count = (1 << $count_log2) - 1;

		$output = '_';
		$output .= $this->itoa64[$count & 0x3f];
		$output .= $this->itoa64[($count >> 6) & 0x3f];
		$output .= $this->itoa64[($count >> 12) & 0x3f];
		$output .= $this->itoa64[($count >> 18) & 0x3f];

		$output .= $this->encode64($input, 3);

		return $output;
	}

	function gensaltBlowfish($input)
	{
		# This one needs to use a different order of characters and a
		# different encoding scheme from the one in encode64() above.
		# We care because the last character in our encoded string will
		# only represent 2 bits.  While two known implementations of
		# bcrypt will happily accept and correct a salt string which
		# has the 4 unused bits set to non-zero, we do not want to take
		# chances and we also do not want to waste an additional byte
		# of entropy.
		$itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

		$output = sprintf('$%s$%02d$', $this->blowfish_mode, $this->iteration_count_log2);

		$i = 0;
		do {
			$c1 = ord($input[$i++]);
			$output .= $itoa64[$c1 >> 2];
			$c1 = ($c1 & 0x03) << 4;
			if ($i >= 16) {
				$output .= $itoa64[$c1];
				break;
			}

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 4;
			$output .= $itoa64[$c1];
			$c1 = ($c2 & 0x0f) << 2;

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 6;
			$output .= $itoa64[$c1];
			$output .= $itoa64[$c2 & 0x3f];
		} while (1);

		return $output;
	}

	function hash($password)
	{
		$random = '';

		if (CRYPT_BLOWFISH == 1 && !$this->portable_hashes) {
			$random = $this->getRandomBytes(16);
			$hash =
			    crypt($password, $this->gensaltBlowfish($random));
			if (strlen($hash) == 60)
				return $hash;
		}

		if (CRYPT_EXT_DES == 1 && !$this->portable_hashes) {
			if (strlen($random) < 3)
				$random = $this->getRandomBytes(3);
			$hash =
			    crypt($password, $this->gensaltExtended($random));
			if (strlen($hash) == 20)
				return $hash;
		}

		if (strlen($random) < 6)
			$random = $this->getRandomBytes(6);
		$hash =
		    $this->cryptPrivate($password,
		    $this->gensaltPrivate($random));
		if (strlen($hash) == 34)
			return $hash;

		# Returning '*' on error is safe here, but would _not_ be safe
		# in a crypt(3)-like function used _both_ for generating new
		# hashes and for validating passwords against existing hashes.
		return '*';
	}

	function check($password, $stored_hash)
	{
		$hash = $this->cryptPrivate($password, $stored_hash);
		if ($hash[0] == '*')
			$hash = crypt($password, $stored_hash);

		return hash_equals($stored_hash, $hash);
	}
}

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
