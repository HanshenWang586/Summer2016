<?php
class ForumBoard {

	public function __construct($board_id = '') {
		if (ctype_digit($board_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT *
								FROM bb_boards
								WHERE board_id = '. (int) $board_id);
			$this->setData($rs->getRow());
		}
	}

	function setData($data) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$this->$key = $value;
			}
		}
	}

	public function getTitle() {
		$title = $this->board;

		return $title;
	}

	public function getBoardID() {
		return $this->board_id;
	}

	private function getRecent() {
		$db = new DatabaseQuery;
		$rs = $db->execute('	SELECT *
								FROM bb_threads
								WHERE board_id = '.$this->board_id.'
								AND live = 1
								ORDER BY ts DESC
								LIMIT 10');

		while ($row = $rs->getRow()) {
			$ft = new ForumThread;
			$ft->setData($row);
			$threads[] = $ft->display();
		}

		return HTMLHelper::wrapArrayInUl($threads);
	}

	public function displayPublicSummary($class) {
		$view = new View;
		$view->setPath('forums/summary.html');
		$view->setTag('url', $this->getURL());
		$view->setTag('name', $this->getTitle());
		$view->setTag('board_id', $this->getBoardID());
		$view->setTag('class', $class);
		$view->setTag('threads_count', number_format($this->getNumberThreads()));
		$view->setTag('posts_count', number_format($this->getNumberPosts()));
		$view->setTag('list', $this->getRecent());
		return $view->getOutput();
	}

	public function getNumberThreads() {

		if (!isset($this->number_threads)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT COUNT(*) AS number_threads
								FROM bb_threads
								WHERE board_id = '.$this->board_id.'
								AND live = 1');
			$row = $rs->getRow();
			$this->number_threads = $row['number_threads'];
		}

		return $this->number_threads;
	}

	public function getNumberPosts() {
		if (!isset($this->number_posts)) {
			$this->number_posts = 0;
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT COUNT(*) AS tally
								FROM bb_threads t
								LEFT JOIN bb_posts p ON (t.thread_id = p.thread_id)
								WHERE board_id = '.$this->board_id.'
								AND t.live = 1
								AND p.live = 1');
			$row = $rs->getRow();
			$this->number_posts = $row['tally'];
		}

		return $this->number_posts;
	}

	function display_add_thread_link() {
		return "<a href=\"/en/forums/form_thread/$this->board_id/\">New thread</a>";
	}

	public function getPath() {
		$parent_id = $this->board_id; // we know this is non-zero
		$board_id = $this->board_id;
		$db = new DatabaseQuery;

		while ($parent_id > 0) {
			$rs = $db->execute("SELECT *
								FROM bb_boards
								WHERE board_id=$board_id");
			$row = $rs->getRow();

			// for the benefit of this loop
			$parent_id = $row['parent_id'];
			$board_id = $row['parent_id'];

			// for the benefit of doing something useful
			$fb = new ForumBoard;
			$fb->setData($row);
			$path[] = '<a href="'.$fb->getURL().'">'.$fb->getTitle().'</a>';
		}

		$path[] = '<a href="/en/forums/">Forums</a>';
		return implode(' > ', array_reverse($path));
	}

	function getURL() {
		return '/en/forums/board/'.$this->board_id.'/'.$this->getTitleForURL();
	}

	public function getLink() {
		return '<a class="forumBoardLink" href="'.$this->getURL().'">'.$this->getTitle().'</a>';
	}

	function getTitleForURL() {
		return ContentCleaner::processForURL($this->getTitle());
	}
}
?>