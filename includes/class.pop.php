<?php
/**
 * @package   Wanewsletter
 * @author    Bobe <wascripts@phpcodeur.net>
 * @link      http://phpcodeur.net/wascripts/wanewsletter/
 * @copyright 2002-2015 Aurélien Maille
 * @license   http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 */

/**
 * Classe de connexion et consultation de serveur POP
 *
 * Les sources qui m'ont bien aidées :
 *
 * @link http://www.interpc.fr/mapage/billaud/telmail.htm
 * @link http://www.devshed.com/Server_Side/PHP/SocketProgramming/page8.html
 * @link http://www.commentcamarche.net/internet/smtp.php3
 * @link http://abcdrfc.free.fr/
 *
 * Toutes les commandes de connexion et de dialogue avec le serveur sont
 * détaillées dans la RFC 1939.
 *
 * @link http://abcdrfc.free.fr/rfc-vf/rfc1939.html (français)
 * @link http://www.rfc-editor.org/rfc/rfc1939.txt (anglais)
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
	private $opts       = array(
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
		'stream_context_options' => null,
		'stream_context_params'  => null
	);

	private $_responseData;

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
			// Alternative pour l'activation du débogage
			if (!empty($opts['debug'])) {
				$this->debug = $opts['debug'];
			}

			$this->opts = array_merge($this->opts, $opts);
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

		$startTLS = false;
		if (!preg_match('#^(ssl|tls)(v[.0-9]+)?://#', $host)) {
			$startTLS = $this->opts['starttls'];
		}

		//
		// Ouverture de la connexion au serveur POP
		//
		$params = array();
		if (is_array($this->opts['stream_context_options'])) {
			$params[] = $this->opts['stream_context_options'];

			if (is_array($this->opts['stream_context_params'])) {
				$params[] = $this->opts['stream_context_params'];
			}
		}

		$context = call_user_func_array('stream_context_create', $params);

		$this->socket = stream_socket_client(
			sprintf('%s:%d', $host, $port),
			$errno,
			$errstr,
			$this->timeout,
			STREAM_CLIENT_CONNECT,
			$context
		);

		if (!$this->socket) {
			throw new Exception("Pop::connect(): Failed to connect to POP server ($errno - $errstr)");
		}

		stream_set_timeout($this->socket, $this->timeout);

		if (!$this->checkResponse()) {
			return false;
		}

		//
		// Le cas échéant, on utilise le protocole sécurisé TLS
		//
		if ($startTLS) {
			$this->put('STLS');
			if (!$this->checkResponse()) {
				return false;
			}

			if (!stream_socket_enable_crypto(
				$this->socket,
				true,
				STREAM_CRYPTO_METHOD_TLS_CLIENT
			)) {
				return false;
			}
		}

		//
		// Identification
		//
		$this->put(sprintf('USER %s', $username));
		if (!$this->checkResponse()) {
			return false;
		}

		$this->put(sprintf('PASS %s', $passwd));
		if (!$this->checkResponse()) {
			return false;
		}

		return true;
	}

	/**
	 * Envoit les données au serveur
	 *
	 * @param string $data Données à envoyer
	 */
	protected function put($data)
	{
		$data .= "\r\n";
		$this->log($data);

		fputs($this->socket, $data);
	}

	/**
	 * Récupère la réponse du serveur
	 *
	 * @return boolean
	 */
	protected function checkResponse()
	{
		$data = fgets($this->socket);
		$this->log($data);
		$this->_responseData = rtrim($data);

		if (!(substr($this->_responseData, 0, 3) == '+OK')) {
			return false;
		}

		return true;
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

		list(, $total_msg, $total_size) = explode(' ', $this->_responseData);

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
		$msg_send = 'LIST';
		if ($num > 0) {
			$msg_send .= ' ' . $num;
		}

		$this->put($msg_send);
		if (!$this->checkResponse()) {
			return false;
		}

		if ($num == 0) {
			$list = array();

			do {
				$tmp = fgets($this->socket, 150);

				$this->log($tmp);

				if (substr($tmp, 0, 1) != '.') {
					list($mail_id, $mail_size) = explode(' ', $tmp);
					$list[$mail_id] = $mail_size;
				}
			}
			while (substr($tmp, 0, 1) != '.');

			return $list;
		}
		else {
			list(,, $mail_size) = explode(' ', $this->_responseData);

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
			$msg_send = 'RETR ' . $num;
		}
		else {
			$msg_send = 'TOP ' . $num . ' ' . $max_line;
		}

		$this->put($msg_send);
		if (!$this->checkResponse()) {
			return false;
		}

		$output = '';

		do {
			$tmp = fgets($this->socket, 150);

			$this->log($tmp);

			if (substr($tmp, 0, 1) != '.') {
				$output .= $tmp;
			}
		}
		while (substr($tmp, 0, 1) != '.');

		$output = preg_replace("/\r\n?/", "\n", $output);

		list($headers, $message) = explode("\n\n", $output, 2);

		$this->contents[$num]['headers'] = trim(preg_replace("/\n( |\t)+/", ' ', $headers));
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

		$lines = explode("\n", $str);
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
		$total = preg_match_all('/=\?[^?]+\?(Q|q|B|b)\?([^?]+)\?\=/', $str, $matches);

		for ($i = 0; $i < $total; $i++) {
			if ($matches[1][$i] == 'Q' || $matches[1][$i] == 'q') {
				$tmp = preg_replace('/=([a-zA-Z0-9]{2})/e', 'chr(ord("\\x\\1"));', $matches[2][$i]);
				$tmp = str_replace('_', ' ', $tmp);
			}
			else {
				$tmp = base64_decode($matches[2][$i]);
			}

			$str = str_replace($matches[0][$i], $tmp, $str);
		}

		return trim($str);
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
		$lines    = explode("\n", $message);
		$offset   = 0;

		for ($i = 0; $i < count($lines); $i++) {
			if (strstr($lines[$i], $infos['boundary'])) {
				$offset         = count($parts);
				$parts[$offset] = '';

				if (isset($parts[$offset - 1])) {
					preg_match("/^(.+?)\n\n(.*?)$/s", trim($parts[$offset - 1]), $match);

					$local_headers = trim(preg_replace("/\n( |\t)+/", ' ', $match[1]));
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
				$parts[$offset] .= $lines[$i] . "\n";
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
		$this->put('DELE ' . $num);

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
		$this->put('STAT');

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
	private function log($str)
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
