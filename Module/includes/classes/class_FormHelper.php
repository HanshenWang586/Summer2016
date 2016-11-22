<?php
class FormHelper {

	/**
	 * @return string HTML form tag
	 */
	public static function open($path, $options = array()) {
		$method = 'post';
		
		$args = array();
		
		if (request($options['class'])) $args['class'] = $options['class'];
		if (request($options['id'])) $args['id'] = $options['id'];
		$args['method'] = request($options['method']) ? $options['method'] : 'post';
		if (request($options['file_upload'])) $args['enctype'] = "multipart/form-data";
		$args['action'] = $path;
		
		return sprintf('<form %s>', FormHelper::generateAttributes($args));
	}

	public static function open_ajax($id) {
		return "<form id=\"$id\">";
	}

	/**
	 * @return string HTML hidden input tag
	 */
	public static function hidden($name, $value) {
		return FormHelper::input('', $name, $value, array('type' => 'hidden'));
	}
	
	/**
	 * @return string HTML text input tag
	 */
	public static function input($label, $name, $value = '', $options = array()) {
		ifNot($options, 'text', 'type');
		
		$attr = array();
		
		if ($options['disable_autocomplete']) $attr['autocomplete'] = 'off';
		
		if ($options['disabled']) 
			$disabled = ' disabled';
		$attr['type'] = $options['type'];
		$attr['id'] = $name;
		$attr['name'] = $name;
		$attr['value'] = $value;
		if ($options['style']) $attr['style'] = $options['style'];
		if ($options['maxlength']) $attr['maxlength'] = $options['maxlength'];
		if ($options['onkeyup']) $attr['onkeyup'] = $options['onkeyup'];
		if ($options['onchange']) $attr['onchange'] = $options['onchange'];
		if ($options['placeholder']) $attr['placeholder'] = $options['placeholder'];
		if ($options['data'] and is_array($options['data'])) foreach($options['data'] as $key => $value) $attr['data-' . $key] = $value;
		
		$classes = isset($attr['class']) ? (is_array($attr['class']) ? $attr['class'] : explode(' ', $attr['class'])) : array();
		if (!in_array($options['type'], array('file', 'hidden'))) $classes[] = 'text';
		if ($options['type'] != 'text') $classes[] = $options['type'];
		$attr['class'] = $classes;
		
		if (isset($options['readonly']) and ($options['readonly'] === true)) {
			$classes[] = 'readonly';
			$attr['readonly'] = 'readonly';
		}
					
		if ($options['mandatory'])
			$mandatory = FormHelper::getMandatoryDesignator();
		
		if ($label != '')
			$content = '<label for="'.$name.'">'.$label.$mandatory.'</label>';
		
		$required = $options['mandatory'] ? ' required' : '';
		$content .= sprintf("<input%s%s %s>", $required, $disabled, FormHelper::generateAttributes($attr));

		if ($options['guidetext'] != '')
			$content .= FormHelper::getGuideText($options['guidetext']);

		return sprintf('<div class="inputWrapper">%s</div>', $content);
	}
	
	public static function generateAttributes(array $attr) {
		$html = '';
		if (isset($attr['class']) && is_array($attr['class'])) $attr['class'] = implode(' ', $attr['class']);
		if ($attr) foreach ($attr as $key => $val) {
			if (trim($val)) $html .= sprintf(' %s="%s"', $key, htmlspecialchars($val));
		}
		return $html;
	}
	
	/**
	 * @return string HTML file input tag
	 */
	public static function file($label, $name, $options = array()) {
		$options['type'] = 'file';
		return FormHelper::input($label, $name, '', $options);
	}

	/**
	 * @return string HTML password input tag
	 */
	public static function password($label, $name, $value = '', $options = array()) {
		$options['type'] = 'password';
		return FormHelper::input($label, $name, $value, $options);
	}

	/**
	 * @return string
	 */
	public static function element($label, $element, $options = array()) {

		$content .= "<label for=\"$name\">$label".($options['mandatory'] ? FormHelper::getMandatoryDesignator() : '')."</label>$element";

		if ($options['guidetext'] != '') {
			$content .= FormHelper::getGuideText($options['guidetext']);
		}

		return sprintf('<div class="inputWrapper">%s</div>', $content);
	}

	/**
	 * @return string HTML textarea tag
	 */
	public static function textarea($label, $name, $value = '', $options = array()) {
		$required = $options['mandatory'] ? ' required' : '';
		
		if ($label) $content = "<label for=\"$name\">$label".($options['mandatory'] ? FormHelper::getMandatoryDesignator() : '')."</label>";
		
		$attr = array();
		
		if ($options['disable_autocomplete']) $attr['autocomplete'] = 'off';
		
		if ($options['disabled']) $disabled = ' disabled';
		$attr['id'] = $name;
		$attr['name'] = $name;
		if ($options['style']) $attr['style'] = $options['style'];
		if ($options['maxlength']) $attr['maxlength'] = $options['maxlength'];
		if ($options['onkeyup']) $attr['onkeyup'] = $options['onkeyup'];
		if ($options['onchange']) $attr['onchange'] = $options['onchange'];
		if ($options['placeholder']) $attr['placeholder'] = $options['placeholder'];
		if ($options['class']) $attr['class'] = $options['class'];
		
		$classes = isset($attr['class']) ? (is_array($attr['class']) ? $attr['class'] : explode(' ', $attr['class'])) : array();
		$attr['class'] = $classes;
		
		if (isset($options['readonly']) and ($options['readonly'] === true)) {
			$classes[] = 'readonly';
			$attr['readonly'] = 'readonly';
		}
		
		$content .= sprintf("<textarea%s%s %s>%s</textarea>", $required, $disabled, FormHelper::generateAttributes($attr), htmlentities($value, ENT_QUOTES, 'UTF-8'));
		
		if ($options['guidetext'] != '')
			$content .= FormHelper::getGuideText($options['guidetext']);

		if ($options['append'] != '')
			$content .= $options['append'];

		return sprintf('<div class="inputWrapper">%s</div>', $content);
	}

