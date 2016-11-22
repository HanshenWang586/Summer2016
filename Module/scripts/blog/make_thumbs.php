<?php
header("Content-type: text/plain; charset=utf-8");
include($_SERVER['DOCUMENT_ROOT']."/includes/functions.php");
set_time_limit(0);

$db = new DatabaseQuery;
$rs = $db->execute("SELECT *
					FROM blog_images");
				
while ($row = $rs->getRow()) {
	$bi = new BlogImage($row['image_id']);
	$bi->makeThumbnail();
}

echo 'done';
?>
