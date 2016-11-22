<?php
class ForumPost {

	private $show_path = false;
	private $show_user = true;
	private $date_options = array('show_year' => true);

	public function __construct($post_id = '') {
		if ($post_id) {
			if (ctype_digit($post_id)) {
				$db = new DatabaseQuery;
				$rs = $db->execute('SELECT *, UNIX_TIMESTAMP(ts) AS ts_unix
									FROM bb_posts
									WHERE post_id = '.$post_id);
				$this->setData($rs->getRow());
			} elseif (is_array($post_id)) $this->setData($post_id);
		}
	}

	public function setData($data) {
		if (is_array($data)) foreach ($data as $key => $value) $this->$key = $value;
	}

	function getData() {
		$thread = $this->getThread();
		return array('ts' => $this->ts,
					 'title' => strip_tags($thread->getTitle()),
					 'body' => $this->post,
					 'user_id' => $this->user_id,
					 'thread_id' => $this->thread_id);
	}

	public function setShowPath($bool) {
		$this->show_path = $bool;
	}

	public function setShowUser($bool) {
		$this->show_user = $bool;
	}

	public function getUserID() {
		return $this->user_id;
	}

	private function getUser() {
		return $GLOBALS['site']->getUser($this->user_id);
		//return new User($this->user_id);
	}

	public function setThreadID($thread_id) {
		$this->thread_id = $thread_id;
	}
	
	public function setThread($forumThread) {
		$this->thread = $forumThread;
	}
	
	public function getThread() {
		if (!$this->thread or $this->thread_id != $this->thread->getThreadID()) {
			$this->thread = new ForumThread($this->thread_id);
		}
		return $this->thread;
	}

	public function display($newest = false, $index = false, $pageLimit = false) {
		global $user, $model;
		$post = ContentCleaner::cleanPublicDisplay($this->post);
		$post = ContentCleaner::linkURLs($post);
		$post = ContentCleaner::wrapChinese($post);
		$post = ContentCleaner::PWrap($post);
		
		if (is_array($newest)) {
			$options = $newest;
			$newest = request($options['newest']);
			$index = request($options['index']);
			$pageLimit = request($options['pageLimit']);
		}
		$view = new View;
		$view->setPath('forums/post.html');
		$view->setTag('post_id', $this->post_id);
		$view->setTag('newest', $newest);
		
		/*
		$options = sprintf('<a title="%s" class="tooltip" href="%s"><span class="icon icon-flag"> </span><span class="tooltip-text">%s</span></a>',
			$model->lang('REPORT_POST', 'ForumsModel', false, true),
			$model->url(array('m' => 'forums', 'view' => 'report_post', 'id' => $this->post_id)),
			$model->lang('REPORT_POST', 'ForumsModel', false, true)
		);
		if ($user->getPower()) {
			$options .= sprintf(
				'<a title="%s" class="tooltip" href="%s"><span class="icon icon-trash"> </span><span class="tooltip-text">%s</span></a>',
				$model->lang('DELETE_POST', 'ForumsModel', false, true),
				$model->url(array('m' => 'forums', 'view' => 'delete_post', 'id' => $this->post_id)),
				$model->lang('DELETE_POST', 'ForumsModel')
			);
		}
		*/
		if ($user->isLoggedIn() and $newest and $this->user_id == $user->getUserID()) {
			$view->setTag('edit', '<a href="/en/forums/form_post/'.$this->post_id.'/">Edit</a>');
			/*
			$options .= sprintf(
				'<a title="%s" class="tooltip" href="%s"><span class="icon icon-edit-2"> </span><span class="tooltip-text">%s</span></a>',
				$model->lang('EDIT_POST', 'ForumsModel', false, true),
				$model->url(array('m' => 'forums', 'view' => 'form_post', 'id' => $this->post_id)),
				$model->lang('EDIT_POST', 'ForumsModel')
			);
			*/
		}
		
		$view->setTag('options', $options);

		if ($index and $pageLimit) $view->setTag('url', $this->getURL($index, $pageLimit));
		
		if ($this->show_user) {
			$poster = $this->getUser();
			$numberPosts = $poster->getNumberForumPosts();
			$view->setTag('number_of_posts', $numberPosts .' post'.($numberPosts > 1 ? 's' : ''));

			$poster_text = $poster->isBanned() ? $poster->getNickname() : $poster->getLinkedNickname();

			$view->setTag('nickname', $poster_text);
		}

		if ($this->show_path)
			$view->setTag('forum_path', $this->getForumPath());

		$view->setTag('date', $model->tool('datetime')->getDateTag($this->ts_unix, false, false, true));
		$view->setTag('post', $post);
		return $view->getOutput();
	}
	
	public function getRSS($index, $pageLimit) {
		$view = new View;
		$view->setPath('blog/rss/item.html');
		$view->setTag('title', $this->getThread()->getTitle().' - Post by '.$this->getUser()->getNickname(false, true));
		$view->setTag('absolute_url', $this->getURL($index, $pageLimit));
		preg_match('/^([^.!?\s]*[\.!?\s]+){0,35}/', strip_tags($this->post), $abstract);
		$body = trim($abstract[0]) . '&hellip;';
		$body = nl2br(ContentCleaner::wrapChinese($body));
		$view->setTag('description', $body);
		$view->setTag('pubdate', date('r', $this->ts_unix));
		return $view->getOutput();
	}

	private function getURL($index, $pageLimit) {
		return $this->getThread()->getURL(false, ceil($index / $pageLimit)) . '#post-'.$this->post_id;
	}

	public function displayAdminRow() {
		if ($this->live)
			$action = 'Delete';
		else {
			$action = 'Undelete';
			$class = ' class="fadeout"';
		}
		
		$content = "<tr valign=\"top\"$class>
		<td width=\"350\">".nl2br($this->post)."</td>
		<td>$this->ts</td>
		<td>$this->nickname</td>
		<td>$this->ip</td>
		<td>".($this->live ? 'Yes' : 'No')."</td>
		<td><a href=\"toggle_live_post.php?post_id=$this->post_id\" onClick=\"return confirm('Are you sure you want to ".strtolower($action)." this?')\">$action</a></td>
		</tr>";
		return $content;
	}

	public function isLastPost() {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT post_id
						   FROM bb_posts
						   WHERE thread_id = $this->thread_id
						   AND live = 1
						   ORDER BY ts DESC LIMIT 1");
		$row = $rs->getRow();
		return $row['post_id'] == $this->post_id ? true : false;
	}

	public function save() {
		global $user, $model;
		$this->post = ContentCleaner::cleanForDatabase($this->post);
		if ($this->post != '' && $user->isLoggedIn()) {
			$db = new DatabaseQuery;
			$thread = $this->getThread();

			if (ctype_digit($this->post_id) && $this->user_id == $user->getUserID() && $this->isLastPost()) {
				$db->execute("	UPDATE bb_posts
								SET ip = '".$user->getIP()."',
									post = '".$db->clean($this->post)."'
								WHERE post_id = ".$this->post_id);
			} elseif (!$this->post_id) {
				if (
					$model->db()->insert('bb_posts', array(
						'thread_id' => $this->thread_id,
						'user_id' => $user->getUserID(), 
						'ts' => unixToDatetime(),
						'ip' => $db->clean($user->getIP()),
						'post' => $this->post
					))
				) {
					$thread->reTimeStamp();
					if (SEND_SUBSCRIBE_EMAILS) $thread->sendSubscribeEmails($this->post);
				}
			}

			if ($this->subscribe == 1) $thread->subscribeUser($user);
		}
	}

	public function getForm() {
		global $user, $model;
		$thread = $this->getThread();

		$subscribe = FormHelper::checkbox(
			$model->lang('FORM_SUBSCRIBE_CAPTION', 'ForumsModel'),
			'subscribe',
			$thread->userIsSubscribed($user),
			array(
				'guidetext' => $model->lang('FORM_SUBSCRIBE_DESCR', 'ForumsModel')
			));

		$view = new View;
		$view->setPath('forums/form.html');
		$view->setTag('thread_id', $this->thread_id);
		$view->setTag('nickname', $user->getNickname());
		$view->setTag('subscribe', $subscribe);
		$view->setTag('post', $this->post);
		$view->setTag('post_id', $this->post_id);
		return $view->getOutput();
	}

	public function toggleLive() {
		$db = new DatabaseQuery;
		$db->execute("	UPDATE bb_posts
						SET live = (live + 1) % 2
						WHERE post_id = $this->post_id");
		$thread = $this->getThread();
		$thread->resetTs();
		$thread->deleteIfZeroPosts();
	}

	function getThreadID()
	{
	return $this->thread_id;
	}

	private function getForumPath() {
		$thread = $this->getThread();
		return sprintf('<span class="forumPostPath">%s</span>', $thread->getPath().' > '.$thread->getLinkedTitle());
	}
}
?>