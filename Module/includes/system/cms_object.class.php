<?php

/**
 * The object abstract class is used for Objects that represent datatructures, like a datamapper.
 * 
 * @author Yereth Jansen
 */
abstract class CMS_Object extends CMS_Class {
	/**
	 * Set the main table name to be used here. If not set, some functions might not work
	 * @var string
	 */
	public $table;
	
	/**
	 * Stores the unique identifier (ID) of the loaded data
	 * @var int
	 */
	public $id;
	
	/**
	 * Stores the data the class represents, split into 'data' and 'langData', where 'langData' stores
	 * multilingual information, grouped by language (2 letter uppercase ISO code)
	 * 
	 * @var array
	 */
	public $data = array(
		'data' => array(),
		'langData' => array()
	);
	
	/**
	 * Classifies whether the class has successfully loaded its data
	 * @var boolean
	 */
	public $loaded = false;
	
	/**
	 * Creates a new CMS_Object and based on the $args perform an action.
	 * 
	 * @param MainModel $model The main model of the framework
	 * @param string $className The className of the main class  
	 * @param string $name The name of the class
	 * @param array $args The arguments to initialise the class with
	 * 
	 * @return CMS_Object|boolean
	 */
	public function __construct(MainModel $model, $className, $name, $args = array()) {
		parent::__construct($model, $className, $name, $args);
		if (is_numeric($args)) $this->load($args);
		elseif ($action = request($args['action'])) {
			if ($data = request($args['data'])) {
				if (method_exists($this, $action)) $this->$action($data);
			} else $this->logL(constant('LOG_USER_ERROR'), 'E_OBJECT_ACTION_NO_PARAMETERS');
		} else $this->logL(constant('LOG_USER_ERROR'), 'E_OBJECT_ACTION_NOT_AVAILABLE');
		if ($this->loaded) return $this;
		else return false;
	}
	
	/**
	 * Retrieves the data from the loaded DB data. Also searches the language dependant data of the currently active language
	 * 
	 * @param array|string $data Which data to get
	 * @param bool $getAssoc When an array of data is requested, if the returned array should be associative
	 * @param array $langData Only for internal usage
	 * 
	 * @return Ambigous bool|string|array
	 */
	public function get($data = false, $getAssoc = false, $lang = NULL, $langData = NULL) {
		if ($langData === NULL) {
			if (isset($lang)) {
				if (is_array($lang)) {
					$return = array();
					foreach($lang as $l) $return[$l] = $this->get($data, $getAssoc, $l);
					return $return;
				}
				if (!request($this->data['langData'][$lang]) && method_exists($this, 'loadFields')) $this->loadFields($lang);
			} else $lang = $this->model->lang;
			$langData = request($this->data['langData'][$lang]);
			$langData = is_array($langData) ? $langData : array();
		}
		if (is_array($data)) {
			$result = array();
			foreach($data as $key) $getAssoc ? $result[$key] = $this->get($key, false, $lang, $langData) : $result[] = $this->get($key, false, $lang, $langData);
			return $result;
		} elseif ($data) {
			if (array_key_exists($data, $this->data['data'])) return $this->data['data'][$data];
			elseif ($langData && array_key_exists($data, $langData)) return $langData[$data]; 
		} else {
			if ($langData) return array_merge($this->data['data'], $langData);
			else return $this->data['data'];
		}
		return false;
	}
	
	/**
	 * Sets extra data on the object, defined by $fields
	 * @param array $fields
	 * @return boolean
	 */
	public function setData($fields) {
		if (!$fields || !is_array($fields)) return true;
		if (!$this->table) {
			$this->logL(constant('LOG_SYSTEM_ERROR'), 'E_SETDATA_CALLED_NO_TABLE_SET');
			return false;
		}
		if (!$this->id) {
			$this->logL(constant('LOG_SYSTEM_ERROR'), 'E_SETDATA_CALLED_NO_DATA_ID_FOUND');
			return false;
		}
		
		if (method_exists($this, 'normaliseData')) {
			$fields = $this->normaliseData($fields, 'setData');
			if (!$fields) return false;
		}
		
		if (false !== $this->db()->update($this->table, $this->id, $fields)) {
			$this->data['data'] = array_merge_real($this->data['data'], $fields);
			return true;
		}
		return false;
	}
	
	/*
	public function normaliseData($fields, $action) {
		Process fields to be stored in DB
		$action defines which function calls the normalisation
		Return $fields if OK
		return false if data error
	}
	*/
	
	/**
	 * Creates an object with $fields and adds default Object fields (like date). Calls CMS_Object::load() if succeeded.
	 * Validation has to be done elsewhere (the function that calls the internal create, usually CMS_Object::create())
	 * 
	 * @param array $fields
	 * @return boolean
	 * 
	 * @see CMS_Object::load()
	 * @see CMS_Object::create()
	 */
	public function _create(array $fields) {
		if (!$this->table) {
			$this->logL(constant('LOG_SYSTEM_ERROR'), 'E_CREATE_CALLED_NO_TABLE_SET');
			return false;
		}
		$fields['date'] = date("Y-m-d H:i:s");
		
		if (method_exists($this, 'normaliseData')) {
			$fields = $this->normaliseData($fields, 'create');
			if (!$fields) return false;
		}
		
		if ($id = $this->db()->insert($this->table, $fields)) {
			return $this->load($id);
		}
		
		// If we failed?
		$this->logL(constant('LOG_USER_ERROR'), 'E_CREATE_OBJECT_FAILED');
		return false;
	}
	
	
	/**
	 * Should load the data from the database, based on $args, and store it in $this->data 
	 * @param mixed $args
	 */
	abstract function load($args);
	
	/**
	 * Should create a new entry after checking the $row data and call the load function afterwards to load up the new entry
	 * @param array $fields The data to insert into the database and load up
	 */
	abstract function create(array $fields);
	
}

?>