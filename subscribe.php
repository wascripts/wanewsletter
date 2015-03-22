<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

define('IN_SUBSCRIBE', true);

require './newsletter.php';

$list_box = '';

$sql = "SELECT liste_id, liste_name, liste_format
	FROM " . LISTE_TABLE . "
	WHERE liste_public = 1";
$result = $db->query($sql);

$list_box = '<select id="liste" name="liste">';

if ($row = $result->fetch()) {
	do {
		if ($row['liste_format'] == FORMAT_TEXTE) {
			$format = 'txt';
		}
		else if ($row['liste_format'] == FORMAT_HTML) {
			$format = 'html';
		}
		else {
			$format = 'txt &amp; html';
		}

		$list_box .= sprintf('<option value="%d"> %s (%s) </option>',
			$row['liste_id'],
			wan_htmlspecialchars($row['liste_name']),
			$format
		);
	}
	while ($row = $result->fetch());
}
else {
	$message = 'No list found';
}

$list_box .= '</select>';

$output->send_headers();

$output->set_filenames(array(
	'body' => 'subscribe_body.tpl'
));

$output->assign_vars(array(
	'PAGE_TITLE'      => $lang['Title']['form'],
	'L_INVALID_EMAIL' => str_replace('\'', '\\\'', $lang['Message']['Invalid_email']),
	'L_PAGE_LOADING'  => str_replace('\'', '\\\'', $lang['Page_loading']),
	'L_EMAIL'         => $lang['Email_address'],
	'L_FORMAT'        => $lang['Format'],
	'L_DIFF_LIST'     => $lang['Diff_list'],
	'L_SUBSCRIBE'     => $lang['Subscribe'],
	'L_SETFORMAT'     => $lang['Setformat'],
	'L_UNSUBSCRIBE'   => $lang['Unsubscribe'],
	'L_VALID_BUTTON'  => $lang['Button']['valid'],

	'LIST_BOX' => $list_box,
	'MESSAGE'  => $message
));

$output->pparse('body');

//
// On réactive le gestionnaire d'erreur précédent
//
restore_error_handler();
