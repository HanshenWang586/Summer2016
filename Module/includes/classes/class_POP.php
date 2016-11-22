<?php
class POP {

	private $pop;
	private $host;
	private $port = 110;
	private $timeout = 60;
	private $user;
	private $pass;
	private $num_mails;
	private $num_bytes;
	private $data;
	private $address = "/[\w\-]+(\.[\w\-]+)*@[\w\-]+(\.[\w\-]+)+/";
	
	public function __construct() {
	
	}
	
	function getNumMails() {
		return $this->num_mails;
	}
	
	function getMessageData() {
	return $this->data;
	}
	
	function getLog() {
	return $this->log;
	}
	
	function setHost($host) {
		$this->host = $host;
	}
	
	function setPort($port) {
		$this->port = $port;
	}
	
	function setUser($user) {
		$this->user = $user;
	}
	
	function setPassword($pass) {
		$this->pass = $pass;
	}
	
	function log($text) {
		$this->log .= trim($text)."\n";
	}

	function write($text) {
	
		if (!$this->pop) {
			$this->log("no socket");
		}
		else {
			$bytes = fwrite($this->pop, $text."\r\n");
			
			if (!$bytes) {
				$this->log("write [$text] failed");
			}
			else {
				$this->log("$bytes bytes written");
			}
		}
	}
	
	function read() {
	
		if (!$this->pop) {
			$this->log("no socket");
		}
		else {
			$text = fgets($this->pop);
			$this->log($text);
			return $text;
		}
	}

	function open() {
	
		// open a socket to the POP server
		$this->pop = fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
		
		if (!$this->pop) {
			$this->log("failed to open socket [$this->host:$this->port] $errno $errstr");
		}
		else {
			$this->log("socket opened successfully");
		}
		
		// read what should be a server id string
		$this->read();
		
		// send user name to server
		$this->write("USER $this->user");
		
		$this->read();
		
		// send password to server
		$this->write("PASS $this->pass");
		
		$this->read();
	}

	function stat() {

		// get mailbox details
		// box_props[1] gives the number of mails
		// box_props[2] gives the mailbox size in bytes

		$this->write('STAT');
		$line = $this->read();

		$box_props = explode(' ', trim($line));

		$this->num_mails = $box_props[1];
		$this->num_bytes = $box_props[2];
	}

	function retrieve($id) {

		// empty out the last email
  		unset($this->data);

		// retr message with id=$id
		$this->write("RETR $id");

		// put the retr'd data line by line into an array
		while (substr($line = $this->read(), 0, 1)  !=  '.') {
		$matches = array();
		
			if (substr($line, 0, 9) == 'Subject: ' && $this->data['subject'] == '') {
			$this->data['subject'] = trim(substr($line, 9));
			}

			if (substr($line, 0, 6) == 'From: ' && $this->data['from'] == '') {
			preg_match($this->address, $line, $matches);
			$this->data['from'] = $matches[0];
			}

			if (substr($line, 0, 4) == 'To: ' && $this->data['to'] == '') {
			preg_match_all($this->address, $line, $matches);
			$this->data['to'] = $matches[0];
			}
			
			if (substr($line, 0, 4) == 'CC: ' && $this->data['cc'] == '') {
			preg_match_all($this->address, $line, $matches);
			$this->data['cc'] = $matches[0];
			}
			
			if (substr($line, 0, 6) == 'Date: ' && $this->data['date'] == '') {
			$this->data['date'] = trim(substr($line, 6));
			}

		$this->data['data'] .= $line;
		}
		
	$this->data['data'] = trim(substr($this->data['data'], 3));
	}

	function delete($id) {
		$this->write("DELE $id");
	    $this->read();
	}

	function quit() {
		$this->write('QUIT');
	    $this->read();
		
		fclose($this->pop);
	}

} // end of POP class



/* EXAMPLE USAGE

header('Content-type: text/plain');
$pop = new POP;
$pop->setHost('www.intersolua.com');
$pop->setUser('db@gokunming.com');
$pop->setPassword('throw992');
$pop->open();
$pop->stat();

	for ($i = 1; $i <= $this->getNumMails(); $i++) {
		$pop->retrieve($i);
		print_r($pop->getMessageData());
	}

$pop->quit();
echo $pop->getLog();
*/
?>