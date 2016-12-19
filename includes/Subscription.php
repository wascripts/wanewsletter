<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

use Patchwork\Utf8 as u;
use Wamailer\Mailer;
use Wamailer\Email;

class Subscription
{
	/**
	 * Retourne les données sur l’utilisateur.
	 * Cette méthode accepte deux modes d’appel :
	 *    ...getUserData(int $liste_id, string $email)
	 *    ...getUserData(string $register_key)
	 *
	 * @return boolean|array
	 */
	protected function getUserData()
	{
		global $db;

		$fields_str = '';
		foreach (wan_get_tags() as $tag) {
			$fields_str .= 'a.' . $tag['column_name'] . ', ';
		}

		$sql = "SELECT %s a.abo_id, a.abo_pseudo, a.abo_email, a.abo_status,
				al.format, al.register_key, al.register_date, al.confirmed, al.liste_id
			FROM %s AS a LEFT JOIN %s AS al ON al.abo_id = a.abo_id";

		if (func_num_args() == 2) {
			$sql .= " AND al.liste_id = %d WHERE LOWER(a.abo_email) = '%s'";
			$p1 = func_get_arg(0);
			$p2 = $db->escape(strtolower(func_get_arg(1)));
		}
		else {
			$sql .= " WHERE al.register_key = '%s'";
			$p1 = $db->escape(func_get_arg(0));
			$p2 = null;
		}

		$sql = sprintf($sql, $fields_str, ABONNES_TABLE, ABO_LISTE_TABLE, $p1, $p2);

		$result = $db->query($sql);

		return $result->fetch($result::FETCH_ASSOC);
	}

	/**
	 * Inscription à la liste concernée.
	 *
	 * @param array   $listdata
	 * @param string  $email
	 * @param string  $pseudo
	 * @param integer $format
	 *
	 * @throws Exception
	 * @return string
	 */
	public function subscribe(array $listdata, $email, $pseudo, $format = null)
	{
		global $db, $lang, $nl_config;

		// Vérification syntaxique de l’adresse email
		if (!Mailer::checkMailSyntax($email)) {
			throw new Exception($lang['Message']['Invalid_email']);
		}

		// Purge des éventuelles inscriptions non validées et dont
		// le délai de confirmation est dépassé, pour parer au cas
		// d’une réinscription.
		purge_liste($listdata);

		$abodata = $this->getUserData($listdata['liste_id'], $email);

		$register_key = generate_key(20);

		// L’adresse email n’est pas présente dans la table abonnés
		if (!$abodata) {
			$sql = "SELECT ban_email
				FROM " . BAN_LIST_TABLE . "
				WHERE liste_id = " . $listdata['liste_id'];
			$result = $db->query($sql);

			while ($ban_email = $result->column('ban_email')) {
				if (preg_match('/\b' . str_replace('*', '.*?', $ban_email) . '\b/i', $email)) {
					throw new Exception($lang['Message']['Email_banned']);
				}
			}

			$abodata = [];
			$abodata['abo_email']  = $email;
			$abodata['abo_pseudo'] = $pseudo;
			$abodata['abo_pwd']    = md5($register_key);
			$abodata['abo_status'] = ($listdata['confirm_subscribe'] == CONFIRM_NONE)
				? ABO_ACTIVE
				: ABO_INACTIVE;

			foreach (wan_get_tags() as $tag) {
				$input_name = (!empty($tag['field_name'])) ? $tag['field_name'] : $tag['column_name'];
				$data = u::filter_input(INPUT_POST, $input_name);

				if (!is_null($data)) {
					$abodata[$tag['column_name']] = trim($data);
				}
			}

			$db->insert(ABONNES_TABLE, $abodata);

			$abodata['abo_id']    = $db->lastInsertId();
			$abodata['confirmed'] = null;
		}

		if ($abodata['confirmed'] == SUBSCRIBE_CONFIRMED) {
			throw new Exception($lang['Message']['Allready_reg']);
		}

		$reconfirm = true;

		// Pas inscrit sur cette liste ?
		// (= les champs provenant de wa_abo_liste ont la valeur null)
		if (is_null($abodata['confirmed'])) {
			$confirmed = SUBSCRIBE_NOT_CONFIRMED;

			if ($abodata['abo_status'] == ABO_ACTIVE
				&& $listdata['confirm_subscribe'] != CONFIRM_ALWAYS
			) {
				$confirmed = SUBSCRIBE_CONFIRMED;
			}

			if ($listdata['liste_format'] == FORMAT_MULTIPLE) {
				if (!in_array($format, [FORMAT_TEXT, FORMAT_HTML])) {
					$format = FORMAT_TEXT;
				}
			}
			else {
				$format = $listdata['liste_format'];
			}

			$sql_data = [
				'abo_id'        => $abodata['abo_id'],
				'liste_id'      => $listdata['liste_id'],
				'format'        => $format,
				'register_key'  => $register_key,
				'register_date' => time(),
				'confirmed'     => $confirmed
			];
			$db->insert(ABO_LISTE_TABLE, $sql_data);
			$abodata = array_merge($abodata, $sql_data);

			$reconfirm = false;
		}

		if ($abodata['confirmed'] == SUBSCRIBE_CONFIRMED) {
			update_stats($listdata);
			$this->sendNotification('subscribe', $listdata, $abodata);
			$message = $lang['Message']['Subscribe_2'];
			$email_tpl = ($listdata['use_cron']) ? 'welcome_cron1' : 'welcome_form1';
		}
		else {
			$message = $lang['Message']['Subscribe_1'];
			if ($reconfirm) {
				$message = $lang['Message']['Reg_not_confirmed'];
			}

			$message = sprintf($message, $listdata['limitevalidate']);
			$email_tpl = ($listdata['use_cron']) ? 'welcome_cron2' : 'welcome_form2';
		}

		try {
			$subject = sprintf($lang['Subject_email']['Subscribe'], $nl_config['sitename']);
			$this->sendEmail($listdata, $abodata, $email_tpl, $subject);
		}
		catch (\Exception $e) {
			$message = sprintf($lang['Message']['Failed_sending'],
				htmlspecialchars($e->getMessage())
			);
		}

		return $message;
	}

