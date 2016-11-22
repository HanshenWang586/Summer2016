<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/model.class.php');

$options = array(
	'title' => 'Script',
	'debug' => false,
	'db_explain' => false,
	'noRedirect' => true
);

// Let's create our model
$model = new MainModel(array('LANG' => 'cn'), $options);

$email = request($_GET['email']);

echo "email verifies: ";

var_dump(validateEmail($email));

?>