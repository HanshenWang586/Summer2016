<?php
class UsersController {
	
	public function autocomplete() {
		global $model, $user;
		
		if (!$user->isLoggedIn()) die();
		
		$search = trim(strip_tags($model->args['q']));
		
		if (!$search) die();
		
		$results = $model->db()->query('public_users', array(sprintf("!status & 1 AND nickname LIKE '%s%%'", $model->db()->escape_clause($search))), array('getFields' => 'user_id AS id, nickname AS text'));
		
		JSONOut($results);
	}
	
	public function index() {
		global $user, $model;
		if ($user->isLoggedIn()) HTTP::redirect($model->url(array('m' => 'users', 'view' => 'dashboard')));
		else HTTP::redirect($model->url(array('m' => 'users', 'view' => 'login')));
	}
	
	public function register() {
		global $model, $user;
		if ($user->isLoggedIn()) HTTP::redirect($model->url(array('m' => 'users', 'view' => 'dashboard')));
		
		$p = new Page();
		$p->setTag('page_title', "Register");
	
		$body = sprintf("<h1 class=\"dark\">%s</h1>", $model->lang('REGISTER_NEW_ACCOUNT'));
		$body .= sprintf('<div id="controls"><a class="icon-link" href="%s"><span class="icon icon-arrow-left"> </span> %s</a></div>', $model->url(array('m' => 'users', 'view' => 'login')), $model->lang('BACK_TO_LOGIN'));
	
		$form = isset($_SESSION['register_form']) ? $_SESSION['register_form'] : new RegisterForm;
		$body .= $form->display();
	
		unset($_SESSION['register_form']);
		$p->setTag('main', $body);
		$p->output();
	}

	public function proc_register() {
		global $model;
		$form = new RegisterForm;
		$form->setData($_POST);

		$exists_validator = new ExistenceValidator($form);
		$exists_validator->validate('nickname', '- please enter a nickname');
		$exists_validator->validate('given_name', '- please enter your first name');
		$exists_validator->validate('family_name', '- please enter your last name');
		$exists_validator->validate('area_id', '- please select your location');

		$email_validator = new EmailValidator($form);
		$email_validator->validate('email', '- please enter a valid email address');

		$len_validator = new LengthValidator($form);
		$len_validator->setMinLength(5);
		$len_validator->setMaxLength(18);
		$len_validator->validate('nickname', '- please enter nickname of 5 to 18 characters');

		$len_validator = new LengthValidator($form);
		$len_validator->setMinLength(7);
		$len_validator->validate('password', '- please enter a password longer than 6 characters');

		$equ_validator = new EqualityValidator($form);
		$equ_validator->validate('password', 'password2', '- please enter your password carefully');

		$db_validator = new DBValidator($form);
		$db = new DatabaseQuery;
		$db_validator->validate(	"	SELECT *
										FROM public_users
										WHERE nickname='".$db->clean($_POST['nickname'])."'",
									'- sorry, that nickname has already been chosen - please enter another');
		
		if (!$this->checkDeaBlocklist($form->getDatum('email'))) {
			$form->addError('- Disposable email accounts are not accepted');
		}
		
		if ($form->getDatum('family_name') == $form->getDatum('given_name')) {
			if ($form->getDatum('nickname') == $form->getDatum('family_name')) {
				$form->addError('- Your nickname, first name and last name cannot be the same');
			} else {
				$form->addError('- Your first name and last name cannot be the same');
			}
		}
		
		if ($form->getDatum('security_code') != $_SESSION['security_code']) {
			$form->addError('- The security code you entered was incorrect');
		}
		
		$db_validator = new DBValidator($form);
		$db_validator->validate(	"	SELECT *
										FROM public_users
										WHERE email = '".$db->clean($_POST['email'])."'
										AND NOT status & 2
										AND email != ''",
									'- sorry, that email address is already registered');

		if (
			!$form->getErrorCount()
			and $user = $form->processForm()
		) {
			if ($user->sendVerifyEmail()) {
				$form->setSuccessMessage('<p class="green"><strong>Your account has been created!</strong></p><p>Please verify it by clicking the <strong>activation link</strong> that has been sent to your email.</p><p>If you have not received any email, please check your email\'s SPAM/JUNK box.</p>');
			} else {
			 // There should be a fallback, like rolling back the changes in the DB and give an error message,
			 // but the code is not ideal to make such changes right now.
			 	$id = $user->getUserID();
			 	if ($id) $GLOBALS['model']->db()->delete('public_users', array('user_id' => $id));
				$form->addError('Something went wrong while creating your new user. Please contact us through the <a href="/en/contact/">contact form</a> to report the error.');
			}
		}

		$_SESSION['register_form'] = $form;
		HTTP::redirect('/en/users/register/');
	}
	
