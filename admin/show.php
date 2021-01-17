<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@webnaute.net>
 * @link      http://dev.webnaute.net/wanewsletter/
 * @copyright 2002-2021 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

require './start.inc.php';

if (!$auth->check(Auth::VIEW, $_SESSION['liste'])) {
	http_response_code(401);
	$output->basic($lang['Message']['Not_auth_view']);
}

$listdata = $auth->getLists(Auth::VIEW)[$_SESSION['liste']];

$file_id  = (int) filter_input(INPUT_GET, 'fid', FILTER_VALIDATE_INT);
$filename = trim(filter_input(INPUT_GET, 'file'));

$attach = new Attach();
$file = $attach->getFile($filename ?: $file_id);

if ($file) {
	if (!is_readable($file['path'])) {
		http_response_code(500);
		$output->basic(sprintf($lang['Message']['File_not_exists'], ''));
	}

	$maxAge = 0;
	$lastModified = filemtime($file['path']);
	$canUseCache  = true;
	$cachetime    = (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : 0;

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

	$fp = fopen($file['path'], 'rb');
	sendfile($file['name'], $file['type'], $fp, false);
}
else {
	http_response_code(404);
	$output->basic(sprintf($lang['Message']['File_not_exists'], ''));
}
