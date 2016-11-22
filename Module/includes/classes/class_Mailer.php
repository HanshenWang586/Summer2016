<?
class Mailer {
	private $mailer;
	private $prefs;
	private $replaceList = array();
	// The allowed encoding types
	private $allowedEncoding = array("8bit", "7bit", "binary", "base64", "quoted-printable");
	// Set the default encoding
	public $encoding = 'quoted-printable';

	// Replace linebreaks with <br> elements?
	private $replaceLinebreaks = true;

	public function __construct($options = array()) {
		$this->prefs = array(
			'mailer' => 'smtp'
		);
		$this->reset();
	}

	public function reset() {
		global $model;
		$this->addReplaceList(array(
			'url' => $GLOBALS['rootURL'],
			'title' => $model->lang('SITE_NAME')
		), 'site');
		
		$this->clearAllRecipients();
		$this->clearAttachments();
	}

	/**
	 * Sets the Encoding of the message. Options for this are
	 * "8bit", "7bit", "binary", "base64", and "quoted-printable".
	 * @var enc
	 */
	public function setEncoding($enc) {
		$enc = strtolower($enc);
		if (in_array($enc, $this->allowedEncoding)) $this->encoding = $enc;
	}

	/**
	 *	Replace linebreaks with <br> elements? Use a boolean
	 */
	public function setReplaceLinebreaks($set) {
		$this->replaceLinebreaks = $set;
	}

	function addReplaceList($array, $prepend = '') {
		if ($prepend && $prepend[strlen($prepend) - 1] != '_') $prepend .= '_';
		foreach($array as $key => $value) {
			if (is_array($value)) $this->addReplaceList($value, $prepend . $key);
			else {
				$this->replaceList[$prepend . $key] = $value;
			}
		}
		return $this->replaceList;
	}

	function getMailer() {
		if (!$this->mailer) {
			include_once("phpmailer/class.phpmailer.php");
			$this->mailer = new PHPMailer();
			$this->mailer->Mailer = ifNot($this->prefs['mailer'], 'sendmail');

			if ($this->mailer->Mailer == 'smtp' and array_key_exists('smtpauth', $this->prefs) and $this->prefs['smtpauth'] == 'true') {
				$this->mailer->Host     = $this->prefs['host'];
				$this->mailer->SMTPAuth = $this->prefs['smtpauth'] == 'true';
				$this->mailer->Username = $this->prefs['username'];
				$this->mailer->Password = $this->prefs['password'];
			}
		}
		return $this->mailer;
	}

	function validateEmail($email, $source) {
		$validate = filter_var($email, FILTER_VALIDATE_EMAIL);
		if (!$validate) $this->log(sprintf("<strong>Mailer:</strong> %s address \"%s\" could not be validated.", $source, $email));
		return $validate;
	}

	/**
	 * Adds a "To" address.
	 * @param string $address
	 * @param string $name
	 * @return void
	 */
	function addTo($address, $name = "") {
		if (is_numeric($name)) $name = "";

		if (is_string($address) and strpos($address, ',') !== false) $address = split(',', $address);
		if (is_array($address)) foreach($address as $addr => $name) $this->addTo($addr, $name);
		elseif ($this->validateEmail(trim($address), 'to')) $this->getMailer()->AddAddress($address, $name);
		return true;
	}

	/**
	 * Adds a "Cc" address. Note: this function works
	 * with the SMTP mailer on win32, not with the "mail"
	 * mailer.
	 * @param string $address
	 * @param string $name
	 * @return void
	 */
	function addCc($address, $name = "") {
		if (is_numeric($name)) $name = "";

		if (is_string($address) and strpos($address, ',') !== false) $address = split(',', $address);
		if (is_array($address)) foreach($address as $addr => $name) $this->addCc($addr, $name);
		elseif ($this->validateEmail($address, 'cc')) $this->getMailer()->AddCC($address, $name);
		return true;
	}

	/**
	 * Adds a "Bcc" address. Note: this function works
	 * with the SMTP mailer on win32, not with the "mail"
	 * mailer.
	 * @param string $address
	 * @param string $name
	 * @return void
	 */
	function addBcc($address, $name = "") {
		if (is_numeric($name)) $name = "";

		if (is_string($address) and strpos($address, ',') !== false) $address = split(',', $address);
		if (is_array($address)) foreach($address as $addr => $name) $this->addBcc($addr, $name);
		elseif ($this->validateEmail($address, 'bcc')) $this->getMailer()->AddBCC($address, $name);
		return true;
	}

	/**
	 * Adds a "from" address.
	 * @param string $address
	 * @param string $name
	 * @return void
	 */
	function addFrom($address, $name = "") {
		if (is_numeric($name)) $name = "";

		if (is_string($address) and strpos($address, ',') !== false) $address = split(',', $address);
		if (is_array($address)) foreach($address as $addr => $name) $this->addFrom($addr, $name);
		elseif ($this->validateEmail($address, 'from')) {
			$mailer = $this->getMailer();
			// Also set the sender in case of 'sendmail' being used. This will help in identifying the sender (and will keep us out of the spambox, hopefully)
			$mailer->Sender = $mailer->From = $this->fromAddress = $address;
			$mailer->FromName = $this->fromName = $name;
		}
		return true;
	}

