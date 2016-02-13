<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2016 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 *
 * @see RFC 1939 - Post Office Protocol - Version 3
 * @see RFC 2449 - POP3 Extension Mechanism
 * @see RFC 2595 - Using TLS with IMAP, POP3 and ACAP
 *
 * D’autres sources qui m’ont bien aidées :
 *
 * @link http://www.commentcamarche.net/internet/smtp.php3
 */

namespace Wanewsletter;

class PopClient
{
	/**
	 * Identifiant de connexion
	 *
	 * @var resource
	 */
	protected $socket;

	/**
	 * Nom ou IP du serveur pop à contacter, ainsi que le port.
	 *
	 * @var string
	 */
	protected $server   = 'localhost:110';

	/**
	 * Durée maximale d’une tentative de connexion
	 *
	 * @var integer
	 */
	public $timeout     = 30;

	/**
	 * Débogage.
	 * true pour afficher sur la sortie standard ou bien toute valeur utilisable
	 * avec call_user_func()
	 *
	 * @var boolean|callable
	 */
	public $debug       = false;

	/**
	 * Options diverses.
	 * Les propriétés 'timeout' et 'debug' peuvent être configurées également
	 * au travers de la méthode PopClient::options()
	 *
	 * @var array
	 */
	protected $opts     = [
		/**
		 * Utilisation de la commande STLS pour sécuriser la connexion.
		 * Ignoré si la connexion est sécurisée en utilisant un des préfixes de
		 * transport ssl ou tls supportés par PHP.
		 *
		 * @var boolean
		 */
		'starttls' => false,

		/**
		 * Utilisés pour la création du contexte de flux avec stream_context_create()
		 *
		 * @link http://php.net/stream_context_create
		 *
		 * @var array
		 */
		'stream_opts'   => [
			'ssl' => [
				'disable_compression' => true, // default value in PHP ≥ 5.6
			]
		],
		'stream_params' => null
	];

	/**
	 * Liste des extensions POP supportées.
	 *
	 * @see self::getExtensions() self::connect()
	 *
	 * @var array
	 */
	protected $extensions = [];

	/**
	 * Dernier message de réponse retourné par le serveur.
	 * Accessible en lecture.
	 *
	 * @var string
	 */
	protected $responseData;

	/**
	 * Tableau compilant quelques informations sur la connexion en cours (accès en lecture).
	 *
	 * @var array
	 */
	protected $serverInfos = [
		'host'      => '',
		'port'      => 0,
		// true si la connexion est chiffrée avec SSL/TLS
		'encrypted' => false,
		// true si le certificat a été vérifié
		'trusted'   => false,
		// Ressource de contexte de flux manipulable avec les fonctions stream_context_*
		'context'   => null
	];

	/**
	 * @param array $opts
	 */
	public function __construct(array $opts = [])
	{
		if (!strpos($this->server, '://')) {
			$this->server = 'tcp://'.$this->server;
		}

		$this->options($opts);
	}

	/**
	 * Définition des options d’utilisation.
	 * Les options 'debug' et 'timeout' renvoient aux propriétés de classe
	 * de même nom.
	 *
	 * @param array $opts
	 *
	 * @return array
	 */
	public function options(array $opts = [])
	{
		// Configuration alternative
		foreach (['debug','timeout'] as $name) {
			if (!empty($opts[$name])) {
				$this->{$name} = $opts[$name];
				unset($opts[$name]);
			}
		}

		$this->opts = array_replace_recursive($this->opts, $opts);

		return $this->opts;
	}

