<?php
/**
 * Example: 
 * <code>
 * <?php
 * $view = new View;
 * $view->setPath('template');
 * $view->setTag(<tag>, <value>);
 * $this->output = $view->getOutput();
 * ?>
 * </code>
 */
class View {
	/**
	 * @var bool Determines whether to strip off the first and last lines of the
	 * view's output. Useful for AJAXy stuff.
	 */
	private $strip = false;
	
	/**
	 * @var string The path to the view file
	 */
	private $path;
	
	/**
	 * @var string The original path to the views directory, before methods
	 * alter it. Yes, a bit hacky.
	 */
	private $original_path;
	
	private $_tags = array();
	
	private $cacheExpire = false;

	/**
	 * Sets the base view path, and then copies it into $original_path
	 */
	public function __construct($path = false, $cacheExpire = false, $cacheAppend = false) {
		$this->path = $_SERVER['DOCUMENT_ROOT'].'/includes/views/';
		$this->original_path = $this->path; // hacky
		
		if ($path) $this->setPath($path, false, $cacheExpire, $cacheAppend);
	}
	
	public function exists() {
		return file_exists($this->path);
	}

	/**
	 * Builds the output. Member variables (belonging to $this) in view files
	 * will be subbed in. If $strip == true, first and last lines are pulled off.
	 */
	private function buildOutput() {
		ob_start();
		include($this->path);
		$this->content = ob_get_clean();

		if ($this->strip) {
			$lines = explode("\n", trim($this->content));
			$this->content = implode("\n", array_slice($lines, 1, count($lines) - 2));
		}
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
		return $model->module('lang')->get($module, $name, $lang, $disableEditable, $forceEditable);
	}
	
	
	
	/**
	 * @return string The built view.
	 */
	public function getOutput($path = false, $clearTags = false) {
		if ($path) $this->setPath($path, $clearTags);
		$this->buildOutput();
		if ($this->cacheExpire) $this->saveCache();
		return $this->content;
	}

	/**
	 * Writes the view output to a cache path.
	 *
	 * @param string $path relative path to the cache file - gets concatenated
	 * onto the end of $original_path. TODO This can be improved.
	 */
	public function saveToCache($path) {
		$fp = fopen($this->original_path.$path, 'w');
		fputs($fp, $this->getOutput());
		fclose($fp);
	}

	/**
	 * Sets a tag by name/value pair.
	 *
	 * @param string $tag The tag name
	 * @param string $value The value of that tag
	 */
	public function setTag($tag, $value) {
		$this->_tags[] = $tag;
		$this->$tag = $value;
	}
	
	public function clearTags() {
		if ($this->_tags) foreach($this->_tags as $tag) unset($this->tag);
		$this->_tags = array();
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
		global $model;
		return $model->tool('linker')->prettifyURL($args, $options, $useCurrentPageArgs);
	}
	
	/**
	 * Sets the path for the view file. Is concatenated onto the base view path.
	 *
	 * @param string $path
	 */
	public function setPath($path, $clearTags = false, $cacheExpire = false, $cacheAppend = false) {
		$this->path = $this->original_path . $path;
		$this->cachePath = $this->path . '?' . $cacheAppend;
		if ($clearTags) $this->clearTags();
		$this->cacheExpire = $cacheExpire;
		if ($this->cacheExpire) return $this->getCache();
	}
	
	private function getCache() {
		if ($this->cachePath) return $GLOBALS['model']->tool('cache')->get('/templates/' . $this->cachePath);
	}
	
	private function saveCache() {
		if ($this->cachePath) return $GLOBALS['model']->tool('cache')->set('/templates/' . $this->cachePath, $this->content, $this->cacheExpire);
	}
	
	/**
	 * Sets the path for the view file. Replaces the base view path entirely.
	 *
	 * @param string $path
	 */
	public function replacePath($path) {
		$this->path = $path;
	}
	
	/**
	 * Sets the bool $strip to true (default is false).
	 */
	public function strip() {
		$this->strip = true;
	}
}