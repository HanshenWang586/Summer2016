<?
class MenuTools extends CMS_Class {
	public $activeItem;

	public function init($args) {
		$this->setExtensions($extensions);
		$this->setUploadFolder($uploadFolder);
	}
	
	/**
	 * builds a menu starting from a given navigation node
	 *
	 * @param mixed $menu can be an array (corresponding with the result of $pageBuilder->getMenuLevel),
	 * 			an integer (the ID of the Menu to select) or string (name of the Menu to select)
	 * @param integer $maxDepth the maximum depth of iteration down the menu tree
	 * @param array $params extra parameters to be used when constructing the menu. Possible values:
	 *  - integer $addHelperSpans The number of helper spans to add to the link (<a> element), or none when left empty
	 *  - string $params['addToLink'] - appends the contained string / html to the link (<a> element)
	 *  - $params['addTitle'] (boolean) - if true, adds an H2 title before the menu
	 *  + $param string 'id' adds an id UL and, when applicable, an id with 'Title' added as suffix to the generated H2
	 *  + $param string 'ulClass' adds a class to the UL and when applicable, a class with 'Title' added as suffix to the generated H2
	 *
	 * @return string the html of the menu
	 */
	public function getMenu($menu, $maxDepth = 2, $params = array()) {
		if (!$menu) return '';
		$this->activeItem = false;
		$level = 1;

		if ($i = request($params['addHelperSpans']) and is_numeric($i)) {
			ifNot($params, '', 'addToLink');
			for ($j = 1; $j <= $i; $j++) $params['addToLink'] .= sprintf('<span class="helper%d">&nbsp;</span>', $j);
		}

		if ((is_array($menu) and $parent = $menu[0]['pid']) || (is_numeric($menu) and $parent = $menu)) {
			$title = query('navigation', false, $parent, array('selectField' => 'name'));
		} elseif (is_string($menu)) $title = $menu;

		$params['parentTitle'] = $title;
		$content = $this->getMenuLevel($menu, $level, $maxDepth, $params);

		if ($content && $params['addTitle'] && $title) {
			$id = $params['id'] ? sprintf(' id="%sTitle"', $params['id']) : '';
			$class = $params['ulClass'] ? sprintf(' class="%sTitle"', $params['ulClass']) : '';
			$content = sprintf("\t\t<h2%s%s>%s</h2>\n", $id, $class, $title) . $content;
		}

		return $content;
	}

	/**
	 * This function constructs the menu for one level under a given navigation node. By means of recursion this function will construct deeper levels under the navigation node as deep as is specified by maxDepth
	 *
	 * @param mixed $menu the navigation node to construct
	 * @param integer $level the current level inside the navigation structure
	 * @param integer $maxDepth the maximum depth of the menu
	 * @param array $params extra parameters to be used when constructing the menu. Possible values:
	 * 				@param string 'id' adds an id to the ul element
	 * 				@param string 'ulClass' adds a class to the ul element
	 * 				@param boolean 'zebra' adds a class odd or even to each navigation item
	 * 				@param boolean 'withImage' add the image selected in ewyse to the navigation item
	 * 				@param string 'addToLink' adds inside the navigation link
	 *
	 * @return string the html of the menulevel
	 */
	private function getMenuLevel($menu, $level, $maxDepth, $params) {
		if (!is_array($menu)) $menu = $this->pageBuilder->getMenuLevel($menu);
		if (!$menu) return false;

		$tabs = str_repeat("\t", $level * 2);

		$class = "level" . $level;
		$content .= sprintf("\n%s<ul%s class=\"%s %s\">\n", $tabs, ($level == 1 and $params['id']) ? sprintf(' id="%s"', $params['id']) : '', $class, $params['ulClass']);
		$count = count($menu);
		for($i = 0; $i < $count; $i++) {
			$menuItem = $menu[$i];
			$itemClass = $class;
			if (request($params['zebra'])) $itemClass .= $i % 2 == 0 ? ' even' : ' odd';
			if ($i == 0) $itemClass .= ' first';
			if (($count - 1) == $i) $itemClass .= ' last';
			if ($menuItem['activeItem']) {
				$itemClass .= " activeItem";
				$this->activeItem = $menuItem;
			}
			$content .= sprintf("%s\t<li class=\"%s\">\n", $tabs, $itemClass);
			// Don't use the pretty URL for external URLs
			if (strpos($menuItem['url'],'http') !== 0) {
				if ($menuItem['prettyURL'] and !$this->pageBuilder->adminMode) {
					$menuItem['url'] = $menuItem['prettyURL'];
				}
			}
			// Add an image?
			if (request($params['withImage']) and is_numeric($menuItem['asset_id'])) $image = $this->pageBuilder->getTool('linker')->getImageLink($menuItem['asset_id']);
			$content .= sprintf("%s\t\t<a href=\"%s\" class=\"%s\">%s%s<span class=\"caption\">%s</span></a>\n", $tabs, $menuItem['url'], $itemClass, request($params['addToLink']), request($image), $menuItem['name']);
			if ($level < $maxDepth) $content .= $this->getMenuLevel($menuItem['id'], $level + 1, $maxDepth, $params);
			$content .= sprintf("%s\t</li>\n", $tabs);
		}
		$content .= sprintf("%s</ul>\n", $tabs);
		return $content;
	}

	/**
	 * Constructs a breadcrum
	 *
	 * @param boolean $skipFirst set to false will not include the navigation item at the first level. When using pretty url's this is the root
	 * @param boolean $hideIfDepthIsOne The breadcrum will not be returned if there is only one navigation item
	 *
	 * @return string the html of the breadcrumb
	 */
	public function getBreadCrumb($skipFirst = false, $hideIfDepthIsOne = false){
		$first = true;
		$breadCrum = '';
		if($this->pageBuilder->activeItems){
			$data = (db_select('navigation', false, $this->pageBuilder->activeItems, "name,id"));
			if (!$hideIfDepthIsOne || (count($data) > 1) && !$skipFirst || count($data) > 2) {
				foreach($data as $field){
					if (($first && !$skipFirst) || !$first) {
						$breadCrum .= sprintf(" / <a href=\"?id=%d\">%s</a>", $field['id'], $field['name']);
					} else {
						$first = false;
					}
				}
				return '<div id="breadCrum">'. $breadCrum .'</div>';
			}
		}
	}

	function getActiveSubMenu($menu) {
		foreach($menu as $item) {
			if ($item['activeItem']) return $item;
		}
		return false;
	}
}

?>