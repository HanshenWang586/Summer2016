<?php

class PreferencesModel extends CMS_Model {
	// Store retrieved preferences strings ordered by module
	private $cache = array();
	
	public function init($args) {
		
	}
	
	public function get($name = false, $module = 'system') {
		// If not retrieved yet, get the preferences array for the current module
		if (!isset($this->cache[$module])) {
			$this->cache[$module] = $this->db()->query('preferences', array('module' => $module), array('transpose' => array('selectKey' => 'name', 'selectValue' => 'value')));
		}
		
		if (!isset($name)) return $this->cache[$module];
		
		// Get the requested value
		$value = request($this->cache[$module][$name]);
		
		
		// If there's no result, we will create an entry that can be edited later in the language editor
		if (!is_string($value)) {
			$this->db()->insert('preferences', array('module' => $module, 'name' => $name));
			// To prevent we'll insert the same string twice one requested twice in a page
			$this->cache[$module][$name] = '';
		}
		// If there's no result, we will create an entry that can be edited later in the editor
		return $value;
	}
	
	public function save($m, $name = NULL, $value = NULL) {
		if (is_array($m)) {
			$data = $m;
			foreach(array('m','name','value') as $key) {
				if (!$$key = request($data[$key])) return false;
			}
		} elseif (!isset($name, $value)) return false;
		
		// Update our cache
		if (request($this->cache[$m])) $this->cache[$m][$name] = $value;
		
		$row = array('module' => $m, 'name' => $name, 'value' => $value);
		return $this->db()->insert('preferences', $row, array('update' => 'value=VALUES(value)'));
	}
	
	public function getTable() {
		$names = $this->db()->query('preferences', false, array('modifier' => 'DISTINCT', 'orderBy' => 'name', 'getFields' => array('name', 'value'), 'transpose' => array('selectKey' => 'name', 'selectValue' => 'module')));
		$modules = array_unique(array_values($names));
		$values = $this->db()->query('preferences', array('lang' => $lang), array('transpose' => array('selectKey' => 'name', 'selectValue' => true)));
		$content = sprintf("<table class=\"langList %s\"><thead><tr><th>Name</th><th>Value</th></thead>\n<tbody>\n", $lang);
		foreach ($names as $name => $module) {
			$value = request($values[$name]);
			$content .= sprintf("<tr class=\"module-%s\"><td>%s</td><td>%s</td></tr>\n",
							$module,
							$name,
							$this->getEditable($module, $name, $value ? $value['value'] : '')
						);
		}
		$content .= "</tbody></table>\n";
		return $content;
	}
	
	public function getContent() {
		$content = '';
		if ($this->model->args['view'] == 'list') {
			$allowedLanguages = $this->db()->query('locale', false, array('transpose' => array('selectKey' => 'shortname')));
			$content = sprintf("<form method=\"post\" id=\"langEditorSelect\" action=\"%s\"><div>\n", $this->tool('linker')->prettifyURL($this->model->args));
			$content .= $this->tool('tag')->select('selLang', $allowedLanguages, $this->model->lang, array('emptyCaption' => 'select language'));
			$content .= "<input type=\"submit\" value=\"go\">\n";
			$content .= "</div></form>\n";
			$content .= $this->getTable();
			$content = $this->tool('layout')->getPanel($content, 'language editor', array('width' => 300));
		}
		return $content;
	}
}