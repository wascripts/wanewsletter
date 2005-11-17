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

require './pagestart.php';

$num_inscrits = $num_temp = $num_logs = $last_log = $filesize = 0;

$liste_ids = $auth->check_auth(AUTH_VIEW);

if( count($liste_ids) > 0 )
{
	$sql_liste_ids = implode(', ', $liste_ids);
	
	//
	// Récupération des nombres d'inscrits
	//
	$sql_abo_ids = "SELECT DISTINCT(abo_id)
		FROM " . ABO_LISTE_TABLE . "
		WHERE liste_id IN($sql_liste_ids)";
	if( DATABASE == 'mysql' )
	{
		if( !($result = $db->query($sql_abo_ids)) )
		{
			trigger_error('Impossible d\'obtenir le nombre d\'inscrits/inscrits en attente', ERROR);
		}
		
		$abo_ids = array();
		
		if( $db->num_rows() > 0 )
		{
			while( $row = $db->fetch_array($result) )
			{
				array_push($abo_ids, $row['abo_id']);
			}
		}
		else
		{
			$abo_ids[] = 0;
		}
		
		$sql_abo_ids = implode(', ', $abo_ids);
	}
	
	$sql = "SELECT COUNT(abo_id) AS num_abo, abo_status
		FROM " . ABONNES_TABLE . "
		WHERE abo_id IN($sql_abo_ids)
		GROUP BY abo_status";
	if( !($result = $db->query($sql)) )
	{
		trigger_error('Impossible d\'obtenir le nombre d\'inscrits/inscrits en attente', ERROR);
	}
	
	while( $row = $db->fetch_array($result) )
	{
		if( $row['abo_status'] == ABO_ACTIF )
		{
			$num_inscrits = $row['num_abo'];
		}
		else
		{
			$num_temp = $row['num_abo'];
		}
	}
	
	//
	// Récupération du nombre d'archives
	//
	$sql = "SELECT SUM(liste_numlogs) AS num_logs 
		FROM " . LISTE_TABLE . " 
		WHERE liste_id IN($sql_liste_ids)";
	if( !($result = $db->query($sql)) )
	{
		trigger_error('Impossible d\'obtenir le nombre de logs envoyés', ERROR);
	}
	
	if( $row = $db->fetch_array($result) )
	{
		$num_logs = $row['num_logs'];
	}
	
	//
	// Récupération de la date du dernier envoi
	//
	$sql = "SELECT MAX(log_date) AS last_log 
		FROM " . LOG_TABLE . " 
		WHERE log_status = " . STATUS_SENDED . "
			AND liste_id IN($sql_liste_ids)";
	if( !($result = $db->query($sql)) )
	{
		trigger_error('Impossible d\'obtenir la date du dernier envoyé', ERROR);
	}
	
	if( $row = $db->fetch_array($result) )
	{
		$last_log = $row['last_log'];
	}
	
	//
	// Espace disque occupé
	//
	$sql_file_ids = "SELECT lf.file_id
		FROM " . LOG_FILES_TABLE . " AS lf
			INNER JOIN " . LOG_TABLE . " AS l
			ON l.log_id = lf.log_id
				AND l.liste_id IN($sql_liste_ids)";
	
	if( DATABASE == 'mysql' )
	{
		if( !($result = $db->query($sql_file_ids)) )
		{
			trigger_error('Impossible d\'obtenir la liste des identifiants de fichiers', ERROR);
		}
		
		$file_ids = array();
		
		if( $db->num_rows() > 0 )
		{
			while( $row = $db->fetch_array($result) )
			{
				array_push($file_ids, $row['file_id']);
			}
		}
		else
		{
			$file_ids[] = 0;
		}
		
		$sql_file_ids = implode(', ', $file_ids);
	}
	
	$sql = "SELECT SUM(jf.file_size) AS totalsize
		FROM " . JOINED_FILES_TABLE . " AS jf
		WHERE jf.file_id IN($sql_file_ids)";
	$result   = $db->query($sql);
	$filesize = $db->result($result, 0, 'totalsize');
	
	if( file_exists(WA_ROOTDIR . '/stats') && is_readable(WA_ROOTDIR . '/stats') )
	{
		$listid = implode('', array_unique($liste_ids));
		$browse = dir(WA_ROOTDIR . '/stats');
		while( ($entry = $browse->read()) !== false )
		{
			if( is_file(WA_ROOTDIR . '/stats/' . $entry) && $entry != 'index.html' && preg_match('/list['.$listid.']\.txt$/', $entry) )
			{
				$filesize += filesize(WA_ROOTDIR . '/stats/' . $entry);
			}
		}
		$browse->close();
	}
}

//
// Poids des tables du script 
// (excepté la table des sessions)
//
switch( DATABASE )
{
	case 'mysql':
	case 'mysql4':
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
		}
		else
		{
			$dbsize = $lang['Not_available'];
		}
		break;
	
	case 'sqlite':
		$dbsize = filesize($dbhost);
		break;
	
	default:
		$dbsize = $lang['Not_available'];
		break;
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
	$l_num_inscrits = sprintf($lang['Registered_subscribers'], $num_inscrits, wa_number_format($num_inscrits/$days));
}
else if( $num_inscrits == 1 )
{
	$l_num_inscrits = sprintf($lang['Registered_subscriber'], wa_number_format($num_inscrits/$days));
}
else
{
	$l_num_inscrits = $lang['No_registered_subscriber'];
}

if( $num_temp > 1 )
{
	$l_num_temp = sprintf($lang['Tmp_subscribers'], $num_temp);
}
else if( $num_temp == 1 )
{
	$l_num_temp = sprintf($lang['Tmp_subscriber'], $num_temp);
}
else
{
	$l_num_temp = $lang['No_tmp_subscriber'];
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
		$l_num_logs = sprintf($lang['Total_newsletters'], $num_logs, wa_number_format($num_logs/$month));
	}
	else
	{
		$l_num_logs = sprintf($lang['Total_newsletter'], $num_logs, wa_number_format($num_logs/$month));
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
	'L_FILESIZE'             => $lang['Total_Filesize'],
	
	'REGISTERED_SUBSCRIBERS' => $l_num_inscrits,
	'TEMP_SUBSCRIBERS'       => $l_num_temp,
	'NEWSLETTERS_SENDED'     => $l_num_logs,
	'DBSIZE'                 => (is_numeric($dbsize)) ? formateSize($dbsize) : $dbsize,
	'FILESIZE'               => formateSize($filesize)
));

$output->pparse('body');

$output->page_footer();
?>