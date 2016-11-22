<?php
class ForumThread {
	private $icons = array(1 => 'airplane', 2 => 'uniE078', 3 => 'food', 4 => 'graduation');
	private $date_options = array('show_year' => true);

	public function __construct($thread_id = false) {
		if (ctype_digit($thread_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT *
								FROM bb_threads
								WHERE thread_id = '.$thread_id);
			$this->setData($rs->getRow());
		} elseif (is_array($thread_id)) $this->setData($thread_id);
	}
	
	public function getIcon() {
		if ($this->board_id and array_key_exists($this->board_id, $this->icons)) {
			return sprintf('<span class="icon icon-%s"> </span>', $this->icons[$this->board_id]);
		}
		return '';
	}

	public function getNumberPosts() {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT COUNT(*) AS number_posts
							FROM bb_posts
							WHERE live = 1
							AND thread_id = '.$this->thread_id);
		$row = $rs->getRow();
		return $row['number_posts'];
	}
	
	public function setData($data) {
		if (is_array($data)) {
			foreach ($data as $key => $value)
				$this->$key = $value;
		}
	}

	function setShowPath($bool)
	{
	$this->show_path = $bool;
	}

	public function display() {
		return '<h3><a href="'.$this->getURL().'">'.$this->getTitle().'</a></h3>';
	}

	public function getHeader() {
		if ($this->live) {
			$view = new View;
			$view->setPath('forums/header.html');
			$view->setTag('breadcrumb', $this->getPath());
			$view->setTag('title', $this->getTitle());
			$content = $view->getOutput();
		}

		return $content;
	}

	public function getThreadID() {
		return $this->thread_id;
	}

	private function getRawTitle() {
		return $this->thread;
	}

	public function getTitle() {
		return ContentCleaner::wrapChinese(ContentCleaner::cleanPublicDisplay($this->getRawTitle()));
	}

	public function getLinkedTitle($linkToLatestPost = false)
	{
	return "<a href=\"".$this->getURL()."\">".$this->getTitle()."</a>";
	}

	public function getURL($linkToLatestPost = false, $page = false, $options = array()) {
		global $model;
		$args = array('m' => 'forums', 'view' => 'thread', 'id' => $this->getThreadID(), 'name' => $this->getTitleForURL());
		if ($linkToLatestPost) {
			$posts = $this->getNumberPosts();
			if ($posts > 10) $args['page'] = ceil($posts / 10);
			$post_id = $this->getLatestPostID();
		} elseif ($page > 1) $args['page'] = $page;
		return $model->tool('linker')->prettifyURL($args, $options) . ($linkToLatestPost ? '#post-' . $post_id : '');
		//return 'http://' . $model->module('preferences')->get('url') . '/en/forums/thread/'.$this->getThreadID().'/'.$this->getTitleForURL();
	}

	public function isLive() {
		return $this->live;
	}
	
	public function displayPosts($pager) {
		global $user;
		
		$rs = $pager->setSQL('	SELECT p.*, u.nickname, u.user_id, UNIX_TIMESTAMP(ts) AS ts_unix
								FROM bb_posts p
								LEFT JOIN public_users u ON (u.user_id = p.user_id)
								WHERE thread_id = '.$this->thread_id.'
								AND live = 1
								ORDER BY ts ASC');
		$this->number_posts = $rs->getNum();
		
		$posts = "<div class=\"userContentList\">\n";
		$fp = new ForumPost;
		$fp->setThread($this);
		$index = ($pager->page - 1) * $pager->getLimit() + 1;
		$total_posts = $this->getNumberPosts();
		while ($row = $rs->getRow()) {
			$fp->setData($row);
			$posts .= $fp->display($index == $total_posts, $index, $pager->getLimit());
			$index++;
		}
		$posts .= "</div>\n";

		return $posts;
	}
	
	function displayAdmin() {
		$db = new DatabaseQuery;

		$content = "<table cellspacing=\"1\" class=\"gen_table\">
		<tr>
		<td><b>Post</b></td>
		<td><b>Time</b></td>
		<td><b>User</b></td>
		<td><b>IP</b></td>
		<td><b>Live</b></td>
		<td></td>
		</tr>";

		$rs = $db->execute("	SELECT p.*, u.nickname
								FROM bb_posts p
								LEFT JOIN public_users u ON (u.user_id = p.user_id)
								WHERE thread_id = $this->thread_id
								ORDER BY ts DESC");
		
		$fp = new ForumPost;
		$fp->setThread($this);
		while ($row = $rs->getRow()) {
			$fp->setData($row);
			$content .= $fp->displayAdminRow();
		}

		$content .= '</table>';
		return $content;
	}
	
	public function save() {
		global $user;

		$cc = new ContentCleaner;
		$cc->cleanForDatabase($this->thread);
		$cc->cleanForDatabase($this->post);

		if (!$user->isBanned() && $user->isLoggedIn()) {
			$db = new DatabaseQuery;
			$db->execute("	INSERT INTO bb_threads (	board_id,
														ts_created,
														ts,
														user_id,
														thread)
							VALUES (	$this->board_id,
										NOW(),
										NOW(),
										".$user->getUserID().",
										'".$db->clean($this->thread)."')");
			$this->thread_id = $db->getNewID();

			if ($this->subscribe == 1)
				$this->subscribeUser($user);

			$post_data = array(	'thread_id' => $this->thread_id,
								'post' => $this->post,
								'user_id' => $user->getUserID()
								);

			$fp = new ForumPost;
			$fp->setData($post_data);
			$fp->save($user);
		}
	}

	public function displayAdminRow() {
		$action = $this->live ? 'Delete' : 'Undelete';
		$lock_action = $this->locked ? 'Unlock' : 'Lock';

		$content = "<tr valign=\"top\"".($this->live ? '' : ' class="fadeout"').">
		<td>$this->site_name</td>
		<td>$this->thread<br />
		<span class=\"highlight\">".strip_tags($this->getPath())."</span></td>
		<td>$this->ts</td>
		<td><a target=\"_blank\" href=\"../public_users/form_user.php?user_id=$this->user_id\">$this->nickname</a></td>
		<td>".($this->live ? 'Yes' : 'No')."</td>
		<td>".($this->locked ? 'Yes' : 'No')."</td>
		<td><a href=\"list_posts.php?thread_id=$this->thread_id\">View&nbsp;posts</a></td>
		<td><a href=\"toggle_locked_thread.php?thread_id=$this->thread_id\" onClick=\"return confirm('Are you sure you want to ".strtolower($lock_action)." this?')\">$lock_action</a></td>
		<td><a href=\"toggle_live_thread.php?thread_id=$this->thread_id\" onClick=\"return confirm('Are you sure you want to ".strtolower($action)." this?')\">$action</a></td>
		</tr>";
		return $content;
	}

	public function deleteIfZeroPosts() {
		if ($this->getNumberPosts() == 0)
			$this->toggleLive();
	}

	public function toggleLive() {
		$db = new DatabaseQuery;
		$db->execute('	UPDATE bb_threads
						SET live = (live + 1) % 2
						WHERE thread_id = '.$this->thread_id);
	
		$rs = $db->execute('SELECT live
							FROM bb_threads
							WHERE thread_id = '.$this->thread_id);
		$row = $rs->getRow();
	
		$db->execute("	UPDATE bb_posts
						SET live = {$row['live']}
						WHERE thread_id = $this->thread_id");
	}

	public function isLocked() {
		return $this->locked ?  true : false;
	}

	function toggleLocked()
	{
	$db = new DatabaseQuery;
	$db->execute("	UPDATE bb_threads
					SET locked = (locked + 1) % 2
					WHERE thread_id=$this->thread_id");
	}

	function resetTs()
	{
	$db = new DatabaseQuery;
	$rs = $db->execute("SELECT ts
						FROM bb_posts
						WHERE thread_id=$this->thread_id
						AND live=1
						ORDER BY ts DESC
						LIMIT 1");
	$row = $rs->getRow();
	$db->execute("	UPDATE bb_threads
					SET ts='{$row['ts']}'
					WHERE thread_id=$this->thread_id");
	}

	public function reTimeStamp() {
		$db = new DatabaseQuery;
		$db->execute('	UPDATE bb_threads
						SET ts = NOW()
						WHERE thread_id = '.$this->thread_id);
	}

	public function getPath() {
		return $this->getBoard()->getPath();
	}
	
	public function getBoard() {
		if (!$this->board or $this->board_id != $this->board->getBoardID()) {
			$this->board = new ForumBoard($this->board_id);
		}
		return $this->board;
	}

	function getTitleForURL() {
		return ContentCleaner::processForURL($this->getRawTitle());
	}

	public function getOriginalPosterUser() {
		return $GLOBALS['site']->getUser($this->user_id);
	}
	
	public function getLatestPost($field = false) {
		if (!$this->latestPost or $this->latestPost->thread_id != $this->thread_id) {
			$this->latestPost = $GLOBALS['model']->db()->query('bb_posts', array('thread_id' => $this->getThreadID(), 'live' => 1), array('singleResult' => true, 'limit' => 1, 'orderBy' => 'ts', 'order' => 'DESC'));
		}
		return $field ? $this->latestPost[$field] : $this->latestPost;
	}
	
	public function getLatestPosterUser() {
		return $GLOBALS['site']->getUser($this->getLatestPost('user_id'));
	}
	
	public function getLatestPostID() {
		return $this->getLatestPost('post_id');
	}
	
	public function getLatestPostingDate() {
		return $GLOBALS['model']->tool('datetime')->getDateTag($this->getLatestPost('ts'));
	}

	public function getOriginalPostingDate() {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT ts
							FROM bb_posts
							WHERE thread_id = $this->thread_id
							AND live = 1
							ORDER BY ts ASC
							LIMIT 1");
		$row = $rs->getRow();
		return $GLOBALS['model']->tool('datetime')->getDateTag($row['ts']);
	}

	public function userIsSubscribed($user) {
		return $GLOBALS['model']->db()->count('bb_subscriptions', array('user_id' => $user->getUserID(), 'thread_id' => $this->thread_id)) > 0;
	}


	public function subscribeUser($user) {
		if (!$this->userIsSubscribed($user)) {
			$db = new DatabaseQuery;
			$db->execute('INSERT INTO bb_subscriptions (user_id, thread_id)
						 VALUES ('.$user->getUserID().', '.$this->thread_id.')');
		}
	}

	public function unsubscribeUser($user) {
		$db = new DatabaseQuery;
		$db->execute('DELETE FROM bb_subscriptions
					WHERE user_id = '.$user->getUserID().'
					AND thread_id = '.$this->thread_id);
	}

	public function sendSubscribeEmails($post) {
		global $user;
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT user_id
							FROM bb_subscriptions
							WHERE thread_id = '.$this->thread_id);

		$notification = new View;
		$notification->setPath('forums/notification.html');
		$notification->setTag('url', $this->getURL(true));
		$notification->setTag('body', $post);
		$message = $notification->getOutput();

		$subject = 'New Forum Post: '.$this->getTitle();

		while ($row = $rs->getRow()) {
			if ($row['user_id'] != $user->getUserID()) {
				$p_user = $GLOBALS['site']->getUser($row['user_id']);
				$p_user->sendEmail($subject, $message);
			}
		}
	}
}
?>