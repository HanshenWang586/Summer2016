<?php
// This shit is garbage
class VersionController {

	public function index() {
		$_SESSION['version'] = 'regular';
		$uri = isset($_SERVER['HTTP_REFERER']) ? str_replace('://m.gokunming.com', '://www.gokunming.com', $_SERVER['HTTP_REFERER']) : 'http://www.gokunming.com/en/';
		HTTP::redirect($uri);
	}
	
	public function mobile() {
		$_SESSION['version'] = 'mobile';
		$uri = isset($_SERVER['HTTP_REFERER']) ? str_replace('://www.gokunming.com', '://m.gokunming.com', $_SERVER['HTTP_REFERER']) : 'http://m.gokunming.com/en/';
		HTTP::redirect($uri);
	}
}
?>