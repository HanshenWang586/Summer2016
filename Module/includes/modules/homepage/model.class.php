<?php

class HomepageModel extends CMS_Model {
	// Required for each module
	public $actions = array();
	
	public function init($args) {
		
	}
	
	public function getContent($args = array()) {
		global $user;
		
		$this->css('default');
		$this->tool('html')->addJS($GLOBALS['URL']['root'] . '/js/jquery/jquery.ajaxnav.js');
		$this->js('general');
		
		$view = new View;
		
		$p = new Page;
		$p->setTag('body_id', 'home');
		
		$cache = $this->tool('cache');
		
		$text = sprintf("<section id =\"home\"><h1 class=\"dark\"><a href=\"%s\">%s <span class=\"icon icon-arrow-right-2\"> </span></a></h1>\n", $this->url(array('m' => 'blog')), $this->lang('LATEST_ARTICLES'));
		$cache->delete('/articles/homepage');
		if (!$content = $cache->get('/articles/homepage')) {
			$bloglist = new BlogList;
			
			$articles = $bloglist->getItems(array('limit' => 4));
			$list = array();
			$bi = new BlogItem;
			$content = '<div id="homeCarrousel" class="homeArticles homeBlock clearfix">';
			$first = true;
			foreach($articles as $article) {
				$bi->setData($article);
				$thumb = $bi->getImage(100, 100);
				$large = $bi->getImage(673, 449);
				
				$class = $first ? ' class="active"' : '';
				$first = false;
				
				$content .= sprintf('
					<article itemscope itemtype="http://schema.org/Article">
						<a%s itemprop="url" href="%s">
							<span class="large">
								<img itemprop="image" width="673" height="449" src="%s" alt="image">
								<span class="info">
									<span class="cat">%s</span>
									<h2 itemprop="name">%s</h2>
									%s, %s
								</span>
							</span>
							<span class="thumb">
								<img itemprop="thumbnailUrl" width="100" height="100" src="%s" alt="thumb">
							</span>
						</a>
					</article>',
					$class,
					$bi->getURL(),
					$large,
					$bi->getCategory(),
					$bi->getTitle(),
					$bi->getAuthor()->getDisplayName(),
					$this->tool('datetime')->getDateTag($this->ts, 'published', 'datePublished'),
					$thumb
				);
			}
			$content .= '</div>';
			
			$ads = $this->module('ads');
			if ($ad = $ads->get('topHome')) $content .= sprintf('<div class="homeBlock prom promHome" id="promHomeTop">%s</div>', $ad);
			
			$date = array_get(array_top($articles), 'ts');
			$popular = $bloglist->getItems(array('limit' => 10, 'orderBy' => 'num_comments', 'before' => '"' . $date . '"', 'after' => sprintf('DATE_SUB("%s", INTERVAL 1 MONTH)', $date)));
			
			$content .= sprintf("<section class=\"homeArticles\" id=\"popularArticles\">\n\t<h1 class=\"dark\"><a href=\"%s\">%s <span class=\"icon icon-arrow-right-2\"> </span></a></h1><div class=\"homeBlock articlesList\"><div class=\"row\">\n", $this->url(array('m' => 'blog', 'sort' => 'popular')), $this->lang('POPULAR_ARTICLES', 'BlogModel'));
			
			// Pick two random popular articles
			$random = array();
			$rand1 = mt_rand(0,9);
			$random[] = $popular[$rand1];
			unset($popular[$rand1]);
			sort($popular);
			$rand1 = mt_rand(0,8);
			$random[] = $popular[$rand1];
			
			foreach($random as $article) {
				$bi->setData($article);
				$image = $bi->getImage(395, 263);
				
				$content .= sprintf('
					<article class="span4" itemscope itemtype="http://schema.org/Article">
						<a itemprop="url" href="%s">
							<img itemprop="image" width="395" height="263" src="%s" alt="image">
							<span class="cat">%s</span>
							<span class="info">
								<h2 itemprop="name">%s</h2>
								<span class="extraInfo">%s, %s</span>
							</span>
						</a>
					</article>',
					$bi->getURL(),
					$image,
					$bi->getCategory(),
					$bi->getTitle(),
					$bi->getAuthor()->getDisplayName(),
					$this->tool('datetime')->getDateTag($this->ts, 'published', 'datePublished'),
					$thumb
				);
			}
			$content .= "</div></div></section>";
			$cache->set('/articles/homepage', $content, 300);
		}
		$text .= $content;
		
		$cache->delete('/ftw/home');
		if (!$ftw = $cache->get('/ftw/home')) {
			$result = $this->db()->query('fromtheweb', array('!ts > DATE_SUB(ts, INTERVAL 10 DAY)'), array('orderBy' => 'ts', 'order' => 'DESC', 'limit' => 4));
			$ftwClass = new FromTheWeb;
			$ftw = "<section class=\"ftwList\" id=\"homeFTW\">";
			$ftw .= sprintf("<h1 class=\"dark\"><a href=\"%s\">%s</a> <span class=\"icon icon-arrow-right-2\"> </span></h1>\n<div id=\"ftwWrapper\"><div class=\"itemList row\">\n", $this->url(array('m' => 'fromtheweb')), $this->lang('FROM_THE_WEB_TITLE'));
			$ftw .= $ftwClass->sprintItems($result, 'span4');
			$ftw .= "</div></div></section>\n";
			$cache->set('/ftw/home', $ftw, 600);
		}
		
		$text .= $ftw;
		
		if ($ad = $ads->get('bottomHome')) $text .= sprintf('<div class="homeBlock prom promHome" id="promHomeTop">%s</div>', $ad);
		
		$listings = "<section id=\"homeListings\">";
		$listings .= sprintf("<h1 class=\"dark\">%s</h1>\n", $this->lang('LISTINGS_HOME_TITLE'));
		$listingsClass = new Listings;
		$cats = $listingsClass->getCategories(false, 0, true);
		//die($this->db()->getQuery());
		$listings .= sprintf("<div class=\"ListingCategories\">\n<div class=\"caption\">%s</div>\n<ul class=\"categoryList\">", $this->lang('SELECT_CATEGORY', 'ListingsModel'));
		$category = request($this->args['listings_category']);
		ifNot($category, 'all');
		foreach($cats as $cat) {
			$active = ($category == $cat['category_code']) ? ' class="active"' : '';
			$lang = strtolower($this->model->lang);
			if ($lang == 'cn') $lang = 'zh';
			$listings .= sprintf(
				'<li><a%s rel="nofollow" href="%s">%s</a></li>', 
				$active, 
				$this->url(array('listings_category' => $cat['category_code']), false, true),
				request($cat['category_' . $lang])
			);
		}
		$listings .= "</ul></div>\n";
		$listingsList = new ListingsList;
		$listings .= '<div id="listingsBoxWrapper">' . $listingsList->getHomepageListingsBox($category) . '</div>';
		$listings .= "</section>\n";
		
		$text .= $listings;
		
		$text .= "</section>\n";
		$p->setTag('main', $text);
		$p->output();
	}
	
	public function _getListingsBox() {
		global $model;
		$category = request($model->args['listings_category']);
		ifNot($category, 'all');
		$listingsList = new ListingsList;
		JSONOut(array('content' => $listingsList->getHomepageListingsBox($category)));
	}
}