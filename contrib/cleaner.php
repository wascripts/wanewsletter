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
 * 
 * Recherche les entrées orphelines dans les tables abonnes et abo_liste
 * et les efface, si demandé.
 */

//
// Ceci est un fichier de test ou d'aide lors du développement. 
// Commentez les lignes suivantes uniquement si vous êtes sùr de ce que vous faites !
//
echo "This script has been disabled for security reasons\n";
exit(0);


define('IN_NEWSLETTER', true);
define('WA_ROOTDIR',   '..');

require WA_ROOTDIR . '/start.php';

$type = ( !empty($_GET['type']) ) ? $_GET['type'] : '';

if( $type != 'files' && $type != 'subscribers' )
{
    $output->basic(
        '<h1>Selection :</h1>
<ul>
    <li> <a href="cleaner.php?type=files">Fichiers joints</a> </li>
    <li> <a href="cleaner.php?type=subscribers">Abonnés</a> </li>
</ul>');
}

if( $type == 'subscribers' )
{
    $sql = "SELECT abo_id 
        FROM " . ABONNES_TABLE;
    if( !($result = $db->query($sql)) )
    {
        trigger_error('Impossible d\'obtenir les identifiants [abonnes]', CRITICAL_ERROR);
    }
    
    $abonnes_id = array();
    while( $result->hasMore() )
    {
        $abonnes_id[] = $result->column('abo_id');
		$result->next();
    }
    
    $sql = "SELECT abo_id 
        FROM " . ABO_LISTE_TABLE . " 
        GROUP BY abo_id";
    if( !($result = $db->query($sql)) )
    {
        trigger_error('Impossible d\'obtenir les identifiants [abo_liste]', CRITICAL_ERROR);
    }
    
    $abo_liste_id = array();
    while( $result->hasMore() )
    {
        $abo_liste_id[] = $result->column('abo_id');
		$result->next();
    }
    
    $diff_1 = array_diff($abonnes_id, $abo_liste_id);
    $diff_2 = array_diff($abo_liste_id, $abonnes_id);
    
    $total_diff_1 = count($diff_1);
    $total_diff_2 = count($diff_2);
    
    if( !empty($_GET['delete']) && ( $total_diff_1 > 0 || $total_diff_2 > 0 ) )
    {
        if( $total_diff_1 > 0 )
        {
            $sql = "DELETE FROM " . ABONNES_TABLE . " 
                WHERE abo_id IN(" . implode(', ', $diff_1) . ")";
            if( !$db->query($sql) )
            {
                trigger_error('Impossible d\'effacer les entrées orphelines de la table ' . ABONNES_TABLE, CRITICAL_ERROR);
            }
        }
        
        if( $total_diff_2 > 0 )
        {
            $sql = "DELETE FROM " . ABO_LISTE_TABLE . " 
                WHERE abo_id IN(" . implode(', ', $diff_2) . ")";
            if( !$db->query($sql) )
            {
                trigger_error('Impossible d\'effacer les entrées orphelines de la table ' . ABO_LISTE_TABLE, CRITICAL_ERROR);
            }
        }
        
        $output->basic('Opération effectuée');
    }
    
    $data  = '<ul>';
    $data .= '<li>' . $total_diff_1 . ' entrées orphelines dans la table ' . ABONNES_TABLE . ' (' . implode(', ', $diff_1) . ')</li>';
    $data .= '<li>' . $total_diff_2 . ' entrées orphelines dans la table ' . ABO_LISTE_TABLE . ' (' . implode(', ', $diff_2) . ')</li>';
    $data .= '</ul>';
    
    if( $total_diff_1 > 0 || $total_diff_2 > 0 )
    {
        $data .= '<p><a href="cleaner.php?type=subscribers&amp;delete=true">Effacer les entrées orphelines</a></p>';
    }
    
    $output->basic($data);
}
else if( $type == 'files' )
{
    $sql = "SELECT file_id 
        FROM " . JOINED_FILES_TABLE;
    if( !($result = $db->query($sql)) )
    {
        trigger_error('Impossible d\'obtenir les identifiants [joined_files]', CRITICAL_ERROR);
    }
    
    $jf_id = array();
    while( $result->hasMore() )
    {
        $jf_id[] = $result->column('file_id');
		$result->next();
    }
    
    $sql = "SELECT file_id 
        FROM " . LOG_FILES_TABLE . " 
        GROUP BY file_id";
    if( !($result = $db->query($sql)) )
    {
        trigger_error('Impossible d\'obtenir les identifiants [log_files]', CRITICAL_ERROR);
    }
    
    $lf_id = array();
    while( $result->hasMore() )
    {
        $lf_id[] = $result->column('file_id');
		$result->next();
    }
    
    $diff_1 = array_diff($jf_id, $lf_id);
    $diff_2 = array_diff($lf_id, $jf_id);
    
    $total_diff_1 = count($diff_1);
    $total_diff_2 = count($diff_2);
    
    if( !empty($_GET['delete']) && ( $total_diff_1 > 0 || $total_diff_2 > 0 ) )
    {
        if( $total_diff_1 > 0 )
        {
            $sql = "DELETE FROM " . JOINED_FILES_TABLE . " 
                WHERE file_id IN(" . implode(', ', $diff_1) . ")";
            if( !$db->query($sql) )
            {
                trigger_error('Impossible d\'effacer les entrées orphelines de la table ' . JOINED_FILES_TABLE, CRITICAL_ERROR);
            }
        }
        
        if( $total_diff_2 > 0 )
        {
            $sql = "DELETE FROM " . LOG_FILES_TABLE . " 
                WHERE file_id IN(" . implode(', ', $diff_2) . ")";
            if( !$db->query($sql) )
            {
                trigger_error('Impossible d\'effacer les entrées orphelines de la table ' . LOG_FILES_TABLE, CRITICAL_ERROR);
            }
        }
        
        $output->basic('Opération effectuée');
    }
    
    $data  = '<ul>';
    $data .= '<li>' . $total_diff_1 . ' entrées orphelines dans la table ' . JOINED_FILES_TABLE . ' (' . implode(', ', $diff_1) . ')</li>';
    $data .= '<li>' . $total_diff_2 . ' entrées orphelines dans la table ' . LOG_FILES_TABLE . ' (' . implode(', ', $diff_2) . ')</li>';
    $data .= '</ul>';
    
    if( $total_diff_1 > 0 || $total_diff_2 > 0 )
    {
        $data .= '<p><a href="cleaner.php?type=files&amp;delete=true">Effacer les entrées orphelines</p>';
    }
    
    $output->basic($data);
}

exit(0);

?>