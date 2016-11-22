<?php
class BlogController {
	private $types = array(
			'all',
			'news',
			'features',
			'travel'
		);
	
	public function item($blog_id = false) {
		global $user, $site, $model;
		
		if (!$blog_id) if (array_key_exists('blog_id', $model->args)) {
			$blog_id = $model->args['blog_id'];
			unset($model->args['blog_id']);
		}
		if (!is_numeric($blog_id)) HTTP::redirect($model->url(array('m' => 'blog')));
		
		$bi = new BlogItem($blog_id);
		
		if (!$bi->getBlogID() or !$bi->checkProperty('live')) HTTP::throw404();
		
		$canonical = $bi->getURL();
		$urlWithArgs = str_replace('&amp;', '&', $bi->getURL(true, array('amp' => '&')));
		
		// Redirect to the proper address if necessary
		if (request($_SERVER['REQUEST_URI']) and $urlWithArgs != $model->urls['root'] . $_SERVER['REQUEST_URI']) HTTP::redirect($urlWithArgs);
		

		// Set meta tags
		$descr = strip_tags($bi->getFirstPara());
		
		$site->addMeta('description', $descr);
		$site->addMeta('keywords', $bi->getTagsArray());
		$site->addMeta('og:description', $descr, 'property');
		$site->addMeta('og:type', 'article', 'property');
		$site->addMeta('og:url', $canonical, 'property');
		$site->addMeta('article:author', sprintf('%s/en/blog/poster/%d/', $GLOBALS['rootURL'], $bi->user_id), 'property');
		$site->addMeta('article:section', $bi->getCategory(), 'property');
		$site->addMeta('article:published_time', date('c', $bi->ts_unix), 'property');
		if ($img = $bi->getImage()) {
			$img = $GLOBALS['rootURL'] . $img;
			$site->addMeta('og:image', $img, 'property');
		}
		
		$p = new Page();
		$author = $bi->getAuthor();
		if ($author->google_plus) $p->setTag('header_extra', sprintf("<link rel=\"author\" href=\"%s\">\n", $author->google_plus));
		$view = new View;
		
		$view->setTag('extra', $this->getCatTabs(strtolower($bi->getCategory())));
		$view->setPath('blog/post.html');
		$view->setTag('blog_id', $bi->getBlogID());
		$view->setTag('absolute_url', $canonical);
		$view->setTag('slashed_title', addslashes(strip_tags($bi->getTitle())));
		$view->setTag('title', $bi->getTitle());
		$social = new Social;
		$view->setTag('social', $social->getSharingList($bi->getTitle()  . ' – ' . $model->lang('SITE_NAME', false, false, true)));
		$view->setTag('body', $bi->getCachedBody());
		$view->setTag('date_publish', $bi->ts_unix);
		$view->setTag('num_comments', $bi->getNumComments());
		$view->setTag('ymd_date', $bi->getYMDate());
		$view->setTag('author_linked', $bi->getAuthorLinked());
		$view->setTag('category', $bi->getCategory());
		$view->setTag('related_articles', $bi->getRelatedArticles());
		$view->setTag('tags', $bi->getTags());
		$view->setTag('comments', $bi->displayComments());
		$view->setTag('comments_form', $bi->displayCommentsForm());
		$view->setTag('prevnext', $bi->getPrevNext());
		$body = $view->getOutput();

		$p->setTag('page_title', strip_tags($bi->getTitle()));
		$p->setTag('main', $body);
		$p->output();
	}
	
	public function image($size = false, $file = false) {
		global $model;
		$sizes = array('small', 'big');
		if (!$size or !$file or (strlen($file) < 5) or !in_array($size, $sizes)) HTTP::Throw404();
		$path = BLOG_PHOTO_STORE_FILEPATH . $file;
		if (!file_exists($path)) HTTP::Throw404();
		if ($size == 'small') {
			$x = 673; $y = 600;
		} else {
			$x = 1300; $y = 900;
		}
		$model->tool('image')->show($path, $x, $y);
	}

