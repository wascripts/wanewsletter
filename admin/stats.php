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

//
// Si le module de statistiques est désactivé ou que la librairie GD n'est pas installé, 
// on affiche le message d'information correspondant
//
if( $nl_config['disable_stats'] )
{
	trigger_error('Stats_disabled', MESSAGE);
}
else if( !is_available_extension('gd') )
{
	trigger_error('No_gd_lib', MESSAGE);
}

include $waroot . 'includes/functions.stats.php';
$img_type = $nl_config['gd_img_type'];

$img  = ( !empty($_GET['img']) ) ? trim($_GET['img']) : '';
$date = ( !empty($_GET['date']) ) ? trim($_GET['date']) : '';

$liste_id_ary = $auth->check_auth(AUTH_VIEW);

if( !$admindata['session_liste'] )
{
	$output->build_listbox(AUTH_VIEW);
}

if( !$auth->check_auth(AUTH_VIEW, $admindata['session_liste']) )
{
	trigger_error('Not_auth_view', MESSAGE);
}

$listdata = $auth->listdata[$admindata['session_liste']];

if( $date != '' )
{
	list($month, $year) = explode('_', $date);
}
else
{
	$month = date('m');
	$year  = date('Y');
}

if( $img == 'graph' )
{
	$str_month = date('F', mktime(0, 0, 0, $month, 1, $year));
	
	//
	// Dimensions de l'image 
	//
	$width_img	= 510;
	$height_img = 250;
	
	$im = imagecreate($width_img, $height_img);
	
	$black   = rvb_color($im, '000000');
	$border  = rvb_color($im, 'FFFFFF');
	$back_1  = rvb_color($im, '003366');
	$back_2  = rvb_color($im, 'EAEAEA');
	$color[] = rvb_color($im, 'FF8800');
	$color[] = rvb_color($im, '0022FF');
	
	imagefill($im, 0, 0, $black);
	
	//
	// contours 
	//
	imagefilledrectangle($im, 1, 1, ($width_img - 2), ($height_img - 2), $back_1);
	imagefilledrectangle($im, 17, 20, ($width_img - 27), ($height_img - 19), $border);
	imagefilledrectangle($im, 18, 21, ($width_img - 28), ($height_img - 20), $back_2);
	
	//
	// titre du graphe 
	//
	$title  = unhtmlspecialchars($auth->listdata[$listdata['liste_id']]['liste_name']);
	$title .= ' : ' . $lang['Subscribe_per_day'] . ' - ' . $datetime[$str_month] . ' ' . $year;
	
	$start = (($width_img - (imagefontwidth(3) * strlen($title))) / 2);
	imagestring($im, 3, $start, 4, $title, $color[0]);
	
	//
	// Echelle horizontale et lecture du fichier des stats 
	//
	$max_value = 0;
	$filename  = $year . '_' . $str_month . '_list' . $listdata['liste_id'] . '.txt';
	
	if( !file_exists(wa_stats_path . $filename) )
	{
		create_stats($listdata, $month, $year);
	}
	
	if( $fp = @fopen(wa_stats_path . $filename, 'r') )
	{
		$contents = fread($fp, filesize(wa_stats_path . $filename));
		$stats    = clean_stats($contents);
		
		for( $day = 1, $i = 0, $int = 0; $day <= 31; $day++, $i++, $int += 15 )
		{
			if( checkdate($month, $day, $year) )
			{
				imagestring($im, 1, (20 + $int), 234, sprintf('%02d', $day), $border);
				
				$num_per_day[$day] = ( isset($stats[$i]) ) ? $stats[$i] : 0;
				
				if( $stats[$i] > $max_value )
				{
					$max_value = $stats[$i];
				}
			}
		}
	}
	else
	{
		return;
	}
	
	//
	// Echelle vertical (nombre d'inscriptions) 
	//
	if( $max_value == 0 )
	{
		$max_value = 10;
	}
	
	$top_value = $max_value;
	while( ($top_value % 10) != 0 )
	{
		$top_value++;
	}
	
	$interval = (198 / $top_value);
	
	$num = ($top_value / 5);
	for( $i = 0, $int = 0; $i < 5; $i++, $int += 40, $top_value -= $num )
	{
		imagestring($im, 1, 5, (29 + $int), $top_value, $border);
		imageline($im, 18, (32 + $int), 482, (32 + $int), $black);
	}
	
	$fct_imagecreatefrom = 'imagecreatefrom' . $img_type;
	$src = @$fct_imagecreatefrom($waroot . 'images/barre.' . $img_type);
	if( !$src )
	{
		return;
	}
	
	//
	// Affichage des résultats 
	//
	for( $day = 1, $int = 0; $day <= 31; $day++, $int += 15 )
	{
		if( checkdate($month, $day, $year) && $num_per_day[$day] )
		{
			$val = ($interval * $num_per_day[$day]);
			$height = (232 - $val);
			
			imagefilledrectangle($im, (19 + $int), $height, (31 + $int), 230, $black);
			imagecopyresized($im, $src, (20 + $int), ($height + 1), 0, 0, 11, ($val - 1), 10, 1);
			
			$start = (26 + $int - ((imagefontwidth(1) * strlen($num_per_day[$day])) / 2));
			$color_value = ( $num_per_day[$day] == $max_value ) ? $color[1] : $black;
			imagestring($im, 1, $start, ($height - 10), $num_per_day[$day], $color_value);
		}
	}
	
	header('Content-Disposition: inline; filename="subscribers_per_day.' . $img_type . '"');
	header('Content-Type: image/' . $img_type);
	$fct_image = 'image' . $img_type;
	$fct_image($im);
	
	imagedestroy($im);
	exit;
}

