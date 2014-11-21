<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

class Output extends Template
{
	/**
	 * Liens relatifs au document
	 *
	 * @var array
	 */
	private $links         = array();

	/**
	 * Scripts clients liés au document
	 *
	 * @var array
	 */
	private $scripts       = array();

	/**
	 * Champs cachés d'un formulaire du document
	 *
	 * @var array
	 */
	private $hidden_fields = array();

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
	private $messageList   = array();

	/**
	 * @param string $template_root
	 */
	public function __construct($template_root)
	{
		//
		// Réglage du dossier contenant les templates
		//
		$this->set_rootdir($template_root);
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
			$attrs = array(
				'rel'   => $rel,
				'href'  => $href,
				'title' => $title,
				'type'  => $type
			);
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
		$this->links = array();

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
			$attrs = array(
				'src'   => $src,
				'type'  => $type,
				'async' => $async,
				'defer' => $defer
			);
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
		$this->scripts = array();

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
		$this->hidden_fields[] = array('name' => $name, 'value' => $value);
	}

	/**
	 * Retourne l'ensemble des champs cachés ajoutés et réinitialise la propriété hidden_fields
	 *
	 * @return string
	 */
	public function getHiddenFields()
	{
		$type = array('type' => 'hidden');
		foreach ($this->hidden_fields as &$field) {
			$field = array_merge($type, $field);
			$field = $this->getHTMLElement('input', $field);
		}

		$fields = implode("\r\n\t", $this->hidden_fields);
		$this->hidden_fields = array();

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
				$attr = sprintf('%s="%s"', $attrname, wan_htmlspecialchars($value));
			}

