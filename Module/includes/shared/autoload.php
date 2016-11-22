<?php
function __autoload($class_name) {
	$class_file = $_SERVER['DOCUMENT_ROOT'].'/includes/classes/class_'.$class_name.'.php';
	$shared_class_file = $_SERVER['DOCUMENT_ROOT'].'/includes/classes/shared/class_'.$class_name.'.php';

	if (file_exists($class_file))
		include $class_file;
	else if (file_exists($shared_class_file))
		include $shared_class_file;
	else {
		//var_dump($class_file, $shared_class_file, file_exists($class_file), file_exists($shared_class_file));
		//die('Not found');
		HTTP::throw404();
	}
}
?>