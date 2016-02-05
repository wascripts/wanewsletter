<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
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
		$output->message('Compress_unsupported');
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

$mode = filter_input(INPUT_GET, 'mode');

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
			$output->message();
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

if (!in_array($mode, ['backup','restore','debug', '']) && !$_SESSION['liste']) {
	$output->header();
	$output->listbox($auth_type, true, $url_page)->pparse();
	$output->footer();
}
else if ($_SESSION['liste']) {
	if (!$auth->check_auth($auth_type, $_SESSION['liste'])) {
		$output->message('Not_' . $auth->auth_ary[$auth_type]);
	}

	$listdata = $auth->listdata[$_SESSION['liste']];
}

//
// Affichage de la boîte de sélection des modules
//
if (!isset($_POST['submit'])) {
	$tools_ary = ['export', 'import', 'ban', 'generator'];

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

	$output->header();

	$main = new Template('tools_body.tpl');

	$main->assign([
		'L_TITLE'        => $lang['Title']['tools'],
		'L_EXPLAIN'      => nl2br($lang['Explain']['tools']),
		'L_SELECT_TOOL'  => $lang['Select_tool'],
		'L_VALID_BUTTON' => $lang['Button']['valid'],

		'S_TOOLS_BOX'    => $tools_box
	]);

	if ($mode != 'backup' && $mode != 'restore') {
		$main->assign([
			'LISTBOX' => $output->listbox($auth_type, false, $url_page)
		]);
	}
}

//
// On vérifie la présence des extensions nécessaires pour les différents formats de fichiers proposés
//
define('ZIPLIB_LOADED', extension_loaded('zip'));
define('ZLIB_LOADED',   extension_loaded('zlib'));
define('BZIP2_LOADED',  extension_loaded('bz2'));

$user_agent = filter_input(INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_SPECIAL_CHARS);
$EOL = (stripos($user_agent, 'Win')) ? "\r\n" : "\n";

//
// On augmente le temps d'exécution du script
// Certains hébergeurs empèchent pour des raisons évidentes cette possibilité
// Si c'est votre cas, vous êtes mal barré
//
@set_time_limit(3600);

