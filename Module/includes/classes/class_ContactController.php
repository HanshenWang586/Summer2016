<?php
class ContactController {
	public function index() {
		global $user, $model;

		$p = new Page();
		$p->setTag('page_title', 'Contact us');
		$body = '<h1 class="dark">Contact us</h1>';

		$form = isset($_SESSION['gc_form']) ? $_SESSION['gc_form'] : new GeneralContactForm;
		$form->setAction('/en/contact/proc_contact/');
		$body .= $form->display();
		unset($_SESSION['gc_form']);
		$social = new Social;
		$body .= sprintf('<h2>%s</h2>', $model->lang('FIND_US_ON')) . $social->getLinkList();
		$p->setTag('main', $body);
		$p->output();
	}

	public function proc_contact() {
		global $model, $user;
		
		$form = new GeneralContactForm;
		$form->setData($_POST);
		$exists_validator = new ExistenceValidator($form);
		if (!$user->isLoggedIn()) {
			if ($form->getDatum('security_code') != $_SESSION['security_code']) {
				$form->addError('The entered security code was incorrect');
			}
			$email_validator = new EmailValidator($form);
			$email_validator->validate('email', 'Please enter your email address');
			$exists_validator->validate('name', 'Please enter your name');
		}
		
		$lv = new LengthValidator($form);
		$lv->setMinLength(3);
		$lv->setMaxLength(50);
		$lv->validate('subject', 'The subject must be between 3 and 50 characters');
		
		$message = $form->getDatum('message');
		
		if (strpos($message, '[url=') > -1 or strpos($message, '[link=') > -1) $form->addError('Please avoid using encoded links in your message.');
		
		$exists_validator->validate('message', 'Please enter a message');
		
		if (!$form->getErrorCount()) {
			$form->set_files_data($_FILES);
			$form->processForm();
			$form->setSuccessMessage('Thank you! The '.$model->lang('SITE_NAME').' Team will be in touch soon.');
		}
		
		$_SESSION['gc_form'] = $form;
		HTTP::redirect('/en/contact/');
	}
}
?>