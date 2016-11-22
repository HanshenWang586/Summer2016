<?php
class BlogList {

	private $types = array(	'news' => 1,
							'features' => 2,
							'travel' => 3);
	
	public function getItems($options = array()) {
		global $user;
		global $model;
		
		$clauses = array('!blog_content.ts < ' . ifElse($options['before'], 'NOW()') . ' AND blog_content.properties & 1');
		if (array_key_exists('clauses', $options)) $clauses = array_merge($clauses, $options['clauses']);
		if ($options['after']) $clauses[0] .= ' AND blog_content.ts > ' . $options['after'];
		if ($options['category']) $options['category_id'] = array_get($this->types, $options['category']);
		if ($options['category_id']) $clauses['category_id'] = $options['category_id'];
		
		$opts = array(
			'orderBy' => ifElse($options['orderBy'], 'blog_content.ts'),
			'order' => ifElse($options['order'], 'DESC'),
			'offset' => ifElse($options['offset'], 0),
			'limit' => ifElse($options['limit'], 5),
			'getFields' => ifElse($options['fields'], array('blog_id', 'content', 'title', 'user_id', 'category_id', 'ts')),
			'join' => array('table' => 'blog_comments', 'on' => array('blog_id', 'blog_id'), 'fields' => 'COUNT(blog_comments.comment_id) AS num_comments'),
			'groupBy' => 'blog_content.blog_id'
		);
		
		$items = $model->db()->query('blog_content', $clauses, $opts);
		
		if (request($options['displayBrief'])) return $this->displayBrief($items);
		
		return $items;
	}
	
	public function displayBrief($items = array(), $opts = array()) {
		if ($items and is_array($items)) {
			$bi = new BlogItem;
			$content = '';
			foreach($items as $index => $data) {
				$bi->setData($data);
				$content .= $bi->displayBrief();
			}
			$class = 'itemList';
			if (request($opts['class'])) $class .= ' ' . $opts['class'];
			$id = request($opts['id']) ? sprintf(' id="%s"', $opts['id']) : '';
			return sprintf("<div%s class=\"%s\">%s</div>\n", $id, $class, $content);
		}
	}
	
	public function getPoster($poster_id, &$pager) {
		global $user;
		$rs = $pager->setSQL("	SELECT	c.*,
										a.user_id,
										a.display_name,
										UNIX_TIMESTAMP(ts) AS ts_unix
								FROM blog_content c
								LEFT JOIN admin_users a ON (c.user_id = a.user_id)
								WHERE c.user_id = $poster_id
								AND ts < NOW()
								ORDER BY ts DESC");

		while ($row = $rs->getRow()) {
			$bi = new BlogItem;
			$bi->setData($row);
			$items .= $bi->displayBrief();
		}

		return $items;
	}

	public function getPosts(&$pager, $category = false, $search = false, $overrideStart = false, $overrideLimit = false) {
		global $user, $model;
		if ($category) $this->title = ucfirst($category);

		if (!$category or $catID = request($this->types[$category])) {
			if ($search) {
				$terms = $model->db()->escape_clause($search);
				
				$clauses = sprintf(", CASE when c.title like '%%%s%%' then 1 else 0 END as titlematch,
									SUM(CASE when t.tag LIKE '%%%s%%' THEN 1 ELSE 0 END) as tagmatch,	
									MATCH (title, content_stripped) AGAINST ('%s') AS relevance ", $terms, $terms, $terms);
				$join = ' LEFT JOIN blog_tags t ON (c.blog_id = t.blog_id) ';
				$order = sprintf("GROUP BY c.blog_id HAVING titlematch = 1 OR tagmatch >= 1 OR relevance > 1 ORDER BY titlematch DESC, tagmatch DESC, relevance DESC, ts DESC", $terms, $terms, $terms);
			} else {
				$clauses = '';
				$order = ' ORDER BY ts DESC';
			}
			$query = "SELECT
											c.blog_id,
											c.category_id,
											c.user_id,
											c.title,
											c.content,
											c.content_stripped,
											a.display_name,
											ts
											$clauses
									FROM blog_content c
									LEFT JOIN admin_users a ON (c.user_id = a.user_id)
									$join
									WHERE ts < NOW()
									AND properties & 1 " . 
									($category ? "AND category_id = $catID " : '') .
									$order;
			$rs = $pager->setSQL($query, $overrideStart, $overrideLimit);
			
			//echo "<br><br><br><br>$query";
			
			while ($row = $rs->getRow()) {
				$bi = new BlogItem;
				$bi->setData($row);
				$items .= $bi->displayBrief();
			}

			return $items;
		}
	}

	public function getTagged($tag, &$pager) {
		global $user;
		$this->title = "Posts tagged '$tag'";
		$db = new DatabaseQuery;
		$rs = $pager->setSQL("	SELECT 	c.*,
										a.user_id,
										a.display_name,
										UNIX_TIMESTAMP(ts) AS ts_unix
								FROM blog_content c
								LEFT JOIN admin_users a ON (c.user_id = a.user_id)
								LEFT JOIN blog_tags t ON (c.blog_id = t.blog_id)
								AND tag = '".$db->clean($tag)."'
								AND ts < NOW()
								ORDER BY ts DESC");
		
		while ($row = $rs->getRow()) {
			$bi = new BlogItem;
			$bi->setData($row);
			$items .= $bi->displayBrief();
		}

		return $items;
	}

	public function displayAdmin(&$pager) {
		global $admin_user;
		$content = "<table cellspacing=\"1\" class=\"gen_table\">
		<tr>
		<td><b>ID</b></td>
		<td><b>Title</b></td>
		<td><b>Date</b></td>
		<td><b>Author</b></td>
		<td colspan=\"4\"></td>
		</tr>";
		$rs = $pager->setSQL("	SELECT c.*,
										a.user_id,
										a.display_name,
										UNIX_TIMESTAMP(ts) AS ts_unix
								FROM blog_content c
								LEFT JOIN admin_users a ON (c.user_id = a.user_id)
								ORDER BY ts DESC");

			while ($row = $rs->getRow()) {
				$bi = new BlogItem;
				$bi->setData($row);
				$content .= $bi->displayAdminRow();
			}
		$content .= '</table>';
		return $content;
	}

	public function displaySearch(&$pager, $ss) {
		$content .= "<form><input name=\"ss\" value=\"$ss\"><input type=\"submit\" value=\"Go\"></form><br />";
		$db = new DatabaseQuery;

		if ($ss != '') {
			$rs = $pager->setSQL("	SELECT	c.*,
											a.user_id,
											a.display_name,
											UNIX_TIMESTAMP(ts) AS ts_unix
								FROM blog_content c, admin_users a
								WHERE c.user_id = a.user_id
								AND MATCH (title, content_stripped) AGAINST ('".$db->clean($ss)."')
								ORDER BY MATCH (title, content_stripped) AGAINST ('".$db->clean($ss)."') DESC");

			if ($rs->getNum() > 0) {
				$content .= "<table cellspacing=\"1\" class=\"gen_table\">
				<tr>
				<td><b>ID</b></td>
				<td><b>Title</b></td>
				<td><b>Date</b></td>
				<td><b>Author</b></td>
				<td colspan=\"5\"></td>
				</tr>";

				while ($row = $rs->getRow()) {
					$bi = new BlogItem;
					$bi->setData($row);
					$content .= $bi->displayAdminRow();
				}

				$content .= '</table>';
			}
		}

		return $content;
	}

	public function getTitle() {
		return $this->title;
	}
}
?>