	/**
	 * Désinscription de la liste concernée.
	 *
	 * @param array  $listdata
	 * @param string $email
	 *
	 * @throws Exception
	 * @return string
	 */
	public function unsubscribe(array $listdata, $email)
	{
		global $db, $lang;

		$abodata = $this->getUserData($listdata['liste_id'], $email);

		if (!$abodata || $abodata['confirmed'] != SUBSCRIBE_CONFIRMED) {
			throw new Exception($lang['Message']['Unknown_email']);
		}

		$abodata['register_key'] = generate_key(20);

		$sql = "UPDATE %s SET register_key = '%s' WHERE abo_id = %d AND liste_id = %d";
		$sql = sprintf($sql, ABO_LISTE_TABLE,
			$db->escape($abodata['register_key']),
			$abodata['abo_id'],
			$listdata['liste_id']
		);
		$db->query($sql);

		$email_tpl = ($listdata['use_cron']) ? 'unsubscribe_cron' : 'unsubscribe_form';

		try {
			$message = $lang['Message']['Unsubscribe_1'];
			$subject = $lang['Subject_email']['Unsubscribe_1'];
			$this->sendEmail($listdata, $abodata, $email_tpl, $subject);
		}
		catch (\Exception $e) {
			$message = sprintf($lang['Message']['Failed_sending'],
				htmlspecialchars($e->getMessage())
			);
		}

		return $message;
	}

	/**
	 * Changement de format, soit en prenant compte du paramètre $format,
	 * soit en intervertissant l’entrée 'format' dans la base de données.
	 *
	 * @param array   $listdata
	 * @param string  $email
	 * @param integer $format
	 *
	 * @throws Exception
	 * @return string
	 */
	public function setFormat(array $listdata, $email, $format = null)
	{
		global $db, $lang;

		$abodata = $this->getUserData($listdata['liste_id'], $email);

		if (!$abodata || $abodata['confirmed'] != SUBSCRIBE_CONFIRMED) {
			throw new Exception($lang['Message']['Unknown_email']);
		}

		if ($listdata['liste_format'] != FORMAT_MULTIPLE) {
			throw new Exception($lang['Message']['Inactive_format']);
		}

		if (!in_array($format, [FORMAT_TEXT, FORMAT_HTML])) {
			if ($abodata['format'] == FORMAT_TEXTE) {
				$format = FORMAT_HTML;
			}
			else {
				$format = FORMAT_TEXTE;
			}
		}

		$sql = "UPDATE %s SET format = %d WHERE liste_id = %d AND abo_id = %d";
		$sql = sprintf($sql, ABO_LISTE_TABLE, $format,
			$listdata['liste_id'],
			$abodata['abo_id']
		);
		$db->query($sql);

		return $lang['Message']['Success_setformat'];
	}

