<?php
class User {

	private $loggedIn = false;
	public $verified = 0;
	public $verification_sent = 0;
	private $area_id = 1;
	private $user_id = 0;
	private $status;
	private $viewing_city_id;
	private $viewing_category_id;

	/*
	USERS
	live [unbanned]		1
	imported			2
	enews				4
	classifieds			8
	*/

	public function __construct($user_id = '') {
		if (filter_var($user_id, FILTER_VALIDATE_INT)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT *, UNIX_TIMESTAMP(ts_registered) AS ts_registered_unix
								FROM public_users u
								LEFT JOIN gk4_areas a ON (a.area_id = u.area_id)
								WHERE user_id = '.$user_id);
			$this->setData($rs->getRow());
		}
	}

	function setURL($url) {
		$this->odu_temp = $url;
		$url_parts = explode('/', $url);
		$this->setLanguageAbbrev($url_parts[1]);

		switch($url_parts[3]) {
			case 'itemlist':
				$this->setCityCode($url_parts[3]);
				$this->setCategoryCode($url_parts[4]);
				$this->setPage($url_parts[5]);
			break;

			case 'city':
				$this->setCityCode($url_parts[3]);
			break;
		}
	}
	
	public function setViewingCityID($city_id) {
		$this->viewing_city_id = $city_id;
	}

	function setCategoryID($category_id) {
		$this->category_id = $category_id;
	}

	function getCategoryID() {
		return $this->category_id;
	}
	
	public function setViewingCategoryID($category_id) {
		$this->viewing_category_id = $category_id;
	}

	public function getViewingCategoryID() {
		return $this->viewing_category_id;
	}

	public function getViewingCity() {
		return new City($this->viewing_city_id);
	}

	function setCityCode($city) {
		if ($city != '') {
			$c = new City;
			$this->setViewingCityID($c->getCityIDFromName($city));
		}
	}

	public function setData($row) {
		if (is_array($row)) {
			foreach($row as $key => $value) {
				$this->$key = $value;
			}
		}
	}

	public function getData() {
		// this is used by UserProfileForm
		return array('email' => $this->email,
					 'enews' => $this->isSetStatusBit(4) ? 1 : 0,
					 'given_name' => $this->given_name,
					 'family_name' => $this->family_name,
					 'area_id' => $this->area_id);
	}

	public function getDateRegistered() {
		return DateManipulator::convertUnixToFriendly($this->ts_registered_unix, array('show_year' => true));
	}

	public function getRegion() {
		$db = $GLOBALS['model']->db();
		return !$this->area_id ? false : $db->query('gk4_areas', array('area_id' => $this->area_id), array('selectField' => 'area_en'));
	}

	function setIP($ip) {
		$this->ip = $ip;
	}

	function getIP() {
		return $this->ip;
	}

	function getUserID() {
		return $this->user_id;
	}

	function setNickname($nickname) {
		$this->nickname = $nickname;
	}

	public function getNickname($metadata = false, $noSpan = false) {
		if ($noSpan) return $this->nickname;
		$itemProp = $metadata ? ' itemprop="name"' : '';
		$name = ContentCleaner::wrapChinese($this->nickname);
		return sprintf('<span%s class="nickname">%s</span>', $itemProp, $name);
	}
	
	public function getFullName() {
		return trim($this->given_name . ' ' . $this->family_name);
	}
	
	public function getProfileURL() {
		return "/en/users/profile/$this->user_id/".ContentCleaner::processForURL($this->nickname);
	}
	
	public function getPublicURL($metadata = false) {
		return $this->isBanned() ? $this->getNickname($metadata) : $this->getLinkedNickname($metadata);
	}
	
	public function getLinkedNickname($metadata = false) {
		$itemProp = $metadata ? ' itemprop="url"' : '';
		return sprintf('<a%s href="/en/users/profile/%d/%s">%s</a>', $itemProp, $this->user_id, ContentCleaner::processForURL($this->nickname), $this->getNickname($metadata));
	}
	
