<?php
if (strpos($_SERVER['SERVER_NAME'], 'localhost') or strpos($_SERVER['SERVER_ADDR'], '127.') > -1) {
	// local
	define('DB_HOST2', 							'localhost');
	define('DB_HOST', 							'p:localhost');
	define('DB_DB', 							'olympus2');
	define('DB_USER', 							'root');
	define('DB_PASS', 							'922ia9LX');

	define('SESSION_DOMAIN',					'');

	define('TMP', 								'/tmp/');
	define('ERROR_REPORTING',					E_ALL ^ E_NOTICE);
	define('SEND_SUBSCRIBE_EMAILS',				false);
	define('LOCAL',								true);
}
else {
	// remote
	define('DB_HOST2', 							'localhost');
	define('DB_HOST', 							'p:localhost');
	define('DB_DB', 							'gokunming');
	define('DB_USER', 							'gokunming');
	define('DB_PASS', 							'gokunming223@@');

	define('SESSION_DOMAIN',					'.'.str_replace(array('m.', 'www.'), '', $_SERVER['HTTP_HOST']));

	define('TMP', 								'/tmp/');
	define('ERROR_REPORTING',					0);
	define('SEND_SUBSCRIBE_EMAILS',				true);
	define('LOCAL',								false);
}

define('SMTP_HOST', 						'localhost');
define('SMTP_PORT', 						25);
define('SMTP_USER', 						'send@intersolua.com');
define('SMTP_PASS', 						'xtp72fgp1');

define('GALLERY_PHOTO_STORE_FILEPATH',			$_SERVER['DOCUMENT_ROOT'].'/images/gallery/');
define('GALLERY_PHOTO_STORE_URL',				'/images/gallery/');

define('IMAGE_STORE_FILEPATH',					$_SERVER['DOCUMENT_ROOT'].'/images/store/');
define('IMAGE_STORE_URL',						'/images/store/');

define('AD_STORE_FILEPATH',						$_SERVER['DOCUMENT_ROOT'].'/images/prom/');
define('AD_STORE_URL',							'/images/prom/');

define('BLOG_PHOTO_STORE_FILEPATH',				$_SERVER['DOCUMENT_ROOT'].'/images/blog/');
define('BLOG_PHOTO_STORE_URL',					'/images/blog/');

define('EVENTS_POSTER_STORE_FILEPATH',			$_SERVER['DOCUMENT_ROOT'].'/images/calendar/posters/');
define('EVENTS_POSTER_STORE_URL',				'/images/calendar/posters/');

define('LISTINGS_LOGO_STORE_FILEPATH',			$_SERVER['DOCUMENT_ROOT'].'/images/listings/logos/');
define('LISTINGS_LOGO_STORE_URL',				'/images/listings/logos/');

define('CONVERSATIONS_ATTACHMENTS_STORE_FILEPATH',			$_SERVER['DOCUMENT_ROOT'].'/images/conversations/attachments/');
define('CONVERSATIONS_ATTACHMENTS_STORE_URL',				'/images/conversations/attachments/');

define('BLOG_GALLERY_STORE_FILEPATH',			$_SERVER['DOCUMENT_ROOT'].'/images/blog/gallery/');
define('BLOG_GALLERY_STORE_URL',				'/images/blog/gallery/');

define('TEAM_PHOTO_STORE_FILEPATH',				$_SERVER['DOCUMENT_ROOT'].'/images/team/');
define('TEAM_PHOTO_STORE_URL',					'/images/team/');
?>