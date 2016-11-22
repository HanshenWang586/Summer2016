<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

if ($username = request($_POST['username']) and $password = request($_POST['password'])) {
	$id = $model->db()->query('admin_users', array('username' => $username, 'password' => $password), array('selectField' => 'user_id'));
	if ($id) $_SESSION['admin_user'] = new AdminUser($id);
}

HTTP::redirect();
?>