	// Checks if the domain name used for the email address is blocked
	private function checkDeaBlocklist($email) {
		$db = $GLOBALS['model']->db();
		
		list($username, $domain) = explode('@', $email);
		$domain = strtolower(trim($domain));
		
		// Simple check – if we find the domain then we have a positive.
		if ($db->query('blocklist_dea', array('domain' => $domain))) return false;
		
		// Tough check to prevent subdomains of blocked domains from passing
		$domains = $db->query('blacklist_dea', false, array('transpose' => 'domain'));
		foreach ($domains as $dom) if (strpos($domain, $dom) !== false) return false;
		
		// If the tests above didn't return false, we hopefully have a valid email account
		return true;
	}
	
	public function forgot() {
		global $user, $model;
		$p = new Page();
		$body = "<h1 class=\"dark\">Forgot Password</h1>";
		$body .= sprintf('<div id="controls"><a class="icon-link" href="%s"><span class="icon icon-arrow-left"> </span> %s</a></div>', $model->url(array('m' => 'users', 'view' => 'login')), $model->lang('BACK_TO_LOGIN'));
		
		if ($_POST and $email = request($_POST['email'])) {
			$user_id = $model->db()->query('public_users', array('email' => $email), array('selectField' => 'user_id'));
			if ($user_id) {
				$user_found = new User($user_id);
				$new_password = $user_found->generatePassword();
				$message = "Hi $user_found->given_name!\n\n".
				"Your new ".$model->lang('SITE_NAME')." password is $new_password. You can login with this and then".
				" change it to a password of your choice at " . $model->urls['root'] .  "/en/users/change/\n\n".
				"Regards, The ".$model->lang('SITE_NAME')." Team";
				
				$mailer = new Mailer();
				$domain = $model->module('preferences')->get('emailDomain');
				$success = $mailer->send(array(
					'subject' => $model->lang('SITE_NAME') . ' – Retrieve Password',
					'content' => $message,
					'from' => array('info@'.$domain => $model->lang('SITE_NAME')),
					'to' => array($user_found->email => trim($user_found->given_name . ' ' . $user_found->family_name)),
					'bcc' => 'bitbucket@'.$domain
				));
				
				if ($success) $body .= '<p class="pageWrapper infoMessage">A new password has been sent to "' . $user_found->email . '". Please also check your JUNK/SPAM box.</p>';
				else $body .= '<p class="error message">Your account was found but we were unable to send you an email. Please try again later.</p>';
			} else $body .= '<p class="error message">The email address you entered could not be found.</p>';
		}
		
		$body .= FormHelper::open('/en/users/forgot/');
		$f[] = FormHelper::input('Email', 'email', request($_POST['email']), array('type' => 'email', 'mandatory' => true, 'placeholder' => 'john.doe@gmail.com'));
		$f[] = FormHelper::submit('Send');
		$body .= FormHelper::fieldset('Retrieve new password', $f);
		$body .= FormHelper::close();

		$p->setTag('page_title', 'Forgot Password');
		$p->setTag('main', $body);
		$p->output();
	}

	public function proc_forgot() {
		global $user, $model;

		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT user_id
							FROM public_users
							WHERE nickname='".$db->clean($_POST['nickname'])."'");

		if ($rs->getNum() == 1) {
			$row = $rs->getRow();
			$user_found = new User($row['user_id']);
			$new_password = $user_found->generatePassword();
			$message = "Hi $user_found->given_name!\n\n".
			"Your new ".$model->lang('SITE_NAME')." password is $new_password. You can login with this (remember to use your email address as username) and then".
			" change it to a password of your choice at " . $model->urls['root'] .  "/en/users/change/\n\n".
			"Regards, The ".$model->lang('SITE_NAME')." Team";

			$smtp = new SMTP;
			$smtp->open();
			$mail = new Mail;
			$mail->setFrom('info@'.$model->module('preferences')->get('emailDomain'), $model->lang('SITE_NAME'));
			$mail->addTo($user_found->email);
			$mail->setSubject($model->lang('SITE_NAME') . ' – Retrieve Password');
			$mail->setMessage($message);
			$smtp->send($mail->getFrom(), $mail->getAllRecipients(), $mail->getData());
			$smtp->quit();

			HTTP::redirect('/en/users/forgot_success/');
		}
		else
			HTTP::redirect('/en/users/forgot_notfound/');
	}

	function forgot_success()
	{
	global $user, $model;

	$p = new Page;

	$body = "<h1 class=\"dark\">Forgot Password - Success</h1>";
	$body .= sprintf('<div id="controls"><a class="icon-link" href="%s"><span class="icon icon-arrow-left"> </span> %s</a></div>', $model->url(array('m' => 'users', 'view' => 'login')), $model->lang('BACK_TO_LOGIN'));
	$body .= "<p class=\"message infoMessage\">We've found you in our records - please check your email for your new password.</p>";

	$p->setTag('main', $body);
	$p->output();
	}

