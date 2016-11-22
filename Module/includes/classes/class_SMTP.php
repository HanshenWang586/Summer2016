<?php
class SMTP {

	private $smtp;
	private $host = SMTP_HOST;
	private $port = 25;
	private $timeout = 60;
	private $user = SMTP_USER;
	private $pass = SMTP_PASS;
	private $auth_type = 'cram-md5';
	private $num_mails;
	private $num_bytes;
	private $data;
	private $address = "/[\w\-]+(\.[\w\-]+)*@[\w\-]+(\.[\w\-]+)+/";

	public function __construct() {
		if (defined('SMTP_PORT'))
			$this->setPort(SMTP_PORT);
	}

	public function getLog() {
		return $this->log;
	}

	public function setHost($host) {
		$this->host = $host;
	}

	function setPort($port) {
		$this->port = $port;
	}

	function setAuthType($auth_type) {
		$this->auth_type = $auth_type;
	}

	function setUser($user) {
		$this->user = $user;
	}

	function setPassword($pass) {
		$this->pass = $pass;
	}

	private function log($text) {
		$this->log .= trim($text)."\n";
	}

	function write($text) {

		if (!$this->smtp)
			$this->log('no socket');
		else {
			$bytes = fwrite($this->smtp, $text."\r\n");

			if (!$bytes)
				$this->log("write [$text] failed");
			else
				$this->log("$bytes bytes written [$text]");
		}
	}

	function read() {

		if (!$this->smtp) {
			$this->log("no socket");
		}
		else {
			$text = fgets($this->smtp);
			$this->log($text);
			return $text;
		}
	}

	function open() {
		// open a socket to the SMTP server
		$this->smtp = fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

		if (!$this->smtp)
			$this->log("failed to open socket [$this->host:$this->port] $errno $errstr");
		else
			$this->log('socket opened successfully');

		// read what should be a server id string
		$this->read();
		$this->write('HELO');
		$this->read();

		if ($this->auth_type)
			$this->performAuth();
	}

	function performAuth() {

		switch ($this->auth_type) {
			case 'cram-md5':
			$this->write('AUTH cram-md5');
			$line = $this->read();
			$line_bits = explode(' ', trim($line));
			$challenge = $line_bits[1];

			$this->write(base64_encode($this->user.' '.$this->hmac($this->pass, base64_decode($challenge))));
			$this->read();
			break;

		// TODO add other auth types
		}
	}

	private function hmac($key, $data) {
		// RFC 2104 HMAC implementation for php.
		// Creates an md5 HMAC.
		// Eliminates the need to install mhash to compute a HMAC
		// Hacked by Lance Rushing

		$b = 64; // byte length for md5

		if (strlen($key) > $b) {
			$key = pack("H*",md5($key));
		}

		$key = str_pad($key, $b, chr(0x00));
		$ipad = str_pad('', $b, chr(0x36));
		$opad = str_pad('', $b, chr(0x5c));
		$k_ipad = $key ^ $ipad ;
		$k_opad = $key ^ $opad;

		return md5($k_opad.pack("H*", md5($k_ipad.$data)));
	}

	public function send($from, $recipients, $data) {
		$this->write('MAIL FROM: '.$from);
		$this->read();

		foreach ($recipients as $recipient) {
			$this->write('RCPT TO: '.$recipient);
			$this->read();
		}

		$this->write('DATA');
		$this->read();
		$this->write($data);
		$this->write('.');
		$this->read();
	}

	function quit() {
		$this->write('QUIT');
	    $this->read();

		fclose($this->smtp);
	}

} // end of SMTP class



/* EXAMPLE USAGE

header('Content-type: text/plain');
$smtp = new SMTP;
$smtp->setHost('www.intersolua.com');
$smtp->setUser('matthew@gokunming.com');
$smtp->setPassword('pioneers');
$smtp->setAuthType('cram-md5');
$smtp->open();

$from = 'matthew@gokunming.com';
$to = array('matt@ghatzhat.com');

$data = "Date: ".date('r')."
Subject: test 2
To: matt@ghatzhat.com
From: matthew@gokunming.com
Content-Type: text/plain

message body";
$smtp->send($from, $to, $data);

$smtp->quit();
echo $smtp->getLog();
*/
?>