<?php
/**
 * Copyright (c) 2002-2006 Aurélien Maille
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
define('WA_PATH',      '../');

require WA_PATH . 'start.php';

load_settings();

$js_data  = 'No data';
$liste_id = ( !empty($_GET['liste']) ) ? intval($_GET['liste']) : 0;

if( $liste_id > 0 )
{
	$sql = "SELECT COUNT(a.abo_id) AS num_inscrits 
		FROM " . ABONNES_TABLE . " AS a, " . ABO_LISTE_TABLE . " AS al 
		WHERE al.liste_id = $liste_id 
			AND a.abo_id = al.abo_id 
			AND a.abo_status = " . ABO_ACTIF;
	if( $result = $db->query($sql) )
	{
		$js_data = $db->result($result, 0, 'num_inscrits');
	}
}

header('Content-Type: application/x-javascript; charset=ISO-8859-1');

if( isset($_GET['mode']) && strtoupper($_GET['mode']) == 'DOM' ) {
?>


<?php
} else {
	echo "document.write('$js_data');";
}
?>