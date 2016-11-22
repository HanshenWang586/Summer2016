<?php
class ForumPostsList {
	public function getLatest($x = 0) {
		/*
		TODO
		there's an argument for having all posts show, on both sites, with the links
		sorted so that a gochengdoo reader can link to gokunming, if that's where the
		post was originally put
		*/
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *,
									UNIX_TIMESTAMP(p.ts) AS ts_unix
							FROM bb_posts p
							LEFT JOIN bb_threads t ON t.thread_id = p.thread_id
							WHERE p.live = 1
							AND p.user_id = $this->user_id
							ORDER BY p.ts DESC
							".($x != 0 ? "LIMIT $x" : ''));

		if ($rs->getNum()) {
			$content .= '<h1>Latest Forum Posts</h1>
			<a href="/en/users/all/forum/'.$this->user_id.'/">View all</a>';

			while ($row = $rs->getRow()) {
				$fp = new ForumPost;
				$fp->setData($row);
				$fp->setShowPath(true);
				$fp->setShowUser(false);
				$items[] = $fp->display();
			}

			$content .= HTMLHelper::wrapArrayInUl($items);
		}

		return $content;
	}

	public function getAll(&$pager, $user_id = false) {
		$user = $user_id ? ' AND p.user_id = ' . $user_id : '';
		$rs = $pager->setSQL("SELECT *,
									UNIX_TIMESTAMP(p.ts) AS ts_unix
							FROM bb_posts p
							WHERE p.live = 1
							$user
							ORDER BY p.ts DESC");
		
		$content = '';
		if ($rs->getNum()) {
			$fp = new ForumPost;
			$fp->setShowPath(true);
			$fp->setShowUser(false);
			$content = '<div class="userContentList">';
			while ($row = $rs->getRow()) {
				$fp->setData($row);
				$content .= $fp->display();
			}
			$content .= '</div>';
		}

		return $content . $pager->getNav();
	}
}
?>