<?php
	/**
	 * i'm thinking that this will be a class that can read/write MIME
	 *
	 * as such, it can be used to interpret messages read over POP
	 * or produce messages to be sent over SMTP
	 *
	 * smtp is just concerned with from, to, and data
	 * in that data is:
	 *
	 * message headers
	 * <empty line>
	 * message body
	 * .
	 *
	 * i think this class should be able to create the message headers and body
	 *
	 * <code>function getDataForSMTP() {
	 * return $this->getHeaders()."\r\n\r\n".$this->getBody();
	 * }
	 */
class Mail {
	private $LE = "\r\n";
	private $address = "/[\w\-]+(\.[\w\-]+)*@[\w\-]+(\.[\w\-]+)+/";
	private $mime = array();
	private $num_attachments = 0;

	public function __construct() {
		$this->addresses = array('to' => array(), 'cc' => array(), 'bcc' => array());
		$this->addBcc('bitbucket@gokunming.com');
	}

	public function getData() {
		return $this->getHeaders().$this->LE.$this->LE.$this->bundleBody();
	}

	public function setData($data) {
		$this->data = $data;
	}

	public function setFrom($address, $name = '') {
		$this->from = array('address' => $address,
							'name' => $name);
	}

	public function getFrom() {
		return $this->from['address'];
	}

	private function getFromHeader() {
		return"\"".$this->from['name']."\" <".$this->from['address'].">";
	}

	public function setDate($date) {
		$this->date = $date;

		// TODO
		//$date_parsed = date_parse($date);

		//print_r($date_parsed);
	}

	public function getDate() {

		if ($this->date) {
			return $this->date;
		}
		else {
			return date('r');
		}
	}

	public function setSubject($subject) {
		$this->subject = strip_tags($subject);
	}

	private function getSubject() {
		return $this->subject;
	}

	public function setBody($body) {
		$this->body = $body;
	}

	public function setMime($mime) {
		$this->mime = $mime;
	}

	public function setMessage($message) {
		$message = trim($message);
		$message = str_replace("\r", '', $message);

		// we're going to assume it's in plain text for now, and build simple html from it
		$this->mime['content_type'] = 'multipart/alternative';

		$this->mime[0]['content_type'] = 'text/plain';
		$this->mime[0]['charset'] = 'UTF-8';
		$this->mime[0]['content-transfer-encoding'] = '7bit';
		$this->mime[0]['body'] = strip_tags(str_replace("\n", $this->LE, $message));

		$this->mime[1]['content_type'] = 'text/html';
		$this->mime[1]['charset'] = 'UTF-8';
		$this->mime[1]['content-transfer-encoding'] = '7bit';
		$this->mime[1]['body'] = $this->HTMLify($message);
	}

	public function setHTMLMessage($message) {
		$message = chunk_split(base64_encode(trim($message)));

		$this->mime['content_type'] = 'multipart/html';

		/*$this->mime[0]['content_type'] = 'text/plain';
		$this->mime[0]['charset'] = 'UTF-8';
		$this->mime[0]['content-transfer-encoding'] = '7bit';
		$this->mime[0]['body'] = 'Sorry, this is an HTML email - please open this email in a mail program that can read HTML';*/

		$this->mime[0]['content_type'] = 'text/html';
		$this->mime[0]['charset'] = 'UTF-8';
		$this->mime[0]['content-transfer-encoding'] = 'base64';
		$this->mime[0]['body'] = $message;
	}

	public function addAttachment($filepath, $filename, $mime_type = '') {

		// if this is the first attachment
		if ($this->num_attachments == 0) {
		$current_mime = $this->mime;
		$this->mime = array();
		$this->mime[0] = $current_mime;
		$this->mime['content_type'] = 'multipart/mixed';
		}

		$this->num_attachments++;

		// if the mime type has not been specified, try to work it out from the filename
		if ($mime_type == '') {
			$mime_type = $this->getFileContentType($filename);
		}

		$this->mime[] = array(	'headers'	=> "Content-Type: $mime_type; name=\"$filename\"".$this->LE.
												"Content-Transfer-Encoding: base64".$this->LE.
												"Content-Disposition: attachment; filename=\"$filename\"",
								'body'	=> chunk_split(base64_encode(file_get_contents($filepath))));
	//print_r($this->mime);
	}

