<?php
class ForumsController {
	private $reasons = array('spam', 'double_post', 'off_topic', 'guidelines', 'commercial', 'other');

	public function index() {
		// We don't need this index page
		HTTP::redirect('/en/forums/all/', 301);
	}
	
	private function getBoards($includelink = false) {
		global $model;
		$boards = $GLOBALS['model']->db()->query('bb_boards', false, array('transpose' => array('selectKey' => 'board_id', 'selectValue' => 'board')));
		foreach($boards as $i => $board) {
			$lang = $model->lang('BOARD_' . str_replace(' ', '_', strtoupper($board)), 'ForumsModel');
			$boards[$i] = !$includelink ? $lang : array('board' => $board, 'lang' => $lang, 'url' => $model->url(array('m' => 'forums', 'view' => 'board', 'id' => $i, 'name' => $board)));
		}
		return $boards;
	}
	
	private function getReasons() {
		global $model;
		foreach($this->reasons as $reason) {
			$result[$reason] = $model->lang('REASON_' . strtoupper($reason), 'ForumsModel');
		}
		return $result;
	}
	
	private function getBoardTabs($selected = false) {
		global $model;
		$boards = $this->getBoards(true);
		$boards[0] = array(
			'lang' => $model->lang('BOARD_ALL', 'ForumsModel'),
			'url' =>$model->url(array('m' => 'forums', 'view' => 'all'))
		);
		ksort($boards);
		foreach($boards as $index => $board) {
			$class = $selected == $index  ? ' class="selected"' : '';
			$links[] = sprintf('<a%s href="%s">%s</a>', $class, $board['url'], $board['lang']);
		}
		$content = '<span class="tabListWrapper">';
		$content .= HTMLHelper::wrapArrayInUl($links, 'boardSelect', 'tabList');
		$content .= '</span>';
		return $content;
	}
	
	public function all($page = false) {
		$this->board(false, $page);
	}
	
