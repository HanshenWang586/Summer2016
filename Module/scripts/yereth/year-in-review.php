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

$db = $GLOBALS['site']->db();
$result = $db->query('blog_content', "!ts > '2014-01-01 00:00:00' AND ts < '2015-01-01 00:00:00'", array('getFields' => 'blog_id, MONTHNAME(ts) AS month', 'arrayGroupBy' => 'month', 'transpose' => 'blog_id', 'orderBy' => 'ts', 'order' => 'ASC'));

$bi = new BlogItem();

error_reporting(E_NONE);

?>
<!DOCTYPE html>
<html>
	<head>
        <meta charset="utf-8">
	</head>
	<body>
<?

foreach($result as $month => $posts) {
	printf("<br>&lt;h2&gt;%s&lt;/h2&gt;<br><br>", $month);
	foreach($posts as $id) {
		$bi->load($id);
		printf("#%s#%s#<br>", $bi->getTitle(), $bi->getAbsoluteURL());
	}
}
?>
	</body>
</html>