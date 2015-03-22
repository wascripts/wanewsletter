<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 *
 * @see RFC 1939 - Post Office Protocol - Version 3
 * @see RFC 2449 - POP3 Extension Mechanism
 *
 * Les sources qui m'ont bien aidées :
 *
 * @link http://www.commentcamarche.net/internet/smtp.php3
 * @link http://abcdrfc.free.fr/ (français)
 * @link http://www.faqs.org/rfcs/ (anglais)
 */

class Pop {

	/**
	 * Identifiant de connexion
	 *
	 * @var resource
	 */
	protected $socket;

	/**
	 * Nom ou IP du serveur pop à contacter
	 *
	 * @var string
	 */
	protected $host     = '';

	/**
	 * Port d'accés (en général, 110)
	 *
	 * @var integer
	 */
	protected $port     = 110;

	/**
	 * Nom d'utilisateur du compte
	 *
	 * @var string
	 */
	protected $username = '';

	/**
	 * Mot de passe d'accés au compte
	 *
	 * @var string
	 */
	protected $passwd   = '';

	/**
	 * Tableau contenant les données des emails lus
	 *
	 * @var array
	 */
	public $contents    = array();

	/**
	 * Durée maximale d'une tentative de connexion
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
	 * Voir méthode Pop::options()
	 *
	 * @var array
	 */
	protected $opts     = array(
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
		'stream_context_opts'   => array(
			'ssl' => array(
				'disable_compression' => true, // default value in PHP ≥ 5.6
			)
		),
		'stream_context_params' => null
	);

	/**
	 * Liste des extensions POP supportées.
	 *
	 * @see self::getExtensions() self::connect()
	 *
	 * @var array
	 */
	protected $extensions = array();

	/**
	 * Dernier message de réponse retourné par le serveur.
	 * Accessible en lecture sous la forme $obj->responseData
	 *
	 * @var string
	 */
	protected $_responseData;

	/**
	 * Si l'argument vaut true, la connexion est établie automatiquement avec les paramètres par défaut
	 * de la classe. (On suppose qu'ils ont été préalablement remplacés par les bons paramètres)
	 *
	 * @param boolean $auto_connect true pour établir la connexion à l'instanciation de la classe
	 */
	public function __construct($auto_connect = false)
	{
		if ($auto_connect) {
			$this->connect($this->host, $this->port, $this->username, $this->passwd);
		}
	}

	/**
	 * Définition des options d'utilisation
	 *
	 * @param array $opts
	 */
	public function options($opts)
	{
		if (is_array($opts)) {
			// Configuration alternative
			foreach (array('debug','timeout') as $name) {
				if (!empty($opts[$name])) {
					$this->{$name} = $opts[$name];
					unset($opts[$name]);
				}
			}

			$this->opts = array_replace_recursive($this->opts, $opts);
		}
	}

