<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 *
 * @link http://www.cru.fr/listes/atelier/bounce.html
 *
 * @see RFC 1892 - The Multipart/Report Content Type for the Reporting of Mail System Administrative Messages
 * @see RFC 1893 - Enhanced Mail System Status Codes
 * @see RFC 3462 - The Multipart/Report Content Type for the Reporting of Mail System Administrative Messages
 *
 * Ce script se charge de scanner le compte mail indiqué pour récupérer les mail-daemon renvoyés
 * en cas de compte inexistant ou de boite pleine et supprime les emails indiqués de la base des
 * inscrits (si boite inexistante).
 *
 * Si vous utilisez ce script pour scanner le compte sur lequel vous avez demandé que soient renvoyés
 * les emails de retours d'erreur, faites attention de décommenter ensuite la ligne plus haut pour éviter
 * d'éventuels actes malveillants.
 */

//
// Ceci est un fichier de test ou d'aide lors du développement.
// Commentez les lignes suivantes uniquement si vous êtes sùr de ce que vous faites !
//
echo "This script has been disabled for security reasons\n";
exit(0);

//
// Configuration du script
//
$pop_server = '';// Préfixez avec ssl:// ou tls:// si besoin
$pop_port   = 110; // port du serveur. La valeur par défaut (110) est la plus répandue.
$pop_user   = '';
$pop_passwd = '';
$pop_opts   = array(
	'starttls' => false // true pour activer le STARTTLS; ignoré si le host est préfixé avec ssl:// ou tls://
);

//
// Fin de la configuration
//

function process_bounce($deliveryReport)
{
	$status = $action = $recipient = '';
	$body   = preg_replace("/\r\n?/", "\n", $body);
	$lines  = explode("\n", $body);

	foreach ($lines as $line) {
		if ($pos = strpos($line, ':')) {
			$name = strtolower(substr($line, 0, $pos));
			$value = trim(substr($line, $pos + 1));

			switch ($name) {
				case 'status':
				case 'action':
					$$name = $value;
					break;
				case 'final-recipient':
					$recipient = trim(preg_replace('/rfc822;/i', '', $value));
					break;
			}
		}
	}

	// Ne nous occupons que des erreurs permanentes (classe 5)
	if (preg_match('/^5\.(\d{1,3})\.(\d{1,3})$/', $status)) {

		/*
		$sql = "SELECT abo_id
				FROM " . ABONNES_TABLE . "
				WHERE abo_email = '" . $db->escape($match[1]) . "'";
			$result = $db->query($sql);

			$abo_id = $result->column('abo_id');

			$sql = "DELETE FROM " . ABONNES_TABLE . " WHERE abo_id = " . $abo_id;
			$db->query($sql);

			$sql = "DELETE FROM " . ABO_LISTE_TABLE . " WHERE abo_id = " . $abo_id;
			$db->query($sql);*/
	}
}

define('IN_NEWSLETTER', true);
define('WA_ROOTDIR',    dirname(__DIR__));

require WA_ROOTDIR . '/includes/common.inc.php';

$db = WaDatabase($dsn);

$process = false;
foreach ($_SERVER['argv'] as $arg) {
	if ($arg == '--process' || $arg == 'process=true') {
		$process = true;
	}
}

if ($process) {
	if (extension_loaded('imap')) {
		$secure = '';
		if (preg_match('#^(ssl|tls)(v[.0-9]+)?://#', $pop_server, $m)) {
			$pop_server = str_replace($m[0], '', $pop_server);
			$secure = '/ssl';
		}
		else if (!empty($pop_opts['starttls'])) {
			$secure = '/tls';
		}
		$cid = imap_open("\{$pop_server:$pop_port{$secure}/service=pop3\}INBOX", $pop_user, $pop_passwd);

		$mail_box = imap_sort($cid, SORTDATE, 1);

		foreach ($mail_box as $mail_id) {
			$email = imap_fetchstructure($cid, $mail_id);

			if ($email->type == TYPEMULTIPART && isset($email->parts[1]) &&
				$email->parts[1]->type == TYPEMESSAGE &&
				$email->parts[1]->ifsubtype &&
				$email->parts[1]->subtype == 'DELIVERY-STATUS'
			) {
				$body = imap_fetchbody($cid, $mail_id, 2, FT_PEEK);


				plain_error($body);

				$deleted_mails[] = process_bounce($body);

				//
				// On supprime l'email maintenant devenu inutile
				//
				//imap_delete($cid, $mail_id);
			}
		}

		imap_close($cid, CL_EXPUNGE);
	}
	else {
		require 'Mail/mimeDecode.php';

		$pop = new Pop();
		$pop->options($pop_opts);
		$pop->connect($pop_server, $pop_port, $pop_user, $pop_passwd);

		$total    = $pop->stat_box();
		$mail_box = $pop->list_mail();
		$deleted_mails = array();

		foreach ($mail_box as $mail_id => $mail_size) {
			$headers = $pop->contents[$mail_id]['headers'];
			$message = $pop->contents[$mail_id]['message'];

			$decode = new Mail_mimeDecode($headers . "\r\n\r\n" . $message, "\r\n");
			$email  = $decode->decode(array('include_bodies' => true, 'decode_bodies' => true));

			if ($email->ctype_primary == 'multipart' &&
				$email->ctype_secondary == 'report' &&
				$email->ctype_parameters['report-type'] == 'delivery-status' &&
				isset($email->parts[1]) && $email->parts[1]->ctype_primary == 'message' &&
				$email->parts[1]->ctype_secondary == 'delivery-status'
			) {
				$deleted_mails[] = process_bounce($email->parts[1]->body);

				//
				// On supprime l'email maintenant devenu inutile
				//
				//$pop->delete_mail($mail_id);
			}
		}

		$pop->quit();
	}

	$output  = "Opération effectuée avec succés\n";
	$output .= count($deleted_mails) . " compte(s) supprimé(s) pour cause d'adresse non valide.\n\n";

	foreach ($deleted_mails as $mail) {
		$output .= ' - ' . $mail . "\n";
	}

	plain_error($output);
}
else {
	$sql = "SELECT a.abo_id, a.abo_email
		FROM wa_abonnes AS a";
	$result = $db->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>Bounces Manager</title>

	<style type="text/css">
	html  { background-color: white; font: .9em "Bitstream Vera Sans", sans-serif; color: black; }
	table {
		border: none;
	}
	table th { background-color: #8B8; }
	table th,
	table td { border: 1px solid silver; padding: 2px 5px; }
	table td:first-child { text-align: center; }
	</style>
</head>
<body>

<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
<table border="1" cellpadding="2" cellspacing="1">
	<tr>
		<th>#</th>
		<th>Email</th>
		<th>Action</th>
	</tr>
<?php

	if ($result) {
		while ($row = $result->fetch()) {
			echo <<<DATA
	<tr>
		<td>$row[abo_id]</td>
		<td>$row[abo_email]</td>
		<td><input type="submit" name="delete[$row[abo_id]]" value="delete"></td>
	</tr>
DATA;
		}
	}
?>
</table>
</form>

</body>
</html>
<?php } ?>
