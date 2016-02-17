<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter\Output;

interface MessageInterface
{
	/**
	 * Formatage d’une exception/erreur pour affichage.
	 *
	 * @param \Throwable $error
	 */
	public static function formatError($error);

	/**
	 * Affichage d’un message d’erreur.
	 * Appelle à son tour self::message()
	 *
	 * @param mixed $error Peut être une simple chaîne à afficher, ou bien
	 *                     un objet \Throwable
	 */
	public function error($error);

	/**
	 * Affichage d’un message d’information/erreur.
	 *
	 * @param string  $str
	 * @param boolean $is_error
	 */
	public function message($str = '', $is_error = false);
}
