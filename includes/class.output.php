<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

class Output
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
	 * @param string $src    URL du script
	 * @param string $type   Type MIME
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
		if (wan_get_debug_level() == DEBUG_LEVEL_QUIET) {
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
	 * Envoie en sortie les en-têtes HTTP appropriés et l'en-tête du document
	 *
	 * @param string  $page_title
	 */
	public function header($page_title = '')
	{
		global $nl_config, $lang, $admindata, $auth, $msg_error;

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

		if (check_in_admin()) {
			$this->addLink('home', './',              				$lang['Title']['accueil']);
			$this->addLink('section', './config.php',               $lang['Module']['config']);
			$this->addLink('section', './envoi.php',                $lang['Title']['send']);
			$this->addLink('section', './view.php?mode=abonnes',    $lang['Module']['subscribers']);
			$this->addLink('section', './view.php?mode=liste',      $lang['Module']['list']);
			$this->addLink('section', './view.php?mode=log',        $lang['Module']['log']);
			$this->addLink('section', './tools.php?mode=export',    $lang['Title']['export']);
			$this->addLink('section', './tools.php?mode=import',    $lang['Title']['import']);
			$this->addLink('section', './tools.php?mode=ban',       $lang['Title']['ban']);
			$this->addLink('section', './tools.php?mode=generator', $lang['Title']['generator']);

			if (wan_is_admin($admindata)) {
				$this->addLink('section', './tools.php?mode=attach' , $lang['Title']['attach']);
				$this->addLink('section', './tools.php?mode=backup' , $lang['Title']['backup']);
				$this->addLink('section', './tools.php?mode=restore', $lang['Title']['restore']);
			}

			$this->addLink('section',   './admin.php', $lang['Module']['users']);
			$this->addLink('section',   './stats.php', $lang['Title']['stats']);
			$this->addLink('help',      '../docs/faq.' . $lang['CONTENT_LANG'] . '.html'   , $lang['Faq']);
			$this->addLink('author',    '../docs/readme.' . $lang['CONTENT_LANG'] . '.html', $lang['Author_note']);
			$this->addLink('copyright', 'http://www.gnu.org/copyleft/gpl.html', 'Licence GPL 2');

			if ($page_title == '') {
				$page_title = $lang['General_title'];
			}
		}
		else {
			$this->addLink('home', 		'./profil_cp.php',                  $lang['Title']['accueil']);
			$this->addLink('section',   './profil_cp.php?mode=editprofile', $lang['Module']['editprofile']);
			$this->addLink('section',   './profil_cp.php?mode=archives',    $lang['Module']['log']);
			$this->addLink('section',   './profil_cp.php?mode=logout',      $lang['Module']['logout']);

			if ($page_title == '') {
				$page_title = $lang['Title']['profil_cp'];
			}
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
			'ERROR_BOX'    => $this->errorbox($msg_error)
		]);

		// Si l'utilisateur est connecté, affichage du menu
		if (!$simple_header) {
			if (check_in_admin()) {
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

		$entries = wanlog();
		$wanlog_box = '';

		foreach ($entries as $entry) {
			if ($entry instanceof \Throwable || $entry instanceof \Exception) {
				// Les exceptions sont affichées via wan_exception_handler().
				// Les erreurs fatales sont affichées via wan_error_handler().
				if (!($entry instanceof Error) || $entry->isFatal() || $entry->ignore() || !DISPLAY_ERRORS_IN_LOG) {
					continue;
				}

				$entry = wan_format_error($entry);
			}
			else if (!is_scalar($entry)) {
				$entry = print_r($entry, true);
			}

			$wanlog_box .= sprintf("<li>%s</li>\n", $entry);
		}

		$template = new Template('footer.tpl');
		$version  = WANEWSLETTER_VERSION;

		if (wan_get_debug_level() > DEBUG_LEVEL_QUIET && $db instanceof Dblayer\Wadb) {
			$version  .= sprintf(' (%s)', $db::ENGINE);
			$endtime   = array_sum(explode(' ', microtime()));
			$totaltime = ($endtime - $starttime);

			$template->assignToBlock('dev_infos', [
				'TIME_TOTAL' => sprintf('%.8f', $totaltime),
				'TIME_PHP'   => sprintf('%.3f', $totaltime - $db->sqltime),
				'TIME_SQL'   => sprintf('%.3f', $db->sqltime),
				'MEM_USAGE'  => (function_exists('memory_get_usage'))
					? formateSize(memory_get_usage()) : 'Unavailable',
				'QUERIES'    => $db->queries
			]);
		}

		$template->assign([
			'VERSION'   => $version,
			'TRANSLATE' => (!empty($lang['TRANSLATE'])) ? ' | Translate by ' . $lang['TRANSLATE'] : ''
		]);

		if ($wanlog_box != '') {
			$template->assign([
				'WANLOG_BOX' => sprintf('<ul class="warning"
					style="font-family:monospace;font-size:12px;">%s</ul>',
					$wanlog_box
				)
			]);
		}

		$template->pparse();
		exit;
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
	$this->meta_redirect
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

	public function useTheme()
	{
		$is_used_theme  = check_in_admin();
		$is_used_theme |= defined(__NAMESPACE__.'\\IN_INSTALL');
		$is_used_theme |= defined(__NAMESPACE__.'\\IN_PROFILCP');

		return ($is_used_theme && !check_cli());
	}

	/**
	 * Affichage d’un message d’information.
	 *
	 * @param string $str
	 * @param string $title
	 */
	public function message($str = '', $title = '')
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

		$type = '';

		if (empty($title)) {
			$title = $lang['Title']['info'];
		}
		else if (!empty($lang['Title'][$title])) {
			if ($title == 'error') {
				$type = 'error';
			}

			$title = $lang['Title'][$title];
		}

		if (check_cli()) {
			$str = htmlspecialchars_decode(strip_tags($str));

			fwrite(STDOUT, $str."\n");
		}
		else {
			if ($this->useTheme()) {
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
		}

		exit(0);
	}

	/**
	 * Génération de la liste des erreurs
	 *
	 * @param array $msg_errors
	 *
	 * @return string
	 */
	public function errorbox(array $msg_errors)
	{
		$error_box = '';
		foreach ($msg_errors as $msg_error) {
			$error_box .= sprintf("<li>%s</li>\n", $msg_error);
		}

		if ($error_box) {
			$error_box = sprintf('<ul class="warning">%s</ul>', $error_box);
		}

		return $error_box;
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

		$page_envoi  = (strpos($_SERVER['SCRIPT_NAME'], 'envoi.php') !== false);
		$body_size   = (strlen($logdata['log_body_text']) + strlen($logdata['log_body_html']));
		$total_size  = 1024; // ~ 1024 correspond au poids de base d'un email (en-têtes)
		$total_size += ($body_size > 0) ? ($body_size / 2) : 0;
		$num_files   = count($logdata['joined_files']);

		if ($num_files == 0) {
			return '';
		}

		for ($i = 0; $i < $num_files; $i++) {
			$total_size  += $logdata['joined_files'][$i]['file_size'];
			$test_files[] = $logdata['joined_files'][$i]['file_real_name'];
		}

		$embed_files = [];
		if ($format == FORMAT_HTML && hasCidReferences($logdata['log_body_html'], $refs) > 0) {
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

			'TOTAL_LOG_SIZE'   => formateSize($total_size),
			'S_ROWSPAN'        => ($page_envoi) ? '4' : '3'
		]);

		if ($page_envoi) {
			$template->assignToBlock('del_column');
			$template->assignToBlock('joined_files.files_box', [
				'L_DEL_FILE_BUTTON' => $lang['Button']['del_file']
			]);

			$u_download = './envoi.php?mode=download&amp;fid=%d';
		}
		else {
			$u_download = './view.php?mode=download&amp;fid=%d';
		}

		for ($i = 0; $i < $num_files; $i++) {
			$filesize  = $logdata['joined_files'][$i]['file_size'];
			$filename  = $logdata['joined_files'][$i]['file_real_name'];
			$file_id   = $logdata['joined_files'][$i]['file_id'];
			$mime_type = $logdata['joined_files'][$i]['file_mimetype'];

			$tmp_filename = WA_ROOTDIR . '/' . $nl_config['upload_path'] . $logdata['joined_files'][$i]['file_physical_name'];
			$s_show = '';

			if (file_exists($tmp_filename)) {
				//
				// On affiche pas dans la liste les fichiers incorporés dans
				// une newsletter au format HTML.
				//
				if ($format == FORMAT_HTML && in_array($filename, $embed_files)) {
					continue;
				}

				$filename = sprintf('<a href="%s">%s</a>',
					sprintf($u_download, $file_id),
					htmlspecialchars($filename)
				);

				if (preg_match('#^image/#', $mime_type)) {
					$s_show  = sprintf('<a class="show" href="show.php?fid=%d" type="%s">', $file_id, $mime_type);
					$s_show .= '<img src="../templates/images/icon_loupe.png" width="14"';
					$s_show .= ' height="14" alt="voir" title="' . $lang['Show'] . '" />';
					$s_show .= '</a>';
				}
			}
			else {
				$filename = sprintf('<del title="%s">%s</del>',
					$lang['Message']['File_not_found'], htmlspecialchars($filename));
			}

			$template->assignToBlock('file_info', [
				'OFFSET'   => ($i + 1),
				'FILENAME' => $filename,
				'FILESIZE' => formateSize($filesize),
				'S_SHOW'   => $s_show
			]);

			if ($page_envoi) {
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
				htmlspecialchars(cut_str($data['liste_name'], 30))
			);
		}

		if (!$tmpbox) {
			if ($complete) {
				$this->addLine($lang['Message']['No_liste_exists']);

				if (wan_is_admin($admindata)) {
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