	public function poster($poster_id = false, $page = false) {
		global $user, $site;
		
		if (!$poster_id) HTTP::Throw404();
		
		$admin_user = new AdminUser((int) $poster_id);
		
		if (!$admin_user->user_id) HTTP::Throw404();
		
		$p = new Page();
		$pager = new Pager;
		if ($page) $pager->setCurrentPage($page);

		$name = $admin_user->getDisplayName();
		// Get bio
		$site->addMeta('og:type', 'profile', 'property');
		$site->addMeta('og:url', sprintf('%s/en/blog/poster/%d/', $GLOBALS['rootURL'], $poster_id), 'property');
		
		$bl = new BlogList;
		$view = new View;
		$view->setPath('blog/poster.html');
		if ($admin_user->bio) {
			$view->setTag('bio', ContentCleaner::linkHashURLs($admin_user->bio));
			$site->addMeta('og:description', strip_tags($admin_user->bio), 'property');
			$site->addMeta('description', strip_tags($admin_user->bio));
		}
		$size = @getimagesize(TEAM_PHOTO_STORE_FILEPATH.$poster_id.'.jpg');
		
		$site->addMeta('profile:first_name', strip_tags($admin_user->given_name), 'property');
		$site->addMeta('profile:last_name', strip_tags($admin_user->family_name), 'property');
		$site->addMeta('profile:username', strip_tags($admin_user->display_name), 'property');
		
		if ($size) {
			$imageURL = TEAM_PHOTO_STORE_URL . $poster_id . '.jpg';
			$imageTag = sprintf('<span class="imageWrapper"><img class="photo" src="%s" %s></span>', $imageURL, $size[3]);
			$view->setTag('image_tag', $imageTag);
			$site->addMeta('og:image', $GLOBALS['rootURL'] . $imageURL, 'property');
		}
		$view->setTag('google_plus', $admin_user->google_plus);
		$view->setTag('content', $bl->getPoster($poster_id, $pager));
		$view->setTag('pagination', $pager->getNav());
		$view->setTag('author', $admin_user->getAuthorLinked());
		$view->setTag('name', $name);

		$p->setTag('page_title', 'Profile: '.$name);
		$p->setTag('main', $view->getOutput());
		$p->output();
	}

	public function tag() {
		global $user, $model;
		$tag = func_get_arg(0);

		if (func_num_args() > 1) {
			$page = func_get_arg(0);
			$tag = func_get_arg(1);

			if (!ctype_digit($page)) {
				// ok, this is wacky, i agree
				$tag = $page;
				$page = 1;
			}
		}
		$model->tool('linker')->loadURL(false, array('m' => 'blog', 'search' => urldecode($tag)));
	}

	public function category($category = false) {
		global $user, $model, $site;
		if (!$category) HTTP::redirect('/en/blog/', 301);
		elseif (!in_array($category, $this->types)) HTTP::throw404();
		
		$p = new Page();
		$pager = new Pager;
		
		$catLang = $this->getCatLang($category);
		
		$page = request($model->args['page']);
		$search = request($model->args['search']);
		
		$site->addMeta('og:image', $model->urls['root'] . '/assets/og/articles-' . $category . '.png', 'property');
		
		$path = 'blog/search/' . http_build_query(array('category' => $category, 'search' => $model->args['search'], 'page' => $model->args['page']));
		if (!$content = $model->tool('cache')->get($path)) {
			$bl = new BlogList;
			$view = new View('blog/list.html');
		
			if (!$search and (!$page or $page == 1)) {
				$args = array('limit' => 2);
				if ($category != 'all') $args['category'] = $category;
				$articles = $bl->getItems($args);
				$list = array();
				$bi = new BlogItem;
				$content = '<div id="homeTopArticles">';
				foreach($articles as $article) {
					$bi->setData($article);
					$content .= $bi->displayHome();
				}
				$content .= '</div>';
				
				$posts = $bl->getPosts($pager, $category == 'all' ? false : $category, false, 2, 8);
			} else $posts = $bl->getPosts($pager, $category == 'all' ? false : $category, request($model->args['search']));
			
			$view->setTag('extra', $this->getCatTabs($category));
			$view->setTag('content', $posts);
			$view->setTag('pagination', $pager->getNav());
			$view->setTag('searchInfo', $pager->getText() . $content);
			$view->setTag('category', $catLang);
			$title = $model->lang($search ? 'SEARCH' : 'LATEST', 'blog');
			if ($category != 'all') $title .= ' ' . $catLang;
			$title .= ' ' . $model->lang('ARTICLES', 'blog', false, true);
			if ($search) {
				$page_title = htmlspecialchars($search) . ' – ' . $title;
			} else $page_title = $title;
			//$title = sprintf("%s %s %s", $model->lang('LATEST', 'blog'), $catLang, $model->lang('ARTICLES', 'blog'));
			$view->setTag('title', $title);
			$content = $view->getOutput();
			$model->tool('cache')->set($path, $content, 300);
		} else {
			$title = $model->lang($search ? 'SEARCH' : 'LATEST', 'blog');
			if ($category != 'all') $title .= ' ' . $catLang;
			$title .= ' ' . $model->lang('ARTICLES', 'blog', false, true);
			if ($search) {
				$page_title = htmlspecialchars($search) . ' – ' . $title;
			} else $page_title = $title;
		}

		$p->setTag('page_title', $page_title);
		$p->setTag('main', $content);
		$p->output();
	}
	
