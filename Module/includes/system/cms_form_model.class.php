<?php

abstract class CMS_Form_Model extends CMS_Model {
	public $data = array(
		'name' => 'Comments',
		'data' => array(),
		'missing' => array(),
		'validated' => false, 
		'message' => '',
		'action' => false
	);
	
	public $mimegroups = array(
		'image' => 'image/jpg,image/jpeg,image/gif,image/png,image/pjpeg'
	);
	
	/**
	 * The list of required input fields, based on the model action and the defined actions in the model extending this class.
	 * @var array
	 */
	private $required = array();
	/**
	 * The list of accepted input fields, based on the model action and the defined actions in the model extending this class.
	 * 
	 * @var array
	 */
	private $accept = array();
	
	public function __construct(MainModel $model, $className, $name, $args = array()) {
		parent::__construct($model, $className, $name, $args);
		$args = request($args['data']);
		if ($action = $this->model->state('action') and $this->setAction($action)) {
			$data = request($args['data']);
			$this->data('data', $this->valData(ifElse($data, array())));
			$this->data('validated', $this->valFields($this->data('data')));
		}
	}
	
	public function setAction($action) {
		if ($this->isAction($action)) {
			$this->data('action', $action);
			$this->required = ifElse($this->actions[$action]['required'], array());
			$this->accept = request($this->actions[$action]['accept']);
			return true;
		}
		return false;
	}
	
	public function getRequired($name, $group = NULL) {
		if (isset($this->required)) {
			if ($group) {
				if (is_array($group)) $group = request($group['group']);
				if (array_key_exists($group, $this->required)) $required = $this->required[$group];
				else return false;
			}
			else $required = $this->required;
			foreach ($required as $key => $value) {
				if (is_string($key) && $name == $key) return $value;
				elseif ($name == $value) return true; 
			}
		}
		return false;
	}
	
	public function data($name, $value = NULL) {
		if ($value === NULL) return array_key_exists($name, $this->data) ? $this->data[$name] : false;
		else $this->data[$name] = $value;
	}
	
	public function validated() {
		return $this->data('validated') === true;
	}
	
	public function valData(array $data = array(), $accept = NULL) {
		if ($accept === NULL) $accept = $this->accept;
		// If a language array is given, process it to represent the active languages instead
		if (is_array($accept) && array_key_exists('!lang', $accept)) {
			$lang = $this->getActiveLanguages($data);
			foreach($lang as $l) $accept[$l] = $accept['!lang'];
			unset($accept['!lang']);
		}
		foreach($data as $key => $value) {
			if (is_array($value)) $data[$key] = $this->valData($value, request($accept[$key]));
			elseif (is_array($accept) && !in_array($key, $accept)) unset($data[$key]);
			else $data[$key] = htmlspecialchars(trim($value));
		}
		return $data;
	}
	
	public function valFields(array $data, array $required = NULL, $group = false) {
		$validated = true;
		ifNot($required, $this->required);
		foreach ($required as $key => $field) {
			if ($key === '!lang') {
				$lang = $this->getActiveLanguages($data);
				foreach($lang as $l) $this->valFields(request($data[$l]), $field, $l);
			} elseif (is_array($field)) {
				if (!$this->valFields((array) request($data[$key]), $field, $key)) $validated = false;
			} elseif ($field) {
				$val = true;
				if (is_string($key)) {
					$val = array_key_exists($key, $data) ? $this->validate($data[$key], $field) : false;
					$field = $key;
				} elseif (!array_key_exists($field, $data) || ($data[$field] !== '0' && empty($data[$field]))) $val = false;
				if (!$val) {
					$this->addMissing($field, $group);
					$validated = false;
				}
			}
		}

		return $validated;
	}
	
	public function getActiveLanguages(array $data = NULL, $company_id = NULL) {
		// Check if in the post data languages were set
		if (($data or $data = $this->data['data']) && array_key_exists('lang', $data) && $data['lang']) {
			$lang = is_array($data['lang']) ? $data['lang'] : explode(',', $data['lang']);
			// Add the current language
			if (!in_array($this->model->lang, $lang)) array_unshift($lang, $this->model->lang);
		// If there's an active user, check if it has a company with language settings 
		} elseif ($user = $this->tool('security')->getActiveUser() and $company = $user->getCompany($company_id) and $lang = $company->get('lang')) {
			$lang = is_array($lang) ? $lang : explode(',', $lang);
		} else {
			$lang = $this->model->allowedLanguages;
		}
		return $lang;
	}
	
