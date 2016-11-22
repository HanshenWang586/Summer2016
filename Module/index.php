<?php
require_once('model.class.php');

$time = microtime_float();

$options = array(
	'title' => 'GoKunming',
	'debug' => false,
	'db_explain' => false
);

// Let's create our model
$model = new MainModel(array(), $options);

if ($model->state('output') == 'html') {
	$html = $model->tool('html');
	
	//$key = $_SERVER['HTTP_HOST'] == 'localhost' ? 'ABQIAAAApeN0mUv7zQuyJM2INcJgQBT2yXp_ZAY8_ufC3CFXhHIE1NvwkxTnOBHK5qiyr6z8rOyJ_xVYi5Z-vg' : 'ABQIAAAApeN0mUv7zQuyJM2INcJgQBTR-R_nvX4WaoT1OZnYx5EQmyihVxRLtjSKhoRDcRBB4NLJ0bX1p5YLhg';
	//$html->addJS('http://www.google.com/jsapi?key=' . $key);
	
	// Be more flexible in cache control
	$html->cacheControl('nocache', true);
	
	$html->addMeta('author', "Yereth Jansen");
}

$model->outputContent();

//if ($model->state('output') == 'html') echo $time = microtime_float() - $time;
?>