if( $img == 'camenbert' )
{
	$sql = "SELECT COUNT(al.abo_id) AS num_inscrits, al.liste_id 
		FROM " . ABO_LISTE_TABLE . " AS al, " . ABONNES_TABLE . " AS a 
		WHERE al.liste_id IN(" . implode(', ', $liste_id_ary) . ") 
			AND a.abo_status = " . ABO_ACTIF . " 
			AND a.abo_id = al.abo_id 
		GROUP BY al.liste_id";
	if( !($result = $db->query($sql)) )
	{
		return;
	}
	
	$tmpdata = array();
	while( $row = $db->fetch_array($result) )
	{
		$tmpdata[$row['liste_id']] = $row['num_inscrits'];
	}
	
	$total_inscrits = 0;
	$listes = array();
	foreach( $liste_id_ary AS $liste_id )
	{
		$liste_name = unhtmlspecialchars($auth->listdata[$liste_id]['liste_name']);
		if( strlen($liste_name) > 30 )
		{
			$liste_name = substr($liste_name, 0, 30);
			$liste_name = substr($liste_name, 0, strrpos($liste_name, ' ')) . '...';
		}
		
		$num_inscrits = ( !empty($tmpdata[$liste_id]) ) ? $tmpdata[$liste_id] : 0;
		
		$listes[] = array('name' => htmlspecialchars($liste_name), 'num' => $num_inscrits);
		$total_inscrits += $num_inscrits;
	}
	
	$total_listes = count($listes);
	
	//
	// Taille de base de l'image (varie s'il y a beaucoup de listes)
	//
	$width_img  = 510;
	$height_img = 170;
	
	if( $total_listes > 3 )
	{
		$height_img += (($total_listes - 3) * 20);
	}
	
	$im = imagecreate($width_img, $height_img);
	
	//
	// Allocation des couleurs
	//
	$black   = rvb_color($im, '000000');
	$back_1  = rvb_color($im, '003366');
	$back_2  = rvb_color($im, 'EAEAEA');
	$color[] = rvb_color($im, 'FF8800');
	$color[] = rvb_color($im, '66BB00');
	$color[] = rvb_color($im, '00BBCC');
	$color[] = rvb_color($im, '3300CC');
	$color[] = rvb_color($im, '660088');
	$color[] = rvb_color($im, 'CC0033');
	
	//
	// Création du contour noir 
	//
	imagefill($im, 0, 0, $black);
	imagefilledrectangle($im, 1, 1, ($width_img - 2), ($height_img - 2), $back_1);
	
	//
	// titre du graphe 
	//
	$title = $lang['Num_abo_per_liste'];
	$start = (($width_img - (imagefontwidth(3) * strlen($title))) / 2);
	imagestring($im, 3, $start, 4, $title, $color[0]);
	
	//
	// Positionnement de départ du camenbert
	//
	$start_x = 70;
	$start_y = 100;
	
	//
	// Emplacement des noms de liste de diffusion (cadre blanc) 
	//
	$hauteur = ($start_y - (100 / 2));
	imagefilledrectangle($im, 145, $hauteur, ($width_img - 20), ($hauteur + 30 + ($total_listes * 20)), $black);
	imagefilledrectangle($im, 146, ($hauteur + 1), ($width_img - 21), ($hauteur + 29 + ($total_listes * 20)), $back_2);
	
	//
	// Ok, on génère le camenbert
	//
	$degre = 0;
	
	for( $i = 0, $j = 0, $int = 20; $i < $total_listes; $i++, $j++, $int += 20 )
	{
		if( !empty($color[$j]) )
		{
			$color_arc = $color[$j];
		}
		else
		{
			$j = 0;
			$color_arc = $color[0];
		}
		
		//
		// On vérifie si le nombre d'inscrits représente au moins un millième du total
		// (Sans cela, il se produit un bug d'affichage)
		//
		$part = 0;
		
		if( $total_inscrits > 0 && ($part = round($listes[$i]['num'] / $total_inscrits, 3)) > 0.001 )
		{
			$deb_arc = round($degre);
			$degre  += ($part * 360);
			$end_arc = round($degre);
			
			imagearc($im, $start_x, $start_y, 100, 100, $deb_arc, $end_arc, $color_arc);
			
			list($x_arc, $y_arc) = xy_arc($deb_arc, 100);
			imageline($im, $start_x, $start_y, floor($start_x + $x_arc), floor($start_y + $y_arc), $color_arc);
			
			list($x_arc, $y_arc) = xy_arc($end_arc, 100);
			imageline($im, $start_x, $start_y, ceil($start_x + $x_arc), ceil($start_y + $y_arc), $color_arc);
			
			$mid_arc = round((($end_arc - $deb_arc) / 2) + $deb_arc);
			list($x_arc, $y_arc) = xy_arc($mid_arc, 50);
			imagefilltoborder($im, floor($start_x + $x_arc), floor($start_y + $y_arc), $color_arc, $color_arc);
		}
		
		//
		// Insertion du carré de couleur pour la légende, suivi du nom de la liste et du nombre d'abonnés 
		//
		imagefilledrectangle($im, 165, ($hauteur + $int), 175, ($hauteur + $int + 10), $black);
		imagefilledrectangle($im, 166, ($hauteur + $int + 1), 176, ($hauteur + $int + 11), $color_arc);
		
		imagestring($im, 2, 185, ($hauteur + $int), $listes[$i]['name'] . ' [' . $listes[$i]['num'] . '] [' . ($part * 100) . '%]', $black);
	}
	
	imagearc($im, $start_x, $start_y, 100, 100, 0, 360, $black);
	imagearc($im, $start_x, $start_y, 101, 101, 0, 360, $black);
	
	header('Content-Disposition: inline; filename="parts_by_liste.' . $img_type . '"');
	header('Content-Type: image/' . $img_type);
	$fct_image = 'image' . $img_type;
	$fct_image($im);
	
	imagedestroy($im);
	exit;
}

$output->build_listbox(AUTH_VIEW, false);

include $waroot . 'includes/functions.box.php';

if( $session->sessid_url != '' )
{
	$output->addHiddenField('sessid', $session->session_id);
}

$output->page_header();

$output->set_filenames( array(
	'body' => 'stats_body.tpl'
));

$output->assign_vars(array(
	'L_TITLE'         => $lang['Title']['stats'],
	'L_EXPLAIN_STATS' => nl2br($lang['Explain']['stats']),
	'L_GO_BUTTON'     => $lang['Button']['go'],
	'L_IMG_GRAPH'     => $lang['Graph_bar_title'],
	'L_IMG_CAMENBERT' => $lang['Camenbert_title'],
	
	'DATE_BOX'        => date_box($listdata, $month, $year),
	'U_IMG_GRAPH'     => sessid('./stats.php?img=graph&amp;date=' . $month . '_' . $year),
	'U_IMG_CAMENBERT' => sessid('./stats.php?img=camenbert'),
	
	'S_HIDDEN_FIELDS' => $output->getHiddenFields()
));

$output->pparse('body');

$output->page_footer();
?>