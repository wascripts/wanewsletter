<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2014 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

/**
 * Wrapper pour les erreurs générées par PHP
 * Utilisé comme simple contenant pour les informations sur une erreur donnée
 */
class WanError extends Exception
{
	public function __construct($error)
	{
		$this->code    = $error['type'];
		$this->message = $error['message'];
		$this->file    = $error['file'];
		$this->line    = $error['line'];
	}
}
