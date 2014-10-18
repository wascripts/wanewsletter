<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

define('IN_NEWSLETTER', true);

require './pagestart.php';
require WA_ROOTDIR . '/includes/functions.stats.php';

//
// Si le module de statistiques est désactivé ou que la librairie GD n'est pas installé, 
// on affiche le message d'information correspondant
//
if( $nl_config['disable_stats'] )
{
	$output->displayMessage('Stats_disabled');
}
else if( !extension_loaded('gd') )
{
	$output->displayMessage('No_gd_lib');
}

$liste_ids = $auth->check_auth(AUTH_VIEW);

if( !$admindata['session_liste'] )
{
	$output->build_listbox(AUTH_VIEW);
}

if( !$auth->check_auth(AUTH_VIEW, $admindata['session_liste']) )
{
	http_response_code(401);
	$output->displayMessage('Not_auth_view');
}

$listdata = $auth->listdata[$admindata['session_liste']];

$img   = ( !empty($_GET['img']) ) ? trim($_GET['img']) : '';
$year  = ( !empty($_GET['year']) ) ? intval($_GET['year']) : date('Y');
$month = ( !empty($_GET['month']) ) ? intval($_GET['month']) : date('n');

$img_type = (imagetypes() & IMG_GIF) ? 'gif' : null;
$img_type = (imagetypes() & IMG_PNG) ? 'png' : $img_type;

if( is_null($img_type) ) {
	// WTF ?!
	$output->displayMessage($lang['Message']['No_gd_img_support']);
}

function send_image($name, $img, $lastModified = null)
{
	global $img_type;
	
	if( !is_numeric($lastModified) )
	{
		$lastModified = null;
	}
	
	$canUseCache = true;
	$cachetime   = !empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : 0;
	
	if( !empty($_SERVER['HTTP_CACHE_CONTROL']) )
	{
		$canUseCache = !preg_match('/no-cache/i', $_SERVER['HTTP_CACHE_CONTROL']);
	}
	else if( !empty($_SERVER['HTTP_PRAGMA']) )// HTTP 1.0
	{
		$canUseCache = !preg_match('/no-cache/i', $_SERVER['HTTP_PRAGMA']);
	}
	
	if( !is_null($lastModified) && $lastModified <= $cachetime && $canUseCache )
	{
		http_response_code(304);
		header('Date: ' . gmdate(DATE_RFC1123));
		exit;
	}
	
	$maxAge = 0;
	
	if( !is_null($lastModified) )
	{
		header('Last-Modified: ' . gmdate(DATE_RFC1123, $lastModified));
	}
	
	header('Expires: ' . gmdate(DATE_RFC1123, (time() + $maxAge)));// HTTP 1.0
	header('Pragma: private');// HTTP 1.0
	header('Cache-Control: private, must-revalidate, max-age='.$maxAge);
	header('Content-Disposition: inline; filename="' . $name . '.' . $img_type . '"');
	header('Content-Type: image/' . $img_type);
	
	$fct_image = 'image' . $img_type;
	$fct_image($img);
	imagedestroy($img);
	
	exit;
}

function display_img_error($str)
{
	global $img_type;
	
	$imageW = 560;
	$imageH = 80;
	$text_font = 3;
	
	$im = imagecreate($imageW, $imageH);
	
	$black = imagecolorallocate($im, 0, 0, 0);
	$gray1 = convertToRGB('EAEAEA');
	$gray1 = imagecolorallocate($im, $gray1->red, $gray1->green, $gray1->blue);
	
	//
	// Création du contour noir 
	//
	imagefill($im, 0, 0, $black);
	imagefilledrectangle($im, 1, 1, ($imageW - 2), ($imageH - 2), $gray1);
	
	//
	// titre du graphe 
	//
	$startW = (($imageW - (imagefontwidth($text_font) * strlen($str))) / 2);
	$startH = (($imageH - imagefontheight($text_font)) / 2);
	imagestring($im, $text_font, $startW, $startH, $str, $black);
	
	http_response_code(500);
	send_image('error', $im);
}

