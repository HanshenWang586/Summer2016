<?php
include($_SERVER['DOCUMENT_ROOT'].'/includes/functions.php');
header('Content-type: text/plain; charset=utf-8');
set_time_limit(0);

$db = new DatabaseQuery;
$rs = $db->execute("SELECT listing_id,
							UNIX_TIMESTAMP(ts_added) AS ts_added_unix,
							UNIX_TIMESTAMP(ts_updated) AS ts_updated_unix,
							UNIX_TIMESTAMP(ts_squashed) AS ts_squashed_unix
					FROM listings_data
					WHERE city_id = 1
					AND status = 1
					ORDER BY ts_squashed DESC");

	while ($row = $rs->getRow()) {
		$review_date = 0;
		echo "{$row['listing_id']}, {$row['ts_added_unix']}, {$row['ts_updated_unix']}, {$row['ts_squashed_unix']}\n";

		// seek reviews
		$rs_2 = $db->execute("SELECT UNIX_TIMESTAMP(ts) AS ts_unix
								FROM listings_reviews
								WHERE listing_id = {$row['listing_id']}
								ORDER BY ts DESC
								LIMIT 1");
		if ($rs_2->getNum()) {
			$row_2 = $rs_2->getRow();
			echo "{$row_2['ts_unix']}\n";
			$review_date = $row_2['ts_unix'];
		}

		if ($row['ts_added_unix'] > $row['ts_squashed_unix']) {
			echo "shifting to added date\n";
			$new_squashed_date = $row['ts_added_unix'];
		}

		if ($row['ts_updated_unix'] > $new_squashed_date) {
			echo "shifting to edited date\n";
			$new_squashed_date = $row['ts_updated_unix'];
		}

		if ($review_date > $new_squashed_date) {
			echo "shifting to latest review date\n";
			$new_squashed_date = $row_2['ts_unix'];
		}

		echo "$new_squashed_date\n";
		$db->execute("UPDATE listings_data SET ts_squashed = '".date('Y-m-d H:i:s', $new_squashed_date)."' WHERE listing_id = {$row['listing_id']}");
		echo "\n";
	}
?>