switch ($mode) {
	case 'debug':
		print_debug_infos();
		$output->footer();
		break;

	case 'export':
		if (isset($_POST['submit'])) {
			if ($listdata['liste_format'] != FORMAT_MULTIPLE) {
				$format = $listdata['liste_format'];
			}
			else {
				$format = filter_input(INPUT_POST, 'format', FILTER_VALIDATE_INT);
				if (!in_array($format, [FORMAT_TEXTE, FORMAT_HTML])) {
					$format = FORMAT_TEXTE;
				}
			}

			$sql = "SELECT a.abo_email
				FROM " . ABONNES_TABLE . " AS a
					INNER JOIN " . ABO_LISTE_TABLE . " AS al ON al.abo_id = a.abo_id
						AND al.liste_id  = $listdata[liste_id]
						AND al.format    = $format
						AND al.confirmed = " . SUBSCRIBE_CONFIRMED . "
				WHERE a.abo_status = " . ABO_ACTIVE;
			$result = $db->query($sql);

			$contents = '';
			if (filter_input(INPUT_POST, 'eformat') == 'xml') {
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
				$glue = trim(filter_input(INPUT_POST, 'glue'));
				if (!$glue) {
					$glue = $EOL;
				}

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
			$compress = filter_input(INPUT_POST, 'compress');
			$contents = compress_filedata($filename, $mime_type, $contents, $compress);

			if (filter_input(INPUT_POST, 'action') == 'download') {
				sendfile($filename, $mime_type, $contents);
			}
			else {
				if (!($fw = fopen(WA_TMPDIR . '/' . $filename, 'wb'))) {
					trigger_error('Impossible d\'écrire le fichier de sauvegarde', E_USER_ERROR);
				}

				fwrite($fw, $contents);
				fclose($fw);

				$output->message('Success_export');
			}
		}

		$template = new Template('export_body.tpl');

		$template->assign([
			'L_TITLE_EXPORT'    => $lang['Title']['export'],
			'L_EXPLAIN_EXPORT'  => nl2br($lang['Explain']['export']),
			'L_EXPORT_FORMAT'   => $lang['Export_format'],
			'L_PLAIN_TEXT'      => $lang['Plain_text'],
			'L_GLUE'            => $lang['Char_glue'],
			'L_ACTION'          => $lang['File_action'],
			'L_DOWNLOAD'        => $lang['Download_action'],
			'L_STORE_ON_SERVER' => $lang['Store_action'],
			'L_VALID_BUTTON'    => $lang['Button']['valid'],
			'L_RESET_BUTTON'    => $lang['Button']['reset']
		]);

		if (ZIPLIB_LOADED || ZLIB_LOADED || BZIP2_LOADED) {
			$template->assignToBlock('compress_option', [
				'L_COMPRESS' => $lang['Compress'],
				'L_NO'       => $lang['No']
			]);

			if (ZIPLIB_LOADED) {
				$template->assignToBlock('compress_option.zip_compress');
			}

			if (ZLIB_LOADED) {
				$template->assignToBlock('compress_option.gzip_compress');
			}

			if (BZIP2_LOADED) {
				$template->assignToBlock('compress_option.bz2_compress');
			}
		}

		if ($listdata['liste_format'] == FORMAT_MULTIPLE) {
			require 'includes/functions.box.php';

			$template->assignToBlock('format_box', [
				'L_FORMAT'   => $lang['Format_to_export'],
				'FORMAT_BOX' => format_box('format')
			]);
		}

		$main->assign(['TOOL_BODY' => $template]);
		break;

	case 'import':
		if (isset($_POST['submit'])) {
			$upload_file = (!empty($_FILES['upload_file'])) ? $_FILES['upload_file'] : null;
			$local_file  = trim(filter_input(INPUT_POST, 'local_file'));
			$list_email  = trim(filter_input(INPUT_POST, 'list_email'));

			if (is_array($upload_file) && $upload_file['error'] == UPLOAD_ERR_NO_FILE) {
				$upload_file = null;
			}

			$list_tmp    = '';
			$data_is_xml = false;

			//
			// Import via upload ou fichier local ?
			//
			if ($local_file || $upload_file) {
				$unlink = false;

				if ($local_file) {
					$tmp_filename = WA_ROOTDIR . '/' . str_replace('\\', '/', $local_file);
					$filename     = $local_file;

					if (!file_exists($tmp_filename)) {
						$output->redirect('./tools.php?mode=import', 4);
						$output->addLine(sprintf($lang['Message']['Error_local'], htmlspecialchars($filename)));
						$output->addLine($lang['Click_return_back'], './tools.php?mode=import');
						$output->message();
					}
				}
				else {
					$tmp_filename = $upload_file['tmp_name'];
					$filename     = $upload_file['name'];

					if (!isset($upload_file['error']) && empty($tmp_filename)) {
						$upload_file['error'] = -1;
					}

					if ($upload_file['error'] != UPLOAD_ERR_OK) {
						if (isset($lang['Message']['Upload_error_'.$upload_file['error']])) {
							$upload_error = 'Upload_error_'.$upload_file['error'];
						}
						else {
							$upload_error = 'Upload_error_5';
						}

						$output->message($upload_error);
					}

					//
					// Si nous n'avons pas d'accès direct au fichier uploadé,
					// il doit être déplacé vers le dossier des fichiers
					// temporaires du script pour être accessible en lecture.
					//
					if (!is_readable($tmp_filename)) {
						$unlink = true;
						$tmp_filename = tempnam(WA_TMPDIR, 'wa');

						if (!move_uploaded_file($upload_file['tmp_name'], $tmp_filename)) {
							unlink($tmp_filename);
							$output->message('Upload_error_5');
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

			if (!empty($list_tmp) && $data_is_xml) {
				$emails = [];

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
						$output->message(sprintf(
							$lang['Message']['Invalid_xml_data'],
							htmlspecialchars(xml_error_string(xml_get_error_code($parser)), ENT_NOQUOTES),
							xml_get_current_line_number($parser)
						));
					}

					xml_parser_free($parser);
				}
				else {
					$output->message('Xml_ext_needed');
				}
			}
			else {
				$glue = trim(filter_input(INPUT_POST, 'glue'));
				if (!$glue) {
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
				$output->message();
			}

			$report = '';

			$emails = array_slice($emails, 0, MAX_IMPORT);
			$emails = array_map('trim', $emails);
			$emails = array_unique($emails);

			//
			// Vérification syntaxique des emails
			//
			$emails = array_filter($emails,
				function ($email) use (&$lang, &$report, &$EOL) {
					if (\Wamailer\Mailer::checkMailSyntax($email)) {
						return true;
					}
					else {
						$report .= sprintf('%s : %s%s', $email, $lang['Message']['Invalid_email2'], $EOL);
						return false;
					}
				}
			);

			if (count($emails) > 0) {
				if ($listdata['liste_format'] != FORMAT_MULTIPLE) {
					$format = $listdata['liste_format'];
				}
				else {
					$format = filter_input(INPUT_POST, 'format', FILTER_VALIDATE_INT);
					if (!in_array($format, [FORMAT_TEXTE, FORMAT_HTML])) {
						$format = FORMAT_TEXTE;
					}
				}

				$current_time = time();
				$emails_ok    = [];

				$sql_emails = array_map('strtolower', $emails);
				$sql_emails = array_map([$db, 'escape'], $sql_emails);

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
						$sql_data = [];
						$sql_data['abo_id']        = $abodata['abo_id'];
						$sql_data['liste_id']      = $listdata['liste_id'];
						$sql_data['format']        = $format;
						$sql_data['register_key']  = generate_key(20, false);
						$sql_data['register_date'] = $current_time;
						$sql_data['confirmed']     = ($abodata['abo_status'] == ABO_ACTIVE)
							? SUBSCRIBE_CONFIRMED : SUBSCRIBE_NOT_CONFIRMED;

						$db->insert(ABO_LISTE_TABLE, $sql_data);
					}
					else {
						$report .= sprintf('%s : %s%s',
							$abodata['abo_email'],
							$lang['Message']['Allready_reg'],
							$EOL
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

					$sql_data = [];
					$sql_data['abo_email']  = $email;
					$sql_data['abo_status'] = ABO_ACTIVE;

					try {
						$db->insert(ABONNES_TABLE, $sql_data);
					}
					catch (Dblayer\Exception $e) {
						$report .= sprintf('%s : SQL error (#%d: %s)%s', $email, $db->errno, $db->error, $EOL);
						$db->rollBack();
						continue;
					}

					$sql_data = [];
					$sql_data['abo_id']        = $db->lastInsertId();
					$sql_data['liste_id']      = $listdata['liste_id'];
					$sql_data['format']        = $format;
					$sql_data['register_key']  = generate_key(20, false);
					$sql_data['register_date'] = $current_time;
					$sql_data['confirmed']     = SUBSCRIBE_CONFIRMED;

					$db->insert(ABO_LISTE_TABLE, $sql_data);

					$db->commit();

					fake_header();
				}
			}

			//
			// Selon que des emails ont été refusés ou pas, affichage du message correspondant
			// et mise à disposition éventuelle du rapport d'erreurs
			//
			if ($report != '') {
				$report_str  = '#' . $EOL;
				$report_str .= '# Rapport des adresses emails refusées / Bad address email report' . $EOL;
				$report_str .= '#' . $EOL;
				$report_str .= $EOL;
				$report_str .= $report . $EOL;
				$report_str .= '# END' . $EOL;

				$url = 'data:text/plain;base64,' . base64_encode($report_str);
				$output->addLine($lang['Message']['Success_import3'], $url);

				file_put_contents(WA_TMPDIR . '/wa_import_report.txt', $report_str);
			}
			else {
				$output->addLine($lang['Message']['Success_import']);
			}

			$output->message();
		}

		$max_filesize = get_max_filesize();

		$template = new Template('import_body.tpl');

		$template->assign([
			'L_TITLE_IMPORT'   => $lang['Title']['import'],
			'L_EXPLAIN_IMPORT' => nl2br(sprintf($lang['Explain']['import'],
				MAX_IMPORT,
				sprintf('<a href="%s">', wan_get_faq_url('import')),
				'</a>'
			)),
			'L_GLUE'           => $lang['Char_glue'],
			'L_LOCAL_FILE'     => $lang['File_local'],
			'L_VALID_BUTTON'   => $lang['Button']['valid'],
			'L_RESET_BUTTON'   => $lang['Button']['reset'],

			'S_ENCTYPE'        => ($max_filesize) ? 'multipart/form-data' : 'application/x-www-form-urlencoded'
		]);

		if ($listdata['liste_format'] == FORMAT_MULTIPLE) {
			require 'includes/functions.box.php';

			$template->assignToBlock('format_box', [
				'L_FORMAT'   => $lang['Format_to_import'],
				'FORMAT_BOX' => format_box('format')
			]);
		}

		if ($max_filesize) {
			//
			// L'upload est disponible sur le serveur
			// Affichage du champ file pour importation
			//
			$template->assignToBlock('upload_file', [
				'L_BROWSE_BUTTON' => $lang['Button']['browse'],
				'L_UPLOAD_FILE'   => $lang['File_upload'],
				'L_MAXIMUM_SIZE'  => sprintf($lang['Maximum_size'], formateSize($max_filesize)),
				'MAX_FILE_SIZE'   => $max_filesize
			]);
		}

		$main->assign(['TOOL_BODY' => $template]);
		break;

	case 'ban':
		if (isset($_POST['submit'])) {
			$pattern   = trim(u::filter_input(INPUT_POST, 'pattern'));
			$unban_ids = (array) filter_input(INPUT_POST, 'unban_ids',
				FILTER_VALIDATE_INT,
				FILTER_REQUIRE_ARRAY
			);
			$unban_ids = array_filter($unban_ids);

			if ($pattern) {
				$pattern_list = explode(',', $pattern);
				$sql_dataset  = [];

				foreach ($pattern_list as $pattern) {
					$sql_dataset[] = [
						'liste_id'  => $listdata['liste_id'],
						'ban_email' => trim($pattern)
					];
				}

				if (count($sql_dataset) > 0) {
					$db->insert(BAN_LIST_TABLE, $sql_dataset);
				}

				unset($sql_dataset);
			}

			if (count($unban_ids) > 0) {
				$sql = "DELETE FROM " . BAN_LIST_TABLE . "
					WHERE ban_id IN (" . implode(', ', $unban_ids) . ")";
				$db->query($sql);

				//
				// Optimisation des tables
				//
				$db->vacuum(BAN_LIST_TABLE);
			}

			$output->redirect('./tools.php?mode=ban', 4);
			$output->addLine($lang['Message']['Success_modif']);
			$output->addLine($lang['Click_return_back'], './tools.php?mode=ban');
			$output->addLine($lang['Click_return_index'], './index.php');
			$output->message();
		}

		$sql = "SELECT ban_id, ban_email
			FROM " . BAN_LIST_TABLE . "
			WHERE liste_id = " . $listdata['liste_id'];
		$result = $db->query($sql);

		$unban_email_box = '<select id="unban_ids" name="unban_ids[]" multiple="multiple" size="10">';
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

		$template = new Template('ban_list_body.tpl');

		$template->assign([
			'L_TITLE_BAN'     => $lang['Title']['ban'],
			'L_EXPLAIN_BAN'   => nl2br($lang['Explain']['ban']),
			'L_EXPLAIN_UNBAN' => nl2br($lang['Explain']['unban']),
			'L_BAN_EMAIL'     => $lang['Ban_email'],
			'L_UNBAN_EMAIL'   => $lang['Unban_email'],
			'L_VALID_BUTTON'  => $lang['Button']['valid'],
			'L_RESET_BUTTON'  => $lang['Button']['reset'],

			'UNBAN_EMAIL_BOX' => $unban_email_box
		]);

		$main->assign(['TOOL_BODY' => $template]);
		break;

	case 'attach':
		if (isset($_POST['submit'])) {
			$ext_list = trim(u::filter_input(INPUT_POST, 'ext_list'));
			$ext_ids  = (array) filter_input(INPUT_POST, 'ext_ids',
				FILTER_VALIDATE_INT,
				FILTER_REQUIRE_ARRAY
			);
			$ext_ids = array_filter($ext_ids);

			if ($ext_list != '') {
				$ext_list    = explode(',', $ext_list);
				$sql_dataset = [];

				foreach ($ext_list as $ext) {
					if (preg_match('/^[\w_-]+$/', $ext)) {
						$sql_dataset[] = [
							'liste_id' => $listdata['liste_id'],
							'fe_ext'   => trim(mb_strtolower($ext))
						];
					}
				}

				if (count($sql_dataset) > 0) {
					$db->insert(FORBIDDEN_EXT_TABLE, $sql_dataset);
				}

				unset($sql_dataset);
			}

			if (count($ext_ids) > 0) {
				$sql = "DELETE FROM " . FORBIDDEN_EXT_TABLE . "
					WHERE fe_id IN (" . implode(', ', $ext_ids) . ")";
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
			$output->message();
		}

		$sql = "SELECT fe_id, fe_ext
			FROM " . FORBIDDEN_EXT_TABLE . "
			WHERE liste_id = " . $listdata['liste_id'];
		$result = $db->query($sql);

		$reallow_ext_box = '<select id="ext_ids" name="ext_ids[]" multiple="multiple" size="10">';
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

		$template = new Template('forbidden_ext_body.tpl');

		$template->assign([
			'L_TITLE_EXT'          => $lang['Title']['attach'],
			'L_EXPLAIN_TO_FORBID'  => nl2br($lang['Explain']['forbid_ext']),
			'L_EXPLAIN_TO_REALLOW' => nl2br($lang['Explain']['reallow_ext']),
			'L_FORBID_EXT'         => $lang['Forbid_ext'],
			'L_REALLOW_EXT'        => $lang['Reallow_ext'],
			'L_VALID_BUTTON'       => $lang['Button']['valid'],
			'L_RESET_BUTTON'       => $lang['Button']['reset'],

			'REALLOW_EXT_BOX'      => $reallow_ext_box
		]);

		$main->assign(['TOOL_BODY' => $template]);
		break;

	case 'backup':
		$tables_plus = (array) filter_input(INPUT_POST, 'tables_plus',
			FILTER_DEFAULT,
			FILTER_REQUIRE_ARRAY
		);
		$backup_type = filter_input(INPUT_POST, 'backup_type', FILTER_VALIDATE_INT);
		$drop_option = filter_input(INPUT_POST, 'drop_option', FILTER_VALIDATE_BOOLEAN);

		if (!($backup = $db->initBackup())) {
			$output->message('Database_unsupported');
		}

		$tables_list = $backup->get_tables();
		$tables      = [];

		foreach ($tables_list as $tablename => $tabletype) {
			if (!isset($_POST['submit'])) {
				if (!isset($sql_schemas[$tablename])) {
					$tables_plus[] = $tablename;
				}
			}
			else {
				if (isset($sql_schemas[$tablename]) || in_array($tablename, $tables_plus)) {
					$tables[] = ['name' => $tablename, 'type' => $tabletype];
				}
			}
		}

		if (isset($_POST['submit'])) {
			//
			// Lancement de la sauvegarde. Pour commencer, l'entête du fichier sql
			//
			$contents  = $backup->header(sprintf(USER_AGENT_SIG, WANEWSLETTER_VERSION));
			$contents .= $backup->get_other_queries($drop_option);

			foreach ($tables as $tabledata) {
				if ($backup_type != 2) {// save complète ou structure uniquement
					$contents .= $backup->get_table_structure($tabledata, $drop_option);
				}

				if ($backup_type != 1) {// save complète ou données uniquement
					$contents .= $backup->get_table_data($tabledata['name']);
				}

				$contents .= $EOL . $EOL;

				fake_header();
			}

			$filename  = 'wanewsletter_backup.sql';
			$mime_type = 'text/plain';

			//
			// Préparation des données selon l'option demandée
			//
			$compress = filter_input(INPUT_POST, 'compress');
			$contents = compress_filedata($filename, $mime_type, $contents, $compress);

			if (filter_input(INPUT_POST, 'action') == 'download') {
				sendfile($filename, $mime_type, $contents);
			}
			else {
				if (!($fw = fopen(WA_TMPDIR . '/' . $filename, 'wb'))) {
					trigger_error('Impossible d\'écrire le fichier de sauvegarde', E_USER_ERROR);
				}

				fwrite($fw, $contents);
				fclose($fw);

				$output->message('Success_backup');
			}
		}

		$template = new Template('backup_body.tpl');

		$template->assign([
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
			'L_RESET_BUTTON'    => $lang['Button']['reset']
		]);

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

			$template->assignToBlock('tables_box', [
				'L_ADDITIONAL_TABLES' => $lang['Additionnal_tables'],
				'S_TABLES_BOX'        => $tables_box
			]);
		}

		if (ZIPLIB_LOADED || ZLIB_LOADED || BZIP2_LOADED) {
			$template->assignToBlock('compress_option', [
				'L_COMPRESS' => $lang['Compress']
			]);

			if (ZIPLIB_LOADED) {
				$template->assignToBlock('compress_option.zip_compress');
			}

			if (ZLIB_LOADED) {
				$template->assignToBlock('compress_option.gzip_compress');
			}

			if (BZIP2_LOADED) {
				$template->assignToBlock('compress_option.bz2_compress');
			}
		}

		$main->assign(['TOOL_BODY' => $template]);
		break;

	case 'restore':
		if (isset($_POST['submit'])) {
			require 'includes/dblayer/sqlparser.php';

			$upload_file = (!empty($_FILES['upload_file'])) ? $_FILES['upload_file'] : null;
			$local_file  = trim(filter_input(INPUT_POST, 'local_file'));

			if (is_array($upload_file) && $upload_file['error'] == UPLOAD_ERR_NO_FILE) {
				$upload_file = null;
			}

			//
			// On règle le script pour ignorer une déconnexion du client et mener
			// la restauration à son terme
			//
			@ignore_user_abort(true);

			//
			// Import via upload ou fichier local ?
			//
			if ($local_file || $upload_file) {
				$unlink = false;

				if ($local_file) {
					$tmp_filename = WA_ROOTDIR . '/' . str_replace('\\', '/', $local_file);
					$filename     = $local_file;

					if (!file_exists($tmp_filename)) {
						$output->redirect('./tools.php?mode=restore', 4);
						$output->addLine(sprintf($lang['Message']['Error_local'], htmlspecialchars($filename)));
						$output->addLine($lang['Click_return_back'], './tools.php?mode=restore');
						$output->message();
					}
				}
				else {
					$tmp_filename = $upload_file['tmp_name'];
					$filename     = $upload_file['name'];

					if (!isset($upload_file['error']) && empty($tmp_filename)) {
						$upload_file['error'] = -1;
					}

					if ($upload_file['error'] != UPLOAD_ERR_OK) {
						if (isset($lang['Message']['Upload_error_'.$upload_file['error']])) {
							$upload_error = 'Upload_error_'.$upload_file['error'];
						}
						else {
							$upload_error = 'Upload_error_5';
						}

						$output->message($upload_error);
					}

					//
					// Si nous n'avons pas d'accès direct au fichier uploadé,
					// il doit être déplacé vers le dossier des fichiers
					// temporaires du script pour être accessible en lecture.
					//
					if (!is_readable($tmp_filename)) {
						$unlink = true;
						$tmp_filename = tempnam(WA_TMPDIR, 'wa');

						if (!move_uploaded_file($upload_file['tmp_name'], $tmp_filename)) {
							unlink($tmp_filename);
							$output->message('Upload_error_5');
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
				$output->message();
			}

			$queries = Dblayer\parseSQL($data);

			$db->beginTransaction();

			foreach ($queries as $query) {
				$db->query($query);
				fake_header();
			}

			$db->commit();

			$output->message('Success_restore');
		}

		$max_filesize = get_max_filesize();

		$template = new Template('restore_body.tpl');

		$template->assign([
			'L_TITLE_RESTORE'   => $lang['Title']['restore'],
			'L_EXPLAIN_RESTORE' => nl2br($lang['Explain']['restore']),
			'L_LOCAL_FILE'      => $lang['File_local'],
			'L_VALID_BUTTON'    => $lang['Button']['valid'],
			'L_RESET_BUTTON'    => $lang['Button']['reset'],

			'S_ENCTYPE'         => ($max_filesize) ? 'multipart/form-data' : 'application/x-www-form-urlencoded'
		]);

		if ($max_filesize) {
			//
			// L'upload est disponible sur le serveur
			// Affichage du champ file pour importation
			//
			$template->assignToBlock('upload_file', [
				'L_BROWSE_BUTTON' => $lang['Button']['browse'],
				'L_UPLOAD_FILE'   => $lang['File_upload_restore'],
				'L_MAXIMUM_SIZE'  => sprintf($lang['Maximum_size'], formateSize($max_filesize)),
				'MAX_FILE_SIZE'   => $max_filesize
			]);
		}

		$main->assign(['TOOL_BODY' => $template]);
		break;

	case 'generator':
		if (isset($_POST['generate'])) {
			$url_form = trim(filter_input(INPUT_POST, 'url_form'));

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

			$template = new Template('result_generator_body.tpl');

			$template->assign([
				'L_TITLE_GENERATOR'   => $lang['Title']['generator'],
				'L_EXPLAIN_CODE_HTML' => nl2br($lang['Explain']['code_html']),
				'L_EXPLAIN_CODE_PHP'  => nl2br($lang['Explain']['code_php']),

				'CODE_HTML' => htmlspecialchars($code_html, ENT_NOQUOTES),
				'CODE_PHP'  => htmlspecialchars($code_php, ENT_NOQUOTES)
			]);
		}
		else {
			$template = new Template('generator_body.tpl');

			$template->assign([
				'L_TITLE_GENERATOR'   => $lang['Title']['generator'],
				'L_EXPLAIN_GENERATOR' => nl2br($lang['Explain']['generator']),
				'L_TARGET_FORM'       => $lang['Target_form'],
				'L_VALID_BUTTON'      => $lang['Button']['valid']
			]);
		}

		$main->assign(['TOOL_BODY' => $template]);
		break;
}

$main->pparse();
$output->footer();
