<?php

// Standard includes
// Get the configuration file
require_once('settings/config.php');
require_once('settings/db.php');

class MainModel {
	/**
	 * The currently active language, uppercase encoded with 2 letters
	 * @var string
	 */
	public $lang;
	
	/**
	 * A list of paths used by the MainModel::createClass() method to decide where to find classes
	 * @var array
	 */
	private $classPaths;
	
	/**
	 * The classCache is used to store classes constructed by the model.
	 * 
	 * @var array
	 */
	private $classCache = array();
	
	/**
	 * The current state of the module; for instance, which view (module) is active
	 * 
	 * @var array
	 */
	private $state = array();
	
	/**
	 * Stores the currently active Model class of the active view
	 * 
	 * @var CMS_Model
	 */
	public $currentModule;
	
	/**
	 * The framework template.. This template is used for the generic framework. Other templates may be used
	 * for specific parts of the page.
	 * 
	 * @var CMS_Class
	 */
	public $template;
	
	/**
	 * The arguments used to create the model
	 * 
	 * @var array
	 */
	public $args;
	
	/**
	 * Options to run the model with
	 * 
	 * @var array
	 */
	public $options;
	
	/**
	 * Database class used for website interaction
	 * 
	 * @var DbTools
	 */
	public $db;
	
	/**
	 * Mongo database connection
	 */
	private $mongo;
	
	/**
	 * An array of the supported output modes.
	 * @var array
	 */
	private $outputList = array('html','json','xml', 'vcard');
	
	/**
	 * Stores a list of useful paths within the system
	 * @var array
	 */
	public $paths;
	
	/**
	 * Stores a list of useful urls within the system 
	 * @var array
	 */
	public $urls;
	
	/**
	 * Initialises the main model. $args are merged with the $_GET arguments, where $args takes precedence.
	 * $options are further options to run with.
	 * 
	 * @param array $args
	 * @param array $options
	 */
	public function __construct(array $args = array(), array $options = array()) {
		$GLOBALS['model'] = $this;
		
		$this->options = $options;
		
		$this->paths = $GLOBALS['PATH'];
		$this->paths['cms'] = $this->paths['root'] . 'cms/modules/';
		
		$this->urls = $GLOBALS['URL'];
		$this->urls['cms'] = $this->urls['root'] . '/cms/modules/';
		
		
		
		// set the class paths to create new classes
		$this->classPaths = array(
			'model' => $this->paths['root'] . 'includes/modules/%s/model.class.php',
			'tools' => $this->paths['root'] . 'includes/tools/%s.class.php',
			'object' => $this->paths['root'] . 'includes/objects/%s.class.php',
			'template' => $this->paths['root'] . 'includes/templates/%s.class.php'
		);
		
		// Initiate standard modules
		$this->db();
		$this->tool('log');
		$this->tool('session');
		
		// Backwards (compatibility) shizzle
		$GLOBALS['site'] = new Site();
		// This sucks, but it's a compatibility hack
		$GLOBALS['user']->reloadData();
		
		// Use the $_GET arguments, possibly overwritten by arguments given to the constructor
		$get = $this->tool('linker')->parseGet($_GET); // Parse the rewrite path, if given
		
		$this->args = $args ? array_merge_recursive($get, $args) : $get;
		
		$lang = $this->module('lang')->getCurrentLanguage($get);
		if ((!array_key_exists('LANG', $this->args) or $lang != $this->args['LANG']) and !request($options['noRedirect'])) {
			$this->args['LANG'] = $lang;
			$this->tool('linker')->loadURL(false, $this->args);
		}
		// Some backwards compatibility
		$this->allowedLanguages = $this->module('lang')->allowedLanguages;
		$this->setLang($lang);
		
		$this->urls['langRoot'] = $this->tool('linker')->url();
		
		if ($template = $this->module('preferences')->get('defaultTemplate')) $this->template = $this->template($template);
		
		// Add css on initiation
		if (isset($options['css'])) $this->tool('html')->addCSS($options['css']);
		
		$this->state['view'] = request($this->args['view']);
		$this->state['get'] = request($this->args['get']);
		$this->state['args'] = request($this->args['args']);
		$this->state['output'] = isset($this->args['output']) && in_array($this->args['output'], $this->outputList) ? $this->args['output'] : 'html';
		// output and get is only used for deciding the output mode. They shouldn't be part of our arguments (used for
		// generating links and changing the way modules decide on their output)
		unset($this->args['output'], $this->args['get'], $this->args['args']);
		
		// If we're not logged in, that means we're logging in
		//if (!$this->tool('security')->loggedIn()) $this->state['module'] = 'login';
		if (isset($this->args['m'])) $this->state['module'] = $this->args['m'];
		elseif (isset($options['defaultModule'])) $this->state['module'] = $options['defaultModule'];  
		else $this->state['module'] = false;
		
		// Set the data for actions, views, etc. It may only be an array. Other data parameters are discarded
		if ($_POST) $this->args['data'] = $_POST;
		elseif (!array_key_exists('data', $this->args) || !is_array($this->args['data'])) $this->args['data'] = array();
		
		$this->setAction();
		
		// Initiate the current content module
		if ($this->state['module']) $this->currentModule = @$this->module($this->state['module'], $this->args);
		
		if ($this->currentModule && $this->state('action')) $this->state['result'] = $this->runAction($this->state['action'], request($this->args['data']));
	}
	
