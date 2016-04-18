<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

namespace Wanewsletter;

use Wamailer\Email;

class Sender
{
	/**
	 * Initialisé dans le constructeur.
	 * @var array
	 */
	private $hooks;

	/**
	 * Module de transport pour l’envoi des mails.
	 * @var \Wamailer\Transport\Transport
	 */
	private $transport;

	/**
	 * Représentation objet de l’email à envoyer.
	 * @var \Wamailer\Email
	 */
	private $email;

	/**
	 * Utilisé pour le formatage texte du message de l’email.
	 * @var Template
	 */
	private $textTemplate;

	/**
	 * Utilisé pour le formatage HTML du message de l’email.
	 * @var Template
	 */
	private $htmlTemplate;

	/**
	 * Lien de désinscription placé dans l’en-tête 'List-Unsubscribe'
	 * @see RFC 2369#3.2
	 * @var string
	 */
	private $unsubscribe_link;

	/**
	 * Données de la liste concernée par l’envoi.
	 * @var array
	 */
	private $listdata;

	/**
	 * Données de la newsletter à envoyer.
	 * @var array
	 */
	private $logdata;

	/**
	 * Pointeur vers le fichier de verrouillage.
	 * @var resource
	 */
	private $fp;

	/**
	 * @param array $listdata
	 * @param array $logdata
	 */
	public function __construct(array $listdata, array $logdata = [])
	{
		$this->listdata  = $listdata;
		$this->logdata   = $logdata;
		// Initialisation du module d’envoi d’emails.
		// Option "keepalive" en cas d’utilisation du transport SMTP
		$this->transport = wamailer(['keepalive' => true]);

		$this->hooks = array_fill_keys(['start-send','pre-send','post-send','end-send'], []);
	}

	/**
	 * @param string   $name
	 * @param callable $hook
	 *
	 * @throws Exception
	 */
	public function registerHook($name, $hook)
	{
		if (!is_callable($hook)) {
			throw new Exception(sprintf("Hook must be a callable. %s received.", gettype($hook)));
		}

		$this->hooks[$name][] = $hook;
	}

	/**
	 * @param string $name
	 * @param array  $params
	 */
	private function triggerHooks($name, array &$params)
	{
		foreach ($this->hooks[$name] as $hook) {
			call_user_func($hook, $params);
		}
	}

	/**
	 * Création du verrou et des différents hooks.
	 *
	 * @throws Exception
	 */
	public function lock()
	{
		global $nl_config, $output;

		//
		// On pose un verrou avec un fichier lock pour empêcher plusieurs
		// envois simultanés sur une liste de diffusion.
		//
		$lockfile = sprintf('%s/liste-%d.lock', $nl_config['tmp_dir'], $this->listdata['liste_id']);

		if (file_exists($lockfile) && (!is_readable($lockfile) || !is_writable($lockfile))) {
			throw new Exception("Lock file has wrong permissions. Must be readable and writable");
		}

		$this->fp = fopen($lockfile, 'c+');

		if (!flock($this->fp, LOCK_EX|LOCK_NB)) {
			fclose($this->fp);
			$output->error('List_is_busy');
		}

		chmod($lockfile, 0600);

		$update_abo_list = function ($params) {
			global $db;

			if (count($params['ids']) > 0) {
				$sql = "UPDATE %s SET send = 1 WHERE abo_id IN(%s) AND liste_id = %d";
				$sql = sprintf($sql, ABO_LISTE_TABLE,
					implode(', ', $params['ids']),
					$this->listdata['liste_id']
				);
				$db->query($sql);
			}

			ftruncate($this->fp, 0);
			fseek($this->fp, 0);
		};

		if (($filesize = filesize($lockfile)) > 0) {
			//
			// L’envoi a été interrompu au cours d'un "flôt" précédent.
			// On récupère les identifiants d’abonnés stockés dans le
			// fichier lock et on met à jour la table.
			//
			$abo_ids = fread($this->fp, $filesize);
			$abo_ids = array_unique(array_map('intval', explode("\n", $abo_ids)));
			$hook_params['ids'] = $abo_ids;

			$update_abo_list($hook_params);
		}

		$this->registerHook('post-send', function ($params) {
			if ($params['ids']) {
				fwrite($this->fp, implode("\n", $params['ids']) . "\n");
			}
		});

		$this->registerHook('end-send', $update_abo_list);
	}

	/**
	 * S’assure que le verrou est bien supprimé à la fin de
	 * l’exécution du script.
	 */
	public function __destruct()
	{
		if (is_resource($this->fp)) {
			$lockfile = stream_get_meta_data($this->fp)['uri'];
			flock($this->fp, LOCK_UN);
			fclose($this->fp);
			unlink($lockfile);
		}
	}