	public function forgot_notfound() {
		global $user;

		$p = new Page;

		$body = "<h1 class=\"dark\">Forgot Password - User Not Found</h1>
		<p class=\"message infoMessage\">Sorry, we're unable to find that nickname in our records.<br>
		<br>
		Please <a href=\"/en/users/forgot/\">click here</a> to go back to the form.</p>";

		$p->setTag('main', $body);
		$p->output();
	}
	
	private function setLoginDestination($ref) {
		if ($ref) {
			$serverParts = parse_url($_SERVER['REQUEST_URI']);
			$parts = parse_url($ref);
			if ($parts['host'] == $_SERVER['HTTP_HOST'] and $parts['path'] != $serverParts['path']) 
				$_SESSION['destination_url'] = $ref;
		}
		
	}
	
	private function getLoginDestination() {
		$result = $_SESSION['destination_url'];
		$_SESSION['destination_url'] = NULL;
		
		return $result ? $result : '/en/users/dashboard/';
	}
	
	public function login() {
		global $user, $model;
		$forward = array_key_exists('forward', $model->args) ? $model->args['forward'] : request($_SERVER['HTTP_REFERER']);
		$this->setLoginDestination($forward);
		$p = new Page();
		$body = $user->getLoginForm();
		$p->setTag('main', $body);
		$p->output();
	}

	public function dashboard() {
		global $user, $model;
		
		if ($user->isLoggedIn()) {
			$p = new Page;
			$body = sprintf('
				<h1 class="dark">Hello, %s</h1>
				<div id="controls">
					<a class="button" href="%s"><span class="icon icon-edit"> </span> %s</a>
					<a class="button" href="%s"><span class="icon icon-edit"> </span> %s</a>
				</div>',
				$user->getNickname(),
				$model->url(array('m' => 'classifieds', 'view' => 'post')),
				$model->lang('POST_NEW_CLASSIFIEDS', 'UsersModel'),
				$model->url(array('m' => 'forums', 'view' => 'post')),
				$model->lang('START_FORUM_THREAD', 'usersModel')
			);
			$body .= '<section id="dashboard" class="row">
				<div class="span4">
					<div class="whiteBox">
						<small>Personal info</small>
						<p>
							Date registered: '.$user->getDateRegistered().'<br>
							Region: '.$user->getRegion().'<br>
							Email: '.$user->getEmail().'<br>
							Verified: ' . ($user->verified ? '<span title="Your email address has been verified" class="green info">Yes</span>' : '<a title="Click here to verify your email address" class="red info" href="/en/users/verify/">No</a>') . '<br>
							eNews: '.($user->isEnewsRecipient() ? 'Subscribed' : 'Not subscribed').'
						</p>
					</div>
					
					<div class="whiteBox">
						<small>Profile</small>
						<p>
							<a href="/en/users/profile/'.$user->getUserID().'/">My Public Profile</a><br>
							<a href="/en/users/change/">Change Password</a><br>
							<a href="/en/users/edit/">Edit Profile</a><br>
							<a href="/en/users/logout/">Logout</a>
						</p>
					</div>
				</div>
				<div class="span4">
					<div class="whiteBox">
						<small>User content</small>
						<p>
							<a href="/en/users/classifieds/">My Classifieds</a><br>
							<a href="/en/users/classifieds_subscriptions/">Classifieds Subscriptions</a><br>
							<a href="/en/users/forums_subscriptions/">Forum Subscriptions</a><br>
						</p>
					</div>
				
					<div class="whiteBox">
						<small>Private messages</small>
						<p>
							<a href="/en/users/pm_inbox/">My Private Messages</a>'.$user->getPMDashboardNotification().'<br>
							<a href="/en/users/pm_sent/">My Sent Private Messages</a><br>
							<a href="/en/users/pm_compose/">Create New Private Message</a><br>
						</p>
					</div>
				</div>
			</section>';
			$p->setTag('main', $body);
			$p->output();
		}
		else
			HTTP::redirect('/en/users/login/');
	}

	public function login_proc() {
		$user = new User;
		
		$user = $user->validate($_POST['email'], $_POST['password']);
		
		if ($user->isLoggedIn()) {
			$_SESSION['user'] = $user;
			$destination = $this->getLoginDestination();
		} else {
			$_SESSION['user'] = new User;
			if ($user->isBanned()) $destination = '/en/users/login/?banned';
			elseif (!$user->isEmailVerified()) $destination = '/en/users/login/?notVerified';
			else $destination = '/en/users/login/?fail';
		}
		
		HTTP::redirect($destination);
	}
	
