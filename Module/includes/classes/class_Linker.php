<?php
class Linker
{
	function setCityID($city_id)
	{
	$this->city_id = $city_id;
	}

	function setCategoryID($category_id)
	{
	$this->category_id = $category_id;
	}

	public function getLinkedBreadcrumb($category_id = 0) {
		global $user;

		$links = array();
		$output_links = array();
		//$category_id = $user->getViewingCategoryID();

		// track back up the chain of categories
		while ($category_id != 0) {
			$category = new Category($category_id);
			$links[] = array(	'text' => $category->getName(),
								'url' => $category->getCategoryCode());
			$category_id = $category->getParentID();
		}

		if (is_object($user->getViewingCity())) {
			$city = $user->getViewingCity();
			$links[] = array(	'type' => 'city',
								'text' => $city->getName(),
								'url' => $city->getCityCode());
		}
		else	{
			$links[] = array(	'text' => 'All Listings',
								'url' => 'all');
		}

		foreach($links as $link) {
			if ($link['type'] != 'city') {
				$output_links[] = $this->buildLink($link);
			}
			else {
				$output_links[] = "<a href=\"/en/listings/city/{$link['url']}/\">{$link['text']}</a>";
			}
		}

		return implode(' > ', array_reverse($output_links));
	}

	public function getSubCategoryList($category_id = 0) {
		global $user;
		$links = array();
		$output_links = array();

		$category = new Category($category_id);
		$city = $user->getViewingCity();

		$sub_category_ids = array_intersect($category->getImmediateSubCategoryIDs(), $city->getCategoryIDs());

		foreach ($sub_category_ids as $sub_category_id) {
			$category = new Category($sub_category_id);
			$links[] = array(	'text' => $category->getName(),
								'url' => $category->getCategoryCode());
		}

		foreach($links as $link)
			$output_links[] = $this->buildLink($link);

		$content .= count($output_links) ? 'Refine by: ' : '';
		$content .= HTMLHelper::wrapArrayInUl($output_links);
		return $content;
	}

	function setAllowedCityIDs($city_ids)
	{
	$this->allowed_city_ids = $city_ids;
	}

	function setAllowedCategoryIDs($category_ids)
	{
	$cat_ids = array();

		foreach ($category_ids as $category_id)
		{
		$lc = new Category($category_id);
		$cat_ids = array_merge($cat_ids, $lc->getAllSubCategoryIDs(), array($category_id));
		}

	$this->allowed_category_ids = array_unique($cat_ids);
	}

	private function buildLink($link) {
		global $user;
		$content = '<a href="/en/listings/itemlist/';

		if (is_object($user->getViewingCity()))
			$content .= $user->getViewingCity()->getCityCode().'/';
		else
			$content .= 'china/';

		$content .= $link['url'].'/">'.$link['text'].'</a>';
		return $content;
	}
}
?>