	/**
	 * @param array $supp_address
	 *
	 * @return array Retourne un tableau tel que ['total_sent' => int, 'total_to_send' => int]
	 */
	public function process(array $supp_address = [])
	{
		global $nl_config, $db, $output, $lang;

		if (!$this->logdata) {
			// on récupère la dernière newsletter en cours d’envoi
			$sql = "SELECT log_id, log_subject, log_body_text, log_body_html, log_status, liste_id
				FROM %s
				WHERE liste_id = %d
					AND log_status = %d
				LIMIT 1 OFFSET 0";
			$sql = sprintf($sql, LOG_TABLE, $this->listdata['liste_id'], STATUS_SENDING);
			$result = $db->query($sql);

			if (!($this->logdata = $result->fetch($result::FETCH_ASSOC))) {
				$output->message('No_log_to_send');
			}

			$this->logdata['joined_files'] = get_joined_files($this->logdata);
		}

		$abodata_list  = [];
		$total_to_send = $total_sent = 0;

		//
		// Récupération des destinataires
		//
		if ($this->logdata['log_status'] == STATUS_SENDING) {
			$sql = "SELECT COUNT(a.abo_id) AS total, al.send
				FROM %s AS a
					INNER JOIN %s AS al ON al.abo_id = a.abo_id
						AND al.liste_id  = %d
						AND al.confirmed = %d
				WHERE a.abo_status = %d
				GROUP BY al.send";
			$sql = sprintf($sql, ABONNES_TABLE, ABO_LISTE_TABLE,
				$this->listdata['liste_id'],
				SUBSCRIBE_CONFIRMED,
				ABO_ACTIVE
			);
			$result = $db->query($sql);

			while ($row = $result->fetch()) {
				if ($row['send'] == 1) {
					$total_sent = $row['total'];
				}
				else {
					$total_to_send = $row['total'];
				}
			}

			$tags_list   = wan_get_tags();
			$tags_fields = '';
			foreach ($tags_list as $tag) {
				$tags_fields .= ', a.' . $tag['column_name'];
			}

			$sql = "SELECT a.abo_id, a.abo_pseudo, a.abo_email, al.register_key, al.format %s
				FROM %s AS a
					INNER JOIN %s AS al ON al.abo_id = a.abo_id
						AND al.liste_id  = %d
						AND al.confirmed = %d
						AND al.send      = 0
				WHERE a.abo_status = %d";
			if ($nl_config['sending_limit'] > 0) {
				$sql .= " LIMIT $nl_config[sending_limit] OFFSET 0";
			}

			$sql = sprintf($sql, $tags_fields, ABONNES_TABLE, ABO_LISTE_TABLE,
				$this->listdata['liste_id'],
				SUBSCRIBE_CONFIRMED,
				ABO_ACTIVE
			);
			$result = $db->query($sql);

			while ($row = $result->fetch($result::FETCH_ASSOC)) {
				if ($this->listdata['liste_format'] != FORMAT_MULTIPLE) {
					$row['format'] = $this->listdata['liste_format'];
				}

				$abodata_list[] = $row;
			}

			$result->free();
		}

		foreach ($supp_address as $address) {
			$data = [
				'abo_id'       => false,
				'abo_pseudo'   => '',
				'abo_email'    => $address,
				'register_key' => ''
			];

			if ($this->listdata['liste_format'] != FORMAT_HTML) {
				$data['format'] = FORMAT_TEXT;
				$abodata_list[] = $data;
			}

			if ($this->listdata['liste_format'] != FORMAT_TEXT) {
				$data['format'] = FORMAT_HTML;
				$abodata_list[] = $data;
			}
		}

		if (count($abodata_list) == 0) {
			$output->message('No_subscribers');
		}

		//
		// Initialisation de l’objet Email
		//
		$this->createEmail();

		//
		// Paramètres transmis à chaque 'hook' sous forme de tableau.
		// (voir méthode lock() pour des exemples)
		//
		$hook_params['list']  = $abodata_list;
		$hook_params['ids']   = [];
		$hook_params['email'] = $this->email;

		// Actions avant la boucle d’envoi
		$this->triggerHooks('start-send', $hook_params);

		if ($nl_config['engine_send'] == ENGINE_BCC) {
			$address = [FORMAT_TEXT => [], FORMAT_HTML => []];
			$abo_ids = $address;

			foreach ($abodata_list as $data) {
				if ($data['abo_id']) {
					$abo_ids[$data['format']][] = $data['abo_id'];
				}

				$address[$data['format']][] = $data['abo_email'];
			}

			$data = [
				'email' => $this->listdata['sender_email'],
				'name'  => $this->listdata['liste_name']
			];

			foreach ([FORMAT_TEXT, FORMAT_HTML] as $format) {
				// Actions pré-envoi
				$this->triggerHooks('pre-send', $hook_params);

				if (count($address[$format]) > 0) {
					try {
						$data['format'] = $format;
						$this->send($data, $address[$format]);
					}
					catch (\Exception $e) {
						trigger_error(sprintf($lang['Message']['Failed_sending'],
							htmlspecialchars($e->getMessage())
						), E_USER_ERROR);
					}
				}

				// Actions post-envoi
				$hook_params['ids'] = $abo_ids[$format];
				$this->triggerHooks('post-send', $hook_params);
			}

			$abo_ids = array_merge($abo_ids[FORMAT_TEXT], $abo_ids[FORMAT_HTML]);
		}
		else {
			$abo_ids = [];

			while ($data = array_pop($abodata_list)) {
				// Actions pré-envoi
				$this->triggerHooks('pre-send', $hook_params);

				try {
					$data['email'] = $data['abo_email'];
					$data['name']  = $data['abo_pseudo'];
					$this->send($data);
				}
				catch (\Exception $e) {
					trigger_error(sprintf($lang['Message']['Failed_sending'],
						htmlspecialchars($e->getMessage())
					), E_USER_ERROR);
				}

				$hook_params['ids'] = [];
				if ($data['abo_id']) {
					$abo_ids[] = $data['abo_id'];
					$hook_params['ids'] = [$data['abo_id']];
				}

				// Actions post-envoi
				$this->triggerHooks('post-send', $hook_params);
			}
		}

		// On termine proprement la phase d’envoi
		$this->transport->close();

		// La base de données peut avoir refermé la connexion après un certain
		// délai sans activité.
		if (!$db->ping()) {
			/**
			 * mysqli_ping() ne fonctionne pas avec mysqlnd
			 *
			 * @link https://bugs.php.net/bug.php?id=52561
			 */
			$db->connect();
		}

		if (($sent = count($abo_ids)) > 0) {
			$total_to_send -= $sent;
			$total_sent    += $sent;
		}

		// Actions après la boucle d’envoi
		$hook_params['ids'] = $abo_ids;
		$this->triggerHooks('end-send', $hook_params);

		if ($this->logdata['log_status'] == STATUS_SENDING && $total_to_send == 0) {
			$db->beginTransaction();

			$sql = "UPDATE %s SET log_status = %d, log_numdest = %d WHERE log_id = %d";
			$sql = sprintf($sql, LOG_TABLE, STATUS_SENT, $total_sent, $this->logdata['log_id']);
			$db->query($sql);

			$sql = "UPDATE %s SET send = 0 WHERE liste_id = %d";
			$sql = sprintf($sql, ABO_LISTE_TABLE, $this->listdata['liste_id']);
			$db->query($sql);

			$sql = "UPDATE %s SET liste_numlogs = liste_numlogs + 1 WHERE liste_id = %d";
			$sql = sprintf($sql, LISTE_TABLE, $this->listdata['liste_id']);
			$db->query($sql);

			$db->commit();
		}

		return ['total_to_send' => $total_to_send, 'total_sent' => $total_sent];
	}

