<?php

class HtmlTools extends CMS_Class {
	public $includes;
	private $jsVars = array();
	private $execJS = array();
	private $headers = array();
	private $meta = array();
	private $enc = 'UTF-8';
	public $browser;

	public function init($args) {
		$this->resetIncludes();
	}
	
	public function encoding($enc) {
		$this->enc = $enc;
	}
	
	public function resetIncludes() {
		$this->includes = array('all' => array('js' => array(), 'css' => array()));
	}

	/**
	 * returns name of the browser or a boolean $whitch == browser
	 *
	 * @param ?? $which
	 * @return mixed
	 */
	public function browser($which = false) {
		if ($this->browser == NULL) {
			// Only check for browsers which need detection (because they suck):
			$string = strtolower($_SERVER['HTTP_USER_AGENT']);
			if (strpos('opera', $string) > -1) $this->browser = 'opera';
			elseif (strpos('msie', $string) > -1) {
				$val = explode(" ", strstr($string, "msie"));
				$version = (int) $val[1];
				if ($version == 8) $this->browser = 'ie8';
				elseif ($version == 7) $this->browser = 'ie7';
				elseif($version <= 6) $this->browser = 'ie6';
			} else $this->browser = false;
		}
		return $which ? $this->browser == $which : $this->browser;
	}
	/**
	 * Adds caching headers to a/the stack
	 *
	 * @param string $policy caching method (nocache, private or public)
	 * @param bool $revalidate must revalidate
	 */
	function cacheControl($policy, $revalidate = false) {
		$reval = $revalidate ? ", must-revalidate" : "";
		switch($policy) {
			case "nocache":
				$this->addHeader(array(
					"Cache-Control: no-store, no-cache" . $reval,
					"Pragma: no-cache",
					"Expires: Thu, 01 Jan 1970 00:00:01 GMT"
				));
				break;

			case "private":
				$this->addHeader(array(
					"Cache-Control: private"  . $reval,
					"Pragma: no-cache"
				));
				break;

			default:
				$this->addHeader("Cache-Control: public" . $reval);
				break;
		}
	}
	/**
	 * Adds a custom header to the stack.
	 *
	 * @param mixed[array|string] $mixed header string(s) to add
	 */
	function addHeader($mixed = false) {
		if (is_array($mixed)) $this->headers = array_merge($this->headers, $mixed);
		elseif($mixed) $this->headers[] = $mixed;
	}
	/**
	 * Sends the header stack to client.
	 *
	 */
	function sendHeaders() {
		if (headers_sent()) {
			$this->logL(constant('LOG_SYSTEM_ERROR'), 'E_HEADERS_ALREADY_SENT');
			return;
		}
		$this->addHeader('Vary: Accept');
		$this->addHeader(sprintf("Content-Type: text/html; charset=%s", $this->enc));
		foreach($this->headers as $header) header($header);
	}
	
	/**
	 * Prints a minimal of <head> related tags.
	 *
	 * @param array $options array( 'encoding' => ..., 'contentType' => ... 'docType' ...)
	 */
	function getHTMLHeader($pageTitle, $options = array()) {
		$html = '';
		$html .= "<!DOCTYPE html>\n";
		$html .= 
"<!--+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  +                          Built by yereth.nl                       +
  ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++-->
";
		$html .= sprintf("<html lang=\"%s\">\n", $this->model->lang);
		$html .= sprintf("<head>\n");
		$this->addMeta('charset', $this->enc);
		$this->addMeta('X-UA-Compatible', 'chrome=1', 'http-equiv');
		$html .= $this->sprintMeta();
		// Only add base URL in other browsers than IE (when in adminMode), cause IE sucks
		if (strpos($this->browser(), 'ie') === false) $html .= sprintf("\t<base href=\"%s\">\n", $GLOBALS['URL']['root']);
		$html .= sprintf("\t<title>%s</title>\n", $pageTitle);
		$html .= $this->sprintCSSIncludes();
		if (request($options['favicon'])) $html .= sprintf("\t<link rel=\"shortcut icon\" href=\"%sfavicon.ico\" type=\"image/vnd.microsoft.icon\">\n", $this->model->urls['root']);
		$html .= "</head>\n<body>\n";
		return $html;
	}

