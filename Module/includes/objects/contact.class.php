<?php

/**
 * @author Yereth Jansen
 *
 * This class does most of the session handling, including cookies
 */
class ContactObject extends CMS_Object {
	public function init($args) {
		$this->table = 'contacts';
	}
	
	public function load($args) {
		if ($args) $row = $this->db()->query('contacts', $args, array('singleResult' => true));
		else return false;
		if ($row) {
			$this->data['data'] = $row;
			$this->id = (int) $row['id'];
			$this->loaded = true;
			return true;
		} else $this->logL(constant('LOG_USER_NOTICE'), 'E_CONTACT_NOT_LOADED');
		return false;
	}
	
	public function create(array $args) {
		$required = array('name', 'telephone', 'email');
		foreach($required as $field) {
			if (!request($args[$field])) {
				$this->logL(constant('LOG_USER_ERROR'), 'E_CREATE_CONTACT_MISSING_FIELD');
				return false;
			}
		}		
		
		return $this->_create($args);
	}
}

?>