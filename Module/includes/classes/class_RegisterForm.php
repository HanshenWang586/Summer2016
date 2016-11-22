<?php
class RegisterForm extends PrimordialForm {
	public function displayForm() {
		global $model;

		$content = $this->displayErrors('<p>Sorry, there seems to have been problems with your form:</p>');

		$content .= FormHelper::open('/en/users/proc_register/');

		$f[] = FormHelper::element('&nbsp;', "<img src=\"/scripts/captcha/generate.php\" width=\"120\" height=\"40\" alt=\"captcha\" />");
		$f[] = FormHelper::input('Security code', 'security_code', '', array('mandatory' => true));
		$content .= FormHelper::fieldset('', $f);
		
		$f[] = FormHelper::input('Email',
								 'email',
								 $this->getDatum('email'),
								 array(	'mandatory' => true,
										'guidetext' => 'We require that you provide an email address for responses to classifieds and other '.$model->lang('SITE_NAME').' features. Your email will never be displayed publicly on the '.$model->lang('SITE_NAME').' website.<br><br><strong>Please note that you need to verify your email address to register an account!</strong>'));

		$f[] = FormHelper::input('Nickname',
								 'nickname',
								 $this->getDatum('nickname'),
								 array(	'mandatory' => true,
										'guidetext' => 'Your nickname will be the name you use within '.$model->lang('SITE_NAME').' to post ads or comments on forums. Your nickname should be 5-18 characters.'));

		$f[] = FormHelper::password('Password',
									'password',
									$this->getDatum('password'),
									array(	'mandatory' => true,
										   'guidetext' => 'This is a password for '.$model->lang('SITE_NAME').' that you create during the registration process. Please enter more than 6 characters.'));

		$f[] = FormHelper::password('Repeat password',
									'password2',
									$this->getDatum('password2'),
									array(	'mandatory' => true,
										   'guidetext' => 'Please re-enter the password you entered above.'));

		$content .= FormHelper::fieldset('Your Account', $f);

		$f[] = FormHelper::input('First name',
								 'given_name',
								 $this->getDatum('given_name'),
								 array('mandatory' => true));

		$f[] = FormHelper::input('Last name',
								 'family_name',
								 $this->getDatum('family_name'),
								 array('mandatory' => true));

		$f[] = FormHelper::select('Location',
								  'area_id',
								  $this->getSelectData(),
								  $this->getDatum('area_id'),
								  array('mandatory' => true));

		$content .= FormHelper::fieldset('About You', $f);
		$content .= FormHelper::submit('Submit');
		$content .= FormHelper::close();
		return $content;
	}

	public static function getSelectData() {

		$options[''] = 'Please select...';
		$db = new DatabaseQuery;
		$rs = $db->execute('SELECT *
							FROM gk4_areas
							ORDER BY area_en');

		while ($row = $rs->getRow())
			$options[$row['area_id']] = $row['area_en'];

		return $options;
	}

	public function processForm() {
		$user = new User;
		$user->setNickname($this->getDatum('nickname'));
		$user->setEmail($this->getDatum('email'));
		$user->setPassword($this->getDatum('password'));
		$user->setGivenName($this->getDatum('given_name'));
		$user->setFamilyName($this->getDatum('family_name'));
		$user->setAreaID($this->getDatum('area_id'));
		$user->setIP($_SERVER['REMOTE_ADDR']);
		$user->setSessionID(session_id());
		$user->save();
		return $user;
	}
}
?>