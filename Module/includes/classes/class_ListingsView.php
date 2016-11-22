<?php
class ListingsView
{

	public function setCategoryID($category_id) {
		$this->category_id = $category_id;
	}

	public function setCityID($city_id) {
		$this->city_id = $city_id;
	}

	function getListingIDs() {
		$category_ids = array();
		$category_ids[] = $this->user->getCategoryID();
	
		$lc = new Category($this->user->getCategoryID());
		$lc->setCityID($this->user->getCityID());
	
		$category_ids = array_merge($category_ids, $lc->getSubCategories());
	
		$listing_ids = array();
		$rs = execute("	SELECT DISTINCT d.listing_id
						FROM listings_i2c i2c, listings_data d, listings_cities c
						WHERE i2c.category_id IN (".implode(', ', $category_ids).")
						AND i2c.listing_id=d.listing_id
						AND c.city_id=d.city_id
						AND d.status=1
						".($this->user->getCityID()!=0 ? "AND d.city_id=".$this->user->getCityID() : ''));
		
		while ($row = get_row($rs)) {
			$listing_ids[] = $row['listing_id'];
		}
		
		return $listing_ids;
	}

	function display($pager)
	{
	global $user;

	// set up category we're viewing
	$category = new Category($user->getCategoryID());
	// and create array of all sub-categories AND that category
	$category_ids = $category->getAllSubCategoryIDs();

	$linker = new Linker;
	$this->linked_breadcrumb = $linker->getLinkedBreadcrumb(); // see this->getPageTitle

	$content .= "<div id=\"ccl_breadcrumb\"><h1>".$this->linked_breadcrumb."</h1></div>";
	$content .= "<div id=\"ccl_refine\">".$linker->getSubCategoryList()."</div>";

		if ($user->getCityID() != 0) // viewing a specific city, not viewing 'china'
		{
		$city_ids = array($user->getCityID());
		}
		else // viewing 'china', so we can use all city_ids
		{
		$city_ids = $user->getCityIDs();
		}

	$sql =	"	SELECT 	DISTINCT d.*,
						phone_code,
						city_en,
						city_zh
				FROM listings_i2c i2c, listings_data d, listings_cities c
				WHERE 1=1
				".(count($category_ids) ? "AND i2c.category_id IN (".implode(', ', $category_ids).")" : '')."
				AND i2c.listing_id=d.listing_id
				AND c.city_id=d.city_id
				AND d.status=1
				AND c.live=1
				".(count($city_ids) ? "AND d.city_id IN (".implode(',', $city_ids).")" : '')."
				ORDER BY name_en ASC";

	$content .= $this->displayResults($pager, $sql);
	return $content;
	}

	/*public function displayMobile() {

	// set up category we're viewing
	$category = new Category($this->category_id);
	// and create array of all sub-categories AND that category
	$category_ids = $category->getAllSubCategoryIDs();

	$linker = new Linker;
	$content .= "<div id=\"ccl_breadcrumb\"><h1>".$linker->getLinkedBreadcrumb()."</h1></div>";
	$content .= "<div id=\"ccl_refine\">".$linker->getSubCategoryList()."</div>";

	$sql =	"	SELECT 	DISTINCT d.*,
						phone_code,
						city_en,
						city_zh
				FROM listings_i2c i2c, listings_data d, listings_cities c
				WHERE i2c.listing_id=d.listing_id
				".(count($category_ids) ? "AND i2c.category_id IN (".implode(', ', $category_ids).")" : '')."
				AND c.city_id=d.city_id
				AND d.status=1
				AND c.live=1
				AND d.city_id = $this->city_id
				ORDER BY name_en ASC";

	$content .= $this->displayResultsMobile($sql);
	return $content;
	}*/

	function displayResults($pager, $sql)
	{
	$rs = $pager->setSQL($sql);

		while ($row = $rs->getRow())
		{
		$li = new ListingsItem;
		$li->setUser($this->user);
		$li->setListURL($this->list_url);
		$li->setItemURL($this->item_url);
		$li->setData($row);
		$items[] = $li->displayPublic(true);
		}

		// pagination navigation (top)
		if ($pager->hasMultiplePages()) {
			$content .= $pager->getNav();
		}

	$content .= HTMLHelper::wrapArrayInUl($items);

		// pagination navigation (bottom)
		if ($pager->hasMultiplePages()) {
		$content .= $pager->getNav();
		}

	return $content;
	}

	/*function displayResultsMobile($sql) {

		$db = new DatabaseQuery;
		$rs = $db->execute($sql);

		while ($row = $rs->getRow())
		{
			$li = new ListingsItem;
			$li->setUser($this->user);
			$li->setListURL($this->list_url);
			$li->setItemURL($this->item_url);
			$li->setData($row);
			$items[] = "<li>".$li->displayPublic(true)."</li>";
		}

		$content = "<ul>".@implode($separator, $items)."</ul>";

		return $content;
	}*/

	function displaySearch($pager, $search)
	{
	$separator = "<div class=\"ccl_separator\"></div>";

	$lc = new Category($search->getCategoryID());
	$lc->setCityID($search->getCityID());
	$content .= "<br />
	<b>Searching within:</b> ".$lc->displayBreadcrumb()."<br />
	<br />";

	//$content .= "<div style=\"float:right\">".$this->getSorter()."</div>";

	$sql = $search->buildSQL();

	if ($sql!='')
	{

	$rs = $pager->setSQL($sql, true);
	//$sql_time = microtime_float() - $start;

		if (get_num($rs)>0)
		{
			while ($row = get_row($rs))
			{
			$li = new ListingsItem;
			$li->setUser($this->user);
			$li->setListURL($this->list_url);
			$li->setItemURL($this->item_url);
			$li->setData($row);
			$items[] = $li->displayPublic($this->user, true);
			}

			// pagination navigation (top)
			if ($pager->hasResults())
			{
			$content .= $pager->getNumberFlow();
			$content .= '<br /><br />';
			}

			// pagination navigation (top)
			if ($pager->hasMultiplePages())
			{
			$content .= $pager->getPrevNext();
			$content .= CCL_LIST_SEPARATOR;
			$content .= $pager->getPageList();
			}

			if ($pager->hasResults())
			{
			$content .= $separator.implode($separator, $items).$separator;
			}

			// pagination navigation (bottom)
			if ($pager->hasMultiplePages())
			{
			$content .= '<br />';
			$content .= $pager->getPrevNext();
			$content .= CCL_LIST_SEPARATOR;
			$content .= $pager->getPageList();
			}
		}
		else
		{
		$content .= "Sorry, we've found nothing that matches your search";
		}
	}

	return $content;
	}

	function getSorter()
	{
	$search = new Search;
	$search->setCityID($this->user->getCityID());
	$search->setCategoryID($this->user->getCategoryID());
	$content = $search->getSorter();
	return $content;
	}

	function getPageTitle() {
		return strip_tags($this->linked_breadcrumb);
	}
}
?>