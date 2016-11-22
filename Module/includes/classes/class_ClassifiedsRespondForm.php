<?php
class ClassifiedsRespondForm extends PrimordialForm {

	public function displayForm() {
		global $user;
		$content = $this->displayErrors('<p>Sorry, there seems to have been problems with your form:</p>');

		// if user is logged in, use their registered email in the form
		$response_email = $this->getDatum('response_email') == '' ? $user->getEmail() : $this->getDatum('response_email');

		$content .= '<h1>Send A Message</h1>';

		$content .= FormHelper::open('/en/classifieds/respond_proc/', array('file_upload' => true));
		$content .= FormHelper::hidden('classified_id', $this->getDatum('classified_id'));

		$f[] = FormHelper::element('&nbsp;', "<img src=\"/scripts/captcha/generate.php\" width=\"120\" height=\"40\" alt=\"captcha\" />");
		$f[] = FormHelper::input('Security code', 'security_code', '', array('mandatory' => true));
		$f[] = FormHelper::textarea('Your message', 'message', $this->getDatum('message'), array('mandatory' => true));
		$f[] = FormHelper::file('Attachment', 'attachment');

		$content .= FormHelper::fieldset('', $f);
		$content .= FormHelper::submit('Send');
		$content .= FormHelper::close();
		return $content;
	}

	public function processForm() {
		global $model, $user;
		
		if (!$user->isLoggedIn()) $this->addError('You have to be logged in to do this');
		if ($this->getDatum('security_code') != $_SESSION['security_code']) {
			$this->addError('The entered security code was incorrect');
		}
		$exists_validator = new ExistenceValidator($this);
		$exists_validator->validate('message', 'Please enter a message');
		$message = $this->getDatum('message');
		if ($message != strip_tags($message)) $this->addError('Please avoid using HTML in your message');

		if ($this->getErrorCount()) {
			return false;
		}
		
		$ci = new ClassifiedsItem($this->getDatum('classified_id'));
		$recipient = $ci->getUser();
		// build message
		$message = '<b>'. $model->lang('SITE_NAME') ." Classified Advertisement Response</b>\n\n".
		"<b>Your Classified Ad</b>\n".$ci->getTitle()."\n".$ci->getBody()."\n\n".
		"<b>Respondent's Message</b>\n".$this->getDatum('message');
		
		$mailer = new Mailer();
		$mailer->attachUploads();
		$success = $mailer->send(array(
			'subject' => "Re: ".$ci->getTitle()." [".$model->lang('SITE_NAME')."][".$this->getDatum('classified_id')."]",
			'content' => $message,
			'from' => array($user->getEmail() => $user->nickname),
			'to' => array($recipient->getEmail() => trim($recipient->given_name . ' ' . $recipient->family_name)),
			'bcc' => 'bitbucket@'.$model->module('preferences')->get('emailDomain')
		));
		if ($success) {
			$this->setSuccessMessage('Your response has been sent.');
			$ci->incrementResponses();
			return true;
		} else {
			$this->addError('There was a problem with sending your email. Please try again later');
			return false;
		}
	}

	public function setClassifiedID($classified_id) {
		$this->setDatum('classified_id', $classified_id);
	}

	public function setFiles($files) {
		$this->files = $files;
	}
}
?>