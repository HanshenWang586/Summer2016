<?
class SearchTools extends CMS_Class {
	private $data = array();
	
	public function init($args) {
	
	}
	
	public function processSearchString($q) {
		// Return empty array if $q is not a string
		if (!is_string($q)) return array();
		// Remove slashes, tags and whitespaces in the end and front of the string
		$q = strtolower(trim(strip_tags(stripslashes($q))));
		// Split the keywords into an array and remove all kinds of ugly characters
		return preg_split('/\s*[\s+\.|\?|\&|\`|\-|,|(|)|\+|>|<|\'|\\\|\"|=|;|�|\$|\/|:|{|}]\s*/i', $q, -1, PREG_SPLIT_NO_EMPTY);
	}
	
	public function getPager($params, $max = 10) {
		$content = '';
		if($params['pages'] > 1){
			$content = "<ul class=\"pager\">\n";
			
			$args = $this->model->args;
			unset($args['page'], $args['data']);
			
			$url = $this->url($args);
			
			// How many do we want to see at the time? We can't show a pager with 100 pages.. that would be too much!
			if ($max !== false && $max < $params['pages']) {
				$before = floor(($max - 1) / 2);
				$after = ceil(($max - 1) / 2);
				$index = $params['page'] - $before + 1;
				$upper = $params['page'] + $after + 1;
				if ($index < 1) {
					$upper += 1 - $index;
					$index = 1;
				} elseif ($upper > $params['pages']) {
					$index -= $upper - $params['pages'];
					$upper = $params['pages'];
				}
			} else {
				$index = 1;
				$upper = $params['pages'];
			}
			
			$linker = $this->tool('linker');
			
			if ($max && $index > 1) $content .= sprintf("\t<li><a class=\"firstItem\" href=\"%s\"><span>[%d]</span></a></li>\n", $linker->makeURL($url, array('page' => 1)), 1);
			if ($params['page'] > 1) $content .= sprintf("\t<li><a class=\"prevItem\" href=\"%s\"><span>%s</span></a></li>\n", $linker->makeURL($url, array('page' => $params['page'] - 1)), $this->lang('PAGER_PREVIOUS_PAGE'));
			for($index; $index <= $upper; $index++){
				$pageLink = $linker->makeURL($url, array('page' => $index));
				$class = ($params['page'] == $index - 1) ? ' activePage' : '';
				$content .= sprintf("\t<li><a href=\"%s\" class=\"page %s\"><span>%d</span></a></li>\n", $pageLink, $class, $index);
			}
			if ($params['page'] < $params['pages'] - 1) $content .= sprintf("\t<li><a class=\"nextItem\" href=\"%s\"><span>%s</span></a></li>\n", $linker->makeURL($url, array('page' => $params['page'] + 1)), $this->lang('PAGER_NEXT_PAGE'));
			if ($max && $upper < $params['pages']) $content .= sprintf("\t<li><a class=\"lastItem\" href=\"%s\"><span>[%d]</span></a></li>\n", $linker->makeURL($url, array('page' => $params['pages'])), $params['pages']);
			$content .= "</ul>\n";
		}
		return $content;
	}
}

?>