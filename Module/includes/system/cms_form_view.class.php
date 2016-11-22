<?php

abstract class CMS_Form_View extends CMS_View {
	public $inputTypes = array(
		'hidden' => array(),
		'text' => array('placeholder' => true),
		'search' => array('placeholder' => true),
		'tel' => array('placeholder' => true),
		'url' => array('placeholder' => true),
		'email' => array('placeholder' => true),
		'password' => array(),
		'datetime' => array(),
		'date' => array(),
		'month' => array(),
		'week' => array(),
		'time' => array(),
		'datetime-local' => array(),
		'number' => array(),
		'range' => array(),
		'color' => array(),
		'checkbox' => array(),
		'radio' => array(),
		'file' => array(),
		'submit' => array(),
		'image' => array(),
		'reset' => array(),
		'button' => array(),
		'radio' => array(),
		'checkbox' => array()
	);
	
	/**
	 * Holds the CMS_Form_Model for this view
	 * @var CMS_Form_Model
	 */
	public $m;
	
	/**
	 * The name of the current view, as $CMS_Form_View::name refers to the model name.
	 * @var string
	 */
	public $viewName;
	
	/**
	 * @param MainModel $model
	 * @param CMS_Form_Model $m
	 * @param string $className
	 * @param string $name
	 * @param array $args
	 * 
	 * @see CMS_Model::__construct()
	 */
	public function __construct(MainModel $model, CMS_Form_Model $m, $className, $name, array $args = array()) {
		$this->viewName = $name;
		parent::__construct($model, $m, $className, $name, $args);
		$html = $this->tool('html');
		$html->addCSS('formCSS', 'css/form.css');
		$html->addCSS('dateinputCSS', 'css/tools/dateinput.css');
		$html->addJS(array('js/form.js', 'js/tools/dateinput.js', 'js/tools/validator.js'));
		$dt = $this->tool('datetime');
		$data = array(
			'months' => implode(',', $dt->getMonths(false, true)),
			'shortMonths' => implode(',', $dt->getMonths(true, true)),
			'days' => implode(',', $dt->getDays(false, true)),
			'shortDays' => implode(',', $dt->getDays(true, true)),
		);
		$html->addJSVariable('dateinputData', $data);
		$m->setAction($name);
	}
	
	public function userlog($message){
		return $this->pageBuilder->log->add(constant('LOG_USER_WARNING'), "<strong>" . $this->lang('TITLE') . ":</strong> ". $message);
	}
	
	public function getLanguageSelect(array $data = NULL, $company_id = NULL) {
		$lang = $this->m->getActiveLanguages($data, $company_id);
		if (!$lang) return '';
		$default = in_array($this->model->lang, $lang) ? $this->model->lang : array_get($lang, 0);
		return 
			sprintf("\t\t\t<label>%s</label>\n", $this->lang('ALSO_REGISTER_IN')) .
			$this->getCheckbox('lang', $lang, $default);
	}
	
