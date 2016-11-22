<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

if (ctype_digit($_POST['photo_id'])) {
	$photo = new Photo;
	$photo->setData($_POST);
	$photo->save();
}
else {
	for ($i=0; $i<count($_FILES['file']['name']); $i++) {
		$photo = new Photo;
		$photo->setSource($_FILES['file']['tmp_name'][$i]);
		$photo->setData($_POST);
		$photo->save();
	}
}

HTTP::redirect('index.php');
?>