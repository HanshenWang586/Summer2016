<?php
function load_category($category_id) {
	static $categories = array();

	if (in_array($category_id, array_keys($categories)))
		return $categories[$category_id];
	else {
		$category = new Category($category_id);
		$categories[$category_id] = $category;
		return $category;
	}
}
?>