	public function addMissing($field, $group = false) {
		$this->data['missing'][] = $this->getInputName($field, $group);
		$this->log(constant('LOG_USER_WARNING'), $this->lang('E_MISSING_INCORRECT') . ' <span class="missingField">' . $this->lang($this->getInputLangKey($field, $group)) . '</span>');
	}
	
	public function validate($value, $type) {
		$method = 'validate' . ucfirst($type);
		if (method_exists($this, $method)) return $this->$method($value);
		
		$this->log(constant('LOG_SYSTEM_WARNING'), $this->lang('E_VALIDATION_METHOD_NOT_FOUND') . ' <em>' . $type . '</em>');
		return false;
	}
	
	public function validateEmail($email) {
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
		$atIndex = strrpos($email, "@");
		if (is_bool($atIndex) && !$atIndex) return false;
		else {
			$domain = substr($email, $atIndex+1);
			$local = substr($email, 0, $atIndex);
			$localLen = strlen($local);
			$domainLen = strlen($domain);
			if ($localLen < 1 || $localLen > 64) return false;
			else if ($domainLen < 1 || $domainLen > 255) return false;
			else if ($local[0] == '.' || $local[$localLen-1] == '.') return false;
			else if (preg_match('/\\.\\./', $local)) return false;
			else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) return false;
			else if (preg_match('/\\.\\./', $domain)) return false;
			else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))) {
	         // character not valid in local part unless 
	         // local part is quoted
				if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) return false;
			}
		}
		return true;
	}
	
	public function validateUrl($url) {
		return filter_var($url, FILTER_VALIDATE_URL);
	}
	
	public function validateDate($date) {
		return strtotime($date) > 0;
	}
	
	public function getInputName($name, $group = false) {
		if (is_array($group)) $group = request($group['group']);
		if ($group) return sprintf('data[%s][%s]', $group, $name);
		else return sprintf('data[%s]', $name);
	}
	
	public function getInputId($name, $group = false) {
		$name = str_replace(' ', '-', $name);
		if (is_array($group)) $group = request($group['group']);
		if ($group) return str_replace(' ', '-', $group) . ucfirst($name);
		else return $name;
	}
	
	public function getInputLangKey($name, $group = false) {
		return 'LABEL_' . $this->getLangKey($name, $group);
	}
	
	public function getLangKey($name, $group = false) {
		if (is_array($group)) $group = request($group['group']);
		if ($group) return strtoupper(sprintf('%s_%s', $group, $name));
		else return strtoupper($name);
	}
	
	public function getValue($name, $group = false) {
		if (is_array($group)) $group = request($group['group']);
		//var_dump($group, $name, $this->data);
		return $group ? request($this->data['data'][$group][$name]) : request($this->data['data'][$name]);
	}
	
	
	public function handleUpload($name, $uploadFolder, $acceptFiles) {
		$uploader = $this->tool('uploader');
		$uploader->setExtensions($acceptFiles);
		$uploader->setUploadFolder($uploadFolder);
		
		if ($uploader->captureUpload($name)) {
			return $uploader->successful[0];
		} else $this->logL(constant('LOG_SYSTEM_WARNING'), "FILE_UPLOAD_FAILED");
	}
	
	/**
	 * emailSend() Sends an email
	 *
	 * @param string $subject
	 * @param boolean $save whether to save the email in ewyse
	 * @param string $message the email
	 * @param string $postMessage the message shown at failure and success
	 * @param mixed $to when an address is assigned the email will be sent there. When false is assigned the email will be sent to the default email address from ewyse
	 */
	private function emailSend($subject, $save, $message, $to) {
		$mailer = $this->pageBuilder->getTool('mailer');

		$params= array(
			'subject' => $subject,
			'content' => $message
		);
			
		if ($to!= false) $params['to']= $to;
		if ($save) $params['folder'] = $this->data['name'];

		//attach uploads to email:
		$files = $mailer->attachUploads($this->uploadFolder);

		// Send the email! Woot! The To and From are added from the preferences
		if ($mailer->send($params)) {
			$this->data['message'] = $this->postMessage['success'];
			$this->data['reply'] = true;
		} else {
			$this->data['message'] = $this->postMessage['failure'];
			$this->data['error'] = $this->pageBuilder->log->history;
		}
		$mailer->reset();
	}
	
	
}

?>