<?php
class SearchController {

	public function index() {
		global $user, $model;

		if (func_num_args() > 0)
			$ss = urldecode(func_get_arg(0));

		if (func_num_args() > 1) {
			$page = func_get_arg(0);
			$ss = urldecode(func_get_arg(1));
		}

		$p = new Page;
		$p->setTag('page_title', 'Search ' . $model->lang('SITE_NAME'));

		$body = '<h1>Search . ' . $model->lang('SITE_NAME') .  '</h1>';
		$pager = new Pager;
		$pager->setLimit(20);
		$pager->setbaseURL('/en/search/index/'.urlencode($ss));
		$pager->setCurrentPage($page);

		$sf = new SearchForm;
		$sf->setSearchString($ss);
		$body .= $sf->displayForm();
		$body .= $sf->displayResults($pager);
		$body .= $pager->getNav();
		$p->setTag('main', $body);
		$p->output();
	}

	public function redirect() {
		$ss = str_replace('/', '', $_GET['ss']);
		HTTP::redirect('/en/search/results/'.urlencode($ss));
	}

	public function results() {
		global $user;
		global $model;

		if (func_num_args() > 0)
			$ss = urldecode(func_get_arg(0));

		$p = new Page();
		$p->setTag('scripts', '<script src="/js/jquery.js" type="text/javascript"></script>
		<script src="/js/default.js" type="text/javascript"></script>');
		$p->setTag('page_title', 'Search '.$model->lang('SITE_NAME'));

		$sf = new SearchForm;
		$sf->setSearchString($ss);
		$sf->logSearch();
		$body .= $sf->displayForm();

		$body .= '<div id="search_results">
		<h1>Search Results</h1>';

		$types = array('articles', 'forums', 'listings', 'classifieds', 'users');

		foreach ($types as $type) {
			$body .= '<div id="'.$type.'_results"></div>
			<script>runSearch(\''.$type.'\', \''.addslashes($ss).'\');</script>';
		}

		$body .= '</div>';

		$p->setTag('main', $body);
		$p->output();
	}

	public function type() {
		$type = func_get_arg(0);
		$ss = urldecode(func_get_arg(1));

		if (func_num_args() > 2)
			$page = func_get_arg(2);
		else
			$page = 1;

		switch ($type) {
			case 'listings':
				$ls = new ListingsSearch;
				$ls->setSearchString($ss);
				$ls->setShowTitle(true);
				echo $ls->getResults();
			break;

			case 'classifieds':
				$classifieds_search = new ClassifiedsSearch;
				$classifieds_search->setSearchString($ss);
				echo $classifieds_search->getResults($page);
			break;

			case 'forums':
				$forums_search = new ForumsSearch;
				$forums_search->setSearchString($ss);
				echo $forums_search->getResults($page);
			break;

			case 'articles':
				$articles_search = new BlogSearch;
				$articles_search->setSearchString($ss);
				echo $articles_search->getResults($page);
			break;

			case 'users':
				$user_search = new UserSearch;
				$user_search->setSearchString($ss);
				echo $user_search->getResults($page);
			break;
		}
	}
}
?>