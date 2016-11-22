<?php

class LangModel extends CMS_Model {
	/**
	 * The default language to use, uppercase encoded with 2 letters
	 * @var string
	 */
	public $defaultLang = 'en';
	/**
	 * The allowed languages in the system
	 * @var array
	 */
	public $allowedLanguages;
	/**
	 * The locale settings (languages and all that)
	 * @var array
	 */
	public $locale;
	/**
	 * The currently active language, uppercase encoded with 2 letters
	 * @var string
	 */
	public $lang;
	// Store retrieved language strings ordered by module
	private $langCache = array();
	
	// Required for each module
	public $actions = array('save' => false, 'getTable' => false, 'get' => false, 'remove' => false);
	
	public function init($args) {
		if ($GLOBALS['user']->getPower()) {
			$this->tool('html')->addJS($GLOBALS['URL']['root'] . '/js/jquery/jquery.jeditable.js');
			$this->js('general');
		}
		
		$this->locale = $this->db()->query('locale', false, array('transpose' => array('shortname', true)));
		$this->allowedLanguages = array_keys($this->locale);
		
		$default = strtolower($this->pref('defaultLanguage'));
		if (!$this->languageAllowed($default)) $default = 'en';
		$this->defaultLang = $default;
	}
	
	public function getCurrentLanguage($args = NULL) {
		if (!$this->lang) {
			ifNot($args, $this->model->args);
			if ($lang = request($args['LANG'])) {
			} elseif($lang = $this->tool('session')->get('LANG')) {
			} elseif($lang = $this->getUserLanguage()) {}
			
			if (!$lang or !$this->languageAllowed($lang)) $lang = $this->defaultLang;
			
			$lang = strtolower($lang);
			
			$this->lang = $lang;
			$this->tool('session')->set('LANG', $lang, true);
		}	
		return $this->lang;
	}
	
	public function languageAllowed($lang) {
		return in_array(strtolower($lang), $this->allowedLanguages);
	}
	
	public function getUserLanguage() {
		$langs = array();
		$lang = false;
		
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		    // break up string into pieces (languages and q factors)
		    preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);
		
