<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

use Patchwork\Utf8 as u;
use ZipArchive;

require './start.inc.php';

//
// Compression éventuelle des données et réglage du mime-type et du
// nom de fichier en conséquence.
//
function compress_filedata(&$filename, &$mime_type, $contents, $compress)
{
	switch ($compress) {
		case 'zip':
			$tmp_filename = tempnam(WA_TMPDIR, 'wa-');
			$mime_type = 'application/zip';
			$zip = new ZipArchive();
			$zip->open($tmp_filename, ZipArchive::CREATE);
			$zip->addFromString($filename, $contents);
			$zip->close();
			$contents = file_get_contents($tmp_filename);
			unlink($tmp_filename);
			$filename .= '.zip';
			break;
		case 'gzip':
			$mime_type = 'application/x-gzip';
			$contents  = gzencode($contents);
			$filename .= '.gz';
			break;
		case 'bz2':
			$mime_type = 'application/x-bzip';
			$contents  = bzcompress($contents);
			$filename .= '.bz2';
			break;
	}

	return $contents;
}

//
// Lecture et décompression éventuelle des données
//
function decompress_filedata($tmp_filename, $filename)
{
	global $output;

	$ext = pathinfo($filename, PATHINFO_EXTENSION);

	if ((!ZIPLIB_LOADED && $ext == 'zip') ||
		(!ZLIB_LOADED && $ext == 'gz') ||
		(!BZIP2_LOADED && $ext == 'bz2')
	) {
		$output->displayMessage('Compress_unsupported');
	}

	switch ($ext) {
		case 'zip':
			$scheme = 'zip://';
			$tmp_filename .= '#' . pathinfo($filename, PATHINFO_FILENAME);
			break;
		case 'gz':
			$scheme = 'compress.zlib://';
			break;
		case 'bz2':
			$scheme = 'compress.bzip2://';
			break;
		default:
			$scheme = '';
			break;
	}

	return file_get_contents($scheme . $tmp_filename);
}

$mode     = (!empty($_REQUEST['mode'])) ? $_REQUEST['mode'] : '';
$format   = (!empty($_POST['format'])) ? intval($_POST['format']) : FORMAT_TEXTE;
$eformat  = (!empty($_POST['eformat'])) ? $_POST['eformat'] : '';
$glue     = (!empty($_POST['glue'])) ? trim($_POST['glue']) : '';
$action   = (!empty($_POST['action'])) ? $_POST['action'] : 'download';
$compress = (!empty($_POST['compress'])) ? $_POST['compress'] : 'none';

$file_local  = (!empty($_POST['file_local'])) ? trim($_POST['file_local']) : '';
$file_upload = (!empty($_FILES['file_upload'])) ? $_FILES['file_upload'] : array();

if (!in_array($format, array(FORMAT_TEXTE, FORMAT_HTML))) {
	$format = FORMAT_TEXTE;
}

switch ($mode) {
	case 'export':
		$auth_type = Auth::EXPORT;
		break;
	case 'import':
		$auth_type = Auth::IMPORT;
		break;
	case 'ban':
		$auth_type = Auth::BAN;
		break;
	case 'backup':
	case 'restore':
	case 'debug':
	case 'attach':
		if (!wan_is_admin($admindata)) {
			http_response_code(401);
			$output->redirect('./index.php', 4);
			$output->addLine($lang['Message']['Not_authorized']);
			$output->addLine($lang['Click_return_index'], './index.php');
			$output->displayMessage();
		}
		// no break
	case 'generator':
		$auth_type = Auth::VIEW;
		break;
	default:
		$mode = '';
		$auth_type = Auth::VIEW;
		break;
}

$url_page  = './tools.php';
$url_page .= ($mode != '') ? '?mode=' . $mode : '';

if (!in_array($mode, array('backup','restore','debug', '')) && !$_SESSION['liste']) {
	$output->build_listbox($auth_type, true, $url_page);
}
else if ($_SESSION['liste']) {
	if (!$auth->check_auth($auth_type, $_SESSION['liste'])) {
		$output->displayMessage('Not_' . $auth->auth_ary[$auth_type]);
	}

	$listdata = $auth->listdata[$_SESSION['liste']];
}

//
// Affichage de la boîte de sélection des modules
//
if (!isset($_POST['submit'])) {
	if ($mode != 'backup' && $mode != 'restore') {
		$output->build_listbox($auth_type, false, $url_page);
	}

	$tools_ary = array('export', 'import', 'ban', 'generator');

	if (wan_is_admin($admindata)) {
		array_push($tools_ary, 'attach', 'backup', 'restore', 'debug');
	}

	$tools_box = '<select id="mode" name="mode">';
	foreach ($tools_ary as $tool_name) {
		$tools_box .= sprintf(
			"<option value=\"%s\"%s> %s </option>\n\t",
			$tool_name,
			$output->getBoolAttr('selected', ($mode == $tool_name)),
			$lang['Title'][$tool_name]
		);
	}
	$tools_box .= '</select>';

	$output->page_header();

	$output->set_filenames(array(
		'body' => 'tools_body.tpl'
	));

	$output->assign_vars(array(
		'L_TITLE'        => $lang['Title']['tools'],
		'L_EXPLAIN'      => nl2br($lang['Explain']['tools']),
		'L_SELECT_TOOL'  => $lang['Select_tool'],
		'L_VALID_BUTTON' => $lang['Button']['valid'],

		'S_TOOLS_BOX'    => $tools_box,
		'S_TOOLS_HIDDEN_FIELDS' => $output->getHiddenFields()
	));
}

