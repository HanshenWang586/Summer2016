<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('forum');

// set up pagination
$pager = new AdminPager;
$pager->setLimit(20);
$pager->setEnvironment($_SERVER['PHP_SELF'], $_SERVER['QUERY_STRING']);

$body = "<form id=\"filter_threads\">
Search <input name=\"ss\" onkeyup=\"forumFilterThreads(this.value)\">
</form>

<div id=\"results\">";

$ftl = new ForumThreadList;
$data = $ftl->displayAdmin($pager);

$body .= $pager->getNav();
$body .= $data;
$body .= $pager->getNav();

$body .= '</div>';

$pap->setTag('main', $body);
$pap->output();
?>