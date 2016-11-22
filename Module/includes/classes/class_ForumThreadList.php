<?php
class ForumThreadList {

	public function setSearchString($ss) {
		$this->ss = $ss;
	}

	public function displayAdmin(&$pager) {
		global $admin_user;
		$content = "<table cellspacing=\"1\" class=\"gen_table\">
		<tr>
		<td><b>Site</b></td>
		<td width=\"300\"><b>Thread</b></td>
		<td><b>Time</b></td>
		<td><b>User</b></td>
		<td><b>Live</b></td>
		<td><b>Locked</b></td>
		<td colspan=\"3\"></td>
		</tr>";

		$sql = "	SELECT	*,
							UNIX_TIMESTAMP(ts) AS ts_unix
					FROM bb_threads t
					LEFT JOIN public_users u ON u.user_id = t.user_id 
					".(isset($this->ss) ? "WHERE (thread LIKE '%$this->ss%' OR nickname LIKE '%$this->ss%')" : '')."
					ORDER BY ts DESC";

		if (isset($this->ss)) {
			$db = new DatabaseQuery;
			$rs = $db->execute($sql);
		}
		else {
			$rs = $pager->setSQL($sql);
		}

		while ($row = $rs->getRow()) {
			$ft = new ForumThread;
			$ft->setData($row);
			$content .= $ft->displayAdminRow();
		}

	$content .= "</table>";
	return $content;
	}

	public function getLatest($x = 10) {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT DISTINCT t.*, COUNT(p.post_id) AS posts
				FROM bb_threads t
				LEFT JOIN bb_posts p ON (t.thread_id = p.thread_id)
				WHERE t.live = 1 and p.live = 1 AND t.locked = 0
				GROUP BY thread_id HAVING posts > 0 ORDER BY ts DESC
				'.($x != 0 ? 'LIMIT '.$x : ''));
		
		$thread = new ForumThread;
		$content = '';
		while ($row = $rs->getRow()) {
			$thread->setData($row);
			$user = $thread->getLatestPosterUser();
			$userName = $user ? $user->getNickname() : 'N/A';
			$content .= sprintf('<article>
				<a href="%s">
					%s
					<span class="top">%s • %s</span>
					<h1>%s</h1>
				</a>
			</article>',
				$thread->getURL(true),
				$thread->getIcon(),
				$userName,
				$thread->getLatestPostingDate(),
				$thread->getTitle()
			);
			//$content .= '<p><a href="'.$thread->getURL().'">'.$thread->getTitle().'</a></p>'
			//.$thread->getNumberPosts().' post'.($thread->getNumberPosts() > 1 ? 's' : '').' • '.$thread->getLatestPosterUser()->getLinkedNickname().' in '.$thread->getBoard()->getLink();
		}
		return $content;
	}
}
?>