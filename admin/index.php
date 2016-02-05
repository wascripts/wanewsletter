<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

require './start.inc.php';

$num_inscrits = $num_temp = $num_logs = $last_log = $filesize = 0;

$liste_ids = $auth->check_auth(Auth::VIEW);

if (count($liste_ids) > 0) {
	$sql_liste_ids = implode(', ', $liste_ids);

	//
	// Récupération des nombres d'inscrits
	//
	$sql = "SELECT COUNT(abo_id) AS num_abo, abo_status
		FROM " . ABONNES_TABLE . "
		WHERE abo_id IN(
			SELECT DISTINCT(abo_id)
			FROM " . ABO_LISTE_TABLE . "
			WHERE liste_id IN($sql_liste_ids)
		)
		GROUP BY abo_status";
	$result = $db->query($sql);

	while ($row = $result->fetch()) {
		if ($row['abo_status'] == ABO_ACTIVE) {
			$num_inscrits = $row['num_abo'];
		}
		else {
			$num_temp = $row['num_abo'];
		}
	}

	//
	// Récupération du nombre d'archives
	//
	$sql = "SELECT SUM(liste_numlogs) AS num_logs
		FROM " . LISTE_TABLE . "
		WHERE liste_id IN($sql_liste_ids)";
	$result = $db->query($sql);

	if ($tmp = $result->column('num_logs')) {
		$num_logs = $tmp;
	}

	//
	// Récupération de la date du dernier envoi
	//
	$sql = "SELECT MAX(log_date) AS last_log
		FROM " . LOG_TABLE . "
		WHERE log_status = " . STATUS_SENT . "
			AND liste_id IN($sql_liste_ids)";
	$result = $db->query($sql);

	if ($tmp = $result->column('last_log')) {
		$last_log = $tmp;
	}

	//
	// Espace disque occupé
	//
	$sql = "SELECT SUM(jf.file_size) AS totalsize
		FROM " . JOINED_FILES_TABLE . " AS jf
		WHERE jf.file_id IN(
			SELECT lf.file_id
			FROM " . LOG_FILES_TABLE . " AS lf
				INNER JOIN " . LOG_TABLE . " AS l ON l.log_id = lf.log_id
					AND l.liste_id IN($sql_liste_ids)
		)";
	$result   = $db->query($sql);
	$filesize = $result->column('totalsize');

	if (is_readable(WA_ROOTDIR . '/stats')) {
		$listid = implode('', array_unique($liste_ids));
		$browse = dir(WA_ROOTDIR . '/stats');
		while (($entry = $browse->read()) !== false) {
			if (is_file(WA_ROOTDIR . '/stats/' . $entry) && $entry != 'index.html' &&
				preg_match('/list['.$listid.']\.txt$/', $entry)
			) {
				$filesize += filesize(WA_ROOTDIR . '/stats/' . $entry);
			}
		}
		$browse->close();
	}
}

//
// Poids des tables du script
// (excepté la table des sessions, sauf dans le cas de sqlite)
//
if ($db::ENGINE == 'mysql') {
	$sql = sprintf("SHOW TABLE STATUS FROM %s", $db->quote($db->infos['dbname']));

	try {
		$result = $db->query($sql);
		$dbsize = 0;

		while ($row = $result->fetch()) {
			$add = false;

			if ($prefixe != '') {
				if ($row['Name'] != SESSION_TABLE && strncmp($row['Name'], $prefixe, strlen($prefixe)) == 0) {
					$add = true;
				}
			}
			else {
				$add = true;
			}

			if ($add) {
				$dbsize += ($row['Data_length'] + $row['Index_length']);
			}
		}
	}
	catch (Dblayer\Exception $e) {
		wanlog($e);
		$dbsize = $lang['Not_available'];
	}
}
else if ($db::ENGINE == 'postgres') {
	$sql = "SELECT sum(pg_total_relation_size(schemaname||'.'||tablename))
		FROM pg_tables WHERE schemaname = 'public'
			AND tablename ~ '^$prefixe'";

	try {
		$result = $db->query($sql);
		$row    = $result->fetch();
		$dbsize = $row[0];
	}
	catch (Dblayer\Exception $e) {
		wanlog($e);
		$dbsize = $lang['Not_available'];
	}
}
else if ($db::ENGINE == 'sqlite') {
	$dbsize = filesize($db->infos['path']);
}
else {
	$dbsize = $lang['Not_available'];
}

