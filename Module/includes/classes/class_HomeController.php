<?php
class HomeController {

	public function index() {
		global $user, $model;
		
		$view = new View;
		
		$p = new Page;
		$p->setTag('body_id', 'home');
		
		$cache = $model->tool('cache');
		
		$text = sprintf("<section id =\"home\"><h1 class=\"dark\" id=\"homeTopTitle\">%s</h1>\n", $model->lang('ARTICLES'));
		//$cache->delete('/articles/home');
		if (!$content = $cache->get('/articles/home')) {
			$bloglist = new BlogList;
			
			$articles = $bloglist->getItems(array('limit' => 2));
			$list = array();
			$bi = new BlogItem;
			$content = '<div id="homeTopArticles">';
			foreach($articles as $article) {
				$bi->setData($article);
				$content .= $bi->displayHome();
			}
			$content .= '</div>';
			
			$latest = $bloglist->getItems(array('offset' => 2, 'limit' => 4));
			// Get the date of the last item
			$date = array_get(array_top($latest), 'ts');
			$popular = $bloglist->getItems(array('limit' => 4, 'orderBy' => 'num_comments', 'before' => '"' . $date . '"', 'after' => sprintf('DATE_SUB("%s", INTERVAL 1 MONTH)', $date)));
			
			$content .= "<div class=\"row\" id=\"homeArticlesList\">\n";
			$content .= sprintf("<section id=\"latestArticles\" class=\"span4\">\n\t<h1 class=\"bright\">%s</h1>\n", $model->lang('LATEST_ARTICLES', 'blog'));
			$content .= $bloglist->displayBrief($latest, array('id' => 'latestArticles'));
			$content .= sprintf("</section><section id=\"popularArticles\" class=\"span4\">\n\t<h1 class=\"bright\">%s</h1>\n", $model->lang('POPULAR_ARTICLES', 'blog'));
			$content .= $bloglist->displayBrief($popular, array('id' => 'popularArticles'));
			$content .= "</section>";
			$content .= "</div>";
			$content .= sprintf('<a class="seeAll icon-right icon-link" href="%s">%s <span class="icon icon-arrow-right-2"> </span></a>', $view->url(array('m' => 'blog')), $model->lang('SEE_ALL_ARTICLES'));
			$cache->set('/articles/home', $content, 300);
		}
		$text .= $content;
		$ads = $model->module('ads');
		if ($ad = $ads->get('topHome')) $text .= sprintf('<div class="promHome" id="promHomeTop">%s</div>', $ad);
		
		if (!$ftw = $cache->get('/ftw/home')) {
			$result = $model->db()->query('fromtheweb', array('!ts > DATE_SUB(ts, INTERVAL 10 DAY)'), array('orderBy' => 'ts', 'order' => 'DESC', 'limit' => 6));
			$ftwClass = new FromTheWeb;
			$ftw = "<section class=\"ftwList\" id=\"homeFTW\">";
			$ftw .= sprintf("<h1 class=\"bright\">%s</h1>\n<div class=\"itemList row\">\n", $model->lang('FROM_THE_WEB_TITLE'));
			$ftw .= $ftwClass->sprintItems($result, 'span4');
			$ftw .= "</div></section>\n";
			$ftw .= sprintf('<a class="seeAll icon-right icon-link" href="%s">%s <span class="icon icon-arrow-right-2"> </span></a>', $view->url(array('m' => 'fromtheweb')), $model->lang('SEE_ALL_FTW'));
			$cache->set('/ftw/home', $ftw, 600);
		}
		
		$text .= $ftw;
		
		$text .= "</section>\n";
		$p->setTag('main', $text);
		$p->output();
	}
}