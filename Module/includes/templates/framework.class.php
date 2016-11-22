<?php

class FrameworkTemplate extends CMS_Class {
	public function init($args) {
		
	}
	
	/**
	 * Returns a generic header for nearly all pages. Includes a title bar and a possible description.
	 * 
	 * @param string $title The title for the page
	 * @param string $description The description for the page
	 * 
	 * @return string 
	 */
	public function getHeader($title, $description = false) {
		$descr = $description ? sprintf('<p class="subTitle">%s</p>', $description) : '';
		return sprintf("\t\t<header id=\"contentHeader\"><h1>%s</h1>%s</header>\n%s\n",
			$title,
			$descr,
			$this->model->module('log')->getUserLog()
		);
	}
	
	public function getColumnLeft($args = array()) {
		$content = is_string($args) ? $args : (isset($args['content']) ? $args['content'] : '');
		$content = sprintf("\t\t<div id=\"leftColumn\">%s</div>\n", $content);
		return $content;
	}
	
	public function getColumnRight($args = array()) {
		$content = ifElse($args['content'], '');
		$url = request($args['url']) ? (is_string($args['url']) ? $args['url'] : $this->url($args['url'])) : $this->url(array('m' => $this->model->state('module'), 'view' => 'post')); 
		if (isset($args['postAd'])) {
			$content = sprintf("\n\t\t\t<a href=\"%s\" class=\"postAd\"><span class=\"subTitle\">%s</span><span class=\"title\">%s</span></a>\n",
				$url,
				request($args['postAd']['subTitle']),
				request($args['postAd']['title'])
			);
		}
		$column = sprintf("\t\t<div id=\"rightColumn\">%s</div>\n", $content);
		return $column;
	}
	
	public function getContent($content, $options = array()) {
		ob_start();
		$user = $this->tool('security')->getActiveUser();
?>
	<header id="pageHeader">
<? if ($user) { ?>		
		<span class="welcome"><?=$this->lang('WELCOME_USER') . ' ' . $user->get('email');?></span>
<? } ?>
		<ul class="right" id="lang">
<? foreach($this->model->allowedLanguages as $key) {
	$class = array($key);
	if ($key == $this->model->lang) $class[] = 'active';		
	printf("\t\t\t<li class=\"%s\"><a href=\"%s\"><img src=\"lang/%s/assets/flag.gif\"><span class=\"caption\">%s</span></a></li>\n",
		implode(' ', $class),
		$this->url(array('LANG' => $key), false, true),
		strtolower($key),
		$this->lang($key)
	);
} ?>
		</ul>
		<div class="right" id="login">
<? if ($this->tool('security')->loggedIn()) { ?>
			<a id="frameworkLogoff" href="<?=$this->url(array('m' => 'login', 'action' => 'logoff'));?>"><?=$this->lang('LOGOFF')?></a>
<? } else { ?>
			<a id="frameworkLogin" href="<?=$this->url(array('m' => 'login'));?>"><?=$this->lang('SIGN_IN_SIGN_UP')?></a>
<? } ?>
		</div>
		<div class="right" id="shortLinks">
			<a id="myChinaBox" href="<?=$this->url(array('m' => 'mychinabox'));?>"><img src="assets/icons/mychinabox.gif" alt=""><?=$this->lang('MY_CHINA_BOX');?></a>
			<a id="adsLink" href="<?=$this->url(array('m' => 'banners'));?>"><img src="assets/icons/ads.gif" alt=""><?=$this->lang('ADS');?></a>
			<a id="favourites" href="<?=$this->url(array('m' => 'mychinabox', 'view' => 'favourites'));?>"><img src="assets/icons/favourites.gif" alt=""><?=$this->lang('MY_FAVOURITES');?></a>
		</div>
	</header>
	<div id="siteFrame">
<? if (!$this->tool('security')->loggedIn()) { ?>
		<div id="frameworkLoginFrame">
<?=$this->model->module('login')->view('simplelogin');?>		
		</div>
<? } ?>
		<div id="siteWidth">
			<section id="top">
				<a id="logo" href="<?=$this->url();?>"><span class="caption"><?=$this->lang('SITE_TITLE', 'site');?></span><img src="assets/logo.png" alt="<?=$this->lang('SITE_TITLE', 'site', false, true);?>"></a>
				<div id="topBanner" class="corners"><a href="#"><img class="corners-s" src="assets/banners/top/sample.jpg" alt="test"></a></div>
			</section>
			<section id="main">
				<?=$content;?>
			</section>
		</div>
	</div>
<?	
		$content = ob_get_clean();
		return $content;
	}	
}

?>