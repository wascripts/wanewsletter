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
	private $format      = FORMAT_TEXT;
	private $listdata    = [];
	public  $liste_email = '';

	private $account      = [];
	private $hasAccount   = false;
	private $isRegistered = false;
	public  $message      = '';

	private $tpldir;
	private $other_tags;

	public function __construct($listdata = null)
	{
		global $nl_config;

		$this->tpldir = sprintf('%s/languages/%s/emails/', WA_ROOTDIR, $nl_config['language']);

		if (isset($listdata)) {
			$this->listdata    = $listdata;
			$this->liste_email = (!empty($listdata['liste_alias']))
				? $listdata['liste_alias'] : $listdata['sender_email'];

			if ($listdata['liste_format'] == FORMAT_TEXT || $listdata['liste_format'] == FORMAT_HTML) {
				$this->format = $listdata['liste_format'];
			}
		}

		$this->other_tags = wan_get_tags();
	}

	private function check($action, $email, $pseudo)
	{
		global $db, $lang;

		//
		// Vérification syntaxique de l'email
		//
		if (!Mailer::checkMailSyntax($email)) {
			return ['error' => true, 'message' => $lang['Message']['Invalid_email']];
		}

		//
		// Vérification de la liste des masques de bannissements
		//
		if ($action == 'inscription') {
			$sql = "SELECT ban_email
				FROM " . BAN_LIST_TABLE . "
				WHERE liste_id = " . $this->listdata['liste_id'];
			$result = $db->query($sql);

			while ($ban_email = $result->column('ban_email')) {
				if (preg_match('/\b' . str_replace('*', '.*?', $ban_email) . '\b/i', $email)) {
					return ['error' => true, 'message' => $lang['Message']['Email_banned']];
				}
			}
		}

		if (count($this->other_tags) > 0) {
			$fields_str = '';
			foreach ($this->other_tags as $tag) {
				$fields_str .= 'a.' . $tag['column_name'] . ', ';
			}
		}
		else {
			$fields_str = '';
		}

		$sql = "SELECT $fields_str a.abo_id, a.abo_pseudo, a.abo_pwd, a.abo_email, a.abo_lang,
				a.abo_status, al.format, al.register_key, al.register_date, al.confirmed
			FROM " . ABONNES_TABLE . " AS a
				LEFT JOIN " . ABO_LISTE_TABLE . " AS al ON al.abo_id = a.abo_id
					AND al.liste_id = {$this->listdata['liste_id']}
			WHERE LOWER(a.abo_email) = '" . $db->escape(strtolower($email)) . "'";
		$result = $db->query($sql);

		if ($abodata = $result->fetch()) {
			if (!is_null($abodata['confirmed'])) {
				if ($action == 'inscription' && $abodata['confirmed'] == SUBSCRIBE_CONFIRMED) {
					return ['error' => true, 'message' => $lang['Message']['Allready_reg']];
				}
				else if ($action == 'desinscription' && $abodata['confirmed'] == SUBSCRIBE_NOT_CONFIRMED) {
					return ['error' => true, 'message' => $lang['Message']['Unknown_email']];
				}
			}
			else if ($action != 'inscription') {
				return ['error' => true, 'message' => $lang['Message']['Unknown_email']];
			}
		}
		else if ($action != 'inscription') {
			return ['error' => true, 'message' => $lang['Message']['Unknown_email']];
		}

		$this->account['tags'] = [];

		if (is_array($abodata)) {
			$this->hasAccount   = true;
			$this->isRegistered = !is_null($abodata['confirmed']);

			$this->account['abo_id'] = $abodata['abo_id'];
			$this->account['email']  = $abodata['abo_email'];
			$this->account['pseudo'] = $abodata['abo_pseudo'];
			$this->account['status'] = $abodata['abo_status'];

			foreach ($this->other_tags as $tag) {
				if (isset($abodata[$tag['column_name']])) {
					$this->account['tags'][$tag['column_name']] = $abodata[$tag['column_name']];
				}
			}
		}
		else {
			$this->hasAccount = false;

			$this->account['abo_id'] = 0;
			$this->account['email']  = $email;
			$this->account['pseudo'] = (is_string($pseudo)) ? trim($pseudo) : '';
			$this->account['status'] = ($this->listdata['confirm_subscribe'] == CONFIRM_NONE) ? ABO_ACTIVE : ABO_INACTIVE;
		}

		if ($this->isRegistered) {
			$this->account['code']   = $abodata['register_key'];
			$this->account['date']   = $abodata['register_date'];
			$this->account['format'] = $abodata['format'];
		}
		else {
			$this->account['code']   = generate_key(20);
			$this->account['date']   = time();
			$this->account['format'] = $this->format;
		}

		return ['error' => false, 'abodata' => $abodata];
	}

	public function do_action($action, $email, $format = null, $pseudo = null)
	{
		if ($this->listdata['liste_format'] == FORMAT_MULTIPLE &&
			!is_null($format) &&
			in_array($format, [FORMAT_TEXT, FORMAT_HTML])
		) {
			$this->format = $format;
		}

		$result = $this->check($action, $email, $pseudo);

		if (!$result['error']) {
			switch ($action) {
				case 'inscription':
					$this->subscribe();
					break;
				case 'desinscription':
					$this->unsubscribe();
					break;
				case 'setformat':
					$this->setformat();
					break;
			}
		}
		else if (empty($this->message)) {
			$this->message = $result['message'];
		}
	}

	public function check_code($code, $time = null)
	{
		global $db, $lang;

		if (count($this->other_tags) > 0) {
			$fields_str = '';
			foreach ($this->other_tags as $tag) {
				$fields_str .= 'a.' . $tag['column_name'] . ', ';
			}
		}
		else {
			$fields_str = '';
		}

		$sql = "SELECT $fields_str a.abo_id, a.abo_pseudo, a.abo_email, a.abo_status,
				al.confirmed, al.register_date, l.liste_id, l.liste_format,
				l.sender_email, l.liste_alias, l.limitevalidate, l.liste_name,
				l.return_email, l.form_url, l.liste_sig, l.use_cron, l.confirm_subscribe
			FROM " . ABONNES_TABLE . " AS a
				INNER JOIN " . ABO_LISTE_TABLE . " AS al ON al.abo_id = a.abo_id
					AND al.register_key = '" . $db->escape($code) . "'
				INNER JOIN " . LISTE_TABLE . " AS l ON l.liste_id = al.liste_id";
		$result = $db->query($sql);

		if ($abodata = $result->fetch()) {
			$this->account['abo_id'] = $abodata['abo_id'];
			$this->account['email']  = $abodata['abo_email'];
			$this->account['pseudo'] = $abodata['abo_pseudo'];
			$this->account['status'] = $abodata['abo_status'];
			$this->account['date']   = $abodata['register_date'];
			$this->account['code']   = $code;
			$this->account['tags']   = [];

			foreach ($this->other_tags as $tag) {
				if (isset($abodata[$tag['column_name']])) {
					$this->account['tags'][$tag['column_name']] = $abodata[$tag['column_name']];
				}
			}

			$this->listdata = $abodata;// Récupération des données relatives à la liste

			if ($abodata['confirmed'] == SUBSCRIBE_NOT_CONFIRMED) {
				$this->confirm($time);
			}
			else {
				$this->unsubscribe($code);
			}
		}
		else {
			$this->message = $lang['Message']['Invalid_code'];
		}
	}

	private function subscribe()
	{
		global $db, $nl_config, $lang;

		$db->beginTransaction();

		if (!$this->hasAccount) {
			$sql_data = [
				'abo_email'  => $this->account['email'],
				'abo_pseudo' => $this->account['pseudo'],
				'abo_pwd'    => md5($this->account['code']),
				'abo_status' => $this->account['status']
			];

			foreach ($this->other_tags as $tag) {
				$input_name = (!empty($tag['field_name'])) ? $tag['field_name'] : $tag['column_name'];
				$data = u::filter_input(INPUT_POST, $input_name);

				if (!is_null($data)) {
					$this->account['tags'][$tag['column_name']] = trim($data);
				}
			}

			$sql_data = array_merge($sql_data, $this->account['tags']);
			$db->insert(ABONNES_TABLE, $sql_data);

			$this->account['abo_id'] = $db->lastInsertId();
		}

		if (!$this->isRegistered) {
			$confirmed = SUBSCRIBE_NOT_CONFIRMED;

			if (!$this->hasAccount && $this->listdata['confirm_subscribe'] == CONFIRM_NONE) {
				$confirmed = SUBSCRIBE_CONFIRMED;
			}

			if ($this->hasAccount && $this->account['status'] == ABO_ACTIVE &&
				$this->listdata['confirm_subscribe'] != CONFIRM_ALWAYS
			) {
				$confirmed = SUBSCRIBE_CONFIRMED;
			}

			$sql_data = [
				'abo_id'        => $this->account['abo_id'],
				'liste_id'      => $this->listdata['liste_id'],
				'format'        => $this->format,
				'register_key'  => $this->account['code'],
				'register_date' => $this->account['date'],
				'confirmed'     => $confirmed
			];
			$db->insert(ABO_LISTE_TABLE, $sql_data);
		}

		$db->commit();

		if (!$this->hasAccount) {
			//
			// Une confirmation est envoyée si la liste le demande
			//
			$confirm = !($this->listdata['confirm_subscribe'] == CONFIRM_NONE);
		}
		else {
			//
			// Une confirmation est envoyée si la liste demande une confirmation même
			// si l'email a été validé dans une précédente inscription à une autre liste,
			// et également si l'inscription est faite mais n'a pas encore été confirmée.
			//
			$confirm = ($this->isRegistered || $this->listdata['confirm_subscribe'] == CONFIRM_ALWAYS);
		}

		if (!$confirm) {
			$this->update_stats();
			$this->alert_admin(true);
			$message = $lang['Message']['Subscribe_2'];
			$email_tpl = ($this->listdata['use_cron']) ? 'welcome_cron1' : 'welcome_form1';
		}
		else {
			$name = ($this->hasAccount && $this->isRegistered) ? 'Reg_not_confirmed' : 'Subscribe_1';
			$message = sprintf($lang['Message'][$name], $this->listdata['limitevalidate']);
			$email_tpl = ($this->listdata['use_cron']) ? 'welcome_cron2' : 'welcome_form2';
		}

		$template = new Template(sprintf('%s/%s.txt', $this->tpldir, $email_tpl));
		$template->assign([
			'LISTE'    => $this->listdata['liste_name'],
			'SITENAME' => $nl_config['sitename'],
			'URLSITE'  => $nl_config['urlsite'],
			'SIG'      => $this->listdata['liste_sig'],
			'PSEUDO'   => $this->account['pseudo']
		]);

		if ($this->listdata['use_cron']) {
			$template->assign(['EMAIL_NEWSLETTER' => $this->liste_email]);
		}
		else {
			$template->assign(['LINK' => $this->make_link()]);
		}

		if (count($this->other_tags) > 0) {
			$tags = [];
			foreach ($this->other_tags as $tag) {
				if (isset($this->account['tags'][$tag['column_name']])) {
					$tags[$tag['tag_name']] = $this->account['tags'][$tag['column_name']];
				}
			}

			$template->assign($tags);
		}

		if ($nl_config['enable_profil_cp']) {
			$link_profil_cp = $nl_config['urlsite'] . $nl_config['path'] . 'profil_cp.php';
			$template->assignToBlock('enable_profil_cp', [
				'LINK_PROFIL_CP' => $link_profil_cp
			]);
		}

		$body = $template->pparse(true);

		$email = new Email();
		$email->setFrom($this->listdata['sender_email'], $this->listdata['liste_name']);
		$email->addRecipient($this->account['email']);
		$email->setSubject(sprintf($lang['Subject_email']['Subscribe'], $nl_config['sitename']));
		$email->setTextBody($body);

		if ($this->listdata['return_email'] != '') {
			$email->setReturnPath($this->listdata['return_email']);
		}

		try {
			wamailer()->send($email);
		}
		catch (\Exception $e) {
			$message = sprintf($lang['Message']['Failed_sending'], htmlspecialchars($e->getMessage()));
		}

		$this->message = $message;
	}

	private function confirm($time = null)
	{
		global $db, $lang;

		$time_limit = strtotime(
			sprintf('-%d days', $this->listdata['limitevalidate']),
			(!is_int($time)) ? time() : $time
		);

		if ($this->account['date'] > $time_limit) {
			$db->beginTransaction();

			if ($this->account['status'] == ABO_INACTIVE) {
				$sql = "UPDATE " . ABONNES_TABLE . "
					SET abo_status = " . ABO_ACTIVE . "
					WHERE abo_id = " . $this->account['abo_id'];
				$db->query($sql);
			}

			$sql = "UPDATE " . ABO_LISTE_TABLE . "
				SET confirmed = " . SUBSCRIBE_CONFIRMED . ",
					register_key = '" . generate_key(20) . "'
				WHERE liste_id = " . $this->listdata['liste_id'] . "
					AND abo_id = " . $this->account['abo_id'];
			$db->query($sql);

			$db->commit();

			$this->update_stats();
			$this->alert_admin(true);

			$this->message = $lang['Message']['Confirm_ok'];

			return true;
		}
		else {
			$this->message = $lang['Message']['Invalid_date'];
		}

		return false;
	}

	private function unsubscribe($code = '')
	{
		global $db, $nl_config, $lang;

		if (!empty($code)) {
			$sql = "SELECT COUNT(abo_id) AS num_subscribe
				FROM " . ABO_LISTE_TABLE . "
				WHERE abo_id = " . $this->account['abo_id'];
			$result = $db->query($sql);

			$num_subscribe = $result->column('num_subscribe');

			$db->beginTransaction();

			$sql = "DELETE FROM " . ABO_LISTE_TABLE . "
				WHERE liste_id = " . $this->listdata['liste_id'] . "
					AND abo_id = " . $this->account['abo_id'];
			$db->query($sql);

			if ($num_subscribe == 1) {
				$sql = 'DELETE FROM ' . ABONNES_TABLE . '
					WHERE abo_id = ' . $this->account['abo_id'];
				$db->query($sql);

				$this->message = $lang['Message']['Unsubscribe_3'];
			}
			else {
				$this->message = $lang['Message']['Unsubscribe_2'];
			}

			$db->commit();
			$this->alert_admin(false);

			return true;
		}
		else {
			$this->account['code'] = generate_key(20);

			$sql = "UPDATE " . ABO_LISTE_TABLE . "
				SET register_key = '{$this->account['code']}'
				WHERE abo_id = {$this->account['abo_id']}
					AND liste_id = " . $this->listdata['liste_id'];
			$db->query($sql);

			$email_tpl = ($this->listdata['use_cron']) ? 'unsubscribe_cron' : 'unsubscribe_form';

			$template = new Template(sprintf('%s/%s.txt', $this->tpldir, $email_tpl));
			$template->assign([
				'LISTE'    => $this->listdata['liste_name'],
				'SITENAME' => $nl_config['sitename'],
				'URLSITE'  => $nl_config['urlsite'],
				'SIG'      => $this->listdata['liste_sig'],
				'PSEUDO'   => $this->account['pseudo']
			]);

			if ($this->listdata['use_cron']) {
				$template->assign([
					'EMAIL_NEWSLETTER' => $this->liste_email,
					'CODE'             => $this->account['code']
				]);
			}
			else {
				$template->assign(['LINK' => $this->make_link()]);
			}

			if (count($this->other_tags) > 0) {
				$tags = [];
				foreach ($this->other_tags as $tag) {
					if (isset($this->account['tags'][$tag['column_name']])) {
						$tags[$tag['tag_name']] = $this->account['tags'][$tag['column_name']];
					}
				}

				$template->assign($tags);
			}

			$body = $template->pparse(true);

			$email = new Email();
			$email->setFrom($this->listdata['sender_email'], $this->listdata['liste_name']);
			$email->addRecipient($this->account['email']);
			$email->setSubject($lang['Subject_email']['Unsubscribe_1']);
			$email->setTextBody($body);

			if ($this->listdata['return_email'] != '') {
				$email->setReturnPath($this->listdata['return_email']);
			}

			try {
				wamailer()->send($email);
			}
			catch (\Exception $e) {
				$this->message = sprintf($lang['Message']['Failed_sending'],
					htmlspecialchars($e->getMessage())
				);
				return false;
			}

			$this->message = $lang['Message']['Unsubscribe_1'];

			return true;
		}
	}

	private function setformat()
	{
		global $db, $lang;

		if ($this->listdata['liste_format'] == FORMAT_MULTIPLE) {
			if ($this->account['format'] == FORMAT_TEXT) {
				$this->format = FORMAT_HTML;
			}
			else {
				$this->format = FORMAT_TEXT;
			}

			$sql = "UPDATE " . ABO_LISTE_TABLE . "
				SET format = " . $this->format . "
				WHERE liste_id = " . $this->listdata['liste_id'] . "
					AND abo_id = " . $this->account['abo_id'];
			$db->query($sql);

			$this->message = $lang['Message']['Success_setformat'];

			return true;
		}
		else {
			$this->message = $lang['Message']['Inactive_format'];

			return false;
		}
	}

	private function make_link()
	{
		$formURL = $this->listdata['form_url'];
		if (!empty($GLOBALS['formURL'])) {
			$formURL = $GLOBALS['formURL'];
		}

		return $formURL . (strstr($formURL, '?') ? '&' : '?') . $this->account['code'];
	}

	private function alert_admin($new_subscribe)
	{
		global $db, $nl_config, $lang;

		if ($new_subscribe) {
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
			FROM " . ADMIN_TABLE . " AS a
				LEFT JOIN " . AUTH_ADMIN_TABLE . " AS aa ON aa.admin_id = a.admin_id
					AND aa.liste_id = {$this->listdata['liste_id']}
			WHERE a.$fieldname = " . $fieldvalue;
		if ($result = $db->query($sql)) {
			if ($row = $result->fetch()) {
				$template = new Template(sprintf('%s/%s.txt', $this->tpldir, $email_tpl));
				$template->assign([
					'EMAIL'   => $this->account['email'],
					'LISTE'   => $this->listdata['liste_name'],
					'URLSITE' => $nl_config['urlsite'],
					'SIG'     => $this->listdata['liste_sig'],
					'PSEUDO'  => $this->account['pseudo']
				]);

				$email = new Email();
				$email->setFrom($this->listdata['sender_email'], $this->listdata['liste_name']);
				$email->setSubject($subject);

				if ($this->listdata['return_email'] != '') {
					$email->setReturnPath($this->listdata['return_email']);
				}

				if (count($this->other_tags) > 0) {
					$tags = [];
					foreach ($this->other_tags as $tag) {
						if (isset($this->account['tags'][$tag['column_name']])) {
							$tags[$tag['tag_name']] = $this->account['tags'][$tag['column_name']];
						}
					}

					$template->assign($tags);
				}

				do {
					if (!wan_is_admin($row) && !$row['auth_view']) {
						continue;
					}

					$template->assign(['USER' => $row['admin_login']]);
					$body = $template->pparse(true);

					$email->clearRecipients();
					$email->addRecipient($row['admin_email'], $row['admin_login']);
					$email->setTextBody($body);

					try {
						wamailer()->send($email);
					}
					catch (\Exception $e) { }
				}
				while ($row = $result->fetch());
			}
		}
	}

	private function update_stats()
	{
		require 'includes/functions.stats.php';

		update_stats($this->listdata);
	}
}
