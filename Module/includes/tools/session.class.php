<?php

/**
 * @author Yereth Jansen
 *
 * This class does most of the session handling, including cookies
 */
class SessionTools extends CMS_Class {
	private $session;
	
	public function init($args) {
		if (!$this->model->userIsBot()) {
			$appName = $this->pref('appName', 'site');
			// Use a different session handler for php 5.4 and higher due to the change in interface
			$this->session = (version_compare(PHP_VERSION, '5.4.0') >= 0) ?
				new Session($this->model, $appName, 'session_data') :
				new SessionHandler($this->model, $appName, 'session_data');
		}
	}
	
	public function set($name, $value, $setCookie = false, $sessionOnly = false) {
	 	if ($this->session) $_SESSION[$name] = $value;
		else {
			//$this->logL(LOG_USER_ERROR, 'E_SESSION_NOT_STARTED');
			return false;
		}
	 	if ($setCookie and (is_string($value) || is_bool($value) || is_numeric($name))) setcookie($name, $value, $sessionOnly ? 0 : time()+60*60*24*30, $GLOBALS['URL']['relativeRoot']);
	}
	
	public function get($name, $noCookie = false) {
		if (!$this->session) {
			//$this->logL(LOG_USER_ERROR, 'E_SESSION_NOT_STARTED');
			return false;	
		}
		if (array_key_exists($name, $_SESSION)) return $_SESSION[$name];
		elseif(!$noCookie and array_key_exists($name, $_COOKIE)) return $_COOKIE[$name];
		return NULL;
	}
	
	public function delete($name, $noCookie = false) {
		if (!$this->session) {
			//$this->logL(LOG_USER_ERROR, 'E_SESSION_NOT_STARTED');
			return false;	
		}
		unset($_SESSION[$name]);
		if (!$noCookie) setcookie($name, "", time()-3600);
	}
}

?>