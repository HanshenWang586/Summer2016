<?php
require($_SERVER['DOCUMENT_ROOT'].'/includes/functions.php');

$db = $GLOBALS['site']->db();

$result = $db->run_query("
SELECT DISTINCT u.user_id, u.*, MAX(l.ts) AS last_login FROM public_users u
LEFT JOIN log_logins l ON (u.user_id = l.user_id)
WHERE (
		(family_name = nickname AND nickname = given_name) AND verified = 0
	)
	AND status & 4
	AND status & 1
	AND u.site_id = 1
	AND l.site_id = 1
GROUP BY u.user_id
HAVING last_login = ts_registered
ORDER BY last_login DESC
");

$result = $db->num_rows($result);

$rounds = ceil($result / 50);

for ($i = 0; $i < $rounds; $i++) {

$results = $db->run_select("
SELECT DISTINCT u.user_id, u.*, MAX(l.ts) AS last_login FROM public_users u
LEFT JOIN log_logins l ON (u.user_id = l.user_id)
WHERE (
		(family_name = nickname AND nickname = given_name) AND verified = 0
	)
	AND status & 4
	AND status & 1
	AND u.site_id = 1
	AND l.site_id = 1
GROUP BY u.user_id
HAVING last_login = ts_registered
ORDER BY last_login DESC
LIMIT $i, 50
", false, array('transpose' => 'user_id'));

$r = $db->query('bb_posts', array('user_id' => $results));
if ($r) echo sprint_rf($r);
ob_flush();

}

foreach($results as $id) {
	$result = $db->query('bb_posts', array('user_id' => $id));
	if ($result) var_dump($result);
	ob_flush();
}

?>