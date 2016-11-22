<?php
class HTMLHelper {
	
	public static function link($uri, $text, $options = array()) {
		if ($options['external'])
			$target = ' target="_blank"';
		return '<a href="'.$uri.'"'.$target.'>'.$text.'</a>';
	}
	
	public static function wrapArrayInTr(&$data, $tr_class = '') {
		foreach ($data as $datum)
			$tds[] = '<td>'.$datum.'</td>';
		
		$content = '<tr';
		if ($tr_class != '')
			$content .= ' class="'.$tr_class.'"';
		$content .= '>'.implode('', $tds).'</tr>';
		return $content;
	}
	
	public static function wrapArrayInTh(&$data) {
		foreach ($data as $datum)
			$content .= '<th>'.$datum.'</th>';
		
		return '<tr>'.$content.'</tr>';
	}
	
	public static function wrapArrayInUl(&$data, $ul_id = '', $ul_class = '', $li_class = '') {
		if (is_array($data) && count($data)) {
			$content = '<ul';
			if ($ul_id) $content .= ' id="'.$ul_id.'"';
			if ($ul_class) $content .= ' class="'.$ul_class.'"';
			$content .= '>';
			if ($li_class) $li_class = ' class="'.$li_class.'"';
			foreach ($data as $datum)
				$content .= '<li'. $li_class .'>'.$datum.'</li>'; // don't put a \n here.
			$content .= '</ul>';
			$data = array();
			return $content;
		}
	}
}
?>
