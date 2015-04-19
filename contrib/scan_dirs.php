<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 *
 * Affiche la liste des fichiers et dossier inconnus/obsolètes présents dans
 * le répertoire d'installation de Wanewsletter.
 */

//
// Ceci est un fichier de test ou d'aide lors du développement.
// Commentez les lignes suivantes uniquement si vous êtes sùr de ce que vous faites !
//
echo "This script has been disabled for security reasons\n";
exit(0);


###############################################

$files = <<<LOF
.gitignore
COPYING
CREDITS
README
admin/admin.php
admin/config.php
admin/envoi.php
admin/index.php
admin/login.php
admin/show.php
admin/start.inc.php
admin/stats.php
admin/tools.php
admin/upgrade.php
admin/view.php
composer.json
contrib/.htaccess
contrib/bounces.php
contrib/cleaner.php
contrib/convertdb.php
contrib/diff_lang.php
contrib/index.html
contrib/scan_dirs.php
contrib/testlock.php
contrib/wanewsletter
contrib/wanewsletter.bat
data/.htaccess
data/db/.gitignore
data/stats/.gitignore
data/stats/index.html
data/tmp/.gitignore
data/tmp/index.html
data/uploads/.gitignore
data/uploads/index.html
docs/index.html
docs/wadoc.css
images/barre.gif
images/barre.png
images/button-wa.gif
images/button-wa.png
images/index.html
images/logo-wa.gif
images/logo-wa.png
images/shadow.png
includes/class.attach.php
includes/class.auth.php
includes/class.error.php
includes/class.exception.php
includes/class.subscription.php
includes/class.output.php
includes/class.popclient.php
includes/class.session.php
includes/class.template.php
includes/common.inc.php
includes/compat.inc.php
includes/config.sample.inc.php
includes/constantes.php
includes/engine_send.php
includes/functions.box.php
includes/functions.php
includes/functions.stats.php
includes/functions.validate.php
includes/index.html
includes/install.inc.php
includes/login.inc.php
includes/sql/index.html
includes/sql/mysql.php
includes/sql/mysqli.php
includes/sql/postgres.php
includes/sql/schemas/data.sql
includes/sql/schemas/index.html
includes/sql/schemas/mysql_tables.sql
includes/sql/schemas/postgres_tables.sql
includes/sql/schemas/sqlite_tables.sql
includes/sql/sqlite3.php
includes/sql/sqlitepdo.php
includes/sql/sqlparser.php
includes/sql/wadb.php
includes/tags.sample.inc.php
includes/wadb_init.php
index.html
install.php
language/email_english/admin_new_subscribe.txt
language/email_english/admin_unsubscribe.txt
language/email_english/index.html
language/email_english/new_admin.txt
language/email_english/reset_passwd.txt
language/email_english/unsubscribe_cron.txt
language/email_english/unsubscribe_form.txt
language/email_english/welcome_cron1.txt
language/email_english/welcome_cron2.txt
language/email_english/welcome_form1.txt
language/email_english/welcome_form2.txt
language/email_francais/admin_new_subscribe.txt
language/email_francais/admin_unsubscribe.txt
language/email_francais/index.html
language/email_francais/new_admin.txt
language/email_francais/reset_passwd.txt
language/email_francais/unsubscribe_cron.txt
language/email_francais/unsubscribe_form.txt
language/email_francais/welcome_cron1.txt
language/email_francais/welcome_cron2.txt
language/email_francais/welcome_form1.txt
language/email_francais/welcome_form2.txt
language/index.html
language/lang_english.php
language/lang_francais.php
newsletter.php
options/cron.php
options/extra.php
options/index.html
profil_cp.php
subscribe.php
templates/admin/add_admin_body.tpl
templates/admin/admin.js
templates/admin/admin_body.tpl
templates/admin/backup_body.tpl
templates/admin/ban_list_body.tpl
templates/admin/config_body.tpl
templates/admin/confirm_body.tpl
templates/admin/edit_abo_profil_body.tpl
templates/admin/edit_liste_body.tpl
templates/admin/editor.js
templates/admin/export_body.tpl
templates/admin/files_box.tpl
templates/admin/footer.tpl
templates/admin/forbidden_ext_body.tpl
templates/admin/generator_body.tpl
templates/admin/header.tpl
templates/admin/iframe_body.tpl
templates/admin/import_body.tpl
templates/admin/index.html
templates/admin/index_body.tpl
templates/admin/list_box.tpl
templates/admin/message_body.tpl
templates/admin/restore_body.tpl
templates/admin/result_generator_body.tpl
templates/admin/result_upgrade_body.tpl
templates/admin/select_liste_body.tpl
templates/admin/select_log_body.tpl
templates/admin/send_body.tpl
templates/admin/send_progress_body.tpl
templates/admin/simple_header.tpl
templates/admin/stats_body.tpl
templates/admin/tools_body.tpl
templates/admin/upgrade_body.tpl
templates/admin/view_abo_list_body.tpl
templates/admin/view_abo_profil_body.tpl
templates/admin/view_liste_body.tpl
templates/admin/view_logs_body.tpl
templates/archives_body.tpl
templates/editprofile_body.tpl
templates/footer.tpl
templates/header.tpl
templates/images/archive-hover.png
templates/images/archive.png
templates/images/icon_clip.png
templates/images/icon_loupe.png
templates/images/index.html
templates/images/loading.gif
templates/images/puce.png
templates/index.html
templates/index_body.tpl
templates/install.tpl
templates/login.tpl
templates/lost_passwd.tpl
templates/message_body.tpl
templates/reset_passwd.tpl
templates/simple_header.tpl
templates/subscribe_body.tpl
templates/wanewsletter.css

## other files ##

.git
composer.lock
templates/wanewsletter.custom.css
vendor

LOF;

$skip_dirs = [
	'.git',
	'docs',
	'data',
	'vendor',
];

define('WA_ROOTDIR', dirname(__DIR__));

ini_set('xdebug.default_enable', false);
ini_set('default_mimetype', 'text/plain');

function scan_dir($dir)
{
	global $listing, $skip_dirs;

	$browse = dir($dir);
	$scan_dirs = [];

	while (($entry = $browse->read()) !== false) {
		if ($entry == '..' || $entry == '.') {
			continue;
		}

		$filename = (($dir) ? $dir.'/' : '') . $entry;
		$relname  = ltrim(str_replace(WA_ROOTDIR, '', $filename), '/');

		$i = array_search($relname, $listing);

		if ($i !== false) {
			if (is_dir($filename)) {
				$scan_dirs[$relname] = $filename;
			}
			continue;
		}

		echo "$relname\n";
	}
	$browse->close();

	foreach ($scan_dirs as $relname => $dir) {
		if (in_array($relname, $skip_dirs)) {
			continue;
		}

		scan_dir($dir);
	}
}

$files = explode("\n", $files);
$dirs  = [];
foreach ($files as &$file) {
	$file = trim($file);
	$dirs[] = dirname($file);
	if (!$file || $file[0] == '#') {
		$file = null;
	}
}

$files = array_filter($files);
$dirs  = array_unique($dirs);
$listing = array_merge($files, $dirs);

echo "Search for obsolete/unknown files\n";
echo "---------------------------------\n";

scan_dir(WA_ROOTDIR);
