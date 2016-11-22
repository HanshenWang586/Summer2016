<?php
class ListingsList
{
var $city_ids = array(1);

	public function getSquashList(&$pager) {
		$rs = $pager->setSQL("SELECT *
							 FROM listings_data
							 WHERE city_id = 1
							 AND status = 1
							 ORDER BY ts_squashed ASC");

		$content .= "<table class=\"gen_table\" cellspacing=\"1\">
		<tr><td><b>Name</b></td>
			<td><b>Address</b></td>
			<td><b>Contact</b></td>
			<td><b>Description</b></td>
			<td></td></tr>";

		while ($row = $rs->getRow()) {
			$li = new ListingsItem($row['listing_id']);
			$content .= "
				<tr valign=\"top\">
					<td>
						{$row['name_en']}<br />
						<span class=\"chinese\">{$row['name_zh']}</span>
					</td>
					<td>{$row['address_en']}<br />
					<span class=\"chinese\">{$row['address_zh']}</span>
				</td>
				<td>
					Phone:<br />{$row['phone']}<br /><br />
					Fax:<br />{$row['fax']}<br /><br />
					Web:<br />{$row['url']}
				</td>
				<td>".nl2br($row['description'])."<br /><br />".$li->getCategoriesList()."</td>
				<td>
					<a href=\"".str_replace('/item/', '/form/', $li->getURL())."\" target=\"_blank\">Edit</a><br />
					<a href=\"squash.php?listing_id={$row['listing_id']}&page=".$pager->getCurrentPage()."\">Squash</a><br />
					<a href=\"delete_item.php?listing_id={$row['listing_id']}&return=squash&page=".$pager->getCurrentPage()."\" onclick=\"return conf_del();\">Remove</a><br />
				</td>
			</tr>";
		}

		$content .= "</table>";

		return $content;
	}

	function ListingsList($user='') {
		$this->user = $user;
	}

	function getTitle() {
		return $this->title;
	}

	function setCategoryID($category_id) {
		$this->category_id = $category_id;
	}

	function setCityID($city_id) {
		$this->city_id = $city_id;
	}

	public function getUnorderedList() {
		global $user;

		$reviews = array();
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT listing_id
							FROM listings_data
							WHERE status = 1
							AND city_id = '.$this->city_id.'
							ORDER BY ts_added DESC
							LIMIT 10');

		while ($row = $rs->getRow()) {
			$item = new ListingsItem($row['listing_id']);
			$additions[] = '<a href="'.$item->getURL().'">'.$item->getName().'</a>';
		}

		if (count($additions)) {
			return '<h2>Latest Additions</h2>'.
			HTMLHelper::wrapArrayInUl($additions);
		}
	}
	
	public function getListingsSQL($city_id = false, $category_id = false, $search = false, $orderBy = false, $order = false) {
		global $user, $model;
		if ($category_id and $category_id != 'all') {
			$category = new Category($category_id);
			// and create array of all sub-categories AND that category
			$category_ids = $category->getAllSubCategoryIDs();
		}
		
		if ($search) $search = $model->db()->escape_clause($search);
		if ($city_id) $city = sprintf(' AND d.city_id = %d ', $city_id);
		
		if ($orderBy == 'rating') {
			$select = ', AVG( stars ) AS rating, COUNT( stars ) AS total_ratings';
			$join = 'LEFT JOIN listings_reviews r ON ( r.listing_id = d.listing_id )';
			$city .= ' AND r.ts > DATE_SUB(NOW( ), INTERVAL 9 MONTH)';
			$having = ' HAVING rating > 0 ';
			$orderBy = 'rating DESC, total_ratings';
		}
		
		$sql =	'SELECT DISTINCT d.*,
						phone_code,
						city_en,
						city_zh ' .
				$select .
				' FROM listings_data d
				LEFT JOIN listings_i2c i2c ON (i2c.listing_id = d.listing_id)
				LEFT JOIN listings_cities c ON (c.city_id = d.city_id) ' .
				$join .
				' WHERE d.status = 1
				'. ($category_ids ?  ((count($category_ids) ? 'AND i2c.category_id IN ('.implode(', ', $category_ids).')' : '')) : '') .
				$city .
				($search ?
					" AND (
						name_en LIKE '%".$search."%'
						OR name_zh LIKE '%".$search."%'
					)"
					: '') .
				' AND c.live = 1 GROUP BY d.listing_id' .
				$having .
				($orderBy ? ' ORDER BY ' . $orderBy . ' ' . $order : '');
		return $sql;
	}

	public function getListings(&$pager, $city_id = 0, $category_id = 0, $search = false, $orderBy = 'ts_updated', $order = 'DESC') {
		$sql = $this->getListingsSQL($city_id, $category_id, $search, $orderBy, $order);
		$rs = $pager->setSQL($sql);
		$items = '';
		$li = new ListingsItem;
		while ($row = $rs->getRow()) {
			$li->setData($row);
			$items .= $li->displayPublic();
		}

		return '<section class="listingsList">' . $items . '</section>';
	}
	
	public function getLatest(&$pager, $city_id = 0, $category_id = 0, $search = false) {
		$sql = $this->getListingsSQL($city_id, $category_id, $search, 'ts_added', 'DESC');
		$rs = $pager->setSQL($sql);
		$items = '';
		$li = new ListingsItem;
		while ($row = $rs->getRow()) {
			$li->setData($row);
			$items .= $li->displayPublic(true);
		}

		return '<section class="listingsList">' . $items . '</section>';
	}
	
	public function getHomepageListingsBox($category) {
		global $model;
		$path = 'listings/home/' . http_build_query(array('category' => $category));
		if (!$content = $model->tool('cache')->get($path)) {
			$view = new View('listings/homepage_box.html');
			
			$rl = new ReviewList;
			$pager = new Pager;
			$pager->setLimit(5);
			$latest = $rl->getReviews($pager, false, true, $category, true);
			$view->setTag('latestReviews', $latest);
			$view->setTag('recent', $this->getLatest($pager, 1, $category));
			
			$view->setTag('topRated', $this->getListings($pager, 1, $category, false, 'rating'));
			
			$content = $view->getOutput();
			$model->tool('cache')->set($path, $content, 1);
		}
		return $content;
	}
}
?>