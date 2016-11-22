<?php
class Page {

	private $template = 'default.html';

	public function __construct() {
		global $model;
		$this->start = microtime(true);
		
		$ads = $model->module('ads');
		$this->setTag('topBanner', $ads->get('topBanner'));
		$this->setTag('topSidebar', $ads->get('topSidebar'));
		$this->setTag('bottomSidebar', $ads->get('bottomSidebar'));
		
		$cache = $model->tool('cache');
		
		$cal = new Calendar;
		
		$this->setTag('calendar', $cal->getSidebar());
				
		if (!$latest = $cache->get('sidebarClassifieds')) {
			$cl = new ClassifiedsList;
			$latest = $cl->getLatest(5);
			$cache->set('sidebarClassifieds', $latest, 177);
		}
		$this->setTag('classifieds', $latest);
		
		if (!$latest = $cache->get('sidebarForums')) {
			$ftl = new ForumThreadList;
			$latest = $ftl->getLatest(5);
			$cache->set('sidebarForums', $latest, 179);
		}
		$this->setTag('forums', $latest);
		
		if (!$latest = $cache->get('sidebarComments')) {
			$bcl = new BlogCommentsList;
			$latest = $bcl->getSidebarLatest(5);
			$cache->set('sidebarComments', $latest, 183);
		}
		$this->setTag('comments', $latest);
		
		/*
		$view = new View;
		// featured advertisers
		if (!$content = $view->setPath('featured_advertisers.html', true, 300)) {
			$al = new AdvertiserList;
			$view->setTag('advertisers', $al->getFeatAdv());
			$content = $view->getOutput();
		}
		$this->setTag('featured_advertisers', $content);
		*/
	}
	
	public function setTag($tag, $content) {
		$this->tags[$tag] = $content;
	}

	public function setTemplate($template) {
		$this->template = $template;
	}
	
	/**
	 * Returns the metatag stack as a String
	 *
	 * @return string
	 */
	public function sprintMeta() {
		global $site;
		$return = '';
		if ($site->metaTags) foreach ($site->metaTags as $name => $value) {
			if (is_vector($value[1])) $value[1] = implode(',', $value[1]);
			$return .= sprintf("\t\t<meta %s=\"%s\" content=\"%s\">\n", $value[0], makeTagEntities($name), makeTagEntities($value[1]));
		}
		return $return;
	}
	