	private function HTMLify($text) {
		$text = nl2br($text);
		$text = "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
<html>
<head>
</head>
<body bgcolor=\"#ffffff\" text=\"#000000\">
<font size=\"-1\" face=\"Helvetica, Arial, sans-serif\">$text</font>
</body>
</html>";
		$text = str_replace("\n", $this->LE, $text);
		return $text;
	}

	private function bundleBody() {

		foreach($this->mime as $mime) {

			if (is_array($mime)) {

				//print_r($mime);

				$body .= '--'.$this->boundary.$this->LE;

				$mail = new Mail;
				$mail->setMime($mime);
				$mail->setBody($mime['body']);

					/*if ($mime['headers']) {
					$mail->setHeaders($mime['headers']);
					}

				$mail->setContentType($mime['content_type']);
				$mail->setCharset($mime['charset']);
				$mail->setContentTransferEncoding($mime['content-transfer-encoding']);*/

				$body .= $mail->getData();
			}
		}

		$body .= $this->body.$this->LE;

		if ($this->boundary) {
			$body .= '--'.$this->boundary.'--'.$this->LE;
		}

		return $body;
	}

	private function getBody() {
		return $this->body;
	}

	private function clearAddresses($type) {

		$this->addresses[$type] = array();
	}

	private function addAddress($type, $address, $name) {

		$this->addresses[$type][] = array('address' => $address,
										'name' => $name);
	}

	public function clearTo() {

		$this->clearAddresses('to');
	}

	public function addTo($address, $name = '') {

		if (!$this->isDuplicate($address))
		{
		$this->addAddress('to', $address, $name);
		}
	}

	public function clearCc() {

		$this->clearAddresses('cc');
	}

	public function addCc($address, $name = '') {

		if (!$this->isDuplicate($address))
		{
		$this->addAddress('cc', $address, $name);
		}
	}

	public function clearBcc() {

		$this->clearAddresses('bcc');
	}

	public function addBcc($address, $name = '') {

		if (!$this->isDuplicate($address))
		{
		$this->addAddress('bcc', $address, $name);
		}
	}

	public function setHeaders($headers) {
		$this->headers = $headers;
	}

	private function getHeaders() {

		if ($this->hasTo()) {
			$headers[] = 'To: '.$this->compileAddresses('to');
			$headers[] = 'Date: '.$this->getDate();
			$headers[] = 'From: '.$this->getFromHeader();
			$headers[] = 'MIME-Version: 1.0';
			$headers[] = 'User-Agent: MGHK Mailer';
			$headers[] = 'Subject: '.$this->getSubject();
		}

		if ($this->hasCc()) {
			$headers[] = 'Cc: '.$this->compileAddresses('cc');
		}

		if ($this->mime['headers']) { // attachments have these
			$headers[] = $this->mime['headers'];
		}
		else {
			$headers[] = $this->getContentTypeHeader();
		}

	return implode($this->LE, $headers);
	}

	private function getContentTypeHeader() {

		$ct = 'Content-Type: '.$this->getContentType();

		if (strpos($this->getContentType(), 'multipart') === 0) {
			$ct .= '; boundary='.$this->generateBoundary();
		}
		else {

			if ($this->getCharset()) {
			$ct .= '; charset='.$this->getCharset();
			}

			if ($this->getContentTransferEncoding()) {
			$ct .= "\r\n".'Content-Transfer-Encoding:'.$this->getContentTransferEncoding();
			}
		}

		return $ct;
	}

	private function generateBoundary() {

		$poss = array_merge(range(0, 9), range(A, Z), range(a, z));

		for ($i=0; $i<20; $i++) {
			$separator .= $poss[mt_rand(0, count($poss))];
		}

		$this->boundary = $separator;
		return $separator;
	}

