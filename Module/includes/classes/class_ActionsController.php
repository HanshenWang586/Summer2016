<?php
/*
class ActionsController
{
	function rate()
	{
	$li = new ListingsItem($_GET['listing_id']);
	echo $li->displayStarsForm();
	}

	function proc_rate()
	{
	$li = new ListingsItem($_POST['listing_id']);

	$star = new Star;
	$star->setListingID($_POST['listing_id']);
	$star->setStars($_POST['stars']);
	$star->save();
	}

	function review()
	{
	$lr = new Review;
	$lr->setListingID($_GET['listing_id']);
	echo $lr->displayPublicForm();
	}

	function proc_review()
	{
	$li = new ListingsItem($_POST['listing_id']);
	$lr = new Review;
	$lr->setListingID($_POST['listing_id']);
	$lr->setReview($_POST['review']);
	$lr->savePublic();
	}

	function vote()
	{
	$lr = new Review;
	$lr->setReviewID($_GET['review_id']);
	$lr->saveVote($_GET['vote']);
	echo $lr->getVoter();
	}

	function reset()
	{
	global $user;

	$li = new ListingsItem($_GET['listing_id']);
	echo $li->getActions();
	}

	function refresh_reviews()
	{
	$li = new ListingsItem($_GET['listing_id']);
	echo $li->getReviews();
	}

	function phone_code()
	{
	$city = new City($_GET['city_id']);
	echo $city->getPhoneCode();
	}

	function form_categorize()
	{
	$li = new ListingsItem($_GET['listing_id']);
	echo $li->displayCategoriesForm();
	}

	

	function photo() {
		$li = new ListingsItem($_GET['listing_id']);
		echo $li->displayPhotoForm();
	}

	function photo_save() {

		$photo = new ListingsPhoto;
		$photo->setListingID($_POST['listing_id']);
		$photo->save($_FILES);

		$item = new ListingsItem($_POST['listing_id']);
		HTTP::redirect($item->getURL());
	}
}
*/
?>