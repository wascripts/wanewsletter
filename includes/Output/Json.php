<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@webnaute.net>
 * @link      http://dev.webnaute.net/wanewsletter/
 * @copyright 2002-2021 Aurélien Maille
 * @license   https://www.gnu.org/licenses/gpl.html  GNU General Public License
 */

namespace Wanewsletter\Output;

use Wanewsletter\Error;

class Json implements MessageInterface
{
	/**
	 * Paramètres additionnels pour la réponse JSON
	 * @var array
	 * @see self::addParams()
	 */
	protected $params = [];

	/**
	 * Envoi des en-têtes HTTP
	 */
	public function httpHeaders()
	{
		global $lang;

		header('Expires: ' . gmdate(DATE_RFC1123));// HTTP/1.0
		header('Pragma: no-cache');// HTTP/1.0
		header('Cache-Control: no-cache, must-revalidate, max-age=0');
		header('Content-Language: ' . $lang['CONTENT_LANG']);
		header('Content-Type: application/json; charset=UTF-8');
	}

	public static function formatError($error)
	{
		return $error->getMessage();
	}

	public function error($error)
	{
		if ($error instanceof \Throwable || $error instanceof \Exception) {
			if ($error instanceof Error && !$error->isFatal()) {
				return null;
			}

			$error = static::formatError($error);
		}

		$this->message($error, true);
	}

	public function message($str = '', $is_error = false)
	{
		global $lang;

		if (!empty($lang['Message'][$str])) {
			$str = $lang['Message'][$str];
		}

		$this->httpHeaders();

		$json = $this->params;

		$json['error']   = (bool) $is_error;
		$json['message'] = strip_tags($str);
		echo json_encode($json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
		exit;
	}

	/**
	 * Ajout d’entrées additionnelles à la réponse JSON
	 *
	 * @param array $params
	 */
	public function addParams($params)
	{
		$this->params = array_replace_recursive($this->params, $params);
	}
}
