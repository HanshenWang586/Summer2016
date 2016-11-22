<?php
class HTTP {
	
	/**
	 * Performs a browser redirect.
	 * @static
	 * @param string $location
	 */
	public static function redirect($location = 'index.php', $type = false) {
		if (headers_sent())
			echo 'HEADERS ALREADY SENT - '.$string;
		else {
			session_write_close();
			if ($type == 301) header('HTTP/1.1 301 Moved Permanently');
			header('Location: '.$location);
		}

		exit;
	}

	/**
	 * gzips content if supported by browser.
	 * @static
	 * @param string $data The data to compress
	 */
	public static function compress($data) {
		return $data;
		$encoding = strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') ? 'gzip' : '';
		
		if ($encoding != '') {
			$gdata = gzencode($data);
			header ('Content-Encoding: '.$encoding);
			header ('Content-Length: '.strlen($gdata));
			return $gdata;
		}
		else {
			header ('Content-Length: '.strlen($data));
			return $data;
		}
	}

	/**
	 * Sends a 404 error to the browser.
	 * @static
	 */
	public static function throw404() {
		static $thrown;
		if (!$thrown) {
			header('HTTP/1.0 404 Not Found');
			require($_SERVER['DOCUMENT_ROOT'].'/scripts/errors/404.php');
			$thrown = true;
		}
		die();
	}
	
	/**
	 * Sends a 410 error to the browser.
	 * @static
	 */
	public static function throw410() {
		static $thrown;
		if (!$thrown) {
			header('HTTP/1.0 410 Gone');
			require($_SERVER['DOCUMENT_ROOT'].'/scripts/errors/410.php');
			$thrown = true;
		}
		die();
	}
	
	public static function disallowed() {
		global $user, $model;
		static $thrown;
		if (!$thrown) {
			if ($user->isLoggedIn()) {
				header('HTTP/1.0 403 Forbidden');
				require($_SERVER['DOCUMENT_ROOT'].'/scripts/errors/403.php');
			} else {
				HTTP::redirect($model->url(array('m' => 'users', 'view' => 'login', 'forward' => $model->url(false, false, true))), 401);
				//header('HTTP/1.0 401 Unauthorized');
				//require($_SERVER['DOCUMENT_ROOT'].'/scripts/errors/401.php');
			}
			$thrown = true;
		}
		die();
	}
}
?>
