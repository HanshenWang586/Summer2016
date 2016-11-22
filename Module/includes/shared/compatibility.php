<?
/*
 *	This file is meant for ugly systems such as Windows with an IIS server.
 *	We hereby remove incompatibility issues with decent systems such as
 *	Apache on a linux server.. hopefully :)
 */

// IIS can't stop us! REQUEST_URI will prevail!
if (!isset($_SERVER['REQUEST_URI'])) {
	$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING'];
}

// Lets get rid of the magic quotes stuff
if(get_magic_quotes_gpc()) {
	$_GET = stripslashes_r($_GET);
	$_POST = stripslashes_r($_POST);
	$_REQUEST = stripslashes_r($_REQUEST);
	$_COOKIE = stripslashes_r($_COOKIE);
}

// Get the mime type
if (!function_exists('mime_content_type')) {
	if (function_exists('finfo_open')) {
		function mime_content_type($filename) {
			$finfo    = finfo_open(FILEINFO_MIME);
			$mimetype = finfo_file($finfo, $filename);
			finfo_close($finfo);
			return $mimetype;
		}
	} else {
		function mime_content_type($filename) {
			$result = exec("file -i -b '{$filename}'", $output);
			return request($output[0]);
		}
	}
}
?>