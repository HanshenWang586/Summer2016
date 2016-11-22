<?php
//error_reporting(E_ALL);

// Path information on the filesystem
$GLOBALS['PATH'] = array(
	'root' => realpath(dirname(__FILE__) . "/..") . "/"
);

// URLs to use within the system
$GLOBALS['URL'] = array(
	'server' => "http://" . $_SERVER['HTTP_HOST'],
	'relativeRoot' => substr($GLOBALS['PATH']['root'], strpos($GLOBALS['PATH']['root'], $_SERVER['DOCUMENT_ROOT']) + strlen($_SERVER['DOCUMENT_ROOT'])) 
);

$GLOBALS['URL']['root'] = $GLOBALS['URL']['server'] . $GLOBALS['URL']['relativeRoot'];

$_SERVER['DOCUMENT_ROOT'] = $rootPath = $GLOBALS['PATH']['root'];

// URLs to use within the system
$GLOBALS['URL'] = array(
	'server' => "http://" . $_SERVER['HTTP_HOST'],
	'relativeRoot' => substr($GLOBALS['PATH']['root'], strpos($GLOBALS['PATH']['root'], $_SERVER['DOCUMENT_ROOT']) + strlen($_SERVER['DOCUMENT_ROOT'])) 
);

$GLOBALS['URL']['root'] = $GLOBALS['URL']['server'] . $GLOBALS['URL']['relativeRoot'];

$rootURL = $GLOBALS['URL']['root'];

/***************************************************************/
function loadFolder($include_dir) {
	$handle = opendir($include_dir);

	while (false !== ($file = readdir($handle))) {
		if (is_file($include_dir.$file))
			$files[] = $include_dir.$file;
	}

	closedir($handle);
	asort($files);

	foreach ($files as $file) {
		if (strpos($file, '.php')) require($file);
	}
}

loadFolder($rootPath. 'includes/system/');
loadFolder($rootPath.'includes/shared/');

date_default_timezone_set('Asia/Chongqing');

ini_set("session.gc_maxlifetime", "345600");
ini_set("session.cookie_lifetime", "345600");
header('Cache-Control: private');
session_set_cookie_params('345600', '/', SESSION_DOMAIN, false, true);

// TODO: Make this variable!!
setlocale (LC_TIME, 'en_US.UTF-8');

error_reporting(E_ALL ^ E_NOTICE);
$mysqli = DatabaseConnection::create();
set_time_limit(100);

$_SESSION['user'] = !isset($_SESSION['user']) ? new User : $_SESSION['user'];
$_SESSION['user']->setIP($_SERVER['REMOTE_ADDR']);
$_SESSION['user']->setSessionId(session_id());
$user = $_SESSION['user'];

$_SESSION['site_id'] = $site->getSiteID(); // this is needed by class_Session

// Reload user data in case anything changed in the meantime
$user->reloadData();
/********************* end set up user *********************/
?>