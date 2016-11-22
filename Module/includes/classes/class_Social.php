<?php

class Social {
	public function getLinkList() {
		global $model;
		return sprintf('
			<ul class="social">
				<li><a class="tooltip" href="https://www.facebook.com/GoKunming"><span class="icon icon-facebook"> </span><span class="tooltip-text">%s</span></a></li>
				<li><a class="tooltip" href="http://www.linkedin.com/company/gokunming"><span class="icon icon-linkedin"> </span><span class="tooltip-text">%s</span></a></li>
				<li><a class="tooltip" href="https://twitter.com/gokunming"><span class="icon icon-twitter"> </span><span class="tooltip-text">%s</span></a></li>
				<li><a class="tooltip" href="https://plus.google.com/+Gokunming%%E6%%BB%%87"><span class="icon icon-googleplus"> </span><span class="tooltip-text">%s</span></a></li>
				<li><a class="tooltip" href="http://weibo.com/gokunming"><span class="icon icon-weibo"> </span><span class="tooltip-text">%s</span></a></li>
			</ul>',
				$model->lang('FACEBOOK'),
				$model->lang('LINKED_IN'),
				$model->lang('TWITTER'),
				$model->lang('GOOGLE_PLUS'),
				$model->lang('SINA_WEIBO')
			);
	}
	
	public function getSharingList($title, $img = false, $summary = ' ') {
		$view = new View('general/jiathis_socialsharing.html');

		$view->setTag('title', $title);
		$view->setTag('summary', $summary);
		$view->setTag('url', $GLOBALS['model']->url(false, false, true));
		if ($img) $view->setTag('img', $img);
		
		return $view->getOutput();
	}
}

?>