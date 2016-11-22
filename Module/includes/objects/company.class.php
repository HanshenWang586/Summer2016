<?php

/**
 * @author Yereth Jansen
 *
 * This class does most of the session handling, including cookies
 */
class CompanyObject extends CMS_Object {
	/**
	 * Stores the contacts that are connected to the company
	 * @var array
	 */
	private $contacts;
	
	public function init($args) {
		$this->table = 'companies';
	}
	
	public function load($args) {
		if ($args) $row = $this->db()->query('companies', $args, array('singleResult' => true));
		else return false;
		if ($row) {
			$this->data['data'] = $row;
			$this->id = (int) $row['id'];
			$this->loaded = true;
			$this->loadFields();
			//$this->loadContacts();
			return true;
		} else $this->logL(constant('LOG_USER_WARNING'), 'E_COMPANY_NOT_LOADED');
		return false;
	}
	
	public function normaliseData($fields, $action) {
		if (array_key_exists('lang', $fields) && is_array($fields['lang'])) $fields['lang'] = implode(',', $fields['lang']);
		if (array_key_exists('city', $fields)) {
			if ($city = $this->tool('location')->getCities(false, true, $fields['city'])) {
				$fields['city_id'] = $city[0]['id'];
				unset($fields['city']);
			} else {
				$this->logL(constant('LOG_USER_ERROR'), 'E_COMPANY_INVALID_CITY');
				return false;
			}
		}
		if (array_key_exists('keywords', $fields) && is_array($fields['keywords'])) $fields['keywords'] = implode(',', $fields['keywords']);
		return $fields;
	}
	
	public function create(array $args) {
		return $this->_create($args);
	}
	
	/**
	 * Returns the uploader tool for a specific folder and set of extensions
	 * 
	 * @param string $folderName The name of the subfolder within the current company folder
	 * @param string|array $extensions List of allowed extensions
	 * @return UploaderTools
	 */
	private function getUploader($folderName = NULL, $extensions = 'jpg,jpeg,png,gif', $mimes = 'image/jpg,image/jpeg,image/gif,image/png,image/pjpeg') {
		$uploader = $this->tool('uploader');
		$uploader->setExtensions($extensions);
		$uploader->setMimetypes($mimes);
		if ($folderName) {
			$folder = $this->getFolder($folderName);
			if (!$uploader->setUploadFolder($folder)) {
				return false;
			}
		}
		return $uploader;
	}
	
	/**
	 * Sets the associated images of the company, and scales them down based on the preferences. Doesn't set the image
	 * captions
	 * 
	 * @param array $file Array in the form array(dbFieldName => filename)
	 * @return array|boolean
	 * 
	 * @see UploaderTools::exists()
	 * @see UploaderTools::captureUpload()
	 * @see ImageTools::resize()
	 */
	public function setImages(array $images, $zeroTolerance = false) {
		if ($uploader = $this->getUploader('images')) {
			$width = $this->pref('maxImageWidth');
			$height = $this->pref('maxImageHeight');
			ifNot($width, 800);
			ifNot($height, 800);
			$uploader->captureAllUploads($images);
			// If downloads failed, undo what's been done
			if ($zeroTolerance && $uploader->failed) {
				if ($uploader->successful) foreach($uploader->successful as $image) unlink($image['target']);
				return false;
			}
			// If we have successfull uploads, resize them
			$successful = array();
			if ($uploader->successful) foreach($uploader->successful as $image) {
				if (!$this->tool('image')->resize($image['target'], $width, $height, true)) {
					$this->logL(constant('LOG_USER_WARNING'), 'E_IMAGE_RESIZE_FAIL');
					unlink($image['target']);
					if ($zeroTolerance) {
						if ($successful) foreach($successful as $image) unlink($image['target']);
						return false;
					} 
				} else isset($image['fieldname']) ? $successful[$image['fieldname']] = $image : $successful[] = $image;
			}
			return $this->setData(array_transpose($successful, 'field', 'name')) ? $successful : false;
		}
		return false;
	}
	
