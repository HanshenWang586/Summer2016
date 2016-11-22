<?php
class PagesController {

	public function team() {
		global $user, $model;

		$p = new Page;
		$p->setTag('page_title', $model->lang('ABOUT_US'));

		$view = new View('pages/about_us.html');
		$body = $view->getOutput();
		$body .= '<h2>The GoKunming Team</h2>';
		$db = $model->db();
		
		$team = $db->query('admin_users', array('live' => 1, 'team' => 1, "!bio != ''"), array('orderBy' => 'family_name'));
		$users = array();
		$admin_user = new AdminUser;
		foreach ($team as $person) {
			$admin_user->setData($person);
			$users[] = $admin_user->displayPublic();
		}

		$body .= HTMLHelper::wrapArrayInUl($users, 'team_list', false, 'vcard');
		
		$body .= '<h2>Contributors</h2>';
		$contrib = $db->query('admin_users', array('team' => 0), array(
			'getFields' => array('user_id', 'display_name'),
			'join' => array('table' => 'blog_content', 'alias' => 'bc', 'on' => array('user_id', 'user_id'), 'fields' => 'COUNT(*) AS posts'),
			'orderBy' => 'posts',
			'order' => 'DESC',
			'having' => '!posts > 2',
			'groupBy' => 'admin_users.user_id'
		));
		
		$body .= '<div id="contributors">';
		foreach($contrib as $c) {
			$body .= sprintf("<a href=\"/en/blog/poster/%d/\" class=\"contributors\"><span class=\"name\">%s</span> <span class=\"articles\">%d articles</span></a>", $c['user_id'], $c['display_name'], $c['posts']);
		}
		$body .= '</div>';
		
		$body .= sprintf('<footer>
						<a class="icon-link" href="%s"><span class="icon icon-pen"> </span> %s</a>
						<a class="icon-link" href="%s"><span class="icon icon-envelope"> </span> %s</a>
						</footer>',
						$model->url(array('m' => 'pages', 'view' => 't', 'id' => 'write-for-us')), $model->lang('MENU_CONTRIBUTE'),
						$model->url(array('m' => 'contact')), $model->lang('MENU_CONTACT_US')
					);
		
		$p->setTag('main', $body);
		$p->output();
	}
	
	public function t($page = false) {
		global $model;
		if (!$page or !is_string($page)) HTTP::throw404();
		$page = str_replace('-', '_', $page);
		$view = new View('pages/' . str_replace('-', '_', $page) . '.html');
		if (!$view->exists()) HTTP::throw404();
		else {
			$p = new Page;
			$title = $model->lang('TITLE_' . strtoupper($page), false, false, true);
			$p->setTag('page_title', $title);
			$content = ContentCleaner::wrapChinese($view->getOutput());
			$content = ContentCleaner::linkHashURLs($content);
			$content = ContentCleaner::PWrap($content);
			$body = sprintf('
				<h1 class="dark">%s</h1>
				<div class="pageWrapper">%s</div>',
				$title,
				$content
			);
			$p->setTag('main', $body);
			$p->output();
		}
	}
}
?>