			$html .= $attr;
		}

		if (!is_null($data)) {
			$data = wan_htmlspecialchars($data);
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
			$this->meta_redirect = sprintf('<meta http-equiv="Refresh" content="%d; url=%s" />', $timer, $url);
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
	public function page_header($page_title = '')
	{
		global $nl_config, $lang, $template, $admindata, $auth;
		global $simple_header, $error, $msg_error;

		if (empty($_SESSION) || !$_SESSION['is_logged_in']) {
			$simple_header = true;
		}

		define('HEADER_INC', true);

		$this->send_headers();

		$this->set_filenames(array(
			'header' => ($simple_header) ? 'simple_header.tpl' :'header.tpl'
		));

		if (defined('IN_ADMIN')) {
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

		$this->assign_vars( array(
			'PAGE_TITLE'   => $page_title,
			'META'         => $this->meta_redirect,
			'CONTENT_LANG' => $lang['CONTENT_LANG'],
			'CONTENT_DIR'  => $lang['CONTENT_DIR'],

			'BASEDIR'      => (!empty($nl_config['path'])) ? rtrim($nl_config['path'], '/') : '.',
			'S_NAV_LINKS'  => $this->getLinks(),
			'S_SCRIPTS'    => $this->getScripts(),
			'SITENAME'     => wan_htmlspecialchars($sitename, ENT_NOQUOTES)
		));

		// Si l'utilisateur est connecté, affichage du menu
		if (!$simple_header) {
			if (defined('IN_ADMIN')) {
				$l_logout = sprintf(
					$lang['Module']['logout_2'],
					wan_htmlspecialchars($admindata['admin_login'], ENT_NOQUOTES)
				);

				$this->assign_vars(array(
					'L_INDEX'       => $lang['Module']['accueil'],
					'L_CONFIG'      => $lang['Module']['config'],
					'L_SEND'        => $lang['Module']['send'],
					'L_SUBSCRIBERS' => $lang['Module']['subscribers'],
					'L_LIST'        => $lang['Module']['list'],
					'L_TOOLS'       => $lang['Module']['tools'],
					'L_USERS'       => $lang['Module']['users'],
					'L_STATS'       => $lang['Module']['stats'],
				));
			}
			else {
				$l_logout = $lang['Module']['logout'];

				$this->assign_vars(array(
					'L_EDITPROFILE' => $lang['Module']['editprofile']
				));
			}

			$this->assign_vars(array(
				'L_LOG'    => $lang['Module']['log'],
				'L_LOGOUT' => $l_logout,
			));
		}

		if ($error) {
			$this->error_box($msg_error);
		}

		$this->pparse('header');
	}

	/**
	 * Envoi le pied de page et termine l'exécution du script
	 */
	public function page_footer()
	{
		global $db, $lang, $starttime;

		$entries = wanlog();
		$wanlog_box = '';

		foreach ($entries as $entry) {
			if ($entry instanceof Exception) {
				if (!DISPLAY_ERRORS_IN_LOG) {
					continue;
				}

				$entry = wan_format_error($entry);
			}
			else if (!is_scalar($entry)) {
				$entry = print_r($entry, true);
			}

			$wanlog_box .= sprintf("<li>%s</li>\n", nl2br(trim($entry)));
		}

		$this->set_filenames(array(
			'footer' => 'footer.tpl'
		));

		$version = WANEWSLETTER_VERSION;

		if (wan_get_debug_level() > DEBUG_LEVEL_QUIET && $db instanceof Wadb) {
			$version  .= sprintf(' (%s)', substr(get_class($db), 5));
			$endtime   = array_sum(explode(' ', microtime()));
			$totaltime = ($endtime - $starttime);

			$this->assign_block_vars('dev_infos', array(
				'TIME_TOTAL' => sprintf('%.8f', $totaltime),
				'TIME_PHP'   => sprintf('%.3f', $totaltime - $db->sqltime),
				'TIME_SQL'   => sprintf('%.3f', $db->sqltime),
				'MEM_USAGE'  => (function_exists('memory_get_usage'))
					? formateSize(memory_get_usage()) : 'Unavailable',
				'QUERIES'    => $db->queries
			));
		}

		$this->assign_vars( array(
			'VERSION'   => $version,
			'TRANSLATE' => (!empty($lang['TRANSLATE'])) ? ' | Translate by ' . $lang['TRANSLATE'] : ''
		));

		if ($wanlog_box != '') {
			$this->assign_vars(array(
				'WANLOG_BOX' => sprintf('<ul class="warning"
					style="font-family:monospace;font-size:12px;">%s</ul>',
					$wanlog_box
				)
			));
		}

		$this->pparse('footer');

		exit;
	}

	/**
	 * Envoie des en-têtes HTTP
	 */
	public function send_headers()
	{
		global $lang;

		header('Expires: ' . gmdate(DATE_RFC1123));// HTTP/1.0
		header('Pragma: no-cache');// HTTP/1.0
		header('Cache-Control: no-cache, must-revalidate, max-age=0');
		header('Content-Language: ' . $lang['CONTENT_LANG']);
		header('Content-Type: text/html; charset=UTF-8');
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

		$lang = (!empty($lang['CONTENT_LANG'])) ? $lang['CONTENT_LANG'] : 'fr';
		$dir  = (!empty($lang['CONTENT_DIR'])) ? $lang['CONTENT_DIR'] : 'ltr';

		$this->send_headers();

		echo <<<BASIC
<!DOCTYPE html>
<html lang="$lang" dir="$dir">
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
	 * Affiche de message d'information.
	 * OBSOLÈTE. Voir méthode displayMessage() plus bas.
	 *
	 * @param string $str
	 *
	 * @deprecated since 2.4-beta2
	 */
	public function message($str)
	{
		$this->displayMessage($str);
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
			$str = sprintf($str, sprintf('<a href="%s">', wan_htmlspecialchars($link)), '</a>');
		}

		$this->messageList[] = $str;
	}

	/**
	 * Affichage d'un message d'information
	 * Si $str n'est pas fourni, la pile de messages $this->messageList est utilisée
	 *
	 * @param string $str
	 * @param string $title
	 */
	public function displayMessage($str = '', $title = '')
	{
		global $lang, $message;

		if (!empty($str)) {
			if (!empty($lang['Message'][$str])) {
				$str = $lang['Message'][$str];
			}

			$this->messageList[] = $str;
		}

		$str = '';
		foreach ($this->messageList as $message) {
			$str .= '<br><br>'.str_replace("\n", "<br>\n", $message);
		}
		$str = substr($str, 8);

		if (empty($title)) {
			$title = $lang['Title']['info'];
		}
		else if (!empty($lang['Title'][$title])) {
			if ($title == 'error') {
				$title = '<span style="color: #F66;">' . $lang['Title']['error'] . '</span>';
			}
			else {
				$title = $lang['Title'][$title];
			}
		}

		if (!defined('HEADER_INC')) {
			$this->page_header();
		}

		$this->set_filenames(array(
			'body' => 'message_body.tpl'
		));

		$this->assign_vars( array(
			'MSG_TITLE' => $title,
			'MSG_TEXT'  => $str
		));

		$this->pparse('body');

		$this->page_footer();
		exit;
	}

	/**
	 * Génération et affichage de liste d'erreur
	 *
	 * @param mixed $msg_errors
	 */
	public function error_box($msg_errors)
	{
		if (!is_array($msg_errors)) {
			$msg_errors = array($msg_errors);
		}

		$error_box = '';
		foreach ($msg_errors as $msg_error) {
			$error_box .= sprintf("<li>%s</li>\n", $msg_error);
		}

		$this->assign_vars(array(
			'ERROR_BOX' => sprintf('<ul class="warning">%s</ul>', $error_box)
		));
	}

	/**
	 * Affichage des fichiers joints
	 *
	 * @param array   $logdata Données du log concerné
	 * @param integer $format  Format du log visualisé (si dans view.php)
	 *
	 * @return boolean
	 */
	public function files_list($logdata, $format = 0)
	{
		global $lang, $nl_config;

		$page_envoi  = (strpos(server_info('PHP_SELF'), 'envoi.php') !== false);
		$body_size   = (strlen($logdata['log_body_text']) + strlen($logdata['log_body_html']));
		$total_size  = 1024; // ~ 1024 correspond au poids de base d'un email (en-têtes)
		$total_size += ($body_size > 0) ? ($body_size / 2) : 0;
		$num_files   = count($logdata['joined_files']);

		if ($num_files == 0) {
			return false;
		}

		$test_ary = array();
		for ($i = 0; $i < $num_files; $i++) {
			$total_size  += $logdata['joined_files'][$i]['file_size'];
			$test_files[] = $logdata['joined_files'][$i]['file_real_name'];
		}

		if ($format == FORMAT_HTML && hasCidReferences($logdata['log_body_html'], $refs) > 0) {
			$embed_files = array_intersect($test_files, $refs);

			if (($num_files - count($embed_files)) == 0) {
				return false;
			}
		}
		else {
			$embed_files = array();
		}

		$this->set_filenames(array(
			'files_box_body' => 'files_box.tpl'
		));

		$this->assign_vars(array(
			'L_JOINED_FILES'   => $lang['Title']['joined_files'],
			'L_FILENAME'       => $lang['Filename'],
			'L_FILESIZE'       => $lang['Filesize'],
			'L_TOTAL_LOG_SIZE' => $lang['Total_log_size'],

			'TOTAL_LOG_SIZE'   => formateSize($total_size),
			'S_ROWSPAN'        => ($page_envoi) ? '4' : '3'
		));

		if ($page_envoi) {
			$this->assign_block_vars('del_column', array());
			$this->assign_block_vars('joined_files.files_box', array( // dans send_body.tpl
				'L_DEL_FILE_BUTTON' => $lang['Button']['del_file']
			));

			$u_download = './envoi.php?mode=download&amp;fid=%d';
		}
		else {
			$u_download = './view.php?mode=download&amp;fid=%d';
		}

		$u_show = '../options/show.php?fid=%d';

		for ($i = 0; $i < $num_files; $i++) {
			$filesize  = $logdata['joined_files'][$i]['file_size'];
			$filename  = $logdata['joined_files'][$i]['file_real_name'];
			$file_id   = $logdata['joined_files'][$i]['file_id'];
			$mime_type = $logdata['joined_files'][$i]['file_mimetype'];

			$tmp_filename = WA_ROOTDIR . '/' . $nl_config['upload_path'] . $logdata['joined_files'][$i]['file_physical_name'];
			$s_show = '';

			if ($nl_config['use_ftp'] || file_exists($tmp_filename)) {
				//
				// On affiche pas dans la liste les fichiers incorporés dans
				// une newsletter au format HTML.
				//
				if ($format == FORMAT_HTML && in_array($filename, $embed_files)) {
					continue;
				}

				$filename = sprintf('<a href="%s">%s</a>', sprintf($u_download, $file_id), wan_htmlspecialchars($filename));

				if (preg_match('#^image/#', $mime_type)) {
					$s_show  = sprintf('<a class="show" href="%s" type="%s">', sprintf($u_show, $file_id), $mime_type);
					$s_show .= '<img src="../templates/images/icon_loupe.png" width="14" height="14" alt="voir" title="' . $lang['Show'] . '" />';
					$s_show .= '</a>';
				}
			}
			else {
				$filename = sprintf('<del title="%s">%s</del>',
					$lang['Message']['File_not_found'], wan_htmlspecialchars($filename));
			}

			$this->assign_block_vars('file_info', array(
				'OFFSET'   => ($i + 1),
				'FILENAME' => $filename,
				'FILESIZE' => formateSize($filesize),
				'S_SHOW'   => $s_show
			));

			if ($page_envoi) {
				$this->assign_block_vars('file_info.delete_options', array(
					'FILE_ID' => $file_id
				));
			}
		}

		$this->assign_var_from_handle('JOINED_FILES_BOX', 'files_box_body');

		return true;
	}

	/**
	 * Affichage de la page de sélection de liste ou insertion du select de choix de liste dans
	 * le coin inférieur gauche de l'administration
	 *
	 * @param integer $auth_type
	 * @param boolean $display
	 * @param string  $jump_to
	 */
	public function build_listbox($auth_type, $display = true, $jump_to = '')
	{
		global $output, $admindata, $auth, $lang;

		$tmp_box = '';
		$liste_id_ary = $auth->check_auth($auth_type);

		if (empty($jump_to)) {
			$jump_to = './' . wan_htmlspecialchars(basename(server_info('SCRIPT_NAME')));
			$query_string = server_info('QUERY_STRING');

			if ($query_string != '') {
				$jump_to .= '?' . wan_htmlspecialchars($query_string);
			}
		}

		foreach ($auth->listdata as $liste_id => $data) {
			if (in_array($liste_id, $liste_id_ary)) {
				$tmp_box .= sprintf(
					"<option value=\"%d\"%s>%s</option>\n\t",
					$liste_id,
					$output->getBoolAttr('selected', ($_SESSION['liste'] == $liste_id)),
					wan_htmlspecialchars(cut_str($data['liste_name'], 30))
				);
			}
		}

		if ($tmp_box == '') {
			if ($display) {
				$this->addLine($lang['Message']['No_liste_exists']);

				if (wan_is_admin($admindata)) {
					$this->addLine($lang['Click_create_liste'], './view.php?mode=liste&action=add');
				}

				$this->displayMessage();
			}

			return '';
		}

		$list_box = '<select id="liste" name="liste">';
		if (!$display) {
			$list_box .= '<option value="0">' . $lang['Choice_liste'] . '</option>';
		}
		$list_box .= $tmp_box . '</select>';

		if ($display) {
			$this->page_header();

			$this->set_filenames(array(
				'body' => 'select_liste_body.tpl'
			));

			$this->assign_vars(array(
				'L_TITLE'         => $lang['Title']['select'],
				'L_SELECT_LISTE'  => $lang['Choice_liste'],
				'L_VALID_BUTTON'  => $lang['Button']['valid'],

				'LISTE_BOX'       => $list_box,
				'U_FORM'          => $jump_to
			));

			$this->pparse('body');

			$this->page_footer();
		}
		else {
			$this->set_filenames(array(
				'list_box_body' => 'list_box.tpl'
			));

			$this->assign_vars(array(
				'L_VIEW_LIST' => $lang['View_liste'],
				'L_BUTTON_GO' => $lang['Button']['go'],

				'S_LISTBOX'   => $list_box,
				'U_LISTBOX'   => $jump_to
			));

			$this->assign_var_from_handle('LISTBOX', 'list_box_body');
		}
	}
}