	public function output() {
		global $user, $site, $model;
		
		$html = $model->tool('html');
		$this->setTag('body_id', $model->state('module'));
		$this->setTag('js', $html->sprintJSIncludes() . $html->sprintJS());
		$this->setTag('css', $html->sprintCSSIncludes());
		
		if ($user->isLoggedIn()) {
			$user_status = sprintf('
				<li><span class="caption">%s</span></li>
				<li><a href="%s"><span class="icon icon-dashboard"></span>%s</a></li>
				<li><a href="%s"><span class="icon icon-user-2"></span>%s</a></li>
				<li><a href="%s"><span class="icon icon-mail"></span>%s</a></li>
				<li><a href="%s"><span class="icon icon-logout"></span>%s</a></li>',
				$user->nickname,
				$model->url(array('m' => 'users', 'view' => 'dashboard')),
				$model->lang('DASHBOARD'),
				$model->url(array('m' => 'users', 'view' => 'profile', 'id' => $user->getUserID())),
				$model->lang('PUBLIC_PROFILE'),
				$model->url(array('m' => 'users', 'view' => 'pm_inbox')),
				$model->lang('MY_MESSAGES'),
				$model->url(array('m' => 'users', 'view' => 'logout')),
				$model->lang('LOGOUT')
			);
			if (!$user->verified) {
				$user_status = sprintf('<li><a class="red" href="/en/users/verify/" title="%s">%s</a></li>%s',
					$model->lang('CLICK_TO_VERIFY'),
					$model->lang('EMAIL_NOT_VERIFIED'),
					$user_status
				);
			}
			$this->setTag('user_status', $user_status);
		}
		else {
			$this->setTag('user_status', sprintf('<li><a href="/en/users/login/"><span class="icon icon-login"></span>%s</a></li>
											<li><a href="/en/users/register/"><span class="icon icon-user-add"></span>%s</a></li>',
											$model->lang('MENU_LOGIN'),
											$model->lang('MENU_REGISTER')
										));
		}

		$view = new View;
		$view->setPath('/templates/'.$this->template);
		
		$descr = $model->lang('SITE_DESCR', false, false, true);
		$title = $model->lang('SITE_NAME', false, false, true);
		
		if (!$this->tags['page_title']) {
			$this->tags['page_title'] = $title = $model->lang('SITE_TITLE', false, false, true);
		} else {
			$this->tags['page_title'] .= ' - ' . $title;
		}
		
		$site->addMeta('application-name', $title);
		$site->addMeta('msapplication-tooltip', $descr);
		
		$social = new Social();
		$this->setTag('social', $social->getLinkList());
		
		// Handle meta tags
		if (!array_key_exists('og:title', $site->metaTags)) $site->addMeta('og:title', $this->tags['page_title'], 'property');
		if (!array_key_exists('description', $site->metaTags)) $site->addMeta('description', $descr);
		if (!array_key_exists('og:description', $site->metaTags)) $site->addMeta('og:description', $descr, 'property');
		$site->addMeta('og:site_name', $title, 'property');
		if (!array_key_exists('og:image', $site->metaTags)) {
			if ($m = request($model->args['m'])) {
				$img = '/assets/og/' . $m . '.png';
				if (!file_exists($model->paths['root'] . $img)) $img = '/assets/og/gokunming-general-logo-2.png';
			} else $img = '/assets/og/gokunming-general-logo-2.png';
			$site->addMeta('og:image', $model->urls['root'] . $img, 'property');
		}
		if (!array_key_exists('og:url', $site->metaTags)) $site->addMeta('og:url', $model->url(false, false, true), 'property');
		
		$keywords = array('Kunming', 'Yunnan', 'China', 'events', 'forums', 'classifieds', 'news');
		$kw = $site->metaTags['keywords'];
		if ($kw) $kw = array_merge($keywords, $kw);
		else $kw = array_merge($keywords, array('Spring City', '昆明', '云南', 'southwest China', 'listings', 'travel', 'nightlife', 'bars', 'jobs', 'housing', 'events', 'cafes', 'articles', 'blog'));
		$site->addMeta('keywords', $kw);
		
		$this->tags['meta_tags'] = $this->sprintMeta();
		
		foreach($this->tags as $tag => $content)
			$view->setTag($tag, $content);
		
		// Create main menu
		$items = array(
			array(
				'url' => '/en/',
				'icon' => 'home',
				'code' => 'HOME'
			),
			array(
				'url' => '/en/blog/',
				'icon' => 'pen',
				'code' => 'BLOG'
			),
			array(
				'url' => '/en/calendar/',
				'icon' => 'calendar',
				'code' => 'CALENDAR'
			),
			array(
				'url' => '/en/listings/city/kunming/',
				'urlcheck' => '/en/listings/',
				'icon' => 'star',
				'code' => 'LISTINGS'
			),
			array(
				'url' => '/en/forums/all/',
				'urlcheck' => '/en/forums/',
				'icon' => 'bubbles',
				'code' => 'FORUMS'
			),
			array(
				'url' => '/en/classifieds/',
				'icon' => 'earth-2',
				'code' => 'CLASSIFIEDS'
			),
			array(
				'url' => '/en/pages/team/',
				'icon' => 'info',
				'code' => 'ABOUT_US'
			),
			array(
				'url' => '/en/contact/',
				'icon' => 'envelope',
				'code' => 'CONTACT_US'
			)
		);
		
		$menuItems = array();
		foreach($items as $data) { 
			$urlcheck = array_key_exists('urlcheck', $data) ? $data['urlcheck'] : $data['url'];
			$class = (
					$urlcheck == '/en/' && $_SERVER['REQUEST_URI'] == '/en/'
					or $urlcheck != '/en/' && strpos($_SERVER['REQUEST_URI'], $urlcheck) > -1
				) ? ' focus' : '';
			$menuItems[] = sprintf('<a class="tooltip%s" href="%s"><span class="icon icon-%s"></span>%s<span class="tooltip-text">%s</span></a>', $class, $data['url'], $data['icon'], $model->lang('MENU_' . $data['code']), $model->lang('MENU_' . $data['code'] . '_TOOLTIP'));
		}
		
		$view->setTag('menu', HTMLHelper::wrapArrayInUl($menuItems));
		
		//$model->tool('cache')->delete('weather');
		$w = $model->tool('cache')->get('weather');
		
		if (request($w['moon_phase'])) {
			$w['now'] = date_create();
			$rise = $w['moon_phase']['sunrise']['hour'] . $w['moon_phase']['sunrise']['minute'];
			$set = $w['moon_phase']['sunset']['hour'] . $w['moon_phase']['sunset']['minute'];
			$now = date('G') . date('i');
			$w['daytime'] = $now > $rise && $now < $set;
		}
						
		$view->setTag('weather', $w);
		
		//$view->setTag('log', $model->tool('log')->sprintLog());
		
		$this->output = $view->getOutput();
		$this->output .= '<!--'.(microtime(true)-$this->start).'-->';
		//echo $this->output;
		echo $this->output;
		session_write_close();
		die();
	}
}
?>
