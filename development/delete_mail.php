<?php
/*******************************************************************
 *          
 *          Fichier         :   delete_mail.php 
 *          Créé le         :   05 décembre 2003 
 *          Dernière modif  :   29 février 2004 
 *          Email           :   wascripts@phpcodeur.net 
 * 
 *              Copyright © 2002-2004 phpCodeur
 * 
 *******************************************************************/

/*******************************************************************
 *  This program is free software; you can redistribute it and/or 
 *  modify it under the terms of the GNU General Public License as 
 *  published by the Free Software Foundation; either version 2 of 
 *  the License, or (at your option) any later version. 
 *******************************************************************/


//
// Ceci est un fichier de test ou d'aide lors du développement. 
// Commentez la ligne suivante uniquement si vous êtes sùr de ce que vous faites !
//
exit('<b>Fichier de développement désactivé</b>');

/*
 * @link http://www.cru.fr/listes/atelier/bounce.html
 * 
 * Ce script se charge de scanner le compte mail indiqué pour récupérer les mail-daemon renvoyés 
 * en cas de compte inexistant ou de boite pleine et supprime les emails indiqués de la base des 
 * inscrits (si boite inexistante).
 * 
 * Si vous utilisez ce script pour scanner le compte sur lequel vous avez demandé que soient renvoyés 
 * les mails de retours d'erreur, faites attention de décommenter ensuite la ligne plus haut pour éviter 
 * des actes malveillants.
 * 
 * Je rappelle que ce script est juste un script de développement et ne devrait pas être utilisé
 */

$pop_host = '';
$pop_port = 110; // port du serveur. La valeur par défaut (110) est la plus répandue.
$pop_user = '';
$pop_pass = '';

define('IN_NEWSLETTER', true);

$waroot = '../';
require($waroot . 'start.php');
include($waroot . 'includes/class.pop.php');

$pop = new Pop();
$pop->connect($pop_host, $pop_port, $pop_user, $pop_pass);

$total    = $pop->stat_box();
$mail_box = $pop->list_mail();

$deleted_mails = array();

foreach( $mail_box AS $mail_id => $mail_size )
{
    $headers = $pop->parse_headers($mail_id);
    
/*  $output  = implode("\n", $headers) . "\n--------------------\n";
    $output .= $pop->contents[$mail_id]['message'];
    $output .= "\n------------------------";
    plain_error($output, false);
    continue;
*/  
    //
    // Les emails de retour d'erreur ne spécifient pas de return-path ou en spécifient un vide
    //
/*  if( !empty($headers['return-path']) && strlen($headers['return-path']) > 2 )
    {
        continue;
    }
*/  
    $bounce         = !empty($headers['received']) && stristr($headers['received'], 'bounce');
    $deliveryStatus = !empty($headers['content-type']) && preg_match('/report-type="?delivery-status"?/i', $headers['content-type']);
    
    if( !$bounce && !$deliveryStatus )
    {
        continue;
    }
    
    $message = $pop->contents[$mail_id]['message'];
    if( !preg_match('/<([^@>]+@[^>]+)>/', $message, $match) )
    {
        continue;
    }
    
    $sql = "SELECT abo_id 
        FROM " . ABONNES_TABLE . " 
        WHERE abo_email = '" . $db->escape($match[1]) . "'";
    $result = $db->query($sql);
    
    $abo_id = $db->result($result, 0, 'abo_id');
    
    $sql = "DELETE FROM " . ABONNES_TABLE . " WHERE abo_id = " . $abo_id;
    $db->query($sql);
    
    $sql = "DELETE FROM " . ABO_LISTE_TABLE . " WHERE abo_id = " . $abo_id;
    $db->query($sql);
    
    $deleted_mails[] = $match[1];
    
    //
    // On supprime l'email maintenant devenu inutile
    //
    $pop->delete_mail($mail_id);
}//end for

$pop->quit();

$output  = "Opération effectuée avec succés\n";
$output .= count($deleted_mails) . " compte(s) supprimé(s) pour cause d'adresse non valide.\n\n";

foreach( $deleted_mails AS $mail )
{
    $output .= ' - ' . $mail . "\n";
}

plain_error($output);

?>