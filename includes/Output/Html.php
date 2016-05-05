<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter\Output;

use Wanewsletter\Auth;
use Wanewsletter\Error;
use Wanewsletter\Dblayer;
use Wanewsletter\Template;

class Html implements MessageInterface
{
	/**
	 * Liens relatifs au document
	 *
	 * @var array
	 */
	private $links         = [];

	/**
	 * Scripts clients liés au document
	 *
	 * @var array
	 */
	private $scripts       = [];

	/**
	 * Champs cachés d'un formulaire du document
	 *
	 * @var array
	 */
	private $hidden_fields = [];

	/**
	 * Meta de redirection
	 *
	 * @var string
	 */
	private $meta_redirect = '';

	/**
	 * Pile des messages
	 *
	 * @var array
	 */
	private $messageList   = [];

	/**
	 * Indique si la méthode self::header() a déjà été appelée.
	 *
	 * @var boolean
	 */
	private $header_displayed = false;

	/**
	 * Utilisation du thème wanewsletter ou affichage simplifié
	 * (par exemple, pour cron.php, ou bien si les fichiers de
	 * langue n’ont pas été trouvés).
	 *
	 * @var boolean
	 */
	private $use_theme     = false;

	/**
	 * Pile des messages d'avertissement.
	 *
	 * @var array
	 */
	private $msg_log       = [];

	/**
	 * @param boolean $use_theme
	 */
	public function __construct($use_theme)
	{
		$this->use_theme = (bool) $use_theme;
	}

	/**
	 * Ajout d'un lien relatif au document
	 *
	 * @param string $rel   Relation qui lie le document cible au document courant
	 * @param string $href  URL du document cible
	 * @param string $title Titre éventuel
	 * @param string $type  Type MIME du document cible
	 */
	public function addLink($rel, $href = null, $title = null, $type = null)
	{
		if (is_array($rel)) {
			// Si le premier argument fourni est un tableau, c'est qu'on reçoit
			// directement un tableau d'attributs
			$attrs = $rel;
		}
		else {
			$attrs = [
				'rel'   => $rel,
				'href'  => $href,
				'title' => $title,
				'type'  => $type
			];
		}

		$this->links[] = $attrs;
	}

	/**
	 * Retourne les liens relatifs au document
	 *
	 * @return string
	 */
	public function getLinks()
	{
		foreach ($this->links as &$link) {
			$link = $this->getHTMLElement('link', $link);
		}

		$links = implode("\r\n\t", $this->links);
		$this->links = [];

		return $links;
	}

	/**
	 * Ajout d'un script client
	 *
	 * @param string  $src   URL du script
	 * @param string  $type  Type MIME
	 * @param boolean $async Chargement asynchrone
	 * @param boolean $defer Chargement après le chargement de la page elle-même
	 */
	public function addScript($src, $type = null, $async = null, $defer = null)
	{
		if (is_array($src)) {
			// Si le premier argument fourni est un tableau, c'est qu'on reçoit
			// directement un tableau d'attributs
			$attrs = $src;
		}
		else {
			$attrs = [
				'src'   => $src,
				'type'  => $type,
				'async' => $async,
				'defer' => $defer
			];
		}

		$this->scripts[] = $attrs;
	}

	/**
	 * Retourne les scripts clients liés au document
	 *
	 * @return string
	 */
	public function getScripts()
	{
		foreach ($this->scripts as &$script) {
			$script = $this->getHTMLElement('script', $script, null, true);
		}

		$scripts = implode("\r\n\t", $this->scripts);
		$this->scripts = [];

		return $scripts;
	}

	/**
	 * Ajoute un champs caché pour un formulaire
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function addHiddenField($name, $value)
	{
		$this->hidden_fields[] = ['name' => $name, 'value' => $value];
	}

	/**
	 * Retourne l'ensemble des champs cachés ajoutés et réinitialise la propriété hidden_fields
	 *
	 * @return string
	 */
	public function getHiddenFields()
	{
		$type = ['type' => 'hidden'];
		foreach ($this->hidden_fields as &$field) {
			$field = array_merge($type, $field);
			$field = $this->getHTMLElement('input', $field);
		}

		$fields = implode("\r\n\t", $this->hidden_fields);
		$this->hidden_fields = [];

		return $fields;
	}

