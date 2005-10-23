<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation; either version 2 
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * 
 * @package Wanewsletter
 * @author  Bobe <wascripts@phpcodeur.net>
 * @link    http://phpcodeur.net/wascripts/wanewsletter/
 * @license http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 * @version $Id$
 */

define('IN_NEWSLETTER', true);

require './pagestart.php';

$num_inscrits = $num_temp = $num_logs = $last_log = 0;

$liste_id_ary = $auth->check_auth(AUTH_VIEW);

if( count($liste_id_ary) > 0 )
{
	$data = get_data($liste_id_ary);
	
	$num_inscrits = $data['num_inscrits'];
	$num_temp     = $data['num_temp'];
	$num_logs     = $data['num_logs'];
	$last_log     = $data['last_log'];
}

//
// Poids des tables du script 
// (excepté la table des sessions)
//
if( ereg('^mysql', DATABASE) )
{
	$sql = 'SHOW TABLE STATUS FROM ' . $dbname;
	if( $result = $db->query($sql) )
	{
		$dbsize = 0;
		while( $row = $db->fetch_array($result) )
		{
			$add = false;
			if( $prefixe != '' )
			{
				if( $row['Name'] != SESSIONS_TABLE && preg_match('/^' . $prefixe . '/', $row['Name']) )
				{
					$add = true;
				}
			}
			else
			{
				$add = true;
			}
			
			if( $add )
			{
				$dbsize += ($row['Data_length'] + $row['Index_length']);
			}
		}
		
		if( $dbsize >= 1048576 )
		{
			$lang_size = $lang['MO'];
			$dbsize /= 1048576;
		}
		else if( $dbsize > 1024 )
		{
			$lang_size = $lang['KO'];
			$dbsize /= 1024;
		}
		else
		{
			$lang_size = $lang['Octets'];
		}
		
		$dbsize = sprintf('%.2f ' . $lang_size, $dbsize);
	}
	else
	{
		$dbsize = $lang['Not_available'];
	}
}
else
{
	$dbsize = $lang['Not_available'];
}

if( !($days	 = round(( time() - $nl_config['mailing_startdate'] ) / 86400)) )
{
	$days = 1;
}

if( !($month = round(( time() - $nl_config['mailing_startdate'] ) / 2592000)) )
{
	$month = 1;
}

if( $num_inscrits > 1 )
{
	$l_num_inscrits = sprintf($lang['Registered_subscribers'], $num_inscrits, ($num_inscrits/$days));
}
else
{
	$l_num_inscrits = sprintf($lang['Registered_subscriber'], $num_inscrits, ($num_inscrits/$days));
}

if( $num_temp > 1 )
{
	$l_num_temp = sprintf($lang['Tmp_subscribers'], $num_temp);
}
else
{
	$l_num_temp = sprintf($lang['Tmp_subscriber'], $num_temp);
}

$output->build_listbox(AUTH_VIEW, false, './view.php?mode=liste');
$output->page_header();

$output->set_filenames( array(
	'body' => 'index_body.tpl'
));

if( $num_logs > 0 )
{
	if( $num_logs > 1 )
	{
		$l_num_logs = sprintf($lang['Total_newsletters'], $num_logs, ($num_logs/$month));
	}
	else
	{
		$l_num_logs = sprintf($lang['Total_newsletter'], $num_logs, ($num_logs/$month));
	}
	
	$output->assign_block_vars('switch_last_newsletter', array(
		'DATE_LAST_NEWSLETTER' => sprintf($lang['Last_newsletter'], convert_time($nl_config['date_format'], $last_log))
	));
}
else
{
	$l_num_logs = $lang['No_newsletter_sended'];
}

$output->assign_vars( array(
	'TITLE_HOME'             => $lang['Title']['accueil'],
	'L_EXPLAIN'              => nl2br($lang['Explain']['accueil']),
	'L_DBSIZE'               => $lang['Dbsize'],
	
	'REGISTERED_SUBSCRIBERS' => $l_num_inscrits,
	'TEMP_SUBSCRIBERS'       => $l_num_temp,
	'NEWSLETTERS_SENDED'     => $l_num_logs,
	'DBSIZE'                 => $dbsize
));

$output->pparse('body');

$output->page_footer();
?>