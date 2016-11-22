<?php
class ClassifiedsSearch {

	public function setSearchString($ss) {
		$this->ss = $ss;
	}

	public function getResults($page = 1) {
		global $model;
		$db = new DatabaseQuery;

		$start = $page * 10 - 10;
		$prev_page = $page - 1;
		$next_page = $page + 1;

		$rs_1 = $db->execute("SELECT *
						   FROM classifieds_folders
						   WHERE folder_en LIKE '%".$db->clean($this->ss)."%'
						   LIMIT $start, 10");

		while ($row = $rs_1->getRow()) {
			$folder = new ClassifiedsFolder;
			$folder->setData($row);
			$items[] = $folder->getPath();
		}

		$rs = $db->execute("SELECT classified_id, title, folder_id
						   FROM classifieds_data
						   WHERE MATCH (title, body) AGAINST ('".$db->clean($this->ss)."')
						   AND status = 1
						   LIMIT $start, 10");

		while ($row = $rs->getRow()) {
			$classified = new ClassifiedsItem;
			$classified->setData($row);
			$items[] = $classified->getLink();
		}

		if ($rs->getNum() + $rs_1->getNum()) {
			$view = new View;
			$view->setPath('search/set.html');
			$view->setTag('sitename', strtolower($model->lang('SITE_NAME')));
			$view->setTag('results', HTMLHelper::wrapArrayInUl($items));
			$view->setTag('title', 'Classifieds');
			$view->setTag('type', 'classifieds');
			$view->setTag('prev_page', $prev_page);
			$view->setTag('next_page', $next_page);
			$view->setTag('page', $page);
			$view->setTag('ss', $this->ss);
			echo $view->getOutput();
		}
	}
}
?>