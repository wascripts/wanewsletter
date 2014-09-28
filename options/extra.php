<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

define('IN_NEWSLETTER', true);
define('WA_ROOTDIR',    '..');

require WA_ROOTDIR . '/start.php';

load_settings();

$liste_ids = ( !empty($_GET['liste']) ) ? $_GET['liste'] : 0;
$liste_ids = array_unique(array_map('intval', explode(' ', $liste_ids)));

if( count($liste_ids) > 0 )
{
	$sql = "SELECT COUNT(a.abo_id) AS num_subscribe
		FROM " . ABONNES_TABLE . " AS a
		WHERE a.abo_id IN(
				SELECT al.abo_id
				FROM " . ABO_LISTE_TABLE . " AS al
				WHERE al.liste_id IN(" . implode(', ', $liste_ids) . ")
					AND al.confirmed = " . SUBSCRIBE_CONFIRMED . "
			)
			AND a.abo_status = " . ABO_ACTIF;
	$result = $db->query($sql);
	$data   = $result->column('num_subscribe');
}
else
{
	$data   = '-1';
}

if( isset($_GET['output']) && $_GET['output'] == 'json' )
{
	header('Content-Type: application/json');
	
	printf('{"numSubscribe":"%d"}', $data);
}
else
{
	header('Content-Type: application/x-javascript');
	
	if( isset($_GET['use-variable']) )
	{
		$varname = trim($_GET['use-variable']);
		
		if( !preg_match('/^[A-Za-z0-9_.$\\]+$/', $varname) )
		{
			$varname = 'var numSubscribe';
			echo "console.log('Rejected variable name. Accepted chars are [A-Za-z0-9_.\$\\\\].');\n";
		}
		
		printf("%s = %d;\n", $varname, $data);
	}
	else
	{
		printf("document.write('%d');\n", $data);
	}
}