	/**
	 * Envoi de l’email proprement dit.
	 *
	 * @param array $data           Données sur le destinataire.
	 * @param array $bcc_recipients Destinataires cachés, dans le cas du modèle
	 *                              d’envoi en copie cachée, avec la liste en
	 *                              destinataire principal.
	 */
	public function send(array $data, array $bcc_recipients = [])
	{
		//
		// Initialisation de l’objet Email
		//
		if (!$this->email) {
			$this->createEmail();
		}

		$this->email->removeTextBody();
		$this->email->removeHTMLBody();
		$this->email->clearRecipients();
		$this->email->addRecipient($data['email'], $data['name']);

		$unsubscribe_link = $this->unsubscribe_link;

		// Envoi en copie cachée
		if ($bcc_recipients) {
			foreach ($bcc_recipients as $address) {
				$this->email->addBCCRecipient($address);
			}
		}
		// Envoi personnalisé. On effectue le remplacement des tags
		else {
			$tags_list   = wan_get_tags();
			$tags_list[] = ['tag_name' => 'NAME', 'column_name' => 'abo_pseudo'];

			$tags_to_replace = [];
			foreach ($tags_list as $tag) {
				if (isset($data[$tag['column_name']])) {
					if (!is_numeric($data[$tag['column_name']]) && $data['format'] == FORMAT_HTML) {
						$data[$tag['column_name']] = htmlspecialchars($data[$tag['column_name']]);
					}

					$tags_to_replace[$tag['tag_name']] = $data[$tag['column_name']];

					continue;
				}

				$tags_to_replace[$tag['tag_name']] = '';
			}

			$tags_to_replace['WA_EMAIL'] = $data['email'];
			$tags_to_replace['WA_CODE']  = $data['register_key'];

			$this->textTemplate->assign($tags_to_replace);
			$this->htmlTemplate->assign($tags_to_replace);

			if (!$this->listdata['use_cron']) {
				$unsubscribe_link = str_replace('{WA_CODE}',
					$data['register_key'],
					$unsubscribe_link
				);
			}
		}

		// See RFC 2369#3.2
		$this->email->headers->set('List-Unsubscribe', sprintf('<%s>', $unsubscribe_link));

		if ($this->listdata['liste_format'] != FORMAT_HTML) {
			$this->email->setTextBody($this->textTemplate->pparse(true));
		}

		if ($this->listdata['liste_format'] != FORMAT_TEXT && $data['format'] == FORMAT_HTML) {
			$this->email->setHTMLBody($this->htmlTemplate->pparse(true));
		}

		$this->transport->send($this->email);
	}