	/**
	 * Confirme l’inscription/désinscription.
	 *
	 * @param string  $code
	 * @param integer $time
	 *
	 * @throws Exception
	 * @return string
	 */
	public function checkCode($code, $time = null)
	{
		global $db, $lang;

		$abodata = $this->getUserData($code);

		if (!$abodata) {
			throw new Exception($lang['Message']['Invalid_code']);
		}

		$sql = "SELECT liste_id, liste_name, liste_format, form_url,
				return_email, sender_email, liste_alias, liste_sig, use_cron,
				limitevalidate, purge_freq, confirm_subscribe
			FROM %s
			WHERE liste_id = %d";
		$sql = sprintf($sql, LISTE_TABLE, $abodata['liste_id']);
		$result = $db->query($sql);
		$listdata = $result->fetch($result::FETCH_ASSOC);


		// Confirmation d’inscription
		if ($abodata['confirmed'] == SUBSCRIBE_NOT_CONFIRMED) {
			$time_limit = strtotime(
				sprintf('-%d days', $listdata['limitevalidate']),
				(!is_int($time)) ? time() : $time
			);

			if ($abodata['register_date'] < $time_limit) {
				throw new Exception($lang['Message']['Invalid_date']);
			}

			$db->beginTransaction();

			if ($abodata['abo_status'] == ABO_INACTIVE) {
				$sql = "UPDATE %s SET abo_status = %d WHERE abo_id = %d";
				$sql = sprintf($sql, ABONNES_TABLE, ABO_ACTIVE, $abodata['abo_id']);
				$db->query($sql);
			}

			$sql = "UPDATE %s SET confirmed = %d, register_key = '%s'
				WHERE liste_id = %d AND abo_id = %d";
			$sql = sprintf($sql, ABO_LISTE_TABLE, SUBSCRIBE_CONFIRMED,
				generate_key(20),
				$listdata['liste_id'],
				$abodata['abo_id']
			);
			$db->query($sql);

			$db->commit();

			update_stats($listdata);
			$this->sendNotification('subscribe', $listdata, $abodata);

			$message = $lang['Message']['Confirm_ok'];
		}
		// Confirmation de désinscription
		else {
			$sql = "SELECT COUNT(abo_id) AS num_subscribe
				FROM %s WHERE abo_id = %d";
			$sql = sprintf($sql, ABO_LISTE_TABLE, $abodata['abo_id']);
			$result = $db->query($sql);

			$num_subscribe = $result->column('num_subscribe');

			$db->beginTransaction();

			$sql = "DELETE FROM %s WHERE liste_id = %d AND abo_id = %d";
			$sql = sprintf($sql, ABO_LISTE_TABLE, $listdata['liste_id'], $abodata['abo_id']);
			$db->query($sql);

			if ($num_subscribe == 1) {
				$sql = "DELETE FROM %s WHERE abo_id = %d";
				$sql = sprintf($sql, ABONNES_TABLE, $abodata['abo_id']);
				$db->query($sql);

				$message = $lang['Message']['Unsubscribe_3'];
			}
			else {
				$message = $lang['Message']['Unsubscribe_2'];
			}

			$db->commit();

			$this->sendNotification('unsubscribe', $listdata, $abodata);
		}

		return $message;
	}