	/**
	 * Retourne un élément HTML formaté à partir de son nom, du tableau d'attributs,
	 * et de son contenu éventuel.
	 *
	 * @param string  $name
	 * @param array   $attrs
	 * @param string  $data
	 * @param boolean $closetag true pour forcer l'ajout d'une balise de fermeture
	 *
	 * @return string
	 */
	public function getHTMLElement($name, $attrs, $data = null, $closetag = false)
	{
		$html = '<'.$name;
		foreach ($attrs as $attrname => $value) {
			if (is_null($value)) {
				continue;
			}

			$html .= ' ';
			if (is_bool($value)) {
				$attr = $attrname;
			}
			else {
				$attr = sprintf('%s="%s"', $attrname, htmlspecialchars($value));
			}

			$html .= $attr;
		}

		if (!is_null($data)) {
			$data = htmlspecialchars($data);
		}

		$html .= ($closetag || $data) ? '>'.$data.'</'.$name.'>' : ' />';

		return $html;
	}

	/**
	 * Ajoute un meta de redirection pour la page en cours
	 *
	 * @param string  $url
	 * @param integer $timer
	 */
	public function redirect($url, $timer)
	{
		if (!\Wanewsletter\wan_is_debug_enabled()) {
			$this->meta_redirect = sprintf(
				'<meta http-equiv="Refresh" content="%d; url=%s" />',
				intval($timer),
				htmlspecialchars($url)
			);
		}
	}

	/**
	 * Retourne un attribut HTML booléen si $return vaut true.
	 * Appel typique : ... $o->getBoolAttr('checked', ($var1 == $var2))
	 * L'attribut est dans le format court.
	 *
	 * @param string  $name   Nom de l'attribut booléen (checked, selected, ...)
	 * @param boolean $return Résultat du test conditionnel
	 *
	 * @return string
	 */
	public function getBoolAttr($name, $return = true)
	{
		return ($return) ? " $name " : '';
	}

	/**
	 * Envoi des en-têtes HTTP
	 */
	public function httpHeaders()
	{
		global $lang;

		header('Expires: ' . gmdate(DATE_RFC1123));// HTTP/1.0
		header('Pragma: no-cache');// HTTP/1.0
		header('Cache-Control: no-cache, must-revalidate, max-age=0');
		header('Content-Language: ' . $lang['CONTENT_LANG']);
		header('Content-Type: text/html; charset=UTF-8');
		header('X-Frame-Options: sameorigin');
	}

