<?php

/**
 * @author yereth
 *
 * This class contains the login pages, which can be selected as a sitetool from ewyse.
 *
 * Most of the actual logging in is handled by the tool Security, found in the tools folder.
 *
 * TODO: Abstract some of the contact handling to a new Contact Tool class.
 *
 */
class CategoriesModel extends CMS_Model {
	/**
	 * The name of the category module we're looking up categories for.
	 * 
	 * @var string
	 */
	public $catModule;
	private $langModel;
	
	public $actions = array(
		'addFieldToCategory' => false,
		'addOptionToField' => false,
		'deleteField' => false,
		'deleteOption' => false,
		'deleteOptionIcon' => false,
		'editOptionIcon' => false,
		'addCategory' => false,
		'deleteCategory' => false,
		'deleteCategoryIcon' => false,
		'editCategoryIcon' => false,
		'moveFieldsToGroup' => false
	);
	
	public function init($args) {
		$this->langModel = $this->model->module('lang');
	}
		
	function getContent($options = array()) {
		
	}
	
	public function getCategory($id) {
		$cat = $this->model->mongo()->categories->findOne(array('parent' => 1));
		var_dump($cat);die();
		return $cat;
	}
	
	public function getCategoryFields($ids, $module = NULL, $groupBy = NULL, $options = array()) {
		$clauses = array('category_id' => $ids);
		if ($module) $clauses['module'] = $module;
		if ($groupBy) $options['arrayGroupBy'] = $groupBy;
		$fields = $this->db()->query('categoryFields', $clauses, $options);
		return $fields;
	}
	
	public function getCategories($module, $options = array()) {
		$this->catModule = $module;
		$clauses = array('module' => $module);
		if ($ids = request($options['parents'])) {
			$clauses['parent'] = is_array($ids) ? array('$in' => array_map('intval', $ids)) : (int) $ids;
		}
		if (request($options['selectableOnly'])) $clauses['selectable'] = true;
		if (request($options['noSubCats'])) $clauses[] = array('$ne' => NULL);
		$fields = array('id', 'code', 'name', 'parent');
		
		if (request($options['icons'])) $fields[] = 'icon'; 
		$cats = $this->model->mongo()->categories->find($clauses, $fields);
		$categories = array();
		$groupBy = !request($options['noGroupBy']);
		$defaultLang = $this->model->module('lang')->defaultLang;
		foreach($cats as $i => $cat) {
			$name = array_key_exists($this->model->lang, $cat['name']) ? $cat['name'][$this->model->lang] : $cat['name'][$defaultLang];
			$cat['name'] = $name;
			//if ($cat['parent']) var_dump($cat['parent']['_id']);
			$categories[$groupBy ? ($cat['parent'] ? (string) $cat['parent'] : 'NULL') : $i][] = $cat;
		}
		if (request($options['js'])) {
			$this->tool('html')->addJSVariable('categories', $categories);
			$this->js('categories');
		}
		return $categories;
	}
	
	public function deleteOption($fields = array()) {
		if (is_array($fields)) {
			$required = array('module', 'categoryField_id', 'field', 'value');
			// Check if all data is set
			foreach($required as $field) if (!trim(request($fields[$field]))) return false;
		} elseif (is_numeric($fields)) $fields = array('categoryField_id' => (int) $fields);
		else return false;
		$groups = $this->db()->query('fieldValues', $fields, array('arrayGroupBy' => 'module'));
		if ($groups) {
			foreach($groups as $module => $options) {
				$path = $this->getIconPath($module) . 'options/';
				foreach($options as $option) if ($option['icon']) @unlink($path . $option['icon']);
			}
			$result = $this->db()->delete('fieldValues', $fields);
			return $result;
		}
		return false;
	}
	
	public function deleteField($data = array()) {
		if ($data && ((is_numeric($data) and $id = (int) $data) or $id = (int) request($data['id']))) {
			// Delete options
			$this->deleteOption($id);
			// Delete field
			return $this->db()->delete('categoryFields', $id);
		}
		return false;
	}
	
	public function getIconPath($module) {
		$module = str_replace('Model', '', $module);
		return $this->model->paths['cms'] . $module . '/assets/categories/';
	}
	
	public function getIconURL($module) {
		$module = str_replace('Model', '', $module);
		return $this->model->urls['cms'] . $module . '/assets/categories/';
	}
	
	public function addOptionToField($data = array()) {
		if ($bulk = request($data['bulk'])) {
			$options = preg_split("/[\r\n]/", $bulk, -1, PREG_SPLIT_NO_EMPTY);
			unset($data['bulk'], $data['data']['icon']);
			if ($options) {
				foreach($options as $option) {
					$data['data']['value'] = trim($option);
					$this->addOptionToField($data);
				}
			} else return false;
			return true;
		}
		$required = array('module', 'categoryField_id', 'field', 'value');
		if ($fields = request($data['data']) and is_array($fields)) {
			// Check if all data is set
			foreach($required as $field) if (!$fields[$field] = trim(request($fields[$field]))) return false;
			$icon = $this->captureIconUpload('data/icon', $this->getIconPath($fields['module']) . 'options/');
			if ($icon === false) return false;
			elseif(is_string($icon)) $fields['icon'] = $icon; 
			// Check if the field exists and is a list
			if (!$this->db()->query('categoryFields', array('id' => $fields['categoryField_id'], 'name' => $fields['field'], 'type' => array('list', 'multi-select')))) return false;
			$result = $this->db()->insert('fieldValues', $fields);
			if ($result) {
				$langKey = $this->langkeyFieldValue($fields['field'], $fields['value']);
				$this->model->module('lang')->save($fields['module'], $langKey, $this->model->lang, $fields['value']);
			}
			return $result;
		}
		return false;
	}
	
