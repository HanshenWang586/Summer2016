<?php
$p = new Page;

global $model;

$body .= sprintf("
	<h1 class=\"dark\">401 Unauthorized</h1>
	<div class=\"whiteBox\">
		<h2>Sorry, you must be logged in to do this.</h2>
	</div>
	<p>
		<a class=\"icon-link\" href=\"/en/users/login/\"><span class=\"icon icon-login\"> </span>%s</a>
		<a class=\"icon-link\" href=\"/en/users/register/\"><span class=\"icon icon-user-add\"> </span>%s</a>
	</p>",
		$model->lang('MENU_LOGIN'),
		$model->lang('MENU_REGISTER')
	);

$p->setTag('main', $body);
$p->output();
?>