	private function getCatLang($type) {
		return $GLOBALS['model']->lang('BLOG_' . strtoupper($type), 'blog');
	}
	
	private function getCatTabs($selected = false) {
		$links = array();
		$search = request($GLOBALS['model']->args['search']);
		foreach($this->types as $index => $type) {
			$class = ($index === 0 && !$selected) || $selected == $type  ? ' class="selected"' : '';
			$args = array('m' => 'blog', 'view' => $index ? 'category' : NULL, 'id' => $index ? strtolower($type) : NULL, 'search' => $search);
			$links[] = sprintf('<a%s href="%s">%s</a>', $class, $GLOBALS['model']->url($args), $this->getCatLang($type));
		}
		$content = '<nav class="tabList">';
		$content .= HTMLHelper::wrapArrayInUl($links, 'blogCatSelect');
		$content .= '</nav>';
		return $content;
	}
	
	public function index() {
		$this->category('all');
		global $user, $model;

		$p = new Page();
		
		$view = new View('blog/list.html');
		$title = $GLOBALS['model']->lang('LATEST_ARTICLES', 'blog');
		$path = 'blog/search/' . http_build_query(array('search' => $model->args['search'], 'page' => $model->args['page']));
		if (!$content = $model->tool('cache')->get($path)) {
			$bl = new BlogList;
			$pager = new Pager;
			$view->setTag('extra', $this->getCatTabs());
			$view->setTag('content', $bl->getPosts($pager, false, request($model->args['search'])));
			$view->setTag('pagination', $pager->getNav());
			$view->setTag('searchInfo', $pager->getText());
			
			$view->setTag('title', $title);
			$content = $view->getOutput();
			$model->tool('cache')->set($path, $content, 900);
		}
		$p->setTag('page_title', $title);
		$p->setTag('main', $content);
		$p->output();
	}
	
	public function proc_comment() {
		global $user;

		$blog_comment = new BlogComment;
		$blog_comment->setData($_POST);
		$blog_comment->setUser($user);

		$bi = new BlogItem($_POST['blog_id']);
		$bi->addComment($blog_comment);

		unset($_SESSION['security_code']);
		HTTP::redirect($bi->getURL().'#comments');
	}

	public function stf_form() {
		global $user;
		$blog_id = func_get_arg(0);
		$bi = new BlogItem($blog_id);
		return $bi->displaySTFForm();
	}

	public function proc_stf() 	{
		global $model;
		$blog_item = new BlogItem($_POST['blog_id']);

		if ($_POST['email']) {
			$smtp = new SMTP;
			$smtp->open();

			$mail = new Mail;
			$mail->setFrom('do-not-reply@' . $model->module('preferences')->get('emailDomain'), $model->lang('SITE_NAME'));
			$mail->addTo($_POST['email']);
			$mail->setSubject($model->lang('SITE_NAME') . ': Send-to-Friend');
			$mail->setMessage("Someone sent you the following ".$model->lang('SITE_NAME')." link:\n\nhttp://".$_SERVER['HTTP_HOST'].$blog_item->getURL());

			$smtp->send($mail->getFrom(), $mail->getAllRecipients(), $mail->getData());
			$smtp->quit();
		}

		HTTP::redirect($blog_item->getURL());
	}

	public function comments() {
		global $user;

		$p = new Page();
		$pager = new Pager;
		$pager->setLimit(15);

		$view = new View;
		$view->setPath('blog/all_comments.html');
		$bcl = new BlogCommentsList;
		$view->setTag('content', $bcl->getComments($pager));
		$p->setTag('page_title', 'All Comments');
		$p->setTag('main', $view->getOutput());
		$p->output();
	}
}
?>