if (!($days = round(( time() - $nl_config['mailing_startdate'] ) / 86400))) {
	$days = 1;
}

if (!($month = round(( time() - $nl_config['mailing_startdate'] ) / 2592000))) {
	$month = 1;
}

if ($num_inscrits > 1) {
	$l_num_inscrits = sprintf($lang['Registered_subscribers'], $num_inscrits, wa_number_format($num_inscrits/$days));
}
else if ($num_inscrits == 1) {
	$l_num_inscrits = sprintf($lang['Registered_subscriber'], wa_number_format($num_inscrits/$days));
}
else {
	$l_num_inscrits = $lang['No_registered_subscriber'];
}

if ($num_temp > 1) {
	$l_num_temp = sprintf($lang['Tmp_subscribers'], $num_temp);
}
else if ($num_temp == 1) {
	$l_num_temp = $lang['Tmp_subscriber'];
}
else {
	$l_num_temp = $lang['No_tmp_subscriber'];
}

$output->header();

$template = new Template('index_body.tpl');

if ($num_logs > 0) {
	if ($num_logs > 1) {
		$l_num_logs = sprintf($lang['Total_newsletters'], $num_logs, wa_number_format($num_logs/$month));
	}
	else {
		$l_num_logs = sprintf($lang['Total_newsletter'], wa_number_format($num_logs/$month));
	}

	$template->assignToBlock('switch_last_newsletter', [
		'DATE_LAST_NEWSLETTER' => sprintf($lang['Last_newsletter'], convert_time($admindata['admin_dateformat'], $last_log))
	]);
}
else {
	$l_num_logs = $lang['No_newsletter_sended'];
}

$template->assign([
	'TITLE_HOME'             => $lang['Title']['accueil'],
	'L_EXPLAIN'              => nl2br($lang['Explain']['accueil']),
	'L_DBSIZE'               => $lang['Dbsize'],
	'L_FILESIZE'             => $lang['Total_Filesize'],

	'REGISTERED_SUBSCRIBERS' => $l_num_inscrits,
	'TEMP_SUBSCRIBERS'       => $l_num_temp,
	'NEWSLETTERS_SENDED'     => $l_num_logs,
	'DBSIZE'                 => (is_numeric($dbsize)) ? formateSize($dbsize) : $dbsize,
	'FILESIZE'               => formateSize($filesize),
	'LISTBOX'                => $output->listbox(Auth::VIEW, false, './view.php?mode=liste')
]);

if (wan_is_admin($admindata)) {
	$result = wa_check_update();

	$template->assignToBlock('version_info', [
		'VERSION' => sprintf($lang['Used_version'], WANEWSLETTER_VERSION)
	]);

	if ($result !== false) {
		if ($result === 1) {
			$template->assignToBlock('version_info.update_available', [
				'L_UPDATE_AVAILABLE' => $lang['New_version_available'],
				'L_DOWNLOAD_PAGE'    => $lang['Download_page'],

				'U_DOWNLOAD_PAGE' => DOWNLOAD_PAGE
			]);
		}
		else {
			$template->assignToBlock('version_info.up_to_date', [
				'L_UP_TO_DATE' => $lang['Version_up_to_date'],
			]);
		}
	}
	else {
		$template->assignToBlock('check_update_js', [
			'L_UPDATE_AVAILABLE' => str_replace('\'', '\\\'', $lang['New_version_available']),
			'L_UP_TO_DATE'       => str_replace('\'', '\\\'', $lang['Version_up_to_date']),
			'L_SITE_UNREACHABLE' => str_replace('\'', '\\\'', $lang['Site_unreachable']),
			'L_DOWNLOAD_PAGE'    => str_replace('\'', '\\\'', $lang['Download_page']),

			'U_DOWNLOAD_PAGE' => DOWNLOAD_PAGE
		]);

		$template->assignToBlock('version_info.check_update', [
			'L_CHECK_UPDATE' => $lang['Check_update']
		]);
	}
}

$template->pparse();
$output->footer();
