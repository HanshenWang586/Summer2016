<?php
require($_SERVER['DOCUMENT_ROOT'].'/admin/includes/functions.php');

header('Content-type: text/plain');
set_time_limit(0);
ignore_user_abort();
ob_start();
$start = microtime(true);

$enews_id = (int) $_GET['enews_id'];

$db = new DatabaseQuery;
$rs = $db->execute('SELECT *
					FROM enews
					WHERE enews_id = '.$enews_id);
$row = $rs->getRow();

$site = new Site($row['site_id']);
$message = $row['message'];

$smtp = new SMTP;
$smtp->open();

$mail = new Mail;
$mail->clearBcc();
$mail->setFrom('info@gokunming.com', 'GoKunming');
$mail->setSubject($row['subject']);

$db = new DatabaseQuery;

$optimized = "SELECT DISTINCT u.user_id, u.nickname, u.email, u.ts_registered, MAX(l.ts) AS last_login FROM public_users u
				LEFT JOIN log_logins l ON (u.user_id = l.user_id)
				WHERE verified = 1
				AND status & 1
				AND status & 4
				AND u.user_id NOT IN ( SELECT user_id FROM log_enews WHERE enews_id = $enews_id )
				GROUP BY u.user_id
				HAVING ts_registered != last_login
				AND last_login > '2014-01-01'
				ORDER BY rand() DESC
			";

$verified = "SELECT u.* FROM public_users u
			WHERE verified = 1
			AND (
				u.site_id = 1 OR u.site_id = 0
			)
			AND status & 1
			AND status & 4
			AND u.user_id NOT IN ( SELECT user_id FROM log_enews WHERE enews_id = $enews_id )
			ORDER BY email ASC";

$maybeVerified = "SELECT DISTINCT u.user_id, u.nickname, u.email, u.ts_registered, MAX(l.ts) AS last_login FROM public_users u
				LEFT JOIN log_logins l ON (u.user_id = l.user_id)
				WHERE (
					(family_name != nickname AND nickname != given_name) OR (verified = 1)
				)
				AND status & 1
				AND status & 4
				AND u.user_id NOT IN ( SELECT user_id FROM log_enews WHERE enews_id = $enews_id )
				AND u.site_id = 1
				AND l.site_id = 1
				GROUP BY u.user_id
				HAVING ts_registered != last_login
				ORDER BY u.email ASC";

$rs = $db->execute($optimized);

while ($row = $rs->getRow()) {
		$mail->clearTo();
		$mail->addTo($row['email']);
		$message2 = str_replace(array(
			'[email]',
			'[nickname]',
			'[enews_id]',
			'#EMAIL#'
		), array(
			$row['email'],
			$row['nickname'],
			$enews_id,
			$row['email']
		), $message);
		$mail->setHTMLMessage($message2);
		$smtp->send($mail->getFrom(), $mail->getAllRecipients(), $mail->getData());

		echo $row['email']."\n";
		echo microtime(true) - $start."\n";
		ob_flush();

		$db->execute("	INSERT INTO log_enews (user_id, enews_id, ts)
						VALUES ({$row['user_id']}, $enews_id, NOW())");
}

$smtp->quit();
echo 'end: '.(microtime(true) - $start)."\n";
ob_end_flush();
?>