	/**
	 * Initialise les objets $this->email, $this->textTemplate et $this->htmlTemplate
	 */
	private function createEmail()
	{
		global $nl_config, $lang;

		$email = new Email;
		$email->setFrom($this->listdata['sender_email'], $this->listdata['liste_name']);
		$email->setSubject($this->logdata['log_subject']);

		if ($this->listdata['return_email']) {
			$email->setReturnPath($this->listdata['return_email']);
		}

		// See RFC 2919
		$email->headers->set('List-ID', sprintf('%s <list-%d.%s>',
			\Wamailer\Mime\Header::encode('List-ID', $this->listdata['liste_name'], 'UTF-8', 'phrase'),
			$this->listdata['liste_id'],
			parse_url($nl_config['urlsite'], PHP_URL_HOST)
		));

		$message = [
			FORMAT_TEXT => $this->logdata['log_body_text'],
			FORMAT_HTML  => $this->logdata['log_body_html']
		];

		//
		// Ajout du lien de désinscription, selon les méthodes d'envoi/format utilisés
		//
		$link_template = sprintf('<a href="%%s">%s</a>', str_replace('%', '%%', $lang['Label_link']));

		if ($this->listdata['use_cron']) {
			$liste_email = ($this->listdata['liste_alias'])
				? $this->listdata['liste_alias'] : $this->listdata['sender_email'];

			$link = [
				FORMAT_TEXT => $liste_email,
				FORMAT_HTML => sprintf($link_template,
					sprintf('mailto:%s?subject=unsubscribe', $liste_email)
				)
			];
		}
		else {
			if ($nl_config['engine_send'] == ENGINE_BCC) {
				$link = [
					FORMAT_TEXT => $this->listdata['form_url'],
					FORMAT_HTML => sprintf($link_template, htmlspecialchars($this->listdata['form_url']))
				];
			}
			else {
				$tmp_link = $this->listdata['form_url']
					. (strstr($this->listdata['form_url'], '?') ? '&' : '?')
					. '{WA_CODE}';

				$link = [
					FORMAT_TEXT => $tmp_link,
					FORMAT_HTML => sprintf($link_template, htmlspecialchars($tmp_link))
				];
			}
		}

		$this->unsubscribe_link = $link[FORMAT_TEXT];

		$message[FORMAT_TEXT] = str_replace('{LINKS}', $link[FORMAT_TEXT], $message[FORMAT_TEXT]);
		$message[FORMAT_HTML] = str_replace('{LINKS}', $link[FORMAT_HTML],  $message[FORMAT_HTML]);

		// Si le document HTML ne comporte pas de titre, on insère le sujet de l’email.
		$message[FORMAT_HTML] = preg_replace('#(<head>.*?<title>)\s*(</title>.*?</head>)#si',
			sprintf('$1%s$2', htmlspecialchars($this->logdata['log_subject'], ENT_NOQUOTES)),
			$message[FORMAT_HTML],
			1
		);

		$text_template = new Template;
		$text_template->loadFromString($message[FORMAT_TEXT]);
		$html_template = new Template;
		$html_template->loadFromString($message[FORMAT_HTML]);

		//
		// On s’occupe maintenant des fichiers joints ou incorporés.
		//
		foreach ($this->logdata['joined_files'] as $file) {
			$real_name     = $file['file_real_name'];
			$physical_name = $file['file_physical_name'];
			$mime_type     = $file['file_mimetype'];

			$file = WA_ROOTDIR . '/' . $nl_config['upload_path'] . $physical_name;
			if (!is_readable($file)) {
				continue;
			}

			$email->attach($file, $real_name, $mime_type);
		}

		$this->email    = $email;
		$this->textTemplate = $text_template;
		$this->htmlTemplate = $html_template;
	}
}