	private function compileAddresses($type) {

		$addresses = array();

		for ($i=0; $i < count($this->addresses[$type]); $i++) {

			if ($this->addresses[$type][$i]['name'] == '')
			{
			$addresses[] = $this->addresses[$type][$i]['address'];
			}
			else
			{
			$addresses[] = "\"".$this->addresses[$type][$i]['name']."\" <".$this->addresses[$type][$i]['address'].">";
			}
		}

		return implode(', ', $addresses);
	}

	private function hasTo() {
		return $this->hasAddresses('to');
	}

	private function hasCc() {
		return $this->hasAddresses('cc');
	}

	private function hasAddresses($type) {

		if (count($this->addresses[$type])) {
		return true;
		}
		else {
		return false;
		}
	}

	private function isDuplicate($new_address) {

		if (in_array($address, $this->getAllRecipients())) {
		return true;
		}
	}

	public function getAllRecipients() {

		$types = array('to', 'cc', 'bcc');
		$all = array();

		foreach($types as $type) {

			foreach($this->addresses[$type] as $address) {
				$all[] = $address['address'];
			}
		}

		return $all;
	}

	private function getFileContentType($filename) {

		$str = basename($filename);
		$name_arr = explode(".", $str);
		$len = count($name_arr) - 1;
		$name_arr[$len] = strtolower($name_arr[$len]);

		switch($name_arr[$len])
		{
			case 'jpg':
			$type = 'image/jpeg';
			break;

			case 'jpeg':
			$type = 'image/jpeg';
			break;

			case 'gif':
			$type = 'image/gif';
			break;

			case 'txt':
			$type = 'text/plain';
			break;

			case 'pdf':
			$type = 'application/pdf';
			break;

			case 'csv';
			$type = 'text/csv';
			break;

			case 'html':
			$type = 'text/html';
			break;

			case 'htm':
			$type = 'text/html';
			break;

			case 'xml':
			$type = 'text/xml';
			break;

			case 'zip':
			$type = 'application/zip';
			break;
		}

		return $type;
	}

	// this runs through a message that we've put into
	// $this->data, and works out what it all means
	function parse() {

		$first_lele = strpos($this->data, $this->LE.$this->LE);
		$header_block = trim(substr($this->data, 0, $first_lele));
		$body_block = trim(substr($this->data, $first_lele));

		$this->readHeaders($header_block);
		$this->readBody($body_block);
	}

	private function readHeaders($header_block) {

		// unfold folded headers - see section 2.2.3 of RFC2822
		$header_block = preg_replace("/$this->LE\s+/", ' ', $header_block);
		$headers = explode($this->LE, $header_block);

		foreach ($headers as $header) {

			if (stripos($header, 'subject: ') === 0) {
				$this->setSubject(trim(substr($header, 9)));
			}

			if (stripos($header, 'from: ') === 0) {
				preg_match($this->address, $header, $matches);
				$this->setFrom($matches[0]);
			}

			if (stripos($header, 'content-type: ') === 0) {
				$ct_data = $this->parseContentTypeHeader($header);
				$this->addMIMEData($ct_data);
			}

			$key = 'content-transfer-encoding';

			if (stripos($header, $key) === 0) {
				$this->addMIMEData(array($key => str_ireplace($key.': ', '', $header)));
			}

			$key = 'content-disposition';

			if (stripos($header, $key) === 0) {
				$this->addMIMEData(array($key => str_ireplace($key.': ', '', $header)));
			}

			if (stripos($header, 'to: ') === 0) {
				preg_match_all($this->address, $header, $matches);

				foreach ($matches[0] as $address) {
					$this->addTo($address);
				}
			}

			if (stripos($header, 'cc: ') === 0) {
				preg_match_all($this->address, $header, $matches);

				foreach ($matches[0] as $address) {
					$this->addCc($address);
				}
			}

			if (stripos($header, 'date: ') === 0) {
				$this->setDate(trim(substr($header, 6)));
			}
		}
	}

