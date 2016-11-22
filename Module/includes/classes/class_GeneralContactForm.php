<?php
class GeneralContactForm extends PrimordialForm {

	public function displayForm() {
		$content = $this->displayErrors();
		$content .= FormHelper::open($this->action, array('file_upload' => true));
		
		if (!$GLOBALS['user']->isLoggedIn()) {
			$f[] = FormHelper::element('&nbsp;', "<img src=\"/scripts/captcha/generate.php\" width=\"120\" height=\"40\" alt=\"captcha\">");
			$f[] = FormHelper::input('Security code', 'security_code', '', array('mandatory' => true));
			$f[] = FormHelper::input('Name', 'name', $this->getDatum('name'), array('mandatory' => true));
			$f[] = FormHelper::input('Email', 'email', $this->getDatum('email'), array('mandatory' => true));
		}
		$subject = $this->getDatum('subject');
		if (!$subject and array_key_exists('subject', $_GET)) $subject = $_GET['subject'];
		$f[] = FormHelper::input('Subject', 'subject', $subject, array('mandatory' => true));
		$f[] = FormHelper::textarea('Message', 'message', $this->getDatum('message'), array('mandatory' => true));
		$f[] = FormHelper::file('Attachment', 'attachment');
		$f[] = FormHelper::submit('Send');

		$content .= FormHelper::fieldset('', $f);
		$content .= FormHelper::close();
		return $content;
	}

	function set_files_data($files) {
		$this->files = $files;
	}

	public function processForm() {
		global $model, $user;
		$smtp = new SMTP;
		$smtp->open();

		$mail = new Mail;
		
		if ($user->isLoggedIn()) {
			$mail->setFrom($user->email, $user->nickname);
		} else {
			$mail->setFrom($this->getDatum('email'), stripslashes($this->getDatum('name')));
		}
		$mail->setSubject($model->lang('SITE_NAME') .' Contact Form – ' . $this->getDatum('subject'));
		$mail->setMessage($this->getDatum('message'));

		$mail->addTo('team@' . $model->module('preferences')->get('emailDomain'));
		
		if (is_uploaded_file($this->files['attachment']['tmp_name']))
			$mail->addAttachment($this->files['attachment']['tmp_name'], $this->files['attachment']['name'], $this->files['attachment']['type']);

		$smtp->send($mail->getFrom(), $mail->getAllRecipients(), $mail->getData());
		$smtp->quit();
	}
}
?>