	// Email Verification page
	public function verify() {
		global $user;
		$email = request($_GET['email']);
		$hash = request($_GET['hash']);
		
		// Set the default view
		$db = $GLOBALS['model']->db();
		
		if ($email && $hash) { // Information is supplied
			$view = new View;
			$result = $db->query('user_verification', array('email' => $email, 'hash' => $hash), array('singleResult' => true, 'orderBy' => 'verified'));
			if ($result) { // Verification details found
				$view->setTag('email', $result['email']);
				if ($result['verified'] != 1) { // Not yet verified
					// Update the user to use the verified email address
					$db->update('public_users', array('user_id' => $result['user_id']), array('verified' => 1, 'verification_sent' => 1, 'email' => $result['email']));
					// Update the user verification table
					$db->update('user_verification', array('user_id' => $result['user_id'], 'email' => $result['email']), array('verified' => 1));
					if ($user->isLoggedIn()) { // if the user is logged in we have to check if we just updated their email
						if ($user->getUserID() == $result['user_id']) {
							$user->reloadData();
							$view->setPath('users/verify_email_updated.html');
						} else {
							$nickname = $db->query('public_users', array('user_id' => $result['user_id']), array('selectField' => 'nickname'));
							$view->setTag('nickname', $nickname);
							$view->setPath('users/verify_other_email_updated.html');
						}
					} else $view->setPath('users/verify_verified.html');
				} else $view->setPath('users/verify_already_verified.html');
			} else $view->setPath('users/verify_not_valid.html');
			$content = $view->getOutput();
		} else { // if no information is given, the user might want to request a new verification code
			$loggedIn = $user->isLoggedIn();
			$content = '<h1 class="dark">Verify your ' . ($loggedIn ? 'email address' : 'account') . '</h1>';
			if (isset($_GET['fail'])) {
				$content .= $loggedIn ?
					"<p class=\"message error\">Please enter a valid email address and your current password.</p>" :
					"<p class=\"message error\">We did not recognize the email and password combination you entered. Please try again.</p>";
			}
			if (isset($_GET['success'])) {
				$content .= $loggedIn ?
					"<p class=\"message infoMessage\">Thank you!<br><br>A verification link has been has been sent to you by email. Please follow the supplied instructions to verify your email address.<br><br>If you have not received any email, please check your email's SPAM/JUNK box.</p>" :
					"<p class=\"message infoMessage\">An activation link has been has been sent to you by email. Please follow the supplied instructions to activate your account.<br><br>If you have not received any email, please check your email's SPAM/JUNK box.</p>";	
			}
			if (isset($_GET['alreadyVerified'])) {
				$content .=  "<p class=\"message infoMessage\">Thank you!<br><br>This email address has already been verified. No further action is required.</p>";
			}
			$content .= "<div class=\"message\">";
			if ($loggedIn) $content .= "<p>In the near future " . $GLOBALS['model']->lang('SITE_NAME') . " will require all users to verify their email address to prevent fake account registrations and spam.</p>";
			$content .= '</div>';
			$content .= FormHelper::open('/en/users/verify_proc/');
			$f[] = FormHelper::input('Email', 'email', $loggedIn ? $user->email : $email, array(
																							'mandatory' => true,
																							'guidetext' => $loggedIn ?
																								'Please supply a valid email address to use with your ' . $GLOBALS['model']->lang('SITE_NAME') . ' account.' :
																								'Please enter the email address you signed up with.'
																						));
			$f[] = FormHelper::password('Password', 'password', '', array(
																		'mandatory' => true,
																		'guidetext' => 'Please enter your password.'
																	));
			$f[] = FormHelper::submit('Go!');
			$content .= FormHelper::fieldset('', $f);
			$content .= FormHelper::close();
		}
		
		$p = new Page;	
		$p->setTag('main', $content);
		$p->output();
	}
	
	public function verify_proc() {
		$redirect = '/en/users/verify/';
		// See if an email address and password are given. If not, no action has been taken.
		if ($email = request($_POST['email']) and $password = request($_POST['password'])) {
			if ($GLOBALS['user']->isLoggedIn()) { // If the user is logged in we need to validate their password
				global $user;
				if ($password == $user->password and validateEmail($email)) {
					// If their email address is already validated we don't need to do anything
					if (
						$email = $user->email and
						$user->verified and
						$GLOBALS['model']->db()->query('user_verification', array('email' => $email, 'user_id' => $user->getUserID(), 'verified' => 1))
					) $redirect .= '?alreadyVerified';
					else { // Request a change of email address if it's not validated or if it's changed
						$user->requestChangeEmail($email);
						$redirect .= '?success';
					}
				} else $redirect .= '?fail';
			} else { // If not logged in, let's check if the user exists	
				$user = new User;
				$user = $user->validate($_POST['email'], $_POST['password']);
				if ($user->getUserID()) {
					// If the user exists, see if they're banned or already verified
					if ($user->isBanned()) $redirect = '/en/users/login/?banned';
					elseif ($user->isEmailVerified()) {
						$_SESSION['user'] = $user;
						$redirect = '/en/users/dashboard/';
					} else { // If not banned or verified, send new verification email
						$user->sendVerifyEmail();
						$redirect .= '?success';
					}
				} else $redirect .= '?fail';
			}
		} else $redirect .= '?fail';
		
		HTTP::redirect($redirect);
	}