	private function parseContentTypeHeader($header) {
		// e.g. Content-Type: text/plain; charset=UTF-8; format=flowed
		$header_pieces = explode('; ', $header);

		foreach ($header_pieces as $hp) {

			if (preg_match("/content-type: .*/i", $hp, $matches)) {
				$content_type = str_ireplace('content-type: ', '', $matches[0]);
			}

			if (preg_match("/boundary=.*/i", $hp, $matches)) {
				$boundary = str_ireplace('boundary=', '', $matches[0]);
				$boundary = trim($boundary, '";');
			}

			if (preg_match("/charset=.*/i", $hp, $matches)) {
				$charset = str_ireplace('charset=', '', $matches[0]);
				$charset = trim($charset, ';');
			}

			if (preg_match("/name=.*/i", $hp, $matches)) {
				$filename = str_ireplace('name=', '', $matches[0]);
				$filename = trim($filename, '";');
			}
		}

		return array(	'content_type' => $content_type,
						'boundary' => $boundary,
						'charset' => $charset,
						'filename' => $filename);
	}

	private function addMIMEData($ct_data) {
		$this->mime = array_merge($this->mime, $ct_data);
	}

	private function readMIMEPartProperty($key) {
		return $this->mime[$key];
	}

	private function readBody($body) {

		$this->body = $body;

		if ($this->readMIMEPartProperty('boundary')) {
		//echo "boundary: ".$this->readMIMEPartProperty('boundary');
			$body_parts = explode('--'.$this->readMIMEPartProperty('boundary'), $body);
						//print_r($body_parts);
			// remove preamble and epilogue
			$body_parts = array_slice($body_parts, 1, count($body_parts) - 2);

			foreach ($body_parts as $body_part) {

				$part = new Mail;
				$part->setData(trim($body_part));
				$part->parse();
				$this->mime['children'][] = $part;
			}
		}
	}

	public function setContentType($content_type) {
		$this->mime['content_type'] = $content_type;
	}

	public function getContentType() {
		return $this->mime['content_type'];
	}

	public function setCharset($charset) {
		$this->mime['charset'] = $charset;
	}

	public function getCharset() {
		return $this->mime['charset'];
	}

	public function setContentTransferEncoding($content_transfer_encoding) {
		$this->mime['content-transfer-encoding'] = $content_transfer_encoding;
	}

	public function getContentTransferEncoding() {
		return $this->mime['content-transfer-encoding'];
	}

	public function getContentDisposition() {
		return $this->mime['content-disposition'];
	}

	public function getPlainText() {

		if ($this->getContentType() == 'text/plain') {

			return $this->body;
		}
		else {
			if (is_array($this->mime['children'])) {
				foreach ($this->mime['children'] as $mail) {
					$text = $mail->getPlainText();

					if ($text != '') {
					return $text;
					}
				}
			}
		}
	}

	public function getHTML() {

		if ($this->getContentType() == 'text/html') {

			return $this->body;
		}
		else {
			if (is_array($this->mime['children'])) {
				foreach ($this->mime['children'] as $mail) {
					$html = $mail->getHTML();

					if ($html != '') {
					return $html;
					}
				}
			}
		}
	}

	public function getAttachments() {

		if (strpos($this->getContentDisposition(), 'attachment') === 0 || strpos($this->getContentDisposition(), 'inline') === 0) {

			$attachments[] = array(	'content' => base64_decode($this->body), // TODO we're making a bad assumption here - need to check encoding mime value
									'filename' => $this->mime['filename']);
		}
		else {
			if (is_array($this->mime['children'])) {
				foreach ($this->mime['children'] as $mail) {
					$attachments[] = $mail->getAttachments();
				}
			}
		}

	return $attachments;
	}
}

/*
$smtp = new SMTP;
//$smtp->setHost(SMTP_HOST);
//$smtp->setPort(SMTP_PORT);
//$smtp->setUser(SMTP_USER);
//$smtp->setPassword(SMTP_PASS);
//$smtp->setAuthType('cram-md5');
$smtp->open();

$mail = new Mail;
$mail->setFrom('info@gokunming.com');
$mail->addTo($email);
$mail->setSubject(stripslashes($_POST['subject']));
$mail->setMessage(stripslashes($_POST['message']));
$mail->addAttachment($filepath, $filename);

$smtp->send($mail->getFrom(), $mail->getAllRecipients(), $mail->getData());
$smtp->quit();

*/
?>