	function getHTMLFooter() {
		$html = '';
		if ($this->model->debug()) {
			$html .= $this->model->module('log')->sprintLog();
			if ($this->db()) $html .= sprint_rf($this->db()->getInfo());
		}
		$html .= $this->sprintJSIncludes() . $this->sprintJS();
		$html .= "\t<!--[if IE]><script type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/chrome-frame/1/CFInstall.min.js\"></script><![endif]-->\n";
		
		$html .= "</body>\n</html>";
		return $html;
	}

	/**
	 * add a meta tag to the stack
	 *
	 * @param string $name
	 * @param string $content
	 */
	function addMeta($name, $content, $type = 'name') {
		$this->meta[] = array($type, $name, $content);
	}
	/**
	 * Returns the metatag stack as a String
	 *
	 * @return string
	 */
	function sprintMeta() {
		$return = '';
		foreach ($this->meta as $value) {
			$return .= sprintf("\t<meta %s=\"%s\" content=\"%s\">\n", $value[0], makeTagEntities($value[1]), makeTagEntities($value[2]));
		}
		return $return;
	}
	/**
	 * Adds a javascript variable to the stack.
	 *
	 * @param string $name
	 * @param mixed [array|int|string] $value
	 */
	function addJSVariable($name, $value) {
		$this->jsVars[$name] = $value;
	}

	function execJS($js) {
		$this->execJS[] = $js;
	}

	function sprintJS() {
		$this->addJSVariable('CMS_URL', $this->model->urls);
		$return = "\t<script type=\"text/javascript\">\n";
		if ($this->jsVars) foreach($this->jsVars as $name => $value) {
			if (is_string($value) && $value[0] == '!') $value = substr($value, 1);
			else $value = json_encode($value);
			if ($value) $return .= sprintf("\t\tvar %s = %s;\n", $name, makeSafeEntities($value));
		}
		if ($this->execJS) foreach ($this->execJS as $js) {
			$return .= sprintf("\t\t%s\n", $js);
		}
		$return .=  "\t</script>\n";
		return $return;
	}

	function addCSS($name, $url = false, $args = array(), $browser = 'all') {
		if (is_array($name)) {
			$browser = $url ? $url : 'all';
			foreach($name as $n => $css) {
				$url = request($css['url']); unset($css['url']);
				$this->addCSS($n, $url, $args, $browser);
			}
		} else {
			if (strpos($url, 'http') === false) $url = $this->model->urls['root'] . $url;
			$args = array_merge(array('url' => $url, 'media' => 'all', 'combine' => true), $args);
			$this->includes[$browser]['css'][$name] = $args;
		}
	}

	// Add either an URL or an array of URLs. If adding an array, you can add a second parameter "path",
	// which will be prepended to all the urls in the array
	function addJS($mixed, $path = '', $browser = 'all', $prepend = false, $combine = true) {
		if (is_array($mixed)) {
			$count = count($mixed);
			for ($i = 0; $i < $count; $i++) {
				$js = $path . $mixed[$i];
				$this->addJS($js, false, $browser, $prepend);
			}
		} elseif (!in_array($path . $mixed, $this->includes[$browser]['js'])) {
			if ($prepend) array_unshift($this->includes[$browser]['js'], $path . $mixed); 
			else $this->includes[$browser]['js'][] = $path . $mixed;
		}
	}

	function sprintCSSIncludes() {
		if (!isset($this->includes['all']['css'])) return false;
		$return = "\n";
		if ($this->pref('combineCSS') == 'true')
			$this->combine('css',
				array(
					'minify'	=> $this->pref('minifyCSS') == 'true',
					'cachePath'	=> 'css/'
				)
			);
		
			$targets = array('all', 'page');

		if ($br = $this->browser()) $targets[] = $br;
		foreach ($targets as $target) if (isset($this->includes[$target]['css'])) foreach ($this->includes[$target]['css'] as $name => $array) {
			$return .= sprintf("\t<link type=\"text/css\" rel=\"stylesheet\" id=\"%s\" media=\"%s\" href=\"%s\">\n", $name, $array['media'], $array['url']);
		}
		return $return;
	}