if( $img == 'graph' )
{
	$ts = mktime(0, 0, 0, $month, 1, $year);
	
	//
	// Réglages de l'image 
	//
	$imageW = 560;
	$imageH = 260;
	$title_font = 3;
	$text_font  = 2;
	
	//
	// Récupération des statistiques
	//
	$filename = filename_stats($year . '_' . date('F', $ts), $listdata['liste_id']);
	
	if( !file_exists(WA_STATSDIR . '/' . $filename) )
	{
		create_stats($listdata, $month, $year);
	}
	
	if( ($filesize = filesize(WA_STATSDIR . '/' . $filename)) > 0 && $fp = @fopen(WA_STATSDIR . '/' . $filename, 'r') )
	{
		$contents = fread($fp, $filesize);
		$stats    = clean_stats($contents);
		fclose($fp);
	}
	else
	{
		display_img_error('An error has occured while generating image!');
	}
	
	// C'est parti
	$im = imagecreate($imageW, $imageH);
	
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
	
	//
	// contours 
	//
	imagefill($im, 0, 0, $black);
	imagefilledrectangle($im, 1, 1, ($imageW - 2), ($imageH - 2), $back_2);
	imagefilledrectangle($im, 24, 24, ($imageW - 25), ($imageH - 24), $black);
	imagefilledrectangle($im, 25, 25, ($imageW - 26), ($imageH - 25), $back_2);
	
	//
	// titre du graphe 
	//
	$title = sprintf('%s - %s', $lang['Subscribe_per_day'], convert_time('F Y', $ts));
	$start = (($imageW - (imagefontwidth($title_font) * strlen($title))) / 2);
	imagestring($im, $title_font, $start, 5, $title, $black);
	
	//
	// Échelle horizontale et lecture du fichier des stats 
	//
	$max_days    = date('t', $ts);
	$num_per_day = array();
	$max_value   = 10;
	
	for( $day = 1, $i = 0, $int = 0; $day <= $max_days; $day++, $i++, $int += 16 )
	{
		$t = date('w', mktime(12, 0, 0, $month, $day, $year));
		$color = ($t == 0 || $t == 6) ? $gray : $black;// Gris pour les samedi et dimanche
		imagestring($im, $text_font, (34 + $int), 240, sprintf('%02d', $day), $color);
		
		$num_per_day[$day] = ( isset($stats[$i]) ) ? $stats[$i] : 0;
		
		if( $stats[$i] > $max_value )
		{
			$max_value = $stats[$i];
		}
	}
	
	//
	// Échelle vertical (nombre d'inscriptions) 
	//
	$top_value = $max_value;
	while( ($top_value % 10) != 0 )
	{
		$top_value++;
	}
	
	$num = ($top_value / 5);
	if( ($num % 6) == 0 )
	{
		$numgrad = 6;
	}
	else if( ($num % 4) == 0 )
	{
		$numgrad = 4;
	}
	else
	{
		$numgrad = 5;
	}
	
	$num = ($top_value / $numgrad);
	$coeff = (200 / $top_value);
	
	for( $i = 0, $int = 0; $i < ($numgrad * 2); $i++, $int += (200/($numgrad * 2)) )
	{
		if( ($i % 2) == 0 )
		{
			imagestring($im, $text_font, 7, (29 + $int), $top_value, $black);
			imagesetstyle($im, array($gray, $gray, $gray, $gray, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT));
			imageline($im, 32, (37 + $int), ($imageW - 33), (37 + $int), IMG_COLOR_STYLED);
			imageline($im, 25, (37 + $int), 28, (37 + $int), $black);
			imageline($im, ($imageW - 29), (37 + $int), ($imageW - 26), (37 + $int), $black);
			$top_value -= $num;
		}
		else
		{
			imageline($im, 25, (37 + $int), 26, (37 + $int), $black);
			imageline($im, ($imageW - 27), (37 + $int), ($imageW - 26), (37 + $int), $black);
		}
	}
	
	$fct_imagecreatefrom = 'imagecreatefrom' . $img_type;
	$src = $fct_imagecreatefrom(WA_ROOTDIR . '/images/barre.' . $img_type);
	
	//
	// Affichage des résultats 
	//
	for( $day = 1, $int = 0; $day <= $max_days; $day++, $int += 16 )
	{
		if( $num_per_day[$day] > 0 )
		{
			$val = max(3, ($coeff * $num_per_day[$day]));
			$height = (237 - $val);
			
			imagefilledrectangle($im, (33 + $int), $height, (45 + $int), 235, $black);
			imagecopyresized($im, $src, (34 + $int), ($height + 1), 0, 0, 11, ceil($val - 2), 10, 1);
			
			$start = (40 + $int - ((imagefontwidth($text_font) * strlen($num_per_day[$day])) / 2));
			$color_value = ( $num_per_day[$day] == $max_value ) ? $color2 : $black;
			imagestring($im, $text_font, $start, ($height - 13), $num_per_day[$day], $color_value);
		}
	}
	
	send_image('subscribers_per_day', $im, filemtime(WA_STATSDIR . '/' . $filename));
}

