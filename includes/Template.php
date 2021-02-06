<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@webnaute.net>
 * @link      http://dev.webnaute.net/wanewsletter/
 * @copyright 2002-2021 Aurélien Maille
 * @license   https://www.gnu.org/licenses/gpl.html  GNU General Public License
 *
 * Originellement inspiré de la classe Template de phpBB 2.0.x,
 * elle-même inspirée du module template de la phpLib.
 */

namespace Wanewsletter;

class Template {
	/**
	 * Répertoire par défaut des templates.
	 * @var string
	 */
	protected static $tpldir = 'templates';

	/**
	 * Répertoire des fichiers mis en cache.
	 * @var string
	 */
	protected static $cachedir = 'cache';

	/**
	 * Utilisation du cache.
	 * @var boolean
	 */
	private static $useCache = false;

	/**
	 * Nom du fichier template utilisé.
	 * @var string
	 */
	private $filename        = '';

	/**
	 * Variables à assigner au template.
	 * @var array
	 */
	private $tpldata         = [];

	/**
	 * Code non compilé.
	 * @var string
	 */
	private $uncompiled_code = null;

	/**
	 * Code compilé à exécuter pour obtenir la sortie à afficher.
	 * @var string
	 */
	private $compiled_code   = null;

	/**
	 * @param string $filename Nom du fichier temmplate à utiliser
	 *
	 * @throws Exception
	 */
	public function __construct($filename = '')
	{
		$this->filename = $filename;
	}

	/**
	 * @param string $dir Répertoire de stockage des templates
	 *
	 * @throws Exception
	 */
	public static function setDir($dir)
	{
		$dir = rtrim($dir, '/');
		if (!is_dir($dir)) {
			throw new Exception("Argument must be a dir path with correct permissions.");
		}

		static::$tpldir = $dir;
	}

	/**
	 * @param string $dir Répertoire de stockage des fichiers mis en cache
	 *
	 * @throws Exception
	 */
	public static function setCacheDir($dir)
	{
		static::$useCache = false;

		if ($dir) {
			if (!is_dir($dir)) {
				throw new Exception("Argument must be a dir path with correct permissions.");
			}

			static::$useCache = true;
			static::$cachedir = rtrim($dir, '/');
		}
	}

	/**
	 * @param string $str
	 */
	public function loadFromString($str)
	{
		$this->uncompiled_code = $str;
		$this->compiled_code   = null;
	}

	/**
	 * Root-level variable assignment. Adds to current assignments, overriding
	 * any existing variable assignment with the same name.
	 *
	 * @param array $vararray Variables à assigner
	 */
	public function assign(array $vararray)
	{
		foreach ($vararray as $name => $value) {
			$this->tpldata['.'][$name] = $value;
		}
	}

	/**
	 * Block-level variable assignment. Adds a new block iteration with the given
	 * variable assignments. Note that this should only be called once per block
	 * iteration.
	 *
	 * @param string $blockname Nom du bloc
	 * @param array  $vararray  Variables à assigner
	 */
	public function assignToBlock($blockname, array $vararray = [])
	{
		if (strpos($blockname, '.')) {
			// Nested block.
			$blocks = explode('.', $blockname);
			$blockcount = count($blocks) - 1;

			$str = &$this->tpldata;
			for ($i = 0; $i < $blockcount; $i++) {
				$str = &$str[$blocks[$i]];
				$str = &$str[count($str) - 1];
			}

			// Now we add the block that we're actually assigning to.
			// We're adding a new iteration to this block with the given
			// variable assignments.
			$str[$blocks[$blockcount]][] = $vararray;
		}
		else {
			// Top-level block.
			// Add a new iteration to this block with the variable assignments
			// we were given.
			$this->tpldata[$blockname][] = $vararray;
		}
	}