	/**
	 * Etablit la connexion au serveur POP et effectue l'identification
	 *
	 * @param string  $host     Nom ou IP du serveur
	 * @param integer $port     Port d'accés au serveur POP
	 * @param string  $username Nom d'utilisateur du compte
	 * @param string  $passwd   Mot de passe du compte
	 *
	 * @return boolean
	 */
	public function connect($host = null, $port = null, $username = null, $passwd = null)
	{
		foreach (array('host', 'port', 'username', 'passwd') as $varname) {
			if (empty($$varname)) {
				$$varname = $this->{$varname};
			}
		}

		$this->_responseData = '';
		$this->contents = array();

		$useSSL   = preg_match('#^(ssl|tls)(v[.0-9]+)?://#', $host);
		$startTLS = (!$useSSL && $this->opts['starttls']);

		// check de l'extension openssl si besoin
		if (($useSSL || $startTLS) && !extension_loaded('openssl')) {
			throw new Exception("Cannot use SSL/TLS because the openssl extension isn't loaded!");
		}

		//
		// Ouverture de la connexion au serveur POP
		//
		$context_opts   = $this->opts['stream_context_opts'];
		$context_params = $this->opts['stream_context_params'];
		$context = stream_context_create($context_opts, $context_params);

		$this->socket = stream_socket_client(
			sprintf('%s:%d', $host, $port),
			$errno,
			$errstr,
			$this->timeout,
			STREAM_CLIENT_CONNECT,
			$context
		);

		if (!$this->socket) {
			throw new Exception("Failed to connect to POP server ($errno - $errstr)");
		}

		stream_set_timeout($this->socket, $this->timeout);

		if (!$this->checkResponse()) {
			return false;
		}

		// Support pour la commande APOP ?
		$apop_timestamp = '';
		if (preg_match('#<[^>]+>#', $this->_responseData, $m)) {
			$apop_timestamp = $m[0];
		}

		// Récupération des extensions supportées par le serveur
		$this->put('CAPA');

		if ($this->checkResponse(true)) {
			// On récupère la liste des extensions supportées par ce serveur
			$this->extensions = array();
			$lines = explode("\r\n", trim($this->_responseData));
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
			if (!$this->hasSupport('STLS')) {
				throw new Exception("POP server doesn't support STLS command");
			}

			$this->put('STLS');
			if (!$this->checkResponse()) {
				return false;
			}

			$crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
			if (isset($context_opts['ssl']['crypto_method'])) {
				$crypto_method = $context_opts['ssl']['crypto_method'];
			}

			if (!stream_socket_enable_crypto($this->socket, true, $crypto_method)) {
				fclose($this->socket);
				throw new Exception("Cannot enable TLS encryption");
			}
		}

		//
		// Identification
		//
		if ($apop_timestamp) {
			$this->put(sprintf('APOP %s %s', $username, md5($apop_timestamp.$passwd)));
			if (!$this->checkResponse()) {
				return false;
			}
		}
		else {
			$this->put(sprintf('USER %s', $username));
			if (!$this->checkResponse()) {
				return false;
			}

			$this->put(sprintf('PASS %s', $passwd));
			if (!$this->checkResponse()) {
				return false;
			}
		}

		return true;
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
	 * Envoit les données au serveur
	 *
	 * @param string $data Données à envoyer
	 */
	public function put($data)
	{
		if (!$this->isConnected()) {
			throw new Exception("Connection was closed!");
		}

		$data .= "\r\n";
		$total = strlen($data);
		$this->log($data);

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
	 * @return boolean
	 */
	public function checkResponse($multiline = false)
	{
		if (!$this->isConnected()) {
			throw new Exception("Connection was closed!");
		}

		$this->_responseData = '';
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

			$this->log($data);
			$this->_responseData .= $data;

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
	 * Indique si l'extension ciblée est supportée par le serveur POP.
	 * Si l'extension possède des paramètres (par exemple, SASL donne aussi la
	 * liste des méthodes supportées), ceux-ci sont retournés au lieu de true
	 *
	 * @param string $name Nom de l'extension (insensible à la casse)
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
	 * Commande STAT
	 * Renvoie le nombre de messages présent et la taille totale (en octets)
	 *
	 * @return array
	 */
	public function stat_box()
	{
		$this->put('STAT');
		if (!$this->checkResponse()) {
			return false;
		}

		sscanf($this->_responseData, '+OK %d %d', $total_msg, $total_size);

		return array('total_msg' => $total_msg, 'total_size' => $total_size);
	}

	/**
	 * Commande LIST
	 * Renvoie un tableau avec leur numéro en index et leur taille pour valeur
	 * Si un numéro de message est donné, sa taille sera renvoyée
	 *
	 * @param integer $num Numéro du message
	 *
	 * @return mixed
	 */
	public function list_mail($num = 0)
	{
		$this->put('LIST' . ($num > 0 ? ' ' . $num : ''));
		if (!$this->checkResponse($num == 0)) {
			return false;
		}

		if ($num == 0) {
			$list  = array();
			$lines = explode("\r\n", trim($this->_responseData));
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
			sscanf($this->_responseData, '+OK %d %d', $num, $mail_size);

			return $mail_size;
		}
	}

	/**
	 * Commande RETR/TOP
	 * Renvoie un tableau avec leur numéro en index et leur taille pour valeur
	 *
	 * @param integer $num      Numéro du message
	 * @param integer $max_line Nombre maximal de ligne à renvoyer (par défaut, tout le message)
	 *
	 * @return boolean
	 */
	public function read_mail($num, $max_line = 0)
	{
		if (!$max_line) {
			$cmd = sprintf('RETR %d', $num);
		}
		else {
			$cmd = sprintf('TOP %d %d', $num, $max_line);
		}

		$this->put($cmd);
		if (!$this->checkResponse(true)) {
			return false;
		}

		$lines = explode("\r\n", $this->_responseData);
		array_shift($lines);// On zappe la réponse serveur +OK...
		$output = implode("\r\n", $lines);

		list($headers, $message) = explode("\r\n\r\n", $output, 2);

		$this->contents[$num]['headers'] = trim(preg_replace("/\r\n( |\t)+/", ' ', $headers));
		$this->contents[$num]['message'] = trim($message);

		return true;
	}

	/**
	 * Récupère les entêtes de l'email spécifié par $num et renvoi un tableau avec le
	 * nom des entêtes et leur valeur
	 *
	 * @param string $str
	 *
	 * @return mixed
	 */
	public function parse_headers($str)
	{
		if (is_numeric($str)) {
			if (!isset($this->contents[$str]['headers'])) {
				if (!$this->read_mail($str)) {
					return false;
				}
			}

			$str = $this->contents[$str]['headers'];
		}

		$headers = array();

		$lines = explode("\r\n", $str);
		for ($i = 0; $i < count($lines); $i++) {
			list($name, $value) = explode(':', $lines[$i], 2);

			$name = strtolower($name);
			$headers[$name] = $this->decode_mime_header($value);
		}

		return $headers;
	}

	/**
	 * @param string $str
	 *
	 * @return array
	 */
	public function infos_header($str)
	{
		$total = preg_match_all("/([^ =]+)=\"?([^\" ]+)/", $str, $matches);

		$infos = array();
		for ($i = 0; $i < $total; $i++) {
			$infos[strtolower($matches[1][$i])] = $matches[2][$i];
		}

		return $infos;
	}

	/**
	 * Décode l'entête donné s'il est encodé
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	protected function decode_mime_header($str)
	{
		//
		// On vérifie si l'entête est encodé en base64 ou en quoted-printable, et on
		// le décode si besoin est.
		//
		$total = preg_match_all('/=\?([^?]+)\?(Q|q|B|b)\?([^?]+)\?\=/', $str, $matches);

		for ($i = 0; $i < $total; $i++) {
			if ($matches[2][$i] == 'Q' || $matches[2][$i] == 'q') {
				$tmp = preg_replace_callback('/=([a-zA-Z0-9]{2})/',
					function ($m) { return chr(hexdec($m[1])); },
					$matches[3][$i]
				);
				$tmp = str_replace('_', ' ', $tmp);
			}
			else {
				$tmp = base64_decode($matches[3][$i]);
			}

			$str = str_replace($matches[0][$i], $tmp, $str);
		}

		return $str;
	}

	/**
	 * Parse l'email demandé et renvoie des informations sur les fichiers joints éventuels
	 * Retourne un tableau contenant les données (nom, encodage, données du fichier ..) sur les fichiers joints
	 * ou false si aucun fichier joint n'est trouvé ou que l'email correspondant à $num n'existe pas.
	 *
	 * @param integer $num Numéro de l'email à parser
	 *
	 * @status experimental
	 * @return mixed
	 */
	public function extract_files($num)
	{
		if (!isset($this->contents[$num])) {
			if (!$this->read_mail($num)) {
				return false;
			}
		}

		$headers = $this->parse_headers($this->contents[$num]['headers']);
		$message = $this->contents[$num]['message'];

		//
		// On vérifie si le message comporte plusieurs parties
		//
		if (!isset($headers['content-type']) || !stristr($headers['content-type'], 'multipart')) {
			return false;
		}

		$infos = $this->infos_header($headers['content-type']);

		$boundary = $infos['boundary'];
		$parts    = array();
		$files    = array();
		$lines    = explode("\r\n", $message);
		$offset   = 0;

		for ($i = 0; $i < count($lines); $i++) {
			if (strstr($lines[$i], $infos['boundary'])) {
				$offset         = count($parts);
				$parts[$offset] = '';

				if (isset($parts[$offset - 1])) {
					preg_match("/^(.+?)\r\n\r\n(.*?)$/s", trim($parts[$offset - 1]), $match);

					$local_headers = trim(preg_replace("/\r\n( |\t)+/", ' ', $match[1]));
					$local_message = trim($match[2]);

					$local_headers = $this->parse_headers($local_headers);

					$content_type = $this->infos_header($local_headers['content-type']);
					if (isset($local_headers['content-disposition'])) {
						$content_disposition = $this->infos_header($local_headers['content-disposition']);
					}

					if (!empty($content_type['name']) || !empty($content_disposition['filename'])) {
						$pos = count($files);

						$files[$pos]['filename'] = ( !empty($content_type['name']) ) ? $content_type['name'] : $content_disposition['filename'];
						$files[$pos]['encoding'] = $local_headers['content-transfer-encoding'];
						$files[$pos]['data']     = base64_decode($local_message);
						$files[$pos]['filesize'] = strlen($files[$pos]['data']);
						$files[$pos]['filetype'] = substr($local_headers['content-type'], 0, strpos($local_headers['content-type'], ';'));
					}
				}

				continue;
			}

			if (isset($parts[$offset])) {
				$parts[$offset] .= $lines[$i] . "\r\n";
			}
		}

		return $files;
	}

	/**
	 * Commande DELE
	 * Demande au serveur d'effacer le message correspondant au numéro donné
	 *
	 * @param integer $num Numéro du message
	 *
	 * @return boolean
	 */
	public function delete_mail($num)
	{
		$this->put(sprintf('DELE %d', $num));

		return $this->checkResponse();
	}

	/**
	 * Envoi la commande NOOP
	 *
	 * @return boolean
	 */
	public function noop()
	{
		$this->put('NOOP');

		return $this->checkResponse();
	}

	/**
	 * Commande RSET
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
	 * Commande QUIT
	 * Ferme la connexion au serveur
	 */
	public function quit()
	{
		if (is_resource($this->socket)) {
			$this->put('QUIT');
			fclose($this->socket);

			$this->socket = null;
		}
	}

	/**
	 * Débogage
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

	public function __get($name)
	{
		switch ($name) {
			case 'responseData':
				return $this->{'_'.$name};
				break;
		}
	}
}