		    if (count($lang_parse[1])) {
		        // create a list like "en" => 0.8
		        $langs = array_combine($lang_parse[1], $lang_parse[4]);
		    	
		        // set default to 1 for any without q factor
		        foreach ($langs as $lang => $val) {
		            if ($val === '') $langs[$lang] = 1;
		        }
		
		        // sort list based on value	
		        arsort($langs, SORT_NUMERIC);
		    }
		}
		
		// look through sorted list and use first one that matches our languages
		foreach ($langs as $lang => $val) {
			foreach ($this->allowedLanguages as $aLang) {
				if (strpos(strtoupper($lang), $aLang) === 0) {
					$lang = $aLang;
					break;
				}
			}
			if ($lang) break;
		}
		return strtolower($lang);
	}
	
	private function getValue($module, $name = false, $lang) {
		// If not retrieved yet, get the language array for the current module
		if (!request($this->langCache[$lang][$module])) {
			$this->langCache[$lang][$module] = $this->db()->query('lang', array('module' => $module, 'lang' => $lang), array('transpose' => array('selectKey' => 'name', 'selectValue' => 'value')));
		}
		
		// If no name is given, we want the whole array
		if ($name === false) return $this->langCache[$lang][$module];
		
		// Get the requested value
		$value = request($this->langCache[$lang][$module][$name]);
		
		// If there's no result, we will create an entry that can be edited later in the language editor
		if (!is_string($value)) {
			$this->db()->insert('lang', array('module' => $module, 'lang' => $lang, 'name' => $name));
			// To prevent we'll insert the same string twice one requested twice in a page
			$this->langCache[$lang][$module][$name] = '';
		}
		return $value;
	}
		
	public function save($m, $name = NULL, $lang = NULL, $value = NULL) {
		if (is_array($m)) {
			$data = $m;
			foreach(array('m','name','lang','value') as $key) {
				if (!$$key = request($data[$key])) return false;
			}
		} elseif(!isset($name, $lang, $value)) return false;
		
		if (!trim($name)) return false;
		
		// Update our cache
		if (request($this->cache[$lang][$m])) $this->cache[$lang][$m][$name] = $value;
		
		$row = array('module' => $m, 'lang' => $lang, 'name' => $name, 'value' => $value);
		return $this->db()->insert('lang', $row, array('update' => 'value=VALUES(value)'));
	}
	
	public function get($module, $name, $lang = false, $disableEditable = false, $forceEditable = false) {
		// Normally we want to use the default language
		if (!$lang) $lang = $this->getCurrentLanguage();
		if (!$value = $this->getValue($module, $name, $lang)) {
			if ($lang != $this->defaultLang) {
				$result = $this->getValue($module, $name, $this->defaultLang);
				$value = $result ? $result : $name;
			} else $value = $name;
			if (!$disableEditable) $value = $this->getEditable($module, $name, $value, $lang);
		} elseif ($forceEditable) $value = $this->getEditable($module, $name, $value, $lang);
		return $value;
	}
	
	public function remove($data) {
		if (request($data['name']) && request($data['module'])) {
			return $this->db()->delete('lang', array('name' => $data['name'], 'module' => $data['module']));
		} else return false;
	}
	
	private function getEditable($module, $name, $value, $lang, $class = false) {
		$classes = array('lang_m');
		if ($class) $classes[] = $class;
		return $this->model->tool('tag')->tag('span', $value, array('class' => $classes,  'meta' => array('m' => $module, 'lang' => $lang, 'name' => $name)));
	}
	
	public function getTable($args = array()) {
		if (!isset($args['selLang']) || !in_array($args['selLang'], $this->allowedLanguages)) {
			//TODO: Give error message
			return '<div style="padding: 20px; font-size: 1.5em; color: red;">no valid language set</div>';
		} else $lang = $args['selLang'];
		
		$clauses = array('lang' => $lang);
		$nameClauses = array();
		$tableClass = array($lang);
		if ($module = request($args['selModule'])) {
			$this->tool('session')->set('lang_edit_module', $module);
			$tableClass[] = $module;
			$clauses['module'] = $module;
			$nameClauses['module'] = $module;
		} else return '<div style="padding: 20px; font-size: 1.5em; color: red;">no module selected</div>';
		
		$values = $this->db()->query('lang', $clauses, array('transpose' => array('selectKey' => 'name', 'selectValue' => 'value')));
		$names = $this->db()->query('lang', $nameClauses, array('modifier' => 'DISTINCT', 'orderBy' => 'name', 'transpose' => array('selectKey' => 'name', 'selectValue' => 'module')));
		
		$tableClass[] = 'list';
		
		$content = sprintf("<div class=\"%s\"><table class=\"langList\"><thead><tr><th class=\"name\">Name</th><th class=\"value\">Value</th><th class=\"actions\">Actions</th></thead>\n<tbody>\n", implode('-', $tableClass));
		
		$odd = true;
		foreach ($names as $name => $module) {
			$class = '';
			$value = request($values[$name]);
			if (!$value) {
				if ($lang != $this->defaultLang) {
					$class = 'lang_default';
					$value = $this->getValue($module, $name, $this->defaultLang);
				} else $class = 'lang_empty';
			}
			$content .= sprintf("<tr class=\"%s\"><td>%s</td><td>%s</td><td><a class=\"langRemove\" title=\"Remove (will also remove string from other languages)\" href=\"%s\">Remove</a></td></tr>\n",
				$odd ? 'odd' : 'even',
				$name,
				$this->getEditable($module, $name, $value, $lang, $class),
				$this->url(array('m' => 'lang', 'action' => 'remove', 'data[name]' => $name, 'data[module]' => $module))
			);
			$odd = !$odd;
		}
		$content .= "</tbody></table></div>\n";
		return $content;
	}
	
	public function getContent($args = array()) {
		global $user;
		
		if (!$user->getPower()) HTTP::disallowed();
		
		$this->css('table');
		$this->tool('html')->addJS($this->model->urls['root'] . '/js/jquery/jquery.form.js');
		
		$lang = isset($args['selLang']) && in_array($args['selLang'], $this->allowedLanguages) ? $args['selLang'] : $this->getCurrentLanguage();
		
		$module = request($args['selModule']);
		if (!$module) $module = $this->tool('session')->get('lang_edit_module');
		
		$allowedLanguages = $this->allowedLanguages;
		$modules = $this->db()->query('lang', false, array('modifier' => 'DISTINCT', 'orderBy' => 'module', 'transpose' => 'module'));
		$content .= sprintf("
			<h1 class=\"dark\">%s</h1>
			<div class=\"textContent\">
				<form method=\"post\" id=\"langEditorSelect\" action=\"%s\"><div>\n",
			$this->lang('LANGUAGE_EDITOR'),
			$this->tool('linker')->prettifyURL($this->model->args)
		);
		$content .= $this->tool('tag')->select('selLang', $allowedLanguages, $lang, array('emptyCaption' => 'select language'));
		$content .= $this->tool('tag')->select('selModule', $modules, $module, array('emptyCaption' => 'select module'));
		$content .= "<input type=\"submit\" value=\"go\">\n";
		$content .= "</div></form>\n";
		$content .= $this->getTable(array('selLang' => $lang, 'selModule' => $module));
		$content .= "</div>";
		return $content;
	}
}