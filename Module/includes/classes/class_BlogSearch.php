<?php
class BlogSearch {

	public function setSearchString($ss) {
		$this->ss = $ss;
	}

	public function getResults($page = 1) {
		$db = new DatabaseQuery;

		$start = $page * 10 - 10;
		$prev_page = $page - 1;
		$next_page = $page + 1;
		
		$terms = explode(' ', $this->ss);
		foreach ($terms as $term) {
			$terms_processed[] = $term;
			if (strpos($term, '\''))
				$terms_processed[] = str_replace('\'', '', $term);
		}
		
		$cleaned = $db->clean(implode(' ', $terms_processed));
		$rs = $db->execute("
								SELECT title, blog_id, UNIX_TIMESTAMP(ts) AS ts_unix,
									MATCH (title, content_stripped) AGAINST ('" . $cleaned . "') AS relevance
								FROM blog_content
								WHERE MATCH (title, content_stripped) AGAINST ('" . $cleaned . "')
									AND ts < NOW()
								HAVING relevance > 1.3
								ORDER BY ts DESC
								LIMIT $start, 10"
							);
		//echo $db->getLastQuery();

		while ($row = $rs->getRow()) {
			$bits = array();
			$bi = new BlogItem;
			$bi->setData($row);
			$bits[] = $bi->getTitleLinked();
			$bits[] = $bi->getDate();
			$items[] = HTMLHelper::wrapArrayInUl($bits);
		}

		if ($rs->getNum()) {
			$view = new View;
			$view->setPath('search/set.html');
			$view->setTag('sitename', strtolower($GLOBALS['model']->getLang('SITE_NAME')));
			$view->setTag('results', HTMLHelper::wrapArrayInUl($items));
			$view->setTag('title', 'Articles');
			$view->setTag('type', 'articles');
			$view->setTag('prev_page', $prev_page);
			$view->setTag('next_page', $next_page);
			$view->setTag('page', $page);
			$view->setTag('ss', $this->ss);
			echo $view->getOutput();
		}
	}
}
?>