<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@webnaute.net>
 * @link      http://dev.webnaute.net/wanewsletter/
 * @copyright 2002-2021 Aurélien Maille
 * @license   https://www.gnu.org/licenses/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

$return_message = true;
$message = '';

require './newsletter.php';

$sql = "SELECT liste_id, liste_name, liste_format
	FROM " . LISTE_TABLE . "
	WHERE liste_public = 1";
$result = $db->query($sql);

$list_box = '<select id="liste" name="liste">';

if ($row = $result->fetch()) {
	do {
		if ($row['liste_format'] == FORMAT_TEXT) {
			$format = $lang['Text'];
		}
		else if ($row['liste_format'] == FORMAT_HTML) {
			$format = 'html';
		}
		else {
			$format = sprintf('%s/html', $lang['Text']);
		}

		$list_box .= sprintf('<option value="%d"> %s (%s) </option>',
			$row['liste_id'],
			htmlspecialchars($row['liste_name']),
			$format
		);
	}
	while ($row = $result->fetch());
}
else {
	$message = $lang['Message']['No_list_found'];
}

$list_box .= '</select>';

$output->httpHeaders();

$template = new Template('subscribe_body.tpl');

$template->assign([
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

	'FORMAT_BOX'      => format_box('format'),
	'LIST_BOX'        => $list_box,
	'MESSAGE'         => $message
]);

$template->pparse();

//
// On réactive le gestionnaire d'erreur précédent
//
restore_error_handler();
