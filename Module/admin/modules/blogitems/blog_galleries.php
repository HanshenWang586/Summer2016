<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$pap = new AdminPage($admin_user);
$pap->setModuleKey('blog');

$db = new DatabaseQuery;
$rs = $db->execute('SELECT * FROM blog_galleries ORDER BY ts DESC');

$body .= '<table class="main">';

while ($row = $rs->getRow()) {
	$body .= "<tr>
	<td>#gallery#{$row['blog_gallery_id']}#</td>
	<td>{$row['ts']}</td>
	<td><a href=\"form_blog_gallery.php?blog_gallery_id={$row['blog_gallery_id']}\">Images</a></td>
	</tr>";
}

$body .= '</table>';

$pap->setTag('main', $body);
$pap->output();
?>