<?php

include('../../model.class.php');

$options = array(
	'title' => 'GoKunming',
	'debug' => false,
	'db_explain' => false,
	'noRedirect' => true
);

// Let's create our model
$model = new MainModel(array(), $options);

$captcha = new CaptchaSecurityImages();
?>