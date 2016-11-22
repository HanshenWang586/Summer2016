<?php
class CategoryList {
	private $category_ids;
	
	public function __construct($cat_ids = false) {
		if ($cat_ids) $this->setCategoryIDs($cat_ids);
	}
	
	function setCategoryIDs($category_ids) {
		$this->category_ids = $category_ids;
	}

	public function getPickList() {
		if (count($this->category_ids) > 0) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT *
								FROM listings_categories
								WHERE category_id IN ('.implode(', ', $this->category_ids).')
								ORDER BY category_en');

			while ($row = $rs->getRow()) {
				$category = new Category;
				$category->setData($row);
				$categories[] = $category->getLink();
			}

			return HTMLHelper::wrapArrayInUl($categories);
		}
	}

	function getCategoriesText()
	{
		if (count($this->category_ids) > 0)
		{
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *
							FROM listings_categories
							WHERE category_id IN (".implode(', ', $this->category_ids).")
							ORDER BY category_en");

			while ($row = $rs->getRow())
			{
			$category = new Category;
			$category->setData($row);
			$categories[] = $category->getNameEn();
			}

		return implode(', ', $categories);
		}
	}

	function displayAdmin()
	{
	$rs = execute("	SELECT *
					FROM listings_categories c
					WHERE parent_id=0
					ORDER BY category_en ASC");

	$rs_2 = execute("	SELECT c.category_id, c.category_en, c.category_zh, cc.category_en AS parent_en, c.live
						FROM listings_categories c, listings_categories cc
						WHERE c.parent_id=cc.category_id
						AND c.parent_id!=0
						ORDER BY c.category_en ASC");

	$content = "<table cellspacing=\"1\" class=\"gen_table\">
	<tr>
	<td><b>ID</b></td>
	<td><b>Category (English)</b></td>
	<td><b>Category (Chinese)</b></td>
	<td><b>Live</b></td>
	<td><b>Tally</b></td>
	<td colspan=\"2\"></td>
	</tr>";
		while ($row = get_row($rs))
		{
		//print_r($row);
		$lc = new Category;
		$lc->setData($row);
		$content .= $lc->displayAdminRow();
		}

		while ($row = get_row($rs_2))
		{
		//print_r($row);
		$lc = new Category;
		$lc->setData($row);
		$content .= $lc->displayAdminRow();
		}
	$content .= "</table>";
	return $content;
	}

	function getParentSelect($parent_id, $omit_category_id='')
	{
	$content = "<select name=\"parent_id\">";
	$rs = execute("	SELECT *
					FROM listings_categories
					".($omit_category_id!='' ? "WHERE category_id!=$omit_category_id" : '')."
					ORDER BY category_en");
	$content .= "<option value=\"0\"".(0==$parent_id ? ' selected' : '').">[root category]</option>";

		while ($row = get_row($rs))
		{
		//$lc = new Category($row['category_id']);
		$lc = load_category($row['category_id']);
		$categories[$row['category_id']] = $lc->displayUnlinkedBreadcrumb();
		}

	asort($categories);

		foreach ($categories as $category_id => $text)
		{
		$content .= "<option value=\"$category_id\"".($category_id==$parent_id ? ' selected' : '').">$text</option>";
		}

	$content .= "</select>";
	return $content;
	}

	function getAdminSearchSelect($listing_id)
	{
	$lc = new Category(0);
	$category_ids = $lc->getSubCategories();

	$content = "<select name=\"category_id\">
	<option value=\"\">All</option>";

		foreach ($category_ids as $category_id)
		{
		$lc = new Category($category_id);
		$content .= "<option value=\"$category_id\"".($listing_id==$category_id ? ' selected' : '').">".$lc->displayUnlinkedBreadcrumb()."</option>";
		}

	$content .= "</select>";
	return $content;
	}

	function getAdminSelect($listing_id)
	{
	$lc = new Category(0);
	$category_ids = $lc->getSubCategories();

	$content = "<select name=\"category_id\" onChange=\"if(this.value!=''){location.href='form_add_category_proc.php?listing_id=$listing_id&category_id=' + this.value}\">
	<option value=\"\">Please select...</option>";

		foreach ($category_ids as $category_id)
		{
		$lc = new Category($category_id);
		$content .= "<option value=\"$category_id\">".$lc->displayUnlinkedBreadcrumb()."</option>";
		}

	$content .= "</select>";
	return $content;
	}

	public function getPublicSelect($listing_id) {
		global $user;

		$lc = new Category(0);
		$category_ids = $lc->getSubCategories();

		$content = "<select name=\"category_id\" onChange=\"ajax_action_categorize_add($listing_id, this.value)\">
		<option value=\"\">Please select...</option>";

		foreach ($category_ids as $category_id) {
			$category = new Category($category_id);
			$content .= "<option value=\"$category_id\"".($category_id==$selected_category_id ? ' selected' : '').">".$category->displayUnlinkedBreadcrumb()."</option>";
		}

		$content .= '</select>';
		return $content;
	}

	function getPublicCheckboxes($listing_id)
	{
	global $user;

	$lc = new Category(0);
	$category_ids = $lc->getSubCategories();

		foreach ($category_ids as $category_id)
		{
		$lc = new Category($category_id);
		$content .= "<input type=\"checkbox\" value=\"$category_id\"> ".$lc->displayUnlinkedBreadcrumb()."<br />";
		}

	return $content;
	}
}
?>