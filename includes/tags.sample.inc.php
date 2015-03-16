<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 *
 * Copiez ou renommez ce fichier en tags.inc.php avant utilisation !
 */

if (!defined('IN_NEWSLETTER')) {
    exit('<b>No hacking</b>');
}

$other_tags = array();
$t = 0;

//
// Placez ici vos tags personnalisés
//
// - column_name doit contenir le nom de la colonne concernée dans la table prefixe_abonnes
// - tag_name peut contenir le nom du tag à remplacer dans les newsletters lors des envois
// - field_name peut contenir le nom du champ de formulaire à réceptionner lors des inscriptions
//   ou des modifications de compte (si field_name n'est pas renseigné, le script utilisera
//   la valeur de 'column_name')
//
// LINKS, NAME, WA_EMAIL et WA_CODE sont des noms de tag réservés
//

//$other_tags[$t]['column_name'] = '';
//$other_tags[$t]['tag_name']    = '';
//$other_tags[$t]['field_name']  = '';
//$t++;

//$other_tags[$t]['column_name'] = '';
//$other_tags[$t]['tag_name']    = '';
//$other_tags[$t]['field_name']  = '';
//$t++;

// etc... Reproduisez les trois lignes si nécessaires.
