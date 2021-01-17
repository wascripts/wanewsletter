<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@webnaute.net>
 * @link      http://dev.webnaute.net/wanewsletter/
 * @copyright 2002-2021 Aurélien Maille
 * @license   https://www.gnu.org/licenses/gpl.html  GNU General Public License
 */

namespace Wanewsletter\Output;

use Wanewsletter\Error;
use Wanewsletter\Dblayer;

class CommandLine implements MessageInterface
{
	public static function formatError($error, $ansi = false)
	{
		global $db, $lang;

		$errno   = $error->getCode();
		$errstr  = $error->getMessage();
		$errfile = $error->getFile();
		$errline = $error->getLine();
		$backtrace = $error->getTrace();

		if ($error instanceof Error) {
			// Cas spécial. L'exception personnalisée a été créé dans wan_error_handler()
			// et contient donc l'appel à wan_error_handler() elle-même. On corrige.
			array_shift($backtrace);
		}

		$rootdir = dirname(dirname(__DIR__));

		foreach ($backtrace as $i => &$t) {
			if (!isset($t['file'])) {
				$t['file'] = 'unknown';
				$t['line'] = 0;
			}
			$file = htmlspecialchars(str_replace($rootdir, '~', $t['file']));
			$call = (isset($t['class']) ? $t['class'].$t['type'] : '') . $t['function'];
			$t = sprintf('#%d  %s() called at [%s:%d]', $i, $call, $file, $t['line']);
		}

		if (count($backtrace) > 0) {
			$backtrace = sprintf("<b>Backtrace:</b>\n%s\n", implode("\n", $backtrace));
		}
		else {
			$backtrace = '';
		}

		if (!empty($lang['Message'][$errstr])) {
			$errstr = $lang['Message'][$errstr];
		}

		if (!\Wanewsletter\wan_is_debug_enabled()) {
			// Si on est en mode de non-débogage, on a forcément attrapé une erreur
			// critique pour arriver ici.
			$message = $lang['Message']['Critical_error'];

			if ($errno == E_USER_ERROR) {
				$message = $errstr;
			}
		}
		else if ($error instanceof Dblayer\Exception) {
			if ($db instanceof Dblayer\Wadb && $db->sqlstate != '') {
				$errno = $db->sqlstate;
			}

			$message  = sprintf("<b>SQL errno:</b> %s\n", $errno);
			$message .= sprintf("<b>SQL error:</b> %s\n", htmlspecialchars($errstr));

			if ($db instanceof Dblayer\Wadb && $db->lastQuery != '') {
				$message .= sprintf("<b>SQL query:</b> %s\n", htmlspecialchars($db->lastQuery));
			}

			$message .= $backtrace;
		}
		else {
			$labels  = [
				E_NOTICE => 'PHP Notice',
				E_WARNING => 'PHP Warning',
				E_USER_ERROR => 'Error',
				E_USER_WARNING => 'Warning',
				E_USER_NOTICE => 'Notice',
				E_STRICT => 'PHP Strict',
				E_DEPRECATED => 'PHP Deprecated',
				E_USER_DEPRECATED => 'Deprecated',
				E_RECOVERABLE_ERROR => 'PHP Error'
			];

			$label   = (isset($labels[$errno])) ? $labels[$errno] : 'Unknown Error';
			$errfile = str_replace($rootdir, '~', $errfile);

			$message = sprintf(
				"<b>%s:</b> %s in <b>%s</b> on line <b>%d</b>\n",
				($error instanceof Error) ? $label : get_class($error),
				$errstr,
				$errfile,
				$errline
			);
			$message .= $backtrace;
		}

		if ($ansi) {
			$message = preg_replace("#<b>#",  "\033[1;31m", $message, 1);
			$message = preg_replace("#</b>#", "\033[0m", $message, 1);

			$message = preg_replace("#<b>#",  "\033[1;37m", $message);
			$message = preg_replace("#</b>#", "\033[0m", $message);
		}
		else {
			$message = preg_replace("#</?b>#", "", $message);
		}

		return htmlspecialchars_decode($message);
	}

	public function error($error)
	{
		$exit = true;
		if ($error instanceof \Throwable || $error instanceof \Exception) {
			if ($error instanceof Error) {
				if (!$error->isFatal() && $error->ignore()) {
					return null;
				}

				$exit = $error->isFatal();
			}

			$error = static::formatError($error,
				(function_exists('posix_isatty') && posix_isatty(STDOUT))
			);
		}

		fwrite(STDERR, rtrim($error)."\n");

		if ($exit) {
			exit(1);
		}
	}

	public function message($str = '', $is_error = false)
	{
		global $lang;

		if (!empty($lang['Message'][$str])) {
			$str = $lang['Message'][$str];
		}

		if ($is_error) {
			$fp = STDERR;
			$code = 1;
		}
		else {
			$fp = STDOUT;
			$code = 0;
		}

		$str = htmlspecialchars_decode(strip_tags($str));
		fwrite($fp, $str."\n");
		exit($code);
	}
}
