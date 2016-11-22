<?php
$p = new Page;

$body .= "<h1 class=\"dark\">403 Access Forbidden</h1>
			<div class=\"whiteBox\">
				<h2>Sorry, it seems you are not authorized to view this page.</h2>
				<p>Hopefully, you can find what you're looking for using the navigation around
				this page, or the site search. If you'd like to contact us, please <a href=\"/en/contact/\">click here</a>.</p>
			</div>";

$p->setTag('main', $body);
$p->output();
?>