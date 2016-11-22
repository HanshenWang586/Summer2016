<?php
class Review {

	private $display_mode = 'item_page';
	private $show_place = false;
	private $show_number_reviews = false;

	public function __construct($review_id = '') {
		if (ctype_digit($review_id)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT *
								FROM listings_reviews
								WHERE review_id = '.$review_id);
			$this->setData($rs->getRow());
		}
	}

	public function setData($data) 	{
		if (is_array($data)) {
			foreach ($data as $key => $value)
				$this->$key = $value;
		}
	}

	public function setShowPlace($bool) {
		$this->show_place = $bool;
	}
	
	public function setShowNumberReviews($bool) {
		$this->show_number_reviews = $bool;
	}

	function setListingID($listing_id)
	{
	$this->listing_id = $listing_id;
	}

	function setItemURL($item_url)
	{
	$this->item_url = $item_url;
	}

	function setReview($review)
	{
	$this->review = $review;
	}

	function setReviewID($review_id)
	{
	$this->review_id = $review_id;
	}

	function getDisplayMode()
	{
	return $this->display_mode;
	}

	function setDisplayMode($mode)
	{
	$this->display_mode = $mode;
	}
	
	private function getStarsCode($rating) {
		$stars = '';
		for ($i = 1; $i < 6; $i++) $stars .= $i <= $rating ? '&#xe00f;' : '&#xe011;';
		return sprintf('<span class="icon">%s</span>', $stars);
	}
	
	public function getListing() {
		if (!$this->listing or $this->listing->getListingID() != $this->listing_id) {
			$this->listing = new ListingsItem($this->listing_id);
		}
		return $this->listing;
	}
	
	public function display($showUser = true, $showListing = false) {
		global $user, $model, $site;
		$review = ContentCleaner::cleanPublicDisplay($this->review);
		$review = ContentCleaner::linkURLs($review);
		$review = ContentCleaner::wrapChinese($review);
		$review = ContentCleaner::PWrap($review);
		
		$hash = 'review-' . $this->review_id;
		
		$rating = $this->stars == -1 ? '' : sprintf("
				<div class=\"rating\" itemprop=\"reviewRating\" itemscope itemtype=\"http://schema.org/Rating\">
					<meta itemprop=\"worstRating\" content =\"1\">
					<meta itemprop=\"bestRating\" content =\"5\">
					<meta itemprop=\"ratingValue\" content=\"%d\">
					%s
				</div>",
				$this->stars,
				$this->getStarsCode($this->stars)
			); 
		
		$listing = '';
		if ($showListing) {
			$li = $this->getListing();
			$title = sprintf('<h1 itemprop="itemReviewed" itemscope itemtype="http://schema.org/LocalBusiness">%s</h1>', $li->getLinkedName(true));
		} else $title = sprintf('<span class="username" itemprop="author" itemscope itemtype="http://schema.org/Person">%s</span>', $site->getUser($this->user_id)->getLinkedNickname(true));
		
		return sprintf("
			<article id=\"%s\" class=\"review\" itemprop=\"review\" itemscope itemtype=\"http://schema.org/Review\">
				%s
				%s •
				%s
				<div itemprop=\"description\" class=\"body\">%s</div>
			</article>\n",
			$hash,
			$rating,
			$title,
			$model->tool('datetime')->getDateTag($this->ts, false, 'datePublished', true),
			$review
		);
	}

	public function displayPublic() {
		global $mobile;
		$review = ContentCleaner::cleanPublicDisplay($this->review);
		$review = ContentCleaner::linkURLs($review);
		$review = ContentCleaner::wrapChinese($review);

		$view = new View;
		$view->setPath('listings/review.html');
		$view->setTag('nickname', $this->getUser()->getLinkedNickname());
		//.'-'.$this->getUser()->getAverageStarsAwarded());
		$view->setTag('date', DateManipulator::convertUnixToFriendly($this->ts_unix, array('show_year' => true)));
		$view->setTag('review', ContentCleaner::PWrap($review));
		$view->setTag('stars', $this->getStars());
		
		if ($this->show_place) {
			$li = $this->getListing();
			$view->setTag('place', $li->getCityLink('en').' > '.$li->getLink());
		}
		
		if ($this->show_number_reviews)
			$view->setTag('number_of_reviews', "<a href=\"/en/users/all/reviews/$this->user_id/\">".$this->getUser()->getNumberReviews().' reviews</a>'.(!$mobile ? $this->getUser()->getReviewProfile() : ''));

		return $view->getOutput();
	}
	
