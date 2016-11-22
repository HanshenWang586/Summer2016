<?php
class Pager {
	
	private $limit = 10;
	//private $get_values = '';
	private $contract_page_numbers = true;
	//private $get_values_array = array();
	private $pageParam = 'page';
	private $hash = '';

	public function __construct($pageParam = false, $hash = false) {
		$this->reset($pageParam, $hash);
	}
	
	public function reset($pageParam = false, $hash = false) {
		global $model;
		$bits = explode('/', $base_url);
		if ($pageParam) $this->setPageParam($pageParam);
		if ($hash) $this->setHash($hash);
		$this->total_pages = NULL;
		$this->total = NULL;
		$this->setCurrentPage(request($model->args[$this->pageParam]), false);
	}

	public function setPageParam($param) {
		$this->pageParam = $param;	
	}
	
	public function setHash($hash) {
		$this->hash = $hash;
	}
	
	public function setLimit($limit) {
		// if this is going to be used, call it before set_current_page()
		$this->limit = $limit;
	}

	public function getLimit() {
		return $this->limit;
	}

	public function setCurrentPage($page, $global = true) {
	/* if set_limit() is to be used, call it before this */
		global $model;
		
		if ($global and $p = $model->args[$this->pageParam]) $page = (int) $p;
		
		return $this->page = !ctype_digit($page) ? 1 : $page;
	}

	private function setTotal($total) {
		$this->total = $total;
	}

	public function setSQL($sql, $overrideStart = false, $overrideLimit = false) {
		$db = new DatabaseQuery;

		if (!isset($this->total)) {
			if (strpos($sql, 'DISTINCT') > 0 or strpos($sql, 'GROUP BY') > 0 or strpos($sql, 'MATCH') > 0 or strpos($sql, 'CASE') > 0) {
				$rs = $db->execute($sql);
				$this->total = $rs->getNum();
			} else {
				$sql_abbrev = preg_replace("/SELECT.*FROM/s", "SELECT COUNT(*) AS total FROM", $sql);
				$this->total = $GLOBALS['model']->db()->run_select($sql_abbrev, true, array('selectField' => 'total'));
			}
		}
		
		$start = $overrideStart ? $overrideStart : $this->getStart();
		$limit = $overrideLimit ? $overrideLimit : $this->limit;
		
		$this->total_pages = ceil($this->total/$limit);
		$sql .= ' LIMIT '.$start.', '.$limit;
		
		//echo "<br><br><br><br><pre>";
		//echo $sql;
		//echo "</pre>";
		
		return $db->execute($sql);
	}

	private function getPageList() {
		$page_list = array();

		if ($this->total_pages > 1) {
			$page_list[] = $this->getPrevious();
			
			if ($this->total_pages > 5 && $this->contract_page_numbers) {
				$page_list[] = "<a href=\"".$this->makeURL(1)."\"".($this->page == 1 ? ' class="current"' : '').">1</a>";

				if ($this->page >= 4)
					$page_list[] = '<span class="dots">...</span>';

				for ($i = $this->page - 1; $i <= $this->page + 1; $i++) {
					if ($i > 1 && $i <= $this->total_pages - 1)
						$page_list[] = "<a href=\"".$this->makeURL($i)."\"".($this->page == $i ? ' class="current"' : '').'>'.$i.'</a>';
				}

				if ($this->page < $this->total_pages - 2)
					$page_list[] = '<span class="dots">...</span>';

				$page_list[] = "<a href=\"".$this->makeURL($this->total_pages)."\"".($this->page == $this->total_pages ? ' class="current"' : '').">$this->total_pages</a>";
			}
			else {
				for ($i=1; $i <= $this->total_pages; $i++)
					$page_list[] = "<a href=\"".$this->makeURL($i)."\"".($this->page == $i ? ' class="current"' : '').'>'.$i.'</a>';
			}
			$page_list[] = $this->getNext();
		}
		$page_list = array_filter($page_list, 'request');
		return HTMLHelper::wrapArrayInUl($page_list);
	}
	
	private function getPrevious() {
		if ($this->page != 1)
			return sprintf("<a class=\"prev\" href=\"%s\"><span class=\"icon icon-arrow-left-2\"><span>%s</span></span></a>",
				$this->makeURL($this->page - 1),
				$GLOBALS['model']->lang('NAV_PREV')
			);
	}

	private function getNext() {
		if (($this->page + 1) <= $this->total_pages)
			return sprintf("<a class=\"next\" href=\"%s\"><span class=\"icon icon-arrow-right-2\"><span>%s</span></span></a>",
				$this->makeURL($this->page + 1),
				$GLOBALS['model']->lang('NAV_NEXT')
			);
	}
	
	public function getText() {
		return $this->total > 0 ? 
			sprintf('<p class="searchInfo">Showing results %d-%d of %d total</p>', ($this->page - 1) * $this->limit + 1, min(array($this->page * $this->limit, $this->total)), $this->total)
			: '<p class="searchInfo">No results could be found</p>';
	}
	
	public function getNav() {
		$nav = $this->getPageList();
		return $nav ? sprintf("\n<nav class=\"pagination\">%s</nav>\n", $nav) : '';
	}

	private function getNumberFlow() {
		$start = $this->getStart();
		$from = $start+1;
		$to = $start+$this->limit > $this->total ? $this->total : $start+$this->limit;
		$content = "Displaying <b>$from</b>-<b>$to</b> of <b>$this->total</b>";
		return $content;
	}
	
	public function getStart() {
		return ($this->page - 1) * $this->limit;
	}

	private function hasResults() {
		return $this->total==0 ? false : true;
	}

	private function hasMultiplePages() {
		return $this->total_pages==1 ? false : true;
	}

	private function makeURL($page_number) {
		global $model;
		$args = array($this->pageParam => $page_number > 1 ? $page_number : false);
		$options = array();
		if ($this->hash) $options['hash'] = $this->hash;
		return $model->url($args, $options, true);
	}
}
?>