	/**
	 * Set the company logo by filename or by uploaded image and removes the old logo.
	 * $file denotes the filepath when $isUpload is FALSE. When $isUpload is TRUE,
	 * it denotes the path used by UploaderTools to find uploads.
	 *
	 * @param string $file The filepath or upload name of the file.
	 * @param boolean $isUpload True if the file is a new upload
	 * 
	 * @return boolean Whether setting the logo succeeded
	 * 
	 * @see UploaderTools::captureUpload()
	 */
	public function setLogo($file, $isUpload = false) {
		if ($uploader = $this->getUploader('images')) {
			if ($isUpload ? $uploader->captureUpload($file) : $uploader->addFile($file)) {
				$file = $uploader->successful[0];
				if (!$this->tool('image')->resize($file['target'], 400, 400, true)) {
					$this->logL(constant('LOG_USER_WARNING'), 'E_IMAGE_RESIZE_FAIL');
					unlink($file['target']);
					return false;
				}
				$this->removeLogo();
				
				if ($this->setData(array('logo' => $file['name']))) {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Removes the company logo from the harddrive and database
	 */
	public function removeLogo() {
		if ($logo = $this->get('logo')) {
			$path = $this->getFolder('logo');
			if (file_exists($path . $logo)) {
				unlink($path . $logo);
			}
		}
	}
	
	/**
	 * Loads the extra fields into the CompanyObject, based on $lang. If $lang is not set, it defaults to the
	 * currently active language.
	 * 
	 * @param string $lang A 2 character ISO code string denoting the used language
	 * 
	 * @see MainModel::$lang
	 */
	public function loadFields($lang = false) {
		ifNot($lang, $this->model->lang);
		$this->data['langData'][$lang] = $this->db()->query('companyFields', array('company_id' => $this->id, 'lang' => $lang), array('transpose' => array('selectKey' => 'name', 'selectValue' => 'value')));
	}
	
	/**
	 * Sets language related fields to the CompanyObject and saves them to the database. Returns true when succesful.
	 * If $lang is not set, it defaults to the currently active language. If $lang is an array, $fields should be
	 * an array indexed by language codes, with $fields arrays as their values
	 * 
	 * @param array $fields The fields to save to the database.
	 * @param string|array $lang A 2 character ISO code string denoting the used language, or an array of language codes
	 * @return boolean
	 */
	public function setFields(array $fields, $lang = false) {
		// If lang is an array, loop through the different languages
		if (is_array($lang)) {
			foreach($lang as $l) {
				// Fail if the array is not set or when setting the fields fail
				if (!is_array(request($fields[$l])) || !$this->setFields($fields[$l], $l)) return false;
			}
			// If we get this far, we succeeded storing all the language data
			return true;
		}
		// Normalise the data
		$fields = $this->normaliseData($fields, 'setFields');
		// If the normalised fields failed, we should not store
		if (!$fields) return;
		// If there's no language set, we default to the selected language
		ifNot($lang, $this->model->lang);
		// If the language array is not set yet, attempt to load existing fields
		if (!isset($this->data['langData'][$lang])) $this->loadFields($lang);
		// Merge the new data with the old
		$this->data['langData'][$lang] = array_merge($this->data['langData'][$lang], $fields);
		// Now save the fields of the company
		return $this->saveFields($lang);
	}
	
	/**
	 * Saves the set language fields to the database.
	 * 
	 * @param string|array string|array $lang A 2 character ISO code string denoting the used language, or an array of language codes
	 * 
	 * @return boolean
	 */
	private function saveFields($lang = NULL) {
		$rows = array();
		// Make an array of the language if it is set
		if ($lang && !is_array($lang)) $lang = (array) $lang;
		// If languages are set, only save those languages (for optimisation
		$data = $lang ? array_select_keys($lang, $this->data['langData']) : $this->data['langData'];
		// Loop through the data, creating the appropriate data for the DB
		foreach($data as $lang => $fields) {
			foreach ($fields as $name => $value) {
				$rows[] = array('company_id' => $this->id, 'name' => $name, 'lang' => $lang, 'value' => $value);
			}
		}
		// Insert into DB
		return $this->db()->insert('companyFields', $rows, array('update' => 'value=VALUES(value)'));
	}
	
	/**
	 * Gets the folder path used for the currently active company to save files. When $name is set,
	 * it will be the subfolder in the path;
	 * 
	 * @param string $name Name of the subfolder.
	 * @return string Path to the folder
	 */
	public function getFolder($name = '') {
		$folder = $this->model->paths['root'] . 'userdata' . DIRECTORY_SEPARATOR . 'companies' . DIRECTORY_SEPARATOR . $this->id . DIRECTORY_SEPARATOR;
		if ($name) $folder .= $name . DIRECTORY_SEPARATOR;
		return $folder;
	}
	
	
	/**
	 * !!!! CONTACTS DISABLED 
	 *
	 * Loads the list of contacts by id into the data. Get a contact object by using the id with CompanyObject->getContact()
	 * 
	 * @see CompanyObject::getContact()
	 *
	private function loadContacts() {
		if ($this->loaded) $this->data['contacts'] = $this->db()->query('companiesContacts', array('company_id' => $this->id), array('transpose' => 'contact_id'));
	}
	
	/**
	 * Adds a contact to the company, based on the ID of the contact.
	 * 
	 * @param int $contact_id
	 * @return boolean
	 *
	public function addContact($contact_id) {
		if ($this->loaded) {
			$result = $this->db()->insert('companiesContacts', array('company_id' => $this->id, 'contact_id' => (int) $contact_id));
			$this->loadContacts();
			return $result;
		} else return false;
	}
	
	/**
	 * Returns a ContactObject if the contact is a member of the company, or fetches the first contact from the
	 * list of connected contacts if available
	 * 
	 * @param int $contact_id The contact id
	 * @return ContactObject
	 *
	public function getContact($contact_id = NULL) {
		if ($contact_id === NULL) {
			$contact_id = request($this->data['contacts'][0]);
		}
		if (is_numeric($contact_id)) {
			if (in_array($contact_id, $this->data['contacts'])) {
				if (!isset($this->contacts[$contact_id])) $this->contacts[$contact_id] = $this->object('contact', $contact_id);
				return $this->contacts[$contact_id];
			}
		}
		return false;
	}
	*/
}

?>