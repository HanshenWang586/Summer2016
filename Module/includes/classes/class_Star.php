<?php
class Star
{
	function setListingID($listing_id) {
		$this->listing_id = $listing_id;
	}

	function setUserID($user_id) {
		$this->user_id = $user_id;
	}

	function setStars($stars) {
		$this->stars = $stars;
	}

	public function setAverageStars($average_stars) {
		$this->stars = round($average_stars * 2)/2;
	}

	public function getStars() {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT stars
							FROM listings_reviews
							WHERE user_id = $this->user_id
							AND listing_id = $this->listing_id
							AND live = 1
							AND stars != -1");

		if ($rs->getNum() != 0) {
			$row = $rs->getRow();
			$this->stars = $row['stars'];
			$content = $this->displayStars();
		}

	return $content;
	}

	public function save() {
		global $user;
	
		if ($user->getUserID() != 0) {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT review_id
								FROM listings_reviews
								WHERE user_id = ".$user->getUserID()."
								AND listing_id = $this->listing_id
								AND live = 1");
	
			if ($rs->getNum() == 0) {
				$db->execute("	INSERT INTO listings_reviews (	user_id,
																listing_id,
																stars,
																live,
																ts)
								VALUES (	".$user->getUserID().",
											$this->listing_id,
											$this->stars,
											1,
											NOW())");
			}
			else {
				$row = $rs->getRow();
				$review_id = $row['review_id'];
				$db->execute("	UPDATE listings_reviews
								SET stars = $this->stars,
									ts = NOW()
								WHERE review_id = $review_id");
			}
		}
	}

	public function displayStars() {
		$stars = '';
		for ($i = 1; $i < 6; $i++) $stars .= $i <= $this->stars ? '&#xe00f;' : '&#xe011;';
		return sprintf('
			<div itemprop="aggregateRating" class="listings_stars" itemscope itemtype="http://schema.org/AggregateRating">
    			<meta itemprop="ratingValue" content="%s">
				<span class="icon listings_stars">%s</span>
  			</div>',
			$this->stars,
			$stars
		);
	}
}
?>