if( $img == 'camembert' )
{
	$sql = "SELECT COUNT(al.abo_id) AS num_inscrits, al.liste_id
		FROM " . ABO_LISTE_TABLE . " AS al
		WHERE al.liste_id IN(" . implode(', ', $liste_ids) . ")
			AND confirmed = " . SUBSCRIBE_CONFIRMED . "
		GROUP BY al.liste_id";
	$result = $db->query($sql);
	
	$tmpdata = array();
	while( $row = $result->fetch() )
	{
		$tmpdata[$row['liste_id']] = $row['num_inscrits'];
	}
	
	$total_inscrits = 0;
	$listes = array();
	foreach( $liste_ids as $liste_id )
	{
		$liste_name   = cut_str($auth->listdata[$liste_id]['liste_name'], 30);
		$num_inscrits = ( !empty($tmpdata[$liste_id]) ) ? $tmpdata[$liste_id] : 0;
		
		$listes[] = array('name' => $liste_name, 'num' => $num_inscrits);
		$total_inscrits += $num_inscrits;
	}
	
	$total_listes = count($listes);
	
	//
	// Taille de base de l'image (varie s'il y a beaucoup de listes) et tailles de texte
	//
	$imageW = 560;
	$imageH = 170;
	$title_font = 3;
	$text_font  = 2;
	
	if( $total_listes > 3 )
	{
		$imageH += (($total_listes - 3) * 20);
	}
	
	$im = imagecreate($imageW, $imageH);
	
	//
	// Allocation des couleurs
	//
	$black = imagecolorallocate($im, 0, 0, 0);
	$gray1 = convertToRGB('EAEAEA');
	$gray1 = imagecolorallocate($im, $gray1->red, $gray1->green, $gray1->blue);
	$gray2 = convertToRGB('888');
	$gray2 = imagecolorallocate($im, $gray2->red, $gray2->green, $gray2->blue);
	
	$color = array();
	$colorList = array('F80', '0A0', '0BC', '30C', '608', 'C03');
	foreach( $colorList as $hexColor )
	{
		$tmp = convertToRGB($hexColor);
		$color[] = imagecolorallocate($im, $tmp->red, $tmp->green, $tmp->blue);
	}
	
	//
	// Création du contour noir 
	//
	imagefill($im, 0, 0, $black);
	imagefilledrectangle($im, 1, 1, ($imageW - 2), ($imageH - 2), $gray1);
	
	//
	// Titre du graphe 
	//
	$title = $lang['Num_abo_per_liste'];
	$start = (($imageW - (imagefontwidth($title_font) * strlen($title))) / 2);
	imagestring($im, $title_font, $start, 4, $title, $black);
	
	//
	// Positionnement de départ du camenbert
	//
	$startX = 70;
	$startY = 100;
	
	//
	// Emplacement des noms de liste de diffusion (cadre blanc) 
	//
	$globalY = ($startY - (100 / 2));
	$outer   = 5;
	$rectX   = 145;
	$rectH   = (30 + ($total_listes * 20));
	$shadowX = ($rectX - $outer);
	
	if( $img_type == 'png' )
	{
		$src = imagecreatefrompng(WA_ROOTDIR . '/images/shadow.png');
		imagecopyresized($im, $src, $shadowX, $globalY, 0, 0, $outer, $outer, $outer, $outer); // Angle supérieur gauche
		imagecopyresized($im, $src, $shadowX, ($globalY + $outer), 0, $outer, $outer, $rectH, $outer, 1); // Coté gauche
		imagecopyresized($im, $src, $shadowX, ($globalY + $rectH + 1), 0, (imagesy($src) - $outer), 401, $outer, 401, $outer); // Coté gauche
	}
	else
	{
		imagefilledrectangle($im, $shadowX, ($globalY + $outer), ($imageW - 20 - $outer), ($globalY + $rectH + $outer), $gray2);
	}
	
	imagefilledrectangle($im, $rectX, $globalY, ($imageW - 20), ($globalY + $rectH), $black);
	imagefilledrectangle($im, ($rectX + 1), ($globalY + 1), ($imageW - 21), ($globalY + $rectH - 1), $gray1);
	
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
			
			imagearc($im, $startX, $startY, 100, 100, $deb_arc, $end_arc, $color_arc);
			
			list($arcX, $arcY) = xy_arc($deb_arc, 100);
			imageline($im, $startX, $startY, floor($startX + $arcX), floor($startY + $arcY), $color_arc);
			
			list($arcX, $arcY) = xy_arc($end_arc, 100);
			imageline($im, $startX, $startY, ceil($startX + $arcX), ceil($startY + $arcY), $color_arc);
			
			$mid_arc = round((($end_arc - $deb_arc) / 2) + $deb_arc);
			list($arcX, $arcY) = xy_arc($mid_arc, 50);
			imagefilltoborder($im, floor($startX + $arcX), floor($startY + $arcY), $color_arc, $color_arc);
		}
		
		//
		// Insertion du carré de couleur pour la légende, suivi du nom de la liste et du nombre d'abonnés 
		//
		imagefilledrectangle($im, 165, ($globalY + $int + 2), 175, ($globalY + $int + 12), $gray2);
		imagefilledrectangle($im, 166, ($globalY + $int + 1), 176, ($globalY + $int + 11), $color_arc);
		
		imagestring($im, $text_font, 185, ($globalY + $int),
			sprintf('%s [%d] [%s%%]', $listes[$i]['name'], $listes[$i]['num'],
				wa_number_format(($part > 0 ? round($part * 100, 2) : 0), 1)),
			$black
		);
	}
	
	imagearc($im, $startX, $startY, 100, 100, 0, 360, $black);
	imagearc($im, $startX, $startY, 101, 101, 0, 360, $black);
	
	send_image('parts_by_liste', $im);
}

