<?

/**
 *	Simple helper class to contain functions which need the pagebuilder but are too generic to be added to a more descriptive tools class
 */
class LinkerTools extends CMS_Class {
	// Store the rewrite paths here, so we save on queries
	private $rewriteCache = array();
	
	public function init($args) {
		
	}
	
	/**
	 *	Creates an Image url according to pagebuilder standards
	 */
	public function getImageLink($image, $width = NULL, $height = NULL, array $params = array(), $options = array()) {
		if (is_numeric($image) and $image > 0) $image = query('assets', false, $image, array('getFields' => 'id,name,title,alt,short'));
		if (!$image or !(request($image['id']) > 0)) return false;
		if (is_numeric($params)) $params = array('id' => $params);
		$params = array_merge($params, array(
				'm' => 'assets',
				'doc_id' => $image['id'],
				'name' => $image['name']
		));
		if ($width > 0) $params['width'] = $width;
		if ($height > 0) $params['height'] = $height;
		return $this->prettifyURL($params, $options);
	}

	// Parse pretty URLs
	public function parseGet(array $pageParams = array()) {
		if (isset($pageParams['rewrite'])) {
			// If someone is requesting a JS or CSS file and ended up here, it doesn't exist.
			// TODO: Create a proper error page
			if (in_array(strtolower(array_top(explode('.', $pageParams['rewrite']))), array('js','css','html'))) die('404');

			// Do we have a rewrite url?
			if ($rewrite = array_filter(explode('/', $pageParams['rewrite']), 'request')) {
				// Dirty hack to make sure the $rewrite array is properly indexed
				$rewrite = array_values($rewrite);
				// Get the action if it is set
				$last = count($rewrite) - 1;
				if ($rewrite[$last][0] === ' ') {
					$pageParams['action'] = substr($rewrite[$last], 1);
					unset($rewrite[$last]);
				}
				if (isset($rewrite[0])) {
					// Get the language
					if (strlen($rewrite[0]) == 2) {
						$pageParams['LANG'] = strtolower(array_shift($rewrite));
						// Module
						if (isset($rewrite[0])) {
							$pageParams['m'] = array_shift($rewrite);
							// View
							if (isset($rewrite[0])) {
								if (!is_numeric($rewrite[0])) $pageParams['view'] = array_shift($rewrite);
								if (isset($rewrite[0])) {
									$param = array_shift($rewrite);
									$pageParams['id'] = $param;
									$pageParams['name'] = array_shift($rewrite);
								}
							}
						}
					}
				}
			}
			unset($pageParams['rewrite']);
		}
		return array_filter($pageParams, 'request');
	}
	