	/**
	 * getInput creates input text field. In the script calling this function some parameters should be set: labels, required and errorMessage on the basis of $name.
	 *
	 * @param mixed $name the name of the input field. Can be string or an array.
	 * @return string the html of the input field
	 */
	public function getInput($name, $options = array()) {
		$return = '';
		if (request($options['group']) == '!lang') {
			$langs = $this->m->getActiveLanguages();
			$return .= sprintf("<div id=\"langGroup%s\" class=\"langGroup\">\n", ucfirst($name));
			$class = request($options['class']);
			foreach ($langs as $lang) {
				// Get the required fields
				if (!array_key_exists('required', $options)) if ($required = $this->m->getRequired($name, '!lang')) $options['required'] = $required;
				$options['group'] = $lang;
				$options['class'] = trim($class . ' ' . strtolower($lang));
				$return .= "\t" . $this->getInput($name, $options) . "\n";
			}
			return $return . "</div>\n";
		}
		if (is_array($name)) foreach($name as $input) $return .= $this->getInput($input);
		else {
			$group = request($options['group']);
			$inputName = $this->m->getInputName($name, $group);
			$id = isset($options['id']) ? $options['id'] : $this->m->getInputId($name, $group);
			$langKey = $this->m->getInputLangKey($name, $group);
			$required = array_key_exists('required', $options) ? $options['required'] : $this->m->getRequired($name, $group);
			
			// Set the datatype. If the datatype is specifically set, use it, otherwise, check if the required
			// parameter is set to a type, otherwise, we set the type to text, by default (which is the default for
			// input fields)
			if ($type = request($options['type']) or (is_string($required) and $type = $required)) {
				// Only set the type of the input to the requested type if it's supported by HTML5
				if (!array_key_exists($options['type'], $this->inputTypes)) {
					// If it's not supported, we'll store the type in a different manner.
					$options['dataType'] = $options['type'];
					$type = 'text';
				}
			} else $type = 'text';
			
			if ($type == 'hidden') $options['noLabel'] = true;
			
			ifNot($options, $type, 'dataType');
			
			$inputSettings = $this->inputTypes[$type];
			
			$class = array();
			if (request($options['class'])) $class[] = $options['class'];
			if (in_array($inputName, $this->m->data['missing'])) $class[] = 'invalid';
			
			if (!$type != 'password') {
				$value = $this->m->getValue($name, $options);
				if (is_array($value)) $value = implode(',', $value);
				if (!$value && array_key_exists('value', $options)) $value = $options['value'];
			} else $value = '';
			
			$attr = array(
				'name' => $inputName,
				'value' => $value,
				'class' => $class,
				'id' => $id,
				'title' => isset($options['title']) ? $options['title'] : $this->lang($langKey .  '_TITLE', false, false, true),
				'type' => $type
			);
			
			// Set the accept attribute in case it is set and we're dealing with the file type input
			if ($type = 'file' && array_key_exists('accept', $options)) {
				if (array_key_exists($options['accept'], $this->m->mimegroups)) {
					$options['dataType'] .= '_' . $options['accept'];
					$attr['accept'] = $this->m->mimegroups[$options['accept']];
				} else $attr['accept'] = $options['accept'];
			}
			
			// Add standard placeholder text if explicitly given or when the field is required.
			if (($required || array_key_exists('placeholder', $options)) && !request($options['noPlaceholder']) && request($inputSettings['placeholder'])) {
				// Is a placeholder string given?
				if (isset($options['placeholder'])) $attr['placeholder'] = $options['placeholder'];
				// If we have a special type, use the generic key
				elseif($options['dataType'] != 'text') $attr['placeholder'] = $this->lang('M_INPUT_' . strtoupper($options['dataType']) . '_PLACEHOLDER', 'site', false, true);
				// Otherwise, specific to the module and input element
				else $attr['placeholder'] = $this->lang($langKey . '_PLACEHOLDER', false, false, true);
			}
			
			$meta = array();
			if ($required) {
				$attr['required'] = 'required';
				// jQuery tools validator choice of error message storage
				$attr['data-message'] = $this->lang('M_INPUT_' . strtoupper($options['dataType']) .  '_ERROR', 'site', false, true);
			}
			
			$return .= $this->tool('tag')->input(array(
				'caption' => request($options['noLabel']) ? false : $this->lang($langKey),
				'attr' => $attr,
				'labelAttr' => array(
					'id' => $id . 'Label'
				),
				'meta' => $meta
			));
		}
		return $return;
	}

	/**
	 * getInputRadio() creates a group of input fields of the type radio. Labels need to be specified in the calling script.
	 *
	 * @param string $group the name of the group necessary when using radio buttons.
	 * @param mixed $name the name of the individual radio buttons. Either a string or an array of names.
	 * @return string the html of the fields.
	 */
	public function getInputRadio($group, $name, $default = false, $options = array()) {
		return $this->getInputType('radio', $group, $name, $default, $options);
	}
	
	/**
	 * getInputRadio() creates a group of input fields of the type radio. Labels need to be specified in the calling script.
	 *
	 * @param string $group the name of the group necessary when using radio buttons.
	 * @param mixed $name the name of the individual radio buttons. Either a string or an array of names.
	 * @return string the html of the fields.
	 */
	public function getCheckbox($group, $name, $default = false, $options = array()) {
		return $this->getInputType('checkbox', $group, $name, $default, $options);
	}
	
	private function getInputType($type, $group, $name, $default, $options = array()) {
		$return = '';
		if (is_array($name)) {
			$return .= sprintf("\n\t\t<div class=\"%sGroup %sGroup%s\">\n", $type, $type, ucfirst($group));
			// If we have an associative array, we have the caption as values
			foreach ($name as $key => $value) {
				if (!is_numeric($key)) {
					$options['caption'] = $value;
					$n = $key;
				} else $n = $value;
				$return .= $this->getInputType($type, $group, $n, $default, $options);
			}
			$return .= "\n\t\t</div>\n";
		}
		else {
			$active = $this->m->getValue($group, $options);
			$default = ifElse($active, $default);
			
			$g = request($options['group']);
			$inputName = $this->m->getInputName($group, $g);
			$id = isset($options['id']) ? $options['id'] : $this->m->getInputId($group, $g) . ucfirst($name);
			$langKey = strtoupper($group . '_' . str_replace(' ', '-', $name));
			$required = array_key_exists('required', $options) ? $options['required'] : $this->m->getRequired($name, $g);
			
			$class = array();
			if (request($options['class'])) $class[] = $options['class'];
			if (in_array($inputName, $this->m->data['missing'])) $class[] = 'invalid';
			$return .= $this->tool('tag')->input(array(
				'value' => $active,
				'caption' => isset($options['caption']) ? $options['caption'] : $this->lang(strtoupper($group . '_' . $name)),
				'attr' => array(
					'name' => $inputName . ($type == 'checkbox' ? '[]' : ''),
					'value' => trim($name),
					'title' => isset($options['title']) ? $options['title'] : $this->lang($langKey .  '_TITLE', false, false, true), 
					'class' => $class,
					'type' => $type,
					'id' => $id
				),
				'default' => $default,
				'labelAfter' => true,
				'labelAttr' => array('class' => $type),
				'meta' => array(
					'error' => $this->lang('E_RADIO_NO_SELECTION', 'site', false, true)
				)
			));
		}
		return $return;
	}