	/**
	 * Load the file for the handle, compile the file,
	 * and run the compiled code. This will print out
	 * the results of executing the template.
	 *
	 * @param $return_res Si true, le code généré est retourné au lieu
	 *                    d’être directement affiché.
	 *
	 * @throws Exception
	 * @return string
	 */
	public function pparse($return_res = false)
	{
		$use_cache = false;
		$filename  = $this->filename;

		if (is_null($this->uncompiled_code)) {
			if (strncasecmp(PHP_OS, 'Win', 3) === 0) {
				if (!preg_match('#^[a-z]:[/\\\\]#i', $filename)) {
					$filename = static::$tpldir . '/' . $filename;
				}
			}
			else if ($filename[0] != '/') {
				$filename = static::$tpldir . '/' . $filename;
			}

			if (!is_readable($filename)) {
				throw new Exception("Cannot load '$filename' template file!");
			}

			$use_cache = static::$useCache;
			$cache_filename = static::$cachedir . '/ctpl_' . md5($filename);
			$this->uncompiled_code = file_get_contents($filename);
		}

		if (is_null($this->compiled_code) && (!$use_cache
			|| !is_readable($cache_filename)
			|| filemtime($cache_filename) < filemtime($filename)
		)) {
			$this->compiled_code = $this->compile($this->uncompiled_code);

			if ($use_cache) {
				if (!($fp = fopen($cache_filename, 'wb'))) {
					throw new Exception("Cannot write cache file!");
				}

				chmod($cache_filename, 0600);
				flock($fp, LOCK_EX);
				fwrite($fp, '<'."?php\n// filename: $filename\n\n".$this->compiled_code."\n");
				flock($fp, LOCK_UN);
				fclose($fp);

				$this->compiled_code = null;
			}
		}

		if ($return_res) {
			ob_start();
		}

		if ($use_cache) {
			include $cache_filename;
		}
		else {
			eval($this->compiled_code);
		}

		if ($return_res) {
			return ob_get_clean();
		}
	}

	/**
	 * Callback method used with preg_replace_callback() for replace
	 * all varrefs (with or without namespace) in template
	 *
	 * @see self::compile()
	 *
	 * @param array $varrefs
	 *
	 * @return string
	 */
	private function handleVarref(array $varrefs)
	{
		[, $namespace, $varname] = $varrefs;

		if (!empty($namespace)) {
			// Strip the trailing period.
			$namespace = substr($namespace, 0, -1);

			// get last level name
			if (strrpos($namespace, '.')) {
				$namespace = substr($namespace, strrpos($namespace, '.') + 1);
			}

			$varref = "\${$namespace}['$varname']";
		}
		else {
			$varref = "\$this->tpldata['.']['$varname']";
		}

		$varref = "', \$this->display($varref ?? ''), '";

		return $varref;
	}

	/**
	 * @see self::handleVarref()
	 *
	 * @param mixed $varref
	 *
	 * @return string
	 */
	private function display($varref)
	{
		if (is_object($varref) && is_a($varref, __CLASS__)) {
			return $varref->pparse(true);
		}
		else {
			return $varref;
		}
	}

	/**
	 * Compiles the given string of code, and returns
	 * the result in a string.
	 *
	 * @param string $code
	 *
	 * @return string
	 */
	public function compile($code)
	{
		$block_atom = '[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*';
		$var_atom   = '[a-z0-9_-]+';

		// replace \ with \\ and then ' with \'.
		$code = str_replace('\\', '\\\\', $code);
		$code = str_replace('\'', '\\\'', $code);

		// This one will handle root-level varrefs AND varrefs with namespaces
		$code = preg_replace_callback("#\{((?:$block_atom?\.)+)?($var_atom)\}#i",
			array($this, 'handleVarref'), $code);

		// Break it up into lines.
		$lines = explode("\n", $code);

		$block_nesting_level = 0;
		$block_names = [];

		// Second: prepend echo ', append ' . "\n"; to each line.
		foreach ($lines as &$line) {
			$line = rtrim($line);
			if (preg_match("#<!-- BEGIN ($block_atom) -->#", $line, $m)) {
				// Added: dougk_ff7-Keeps templates from bombing if begin is on the same line as end.. I think. :)
				$is_end = preg_match("#<!-- END ($block_atom) -->#", $line, $n);

				if ($block_nesting_level < 1) {
					// Block is not nested.
					$varref = "\$this->tpldata['$m[1]']";
				}
				else {
					// This block is nested.
					$namespace = $block_names[$block_nesting_level-1];
					$varref = "\${$namespace}['$m[1]']";
				}

				$line  = "if (isset($varref)) {\n";
				$line .= "foreach ($varref as &\$$m[1]) {";

				if (!$is_end) {
					$block_names[$block_nesting_level] = $m[1];
					$block_nesting_level++;
				}
				else {
					// We have the end of a block.
					$line .= '}} // END ' . $n[1];
				}
			}
			else if (preg_match("#<!-- END ($block_atom) -->#", $line, $m)) {
				// We have the end of a block.
				$block_nesting_level--;
				unset($block_names[$block_nesting_level]);
				$line = '}} // END ' . $m[1];
			}
			else {
				// We have an ordinary line of code.
				$line = 'echo \'' . $line . '\', "\\n";';
			}
		}

		return implode("\n", $lines);
	}
}
