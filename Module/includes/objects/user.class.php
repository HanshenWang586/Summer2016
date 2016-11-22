<?php

/**
 * @author Yereth Jansen
 *
 * This class does most of the session handling, including cookies
 */
class UserObject extends CMS_Object {
	/**
	 * Stores possible signup information when the user is still in a signup process
	 * @var array
	 */
	private $signup;
	/**
	 * Stores possible ad creation information when the user is still in a process
	 * @var array
	 */
	private $createAd;
	/**
	 * Stores the companies the user is connected to
	 * @var array
	 */
	private $companies;
	
	public function init($args) {
		$this->table = 'users';
	}
	
	public function load($args) {
		if (is_array($args) && $password = request($args['password'])) unset($args['password']);
		$row = $this->db()->query('users', $args, array('singleResult' => true));
		if ($row) {
			if (!request($password) || $this->tool('hash')->check($row['password'], $password)) {
				$this->data['data'] = $row;
				$this->id = (int) $row['id'];
				$this->loaded = true;
				$this->loadCompanies();
				return true;
			}
		}
		return false;
	}
	
	public function normaliseData($fields, $action) {
		// Can't create an account with an already registered email address
		if (array_key_exists('email', $fields) and $this->get('email') !== $fields['email'] and $this->checkEmailExists($fields['email'])) {
			$this->logL(constant('LOG_USER_WARNING'), 'E_USER_EMAIL_EXISTS');
			return false;
		}
		
		// Password check
		if (array_key_exists('password1', $fields) || array_key_exists('password2', $fields)) {
			if (!request($fields['password1']) or request($fields['password1']) != request($fields['password2'])) {
				$this->logL(constant('LOG_USER_ERROR'), 'E_USER_PASSWORD_NOT_MATCH');
				return false;
			}
			// Set the password field
			$fields['password'] = $fields['password1'];
			unset($fields['password1'], $fields['password2']);
		}
		
		// Hash the password
		if (array_key_exists('password', $fields)) $fields['password'] = $this->tool('hash')->hash($fields['password']);
		return $fields;
	}
	
	public function create(array $args) {
		$required = array('email', 'password1', 'password2');
		foreach($required as $field) {
			if (!request($args[$field])) {
				$this->logL(constant('LOG_USER_ERROR'), 'E_CREATE_USER_MISSING_FIELD');
				return false;
			}
		}
		
		return $this->_create($args);
	}

	public function getCreateAd($module = false, $forceReload = false) {
		if (!$this->createAd or $forceReload and (int) $this->id > 0) {
			ifNot($module, $this->model->state('module'));
			$this->createAd = $this->db()->query('createad', array('user_id' => (int) $this->id, 'module' => $module), array('singleResult' => true));
		}
		return $this->createAd;
	}
	
	public function setCreateAd(array $data, $module = false) {
		if (!$this->id) return false;
		$data['user_id'] = $this->id;
		if (!$module && !array_key_exists('module', $data)) $module = $data['module'] = $this->model->state('module');
		if ($this->getCreateAd($module)) {
			unset($data['user_id']);
			$success = $this->db()->update('createad', array('user_id' => $this->id, 'module' => $module), $data);
		} else $success = $this->db()->insert('createad', $data);
		return $this->getCreateAd($module, true);
	}
	
	public function finaliseCreateAd() {
		$createAd = $this->getCreateAd();

		$this->db()->transaction();
		$result = $this->db()->update($createAd['module'], array('id' => $createAd['ad_id']), array('status' => 'pending'));
		if ($result) {
			if (!$createAd['company_id']) unset($createAd['company_id']);
			if (false !== $this->db()->delete('createad', $createAd)) {
				$this->db()->commit();
				return true;
			}
		}
		$this->db()->rollback();
		$this->logL(constant('LOG_USER_ERROR'), 'SIGN_UP_FINALISE_FAILED');
		return false;
	}
	
	public function getSignup($forceReload = false) {
		if (!$this->signup or $forceReload and (int) $this->id > 0) {
			$this->signup = $this->db()->query('signup', array('user_id' => (int) $this->id), array('singleResult' => true));
		}
		return $this->signup;
	}
	
	public function setSignup(array $data) {
		if (!$this->id) return false;
		$data['user_id'] = $this->id;
		if ($this->getSignup()) {
			unset($data['user_id']);
			$success = $this->db()->update('signup', array('user_id' => $this->id), $data);
		} else $success = $this->db()->insert('signup', $data);
		return $success !== false ? $this->getSignup(true) : false;
	}
	
	public function finaliseSignup() {
		$signup = $this->getSignup();
		$this->db()->transaction();
		if ($this->setData(array('status' => 'pending'))) {
			if ($signup['company_id'] && $company = $this->getCompany($signup['company_id'])) {
				if ($company->setData(array('status' => 'pending'))) {
					if ($this->db()->delete('signup', array('user_id' => $this->id))) {
						$this->db()->commit();
						return true;
					}
				}
			}  else {
				if ($this->db()->delete('signup', array('user_id' => $this->id))) {
					$this->db()->commit();
					return true;
				}
			}
		}
		$this->logL(constant('LOG_USER_ERROR'), 'SIGN_UP_FINALISE_FAILED');
		$this->db()->rollback();
		return false;
	}
	
	public function checkEmailExists($email) {
		return $this->db()->query('users', array('email' => $email), array('selectField' => 'email'));
	}
	
	public function getFolder($name = '') {
		$folder = $this->model->paths['root'] . 'userdata' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $this->get('email') . DIRECTORY_SEPARATOR;
		if ($name) $folder .= $name . DIRECTORY_SEPARATOR;
		return $folder;
	}
	
	public function getCompanyRole($company_id) {
		if ($this->data['companies'] and is_array($this->data['companies']) and array_key_exists($company_id, $this->data['companies'])) {
			return $this->data['companies'][$company_id]['role'];
		}
		return false;
	}
	
	/**
	 * Returns a CompanyObject if the current user is a member of the company, or fetches the first company from the
	 * list of connected companies if available
	 * 
	 * @param int $company_id The company id
	 * @return CompanyObject
	 */
	public function getCompany($company_id = NULL) {
		if (!$this->data['companies'] || !is_array($this->data['companies'])) return false;
		$ids = array_keys($this->data['companies']);
		if ($company_id === NULL) {
			$company_id = request($ids[0]);
		}
		if (is_numeric($company_id)) {
			if (in_array($company_id, $ids)) {
				if (!isset($this->companies[$company_id])) $this->companies[$company_id] = $this->object('company', $company_id);
				return $this->companies[$company_id];
			}
		}
		return false;
	}
	
	/**
	 * Loads the list of companies by id into the data. Get a company object by using the id with UserObject->getCompany()
	 * 
	 * @see UserObject::getCompany()
	 */
	private function loadCompanies() {
		if ($this->loaded) $this->data['companies'] = $this->db()->query('usersCompanies', array('user_id' => $this->id), array('transpose' => array('selectKey' => 'company_id', 'selectValue' => true)));
	} 
	
	/**
	 * Adds the current user to the company as a member and returns true when succeeded
	 * 
	 * @param int $company_id
	 * @param string $role The role the user plays in the company (also: position in the company)
	 * @return boolean
	 */
	public function addCompany($company_id, $role = NULL) {
		if ($this->loaded) {
			$data = array('user_id' => $this->id, 'company_id' => (int) $company_id);
			if ($role) $data['role'] = $role;
			$result = $this->db()->insert('usersCompanies', $data);
			$this->loadCompanies();
			return $result;
		} else return false;
	}
}

?>