	function sprintJSIncludes() {
		$targets = array('all');
		// In admin mode we only include our CMS JS
		if (!$this->includes['all']['js']) return false;
		$return = "\n";
		if ($this->pref('combineJS') == 'true') {
			$this->combine('js',
				array(
					'minify'	=> $this->pref('minifyJS') == 'true',
					'cachePath'	=> 'js/cache/',
					'fileSep'	=> ";" . PHP_EOL . PHP_EOL
				)
			);
		}
		if ($br = $this->browser()) $targets[] = $br;
		// This is page specific JS that's too much to globally include
		$targets[] = 'page';
		foreach ($targets as $target) {
			if (isset($this->includes[$target]['js'])) {
				$files = $this->includes[$target]['js'];
				$count = count($files);
				for ($i = 0; $i < $count; $i++) {
					$return .= sprintf("\t<script type=\"text/javascript\" src=\"%s\"></script>\n", $files[$i]);
				}
			}
		}
		return $return;
	}

	function sprintIncludes() {
		return $this->sprintCSSIncludes() . $this->sprintJSIncludes();
	}

	function combine($type, $options = array()) {
		global $rootURL, $rootPath;
		$cachePath = returnNonEmpty($options['cachePath'], 'cache/');
		$cacheFolder = $rootPath . $cachePath;
		is_dir($cacheFolder) || @mkdir($cacheFolder, 0755, true);
		if (!is_writable($cacheFolder)) return;
		$files = array();
		$result = array();
		$date = 0;

		foreach($this->includes['all'][$type] as $item) {
			// only process general stylesheets, not stylesheets for different media
			if (is_array($item) and $media = request($item['media']) and $media != 'all') $result[] = $item;
			else {
				$url = $type == 'css' ? $item['url'] : $item;
				$path = ($pos = strpos($url, $rootURL)) === 0 ? substr($url, $pos + strlen($rootURL)) : $url;
				if (strpos($path, 'http') === false and file_exists($rootPath . $path)) {
					$date = max($date, filemtime($rootPath . $path));
					$files[] = $rootPath . $path;
				} else $result[] = $item;
			}
		}
		// if we didn't find any files to process, return
		if (!$files) return;

		$filePath = sprintf("%s%d.%s", $cachePath, $date, $type);

		if (!file_exists($rootPath . $filePath)) {
			// Get file contents
			//$content = '';
			// !!! SEPERATOR IS OUT OF USE RIGHT NOW.... use clean code and the files will concat cleanly without problems :)
			//$sep = returnNonEmpty($options['fileSep'], PHP_EOL . PHP_EOL);

			// Changed command to unix cat instead of a php method. Should save speed and memory
			exec(sprintf('cat %s > %s', array_implode_map(' ', $files, 'escapeshellarg'), $rootPath . $filePath));

			//foreach($files as $file) $content .= $sep . file_get_contents($file);
			//echo $content;
			//file_put_contents($rootPath . $filePath, $content);
		}
		if (!file_exists($rootPath . $filePath)) return false;
		if ($options['minify']) {
			$minified = sprintf('%s%s-minified.%s', $cachePath, $date, $type);
			if (!file_exists($minified)) {
				global $pb_paths;
				$output = array();
				exec(sprintf('java -jar %syuicompressor-2.4.2.jar --type %s %s -o %s 2>&1', $pb_paths['js'], $type, escapeshellarg($rootPath . $filePath), escapeshellarg($rootPath . $minified)), $output);
				if (file_exists($minified)) $filePath = $minified;
				else {
					// TODO: ERROR LOGGING!!!
				}
			} else $filePath = $minified;
		}

		$this->includes['all'][$type] = $result;
		if ($type == 'css') $this->addCSS('styles', $rootURL . $filePath);
		elseif ($type == 'js') $this->addJS($rootURL . $filePath);
	}

	public function getImageTag($image, $width = NULL, $height = NULL, array $options = array()) {
		if (is_array($width)) {
			$options = $width;
			$width = request($options['width']);
			$height = request($options['height']);
		}
		$params = is_array(request($options['params'])) ? $options['params'] : array();
		if ($url = $this->model->getTool('linker')->getImageLink($image, $width, $height, $params, $options)) {
			return sprintf("<img%s%s alt=\"%s\" title=\"\" src=\"%s\"%s>\n",
			isset($options['class']) ? sprintf(" class=\"%s\"", $options['class']) : '',
			isset($options['id']) ? sprintf(" id=\"%s\"", $options['id']) : '',
			htmlspecialchars(returnNonEmpty($image['alt'], $image['title'], $image['short'])),
			$url,
			$xml ? ' /' : ''
			);
		} else return false;
	}
}

?>