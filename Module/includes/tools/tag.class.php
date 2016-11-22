<?php

class TagTools extends CMS_Class {
	
	public function init($args) {
		
	}
	
	public function getMeta($array = false) {
		if (!$array || !is_array($array)) return '';
		$html = '';
		if ($array) foreach ($array as $key => $val) {
			if (trim($val)) $html .= sprintf(' data-%s="%s"', $key, htmlspecialchars($val));
		}
		return $html;
		/*
		$md = array();
		foreach($array as $key => $val) {
			if (is_bool($val)) $val = sprintBool($val);
			elseif (is_numeric($val));
			elseif (is_string($val)) $val = "'" . addslashes($val) . "'";
			elseif (is_array($val)) $val = $this->getMeta($val);
			$md[] = sprintf("%s: %s", $key, $val);
		}
		return sprintf("{%s}", implode(', ', $md));
		*/
	}
	
	public function tag($type = 'span', $content = '', array $args = array()) {
		if (is_array($type)) {
			$content = request($type['content']);
			$args = ifElse($type['args'], array());
			$type = request($type['type']);
		}
		
		$attr = ifElse($args['attr'], array());
		$type = ifElse($type, 'span');
		
		$class = ifElse($args['class'], request($attr['class']));
		$classes = $class ? is_array($class) ? $class : explode(' ', $class) : array();
		
		$meta = (array_key_exists('meta', $args) and is_array($args['meta'])) ? $this->getMeta($args['meta']) : '';
		
		$attr['class'] = $classes;
		
		$tag = sprintf('<%s%s%s', $type, $this->generateAttr($attr), $meta);
		if (request($args['nonClosing'])) $tag .= request($args['xml']) ? '/>' : '>'; 
		else $tag .= sprintf('>%s</%s>', makeSafeEntities($content), $type);
		
		return $tag;
	}
	
	function select($name, array $options, $currentSelection, $params = array()) {
		$content = '';
		ifNot($currentSelection, request($params['default']));
		
		if (!isset($params['attr'])) $params['attr'] = array();
		if (request($params['id']) !== false) $params['attr']['id'] = $id = ifElse($params['id'], 'select' . ucfirst($name));
		else $id = false;
		$params['attr']['name'] = $name;
		$params['nonClosing'] = true;
		
		$content .= $this->tag('select', false, $params);
		if (!request($params['noEmpty'])) $content .= sprintf("\t\t\t\t\t<option value=\"\">%s</option>\n", ucfirst(array_get_set($params, array('emptyCaption', 'caption'), $name)));
		
		$keyValue = isset($params['key']) && isset($params['value']);
		// See if we have an indexed or associative array
		if (!$keyValue) $listIsVector = is_vector($options);
		foreach($options as $value => $caption) {
			if ($keyValue) {
				$value = $caption[$params['key']];
				$caption = $caption[$params['value']];
			} elseif ($listIsVector) $value = $caption;
			$selected = $currentSelection == $value ? ' selected="selected"' : '';
			
			$content .= sprintf("\t\t\t\t\t<option value=\"%s\" %s>%s</option>\n", $value, $selected, $caption);
		}
		$content .= "\t\t\t\t</select>\n";
	
		if ($caption = request($params['caption'])) {
			$labelClass = array('select');
			// Add useful classes
			if (isset($options['labelClass'])) $labelClass[] = $options['labelClass'];
			if (request($options['labelAfter'])) $labelClass[] = 'labelAfter';
			// Seperate the label from the input if we have an ID (so we can use the 'for' attribute)
			if ($id) {
				$labelClass[] = 'inputOutside';
				$label = sprintf('<label class="%s" for="%s">%s</label>', join(' ', $labelClass), $id, makeSafeEntities($caption));
				if (!$options['labelAfter']) $content = $label . $content;
				else $content .= $label;
			// Otherwise we put the input inside the label
			} else {
				$labelClass[] = 'inputInside';
				$caption = sprintf('<span class="text">%s</span>', $caption);
				$temp = sprintf('<label class="%s">', join(' ', $labelClass));
				if (!$options['labelAfter']) $temp .= $caption . '&nbsp;' . $content;
				else $temp .= $content . '&nbsp;' . $caption;
				$content = $temp . '</label>';
			}
		}
		
		return $content;
	}
	
	public function input(array $args) {
		$attr = isset($args['attr']) ? $args['attr'] : array();
		$caption = request($args['caption']);
		$default = request($args['default']);
		$value = request($args['value']);

		$classes = isset($attr['class']) ? (is_array($attr['class']) ? $attr['class'] : explode(' ', $attr['class'])) : array();

		ifNot($attr, 'text', 'type');
		$classes[] = $attr['type'];
		
		if (isset($args['placeholder'])) $attr['placeholder'] = $args['placeholder'];
		
		if (isset($args['readonly']) and ($args['readonly'] === true)) {
			$classes[] = 'readonly';
			$attr['readonly'] = 'readonly';
		}
		
		$attr['class'] = $classes;
		if (preg_match('/radio|checkbox/i', $attr['type']) && (!$value && $default === true || $default === $attr['value']) || ($value && ($value == $attr['value'] || is_array($value) && in_array($attr['value'], $value)))) {
			$attr['checked'] = 'checked';
		}
		
		$args['attr'] = $attr;
		$args['nonClosing'] = true;
		
		$input = $this->tag('input', false, $args);
		
		if ($caption) {
			$labelAttr = ifElse($args['labelAttr'], array());
			$labelAttr['class'] = (array) request($labelAttr['class']);
			if (request($attr['id'])) {
				if (request($args['labelAfter']) || $attr['type'] == 'radio') $labelAttr['class'][] = 'after';
				$labelAttr['for'] = $attr['id'];
				$label = sprintf('<label%s>%s</label>', $this->generateAttr($labelAttr), makeSafeEntities($caption));
				if (request($args['labelAfter']) || $attr['type'] == 'radio') $input .= $label;
				else $input = $label . $input;
			} else {
				$temp = sprintf('<label%s>', $this->generateAttr($labelAttr));
				$caption = sprintf('<span class="text">%s</span>', $caption);
				if (request($args['labelAfter']) || $attr['type'] == 'radio')  $temp .= $input . '&nbsp;' . $caption;
				else $temp .= $caption . '&nbsp;' . $input;
				$input = $temp . '</label>';
			}
		}
		return (request($args['wrap']) ? sprintf("<div>%s</div>", $input) : $input) . "\n";
	}
	
	private function generateAttr(array $attr) {
		$html = '';
		if (isset($attr['class']) && is_array($attr['class'])) $attr['class'] = implode(' ', $attr['class']);
		if ($attr) foreach ($attr as $key => $val) {
			if (trim($val)) $html .= sprintf(' %s="%s"', $key, htmlspecialchars($val));
		}
		return $html;
	}
}