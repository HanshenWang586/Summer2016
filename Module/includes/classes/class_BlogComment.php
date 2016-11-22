<?php
class BlogComment {

	private $show_post_linl = false;

	public function __construct($comment_id = '') {
		$this->comment_id = $comment_id;
	}

	public function setData($data) {
		if (is_array($data)) {
			foreach ($data as $key => $value)
				$this->$key = $value;
		}
	}

	function setUser($user)
	{
	$this->user = $user;
	}

	public function setShowPostLink($bool) {
		$this->show_post_link = $bool;
	}

	public function save() {
		if ($this->passesUserChecks()) {
			$comment = ContentCleaner::cleanForDatabase($this->comment);
			$nickname = ContentCleaner::cleanForDatabase($this->nickname);

			if (	$comment != '' &&
					(	$nickname != '' || $this->user->isLoggedIn()) &&
						$this->blog_id != 0
					) {
				$db = new DatabaseQuery;
				$db->execute("	INSERT INTO blog_comments (	blog_id,
															user_id,
															nickname,
															comment,
															ts,
															ip,
															session_id)
								VALUES (	".$db->clean($this->blog_id).",
											".$this->user->getUserID().",
											'".$db->clean($nickname)."',
											'".$db->clean($comment)."',
											NOW(),
											'".$db->clean($this->user->getIP())."',
											'".$db->clean($this->user->getSessionID())."')");
			}
		}
	}

	public function displayForm(&$blog) {
		global $user;
		$view = new View();
		if (!$user->isLoggedIn()) {
			$content .= 
				sprintf(
					"<p>
						<a class=\"icon-link\" href=\"/en/users/login/\"><span class=\"icon icon-login\"> </span>%s</a>
						<a class=\"icon-link\" href=\"/en/users/register/\"><span class=\"icon icon-user-add\"> </span>%s</a>
					</p>",
					$view->lang('LOGIN_TO_COMMENT', 'BlogModel'),
					$view->lang('REGISTER_TO_COMMENT', 'BlogModel')
				);
		}
		else {
			if ($blog->checkProperty('allow_registered_user_comments')) {
				$content .= "<p class=\"infoMessage\">
					You are logged in as <strong>".$user->getNickname()."</strong>. If you are not <strong>".$user->getNickname()."</strong>
					please <a href=\"/en/users/logout/\">click here</a>.
				</p>";

				$content .= FormHelper::open('/en/blog/proc_comment/');
				$content .= FormHelper::hidden('blog_id', $blog->getBlogID());

				$f[] = FormHelper::textarea('Comment', 'comment', '', array('mandatory' => true));
				$f[] = FormHelper::submit('Save');

				$content .= FormHelper::fieldset('', $f);
				$content .= FormHelper::close();
			}
			else
				$content .= '<p class="infoMessage">Comments on this post are disabled.</p>';
		}

		return $content;
	}

	public function displayPublic($showArticle = false) {
		global $model;
		if ($showArticle) {
			$bi = $this->getBlogItem();
			$top = sprintf('
				<div itemprop="discusses" itemscope itemtype="http://schema.org/article">
					<a class="img" itemprop="url" href="%s"><img itemprop="thumbnailUrl" width="60" height="60" src="%s"></a>
					<h1 itemprop="name">%s</h1>
					<span class="postedBy">%s</span>
				</div>
			', $bi->getURL(), $bi->getImage(120,120), $bi->getTitleLinked(), $model->lang('POSTED_BY', 'BlogModel'));
		} else $top = '';
		return sprintf("
			<article id=\"comment-%d\" class=\"comment\" itemprop=\"comment\" itemscope itemtype=\"http://schema.org/Comment\">
				<header>
					%s
					<span itemprop=\"creator\" itemscope itemtype=\"http://schema.org/Person\">%s</span> •
					%s
				</header>
				<div itemprop=\"commentText\" class=\"body\">%s</div>
			</article>\n",
			$this->comment_id,
			$top,
			$this->getProcessedNickname(),
			$this->getDateTag(),
			$this->getCommentBody()
		);
	}
	
	public function getDateTag() {
		return $GLOBALS['model']->tool('datetime')->getDateTag($this->ts_unix, false, 'commentTime', true);
	}

	public function displayPublicForUserProfile() {
		$bits[] = DateManipulator::convertUnixToFriendly($this->ts_unix, array('show_year' => true));
		$bits[] = $this->getBlogItem()->getTitleLinked().'<br />'.$this->getCommentBody();
		return HTMLHelper::wrapArrayInUl($bits);
	}

	public function getBlogItem() {
		if (!$this->blogItem or $this->blog_id != $this->blogItem->getBlogID()) {
			$this->blogItem = new BlogItem($this->blog_id);
		}
		return $this->blogItem;
	}

	private function getCommentBody() {
		$comment = ContentCleaner::cleanPublicDisplay($this->comment);
		$comment = ContentCleaner::wrapChinese($comment);
		$comment = ContentCleaner::linkURLs($comment);
		$comment = ContentCleaner::PWrap($comment);
		return $comment;
	}

	public function displayAdminRow($page) {
		$this->nickname = strip_tags($this->getProcessedNickname());
		$content = "<tr valign=\"top\"".($this->live ? '' : ' class="fadeout"').">
		<td>$this->site_name</td>
		<td width=\"200\">$this->title</td>
		<td>$this->nickname<br />
	$this->ip<br />
	$this->session_id</td>
		<td nowrap>$this->ts</td>
		<td width=\"300\">".$this->getProcessedComment()."</td>
		<td>$this->live</td>
		<td><a href=\"toggle_blogcomment.php?comment_id=$this->comment_id&page=$page\">".($this->live ? 'Delete' : 'Undelete')."</a></td>
		</tr>";
		return $content;
	}

	function getProcessedComment()
	{
	return nl2br(htmlspecialchars($this->comment));
	}

	public function getProcessedNicknameNoLink() {
		return !$this->user_id ? htmlspecialchars($this->nickname, ENT_QUOTES, 'UTF-8') : $GLOBALS['site']->getUser($this->user_id)->getNickname(true);
}

	private function getProcessedNickname() {
		return !$this->user_id ? htmlspecialchars($this->nickname, ENT_QUOTES, 'UTF-8') : $GLOBALS['site']->getUser($this->user_id)->getLinkedNickname(true);
	}

	private function passesUserChecks() {
		if (in_array($this->user->getIP(), array('64.120.169.137', '222.244.64.162', '70.32.38.84', '74.222.11.23', '58.20.115.158')))
			return false;

		if ($this->user->isLoggedIn()) // logged in users get to post fine
			return true;
		else if ($this->security_code == $_SESSION['security_code'] && $_SESSION['security_code'] != '') {
			if (strip_tags($this->comment) != $this->comment) // check for HTML
				return false;
			else
				return true;
		}
		else
			return false;
	}

	public function toggleLive() {
		$db = new DatabaseQuery;
		$db->execute('	UPDATE blog_comments
						SET live = (live + 1) % 2
						WHERE comment_id = '.$this->comment_id);
	}
}
?>