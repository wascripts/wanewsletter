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

include WA_PATH . 'includes/functions.stats.php';
$img_type = $nl_config['gd_img_type'];

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

$img   = ( !empty($_GET['img']) ) ? trim($_GET['img']) : '';
$year  = ( !empty($_GET['year']) ) ? intval($_GET['year']) : date('Y');
$month = ( !empty($_GET['month']) ) ? intval($_GET['month']) : date('n');

if( $img == 'graph' )
{
	$ts = mktime(0, 0, 0, $month, 1, $year);
	
	//
	// Dimensions de l'image 
	//
	$width_img  = 560;
	$height_img = 260;
	
	$im = imagecreate($width_img, $height_img);
	
	$black	= imagecolorallocate($im, 0, 0, 0);
	$gray	= imagecolorallocate($im, 200, 200, 200);
	$back_2 = imagecolorallocate($im, 240, 240, 240);
	$white	= imagecolorallocate($im, 255, 255, 255);
	$back_1 = convertToRGB('036');
	$back_1 = imagecolorallocate($im, $back_1->red, $back_1->green, $back_1->blue);
	$color1 = convertToRGB('F80');
	$color1 = imagecolorallocate($im, $color1->red, $color1->green, $color1->blue);
	$color2 = convertToRGB('02F');
	$color2 = imagecolorallocate($im, $color2->red, $color2->green, $color2->blue);
	
	imagefill($im, 0, 0, $black);
	
	//
	// contours 
	//
	imagefilledrectangle($im, 1, 1, ($width_img - 2), ($height_img - 2), $back_2);
	imagefilledrectangle($im, 24, 24, ($width_img - 25), ($height_img - 24), $black);
	imagefilledrectangle($im, 25, 25, ($width_img - 26), ($height_img - 25), $back_2);
	
	//
	// titre du graphe 
	//
	$title_px = 3;
	$title = sprintf('%s - %s', $lang['Subscribe_per_day'], convert_time('F Y', $ts));
	
	$start = (($width_img - (imagefontwidth($title_px) * strlen($title))) / 2);
	imagestring($im, $title_px, $start, 5, $title, $black);
	
	//
	// Echelle horizontale et lecture du fichier des stats 
	//
	$max_value	= 0;
	$default_px = 2;
	
	$filename  = $year . '_' . date('F', $ts) . '_list' . $listdata['liste_id'] . '.txt';
	
	if( !file_exists(wa_stats_path . $filename) )
	{
		create_stats($listdata, $month, $year);
	}
	
	if( ($filesize = filesize(wa_stats_path . $filename)) > 0 && $fp = @fopen(wa_stats_path . $filename, 'r') )
	{
		$contents = fread($fp, $filesize);
		$stats    = clean_stats($contents);
		
		for( $day = 1, $i = 0, $int = 0; $day <= 31; $day++, $i++, $int += 16 )
		{
			if( checkdate($month, $day, $year) )
			{
				$t = date('w', mktime(12, 0, 0, $month, $day, $year));
				imagestring($im, $default_px, (34 + $int), 240, sprintf('%02d', $day), (($t == 0 || $t == 6) ? $gray : $black));
				
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
	
	$num = ($top_value / 5);
	if( ($num % 6) == 0 )
	{
		$ec = 6;
	}
	else if( ($num % 4) == 0 )
	{
		$ec = 4;
	}
	else
	{
		$ec = 5;
	}
	
	$num = ($top_value / $ec);
	$coeff = (200 / $top_value);
	
	for( $i = 0, $int = 0; $i < ($ec*2); $i++, $int += (200/($ec*2)) )
	{
		if( ($i%2) == 0 )
		{
			imagestring($im, $default_px, 7, (29 + $int), $top_value, $black);
			imagesetstyle($im, array($gray, $gray, $gray, $gray, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT));
			imageline($im, 32, (37 + $int), ($width_img - 33), (37 + $int), IMG_COLOR_STYLED);
			imageline($im, 25, (37 + $int), 28, (37 + $int), $black);
			imageline($im, ($width_img - 29), (37 + $int), ($width_img - 26), (37 + $int), $black);
			$top_value -= $num;
		}
		else
		{
			imageline($im, 25, (37 + $int), 26, (37 + $int), $black);
			imageline($im, ($width_img - 27), (37 + $int), ($width_img - 26), (37 + $int), $black);
		}
	}
	
	$fct_imagecreatefrom = 'imagecreatefrom' . $img_type;
	$src = $fct_imagecreatefrom(WA_PATH . 'images/barre.' . $img_type);
	
	//
	// Affichage des résultats 
	//
	for( $day = 1, $int = 0; $day <= 31; $day++, $int += 16 )
	{
		if( checkdate($month, $day, $year) && $num_per_day[$day] > 0 )
		{
			$val = max(3, ($coeff * $num_per_day[$day]));
			$height = (237 - $val);
			
			imagefilledrectangle($im, (33 + $int), $height, (45 + $int), 235, $black);
			imagecopyresized($im, $src, (34 + $int), $height+1, 0, 0, 11, ceil($val - 2), 10, 1);
			
			$start = (40 + $int - ((imagefontwidth($default_px) * strlen($num_per_day[$day])) / 2));
			$color_value = ( $num_per_day[$day] == $max_value ) ? $color2 : $black;
			imagestring($im, $default_px, $start, ($height - 13), $num_per_day[$day], $color_value);
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
		FROM " . ABO_LISTE_TABLE . " AS al
			INNER JOIN " . ABONNES_TABLE . " AS a ON a.abo_id = al.abo_id
				AND a.abo_status = " . ABO_ACTIF . "
		WHERE al.liste_id IN(" . implode(', ', $liste_id_ary) . ")
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
		$liste_name   = cut_str(unhtmlspecialchars($auth->listdata[$liste_id]['liste_name']), 30);
		$num_inscrits = ( !empty($tmpdata[$liste_id]) ) ? $tmpdata[$liste_id] : 0;
		
		$listes[] = array('name' => htmlspecialchars($liste_name), 'num' => $num_inscrits);
		$total_inscrits += $num_inscrits;
	}
	
	$total_listes = count($listes);
	
	//
	// Taille de base de l'image (varie s'il y a beaucoup de listes)
	//
	$width_img  = 560;
	$height_img = 170;
	
	if( $total_listes > 3 )
	{
		$height_img += (($total_listes - 3) * 20);
	}
	
	$im = imagecreate($width_img, $height_img);
	
	//
	// Allocation des couleurs
	//
	$black   = imagecolorallocate($im, 0, 0, 0);
	$back_1  = convertToRGB('036');
	$back_1  = imagecolorallocate($im, $back_1->red, $back_1->green, $back_1->blue);
	$back_2  = imagecolorallocate($im, 240, 240, 240);
	$tmp     = convertToRGB('F80');
	$color[] = imagecolorallocate($im, $tmp->red, $tmp->green, $tmp->blue);
	$tmp     = convertToRGB('6B0');
	$color[] = imagecolorallocate($im, $tmp->red, $tmp->green, $tmp->blue);
	$tmp     = convertToRGB('0BC');
	$color[] = imagecolorallocate($im, $tmp->red, $tmp->green, $tmp->blue);
	$tmp     = convertToRGB('30C');
	$color[] = imagecolorallocate($im, $tmp->red, $tmp->green, $tmp->blue);
	$tmp     = convertToRGB('608');
	$color[] = imagecolorallocate($im, $tmp->red, $tmp->green, $tmp->blue);
	$tmp     = convertToRGB('C03');
	$color[] = imagecolorallocate($im, $tmp->red, $tmp->green, $tmp->blue);
	
	//
	// Création du contour noir 
	//
	imagefill($im, 0, 0, $black);
	imagefilledrectangle($im, 1, 1, ($width_img - 2), ($height_img - 2), $back_2);
	
	//
	// titre du graphe 
	//
	$title = $lang['Num_abo_per_liste'];
	$start = (($width_img - (imagefontwidth(3) * strlen($title))) / 2);
	imagestring($im, 3, $start, 4, $title, $black);
	
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
		
		imagestring($im, 2, 185, ($hauteur + $int),
			sprintf('%s [%d] [%s%%]', $listes[$i]['name'], $listes[$i]['num'],
				($part > 0 ? round($part * 100, 2) : 0)),
			$black
		);
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

include WA_PATH . 'includes/functions.box.php';

if( $session->sessid_url != '' )
{
	$output->addHiddenField('sessid', $session->session_id);
}

$output->page_header();

$output->set_filenames( array(
	'body' => 'stats_body.tpl'
));

$y = date('Y');
$m = date('n');
$y_list = '';
$m_list = '';

do
{
	$y_list .= sprintf("\n\t<option value=\"%1\$d\">%1\$d</option>", $y);
	
}
while( --$y >= date('Y', $listdata['liste_startdate']) );

if( $y == (date('Y') - 1) )
{
	$n = date('n', $listdata['liste_startdate']);
}
else
{
	$m = 12;
	$n = 1;
}

for(; $m >= $n; $m-- )
{
	$selected = ( $m == $month ) ? ' selected="selected"' : '';
	$m_list  .= sprintf("\n\t<option value=\"%d\"%s>%s</option>", $m, $selected,
		convert_time('F', mktime(0, 0, 0, $m, 1, $y)));
}

$output->assign_vars(array(
	'L_TITLE'         => $lang['Title']['stats'],
	'L_EXPLAIN_STATS' => nl2br($lang['Explain']['stats']),
	'L_GO_BUTTON'     => $lang['Button']['go'],
	'L_IMG_GRAPH'     => $lang['Graph_bar_title'],
	'L_IMG_CAMENBERT' => $lang['Camenbert_title'],
	
	'YEAR_LIST'       => $y_list,
	'MONTH_LIST'      => $m_list,
	'U_IMG_GRAPH'     => sessid('./stats.php?img=graph&amp;year=' . $year . '&amp;month=' . $month),
	'U_IMG_CAMENBERT' => sessid('./stats.php?img=camenbert'),
	
	'S_HIDDEN_FIELDS' => $output->getHiddenFields()
));

$output->pparse('body');

$output->page_footer();
?>