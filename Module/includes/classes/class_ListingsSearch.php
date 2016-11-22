<?php
class ListingsSearch {

	public function setCityID($city_id) {
		$this->city_id = $city_id;
		$this->city_ids = array($city_id);
	}

	public function setCityIDs($city_ids) {
		$this->city_ids = $city_ids;
	}
	
	public function setCategoryID($category_id) {
		$this->category_id = $category_id;
	}

	public function setShowTitle($bool) {
		$this->show_title = $bool;
	}

	public function setSearchString($ss) {
		$this->ss = urldecode($ss);
	}

	public function displayForm($id) {
		$city = new City($this->city_id);
		$content .= "<input type=\"text\" autocomplete=\"off\" name=\"ss\" onkeyup=\"processListingsSearch('$id', $this->city_id, this.value)\" />
		<div id=\"$id\"></div>";
		return $content;
	}

	/*public function displayMobileForm() {
		$city = new City($this->city_id);
		$content .= '<h1>Search '.$city->getName().' Listings</h1>';
		$content .= FormHelper::open('/en/listings/search/', array('method' => 'get'));
		$content .= FormHelper::hidden('city_id', $this->city_id);
		$f[] = FormHelper::input('', 'ss', $this->ss);
		$f[] = FormHelper::submit('&gt;');
		$content .= FormHelper::fieldset('', $f);
		$content .= FormHelper::close();
		return $content;
	}*/

	public function getResults($for_mobile = false) {
		global $user;
		$user->setViewingCityID($this->city_id);
		$categories = array();
		$items = array();

		$db = new DatabaseQuery;

		if (isset($this->category_id)) {
			// set up category we're viewing
			$category = new Category($this->category_id);
			// and create array of all sub-categories AND that category
			$category_ids = $category->getAllSubCategoryIDs();

			$sql = "SELECT 	DISTINCT d.*,
							phone_code,
							city_en,
							city_zh
					FROM listings_i2c i2c, listings_data d, listings_cities c
					WHERE i2c.listing_id = d.listing_id
					AND i2c.category_id IN (".implode(', ', $category_ids).")
					AND c.city_id = d.city_id
					AND d.status = 1
					AND c.live = 1
					AND d.city_id IN (".implode(', ', $this->city_ids).")
					ORDER BY name_en ASC";

			$max_results = 0;
			$rs = $db->execute($sql);

			while ($row = $rs->getRow()) {
				$li = new ListingsItem;
				$li->setData($row);
				$items[] = $li;
			}
		}
		else if (isset($this->city_ids) && $this->ss != '') {
			// categories
			$sql = "SELECT DISTINCT c.*
					FROM listings_categories c, listings_sitecitycat scc
					WHERE city_id IN (".implode(', ', $this->city_ids).")
					AND c.category_id = scc.category_id
					AND (
						category_en LIKE '%".$db->clean($this->ss)."%'
						OR category_zh LIKE '%".$db->clean($this->ss)."%'
						)
					ORDER BY category_en";

			$max_results = 30;
			$rs = $db->execute($sql);

			while ($row = $rs->getRow()) {
				$lc = new Category;
				$lc->setData($row);
				$categories[] = $lc;
			}

			// items
			$sql = "SELECT listing_id, name_en, name_zh, address_en, address_zh, phone, phone_code
					FROM listings_data d, listings_cities c
					WHERE d.city_id IN (".implode(', ', $this->city_ids).")
					AND d.city_id = c.city_id
					AND status = 1
					AND (
						name_en LIKE '%".$db->clean($this->ss)."%'
						OR name_zh LIKE '%".$db->clean($this->ss)."%'
						)
					ORDER BY name_en";

			$max_results = 30;
			$rs = $db->execute($sql);

			while ($row = $rs->getRow()) {
				$li = new ListingsItem;
				$li->setData($row);
				$items[] = $li;
			}
		}
		else {
			// categories
			$sql = "SELECT DISTINCT c.*
					FROM listings_categories c, listings_sitecitycat scc
					WHERE city_id IN (".implode(', ', $this->city_ids).")
					AND c.category_id = scc.category_id
					ORDER BY category_en";

			$max_results = 0;
			$rs = $db->execute($sql);

			while ($row = $rs->getRow()) {
				$lc = new Category;
				$lc->setData($row);
				$categories[] = $lc;
			}
		}

		$num_results = count($items) + count($categories);

		if (!$for_mobile && $num_results > $max_results && $max_results != 0)
			$content = $num_results.' results - please refine your search';
		else if ($num_results == 0 && !$this->show_title)
			$content = 'Sorry, no matches found';
		else {
			foreach ($categories as $category)
				$list_items[] = $category->displayBreadcrumb();

			foreach ($items as $li) {
				if ($for_mobile)
					$list_items[] = $li->getListItemMobile();
				else
					$list_items[] = $li->getListItem();
			}

			$content = HTMLHelper::wrapArrayInUl($list_items);
		}

		if ($this->show_title && $content != '')
			$content = '<h2>Listings</h2>'.$content;

		return $content;
	}
}
?>