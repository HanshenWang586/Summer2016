<?php

class Listings {
	
	public function getCategories($city_id = false, $addAll = false) {
		global $model;
		$city = $city_id ? sprintf(' AND city_id = %d', $city_id) : '';
		$cats = $model->db()->run_select('
			SELECT DISTINCT c.*
			FROM listings_categories c
			LEFT JOIN listings_sitecitycat scc ON (c.category_id = scc.category_id)
			WHERE c.parent_id = 0'
			. $city .
			' ORDER BY c.category_en'
		);
		if ($addAll) {
			array_unshift($cats, array(
				'category_code' => 'all',
				'category_en' => $model->lang('CATEGORY_ALL', 'ListingsModel', 'EN'),
				'category_cn' => $model->lang('CATEGORY_ALL', 'ListingsModel', 'CN')
			));
		}
		return $cats;
	}
	
}
	
?>