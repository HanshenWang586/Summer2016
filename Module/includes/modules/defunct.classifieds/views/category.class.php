<?php

class CategoryView extends CMS_View {
	public function init($args = array()) {
		//$this->css('search');
	}
		
	public function getContent($options = array()) {
		$content = '';
		
		$params = array('page' => (int) ifElse($this->arg('page'), 1));
		
		$results = $this->m->getItems($this->arg(array('q')), $params);
		$tag = $this->tool('tag');
		
		ob_start();
?>
		<header id="contentHeader">
			<h1><?=$this->lang(strtoupper($this->name) . '_TITLE');?></h1>
		</header>
		<div id="controls">
			<a class="button" href="<?=$this->url(array('m' => $this->name, 'view' => 'post', 'id' => $this->model->args['id']));?>"><span class="icon icon-edit"></span><?=$this->lang('LINK_NEW');?></a>
			<a class="icon-link" href="/en/classifieds/rss/"><span class="icon icon-feed"></span><?=$this->lang('LINK_RSS_FEED');?></a>
		</div>
		<form id="searchClassifiedsForm" class="searchForm" method="get" action="<?=$this->url(false, false, true);?>">
			<div>
				<?=$tag->input(array('attr' => array('type' => 'search', 'class' => 'text', 'value' => $this->arg('q'), 'placeholder' => $this->lang('SEARCH_PLACEHOLDER', false, false, true), 'id' => 'inputQ', 'name' => 'q')));?>
				<input class="searchSubmit" type="submit" value="<?=$this->lang('SEARCH_BUTTON_UPDATE', false, false, true);?>">
			</div>
		</form>
<? if ($results) { ?>
				<div class="itemList classifiedsList">
<? 
	foreach($results as $item) { 
		$item = $this->m->processItem($item);
		//$logo = request($item['logo']) ? $this->model->module('image')->getImageLink($item['logo'], 'company', $item['company_id'], 167, 66) : false;
?>
					<article>
						<a class="item" href="<?=$this->url(array('m' => $this->name, 'view' => 'item', 'id' => $item['id'], 'name' => $item['title']))?>">
							<h2><?=$item['title']?></h2>
							<p><?=$item['short']?></p>
						</a>
					</article>
<? } ?>
				</div>
<? } ?>
<?		
		$body = ob_get_clean();
		return $body;
	}
}

?>