	/**
	 * @return string HTML single checkbox
	 */
	public static function checkbox($label, $name, $value, $options = array()) {
		$content = "<label for=\"$name\">$label</label><input class=\"checkbox\" type=\"checkbox\" id=\"$name\" name=\"$name\" value=\"1\"".($value ? ' checked' : '')."".($options['disabled'] ? ' disabled' : '').">";

		if ($options['guidetext'] != '')
			$content .= FormHelper::getGuideText($options['guidetext']);

		return sprintf('<div class="inputWrapper checkboxWrapper">%s</div>', $content);
	}

	/**
	 * @return string HTML checkbox array
	 */
	public static function checkbox_array($label, $name, $data, $checked_values = array(), $options = array()) {

		// $data is array(<value> => <text>)
		// options['disabled'] contains an array of disabled values
		if (!$checked_values) $checked_values = array();
		
		$boxes .= '<div class="checkboxGroup">';
		foreach ($data as $value => $text) {
			$boxes .= "<label><input class=\"checkbox\" type=\"checkbox\" name=\"{$name}[]\" value=\"$value\"".(in_array($value, $checked_values) ? ' checked="checked"' : '')."".(in_array($value, is_array($options['disabled']) ? $options['disabled'] : array()) ? ' disabled' : '')."> $text</label>";
		}
		$boxes .= '</div>';

		return "<div class=\"inputWrapper\"><label>$label".($options['mandatory'] ? FormHelper::getMandatoryDesignator() : '')."</label>$boxes</div>";
	}
	
	/**
	 * @return string HTML radio buttons
	 */
	public static function radio($label, $name, $data, $checked_value, $options = array()) {

		// $data is array(<value> => <text>)
		if (!$checked_value and array_key_exists('default', $options)) $checked_value = $options['default'];
		
		$boxes .= '<div class="radioGroup">';
		foreach ($data as $value => $text) {
			$boxes .= "<label><input class=\"radio\" type=\"radio\" name=\"{$name}\" value=\"$value\"".($value == $checked_value ? ' checked' : '')."> $text</label>";
		}
		$boxes .= "</div>";

		$content = "<div class=\"inputWrapper\"><label>$label".($options['mandatory'] ? FormHelper::getMandatoryDesignator() : '')."</label>$boxes</div>";
		
		if ($options['guidetext']) $content .= FormHelper::getGuideText($options['guidetext']);
			
		return $content;
	}

	/**
	 * @return string HTML select
	 */
	public static function select($label, $name, $data, $checked_value, $options = array()) {
		$boxes = FormHelper::naked_select($name, $data, $checked_value, $options);
		
		$content = "<label for=\"$name\">$label".($options['mandatory'] ? FormHelper::getMandatoryDesignator() : '')."</label>$boxes";
		
		if ($options['guidetext']) $content .= FormHelper::getGuideText($options['guidetext']);
		
		return sprintf('<div class="inputWrapper">%s</div>', $content);
	}

	public static function naked_select($name, $data, $checked_value, $options = array()) {

		// $data is array(<value> => <text>)

		if ($options['onchange'])
			$onchange = " onchange=\"{$options['onchange']}\"";
		$required = $options['mandatory'] ? ' required' : '';
		$boxes .= "<select$required id=\"$name\" name=\"$name\"$onchange>";
		if (array_key_exists('emptyCaption', $options)) $boxes .= sprintf('<option value="">%s</option>', $options['emptyCaption']);
		foreach ($data as $value => $text) {
			$boxes .= "<option value=\"$value\"".($value == $checked_value ? ' selected' : '').">$text</option>";
		}
		$boxes .= '</select>';

		return $boxes;
	}

	public static function button($value, $options = array()) {
		return "<input class=\"button\" type=\"button\" value=\"$value\"".(isset($options['onclick']) ? " onclick=\"{$options['onclick']}\"" : '').">";
	}

	public static function submit($value = '', $options = array()) {

		$value = $value == '' ?  'Save' : $value;

		if ($options['onclick'])
			$onclick = " onclick=\"{$options['onclick']}\"";
		
		if ($options['id'])
			$id = " id=\"{$options['id']}\"";

		return "<input class=\"submit\" type=\"submit\" value=\"$value\"$onclick{$id}>";
	}

	public static function image($image, $options = array()) {
		$required = $options['mandatory'] ? ' required' : '';
		return "<label for=\"image\">&nbsp;</label><input$required id=\"image\" class=\"image\" type=\"image\" src=\"$image\"".($options['onclick'] ? " onclick=\"{$options['onclick']}\"" : '').">";
	}

	public static function close() {
		return '</form>';
	}

	public static function fieldset($legend, &$items) {
		
		if ($legend != '')
			$legend = '<legend>'.$legend.'</legend>';
		
		$content = '<fieldset>'.$legend;
		
		if (is_array($items)) foreach ($items as $item) $content .= $item;

		$content .= '</fieldset>';
		$items = array();
		return $content;
	}

	public static function getMandatoryDesignator() {
		return '<em>*</em>';
	}

	public static function getGuideText($text) {
		return "<div class=\"guidetext\">$text</div>";
	}
}
?>