	public function deleteOptionIcon($fields = array()) {
		if (is_array($fields)) {
			$required = array('module', 'categoryField_id', 'field', 'value');
			// Check if all data is set
			foreach($required as $field) if (!trim(request($fields[$field]))) return false;
		} elseif (is_numeric($fields)) $fields = array('categoryField_id' => (int) $fields);
		else return false;
		// If there's an icon connected to the option, delete that too
		$groups = $this->db()->query('fieldValues', $fields, array('transpose' => 'icon', 'arrayGroupBy' => 'module'));
		if ($groups) {
			foreach($groups as $module => $icons) {
				$path = $this->getIconPath($module) . 'options/';
				foreach($icons as $icon) if ($icon) @unlink($path . $icon);
			}
			$this->db()->update('fieldValues', $fields, array('icon' => ''));
			return true;
		}
		return false;
	}
	
	public function editOptionIcon($data = array()) {
		if ($fields = request($data['data']) and isset($fields['module']) and $this->deleteOptionIcon($fields)) {
			$icon = $this->captureIconUpload('data/icon', $this->getIconPath($fields['module']) . 'options/');
			if(is_string($icon)) return $this->db()->update('fieldValues', $fields, array('icon' => $icon));
		}
		return false;
	}
	
	private function captureIconUpload($name, $folder) {
		$uploader = $this->tool('uploader');
		if ($uploader->exists($name)) {
			$uploader->setUploadFolder($folder);
			if ($uploader->captureUpload($name)) {
				$file = $uploader->successful[0];
				$this->tool('image')->resize($file['target'], 100, 100, true);
				return $file['name'];
			} else {
				$this->logL(constant('LOG_USER_WARNING'), 'E_ICON_FAILED');
				return false;
			}
		} else return true;
	}
	
	public function addFieldToCategory($data = array()) {
		$required = array('module', 'category_id', 'name', 'type');
		if ($fields = request($data['data']) and is_array($fields)) {
			// Check if all data is set
			foreach($required as $field) if (!trim(request($fields[$field]))) return false;
			return $this->db()->insert('categoryFields', $fields);
		}
		return false;
	}
	
	public function moveFieldsToGroup($data = array()) {
		$data = request($data['data']);
		if (!is_array($data) || !isset($data['fields'], $data['which'])) return false;
		if (!$group = request($data[$data['which']])) return false;
		$ids = is_array($data['fields']) ? $data['fields'] : explode(',', $data['fields']);
		if (!$ids) return false;
		return false !== $this->db()->update('categoryFields', array('id' => $ids), array('group' => strtolower($group)));
	}
	
	public function deleteCategory($id) {
		if (!$id || !is_numeric($id)) $id = (int) $this->model->state('args');
		if ($id) {
			if (!$ids = $this->db()->query('categories', array('id' => $id), array('transpose' => 'id'))) return false;
			$parents = $id;
			while ($parents = $this->db()->query('categories', array('category_id' => $parents), array('transpose' => 'id'))) $ids = array_merge($ids, $parents);
			if ($catFields = $this->db()->query('categoryFields', array('category_id' => $ids), array('transpose' => 'id'))) {
				foreach($catFields as $catField_id) $this->deleteField($catField_id);
			}
			$groups = $this->db()->query('categories', $ids, array('arrayGroupBy' => 'module'));
			if ($groups) {
				foreach($groups as $module => $categories) {
					$path = $this->getIconPath($module);
					foreach($categories as $category) {
						if ($category['icon']) @unlink($path . $category['icon']);
					}
				}
				return $this->db()->delete('categories', $ids);
			}
		}
		return false;
	}
	
	public function addCategory($data = array()) {
		if ($fields = request($data['data']) and is_array($fields)) {
			$required = array('module', 'name');
			foreach($required as $field) if (!trim(request($fields[$field]))) {
				$this->logL(constant('LOG_USER_WARNING'), 'E_MISSING_FIELDS');
				return false;
			}
			foreach($this->model->allowedLanguages as $lang) {
				if (!isset($data[$lang]['name'])) {
					$this->logL(constant('LOG_USER_WARNING'), 'E_MISSING_FIELDS');
					return false;
				}
			}
			$icon = $this->captureIconUpload('data/icon', $this->getIconPath($fields['module']));
			if ($icon === false) return false;
			elseif (is_string($icon)) $fields['icon'] = $icon;
			$result = $this->db()->insert('categories', $fields);
			if (!$result) {
				@unlink($this->getIconPath($fields['module']) . $icon);
				$this->logL(constant('LOG_USER_WARNING'), 'E_CREATE_CATEGORY_FAILED');
				return false;
			} else {
				$langModel = $this->model->module('lang');
				foreach($this->model->allowedLanguages as $lang) {
					$langModel->save($fields['module'] . 'Categories', $fields['name'], $lang, $data[$lang]['name']);
				}
				return $result;
			}
		}
		return false;
	}
	
	public function editCategoryIcon($data = array()) {
		if ($id = (int) request($data['data']['id']) and $this->deleteCategoryIcon($data['data'])) {
			$module = $this->db()->query('categories', $id, array('selectField' => 'module'));
			if ($module) {
				$icon = $this->captureIconUpload('data/icon', $this->getIconPath($module));
				if (is_string($icon)) return $this->db()->update('categories', $id, array('icon' => $icon));
			}
		}
		return false;
	}
	
	public function deleteCategoryIcon($data = array()) {
		if (!$id = (int) request($data['id'])) return false;
		$cat = $this->db()->query('categories', $id);
		if ($cat) {
			if ($cat['icon']) @unlink($this->getIconPath($cat['module']) . $cat['icon']);
			$this->db()->update('categories', $id, array('icon' => ''));
			return true;
		} else return false;
	}
}

?>