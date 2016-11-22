<?php
class ChangePasswordForm extends PrimordialForm {
	
	public function displayForm() {
		$content = $this->displayErrors('');
		$content .= FormHelper::open($this->action);
		$f[] = FormHelper::password('New password', 'pw_1', $this->getDatum('pw_1'), array('mandatory' => true));
		$f[] = FormHelper::password('Repeat password', 'pw_2', $this->getDatum('pw_2'), array('mandatory' => true));
		$f[] = FormHelper::submit();
		$content .= FormHelper::fieldset('', $f);
		$content .= FormHelper::close();
		return $content;
	}

	public function processForm() {
		$db = new DatabaseQuery;
		$db->execute("	UPDATE public_users
						SET password='".$db->clean($this->getDatum('pw_1'))."'
						WHERE user_id=".$db->clean($this->getDatum('user_id')));
	}
}
?>