<?php
include($_SERVER['DOCUMENT_ROOT'].'/includes/functions.php');
header('Content-type: text/plain; charset=utf-8');
set_time_limit(0);

/*
this file looks for rows in bb_threads that have no corresponding bb_posts rows
*/

$db = new DatabaseQuery;
$rs = $db->execute('SELECT * FROM bb_threads');

while ($row = $rs->getRow()) {
	$rs_2 = $db->execute("SELECT * FROM bb_posts WHERE thread_id = {$row['thread_id']}");
	if ($rs_2->getNum() == 0)
		print_r($row);
}