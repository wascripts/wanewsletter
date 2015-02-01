<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

define('IN_NEWSLETTER', true);

require '../admin/pagestart.php';

if (!$auth->check_auth(Auth::VIEW, $_SESSION['liste'])) {
	http_response_code(401);
	plain_error($lang['Message']['Not_auth_view']);
}

$listdata = $auth->listdata[$_SESSION['liste']];

$file_id  = (!empty($_GET['fid'])) ? intval($_GET['fid']) : 0;
$filename = (!empty($_GET['file'])) ? trim($_GET['file']) : '';

if ($filename != '') {
	$sql_where = 'jf.file_real_name = \'' . $db->escape($filename) . '\'';
}
else {
	$sql_where = 'jf.file_id = ' . $file_id;
}

$sql = "SELECT jf.file_real_name, jf.file_physical_name, jf.file_size, jf.file_mimetype
	FROM " . JOINED_FILES_TABLE . " AS jf
		INNER JOIN " . LOG_FILES_TABLE . " AS lf ON lf.file_id = jf.file_id
		INNER JOIN " . LOG_TABLE . " AS l ON l.log_id = lf.log_id
			AND l.liste_id = $listdata[liste_id]
	WHERE $sql_where";
$result = $db->query($sql);

if ($filedata = $result->fetch()) {
	if ($nl_config['use_ftp']) {
		$attach = new Attach();
		$tmp_filename = $attach->ftp_to_tmp($filedata);
	}
	else {
		$tmp_filename = WA_ROOTDIR . '/' . $nl_config['upload_path'] . $filedata['file_physical_name'];
	}

	if (!is_readable($tmp_filename)) {
		http_response_code(500);
		plain_error('Impossible de récupérer le contenu du fichier (fichier non accessible en lecture)');
	}

	$maxAge = 0;
	$lastModified = filemtime($tmp_filename);
	$canUseCache  = true;
	$cachetime    = (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) ? @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : 0;

	if (!empty($_SERVER['HTTP_CACHE_CONTROL'])) {
		$canUseCache = !preg_match('/no-cache/i', $_SERVER['HTTP_CACHE_CONTROL']);
	}
	else if (!empty($_SERVER['HTTP_PRAGMA'])) {// HTTP 1.0
		$canUseCache = !preg_match('/no-cache/i', $_SERVER['HTTP_PRAGMA']);
	}

	if ($lastModified <= $cachetime && $canUseCache) {
		http_response_code(304);
		header('Date: ' . gmdate(DATE_RFC1123));
		exit;
	}

	header('Date: ' . gmdate(DATE_RFC1123));
	header('Last-Modified: ' . gmdate(DATE_RFC1123, $lastModified));
	header('Expires: ' . gmdate(DATE_RFC1123, (time() + $maxAge)));// HTTP 1.0
	header('Pragma: private');// HTTP 1.0
	header('Cache-Control: private, must-revalidate, max-age='.$maxAge);
	header('Content-Disposition: inline; filename="' . $filedata['file_real_name'] . '"');
	header('Content-Length: ' . $filedata['file_size']);

	$data = file_get_contents($tmp_filename);

	if (strcasecmp($filedata['file_mimetype'], 'image/svg+xml') === 0) {
		$charset = 'UTF-8';
		if (preg_match('/^<\?xml(.+?)\?>/', $data, $m)) {
			if (preg_match('/encoding="([a-z0-9.:_-]+)"/i', $m[0], $m2)) {
				$charset = $m2[1];
			}
		}

		header('Content-Type: ' . $filedata['file_mimetype'] . '; charset=' . $charset);
	}
	else {
		header('Content-Type: ' . $filedata['file_mimetype']);
	}

	echo $data;

	//
	// Si l'option FTP est utilisée, suppression du fichier temporaire
	//
	if ($nl_config['use_ftp']) {
		$attach->remove_file($tmp_filename);
	}

	exit;
}
else {
	http_response_code(404);
	plain_error('Unknown file !');
}
