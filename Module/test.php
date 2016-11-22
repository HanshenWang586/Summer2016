<?php
$rootPath = realpath(dirname(__FILE__));
// Relative path from site root. Site root is for example http://www.example.com
// Take the address of this file and remove the document root path from the string. This leaves the relative path of Ewyse.
$rootRelURL = substr($rootPath, strlen($_SERVER['DOCUMENT_ROOT']));

$_SERVER['DOCUMENT_ROOT'] = $rootPath;

if (!isset($rootURL)) {
	$serverURL = "http://" . $_SERVER['SERVER_NAME'];
	$rootURL = str_replace('\\', '/', $serverURL . $rootRelURL . '/');
}
echo "<pre>";
print_r(array(
	'rootPath' => $rootPath,
	'rootRelURL' => $rootRelURL,
	'serverURL' => $serverURL,
	'rootURL' => $rootURL
));
echo "</pre>";

?>