	function addContent($content) {
		$mailer = $this->getMailer();
		$content = replaceVars($content, $this->replaceList);
		if ($this->replaceLinebreaks) $content = nl2br($content);
		$mailer->MsgHTML($content);
	}

	function addSubject($subject) {
		$this->getMailer()->Subject = strip_tags(replaceVars($subject, $this->replaceList));
	}

	/**
	 * Clears all recipients assigned in the TO, CC and BCC
	 * array.  Returns void.
	 * @return void
	 */
	function clearAllRecipients() {
		$this->getMailer()->ClearAllRecipients();
		$this->badRecipients = array();
		return true;
	}

	function clearAttachments() {
		$this->getMailer()->ClearAttachments();
		return true;
	}

	// Attach current file uploads. Add an upload directory as parameter to store the file locally.
	function attachUploads($uploadFolder = false, $options = array()) {
		if (!$_FILES) return false;
		$files = false;

		// If an upload folder is set, upload the file.
		if ($uploadFolder) {
			if (!$options['extensions']) $options['extensions'] = array('doc', 'docx', 'pdf', 'jpg', 'jpeg', 'png', 'gif', 'zip');
			$uploader = new Uploader($uploadFolder, $options['extensions']);
			if ($uploader->captureAllUploads()) {
				$files = $uploader->successful;
			} else $this->log("<strong>File upload:</strong> " . $uploader->lastMessage());
		} else $files = $_FILES;
		
		if ($files) foreach ($files as $file) {
			$filepath = false;
			if (array_key_exists('path', $file)) $filepath = $file['path'];
			elseif (array_key_exists('tmp_name', $file)) $filepath = $file['tmp_name'];
			elseif (array_key_exists('name', $file)) $filepath = $file['name'];
			if ($filepath) $this->getMailer()->AddAttachment($filepath, request($file['name']));
		}

		return $files;
	}

	/**
	 *	Adds all repipients and from addresses that are contained in the $addresses parameter.
	 *	It should be in the form of "type => name => email-address".
	 */
	function add($options) {
		foreach ($options as $type => $option) {
			$method = 'add' . ucfirst($type);
			if (method_exists($this, $method)) {
				call_user_func(array($this, $method), $option);
			}
		}
	}

	function getErrorList() {
		return $this->getMailer()->errorList;
	}
	
	private function log($message) {
		//var_dump($message);
	}
	
	/**
	 * Sends an email based on earlier set parameters and the given options. All options are optional, although the email will
	 * not be sent if not enough settings are given (by calling individual setters or by supplying enough parameters to this function)
	 *
	 * @param array $options the options to send the email with:
	 *  - string $options['content'] - The content of the email to be sent
	 *  - string $options['subject'] - The subject of the email
	 *  - mixed $options['to']
	 *  - mixed $options['from']
	 *  - mixed $options['cc']
	 *  - mixed $options['bcc'] - Can be a string (1 or more emailaddresses, comma-seperated) or an array in the form:
	 *  	array(
	 *  		$name1 => $emailAddress1,
	 *  		$name2 => $emailAddress2
	 *  		... etc.
	 *   	)
	 *   - string $options['folder'] - The name of the folder which should be used in the email module in ewyse to store the email
	 *   - string $options['encoding'] - Sets the encoding of the email. Will only be accepted if in the list $this->allowedEncoding
	 *
	 * @return boolean Whether or not the email was succesfully sent
	 */
	function send($options = array()) {
		if ($options) $this->add($options);
		// Get the general prefs
		$prefs = $this->prefs;

		// create the mailer
		$mailer = $this->getMailer();

		// Set the encoding before we send the email.
		if (isset($options['encoding'])) $this->setEncoding($options['encoding']);
		$mailer->Encoding = $this->encoding;

		// Set the default From if there's none set and if a default is available
		if (!$mailer->From) {
			if (!(
			$prefs['mailFrom'] and
			$this->addFrom($prefs['mailFrom'], $prefs['mailFromName']) and
			$mailer->From
			)) {
				$this->log("<strong>Mailer:</strong> No Sender email address was given. Cannot send email");
				return false;
			}
		}
		// Set the default To if there's none set and if a default is available
		if (!$mailer->to) {
			if (!(
			$prefs['mailTo'] and
			$this->addTo($prefs['mailTo'], $prefs['mailToName']) and
			$mailer->to
			)) {
				$this->log("<strong>Mailer:</strong> No Receiver email address was given. Cannot send email");
				return false;
			}
		}

		// if a folder is set, we can store the email!
		if (request($options['folder'])) $this->storeEmail($options['folder']);

		$succes = false;
		try {
			// Disable Warnings, as they cannot be caught by PHPs exception handler.. hurray!!
			$errLevel = error_reporting(E_ALL ^ E_WARNING);
			$succes = $mailer->Send();
			error_reporting($errLevel);
		} catch(Exception $e) {
			$this->log(sprintf("<strong>Mailer:</strong> Sending email threw exception: \"%s\".", $e));
		}
		if (!$succes) $this->log(sprintf("<strong>Mailer:</strong> Sending email generated the error: \"%s\".", $mailer->errorInfo));
		return $succes;
	}
}

?>