	/**
	 * Envoie en sortie les en-têtes HTTP appropriés et l'en-tête du document
	 *
	 * @param string  $page_title
	 */
	public function header($page_title = '')
	{
		global $nl_config, $lang, $admindata, $auth;

		if ($this->header_displayed) {
			return null;
		}

		$this->header_displayed = true;
		$simple_header = false;

		if (!($auth instanceof Auth) || !$auth->isLoggedIn()) {
			$simple_header = true;
		}

		$this->httpHeaders();

		$template = new Template(($simple_header) ? 'simple_header.tpl' : 'header.tpl');

		if (\Wanewsletter\check_in_admin()) {
			$this->addLink('home', './', $lang['Title']['accueil']);
			$this->addLink('help', sprintf('../docs/faq.%s.html', $lang['CONTENT_LANG']), $lang['Faq']);
			$this->addLink('author', sprintf('../docs/readme.%s.html', $lang['CONTENT_LANG']), $lang['Author_note']);
			$this->addLink('copyright', 'http://www.gnu.org/copyleft/gpl.html', 'Licence GPL 2');

			$sections[] = [$lang['Module']['config'],      './config.php'];
			$sections[] = [$lang['Title']['send'],         './envoi.php'];
			$sections[] = [$lang['Module']['subscribers'], './view.php?mode=abonnes'];
			$sections[] = [$lang['Module']['list'],        './view.php?mode=liste'];
			$sections[] = [$lang['Module']['log'],         './view.php?mode=log'];
			$sections[] = [$lang['Title']['export'],       './tools.php?mode=export'];
			$sections[] = [$lang['Title']['import'],       './tools.php?mode=import'];
			$sections[] = [$lang['Title']['ban'],          './tools.php?mode=ban'];
			$sections[] = [$lang['Title']['generator'],    './tools.php?mode=generator'];
			$sections[] = [$lang['Module']['users'],       './admin.php'];
			$sections[] = [$lang['Title']['stats'],        './stats.php'];

			if (Auth::isAdmin($admindata)) {
				$sections[] = [$lang['Title']['attach'],  './tools.php?mode=attach'];
				$sections[] = [$lang['Title']['backup'],  './tools.php?mode=backup'];
				$sections[] = [$lang['Title']['restore'], './tools.php?mode=restore'];
			}

			$page_title = $page_title ?: $lang['General_title'];
		}
		else {
			$this->addLink('home', './profil_cp.php', $lang['Title']['accueil']);

			$sections[] = [$lang['Module']['editprofile'], './profil_cp.php?mode=editprofile'];
			$sections[] = [$lang['Module']['log'],         './profil_cp.php?mode=archives'];
			$sections[] = [$lang['Module']['logout'],      './profil_cp.php?mode=logout'];

			$page_title = $page_title ?: $lang['Title']['profil_cp'];
		}

		foreach ($sections as $section) {
			$this->addLink('section', $section[1], $section[0]);
		}

		$sitename = (!empty($nl_config['sitename'])) ? $nl_config['sitename'] : 'Wanewsletter';

		if (!empty($nl_config['path'])) {
			$base_dir = rtrim($nl_config['path'], '/');
		}
		else {
			$base_dir = (strpos($_SERVER['PHP_SELF'], 'admin/')) ? '..' : '.';
		}

		// Intégration d'une éventuelle feuille de style personnalisée
		if (is_readable(WA_ROOTDIR . '/templates/wanewsletter.custom.css')) {
			$this->addLink('stylesheet', sprintf('%s/templates/wanewsletter.custom.css', $base_dir));
		}

		$template->assign([
			'PAGE_TITLE'   => $page_title,
			'META'         => $this->meta_redirect,
			'CONTENT_LANG' => $lang['CONTENT_LANG'],
			'CONTENT_DIR'  => $lang['CONTENT_DIR'],

			'BASEDIR'      => $base_dir,
			'S_NAV_LINKS'  => $this->getLinks(),
			'S_SCRIPTS'    => $this->getScripts(),
			'SITENAME'     => htmlspecialchars($sitename, ENT_NOQUOTES),
			'NOTICE_BOX'   => $this->msgbox('notice'),
			'WARN_BOX'     => $this->msgbox('warn')
		]);

		// Si l'utilisateur est connecté, affichage du menu
		if (!$simple_header) {
			if (\Wanewsletter\check_in_admin()) {
				$l_logout = sprintf(
					$lang['Module']['logout_2'],
					htmlspecialchars($admindata['admin_login'], ENT_NOQUOTES)
				);

				$template->assign([
					'L_INDEX'       => $lang['Module']['accueil'],
					'L_CONFIG'      => $lang['Module']['config'],
					'L_SEND'        => $lang['Module']['send'],
					'L_SUBSCRIBERS' => $lang['Module']['subscribers'],
					'L_LIST'        => $lang['Module']['list'],
					'L_TOOLS'       => $lang['Module']['tools'],
					'L_USERS'       => $lang['Module']['users'],
					'L_STATS'       => $lang['Module']['stats'],
					'L_DOCS'        => $lang['Module']['docs']
				]);
			}
			else {
				$l_logout = $lang['Module']['logout'];

				$template->assign([
					'L_EDITPROFILE' => $lang['Module']['editprofile']
				]);
			}

			$template->assign([
				'L_RESTORE_DEFAULT' => $lang['Restore_default'],
				'L_LOG'             => $lang['Module']['log'],
				'L_LOGOUT'          => $l_logout
			]);
		}

		$template->pparse();
	}

