<?php
class ForumsSearch {

	public function setSearchString($ss) {
		$this->ss = $ss;
	}

	public function getResults($page = 1) {
		global $model;
		$items = array();
		$db = new DatabaseQuery;

		$start = $page * 10 - 10;
		$prev_page = $page - 1;
		$next_page = $page + 1;

		/*
		if we put fulltext indices on both posts and threads, we can't search
		them both at the same time koz they not in same table.
		but we can search the threads first, and then the posts, and combine the results
		*/

		$cleaned = $db->clean($this->ss);
		$rs = $db->execute("SELECT DISTINCT t.*, MATCH (thread) AGAINST ('".$cleaned."') * 2 AS score
						   FROM bb_threads t
						   WHERE MATCH (thread) AGAINST ('".$cleaned."')
						   AND t.live = 1
						   HAVING score > 5
						   ORDER BY score DESC
						   LIMIT $start, 10");

		while ($row = $rs->getRow()) {
			$thread = new ForumThread;
			$thread->setData($row);
			$items[$row['score']] = $thread->display();
		}

		//echo '<pre>'.print_r($items, true).'</pre>';

		$rs = $db->execute("SELECT DISTINCT t.*, SUM(MATCH (post) AGAINST ('".$cleaned."')) AS score
						   FROM bb_posts p, bb_threads t
						   WHERE t.thread_id = p.thread_id
						   AND MATCH (post) AGAINST ('".$cleaned."')
						   AND t.live = 1
						   AND p.live = 1
						   GROUP BY t.thread_id
						   HAVING score > 5
						   ORDER BY score DESC
						   LIMIT $start, 10");

		while ($row = $rs->getRow()) {
			$thread = new ForumThread;
			$thread->setData($row);
			$items[$row['score']] = $thread->display();
		}

		//echo '<pre>'.print_r($items, true).'</pre>';

		$items = array_unique($items);
		krsort($items);

		//echo '<pre>'.print_r($items, true).'</pre>';

		if ($rs->getNum()) {
			$view = new View;
			$view->setPath('search/set.html');
			$view->setTag('sitename', strtolower($model->lang('SITE_NAME')));
			$view->setTag('results', HTMLHelper::wrapArrayInUl($items));
			$view->setTag('title', 'Forums');
			$view->setTag('type', 'forums');
			$view->setTag('prev_page', $prev_page);
			$view->setTag('next_page', $next_page);
			$view->setTag('page', $page);
			$view->setTag('ss', $this->ss);
			echo $view->getOutput();
		}
	}
}
?>