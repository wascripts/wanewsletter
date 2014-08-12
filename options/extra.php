<?php
/**
 * Copyright (c) 2002-2014 Aurélien Maille
 * 
 * This file is part of Wanewsletter.
 * 
 * Wanewsletter is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation; either version 2 
 * of the License, or (at your option) any later version.
 * 
 * Wanewsletter is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Wanewsletter; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * 
 * @package Wanewsletter
 * @author  Bobe <wascripts@phpcodeur.net>
 * @link    http://phpcodeur.net/wascripts/wanewsletter/
 * @license http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 * @version $Id$
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
	$data   = 'No data';
}

header('Content-Type: application/x-javascript');

if( isset($_GET['use-variable']) ) {
	echo "var numSubscribe = '$data';";
} else {
	echo "document.write('$data');";
}

?>
