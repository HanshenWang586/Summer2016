<?php

abstract class CMS_Model extends CMS_Class {
	public $relPath;
	public $paths = array();
	public $urls = array();
	
	public function __construct(MainModel $model, $className, $name, $args = array()) {
		$this->relPath = 'includes/modules/' . $name . '/';
		$this->paths['root'] = $model->paths['root'] . $this->relPath;
		$this->paths['cms'] = $model->paths['root'] . 'cms/modules/' . $name . '/';
		$this->urls['root'] = $model->urls['root'] . '/' . $this->relPath;
		if (array_key_exists('lang', $model->urls)) $this->urls['lang'] = $model->urls['lang'] . 'modules/' . $name . '/';
		$this->urls['cms'] = $model->urls['root'] . '/cms/modules/' . $name . '/';
		parent::__construct($model, $className, $name, $args);
	}
	
	/**
	 * Tells if an action is a proper executable action in the current module
	 * 
	 * @param string $action
	 * @return boolean
	 */
	public function isAction($action) {
		return
			$action == 'getContent' or 
			(
				$actions = request($this->actions) and
				is_array($actions) and
				in_array($action, array_keys($actions))
				and method_exists($this, $action)
			);
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
		if (!is_array($args)) $args = array();
		if (!array_key_exists('m', $args)) $args['m'] = $this->name;
		return $this->model->tool('linker')->prettifyURL($args, $options, $useCurrentPageArgs);
	}
	
	public function view($name, $options = array()) {
		if (!$this->tool('security')->allowed($this->name, $name, 'view')) {
			$this->module('log')->addL(constant('LOG_SYSTEM_WARNING'), 'E_VIEW_NOT_ALLOWED', 'mainModel');
			return false;
		}
		$method = '_' . $name;
		if (method_exists($this, $method)) return $this->$method($options);
		else {
			$className = ucfirst($name) . 'View';
			$classPath = $this->paths['root'] . 'views/' . $name . '.class.php';
			if (file_exists($classPath)) {
				include_once($classPath);
				if (!$this->model->state('get') and $className::$browserAccess == false and $this->model->args['view'] == $name) {
					$this->logL(constant('LOG_SYSTEM_ERROR'), 'E_VIEW_NOT_DIRECTLY_ACCESSIBLE');
					return false;
				}
				$view = $this->model->classFactory($classPath, $className, $this->model, $this, $className, $name, $options);
				if ($view) {
					// If a method is defined as get, try to run the get method on the view
					$method = $this->model->state('get');
					ifNot($method, 'content');
					$method = 'get' . ucfirst($method);
					if (method_exists($view, $method)) return $view->$method($options);
				}
			}
		}
		$this->logL(constant('LOG_SYSTEM_WARNING'), 'E_VIEW_NOT_AVAILABLE', 'system');
		return false;
	}
	
	public function css($name, $folder = false) {
		ifNot($folder, $this->urls['root'] . 'css/');
		$this->model->tool('html')->addCSS($this->name . ucfirst($name) . 'CSS', $folder . $name . '.css', array('combine' => false), 'all');
	}
	
	public function js($name, $folder = false) {
		ifNot($folder, $this->urls['root'] . 'js/');
		$this->model->tool('html')->addJS($folder . $name . '.js', '', 'all', false, false);
	}
}

?>