	/**
	 * Sets the output language of the CMS. Use with care!
	 * @param string $lang Should be an uppercase 2 character version of the selected language
	 */
	private function setLang($lang) {
		$this->args['LANG'] = $lang;
		$this->urls['lang'] = $this->urls['root'] . '/lang/' . strtolower($lang) . '/';
		$this->lang = $lang;
	}
	
	public function db() {
		if (!$this->db) {
			$this->db = $this->tool('db', $GLOBALS['db_data']);
		}
		return $this->db;
	}
	
	public function mongo() {
		if (!$this->mongo) {
			$db = new Mongo("mongodb://127.0.0.1");
			$this->mongo = $db->gokunming;
		}
		return $this->mongo;
	}
	
	public function userIsBot() {
		if (!isset($this->isBot)) {
			$this->isBot = preg_match('/wget|feed|robot|spider|bot|crawler|curl|^$/i', $_SERVER['HTTP_USER_AGENT']);
		}
		return $this->isBot;
	}
	
	public function getBrowserInfo() {
		if (is_null($this->browserInfo)) {
			if (isset($_SESSION['browserInfo'])) {
				$this->browserInfo = $_SESSION['browserInfo'];
			}
			else {
				$UASparser = new UASparser();
				$UASparser->SetCacheDir($GLOBALS['rootPath'] . "/includes/classes/UASparser/");
				$_SESSION['browserInfo'] = $this->browserInfo = $UASparser->Parse();
			}
		}
		return $this->browserInfo;
	}
	
	private function setAction() {
		// Set the action selected, $_POST actions take precedence.
		if (array_key_exists('action', $_POST)) $action = $_POST['action'];
		// Otherwise, the action might be part of the data array
		elseif (array_key_exists('data', $this->args['data']) && is_array($this->args['data']['data']) && array_key_exists('action', $this->args['data']['data'])) $action = $this->args['data']['data']['action'];
		// Otherwise, look in the $_GET or arguments for the action
		elseif (array_key_exists('action', $this->args)) {
			$action = $this->args['action'];
			unset($this->args['action']);
		}
		else $action = false;
		// Set the action
		$this->state['action'] = $action;
	}
	
	/**
	 * Runs an action on any module (based on $module OR the MainModel::currentModule) if the action is allowed by the current user.
	 * 
	 * @param string $action The name of the method to run as an action
	 * @param string $data The data to pass to the method as arguments
	 * @param string $module Optionally the module name
	 * @return mixed The result of the called method, if it was allowed to run
	 */
	public function runAction($action, $data, $module = false) {
		// Get the module to run the action on
		$module = $module ? (is_string($module) ? $this->module($module) : $module) : $this->currentModule;
		if ($module) {
			$name = $module->name;
			// Every module needs to define the available actions, except for "getContent" 
			if ($module->isAction($action)) {
				if ($this->tool('security')->actionAllowed($name, $action)) {
					return call_user_func(array($module, $action), $data);
				} else $this->module('log')->addL(constant('LOG_SYSTEM_WARNING'), 'E_ACTION_NOT_ALLOWED', 'mainModel');
			} else {
				$this->module('log')->addL(constant('LOG_SYSTEM_ERROR'), 'E_ACTION_NOT_AVAILABLE', 'mainModel');
			}
		} else $this->module('log')->addL(constant('LOG_SYSTEM_ERROR'), 'E_NO_MODULE_SELECTED', 'mainModel');
		return false;
	}
	