	public function sendEmail($subject, $message) {
		if ($subject and $message and $this->email) {
			global $model;
			$mailer = new Mailer();
			$domain = $model->module('preferences')->get('emailDomain');
			$succes = $mailer->send(array(
				'subject' => $subject,
				'content' => $message,
				'from' => array('do-not-reply@' . $domain => $model->lang('SITE_NAME')),
				'to' => array($this->email => trim($this->given_name . ' ' . $this->family_name)),
				'bcc' => 'bitbucket@'.$domain
			));
			return $succes;
		}
	}
	
	function setPassword($password) {
		$this->password = $password;
	}

	function setEmail($email) {
		$this->email = $email;
	}

	function getEmail() {
		return $this->email;
	}

	function setGivenName($given_name) {
		$this->given_name = $given_name;
	}

	function setFamilyName($family_name) {
		$this->family_name = $family_name;
	}

	function setAreaID($area_id) {
		$this->area_id = $area_id;
	}

	function setSessionId($session_id) {
		$this->session_id = $session_id;
	}

	function getSessionId() {
		return $this->session_id;
	}

	function getLogoutURL() {
		return $this->logout_url;
	}

	function getRegisterURL() {
		return $this->register_url;
	}

	function isLoggedIn() {
		return $this->loggedIn and $this->status & 1 and $this->isEmailVerified();
	}

	function displayProfile() {
		$content = "<h1>User Profile</h1>
		<table cellpadding=\"0\" cellspacing=\"0\">
		<tr height=\"20\"><td><b>Nickname</b></td><td width=\"15\" rowspan=\"7\"></td><td>$this->nickname</td></tr>
		<tr height=\"20\"><td><b>Email</b></td><td>$this->email</td></tr>
		<tr height=\"20\"><td><b>First name</b></td><td>$this->given_name</td></tr>
		<tr height=\"20\"><td><b>Last name</b></td><td>$this->family_name</td></tr>
		<tr height=\"20\"><td><b>Location</b></td><td>$this->area_en</td></tr>
		</table>";
		return $content;
	}

	public function getLoginForm() {
		if (!$this->isLoggedIn()) {
			$content .= '<h1 class="dark">Login</h1>';

			if (isset($_GET['fail']))
				$content .= '<p class="message error">We did not recognize the email and password combination you entered. Please try again.</p>';
			if (isset($_GET['banned']))
				$content .= '<p class="message error">You have tried to login as a banned user. Please address any queries about this matter to us <a href="/en/contact/">here</a>.</p>';
			if (isset($_GET['notVerified']))
				$content .= '<p class="message error">Your email address has not yet been verified. Please follow the instructions sent to you by email.<br>
							If you would like to request a new verification code, please click <a href="/en/users/verify/">here</a>.</p>';

			$content .= FormHelper::open('/en/users/login_proc/');
			$f[] = FormHelper::input('Email', 'email', '', array('mandatory' => true, 'type' => 'email'));
			$f[] = FormHelper::password('Password', 'password', '', array('mandatory' => true));
			$f[] = FormHelper::submit($GLOBALS['model']->lang('B_LOGIN_CAPTION', 'usersModel', false, true));
			$content .= FormHelper::fieldset(false, $f);
			$content .= FormHelper::close();
			$content .= "<p><a class=\"icon-link\" href=\"/en/users/register/\"><span class=\"icon icon-user-add\"> </span>Sign up</a>
			<a class=\"icon-link\" href=\"/en/users/forgot/\"><span class=\"icon icon-help\"> </span>Forgot your password?</a></p>";
		} else {
			$content .= "<h1 class=\"dark\">You are now logged in</h1>
						<div class=\"message infoMessage\">
							You are logged in as <strong>$this->nickname</strong>.
							What would you like to do?<br>
							<a class=\"button\" href=\"/en/\">View homepage</a> <a class=\"button\" href=\"/en/users/dashboard/\">View dashboard</a>
						</div>";
		}

		return $content;
	}
	
