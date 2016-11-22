<?php

require_once('model.class.php');

$t = microtime_float();

$model = new MainModel(array(), array('noRedirect' => true));

// Config  
$mongo = $model->mongo();
$mysql = $model->db();

error_reporting(E_ALL);

// CONVERT CLASSIFIEDS
$cursor = $mongo->categories->find(array('module' => 'classifieds'));
$cats = array();
foreach ($cursor as $item) {
	$cats[$item['id']] = $item['code'];
}
function classifiedsCallback($item) {
	$item['id'] = (int) $item['classified_id'];
	$item['user'] = (int) $item['user_id'];
	$item['approved'] = $item['status'] == 1;
	$item['date'] = new MongoDate(strtotime($item['ts']));
	$item['expires'] = new MongoDate(strtotime($item['ts_end']));
	// No more looking for apartments together
	if ($item['folder_id'] == 24) $item['folder_id'] = 15;
	$item['category'] = $GLOBALS['cats'][$item['folder_id']];
	$item['responses'] = array('count' => (int) $item['responses']);
	$item['title'] = array('en' => $item['title']);
	$item['content'] = array('en' => $item['body']);
	unset($item['classified_id'], $item['body'], $item['site_id'], $item['user_id'], $item['status'], $item['folder_id'], $item['ts'], $item['ts_end']);
	return $item;
}

$items = $mysql->query('classifieds_data', array('status' => array(1,2), '!ts > DATE_SUB(NOW(), INTERVAL 3 MONTH)'), array('callback' => 'classifiedsCallback'));
$mongo->classifieds->ensureIndex('id', array("unique" => true));
$mongo->classifieds->batchInsert($items);

/*
$result = $mysql->query('categories');
foreach($result as $i => $cat) {
	$cat['code'] = $cat['name'];
	$en = $model->lang($cat['code'], $cat['module'] . 'Categories', 'EN', true);
	$cn = $model->lang($cat['code'], $cat['module'] . 'Categories', 'CN', true);
	$cat['name'] = array(
		'en' => $en,
		'cn' => $en == $cn ? NULL : $cn
	);
	$cat['selectable'] = $cat['selectable'] ? true : false;
	$cat['parent'] = $cat['category_id'] ? (int) $cat['category_id'] : NULL;
	unset($cat['category_id']);
	$cat['id'] = (int) $cat['id'];
	$result[$i] = $cat;
}
//var_dump($result);die();
$w = $cats->batchInsert($result);
*/
echo microtime_float() - $t;

?>