	public function logout() {
		// Unset all of the session variables.
		$_SESSION = array();

			// If it's desired to kill the session, also delete the session cookie.
			// Note: This will destroy the session, and not just the session data!
			if (isset($_COOKIE[session_name()])) {
				setcookie(session_name(), '', time()-42000, '/');
			}

		// Finally, destroy the session.
		session_destroy();
		
		if (!$ref = request($_SERVER['HTTP_REFERER'])) $ref = $GLOBALS['model']->url();
		
		HTTP::redirect($ref, 0);
		exit;
	}

	public function profile($id = false) {
		global $user, $site, $model;
		
		if (!$id or !is_numeric($id)) HTTP::throw404(); 
		
		$puser = $site->getUser($id);

		if ($id = $puser->getUserID() and !$puser->isBanned()) {
			$p = new Page();
			
			$title = $model->lang('USER_PROFILE', 'UsersModel', false, true) . ': ' . $puser->getNickname(false, true);
			
			$view = new View('users/public_profile.html');
			
			$view->setTag('user', $puser);
			$view->setTag('title', $title);
			
			$pager = new Pager('commentsPage', 'user_comments');
			$pager->setLimit(5);
			$bcl = new BlogCommentsList;
			$view->setTag('comments', $bcl->getComments($pager, $id));
			
			$cl = new ClassifiedsList;
			$pager->reset('ClassifiedsPage', 'user_classifieds');
			$view->setTag('classifieds', $cl->getClassifieds($pager, $id));
			
			$fpl = new ForumPostsList;
			$pager->reset('forumsPage', 'user_forums');
			$view->setTag('forumPosts', $fpl->getAll($pager, $id));
			
			$rl = new ReviewList;
			$pager->reset('reviewsPage', 'user_reviews');
			$view->setTag('reviews', $rl->getReviews($pager, $id, true));
			
			$cal = new Calendar;
			$view->setTag('events', $cal->sprintEvents($cal->getUserEvents($id), 'multiple-days'));
			
			$p->setTag('page_title', $title);
			$p->setTag('main', $view->getOutput());
			$p->output();
			
			/*
			$puser->getNumberForumPosts();
			$puser->getNumberComments();
			$puser->getNumberClassifieds();
			$puser->getNumberReviews();
			*/
			
			$p->setTag('page_title', strip_tags($puser->getNickname())."'s User Profile");
			$p->setTag('main', $body);
			$p->output();
		}
		else
			HTTP::throw404();
	}

	public function banned() {
		$p = new Page();

		$body = '<h1 class="dark">Banned</h1>
		<p class="message error">You have tried to login as a banned user. Please address any queries
		about this matter to us <a href="/en/contact/">here</a>.</p>';

		$p->setTag('main', $body);
		$p->output();
	}

	public function change() {
		global $user, $model;
		
		if (!$user->isLoggedIn()) HTTP::disallowed();
		
		$p = new Page();
		$body = sprintf('<h1 class="dark">Change Password</h1>
				<div id="controls">
					<a class="icon-link" href="%s"><span class="icon icon-arrow-left"> </span> %s</a>
				</div>',
					$model->url(array('m' => 'users', 'view' => 'dashboard')), $model->lang('BACK_TO_DASHBOARD', 'UsersModel'));

		$form = isset($_SESSION['cp_form']) ? $_SESSION['cp_form'] : new ChangePasswordForm;
		$form->setAction('/en/users/proc_change/');
		$body .= $form->display();
		unset($_SESSION['cp_form']);
		
		$p->setTag('page_title', 'Change Password');
		$p->setTag('main', $body);
		$p->output();
	}

	public function proc_change() {
		global $user;

		$data = $_POST;
		$data['user_id'] = $user->getUserID();

		$form = new ChangePasswordForm;
		$form->setData($data);

		$exists_validator = new ExistenceValidator($form);
		$length_validator = new LengthValidator($form);
		$equality_validator = new EqualityValidator($form);

		$length_validator->setMinLength(6);

		$exists_validator->validate('pw_1', '- please enter a new password');
		$exists_validator->validate('pw_2', '- please confirm your new password');

		$length_validator->validate('pw_1', '- please make your new password 6 characters or longer');
		$equality_validator->validate('pw_1', 'pw_2', '- please check that the password and the confirmation match');

		if (!$form->getErrorCount()) {
			$form->processForm();
			$form->setSuccessMessage('Your password has been successfully updated.');
		}

		$_SESSION['cp_form'] = $form;
		HTTP::redirect('/en/users/change/');
	}