	public function requestChangeEmail($email) {
		if (!$this->isLoggedIn()) return false;
		global $model;
		
		$db = $model->db();
		// remove previous unverified entries
		$db->delete('user_verification', array('user_id' => $this->getUserID(), 'verified' => 0));
		
		$hash = md5(rand(0,1000));
		
		if (!$db->insert('user_verification', array(
			'user_id' => $this->getUserID(),
			'hash' => $hash,
			'date' => date('Y-m-d H:i:s', time()),
			'email' => $email
		))) return false;
		
		$view = new View('emails/user_change_email.html');
		$body = $view->getOutput();
		
		$mailer = new Mailer();
		$mailer->addReplaceList(array(
			'hash' => $hash,
			'email' => $email,
			'nickname' => $this->nickname
		));
		
		$domain = $model->module('preferences')->get('emailDomain');
		
		$succes = $mailer->send(array(
			'subject' => '[site_title]: Email Verification',
			'content' => $body,
			'from' => array('info@'.$domain => $model->lang('SITE_NAME')),
			'to' => array($email => trim($this->given_name . ' ' . $this->family_name)),
			'bcc' => 'bitbucket@'.$domain
		));
		return $succes;
	}
	
	public function sendVerifyEmail() {
		if (!$this->getUserID() or !$this->email) return false;
		global $model;
		
		$db = $model->db();
		// remove previous unverified entries
		$db->delete('user_verification', array('user_id' => $this->getUserID(), 'verified' => 0));
		
		$hash = md5(rand(0,1000));
		
		if (!$db->insert('user_verification', array(
			'user_id' => $this->getUserID(),
			'hash' => $hash,
			'date' => date('Y-m-d H:i:s', time()),
			'email' => $this->email
		))) {
			return false;
		}
		
		if (false === $db->update('public_users', array('user_id' => $this->getUserID()), array('verified' => 0, 'verification_sent' => 1))) {
			return false;
		}
		
		$view = new View('emails/user_verification.html');
		$body = $view->getOutput();
		
		$mailer = new Mailer();
		$mailer->addReplaceList(array(
			'hash' => $hash,
			'email' => $this->email,
			'nickname' => $this->nickname,
			'password' => $this->password
		));
		$domain = $model->module('preferences')->get('emailDomain');
		$succes = $mailer->send(array(
			'subject' => '[site_title] Signup | Verification',
			'content' => $body,
			'from' => array('info@'.$domain => $model->lang('SITE_NAME')),
			'to' => array($this->email => trim($this->given_name . ' ' . $this->family_name)),
			'bcc' => 'bitbucket@'.$domain
		));
		return $succes;
	}
	
	public function validate($email, $password) {
		$email = trim($email);
		$password = trim($password);
		
		if (!$email or !$password) return $this;
		
		$db = $GLOBALS['model']->db();
		
		$user = $db->query('public_users',
			array('email' => $email, 'password' => $password),
			array(
				'getFields' => '*, UNIX_TIMESTAMP(ts_registered) AS ts_registered_unix',
				'orderBy' => 'status',
				'singleResult' => true
			)
		);
		
		if ($user) {
			$this->setData($user);
			if ($user['status'] & 1 and $this->isEmailVerified()) {
				$this->loggedIn = true;
				$this->logLogin();
			} else {
				$this->loggedIn = false;
			}
		}
		
		return $this;
	}
	
	public function reloadData() {
		if ($this->user_id) {
			$db = $GLOBALS['site']->db();
			$user = $db->query('public_users', array('user_id' => $this->user_id), array('singleResult' => true));
			if ($user) {
				$this->setData($user);
				return true;
			}
		}
		return false;
	}
	
	public function isEmailVerified() {
		return $this->verified == 1 or $this->verification_sent == 0;
	}

	public function isBanned() {
		if (!$this->user_id) return false;
		$this->reloadStatus();
		return !$this->isSetStatusBit(1);
	}

	public function setClassifiedsApproved() {
		$this->setStatusBit(8);
	}

	public function isClassifiedsApproved() {
		// Nope...
		return false;
	}

	public function isEnewsRecipient() {
		$this->reloadStatus();
		return $this->isSetStatusBit(4);
	}

