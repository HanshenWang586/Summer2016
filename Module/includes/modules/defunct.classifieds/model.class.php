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
class ClassifiedsModel extends CMS_Model {
	private $fields;
	
	public $actions = array(
		'createAd' => array(
			'required' => array(
				'ad' => array(
					'category1', 'category2'
				),
				'!lang' => array(
					'title', 'description'
				)
			),
			'accept' => array(
				'!lang' => array(
					'title', 'address', 'description'
				)
			)
		)
	);
	
	public function init($args) {
		
		// Sanitise categories whenever used
		$cats = $this->arg('category');
		if ($cats) {
			if (is_numeric($cats)) $cats = (int) $cats;
			else {
				if (is_string($cats)) $cats = explode(',', $cats);
				if (is_array($cats)) {
					$_cats = array();
					foreach($cats as $cat) {
						$cat = (int) $cat;
						if ($cat) $_cats[] = $cat;
						else break; 
					}
					$cats = $_cats;
				} else $cats = NULL;
			}
			$this->arg('category', $cats);
			
			// If an action is set, check which fields belong to the categories 
			if ($action = $this->model->state('action') and $this->isAction($action)) {
				$this->fields = $fields = $this->model->module('categories')->getCategoryFields($cats, false, false, array('transpose' => array('selectKey' => 'name', 'selectValue' => true)));
				foreach($fields as $name => $field) {
					$key = $field['type'] == 'text' ? '!lang' : 'adFields';
					if ($field['type'] != 'multi-select') $this->actions[$action]['required'][$key][] = $name;
					$this->actions[$action]['accept'][$key][] = $name;
				}
			}
		}
	}
		
	function getContent($options = array()) {
		return $this->view('default');
	}
	
	/**
	 * The post view. This function only delegates to the right view, depending on which stage we're in.
	 * @param array $options The options to run the view with
	 * @return string The content for the view
	 */
	public function _post($options = array()) {
		$user = $this->tool('security')->getActiveUser();
		
		if ($user && $status = $user->getCreateAd($this->name)) {
			if ($status['stage'] == 'confirmAd') {
				$stage = $status['stage'];
				$user->finaliseCreateAd();
			} else {
				$stage = $view = $status['stage'];
				$options['status'] = $status;
			}
		} else {
			// Otherwise, we want to create a new add. Add the districts information to the options
			$stage = $view = 'createAd';
		}
		if (isset($view)) $content = $this->view($view, $options);
		return $content;
	}
	
	public function getCategoryIcon($name) {
		return $name ? $this->model->urls['cms'] . $this->name . '/assets/categories/' . $name : false;
	}	
	
	public function getItems($args = array(), array $options = array()) {
		$classifieds = $this->model->mongo()->classifieds;
		$results = $classifieds->find(array('category' => $this->model->args['id']));
		$skip = 10 * ((int) request($options['page']) - 1);
		if ($skip > 0) $results->skip($skip);
		$results->limit(10);
		$results->sort(array('date' => -1));
		return $results;
	}
	
	public function processItem($item) {
		$langFields = array('title', 'content');
		$langs = array_unique(array($this->model->lang, $this->model->module('lang')->defaultLang));
		foreach($langFields as $field) {
			if (array_key_exists($field, $item)) {
				foreach($langs as $lang) if (array_key_exists($lang, $item[$field])) {
					$item[$field] = $item[$field][$lang];
					break;
				}
			}
		}
		preg_match('/^([^.!?\s]*[\.!?\s]+){0,30}/', strip_tags($item['content']), $abstract);
		$item['short'] = trim($abstract[0]) . ' &hellip;';
		return $item;
	}
	
	// ACTIONS
/**
	 * Creates an Ad based on the form from the View createAd and sets the apropriate createad
	 * 
	 * 
	 * @param array $args
	 * @return boolean Whether the creation was succesful
	 */
	public function createAd($args = array()) {
		if ($this->validated() && $data = $this->data('data')) {
			$user = $this->tool('security')->getActiveUser();
			// TODO: Check more?
			
			if (!$user) {
				$this->logL(constant('LOG_USER_ERROR'), 'E_NOT_LOGGED_IN');
				return false;
			}
			
			if ($user->getCreateAd()) {
				$this->logL(constant('LOG_USER_WARNING'), 'E_AD_CREATION_IN_PROGRESS');
				return false;
			}
			
			$ad = $data['ad'];
			$ad['date'] = date("Y-m-d H:i:s");
			if (array_key_exists('city', $ad)) {
				if ($city = $this->tool('location')->getCities(false, true, $ad['city'])) {
					$ad['city_id'] = $city[0]['id'];
					unset($ad['city']);
				} else {
					$this->logL(constant('LOG_USER_ERROR'), 'E_CREATE_AD_INVALID_CITY');
					return false;
				}
			}
			$ad['user_id'] = $user->id;
			// TODO: What if a user has more companies?
			if ($company = $user->getCompany()) $ad['company_id'] = $company->id;
			$lang = $this->getActiveLanguages($data);
			$ad['lang'] = implode(',', $lang);
			$this->db()->transaction();
			if ($ad_id = $this->db()->insert('marketplace', $ad)) {
				// Set the language of the current input
				$result = true;
				
				echo sprint_rf($data);
				echo sprint_rf($this->fields);
				$result = false;
				
				$arrays = $lang;
				$rows = array();
				foreach($lang as $lang) {
					$fields = request($data[$lang]);
					foreach($fields as $name => $value) {
						var_dump($name, array_key_exists($name, $this->fields));
						if (array_key_exists($name, $this->fields)) $rows[] = array('marketplace_id' => $ad_id, 'field_id' => $this->fields[$name]['id'], 'lang' => $lang, 'value' => $value);
					}
				}
				echo sprint_rf($fields);
				//if (!$this->db()->insert('marketplaceFields', $rows))
				
				if ($result) {
					$createAd = array('ad_id' => $job_id, 'stage' => 'reviewAd');
					if (request($ad['company_id'])) $createAd['company_id'] = $ad['company_id'];
					if ($user->setCreateAd($createAd)) {
						$this->db()->commit();
						return true;
					}
				}
				// Old method... uses a more complex table structure. Not necessary here.
				/*
				$rows = array();
				foreach($jobFields as $name => $value) {
					$rows[] = array('job_id' => $job_id, 'name' => $name, 'lang' => $lang, 'value' => $value);
				}
				if ($this->db()->insert('jobFields', $rows)) {
					$createAd = array('ad_id' => $job_id, 'stage' => 'reviewAd');
					if (request($job['company_id'])) $createAd['company_id'] = $job['company_id'];
					if ($user->setCreateAd($createAd)) {
						$this->db()->commit();
						return true;
					}
				}
				*/
			}
			$this->db()->rollback();
			$this->logL(constant('LOG_USER_ERROR'), 'E_CREATE_AD_FAILED');
		}
		return false;
	}
}

?>