$output->build_listbox(AUTH_VIEW, false);

require WA_ROOTDIR . '/includes/functions.box.php';

$output->page_header();

$output->set_filenames( array(
	'body' => 'stats_body.tpl'
));

$y_list = '';
$m_list = '';

$y = date('Y', $listdata['liste_startdate']);
$n = date('n', $listdata['liste_startdate']);
$c = max(date('Y'), $year);

if( $y == $c )
{
	$m = date('n');
}
else
{
	$m = 12;
	$n = 1;
}

for(; $y <= $c; $y++ )
{
	$selected = ( $y == $year ) ? ' selected="selected"' : '';
	$y_list .= sprintf("\n\t<option value=\"%1\$d\"%2\$s>%1\$d</option>", $y, $selected);
}

for(; $n <= $m; $n++ )
{
	$selected = ( $n == $month ) ? ' selected="selected"' : '';
	$m_list  .= sprintf("\n\t<option value=\"%d\"%s>%s</option>", $n, $selected,
		convert_time('F', mktime(0, 0, 0, $n, 1, $y)));
}

$prev_m = $month-1;
$prev_y = $year;

if( $prev_m < 1 )
{
	$prev_m = 12;
	$prev_y--;
}

$next_m = $month+1;
$next_y = $year;

if( $next_m > 12 )
{
	$next_m = 1;
	$next_y++;
}

$aTitle = sprintf('%s &ndash; %%s', $lang['Module']['stats']);

$output->assign_vars(array(
	'L_TITLE'         => $lang['Title']['stats'],
	'L_EXPLAIN_STATS' => nl2br($lang['Explain']['stats']),
	'L_GO_BUTTON'     => $lang['Button']['go'],
	'L_IMG_GRAPH'     => $lang['Graph_bar_title'],
	'L_IMG_CAMEMBERT' => $lang['Camembert_title'],
	
	'YEAR_LIST'       => $y_list,
	'MONTH_LIST'      => $m_list,
	'L_PREV_PERIOD'   => $lang['Prev_month'],
	'L_NEXT_PERIOD'   => $lang['Next_month'],
	'L_PREV_TITLE'    => sprintf($aTitle, convert_time('F Y', mktime(0, 0, 0, $prev_m, 1, $prev_y))),
	'L_NEXT_TITLE'    => sprintf($aTitle, convert_time('F Y', mktime(0, 0, 0, $next_m, 1, $next_y))),
	'U_PREV_PERIOD'   => sprintf('stats.php?year=%d&amp;month=%d', $prev_y, $prev_m),
	'U_NEXT_PERIOD'   => sprintf('stats.php?year=%d&amp;month=%d', $next_y, $next_m),
	'U_IMG_GRAPH'     => sprintf('stats.php?img=graph&amp;year=%d&amp;month=%d', $year, $month)
));

//
// Affichons un message d'alerte au cas où le répertoire de statistiques n'est pas
// accessible en écriture.
//
if( !is_writable(WA_STATSDIR) )
{
	$output->assign_block_vars('statsdir_error', array(
		'MESSAGE' => $lang['Stats_dir_not_writable']
	));
}

$output->pparse('body');

$output->page_footer();
?>