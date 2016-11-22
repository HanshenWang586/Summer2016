<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/model.class.php');

$options = array(
	'title' => 'Script',
	'debug' => false,
	'db_explain' => false,
	'noRedirect' => true
);

$model = new MainModel(array(), $options);

$db = $model->db();

$result = $db->run_select("
	SELECT i.*, c.title, c.ts AS publish_date
	FROM blog_images i
	LEFT JOIN blog_content c ON (c.blog_id = i.blog_id)
	WHERE c.ts >= '2014-01-01' AND c.ts < '2015-01-01'
	ORDER BY c.ts ASC
", false, array('arrayGroupBy' => 'title'));

//print_rf($result);

$bi = new BlogImage();

error_reporting(E_NONE);

?>
<!DOCTYPE html>
<html>
	<head>
        <meta charset="utf-8">
	</head>
	<body>
<?

foreach($result as $title => $images) {
	printf('<h1>%s</h1><h2>Published: %s</h2>', $title, $images[0]['publish_date']);
	foreach($images as $image) {
		$bi->setData($image);
		printf('<h3>Image code: %d</h3>', $bi->image_id);
		echo $bi->getEmbeddable() . '<br>';
	}
}
?>
	</body>
</html>