	/**
	 * Envoi le pied de page et termine l'exécution du script
	 */
	public function footer()
	{
		global $db, $lang, $starttime;

		$wanlog_box = '';

		foreach (\Wanewsletter\wanlog() as $entry) {
			if ($entry instanceof \Throwable || $entry instanceof \Exception) {
				// Les exceptions sont affichées via wan_exception_handler().
				// Les erreurs fatales sont affichées via wan_error_handler().
				if (!($entry instanceof Error)
					|| $entry->isFatal()
					|| $entry->ignore()
					|| !\Wanewsletter\DELAY_ERROR_DISPLAY
				) {
					continue;
				}

				$entry = static::formatError($entry);
			}
			else if (!is_scalar($entry)) {
				$entry = print_r($entry, true);
			}

			$wanlog_box .= sprintf("<li>%s</li>\n", $entry);
		}

		$template = new Template('footer.tpl');
		$version  = \Wanewsletter\WANEWSLETTER_VERSION;

		if (\Wanewsletter\wan_is_debug_enabled() && $db instanceof Dblayer\Wadb) {
			$version  .= sprintf(' (%s)', $db::ENGINE);
			$endtime   = array_sum(explode(' ', microtime()));
			$totaltime = ($endtime - $starttime);

			$template->assignToBlock('dev_infos', [
				'TIME_TOTAL' => sprintf('%.8f', $totaltime),
				'TIME_PHP'   => sprintf('%.3f', $totaltime - $db->sqltime),
				'TIME_SQL'   => sprintf('%.3f', $db->sqltime),
				'MEM_USAGE'  => (function_exists('memory_get_usage'))
					? \Wanewsletter\formateSize(memory_get_usage())
					: 'Unavailable',
				'QUERIES'    => $db->queries
			]);
		}

		$template->assign([
			'VERSION'   => $version,
			'TRANSLATE' => (!empty($lang['TRANSLATE'])) ? ' | Translate by ' . $lang['TRANSLATE'] : ''
		]);

		if ($wanlog_box != '') {
			$template->assign([
				'WANLOG_BOX' => sprintf('<ul id="wanlog" class="logbox warn">%s</ul>', $wanlog_box)
			]);
		}

		$template->pparse();
		exit;
	}

	/**
	 * Envoi des en-têtes appropriés et d'une page html simplifiée avec les données fournies
	 * Termine également l'exécution du script
	 *
	 * @param string $content
	 * @param string $page_title
	 */
	public function basic($content, $page_title = '')
	{
		global $lang;

		$lang_code = (!empty($lang['CONTENT_LANG'])) ? $lang['CONTENT_LANG'] : 'fr';
		$direction = (!empty($lang['CONTENT_DIR'])) ? $lang['CONTENT_DIR'] : 'ltr';

		$this->httpHeaders();

		echo <<<BASIC
<!DOCTYPE html>
<html lang="$lang_code" dir="$direction">
<head>
	<meta charset="UTF-8" />
	<title>$page_title</title>

	<style>
	body { margin: 10px; text-align: left; }
	</style>
</head>
<body>
	<div>$content</div>
</body>
</html>
BASIC;

		exit;
	}

	/**
	 * Ajoute une entrée à la pile des messages
	 *
	 * @param string $str  le message
	 * @param string $link le lien html à intégrer dans le message
	 */
	public function addLine($str, $link = null)
	{
		if (!is_null($link)) {
			$str = sprintf($str, sprintf('<a href="%s">', htmlspecialchars($link)), '</a>');
		}

		$this->messageList[] = $str;
	}

	public static function formatError($error)
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

