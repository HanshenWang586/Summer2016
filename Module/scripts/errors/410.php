<?php
$p = new Page;

$body .= "<h1 class=\"dark\">410 Permanently Removed</h1>
			<div class=\"whiteBox\">
				<h2>Sorry, looks like we've moved things around.</h2>
				<p>Hopefully, you can find what you're looking for using the navigation around
				this page, or the site search. If you'd like to contact us, please <a href=\"/en/contact/\">click here</a>.</p>
			</div>";

$p->setTag('main', $body);
$p->output();
?>