	public function board($board_id = false) {
		global $user, $model;

		$p = new Page();
		
		$allForumsURL = $model->url(array('m' => 'forums', 'view' => 'all'));
		
		if ($board_id) {
			$fb = new ForumBoard($board_id);
			if (!$fb->getBoardID()) HTTP::Throw404();
			$url = $fb->getURL();
			$title = $fb->getTitle();
		} else {
			$url = $allForumsURL;
			$title = $model->lang('BOARD_ALL', 'ForumsModel');
		}
		
		$pager = new Pager;
		$pager->setLimit(10);
		
		$search = request($model->args['search']);
		$terms = $model->db()->escape_clause($search);
		
		$boardSearch = $board_id ? (" AND board_id = " . $board_id) : '';
		if ($search) {
			if (strlen($search) > 3) {
				$sql = sprintf("
					SELECT 
						t.*,
						CASE WHEN thread LIKE '%%%s%%' THEN 1 ELSE 0 END AS titlematch,
						COUNT(t.thread_id) *
						(
							MATCH (thread) AGAINST ('%s') +
							AVG(MATCH (post) AGAINST ('%s')) 
						) AS score
					FROM bb_threads t
					LEFT JOIN bb_posts p ON (t.thread_id = p.thread_id)
					WHERE t.live = 1 AND p.live = 1
						AND (
							MATCH (thread) AGAINST ('%s')
							OR MATCH (post) AGAINST ('%s')
						)
						%s
					GROUP BY t.thread_id
					HAVING score > 5
					ORDER BY titlematch DESC, score DESC
				", $terms, $terms, $terms, $terms, $terms, $boardSearch);
			} else {
				$sql = sprintf("
					SELECT 
						t.*,
						CASE WHEN thread LIKE '%%%s%%' THEN 1 ELSE 0 END * 5 +
						SUM(CASE WHEN post LIKE '%%%s%%' THEN 1 ELSE 0 END) AS score
					FROM bb_threads t
					LEFT JOIN bb_posts p ON (t.thread_id = p.thread_id)
					WHERE t.live = 1 AND p.live = 1
						AND (
							thread LIKE '%%%s%%'
							OR post LIKE '%%%s%%'
						)
						%s
					GROUP BY t.thread_id
					ORDER BY score DESC
				", $terms, $terms, $terms, $terms, $boardSearch);
			}
		} else {
			$sql = "SELECT DISTINCT t.*, COUNT(p.post_id) AS posts
				FROM bb_threads t
				LEFT JOIN bb_posts p ON (t.thread_id = p.thread_id)
				 WHERE t.live = 1 and p.live = 1" .
				$boardSearch .
				" GROUP BY thread_id HAVING posts > 0 ORDER BY ts DESC";
		}
		
		$rs = $pager->setSQL($sql);
		$content = '';
		$ft = new ForumThread;
		while ($row = $rs->getRow()) {
			$ft->setData($row);
			$url = $ft->getURL();
			$urlLatest = $ft->getURL(true);
			$posts = $ft->getNumberPosts();
			$content .= sprintf("
				<article>
					<a href=\"%s\">%s</a>
					<div class=\"top\">
						<a href=\"%s\" class=\"postCount\">%d %s</a> •
						<span class=\"postedBy\">%s %s, %s</span>
					</div>
					<h1>%s</h1>
					<div class=\"latestBy\"><a href=\"%s\">Latest post</a> by %s, %s</div>
				</article>
			",
				$url,
				$ft->getIcon(),
				$url,
				$ft->getNumberPosts(),
				$model->lang($posts > 1 ? 'POSTS' : 'POST', 'ForumsModel'),
				$model->lang('POSTED_BY', 'ForumsModel'),
				$ft->getOriginalPosterUser()->getPublicURL(),
				$ft->getOriginalPostingDate(),
				$ft->getLinkedTitle(),
				$urlLatest,
				$ft->getLatestPosterUser()->getPublicURL(),
				$ft->getLatestPostingDate()
			);
		}
		
		$pager = $pager->getNav();
		
		$search = sprintf('<form id="searchForumsForm" class="searchForm%s" method="get" action="%s">
				<div>
					%s
					<input class="searchSubmit" type="submit" value="%s">
					<a href="%s" class="icon icon-cancel searchCancel"><span>%s</span></a>
				</div>
			</form>',
			$search ? ' hasSearchValue' : '',
			$model->url(false, false, true),
			$model->tool('tag')->input(array('attr' => array('type' => 'search', 'class' => 'text', 'placeholder' => $model->lang('SEARCH', 'ForumsModel', false, true) . ' ' . strtolower($title . ' ' . $model->lang('POSTS', 'ForumsModel', false, true)), 'id' => 'inputQ', 'name' => 'search', 'value' => $search))),
			$model->lang('SEARCH_BUTTON_UPDATE', 'ForumsModel', false, true),
			$model->url(array('page' => false, 'search' => false), false, true),
			$model->lang('SEARCH_BUTTON_CANCEL', 'ForumsModel')
		);
		
		$body = sprintf("
		<div id=\"forums\">
			<header id=\"contentHeader\">
				<h1><a href=\"%s\">%s</a></h1>
				%s
			</header>
			<div id=\"controls\">
				<a class=\"button\" href=\"%s\"><span class=\"icon icon-edit\"> </span> %s</a>
			</div>
			%s
			<div id=\"forumList\">%s</div>
			<div class=\"pagination\">%s</div>
		</div>
		",
			$allForumsURL,
			$model->lang('FORUMS_TITLE', 'ForumsModel'),
			$this->getBoardTabs($board_id),
			$model->url(array('m' => 'forums', 'view' => 'post', 'id' => $board_id)),
			$model->lang('B_START_THREAD', 'ForumsModel'),
			$search,
			$content,
			$pager
		);
		
		$p->setTag('page_title', $title . ' Forums');
		$p->setTag('main', $body);
		$p->output();
	}

	public function thread($thread_id = false) {
		global $user, $model;
		
		if (!$thread_id) HTTP::redirect('/en/forums/');
		
		$ft = new ForumThread($thread_id);
		
		if ($ft->isLive()) {
			$p = new Page();
			$pager = new Pager;
			$pager->setLimit(10);
			$view = new View;
			$view->setPath('forums/thread.html');
			$view->setTag('thread_id', $ft->thread_id);
			$view->setTag('topTitle', $model->lang('FORUMS_TITLE', 'ForumsModel'));
			$view->setTag('allForumsURL', $model->url(array('m' => 'forums', 'view' => 'all')));
			$view->setTag('url', $ft->getURL());
			$view->setTag('title', $ft->getTitle());
			$view->setTag('subscribed', $ft->userIsSubscribed($user));
			$view->setTag('tabs', $this->getBoardTabs($ft->board_id));
			//$view->setTag('breadcrumb', $ft->getPath());
			$view->setTag('posts', $ft->displayPosts($pager));
			$view->setTag('pagination', $pager->getNav());
			$view->setTag('searchInfo', $pager->getText());
			
			if ($user->isLoggedIn() && !$ft->isLocked()) {
				$fp = new ForumPost;
				$fp->setThreadID($ft->thread_id);
				$view->setTag('form', $fp->getForm());
			}
			
			$body = $view->getOutput();
			
			if (!$user->isLoggedIn()) {
				$body .= sprintf(
					"<p>
						<a class=\"icon-link\" href=\"/en/users/login/\"><span class=\"icon icon-login\"> </span>%s</a>
						<a class=\"icon-link\" href=\"/en/users/register/\"><span class=\"icon icon-user-add\"> </span>%s</a>
					</p>",
					$view->lang('LOGIN_TO_POST'),
					$view->lang('REGISTER_TO_POST')
				);
			}

			if ($ft->isLocked()) {
				$view = new View;
				$view->setPath('forums/locked.html');
				$body .= $view->getOutput();
			}

			$p->setTag('page_title', strip_tags($ft->getTitle()));
			$p->setTag('main', $body);
			$p->output();
		}
		else
			HTTP::throw404();
	}

	public function report_post($id) {
		global $user, $model;
		
		if (!$user->isLoggedIn()) HTTP::disallowed();
		
		if (!$id or !is_numeric($id)) HTTP::throw404();
		else $fp = new ForumPost($id);
		if (!$fp->post_id) HTTP::throw404();
		
		$p = new Page();
		
		$title = $model->lang('REPORT_POST', 'ForumsModel', false, true);
		$content = sprintf('<h1 class="dark">%s</h1>', $title);
		
		//$content = $this->displayErrors('<p>Sorry, there seems to have been problems with your form:</p>');
		$fp->setShowPath(true);
		$content .= sprintf('<div class="userContentList">%s</div>', $fp->display());
		
		$content .= FormHelper::open($model->url(false, false, true));
		
		$f[] = FormHelper::select($model->lang('FORM_CAPTION_REASON', 'ForumsModel'), 'board_id', $this->getReasons(), request($_POST['reason']), array('mandatory' => true, 'emptyCaption' => $model->lang('SELECT_REASON', 'ForumsModel', false, true)));
		$f[] = FormHelper::textarea($model->lang('FORM_CAPTION_EXPLANE', 'ForumsModel'), 'explane', request($_POST['explane']), array('mandatory' => true));
		$f[] = FormHelper::submit($model->lang('FORM_BUTTON_SUBMIT', 'ForumsModel', false, true));
		
		$content .= FormHelper::fieldset('', $f);
		$content .= FormHelper::close();
		
		$p->setTag('page_title', $title);
		$p->setTag('main', $content);
		$p->output();
	}
	
	public function form_post($post_id = false) {
		global $user;
		
		if (!$user->isLoggedIn()) HTTP::disallowed();
		
		if (!$post_id) HTTP::redirect('/en/forums/');
		
		$fp = new ForumPost($post_id);
		
		if (!$fp->post_id) HTTP::throw404();
		
		$ft = $fp->getThread();

		if ($fp->getUserID() == $user->getUserID()) {
			if (
				!$fp->live or
				!$fp->isLastPost()
			) HTTP::redirect($ft->getURL());
			$p = new Page();

			$view = new View;
			$view->setPath('forums/edit_post.html');
			$view->setTag('title', $ft->getTitle());
			$view->setTag('breadcrumb', $ft->getPath());
			$view->setTag('posts', $fp->getForm());
			$view->setTag('number_posts', $ft->getNumberPosts());
			$body = $view->getOutput();

			if (!$user->isLoggedIn()) {
				$view = new View;
				$view->setPath('forums/reminder.html');
				$body .= $view->getOutput();
			}

			$p->setTag('page_title', strip_tags($ft->getTitle()));
			$p->setTag('main', $body);
			$p->output();
		}
		else HTTP::disallowed();
	}

	public function thread_rss($thread_id = false) {
		global $user, $model;
		
		if (!$thread_id or !is_numeric($thread_id)) HTTP::throw404();
		
		$ft = new ForumThread($thread_id);
		
		$rss = '';
		if ($ft->isLive()) {
			header('Content-type: text/xml; charset=utf-8');
			echo '<?xml version="1.0" encoding="utf-8"?>';
			$view = new View;
			if (!$content = $view->setPath('blog/rss/index.html', false, 180, 'forums/thread/' . $thread_id)) {
				$posts = $model->db()->run_select('
					SELECT p.*, u.nickname, u.user_id, UNIX_TIMESTAMP(ts) AS ts_unix
					FROM bb_posts p
					LEFT JOIN public_users u ON (u.user_id = p.user_id)
					WHERE thread_id = ' . $ft->getThreadID() . '
					AND live = 1
					ORDER BY ts ASC
					LIMIT 10
				');
				$fp = new ForumPost;
				$fp->setThread($ft);
				$totalPosts = $ft->getNumberPosts();
				foreach ($posts as $index => $post) {
					$fp->setData($post);
					$rss .= $fp->getRSS($totalPosts - $index, 10);
				}
				
				$view->setTag('title', $ft->getTitle());
				$view->setTag('link', $ft->getURL());
				$view->setTag('atom_link', $model->tool('linker')->prettifyURL(array('m' => 'forums', 'view' => 'thread_rss', 'id' => $ft->thread_id)));
				$view->setTag('description', $ft->getTitle().' - ' . $model->lang('SITE_NAME') . ' Forum');
				$view->setTag('items', $rss);
				$content = $view->getOutput();
			}
			
			echo $content;
		}
		else
			HTTP::throw404();
	}

	public function post($board_id = false) {
		global $user, $model;
		
		if (!$user->isLoggedIn()) HTTP::disallowed();
		
		$p = new Page();
		
		$body = sprintf('<h1 class="dark">%s</h1>', $model->lang('NEW_FORUM_THREAD', 'ForumsModel'));
		$body .= sprintf('<div id="controls"><a class="icon-link" href="%s"><span class="icon icon-arrow-left"> </span> %s</a></div>', $model->url(array('m' => 'forums', 'view' => 'all')), $model->lang('BACK_TO_FORUMS', 'ForumsModel'));
		
		$form = isset($_SESSION['forumthread_form']) ? $_SESSION['forumthread_form'] : new ForumThreadForm(array('board_id' => $board_id, 'boards' => $this->getBoards()));
		$body .= $form->display();
		unset($_SESSION['forumthread_form']);
		
		$p->setTag('main', $body);
		$p->output();
	}

	public function proc_thread() {
		global $user, $model;
		
		if (!$user->isLoggedIn()) HTTP::redirect("/en/forums/");
		
		$board_id = request($_POST['board_id']);
		if (!$board_id or ($fb = new ForumBoard($board_id) and !$fb->getBoardID())) $form->addError('- please select a board to post a new thread in');
		
		$form = new ForumThreadForm(array('board_id' => $board_id, 'boards' => $this->getBoards()));
		
		$form->setData($_POST);
		
		$exists_validator = new ExistenceValidator($form);
		$exists_validator->validate('post', '- please provide the content for your new forum thread');

		$len_validator = new LengthValidator($form);
		$len_validator->setMinLength(10);
		$len_validator->setMaxLength(60);
		$len_validator->validate('thread', '- please enter title of 10 to 60 characters');
		
		// Match any URL
		//$result = preg_match('#(?i)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))#', $form->getDatum('post'));
		$content = ContentCleaner::cleanForDatabase($form->getDatum('post'));
		preg_match_all("/http:\/\/[^\s<]+/", $content, $matches);
		$matches = $matches[0];
		
		$alreadyPostedInLastHalfHour = $model->db()->query('bb_threads', array('user_id' => $user->getUserID(), '!ts_created > DATE_SUB(NOW(), INTERVAL 30 MINUTE)'));
		
		if ($this->isSpam($form->getDatum('post'))) {
			$form->addError('- You are a new poster – please avoid posting links and/or use keywords that may be considered spam');
			$form->addError('- If you believe your post should not be considered as spam, please contact us through the <a href="/en/contact/">contact form</a>');
		} elseif($alreadyPostedInLastHalfHour) {
			$form->addError('- You cannot start more than one new forum thread per half an hour.');
		} elseif (!$form->getErrorCount()) {
			$ft = new ForumThread;
			$ft->setData($_POST);
			$ft->save();
			HTTP::redirect($model->url(array('m' => 'forums', 'view' => 'board', 'id' => $board_id)));
		}
		
		$_SESSION['forumthread_form'] = $form;
		HTTP::redirect($model->url(array('m' => 'forums', 'view' => 'post', 'id' => $board_id)));
	}

	public function post_proc() {
		global $user;
		
		if (!$user->isLoggedIn()) HTTP::redirect("/en/forums/");
		
		if (!$_POST['thread_id'] or !ctype_digit($_POST['thread_id'])) HTTP::redirect('/en/forums/');

		$ft = new ForumThread($_POST['thread_id']);
		
		if (!$ft->thread_id) HTTP::redirect('/en/forums/');
		
		if (
			isset($_POST['thread_id']) and
			ctype_digit($_POST['thread_id']) and
			$ft = new ForumThread($_POST['thread_id']) and
			!$ft->isLocked() and
			$ft->isLive() and
			isset($_POST['post_id']) and
			ctype_digit($_POST['post_id']) and
			!$this->isSpam(request($_POST['post']))
		) {
			$fp = new ForumPost($_POST['post_id']);
			// Another fucked up method. If there's no post idea, we don't have to check if the post
			// may be edited. Otherwise, check if the user is the same and if the post is live and is the last post.
			if (
				!$_POST['post_id'] or
				(
					$fp->post_id and
					$fp->getUserID() == $user->getUserID() and
					$fp->live and
					$fp->isLastPost()
				)
			) {
				$fp->setData($_POST);
				$fp->save();
			}
		} else {
			// If only this was coded decently, we could do something with error reporting.
		}
		HTTP::redirect($ft->getURL(true));
	}
	
	// Generic spam checker for forum posts
	private function isSpam($content) {
		global $user, $model;
		
		if ($user->ts_registered_unix + 3600 * 48 > time() or $user->getNumberForumPosts() < 4) {
			
			//$result = preg_match('#(?i)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))#', $form->getDatum('post'));
			$content_clean = ContentCleaner::cleanForDatabase($content);
			preg_match_all("/http:\/\/[^\s<]+/", $content_clean, $matches);
			$matches = $matches[0];
			
			preg_match_all('/watch|streaming|live|passport|license|drugs|id card|viagra|fake|^$/i', $content_clean, $keywords);
			$keywords = $keywords[0];
			
			// If there's links in the content, check if the user is a new poster
			// Now having less than 4 active posts or an account less than 2 days old disables people from posting links.
			if (
				$matches or count($keywords) > 7
			) {
				// Add a record in our log
				$model->db()->insert('bb_spam_log', array(
					'content' => $content,
					'user_id' => $user->getUserID(),
					'ip' => $_SERVER['REMOTE_ADDR'],
					'user_agent' => $_SERVER['HTTP_USER_AGENT'],
					'user_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'],
					'user_referer' => $_SERVER['HTTP_REFERER'],
					'date' => unixToDatetime()
				));
				return true;
			}
		}
		return false;
	}

	public function subscribe() {
		global $user;
		$thread = new ForumThread(func_get_arg(0));
		
		if ($user->isLoggedIn()) {
			if ($thread->userIsSubscribed($user))
				$thread->unsubscribeUser($user);
			else
				$thread->subscribeUser($user);
		} else HTTP::redirect('/en/users/login/');
		
		if ($_GET['from'] == 'dashboard')
			HTTP::redirect('/en/users/forums_subscriptions/');
		else
			HTTP::redirect($thread->getURL());
	}
}
?>