//
// On vérifie la présence des extensions nécessaires pour les différents formats de fichiers proposés
//
define('ZIPLIB_LOADED', extension_loaded('zip'));
define('ZLIB_LOADED',   extension_loaded('zlib'));
define('BZIP2_LOADED',  extension_loaded('bz2'));

//
// On augmente le temps d'exécution du script
// Certains hébergeurs empèchent pour des raisons évidentes cette possibilité
// Si c'est votre cas, vous êtes mal barré
//
@set_time_limit(1200);

function wan_subdir_status($dir)
{
	if (file_exists($dir)) {
		if (!is_readable($dir)) {
			$str = "non [pas d'accès en lecture]";
		}
		else if (!is_writable($dir)) {
			$str = "non [pas d'accès en écriture]";
		}
		else {
			$str = "ok";
		}
	}
	else {
		$str = "non [n'existe pas]";
	}

	return $str;
}

function wan_print_row($name, $value)
{
	echo u::str_pad($name, 30);
	echo ' : ';
	echo htmlspecialchars($value);
	echo "\r\n";
}

switch ($mode) {
	case 'debug':
		printf("<h2>%s</h2>", $lang['Title']['debug']);
		echo "<pre style='font-size:12px;margin: 20px;white-space:pre-wrap;'>";

		wan_print_row('Version Wanewsletter', WANEWSLETTER_VERSION);
		wan_print_row(' - db_version',     $nl_config['db_version']);
		wan_print_row(' - session_length', $nl_config['session_length']);
		wan_print_row(' - language',       $nl_config['language']);
		wan_print_row(' - Upload dir',     wan_subdir_status(WA_ROOTDIR.'/'.$nl_config['upload_path']));

		if (!$nl_config['disable_stats']) {
			require WA_ROOTDIR . '/includes/functions.stats.php';
			wan_print_row(' - Stats dir',     wan_subdir_status(WA_STATSDIR));
		}
		wan_print_row(' - max_filesize',   $nl_config['max_filesize']);
		wan_print_row(' - use_ftp',        $nl_config['use_ftp'] ? 'oui' : 'non');
		wan_print_row(' - engine_send',    $nl_config['engine_send']);
		wan_print_row(' - sending_limit',  $nl_config['sending_limit']);
		wan_print_row(' - use_smtp',       $nl_config['use_smtp'] ? 'oui' : 'non');

		wan_print_row('Version de PHP', sprintf('%s (%s)', PHP_VERSION, PHP_SAPI));
		wan_print_row(' - Extension Bz2', extension_loaded('zlib') ? 'oui' : 'non');
		wan_print_row(' - Extension FTP',  extension_loaded('ftp') ? 'oui' : 'non');

		if (extension_loaded('gd')) {
			$tmp = gd_info();
			$format = (imagetypes() & IMG_GIF) ? 'GIF' : 'Unavailable';
			$format = (imagetypes() & IMG_PNG) ? 'PNG' : $format;
			$str = sprintf('oui - Version %s - Format %s', $tmp['GD Version'], $format);
		}
		else {
			$str = 'non';
		}
		wan_print_row(' - Extension GD', $str);
		wan_print_row(' - Extension Iconv',
			extension_loaded('iconv') ?
				sprintf('oui - Version %s - Implémentation %s', ICONV_VERSION, ICONV_IMPL) : 'non'
		);
		wan_print_row(' - Extension Mbstring', extension_loaded('mbstring') ? 'oui' : 'non');
		wan_print_row(' - Extension OpenSSL',
			extension_loaded('openssl') ? sprintf('oui - %s', OPENSSL_VERSION_TEXT) : 'non'
		);
		wan_print_row(' - Extension SimpleXML', extension_loaded('simplexml') ? 'oui' : 'non');
		wan_print_row(' - Extension XML', extension_loaded('xml') ? 'oui' : 'non');
		wan_print_row(' - Extension Zip', extension_loaded('zip') ? 'oui' : 'non');
		wan_print_row(' - Extension Zlib', extension_loaded('zlib') ? 'oui' : 'non');

		// Le safe mode et les magic quotes ont été supprimés à partir de PHP 5.4
		if (PHP_VERSION_ID < 50400) {
			wan_print_row(' - safe_mode', config_status('safe_mode') ? 'on' : 'off');
			wan_print_row(' - magic_quotes_gpc', config_status('magic_quotes_gpc') ? 'on' : 'off');
			wan_print_row(' - magic_quotes_runtime', config_status('magic_quotes_runtime') ? 'on' : 'off');
		}

		wan_print_row(' - open_basedir',  config_value('open_basedir'));
		wan_print_row(' - sys_temp_dir', sys_get_temp_dir());
		wan_print_row(' - filter.default', config_value('filter.default'));
		wan_print_row(' - allow_url_fopen', config_status('allow_url_fopen') ? 'on' : 'off');
		wan_print_row(' - allow_url_include', config_status('allow_url_include') ? 'on' : 'off');
		wan_print_row(' - file_uploads', config_status('file_uploads') ? 'on' : 'off');
		wan_print_row(' - upload_tmp_dir', config_value('upload_tmp_dir'));
		wan_print_row(' - upload_max_filesize', config_value('upload_max_filesize'));
		wan_print_row(' - post_max_size', config_value('post_max_size'));
		wan_print_row(' - max_input_time', config_value('max_input_time'));
		wan_print_row(' - memory_limit', config_value('memory_limit'));
		wan_print_row(' - mail.add_x_header', config_status('mail.add_x_header') ? 'on' : 'off');
		wan_print_row(' - mail.force_extra_parameters', config_value('mail.force_extra_parameters'));
		wan_print_row(' - sendmail_from', config_value('sendmail_from'));
		wan_print_row(' - sendmail_path', config_value('sendmail_path'));

		if (strncasecmp(PHP_OS, 'Win', 3) === 0) {
			wan_print_row(' - SMTP Server', config_value('SMTP').':'.config_value('smtp_port'));
		}

		wan_print_row('Type de serveur', $_SERVER['SERVER_SOFTWARE'] . ' - ' . PHP_OS);
		wan_print_row('Connexion sécurisée', wan_ssl_connection() ? 'oui' : 'non');

		if ($db::ENGINE == 'sqlite') {
			wan_print_row('Base de données', sprintf('%s %s - Driver : %s',
				$db->infos['label'],
				$db->libVersion,
				$db->infos['driver']
			));
		}
		else {
			wan_print_row('Base de données', sprintf('%s %s',
				$db->infos['label'],
				$db->serverVersion
			));
			wan_print_row(' - Librairie cliente', $db->clientVersion);
			wan_print_row(' - Driver', $db->infos['driver']);
			wan_print_row(' - Jeu de caractères', $db->encoding());
		}

		wan_print_row('Agent utilisateur',
			filter_input(INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_SPECIAL_CHARS)
		);

		echo "</pre>";

		$output->page_footer();
		exit;
		break;

	case 'export':
		if (isset($_POST['submit'])) {
			if ($listdata['liste_format'] != FORMAT_MULTIPLE) {
				$format = $listdata['liste_format'];
			}

			$glue = ($glue != '') ? $glue : WA_EOL;

			$sql = "SELECT a.abo_email
				FROM " . ABONNES_TABLE . " AS a
					INNER JOIN " . ABO_LISTE_TABLE . " AS al ON al.abo_id = a.abo_id
						AND al.liste_id  = $listdata[liste_id]
						AND al.format    = $format
						AND al.confirmed = " . SUBSCRIBE_CONFIRMED . "
				WHERE a.abo_status = " . ABO_ACTIF;
			$result = $db->query($sql);

			$contents = '';
			if ($eformat == 'xml') {
				while ($email = $result->column('abo_email')) {
					$contents .= sprintf("\t<email>%s</email>\n", $email);
				}

				$format = ($format == FORMAT_HTML) ? 'HTML' : 'text';
				$contents  = '<' . '?xml version="1.0"?' . ">\n"
					. "<!-- Date : " . date(DATE_RFC2822) . " - Format : $format -->\n"
					. "<Wanliste>\n" . $contents . "</Wanliste>\n";

				$mime_type = 'application/xml';
				$ext = 'xml';
			}
			else {
				while ($email = $result->column('abo_email')) {
					$contents .= ($contents != '' ? $glue : '') . $email;
				}

				$mime_type = 'text/plain';
				$ext = 'txt';
			}
			$result->free();

			$filename = sprintf('wa_export_%d.%s', $_SESSION['liste'], $ext);

			//
			// Préparation des données selon l'option demandée
			//
			$contents = compress_filedata($filename, $mime_type, $contents, $compress);

			if ($action == 'download') {
				Attach::send_file($filename, $mime_type, $contents);
			}
			else {
				if (!($fw = fopen(WA_TMPDIR . '/' . $filename, 'wb'))) {
					trigger_error('Impossible d\'écrire le fichier de sauvegarde', E_USER_ERROR);
				}

				fwrite($fw, $contents);
				fclose($fw);

				$output->displayMessage('Success_export');
			}
		}

		$output->set_filenames(array(
			'tool_body' => 'export_body.tpl'
		));

		$output->assign_vars(array(
			'L_TITLE_EXPORT'    => $lang['Title']['export'],
			'L_EXPLAIN_EXPORT'  => nl2br($lang['Explain']['export']),
			'L_EXPORT_FORMAT'   => $lang['Export_format'],
			'L_PLAIN_TEXT'      => $lang['Plain_text'],
			'L_GLUE'            => $lang['Char_glue'],
			'L_ACTION'          => $lang['File_action'],
			'L_DOWNLOAD'        => $lang['Download_action'],
			'L_STORE_ON_SERVER' => $lang['Store_action'],
			'L_VALID_BUTTON'    => $lang['Button']['valid'],
			'L_RESET_BUTTON'    => $lang['Button']['reset'],

			'S_HIDDEN_FIELDS'   => $output->getHiddenFields()
		));

		if (ZIPLIB_LOADED || ZLIB_LOADED || BZIP2_LOADED) {
			$output->assign_block_vars('compress_option', array(
				'L_COMPRESS' => $lang['Compress'],
				'L_NO'       => $lang['No']
			));

			if (ZIPLIB_LOADED) {
				$output->assign_block_vars('compress_option.zip_compress', array());
			}

			if (ZLIB_LOADED) {
				$output->assign_block_vars('compress_option.gzip_compress', array());
			}

			if (BZIP2_LOADED) {
				$output->assign_block_vars('compress_option.bz2_compress', array());
			}
		}

		if ($listdata['liste_format'] == FORMAT_MULTIPLE) {
			require WA_ROOTDIR . '/includes/functions.box.php';

			$output->assign_block_vars('format_box', array(
				'L_FORMAT'   => $lang['Format_to_export'],
				'FORMAT_BOX' => format_box('format')
			));
		}

		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;

	case 'import':
		if (isset($_POST['submit'])) {
			$list_email  = (!empty($_POST['list_email'])) ? trim($_POST['list_email']) : '';
			$list_tmp    = '';
			$data_is_xml = false;

			//
			// Import via upload ou fichier local ?
			//
			if (!empty($file_local) || !empty($file_upload['name'])) {
				$unlink = false;

				if (!empty($file_local)) {
					$tmp_filename = WA_ROOTDIR . '/' . str_replace('\\', '/', $file_local);
					$filename     = $file_local;

					if (!file_exists($tmp_filename)) {
						$output->redirect('./tools.php?mode=import', 4);
						$output->addLine(sprintf($lang['Message']['Error_local'], htmlspecialchars($filename)));
						$output->addLine($lang['Click_return_back'], './tools.php?mode=import');
						$output->displayMessage();
					}
				}
				else {
					$tmp_filename = $file_upload['tmp_name'];
					$filename     = $file_upload['name'];

					if (!isset($file_upload['error']) && empty($tmp_filename)) {
						$file_upload['error'] = -1;
					}

					if ($file_upload['error'] != UPLOAD_ERR_OK) {
						if (isset($lang['Message']['Upload_error_'.$file_upload['error']])) {
							$upload_error = 'Upload_error_'.$file_upload['error'];
						}
						else {
							$upload_error = 'Upload_error_5';
						}

						$output->displayMessage($upload_error);
					}

					//
					// Si nous n'avons pas d'accès direct au fichier uploadé,
					// il doit être déplacé vers le dossier des fichiers
					// temporaires du script pour être accessible en lecture.
					//
					if (!is_readable($tmp_filename)) {
						$unlink = true;
						$tmp_filename = tempnam(WA_TMPDIR, 'wa');

						if (!move_uploaded_file($file_upload['tmp_name'], $tmp_filename)) {
							unlink($tmp_filename);
							$output->displayMessage('Upload_error_5');
						}
					}
				}

				$list_tmp = decompress_filedata($tmp_filename, $filename);

				$data_is_xml = (strncmp($list_tmp, '<?xml', 5) == 0 || strncmp($list_tmp, '<Wanliste>', 10) == 0);

				if ($unlink) {
					unlink($tmp_filename);
				}
			}
			//
			// Mode importation via le textarea
			//
			else if (strlen($list_email) > 5) {
				$list_tmp = $list_email;
			}

			if ($listdata['liste_format'] != FORMAT_MULTIPLE) {
				$format = $listdata['liste_format'];
			}

			if (!empty($list_tmp) && $data_is_xml) {
				$emails = array();

				if (extension_loaded('simplexml')) {
					$xml = simplexml_load_string($list_tmp);
					$xml = $xml->xpath('/Wanliste/email');

					foreach ($xml as $email) {
						$emails[] = "$email";
					}
				}
				else if (extension_loaded('xml')) {
					$depth   = 0;
					$tagname = '';

					$parser = xml_parser_create();
					xml_set_element_handler($parser,
						function ($parser, $name, $attrs) use (&$depth, &$tagname) {
							if (($depth == 0 && strtolower($name) == 'wanliste') || $depth > 0) {
								$depth++;
							}

							$tagname = strtolower($name);
						},
						function ($parser, $name) use (&$depth) { $depth--; }
					);
					xml_set_character_data_handler($parser,
						function ($parser, $data) use (&$depth, &$tagname, &$emails) {
							if ($tagname == 'email' && $depth == 2) {
								$emails[] = $data;
							}
						}
					);

					if (!xml_parse($parser, $list_tmp)) {
						$output->displayMessage(sprintf(
							$lang['Message']['Invalid_xml_data'],
							htmlspecialchars(xml_error_string(xml_get_error_code($parser)), ENT_NOQUOTES),
							xml_get_current_line_number($parser)
						));
					}

					xml_parser_free($parser);
				}
				else {
					$output->displayMessage('Xml_ext_needed');
				}
			}
			else {
				if ($glue == '') {
					$list_tmp = preg_replace("/\r\n?/", "\n", $list_tmp);
					$glue = "\n";
				}

				$emails = explode($glue, trim($list_tmp));
			}

			//
			// Aucun fichier d'import valide reçu et textarea vide
			//
			if (count($emails) == 0) {
				$output->redirect('./tools.php?mode=import', 4);
				$output->addLine($lang['Message']['No_data_received']);
				$output->addLine($lang['Click_return_back'], './tools.php?mode=import');
				$output->displayMessage();
			}

			$report = '';

			$emails = array_slice($emails, 0, MAX_IMPORT);
			$emails = array_map('trim', $emails);
			$emails = array_unique($emails);

			//
			// Vérification syntaxique des emails
			//
			$emails = array_filter($emails,
				function ($email) use (&$lang, &$report) {
					if (\Wamailer\Mailer::checkMailSyntax($email)) {
						return true;
					}
					else {
						$report .= sprintf('%s : %s%s', $email, $lang['Message']['Invalid_email2'], WA_EOL);
						return false;
					}
				}
			);

			if (count($emails) > 0) {
				$current_time = time();
				$emails_ok    = array();

				fake_header(false);

				$sql_emails = array_map('strtolower', $emails);
				$sql_emails = array_map(array($db, 'escape'), $sql_emails);

				$sql = "SELECT a.abo_id, a.abo_email, a.abo_status, al.confirmed
					FROM " . ABONNES_TABLE . " AS a
						LEFT JOIN " . ABO_LISTE_TABLE . " AS al ON al.abo_id = a.abo_id
							AND al.liste_id = $listdata[liste_id]
					WHERE LOWER(a.abo_email) IN('" . implode("', '", $sql_emails) . "')";
				$result = $db->query($sql);

				//
				// Traitement des adresses email déjà présentes dans la base de données
				//
				while ($abodata = $result->fetch()) {
					if (!isset($abodata['confirmed'])) { // N'est pas inscrit à cette liste
						$sql_data = array();
						$sql_data['abo_id']        = $abodata['abo_id'];
						$sql_data['liste_id']      = $listdata['liste_id'];
						$sql_data['format']        = $format;
						$sql_data['register_key']  = generate_key(20, false);
						$sql_data['register_date'] = $current_time;
						$sql_data['confirmed']     = ($abodata['abo_status'] == ABO_ACTIF)
							? SUBSCRIBE_CONFIRMED : SUBSCRIBE_NOT_CONFIRMED;

						$db->insert(ABO_LISTE_TABLE, $sql_data);
					}
					else {
						$report .= sprintf('%s : %s%s',
							$abodata['abo_email'],
							$lang['Message']['Allready_reg'],
							WA_EOL
						);
					}

					$emails_ok[] = $abodata['abo_email'];
				}

				//
				// Traitement des adresses email inconnues
				//
				$emails = array_udiff($emails, $emails_ok, 'strcasecmp');

				foreach ($emails as $email) {
					$db->beginTransaction();

					$sql_data = array();
					$sql_data['abo_email']  = $email;
					$sql_data['abo_status'] = ABO_ACTIF;

					try {
						$db->insert(ABONNES_TABLE, $sql_data);
					}
					catch (Dblayer\Exception $e) {
						$report .= sprintf('%s : SQL error (#%d: %s)%s', $email, $db->errno, $db->error, WA_EOL);
						$db->rollBack();
						continue;
					}

					$sql_data = array();
					$sql_data['abo_id']        = $db->lastInsertId();
					$sql_data['liste_id']      = $listdata['liste_id'];
					$sql_data['format']        = $format;
					$sql_data['register_key']  = generate_key(20, false);
					$sql_data['register_date'] = $current_time;
					$sql_data['confirmed']     = SUBSCRIBE_CONFIRMED;

					$db->insert(ABO_LISTE_TABLE, $sql_data);

					$db->commit();

					fake_header(true);
				}
			}

			//
			// Selon que des emails ont été refusés ou pas, affichage du message correspondant
			// et mise à disposition éventuelle du rapport d'erreurs
			//
			if ($report != '') {
				$report_str  = '#' . WA_EOL;
				$report_str .= '# Rapport des adresses emails refusées / Bad address email report' . WA_EOL;
				$report_str .= '#' . WA_EOL;
				$report_str .= WA_EOL;
				$report_str .= $report . WA_EOL;
				$report_str .= '# END' . WA_EOL;

				$url = 'data:text/plain;base64,' . base64_encode($report_str);
				$output->addLine($lang['Message']['Success_import3'], $url);

				file_put_contents(WA_TMPDIR . '/wa_import_report.txt', $report_str);
			}
			else {
				$output->addLine($lang['Message']['Success_import']);
			}

			$output->displayMessage();
		}

		$max_filesize = get_max_filesize();

		$output->set_filenames(array(
			'tool_body' => 'import_body.tpl'
		));

		$output->assign_vars(array(
			'L_TITLE_IMPORT'   => $lang['Title']['import'],
			'L_EXPLAIN_IMPORT' => nl2br(sprintf($lang['Explain']['import'],
				MAX_IMPORT,
				sprintf('<a href="%s">', wan_get_faq_url(3)),
				'</a>'
			)),
			'L_GLUE'           => $lang['Char_glue'],
			'L_FILE_LOCAL'     => $lang['File_local'],
			'L_VALID_BUTTON'   => $lang['Button']['valid'],
			'L_RESET_BUTTON'   => $lang['Button']['reset'],

			'S_HIDDEN_FIELDS'  => $output->getHiddenFields(),
			'S_ENCTYPE'        => ($max_filesize) ? 'multipart/form-data' : 'application/x-www-form-urlencoded'
		));

		if ($listdata['liste_format'] == FORMAT_MULTIPLE) {
			require WA_ROOTDIR . '/includes/functions.box.php';

			$output->assign_block_vars('format_box', array(
				'L_FORMAT'   => $lang['Format_to_import'],
				'FORMAT_BOX' => format_box('format')
			));
		}

		if ($max_filesize) {
			//
			// L'upload est disponible sur le serveur
			// Affichage du champ file pour importation
			//
			$output->assign_block_vars('upload_file', array(
				'L_BROWSE_BUTTON' => $lang['Button']['browse'],
				'L_FILE_UPLOAD'   => $lang['File_upload'],
				'L_MAXIMUM_SIZE'  => sprintf($lang['Maximum_size'], formateSize($max_filesize)),
				'MAX_FILE_SIZE'   => $max_filesize
			));
		}

		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;

	case 'ban':
		if (isset($_POST['submit'])) {
			$pattern       = (!empty($_POST['pattern'])) ? trim(str_replace('\\\'', '', $_POST['pattern'])) : '';
			$unban_list_id = (!empty($_POST['unban_list_id'])) ? array_map('intval', $_POST['unban_list_id']) : array();

			if ($pattern != '') {
				$pattern_ary = array_map('trim', explode(',', $pattern));
				$sql_dataset = array();

				foreach ($pattern_ary as $pattern) {
					$sql_dataset[] = array(
						'liste_id'  => $listdata['liste_id'],
						'ban_email' => $pattern
					);
				}

				if (count($sql_dataset) > 0) {
					$db->insert(BANLIST_TABLE, $sql_dataset);
				}

				unset($sql_dataset);
			}

			if (count($unban_list_id) > 0) {
				$sql = "DELETE FROM " . BANLIST_TABLE . "
					WHERE ban_id IN (" . implode(', ', $unban_list_id) . ")";
				$db->query($sql);

				//
				// Optimisation des tables
				//
				$db->vacuum(BANLIST_TABLE);
			}

			$output->redirect('./tools.php?mode=ban', 4);
			$output->addLine($lang['Message']['Success_modif']);
			$output->addLine($lang['Click_return_back'], './tools.php?mode=ban');
			$output->addLine($lang['Click_return_index'], './index.php');
			$output->displayMessage();
		}

		$sql = "SELECT ban_id, ban_email
			FROM " . BANLIST_TABLE . "
			WHERE liste_id = " . $listdata['liste_id'];
		$result = $db->query($sql);

		$unban_email_box = '<select id="unban_list_id" name="unban_list_id[]" multiple="multiple" size="10">';
		if ($row = $result->fetch()) {
			do {
				$unban_email_box .= sprintf("<option value=\"%d\">%s</option>\n\t", $row['ban_id'], $row['ban_email']);
			}
			while ($row = $result->fetch());
		}
		else {
			$unban_email_box .= '<option value="0">' . $lang['No_email_banned'] . '</option>';
		}
		$unban_email_box .= '</select>';

		$output->set_filenames( array(
			'tool_body' => 'ban_list_body.tpl'
		));

		$output->assign_vars(array(
			'L_TITLE_BAN'     => $lang['Title']['ban'],
			'L_EXPLAIN_BAN'   => nl2br($lang['Explain']['ban']),
			'L_EXPLAIN_UNBAN' => nl2br($lang['Explain']['unban']),
			'L_BAN_EMAIL'     => $lang['Ban_email'],
			'L_UNBAN_EMAIL'   => $lang['Unban_email'],
			'L_VALID_BUTTON'  => $lang['Button']['valid'],
			'L_RESET_BUTTON'  => $lang['Button']['reset'],

			'UNBAN_EMAIL_BOX' => $unban_email_box,
			'S_HIDDEN_FIELDS' => $output->getHiddenFields()
		));

		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;

	case 'attach':
		if (isset($_POST['submit'])) {
			$ext_list    = (!empty($_POST['ext_list'])) ? trim($_POST['ext_list']) : '';
			$ext_list_id = (!empty($_POST['ext_list_id'])) ? array_map('intval', $_POST['ext_list_id']) : array();

			if ($ext_list != '') {
				$ext_ary	= array_map('trim', explode(',', $ext_list));
				$sql_dataset = array();

				foreach ($ext_ary as $ext) {
					$ext = strtolower($ext);

					if (preg_match('/^[\w_-]+$/', $ext)) {
						$sql_dataset[] = array(
							'liste_id' => $listdata['liste_id'],
							'fe_ext'   => $ext
						);
					}
				}

				if (count($sql_dataset) > 0) {
					$db->insert(FORBIDDEN_EXT_TABLE, $sql_dataset);
				}

				unset($sql_dataset);
			}

			if (count($ext_list_id) > 0) {
				$sql = "DELETE FROM " . FORBIDDEN_EXT_TABLE . "
					WHERE fe_id IN (" . implode(', ', $ext_list_id) . ")";
				$db->query($sql);

				//
				// Optimisation des tables
				//
				$db->vacuum(FORBIDDEN_EXT_TABLE);
			}

			$output->redirect('./tools.php?mode=attach', 4);
			$output->addLine($lang['Message']['Success_modif']);
			$output->addLine($lang['Click_return_back'], './tools.php?mode=attach');
			$output->addLine($lang['Click_return_index'], './index.php');
			$output->displayMessage();
		}

		$sql = "SELECT fe_id, fe_ext
			FROM " . FORBIDDEN_EXT_TABLE . "
			WHERE liste_id = " . $listdata['liste_id'];
		$result = $db->query($sql);

		$reallow_ext_box = '<select id="ext_list_id" name="ext_list_id[]" multiple="multiple" size="10">';
		if ($row = $result->fetch()) {
			do {
				$reallow_ext_box .= sprintf("<option value=\"%d\">%s</option>\n\t", $row['fe_id'], $row['fe_ext']);
			}
			while ($row = $result->fetch());
		}
		else {
			$reallow_ext_box .= '<option value="0">' . $lang['No_forbidden_ext'] . '</option>';
		}
		$reallow_ext_box .= '</select>';

		$output->set_filenames( array(
			'tool_body' => 'forbidden_ext_body.tpl'
		));

		$output->assign_vars(array(
			'L_TITLE_EXT'          => $lang['Title']['attach'],
			'L_EXPLAIN_TO_FORBID'  => nl2br($lang['Explain']['forbid_ext']),
			'L_EXPLAIN_TO_REALLOW' => nl2br($lang['Explain']['reallow_ext']),
			'L_FORBID_EXT'         => $lang['Forbid_ext'],
			'L_REALLOW_EXT'        => $lang['Reallow_ext'],
			'L_VALID_BUTTON'       => $lang['Button']['valid'],
			'L_RESET_BUTTON'       => $lang['Button']['reset'],

			'REALLOW_EXT_BOX'      => $reallow_ext_box,
			'S_HIDDEN_FIELDS'      => $output->getHiddenFields()
		));

		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;

	case 'backup':
		$tables      = array();
		$tables_plus = (!empty($_POST['tables_plus'])) ? array_map('trim', $_POST['tables_plus']) : array();
		$backup_type = (isset($_POST['backup_type'])) ? intval($_POST['backup_type']) : 0;
		$drop_option = !empty($_POST['drop_option']);

		$backup = $db->initBackup();

		if ($backup == null) {
			$output->displayMessage('Database_unsupported');
		}

		$tables_ary = $backup->get_tables();

		foreach ($tables_ary as $tablename => $tabletype) {
			if (!isset($_POST['submit'])) {
				if (!isset($sql_schemas[$tablename])) {
					$tables_plus[] = $tablename;
				}
			}
			else {
				if (isset($sql_schemas[$tablename]) || in_array($tablename, $tables_plus)) {
					$tables[] = array('name' => $tablename, 'type' => $tabletype);
				}
			}
		}

		if (isset($_POST['submit'])) {
			//
			// Lancement de la sauvegarde. Pour commencer, l'entête du fichier sql
			//
			$contents  = $backup->header(sprintf(USER_AGENT_SIG, WANEWSLETTER_VERSION));
			$contents .= $backup->get_other_queries($drop_option);

			fake_header(false);

			foreach ($tables as $tabledata) {
				if ($backup_type != 2) {// save complète ou structure uniquement
					$contents .= $backup->get_table_structure($tabledata, $drop_option);
				}

				if ($backup_type != 1) {// save complète ou données uniquement
					$contents .= $backup->get_table_data($tabledata['name']);
				}

				$contents .= WA_EOL . WA_EOL;

				fake_header(true);
			}

			$filename  = 'wanewsletter_backup.sql';
			$mime_type = 'text/plain';

			//
			// Préparation des données selon l'option demandée
			//
			$contents = compress_filedata($filename, $mime_type, $contents, $compress);

			if ($action == 'download') {
				Attach::send_file($filename, $mime_type, $contents);
			}
			else {
				if (!($fw = fopen(WA_TMPDIR . '/' . $filename, 'wb'))) {
					trigger_error('Impossible d\'écrire le fichier de sauvegarde', E_USER_ERROR);
				}

				fwrite($fw, $contents);
				fclose($fw);

				$output->displayMessage('Success_backup');
			}
		}

		$output->set_filenames(array(
			'tool_body' => 'backup_body.tpl'
		));

		$output->assign_vars(array(
			'L_TITLE_BACKUP'    => $lang['Title']['backup'],
			'L_EXPLAIN_BACKUP'  => nl2br($lang['Explain']['backup']),
			'L_BACKUP_TYPE'     => $lang['Backup_type'],
			'L_FULL'            => $lang['Backup_full'],
			'L_STRUCTURE'       => $lang['Backup_structure'],
			'L_DATA'            => $lang['Backup_data'],
			'L_DROP_OPTION'     => $lang['Drop_option'],
			'L_ACTION'          => $lang['File_action'],
			'L_DOWNLOAD'        => $lang['Download_action'],
			'L_STORE_ON_SERVER' => $lang['Store_action'],
			'L_YES'             => $lang['Yes'],
			'L_NO'              => $lang['No'],
			'L_VALID_BUTTON'    => $lang['Button']['valid'],
			'L_RESET_BUTTON'    => $lang['Button']['reset'],

			'S_HIDDEN_FIELDS'   => $output->getHiddenFields()
		));

		if ($total_tables = count($tables_plus)) {
			if ($total_tables > 10) {
				$total_tables = 10;
			}
			else if ($total_tables < 5) {
				$total_tables = 5;
			}

			$tables_box = '<select id="tables_plus" name="tables_plus[]" multiple="multiple" size="' . $total_tables . '">';
			foreach ($tables_plus as $table_name) {
				$tables_box .= sprintf("<option value=\"%1\$s\">%1\$s</option>\n\t", $table_name);
			}
			$tables_box .= '</select>';

			$output->assign_block_vars('tables_box', array(
				'L_ADDITIONAL_TABLES' => $lang['Additionnal_tables'],
				'S_TABLES_BOX'        => $tables_box
			));
		}

		if (ZIPLIB_LOADED || ZLIB_LOADED || BZIP2_LOADED) {
			$output->assign_block_vars('compress_option', array(
				'L_COMPRESS' => $lang['Compress']
			));

			if (ZIPLIB_LOADED) {
				$output->assign_block_vars('compress_option.zip_compress', array());
			}

			if (ZLIB_LOADED) {
				$output->assign_block_vars('compress_option.gzip_compress', array());
			}

			if (BZIP2_LOADED) {
				$output->assign_block_vars('compress_option.bz2_compress', array());
			}
		}

		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;

	case 'restore':
		if (isset($_POST['submit'])) {
			require WA_ROOTDIR . '/includes/sql/sqlparser.php';

			//
			// On règle le script pour ignorer une déconnexion du client et mener
			// la restauration à son terme
			//
			@ignore_user_abort(true);

			//
			// Import via upload ou fichier local ?
			//
			if (!empty($file_local) || !empty($file_upload['name'])) {
				$unlink = false;

				if (!empty($file_local)) {
					$tmp_filename = WA_ROOTDIR . '/' . str_replace('\\', '/', $file_local);
					$filename     = $file_local;

					if (!file_exists($tmp_filename)) {
						$output->redirect('./tools.php?mode=restore', 4);
						$output->addLine(sprintf($lang['Message']['Error_local'], htmlspecialchars($filename)));
						$output->addLine($lang['Click_return_back'], './tools.php?mode=restore');
						$output->displayMessage();
					}
				}
				else {
					$tmp_filename = $file_upload['tmp_name'];
					$filename     = $file_upload['name'];

					if (!isset($file_upload['error']) && empty($tmp_filename)) {
						$file_upload['error'] = -1;
					}

					if ($file_upload['error'] != UPLOAD_ERR_OK) {
						if (isset($lang['Message']['Upload_error_'.$file_upload['error']])) {
							$upload_error = 'Upload_error_'.$file_upload['error'];
						}
						else {
							$upload_error = 'Upload_error_5';
						}

						$output->displayMessage($upload_error);
					}

					//
					// Si nous n'avons pas d'accès direct au fichier uploadé,
					// il doit être déplacé vers le dossier des fichiers
					// temporaires du script pour être accessible en lecture.
					//
					if (!is_readable($tmp_filename)) {
						$unlink = true;
						$tmp_filename = tempnam(WA_TMPDIR, 'wa');

						if (!move_uploaded_file($file_upload['tmp_name'], $tmp_filename)) {
							unlink($tmp_filename);
							$output->displayMessage('Upload_error_5');
						}
					}
				}

				$data = decompress_filedata($tmp_filename, $filename);

				if ($unlink) {
					unlink($tmp_filename);
				}
			}
			//
			// Aucun fichier de restauration reçu
			//
			else {
				$output->redirect('./tools.php?mode=restore', 4);
				$output->addLine($lang['Message']['No_data_received']);
				$output->addLine($lang['Click_return_back'], './tools.php?mode=restore');
				$output->displayMessage();
			}

			$queries = Dblayer\parseSQL($data);

			$db->beginTransaction();

			fake_header(false);

			foreach ($queries as $query) {
				$db->query($query);
				fake_header(true);
			}

			$db->commit();

			$output->displayMessage('Success_restore');
		}

		$max_filesize = get_max_filesize();

		$output->set_filenames(array(
			'tool_body' => 'restore_body.tpl'
		));

		$output->assign_vars(array(
			'L_TITLE_RESTORE'   => $lang['Title']['restore'],
			'L_EXPLAIN_RESTORE' => nl2br($lang['Explain']['restore']),
			'L_FILE_LOCAL'      => $lang['File_local'],
			'L_VALID_BUTTON'    => $lang['Button']['valid'],
			'L_RESET_BUTTON'    => $lang['Button']['reset'],

			'S_HIDDEN_FIELDS'   => $output->getHiddenFields(),
			'S_ENCTYPE'         => ($max_filesize) ? 'multipart/form-data' : 'application/x-www-form-urlencoded'
		));

		if ($max_filesize) {
			//
			// L'upload est disponible sur le serveur
			// Affichage du champ file pour importation
			//
			$output->assign_block_vars('upload_file', array(
				'L_BROWSE_BUTTON' => $lang['Button']['browse'],
				'L_FILE_UPLOAD'   => $lang['File_upload_restore'],
				'L_MAXIMUM_SIZE'  => sprintf($lang['Maximum_size'], formateSize($max_filesize)),
				'MAX_FILE_SIZE'   => $max_filesize
			));
		}

		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;

	case 'generator':
		if (isset($_POST['generate'])) {
			$url_form = (!empty($_POST['url_form'])) ? trim($_POST['url_form']) : '';

			$code_html  = "<form method=\"post\" action=\"" . htmlspecialchars($url_form) . "\">\n";
			$code_html .= $lang['Email_address'] . " : <input type=\"text\" name=\"email\" maxlength=\"100\" /> &nbsp; \n";

			if ($listdata['liste_format'] == FORMAT_MULTIPLE) {
				$code_html .= $lang['Format'] . " : <select name=\"format\">\n";
				$code_html .= "<option value=\"" . FORMAT_TEXTE . "\">TXT</option>\n";
				$code_html .= "<option value=\"" . FORMAT_HTML . "\">HTML</option>\n";
				$code_html .= "</select>\n";
			}
			else {
				$code_html .= "<input type=\"hidden\" name=\"format\" value=\"$listdata[liste_format]\" />\n";
			}

			$code_html .= "<input type=\"hidden\" name=\"liste\" value=\"$listdata[liste_id]\" />\n";
			$code_html .= "<br />\n";
			$code_html .= "<input type=\"radio\" name=\"action\" value=\"inscription\" checked=\"checked\" /> $lang[Subscribe] <br />\n";
			$code_html .= ($listdata['liste_format'] == FORMAT_MULTIPLE) ? "<input type=\"radio\" name=\"action\" value=\"setformat\" /> $lang[Setformat] <br />\n" : "";
			$code_html .= "<input type=\"radio\" name=\"action\" value=\"desinscription\" /> $lang[Unsubscribe] <br />\n";
			$code_html .= "<input type=\"submit\" name=\"wanewsletter\" value=\"" . $lang['Button']['valid'] . "\" />\n";
			$code_html .= "</form>";

			$code_php  = '<' . "?php\n";
			$code_php .= sprintf("require '%s/newsletter.php';\n", WA_ROOTDIR);
			$code_php .= '?' . ">\n";

			$output->set_filenames(array(
				'tool_body' => 'result_generator_body.tpl'
			));

			$output->assign_vars(array(
				'L_TITLE_GENERATOR'   => $lang['Title']['generator'],
				'L_EXPLAIN_CODE_HTML' => nl2br($lang['Explain']['code_html']),
				'L_EXPLAIN_CODE_PHP'  => nl2br($lang['Explain']['code_php']),

				'CODE_HTML' => htmlspecialchars($code_html, ENT_NOQUOTES),
				'CODE_PHP'  => htmlspecialchars($code_php, ENT_NOQUOTES)
			));
		}
		else {
			$output->set_filenames(array(
				'tool_body' => 'generator_body.tpl'
			));

			$output->assign_vars(array(
				'L_TITLE_GENERATOR'   => $lang['Title']['generator'],
				'L_EXPLAIN_GENERATOR' => nl2br($lang['Explain']['generator']),
				'L_TARGET_FORM'       => $lang['Target_form'],
				'L_VALID_BUTTON'      => $lang['Button']['valid'],

				'S_HIDDEN_FIELDS' => $output->getHiddenFields()
			));
		}

		$output->assign_var_from_handle('TOOL_BODY', 'tool_body');
		break;
}

$output->pparse('body');

$output->page_footer();
