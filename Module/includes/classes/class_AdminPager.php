<?php
class AdminPager
{
var $limit = 5;
var $get_values = '';
var $contract_page_numbers = true;
var $get_values_array = array();

	function __construct()
	{
	}

	function getGETValue($key)
	{
	return $this->get_values_array[$key];
	}

	function hasGETValue($key)
	{
	return in_array($key, array_keys($this->get_values_array));
	}

	function setEnvironment($php_self, $query_string)
	{
	$get_values = explode('&', $query_string);

		foreach ($get_values as $get_value)
		{
		$get_bits = explode('=', $get_value);
		$this->get_values_array[$get_bits[0]] = $get_bits[1];

			if (substr($get_value, 0, 5) != 'page=')
			{
			$new_get_values[] = $get_value;
			}
			else
			{
			$page = substr($get_value, 5);
			}
		}

		$this->setCurrentPage($page);

		if (count($new_get_values) > 0)
		{
		$this->get_values = implode('&', $new_get_values);
		// ampersand here -----------------------------| is intentional - take care
		$this->get_values = ($this->get_values!='') ? '&'.implode('&', $new_get_values) : '';
		}

	$this->baseurl = $php_self;
	}

	function setContractPageNumbers($bool)
	{
	$this->contract_page_numbers = $bool;
	}

	function setLimit($limit)
	{
	/* if this is going to be used, call it before set_current_page() */
	$this->limit = $limit;
	}

	function setCurrentPage($page)
	{
	/* if set_limit() is to be used, call it before this */
		if (!isset($page) || $page <= 0)
		{
		$this->start = 0;
		$this->page = 1;
		}
		else
		{
		$this->page = $page;
		$this->start = ($this->page-1) * $this->limit;
		}
	//return $this->page;
	}

	function setTotal($total)
	{
	$this->total = $total;
	}
	
	public function execute($sql) {
		return $this->setSQL($sql);
	}

	function setSQL($sql) {
		$db = new DatabaseQuery;

		if (!isset($this->total)) {
			if (strpos($sql, 'DISTINCT')) {
				$rs = $db->execute($sql);
				$this->total = $rs->getNum();
			}
			else {
				$sql_abbrev = preg_replace("/SELECT.*FROM/s", "SELECT COUNT(*) AS total FROM", $sql);
				$rs = $db->execute($sql_abbrev);
				$row = $rs->getRow();
				$this->total = $row['total'];
			}
		}

		$this->total_pages = ceil($this->total/$this->limit);
		$sql .= " LIMIT $this->start, $this->limit";
		//echo $sql;
		return $db->execute($sql);
	}

	private function getPageList() {
		$page_list = array();

		if ($this->total_pages > 1) {
			if ($this->total_pages > 11 && $this->contract_page_numbers) {
				if ($this->page >= 5) {
					$page_list[] = "<a href=\"$this->baseurl?page=1$this->get_values\"".($this->page == 1 ? ' class="current"' : '').">1</a> ...";
				}

				for ($i=$this->page-3; $i<=$this->page+3; $i++) {
					if ($i>0 && $i<=$this->total_pages) {
						$page_list[] = "<a href=\"$this->baseurl?page=$i$this->get_values\"".($this->page == $i ? ' class="current"' : '').">$i</a>";
					}
				}

				if ($this->page < $this->total_pages-5) {
					$page_list[] = "... <a href=\"$this->baseurl?page=$this->total_pages$this->get_values\"".($this->page == $this->total_pages ? ' class="current"' : '').">$this->total_pages</a>";
				}
			}
			else {
				for ($i=1; $i <= $this->total_pages; $i++) {
					$page_list[] = "<a href=\"$this->baseurl?page=$i$this->get_values\"".($this->page == $i ? ' class="current"' : '').">$i</a>";
				}
			}
		}
		return implode(' ', $page_list);
	}

	private function getPrevNext() {
		$prevnext = array();

		if ($this->page != 1) {
			$prevnext[] = "<a href=\"$this->baseurl?page=".($this->page-1)."$this->get_values\">Previous</a>";
		}

		if (($this->page + 1) <= $this->total_pages) {
			$prevnext[] = "<a href=\"$this->baseurl?page=".($this->page+1)."$this->get_values\">Next</a>";
		}

		return HTMLHelper::wrapArrayInUl($prevnext, '', 'prevnext');
	}

	function getNumberFlow()
	{
	$from = $this->start+1;
	$to = $this->start+$this->limit > $this->total ? $this->total : $this->start+$this->limit;
	$content = "Displaying <b>$from</b>-<b>$to</b> of <b>$this->total</b>";
	return $content;
	}

	function hasResults()
	{
	return $this->total==0 ? false : true;
	}

	function hasMultiplePages()
	{
	return $this->total_pages==1 ? false : true;
	}

	public function getCurrentPage() {
		return $this->page;
	}

	public function getNav() {
		return '<div class="pagination">'.$this->getPrevNext().$this->getPageList().'</div>';
	}
}
?>