	/**
	 * Returns whether debug mode is on
	 * @return boolean
	 */
	public function debug() {
		return request($this->options['debug']);
	}
	
	public function state($key = false) {
		if ($key) {
			if (isset($this->state[$key])) return $this->state[$key];
			else $this->module('log')->addL(constant('LOG_SYSTEM_NOTIFY'), 'E_STATE_NOT_DEFINED', 'mainModel');
		} else return $this->state;
	}
	
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
		global $model;
		ifNot($module, 'interface');
		return $this->module('lang')->get($module, $name, $lang, $disableEditable, $forceEditable);
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
		return $this->tool('linker')->prettifyURL($args, $options, $useCurrentPageArgs);
	}
	
	/**
	 * Instantiates the model of a Module and returns it.
	 * 
	 * @param string $which
	 * @return CMS_Model
	 * @see MainModel::createClass()
	 */
	public function module($which) {
		$args = func_get_args();
		array_unshift($args, 'model');
		return call_user_func_array(array($this, 'createClass'), $args);
	}
	
	/**
	 * Creates a singular tool, if available, based on the name. The different Tools are available in the tools folder.
	 * This function is merely a convenience function to access the classes and keep only one instance.
	 * 
	 * @param string Name of the tool
	 * @return CMS_Class The requested tool, or false when not available
	 * @see MainModel::createClass()
	 */
	public function tool($which) {
		$args = func_get_args();
		array_unshift($args, 'tools');
		return call_user_func_array(array($this, 'createClass'), $args);
	}
	
	/**
	 * Creates an instance of an object, if available, based on the name. The different Objects are available in the objects folder.
	 * This function is merely a convenience function to access the classes.
	 * 
	 * @param string Name of the tool
	 * @return CMS_Object The requested object, or false when not available
	 * @see MainModel::createClass()
	 */
	public function object($which) {
		$args = func_get_args();
		array_unshift($args, true, 'object');
		$object = call_user_func_array(array($this, 'createClass'), $args);
		return $object && $object->loaded ? $object : false;
	}
	
	/**
	 * Convenience method to create a template
	 * @param string $which
	 * @return CMS_Class
	 * @see MainModel::createClass()
	 */
	public function template($which) {
		$args = func_get_args();
		array_unshift($args, 'template');
		return call_user_func_array(array($this, 'createClass'), $args);
	}
	
	/**
	 * Creates a class based on the parameters, which are all implicit. Uses the classFactory() method.
	 * 
	 * When the first parameter is TRUE, we don't cache the class.
	 * 
	 * The next parameter should be the Type of the class (ie. 'module', 'tool', 'template', 'object', etc)
	 * 
	 * The next parameter is the name of the class, without its type (ie. 'db' (tool), 'lang' (module), etc)
	 * 
	 * The next parameters will be passed as arguments to the class Constructor
	 * 
	 * @throws Exception
	 * @return boolean|CMS_Class
	 * @see MainModel::classFactory()
	 */
	public function createClass() {
		$args = func_get_args();
		$offset = 1;
		if ($args[0] === true) {
			$noCache = true;
			array_shift($args);
			$offset = 2;
		} else $noCache = false;
		$type = array_shift($args);
		$which = array_shift($args);
		$className = ucfirst($which) . ucfirst($type);
		
		if (!$noCache && $class = request($this->classCache[$className])) return $class;
		
		$classPath = sprintf($this->classPaths[$type], strtolower($which));
		
		if (file_exists($classPath)) {
			include_once($classPath);
			
			if (class_exists($className)) {
				// Add the model and the classname to the parameters, as that's what every CMS_Class expects
				array_unshift($args, $this, $className, $which);
				
				// Now create the class
				$rc = new ReflectionClass($className);
				$class = $rc->newInstanceArgs($args);
				if (!$noCache) $this->classCache[$className] = $class;
				return $class;
			} else $this->tool('log')->addL(constant('LOG_SYSTEM_CRITICAL'), 'E_CLASS_NOT_FOUND', 'mainModel'); 
		} else {
			// Ignore models that are not found for now
			if ($type != 'model') $this->tool('log')->addL(constant('LOG_SYSTEM_CRITICAL'), 'E_FILE_NOT_FOUND', 'mainModel');
		}
		return false;
	}
	
	/**
	 * Returns an instance of a class based on $classPath and $className
	 * @param string $classPath The full path to the class we're looking for
	 * @param string $className The full name of the class
	 * @return object|boolean
	 */
	public function classFactory($classPath, $className) {
		if (file_exists($classPath)) {
			include_once($classPath);
			if (class_exists($className)) {
				$args = func_num_args() > 2 ? array_slice(func_get_args(), 2) : array();
				// Now create the class
				$rc = new ReflectionClass($className);
				return $rc->newInstanceArgs($args);
			} else $this->tool('log')->addL(constant('LOG_SYSTEM_CRITICAL'), 'E_CLASS_NOT_FOUND', 'mainModel');
		} else {
			$this->tool('log')->addL(constant('LOG_SYSTEM_CRITICAL'), 'E_FILE_NOT_FOUND', 'mainModel');
		}
		return false;
	}
	
	/**
	 * Will retrieve general data available from the preferences of the system
	 * 
	 * @param string $which What data to retrieve
	 * @return string The information if available
	 */
	public function getData($which) {
		
	}
	
	/**
	 * Simply outputs the current state of the MainModel, based on its parameters. Output can be HTML, JSON, XML, etc
	 */
	public function outputContent() {
		$content = '';
		$data = is_array(request($this->args['data'])) ? $this->args['data'] : array();
		switch($this->state['output']) {
			// In case of HTML output we need to give the full load.. otherwise, just the requested data
			case 'html':
				$html = $this->tool('html');
				// Get the data to pass as arguments
				// Load a view if it is explicitly set.
				if ($this->currentModule) {
					if ($view = $this->state('view')) $content = $this->currentModule->view($view, $data);
					else $content = $this->runAction('getContent', $data);
					if (!$content) HTTP::throw404();
					// If we have a template as main template set, load the content in the template.
					if ($this->template) {
						$content = $this->template->getContent($content);
					} else {
						$page = new Page;
						$page->setTag('main', $content);
						$content = $page->output();
					}
				} else $this->classicOutput();
				// Ready for take off... send the headers, push the content
				$html->sendHeaders();
				$title = isset($this->options['title']) ? $this->options['title'] : $this->module('lang')->get('MainModel', 'HTML_HEADER');  
				// If we have a selected module, get the meta information belonging to it
				// TODO: More specific pagenames! 
				if ($this->currentModule) {
					$title = $this->module('lang')->get('interface', strtoupper($this->state['module']) . '_TITLE', false, true) . ' - ' . $title;
				}
			break;
			// If we requested json, we just want the result of the action we performed.
			case 'json':
				$output = array('result' => request($this->state['result']));
				if (($output['result'] || $this->state['action'] === false) and $this->state('get')) {
					if ($view = $this->state('view')) $output['content'] = $this->currentModule->view($view, request($this->state['args']));
					else $output['content'] = $this->runAction('get', request($this->state['args']));
				} else $output['content'] = $this->module('log')->sprintLog();
				// Do we want debug information? Give the query list, so we can see if anything went wrong
				//if ($this->debug() && $this->db) $output['debug']['db'] = $this->db->getInfo();
				JSONOut($output);
			break;
			default:
				if ($view = $this->state('view')) $content = $this->currentModule->view($view, $data);
			break;
		}
		echo $content;
	}
	
	private function classicOutput() {
		$url_trimmed_parts = array_values(array_filter(explode('/', array_get(parse_url($_SERVER['REQUEST_URI']), 'path'))));
		
		if (!isset($url_trimmed_parts[2]) and isset($_GET['view'])) $url_trimmed_parts[2] = $_GET['view'];
		
		// determine controller, using default if not set
		if (!isset($url_trimmed_parts[1])) $controller = 'home';
		else $controller = $url_trimmed_parts[1];
		
		// determine method, using index() if not set
		if (!isset($url_trimmed_parts[2])) // if the third url spot isn't populated, use default method
		{
			$method = 'index';
			$remaining_segments = array();
		}
		else {
			if (!is_numeric($url_trimmed_parts[2])) {
				$method = $url_trimmed_parts[2];
				$remaining_segments = array_slice($url_trimmed_parts, 3);
			} else {
				$method = 'index';
				$remaining_segments = array_slice($url_trimmed_parts, 2);
			}
		}
		$class = ucfirst($controller).'Controller';
		
		$method = str_replace('.php', '', $method);
		
		if (!class_exists($class) || !in_array(strtolower($method), array_map('strtolower', get_class_methods($class))))
			HTTP::throw404();
		else echo call_user_func_array(array(new $class, $method), $remaining_segments);
	}
}

?>