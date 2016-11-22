<?php
class UserSearch {

	public function setSearchString($ss) {
		$this->ss = $ss;
	}

	public function getResults($page = 1) {
		global $model;
		$db = new DatabaseQuery;

		$start = $page * 10 - 10;
		$prev_page = $page - 1;
		$next_page = $page + 1;

		$rs = $db->execute("SELECT *
						   FROM public_users
						   WHERE nickname LIKE '%".$db->clean($this->ss)."%'
						   AND status & 1
						   LIMIT $start, 10");

		while ($row = $rs->getRow()) {
			$user = new User;
			$user->setData($row);
			$items[] = $user->getLinkedNickname();
		}

		if ($rs->getNum()) {
			$view = new View;
			$view->setPath('search/set.html');
			$view->setTag('sitename', strtolower($model->lang('SITE_NAME')));
			$view->setTag('results', HTMLHelper::wrapArrayInUl($items));
			$view->setTag('title', 'Users');
			$view->setTag('type', 'users');
			$view->setTag('prev_page', $prev_page);
			$view->setTag('next_page', $next_page);
			$view->setTag('page', $page);
			$view->setTag('ss', $this->ss);
			echo $view->getOutput();
		}
	}
}
?>