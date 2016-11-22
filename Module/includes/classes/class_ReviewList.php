<?php
class ReviewList {

	private $date_options = array('show_year' => true);
	
	public static function getListingsItemReviews($listing_id) {
		$db = new DatabaseQuery;
		$rs = $db->execute('	SELECT r.*, nickname, UNIX_TIMESTAMP(r.ts) AS ts_unix
								FROM listings_reviews r, public_users u
								WHERE listing_id = '.$listing_id.'
								AND r.user_id = u.user_id
								AND r.live = 1
								ORDER BY ts DESC');
		while ($row = $rs->getRow()) {
			$lr = new Review;
			$lr->setData($row);
			$reviews[] = $lr;
		}
		return $reviews;
	}

	public function setReviewIDs($review_ids) {
		$this->review_ids = $review_ids;
	}

	public function getReviews($pager, $user_id = false, $showListing = false) {
		$user = $user_id ? ' AND r.user_id = ' . $user_id : '';
		$db = new DatabaseQuery;
		$rs = $pager->setSQL("SELECT r.*, UNIX_TIMESTAMP(r.ts) AS ts_unix
							FROM listings_reviews r
							LEFT JOIN listings_data l ON (r.listing_id = l.listing_id)
							WHERE l.status = 1
							$user
							AND r.live = 1
							ORDER BY r.ts DESC");

		if ($rs->getNum()) {
			$reviews = '';
			$lr = new Review;
			while ($row = $rs->getRow()) {
				$lr->setData($row);
				$reviews .= $lr->display(false, $showListing);
			}
		}
		return $reviews . $pager->getNav();
	}

	public function displayAdmin(&$pager) {
		$content = "<table cellspacing=\"1\" class=\"gen_table\">
		<tr>
		<td><b>Item</b></td>
		<td><b>User</b></td>
		<td><b>Review</b></td>
		<td><b>Time</b></td>
		<td><b>Live</b></td>
		<td colspan=\"2\"></td>
		</tr>";
		$rs = $pager->setSQL("	SELECT *
								FROM listings_reviews r, public_users u, listings_data d
								WHERE u.user_id = r.user_id
								AND d.listing_id = r.listing_id
								AND live = 1
								ORDER BY ts DESC");

		while ($row = $rs->getRow()) {
			$lr = new Review;
			$lr->setData($row);
			$content .= $lr->displayAdminRow();
		}

		$content .= '</table>';
		return $content;
	}
}
?>
