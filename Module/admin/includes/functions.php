<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/model.class.php');

$options = array(
	'title' => 'Olympus',
	'debug' => false,
	'db_explain' => false,
	'noRedirect' => true
);

// Let's create our model
$model = new MainModel(array(), $options);

define('ADMIN_ROOT_URL',			'/admin/');
define('ADMIN_TITLE',				'Olympus');

if (isset($_SESSION['admin_user']))
	$admin_user = $_SESSION['admin_user'];
else {
	$login_urls = array(ADMIN_ROOT_URL.'modules/login/login_proc.php', ADMIN_ROOT_URL.'modules/login/index.php');

	if (!in_array($_SERVER['PHP_SELF'], $login_urls))
		HTTP::redirect(ADMIN_ROOT_URL.'modules/login/index.php');
}
?>