	/**
	 * Envoi d’un email à l’abonné (email de bienvenue, ou de confirmation
	 * d’inscription/désinscription).
	 *
	 * @param array  $listdata
	 * @param array  $abodata
	 * @param string $email_tpl
	 * @param string $subject
	 */
	protected function sendEmail(array $listdata, array $abodata, $email_tpl, $subject)
	{
		global $lang, $nl_config;

		$template = new Template(sprintf('%s/languages/%s/emails/%s.txt',
			WA_ROOTDIR,
			$nl_config['language'],
			$email_tpl
		));
		$template->assign([
			'LISTE'    => $listdata['liste_name'],
			'SITENAME' => $nl_config['sitename'],
			'URLSITE'  => $nl_config['urlsite'],
			'SIG'      => $listdata['liste_sig'],
			'PSEUDO'   => $abodata['abo_pseudo']
		]);

		if ($listdata['use_cron']) {
			$liste_email = (!empty($listdata['liste_alias']))
				? $listdata['liste_alias']
				: $listdata['sender_email'];

			$template->assign([
				'EMAIL_NEWSLETTER' => $liste_email,
				'CODE'             => $abodata['register_key']
			]);
		}
		else {
			$form_url = get_form_url($listdata);
			if (!empty($GLOBALS['formURL'])) {
				$form_url = $GLOBALS['formURL'];
			}

			$form_url .= (strstr($form_url, '?') ? '&' : '?');
			$form_url .= $abodata['register_key'];

			$template->assign(['LINK' => $form_url]);
		}

		$tags = [];
		foreach (wan_get_tags() as $tag) {
			if (isset($abodata[$tag['column_name']])) {
				$tags[$tag['tag_name']] = $abodata[$tag['column_name']];
			}
		}

		$template->assign($tags);

		if ($nl_config['enable_profil_cp']) {
			$link_profil_cp = $nl_config['urlsite'] . $nl_config['path'] . 'profil_cp.php';
			$template->assignToBlock('enable_profil_cp', [
				'LINK_PROFIL_CP' => $link_profil_cp
			]);
		}

		$body = $template->pparse(true);

		$email = new Email();
		$email->setFrom($listdata['sender_email'], $listdata['liste_name']);
		$email->addRecipient($abodata['abo_email']);
		$email->setSubject($subject);
		$email->setTextBody($body);

		if ($listdata['return_email']) {
			$email->setReturnPath($listdata['return_email']);
		}

		wamailer()->send($email);
	}

	/**
	 * Envoi d’une notification aux administrateurs autorisés en ayant fait
	 * la demande.
	 *
	 * @param string $type Peut valoir 'subscribe' ou 'unsubscribe'
	 * @param array  $listdata
	 * @param array  $abodata
	 */
	protected function sendNotification($type, array $listdata, array $abodata)
	{
		global $db, $lang, $nl_config;

		if ($type == 'subscribe') {
			$fieldname  = 'email_new_subscribe';
			$fieldvalue = SUBSCRIBE_NOTIFY_YES;
			$subject    = $lang['Subject_email']['New_subscribe'];
			$email_tpl  = 'admin_new_subscribe';
		}
		else {
			$fieldname  = 'email_unsubscribe';
			$fieldvalue = UNSUBSCRIBE_NOTIFY_YES;
			$subject    = $lang['Subject_email']['Unsubscribe_2'];
			$email_tpl  = 'admin_unsubscribe';
		}

		$sql = "SELECT a.admin_login, a.admin_email, a.admin_level, aa.auth_view
			FROM %s AS a
				LEFT JOIN %s AS aa ON aa.admin_id = a.admin_id
					AND aa.liste_id = %d
			WHERE a.%s = %d";
		$sql = sprintf($sql, ADMIN_TABLE, AUTH_ADMIN_TABLE,
			$listdata['liste_id'],
			$db->quote($fieldname),
			$fieldvalue
		);
		$result = $db->query($sql);

		if (!($admindata = $result->fetch())) {
			return null;
		}

		$template = new Template(sprintf('%s/languages/%s/emails/%s.txt',
			WA_ROOTDIR,
			$nl_config['language'],
			$email_tpl
		));
		$template->assign([
			'EMAIL'   => $abodata['abo_email'],
			'LISTE'   => $listdata['liste_name'],
			'URLSITE' => $nl_config['urlsite'],
			'SIG'     => $listdata['liste_sig'],
			'PSEUDO'  => $abodata['abo_pseudo']
		]);

		$email = new Email();
		$email->setFrom($listdata['sender_email'], $listdata['liste_name']);
		$email->setSubject($subject);

		if ($listdata['return_email']) {
			$email->setReturnPath($listdata['return_email']);
		}

		$tags = [];
		foreach (wan_get_tags() as $tag) {
			if (isset($abodata[$tag['column_name']])) {
				$tags[$tag['tag_name']] = $abodata[$tag['column_name']];
			}
		}

		$template->assign($tags);

		do {
			if (!Auth::isAdmin($admindata) && !$admindata['auth_view']) {
				continue;
			}

			$template->assign(['USER' => $admindata['admin_login']]);
			$body = $template->pparse(true);

			$email->clearRecipients();
			$email->addRecipient($admindata['admin_email'], $admindata['admin_login']);
			$email->setTextBody($body);

			try {
				wamailer()->send($email);
			}
			catch (\Exception $e) { wanlog($e); }
		}
		while ($admindata = $result->fetch());
	}
}
