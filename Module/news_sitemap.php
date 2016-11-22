<?php
require_once('model.class.php');

$options = array(
	'title' => 'Olympus',
	'debug' => false,
	'db_explain' => false,
	'noRedirect' => true
);

// Let's create our model
$model = new MainModel(array(), $options);

header('Content-type: text/xml; charset=utf-8');

$content .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"
xmlns:news=\"http://www.google.com/schemas/sitemap-news/0.9\">";

$db = new DatabaseQuery;
$rs = $db->execute("SELECT blog_id
					FROM blog_content
					WHERE ts > DATE_SUB(NOW(), INTERVAL 4 DAY)
					AND ts < NOW()
					ORDER BY ts DESC
					LIMIT 10");

	while ($row = $rs->getRow()) {
		$bi = new BlogItem($row['blog_id']);
		$content .= $bi->getGoogleNewsSiteMapEntry();
	}

$content .= '</urlset>';
echo $content;
?>