<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

$db = new DatabaseQuery;
$rs = $db->execute('SELECT *
					FROM enews
					WHERE enews_id = '.$_POST['enews_id']);
$row = $rs->getRow();

$site = new Site($row['site_id']);
$message = $row['message'];

$smtp = new SMTP;
$smtp->open();

$mail = new Mail;
$mail->clearBcc();
$mail->setFrom('info@gokunming.com', 'GoKunming');
$mail->setSubject($row['subject']);

$emails = explode("\n", trim($_POST['emails']));

	foreach ($emails as $email) {
		$email = trim($email);
		$mail->clearTo();
		$mail->addTo($email);
		$mail->setHTMLMessage(str_replace('#EMAIL#', $email, $message));
		$smtp->send($mail->getFrom(), $mail->getAllRecipients(), $mail->getData());
	}

$smtp->quit();
HTTP::redirect('index.php');
?>