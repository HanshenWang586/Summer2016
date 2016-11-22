<?php
include($_SERVER['DOCUMENT_ROOT'].'/includes/functions.php');
header('Content-type: text/plain; charset=utf-8');
set_time_limit(0);

/*
this file looks for the datetime of the first post on a thread, and sets ts_created on the thread accordingly
*/

$db = new DatabaseQuery;
$rs = $db->execute("SELECT thread_id, ts
FROM bb_posts
GROUP BY thread_id
HAVING MIN(ts)");

while ($row = $rs->getRow()) {
	echo $row['thread_id']."\n";
	$db->execute("UPDATE bb_threads SET ts_created = '{$row['ts']}' WHERE thread_id = {$row['thread_id']}");
}