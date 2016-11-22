<?php
abstract class PrimordialForm {

	private $errors = array();
	protected $data = array();
	private $messages = array();
	private $success_message = '';
	
	public $args = array();
	
	public function __construct($args = array()) {
		$this->args = $args;
	}
	
	// to be overridden in classes that inherit from this
	abstract public function displayForm();

	// to be overridden in classes that inherit from this
	abstract public function processForm();

	public function display() {
		if ($this->success_message)
			return sprintf('<p class="pageWrapper infoMessage">%s</p>', $this->success_message);
		else
			return $this->displayForm();
	}
	
	public function addMessage($message) {
		$this->messages[] = $message;
	}
	
	public function displayMessages() {
		return HTMLHelper::wrapArrayInUl($this->messages, '', 'formMessages');
	}
	
	public function addError($error) {
		$this->errors[] = $error;
	}

	public function setData($textindexed_array) {
		$this->data = $textindexed_array;
	}

	public function setDatum($key, $value) {
		$this->data[$key] = $value;
	}

	public function setAction($action) {
		$this->action = $action;
	}

	public function setFromPage($from_page) {
		$this->from_page = $from_page;
	}

	public function setSuccessMessage($message) {
		$this->success_message = $message;
	}

	public function getErrorCount() {
		return count($this->errors);
	}

	public function displayErrors($prefix = '') {
		if ($this->getErrorCount())
			return $prefix . HTMLHelper::wrapArrayInUl($this->errors, '', 'message error');
	}

	public function getDatum($data_tag) {
		return $this->data[$data_tag];
	}

	public function getDataArray($data_tag_array) {
		foreach ($data_tag_array as $data_tag)
			$data_array[$data_tag] = $this->data[$data_tag];
			
		return $data_array;
	}

	public function getData() {
		return $this->data;
	}
}
?>