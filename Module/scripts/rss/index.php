<?php
header('Content-type: text/plain; charset=utf-8');
set_time_limit(0);

$db = new DatabaseQuery;
$rs = $db->execute("SELECT DISTINCT DATE(ts) AS ts_day
					FROM log_rss
					WHERE DATE(ts) != DATE(NOW())
					ORDER BY ts DESC");

while ($row = $rs->getRow()) {
	$rs_2 = $db->execute("	SELECT DISTINCT ip, ua
							FROM log_rss
							WHERE DATE(ts) = '{$row['ts_day']}'");
	$db->execute("INSERT INTO log_rss_summary(ts, tally)
				 VALUES ('{$row['ts_day']}', ".$rs_2->getNum().")");
	$db->execute("DELETE FROM log_rss WHERE DATE(ts) = '{$row['ts_day']}'");
}
