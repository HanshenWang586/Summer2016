<?php
class ClassifiedsList {

	public function setSearchString($ss) {
		$this->ss = $ss;
	}
	
	public function getClassifieds($pager, $user_id = false, $allTime = false, $search = false) {
		global $model;
		$args = array();
		if ($user_id) $args[] = ' d.user_id = ' . $user_id;
		if (!$allTime) $args[] = ' d.status = 1 AND ts_end > NOW() ';
		if ($search) {
			$q = $model->db()->escape_clause($search);
			$select = sprintf("
				CASE WHEN title LIKE '%%%s%%' THEN 1 ELSE 0 END AS titlematch,
				MATCH (title, body) AGAINST ('%s') AS score,
			", $q, $q);
			$args[] = sprintf("MATCH (title, body) AGAINST ('%s')", $q);
			$order = 'HAVING score > 1 ORDER BY titlematch DESC, score DESC';
		} else $order = 'ORDER BY ts DESC';
		
		if ($args) $args = 'WHERE ' . implode(' AND ', $args);
		
		$rs = $pager->setSQL("SELECT *,
									d.status AS status,
									$select
									UNIX_TIMESTAMP(ts) AS ts_unix,
									UNIX_TIMESTAMP(ts_end) AS ts_end_unix
								FROM classifieds_data d
								LEFT JOIN public_users u ON (u.user_id = d.user_id)
								$args
								$order");
								
		$ci = new ClassifiedsItem;
		$ci->setShowPath(true);
		if ($user_id) $ci->setShowUser(false);
		$content = '<div class="userContentList classifiedsList">';
		if ($rs->getNum() == 0) {
			if ($search) $content .= '<p class="pageWrapper infoMessage">Your search query did not give any results.</p>';
			else $content .= '<p class="pageWrapper infoMessage">No results found.</p>';
		} else while ($row = $rs->getRow()) {
			$ci->setData($row);
			$content .= $ci->displayPublic(true);
		}
		return $content . '</div>' . $pager->getNav();
	}

	public static function expire() {
		$db = new DatabaseQuery;
		$db->execute('UPDATE classifieds_data SET status = 5 WHERE ts_end < NOW()');
	}

	public function getLatest($x = 10) {
		global $site;
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT *,
									UNIX_TIMESTAMP(ts) AS ts_unix,
									UNIX_TIMESTAMP(ts_end) AS ts_end_unix
							FROM classifieds_data
							WHERE status = 1
							".(isset($this->user_id) ? 'AND user_id = '.$this->user_id : '')."
							ORDER BY ts DESC
							".($x != 0 ? 'LIMIT '.$x : ''));
		
		$ci = new ClassifiedsItem;
		$ci->setShowPath(true);
		$ci->setShowUser(false);
		while ($row = $rs->getRow()) {
			$ci->setData($row);
			
			if (isset($this->user_id)) {
				$content = '<h1>Latest Classifieds</h1>
				<a href="/en/users/all/classifieds/'.$this->user_id.'/">View all</a>';
				$content .= $ci->displayPublic(); // using for profile
			}
			else
				$content .= $ci->getBrief(); // using for sidebar
		}

		return $content;
	}

	public function displayAdmin(&$pager, $ss = '') {
		$db = new DatabaseQuery;
		$rs = $pager->setSQL("	SELECT	*,
										d.status AS status,
										UNIX_TIMESTAMP(ts) AS ts_unix
								FROM classifieds_data d, public_users u
								WHERE u.user_id = d.user_id
								".($ss != '' ? "AND (	nickname LIKE '%".$db->clean($ss)."%'
														OR title LIKE '%".$db->clean($ss)."%'
														OR email LIKE '%".$db->clean($ss)."%')" : '')."
								ORDER BY ts DESC");

		if ($rs->getNum()) {
			$content = "<table cellspacing=\"1\" class=\"gen_table\">
			<tr>
			<td><b>ID</b></td>
			<td width=\"300\"><b>Title</b></td>
			<td><b>Time</b></td>
			<td><b>User</b></td>
			<td><b>Responses</b></td>
			<td><b>Status</b></td>
			<td></td>
			</tr>";
			
			$ci = new ClassifiedsItem;
			while ($row = $rs->getRow()) {
				$ci->setData($row);
				$content .= $ci->displayAdminRow(array('page' => $pager->getCurrentPage(), 'ss' => $ss));
			}

			$content .= '</table>';
		}
		return $content;
	}

	public function displayWaiting() {
		global $admin_user;
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT	*,
									d.status AS status,
									UNIX_TIMESTAMP(ts) AS ts_unix
							FROM classifieds_data d, public_users u
							WHERE u.user_id = d.user_id
							AND d.status = 2
							ORDER BY ts ASC");

		if ($rs->getNum()) {
			$content .= "<table cellspacing=\"1\" class=\"gen_table\">
			<tr>
			<td width=\"300\"><b>Title</b></td>
			<td><b>Time</b></td>
			<td><b>User</b></td>
			<td><b>Status</b></td>
			<td></td>
			</tr>";
		
			$ci = new ClassifiedsItem;
			while ($row = $rs->getRow()) {
				$ci->setData($row);
				$content .= $ci->getWaitingRow();
			}

			$content .= '</table>';
		}

		return $content;
	}
}
?>
