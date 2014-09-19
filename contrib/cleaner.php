<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 * 
 * - Recherche les entrées orphelines dans les tables abonnes et abo_liste
 * et les efface, si demandé.
 * - Peut également effacer les fichiers présents dans le répertoire d'upload
 * sans correspondance dans les tables de fichiers joints ainsi que les entrées
 * dans les tables de fichiers pour lesquelles le fichier n'est plus présent
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

load_settings();

$type = ( !empty($_GET['type']) ) ? $_GET['type'] : '';

if( $type != 'files' && $type != 'files2' && $type != 'subscribers' )
{
    $output->basic(
        '<h1>Outils&#160;:</h1>

<dl>
	<dt><a href="cleaner.php?type=files">Fichiers joints</a></dt>
	<dd>Supprime les entrées de la table wa_joined_files qui n\'ont pas de correspondance
	dans la table wa_log_files, et vice versa.</dd>
	<dt><a href="cleaner.php?type=files2">Fichiers joints (2)</a></dt>
	<dd>Supprime les entrées des tables wa_joined_files et wa_log_files si le fichier concerné
	n\'est plus présent dans le répertoire des fichiers joints, et vice versa</dd>
	<dt><a href="cleaner.php?type=subscribers">Abonnés</a></dt>
	<dd>Supprime les entrées de la table wa_abonnes qui n\'ont pas de correspondance
	dans la table wa_abo_liste, et vice versa.</dd>
</dl>

<p>Le script affiche le nombre d\'entrées concernées et demande confirmation avant de les supprimer.</p>');
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
    while( $abo_id = $result->column('abo_id') )
    {
        array_push($abonnes_id, $abo_id);
    }
    
    $sql = "SELECT abo_id 
        FROM " . ABO_LISTE_TABLE . " 
        GROUP BY abo_id";
    if( !($result = $db->query($sql)) )
    {
        trigger_error('Impossible d\'obtenir les identifiants [abo_liste]', CRITICAL_ERROR);
    }
    
    $abo_liste_id = array();
    while( $abo_id = $result->column('abo_id') )
    {
		array_push($abo_liste_id, $abo_id);
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
    while( $id = $result->column('file_id') )
    {
        array_push($jf_id, $id);
    }
    
    $sql = "SELECT file_id 
        FROM " . LOG_FILES_TABLE . " 
        GROUP BY file_id";
    if( !($result = $db->query($sql)) )
    {
        trigger_error('Impossible d\'obtenir les identifiants [log_files]', CRITICAL_ERROR);
    }
    
    $lf_id = array();
    while( $id = $result->column('file_id') )
    {
        array_push($lf_id, $id);
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
    $data .= '<li>' . $total_diff_1 . ' entrées orphelines dans la table ' . JOINED_FILES_TABLE . ' (ids: ' . implode(', ', $diff_1) . ')</li>';
    $data .= '<li>' . $total_diff_2 . ' entrées orphelines dans la table ' . LOG_FILES_TABLE . ' (ids: ' . implode(', ', $diff_2) . ')</li>';
    $data .= '</ul>';
    
    if( $total_diff_1 > 0 || $total_diff_2 > 0 )
    {
        $data .= '<p><a href="cleaner.php?type=files&amp;delete=true">Effacer les entrées orphelines</p>';
    }
    
    $output->basic($data);
}
else if( $type == 'files2' )
{
	$sql = "SELECT file_id, file_physical_name
		FROM " . JOINED_FILES_TABLE;
	if( !($result = $db->query($sql)) )
    {
        trigger_error('Impossible d\'obtenir les identifiants [joined_files]', CRITICAL_ERROR);
    }
	
	$upload_path    = WA_ROOTDIR . '/' . $nl_config['upload_path'];
	$sql_delete_ids = array();
	$joined_files   = array();
	$delete_files   = array();
	while( $row = $result->fetch() )
	{
		if( !file_exists($upload_path . $row['file_physical_name']) ) {
			array_push($sql_delete_ids, $row['file_id']);
		}
		
		array_push($joined_files, $row['file_physical_name']);
	}
	
	$browse = dir($upload_path);
	while( ($entry = $browse->read()) !== false )
	{
		if( is_file($upload_path . $entry) && $entry != 'index.html' && !in_array($entry, $joined_files) )
		{
			array_push($delete_files, $entry);
			
			if( !empty($_GET['delete']) )
			{
				unlink($upload_path . $entry);
			}
		}
	}
	
	if( !empty($_GET['delete']) )
    {
		if( count($sql_delete_ids) > 0 )
		{
			$db->beginTransaction();
			
			$sql = "DELETE FROM " . JOINED_FILES_TABLE . " 
				WHERE file_id IN(" . implode(', ', $sql_delete_ids) . ")";
			$db->query($sql);
			
			$sql = "DELETE FROM " . LOG_FILES_TABLE . " 
				WHERE file_id IN(" . implode(', ', $sql_delete_ids) . ")";
			$db->query($sql);
			
			$db->commit();
		}
        
        $output->basic('Opération effectuée');
    }
    
    $data  = '<ul>';
    $data .= '<li>' . count($sql_delete_ids) . ' fichiers manquants (ids: ' . implode(', ', $sql_delete_ids) . ')</li>';
    $data .= '<li>' . count($delete_files) . ' fichiers dans "' . $nl_config['upload_path'] . '" sans entrée correspondante dans la base de données (fichiers: ' . implode(', ', $delete_files) . ')</li>';
    $data .= '</ul>';
    
    if( count($sql_delete_ids) > 0 || count($delete_files) > 0 )
    {
        $data .= '<p><a href="cleaner.php?type=files2&amp;delete=true">Effacer les entrées orphelines</p>';
    }
    
    $output->basic($data);
}

exit(0);

?>