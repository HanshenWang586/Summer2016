<?php
class RssController {

	public function index() {
		$view = new View;
		header('Content-type: text/xml; charset=utf-8');
		echo '<?xml version="1.0" encoding="utf-8"?>';
		if (!$content = $view->setPath('blog/rss/index.html', false, 600, 'articles/all')) {
			global $model;
			$bl = new BlogList;
			$items = $bl->getItems(array('limit' => 10));
			$bi = new BlogItem;
			foreach ($items as $item) {
				$bi->setData($item);
				$rss .= $bi->getRSS();
			}
	
			$view->setTag('title', $model->lang('SITE_NAME') . ' ' . $model->lang('ARTICLES'));
			$view->setTag('link', $model->url());
			$view->setTag('atom_link', $model->url(array('m' => 'rss')));
			$view->setTag('description', $model->lang('SITE_DESCR'));
			$view->setTag('items', $rss);
			$content = $view->getOutput();
		}
		
		echo $content;
	}
	
	private function log() {
		$db = new DatabaseQuery;
		$db->execute("	INSERT INTO log_rss (ts, ip, ua)
						VALUES (NOW(),
								'{$_SERVER['REMOTE_ADDR']}',
								'".$db->clean($_SERVER['HTTP_USER_AGENT'])."')");
	}
}
?>