	public function classifieds() {
		global $user, $model;
		
		if (!$user->isLoggedIn()) HTTP::disallowed();
		
		$p = new Page();
		$body = '<h1 class="dark">'.$user->getNickname().'\'s Classifieds</h1>';
		$body .= sprintf('
			<div id="controls">
				<a class="button" href="%s"><span class="icon icon-edit"></span>%s</a>
				<a class="icon-link" href="%s"><span class="icon icon-arrow-left"> </span> %s</a>
			</div>',
			$model->url(array('m' => 'classifieds', 'view' => 'post')),
			$model->module('lang')->get('ClassifiedsModel', 'LINK_NEW'),
			$model->url(array('m' => 'users', 'view' => 'dashboard')),
			$model->lang('BACK_TO_DASHBOARD', 'UsersModel')
		);
		
		$cl = new ClassifiedsList;
		$pager = new Pager;
		$body .= $cl->getClassifieds($pager, $user->getUserID(), true);

		$p->setTag('main', $body);
		$p->output();
	}

	public function pm_message($pm_id = false) {
		global $user;
		
		if (!$pm_id or !is_numeric($pm_id)) HTTP::throw404();
		if (!$user->isLoggedIn()) HTTP::disallowed();
		
		$pm = new PrivateMessage($pm_id);
		
		$p = new Page;
			
		if (!$pm->getToID()) {
			$view = new View('pm/folder.html');
			$view->setTag('title',  'Private Messages - Message Unavailable');
			$view->setTag('content',  'The Private Message you are trying to view does not exist.');
			$p->setTag('main', $view->getOutput());
		} elseif ($pm->getToID() == $user->getUserID() || $pm->getFromID() == $user->getUserID()) {
			$view = new View;
			$view->setPath('pm/message.html');
			$reply_id = $pm->getReplyPMID();
			$view->setTag('title',  'Private Messages - Viewing Message');
			$view->setTag('reply_status', $reply_id ? '<p>You\'ve replied this message. <a href="/en/users/pm_message/'.$reply_id.'/">View</a>.</p>' : '');
			$body = $view->getOutput();
			
			$view = new View;
			if ($pm->getToID() == $user->getUserID())
				$view->setTag('reply', '<a class="icon-link" href="/en/users/pm_compose/'.$pm->getPrivateMessageID().'/"><span class="icon icon-envelope-3"> </span> Reply</a>');
			$view->setPath('pm/stripped_message.html');
			$view->setTag('pm_id',  $pm->getPrivateMessageID());
			$view->setTag('subject',  $pm->getSubject());
			$view->setTag('date',  $pm->getDate());
			$view->setTag('message',  $pm->getMessage());
			$view->setTag('from_name',  $pm->getFromName());
			$view->setTag('to_name',  $pm->getToName());

			$body .= $view->getOutput();
			$pm->markAsRead();
			while ($pm->getReplyToID()) {
				$body .= "<h3>In reply to</h3>";
				$pm = new PrivateMessage($pm->getReplyToID());
				$view = new View;
				$view->setPath('pm/stripped_message.html');
				$view->setTag('pm_id',  $pm->getPrivateMessageID());
				$view->setTag('subject',  $pm->getSubject());
				$view->setTag('date',  $pm->getDate());
				$view->setTag('message',  $pm->getMessage());
				$view->setTag('from_name',  $pm->getFromName());
				$view->setTag('to_name',  $pm->getToName());
				$body .= $view->getOutput();
			}

			$p->setTag('main', $body);
		} else {
			$view = new View('pm/folder.html');
			$view->setTag('title',  'Private Messages - Message Unavailable');
			$view->setTag('content',  'The Private Message you are trying to view is not sent to or by you.');
			$p->setTag('main', $view->getOutput());
		}
		$p->output();
	}

	public function pm_compose($replyToID = false) {
		global $user;
		
		if (!$user->isLoggedIn()) HTTP::disallowed();

		$p = new Page;
		$pm = new PrivateMessage;
		
		if ($replyToID) {
			$replying_to = new PrivateMessage($replyToID);
			if (!$replying_to->getToID()) HTTP::throw404();
			if ($replying_to->getToID() == $user->getUserID()) {
				$view = new View;
				$view->setPath('pm/folder.html');
				$view->setTag('title',  'Reply Private Message');
				$view->setTag('content',  $pm->getReplyForm($replying_to));
				$body .= $view->getOutput();
			}
		}
		else {
			$view = new View;
			$view->setPath('pm/folder.html');
			$view->setTag('title',  'New Private Message');
			$view->setTag('content',  $pm->getComposeForm($_GET['to_id']));
			$body .= $view->getOutput();
		}

		$p->setTag('main', $body);
		$p->output();
	}

