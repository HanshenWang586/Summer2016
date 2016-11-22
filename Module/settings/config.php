<?
	error_reporting(E_ALL ^ E_NOTICE);
	
	// Path information on the filesystem
	$GLOBALS['PATH'] = array(
		'root' => realpath(dirname(__FILE__) . "/..") . "/"
	);
	
	$_SERVER['DOCUMENT_ROOT'] = $rootPath = $GLOBALS['PATH']['root'];
	
	// URLs to use within the system
	$GLOBALS['URL'] = array(
		'server' => "http://" . $_SERVER['HTTP_HOST'],
		'relativeRoot' => substr($GLOBALS['PATH']['root'], strpos($GLOBALS['PATH']['root'], $_SERVER['DOCUMENT_ROOT']) + strlen($_SERVER['DOCUMENT_ROOT']))
	);
	
	$GLOBALS['URL']['root'] = $rootURL = $GLOBALS['URL']['server'] . $GLOBALS['URL']['relativeRoot'];
	
	function loadFolder($include_dir) {
		$handle = opendir($include_dir);
	
		while (false !== ($file = readdir($handle))) if (is_file($include_dir.$file) and strpos($file, '.php')) $files[] = $include_dir.$file;
		
		closedir($handle);
		asort($files);
		foreach ($files as $file) require($file);
	}

	loadFolder($rootPath.'includes/shared/');
	
	require_once($rootPath . 'includes/system/cms_class.class.php');
	require_once($rootPath . 'includes/system/cms_object.class.php');
	require_once($rootPath . 'includes/system/cms_model.class.php');
	require_once($rootPath . 'includes/system/cms_view.class.php');
	require_once($rootPath . 'includes/system/cms_form_model.class.php');
	require_once($rootPath . 'includes/system/cms_form_view.class.php');

	date_default_timezone_set('Asia/Chongqing');
	putenv("PATH=" .$_SERVER["PATH"]. ":/opt/local/bin:/Users/yereth/lib/ImageMagick-6.5.9/bin");	
	ini_set("session.gc_maxlifetime", "345600");
	ini_set("session.cookie_lifetime", "345600");
	header('Cache-Control: private');
	session_set_cookie_params('345600', '/', SESSION_DOMAIN, false, true);
	set_time_limit(100);
	
	// TODO: Make this variable!!
	setlocale (LC_TIME, 'en_GB.UTF-8');
	
	// Sofware en versie
	$CMS = array(
		'software' => 'new CMS',
		'version' => array(
			'major'	=> '0',
			'minor'	=> '1',
			'type'	=> 'alpha'
		),
		'date' => 'December 8th, 2009'
	);
?>