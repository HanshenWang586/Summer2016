<?php

/**
 * @author Yereth Jansen
 *
 * Class which handles all security (login and security checks).
 */

class SecurityTools extends CMS_Class {
	/**
	 * The fields a user can login with.
	 * @var array
	 */
	static $loginFields = array('login','email');
	/**
	 * Stores the active user
	 * @var UserObject
	 */
	private $activeUser;
	
	public function init($args) {
		// Log off if the active user does not exist for some reason
		$user = $this->getActiveUser();
		if (!$user) $this->logoff();
	}

	/**
	 * Sets the given user as active. Use with care, we don't want to login a user that's not
	 * suppose to be logged in. The security check will normally take care of restricting users to
	 * do things they shouldn't be doing. ;)
	 * 
	 * @param UserObject $user The user object to login
	 * @param bool $noLoginForward Whether to forward to the earlier set URL, if it's set
	 */
	public function setActiveUser(UserObject $user, $noLoginForward = false) {
		if ($user->loaded) {
			$session = $this->tool('session');
			$session->set('user_id', $user->id, true);
			$this->activeUser = $user;
			// TODO: CHECK IF THE USER IS NOT INACTIVE
			// Check the account status
			$session->set('loggedIn', true);
			if (!$noLoginForward) $this->loginForward();
		} else $this->logL(constant('LOG_USER_ERROR'), 'E_USER_NOT_LOADED');
	}
	
	/**
	 * retrieves the currently active user. Returns false if there's no active user.
	 * 
	 * @return UserObject|boolean
	 */
	public function getActiveUser() {
		if ($this->activeUser) return $this->activeUser;
		elseif ($this->loggedIn() and $id = $this->tool('session')->get('user_id')) {
			if ($user = $this->object('user', $id)) {
				$user->data['active'] = true;
				if ($user) return $this->activeUser = $user;
			}
		}
		return false;
	}
	
	/**
	 * Returns a user object by $user_id
	 * 
	 * @param int $user_id
	 * @return UserObject|false
	 */
	public function getUser($user_id) {
		if (!is_numeric($user_id)) return false;
		else $user_id = (int) $user_id;
		// Return the active user if the requested user is the active user.
		if ($this->loggedIn() && $user_id == $this->tool('session')->get('user_id')) {
			return $this->getActiveUser();
		} else {
			$user = $this->object('user', $user_id);
			$user->data['active'] = false;
			return $user;
		}
	}
	
	/**
	 * Forwards to the page as set to forward to after login. Sometimes we want to call this function by hand,
	 * in case we wish to delay the forward.
	 */
	public function loginForward() {
	$session = $this->tool('session');
	if ($url = $session->get('loginForward')) {
			$session->delete('loginForward');
			$this->tool('linker')->loadURL($url);
		}
	}

	/**
	 * @param mixed $url The URL to forward to after login. This will be remembered until it is used (on login), unset or until it is overwritten by a second call.
	 * 			parameter should be a String with unencoded ampersands (ie '&' and not '&amp;'). If NULL given, the forward will be removed.
	 */
	public function setLoginForward($url = NULL) {
		if ($url === NULL) $this->tool('session')->unset('loginForward');
		elseif ($url && (is_string($url) || (is_array($url) && $url = $this->url($url)))) $this->tool('session')->set('loginForward', $url, true);
	}
	
	public function requestHTTPLogin() {
		$valid = isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']);
		if ($valid) {
			$valid = $this->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
		}
		if (!$valid) {
			header("WWW-Authenticate: Basic realm=\"Private login area\"");
			header("HTTP/1.0 401 Unauthorized");
			// TODO: USE THE ERRORPAGES?
			die('Not allowed');
		}
	}

	/**
	 * Returns whether the user is logged in. If $forceSession is set TRUE, it will check if the user is logged in
	 * by session. This can be used to make sure that the user confirms they are still the person stored in the
	 * cookie.
	 * 
	 * @param bool $forceSession
	 * @return boolean
	 */
	function loggedIn($forceSession = false) {
		return $this->tool('session')->get('loggedIn', !$forceSession) === true;
	}

	/**
	 * Logs off the user
	 */
	public function logoff(){
		$this->tool('session')->delete('loggedIn', false);
		$this->activeUser = NULL;
		if ($url = $this->tool('session')->get('logoffForward')) $this->tool('linker')->loadURL($url);
	}

	/**
	 * Decides whether a certain action on a certain module is allowed.
	 * TODO: No implementation yet!!!
	 * 
	 * @param string $module
	 * @param string $action
	 * @return bool whether the action is allowed
	 */
	public function actionAllowed($module, $action, $type = 'action') {
		$clauses = array('module' => $module, 'name' => $action, 'type' => $type);
		// Check if the action already exists in our database. If not, insert it, so we can set access control
		// for the action
		if (!$this->db()->query('actions', $clauses)) $this->db()->insert('actions', $clauses);
		return true;
	}
	
	/**
	 * This function should be called on all entries gotten from the database which can be secured in ewyse. The funcion will check if
	 * the current user has access to the selected items
	 *
	 * @param array		$data			The data coming from the database to check for security
	 * @param boolean	$contentMode	Content mode should only be true when the checked item is the content item (so not in a collection for instance),
	 * 									as when true, the security check will generate log messages.
	 * @return unknown_type
	 */
	public function securityCheck($data, $contentMode = false) {
		if ($this->model->disableSecurity) {
			$this->model->log->add(constant('LOG_SYSTEM_WARNING'), "<strong>Security:</strong> Security has been disabled! Please enable as soon as you can!");
			return true;
		}
		// If access is blocked, there's no access ANYWAY
		if (request($data['blockAccess']) == "true") {
			if ($contentMode) $this->model->log->add(constant('LOG_USER_ERROR'), "<strong>Security:</strong> This item is not accessable!");
			// If security is on, let's check if we have access
		} elseif (request($data['secured']) == "true") {
			if ($this->loggedIn()) {

				if ($groups = request($data['groupSecurity'])) {
					$groups = explode(',', $groups);
					if (is_array($contactGroups = request($_SESSION['contact']['groups']))) {
						$intersection = array_intersect($groups, $contactGroups);

						if (count($intersection) > 0) {
							return true;
						} else {
							if ($contentMode) $this->model->log->add(constant('LOG_USER_ERROR'), "<strong>Security:</strong> You are logged in, but have no access rights to this item.");
						}
					} else {
						if ($contentMode) $this->model->log->add(constant('LOG_USER_ERROR'), "<strong>Security:</strong> You are logged in, but have no access rights to this item.");
					}
				} else {
					return true;
				}
			} else {
				if ($contentMode) $this->model->log->add(constant('LOG_USER_ERROR'), "<strong>Security:</strong> You have to be logged in to access this item.");
			}
		} elseif(request($data['hideOnLogin']) == "true" && request($_SESSION['ingelogd'])) {
			$this->model->log->add(constant('LOG_SYSTEM_WARNING'), "<strong>Security:</strong> The item is not available when logged in.");
		} else {
			return true;
		}
		return false;
	}
}

?>