	public function pm_inbox() {
		global $user;

		if ($user->isLoggedIn()) {
			$p = new Page;
			$pml = new PrivateMessageList;

			$view = new View;
			$view->setPath('pm/folder.html');
			$view->setTag('title', 'Private Messages - Inbox');
			$pager = new Pager();
			$view->setTag('content', $pml->getInbox($pager));
			$body .= $view->getOutput();

			$p->setTag('main', $body);
			$p->output();
		}
		else
			HTTP::redirect('/en/users/login/');
	}
	
	public function pm_delete($id = false) {
		global $user;

		if (!$user->isLoggedIn()) HTTP::disallowed();
		if (!$id or !is_numeric($id)) HTTP::throw404();
		
		$pm = new PrivateMessage($id);
		$result = $pm->delete();
		if (!$result) HTTP::redirect($pm->getURL());
		elseif ($result == 'sender') HTTP::redirect('/en/users/pm_sent/');
		else HTTP::redirect('/en/users/pm_inbox/');
	}

	public function pm_sent() {
		global $user;

		if ($user->isLoggedIn()) {
			$p = new Page;
			$pml = new PrivateMessageList;

			$view = new View;
			$view->setPath('pm/folder.html');
			$view->setTag('title',  'Private Messages - Sent');
			$pager = new Pager;
			$view->setTag('content',  $pml->getSent($pager));
			$body .= $view->getOutput();

			$p->setTag('main', $body);
			$p->output();
		}
		else
			HTTP::redirect('/en/users/login/');
	}

	public function pm_send() {
		global $user;

		//TODO set up proper form checking
		if ($user->isLoggedIn() && ctype_digit($_POST['to_id']) && $_POST['subject'] != '') {
			$pm = new PrivateMessage;
			$data = $_POST;
			$data['from_id'] = $user->getUserID();
			$pm->setData($data);
			$pm->save();
			HTTP::redirect('/en/users/pm_sent/');
		}
		else
			HTTP::redirect('/en/users/login/');
	}

	public function pm_recipients() {
		global $user;
		$ss = urldecode(func_get_arg(0));
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT user_id, nickname
							FROM public_users
							WHERE nickname LIKE '".$db->clean($ss)."%'
							AND status & 1
							AND user_id != ".$user->getUserID()."
							AND NOT status & 2
							ORDER BY LENGTH(nickname) ASC
							LIMIT 20");

		if ($rs->getNum()) {
			while ($row = $rs->getRow()) {
				$items[] = "<a href=\"#\" onclick=\"addPrivateMailRecipient({$row['user_id']}, '{$row['nickname']}');return false;\">{$row['nickname']}</a>";
			}
		}
		else
			$items[] = '[no matches found]';

		echo HTMLHelper::wrapArrayInUl($items);
	}

