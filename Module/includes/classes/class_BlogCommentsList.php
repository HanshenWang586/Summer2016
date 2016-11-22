<?php
class BlogCommentsList {

	public function displayAdmin(&$pager) {
		$content = "<table cellspacing=\"1\" class=\"gen_table\">
		<tr>
		<td><b>Site</b></td>
		<td width=\"200\"><b>Title</b></td>
		<td><b>Nickname</b></td>
		<td><b>Time</b></td>
		<td><b>Comment</b></td>
		<td><b>Live</b></td>
		<td colspan=\"2\"></td>
		</tr>";
		$rs = $pager->setSQL("	SELECT	c.*,
										u.*,
										b.title,
										s.*,
										c.ip AS ip,
										IF(c.user_id=0, c.nickname, u.nickname) AS nickname
								FROM blog_comments c, public_users u, blog_content b, sites s
								WHERE u.user_id = c.user_id
								AND b.blog_id = c.blog_id
								ORDER BY c.ts DESC");

		while ($row = $rs->getRow()) {
			$bc = new BlogComment;
			$bc->setData($row);
			$content .= $bc->displayAdminRow($pager->page);
		}

		$content .= '</table>';
		return $content;
	}

	function setUserID($user_id)
	{
	$this->user_id = $user_id;
	}

	public function getComments($pager, $user_id = false) {
		$user = $user_id ? ' AND c.user_id = ' . $user_id : '';
		$rs = $pager->setSQL("SELECT	c.*,
										IF(u.user_id = 0, c.nickname, u.nickname) AS nickname,
										UNIX_TIMESTAMP(c.ts) AS ts_unix
							FROM blog_comments c
							LEFT JOIN public_users u ON c.user_id = u.user_id
							INNER JOIN blog_content g ON c.blog_id = g.blog_id
							WHERE live = 1
							$user
							ORDER BY c.ts DESC");
		
		$bc = new BlogComment;
		$comments = '<div class="commentsList userContentList">';
		while ($row = $rs->getRow()) {
			$bc->setData($row);
			$comments .= $bc->displayPublic(true);
		}
		return $comments . '</div>' . $pager->getNav();
	}

	function getSidebarLatest() {
		global $model;
		$comments = '';
		$db = new DatabaseQuery;
		$rs = $db->execute(
			"SELECT c.blog_id,
			MAX(c.ts) as ts_max
			FROM blog_comments c
			LEFT JOIN blog_content b ON (c.blog_id = b.blog_id)
			WHERE c.live = 1
			AND b.properties & 1
			AND b.ts < NOW()
			GROUP BY c.blog_id
			ORDER BY ts_max DESC
			LIMIT 5");

		if ($rs->getNum()) {
			while ($row = $rs->getRow()) {
				$data = $model->db()->run_select(sprintf('SELECT 
						c.*,
						c.ts AS ts_unix
					FROM blog_comments c
					WHERE blog_id = %d
					ORDER BY c.ts DESC
					LIMIT 1', $row['blog_id']), array('singleResult' => true));
				$bc = new BlogComment();
				$bc->setData($data);
				$bi = $bc->getBlogItem();
				$comments .= sprintf('<article itemscope itemtype="http://schema.org/article">
					<a itemprop="url" href="%s">
						<img itemprop="image" alt="thumbnail" src="%s" width="60" height="60">
						<span itemscope itemprop="comment" itemtype="http://schema.org/Comment" class="top"><span itemprop="creator" itemscope itemtype="http://schema.org/Person">%s</span> • %s</span>
						<h1 itemprop="name">%s</h1>
					</a>
				</article>',
					$bi->getCommentsURL(),
					$bi->getImage(120,120),
					$bc->getProcessedNicknameNoLink(),
					$bc->getDateTag(),
					$bi->getTitle()
		);
			}
		}

		return $comments;
	}
}
?>