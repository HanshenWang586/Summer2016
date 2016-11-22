<?php
class Router {

	/**
	 * @var string The prefix of the default controller
	 */
	private $default_controller = 'home';

	/**
	 * @var string The default method
	 */
	private $default_method = 'index';
	
	/**
	 * @var string The default language 2-letter code
	 */
	private $default_language = 'en';
	
	public function __construct() {
		global $mobile;
		$mobile = false;
		$this->loadRouting();
	}

	/**
	 * Parses the URI to set $method and $controller
	 */
	private function loadRouting() {
		$host = $_SERVER['HTTP_HOST'];
		$current_url = $host . $_SERVER['REQUEST_URI'];
		if ($host == 'gokunming.com') $host = 'www.gokunming.com';
		if ($_SERVER['REQUEST_URI'] == '/') $_SERVER['REQUEST_URI'] = '/'.$this->default_language.'/';
		
		$new_url = $host . $_SERVER['REQUEST_URI'];
		if ($new_url != $current_url) HTTP::redirect('http://' . $new_url, 301);
		
		$url_trimmed_parts = array_values(array_filter(explode('/', array_get(parse_url($_SERVER['REQUEST_URI']), 'path'))));
		
		// determine controller, using default if not set
		if (!isset($url_trimmed_parts[1])) // if the second url spot isn't populated, use default controller
		{
			$controller = $this->default_controller;
			$this->method = $this->default_method;
		}
		else
			$controller = $url_trimmed_parts[1];
		
		// determine method, using index() if not set
		if (!isset($url_trimmed_parts[2])) // if the third url spot isn't populated, use default method
		{
			$this->method = $this->default_method;
			$this->remaining_segments = array();
		}
		else {
			if (!is_numeric($url_trimmed_parts[2])) {
				$this->method = $url_trimmed_parts[2];
				$this->remaining_segments = array_slice($url_trimmed_parts, 3);
			} else {
				$this->method = $this->default_method;
				$this->remaining_segments = array_slice($url_trimmed_parts, 2);
			}
		}
		$this->controller = ucfirst($controller).'Controller';
	}

	/**
	 * @return string The chosen controller
	 */
	public function getClass() {
		return $this->controller;
	}
	
	/**
	 * @return string The chosen method
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * @return array The remaining segments of the URI to feed to controller->method
	 */
	public function getRemainingSegments() {
		return $this->remaining_segments;
	}
}
?>
