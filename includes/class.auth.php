<?php
/**
 * Copyright (c) 2002-2014 Aurélien Maille
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

if( !defined('CLASS_AUTH_INC') ) {

define('CLASS_AUTH_INC', true);

//
// permissions 
//
define('AUTH_VIEW',   1);
define('AUTH_EDIT',   2);
define('AUTH_DEL',    3);
define('AUTH_SEND',   4);
define('AUTH_IMPORT', 5);
define('AUTH_EXPORT', 6);
define('AUTH_BAN',    7);
define('AUTH_ATTACH', 8);

/**
 * Class Auth
 * 
 * Gestion des permissions des utilisateurs
 */ 
class Auth {
	
	var $listdata  = array();
	var $rowset    = array();
	
	var $auth_ary  = array(
			AUTH_VIEW   => 'auth_view',
			AUTH_EDIT   => 'auth_edit',
			AUTH_DEL    => 'auth_del',
			AUTH_SEND   => 'auth_send',
			AUTH_IMPORT => 'auth_import',
			AUTH_EXPORT => 'auth_export',
			AUTH_BAN    => 'auth_ban',
			AUTH_ATTACH => 'auth_attach'
		);
	
	/**
	 * Auth::Auth()
	 * 
	 * Initialisation de la classe, et récupération des permissions de l'utilisateur courant
	 * 
	 * @return void
	 */
	function Auth()
	{
		global $admindata;
		
		$this->read_data($admindata['admin_id']);
	}
	
	/**
	 * Auth::read_data()
	 * 
	 * Récupèration des permissions pour l'utilisateur demandé
	 * 
	 * @param integer $admin_id    Identifiant de l'utilisateur concerné
	 * 
	 * @return void
	 */
	function read_data($admin_id)
	{
		global $db, $admindata;
		
		$sql = "SELECT li.liste_id, li.liste_name, li.liste_format, li.sender_email, li.return_email,
				li.confirm_subscribe, li.liste_public, li.limitevalidate, li.form_url, li.liste_sig,
				li.auto_purge, li.purge_freq, li.purge_next, li.liste_startdate, li.use_cron, li.pop_host,
				li.pop_port, li.pop_user, li.pop_pass, li.liste_alias, li.liste_numlogs, aa.auth_view, aa.auth_edit,
				aa.auth_del, aa.auth_send, aa.auth_import, aa.auth_export, aa.auth_ban, aa.auth_attach, aa.cc_admin
			FROM " . LISTE_TABLE . " AS li
				LEFT JOIN " . AUTH_ADMIN_TABLE . " AS aa ON aa.admin_id = $admin_id
					AND aa.liste_id = li.liste_id
			ORDER BY li.liste_name ASC";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir les données des listes de diffusion', ERROR);
		}
		
		$tmp_ary = array();
		while( $row = $result->fetch() )
		{
			$tmp_ary[$row['liste_id']] = $row;
		}
		
		if( $admindata['admin_id'] != $admin_id )
		{
			return $tmp_ary;
		}
		
		$this->listdata = $tmp_ary;
	}
	
	/**
	 * Auth::check_auth()
	 * 
	 * Fonction de vérification des permissions, selon la permission concernée et la liste concernée
	 * Si vérification pour une liste particulière, retourne un booléen, sinon retourne un tableau d'identifiant 
	 * des listes pour lesquelles la permission est accordée
	 * 
	 * @param integer $auth_type    Code de la permission concernée
	 * @param integer $liste_id     Identifiant de la liste concernée
	 * 
	 * @return array/boolean
	 */
	function check_auth($auth_type, $liste_id = null)
	{
		global $admindata;
		
		$auth_name = $this->auth_ary[$auth_type];
		
		if( $liste_id == null )
		{
			$liste_id_ary = array();
			foreach( $this->listdata as $liste_id => $auth_list )
			{
				if( $admindata['admin_level'] == ADMIN || !empty($auth_list[$auth_name]) )
				{
					$liste_id_ary[] = $liste_id;
				}
			}
			
			return $liste_id_ary;
		}
		else
		{
			if( isset($this->listdata[$liste_id])
				&& ($admindata['admin_level'] == ADMIN || !empty($this->listdata[$liste_id][$auth_name])) )
			{
				return true;
			}
			
			return false;
		}
	}
	
	/**
	 * Auth::box_auth()
	 * 
	 * Construction de la liste déroulante oui/non pour la permission concernée et la liste concernée
	 * 
	 * @param integer $auth_type    Code de la permission
	 * @param array   $listdata     Tableau des permissions pour la liste en cours
	 * 
	 * @return string
	 */
	function box_auth($auth_type, $listdata)
	{
		global $lang;
		
		$auth_name = $this->auth_ary[$auth_type];
		
		$selected_yes = ( !empty($listdata[$auth_name]) ) ? ' selected="selected"' : '';
		$selected_no  = ( empty($listdata[$auth_name]) ) ? ' selected="selected"' : '';
		
		$box_auth  = '<select name="' . $auth_name . '[]">';
		$box_auth .= '<option value="1"' . $selected_yes . '> ' . $lang['Yes'] . ' </option>';
		$box_auth .= '<option value="0"' . $selected_no . '> ' . $lang['No'] . ' </option>';
		$box_auth .= '</select>';
		
		return $box_auth;
	}
}

}
?>