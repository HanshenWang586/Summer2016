<?php
class UserProfileForm extends PrimordialForm {

	public function displayForm() {
		global $user;
		
		$this->setData($user->getData());
		
		$content = $this->displayMessages();
		$content .= $this->displayErrors('<p>Sorry, there seems to have been problems with your form:</p>');
		$content .= FormHelper::open('/en/users/edit_proc/');
		$f[] = FormHelper::select(	'Region',
									'area_id',
									RegisterForm::getSelectData(),
									$this->getDatum('area_id'),
									array('mandatory' => true));
		$f[] = FormHelper::input('First name',
								 'given_name',
								 $this->getDatum('given_name'),
								 array('mandatory' => true));

		$f[] = FormHelper::input('Last name',
								 'family_name',
								 $this->getDatum('family_name'),
								 array('mandatory' => true));
		$f[] = FormHelper::input(	'Email',
									'email',
									$this->getDatum('email'),
									array('mandatory' => true,
										'guidetext' => '<strong>Please note that you need to verify your email address if you choose to change it.</strong>'
									)
								);
		$f[] = FormHelper::password('Password',
									'password',
									'',
									array(	'mandatory' => true,
										   'guidetext' => 'Please confirm your password to change your profile information.'));

		$f[] = FormHelper::checkbox('eNews',
									'enews',
									$this->getDatum('enews'),
									array('guidetext' => 'Check if you would like to receive our weekly newsletter.'));
		$f[] = FormHelper::submit();
		$content .= FormHelper::fieldset('', $f);
		$content .= FormHelper::close();
		return $content;
	}

	public function processForm() {
		global $user;
		
		$user->setData(array('family_name' => $this->getDatum('family_name'),
							 'given_name' => $this->getDatum('given_name'),
							 'area_id' => $this->getDatum('area_id'),
							 'enews' => $this->getDatum('enews')));
		$user->saveProfileUpdate();
		
		$this->addMessage('Your profile has been updated.');
		
		$email = $this->getDatum('email');
		if ($email != $user->email) {
			if ($user->requestChangeEmail($email)) {
				$this->addMessage('An email has been sent to "' . $email . '". To active your new email address, please click the link provided in the email.');
			}
		}
	}

	public function setFiles($files) {
		$this->files = $files;
	}
}
?>