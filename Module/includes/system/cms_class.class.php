<?php

/**
 * @author Yereth Jansen
 *
 */
abstract class CMS_Class {
	/**
	 * Stores the main model
	 * @var MainModel
	 */
	public $model;
	/**
	 * The clean name of the class
	 * @var string
	 */
	public $name;
	/**
	 * The class name
	 * @var string
	 */
	public $className;
	
	/**
	 * Constructs the class
	 * 
	 * @param MainModel $model Reference to the main model of the system
	 * @param string $className The name of the current class
	 * @param string $name The proper name of the module, tool, object, etc
	 * @param array $args The arguments to pass on to the CMS_Class::init() function
	 */
	public function __construct(MainModel $model, $className, $name, $args = array()) {
		$this->model = $model;
		$this->className = $className;
		$this->name = $name;
		$this->init($args);
	}
	
	/**
	 * Forces the child class to implement the init() function, which will be called when the class is constructed
	 * 
	 * @param array $args
	 */
	abstract function init($args);
	
	/**
	 * Gets a language string based on the currently selected language, if no language is given
	 * 
	 * @param string $name The name of the language string
	 * @param string $module The name of the module to retrieve the language string for
	 * @param string $lang The language to retrieve the string for. Defaults to the currently selected language
	 * @param string $disableEditable Disables the editable markup, in case the string is used inside other markup tags
	 * @param string $forceEditable Forces the editable markup
	 * 
	 * @see LangModel::get()
	 */
	public function lang($name = false, $module = false, $lang = false, $disableEditable = false, $forceEditable = false) {
		ifNot($module, $this->className);
		return $this->model->module('lang')->get($module, $name, $lang, $disableEditable, $forceEditable);
	}
	
	/**
	 * Returns a get argument by name. Using get args are unusual, so there's a different method for it. Post data is
	 * fed straight to the end methods
	 * 
	 * @param string|array $name
	 * @return boolean|mixed
	 */
	public function arg($name = NULL, $value = false) {
		if ($name === NULL) return $this->model->args;
		elseif (is_array($name)) return array_select_keys($name, $this->model->args);
		elseif (is_string($name)) {
			if ($value === NULL) unset($this->model->args[$name]);
			elseif ($value) $this->model->args[$name] = $value;
			elseif (array_key_exists($name, $this->model->args)) return $this->model->args[$name];
		}
		return false;
	}
	
	/**
	 * Gets or sets a preference setting, depending on whether a $value is given as parameter
	 * 
	 * @param string $name The name of the preference setting
	 * @param string $module The name of the module to retrieve the setting for
	 * @param string $value The value to set for the selected $name and $module
	 * 
	 * @return boolean|unknown
	 * 
	 * @see PreferencesModel::save()
	 * @see PreferencesModel::get()
	 */
	public function pref($name = false, $module = false, $value = NULL) {
		ifNot($module, $this->className);
		if ($value !== NULL) {
			if (!$name) return false;
			else return $this->model->module('preferences')->save($module, $name, $value);
		} else {
			$val = $this->model->module('preferences')->get($name, $module);
			return $val;
		}
	}
	
	/**
	 * Adds a log entry
	 * 
	 * @param int $priority The log priority. Use the constants from LogTools
	 * @param string $message The message to add to the log
	 * 
	 * @see LogModel::add()
	 */
	public function log($priority, $message) {
		$this->model->tool('log')->add($priority, $message, $this->name);
	}
	
	/**
	 * Adds a log entry using a language string.
	 * 
	 * @param int $priority The log priority. Use the constants from LogModel
	 * @param string $langKey The language key to use
	 * @param string $langModule The name of the module the language string belongs to
	 * @param string $module The name of the module the log entry belongs to
	 * 
	 * @see LogModel::addL()
	 * @see LangModel::get()
	 */
	public function logL($priority, $langKey, $langModule = false, $module = false) {
		ifNot($langModule, $this->className);
		ifNot($module, $this->name);
		$this->model->tool('log')->addL($priority, $langKey, $langModule, $module);
	}
	
	/**
	 * For internal class usage. Check if an internal class action is allowed
	 * 
	 * @param string $name
	 * @return bool
	 */
	public function allowed($name) {
		return $this->model->tool('security')->actionAllowed($this->name, $name, 'internal');
	}
	
	/**
	 * Fetch the model of a module
	 * @param string $name
	 * @return CMS_Class
	 */
	public function module($name) {
		return call_user_func_array(array($this->model, 'module'), func_get_args());
	}
	
	/**
	 * Initiate a system tool
	 * @param string $name
	 * @return CMS_Class
	 */
	public function tool($name) {
		return call_user_func_array(array($this->model, 'tool'), func_get_args());
	}
	
	/**
	 * Create a system object
	 * 
	 * @param string $name
	 * @return CMS_Object
	 */
	public function object($name) {
		return call_user_func_array(array($this->model, 'object'), func_get_args());
	}
	
	/**
	 * Returns the open database connection of the system.
	 * 
	 * @return DbTools
	 */
	public function db() {
		return $this->model->db;
	}
	
	/**
	 * Generate a url
	 * 
	 * @param array $args The arguments to use in the URL
	 * @param array $options Extra options to call the url
	 * @param bool $useCurrentPageArgs Whether to use the current page arguments
	 * 
	 * @see LinkerTools::prettifyURL()
	 */
	public function url($args = array(), $options = array(), $useCurrentPageArgs = false) {
		return $this->model->tool('linker')->prettifyURL($args, $options, $useCurrentPageArgs);
	}
	
	public function fieldValues($fieldname, $module = NULL, $langEditable = false, $full = false) {
		ifNot($module, $this->className);
		if (is_numeric($fieldname)) $clauses = array('categoryField_id' => (int) $fieldname);
		else $clauses = array('module' => $module, 'field' => $fieldname);
		$values = $this->db()->query('fieldValues', $clauses, array('orderBy' => 'value'));
		$return = array();
		foreach($values as $value) {
			$value['langName'] = $this->langFieldValue($value['field'], $value['value'], $value['module'], $langEditable);
			if ($full) $return[] = $value;
			else $return[$value['value']] = $value['langName'];
		}
		return $return;
	}
	
	public function langFieldValue($fieldname, $value, $module = NULL, $langEditable = false) {
		ifNot($module, $this->className);
		return $this->lang(strtoupper($fieldname . '_' . str_replace(' ', '-', $value)), $module, false, !$langEditable);
	}
	
	public function langkeyFieldValue($fieldname, $value) {
		return strtoupper($fieldname . '_' . str_replace(' ', '-', $value));
	}
}

?>