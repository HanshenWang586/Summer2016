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

$awards = $db->query('awards', false, array('orderBy' => 'date_start', 'order' => 'DESC'));
$results = array();

$listing = new ListingsItem();

foreach($awards as $award) {
	echo sprintf("<h1>%s</h1>", $award['name_en']);
	$cats = $db->query('awards_categories', array('awards_id' => $award['id']));
	$voters = $db->query('awards_votes', array('category_id' => array_transpose($cats, 'id')), array('getFields' => 'COUNT(DISTINCT user_id) AS voters'));
	$votes = $db->count('awards_votes', array('category_id' => array_transpose($cats, 'id')));
	$totalVoters = request($voters[0]['voters']);
	echo "<p>";
	printf("<strong>%d</strong> categories<br>\n", count($cats));
	printf("<strong>%d</strong> total voters<br>\n", $totalVoters);
	printf("<strong>%d</strong> total votes<br>\n", $votes);
	printf("<strong>%d</strong> average voted categories per voter<br>\n", round($votes / $totalVoters));
	echo "</p>";
	foreach($cats as $cat) {
		echo sprintf("<h2>%s</h2>", $cat['name_en']);
		$voters = $db->query('awards_votes', array('category_id' => $cat['id']), array('getFields' => 'COUNT(DISTINCT user_id) AS voters'));
		$voters = request($voters[0]['voters']);
		printf("<p><strong>%d voted in this category</strong> <em>(%d%%)</em></p>\n", $voters, round($voters / $totalVoters * 100));
		$nominees = $db->query('awards_nominees', false, array(
			'join' => array(
				array('table' => 'awards_votes', 'as' => 'av', 'on' => array('id', 'nominee_id'), 'where' => array('category_id' => $cat['id']))
			), 'groupBy' => 'nominee_id',
			'getFields' => "DISTINCT awards_nominees.*, count(*) AS votes",
			'orderBy' => 'votes',
			'order' => 'DESC'
		));
		echo "<table>";
		foreach($nominees as $nominee) {
			$listing->load($nominee['listings_id']);
			echo "<tr>";
			printf("<td>%s</td>", $listing->getPublicName());
			printf('<td>%d votes</td>', $nominee['votes']);
			echo "</tr>";
		}
		echo "</table>";
		//var_dump($db->getQuery(), $db->getError(), $nominees);
		//die();
	}
}
?>