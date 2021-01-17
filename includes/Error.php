<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@webnaute.net>
 * @link      http://dev.webnaute.net/wanewsletter/
 * @copyright 2002-2021 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

/**
 * Wrapper pour les erreurs générées par PHP
 * Utilisé comme simple contenant pour les informations sur une erreur donnée
 */
class Error extends Exception
{
	/**
	 * Marqueur indiquant si l’erreur est prise en compte par le niveau
	 * de rapport d’erreurs existant au moment de son traitement.
	 *
	 * @var boolean
	 */
	protected $_ignore;

	/**
	 * @param array $error
	 */
	public function __construct(array $error)
	{
		$this->code    = $error['type'];
		$this->message = $error['message'];
		$this->file    = $error['file'];
		$this->line    = $error['line'];
		$this->_ignore = $error['ignore'];
	}

	/**
	 * @return boolean
	 */
	public function ignore()
	{
		return $this->_ignore;
	}

	/**
	 * Indique si l’erreur est fatale.
	 *
	 * @return boolean
	 */
	public function isFatal()
	{
		return ($this->code == E_USER_ERROR || $this->code == E_RECOVERABLE_ERROR);
	}
}