	public function getSelect($name, $values, array $options = array()) {
		$group = request($options['group']);
		$inputName = $this->m->getInputName($name, $group);
		$id = isset($options['id']) ? $options['id'] : $this->m->getInputId($name, $group);
		$langKey = $this->m->getInputLangKey($name, $group);
		$required = array_key_exists('required', $options) ? $options['required'] : $this->m->getRequired($name, $group);
		
		$active = $this->m->getValue($name, $options);
		ifNot($active, request($options['active']));
		
		$caption = $this->lang($langKey);
		
		$class = array();
		if (request($options['class'])) $class[] = $options['class'];
		if (in_array($inputName, $this->m->data['missing'])) $class[] = 'invalid';
		$options['class'] = $class;
		
		$attr = (array) request($options['attr']);
		$attr['title'] = isset($options['title']) ? $options['title'] : $this->lang($langKey . '_TITLE', false, false, true);
		
		$options = array_merge($options, array(
			'caption' => request($options['noLabel']) ? false : $caption,
			'emptyCaption' => request($options['noEmpty']) ? false : $this->lang($langKey . '_EMPTY_TEXT'),
			'attr' => $attr,
			'id' => $id
		));
		
		if ($required) {
			$options['attr']['required'] = 'required';
			$options['attr']['data-message'] = $this->lang($langKey . '_ERROR', false, false, true);
		}
		return $this->tool('tag')->select(
			$inputName,
			$values,
			$active,
			$options
		);
	}

	function getTextarea($name, array $options = array()) {
		$content = '';
		if (request($options['group']) == '!lang') {
			// this is quite stupid, but we want to move the current language to the front of the array
			$langs = $this->m->getActiveLanguages();
			$content .= sprintf("<div id=\"langGroup%s\" class=\"langGroup\">\n", ucfirst($name));
			$class = request($options['class']);
			foreach ($langs as $lang) {
				// Get the required fields
				if (!array_key_exists('required', $options)) if ($required = $this->m->getRequired($name, '!lang')) $options['required'] = $required;
				$options['group'] = $lang;
				$options['class'] = trim($class . ' ' . strtolower($lang));
				$content .= "\t" . $this->getTextarea($name, $options) . "\n";
			}
			return $content . "</div>\n";
		}
		$group = request($options['group']);
		$inputName = $this->m->getInputName($name, $group);
		$id = isset($options['id']) ? $options['id'] : $this->m->getInputId($name, $group);
		$langKey = $this->m->getInputLangKey($name, $group);
		$required = array_key_exists('required', $options) ? $options['required'] : $this->m->getRequired($name, $group);
		
		$class = $labelClass = isset($options['class']) ? (array) $options['class'] : array();
		$labelClass[] = 'textarea';
		
		$content .= "\t<div class=\"textareaWrapper\">\n";
		$content .= $this->tool('tag')->tag(
			'label',
			$this->lang($langKey),
			array(
				'attr' => array('for' => $id),
				'class' => implode(' ', $labelClass)
			)
		);
		
		$attr = array(
			'id' => $id,
			'name' => $inputName,
			'title' => isset($options['title']) ? $options['title'] : $this->lang($langKey .  '_TITLE', false, false, true),
		);
		
		if ($required) {
				$attr['required'] = 'required';
				$attr['data-message'] = $this->lang($langKey . '_TITLE', false, false, true);
		}
		
		// Add standard placeholder text if explicitly given or when the field is required.
		if (($required || array_key_exists('placeholder', $options)) && !request($options['noPlaceholder'])) {
			$attr['placeholder'] = isset($options['placeholder']) ? $options['placeholder'] : $this->lang($langKey . '_PLACEHOLDER', false, false, true);
		}
		
		if (in_array($inputName, $this->m->data['missing'])) $class[] = 'invalid';
		$content .= $this->tool('tag')->tag(
			'textarea',
			$this->m->getValue($name, $group),
			array(
				'attr' => $attr,
				'class' => implode(' ', $class)
			)
		);
		$content .= "\t</div>\n";
		return $content;
	}

	function replaceVars($content, $replaceList, $prepend = '', $sanitise = false) {
		if ($replaceList && $content) {
			foreach ($replaceList as $key => $value) {
				if ($sanitise) $value = htmlentities($value);
				$content = str_replace('[' . $prepend . $key . ']', $value, $content);
			}
		}
		return $content;
	}
}

?>