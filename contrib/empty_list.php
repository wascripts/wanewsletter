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
 * @version $Id: profil_cp.php 221 2005-11-22 00:07:42Z bobe $
 * 
 * Ce script vous permet de supprimer rapidement tous les abonnés inscrits à une
 * liste donnée et/ou toutes les archives ainsi que les fichiers joints associés.
 * 
 * Il est recommandé de faire une sauvegarde des tables wa_abonnes, wa_abo_liste
 * et wa_log.
 */

//
// Ceci est un fichier de test ou d'aide lors du développement. 
// Commentez les lignes suivantes uniquement si vous êtes sùr de ce que vous faites !
//
echo "This script has been disabled for security reasons\n";
exit(0);


define('IN_NEWSLETTER', true);
define('WA_ROOTDIR',    '.');

require WA_ROOTDIR . '/start.php';

//
// Configuration du script
//
$liste = 0; // Identifiant de la liste concernée
$remove_logs = false; // Suppression des archives ?
$remove_abo  = true; // Suppression des abonnés ?

//
// Fin de la configuration
//

if( $remove_abo == true ) {
	$db->beginTransaction();
	
	switch( SQL_DRIVER ) {
		case 'mysql':
			$sql = "SELECT abo_id 
				FROM " . ABO_LISTE_TABLE . " AS al 
				WHERE liste_id = " . $liste;
			if( !($result = $db->query($sql)) )
			{
				trigger_error('Impossible d\'obtenir la liste des abonnés de cette liste', ERROR);
			}
			
			$abo_ids = array();
			while( $abo_id = $result->column('abo_id') )
			{
				array_push($abo_ids, $abo_id);
			}
			
			if( count($abo_ids) > 0 )
			{
				$sql = "SELECT abo_id 
					FROM " . ABO_LISTE_TABLE . " 
					WHERE abo_id IN(" . implode(', ', $abo_ids) . ") 
					GROUP BY abo_id 
					HAVING COUNT(abo_id) = 1";
				if( !($result = $db->query($sql)) )
				{
					trigger_error('Impossible d\'obtenir la liste des comptes à supprimer', ERROR);
				}
				
				$abo_ids = array();
				while( $abo_id = $result->column('abo_id') )
				{
					array_push($abo_ids, $abo_id);
				}
				
				if( count($abo_ids) > 0 )
				{
					$sql = "DELETE FROM " . ABONNES_TABLE . " 
						WHERE abo_id IN(" . implode(', ', $abo_ids) . ")";
					if( !$db->query($sql) )
					{
						trigger_error('Impossible de supprimer les entrées inutiles de la table des abonnés', ERROR);
					}
				}
			}
			break;
		
		default:
			$sql = "DELETE FROM " . ABONNES_TABLE . "
				WHERE abo_id IN(
					SELECT abo_id
					FROM " . ABO_LISTE_TABLE . "
					WHERE abo_id IN(
						SELECT abo_id
						FROM " . ABO_LISTE_TABLE . " AS al
						WHERE liste_id = $liste
					)
					GROUP BY abo_id
					HAVING COUNT(abo_id) = 1
				)";
			if( !$db->query($sql) )
			{
				trigger_error('Impossible de supprimer les entrées inutiles de la table des abonnés', ERROR);
			}
			break;
	}
	
	$sql = "DELETE FROM " . ABO_LISTE_TABLE . " 
		WHERE liste_id = " . $liste;
	if( !$db->query($sql) )
	{
		trigger_error('Impossible de supprimer les entrées de la table abo_liste', ERROR);
	}
	
	$db->commit();
}

if( $remove_logs == true ) {
	$sql = "SELECT log_id
		FROM " . LOG_TABLE . "
		WHERE liste_id = " . $liste;
	if( !($result = $db->query($sql)) )
	{
		trigger_error('Impossible d\'obtenir la liste des logs', ERROR);
	}
	
	$log_ids = array();
	while( $log_id = $result->column('log_id') )
	{
		array_push($log_ids, $log_id);
	}
	
	$db->beginTransaction();
	
	require WA_ROOTDIR . '/includes/class.attach.php';
	
	$attach = new Attach();
	$attach->delete_joined_files(true, $log_ids);
	
	$sql = "DELETE FROM " . LOG_TABLE . "
		WHERE liste_id = " . $liste;
	if( !$db->query($sql) )
	{
		trigger_error('Impossible de supprimer les entrées de la table des logs', ERROR);
	}
	
	$db->commit();
}

?>