	public function pm_users() {
		global $user;
		$ss = urldecode(func_get_arg(0));
		$db = new DatabaseQuery;
		$rs = $db->execute("SELECT user_id, nickname
							FROM public_users
							WHERE nickname LIKE '".$db->clean($ss)."%'
							AND status & 1
							AND NOT status & 2
							ORDER BY LENGTH(nickname) ASC
							LIMIT 20");

		if ($rs->getNum()) {
			while ($row = $rs->getRow()) {
				$bits = array();
				$blocked = in_array($row['user_id'], $user->getPMBlockListIDs());
				$bits[] = $row['nickname'];
				$bits[] = $blocked ? 'Blocked' : 'Not blocked';
				$bits[] = "<a href=\"/en/users/pm_toggleblock/{$row['user_id']}/\">".($blocked ? 'Unblock' : 'Block').'</a>';
				$items[] = HTMLHelper::wrapArrayInUl($bits);
			}
		}
		else
			$items[] = '[no matches found]';

		echo HTMLHelper::wrapArrayInUl($items);
	}

	public function pm_toggleblock() {
		global $user;

		if ($user->isLoggedIn()) {
			$other_user_id = func_get_arg(0);
			if (in_array($other_user_id, $user->getPMBlockListIDs()))
				$user->unPMBlock($other_user_id);
			else
				$user->PMBlock($other_user_id);
			HTTP::redirect('/en/users/pm_blocklist/');
		}
		else
			HTTP::redirect('/en/users/login/');
	}

	public function classifieds_subscriptions() {
		global $user, $model;

		if ($user->isLoggedIn()) {
			$p = new Page;
			$body = sprintf('
				<h1 class="dark">Classifieds subscriptions</h1>
				<div id="controls">
					<a class="icon-link" href="%s"><span class="icon icon-arrow-left"> </span> %s</a>
				</div>
				<div id="dashboard_subscriptions">',
					$model->url(array('m' => 'users', 'view' => 'dashboard')), $model->lang('BACK_TO_DASHBOARD', 'UsersModel'));


			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT f.*
							   FROM classifieds_subscriptions s, classifieds_folders f
							   WHERE s.folder_id = f.folder_id
							   AND user_id = '.$user->getUserID());

			if ($rs->getNum() != 0) {
				$body .= '<p>You are subscribed to the following classified ads categories:</p><ul class="row forumSubscriptions">';
				$cf = new ClassifiedsFolder;
				while ($row = $rs->getRow()) {
					$cf->setData($row);
					$body .= sprintf(
						'<li class="span4">
							<h2>%s</h2>
							<a href="/en/classifieds/subscribe/%d/?from=dashboard">Unsubscribe</a>
						</li>',
							$cf->getPath(),
							$cf->getFolderID()
						);
				}
				$body .= "</ul>";
			}
			else
				$body .= '<p class="infoMessage message">You are not subscribed to any classified ads categories.</p>';

			$body .= HTMLHelper::wrapArrayInUl($items).'</div>';
			$p->setTag('main', $body);
			$p->output();
		}
		else
			HTTP::redirect('/en/users/login/');
	}

	public function forums_subscriptions() {
		global $user, $model;

		if ($user->isLoggedIn()) {
			$p = new Page;
			$body = sprintf('
				<h1 class="dark">Forum Subscriptions</h1>
				<div id="controls">
					<a class="icon-link" href="%s"><span class="icon icon-arrow-left"> </span> %s</a>
				</div>
				<div id="dashboard_subscriptions">',
					$model->url(array('m' => 'users', 'view' => 'dashboard')), $model->lang('BACK_TO_DASHBOARD', 'UsersModel'));
			
			$db = new DatabaseQuery;
			$rs = $db->execute('SELECT t.*
							   FROM bb_subscriptions s, bb_threads t
							   WHERE s.thread_id = t.thread_id
							   AND s.user_id = '.$user->getUserID().'
							   ORDER BY t.ts DESC');

			if ($rs->getNum() != 0) {
				$body .= '<p>You are subscribed to the following forum threads:</p><ul class="row forumSubscriptions">';
				$thread = new ForumThread;
				while ($row = $rs->getRow()) {
					$thread->setData($row);
					$body .= sprintf(
						'<li class="span4">
							<h2><a class="title" href="%s">%s</a></h2>
							<span class="lastPost">Latest post: %s</span>
							<a href="/en/forums/subscribe/%d/?from=dashboard">Unsubscribe</a>
						</li>',
							$thread->getURL(),
							$thread->getTitle(),
							$thread->getLatestPostingDate(),
							$thread->getThreadID()
						);
				}
				$body .= "</ul>";
			}
			else
				$body .= '<p class="message infoMessage">You are not subscribed to any forum threads.</p>';

			$body .= HTMLHelper::wrapArrayInUl($items).'</div>';
			$p->setTag('main', $body);
			$p->output();
		}
		else
			HTTP::redirect('/en/users/login/');
	}

	public function edit() {
		global $user, $model;

		if ($user->isLoggedIn()) {
			$p = new Page;
			$body .= sprintf('
				<h1 class="dark">Edit Profile</h1>
				<div id="controls">
					<a class="icon-link" href="%s"><span class="icon icon-arrow-left"> </span> %s</a>
				</div>',
					$model->url(array('m' => 'users', 'view' => 'dashboard')), $model->lang('BACK_TO_DASHBOARD', 'UsersModel'));

			$form = isset($_SESSION['user_profile_form']) ? $_SESSION['user_profile_form'] : new UserProfileForm;
			$body .= $form->display();
			unset($_SESSION['user_profile_form']);

			$p->setTag('main', $body);
			$p->output();
		}
		else
			HTTP::redirect('/en/users/login/');

	}

	public function edit_proc() {
		global $user;

		$form = new UserProfileForm;
		$form->setData($_POST);

		$exists_validator = new ExistenceValidator($form);
		$exists_validator->validate('area_id', '- please select your region');
		
		$email_validator = new EmailValidator($form);
		$email_validator->validate('email', '- please enter a vaild email address');
		
		$db = new DatabaseQuery;
		
		$db1 = $GLOBALS['model']->db();
		$query = $db1->get_query('public_users', array('user_id' => $user->getUserID(), 'password' => $_POST['password']));
		
		$db_validator = new DBValidator($form);
		$db_validator->validate($query, '- You need to enter your current password to change your profile information.', true);
		
		if ($_POST['email'] != $user->getEmail()) { // only check the email address against rest of db if it's actually been edited
			$db_validator->validate(	"	SELECT *
											FROM public_users
											WHERE email = '".$db->clean($_POST['email'])."'
											AND NOT status & 2
											AND user_id != ".$user->getUserID()."
											AND email != ''",
										'- sorry, that email address is already registered to another user');
		}
		
		if (!$form->getErrorCount()) {
			$form->processForm();
		}

		$_SESSION['user_profile_form'] = $form;
		HTTP::redirect('/en/users/edit/');
	}
}
?>