	/**
	 * Etablit la connexion au serveur POP et effectue l'identification
	 *
	 * @param string  $server   Nom ou IP du serveur (hostname, proto://hostname, proto://hostname:port)
	 * @param string  $username Nom d'utilisateur du compte
	 * @param string  $password Mot de passe du compte
	 *
	 * @throws Exception
	 * @return boolean
	 */
	public function connect($server = null, $username = null, $password = null)
	{
		// Reset des données relatives à l’éventuelle connexion précédente
		$this->responseData = '';

		if (!$server) {
			$server = $this->server;
		}

		if (!strpos($server, '://')) {
			$server = 'tcp://'.$server;
		}

		$url = parse_url($server);
		if (!$url) {
			throw new Exception("Invalid server argument given.");
		}

		$proto = substr($url['scheme'], 0, 3);
		$useSSL   = ($proto == 'ssl' || $proto == 'tls');
		$startTLS = (!$useSSL && $this->opts['starttls']);

		// Attribution du port par défaut si besoin
		if (empty($url['port'])) {
			$url['port'] = 110;
			if ($useSSL) {
				$url['port'] = 995;// POP3S
			}

			$server .= ':'.$url['port'];
		}

		// check de l’extension openssl si besoin
		if (($useSSL || $startTLS) && !in_array('tls', stream_get_transports())) {
			throw new Exception("Cannot use SSL/TLS because the openssl extension is not available!");
		}

		//
		// Ouverture de la connexion au serveur POP
		//
		$context = stream_context_create(
			$this->opts['stream_opts'],
			$this->opts['stream_params']
		);

		$this->socket = stream_socket_client(
			$server,
			$errno,
			$errstr,
			$this->timeout,
			STREAM_CLIENT_CONNECT,
			$context
		);

		if (!$this->socket) {
			if ($errno == 0) {
				$errstr = 'Unknown error. Check PHP errors log to get more information.';
			}
			throw new Exception("Failed to connect to POP server ($errstr)");
		}

		stream_set_timeout($this->socket, $this->timeout);

		if (!$this->checkResponse()) {
			return false;
		}

		// Support pour la commande APOP ?
		$apop_timestamp = '';
		if (preg_match('#<[^>]+>#', $this->responseData, $m)) {
			$apop_timestamp = $m[0];
		}

		// Récupération des extensions supportées par le serveur
		$this->put('CAPA');

		if ($this->checkResponse(true)) {
			// On récupère la liste des extensions supportées par ce serveur
			$this->extensions = [];
			$lines = explode("\r\n", trim($this->responseData));
			array_shift($lines);// On zappe la réponse serveur +OK...

			foreach ($lines as $line) {
				// La RFC 2449 ne précise pas la casse des noms d'extension,
				// on normalise en haut de casse
				$name  = strtoupper(strtok($line, ' '));
				$space = strpos($line, ' ');
				$this->extensions[$name] = ($space !== false)
					? strtoupper(substr($line, $space+1)) : true;
			}
		}

		//
		// Le cas échéant, on utilise le protocole sécurisé TLS
		//
		if ($startTLS) {
			$this->startTLS();
		}

		// On compile les informations sur la connexion
		$infos = [];
		$infos['host']      = $url['host'];
		$infos['port']      = $url['port'];
		$infos['encrypted'] = ($useSSL || $startTLS);
		$infos['trusted']   = ($infos['encrypted'] && PHP_VERSION_ID >= 50600);
		$infos['context']   = $context;

		if (isset($this->opts['stream_opts']['ssl']['verify_peer'])) {
			$infos['trusted'] = $this->opts['stream_opts']['ssl']['verify_peer'];
		}

		$this->serverInfos = $infos;

		//
		// Identification
		//
		if ($apop_timestamp) {
			$this->put(sprintf('APOP %s %s', $username, md5($apop_timestamp.$password)));
			if (!$this->checkResponse()) {
				return false;
			}
		}
		else {
			$this->put(sprintf('USER %s', $username));
			if (!$this->checkResponse()) {
				return false;
			}

			$this->put(sprintf('PASS %s', $password));
			if (!$this->checkResponse()) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Utilisation de la commande STLS pour sécuriser la connexion.
	 *
	 * @throws Exception
	 */
	public function startTLS()
	{
		if (!$this->hasSupport('STLS')) {
			throw new Exception("POP server doesn't support STLS command");
		}

		$this->put('STLS');
		if (!$this->checkResponse()) {
			throw new Exception(sprintf(
				"STLS command returned an error (%s)",
				$this->responseData
			));
		}

		$ssl_options   = $this->opts['stream_opts']['ssl'];
		$crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;

		if (isset($ssl_options['crypto_method'])) {
			$crypto_method = $ssl_options['crypto_method'];
		}

		if (!stream_socket_enable_crypto($this->socket, true, $crypto_method)) {
			fclose($this->socket);
			throw new Exception("Cannot enable TLS encryption");
		}
	}

	/**
	 * Vérifie l'état de la connexion
	 *
	 * @return boolean
	 */
	public function isConnected()
	{
		return is_resource($this->socket);
	}

	/**
	 * Envoie les données au serveur
	 *
	 * @param string $data
	 *
	 * @throws Exception
	 */
	public function put($data)
	{
		if (!$this->isConnected()) {
			throw new Exception("Connection was closed!");
		}

		$data .= "\r\n";
		$this->log(sprintf('C: %s', $data));

		while ($data) {
			$bw = fwrite($this->socket, $data);

			if (!$bw) {
				$md = stream_get_meta_data($this->socket);

				if ($md['timed_out']) {
					throw new Exception("Connection timed out!");
				}

				break;
			}

			$data = substr($data, $bw);
		}
	}

	/**
	 * Récupère la réponse du serveur
	 *
	 * @param boolean $multiline Précise si on attend une réponse multi-lignes
	 *
	 * @throws Exception
	 * @return boolean
	 */
	public function checkResponse($multiline = false)
	{
		if (!$this->isConnected()) {
			throw new Exception("Connection was closed!");
		}

		$this->responseData = '';
		$isok = false;

		do {
			$data = fgets($this->socket);

			if (!$data) {
				$md = stream_get_meta_data($this->socket);

				if ($md['timed_out']) {
					throw new Exception("Connection timed out!");
				}

				break;
			}

			$this->log(sprintf('S: %s', $data));
			$this->responseData .= $data;

			if (!$isok && substr($data, 0, 3) !== '+OK') {
				return false;
			}

			$isok = true;
		}
		while (!feof($this->socket) && ($multiline && rtrim($data) != '.'));

		return true;
	}

	/**
	 * Retourne la liste des extensions supportées par le serveur POP.
	 * Les noms des extensions, ainsi que les éventuels paramètres, sont
	 * normalisés en haut de casse. Exemple :
	 * [
	 *     'STLS' => true,
	 *     'TOP'  => true,
	 *     'LOGIN-DELAY' => 900
	 * ]
	 *
	 * @return array
	 */
	public function getExtensions()
	{
		return $this->extensions;
	}

	/**
	 * Indique si l’extension ciblée est supportée par le serveur POP.
	 * Si l’extension possède des paramètres (par exemple, SASL donne aussi la
	 * liste des méthodes supportées), ceux-ci sont retournés au lieu de true
	 *
	 * @param string $name Nom de l’extension (insensible à la casse)
	 *
	 * @return mixed
	 */
	public function hasSupport($name)
	{
		$name = strtoupper($name);

		if (isset($this->extensions[$name])) {
			return $this->extensions[$name];
		}

		return false;
	}

	/**
	 * Envoie la commande STAT.
	 * Retourne le nombre de messages présents, et le poids total en octets
	 *
	 * @return array
	 */
	public function stat()
	{
		$this->put('STAT');

		if (!$this->checkResponse()) {
			return false;
		}

		sscanf($this->responseData, '+OK %d %d', $total_msg, $total_size);

		return ['total_msg' => $total_msg, 'total_size' => $total_size];
	}

	/**
	 * Envoie la commande LIST.
	 * Retourne un tableau avec l’ID des messages en index et leur poids
	 * comme valeur.
	 * Si un ID de message est donné, sa taille sera renvoyée
	 *
	 * @param integer $num ID du message
	 *
	 * @return mixed
	 */
	public function list($num = null)
	{
		$cmd = 'LIST';
		if ($num) {
			$cmd = sprintf('%s %d', $cmd, $num);
		}

		$this->put($cmd);

		if (!$this->checkResponse(!$num)) {
			return false;
		}

		if (!$num) {
			$list  = [];
			$lines = explode("\r\n", trim($this->responseData));
			array_shift($lines);// On zappe la réponse serveur +OK...

			foreach ($lines as $line) {
				if ($line != '.') {// fin d'une réponse multi-ligne
					sscanf($line, '%d %d', $mail_id, $mail_size);
					$list[$mail_id] = $mail_size;
				}
			}

			return $list;
		}
		else {
			sscanf($this->responseData, '+OK %d %d', $num, $mail_size);

			return $mail_size;
		}
	}

	/**
	 * Envoie la commande RETR/TOP.
	 * Retourne un tableau contenant les en-têtes et le corps de l’email.
	 *
	 * @param integer $num ID du message
	 * @param integer $top Nombre de lignes à récupérer (par défaut, tout le message)
	 *
	 * @return array
	 */
	public function read($num, $top = null)
	{
		if (!$top) {
			$cmd = sprintf('RETR %d', $num);
		}
		else {
			$cmd = sprintf('TOP %d %d', $num, $top);
		}

		$this->put($cmd);
		if (!$this->checkResponse(true)) {
			return false;
		}

		$lines = explode("\r\n", $this->responseData);
		array_shift($lines);// On zappe la réponse serveur +OK...

		$sep = array_search('', $lines);
		$headers = implode("\r\n", array_slice($lines, 0, $sep));
		$message = implode("\r\n", array_slice($lines, $sep + 1));

		return ['headers' => $headers, 'message' => $message];
	}

	/**
	 * Envoie la commande DELE.
	 * Demande au serveur d’effacer le message correspondant au numéro donné
	 *
	 * @param integer $num Numéro du message
	 *
	 * @return boolean
	 */
	public function delete($num)
	{
		$this->put(sprintf('DELE %d', $num));

		return $this->checkResponse();
	}

	/**
	 * Envoie la commande NOOP
	 *
	 * @return boolean
	 */
	public function noop()
	{
		$this->put('NOOP');

		return $this->checkResponse();
	}

	/**
	 * Envoie la commande RSET.
	 * Annule les dernières commandes (effacement ..)
	 *
	 * @return boolean
	 */
	public function reset()
	{
		$this->put('RSET');

		return $this->checkResponse();
	}

	/**
	 * Envoie la commande QUIT et ferme la connexion au serveur
	 */
	public function quit()
	{
		if (is_resource($this->socket)) {
			$this->put('QUIT');
			fclose($this->socket);

			$this->socket = null;
		}

		$infos = [];
		$infos['host']      = '';
		$infos['port']      = 0;
		$infos['encrypted'] = false;
		$infos['trusted']   = false;
		$infos['context']   = null;

		$this->serverInfos = $infos;
	}

	/**
	 * Débogage
	 *
	 * @param string $str
	 */
	protected function log($str)
	{
		if ($this->debug) {
			if (is_callable($this->debug)) {
				call_user_func($this->debug, $str);
			}
			else {
				echo $str;
				flush();
			}
		}
	}

	/**
	 * Lecture des propriétés non publiques autorisées.
	 *
	 * @param string $name Nom de la propriété
	 *
	 * @throws Exception
	 * @return mixed
	 */
	public function __get($name)
	{
		switch ($name) {
			case 'serverInfos':
			case 'responseData':
				return $this->{$name};
				break;
			default:
				throw new Exception("Error while trying to get property '$name'");
				break;
		}
	}

	/**
	 * Destructeur de classe.
	 * On s’assure de fermer proprement la connexion s’il y a lieu.
	 */
	public function __destruct()
	{
		$this->quit();
	}
}