	private function setStatusBit($bit) {
		if (!$this->isSetStatusBit($bit)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('UPDATE public_users
								SET status = status + '.$bit.'
								WHERE user_id = '.$this->user_id);
		}
	}

	public function unsubscribeEnews() {
		if ($this->isSetStatusBit(4)) {
			$db = new DatabaseQuery;
			$rs = $db->execute('UPDATE public_users
								SET status = status - 4
								WHERE user_id = '.$this->user_id);
		}
	}

	private function isSetStatusBit($bit) {
		$this->reloadStatus();
		return $bit & $this->status;
	}

	private function reloadStatus() {
		$db = $GLOBALS['model']->db();
		$this->status = $db->query('public_users', array('user_id' => $this->user_id), array('selectField' => 'status'));
	}

	public function ban() {
		$db = new DatabaseQuery;
		$db->execute('	UPDATE public_users
						SET status = status - 1
						WHERE user_id = '.$this->user_id);
	}

	function generatePassword()
	{
	$char_array = range('a', 'z');

		while (strlen($new_password)<8)
		{
		$new_password .= $char_array[rand(0, count($char_array)-1)];
		}

	$db = new DatabaseQuery;
	$db->execute("	UPDATE public_users
					SET password='$new_password'
					WHERE user_id=$this->user_id");

	return $new_password;
	}

	private function logLogin() {
		$db = new DatabaseQuery;
		$db->execute("	INSERT INTO log_logins (	user_id,
													method,
													ip,
													ts,
													session_id)
						VALUES (	$this->user_id,
									'$this->method',
									'$this->ip',
									NOW(),
									'$this->session_id')");
	}

	public function saveProfileUpdate() {
		global $user;
		$status_change = '';

		if ($user->isEnewsRecipient() && $this->enews != 1)
			$status_change = '- 4';
		else if (!$user->isEnewsRecipient() && $this->enews == 1)
			$status_change = '+ 4';
		
		$db = new DatabaseQuery;
		$db->execute("UPDATE public_users
							SET email = '".$db->clean($this->email)."',
								area_id = ".$db->clean($this->area_id).",
								status = status $status_change
							WHERE user_id = ".$user->getUserID());
	}
	
	public function saveAdmin() {
		$db = new DatabaseQuery;
		$db->execute('	UPDATE public_users
						SET status = '.(is_array($this->status) ? array_sum($this->status) : 0).'
						WHERE user_id = '.$this->user_id);
	}

	public function save() {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT user_id
							FROM public_users
							WHERE status & 2
							AND email = '".$db->clean($this->email)."'");
		$row = $rs->getRow();

		if (ctype_digit($row['user_id'])) {
			// imported semi-registered user; status 5 = live + enews
			$db->execute("	UPDATE public_users
							SET nickname = '".$db->clean($this->nickname)."',
								given_name = '".$db->clean($this->given_name)."',
								family_name = '".$db->clean($this->family_name)."',
								password = '".$db->clean($this->password)."',
								area_id = $this->area_id,
								ts_registered = NOW(),
								ip = '".$this->getIP()."',
								status = 5
							WHERE user_id = {$row['user_id']}");
			$this->user_id = $row['user_id'];
		}
		else {
			// a nobody
			$db->execute("	INSERT INTO public_users (	nickname,
														given_name,
														family_name,
														email,
														password,
														area_id,
														ts_registered,
														ip,
														status)
							VALUES (	'".$db->clean($this->nickname)."',
										'".$db->clean($this->given_name)."',
										'".$db->clean($this->family_name)."',
										'".$db->clean(strtolower($this->email))."',
										'".$db->clean($this->password)."',
										$this->area_id,
										NOW(),
										'".$this->getIP()."',
										1 + 4)");
			$this->user_id = $db->getNewID();
		}
	}
	
	public function getLatestReviews() {
		$rl = new ReviewList;
		return $rl->getLatestUser($this->user_id);
	}

	public function getPower() {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT power
							FROM ccl_userpowers
							WHERE user_id = '.$this->user_id);

		if ($rs->getNum() == 0)
			return 0;
		else {
			$row = $rs->getRow();
			return $row['power'];
		}
	}

	public function getStatusMobile() {
		if (!$this->isLoggedIn())
			return "<a href=\"/en/users/login/\">Login</a>";
		else
			return "<a href=\"/en/users/logout/\">Logout</a>";
	}

	public function getPMBlockList() {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT * FROM pm_blocklist WHERE user_id = '.$this->user_id);

		while ($row = $rs->getRow()) {
			$bits = array();
			$puser = new User($row['blocked_id']);
			$bits[] = $puser->getNickname();
			$bits[] = 'Blocked';
			$bits[] = '<a href="/en/users/pm_toggleblock/'.$row['blocked_id'].'/">Unblock</a>';
			$blocked[] = HTMLHelper::wrapArrayInUl($bits);
		}

		return HTMLHelper::wrapArrayInUl($blocked);
	}

	public function getPMBlockListIDs() {
		$blocked = array();
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT *
							FROM pm_blocklist
							WHERE user_id = '.$this->user_id);

		while ($row = $rs->getRow())
			$blocked[] = $row['blocked_id'];

		return $blocked;
	}

	public function unPMBlock($blocked_id) {
		$db = new DatabaseQuery;
		$db->execute("	DELETE FROM pm_blocklist
						WHERE user_id = $this->user_id
						AND blocked_id = $blocked_id");
	}

	public function PMBlock($blocked_id) {
		$db = new DatabaseQuery;
		$db->execute("	INSERT INTO pm_blocklist (user_id, blocked_id)
						VALUES ($this->user_id, $blocked_id)");
	}

	public function getAvatarFull() {
		if (file_exists($_SERVER['DOCUMENT_ROOT'].'/images/avatars/'.$this->user_id.'.jpg') || 1==1) {
			return '<div style="background-color: #f00; height:150px; width:150px; float: right;">avatar</div>';
		}
	}

	public function getNumberForumPosts($force = false) {
		global $user, $model;
		if ($force or !isset($this->number_forum_posts)) {
			$this->number_forum_posts = (int) $model->db()->query('bb_posts', array('user_id' => $this->getUserID(), 'live' => 1),
				array(
					'join' => array('table' => 'bb_threads', 'type' => 'LEFT', 'where' => array('live' => 1), 'on' => array('thread_id', 'thread_id')),
					'getFields' => 'count(*) AS count',
					'selectField' => 'count'
				)
			);
		}

		return $this->number_forum_posts;
	}

	private function getNumberComments() {
		if (!isset($this->number_comments)) {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT COUNT(*) AS tally
							   FROM blog_comments
							   WHERE user_id = $this->user_id
							   AND live = 1");
			$row = $rs->getRow();
			$this->number_comments = $row['tally'];
		}

		return $this->number_comments;
	}

	private function getNumberClassifieds() {
		if (!isset($this->number_classifieds)) {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT COUNT(*) AS tally
							   FROM classifieds_data
							   WHERE user_id = $this->user_id
							   AND status = 1");
			$row = $rs->getRow();
			$this->number_classifieds = $row['tally'];
		}

		return $this->number_classifieds;
	}

	public function getNumberReviews() {
		if (!isset($this->number_reviews)) {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT COUNT(*) AS tally
							   FROM listings_reviews r
							   LEFT JOIN listings_data d ON (r.listing_id = d.listing_id)
							   WHERE r.user_id = $this->user_id
							   AND r.live = 1
							   AND d.status = 1");
			$row = $rs->getRow();
			$this->number_reviews = $row['tally'];
		}

		return $this->number_reviews;
	}
	
	public function getReviewProfile() {
		$stars = array();
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT stars, COUNT(*) AS tally
							FROM listings_reviews
							WHERE stars != -1
							AND live = 1
							AND user_id = $this->user_id
							GROUP BY stars");
		while ($row = $rs->getRow()) {
			$stars[] = $row['stars'].' star'.($row['stars'] == 1 ? '' : 's').': '.$row['tally'];
		}
		return '<div class="star_profile">'.implode('<br />', $stars).'</div>';
	}
	
	private function getStarsAwarded() {
		if (!isset($this->stars_awarded)) {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT SUM(stars) AS tally
							   FROM listings_reviews r
							   LEFT JOIN listings_data d ON (r.listing_id = d.listing_id)
							   WHERE r.user_id = $this->user_id
							   AND r.live = 1
							   AND r.stars != -1
							   AND d.status = 1");
			$row = $rs->getRow();
			$this->stars_awarded = $row['tally'];
		}

		return $this->stars_awarded;
	}
	
	private function getNumberStarredReviews() {
		if (!isset($this->starred_reviews)) {
			$db = new DatabaseQuery;
			$rs = $db->execute("SELECT COUNT(*) AS tally
							   FROM listings_reviews r
							   LEFT JOIN listings_data d ON (r.listing_id = d.listing_id)
							   WHERE r.user_id = $this->user_id
							   AND r.live = 1
							   AND r.stars != -1
							   AND d.status = 1");
			$row = $rs->getRow();
			$this->starred_reviews = $row['tally'];
		}

		return $this->starred_reviews;
	}
	
	public function getAverageStarsAwarded() {
		if ($this->getNumberStarredReviews() > 0)
			return $this->getStarsAwarded() / $this->getNumberStarredReviews();
	}

	public function getPMDashboardNotification() {
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT COUNT(*) AS tally
							FROM pm_messages
							WHERE to_id = ".$this->getUserID()."
							AND NOT status & 4
							AND NOT status & 2");
		$row = $rs->getRow();
		if ($row['tally'] > 0)
			return ' ('.$row['tally'].' New)';
	}
	
	public function getAdminForm() {
		// calculate checked statuses
		foreach ($this->getStatusArray() as $key => $text)
			$checked_statuses[] = $key & $this->status;
		$content .= "
			<style type=\"text/css\">
				fieldset li { display: block; clear: both; }
				label { width: 141px; }
			</style>
		";
		$content .= FormHelper::open('form_user_proc.php');
		$content .= FormHelper::hidden('user_id', $this->user_id);
		$content .= FormHelper::submit();
		
		$f[] = FormHelper::element('&nbsp;', "<a style=\"color: blue;\" href=\"/en/users/profile/" . $this->getUserID() . "/\" target=\"_blank\">View user site profile</a>");
		$f[] = FormHelper::element('Nickname', $this->nickname);
		$f[] = FormHelper::element('Given name', $this->given_name);
		$f[] = FormHelper::element('Family name', $this->family_name);
		$f[] = FormHelper::element('Registered', $this->ts_registered);
		$f[] = FormHelper::element('Verified', $this->verified ? 'Yes' : 'No');
		$f[] = FormHelper::element('Sent verification email', $this->verification_sent ? 'Yes' : 'No');
		$f[] = FormHelper::element('Email', '<a style="color: blue;" href="mailto:' . $this->email . '?subject=GoKunming&body=Hello%20' . trim(ucfirst($this->given_name) . '%20' . ucfirst($this->family_name)) . ',">' . $this->email . '</a>');
		$f[] = FormHelper::checkbox_array('Status', 'status', $this->getStatusArray(), $checked_statuses, array('disabled' => array(2)));
		$content .= FormHelper::fieldset('User Information', $f);
		
		$f[] = FormHelper::element('DEV', $this->getForumThreadSubscriptions());
		$content .= FormHelper::fieldset('Forum Subscriptions', $f);
			
		$content .= FormHelper::submit();
		$content .= FormHelper::close();
		return $content;
	}
	
	private function getForumThreadSubscriptions() {
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT *
							FROM bb_subscriptions
							WHERE user_id = '.$this->user_id);
		while ($row = $rs->getRow()) {
			$content .= '<pre>'.print_r($row, true).'</pre>';
		}
		return $content;
	}
	
	private function getStatusArray() {
		return array (	1 => 'Live [i.e. unbanned]',
						2 => 'Imported',
						4 => 'Subscribed to eNews',
						8 => 'Classifieds - can post straight-to-live');
		}
}
?>
