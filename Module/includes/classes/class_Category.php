<?php
class Category
{
	function __construct($category_id = false) {
		if ($category_id > 0) {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT *
								FROM listings_categories
								WHERE category_id = $category_id",
								'category constructor');
			$this->setData($rs->getRow());
		} else {
			$this->category_id = 0;
		}
	}

	function setData($data) {
		if (is_array($data)) {
			foreach($data as $key => $value) {
				$this->$key = $value;
			}
		}
	}

	public function getURL() {
		global $user;
		$link_parts[] = 'en/listings/itemlist';
		$link_parts[] = $user->getViewingCity()->getCityCode();
		$link_parts[] = $this->getCategoryCode();
		return '/'.implode('/', $link_parts).'/';
	}

	function getLink() {
	return "<a href=\"".$this->getURL()."\">".$this->getName()."</a>";
	}

	function getNameEn()
	{
	return $this->category_en;
	}

	function getName() {
		return $this->category_en;
	}

	public static function getCategoryIDFromName($category) {
		if ($category == 'all')
			return 0;
		else {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT category_id
								FROM listings_categories
								WHERE category_code='".$db->clean($category)."'");
			$row = $rs->getRow();
			return $row['category_id'];
		}
	}

	function getCategoryCode() {
		return $this->category_code;
	}

	function getParentID() {
		return $this->parent_id;
	}

	function hasItems() {
		$sql = "SELECT COUNT(*) AS tally FROM listings_i2c WHERE category_id=$this->category_id";
		$rs = execute($sql);
		$row = get_row($rs);
		return $row['tally'] == 0 ? false : true;
	}

	function setListURL($list_url) {
		$this->list_url = $list_url;
	}

	function setCityID($city_id) {
		$this->city_id = $city_id;
	}

	function displayPublicLink() {
		global $user;
		//$content .= count($this->city_ids);
		$content .= "<a href=\"/".$user->getLanguageCode()."/listings/itemlist/";

		if ($this->city_id != 0) {
			$city = new City($this->city_id);
			$content .= $city->getCityCode().'/';
		} else {
			$content .= 'china/';
		}

		$content .= $this->getCategoryCode()."/\">".$this->getName()."</a>";
		return $content;
	}

	function getAllSubCategoryIDs() {
		$ids = array();
		if ($this->category_id != 0) {
			$ids = $this->getImmediateSubCategoryIDs();

			foreach ($ids as $id) {
				$ids = array_merge($ids, $this->getImmediateSubCategoryIDs($id));
			}
		}
		
		array_unshift($ids, $this->category_id);
		return $ids;
	}
	
	
	public function getImmediateSubCategoryIDs($cat_id = false) {
		if ($cat_id === false) $cat_id = $this->category_id;
		return $GLOBALS['model']->db()->query('listings_categories', array('live' => 1, 'parent_id' => $cat_id), array('orderBy' => 'category_en', 'transpose' => 'category_id'));
	}
	
	
	function getSubCategories($cat_id = false) {
		$ids = array();
		$db = new DatabaseQuery;
		
		if ($cat_id === false) $cat_id = $this->category_id;
		
		$rs = $db->execute("SELECT category_id
							FROM listings_categories c
							WHERE parent_id = $cat_id
							AND live = 1
							ORDER BY category_en");
		
		while ($row = $rs->getRow()) {
			$ids[] = $row['category_id'];
			$ids = array_merge($ids, $this->getSubCategories($row['category_id']));
		}
	
		return $ids;
	}

	public function displayUnlinkedBreadcrumb() {
		$category_id = $this->category_id;
		$db = new DatabaseQuery;
		
		while ($category_id != 0) {
			$rs = $db->execute('SELECT category_en, parent_id
								FROM listings_categories
								WHERE category_id = '.$category_id);
			$row = $rs->getRow();
			$categories[] = $row['category_en'];
			$category_id = $row['parent_id'];
		}

		return implode(' > ', array_reverse($categories));
	}
	
	function displayBreadcrumb() {
		global $user;
		
		$category_id = $this->category_id;

		while ($category_id != 0) {
			$cat = new Category($category_id);
			$cat->setCityID($this->city_id);
			$links[] = $cat->getLink();
			$category_id = $cat->getParentID();
		}

		$this->breadcrumb = implode(" &gt; ", array_reverse($links));
		return $this->breadcrumb;
	}

	function displayForm() {
	$lcl = new CategoryList;

	$content = "<form action=\"form_category_proc.php\" method=\"post\">
	<input type=\"hidden\" name=\"category_id\" value=\"$this->category_id\">
	<table cellspacing=\"1\" class=\"gen_table\">
	<tr><td><b>Parent Category</b></td><td>".$lcl->getParentSelect($this->parent_id, $this->category_id)."</td></tr>
	<tr><td><b>Category Name (English)</b></td><td><input name=\"category_en\" value=\"$this->category_en\" size=\"40\"></td></tr>
	<tr><td><b>Category Name (Chinese)</b></td><td><input name=\"category_zh\" value=\"$this->category_zh\" size=\"40\"></td></tr>
	</table><br />
	<input type=\"submit\" value=\"Save\">
	</form>";
	return $content;
	}

	function delete()
	{
		if ($this->category_id!='')
		{
		execute("	UPDATE listings_categories
					SET live=0
					WHERE category_id=$this->category_id");
		}
	}

	function displayAdminRow()
	{
	$rs = execute("	SELECT COUNT(*) AS tally
					FROM listings_i2c
					WHERE category_id=$this->category_id");

	$row = get_row($rs);

		if ($row['tally']==0)
		{
			if ($this->live == 0)
			{
			$delete_control = "UNDELETE";//"<a href=\"delete_category.php?category_id=$this->category_id\" onClick=\"return conf_del()\">Delete</a>";
			}
			else
			{
			$delete_control = "<a href=\"delete_category.php?category_id=$this->category_id\" onClick=\"return conf_del()\">Delete</a>";
			}
		}

	$content = "<tr>
	<td>$this->category_id</td>
	<td>".$this->displayUnlinkedBreadcrumb()."</td>
	<td>$this->category_zh</td>
	<td>$this->live</td>
	<td>{$row['tally']}</td>
	<td><a href=\"form_category.php?category_id=$this->category_id\">Edit</a></td>
	<td>$delete_control</td>
	</tr>";
	return $content;
	}
	
	function displayAdminCategoryAdder($listing_id) {
		$lcl = new CategoryList();
		$content = $lcl->getAdminSelect($listing_id);
		return $content;
	}
}
?>
