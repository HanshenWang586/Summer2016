<?php

class DefaultView extends CMS_View {
	public static $browserAccess = false;
	
	public function init($args) {
		$this->css('default');
		$this->js('default');
		$this->js('jquery.ajaxnav', 'js/jquery/');
	}
	
	public function getContent($options = array()) {
		$catModel = $this->model->module('categories');
		$categories = $catModel->getCategories($this->name, array('icons' => true));
		$category = ($cat_id = $this->arg('category')) ? $catModel->getCategory($cat_id) : false;
		// If the user tricks us into showing a sub-cat, reject
		if ($category['category_id']) $category = false;
		if ($category) {
			$title = $category['name'];
			$searchTipKey = str_replace(' ', '-', strtoupper($category['code'])) . '_SEARCH_TIP';
		} else {
			$title = $this->lang(strtoupper($this->name) . '_TITLE');
			$searchTipKey = 'HOMEPAGE_SEARCH_TIP'; 
		}
		ob_start();
?>
		<header id="contentHeader">
			<h1><?=$title;?></h1>
		</header>
		<div id="controls">
			<a class="button" href="<?=$this->url(array('m' => $this->name, 'view' => 'post'));?>"><span class="icon icon-edit"></span><?=$this->lang('LINK_NEW');?></a>
			<a class="icon-link" href="/en/classifieds/rss/"><span class="icon icon-feed"></span><?=$this->lang('LINK_RSS_FEED');?></a>
		</div>
<?
		$content .= ob_get_clean();
		$content .= "\t\t\t<ul id=\"categories\" class=\"row\">\n";
		ob_start();
		if ($categories) foreach($categories['NULL'] as $topCats) {
			$icon = $this->m->getCategoryIcon($topCats['icon']);
	?>
			<li class="span4">
				<span class="img"><img src="<?=$icon?>" alt="<?=htmlspecialchars($topCats['name'])?>"></span>
			<h2><?=$topCats['name'];?></h2>
		<? if ($cats = request($categories[(string)$topCats['_id']])) {
			$totals = $this->db()->query('classifieds_data', array('status' => 1), array('getFields' => 'count(*) AS count, folder_id', 'groupBy' => 'folder_id', 'transpose' => array('folder_id', 'count')));
		?>
			<ul class="subCategories">
			<? foreach($cats as $cat) { ?>
				<li><h3><a href="<?=$this->url(array('m' => $this->name, 'view' => 'category', 'id' => $cat['code']))?>"><?=$cat['name']?></a> (<?=(int) request($totals[$cat['id']]);?>)</h3></li>
			<? } ?>
			</ul></li>
		<? }
		}
		$content .= ob_get_clean();
		$content .= "\t\t\t</ul>\n";
		return $content;
	}
}
?>