		return nl2br($message);
	}

	public function error($error)
	{
		$exit = true;
		if ($error instanceof \Throwable || $error instanceof \Exception) {
			if ($error instanceof Error) {
				$skip  = $error->ignore();
				$skip |= ($this->use_theme && \Wanewsletter\DELAY_ERROR_DISPLAY);
				if (!$error->isFatal() && $skip) {
					return null;
				}

				$exit = $error->isFatal();
			}

			$error = static::formatError($error);
		}

		if (!$exit) {
			echo $error;
		}
		else {
			$this->message($error, true);
		}

		if ($exit) {
			exit;
		}
	}

	public function message($str = '', $is_error = false)
	{
		global $lang;

		if (empty($lang)) {
			$this->basic($str);
		}

		if (!empty($str)) {
			if (!empty($lang['Message'][$str])) {
				$str = $lang['Message'][$str];
			}

			$this->messageList[] = $str;
		}

		$str = '';
		foreach ($this->messageList as $message) {
			if ($str) {
				$str .= '<br /><br />';
			}
			$str .= $message;
		}



		if ($is_error) {
			$title = $lang['Title']['error'];
			$type  = 'error';
		}
		else {
			$title = $lang['Title']['info'];
			$type  = 'info';
		}

		if ($this->use_theme) {
			$this->header();

			$template = new Template('message_body.tpl');

			$template->assign([
				'MSG_TITLE' => $title,
				'MSG_TEXT'  => $str,
				'MSG_TYPE'  => $type
			]);

			$template->pparse();

			$this->footer();
		}
		else {
			$this->basic($str);
		}

		exit;
	}

	public function notice()
	{
		$args = func_get_args();
		array_unshift($args, 'notice');
		call_user_func_array([$this,'log'], $args);
	}

	public function warn()
	{
		$args = func_get_args();
		array_unshift($args, 'warn');
		call_user_func_array([$this,'log'], $args);
	}

	public function log($type, $str)
	{
		global $lang;

		if (!empty($lang['Message'][$str])) {
			$str = $lang['Message'][$str];
		}

		if (func_num_args() > 2) {
			$args = func_get_args();
			array_shift($args);
			$args = array_map('htmlspecialchars', $args);
			$args[0] = $str;
			$str  = call_user_func_array('sprintf', $args);
		}

		$this->msg_log[$type][] = $str;
	}

	/**
	 * Génération de la liste des alertes
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function msgbox($type)
	{
		if (!empty($this->msg_log[$type])) {
			$list = $this->msg_log[$type];
			array_walk($list, function (&$value, $key) {
				$value = sprintf("<li>%s</li>\n", nl2br($value));
			});

			return sprintf('<ul class="logbox %s">%s</ul>', $type, implode('', $list));
		}

		return '';
	}

	/**
	 * Génération du template des fichiers joints
	 *
	 * @param array   $logdata Données du log concerné
	 * @param integer $format  Format du log visualisé (si dans view.php)
	 *
	 * @return Template|string
	 */
	public function filesList(array $logdata, $format = 0)
	{
		global $lang, $nl_config;

		if (($num_files = count($logdata['joined_files'])) == 0) {
			return '';
		}

		$total_size  = 1024; // ~ 1024 correspond au poids de base d'un email (en-têtes)
		$total_size += (strlen($logdata['log_body_text']) + strlen($logdata['log_body_html']));

		if ($format == \Wanewsletter\FORMAT_TEXT) {
			$total_size -= strlen($logdata['log_body_html']);
		}

		for ($i = 0; $i < $num_files; $i++) {
			$total_size  += $logdata['joined_files'][$i]['file_size'];
			$test_files[] = $logdata['joined_files'][$i]['file_real_name'];
		}

		$embed_files = [];
		if ($format == \Wanewsletter\FORMAT_HTML
			&& \Wanewsletter\hasCidReferences($logdata['log_body_html'], $refs) > 0
		) {
			$embed_files = array_intersect($test_files, $refs);

			if (($num_files - count($embed_files)) == 0) {
				return '';
			}
		}

		$template = new Template('files_box.tpl');

		$template->assign([
			'L_JOINED_FILES'   => $lang['Title']['joined_files'],
			'L_FILENAME'       => $lang['Filename'],
			'L_FILESIZE'       => $lang['Filesize'],
			'L_TOTAL_LOG_SIZE' => $lang['Total_log_size'],

			'TOTAL_LOG_SIZE'   => \Wanewsletter\formateSize($total_size),
			'S_ROWSPAN'        => 3
		]);

		// Si $format vaut false, cela signifie qu’on est sur la page envoi,
		// et donc que les checkbox de suppression de fichiers peuvent
		// être affichées.
		if (!$format) {
			$template->assignToBlock('del_column');
			$template->assign(['S_ROWSPAN' => 4]);
		}

		for ($i = 0, $offset = 0; $i < $num_files; $i++) {
			$filesize  = $logdata['joined_files'][$i]['file_size'];
			$filename  = $logdata['joined_files'][$i]['file_real_name'];
			$file_id   = $logdata['joined_files'][$i]['file_id'];
			$mime_type = $logdata['joined_files'][$i]['file_mimetype'];

			$tmp_filename = WA_ROOTDIR . '/'
				. $nl_config['upload_path']
				. $logdata['joined_files'][$i]['file_physical_name'];

			$s_show = '';

			if (file_exists($tmp_filename)) {
				//
				// On affiche pas dans la liste les fichiers incorporés dans
				// une newsletter au format HTML.
				//
				if (in_array($filename, $embed_files)) {
					continue;
				}

				$show_url = sprintf('./show.php?fid=%d', $file_id);
				$filename = sprintf('<a href="%s">%s</a>',
					$show_url,
					htmlspecialchars($filename)
				);

				if (preg_match('#^image/#', $mime_type)) {
					$s_show  = sprintf('<a class="show" href="%s" type="%s">', $show_url, $mime_type);
					$s_show .= '<img src="../templates/images/icon_loupe.png" width="14"';
					$s_show .= ' height="14" alt="voir" title="' . $lang['Show'] . '" />';
					$s_show .= '</a>';
				}
			}
			else {
				$filename = sprintf('<del title="%s">%s</del>',
					$lang['Message']['File_not_found'],
					htmlspecialchars($filename)
				);
			}

			$template->assignToBlock('file_info', [
				'OFFSET'   => ++$offset,
				'FILENAME' => $filename,
				'FILESIZE' => \Wanewsletter\formateSize($filesize),
				'S_SHOW'   => $s_show
			]);

			if (!$format) {
				$template->assignToBlock('file_info.delete_options', [
					'FILE_ID' => $file_id
				]);
			}
		}

		return $template;
	}

	/**
	 * Génération de la page de sélection de liste, ou du bloc de selection
	 * de liste à intégrer dans le coin inférieur droit de l’administration
	 *
	 * @param string  $auth_type
	 * @param boolean $complete
	 * @param string  $jump_to
	 *
	 * @return Template|string
	 */
	public function listbox($auth_type, $complete = true, $jump_to = '')
	{
		global $admindata, $auth, $lang;

		$lists = $auth->getLists($auth_type);

		if (!$jump_to) {
			$jump_to = './' . htmlspecialchars(basename($_SERVER['SCRIPT_NAME']));
			$query_string = $_SERVER['QUERY_STRING'];

			if ($query_string != '') {
				$jump_to .= '?' . htmlspecialchars($query_string);
			}
		}

		$tmpbox = '';
		foreach ($lists as $liste_id => $data) {
			$tmpbox .= sprintf(
				"<option value=\"%d\"%s>%s</option>\n\t",
				$liste_id,
				$this->getBoolAttr('selected', ($_SESSION['liste'] == $liste_id)),
				htmlspecialchars($data['liste_name'])
			);
		}

		if (!$tmpbox) {
			if ($complete) {
				$this->addLine($lang['Message']['No_liste_exists']);

				if (Auth::isAdmin($admindata)) {
					$this->addLine($lang['Click_create_liste'], './view.php?mode=liste&action=add');
				}

				$this->message();
			}

			return '';
		}

		$listbox = '<select id="liste" name="liste">';
		if (!$complete) {
			$listbox .= '<option value="0">' . $lang['Choice_liste'] . '</option>';
		}
		$listbox .= $tmpbox . '</select>';

		if ($complete) {
			$template = new Template('select_liste_body.tpl');

			$template->assign([
				'L_TITLE'        => $lang['Title']['select'],
				'L_SELECT_LISTE' => $lang['Choice_liste'],
				'L_VALID_BUTTON' => $lang['Button']['valid'],

				'LISTE_BOX'      => $listbox,
				'U_FORM'         => $jump_to
			]);
		}
		else {
			$template = new Template('list_box.tpl');

			$template->assign([
				'L_VIEW_LIST' => $lang['View_liste'],
				'L_BUTTON_GO' => $lang['Button']['go'],

				'S_LISTBOX'   => $listbox,
				'U_LISTBOX'   => $jump_to
			]);
		}

		return $template;
	}
}