	/**
	 * Gets a user object for the reviewer
	 *
	 * @return User The reviewer
	 */
	private function getUser() {
		return new User($this->user_id);
	}

	function getReviewBody() {
		if ($this->review != '')
			$content = ContentCleaner::wrapChinese($this->review);

		return $content;
	}

	public function getStars() {
		$star = new Star;
		$star->setListingID($this->listing_id);
		$star->setUserID($this->user_id);
		return $star->getStars();
	}

	public function displayAdminForm() {
		$li = new ListingsItem($this->listing_id);
		$content = $li->displayAdminBrief();
		$content .= FormHelper::open('form_review_proc.php');
		$content .= FormHelper::hidden('review_id', $this->review_id);
		$f[] = FormHelper::textarea('Review', 'review', $this->review);
		$content .= FormHelper::fieldset('User Review', $f);
		$content .= FormHelper::submit();
		$content .= FormHelper::close();
		return $content;
	}

	public function savePublic() {
		global $user;
		$user_id = $user->getUserID();
		$review = ContentCleaner::cleanForDatabase($this->review);

		if (trim($this->review) != '' && $user_id != 0) {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT *
								FROM listings_reviews
								WHERE user_id = $user_id
								AND listing_id = $this->listing_id
								AND live = 1");

			if ($rs->getNum() == 0) {
				$db->execute("	INSERT INTO listings_reviews (	user_id,
																listing_id,
																review,
																ts,
																live,
																stars)
								VALUES (	$user_id,
											$this->listing_id,
											'".$db->clean($review)."',
											NOW(),
											1,
											-1)");
			}
			else {
				$db->execute("	UPDATE listings_reviews
								SET review = '".$db->clean($review)."',
									ts = NOW()
								WHERE user_id = $user_id
								AND listing_id = $this->listing_id");
			}
		}
		
		$li = new ListingsItem($this->listing_id);
		$li->squash();

		$star = new Star;
		$star->setListingID($this->listing_id);
		$star->setStars($this->stars);
		$star->save();
	}

	public function saveAdmin() {
		$this->review = ContentCleaner::cleanForDatabase($this->review);
		$db = new DatabaseQuery;
		$db->execute("	UPDATE listings_reviews
						SET review = '".$db->clean($this->review)."'
						WHERE review_id = $this->review_id");
	}

	function displayAdminRow()
	{
	$cc = new ContentCleaner;
	$cc->cleanAdminDisplay($this->review);

	$content = "<tr valign=\"top\">
	<td>$this->name_en</td>
	<td>$this->nickname</td>
	<td width=\"300\">$this->review</td>
	<td>$this->ts</td>
	<td>$this->live</td>
	<td><a href=\"form_review.php?review_id=$this->review_id\">Edit</a></td>
	<td><a href=\"delete_review.php?review_id=$this->review_id\" onClick=\"return conf_del()\">Delete</a></td>
	</tr>";
	return $content;
	}

	public function delete() {
		$db = new DatabaseQuery;
		$db->execute('UPDATE listings_reviews
					 SET live = 0
					 WHERE review_id = '.$this->review_id);
	}

	function displayLatest()
	{
	$li = new ListingsItem($this->listing_id);
	//$content = "<a href=\"/en/profile/index.php?user_id=$this->user_id\" class=\"ccl_link\">$this->nickname</a>: <a href=\"$this->item_url?listing_id=$this->listing_id\" class=\"ccl_link\">$this->name_en</a>";
	$content = "$this->nickname: ".$li->getLink();
	return $content;
	}
}
?>