	// Create pretty URLs
	public function prettifyURL($params = array(), $options = array(), $useCurrentPageArgs = false) {
		// Generate the navigation Path when we're not in adminMode
		if (!is_array($options)) $options = array();
		if (is_string($params)) $params = parseString($params);
		elseif(!is_array($params)) $params = array();
		if ($useCurrentPageArgs) {
			//$remove_keys = array('data', 'action', 'utm_source', 'utm_medium', 'utm_term', 'utm_content', 'utm_campaign');
			$remove_keys = array('data', 'action');
			$args = array_remove_keys($this->model->args, $remove_keys);
			$params = array_merge($args, $params);
		}
		if (request($params['action']) == 'getContent') unset($params['action']);
		if (!isset($params['LANG'])) $params['LANG'] = $this->model->lang;
		if (isset($options['extraParams']) && is_array($options['extraParams'])) $params = array_merge($params, $options['extraParams']);
		$path = $this->model->urls['root'];
		if ($params = array_filter($params, 'request')) {
			$addSlash = true;
			//ob_get_contents(); ob_get_clean();var_dump($params);die();
			// THE GLOBAL REWRITE LOGIC
			$_path = array($path, strtolower($params['LANG']));
			unset($params['LANG']);
			if (isset($params['m'])) {
				$_path[] = urlencode(mb_strtolower($params['m'], 'UTF-8'));
				unset($params['m']);
				if (isset($params['view'])) {
					$_path[] = urlencode(mb_strtolower($params['view'], 'UTF-8'));
					unset($params['view']);
				}
				if (isset($params['id'])) {
					$_path[] = is_numeric($params['id']) ? ((int) $params['id']) : urlencode(mb_strtolower($params['id'], 'UTF-8'));
					
					unset($params['id']);
					if (isset($params['name'])) {
						$addSlash = false;
						$content = strip_tags($params['name']);
						$content = str_replace(array(' ', '-'), array('_', '_'), strtolower($content));
						$content = preg_replace("/[^a-z0-9_]/", '', $content);
						$content = preg_replace("/_+/", '_', trim($content, '_'));
						$_path[] = urlencode(mb_strtolower($content, 'UTF-8'));
						unset($params['name']);
					}
				}
			}
			
			if (isset($params['action'])) {
				$addSlash = false;
				$_path[] = '+' . urlencode($params['action']);
				unset($params['action']);
			}
			$path = implode('/', $_path);
			if ($addSlash) $path .= '/';
			// END GLOBAL REWRITE LOGIC
			// OLD REWRITE LOGIC
			if (false) {
				if (request($params['id']) > 0) {
					$path = $this->model->urls['root'] . $this->getRewritePath($params['id']);
					unset($params['id']);
				} else {
					$path = $this->model->urls['rewriteRoot'];
				}
	
				// Make the date more pretty, when it's set
				if (isset($params['year'], $params['month'], $params['day'])) {
					if ($path[strlen($path) - 1] != '/') $path .= '/';
					$path .= sprintf('%d/%d/%d', $params['year'], $params['month'], $params['day']);
					unset($params['year'], $params['month'], $params['day']);
				}
	
				// paths to module items
				if ($m = request($params['m']) and $doc_id = (int) request($params['doc_id'])) {
					$name = request($params['name']);
					if (!$name) $name = query($m, false, $doc_id, array('selectField' => 'name'));
					if ($path[strlen($path) - 1] != '/') $path .= '/';
					$path = sprintf('%s_%s%d/%s', $m == 'assets' ? $this->model->urls['root'] : $path, $m == 'documents' ? '' : urlencode(mb_strtolower($m, 'UTF-8')) . '/', $doc_id, urlencode(mb_strtolower($name, 'UTF-8')));
					unset($params['m'], $params['doc_id'], $params['name']);
				}
			}
		}
		// What's left of our GET params we'll just add to the URL, but clean out the parameters which are empty
		$params = is_array($params) ? array_filter($params, 'request') : array();
		
		$url = $params ? (isset($options['amp']) ? $this->makeURL($path, $params, $options['amp']) : $this->makeURL($path, $params)) : $path;
		
		if (array_key_exists('hash', $options) and $options['hash']) $url .= '#' . $options['hash'];
		
		return $url;
	}

	public function loadURL($url, $args = false, $type = false) {
		if (request($this->model->options['noRedirect']) or headers_sent()) return false;
		if (!$url and !$args) return false;
		if (($url[0] == '?' or !$url)) {
			$url = $this->prettifyURL($url, array('extraParams' => $args));
			$args = false;
		}
		if ($args) $url = $this->makeURL($url, $args, "&");
		if ($type == 301) header('HTTP/1.1 301 Moved Permanently');
		header("location: " . $url);
		exit();
	}

	public function makeURL($url = NULL, $args = false, $amp = '&amp;') {
		$search  = array('%2F', '%3A', ' ');
		$replace = array('/', ':', '+');
		if (!$url) {
			$url = $this->model->urls['root'];
			if ($args === false) $args = $_GET;
		}
		// Prettify the url a bit by replacing elements which can be shown fine
		$url = str_replace($search, $replace, $url);
		if ($args) {
			$url .= (strpos($url, '?') > -1 ? $amp : "?");
			$url .= is_string($args) ? $args : http_build_query($args, false, $amp);
		}
		return $url;
	}
}

?>